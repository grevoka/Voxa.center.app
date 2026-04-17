<?php

namespace App\Services;

use App\Models\WidgetToken;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WidgetGuestService
{
    private string $connection = 'asterisk';

    /**
     * Provision an ephemeral WebRTC guest endpoint for a widget visitor.
     */
    public function provisionGuest(WidgetToken $widget): array
    {
        $id = 'wgt-' . $widget->getTokenPrefix() . '-' . Str::random(6);
        $password = Str::random(16);

        $targetType = $widget->getTargetType();
        $targetValue = $widget->getTargetValue();
        $widgetName = str_replace([',', '=', '"'], '', $widget->name);
        $setVar = "WIDGET_TOKEN_ID={$widget->id},WIDGET_TARGET_TYPE={$targetType},WIDGET_TARGET={$targetValue},WIDGET_NAME={$widgetName}";

        try {
            DB::connection($this->connection)->transaction(function () use ($id, $password, $setVar) {
                // WebRTC endpoint (same settings as SipProvisioningService for WebRTC lines)
                DB::connection($this->connection)->table('ps_endpoints')->insert([
                    'id'                           => $id,
                    'transport'                    => '',
                    'aors'                         => $id,
                    'auth'                         => $id,
                    'context'                      => 'from-widget',
                    'disallow'                     => 'all',
                    'allow'                        => 'opus,ulaw,alaw',
                    'direct_media'                 => 'no',
                    'force_rport'                  => 'yes',
                    'rewrite_contact'              => 'yes',
                    'rtp_symmetric'                => 'yes',
                    'dtmf_mode'                    => 'auto',
                    'media_encryption'             => 'dtls',
                    'ice_support'                  => 'yes',
                    'rtcp_mux'                     => 'yes',
                    'use_avpf'                     => 'yes',
                    'media_use_received_transport' => 'yes',
                    'dtls_auto_generate_cert'      => 'yes',
                    'dtls_verify'                  => 'no',
                    'dtls_setup'                   => 'actpass',
                    'set_var'                      => $setVar,
                ]);

                DB::connection($this->connection)->table('ps_auths')->insert([
                    'id'        => $id,
                    'auth_type' => 'userpass',
                    'username'  => $id,
                    'password'  => $password,
                ]);

                DB::connection($this->connection)->table('ps_aors')->insert([
                    'id'                  => $id,
                    'max_contacts'        => 1,
                    'remove_existing'     => 'yes',
                    'default_expiration'  => 300, // 5 minutes TTL
                    'qualify_frequency'   => 0,
                ]);
            });

            Log::info('Widget guest provisioned', ['id' => $id, 'widget' => $widget->name]);
        } catch (\Throwable $e) {
            Log::error('Widget guest provisioning failed', ['error' => $e->getMessage()]);
            throw $e;
        }

        return [
            'endpoint_id' => $id,
            'password'    => $password,
        ];
    }

    /**
     * Remove an ephemeral guest endpoint.
     */
    public function deprovisionGuest(string $id): void
    {
        try {
            DB::connection($this->connection)->table('ps_endpoints')->where('id', $id)->delete();
            DB::connection($this->connection)->table('ps_auths')->where('id', $id)->delete();
            DB::connection($this->connection)->table('ps_aors')->where('id', $id)->delete();
            Log::info('Widget guest deprovisioned', ['id' => $id]);
        } catch (\Throwable $e) {
            Log::warning('Widget guest deprovision failed', ['id' => $id, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Clean up stale guest endpoints (no active registration, older than TTL).
     */
    public function cleanupStaleGuests(): int
    {
        $count = 0;

        try {
            // Find all guest endpoints
            $guests = DB::connection($this->connection)
                ->table('ps_endpoints')
                ->where('id', 'like', 'wgt-%')
                ->pluck('id');

            foreach ($guests as $id) {
                // Check if AOR has active contacts
                $hasContact = DB::connection($this->connection)
                    ->table('ps_aors')
                    ->where('id', $id)
                    ->where('qualify_frequency', '>', 0)
                    ->exists();

                // Check for active channels via Asterisk
                $activeChannels = 0;
                exec('sudo /usr/sbin/asterisk -rx ' . escapeshellarg("pjsip show endpoint {$id}") . ' 2>&1', $output);
                $channelLine = collect($output)->first(fn($l) => str_contains($l, 'Channels'));
                if ($channelLine && preg_match('/(\d+) of/', $channelLine, $m)) {
                    $activeChannels = (int) $m[1];
                }

                if ($activeChannels === 0) {
                    $this->deprovisionGuest($id);
                    $count++;
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Widget guest cleanup failed', ['error' => $e->getMessage()]);
        }

        return $count;
    }

    /**
     * Count active guest endpoints for a widget token.
     */
    public function countActiveGuests(WidgetToken $widget): int
    {
        return DB::connection($this->connection)
            ->table('ps_endpoints')
            ->where('id', 'like', 'wgt-' . $widget->getTokenPrefix() . '-%')
            ->count();
    }
}

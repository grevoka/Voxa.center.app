<?php

namespace App\Services;

use App\Models\SipLine;
use App\Models\Trunk;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SipProvisioningService
{
    private string $connection = 'asterisk';

    public function provisionLine(SipLine $line): void
    {
        $id = $line->getAsteriskEndpointId();
        $transportKey = $line->getTransportKey();

        try {
            DB::connection($this->connection)->transaction(function () use ($line, $id, $transportKey) {
                DB::connection($this->connection)->table('ps_endpoints')->updateOrInsert(
                    ['id' => $id],
                    [
                        'transport'       => $transportKey,
                        'aors'            => $id,
                        'auth'            => $id,
                        'context'         => $line->context,
                        'disallow'        => 'all',
                        'allow'           => $line->getCodecsList(),
                        'direct_media'    => 'no',
                        'force_rport'     => 'yes',
                        'rewrite_contact' => 'yes',
                        'rtp_symmetric'   => 'yes',
                        'callerid'        => $line->caller_id
                            ? "\"{$line->name}\" <{$line->caller_id}>"
                            : $line->name,
                        'dtmf_mode'       => 'rfc4733',
                        'media_encryption' => ($line->protocol === 'SIP/TLS' || $line->protocol === 'WebRTC')
                            ? 'sdes' : 'no',
                        'ice_support'     => $line->protocol === 'WebRTC' ? 'yes' : 'no',
                    ]
                );

                DB::connection($this->connection)->table('ps_auths')->updateOrInsert(
                    ['id' => $id],
                    [
                        'auth_type' => 'userpass',
                        'username'  => $line->extension,
                        'password'  => $line->decrypted_secret,
                    ]
                );

                DB::connection($this->connection)->table('ps_aors')->updateOrInsert(
                    ['id' => $id],
                    [
                        'max_contacts'       => $line->max_contacts,
                        'remove_existing'    => 'yes',
                        'default_expiration' => 3600,
                        'qualify_frequency'  => '60',
                    ]
                );
            });

            Log::info("Provisioned SIP line", ['extension' => $line->extension, 'endpoint' => $id]);
        } catch (\Throwable $e) {
            Log::warning("Could not provision SIP line (Asterisk RT tables may not exist)", [
                'extension' => $line->extension,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function deprovisionLine(SipLine $line): void
    {
        $id = $line->getAsteriskEndpointId();

        try {
            DB::connection($this->connection)->table('ps_endpoints')->where('id', $id)->delete();
            DB::connection($this->connection)->table('ps_auths')->where('id', $id)->delete();
            DB::connection($this->connection)->table('ps_aors')->where('id', $id)->delete();
            Log::info("Deprovisioned SIP line", ['extension' => $line->extension]);
        } catch (\Throwable $e) {
            Log::warning("Could not deprovision SIP line", ['error' => $e->getMessage()]);
        }
    }

    public function provisionTrunk(Trunk $trunk): void
    {
        $id = $trunk->getAsteriskEndpointId();
        $authId = $id . '-auth';
        $registrationId = $id . '-reg';

        try {
            DB::connection($this->connection)->transaction(function () use ($trunk, $id, $authId, $registrationId) {
                $codecs = $trunk->codecs ? implode(',', $trunk->codecs) : 'alaw,ulaw';

                DB::connection($this->connection)->table('ps_endpoints')->updateOrInsert(
                    ['id' => $id],
                    [
                        'transport'       => $trunk->getTransportKey(),
                        'aors'            => $id,
                        'auth'            => $authId,
                        'context'         => $trunk->context,
                        'disallow'        => 'all',
                        'allow'           => $codecs,
                        'direct_media'    => 'no',
                        'force_rport'     => 'yes',
                        'rewrite_contact' => 'yes',
                        'callerid'        => $trunk->caller_id ?? '',
                        'from_user'       => $trunk->username ?? '',
                        'from_domain'     => $trunk->host,
                        'dtmf_mode'       => 'rfc4733',
                        'trust_id_inbound' => 'yes',
                    ]
                );

                DB::connection($this->connection)->table('ps_auths')->updateOrInsert(
                    ['id' => $authId],
                    [
                        'auth_type' => 'userpass',
                        'username'  => $trunk->username,
                        'password'  => $trunk->decrypted_secret,
                    ]
                );

                DB::connection($this->connection)->table('ps_aors')->updateOrInsert(
                    ['id' => $id],
                    [
                        'contact'            => "sip:{$trunk->host}:{$trunk->port}",
                        'qualify_frequency'  => '60',
                        'default_expiration' => $trunk->expiration,
                    ]
                );

                if ($trunk->register && $trunk->username) {
                    $regData = [
                        'transport'                => $trunk->getTransportKey(),
                        'outbound_auth'            => $authId,
                        'server_uri'               => $trunk->getServerUri(),
                        'client_uri'               => $trunk->getClientUri(),
                        'retry_interval'           => $trunk->retry_interval,
                        'expiration'               => $trunk->expiration,
                        'contact_user'             => $trunk->username,
                        'auth_rejection_permanent' => 'no',
                    ];

                    // Add outbound proxy if configured (e.g. OVH sip-proxy)
                    if ($trunk->outbound_proxy) {
                        $proxy = $trunk->outbound_proxy;
                        if (!str_starts_with($proxy, 'sip:')) {
                            $proxy = "sip:{$proxy}";
                        }
                        $regData['outbound_proxy'] = $proxy;
                    }

                    DB::connection($this->connection)->table('ps_registrations')->updateOrInsert(
                        ['id' => $registrationId],
                        $regData
                    );
                }
            });

            Log::info("Provisioned trunk", ['name' => $trunk->name, 'endpoint' => $id]);
        } catch (\Throwable $e) {
            Log::warning("Could not provision trunk", ['error' => $e->getMessage()]);
        }

        // Provision inbound endpoint (identify by IP) if IPs are configured
        $this->provisionTrunkInbound($trunk);
    }

    public function provisionTrunkInbound(Trunk $trunk): void
    {
        $inboundId = $trunk->getInboundEndpointId();
        $inboundIps = $trunk->inbound_ips ?? [];

        try {
            // Remove old identify entries for this trunk
            DB::connection($this->connection)->table('ps_endpoint_id_ips')
                ->where('endpoint', $inboundId)->delete();

            if (empty($inboundIps)) {
                // No IPs = no inbound endpoint needed, clean up
                DB::connection($this->connection)->table('ps_endpoints')->where('id', $inboundId)->delete();
                DB::connection($this->connection)->table('ps_aors')->where('id', $inboundId)->delete();
                return;
            }

            $codecs = $trunk->codecs ? implode(',', $trunk->codecs) : 'alaw,ulaw';
            $context = $trunk->getEffectiveInboundContext();

            DB::connection($this->connection)->table('ps_endpoints')->updateOrInsert(
                ['id' => $inboundId],
                [
                    'transport'        => $trunk->getTransportKey(),
                    'context'          => $context,
                    'disallow'         => 'all',
                    'allow'            => $codecs,
                    'direct_media'     => 'no',
                    'force_rport'      => 'yes',
                    'rewrite_contact'  => 'yes',
                    'rtp_symmetric'    => 'yes',
                    'trust_id_inbound' => 'yes',
                    'dtmf_mode'        => 'rfc4733',
                ]
            );

            DB::connection($this->connection)->table('ps_aors')->updateOrInsert(
                ['id' => $inboundId],
                ['max_contacts' => 1, 'qualify_frequency' => 0]
            );

            foreach ($inboundIps as $i => $ip) {
                $ip = trim($ip);
                if (empty($ip)) continue;

                $identifyId = strtolower(preg_replace('/[^a-zA-Z0-9]/', '-', $trunk->name)) . '-ip-' . ($i + 1);
                DB::connection($this->connection)->table('ps_endpoint_id_ips')->insert([
                    'id'       => $identifyId,
                    'endpoint' => $inboundId,
                    'match'    => $ip,
                ]);
            }

            Log::info("Provisioned trunk inbound IPs", ['name' => $trunk->name, 'ips' => $inboundIps]);
        } catch (\Throwable $e) {
            Log::warning("Could not provision trunk inbound", ['error' => $e->getMessage()]);
        }

        // Regenerate static identify config (Realtime IP matching is unreliable)
        $this->writeIdentifyConf();
    }

    /**
     * Rewrite the auto-generated section of /etc/asterisk/pjsip.conf
     * with static endpoint + identify sections for all trunks with inbound IPs.
     * Static config is required because sorcery Realtime does not reliably
     * handle IP identification at runtime, and #tryinclude doesn't work
     * with sorcery's config wizard.
     */
    public function writeIdentifyConf(): void
    {
        $pjsipPath = '/etc/asterisk/pjsip.conf';
        $marker = '; === AUTO-GENERATED TRUNKS BY SIP.ctrl ===';

        try {
            // Read base pjsip.conf (everything before marker)
            $base = '';
            if (file_exists($pjsipPath)) {
                $content = file_get_contents($pjsipPath);
                $pos = strpos($content, $marker);
                $base = $pos !== false ? rtrim(substr($content, 0, $pos)) : rtrim($content);
            }

            $lines = [];
            $trunks = Trunk::whereNotNull('inbound_ips')->get();

            foreach ($trunks as $trunk) {
                $ips = $trunk->inbound_ips ?? [];
                if (empty($ips)) continue;

                $inboundId = $trunk->getInboundEndpointId();

                // Only static identify (endpoint comes from Realtime DB)
                $lines[] = '';
                $lines[] = "[{$inboundId}-identify]";
                $lines[] = 'type = identify';
                $lines[] = "endpoint = {$inboundId}";
                foreach ($ips as $ip) {
                    $ip = trim($ip);
                    if (!empty($ip)) {
                        $lines[] = "match = {$ip}";
                    }
                }
            }

            $output = $base . "\n\n" . $marker . "\n";
            $output .= "; Ne pas editer — gere par SipProvisioningService\n";
            $output .= implode("\n", $lines) . "\n";

            $this->writeAsteriskFile($pjsipPath, $output);

            Log::info('Written pjsip.conf trunk sections', ['trunks' => $trunks->count()]);
        } catch (\Throwable $e) {
            Log::warning('Could not write pjsip.conf trunk sections', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Write a file to /etc/asterisk/ using sudo tee (permission-safe).
     */
    private function writeAsteriskFile(string $path, string $content): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'ast_');
        file_put_contents($tmpFile, $content);

        exec(sprintf(
            'sudo /usr/bin/tee %s < %s > /dev/null 2>&1',
            escapeshellarg($path),
            escapeshellarg($tmpFile)
        ), $out, $code);

        unlink($tmpFile);

        if ($code !== 0) {
            throw new \RuntimeException("Failed to write {$path} via sudo tee (code {$code})");
        }
    }

    public function deprovisionTrunk(Trunk $trunk): void
    {
        $id = $trunk->getAsteriskEndpointId();
        $authId = $id . '-auth';
        $registrationId = $id . '-reg';
        $inboundId = $trunk->getInboundEndpointId();

        try {
            DB::connection($this->connection)->table('ps_endpoints')->where('id', $id)->delete();
            DB::connection($this->connection)->table('ps_auths')->where('id', $authId)->delete();
            DB::connection($this->connection)->table('ps_aors')->where('id', $id)->delete();
            DB::connection($this->connection)->table('ps_registrations')->where('id', $registrationId)->delete();

            // Clean up inbound endpoint + identify IPs
            DB::connection($this->connection)->table('ps_endpoint_id_ips')->where('endpoint', $inboundId)->delete();
            DB::connection($this->connection)->table('ps_endpoints')->where('id', $inboundId)->delete();
            DB::connection($this->connection)->table('ps_aors')->where('id', $inboundId)->delete();

            Log::info("Deprovisioned trunk", ['name' => $trunk->name]);
        } catch (\Throwable $e) {
            Log::warning("Could not deprovision trunk", ['error' => $e->getMessage()]);
        }
    }
}

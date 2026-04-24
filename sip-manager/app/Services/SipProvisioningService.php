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
                $isWebRTC = $line->protocol === 'WebRTC';

                $endpointData = [
                    'transport'       => $isWebRTC ? '' : $transportKey,
                    'aors'            => $id,
                    'auth'            => $id,
                    'context'         => $line->context,
                    'disallow'        => 'all',
                    'allow'           => $isWebRTC ? 'opus,ulaw,alaw' : $line->getCodecsList(),
                    'direct_media'    => 'no',
                    'force_rport'     => 'yes',
                    'rewrite_contact' => 'yes',
                    'rtp_symmetric'   => 'yes',
                    'callerid'        => $line->caller_id
                        ? "\"{$line->name}\" <{$line->caller_id}>"
                        : $line->name,
                    'dtmf_mode'       => $isWebRTC ? 'auto' : 'rfc4733',
                    'media_encryption' => $isWebRTC ? 'dtls'
                        : ($line->protocol === 'SIP/TLS' ? 'sdes' : 'no'),
                    'ice_support'              => $isWebRTC ? 'yes' : 'no',
                    'rtcp_mux'                 => $isWebRTC ? 'yes' : null,
                    'use_avpf'                 => $isWebRTC ? 'yes' : null,
                    'media_use_received_transport' => $isWebRTC ? 'yes' : null,
                    'dtls_auto_generate_cert'  => $isWebRTC ? 'yes' : null,
                    'dtls_verify'              => $isWebRTC ? 'no' : null,
                    'dtls_setup'               => $isWebRTC ? 'actpass' : null,
                    'from_domain'     => \App\Models\SipSetting::get('sip_server', request()?->getHost() ?? ''),
                ];

                DB::connection($this->connection)->table('ps_endpoints')->updateOrInsert(
                    ['id' => $id],
                    $endpointData
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
                        'qualify_frequency'  => $isWebRTC ? '0' : '60',
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
                        'outbound_auth'   => $authId,
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

                // AOR contact: use proxy if configured (domain may not be DNS-resolvable)
                $aorContact = $trunk->outbound_proxy
                    ? "sip:{$trunk->outbound_proxy}:{$trunk->port}"
                    : "sip:{$trunk->host}:{$trunk->port}";

                DB::connection($this->connection)->table('ps_aors')->updateOrInsert(
                    ['id' => $id],
                    [
                        'contact'            => $aorContact,
                        'qualify_frequency'  => '60',
                        'default_expiration' => $trunk->expiration,
                    ]
                );

                // Clean up any legacy Realtime registration
                DB::connection($this->connection)->table('ps_registrations')
                    ->where('id', $registrationId)->delete();
            });

            Log::info("Provisioned trunk", ['name' => $trunk->name, 'endpoint' => $id]);
        } catch (\Throwable $e) {
            Log::warning("Could not provision trunk", ['error' => $e->getMessage()]);
        }

        // Provision inbound endpoint (identify by IP) if IPs are configured
        $this->provisionTrunkInbound($trunk);

        // Always regenerate pjsip.conf: registration sections live there, and
        // provisionTrunkInbound() returns early when inbound_ips is empty.
        $this->writeIdentifyConf();
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
     * with static identify + registration sections for all trunks.
     * Registrations MUST be static because Realtime ODBC registration
     * loading fails on many Asterisk 20 builds (xmldoc/sorcery bug).
     */
    public function writeIdentifyConf(): void
    {
        $pjsipPath = '/etc/asterisk/pjsip.conf';
        $marker = '; === AUTO-GENERATED TRUNKS BY Voxa Center ===';

        try {
            // Read base pjsip.conf (everything before marker)
            $base = '';
            if (file_exists($pjsipPath)) {
                $content = file_get_contents($pjsipPath);
                $pos = strpos($content, $marker);
                $base = $pos !== false ? rtrim(substr($content, 0, $pos)) : rtrim($content);
            }

            $lines = [];
            $allTrunks = Trunk::all();

            // Identify sections (inbound IP matching)
            foreach ($allTrunks as $trunk) {
                $ips = $trunk->inbound_ips ?? [];
                if (empty($ips)) continue;

                $inboundId = $trunk->getInboundEndpointId();
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

            // Registration sections (outbound trunk registration)
            foreach ($allTrunks as $trunk) {
                if (!$trunk->register || !$trunk->username) continue;

                $id = $trunk->getAsteriskEndpointId();
                $authId = $id . '-auth';
                $regId = $id . '-reg';

                $lines[] = '';
                $lines[] = "[{$regId}]";
                $lines[] = 'type = registration';
                $lines[] = "transport = {$trunk->getTransportKey()}";
                $lines[] = "outbound_auth = {$authId}";
                $lines[] = "server_uri = {$trunk->getServerUri()}";
                $lines[] = "client_uri = {$trunk->getClientUri()}";
                $lines[] = "retry_interval = " . ($trunk->retry_interval ?? 60);
                $lines[] = "expiration = " . ($trunk->expiration ?? 3600);
                $lines[] = "contact_user = {$trunk->username}";
                $lines[] = 'auth_rejection_permanent = no';

                if ($trunk->outbound_proxy) {
                    $proxy = preg_replace('#^sip:#', '', $trunk->outbound_proxy);
                    if (!str_contains($proxy, ':')) {
                        $proxy .= ':' . $trunk->port;
                    }
                    // \;lr: \ escapes ; from asterisk conf parser (comment), ;lr tells PJSIP
                    // to treat the URI as a preloaded Route header instead of overriding
                    // the Request-URI (required by Cirpack/OVH SIP-Proxy et al.).
                    $lines[] = "outbound_proxy = sip:{$proxy}\\;lr";
                }
            }

            $output = $base . "\n\n" . $marker . "\n";
            $output .= "; Ne pas editer — gere par SipProvisioningService\n";
            $output .= implode("\n", $lines) . "\n";

            $this->writeAsteriskFile($pjsipPath, $output);

            // Reload PJSIP to pick up registration changes
            exec('sudo /usr/sbin/asterisk -rx "module reload res_pjsip.so" 2>&1');

            Log::info('Written pjsip.conf trunk sections', ['trunks' => $allTrunks->count()]);
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

            // Rewrite pjsip.conf to remove registration + identify
            $this->writeIdentifyConf();

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

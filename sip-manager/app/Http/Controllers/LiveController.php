<?php

namespace App\Http\Controllers;

use App\Models\SipLine;
use App\Models\Trunk;
use Illuminate\Http\Request;

class LiveController extends Controller
{
    public function index()
    {
        return view('live.index');
    }

    /**
     * API: return real-time Asterisk state (channels, endpoints, registrations).
     */
    public function poll()
    {
        return response()->json([
            'channels'      => $this->getChannels(),
            'endpoints'     => $this->getEndpoints(),
            'registrations' => $this->getRegistrations(),
            'timestamp'     => now()->format('H:i:s'),
        ]);
    }

    /**
     * Parse "core show channels verbose" for active calls using column positions.
     */
    private function getChannels(): array
    {
        $output = $this->asteriskCmd('core show channels verbose');
        $channels = [];
        $lines = explode("\n", $output);

        // Find header line to get column positions
        $cols = [];
        $headerLine = '';
        foreach ($lines as $line) {
            if (str_starts_with(trim($line), 'Channel')) {
                $headerLine = $line;
                break;
            }
        }

        if (!$headerLine) return [];

        // Parse column positions from header
        $colNames = ['Channel', 'Context', 'Extension', 'Prio', 'State', 'Application', 'Data', 'CallerID', 'Duration', 'Accountcode', 'PeerAccount', 'BridgeID'];
        foreach ($colNames as $colName) {
            $pos = strpos($headerLine, $colName);
            if ($pos !== false) {
                $cols[$colName] = $pos;
            }
        }

        foreach ($lines as $line) {
            if (!preg_match('/^PJSIP\//', $line)) continue;
            if (strlen($line) < 20) continue;

            $channel    = trim(substr($line, $cols['Channel'] ?? 0, ($cols['Context'] ?? 80) - ($cols['Channel'] ?? 0)));
            $context    = trim(substr($line, $cols['Context'] ?? 0, ($cols['Extension'] ?? 100) - ($cols['Context'] ?? 0)));
            $exten      = trim(substr($line, $cols['Extension'] ?? 0, ($cols['Prio'] ?? 120) - ($cols['Extension'] ?? 0)));
            $state      = isset($cols['State']) ? trim(substr($line, $cols['State'], ($cols['Application'] ?? $cols['State'] + 12) - $cols['State'])) : '';
            $application = isset($cols['Application']) ? trim(substr($line, $cols['Application'], ($cols['Data'] ?? $cols['Application'] + 15) - $cols['Application'])) : '';
            $callerId   = isset($cols['CallerID']) ? trim(substr($line, $cols['CallerID'], ($cols['Duration'] ?? $cols['CallerID'] + 20) - $cols['CallerID'])) : '';
            $duration   = isset($cols['Duration']) ? trim(substr($line, $cols['Duration'], ($cols['Accountcode'] ?? $cols['Duration'] + 12) - $cols['Duration'])) : '';
            $bridgeId   = isset($cols['BridgeID']) ? trim(substr($line, $cols['BridgeID'])) : '';

            // Determine direction
            $direction = 'internal';
            if (str_contains($context, 'outbound') || str_contains($context, 'sortant')) {
                $direction = 'outbound';
            } elseif (str_contains($context, 'from-trunk') || str_contains($context, 'inbound') || str_contains($context, 'from-')) {
                $direction = 'inbound';
            }

            // Extract extension from channel name
            $ext = '';
            if (preg_match('/PJSIP\/([^-]+)/', $channel, $m)) {
                $ext = $m[1];
            }

            // Clean caller ID
            $callerIdClean = preg_replace('/[<>"]/', '', $callerId);

            $channels[] = [
                'channel'     => $channel,
                'extension'   => $ext,
                'context'     => $context,
                'exten'       => $exten,
                'state'       => $state,
                'duration'    => $duration,
                'application' => $application,
                'caller_id'   => $callerIdClean,
                'bridge_id'   => $bridgeId,
                'direction'   => $direction,
            ];
        }

        // Group bridged channels to show caller + callee
        return $this->enrichChannels($channels);
    }

    /**
     * Enrich channels: for inbound calls, show the caller number prominently.
     */
    private function enrichChannels(array $channels): array
    {
        $bridges = [];
        foreach ($channels as &$ch) {
            if (!empty($ch['bridge_id'])) {
                $bridges[$ch['bridge_id']][] = &$ch;
            }
            // For inbound trunk channels, the extension is the trunk name — use caller_id instead
            if ($ch['direction'] === 'inbound' && $ch['caller_id']) {
                $ch['display_number'] = $ch['caller_id'];
                $ch['display_label'] = 'Appelant';
            } elseif ($ch['direction'] === 'outbound') {
                $ch['display_number'] = $ch['exten'] ?: $ch['extension'];
                $ch['display_label'] = 'Destination';
            } else {
                $ch['display_number'] = $ch['extension'];
                $ch['display_label'] = 'Poste';
            }
        }
        unset($ch);

        // For bridged calls, add the connected party info
        foreach ($bridges as $bid => $group) {
            if (count($group) === 2) {
                foreach ($channels as &$ch) {
                    if ($ch['bridge_id'] !== $bid) continue;
                    $other = ($group[0]['channel'] === $ch['channel']) ? $group[1] : $group[0];
                    $ch['connected_to'] = $other['caller_id'] ?: $other['extension'];
                    $ch['connected_channel'] = $other['channel'];
                }
                unset($ch);
            }
        }

        return $channels;
    }

    /**
     * Parse "pjsip show contacts" for endpoint registration status.
     */
    private function getEndpoints(): array
    {
        $output = $this->asteriskCmd('pjsip show contacts');
        $endpoints = [];
        $lines = explode("\n", $output);

        foreach ($lines as $line) {
            // Match contact lines:  1001/sip:1001@192.168.1.x:5060  ... Avail  ...
            if (!preg_match('/^\s*(\S+)\/sip:(\S+)\s+\S+\s+(\w+)/', $line, $m)) continue;

            $ext = $m[1];
            $contact = $m[2];
            $status = strtolower($m[3]);

            // Look up SipLine for display name
            $sipLine = SipLine::where('extension', $ext)->first();

            $endpoints[$ext] = [
                'extension' => $ext,
                'name'      => $sipLine->name ?? $ext,
                'contact'   => $contact,
                'status'    => $status === 'avail' ? 'online' : 'offline',
                'ip'        => preg_match('/@([\d.]+)/', $contact, $ip) ? $ip[1] : '',
            ];
        }

        // Add offline lines that didn't show up
        $allLines = SipLine::orderBy('extension')->get();
        foreach ($allLines as $line) {
            if (!isset($endpoints[$line->extension])) {
                $endpoints[$line->extension] = [
                    'extension' => $line->extension,
                    'name'      => $line->name ?: $line->extension,
                    'contact'   => '',
                    'status'    => 'offline',
                    'ip'        => '',
                ];
            }
        }

        return array_values($endpoints);
    }

    /**
     * Parse "pjsip show registrations" for trunk status.
     */
    private function getRegistrations(): array
    {
        $output = $this->asteriskCmd('pjsip show registrations');
        $regs = [];
        $lines = explode("\n", $output);

        foreach ($lines as $line) {
            if (!preg_match('/^\s*(\S+)\s+(\S+)\s+(\w+)/', $line, $m)) continue;
            if ($m[1] === 'Registration') continue; // header

            $regs[] = [
                'name'   => $m[1],
                'server' => $m[2],
                'status' => strtolower($m[3]),
            ];
        }

        return $regs;
    }

    private function asteriskCmd(string $cmd): string
    {
        $output = [];
        exec('sudo /usr/sbin/asterisk -rx ' . escapeshellarg($cmd) . ' 2>&1', $output);
        return implode("\n", $output);
    }
}

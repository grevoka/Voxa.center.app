<?php

namespace App\Http\Controllers;

use App\Models\FirewallRule;
use App\Models\SipSetting;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FirewallController extends Controller
{
    public function index()
    {
        $whitelist = FirewallRule::where('type', 'whitelist')->orderBy('label')->get();
        $blacklist = FirewallRule::where('type', 'blacklist')->orderBy('created_at', 'desc')->get();
        $firewallMode = SipSetting::get('firewall_mode', 'fail2ban');
        $banned = $this->getFail2banBanned();

        return view('firewall.index', compact('whitelist', 'blacklist', 'firewallMode', 'banned'));
    }

    public function setMode(Request $request)
    {
        $mode = $request->validate(['mode' => 'required|in:whitelist,fail2ban,off'])['mode'];
        SipSetting::set('firewall_mode', $mode, 'firewall', 'string');

        $this->applyFirewallRules();
        ActivityLog::log('Firewall mode', "Mode: {$mode}", 'info');

        $labels = ['whitelist' => 'Whitelist active', 'fail2ban' => 'Fail2Ban uniquement', 'off' => 'Firewall desactive'];
        return back()->with('success', $labels[$mode]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'ip_range' => ['required', 'string', 'max:45', 'regex:/^[0-9a-f.:\/]+$/i'],
            'label'    => 'nullable|string|max:100',
            'type'     => 'required|in:whitelist,blacklist',
        ]);
        $data['created_by'] = auth()->id();

        FirewallRule::firstOrCreate(
            ['ip_range' => $data['ip_range'], 'type' => $data['type']],
            $data
        );

        $this->applyFirewallRules();
        ActivityLog::log('Firewall modifie', "Ajout {$data['type']}: {$data['ip_range']}", 'info');

        return back()->with('success', "IP {$data['ip_range']} ajoutee.");
    }

    public function toggle(FirewallRule $rule)
    {
        $rule->update(['enabled' => !$rule->enabled]);
        $this->applyFirewallRules();

        $label = $rule->enabled ? 'activee' : 'desactivee';
        return back()->with('success', "Regle {$rule->ip_range} {$label}.");
    }

    public function destroy(FirewallRule $rule)
    {
        $ip = $rule->ip_range;
        $rule->delete();
        $this->applyFirewallRules();
        ActivityLog::log('Firewall modifie', "Suppression: {$ip}", 'info');

        return back()->with('success', "Regle {$ip} supprimee.");
    }

    public function unban(Request $request)
    {
        $ip = $request->validate(['ip' => 'required|string|max:45'])['ip'];
        exec("fail2ban-client set asterisk-sip unbanip " . escapeshellarg($ip) . " 2>&1");

        return back()->with('success', "IP {$ip} debannie.");
    }

    /**
     * Apply firewall rules based on current mode.
     */
    public function applyFirewallRules(): void
    {
        $mode = SipSetting::get('firewall_mode', 'fail2ban');

        // Always flush our custom chains
        $cmds = [];
        $cmds[] = "iptables -F SIP_FILTER 2>/dev/null; iptables -N SIP_FILTER 2>/dev/null";
        $cmds[] = "iptables -D INPUT -p udp --dport 5060 -j SIP_FILTER 2>/dev/null";
        $cmds[] = "iptables -D INPUT -p tcp --dport 5060 -j SIP_FILTER 2>/dev/null";
        $cmds[] = "iptables -D INPUT -p udp --dport 5061 -j SIP_FILTER 2>/dev/null";
        $cmds[] = "iptables -D INPUT -p tcp --dport 5061 -j SIP_FILTER 2>/dev/null";

        if ($mode === 'off') {
            // No filtering, just flush
            $this->runCommands($cmds);
            return;
        }

        // Blacklist always applied
        $blacklist = FirewallRule::where('type', 'blacklist')->where('enabled', true)->pluck('ip_range');
        foreach ($blacklist as $ip) {
            $cmds[] = "iptables -A SIP_FILTER -s " . escapeshellarg($ip) . " -j DROP";
        }

        if ($mode === 'whitelist') {
            // Whitelist: allow only listed IPs, drop the rest
            $whitelist = FirewallRule::where('type', 'whitelist')->where('enabled', true)->pluck('ip_range');
            foreach ($whitelist as $ip) {
                $cmds[] = "iptables -A SIP_FILTER -s " . escapeshellarg($ip) . " -j ACCEPT";
            }
            if ($whitelist->isNotEmpty()) {
                $cmds[] = "iptables -A SIP_FILTER -j DROP";
            }
        }
        // fail2ban mode: blacklist applied above, rest is allowed (fail2ban handles banning)

        // Insert jump rules
        $cmds[] = "iptables -I INPUT -p udp --dport 5060 -j SIP_FILTER";
        $cmds[] = "iptables -I INPUT -p tcp --dport 5060 -j SIP_FILTER";
        $cmds[] = "iptables -I INPUT -p udp --dport 5061 -j SIP_FILTER";
        $cmds[] = "iptables -I INPUT -p tcp --dport 5061 -j SIP_FILTER";

        $this->runCommands($cmds);
    }

    private function runCommands(array $cmds): void
    {
        $script = "#!/bin/bash\n" . implode("\n", $cmds) . "\n";
        $tmp = tempnam(sys_get_temp_dir(), 'fw_');
        file_put_contents($tmp, $script);
        exec("bash " . escapeshellarg($tmp) . " 2>&1", $out, $code);
        unlink($tmp);

        if ($code !== 0) {
            Log::warning('Firewall apply failed', ['output' => implode("\n", $out)]);
        }
    }

    private function getFail2banBanned(): array
    {
        $banned = [];
        exec("fail2ban-client status asterisk-sip 2>/dev/null", $lines);
        foreach ($lines as $line) {
            if (preg_match('/Banned IP list:\s*(.+)/', $line, $m)) {
                $banned = array_filter(array_map('trim', explode(' ', $m[1])));
            }
        }
        return $banned;
    }
}

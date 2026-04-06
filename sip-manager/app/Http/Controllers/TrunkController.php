<?php

namespace App\Http\Controllers;

use App\Models\Trunk;
use App\Models\ActivityLog;
use App\Http\Requests\TrunkRequest;
use App\Services\SipProvisioningService;

class TrunkController extends Controller
{
    public function __construct(
        private SipProvisioningService $provisioning,
    ) {}

    public function index()
    {
        $trunks = Trunk::latest()->paginate(25);
        $registrations = $this->getRegistrations();
        return view('trunks.index', compact('trunks', 'registrations'));
    }

    /**
     * Parse "pjsip show registrations" for live trunk status.
     */
    private function getRegistrations(): array
    {
        $regs = [];
        try {
            $output = [];
            exec('sudo /usr/sbin/asterisk -rx ' . escapeshellarg('pjsip show registrations') . ' 2>&1', $output);
            $text = implode("\n", $output);

            foreach (explode("\n", $text) as $line) {
                if (!preg_match('/^\s*(\S+)\s+(\S+)\s+(\w+)/', $line, $m)) continue;
                if ($m[1] === 'Registration') continue;
                // Extract name before "/" (e.g. "trunk-ovh-sip-reg/sip:host:5060" -> "trunk-ovh-sip-reg")
                $name = strtolower(explode('/', $m[1])[0]);
                $regs[$name] = strtolower($m[3]);
            }
        } catch (\Throwable $e) {
            // Silently fail — will show DB status as fallback
        }
        return $regs;
    }

    public function create()
    {
        $codecs = config('asterisk.codecs');
        return view('trunks.create', compact('codecs'));
    }

    public function store(TrunkRequest $request)
    {
        $data = $request->validated();
        $data['inbound_ips'] = $this->parseInboundIps($request->input('inbound_ips_text'));
        $data['created_by'] = auth()->id();

        $trunk = Trunk::create($data);

        $this->provisioning->provisionTrunk($trunk);

        ActivityLog::log('Trunk créé', "{$trunk->name} → {$trunk->host}:{$trunk->port}", 'success', $trunk);

        return redirect()->route('trunks.index')
            ->with('success', "Trunk \"{$trunk->name}\" créé avec succès.");
    }

    public function edit(Trunk $trunk)
    {
        $codecs = config('asterisk.codecs');
        return view('trunks.edit', compact('trunk', 'codecs'));
    }

    public function update(TrunkRequest $request, Trunk $trunk)
    {
        $data = $request->validated();
        $data['inbound_ips'] = $this->parseInboundIps($request->input('inbound_ips_text'));
        if (empty($data['secret'])) {
            unset($data['secret']);
        }
        $trunk->update($data);

        $this->provisioning->provisionTrunk($trunk);

        ActivityLog::log('Trunk modifié', $trunk->name, 'info', $trunk);

        return redirect()->route('trunks.index')
            ->with('success', "Trunk \"{$trunk->name}\" mis à jour.");
    }

    public function destroy(Trunk $trunk)
    {
        $this->provisioning->deprovisionTrunk($trunk);

        $name = $trunk->name;
        $trunk->delete();

        ActivityLog::log('Trunk supprimé', $name, 'warning');

        return redirect()->route('trunks.index')
            ->with('success', "Trunk \"{$name}\" supprimé.");
    }

    private function parseInboundIps(?string $text): array
    {
        if (empty($text)) return [];

        return array_values(array_filter(
            array_map('trim', explode("\n", $text)),
            fn ($line) => $line !== ''
        ));
    }

    public function toggle(Trunk $trunk)
    {
        $newStatus = $trunk->status === 'online' ? 'offline' : 'online';
        $regId = $trunk->getAsteriskEndpointId() . '-reg';

        if ($newStatus === 'offline') {
            exec('sudo /usr/sbin/asterisk -rx ' . escapeshellarg("pjsip send unregister {$regId}") . ' 2>&1');
        } else {
            exec('sudo /usr/sbin/asterisk -rx ' . escapeshellarg("pjsip send register {$regId}") . ' 2>&1');
        }

        $trunk->update(['status' => $newStatus]);

        $message = $newStatus === 'online'
            ? "Trunk \"{$trunk->name}\" : registration en cours…"
            : "Trunk \"{$trunk->name}\" : desinscription en cours…";
        return back()->with('success', $message)->with('trunk_toggled', true);
    }
}

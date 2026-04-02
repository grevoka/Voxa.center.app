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
        return view('trunks.index', compact('trunks'));
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
        $trunk->update([
            'status' => $trunk->status === 'online' ? 'offline' : 'online',
        ]);

        return back()->with('success', "Trunk \"{$trunk->name}\" : {$trunk->status}");
    }
}

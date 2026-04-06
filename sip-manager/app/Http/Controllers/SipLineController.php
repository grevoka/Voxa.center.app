<?php

namespace App\Http\Controllers;

use App\Models\SipLine;
use App\Models\Trunk;
use App\Models\ActivityLog;
use App\Http\Requests\SipLineRequest;
use App\Services\SipProvisioningService;

class SipLineController extends Controller
{
    public function __construct(
        private SipProvisioningService $provisioning,
    ) {}

    public function index()
    {
        $lines = SipLine::latest()->paginate(25);
        return view('lines.index', compact('lines'));
    }

    public function create()
    {
        $codecs     = config('asterisk.codecs');
        $transports = config('asterisk.transports');
        $trunks     = Trunk::orderBy('name')->get();
        return view('lines.create', compact('codecs', 'transports', 'trunks'));
    }

    public function store(SipLineRequest $request)
    {
        $line = SipLine::create([
            ...$request->validated(),
            'created_by' => auth()->id(),
        ]);

        $this->provisioning->provisionLine($line);

        ActivityLog::log('Ligne créée', "Extension {$line->extension} — {$line->name}", 'success', $line);

        return redirect()->route('lines.index')
            ->with('success', "Ligne {$line->extension} créée avec succès.");
    }

    public function edit(SipLine $line)
    {
        $codecs     = config('asterisk.codecs');
        $transports = config('asterisk.transports');
        $trunks     = Trunk::orderBy('name')->get();
        return view('lines.edit', compact('line', 'codecs', 'transports', 'trunks'));
    }

    public function update(SipLineRequest $request, SipLine $line)
    {
        $data = $request->validated();
        if (empty($data['secret'])) {
            unset($data['secret']);
        }
        $line->update($data);

        $this->provisioning->provisionLine($line);

        ActivityLog::log('Ligne modifiée', "Extension {$line->extension}", 'info', $line);

        return redirect()->route('lines.index')
            ->with('success', "Ligne {$line->extension} mise à jour.");
    }

    public function destroy(SipLine $line)
    {
        $this->provisioning->deprovisionLine($line);

        $ext = $line->extension;
        $line->delete();

        ActivityLog::log('Ligne supprimée', "Extension {$ext}", 'warning');

        return redirect()->route('lines.index')
            ->with('success', "Ligne {$ext} supprimée.");
    }

    public function toggle(SipLine $line)
    {
        $line->update([
            'status' => $line->status === 'online' ? 'offline' : 'online',
        ]);

        ActivityLog::log(
            'Statut modifié',
            "Extension {$line->extension} → {$line->status}",
            'info',
            $line,
        );

        return back()->with('success', "Ligne {$line->extension} : {$line->status}");
    }
}

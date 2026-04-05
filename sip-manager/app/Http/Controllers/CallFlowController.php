<?php

namespace App\Http\Controllers;

use App\Models\CallFlow;
use App\Models\CallFlowTemplate;
use App\Models\CallQueue;
use App\Models\Trunk;
use App\Models\SipLine;
use App\Services\DialplanService;
use Illuminate\Http\Request;

class CallFlowController extends Controller
{
    public function __construct(private DialplanService $dialplan) {}

    public function index()
    {
        $flows = CallFlow::with('trunk')->latest()->paginate(25);
        return view('callflows.index', compact('flows'));
    }

    public function create(Request $request)
    {
        $trunks = Trunk::orderBy('name')->get();
        $queues = CallQueue::where('enabled', true)->orderBy('name')->get();
        $lines = SipLine::orderBy('extension')->get();
        $templates = CallFlowTemplate::orderByDesc('is_system')->orderBy('name')->get();

        // If a template was chosen, pre-fill
        $templateSteps = null;
        if ($request->filled('template')) {
            $tpl = CallFlowTemplate::find($request->input('template'));
            if ($tpl) {
                $templateSteps = $tpl->steps;
            }
        }

        return view('callflows.builder', compact('trunks', 'queues', 'lines', 'templates', 'templateSteps'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'            => 'required|string|max:100|unique:call_flows',
            'description'     => 'nullable|string|max:500',
            'trunk_id'        => 'required|exists:trunks,id',
            'inbound_context' => 'required|string|max:50',
            'steps'           => 'required|json',
            'enabled'         => 'boolean',
            'priority'        => 'nullable|integer|min:1|max:100',
        ]);

        $data['steps'] = json_decode($data['steps'], true);
        $data['created_by'] = auth()->id();
        $data['enabled'] = $request->boolean('enabled', true);

        $flow = CallFlow::create($data);

        // Write to Asterisk extensions.conf + reload
        $this->dialplan->writeExtensions();

        return redirect()->route('callflows.edit', $flow)
            ->with('success', "Scenario \"{$flow->name}\" cree et dialplan applique.");
    }

    public function edit(CallFlow $callflow)
    {
        $trunks = Trunk::orderBy('name')->get();
        $queues = CallQueue::where('enabled', true)->orderBy('name')->get();
        $lines = SipLine::orderBy('extension')->get();

        return view('callflows.builder', compact('callflow', 'trunks', 'queues', 'lines'));
    }

    public function update(Request $request, CallFlow $callflow)
    {
        $data = $request->validate([
            'name'            => 'required|string|max:100|unique:call_flows,name,' . $callflow->id,
            'description'     => 'nullable|string|max:500',
            'trunk_id'        => 'required|exists:trunks,id',
            'inbound_context' => 'required|string|max:50',
            'steps'           => 'required|json',
            'enabled'         => 'boolean',
            'priority'        => 'nullable|integer|min:1|max:100',
        ]);

        $data['steps'] = json_decode($data['steps'], true);
        $data['enabled'] = $request->boolean('enabled', true);

        $callflow->update($data);

        // Rewrite Asterisk dialplan
        $this->dialplan->writeExtensions();

        return redirect()->route('callflows.edit', $callflow)
            ->with('success', "Scenario \"{$callflow->name}\" mis a jour et dialplan applique.");
    }

    public function destroy(CallFlow $callflow)
    {
        $name = $callflow->name;
        $callflow->delete();

        // Rewrite dialplan without this flow
        $this->dialplan->writeExtensions();

        return redirect()->route('callflows.index')
            ->with('success', "Scenario \"{$name}\" supprime et dialplan mis a jour.");
    }

    public function toggle(CallFlow $callflow)
    {
        $callflow->update(['enabled' => !$callflow->enabled]);

        // Rewrite dialplan (active/inactive change)
        $this->dialplan->writeExtensions();

        return back()->with('success', "Scenario \"{$callflow->name}\" " . ($callflow->enabled ? 'active' : 'desactive') . ' — dialplan recharge.');
    }

    public function dialplan(CallFlow $callflow)
    {
        $dialplan = $callflow->toDialplan();
        return view('callflows.dialplan', compact('callflow', 'dialplan'));
    }

    public function saveTemplate(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'steps'       => 'required|json',
        ]);

        CallFlowTemplate::create([
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'icon'        => 'bi-bookmark',
            'steps'       => json_decode($data['steps'], true),
            'is_system'   => false,
            'created_by'  => auth()->id(),
        ]);

        return back()->with('success', "Template \"{$data['name']}\" sauvegarde.");
    }

    public function deleteTemplate(CallFlowTemplate $template)
    {
        if ($template->is_system) {
            return back()->with('error', 'Impossible de supprimer un template systeme.');
        }
        $template->delete();
        return back()->with('success', 'Template supprime.');
    }

    public function preview(Request $request)
    {
        $steps = json_decode($request->input('steps', '[]'), true);
        $trunkId = $request->input('trunk_id');
        $context = $request->input('inbound_context', 'from-trunk');

        $flow = new CallFlow([
            'name' => 'preview',
            'trunk_id' => $trunkId,
            'inbound_context' => $context,
            'steps' => $steps,
        ]);
        $flow->setRelation('trunk', Trunk::find($trunkId) ?? new Trunk(['name' => 'unknown']));

        return response()->json(['dialplan' => $flow->toDialplan()]);
    }
}

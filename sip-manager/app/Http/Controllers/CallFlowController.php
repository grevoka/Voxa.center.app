<?php

namespace App\Http\Controllers;

use App\Models\CallFlow;
use App\Models\CallFlowTemplate;
use App\Models\CallerId;
use App\Models\CallQueue;
use App\Models\Trunk;
use App\Models\SipLine;
use App\Models\AudioFile;
use App\Services\DialplanService;
use Illuminate\Http\Request;

class CallFlowController extends Controller
{
    public function __construct(private DialplanService $dialplan) {}

    /**
     * Extract DID numbers used in did_filter blocks of a scenario's steps.
     * Returns empty array if no DID filter → means "ALL".
     */
    private function extractDidsFromSteps(array $steps): array
    {
        $dids = [];
        foreach ($steps as $step) {
            if (($step['type'] ?? '') === 'did_filter' && !empty($step['match_number'])) {
                $dids[] = $step['match_number'];
            }
        }
        return $dids;
    }

    /**
     * Check for DID conflicts with other enabled scenarios on the same trunk.
     */
    private function checkDidConflicts(int $trunkId, array $steps, ?int $excludeFlowId = null, ?array $columnDidFilter = null): ?string
    {
        // DIDs come from two sources: visual `did_filter` steps AND the flow's
        // column-level `did_filter`. For the flow being saved, prefer the
        // about-to-be-persisted value (caller passes it); otherwise fall back
        // to whatever is currently in DB.
        $selfCol = $columnDidFilter;
        if ($selfCol === null && $excludeFlowId) {
            $selfCol = CallFlow::find($excludeFlowId)?->did_filter ?? [];
        }
        $myDids = array_values(array_unique(array_merge(
            $this->extractDidsFromSteps($steps),
            $selfCol ?? []
        )));
        $hasDidFilter = !empty($myDids);

        $otherFlows = CallFlow::where('trunk_id', $trunkId)
            ->where('enabled', true)
            ->when($excludeFlowId, fn($q) => $q->where('id', '!=', $excludeFlowId))
            ->get();

        foreach ($otherFlows as $other) {
            $otherDids = array_values(array_unique(array_merge(
                $this->extractDidsFromSteps($other->steps ?? []),
                $other->did_filter ?? []
            )));
            $otherHasFilter = !empty($otherDids);

            if (!$hasDidFilter && !$otherHasFilter) {
                // Both catch ALL → conflict
                return "Conflit: le scenario \"{$other->name}\" utilise deja ce trunk sans filtre DID. Ajoutez un bloc DID Filter pour distinguer les scenarios.";
            }
            if (!$hasDidFilter && $otherHasFilter) {
                // New = ALL, other = specific → new would catch other's DIDs too
                return "Conflit: le scenario \"{$other->name}\" filtre sur le(s) DID " . implode(', ', $otherDids) . ". Votre scenario sans filtre DID intercepterait tous les appels, y compris ceux-la.";
            }
            if ($hasDidFilter && !$otherHasFilter) {
                // New = specific, other = ALL → other already catches everything
                return "Conflit: le scenario \"{$other->name}\" capte deja tous les DID sur ce trunk. Ajoutez-lui un bloc DID Filter avant de creer un nouveau scenario.";
            }
            // Both have specific DIDs → check overlap
            $overlap = array_intersect($myDids, $otherDids);
            if (!empty($overlap)) {
                return "Conflit: le DID " . implode(', ', $overlap) . " est deja utilise par le scenario \"{$other->name}\".";
            }
        }

        return null; // No conflict
    }

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
        $audioFiles = AudioFile::orderBy('name')->get();

        // If a template was chosen, pre-fill
        $templateSteps = null;
        if ($request->filled('template')) {
            $tpl = CallFlowTemplate::find($request->input('template'));
            if ($tpl) {
                $templateSteps = $tpl->steps;
            }
        }

        $callerIds = CallerId::where('is_active', true)->with('trunk')->orderBy('label')->get();

        return view('callflows.builder', compact('trunks', 'queues', 'lines', 'templates', 'templateSteps', 'audioFiles', 'callerIds'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'            => 'required|string|max:100|unique:call_flows',
            'description'     => 'nullable|string|max:500',
            'trunk_id'        => 'required|exists:trunks,id',
            'inbound_context' => 'required|string|max:50',
            'steps'           => 'required|json',
            'enabled'            => 'nullable|boolean',
            'record_calls'       => 'nullable|boolean',
            'record_optout'      => 'nullable|boolean',
            'record_optout_key'  => 'nullable|string|size:1|regex:/^[0-9*#]$/',
            'caller_id_filter'   => 'nullable|json',
            'did_filter'         => 'nullable|json',
            'priority'           => 'nullable|integer|min:1|max:100',
            'positions'          => 'nullable|json',
            'queue_members'      => 'nullable|string',
        ]);

        $data['steps'] = json_decode($data['steps'], true);
        $data['positions'] = !empty($data['positions']) ? json_decode($data['positions'], true) : null;
        $data['caller_id_filter'] = !empty($data['caller_id_filter']) ? json_decode($data['caller_id_filter'], true) : null;
        $data['did_filter'] = !empty($data['did_filter']) ? json_decode($data['did_filter'], true) : null;
        $data['created_by'] = auth()->id();
        $data['enabled'] = $request->boolean('enabled', true);
        $data['record_calls'] = $request->boolean('record_calls');
        $data['record_optout'] = $request->boolean('record_optout');
        $data['record_optout_key'] = $request->input('record_optout_key') ?? '8';

        // Auto-create queue if wizard sent members
        if ($request->filled('queue_members')) {
            $members = array_filter(explode(',', $request->input('queue_members')));
            if (!empty($members)) {
                $queueName = 'q-' . preg_replace('/[^a-z0-9-]/', '', strtolower($data['name']));
                $queue = CallQueue::updateOrCreate(
                    ['name' => $queueName],
                    [
                        'display_name' => 'File ' . $data['name'],
                        'strategy'     => 'ringall',
                        'timeout'      => 25,
                        'retry'        => 5,
                        'max_wait_time' => 300,
                        'music_on_hold' => 'default',
                        'members'      => collect($members)->map(fn($ext) => ['extension' => $ext, 'penalty' => 0])->all(),
                        'enabled'      => true,
                        'created_by'   => auth()->id(),
                    ],
                );

                // Replace queue_name in steps with the auto-created queue
                foreach ($data['steps'] as &$step) {
                    if (($step['type'] ?? '') === 'queue') {
                        $step['queue_name'] = $queueName;
                    }
                }
                unset($step);

                // Write queues.conf
                $this->dialplan->writeQueues();
            }
        }
        unset($data['queue_members']);

        // Check for DID conflicts — if conflict, create disabled + warning
        $conflict = $this->checkDidConflicts((int) $data['trunk_id'], $data['steps'], null, $data['did_filter']);
        if ($conflict) {
            $data['enabled'] = false;
        }

        $flow = CallFlow::create($data);

        // Write to Asterisk extensions.conf + reload
        $this->dialplan->writeExtensions();

        if ($conflict) {
            return redirect()->route('callflows.edit', $flow)
                ->with('warning', $conflict . ' Le scenario a ete cree mais desactive.');
        }

        return redirect()->route('callflows.edit', $flow)
            ->with('success', "Scenario \"{$flow->name}\" cree et dialplan applique.");
    }

    public function edit(CallFlow $callflow)
    {
        $trunks = Trunk::orderBy('name')->get();
        $queues = CallQueue::where('enabled', true)->orderBy('name')->get();
        $lines = SipLine::orderBy('extension')->get();
        $templates = CallFlowTemplate::orderByDesc('is_system')->orderBy('name')->get();
        $audioFiles = AudioFile::orderBy('name')->get();

        $callerIds = CallerId::where('is_active', true)->with('trunk')->orderBy('label')->get();

        return view('callflows.builder', compact('callflow', 'trunks', 'queues', 'lines', 'templates', 'audioFiles', 'callerIds'));
    }

    public function update(Request $request, CallFlow $callflow)
    {
        $data = $request->validate([
            'name'            => 'required|string|max:100|unique:call_flows,name,' . $callflow->id,
            'description'     => 'nullable|string|max:500',
            'trunk_id'        => 'required|exists:trunks,id',
            'inbound_context' => 'required|string|max:50',
            'steps'           => 'required|json',
            'enabled'            => 'nullable|boolean',
            'record_calls'       => 'nullable|boolean',
            'record_optout'      => 'nullable|boolean',
            'record_optout_key'  => 'nullable|string|size:1|regex:/^[0-9*#]$/',
            'caller_id_filter'   => 'nullable|json',
            'did_filter'         => 'nullable|json',
            'positions'          => 'nullable|json',
            'priority'           => 'nullable|integer|min:1|max:100',
        ]);

        $data['steps'] = json_decode($data['steps'], true);
        $data['positions'] = !empty($data['positions']) ? json_decode($data['positions'], true) : null;
        $data['caller_id_filter'] = !empty($data['caller_id_filter']) ? json_decode($data['caller_id_filter'], true) : null;
        $data['did_filter'] = !empty($data['did_filter']) ? json_decode($data['did_filter'], true) : null;
        $data['enabled'] = $request->boolean('enabled', true);
        $data['record_calls'] = $request->boolean('record_calls');
        $data['record_optout'] = $request->boolean('record_optout');
        $data['record_optout_key'] = $request->input('record_optout_key') ?? '8';

        // Check for DID conflicts — if conflict, force disable + warning
        $conflict = $this->checkDidConflicts((int) $data['trunk_id'], $data['steps'], $callflow->id, $data['did_filter']);
        if ($conflict && $request->boolean('enabled', true)) {
            $data['enabled'] = false;
        }

        $callflow->update($data);

        // Rewrite Asterisk dialplan
        $this->dialplan->writeExtensions();

        if ($conflict && !$callflow->enabled) {
            return redirect()->route('callflows.edit', $callflow)
                ->with('warning', $conflict . ' Le scenario a ete desactive.');
        }

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
        // Check for DID conflicts when enabling
        if (!$callflow->enabled) {
            $conflict = $this->checkDidConflicts($callflow->trunk_id, $callflow->steps ?? [], $callflow->id);
            if ($conflict) {
                return back()->with('error', $conflict);
            }
        }

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
            'record_calls' => $request->boolean('record_calls'),
            'record_optout' => $request->boolean('record_optout'),
            'record_optout_key' => $request->input('record_optout_key', '8'),
        ]);
        $flow->setRelation('trunk', Trunk::find($trunkId) ?? new Trunk(['name' => 'unknown']));

        return response()->json(['dialplan' => $flow->toDialplan()]);
    }
}

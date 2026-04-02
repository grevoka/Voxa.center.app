<?php

namespace App\Http\Controllers;

use App\Models\CallContext;
use App\Models\Trunk;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class CallContextController extends Controller
{
    private function validationRules(?CallContext $context = null): array
    {
        $uniqueRule = $context
            ? "unique:call_contexts,name,{$context->id}"
            : 'unique:call_contexts';

        return [
            'name'               => "required|string|max:50|regex:/^[a-z0-9_-]+$/|{$uniqueRule}",
            'direction'          => 'required|in:inbound,outbound,internal',
            'description'        => 'nullable|string|max:255',
            'dial_pattern'       => 'nullable|string|max:100',
            'destination'        => 'nullable|string|max:100',
            'destination_type'   => 'required|in:extensions,trunk,ivr,queue',
            'trunk_id'           => 'nullable|exists:trunks,id',
            'caller_id_override' => 'nullable|string|max:50',
            'prefix_strip'       => 'nullable|string|max:10',
            'prefix_add'         => 'nullable|string|max:20',
            'timeout'            => 'required|integer|min:5|max:120',
            'ring_timeout'       => 'required|integer|min:5|max:120',
            'record_calls'       => 'boolean',
            'voicemail_enabled'  => 'boolean',
            'voicemail_box'      => 'nullable|string|max:50',
            'greeting_sound'     => 'nullable|string|max:100',
            'music_on_hold'      => 'nullable|string|max:50',
            'enabled'            => 'boolean',
            'priority'           => 'required|integer|min:1|max:99',
            'notes'              => 'nullable|string',
        ];
    }

    private function validationMessages(): array
    {
        return [
            'name.regex'  => 'Le nom doit contenir uniquement des lettres minuscules, chiffres, tirets et underscores.',
            'name.unique' => 'Ce nom de contexte existe deja.',
        ];
    }

    public function index()
    {
        $contexts = CallContext::orderBy('priority')->orderBy('name')->paginate(25);
        return view('contexts.index', compact('contexts'));
    }

    public function create()
    {
        $trunks = Trunk::orderBy('name')->get();
        return view('contexts.create', compact('trunks'));
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->validationRules(), $this->validationMessages());

        $data['record_calls'] = $request->boolean('record_calls');
        $data['voicemail_enabled'] = $request->boolean('voicemail_enabled');
        $data['enabled'] = $request->boolean('enabled', true);
        $data['created_by'] = auth()->id();

        $context = CallContext::create($data);

        ActivityLog::log('Contexte cree', "{$context->name} ({$context->direction})", 'success', $context);

        return redirect()->route('contexts.index')
            ->with('success', "Contexte « {$context->name} » cree avec succes.");
    }

    public function edit(CallContext $context)
    {
        $trunks = Trunk::orderBy('name')->get();
        return view('contexts.edit', compact('context', 'trunks'));
    }

    public function update(Request $request, CallContext $context)
    {
        $data = $request->validate($this->validationRules($context), $this->validationMessages());

        $data['record_calls'] = $request->boolean('record_calls');
        $data['voicemail_enabled'] = $request->boolean('voicemail_enabled');
        $data['enabled'] = $request->boolean('enabled', true);

        $context->update($data);

        ActivityLog::log('Contexte modifie', $context->name, 'info', $context);

        return redirect()->route('contexts.index')
            ->with('success', "Contexte « {$context->name} » mis a jour.");
    }

    public function destroy(CallContext $context)
    {
        $name = $context->name;
        $context->delete();

        ActivityLog::log('Contexte supprime', $name, 'warning');

        return redirect()->route('contexts.index')
            ->with('success', "Contexte « {$name} » supprime.");
    }

    public function toggle(CallContext $context)
    {
        $context->update(['enabled' => !$context->enabled]);

        ActivityLog::log(
            'Contexte ' . ($context->enabled ? 'active' : 'desactive'),
            $context->name,
            'info',
            $context,
        );

        return back()->with('success', "Contexte « {$context->name} » : " . ($context->enabled ? 'active' : 'desactive'));
    }

    public function dialplan()
    {
        $dialplan = CallContext::generateFullDialplan();
        return view('contexts.dialplan', compact('dialplan'));
    }
}

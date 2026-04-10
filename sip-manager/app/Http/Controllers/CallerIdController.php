<?php

namespace App\Http\Controllers;

use App\Models\CallerId;
use App\Models\CallerIdGroup;
use App\Models\Trunk;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class CallerIdController extends Controller
{
    public function index()
    {
        $callerIds = CallerId::with('trunk', 'groups')->latest()->paginate(25);
        $groups = CallerIdGroup::with('callerIds', 'users')->latest()->get();
        $trunks = Trunk::orderBy('name')->get();
        $operators = User::where('role', 'operator')->orderBy('name')->get();

        return view('caller-ids.index', compact('callerIds', 'groups', 'trunks', 'operators'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'number' => 'required|string|max:40',
            'label'  => 'required|string|max:100',
            'trunk_id' => 'nullable|exists:trunks,id',
        ]);

        $cid = CallerId::create($data);
        ActivityLog::log('Caller ID cree', "{$cid->label} ({$cid->number})", 'success', $cid);

        return back()->with('success', "Caller ID \"{$cid->label}\" cree.");
    }

    public function update(Request $request, CallerId $callerId)
    {
        $data = $request->validate([
            'number' => 'required|string|max:40',
            'label'  => 'required|string|max:100',
            'trunk_id' => 'nullable|exists:trunks,id',
        ]);

        $callerId->update($data);
        ActivityLog::log('Caller ID modifie', "{$callerId->label} ({$callerId->number})", 'info', $callerId);

        return back()->with('success', "Caller ID \"{$callerId->label}\" mis a jour.");
    }

    public function toggle(CallerId $callerId)
    {
        $callerId->update(['is_active' => !$callerId->is_active]);
        return back()->with('success', "Caller ID \"{$callerId->label}\" " . ($callerId->is_active ? 'active' : 'desactive') . '.');
    }

    public function destroy(CallerId $callerId)
    {
        $label = $callerId->label;
        $callerId->delete();
        ActivityLog::log('Caller ID supprime', $label, 'warning');

        return back()->with('success', "Caller ID \"{$label}\" supprime.");
    }

    // ── Groups ──

    public function storeGroup(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
            'caller_id_ids' => 'array',
            'caller_id_ids.*' => 'exists:caller_ids,id',
            'user_ids' => 'array',
            'user_ids.*' => 'exists:users,id',
        ]);

        $group = CallerIdGroup::create($data);
        $group->callerIds()->sync($request->input('caller_id_ids', []));
        $group->users()->sync($request->input('user_ids', []));

        ActivityLog::log('Groupe Caller ID cree', $group->name, 'success', $group);

        return back()->with('success', "Groupe \"{$group->name}\" cree.");
    }

    public function updateGroup(Request $request, CallerIdGroup $group)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
            'caller_id_ids' => 'array',
            'caller_id_ids.*' => 'exists:caller_ids,id',
            'user_ids' => 'array',
            'user_ids.*' => 'exists:users,id',
        ]);

        $group->update($data);
        $group->callerIds()->sync($request->input('caller_id_ids', []));
        $group->users()->sync($request->input('user_ids', []));

        ActivityLog::log('Groupe Caller ID modifie', $group->name, 'info', $group);

        return back()->with('success', "Groupe \"{$group->name}\" mis a jour.");
    }

    public function destroyGroup(CallerIdGroup $group)
    {
        $name = $group->name;
        $group->delete();
        ActivityLog::log('Groupe Caller ID supprime', $name, 'warning');

        return back()->with('success', "Groupe \"{$name}\" supprime.");
    }
}

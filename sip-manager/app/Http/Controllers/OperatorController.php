<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\SipLine;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class OperatorController extends Controller
{
    public function index()
    {
        $operators = User::where('role', 'operator')->with('sipLine')->orderBy('name')->get();
        $lines = SipLine::orderBy('extension')->get();

        return view('operators.index', compact('operators', 'lines'));
    }

    public function create()
    {
        $lines = SipLine::doesntHave('operator')->orderBy('extension')->get();
        return view('operators.create', compact('lines'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100',
            'email'       => 'required|email|max:255|unique:users,email',
            'password'    => 'required|string|min:6|confirmed',
            'sip_line_id' => 'required|exists:sip_lines,id',
        ]);

        User::create([
            'name'        => $data['name'],
            'email'       => $data['email'],
            'password'    => Hash::make($data['password']),
            'role'        => 'operator',
            'sip_line_id' => $data['sip_line_id'],
            'email_verified_at' => now(),
        ]);

        ActivityLog::log('Operateur cree', $data['name'], 'success');

        return redirect()->route('operators.index')->with('success', "Operateur \"{$data['name']}\" cree.");
    }

    public function edit(User $operator)
    {
        abort_if($operator->role !== 'operator', 404);
        $lines = SipLine::where(function ($q) use ($operator) {
            $q->doesntHave('operator')->orWhere('id', $operator->sip_line_id);
        })->orderBy('extension')->get();

        return view('operators.edit', compact('operator', 'lines'));
    }

    public function update(Request $request, User $operator)
    {
        abort_if($operator->role !== 'operator', 404);

        $data = $request->validate([
            'name'        => 'required|string|max:100',
            'email'       => 'required|email|max:255|unique:users,email,' . $operator->id,
            'password'    => 'nullable|string|min:6|confirmed',
            'sip_line_id' => 'required|exists:sip_lines,id',
        ]);

        $operator->update([
            'name'        => $data['name'],
            'email'       => $data['email'],
            'sip_line_id' => $data['sip_line_id'],
        ]);

        if (!empty($data['password'])) {
            $operator->update(['password' => Hash::make($data['password'])]);
        }

        return redirect()->route('operators.index')->with('success', "Operateur \"{$operator->name}\" mis a jour.");
    }

    public function destroy(User $operator)
    {
        abort_if($operator->role !== 'operator', 404);
        $name = $operator->name;
        $operator->delete();
        ActivityLog::log('Operateur supprime', $name, 'warning');

        return redirect()->route('operators.index')->with('success', "Operateur \"{$name}\" supprime.");
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\WidgetToken;
use App\Models\CallFlow;
use App\Models\SipLine;
use Illuminate\Http\Request;

class WidgetTokenController extends Controller
{
    public function index()
    {
        $widgets = WidgetToken::with('callflow')->latest()->paginate(25);
        return view('widgets.index', compact('widgets'));
    }

    public function create()
    {
        $callflows = CallFlow::where('enabled', true)->orderBy('name')->get();
        $lines = SipLine::orderBy('extension')->get();
        return view('widgets.create', compact('callflows', 'lines'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'           => 'required|string|max:100',
            'domain'         => 'required|string|max:255',
            'target_type'    => 'required|in:callflow,extension',
            'callflow_id'    => 'required_if:target_type,callflow|nullable|exists:call_flows,id',
            'extension'      => 'required_if:target_type,extension|nullable|string|max:20',
            'max_concurrent' => 'nullable|integer|min:1|max:100',
            'enabled'        => 'nullable|boolean',
        ]);

        $widget = WidgetToken::create([
            'name'           => $data['name'],
            'domain'         => $data['domain'],
            'callflow_id'    => $data['target_type'] === 'callflow' ? $data['callflow_id'] : null,
            'extension'      => $data['target_type'] === 'extension' ? $data['extension'] : null,
            'max_concurrent' => $data['max_concurrent'] ?? 5,
            'enabled'        => $request->boolean('enabled', true),
            'created_by'     => auth()->id(),
        ]);

        return redirect()->route('widgets.edit', $widget)
            ->with('success', "Widget \"{$widget->name}\" created.");
    }

    public function edit(WidgetToken $widget)
    {
        $callflows = CallFlow::where('enabled', true)->orderBy('name')->get();
        $lines = SipLine::orderBy('extension')->get();
        return view('widgets.edit', compact('widget', 'callflows', 'lines'));
    }

    public function update(Request $request, WidgetToken $widget)
    {
        $data = $request->validate([
            'name'           => 'required|string|max:100',
            'domain'         => 'required|string|max:255',
            'target_type'    => 'required|in:callflow,extension',
            'callflow_id'    => 'required_if:target_type,callflow|nullable|exists:call_flows,id',
            'extension'      => 'required_if:target_type,extension|nullable|string|max:20',
            'max_concurrent' => 'nullable|integer|min:1|max:100',
            'enabled'        => 'nullable|boolean',
        ]);

        $widget->update([
            'name'           => $data['name'],
            'domain'         => $data['domain'],
            'callflow_id'    => $data['target_type'] === 'callflow' ? $data['callflow_id'] : null,
            'extension'      => $data['target_type'] === 'extension' ? $data['extension'] : null,
            'max_concurrent' => $data['max_concurrent'] ?? 5,
            'enabled'        => $request->boolean('enabled', true),
        ]);

        return redirect()->route('widgets.edit', $widget)
            ->with('success', "Widget \"{$widget->name}\" updated.");
    }

    public function destroy(WidgetToken $widget)
    {
        $name = $widget->name;
        $widget->delete();
        return redirect()->route('widgets.index')
            ->with('success', "Widget \"{$name}\" deleted.");
    }

    public function toggle(WidgetToken $widget)
    {
        $widget->update(['enabled' => !$widget->enabled]);
        return back()->with('success', "Widget \"{$widget->name}\" " . ($widget->enabled ? 'enabled' : 'disabled') . '.');
    }

    public function regenerate(WidgetToken $widget)
    {
        $widget->update(['token' => bin2hex(random_bytes(24))]);
        return back()->with('success', "Token regenerated for \"{$widget->name}\".");
    }
}

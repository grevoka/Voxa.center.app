<?php

namespace App\Http\Controllers;

use App\Models\CallContext;
use App\Models\Trunk;
use App\Models\ActivityLog;
use App\Services\DialplanService;
use Illuminate\Http\Request;

class OutboundRouteController extends Controller
{
    public function __construct(private DialplanService $dialplan) {}

    public function index()
    {
        $routes = CallContext::where('direction', 'outbound')
            ->with('trunk')
            ->orderBy('priority')
            ->orderBy('name')
            ->get();

        $trunks = Trunk::orderBy('name')->get();

        return view('outbound.index', compact('routes', 'trunks'));
    }

    public function create()
    {
        $trunks = Trunk::orderBy('name')->get();
        return view('outbound.form', compact('trunks'));
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());

        $data['direction'] = 'outbound';
        $data['destination_type'] = 'trunk';
        $data['record_calls'] = $request->boolean('record_calls');
        $data['enabled'] = $request->boolean('enabled', true);
        $data['created_by'] = auth()->id();

        $route = CallContext::create($data);

        ActivityLog::log('Route sortante creee', "{$route->name} ({$route->dial_pattern})", 'success', $route);

        $this->dialplan->writeExtensions();

        return redirect()->route('outbound.index')
            ->with('success', "Route « {$route->name} » creee et dialplan applique.");
    }

    public function edit(CallContext $outbound_route)
    {
        $route = $outbound_route;
        $trunks = Trunk::orderBy('name')->get();
        return view('outbound.form', compact('route', 'trunks'));
    }

    public function update(Request $request, CallContext $outbound_route)
    {
        $route = $outbound_route;
        $data = $request->validate($this->rules($route));

        $data['direction'] = 'outbound';
        $data['destination_type'] = 'trunk';
        $data['record_calls'] = $request->boolean('record_calls');
        $data['enabled'] = $request->boolean('enabled', true);

        $route->update($data);

        ActivityLog::log('Route sortante modifiee', $route->name, 'info', $route);

        $this->dialplan->writeExtensions();

        return redirect()->route('outbound.index')
            ->with('success', "Route « {$route->name} » mise a jour et dialplan applique.");
    }

    public function destroy(CallContext $outbound_route)
    {
        $name = $outbound_route->name;
        $outbound_route->delete();

        ActivityLog::log('Route sortante supprimee', $name, 'warning');

        $this->dialplan->writeExtensions();

        return redirect()->route('outbound.index')
            ->with('success', "Route « {$name} » supprimee et dialplan mis a jour.");
    }

    public function toggle(CallContext $outbound_route)
    {
        $outbound_route->update(['enabled' => !$outbound_route->enabled]);

        ActivityLog::log(
            'Route sortante ' . ($outbound_route->enabled ? 'activee' : 'desactivee'),
            $outbound_route->name,
            'info',
            $outbound_route,
        );

        $this->dialplan->writeExtensions();

        return back()->with('success', "Route « {$outbound_route->name} » " . ($outbound_route->enabled ? 'activee' : 'desactivee') . ' — dialplan recharge.');
    }

    public function reorder(Request $request)
    {
        $request->validate(['order' => 'required|array', 'order.*' => 'integer|exists:call_contexts,id']);

        foreach ($request->input('order') as $priority => $id) {
            CallContext::where('id', $id)->update(['priority' => $priority + 1]);
        }

        $this->dialplan->writeExtensions();

        return response()->json(['ok' => true]);
    }

    private function rules(?CallContext $route = null): array
    {
        $uniqueRule = $route
            ? "unique:call_contexts,name,{$route->id}"
            : 'unique:call_contexts';

        return [
            'name'               => "required|string|max:50|regex:/^[a-z0-9_-]+$/|{$uniqueRule}",
            'description'        => 'nullable|string|max:255',
            'dial_pattern'       => 'required|string|max:100',
            'trunk_id'           => 'required|exists:trunks,id',
            'caller_id_override' => 'nullable|string|max:50',
            'prefix_strip'       => 'nullable|string|max:10',
            'prefix_add'         => 'nullable|string|max:20',
            'timeout'            => 'required|integer|min:5|max:120',
            'record_calls'       => 'boolean',
            'enabled'            => 'boolean',
            'priority'           => 'required|integer|min:1|max:99',
            'notes'              => 'nullable|string',
        ];
    }
}

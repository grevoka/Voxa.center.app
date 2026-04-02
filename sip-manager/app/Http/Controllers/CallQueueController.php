<?php

namespace App\Http\Controllers;

use App\Models\CallQueue;
use App\Models\SipLine;
use App\Services\DialplanService;
use Illuminate\Http\Request;

class CallQueueController extends Controller
{
    public function __construct(private DialplanService $dialplan) {}

    public function index()
    {
        $queues = CallQueue::latest()->paginate(25);
        return view('queues.index', compact('queues'));
    }

    public function create()
    {
        $lines = SipLine::orderBy('extension')->get();
        $strategies = CallQueue::$strategies;
        return view('queues.create', compact('lines', 'strategies'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'               => 'required|string|max:100|unique:call_queues|regex:/^[a-zA-Z0-9_-]+$/',
            'display_name'       => 'nullable|string|max:150',
            'strategy'           => 'required|string|in:' . implode(',', array_keys(CallQueue::$strategies)),
            'timeout'            => 'required|integer|min:5|max:120',
            'retry'              => 'required|integer|min:0|max:60',
            'max_wait_time'      => 'required|integer|min:30|max:3600',
            'music_on_hold'      => 'nullable|string|max:80',
            'announce_frequency' => 'nullable|integer|min:0|max:300',
            'announce_holdtime'  => 'nullable|in:yes,no,once',
            'members'            => 'nullable|array',
            'members.*.extension' => 'required|string',
            'members.*.penalty'  => 'nullable|integer|min:0|max:10',
        ]);

        $data['created_by'] = auth()->id();
        $data['music_on_hold'] = $data['music_on_hold'] ?: 'default';
        $data['announce_holdtime'] = $data['announce_holdtime'] ?: 'no';
        $data['announce_frequency'] = $data['announce_frequency'] ?: 0;

        $queue = CallQueue::create($data);

        // Write queues.conf + reload Asterisk
        $this->dialplan->writeQueues();

        return redirect()->route('queues.index')
            ->with('success', "File d'attente \"{$queue->display_name}\" creee et config appliquee.");
    }

    public function edit(CallQueue $queue)
    {
        $lines = SipLine::orderBy('extension')->get();
        $strategies = CallQueue::$strategies;
        return view('queues.edit', compact('queue', 'lines', 'strategies'));
    }

    public function update(Request $request, CallQueue $queue)
    {
        $data = $request->validate([
            'name'               => 'required|string|max:100|regex:/^[a-zA-Z0-9_-]+$/|unique:call_queues,name,' . $queue->id,
            'display_name'       => 'nullable|string|max:150',
            'strategy'           => 'required|string|in:' . implode(',', array_keys(CallQueue::$strategies)),
            'timeout'            => 'required|integer|min:5|max:120',
            'retry'              => 'required|integer|min:0|max:60',
            'max_wait_time'      => 'required|integer|min:30|max:3600',
            'music_on_hold'      => 'nullable|string|max:80',
            'announce_frequency' => 'nullable|integer|min:0|max:300',
            'announce_holdtime'  => 'nullable|in:yes,no,once',
            'members'            => 'nullable|array',
            'members.*.extension' => 'required|string',
            'members.*.penalty'  => 'nullable|integer|min:0|max:10',
        ]);

        $data['music_on_hold'] = $data['music_on_hold'] ?: 'default';
        $data['announce_holdtime'] = $data['announce_holdtime'] ?: 'no';
        $data['announce_frequency'] = $data['announce_frequency'] ?: 0;
        $queue->update($data);

        // Rewrite queues.conf
        $this->dialplan->writeQueues();

        return redirect()->route('queues.index')
            ->with('success', "File d'attente \"{$queue->display_name}\" mise a jour et config appliquee.");
    }

    public function destroy(CallQueue $queue)
    {
        $name = $queue->display_name;
        $queue->delete();

        // Rewrite queues.conf without this queue
        $this->dialplan->writeQueues();

        return redirect()->route('queues.index')
            ->with('success', "File d'attente \"{$name}\" supprimee et config mise a jour.");
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\ConferenceRoom;
use App\Services\DialplanService;
use Illuminate\Http\Request;

class ConferenceRoomController extends Controller
{
    public function __construct(private DialplanService $dialplan) {}

    public function index()
    {
        $rooms = ConferenceRoom::latest()->paginate(25);
        return view('conferences.index', compact('rooms'));
    }

    public function create()
    {
        return view('conferences.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'                => 'required|string|max:100|unique:conference_rooms|regex:/^[a-zA-Z0-9_-]+$/',
            'display_name'        => 'nullable|string|max:150',
            'conference_number'   => 'required|string|max:20|unique:conference_rooms|regex:/^[0-9]+$/',
            'pin'                 => 'nullable|string|max:20|regex:/^[0-9]+$/',
            'admin_pin'           => 'nullable|string|max:20|regex:/^[0-9]+$/',
            'max_members'         => 'required|integer|min:2|max:100',
            'music_on_hold'       => 'nullable|string|max:80',
            'record'              => 'boolean',
            'mute_on_join'        => 'boolean',
            'announce_join_leave' => 'boolean',
            'wait_for_leader'     => 'boolean',
        ]);

        $data['record'] = $request->boolean('record');
        $data['mute_on_join'] = $request->boolean('mute_on_join');
        $data['announce_join_leave'] = $request->boolean('announce_join_leave');
        $data['wait_for_leader'] = $request->boolean('wait_for_leader');
        $data['music_on_hold'] = $data['music_on_hold'] ?: 'default';
        $data['created_by'] = auth()->id();

        $room = ConferenceRoom::create($data);

        $this->dialplan->writeConferences();
        $this->dialplan->writeExtensions();

        $label = $room->display_name ?: $room->name;
        return redirect()->route('conferences.index')
            ->with('success', "Salle \"{$label}\" creee — numero {$room->conference_number}.");
    }

    public function edit(ConferenceRoom $conference)
    {
        return view('conferences.edit', ['room' => $conference]);
    }

    public function update(Request $request, ConferenceRoom $conference)
    {
        $data = $request->validate([
            'name'                => 'required|string|max:100|regex:/^[a-zA-Z0-9_-]+$/|unique:conference_rooms,name,' . $conference->id,
            'display_name'        => 'nullable|string|max:150',
            'conference_number'   => 'required|string|max:20|regex:/^[0-9]+$/|unique:conference_rooms,conference_number,' . $conference->id,
            'pin'                 => 'nullable|string|max:20|regex:/^[0-9]+$/',
            'admin_pin'           => 'nullable|string|max:20|regex:/^[0-9]+$/',
            'max_members'         => 'required|integer|min:2|max:100',
            'music_on_hold'       => 'nullable|string|max:80',
            'record'              => 'boolean',
            'mute_on_join'        => 'boolean',
            'announce_join_leave' => 'boolean',
            'wait_for_leader'     => 'boolean',
        ]);

        $data['record'] = $request->boolean('record');
        $data['mute_on_join'] = $request->boolean('mute_on_join');
        $data['announce_join_leave'] = $request->boolean('announce_join_leave');
        $data['wait_for_leader'] = $request->boolean('wait_for_leader');
        $data['music_on_hold'] = $data['music_on_hold'] ?: 'default';

        $conference->update($data);

        $this->dialplan->writeConferences();
        $this->dialplan->writeExtensions();

        $label = $conference->display_name ?: $conference->name;
        return redirect()->route('conferences.index')
            ->with('success', "Salle \"{$label}\" mise a jour.");
    }

    public function destroy(ConferenceRoom $conference)
    {
        $name = $conference->display_name ?: $conference->name;
        $conference->delete();

        $this->dialplan->writeConferences();
        $this->dialplan->writeExtensions();

        return redirect()->route('conferences.index')
            ->with('success', "Salle \"{$name}\" supprimee.");
    }

    public function toggle(ConferenceRoom $conference)
    {
        $conference->update(['enabled' => !$conference->enabled]);

        $this->dialplan->writeConferences();
        $this->dialplan->writeExtensions();

        $label = $conference->display_name ?: $conference->name;
        return back()->with('success',
            "Salle \"{$label}\" " .
            ($conference->enabled ? 'activee' : 'desactivee') . '.'
        );
    }
}

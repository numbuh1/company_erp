<?php
namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventLocation;
use App\Models\User;
use App\Helper\NotificationHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EventController extends Controller
{
    public function index()
    {
        if (!auth()->user()->can('module calendar')) abort(403);

        $events = Event::with(['attendants', 'creator'])
            ->latest('start_at')
            ->paginate(20)
            ->withQueryString();

        return view('events.index', compact('events'));
    }

    public function store(Request $request)
    {
        if (!auth()->user()->can('module calendar')) abort(403);

        $data = $request->validate([
            'name'           => 'required|string|max:255',
            'event_type'     => 'required|in:' . implode(',', array_keys(Event::$types)),
            'location'       => 'nullable|string|max:255',
            'start_at'       => 'required|date',
            'end_at'         => 'required|date|after_or_equal:start_at',
            'description'    => 'nullable|string',
            'file'           => 'nullable|file|max:20480',
            'attendants'     => 'nullable|array',
            'attendants.*'   => 'exists:users,id',
            'applicant_ids'  => 'nullable|array',
            'applicant_ids.*'=> 'exists:recruitment_applicants,id',
        ]);

        if ($request->hasFile('file')) {
            $data['file_path'] = $request->file('file')->store('event_files', 'public');
        }

        if (!empty($data['location'])) {
            EventLocation::firstOrCreate(['name' => $data['location']]);
        }

        $data['created_by'] = auth()->id();

        $event = Event::create($data);
        $event->attendants()->sync($request->input('attendants', []));
        $event->applicants()->sync($request->input('applicant_ids', []));

        $event->load('attendants');

        foreach ($event->attendants as $attendant) {
            if ($attendant->id === auth()->id()) continue;

            NotificationHelper::send(
                receivingUser: $attendant,
                title: 'New Event: ' . $event->name,
                description: \App\Models\Event::$types[$event->event_type]
                    . ' on ' . $event->start_at->format('d/m/Y H:i')
                    . ($event->location ? ' at ' . $event->location : ''),
                url: route('calendar.index', [
                    'view' => 'day',
                    'date' => $event->start_at->toDateString(),
                ]),
                incomingUser: auth()->user(),
            );
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'event' => $event->load('attendants')]);
        }

        return back()->with('success', 'Event created.');
    }

    public function edit(Event $event)
    {
        if (!auth()->user()->can('edit events') && $event->created_by !== auth()->id()) abort(403);

        $userOptions     = User::orderBy('name')->get(['id', 'name', 'position']);
        $locationOptions = EventLocation::orderBy('name')->pluck('name');
        $event->load('attendants');

        return view('events.form', compact('event', 'userOptions', 'locationOptions'));
    }

    public function update(Request $request, Event $event)
    {
        if (!auth()->user()->can('edit events') && $event->created_by !== auth()->id()) abort(403);

        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'event_type'  => 'required|in:' . implode(',', array_keys(Event::$types)),
            'location'    => 'nullable|string|max:255',
            'start_at'    => 'required|date',
            'end_at'      => 'required|date|after_or_equal:start_at',
            'description' => 'nullable|string',
            'file'        => 'nullable|file|max:20480',
            'attendants'  => 'nullable|array',
            'attendants.*'=> 'exists:users,id',
        ]);

        if ($request->hasFile('file')) {
            if ($event->file_path) Storage::disk('public')->delete($event->file_path);
            $data['file_path'] = $request->file('file')->store('event_files', 'public');
        }

        if (!empty($data['location'])) {
            EventLocation::firstOrCreate(['name' => $data['location']]);
        }

        $event->update($data);
        $event->attendants()->sync($request->input('attendants', []));

        return back()->with('success', 'Event updated.');
    }

    public function destroy(Event $event)
    {
        if (!auth()->user()->can('edit events') && $event->created_by !== auth()->id()) abort(403);

        $event->delete();
        return back()->with('success', 'Event deleted.');
    }

    // JSON endpoint: list of users for modal attendants picker
    public function userOptions()
    {
        if (!auth()->check()) abort(403);
        return response()->json(
            User::orderBy('name')->get(['id', 'name', 'position'])
                ->map(fn($u) => [
                    'id'    => $u->id,
                    'label' => $u->name . ($u->position ? ' · ' . $u->position : ''),
                ])
        );
    }

    // JSON endpoint: list of locations for modal
    public function locationOptions()
    {
        if (!auth()->check()) abort(403);
        return response()->json(EventLocation::orderBy('name')->pluck('name'));
    }

    // Show the modal
    public function apiShow(Event $event)
    {
        if (!auth()->check()) abort(403);

        $event->load('attendants');

        return response()->json([
            'id'          => $event->id,
            'name'        => $event->name,
            'event_type'  => $event->event_type,
            'location'    => $event->location ?? '',
            'start_at'    => $event->start_at->format('Y-m-d\TH:i'),
            'end_at'      => $event->end_at->format('Y-m-d\TH:i'),
            'description' => $event->description ?? '',
            'attendants'  => $event->attendants->pluck('id'),
            'file_path'   => $event->file_path,
        ]);
    }

}

<?php
namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventLocation;
use App\Models\LeaveRequest;
use App\Models\OvertimeRequest;
use App\Models\PublicHoliday;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    public function index(Request $request)
    {
        if (!auth()->user()->can('module calendar')) abort(403);

        $user  = auth()->user();
        $view  = $request->input('view', 'month');
        $date  = Carbon::parse($request->input('date', now()->toDateString()));

        $filterTypes    = $request->input('filter_types', []);
        $filterLocations = array_filter((array) $request->input('filter_location', []));

        [$rangeStart, $rangeEnd, $calStart, $calEnd] = match($view) {
            'week'  => [
                $date->copy()->startOfWeek(Carbon::MONDAY),
                $date->copy()->endOfWeek(Carbon::SUNDAY),
                $date->copy()->startOfWeek(Carbon::MONDAY),
                $date->copy()->endOfWeek(Carbon::SUNDAY),
            ],
            'day'   => [
                $date->copy()->startOfDay(),
                $date->copy()->endOfDay(),
                $date->copy()->startOfDay(),
                $date->copy()->endOfDay(),
            ],
            default => [
                $date->copy()->startOfMonth(),
                $date->copy()->endOfMonth(),
                $date->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY),
                $date->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY),
            ],
        };

        $userId = $user->id;

        // ── Leave scoping ────────────────────────────────────────────
        if ($user->can('view all leaves') || $user->can('edit all user')) {
            $leaveUserIds = null;
        } elseif ($user->can('view team leaves')) {
            $teamIds = $user->teamMembers()->pluck('id')->toArray();
            $leaveUserIds = array_unique(array_merge([$userId], $teamIds));
        } else {
            $leaveUserIds = [$userId];
        }

        // ── OT scoping ───────────────────────────────────────────────
        if ($user->can('view all ot') || $user->can('edit all user')) {
            $otUserIds = null;
        } elseif ($user->can('view team ot')) {
            $teamIds = $user->teamMembers()->pluck('id')->toArray();
            $otUserIds = array_unique(array_merge([$userId], $teamIds));
        } else {
            $otUserIds = [$userId];
        }

        // ── Events ──────────────────────────────────────────────────
        $allEventTypes   = ['internal_meeting', 'interview', 'company_event'];
        $showEventTypes  = empty($filterTypes)
            ? $allEventTypes
            : array_values(array_intersect($filterTypes, $allEventTypes));

        $events = collect();
        if (!empty($showEventTypes)) {
            $eventQuery = Event::with('attendants')
                ->where(function ($q) use ($userId) {
                    $q->whereHas('attendants', fn($sq) => $sq->where('users.id', $userId))
                      ->orWhere('created_by', $userId);
                })
                ->where('start_at', '<=', $calEnd)
                ->where('end_at',   '>=', $calStart)
                ->orderBy('start_at');

            if (!empty($filterTypes)) {
                $eventQuery->whereIn('event_type', $showEventTypes);
            }
            if (!empty($filterLocations)) {
                $eventQuery->whereIn('location', $filterLocations);
            }

            $events = $eventQuery->get()->groupBy(fn($e) => $e->start_at->toDateString());
        }

        // ── Leaves ──────────────────────────────────────────────────
        $showLeaves  = empty($filterTypes) || in_array('leave', $filterTypes);
        $leavesByDay = collect();

        if ($showLeaves) {
            $leaveQuery = LeaveRequest::with('user')
                ->where('status', 'approved')
                ->where('start_at', '<=', $calEnd)
                ->where('end_at',   '>=', $calStart)
                ->orderBy('start_at');

            if ($leaveUserIds !== null) {
                $leaveQuery->whereIn('user_id', $leaveUserIds);
            }

            foreach ($leaveQuery->get() as $leave) {
                $start  = Carbon::parse($leave->start_at)->startOfDay()->max($calStart->copy()->startOfDay());
                $end    = Carbon::parse($leave->end_at)->startOfDay()->min($calEnd->copy()->startOfDay());
                $cursor = $start->copy();
                while ($cursor->lte($end)) {
                    $key = $cursor->toDateString();
                    if (!$leavesByDay->has($key)) $leavesByDay->put($key, collect());
                    $leavesByDay->get($key)->push($leave);
                    $cursor->addDay();
                }
            }
        }

        // ── OT ──────────────────────────────────────────────────────
        $showOT   = empty($filterTypes) || in_array('ot', $filterTypes);
        $otsByDay = collect();

        if ($showOT) {
            $otQuery = OvertimeRequest::with('user')
                ->where('status', 'approved')
                ->where('start_at', '<=', $calEnd)
                ->where('end_at',   '>=', $calStart)
                ->orderBy('start_at');

            if ($otUserIds !== null) {
                $otQuery->whereIn('user_id', $otUserIds);
            }

            foreach ($otQuery->get() as $ot) {
                $key = Carbon::parse($ot->start_at)->toDateString();
                if (!$otsByDay->has($key)) $otsByDay->put($key, collect());
                $otsByDay->get($key)->push($ot);
            }
        }

        // ── Nav ──────────────────────────────────────────────────────
        $prevDate = match($view) {
            'week'  => $date->copy()->subWeek()->toDateString(),
            'day'   => $date->copy()->subDay()->toDateString(),
            default => $date->copy()->subMonth()->toDateString(),
        };
        $nextDate = match($view) {
            'week'  => $date->copy()->addWeek()->toDateString(),
            'day'   => $date->copy()->addDay()->toDateString(),
            default => $date->copy()->addMonth()->toDateString(),
        };

        $filterParams = [];
        if (!empty($filterTypes))   $filterParams['filter_types']    = $filterTypes;
        if (!empty($filterLocations)) $filterParams['filter_location'] = $filterLocations;

        $locationOptions = EventLocation::orderBy('name')->pluck('name');
        $holidayDates = PublicHoliday::getHolidayDates($calStart->copy()->startOfDay(), $calEnd->copy()->endOfDay());

        return view('calendar.index', compact(
            'view', 'date', 'events', 'leavesByDay', 'otsByDay',
            'calStart', 'calEnd', 'rangeStart', 'rangeEnd',
            'prevDate', 'nextDate',
            'filterTypes', 'filterLocations', 'locationOptions', 'filterParams',
            'holidayDates'
        ));
    }
}

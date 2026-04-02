<?php
namespace App\Http\Controllers;

use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    public function index(Request $request)
    {
        if (!auth()->user()->can('module calendar')) abort(403);

        $view = $request->input('view', 'month');
        $date = Carbon::parse($request->input('date', now()->toDateString()));

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
            default => [ // month
                $date->copy()->startOfMonth(),
                $date->copy()->endOfMonth(),
                $date->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY),
                $date->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY),
            ],
        };

        $userId = auth()->id();

        $events = Event::with('attendants')
            ->where(function ($q) use ($userId) {
                $q->whereHas('attendants', fn($sq) => $sq->where('users.id', $userId))
                  ->orWhere('created_by', $userId);
            })
            ->where('start_at', '<=', $calEnd)
            ->where('end_at',   '>=', $calStart)
            ->orderBy('start_at')
            ->get()
            ->groupBy(fn($e) => $e->start_at->toDateString());


        // Nav dates
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

        return view('calendar.index', compact(
            'view', 'date', 'events',
            'calStart', 'calEnd', 'rangeStart', 'rangeEnd',
            'prevDate', 'nextDate'
        ));
    }
}

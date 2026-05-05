<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class PublicHoliday extends Model
{
    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'repeats_annually'
    ];

    protected $casts = [
        'start_date'        => 'date',
        'end_date'          => 'date',
        'repeats_annually'  => 'boolean',
    ];

    /**
     * Returns a flat array of Y-m-d strings for all holidays
     * that fall within the given date range.
     */
    public static function getHolidayDates(Carbon $rangeStart, Carbon $rangeEnd): array
    {
        $holidays = static::all();
        $dates    = [];

        foreach ($holidays as $holiday) {
            if ($holiday->repeats_annually) {
                // Check every year from rangeStart.year - 1 to rangeEnd.year + 1
                // to catch cross-year-boundary holidays (e.g. Dec 31 – Jan 1)
                for ($year = $rangeStart->year - 1; $year <= $rangeEnd->year + 1; $year++) {
                    try {
                        $start = Carbon::create($year, $holiday->start_date->month, $holiday->start_date->day);
                        $end   = Carbon::create($year, $holiday->end_date->month,   $holiday->end_date->day);
                        if ($end->lt($start)) $end->addYear();
                    } catch (\Exception $e) {
                        continue; // skip Feb 29 in non-leap years, etc.
                    }

                    if ($end->lt($rangeStart) || $start->gt($rangeEnd)) continue;

                    $cursor = $start->copy();
                    while ($cursor->lte($end)) {
                        if ($cursor->between($rangeStart, $rangeEnd)) {
                            $dates[] = $cursor->toDateString();
                        }
                        $cursor->addDay();
                    }
                }
            } else {
                $cursor = $holiday->start_date->copy();
                while ($cursor->lte($holiday->end_date)) {
                    if ($cursor->between($rangeStart, $rangeEnd)) {
                        $dates[] = $cursor->toDateString();
                    }
                    $cursor->addDay();
                }
            }
        }

        return array_values(array_unique($dates));
    }

    /**
     * Returns holidays in range as an array of ['name', 'start' (Carbon), 'end' (Carbon)].
     * Used for display (e.g. dashboard), where you need names not just date strings.
     */
    public static function getHolidaysForRange(Carbon $rangeStart, Carbon $rangeEnd): array
    {
        $holidays = static::all();
        $result   = [];

        foreach ($holidays as $holiday) {
            if ($holiday->repeats_annually) {
                foreach (array_unique([$rangeStart->year, $rangeEnd->year]) as $year) {
                    try {
                        $start = Carbon::create($year, $holiday->start_date->month, $holiday->start_date->day);
                        $end   = Carbon::create($year, $holiday->end_date->month,   $holiday->end_date->day);
                        if ($end->lt($start)) $end->addYear();
                    } catch (\Exception $e) {
                        continue;
                    }
                    if ($end->lt($rangeStart) || $start->gt($rangeEnd)) continue;
                    $result[] = [
                        'name'  => $holiday->name,
                        'start' => $start->copy()->max($rangeStart),
                        'end'   => $end->copy()->min($rangeEnd),
                    ];
                }
            } else {
                if ($holiday->end_date->lt($rangeStart) || $holiday->start_date->gt($rangeEnd)) continue;
                $result[] = [
                    'name'  => $holiday->name,
                    'start' => $holiday->start_date->copy()->max($rangeStart),
                    'end'   => $holiday->end_date->copy()->min($rangeEnd),
                ];
            }
        }

        usort($result, fn($a, $b) => $a['start']->startOfDay() <=> $b['start']->startOfDay());
        return $result;
    }

}

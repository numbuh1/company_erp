<?php

namespace App\Support;

use App\Models\AppSetting;
use App\Models\PublicHoliday;
use Carbon\Carbon;

/**
 * Working hours inside a date-time range, shared by leave and WFH requests.
 * Mirrored client-side by resources/js/components/work-hours.js — keep the two in sync.
 */
class WorkHours
{
    /** Work-day edges used for the first/last day of a multi-day range. */
    public const DAY_START = '08:00';
    public const DAY_END   = '17:00';

    /**
     * [Y-m-d => net hours], lunch break excluded. A single day is simply start → end.
     * For a multi-day range the first day runs from the start time to DAY_END, the last day from DAY_START
     * to the end time, each day in between is a full day, and weekends/holidays (edge days included) count 0.
     */
    public static function breakdown(Carbon $start, Carbon $end, ?array $holidayDates = null): array
    {
        $lunchS = self::minutes(AppSetting::get('lunch_break_start', '12:00'));
        $lunchE = self::minutes(AppSetting::get('lunch_break_end', '13:00'));
        $net    = fn (int $from, int $to) => round(max(0, ($to - $from) - max(0, min($to, $lunchE) - max($from, $lunchS))) / 60, 2);

        $startM = $start->hour * 60 + $start->minute;
        $endM   = $end->hour * 60 + $end->minute;

        if ($start->isSameDay($end)) {
            return [$start->toDateString() => $net($startM, $endM)];
        }

        $dayStart  = self::minutes(self::DAY_START);
        $dayEnd    = self::minutes(self::DAY_END);
        $holidays  = array_flip($holidayDates ?? PublicHoliday::getHolidayDates($start->copy()->startOfDay(), $end->copy()->startOfDay()));
        $isWorkDay = fn (Carbon $d) => !$d->isWeekend() && !isset($holidays[$d->toDateString()]);

        $days = [];
        if ($isWorkDay($start)) $days[$start->toDateString()] = $net($startM, $dayEnd);
        for ($d = $start->copy()->startOfDay()->addDay(); $d->lt($end->copy()->startOfDay()); $d->addDay()) {
            if ($isWorkDay($d)) $days[$d->toDateString()] = $net($dayStart, $dayEnd);
        }
        if ($isWorkDay($end)) $days[$end->toDateString()] = $net($dayStart, $endM);

        return $days;
    }

    private static function minutes(string $hm): int
    {
        return (int) substr($hm, 0, 2) * 60 + (int) substr($hm, 3, 2);
    }
}

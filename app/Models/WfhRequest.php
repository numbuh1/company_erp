<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class WfhRequest extends Model
{
    /** Work-day edges used for the first/last day of a multi-day WFH range. */
    public const DAY_START = '08:00';
    public const DAY_END   = '17:00';

    protected $fillable = [
        'user_id',
        'start_at',
        'end_at',
        'hours',
        'description',
        'status',
        'approved_by',
        'reject_reason',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at'   => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isMultiDay(): bool
    {
        return !$this->start_at->isSameDay($this->end_at);
    }

    /** [Y-m-d => hours]. A single day keeps its stored (possibly hand-edited) hours. */
    public function dailyHours(?array $holidayDates = null): array
    {
        if (!$this->isMultiDay()) {
            return [$this->start_at->toDateString() => (float) $this->hours];
        }

        return self::breakdown($this->start_at, $this->end_at, $holidayDates);
    }

    /**
     * Net hours per day, minus the lunch break: the first day runs from the start time to DAY_END,
     * the last day from DAY_START to the end time, and each day in between is a full day.
     * In a multi-day range, weekends and holidays (including the first/last day) count as 0.
     */
    public static function breakdown(Carbon $start, Carbon $end, ?array $holidayDates = null): array
    {
        $toMins = fn (string $hm) => (int) substr($hm, 0, 2) * 60 + (int) substr($hm, 3, 2);
        $lunchS = $toMins(AppSetting::get('lunch_break_start', '12:00'));
        $lunchE = $toMins(AppSetting::get('lunch_break_end', '13:00'));
        $net    = fn (int $from, int $to) => round(max(0, ($to - $from) - max(0, min($to, $lunchE) - max($from, $lunchS))) / 60, 2);

        $startM = $start->hour * 60 + $start->minute;
        $endM   = $end->hour * 60 + $end->minute;

        if ($start->isSameDay($end)) {
            return [$start->toDateString() => $net($startM, $endM)];
        }

        $dayStart = $toMins(self::DAY_START);
        $dayEnd   = $toMins(self::DAY_END);
        $holidays = array_flip($holidayDates ?? PublicHoliday::getHolidayDates($start->copy()->startOfDay(), $end->copy()->startOfDay()));

        $isWorkDay = fn (Carbon $d) => !$d->isWeekend() && !isset($holidays[$d->toDateString()]);

        $days = [];
        if ($isWorkDay($start)) $days[$start->toDateString()] = $net($startM, $dayEnd);
        for ($d = $start->copy()->startOfDay()->addDay(); $d->lt($end->copy()->startOfDay()); $d->addDay()) {
            if ($isWorkDay($d)) $days[$d->toDateString()] = $net($dayStart, $dayEnd);
        }
        if ($isWorkDay($end)) $days[$end->toDateString()] = $net($dayStart, $endM);

        return $days;
    }
}

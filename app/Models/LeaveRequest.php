<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveRequest extends Model
{
    protected $fillable = [
        'user_id',
        'start_at',
        'end_at',
        'hours',
        'start_day_hours',
        'end_day_hours',
        'type',
        'description',
        'status',
        'approved_by',
        'reject_reason',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Approved leave hours per day for a user within [from, to]: [Y-m-d => hours].
     * Single-day leaves use their hours; multi-day leaves use start/end-day hours (4h if unset) and 8h in between.
     */
    public static function approvedHoursPerDay(int $userId, \Carbon\Carbon $from, \Carbon\Carbon $to): array
    {
        $from = $from->copy()->startOfDay();
        $to   = $to->copy()->startOfDay();

        $leaves = static::where('status', 'approved')
            ->where('user_id', $userId)
            ->where('start_at', '<=', $to->copy()->endOfDay()->toDateTimeString())
            ->where('end_at', '>=', $from->toDateTimeString())
            ->get(['start_at', 'end_at', 'hours', 'start_day_hours', 'end_day_hours']);

        $result = [];
        foreach ($leaves as $leave) {
            $lStartDay = $leave->start_at->toDateString();
            $lEndDay   = $leave->end_at->toDateString();
            $cur = $leave->start_at->copy()->startOfDay()->max($from);
            $cap = $leave->end_at->copy()->startOfDay()->min($to);

            for (; $cur->lte($cap); $cur->addDay()) {
                $dk = $cur->toDateString();
                $hpd = match (true) {
                    $lStartDay === $lEndDay => (float) $leave->hours,
                    $dk === $lStartDay      => (float) ($leave->start_day_hours ?? 4),
                    $dk === $lEndDay        => (float) ($leave->end_day_hours ?? 4),
                    default                 => 8.0,
                };
                $result[$dk] = ($result[$dk] ?? 0) + $hpd;
            }
        }

        return $result;
    }
}

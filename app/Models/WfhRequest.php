<?php

namespace App\Models;

use App\Support\WorkHours;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class WfhRequest extends Model
{
    protected $fillable = [
        'user_id',
        'project_id',
        'task_id',
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

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    /** Time logs created from this request when it was approved. */
    public function timeLogs()
    {
        return $this->hasMany(TimeLog::class);
    }

    /**
     * Records the approved WFH hours as work time. Each day gets at most what is left of 8h after
     * approved leave and work already logged, so nothing is double-counted. Returns the number of logs created.
     */
    public function logWorkTime(): int
    {
        $this->timeLogs()->delete();

        $days = $this->dailyHours();
        if (!$days) return 0;

        $from   = Carbon::parse(array_key_first($days));
        $to     = Carbon::parse(array_key_last($days));
        $leave  = LeaveRequest::approvedHoursPerDay($this->user_id, $from, $to);
        $logged = TimeLog::where('user_id', $this->user_id)
            ->whereDate('date', '>=', $from->toDateString())
            ->whereDate('date', '<=', $to->toDateString())
            ->selectRaw('DATE(date) as d, SUM(time_spent) as h')
            ->groupByRaw('DATE(date)')
            ->pluck('h', 'd');

        $created = 0;
        foreach ($days as $date => $wfhHours) {
            $hours = round(min($wfhHours, 8 - ($leave[$date] ?? 0) - (float) ($logged[$date] ?? 0)), 2);
            if ($hours < 0.25) continue;

            TimeLog::create([
                'user_id'        => $this->user_id,
                'project_id'     => $this->project_id,
                'task_id'        => $this->task_id,
                'description'    => $this->description ? 'WFH: ' . $this->description : 'WFH',
                'date'           => $date,
                'time_spent'     => $hours,
                'wfh_request_id' => $this->id,
            ]);
            $created++;
        }

        return $created;
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

    /** @see WorkHours::breakdown() */
    public static function breakdown(Carbon $start, Carbon $end, ?array $holidayDates = null): array
    {
        return WorkHours::breakdown($start, $end, $holidayDates);
    }
}

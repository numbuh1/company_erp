<?php

namespace App\Models;

use App\Support\WorkHours;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class WfhRequest extends Model
{
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

    /** @see WorkHours::breakdown() */
    public static function breakdown(Carbon $start, Carbon $end, ?array $holidayDates = null): array
    {
        return WorkHours::breakdown($start, $end, $holidayDates);
    }
}

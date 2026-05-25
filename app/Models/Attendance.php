<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Attendance extends Model
{
    use LogsActivity;

    protected $fillable = [
        'user_id',
        'date',
        'type',
        'check_out_type',
        'check_in_time',
        'check_out_time',
        'actual_work_hours',
        'status',
        'hours',
        'reason',
        'approved_by',
        'approved_at',
        'reject_reason',
        'created_by',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "Attendance {$eventName}")
            ->useLogName('attendance');
    }

    protected function casts(): array
    {
        return [
            'date'        => 'date',
            'approved_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

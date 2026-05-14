<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OvertimeRequest extends Model
{
    protected $fillable = [
        'user_id',
        'project_id',
        'task_id',
        'start_at',
        'end_at',
        'hours',
        'type',
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
        return $this->belongsTo(\App\Models\Project::class);
    }

    public function task()
    {
        return $this->belongsTo(\App\Models\Task::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimeLog extends Model
{
    protected $fillable = [
        'user_id',
        'project_id',
        'task_id',
        'description',
        'date',
        'time_spent',
    ];

    protected $casts = [
        'date'       => 'date',
        'time_spent' => 'float',
    ];

    // Relationship
    public function user()    
    {
        return $this->belongsTo(User::class);
    }

    public function project() 
    {
        return $this->belongsTo(Project::class);
    }

    public function task()    
    {
        return $this->belongsTo(Task::class);
    }

    public function timeLogs()
    {
        return $this->hasMany(TimeLog::class);
    }

    // Activity Logs
    public function getFormattedTimeAttribute(): string
    {
        return self::formatTime($this->time_spent);
    }

    public static function formatTime(float $hours): string
    {
        $h = (int) floor($hours);
        $m = (int) round(($hours - $h) * 60);
        if ($h > 0 && $m > 0) return "{$h}h {$m}m";
        if ($h > 0) return "{$h}h";
        return "{$m}m";
    }
}

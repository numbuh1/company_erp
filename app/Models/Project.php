<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Project extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'name',
        'description',
        'start_date',
        'expected_end_date',
        'actual_end_date',
        'status',
    ];

    protected $casts = [
        'start_date'        => 'date',
        'expected_end_date' => 'date',
        'actual_end_date'   => 'date',
    ];

    // Relationship
    public function teams()
    {
        return $this->belongsToMany(Team::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function files()
    {
        return $this->hasMany(ProjectFile::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }


    // Activity Logs
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }
}

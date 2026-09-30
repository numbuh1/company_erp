<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Task extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'project_id',
        'name',
        'task_code',
        'description',
        'progress',
        'budget_hours',
        'start_date',
        'expected_end_date',
        'actual_end_date',
        'status',
    ];

    protected $casts = [
        'start_date'        => 'date',
        'expected_end_date' => 'date',
        'actual_end_date'   => 'date',
        'progress'          => 'integer',
        'budget_hours'      => 'float',
    ];

    // Relationship
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function assignees()
    {
        return $this->belongsToMany(User::class, 'task_user')->withTimestamps();
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable')->oldest();
    }

    /** Tasks the user may see in lists; mirrors TaskPolicy::view. */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->can('view all tasks') || $user->can('edit tasks')) return $query;

        return $query->where(function ($q) use ($user) {
            $q->whereRaw('1 = 0');

            if ($user->can('view team tasks') || $user->can('edit team tasks')) {
                $q->orWhereHas('assignees', fn ($a) => $a->whereIn('users.id', $user->teamMemberIds()));
            }
            if ($user->can('view assigned tasks') || $user->can('edit assigned tasks')) {
                $q->orWhereHas('assignees', fn ($a) => $a->where('users.id', $user->id));
            }
            if ($user->can('edit assigned projects')) {
                $q->orWhereIn('project_id', $user->assignedProjectIds());
            }
        });
    }

    // Acitvity
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }
}

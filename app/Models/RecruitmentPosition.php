<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class RecruitmentPosition extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'name', 'team_id', 'search_start_date', 'search_end_date',
        'description', 'file_path', 'salary_min', 'salary_max',
        'status'
    ];

    protected function casts(): array
    {
        return [
            'search_start_date' => 'date',
            'search_end_date'   => 'date',
        ];
    }

    public static array $statuses = ['upcoming', 'in_progress', 'done'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public function assignedUsers()
    {
        return $this->belongsToMany(User::class, 'recruitment_position_user');
    }

    public function applicants()
    {
        return $this->hasMany(RecruitmentApplicant::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function tags()
    {
        return $this->belongsToMany(RecruitmentTag::class, 'recruitment_position_tag');
    }

    public function skills()
    {
        return $this->belongsToMany(Skill::class, 'recruitment_position_skill')
                    ->withPivot('level');
    }

}

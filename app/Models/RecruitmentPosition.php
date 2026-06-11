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
        'status', 'custom_applicant_statuses',
    ];

    protected function casts(): array
    {
        return [
            'search_start_date'         => 'date',
            'search_end_date'           => 'date',
            'custom_applicant_statuses' => 'array',
        ];
    }

    public static array $statuses = ['upcoming', 'in_progress', 'done'];

    /**
     * All applicant statuses available for this position: the fixed set
     * defined on RecruitmentApplicant, plus any custom statuses added via
     * the Kanban board for this position only.
     */
    public function allStatuses(): array
    {
        $statuses = RecruitmentApplicant::$statuses;

        foreach (($this->custom_applicant_statuses ?? []) as $custom) {
            $statuses[$custom] = $custom;
        }

        return $statuses;
    }

    /**
     * Add a custom applicant status, scoped to this position only.
     * Returns false if the name is blank or already exists (fixed or custom).
     */
    public function addCustomStatus(string $name): bool
    {
        $name = trim($name);
        if ($name === '') return false;

        $existing = $this->allStatuses();
        if (isset($existing[$name])) return false;

        $custom   = $this->custom_applicant_statuses ?? [];
        $custom[] = $name;
        $this->custom_applicant_statuses = $custom;
        $this->save();

        return true;
    }

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

<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class RecruitmentApplicant extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'recruitment_position_id', 'name', 'cv_path', 'notes', 'status',
        'evaluation', 'email', 'phone', 'profile_url',
        'salary_expectation', 'available_date', 'referer_user_id',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public function position()
    {
        return $this->belongsTo(RecruitmentPosition::class, 'recruitment_position_id');
    }

    public static array $statuses = [
        'CV Screening',
        'Approved for Interview',
        'Approved',
        'Rejected',
        'Offered',
        'Hired',
    ];

    protected function casts(): array
    {
        return [
            'available_date' => 'date',
            'evaluation'     => 'integer',
        ];
    }

    public function referer()
    {
        return $this->belongsTo(User::class, 'referer_user_id');
    }

    public function tags()
    {
        return $this->belongsToMany(RecruitmentTag::class, 'recruitment_applicant_tag');
    }

    public function skills()
    {
        return $this->belongsToMany(Skill::class, 'recruitment_applicant_skill')
                    ->withPivot('level');
    }

    public function events()
    {
        return $this->belongsToMany(Event::class, 'event_recruitment_applicant');
    }

}

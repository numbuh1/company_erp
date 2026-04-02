<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Event extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'name', 'event_type', 'location',
        'start_at', 'end_at', 'description', 'file_path', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at'   => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public static array $types = [
        'internal_meeting' => 'Internal Meeting',
        'interview'        => 'Interview',
        'company_event'    => 'Company Event',
    ];

    public function attendants()
    {
        return $this->belongsToMany(User::class, 'event_user');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function applicants()
    {
        return $this->belongsToMany(RecruitmentApplicant::class, 'event_recruitment_applicant');
    }
}

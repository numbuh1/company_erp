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

    /**
     * Key = value stored in DB.  Value = label shown to the user.
     * Edit the values here to change what is displayed (e.g. translate to another language).
     */
    public static array $statuses = [
        'CV Screening'           => 'Lọc CV',
        'Approved for Interview' => 'Duyệt PV',
        'Approved'               => 'Đã duyệt',
        'Rejected'               => 'Đã từ chối',
        'Offered'                => 'Đã gửi offer',
        'Hired'                  => 'Đã tuyển',
    ];

    /** Return the display label for a stored status key. */
    public static function statusLabel(string $status): string
    {
        return static::$statuses[$status] ?? $status;
    }

    /** Return the Tailwind badge colour classes for a status key. */
    public static function statusColor(string $status): string
    {
        return match($status) {
            'CV Screening'           => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
            'Approved for Interview' => 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300',
            'Approved'               => 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300',
            'Rejected'               => 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300',
            'Offered'                => 'bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-300',
            'Hired'                  => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900 dark:text-emerald-300',
            default                  => 'bg-gray-100 text-gray-600',
        };
    }

    /** Return the Tailwind header + dot colour config for the Kanban board columns. */
    public static function kanbanCols(): array
    {
        return [
            'CV Screening'           => ['header' => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200',           'dot' => 'bg-gray-400'],
            'Approved for Interview' => ['header' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/60 dark:text-blue-200',         'dot' => 'bg-blue-500'],
            'Approved'               => ['header' => 'bg-green-100 text-green-700 dark:bg-green-900/60 dark:text-green-200',     'dot' => 'bg-green-500'],
            'Rejected'               => ['header' => 'bg-red-100 text-red-700 dark:bg-red-900/60 dark:text-red-200',             'dot' => 'bg-red-500'],
            'Offered'                => ['header' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/60 dark:text-purple-200', 'dot' => 'bg-purple-500'],
            'Hired'                  => ['header' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/60 dark:text-emerald-200', 'dot' => 'bg-emerald-500'],
        ];
    }

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

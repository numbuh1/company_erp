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
        'recruitment_position_id', 'name', 'cv_path', 'notes', 'hr_note', 'status',
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
     * Fixed statuses available to every recruitment position.
     * Key = value stored in DB.  Value = label shown to the user.
     * Positions can additionally define their own custom statuses
     * (see RecruitmentPosition::$custom_applicant_statuses / allStatuses()).
     */
    public static array $statuses = [
        'Tiềm năng'       => 'Tiềm năng',
        'Lọc CV'          => 'Lọc CV',
        'Duyệt phỏng vấn' => 'Duyệt phỏng vấn',
        'Cân nhắc offer'  => 'Cân nhắc offer',
        'Đã gửi offer'    => 'Đã gửi offer',
        'Đã tuyển'        => 'Đã tuyển',
        'Không phù hợp'   => 'Không phù hợp',
    ];

    /**
     * Fallback colour palette (badge / kanban header / kanban dot) for
     * custom statuses that aren't part of the fixed set above. A status
     * is deterministically assigned one of these based on its name, so
     * it always renders with the same colour.
     */
    private static array $customPalette = [
        ['badge' => 'bg-pink-100 text-pink-700 dark:bg-pink-900 dark:text-pink-300',     'header' => 'bg-pink-100 text-pink-700 dark:bg-pink-900/60 dark:text-pink-200',     'dot' => 'bg-pink-500'],
        ['badge' => 'bg-teal-100 text-teal-700 dark:bg-teal-900 dark:text-teal-300',     'header' => 'bg-teal-100 text-teal-700 dark:bg-teal-900/60 dark:text-teal-200',     'dot' => 'bg-teal-500'],
        ['badge' => 'bg-orange-100 text-orange-700 dark:bg-orange-900 dark:text-orange-300', 'header' => 'bg-orange-100 text-orange-700 dark:bg-orange-900/60 dark:text-orange-200', 'dot' => 'bg-orange-500'],
        ['badge' => 'bg-lime-100 text-lime-700 dark:bg-lime-900 dark:text-lime-300',     'header' => 'bg-lime-100 text-lime-700 dark:bg-lime-900/60 dark:text-lime-200',     'dot' => 'bg-lime-500'],
        ['badge' => 'bg-violet-100 text-violet-700 dark:bg-violet-900 dark:text-violet-300', 'header' => 'bg-violet-100 text-violet-700 dark:bg-violet-900/60 dark:text-violet-200', 'dot' => 'bg-violet-500'],
        ['badge' => 'bg-sky-100 text-sky-700 dark:bg-sky-900 dark:text-sky-300',         'header' => 'bg-sky-100 text-sky-700 dark:bg-sky-900/60 dark:text-sky-200',         'dot' => 'bg-sky-500'],
    ];

    /** Deterministically pick a palette entry for a custom status name. */
    private static function customStyle(string $status): array
    {
        $index = crc32($status) % count(static::$customPalette);
        return static::$customPalette[$index];
    }

    /** Return the display label for a stored status key. */
    public static function statusLabel(string $status): string
    {
        return static::$statuses[$status] ?? $status;
    }

    /** Return the Tailwind badge colour classes for a status key. */
    public static function statusColor(string $status): string
    {
        return match($status) {
            'Tiềm năng'       => 'bg-cyan-100 text-cyan-700 dark:bg-cyan-900 dark:text-cyan-300',
            'Lọc CV'          => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
            'Duyệt phỏng vấn' => 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300',
            'Cân nhắc offer'  => 'bg-amber-100 text-amber-700 dark:bg-amber-900 dark:text-amber-300',
            'Đã gửi offer'    => 'bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-300',
            'Đã tuyển'        => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900 dark:text-emerald-300',
            'Không phù hợp'   => 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300',
            default           => static::customStyle($status)['badge'],
        };
    }

    /** Return the Tailwind header + dot colour config for the fixed Kanban board columns. */
    public static function kanbanCols(): array
    {
        return [
            'Tiềm năng'       => ['header' => 'bg-cyan-100 text-cyan-700 dark:bg-cyan-900/60 dark:text-cyan-200',     'dot' => 'bg-cyan-500'],
            'Lọc CV'          => ['header' => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200',        'dot' => 'bg-gray-400'],
            'Duyệt phỏng vấn' => ['header' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/60 dark:text-blue-200',     'dot' => 'bg-blue-500'],
            'Cân nhắc offer'  => ['header' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/60 dark:text-amber-200', 'dot' => 'bg-amber-500'],
            'Đã gửi offer'    => ['header' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/60 dark:text-purple-200', 'dot' => 'bg-purple-500'],
            'Đã tuyển'        => ['header' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/60 dark:text-emerald-200', 'dot' => 'bg-emerald-500'],
            'Không phù hợp'   => ['header' => 'bg-red-100 text-red-700 dark:bg-red-900/60 dark:text-red-200',         'dot' => 'bg-red-500'],
        ];
    }

    /**
     * Return the Tailwind header + dot colour config for a Kanban column,
     * including a deterministic fallback for custom (per-position) statuses.
     */
    public static function kanbanColConfig(string $status): array
    {
        $cols = static::kanbanCols();
        if (isset($cols[$status])) return $cols[$status];

        $style = static::customStyle($status);
        return ['header' => $style['header'], 'dot' => $style['dot']];
    }

    protected function casts(): array
    {
        return [
            'available_date'             => 'date',
            'evaluation'                 => 'integer',
            'duplicate_check_dismissed'  => 'boolean',
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

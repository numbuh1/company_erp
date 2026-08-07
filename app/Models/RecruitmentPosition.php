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
        'status', 'custom_applicant_statuses', 'applicant_status_order',
    ];

    protected function casts(): array
    {
        return [
            'search_start_date'         => 'date',
            'search_end_date'           => 'date',
            'custom_applicant_statuses' => 'array',
            'applicant_status_order'    => 'array',
        ];
    }

    public static array $statuses = ['upcoming', 'in_progress', 'done'];

    /**
     * All applicant statuses available for this position: the fixed set
     * defined on RecruitmentApplicant, plus any custom statuses added via
     * the Kanban board for this position only — reordered according to
     * `applicant_status_order` (if set) so the Kanban columns and the
     * status dropdown stay in sync.
     */
    public function allStatuses(): array
    {
        $statuses = RecruitmentApplicant::$statuses;

        foreach (($this->custom_applicant_statuses ?? []) as $custom) {
            $statuses[$custom] = $custom;
        }

        $order = $this->applicant_status_order ?? [];
        if (empty($order)) {
            return $statuses;
        }

        $ordered = [];
        foreach ($order as $name) {
            if (isset($statuses[$name])) {
                $ordered[$name] = $statuses[$name];
                unset($statuses[$name]);
            }
        }

        // Append any statuses not present in the saved order yet (e.g. a
        // status added after the order was last saved).
        return $ordered + $statuses;
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

    /**
     * Persist a new display order for the applicant statuses (Kanban
     * columns / status dropdown), scoped to this position only.
     * Unknown status names are dropped; any valid statuses missing from
     * the supplied order are appended at the end, preserving their
     * previous relative order.
     */
    public function setStatusOrder(array $order): void
    {
        $valid = array_keys($this->allStatuses());

        $order   = array_values(array_intersect(array_unique($order), $valid));
        $missing = array_values(array_diff($valid, $order));

        $this->applicant_status_order = array_merge($order, $missing);
        $this->save();
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

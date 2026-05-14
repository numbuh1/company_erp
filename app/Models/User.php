<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use SoftDeletes;
    use HasFactory, Notifiable, HasRoles, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'full_name',
        'email',
        'password',
        'leave_balance',
        'salary',
        'salary_type',
        'position',
        'phone_number',
        'citizen_id',
        'home_address',
        'tax_code',
        'social_insurance_id',
        'birthday',
        'contract_expiry',
        'is_active',
        'wfh_without_approval',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
            'wfh_without_approval' => 'boolean',
            'birthday'         => 'date',
            'contract_expiry'  => 'date',
        ];
    }

    /**
     * Computed hourly rate based on salary and salary_type.
     * monthly → salary / (22 working days × 8 hours)
     * weekly  → salary / 40 hours
     * daily   → salary / 8 hours
     * hourly  → salary directly
     */
    public function getHourlyRateAttribute(): ?float
    {
        if (!$this->salary || !$this->salary_type) return null;
        return match ($this->salary_type) {
            'monthly' => $this->salary / (22 * 8),
            'weekly'  => $this->salary / 40,
            'daily'   => $this->salary / 8,
            'hourly'  => (float) $this->salary,
            default   => null,
        };
    }

    /**
     * Estimated monthly rate based on salary and salary_type.
     * monthly → salary directly
     * weekly  → salary × 52 / 12
     * daily   → salary × 22
     * hourly  → salary × 22 × 8
     */
    public function getMonthlyRateAttribute(): ?float
    {
        if (!$this->salary || !$this->salary_type) return null;
        return match ($this->salary_type) {
            'monthly' => (float) $this->salary,
            'weekly'  => $this->salary * 52 / 12,
            'daily'   => $this->salary * 22,
            'hourly'  => $this->salary * 22 * 8,
            default   => null,
        };
    }

    // Relationship
    public function teams()
    {
        return $this->belongsToMany(Team::class)
            ->withPivot('is_leader')
            ->withTimestamps();
    }

    public function teamMembers()
    {
        return User::whereHas('teams', function ($q) {
            $q->whereIn('teams.id', $this->teams->pluck('id'));
        });
    }

    public function leaveBalanceLogs()
    {
        return $this->hasMany(LeaveBalanceLog::class);
    }

    public function supervisors()
    {
        return $this->belongsToMany(
            User::class, 'user_supervisors', 'user_id', 'supervisor_id'
        );
    }

    public function subordinates()
    {
        return $this->belongsToMany(
            User::class, 'user_supervisors', 'supervisor_id', 'user_id'
        );
    }

    // Log Functions
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}

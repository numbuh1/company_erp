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
     * Salary rate conversion constants:
     *   1 month = 4 weeks = 20 days = 160 hours
     */

    public function getHourlyRateAttribute(): ?float
    {
        if (!$this->salary || !$this->salary_type) return null;
        return match ($this->salary_type) {
            'monthly' => $this->salary / 160,   // ÷ (4w × 5d × 8h)
            'weekly'  => $this->salary / 40,    // ÷ (5d × 8h)
            'daily'   => $this->salary / 8,
            'hourly'  => (float) $this->salary,
            default   => null,
        };
    }

    public function getDailyRateAttribute(): ?float
    {
        if (!$this->salary || !$this->salary_type) return null;
        return match ($this->salary_type) {
            'monthly' => $this->salary / 20,    // ÷ (4w × 5d)
            'weekly'  => $this->salary / 5,
            'daily'   => (float) $this->salary,
            'hourly'  => $this->salary * 8,
            default   => null,
        };
    }

    public function getWeeklyRateAttribute(): ?float
    {
        if (!$this->salary || !$this->salary_type) return null;
        return match ($this->salary_type) {
            'monthly' => $this->salary / 4,
            'weekly'  => (float) $this->salary,
            'daily'   => $this->salary * 5,
            'hourly'  => $this->salary * 40,
            default   => null,
        };
    }

    public function getMonthlyRateAttribute(): ?float
    {
        if (!$this->salary || !$this->salary_type) return null;
        return match ($this->salary_type) {
            'monthly' => (float) $this->salary,
            'weekly'  => $this->salary * 4,
            'daily'   => $this->salary * 20,
            'hourly'  => $this->salary * 160,
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

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
        'position',
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
        ];
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

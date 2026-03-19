<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\SoftDeletes;

class Team extends Model
{
    //
    use SoftDeletes;
    use LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    // Relationship
    public function users()
    {
        return $this->belongsToMany(User::class)
            ->withPivot('is_leader')
            ->withTimestamps();
    }

    public function leaders()
    {
        return $this->belongsToMany(User::class)
            ->wherePivot('is_leader', true);
    }

    // Log Functions
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}

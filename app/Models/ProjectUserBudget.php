<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectUserBudget extends Model
{
    protected $fillable = ['project_id', 'user_id', 'budget_hours'];

    protected $casts = ['budget_hours' => 'float'];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

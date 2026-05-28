<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserPreference extends Model
{
    protected $fillable = [
        'user_id',
        'task_list_column_preferences',
        'project_task_column_preferences',
        'email_notifications',
    ];

    protected function casts(): array
    {
        return [
            'task_list_column_preferences'    => 'array',
            'project_task_column_preferences' => 'array',
            'email_notifications'             => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectFile extends Model
{
    protected $fillable = [
        'project_id',
        'uploaded_by',
        'original_name',
        'stored_name',
        'size',
        'is_folder',
        'parent_id',
        'name',
    ];

    protected $casts = [
        'is_folder' => 'boolean',
    ];

    // Relationship
    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function parent()
    {
        return $this->belongsTo(ProjectFile::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(ProjectFile::class, 'parent_id');
    }

    public function timeLogs()
    {
        return $this->hasMany(TimeLog::class);
    }

    // Accessor: use name field, fall back to original_name
    public function getDisplayNameAttribute(): string
    {
        return $this->name ?? $this->original_name ?? 'Unnamed';
    }
}

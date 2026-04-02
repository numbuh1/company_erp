<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Skill extends Model
{
    protected $fillable = ['name', 'category'];

    // Suggested categories shown as datalist hints — not enforced
    public static array $categories = [
        'Languages', 'Engineering', 'IT', 'Management',
        'Design', 'Marketing', 'Finance', 'Operations',
    ];

    public static array $levels = ['beginner', 'intermediate', 'advanced'];

    public function positions()
    {
        return $this->belongsToMany(RecruitmentPosition::class, 'recruitment_position_skill');
    }

    public function applicants()
    {
        return $this->belongsToMany(RecruitmentApplicant::class, 'recruitment_applicant_skill');
    }
}

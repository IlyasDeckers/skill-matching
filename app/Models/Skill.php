<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Skill extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category',
        'popularity',
        'is_growing',
    ];

    /**
     * Get the related skills for this skill.
     */
    public function relatedSkills(): BelongsToMany
    {
        return $this->belongsToMany(
            Skill::class,
            'skill_relationships',
            'skill_id',
            'related_skill_id'
        )->withPivot('similarity_score');
    }

    /**
     * Get the candidates who have this skill.
     */
    public function candidates(): BelongsToMany
    {
        return $this->belongsToMany(Candidate::class, 'candidate_skills')
            ->withPivot(['proficiency_level', 'years_experience', 'last_used_date']);
    }

    /**
     * Get the jobs that require this skill.
     */
    public function requiredForJobs(): BelongsToMany
    {
        return $this->belongsToMany(Job::class, 'job_required_skills')
            ->withPivot(['importance_weight', 'minimum_years']);
    }

    /**
     * Get the jobs that prefer this skill.
     */
    public function preferredForJobs(): BelongsToMany
    {
        return $this->belongsToMany(Job::class, 'job_preferred_skills')
            ->withPivot(['importance_weight', 'minimum_years']);
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Job extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'years_experience_required',
    ];

    /**
     * Get the required skills for this job.
     */
    public function requiredSkills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'job_required_skills')
            ->withPivot(['importance_weight', 'minimum_years']);
    }

    /**
     * Get the preferred skills for this job.
     */
    public function preferredSkills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'job_preferred_skills')
            ->withPivot(['importance_weight', 'minimum_years']);
    }

    /**
     * Get the matching results for this job.
     */
    public function matchResults(): HasMany
    {
        return $this->hasMany(MatchResult::class);
    }
}
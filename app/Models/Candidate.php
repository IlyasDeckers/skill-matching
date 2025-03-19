<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Candidate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'years_experience',
    ];

    /**
     * Get the skills this candidate has.
     */
    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'candidate_skills')
            ->withPivot(['proficiency_level', 'years_experience', 'last_used_date']);
    }

    /**
     * Get the matching results for this candidate.
     */
    public function matchResults(): HasMany
    {
        return $this->hasMany(MatchResult::class);
    }
}
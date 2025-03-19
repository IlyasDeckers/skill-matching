<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MatchResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'candidate_id',
        'job_id',
        'match_score',
        'required_skills_score',
        'preferred_skills_score',
        'experience_score',
        'breakdown',
    ];

    protected $casts = [
        'match_score' => 'float',
        'required_skills_score' => 'float',
        'preferred_skills_score' => 'float',
        'experience_score' => 'float',
        'breakdown' => 'array',
    ];

    /**
     * Get the candidate for this match result.
     */
    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    /**
     * Get the job for this match result.
     */
    public function job()
    {
        return $this->belongsTo(Job::class);
    }
}
<?php

namespace App\Services;

use App\Models\Candidate;
use App\Models\Job;
use App\Models\MatchResult;
use App\Models\Skill;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Swoole\Coroutine as Co;

class SkillMatchingService
{
    /**
     * Calculate match score between a candidate and a job
     *
     * @param Candidate $candidate
     * @param Job $job
     * @return MatchResult
     */
    public function calculateMatch(Candidate $candidate, Job $job): MatchResult
    {
        // Get all the candidate's skills with pivot data
        $candidateSkills = $candidate->skills()->get()
            ->keyBy('id')
            ->map(function ($skill) {
                $skill->recency_factor = $this->calculateRecencyFactor($skill->pivot->last_used_date);
                return $skill;
            });

        // Calculate required skills score
        $requiredSkillsScore = $this->calculateRequiredSkillsScore($job, $candidateSkills);

        // Calculate preferred skills score
        $preferredSkillsScore = $this->calculatePreferredSkillsScore($job, $candidateSkills);

        // Calculate experience match
        $experienceScore = $this->calculateExperienceScore($candidate, $job);

        // Calculate overall match score (weighted average)
        $overallScore = ($requiredSkillsScore * 0.6) + ($preferredSkillsScore * 0.3) + ($experienceScore * 0.1);

        // Prepare breakdown data
        $breakdown = [
            'required_skills' => $this->getRequiredSkillsBreakdown($job, $candidateSkills),
            'preferred_skills' => $this->getPreferredSkillsBreakdown($job, $candidateSkills),
            'experience' => [
                'candidate_years' => $candidate->years_experience,
                'job_required_years' => $job->years_experience_required,
                'score' => $experienceScore
            ]
        ];

        // Create or update match result
        return MatchResult::updateOrCreate(
            ['candidate_id' => $candidate->id, 'job_id' => $job->id],
            [
                'match_score' => $overallScore,
                'required_skills_score' => $requiredSkillsScore,
                'preferred_skills_score' => $preferredSkillsScore,
                'experience_score' => $experienceScore,
                'breakdown' => $breakdown
            ]
        );
    }

    /**
     * Calculate batch matches between multiple candidates and jobs
     *
     * @param Collection $candidates
     * @param Collection $jobs
     * @return Collection
     */
    public function calculateBatchMatches(Collection $candidates, Collection $jobs): Collection
    {
        $results = collect();

        // Only use Swoole if it's available
        if (extension_loaded('swoole') && function_exists('go')) {
            return $this->calculateBatchMatchesWithSwoole($candidates, $jobs);
        }

        foreach ($candidates as $candidate) {
            foreach ($jobs as $job) {
                $results->push($this->calculateMatch($candidate, $job));
            }
        }

        return $results;
    }

    /**
     * Calculate batch matches using Swoole coroutines for concurrent processing
     *
     * @param Collection $candidates
     * @param Collection $jobs
     * @return Collection
     */
    private function calculateBatchMatchesWithSwoole(Collection $candidates, Collection $jobs): Collection
    {
        $results = [];
        $wg = new Co\WaitGroup();

        foreach ($candidates as $candidate) {
            foreach ($jobs as $job) {
                $wg->add();
                go(function () use ($candidate, $job, &$results, $wg) {
                    $results[] = $this->calculateMatch($candidate, $job);
                    $wg->done();
                });
            }
        }

        $wg->wait();
        return collect($results);
    }

    /**
     * Get the top matching candidates for a job
     *
     * @param Job $job
     * @param int $limit
     * @return Collection
     */
    public function getTopCandidatesForJob(Job $job, int $limit = 10): Collection
    {
        return MatchResult::where('job_id', $job->id)
            ->orderByDesc('match_score')
            ->with('candidate')
            ->take($limit)
            ->get();
    }

    /**
     * Get the top matching jobs for a candidate
     *
     * @param Candidate $candidate
     * @param int $limit
     * @return Collection
     */
    public function getTopJobsForCandidate(Candidate $candidate, int $limit = 10): Collection
    {
        return MatchResult::where('candidate_id', $candidate->id)
            ->orderByDesc('match_score')
            ->with('job')
            ->take($limit)
            ->get();
    }

    /**
     * Calculate how recent a skill was used (1.0 = current, decreases with time)
     *
     * @param string|null $lastUsedDate
     * @return float
     */
    private function calculateRecencyFactor(?string $lastUsedDate): float
    {
        if (!$lastUsedDate) {
            return 0.5; // Default if no date provided
        }

        $lastUsed = Carbon::parse($lastUsedDate);
        $now = Carbon::now();
        $monthsDiff = $now->diffInMonths($lastUsed);

        if ($monthsDiff <= 3) return 1.0;
        if ($monthsDiff <= 12) return 0.9;
        if ($monthsDiff <= 24) return 0.7;
        if ($monthsDiff <= 36) return 0.5;
        if ($monthsDiff <= 60) return 0.3;
        return 0.1;
    }

    /**
     * Calculate required skills score
     *
     * @param Job $job
     * @param Collection $candidateSkills
     * @return float
     */
    private function calculateRequiredSkillsScore(Job $job, Collection $candidateSkills): float
    {
        $requiredSkills = $job->requiredSkills()->get();

        if ($requiredSkills->isEmpty()) {
            return 100; // No required skills means perfect match
        }

        $totalWeight = $requiredSkills->sum('pivot.importance_weight');
        $score = 0;

        foreach ($requiredSkills as $requiredSkill) {
            // Direct match
            if ($candidateSkills->has($requiredSkill->id)) {
                $candidateSkill = $candidateSkills->get($requiredSkill->id);

                // Calculate proficiency score (0-1)
                $proficiencyScore = $candidateSkill->pivot->proficiency_level / 5;

                // Calculate years experience score (0-1)
                $yearsScore = min(1, $candidateSkill->pivot->years_experience / max(1, $requiredSkill->pivot->minimum_years));

                // Apply recency factor
                $recencyFactor = $candidateSkill->recency_factor;

                // Combined skill score
                $skillScore = (($proficiencyScore * 0.4) + ($yearsScore * 0.4) + ($recencyFactor * 0.2))
                    * $requiredSkill->pivot->importance_weight;

                $score += $skillScore;
            } else {
                // Check for related skills
                $relatedSkillScore = $this->calculateRelatedSkillScore(
                    $requiredSkill,
                    $candidateSkills,
                    $requiredSkill->pivot->importance_weight,
                    $requiredSkill->pivot->minimum_years
                );
                $score += $relatedSkillScore;
            }
        }

        return $totalWeight > 0 ? min(100, ($score / $totalWeight) * 100) : 0;
    }

    /**
     * Calculate preferred skills score
     *
     * @param Job $job
     * @param Collection $candidateSkills
     * @return float
     */
    private function calculatePreferredSkillsScore(Job $job, Collection $candidateSkills): float
    {
        $preferredSkills = $job->preferredSkills()->get();

        if ($preferredSkills->isEmpty()) {
            return 100; // No preferred skills means perfect match
        }

        $totalWeight = $preferredSkills->sum('pivot.importance_weight');
        $score = 0;

        foreach ($preferredSkills as $preferredSkill) {
            // Direct match
            if ($candidateSkills->has($preferredSkill->id)) {
                $candidateSkill = $candidateSkills->get($preferredSkill->id);

                // Calculate proficiency score (0-1)
                $proficiencyScore = $candidateSkill->pivot->proficiency_level / 5;

                // Calculate years experience score (0-1)
                $yearsScore = min(1, $candidateSkill->pivot->years_experience / max(1, $preferredSkill->pivot->minimum_years));

                // Apply recency factor
                $recencyFactor = $candidateSkill->recency_factor;

                // Combined skill score
                $skillScore = (($proficiencyScore * 0.4) + ($yearsScore * 0.4) + ($recencyFactor * 0.2))
                    * $preferredSkill->pivot->importance_weight;

                $score += $skillScore;
            } else {
                // Check for related skills
                $relatedSkillScore = $this->calculateRelatedSkillScore(
                    $preferredSkill,
                    $candidateSkills,
                    $preferredSkill->pivot->importance_weight,
                    $preferredSkill->pivot->minimum_years
                );
                $score += $relatedSkillScore;
            }
        }

        return $totalWeight > 0 ? min(100, ($score / $totalWeight) * 100) : 0;
    }

    /**
     * Calculate score for related skills
     *
     * @param Skill $requiredSkill
     * @param Collection $candidateSkills
     * @param int $importanceWeight
     * @param int $minimumYears
     * @return float
     */
    private function calculateRelatedSkillScore(Skill $requiredSkill, Collection $candidateSkills, int $importanceWeight, int $minimumYears): float
    {
        // Get related skills with similarity scores
        $relatedSkills = $requiredSkill->relatedSkills()->get();

        $bestScore = 0;

        foreach ($relatedSkills as $relatedSkill) {
            if ($candidateSkills->has($relatedSkill->id)) {
                $candidateSkill = $candidateSkills->get($relatedSkill->id);

                // Get similarity score (0-1)
                $similarityScore = $relatedSkill->pivot->similarity_score;

                // Calculate proficiency score (0-1)
                $proficiencyScore = $candidateSkill->pivot->proficiency_level / 5;

                // Calculate years experience score (0-1)
                $yearsScore = min(1, $candidateSkill->pivot->years_experience / max(1, $minimumYears));

                // Apply recency factor
                $recencyFactor = $candidateSkill->recency_factor;

                // Combined skill score
                $skillScore = $similarityScore * (($proficiencyScore * 0.4) + ($yearsScore * 0.4) + ($recencyFactor * 0.2))
                    * $importanceWeight;

                $bestScore = max($bestScore, $skillScore);
            }
        }

        return $bestScore;
    }

    /**
     * Calculate experience match score
     *
     * @param Candidate $candidate
     * @param Job $job
     * @return float
     */
    private function calculateExperienceScore(Candidate $candidate, Job $job): float
    {
        if ($job->years_experience_required <= 0) {
            return 100; // No experience required means perfect match
        }

        $ratio = $candidate->years_experience / $job->years_experience_required;

        if ($ratio >= 1.5) return 100;  // Significantly more experience than required
        if ($ratio >= 1.0) return 90;   // More experience than required
        if ($ratio >= 0.8) return 70;   // Slightly less experience
        if ($ratio >= 0.6) return 50;   // Moderately less experience
        if ($ratio >= 0.4) return 30;   // Significantly less experience
        return 10;                      // Minimal experience compared to requirements
    }

    /**
     * Get detailed breakdown of required skills matching
     *
     * @param Job $job
     * @param Collection $candidateSkills
     * @return array
     */
    private function getRequiredSkillsBreakdown(Job $job, Collection $candidateSkills): array
    {
        $breakdown = [];
        $requiredSkills = $job->requiredSkills()->get();

        foreach ($requiredSkills as $requiredSkill) {
            $skillBreakdown = [
                'skill_id' => $requiredSkill->id,
                'skill_name' => $requiredSkill->name,
                'importance_weight' => $requiredSkill->pivot->importance_weight,
                'minimum_years' => $requiredSkill->pivot->minimum_years,
                'direct_match' => false,
                'related_match' => false,
                'proficiency' => 0,
                'years' => 0,
                'recency' => 0,
                'match_score' => 0
            ];

            // Direct match
            if ($candidateSkills->has($requiredSkill->id)) {
                $candidateSkill = $candidateSkills->get($requiredSkill->id);
                $skillBreakdown['direct_match'] = true;
                $skillBreakdown['proficiency'] = $candidateSkill->pivot->proficiency_level;
                $skillBreakdown['years'] = $candidateSkill->pivot->years_experience;
                $skillBreakdown['recency'] = $candidateSkill->recency_factor;

                // Calculate score
                $proficiencyScore = $candidateSkill->pivot->proficiency_level / 5;
                $yearsScore = min(1, $candidateSkill->pivot->years_experience / max(1, $requiredSkill->pivot->minimum_years));
                $skillBreakdown['match_score'] = (($proficiencyScore * 0.4) + ($yearsScore * 0.4) + ($candidateSkill->recency_factor * 0.2)) * 100;
            } else {
                // Check for related skills
                $relatedMatch = $this->findBestRelatedSkillMatch($requiredSkill, $candidateSkills);

                if ($relatedMatch) {
                    $skillBreakdown['related_match'] = true;
                    $skillBreakdown['related_skill_id'] = $relatedMatch['skill']->id;
                    $skillBreakdown['related_skill_name'] = $relatedMatch['skill']->name;
                    $skillBreakdown['similarity_score'] = $relatedMatch['similarity_score'];
                    $skillBreakdown['proficiency'] = $relatedMatch['skill']->pivot->proficiency_level;
                    $skillBreakdown['years'] = $relatedMatch['skill']->pivot->years_experience;
                    $skillBreakdown['recency'] = $relatedMatch['skill']->recency_factor;
                    $skillBreakdown['match_score'] = $relatedMatch['match_score'];
                }
            }

            $breakdown[] = $skillBreakdown;
        }

        return $breakdown;
    }

    /**
     * Get detailed breakdown of preferred skills matching
     *
     * @param Job $job
     * @param Collection $candidateSkills
     * @return array
     */
    private function getPreferredSkillsBreakdown(Job $job, Collection $candidateSkills): array
    {
        $breakdown = [];
        $preferredSkills = $job->preferredSkills()->get();

        foreach ($preferredSkills as $preferredSkill) {
            $skillBreakdown = [
                'skill_id' => $preferredSkill->id,
                'skill_name' => $preferredSkill->name,
                'importance_weight' => $preferredSkill->pivot->importance_weight,
                'minimum_years' => $preferredSkill->pivot->minimum_years,
                'direct_match' => false,
                'related_match' => false,
                'proficiency' => 0,
                'years' => 0,
                'recency' => 0,
                'match_score' => 0
            ];

            // Direct match
            if ($candidateSkills->has($preferredSkill->id)) {
                $candidateSkill = $candidateSkills->get($preferredSkill->id);
                $skillBreakdown['direct_match'] = true;
                $skillBreakdown['proficiency'] = $candidateSkill->pivot->proficiency_level;
                $skillBreakdown['years'] = $candidateSkill->pivot->years_experience;
                $skillBreakdown['recency'] = $candidateSkill->recency_factor;

                // Calculate score
                $proficiencyScore = $candidateSkill->pivot->proficiency_level / 5;
                $yearsScore = min(1, $candidateSkill->pivot->years_experience / max(1, $preferredSkill->pivot->minimum_years));
                $skillBreakdown['match_score'] = (($proficiencyScore * 0.4) + ($yearsScore * 0.4) + ($candidateSkill->recency_factor * 0.2)) * 100;
            } else {
                // Check for related skills
                $relatedMatch = $this->findBestRelatedSkillMatch($preferredSkill, $candidateSkills);

                if ($relatedMatch) {
                    $skillBreakdown['related_match'] = true;
                    $skillBreakdown['related_skill_id'] = $relatedMatch['skill']->id;
                    $skillBreakdown['related_skill_name'] = $relatedMatch['skill']->name;
                    $skillBreakdown['similarity_score'] = $relatedMatch['similarity_score'];
                    $skillBreakdown['proficiency'] = $relatedMatch['skill']->pivot->proficiency_level;
                    $skillBreakdown['years'] = $relatedMatch['skill']->pivot->years_experience;
                    $skillBreakdown['recency'] = $relatedMatch['skill']->recency_factor;
                    $skillBreakdown['match_score'] = $relatedMatch['match_score'];
                }
            }

            $breakdown[] = $skillBreakdown;
        }

        return $breakdown;
    }

    /**
     * Find the best related skill match
     *
     * @param Skill $requiredSkill
     * @param Collection $candidateSkills
     * @return array|null
     */
    private function findBestRelatedSkillMatch(Skill $requiredSkill, Collection $candidateSkills): ?array
    {
        $relatedSkills = $requiredSkill->relatedSkills()->get();
        $bestMatch = null;
        $bestScore = 0;

        foreach ($relatedSkills as $relatedSkill) {
            if ($candidateSkills->has($relatedSkill->id)) {
                $candidateSkill = $candidateSkills->get($relatedSkill->id);
                $similarityScore = $relatedSkill->pivot->similarity_score;

                $proficiencyScore = $candidateSkill->pivot->proficiency_level / 5;
                $yearsScore = min(1, $candidateSkill->pivot->years_experience / max(1, 1)); // Assuming minimum 1 year
                $matchScore = $similarityScore * (($proficiencyScore * 0.4) + ($yearsScore * 0.4) + ($candidateSkill->recency_factor * 0.2)) * 100;

                if ($matchScore > $bestScore) {
                    $bestScore = $matchScore;
                    $bestMatch = [
                        'skill' => $candidateSkill,
                        'similarity_score' => $similarityScore,
                        'match_score' => $matchScore
                    ];
                }
            }
        }

        return $bestMatch;
    }
}
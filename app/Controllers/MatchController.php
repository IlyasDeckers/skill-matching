<?php

namespace App\Controllers;

use App\Models\Candidate;
use App\Models\Job;
use App\Models\MatchResult;

class MatchController extends Controller
{
    private SkillMatchingService $matchingService;

    /**
     * Constructor
     *
     * @param SkillMatchingService $matchingService
     */
    public function __construct(SkillMatchingService $matchingService)
    {
        $this->matchingService = $matchingService;
    }

    /**
     * Calculate match between a candidate and job
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function calculateMatch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'candidate_id' => 'required|exists:candidates,id',
            'job_id' => 'required|exists:jobs,id',
        ]);

        $candidate = Candidate::findOrFail($validated['candidate_id']);
        $job = Job::findOrFail($validated['job_id']);

        $matchResult = $this->matchingService->calculateMatch($candidate, $job);

        return response()->json(['data' => $matchResult]);
    }

    /**
     * Calculate matches for multiple candidates and jobs
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function calculateBatchMatches(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'candidate_ids' => 'required|array',
            'candidate_ids.*' => 'exists:candidates,id',
            'job_ids' => 'required|array',
            'job_ids.*' => 'exists:jobs,id',
        ]);

        $candidates = Candidate::whereIn('id', $validated['candidate_ids'])->get();
        $jobs = Job::whereIn('id', $validated['job_ids'])->get();

        $results = $this->matchingService->calculateBatchMatches($candidates, $jobs);

        return response()->json(['data' => $results]);
    }

    /**
     * Get match result details
     *
     * @param int $id
     * @return JsonResponse
     */
    public function getMatchDetails(int $id): JsonResponse
    {
        $matchResult = MatchResult::with(['candidate', 'job'])->findOrFail($id);
        return response()->json(['data' => $matchResult]);
    }
}
<?php

namespace App\Controllers;

use App\Models\Candidate;

class CandidateController extends Controller
{
    /**
     * Get all candidates
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $candidates = Candidate::all();
        return response()->json(['data' => $candidates]);
    }

    /**
     * Get a specific candidate with their skills
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $candidate = Candidate::with(['skills' => function($query) {
            $query->orderBy('name');
        }])->findOrFail($id);

        return response()->json(['data' => $candidate]);
    }

    /**
     * Get the best matching jobs for a candidate
     *
     * @param int $id
     * @param SkillMatchingService $matchingService
     * @return JsonResponse
     */
    public function getMatchingJobs(int $id, SkillMatchingService $matchingService): JsonResponse
    {
        $candidate = Candidate::findOrFail($id);
        $matchResults = $matchingService->getTopJobsForCandidate($candidate);

        return response()->json(['data' => $matchResults]);
    }
}
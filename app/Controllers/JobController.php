<?php

namespace App\Controllers;

use App\Models\Job;

class JobController extends Controller
{
    /**
     * Get all jobs
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $jobs = Job::all();
        return response()->json(['data' => $jobs]);
    }

    /**
     * Get a specific job with required and preferred skills
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $job = Job::with(['requiredSkills', 'preferredSkills'])->findOrFail($id);
        return response()->json(['data' => $job]);
    }

    /**
     * Get the best matching candidates for a job
     *
     * @param int $id
     * @param SkillMatchingService $matchingService
     * @return JsonResponse
     */
    public function getMatchingCandidates(int $id, SkillMatchingService $matchingService): JsonResponse
    {
        $job = Job::findOrFail($id);
        $matchResults = $matchingService->getTopCandidatesForJob($job);

        return response()->json(['data' => $matchResults]);
    }
}
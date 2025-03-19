<?php

namespace App\Controllerspi;

//use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\Job;
use App\Models\Skill;
use App\Models\MatchResult;
use App\Services\SkillMatchingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SkillController // extends Controller
{
    /**
     * Get all skills
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $skills = Skill::all();
        return response()->json(['data' => $skills]);
    }

    /**
     * Get a specific skill with related skills
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $skill = Skill::with('relatedSkills')->findOrFail($id);
        return response()->json(['data' => $skill]);
    }
}
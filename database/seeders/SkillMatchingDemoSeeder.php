<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Skill;
use App\Models\Candidate;
use App\Models\Job;
use Carbon\Carbon;

class SkillMatchingDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing data
        DB::table('match_results')->truncate();
        DB::table('job_preferred_skills')->truncate();
        DB::table('job_required_skills')->truncate();
        DB::table('candidate_skills')->truncate();
        DB::table('skill_relationships')->truncate();
        DB::table('jobs')->truncate();
        DB::table('candidates')->truncate();
        DB::table('skills')->truncate();

        // Import skills
        $skillsData = json_decode(file_get_contents(database_path('seeders/data/it-skills-data.json')), true);

        foreach ($skillsData['skills'] as $skillData) {
            Skill::create([
                'id' => $skillData['id'],
                'name' => $skillData['name'],
                'category' => $skillData['category'],
                'popularity' => $skillData['popularity'],
                'is_growing' => $skillData['is_growing'],
            ]);
        }

        // Import skill relationships
        foreach ($skillsData['skill_adjacency'] as $relation) {
            DB::table('skill_relationships')->insert([
                'skill_id' => $relation['skill_id1'],
                'related_skill_id' => $relation['skill_id2'],
                'similarity_score' => $relation['similarity_score'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Add reverse relationship if not already present
            $exists = DB::table('skill_relationships')
                ->where('skill_id', $relation['skill_id2'])
                ->where('related_skill_id', $relation['skill_id1'])
                ->exists();

            if (!$exists) {
                DB::table('skill_relationships')->insert([
                    'skill_id' => $relation['skill_id2'],
                    'related_skill_id' => $relation['skill_id1'],
                    'similarity_score' => $relation['similarity_score'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Import candidates and jobs
        $candidateJobData = json_decode(file_get_contents(database_path('seeders/data/candidate-job-data.json')), true);

        // Import candidates
        foreach ($candidateJobData['candidates'] as $candidateData) {
            $candidate = Candidate::create([
                'id' => $candidateData['id'],
                'name' => $candidateData['name'],
                'email' => $candidateData['email'],
                'years_experience' => $candidateData['years_experience'],
            ]);

            // Add candidate skills
            foreach ($candidateData['skills'] as $skillData) {
                DB::table('candidate_skills')->insert([
                    'candidate_id' => $candidate->id,
                    'skill_id' => $skillData['skill_id'],
                    'proficiency_level' => $skillData['proficiency_level'],
                    'years_experience' => $skillData['years_experience'],
                    'last_used_date' => Carbon::parse($skillData['last_used_date']),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Import jobs
        foreach ($candidateJobData['jobs'] as $jobData) {
            $job = Job::create([
                'id' => $jobData['id'],
                'title' => $jobData['title'],
                'description' => $jobData['description'],
                'years_experience_required' => $jobData['years_experience_required'],
            ]);

            // Add required skills
            foreach ($jobData['required_skills'] as $skillData) {
                DB::table('job_required_skills')->insert([
                    'job_id' => $job->id,
                    'skill_id' => $skillData['skill_id'],
                    'importance_weight' => $skillData['importance_weight'],
                    'minimum_years' => $skillData['minimum_years'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Add preferred skills
            foreach ($jobData['preferred_skills'] as $skillData) {
                DB::table('job_preferred_skills')->insert([
                    'job_id' => $job->id,
                    'skill_id' => $skillData['skill_id'],
                    'importance_weight' => $skillData['importance_weight'],
                    'minimum_years' => $skillData['minimum_years'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $this->command->info('Skill matching demo data imported successfully!');
    }
}
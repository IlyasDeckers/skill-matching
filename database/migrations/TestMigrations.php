<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('skills', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category');
            $table->float('popularity')->default(0.5);
            $table->boolean('is_growing')->default(false);
            $table->timestamps();
        });

        Schema::create('skill_relationships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('skill_id')->constrained()->onDelete('cascade');
            $table->foreignId('related_skill_id')->constrained('skills')->onDelete('cascade');
            $table->float('similarity_score')->default(0.5);
            $table->timestamps();

            $table->unique(['skill_id', 'related_skill_id']);
        });

        Schema::create('candidates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->integer('years_experience')->default(0);
            $table->timestamps();
        });

        Schema::create('candidate_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained()->onDelete('cascade');
            $table->foreignId('skill_id')->constrained()->onDelete('cascade');
            $table->integer('proficiency_level')->default(1);
            $table->integer('years_experience')->default(0);
            $table->date('last_used_date')->nullable();
            $table->timestamps();

            $table->unique(['candidate_id', 'skill_id']);
        });

        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->integer('years_experience_required')->default(0);
            $table->timestamps();
        });

        Schema::create('job_required_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained()->onDelete('cascade');
            $table->foreignId('skill_id')->constrained()->onDelete('cascade');
            $table->integer('importance_weight')->default(5);
            $table->integer('minimum_years')->default(0);
            $table->timestamps();

            $table->unique(['job_id', 'skill_id']);
        });

        Schema::create('job_preferred_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained()->onDelete('cascade');
            $table->foreignId('skill_id')->constrained()->onDelete('cascade');
            $table->integer('importance_weight')->default(3);
            $table->integer('minimum_years')->default(0);
            $table->timestamps();

            $table->unique(['job_id', 'skill_id']);
        });

        Schema::create('match_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained()->onDelete('cascade');
            $table->foreignId('job_id')->constrained()->onDelete('cascade');
            $table->float('match_score')->default(0);
            $table->float('required_skills_score')->default(0);
            $table->float('preferred_skills_score')->default(0);
            $table->float('experience_score')->default(0);
            $table->json('breakdown')->nullable();
            $table->timestamps();

            $table->unique(['candidate_id', 'job_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('match_results');
        Schema::dropIfExists('job_preferred_skills');
        Schema::dropIfExists('job_required_skills');
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('candidate_skills');
        Schema::dropIfExists('candidates');
        Schema::dropIfExists('skill_relationships');
        Schema::dropIfExists('skills');
    }
};
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
        Schema::create('resume_analyses', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('status', 20)->default('queued')->index(); // queued, processing, done, failed
            $table->string('file_path')->nullable(); // private storage path (cleared after deletion)
            $table->string('mime_type')->nullable(); // display only
            $table->string('resume_file_name')->nullable(); // display only
            $table->longText('resume_text')->nullable(); // extracted plain text
            $table->unsignedInteger('word_count')->default(0);
            $table->unsignedInteger('page_count')->default(1);

            $table->string('candidate_name')->nullable();
            $table->string('target_role')->nullable();
            $table->text('job_description')->nullable();

            $table->string('ai_provider')->nullable(); // gemini, openrouter
            $table->string('ai_model')->nullable();

            $table->unsignedTinyInteger('overall_score')->default(0);
            $table->unsignedTinyInteger('rule_score')->default(0);
            $table->unsignedTinyInteger('ats_score')->default(0); // ATS readability score
            $table->unsignedTinyInteger('impact_score')->default(0);
            $table->unsignedTinyInteger('clarity_score')->default(0);
            $table->unsignedTinyInteger('relevance_score')->default(0);
            $table->unsignedTinyInteger('job_match_score')->nullable();

            $table->json('rule_results')->nullable(); // structured pass/fail checks & fixes
            $table->json('result_json')->nullable(); // AI structured feedback
            $table->text('error_message')->nullable();

            $table->timestamps();
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resume_analyses');
    }
};

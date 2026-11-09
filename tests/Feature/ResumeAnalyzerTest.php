<?php

use App\Jobs\AnalyzeResumeJob;
use App\Models\ResumeAnalysis;
use App\Services\DocumentParserService;
use App\Services\GeminiService;
use App\Services\OpenRouterService;
use App\Services\RuleCheckerService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

it('renders the resume checker homepage and responsive navigation', function () {
    $response = $this->get(route('home'));

    $response->assertStatus(200);
    $response->assertSee('Resume Review');
    $response->assertSee('Check your resume.');
    $response->assertSee('Upload your resume for a free review');
    $response->assertSee('Review My CV Now');
    $response->assertSee('mobileNavigationToggle', false);
    $response->assertDontSee('enhancv');
    $response->assertDontSee('256-bit encryption');
});

it('submits a resume file, stores privately, and dispatches queued job', function () {
    Queue::fake();
    Storage::fake('local');

    $file = UploadedFile::fake()->create('john_doe.pdf', 500, 'application/pdf');

    $response = $this->post(route('analyses.analyze'), [
        'resume_file' => $file,
        'target_role' => 'Senior Backend Engineer',
        'job_description' => 'Looking for senior engineer with Laravel and AWS experience.',
        'provider' => 'gemini',
    ]);

    $analysis = ResumeAnalysis::latest()->first();
    expect($analysis)->not->toBeNull();
    expect($analysis->status)->toBe('queued');
    expect($analysis->resume_file_name)->toBe('john_doe.pdf');
    expect($analysis->file_path)->not->toBeNull();
    expect(Storage::disk('local')->exists($analysis->file_path))->toBeTrue();

    Queue::assertPushed(AnalyzeResumeJob::class, function ($job) use ($analysis) {
        return $job->resumeAnalysisId === $analysis->id;
    });

    $response->assertRedirect(route('analyses.show', $analysis));
});

it('executes AnalyzeResumeJob end-to-end and marks record done', function () {
    Storage::fake('local');

    $resumeContent = <<<EOT
Johnathan Doe
New York, NY | john@example.com | 555-0199 | linkedin.com/in/johndoe

PROFESSIONAL SUMMARY
Results-driven software engineer with 6 years experience building scalable Laravel web applications.

WORK EXPERIENCE
Senior Software Engineer | Acme Corp | Jan 2021 – Present
• Architected multi-tenant SaaS billing platform handling \$12M annual transactions.
• Reduced 95th-percentile API response latency from 680ms to 110ms across 15 endpoints.

Full Stack Developer | TechStart | Jan 2018 – Dec 2020
• Developed REST APIs in Laravel and PostgreSQL supporting 100K active users.

EDUCATION
Bachelor of Science in Computer Science | University of Michigan | 2014 – 2018

SKILLS
PHP, Laravel, MySQL, Redis, AWS, Docker, Git
EOT;

    $path = 'resumes/test_resume.txt';
    Storage::disk('local')->put($path, $resumeContent);

    $analysis = ResumeAnalysis::create([
        'status' => 'queued',
        'file_path' => $path,
        'resume_file_name' => 'test_resume.txt',
        'mime_type' => 'text/plain',
        'target_role' => 'Senior Backend Engineer',
        'ai_provider' => 'gemini',
        'ai_model' => 'gemini-3.1-flash-lite',
    ]);

    Http::fake([
        '*generativelanguage.googleapis.com*' => Http::response([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => json_encode([
                                    'candidate_name' => 'Johnathan Doe',
                                    'detected_role' => 'Senior Software Engineer',
                                    'summary_verdict' => 'Strong engineering profile with excellent metric quantification.',
                                    'category_scores' => [
                                        'impact' => 88,
                                        'clarity' => 85,
                                        'relevance' => 90,
                                    ],
                                    'strengths' => [
                                        ['title' => 'Strong Metrics', 'description' => 'Quantifies financial and performance outcomes.'],
                                    ],
                                    'priority_issues' => [
                                        ['severity' => 'low', 'section' => 'Skills', 'problem' => 'Missing Kubernetes', 'fix' => 'Add container orchestration.'],
                                    ],
                                    'bullet_point_rewrites' => [
                                        [
                                            'original' => 'Reduced API latency.',
                                            'improved' => 'Optimized database indexes reducing 95th-percentile API latency by 83% across 15 endpoints.',
                                            'reasoning' => 'Precise metric.',
                                        ],
                                    ],
                                    'missing_keywords' => ['Kubernetes', 'CI/CD'],
                                    'job_match_percentage' => null,
                                ]),
                            ],
                        ],
                    ],
                ],
            ],
        ], 200),
    ]);

    $job = new AnalyzeResumeJob($analysis->id);
    $job->handle(
        app(DocumentParserService::class),
        app(RuleCheckerService::class),
        app(GeminiService::class),
        app(OpenRouterService::class)
    );

    $analysis->refresh();
    expect($analysis->status)->toBe('done');
    expect($analysis->candidate_name)->toBe('Johnathan Doe');
    expect($analysis->overall_score)->toBeGreaterThan(70);
    expect($analysis->ats_score)->toBeGreaterThan(70);
    expect($analysis->impact_score)->toBe(88);
    expect($analysis->clarity_score)->toBe(85);
    expect($analysis->relevance_score)->toBe(90);
    expect(count($analysis->rule_results['checks']))->toBeGreaterThan(10);
    expect(count($analysis->bullet_point_rewrites))->toBe(1);

    // Private file should be deleted upon completion
    expect($analysis->file_path)->toBeNull();
});

it('does not substitute placeholder scores when the AI response omits categories', function () {
    config(['services.gemini.api_key' => 'test-key']);

    $analysis = ResumeAnalysis::create([
        'status' => 'queued',
        'resume_text' => str_repeat('Software engineer experience education skills contact details. ', 12),
        'ai_provider' => 'gemini',
        'ai_model' => 'gemini-3.1-flash-lite',
    ]);

    Http::fake([
        '*generativelanguage.googleapis.com*' => Http::response([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            ['text' => json_encode(['summary_verdict' => 'Resume review completed.'])],
                        ],
                    ],
                ],
            ],
        ], 200),
    ]);

    $job = new AnalyzeResumeJob($analysis->id);
    expect(fn () => $job->handle(
        app(DocumentParserService::class),
        app(RuleCheckerService::class),
        app(GeminiService::class),
        app(OpenRouterService::class)
    ))->toThrow(\UnexpectedValueException::class, 'The AI response did not include category scores.');

    $analysis->refresh();
    expect($analysis->status)->toBe('processing');
    expect($analysis->impact_score)->toBe(0);
    expect($analysis->overall_score)->toBe(0);
});

it('returns JSON status response for the reactive polling endpoint', function () {
    $analysis = ResumeAnalysis::create([
        'status' => 'processing',
        'candidate_name' => 'Sara Ali',
        'overall_score' => 0,
    ]);

    $response = $this->getJson(route('analyses.status', $analysis));

    $response->assertStatus(200);
    $response->assertJson([
        'uuid' => $analysis->uuid,
        'status' => 'processing',
        'is_finished' => false,
        'overall_score' => null,
        'error' => null,
    ]);

    // When status is done
    $analysis->update(['status' => 'done', 'overall_score' => 86]);
    $doneResponse = $this->getJson(route('analyses.status', $analysis));
    $doneResponse->assertJson([
        'status' => 'done',
        'is_finished' => true,
        'overall_score' => 86,
    ]);
});

it('renders the pending state on report page with auto-poller script', function () {
    $analysis = ResumeAnalysis::create([
        'status' => 'queued',
        'candidate_name' => 'Pending Candidate',
    ]);

    $response = $this->get(route('analyses.show', $analysis));
    $response->assertStatus(200);
    $response->assertSee('Your resume is in the queue');
    $response->assertSee('Checking status automatically every 2 seconds');

    $historyResponse = $this->get(route('analyses.history'));
    $historyResponse->assertSee('In progress');
});

it('renders the failed state when analysis fails', function () {
    $analysis = ResumeAnalysis::create([
        'status' => 'failed',
        'error_message' => 'This resume appears to be a scanned image with no selectable text layer.',
    ]);

    $response = $this->get(route('analyses.show', $analysis));
    $response->assertStatus(200);
    $response->assertSee('Analysis Could Not Be Completed');
    $response->assertSee('scanned image with no selectable text layer');
    $response->assertSee('Upload Another Resume');
});

it('renders the completed resume report with readability checks and suggested rewrites', function () {
    $ruleResults = app(RuleCheckerService::class)->evaluate(
        "John Doe\njohn@example.com | 555-0100\n\nEXPERIENCE\nSoftware Engineer at Acme (Jan 2021 - Present)\n• Built APIs.\n\nEDUCATION\nBS Computer Science (2018)\n\nSKILLS\nPHP, Laravel",
        60,
        1
    );

    $analysis = ResumeAnalysis::create([
        'status' => 'done',
        'candidate_name' => 'Priya Sharma',
        'target_role' => 'Machine Learning Engineer',
        'resume_text' => 'Priya Sharma ML engineer with 4 years experience...',
        'ai_provider' => 'gemini',
        'ai_model' => 'gemini-3.1-flash-lite',
        'overall_score' => 88,
        'ats_score' => 85,
        'impact_score' => 85,
        'clarity_score' => 88,
        'relevance_score' => 90,
        'word_count' => 520,
        'page_count' => 1,
        'rule_results' => $ruleResults,
        'result_json' => [
            'summary_verdict' => 'Outstanding candidate profile for Machine Learning.',
            'category_scores' => ['impact' => 85, 'clarity' => 88, 'relevance' => 90],
            'strengths' => [
                ['title' => 'Clear Metrics', 'description' => 'Great numbers.'],
            ],
            'priority_issues' => [
                ['severity' => 'high', 'section' => 'Experience', 'problem' => 'Missing MLOps CI/CD', 'fix' => 'Add model deployment pipelines.'],
            ],
            'bullet_point_rewrites' => [
                [
                    'original' => 'Served ML models.',
                    'improved' => 'Deployed real-time inference microservice serving 450 req/sec at 45ms latency.',
                    'reasoning' => 'Quantified latency.',
                ],
            ],
            'missing_keywords' => ['MLflow', 'Kubernetes'],
            'interview_prep' => [
                ['question' => 'How do you handle model latency?', 'category' => 'System Design', 'tip' => 'Discuss batching.'],
            ],
        ],
    ]);

    $response = $this->get(route('analyses.show', $analysis));
    $response->assertStatus(200);
    $response->assertSee('Priya Sharma');
    $response->assertSee('88');
    $response->assertSee('Impact');
    $response->assertSee('Clarity');
    $response->assertSee('Relevance');
    $response->assertSee('ATS Readability');
    $response->assertSee('Pass / Fail Checklist');
    $response->assertSee('Bullet Point Rewrites');
    $response->assertSee('Missing MLOps CI/CD');
    $response->assertSee('MLflow');
});

it('validates file upload max 2MB and job description max 8000 chars', function () {
    // 3MB file (> 2MB limit)
    $largeFile = UploadedFile::fake()->create('large_resume.pdf', 3000, 'application/pdf');

    $response = $this->post(route('analyses.analyze'), [
        'resume_file' => $largeFile,
    ]);

    $response->assertSessionHasErrors(['resume_file']);

    // Oversized JD (> 8000 chars)
    $oversizedJd = str_repeat('a', 8050);
    $validFile = UploadedFile::fake()->create('resume.pdf', 500, 'application/pdf');

    $responseJd = $this->post(route('analyses.analyze'), [
        'resume_file' => $validFile,
        'job_description' => $oversizedJd,
    ]);

    $responseJd->assertSessionHasErrors(['job_description']);
});

it('exports analysis report to printable format and JSON', function () {
    $analysis = ResumeAnalysis::create([
        'status' => 'done',
        'candidate_name' => 'Marcus Brody',
        'target_role' => 'Data Curator',
        'overall_score' => 79,
        'ats_score' => 75,
        'result_json' => [
            'summary_verdict' => 'Solid cataloging accuracy.',
            'category_scores' => ['impact' => 75, 'clarity' => 80, 'relevance' => 78],
            'bullet_point_rewrites' => [
                [
                    'original' => 'Managed archives.',
                    'improved' => 'Digitized 14,000 artifacts with 99.8% precision.',
                    'reasoning' => 'Adds quantification.',
                ],
            ],
        ],
    ]);

    $printResponse = $this->get(route('analyses.export', $analysis));
    $printResponse->assertStatus(200);
    $printResponse->assertSee('Marcus Brody');

    $jsonResponse = $this->get(route('analyses.export-json', $analysis));
    $jsonResponse->assertStatus(200);
    $jsonResponse->assertHeader('Content-Disposition');
    expect($jsonResponse->json('candidate_name'))->toBe('Marcus Brody');
});

it('does not export scores for analyses that are not complete', function () {
    $analysis = ResumeAnalysis::create([
        'status' => 'queued',
        'overall_score' => 0,
        'ats_score' => 0,
    ]);

    $this->get(route('analyses.export', $analysis))
        ->assertRedirect(route('analyses.show', $analysis));

    $this->getJson(route('analyses.export-json', $analysis))
        ->assertJson([
            'status' => 'queued',
            'overall_score' => null,
            'ats_readability_score' => null,
            'category_scores' => null,
            'rule_results' => null,
            'ai_report' => null,
        ]);
});

it('can delete an analysis from history', function () {
    $analysis = ResumeAnalysis::create([
        'status' => 'done',
        'candidate_name' => 'To Be Deleted',
        'overall_score' => 70,
        'ats_score' => 70,
    ]);

    $response = $this->delete(route('analyses.destroy', $analysis));
    $response->assertRedirect(route('analyses.history'));

    expect(ResumeAnalysis::find($analysis->id))->toBeNull();
});

it('runs resumes:cleanup command and deletes records older than 7 days', function () {
    // Recent record (2 days ago)
    $recent = ResumeAnalysis::create([
        'status' => 'done',
        'candidate_name' => 'Recent Candidate',
    ]);
    $recent->created_at = now()->subDays(2);
    $recent->save();

    // Old record (10 days ago)
    $old = ResumeAnalysis::create([
        'status' => 'done',
        'candidate_name' => 'Old Candidate',
    ]);
    $old->created_at = now()->subDays(10);
    $old->save();

    $this->artisan('resumes:cleanup --days=7')
        ->assertExitCode(0);

    expect(ResumeAnalysis::find($recent->id))->not->toBeNull();
    expect(ResumeAnalysis::find($old->id))->toBeNull();
});

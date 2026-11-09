<?php

namespace App\Http\Controllers;

use App\Jobs\AnalyzeResumeJob;
use App\Models\ResumeAnalysis;
use App\Services\DocumentParserService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ResumeAnalysisController extends Controller
{
    public function __construct(
        protected DocumentParserService $parserService
    ) {}

    /**
     * Display the CV / Resume Checker homepage.
     */
    public function index(): View
    {
        $recentAnalyses = ResumeAnalysis::latest()->take(6)->get();

        return view('resumes.index', compact('recentAnalyses'));
    }

    /**
     * Handle resume upload & dispatch background analysis pipeline.
     */
    public function analyze(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'resume_file' => [
                'nullable',
                'file',
                'max:2048', // 2 MB limit
                'mimes:pdf,docx',
                'mimetypes:application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/zip',
            ],
            'resume_text' => ['nullable', 'string', 'max:50000'],
            'target_role' => ['nullable', 'string', 'max:255'],
            'job_description' => ['nullable', 'string', 'max:8000'], // 8,000 char cap
            'provider' => ['nullable', 'string', 'in:gemini,openrouter'],
        ], [
            'resume_file.max' => 'The resume file may not be greater than 2 MB.',
            'resume_file.mimes' => 'We accept only PDF and DOCX documents.',
            'job_description.max' => 'The target job description may not be greater than 8,000 characters.',
        ]);

        $storedPath = null;
        $originalName = null;
        $mimeType = null;
        $pastedText = null;

        if ($request->hasFile('resume_file')) {
            $file = $request->file('resume_file');
            $originalName = $file->getClientOriginalName();
            $mimeType = $file->getClientMimeType() ?: $file->getMimeType();

            // Save to private local disk with a random name
            $storedPath = Storage::disk('local')->putFile('resumes', $file);
        } elseif (!empty(trim((string) $request->input('resume_text')))) {
            $pastedText = $this->parserService->cleanExtractedText((string) $request->input('resume_text'));
            $originalName = 'Pasted_Resume.txt';
            $mimeType = 'text/plain';

            if (mb_strlen($pastedText) < 30) {
                return back()->withInput()->withErrors([
                    'resume_file' => 'Please provide at least 30 characters of resume content.',
                ]);
            }
        } else {
            return back()->withInput()->withErrors([
                'resume_file' => 'Please select a resume file (PDF or DOCX, max 2MB) or paste your resume text.',
            ]);
        }

        $provider = $validated['provider'] ?? config('services.ai.default_provider', 'gemini');
        $model = ($provider === 'openrouter')
            ? config('services.openrouter.default_model', 'deepseek/deepseek-chat')
            : config('services.gemini.default_model', 'gemini-3.1-flash-lite');

        // Create the record in queued state
        $analysis = ResumeAnalysis::create([
            'uuid' => (string) Str::uuid(),
            'status' => 'queued',
            'file_path' => $storedPath,
            'resume_file_name' => $originalName,
            'mime_type' => $mimeType,
            'resume_text' => $pastedText,
            'target_role' => $validated['target_role'] ?? null,
            'job_description' => $validated['job_description'] ?? null,
            'ai_provider' => $provider,
            'ai_model' => $model,
        ]);

        // Dispatch background queue job
        AnalyzeResumeJob::dispatch($analysis->id);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'redirect_url' => route('analyses.show', $analysis),
                'uuid' => $analysis->uuid,
            ]);
        }

        return redirect()->route('analyses.show', $analysis);
    }

    /**
     * Display the report page (Pending, Failed, or Done).
     */
    public function show(ResumeAnalysis $analysis): View
    {
        return view('resumes.show', compact('analysis'));
    }

    /**
     * Poll status endpoint for the report page (every 2 seconds).
     */
    public function status(ResumeAnalysis $analysis): JsonResponse
    {
        // If still queued, trigger a queue processing tick so local dev without active worker still succeeds
        if ($analysis->status === 'queued') {
            try {
                \Illuminate\Support\Facades\Artisan::call('queue:work', [
                    '--once' => true,
                    '--stop-when-empty' => true,
                    '--tries' => 2,
                    '--timeout' => 90,
                ]);
                $analysis->refresh();
            } catch (\Throwable) {
                // Background worker will handle it
            }
        }

        return response()->json([
            'uuid' => $analysis->uuid,
            'status' => $analysis->status,
            'is_finished' => in_array($analysis->status, ['done', 'failed'], true),
            'overall_score' => $analysis->isDone() ? $analysis->overall_score : null,
            'error' => $analysis->error_message,
            'redirect_url' => route('analyses.show', $analysis),
        ]);
    }

    /**
     * Past audits history page.
     */
    public function history(Request $request): View
    {
        $query = ResumeAnalysis::latest();

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('candidate_name', 'like', "%{$search}%")
                  ->orWhere('target_role', 'like', "%{$search}%")
                  ->orWhere('resume_file_name', 'like', "%{$search}%");
            });
        }

        $analyses = $query->paginate(15)->withQueryString();

        return view('resumes.history', compact('analyses'));
    }

    /**
     * Print / PDF export view.
     */
    public function export(ResumeAnalysis $analysis): View|RedirectResponse
    {
        if (!$analysis->isDone()) {
            return redirect()->route('analyses.show', $analysis);
        }

        return view('resumes.export', compact('analysis'));
    }

    /**
     * Export raw JSON report.
     */
    public function exportJson(ResumeAnalysis $analysis): JsonResponse
    {
        return response()->json([
            'uuid' => $analysis->uuid,
            'candidate_name' => $analysis->candidate_name,
            'target_role' => $analysis->target_role,
            'status' => $analysis->status,
            'overall_score' => $analysis->isDone() ? $analysis->overall_score : null,
            'ats_readability_score' => $analysis->isDone() ? $analysis->ats_score : null,
            'category_scores' => $analysis->isDone() ? $analysis->category_scores : null,
            'rule_results' => $analysis->isDone() ? $analysis->rule_results : null,
            'ai_report' => $analysis->isDone() ? $analysis->result_json : null,
            'created_at' => $analysis->created_at->toIso8601String(),
        ], 200, [
            'Content-Disposition' => "attachment; filename=\"resume_audit_{$analysis->uuid}.json\"",
        ]);
    }

    /**
     * Delete an analysis report.
     */
    public function destroy(ResumeAnalysis $analysis): RedirectResponse
    {
        if ($analysis->file_path && Storage::disk('local')->exists($analysis->file_path)) {
            Storage::disk('local')->delete($analysis->file_path);
        }

        $analysis->delete();

        return redirect()->route('analyses.history')->with('status', 'Audit report deleted successfully.');
    }
}
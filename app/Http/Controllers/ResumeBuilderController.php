<?php

namespace App\Http\Controllers;

use App\Services\ResumeBuilderAiService;
use App\Services\ResumePdfGenerator;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ResumeBuilderController extends Controller
{
    /**
     * @var array<string, string>
     */
    private const TEMPLATES = [
        'classic' => 'Classic',
        'modern' => 'Modern',
        'compact' => 'Compact',
        'minimal' => 'Minimal',
    ];

    public function __construct(
        private ResumeBuilderAiService $aiService,
        private ResumePdfGenerator $pdfGenerator
    ) {}

    public function index(): View
    {
        return view('resumes.builder-templates', [
            'templates' => self::TEMPLATES,
        ]);
    }

    public function edit(string $template): View
    {
        abort_unless(array_key_exists($template, self::TEMPLATES), 404);

        return view('resumes.builder', [
            'template' => $template,
            'templateLabel' => self::TEMPLATES[$template],
        ]);
    }

    public function assist(Request $request): JsonResponse
    {
        $validated = $this->validateResume($request);

        try {
            return response()->json(
                $this->aiService->suggestImprovements($this->resumeData($validated))
            );
        } catch (Exception $exception) {
            Log::warning('AI resume builder assistance failed', [
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'AI suggestions could not be generated. Check the AI provider configuration and try again.',
            ], 503);
        }
    }

    public function download(Request $request): Response
    {
        $validated = $this->validateResume($request);
        $resume = $this->resumeData($validated);
        $pdf = $this->pdfGenerator->generate($resume, $validated['template']);
        $filename = Str::slug($resume['name']) ?: 'resume';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '.pdf"',
            'Content-Length' => (string) strlen($pdf),
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateResume(Request $request): array
    {
        return $request->validate([
            'template' => ['required', 'string', 'in:' . implode(',', array_keys(self::TEMPLATES))],
            'resume' => [
                'required',
                'array:name,target_role,email,phone,location,website,linkedin,summary,experience,education,skills,certifications',
            ],
            'resume.name' => ['required', 'string', 'max:120'],
            'resume.target_role' => ['required', 'string', 'max:160'],
            'resume.email' => ['nullable', 'email', 'max:254'],
            'resume.phone' => ['nullable', 'string', 'max:60'],
            'resume.location' => ['nullable', 'string', 'max:120'],
            'resume.website' => ['nullable', 'url', 'max:255'],
            'resume.linkedin' => ['nullable', 'string', 'max:255'],
            'resume.summary' => ['nullable', 'string', 'max:2500', 'required_without:resume.experience'],
            'resume.experience' => ['nullable', 'string', 'max:16000', 'required_without:resume.summary'],
            'resume.education' => ['nullable', 'string', 'max:6000'],
            'resume.skills' => ['nullable', 'string', 'max:4000'],
            'resume.certifications' => ['nullable', 'string', 'max:4000'],
        ]);
    }

    /**
     * @param array<string, mixed> $validated
     * @return array<string, string>
     */
    private function resumeData(array $validated): array
    {
        $resume = $validated['resume'];

        return [
            'name' => $resume['name'],
            'target_role' => $resume['target_role'],
            'email' => $resume['email'] ?? '',
            'phone' => $resume['phone'] ?? '',
            'location' => $resume['location'] ?? '',
            'website' => $resume['website'] ?? '',
            'linkedin' => $resume['linkedin'] ?? '',
            'summary' => $resume['summary'] ?? '',
            'experience' => $resume['experience'] ?? '',
            'education' => $resume['education'] ?? '',
            'skills' => $resume['skills'] ?? '',
            'certifications' => $resume['certifications'] ?? '',
        ];
    }
}

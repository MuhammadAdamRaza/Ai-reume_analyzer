<?php

namespace App\Services;

use UnexpectedValueException;

class ResumeBuilderAiService
{
    public function __construct(
        private GeminiService $geminiService,
        private OpenRouterService $openRouterService
    ) {}

    /**
     * Generate an AI-assisted summary and experience rewrites from supplied facts.
     *
     * @param array<string, string> $resume
     * @return array{summary: string, rewrites: list<array{original: string, improved: string}>}
     */
    public function suggestImprovements(array $resume): array
    {
        $provider = match (config('services.ai.default_provider', 'gemini')) {
            'openrouter' => $this->openRouterService,
            default => $this->geminiService,
        };

        $resumeText = $this->buildResumeText($resume);
        $result = $provider->analyzeResume(
            resumeText: $resumeText,
            targetRole: $resume['target_role']
        );

        $summary = trim((string) ($result['improved_summary'] ?? ''));
        $rewrites = [];

        foreach (($result['bullet_point_rewrites'] ?? []) as $rewrite) {
            if (!is_array($rewrite)) {
                continue;
            }

            $original = trim((string) ($rewrite['original'] ?? ''));
            $improved = trim((string) ($rewrite['improved'] ?? ''));

            if ($original !== '' && $improved !== '') {
                $rewrites[] = [
                    'original' => $original,
                    'improved' => $improved,
                ];
            }
        }

        if ($summary === '' && $rewrites === []) {
            throw new UnexpectedValueException('The AI response did not include any usable resume suggestions.');
        }

        return [
            'summary' => $summary,
            'rewrites' => $rewrites,
        ];
    }

    /**
     * @param array<string, string> $resume
     */
    private function buildResumeText(array $resume): string
    {
        $sections = [
            'PROFESSIONAL SUMMARY' => $resume['summary'] ?? '',
            'WORK EXPERIENCE AND ACCOMPLISHMENTS' => $resume['experience'] ?? '',
            'EDUCATION' => $resume['education'] ?? '',
            'SKILLS' => $resume['skills'] ?? '',
            'CERTIFICATIONS' => $resume['certifications'] ?? '',
        ];

        $lines = ['Candidate: ' . $resume['name']];
        foreach ($sections as $heading => $content) {
            if (trim($content) !== '') {
                $safeContent = preg_replace('/<\/?RESUME_DATA>/i', '', $content) ?? $content;
                $lines[] = "{$heading}:\n{$safeContent}";
            }
        }

        return implode("\n\n", $lines);
    }
}

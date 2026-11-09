<?php

namespace App\Services;

use App\Models\ResumeAnalysis;
use Exception;
use UnexpectedValueException;

class ResumeAnalysisService
{
    public function __construct(
        protected GeminiService $geminiService,
        protected OpenRouterService $openRouterService
    ) {}

    /**
     * Analyze a real resume and persist the analysis.
     *
     * @param string $resumeText
     * @param string|null $jobDescription
     * @param string|null $targetRole
     * @param string|null $preferredProvider 'gemini' | 'openrouter'
     * @param string|null $preferredModel
     * @param string|null $fileName
     * @param string|null $customApiKey
     * @return ResumeAnalysis
     * @throws Exception
     */
    public function analyzeAndStore(
        string $resumeText,
        ?string $jobDescription = null,
        ?string $targetRole = null,
        ?string $preferredProvider = null,
        ?string $preferredModel = null,
        ?string $fileName = null,
        ?string $customApiKey = null
    ): ResumeAnalysis {
        $providerKey = strtolower($preferredProvider ?: config('services.ai.default_provider', 'gemini'));

        $provider = match ($providerKey) {
            'openrouter' => $this->openRouterService,
            default => $this->geminiService,
        };

        $actualModelUsed = $preferredModel ?: $provider->getDefaultModel();

        // Perform real AI call
        $result = $provider->analyzeResume(
            resumeText: $resumeText,
            jobDescription: $jobDescription,
            targetRole: $targetRole,
            model: $preferredModel,
            customApiKey: $customApiKey
        );

        $overallScore = $result['overall_score'] ?? null;
        $atsScore = $result['ats_check']['score'] ?? $result['scores']['ats_compatibility'] ?? null;
        if (!is_int($overallScore) || $overallScore < 0 || $overallScore > 100) {
            throw new UnexpectedValueException('The AI response did not include a valid overall score.');
        }
        if (!is_int($atsScore) || $atsScore < 0 || $atsScore > 100) {
            throw new UnexpectedValueException('The AI response did not include a valid resume readability score.');
        }

        $candidateName = $result['candidate_name'] ?? 'Candidate';

        return ResumeAnalysis::create([
            'candidate_name' => $candidateName,
            'target_role' => $targetRole ?: ($result['detected_role'] ?? null),
            'job_description' => $jobDescription,
            'resume_file_name' => $fileName,
            'resume_text' => $resumeText,
            'ai_provider' => ($providerKey === 'openrouter') ? 'openrouter' : 'gemini',
            'ai_model' => $actualModelUsed,
            'overall_score' => $overallScore,
            'ats_score' => $atsScore,
            'result_json' => $result,
        ]);
    }

    /**
     * Get system-wide provider configuration details.
     *
     * @return array<string, mixed>
     */
    public function getProvidersStatus(): array
    {
        $geminiConfigured = $this->geminiService->isConfigured();
        $openRouterConfigured = $this->openRouterService->isConfigured();

        return [
            'gemini' => [
                'name' => $this->geminiService->getName(),
                'is_configured' => $geminiConfigured,
                'default_model' => $this->geminiService->getDefaultModel(),
                'models' => $this->geminiService->getAvailableModels(),
            ],
            'openrouter' => [
                'name' => $this->openRouterService->getName(),
                'is_configured' => $openRouterConfigured,
                'default_model' => $this->openRouterService->getDefaultModel(),
                'models' => $this->openRouterService->getAvailableModels(),
            ],
            'any_configured' => $geminiConfigured || $openRouterConfigured,
            'default_provider' => config('services.ai.default_provider', 'gemini'),
        ];
    }
}

<?php

namespace App\Jobs;

use App\Models\ResumeAnalysis;
use App\Services\DocumentParserService;
use App\Services\GeminiService;
use App\Services\OpenRouterService;
use App\Services\RuleCheckerService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;
use UnexpectedValueException;

class AnalyzeResumeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 2;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $resumeAnalysisId
    ) {}

    /**
     * Execute the job pipeline.
     */
    public function handle(
        DocumentParserService $parserService,
        RuleCheckerService $ruleCheckerService,
        GeminiService $geminiService,
        OpenRouterService $openRouterService
    ): void {
        $analysis = ResumeAnalysis::find($this->resumeAnalysisId);
        if (!$analysis) {
            return;
        }

        try {
            // Step 1: Mark record as processing
            $analysis->update([
                'status' => 'processing',
                'error_message' => null,
            ]);

            // Step 2: Extract text from stored file
            $filePath = $analysis->file_path;
            $fullDiskPath = $filePath ? Storage::disk('local')->path($filePath) : null;

            if ($fullDiskPath && file_exists($fullDiskPath)) {
                $parsed = $parserService->extractFromPath($fullDiskPath, $analysis->resume_file_name);
                $resumeText = $parsed['text'];
                $wordCount = $parsed['word_count'];
                $pageCount = $parsed['page_count'];
                $isScanned = $parsed['is_scanned'];
            } else {
                // If text was directly submitted (e.g. text fallback)
                $resumeText = (string) $analysis->resume_text;
                $words = preg_split('/\s+/u', $resumeText, -1, PREG_SPLIT_NO_EMPTY) ?: [];
                $wordCount = count($words);
                $pageCount = max(1, (int) ceil($wordCount / 450));
                $isScanned = $wordCount < 30;
            }

            if ($isScanned || $wordCount < 30) {
                throw new Exception("This file appears to be a scanned image or contains insufficient selectable text. Please upload a text-based PDF or Word document.");
            }

            // Step 3: Run rule-based checks
            $ruleEvaluation = $ruleCheckerService->evaluate(
                resumeText: $resumeText,
                wordCount: $wordCount,
                pageCount: $pageCount,
                isScanned: $isScanned,
                jobDescription: $analysis->job_description
            );

            $ruleScore = $ruleEvaluation['overall_rule_score'];

            // Step 4: Call AI Provider
            $provider = ($analysis->ai_provider === 'openrouter') ? $openRouterService : $geminiService;

            $aiResult = $provider->analyzeResume(
                resumeText: $resumeText,
                jobDescription: $analysis->job_description,
                targetRole: $analysis->target_role,
                model: $analysis->ai_model
            );
            $aiResult['score_evidence'] = $this->verifiedScoreEvidence(
                $aiResult['score_evidence'] ?? [],
                $resumeText
            );

            // Step 5: Calculate combined scores
            $categoryScores = $aiResult['category_scores'] ?? null;
            if (!is_array($categoryScores)) {
                throw new UnexpectedValueException('The AI response did not include category scores.');
            }

            foreach (['impact', 'clarity', 'relevance'] as $category) {
                $score = $categoryScores[$category] ?? null;
                if (!is_int($score) || $score < 0 || $score > 100) {
                    throw new UnexpectedValueException("The AI response included an invalid {$category} score.");
                }
            }

            $impactScore = $categoryScores['impact'];
            $clarityScore = $categoryScores['clarity'];
            $relevanceScore = $categoryScores['relevance'];
            $aiQualityScore = (int) round(($impactScore + $clarityScore + $relevanceScore) / 3);

            $hasJobDesc = !empty(trim((string) $analysis->job_description));
            $jobMatchScore = null;

            if ($hasJobDesc) {
                $jobMatchScore = $aiResult['job_match_percentage'] ?? null;
                if (!is_int($jobMatchScore) || $jobMatchScore < 0 || $jobMatchScore > 100) {
                    throw new UnexpectedValueException('The AI response did not include a valid job match score.');
                }

                // 30% rule score, 40% AI quality, 30% job match
                $overallScore = (int) round(($ruleScore * 0.30) + ($aiQualityScore * 0.40) + ($jobMatchScore * 0.30));
            } else {
                // 50% rule score, 50% AI score
                $overallScore = (int) round(($ruleScore * 0.50) + ($aiQualityScore * 0.50));
            }

            $overallScore = max(0, min(100, $overallScore));

            // Merge detected candidate name from AI if not already set
            $detectedCandidateName = $analysis->candidate_name ?: ($aiResult['candidate_name'] ?? null);

            // Merge missing keywords from rule check and AI check
            $combinedMissingKeywords = array_values(array_unique(array_merge(
                $ruleEvaluation['missing_keywords'] ?? [],
                $aiResult['missing_keywords'] ?? []
            )));
            $aiResult['missing_keywords'] = $combinedMissingKeywords;

            // Step 6: Save results and mark record as done
            $analysis->update([
                'status' => 'done',
                'candidate_name' => $detectedCandidateName,
                'resume_text' => $resumeText,
                'word_count' => $wordCount,
                'page_count' => $pageCount,
                'overall_score' => $overallScore,
                'rule_score' => $ruleScore,
                'ats_score' => $ruleScore, // ATS Readability Score
                'impact_score' => $impactScore,
                'clarity_score' => $clarityScore,
                'relevance_score' => $relevanceScore,
                'job_match_score' => $jobMatchScore,
                'rule_results' => $ruleEvaluation,
                'result_json' => $aiResult,
                'error_message' => null,
            ]);

            // Step 7: Delete uploaded file from private disk and clear file_path
            $this->cleanupFile($analysis);

        } catch (Throwable $e) {
            Log::error('AnalyzeResumeJob failed', [
                'analysis_id' => $analysis->id,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
            ]);

            // If we have exhausted retries or it's a fatal file format error, mark as failed
            if ($this->attempts() >= $this->tries || str_contains($e->getMessage(), 'scanned image')) {
                $analysis->update([
                    'status' => 'failed',
                    'error_message' => $this->formatUserFriendlyError($e->getMessage()),
                ]);

                // Always delete uploaded file even on failure
                $this->cleanupFile($analysis);
            }

            throw $e;
        }
    }

    /**
     * Handle job failure after all retries.
     */
    public function failed(?Throwable $exception): void
    {
        $analysis = ResumeAnalysis::find($this->resumeAnalysisId);
        if ($analysis) {
            $analysis->update([
                'status' => 'failed',
                'error_message' => $exception
                    ? $this->formatUserFriendlyError($exception->getMessage())
                    : 'The resume analysis timed out. Please try again.',
            ]);
            $this->cleanupFile($analysis);
        }
    }

    /**
     * Keep only AI-provided score quotes that can be verified in the resume text.
     *
     * @param mixed $scoreEvidence
     * @return array<string, array{reasoning: string, quotes: list<string>}>
     */
    private function verifiedScoreEvidence(mixed $scoreEvidence, string $resumeText): array
    {
        $verifiedEvidence = [];
        $normalizedResume = $this->normalizeEvidenceText($resumeText);

        foreach (['impact', 'clarity', 'relevance'] as $category) {
            $categoryEvidence = is_array($scoreEvidence) && is_array($scoreEvidence[$category] ?? null)
                ? $scoreEvidence[$category]
                : [];
            $quotes = [];

            foreach (is_array($categoryEvidence['quotes'] ?? null) ? $categoryEvidence['quotes'] : [] as $quote) {
                if (!is_string($quote)) {
                    continue;
                }

                $quote = trim($quote);
                $normalizedQuote = $this->normalizeEvidenceText($quote);

                if (mb_strlen($normalizedQuote) < 8 || !str_contains($normalizedResume, $normalizedQuote)) {
                    continue;
                }

                $quotes[] = $quote;

                if (count($quotes) === 2) {
                    break;
                }
            }

            if ($quotes !== []) {
                $reasoning = $categoryEvidence['reasoning'] ?? '';
                $verifiedEvidence[$category] = [
                    'reasoning' => is_string($reasoning) ? mb_substr(trim($reasoning), 0, 500) : '',
                    'quotes' => array_values(array_unique($quotes)),
                ];
            }
        }

        return $verifiedEvidence;
    }

    private function normalizeEvidenceText(string $text): string
    {
        $normalized = preg_replace('/\s+/u', ' ', trim($text)) ?? '';

        return mb_strtolower($normalized);
    }

    /**
     * Safely delete the stored private file.
     */
    protected function cleanupFile(ResumeAnalysis $analysis): void
    {
        if ($analysis->file_path) {
            try {
                Storage::disk('local')->delete($analysis->file_path);
            } catch (Throwable) {
                // Ignore delete errors
            }
            $analysis->file_path = null;
            $analysis->save();
        }
    }

    /**
     * Convert technical exception message into user-friendly message.
     */
    protected function formatUserFriendlyError(string $rawError): string
    {
        if (str_contains($rawError, 'scanned image') || str_contains($rawError, 'selectable text')) {
            return "This resume appears to be a scanned image with no selectable text layer. Please upload a standard text-based PDF or Word (.docx) document.";
        }

        if (str_contains($rawError, 'API Key') || str_contains($rawError, 'rate limit')) {
            return "The AI analysis service is momentarily experiencing high demand. Please try running your scan again in a moment.";
        }

        if (str_contains($rawError, 'timeout')) {
            return "The analysis timed out while processing your document. Please try submitting again.";
        }

        return "We were unable to complete the analysis on this document. Please verify the file is not password-protected or corrupted, and try again.";
    }
}
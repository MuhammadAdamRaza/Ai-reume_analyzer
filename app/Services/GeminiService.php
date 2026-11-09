<?php

namespace App\Services;

use App\Contracts\AiProviderInterface;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService implements AiProviderInterface
{
    protected ?string $apiKey;
    protected string $baseUrl;
    protected string $defaultModel;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');
        $this->baseUrl = rtrim((string) config('services.gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta'), '/');
        $this->defaultModel = config('services.gemini.default_model', 'gemini-3.1-flash-lite');
    }

    /**
     * Get display name.
     */
    public function getName(): string
    {
        return 'Google Gemini Official';
    }

    /**
     * Check if provider is configured with a real API key.
     */
    public function isConfigured(?string $overrideKey = null): bool
    {
        $key = $overrideKey ?: $this->apiKey;
        if (empty($key)) {
            return false;
        }

        $lowered = strtolower(trim($key));

        return !in_array($lowered, [
            'your_gemini_api_key_here',
            'your_gemini_api_key',
            'placeholder',
            'none',
            'change_me',
        ], true) && !str_contains($lowered, 'placeholder');
    }

    /**
     * Get default model.
     */
    public function getDefaultModel(): string
    {
        return $this->defaultModel;
    }

    /**
     * Get available models.
     */
    public function getAvailableModels(): array
    {
        return [
            'gemini-3.1-flash-lite' => 'Gemini 3.1 Flash Lite (Ultra Fast & Accurate)',
            'gemini-2.5-flash' => 'Gemini 2.5 Flash',
            'gemini-1.5-flash' => 'Gemini 1.5 Flash',
            'gemini-1.5-pro' => 'Gemini 1.5 Pro',
        ];
    }

    /**
     * Analyze resume using Gemini API.
     *
     * @throws Exception
     */
    public function analyzeResume(
        string $resumeText,
        ?string $jobDescription = null,
        ?string $targetRole = null,
        ?string $model = null,
        ?string $customApiKey = null
    ): array {
        $activeKey = $customApiKey ?: $this->apiKey;

        if (!$this->isConfigured($activeKey)) {
            throw new Exception("Google Gemini API Key is missing or using placeholder in .env. Please add your real GEMINI_API_KEY from https://aistudio.google.com/app/apikey to analyze your resume.");
        }

        $chosenModel = $model ?: $this->defaultModel;
        $systemPrompt = AiPromptBuilder::buildSystemPrompt();
        $userPrompt = AiPromptBuilder::buildUserPrompt($resumeText, $jobDescription, $targetRole);

        $endpoint = "{$this->baseUrl}/models/{$chosenModel}:generateContent?key={$activeKey}";

        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $userPrompt],
                    ],
                ],
            ],
            'system_instruction' => [
                'parts' => [
                    ['text' => $systemPrompt],
                ],
            ],
            'generationConfig' => [
                'response_mime_type' => 'application/json',
                'temperature' => 0.2,
                'maxOutputTokens' => 8192,
            ],
        ];

        try {
            $response = Http::timeout(60)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->post($endpoint, $payload);

            if (!$response->successful()) {
                $errorBody = $response->json();
                $errorMessage = $errorBody['error']['message'] ?? $response->body();
                $statusCode = $response->status();

                Log::error('Gemini API request failed', [
                    'status' => $statusCode,
                    'error' => $errorMessage,
                    'model' => $chosenModel,
                ]);

                if ($statusCode === 400 && str_contains(strtolower($errorMessage), 'api key')) {
                    throw new Exception("Invalid Gemini API Key provided. Please verify GEMINI_API_KEY in .env.");
                }

                if ($statusCode === 429) {
                    throw new Exception("Gemini API rate limit or quota exceeded. Please wait a few moments or try another model.");
                }

                throw new Exception("Gemini API Error (HTTP {$statusCode}): {$errorMessage}");
            }

            $data = $response->json();
            $rawText = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

            if (empty($rawText)) {
                throw new Exception("Gemini returned an empty response. Please verify the resume content.");
            }

            return $this->parseJsonResponse($rawText);
        } catch (Exception $e) {
            Log::error('GeminiService exception: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Clean and parse JSON response.
     */
    protected function parseJsonResponse(string $raw): array
    {
        $cleaned = trim($raw);

        // Strip markdown code fences if present
        if (str_starts_with($cleaned, '```json')) {
            $cleaned = substr($cleaned, 7);
        } elseif (str_starts_with($cleaned, '```')) {
            $cleaned = substr($cleaned, 3);
        }

        if (str_ends_with($cleaned, '```')) {
            $cleaned = substr($cleaned, 0, -3);
        }

        $cleaned = trim($cleaned);

        $decoded = json_decode($cleaned, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            Log::warning('JSON decode failed for Gemini response', ['raw' => $raw, 'error' => json_last_error_msg()]);
            throw new Exception("Failed to parse Gemini response as valid JSON: " . json_last_error_msg());
        }

        return $decoded;
    }
}

<?php

namespace App\Services;

use App\Contracts\AiProviderInterface;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenRouterService implements AiProviderInterface
{
    protected ?string $apiKey;
    protected string $baseUrl;
    protected string $defaultModel;

    public function __construct()
    {
        $this->apiKey = config('services.openrouter.api_key');
        $this->baseUrl = rtrim((string) config('services.openrouter.base_url', 'https://openrouter.ai/api/v1'), '/');
        $this->defaultModel = config('services.openrouter.default_model', 'deepseek/deepseek-chat');
    }

    /**
     * Get display name.
     */
    public function getName(): string
    {
        return 'OpenRouter AI';
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
            'your_openrouter_api_key_here',
            'your_openrouter_api_key',
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
            'deepseek/deepseek-chat' => 'DeepSeek V3 (Fast & Highly Economical)',
            'meta-llama/llama-3.3-70b-instruct' => 'Meta Llama 3.3 70B (Recommended, High Quality)',
            'anthropic/claude-3.5-sonnet' => 'Claude 3.5 Sonnet (State of the Art)',
            'openai/gpt-4o-mini' => 'OpenAI GPT-4o Mini',
        ];
    }

    /**
     * Analyze resume using OpenRouter API.
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
            throw new Exception("OpenRouter API Key is missing or using placeholder in .env. Please set OPENROUTER_API_KEY from https://openrouter.ai/keys to analyze your resume.");
        }

        $chosenModel = $model ?: $this->defaultModel;
        $systemPrompt = AiPromptBuilder::buildSystemPrompt();
        $userPrompt = AiPromptBuilder::buildUserPrompt($resumeText, $jobDescription, $targetRole);

        $endpoint = "{$this->baseUrl}/chat/completions";

        $payload = [
            'model' => $chosenModel,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $systemPrompt,
                ],
                [
                    'role' => 'user',
                    'content' => $userPrompt,
                ],
            ],
            'response_format' => [
                'type' => 'json_object',
            ],
            'temperature' => 0.2,
        ];

        try {
            $response = Http::timeout(60)
                ->withHeaders([
                    'Authorization' => "Bearer {$activeKey}",
                    'HTTP-Referer' => config('app.url', 'http://localhost:8000'),
                    'X-Title' => config('app.name', 'AI-Powered Resume Analyzer'),
                    'Content-Type' => 'application/json',
                ])
                ->post($endpoint, $payload);

            if (!$response->successful()) {
                $errorBody = $response->json();
                $errorMessage = $errorBody['error']['message'] ?? $response->body();
                $statusCode = $response->status();

                Log::error('OpenRouter API request failed', [
                    'status' => $statusCode,
                    'error' => $errorMessage,
                    'model' => $chosenModel,
                ]);

                if ($statusCode === 401) {
                    throw new Exception("Invalid OpenRouter API Key. Please verify OPENROUTER_API_KEY in .env.");
                }

                if ($statusCode === 429) {
                    throw new Exception("OpenRouter credit limit reached or rate limited. Please check your OpenRouter account balance.");
                }

                throw new Exception("OpenRouter API Error (HTTP {$statusCode}): {$errorMessage}");
            }

            $data = $response->json();
            $rawContent = $data['choices'][0]['message']['content'] ?? null;

            if (empty($rawContent)) {
                throw new Exception("OpenRouter returned an empty response. Please verify your selected model.");
            }

            return $this->parseJsonResponse($rawContent);
        } catch (Exception $e) {
            Log::error('OpenRouterService exception: ' . $e->getMessage());
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
            Log::warning('JSON decode failed for OpenRouter response', ['raw' => $raw, 'error' => json_last_error_msg()]);
            throw new Exception("Failed to parse OpenRouter response as valid JSON: " . json_last_error_msg());
        }

        return $decoded;
    }
}

<?php

namespace App\Contracts;

interface AiProviderInterface
{
    /**
     * Analyze a resume against optional job description and target role.
     *
     * @param string $resumeText
     * @param string|null $jobDescription
     * @param string|null $targetRole
     * @param string|null $model
     * @param string|null $customApiKey
     * @return array<string, mixed>
     */
    public function analyzeResume(
        string $resumeText,
        ?string $jobDescription = null,
        ?string $targetRole = null,
        ?string $model = null,
        ?string $customApiKey = null
    ): array;

    /**
     * Get the display name of the provider.
     */
    public function getName(): string;

    /**
     * Check if the provider has a valid (non-placeholder) API key configured.
     */
    public function isConfigured(?string $overrideKey = null): bool;

    /**
     * Get the default model identifier.
     */
    public function getDefaultModel(): string;

    /**
     * Get the list of recommended models for this provider.
     *
     * @return array<string, string> Key: model id, Value: label
     */
    public function getAvailableModels(): array;
}

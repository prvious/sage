<?php

declare(strict_types=1);

namespace App\Actions\Brainstorm;

use InvalidArgumentException;

final readonly class ParseGeneratedIdeas
{
    /**
     * Parse AI response into structured ideas array.
     */
    public function handle(string $response): array
    {
        // Try to extract JSON from the response
        $json = $this->extractJson($response);

        if ($json === null) {
            throw new InvalidArgumentException('Could not extract valid JSON from AI response');
        }

        $ideas = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException('Invalid JSON in AI response: '.json_last_error_msg());
        }

        if (! is_array($ideas)) {
            throw new InvalidArgumentException('AI response must be an array of ideas');
        }

        // Validate and normalize ideas
        return array_map([$this, 'validateIdea'], $ideas);
    }

    /**
     * Extract JSON from response (handles markdown code blocks).
     */
    private function extractJson(string $response): ?string
    {
        // Try to extract JSON from markdown code block
        if (preg_match('/```(?:json)?\s*(\[.*?\])\s*```/s', $response, $matches)) {
            return $matches[1];
        }

        // Try to find raw JSON array
        if (preg_match('/(\[\s*\{.*?\}\s*\])/s', $response, $matches)) {
            return $matches[1];
        }

        // If the entire response looks like JSON, use it
        $trimmed = trim($response);
        if (str_starts_with($trimmed, '[') && str_ends_with($trimmed, ']')) {
            return $trimmed;
        }

        return null;
    }

    /**
     * Validate and normalize a single idea.
     */
    private function validateIdea(array $idea): array
    {
        if (! isset($idea['title']) || ! is_string($idea['title'])) {
            throw new InvalidArgumentException('Each idea must have a title');
        }

        if (! isset($idea['description']) || ! is_string($idea['description'])) {
            throw new InvalidArgumentException('Each idea must have a description');
        }

        return [
            'title' => trim($idea['title']),
            'description' => trim($idea['description']),
            'priority' => $this->normalizePriority($idea['priority'] ?? 'medium'),
            'category' => $this->normalizeCategory($idea['category'] ?? 'feature'),
        ];
    }

    /**
     * Normalize priority value.
     */
    private function normalizePriority(string $priority): string
    {
        $priority = strtolower(trim($priority));

        return match ($priority) {
            'high', 'critical', 'urgent' => 'high',
            'low', 'minor' => 'low',
            default => 'medium',
        };
    }

    /**
     * Normalize category value.
     */
    private function normalizeCategory(string $category): string
    {
        $category = strtolower(trim($category));

        return match ($category) {
            'feature', 'new feature' => 'feature',
            'enhancement', 'improvement' => 'enhancement',
            'infrastructure', 'infra' => 'infrastructure',
            'tooling', 'tool' => 'tooling',
            default => 'feature',
        };
    }
}

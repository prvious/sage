<?php

declare(strict_types=1);

namespace App\Actions\Feature;

use App\Models\Spec;
use App\Services\SpecGeneratorService;

final readonly class GenerateSpecFromDescription
{
    public function __construct(
        private SpecGeneratorService $specGenerator,
    ) {}

    /**
     * Generate a spec from a user description.
     */
    public function handle(int $projectId, string $description): Spec
    {
        // Generate spec content using AI
        $generatedContent = $this->specGenerator->generate($description, 'feature');

        // Extract title from first heading in the generated spec
        $title = $this->extractTitle($generatedContent) ?? 'Generated Feature Spec';

        // Create and return the spec
        return Spec::create([
            'project_id' => $projectId,
            'title' => $title,
            'content' => $generatedContent,
            'generated_from_idea' => $description,
        ]);
    }

    /**
     * Extract title from the first markdown heading.
     */
    private function extractTitle(string $content): ?string
    {
        // Match first markdown heading (# Title or ## Title)
        if (preg_match('/^#{1,6}\s+(.+)$/m', $content, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }
}

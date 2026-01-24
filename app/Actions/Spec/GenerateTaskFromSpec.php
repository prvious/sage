<?php

declare(strict_types=1);

namespace App\Actions\Spec;

use App\Enums\TaskStatus;
use App\Models\Spec;
use App\Models\Task;

final readonly class GenerateTaskFromSpec
{
    /**
     * Generate task title from spec title.
     */
    public function generateTitle(Spec $spec): string
    {
        return "Implement: {$spec->title}";
    }

    /**
     * Generate task description (agent prompt) from spec content.
     */
    public function generateDescription(Spec $spec): string
    {
        $prompt = <<<PROMPT
## Feature Implementation Task

**Feature ID:** {$spec->id}
**Title:** {$spec->title}

## Instructions

Implement this feature by:
1. First, explore the codebase to understand the existing structure
2. Plan your implementation approach
3. Write the necessary code changes
4. Ensure the code follows existing patterns and conventions
5. Write tests for the new functionality

## Feature Specification

{$spec->content}

## Guidelines

- Follow the existing code style and patterns in the codebase
- Write clean, maintainable code
- Add appropriate error handling
- Consider edge cases mentioned in the specification
- Write tests to verify the implementation
PROMPT;

        return $prompt;
    }

    /**
     * Create a task from a spec with the given parameters.
     *
     * @param  array{worktree_id?: int|null, title?: string|null, description?: string|null}  $overrides
     */
    public function handle(Spec $spec, array $overrides = []): Task
    {
        $title = ! empty($overrides['title']) ? $overrides['title'] : $this->generateTitle($spec);
        $description = $overrides['description'] ?? $this->generateDescription($spec);

        return Task::create([
            'project_id' => $spec->project_id,
            'spec_id' => $spec->id,
            'worktree_id' => $overrides['worktree_id'] ?? null,
            'title' => $title,
            'description' => $description,
            'status' => TaskStatus::Queued,
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Actions\Feature;

use App\Enums\TaskStatus;
use App\Models\Spec;
use App\Models\Task;

final readonly class CreateTasksFromSpec
{
    /**
     * Create tasks from a spec's content.
     *
     * @return array<Task>
     */
    public function handle(Spec $spec): array
    {
        $tasks = $this->parseTasksFromContent($spec->content);

        if (empty($tasks)) {
            return [];
        }

        $createdTasks = [];

        foreach ($tasks as $taskData) {
            $createdTasks[] = Task::create([
                'project_id' => $spec->project_id,
                'spec_id' => $spec->id,
                'title' => $taskData['title'],
                'description' => $taskData['description'] ?? '',
                'status' => TaskStatus::Queued,
            ]);
        }

        return $createdTasks;
    }

    /**
     * Parse tasks from spec markdown content.
     *
     * @return array<array{title: string, description?: string}>
     */
    private function parseTasksFromContent(string $content): array
    {
        $tasks = [];

        // Strategy 1: Look for markdown task lists: - [ ] Task name
        preg_match_all('/^-\s*\[\s*\]\s+(.+)$/m', $content, $checkboxMatches);

        foreach ($checkboxMatches[1] ?? [] as $taskTitle) {
            $tasks[] = ['title' => trim($taskTitle)];
        }

        // Strategy 2: Look for numbered lists if no checkboxes found
        if (empty($tasks)) {
            preg_match_all('/^\d+\.\s+(.+)$/m', $content, $numberedMatches);

            foreach ($numberedMatches[1] ?? [] as $taskTitle) {
                $tasks[] = ['title' => trim($taskTitle)];
            }
        }

        // Strategy 3: Look for bullet points if still nothing found
        if (empty($tasks)) {
            preg_match_all('/^[-*]\s+(.+)$/m', $content, $bulletMatches);

            foreach ($bulletMatches[1] ?? [] as $taskTitle) {
                // Skip already matched checkbox items
                if (! str_starts_with(trim($taskTitle), '[ ]')) {
                    $tasks[] = ['title' => trim($taskTitle)];
                }
            }
        }

        return $tasks;
    }
}

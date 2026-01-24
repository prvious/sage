<?php

declare(strict_types=1);

namespace App\Actions\Brainstorm;

use App\Models\Brainstorm;
use Carbon\Carbon;

final readonly class ExportIdeas
{
    public function handle(Brainstorm $brainstorm): string
    {
        $ideas = $brainstorm->ideas;
        $project = $brainstorm->project;

        // Calculate stats
        $totalIdeas = count($ideas);
        $highPriority = collect($ideas)->filter(fn ($idea) => $idea['priority'] === 'high')->count();
        $mediumPriority = collect($ideas)->filter(fn ($idea) => $idea['priority'] === 'medium')->count();
        $lowPriority = collect($ideas)->filter(fn ($idea) => $idea['priority'] === 'low')->count();

        // Group ideas by category
        $ideasByCategory = collect($ideas)->groupBy('category');

        // Build markdown content
        $markdown = "# Brainstorm Ideas - {$project->name}\n\n";
        $markdown .= '**Date**: '.Carbon::parse($brainstorm->created_at)->format('Y-m-d')."\n";
        $markdown .= "**Total Ideas**: {$totalIdeas}\n";
        $markdown .= "**High Priority**: {$highPriority}\n";
        $markdown .= "**Medium Priority**: {$mediumPriority}\n";
        $markdown .= "**Low Priority**: {$lowPriority}\n\n";
        $markdown .= "---\n\n";

        // Add ideas grouped by category
        foreach ($ideasByCategory as $category => $categoryIdeas) {
            $markdown .= "## {$category}\n\n";

            foreach ($categoryIdeas as $idea) {
                $priority = ucfirst($idea['priority']);
                $markdown .= "### {$idea['title']} ({$priority} Priority)\n\n";
                $markdown .= "{$idea['description']}\n\n";
            }

            $markdown .= "---\n\n";
        }

        return $markdown;
    }
}

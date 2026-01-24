<?php

declare(strict_types=1);

namespace App\Actions\Guideline;

use App\Models\Project;
use Illuminate\Support\Facades\Process;
use RuntimeException;

final readonly class AggregateGuidelines
{
    public function handle(Project $project): array
    {
        $result = Process::path($project->path)
            ->timeout(60)
            ->env(getenv())
            ->run('php artisan boost:update');

        if (! $result->successful()) {
            throw new RuntimeException(
                'Failed to aggregate guidelines: '.$result->errorOutput()
            );
        }

        return [
            'exit_code' => $result->exitCode(),
            'output' => $result->output(),
            'success' => $result->successful(),
        ];
    }
}

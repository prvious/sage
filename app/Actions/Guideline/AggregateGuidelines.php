<?php

declare(strict_types=1);

namespace App\Actions\Guideline;

use App\Models\Project;
use Illuminate\Support\Facades\Artisan;
use RuntimeException;

final readonly class AggregateGuidelines
{
    public function handle(Project $project): array
    {
        // Check if boost:install or boost:update commands exist
        $availableCommands = array_keys(Artisan::all());

        if (in_array('boost:install', $availableCommands)) {
            $exitCode = Artisan::call('boost:install', ['--project-path' => $project->path]);
        } elseif (in_array('boost:update', $availableCommands)) {
            $exitCode = Artisan::call('boost:update', ['--project-path' => $project->path]);
        } else {
            throw new RuntimeException('Laravel Boost commands not available. Please install Laravel Boost MCP server.');
        }

        $output = Artisan::output();

        return [
            'exit_code' => $exitCode,
            'output' => $output,
            'success' => $exitCode === 0,
        ];
    }
}

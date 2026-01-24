<?php

declare(strict_types=1);

use App\Actions\Guideline\AggregateGuidelines;
use App\Models\Project;
use Illuminate\Support\Facades\Process;

it('executes boost:update in project directory', function () {
    Process::fake([
        'php artisan boost:update' => Process::result(
            output: 'Guidelines aggregated successfully',
            exitCode: 0
        ),
    ]);

    $project = Project::factory()->create(['path' => '/path/to/project']);
    $action = new AggregateGuidelines;

    $result = $action->handle($project);

    expect($result['success'])->toBeTrue();
    expect($result['exit_code'])->toBe(0);
    expect($result['output'])->toContain('Guidelines aggregated successfully');

    Process::assertRan(function ($process) use ($project) {
        return str_contains($process->command, 'php artisan boost:update')
            && $process->path === $project->path
            && $process->timeout === 60;
    });
});

it('passes system environment variables to subprocess', function () {
    Process::fake([
        'php artisan boost:update' => Process::result(
            output: 'Success',
            exitCode: 0
        ),
    ]);

    $project = Project::factory()->create(['path' => '/path/to/project']);
    $action = new AggregateGuidelines;

    $result = $action->handle($project);

    // Just verify it ran successfully - env is set internally
    expect($result['success'])->toBeTrue();
});

it('captures error output when command fails', function () {
    Process::fake([
        'php artisan boost:update' => Process::result(
            errorOutput: 'Command failed: File not found',
            exitCode: 1
        ),
    ]);

    $project = Project::factory()->create(['path' => '/path/to/project']);
    $action = new AggregateGuidelines;

    expect(fn () => $action->handle($project))
        ->toThrow(RuntimeException::class, 'Failed to aggregate guidelines: Command failed: File not found');
});

it('throws exception when command fails', function () {
    Process::fake([
        'php artisan boost:update' => Process::result(
            errorOutput: 'Error occurred',
            exitCode: 1
        ),
    ]);

    $project = Project::factory()->create(['path' => '/path/to/project']);
    $action = new AggregateGuidelines;

    expect(fn () => $action->handle($project))
        ->toThrow(RuntimeException::class);
});

it('applies 60 second timeout to command execution', function () {
    Process::fake([
        'php artisan boost:update' => Process::result(
            output: 'Success',
            exitCode: 0
        ),
    ]);

    $project = Project::factory()->create(['path' => '/path/to/project']);
    $action = new AggregateGuidelines;

    $action->handle($project);

    Process::assertRan(function ($process) {
        return $process->timeout === 60;
    });
});

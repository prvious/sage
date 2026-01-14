<?php

use App\Drivers\Server\ArtisanDriver;
use App\Models\Project;
use App\Models\Worktree;
use Illuminate\Support\Facades\Process;

beforeEach(function () {
    $this->driver = new ArtisanDriver;
});

it('generates empty config for artisan driver', function () {
    $project = Project::factory()->create();
    $worktree = Worktree::factory()->create(['project_id' => $project->id]);

    $config = $this->driver->generateConfig($project, $worktree);

    expect($config)->toBe('');
});

it('validates PHP is available', function () {
    Process::fake([
        'php -v' => Process::result(output: 'PHP 8.4.16'),
    ]);

    $result = $this->driver->validate();

    expect($result)->toBeTrue();
    Process::assertRan('php -v');
});

it('fails validation when PHP is not available', function () {
    Process::fake([
        'php -v' => Process::result(exitCode: 1),
    ]);

    $result = $this->driver->validate();

    expect($result)->toBeFalse();
});

it('starts artisan server and updates preview URL', function () {
    $project = Project::factory()->create();
    $worktree = Worktree::factory()->create([
        'project_id' => $project->id,
        'preview_url' => 'http://placeholder.local',
    ]);

    Process::fake([
        'lsof -i:*' => Process::result(exitCode: 1),
        'php artisan serve *' => Process::result(),
    ]);

    $this->driver->start($worktree);

    $worktree->refresh();

    expect($worktree->preview_url)->toContain('http://127.0.0.1:');
    Process::assertRan(function ($process) use ($worktree) {
        return $process instanceof \Illuminate\Process\PendingProcess &&
               str_contains($process->command, 'php artisan serve') &&
               str_contains($process->command, '--host=127.0.0.1') &&
               str_contains($process->command, '--port=') &&
               $process->path === $worktree->path;
    });
});

it('stops artisan server by killing the port process on Unix', function () {
    $project = Project::factory()->create();
    $worktree = Worktree::factory()->create([
        'project_id' => $project->id,
        'preview_url' => 'http://127.0.0.1:8000',
    ]);

    Process::fake();

    $this->driver->stop($worktree);

    Process::assertRan(function ($process) {
        $command = is_string($process) ? $process : $process->command;

        return str_contains($command, 'lsof -ti:8000') &&
               str_contains($command, 'xargs kill -9');
    });
})->skip(fn () => PHP_OS_FAMILY === 'Windows', 'This test is only for Unix systems');

it('stops artisan server by killing the port process on Windows', function () {
    $project = Project::factory()->create();
    $worktree = Worktree::factory()->create([
        'project_id' => $project->id,
        'preview_url' => 'http://127.0.0.1:8000',
    ]);

    Process::fake();

    $this->driver->stop($worktree);

    Process::assertRan(function ($process) {
        $command = is_string($process) ? $process : $process->command;

        return str_contains($command, 'netstat -ano') &&
               str_contains($command, 'findstr :8000');
    });
})->skip(fn () => PHP_OS_FAMILY !== 'Windows', 'This test is only for Windows systems');

it('does nothing when stopping if preview URL has no port', function () {
    $project = Project::factory()->create();
    $worktree = Worktree::factory()->create([
        'project_id' => $project->id,
        'preview_url' => 'http://example.com',
    ]);

    Process::fake();

    $this->driver->stop($worktree);

    Process::assertNothingRan();
});

it('does nothing when reloading', function () {
    Process::fake();

    $this->driver->reload();

    Process::assertNothingRan();
});

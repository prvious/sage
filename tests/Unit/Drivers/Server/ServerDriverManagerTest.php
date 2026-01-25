<?php

declare(strict_types=1);

use App\Drivers\Server\FakeServerDriver;
use App\Drivers\Server\ServerManager;
use App\Models\Project;
use App\Models\Worktree;

it('fake method returns FakeServerDriver instance', function () {
    $fake = ServerManager::fake();

    expect($fake)->toBeInstanceOf(FakeServerDriver::class);
});

it('fake method accepts configuration options', function () {
    $fake = ServerManager::fake(['config' => 'custom config']);

    $project = Project::factory()->create();
    $worktree = Worktree::factory()->create(['project_id' => $project->id]);

    $config = $fake->generateConfig($project, $worktree);

    expect($config)->toBe('custom config');
});

it('driver uses fake when fake is set', function () {
    ServerManager::fake();

    $manager = app(ServerManager::class);
    $driver = $manager->driver('artisan');

    expect($driver)->toBeInstanceOf(FakeServerDriver::class);
});

it('fake driver can be configured for availability', function () {
    $fake = ServerManager::fake();
    $fake->available = false;

    expect($fake->validate())->toBeFalse();

    $fake->available = true;
    expect($fake->validate())->toBeTrue();
});

it('fake driver can throw exceptions', function () {
    $fake = ServerManager::fake();
    $fake->shouldThrow = new RuntimeException('Test exception');

    $project = Project::factory()->create();
    $worktree = Worktree::factory()->create(['project_id' => $project->id]);

    expect(fn () => $fake->generateConfig($project, $worktree))
        ->toThrow(RuntimeException::class, 'Test exception');
});

it('fake driver reload does not throw errors', function () {
    $fake = ServerManager::fake();

    $fake->reload();

    expect(true)->toBeTrue();
});

it('fake driver start and stop do not throw errors', function () {
    $fake = ServerManager::fake();
    $worktree = Worktree::factory()->create();

    $fake->start($worktree);
    $fake->stop($worktree);

    expect(true)->toBeTrue();
});

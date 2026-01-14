<?php

use App\Models\Project;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::flush();
});

it('redirects to projects list when no cached project exists', function () {
    $response = $this->get(route('home'));

    $response->assertRedirect(route('projects.index'));
});

it('updates cache when visiting project show page', function () {
    $project = Project::factory()->create();

    $this->get(route('projects.show', $project));

    $cacheKey = 'last_opened_project:'.session()->getId();
    expect(Cache::get($cacheKey))->toBe($project->id);
});

it('updates cache when visiting project edit page', function () {
    $project = Project::factory()->create();

    $this->get(route('projects.edit', $project));

    $cacheKey = 'last_opened_project:'.session()->getId();
    expect(Cache::get($cacheKey))->toBe($project->id);
});

it('tracks multiple projects and remembers last one', function () {
    $projectA = Project::factory()->create();
    $projectB = Project::factory()->create();

    // Visit project A
    $this->get(route('projects.show', $projectA));

    // Visit project B - this should update the cache
    $this->get(route('projects.show', $projectB));

    // Verify project B is now cached
    $cacheKey = 'last_opened_project:'.session()->getId();
    expect(Cache::get($cacheKey))->toBe($projectB->id);
});

it('uses correct cache key format with session ID', function () {
    $project = Project::factory()->create();

    $this->get(route('projects.show', $project));

    $sessionId = session()->getId();
    $cacheKey = "last_opened_project:{$sessionId}";

    expect(Cache::has($cacheKey))->toBeTrue();
    expect(Cache::get($cacheKey))->toBe($project->id);
});

it('handles cache failures gracefully', function () {
    // Simulate cache failure by using invalid cache key
    Cache::shouldReceive('get')
        ->once()
        ->andThrow(new \Exception('Cache failure'));

    $response = $this->get(route('home'));

    $response->assertRedirect(route('projects.index'));
});

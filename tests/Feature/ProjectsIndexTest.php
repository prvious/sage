<?php

use App\Models\Project;

it('displays projects index page with empty state when no projects exist', function () {
    $response = $this->get(route('projects.index'));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('projects/index')
        ->has('projects', 0)
    );
});

it('displays projects index page with projects list', function () {
    $projects = Project::factory()->count(3)->create();

    $response = $this->get(route('projects.index'));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('projects/index')
        ->has('projects', 3)
        ->has('projects.0', fn ($project) => $project
            ->has('id')
            ->has('name')
            ->has('path')
            ->has('server_driver')
            ->has('base_url')
            ->has('worktrees_count')
            ->has('tasks_count')
            ->has('created_at')
            ->has('updated_at')
        )
    );
});

it('includes all required project data for display', function () {
    $project = Project::factory()->create([
        'name' => 'Test Project',
        'path' => '/var/www/test',
        'server_driver' => 'caddy',
        'base_url' => 'https://test.local',
    ]);

    $response = $this->get(route('projects.index'));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('projects/index')
        ->where('projects.0.id', $project->id)
        ->where('projects.0.name', 'Test Project')
        ->where('projects.0.path', '/var/www/test')
        ->where('projects.0.server_driver', 'caddy')
        ->where('projects.0.base_url', 'https://test.local')
    );
});

it('shows projects ordered by most recent first', function () {
    $oldProject = Project::factory()->create(['created_at' => now()->subDays(2)]);
    $newProject = Project::factory()->create(['created_at' => now()]);
    $middleProject = Project::factory()->create(['created_at' => now()->subDay()]);

    $response = $this->get(route('projects.index'));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('projects/index')
        ->where('projects.0.id', $newProject->id)
        ->where('projects.1.id', $middleProject->id)
        ->where('projects.2.id', $oldProject->id)
    );
});

it('includes correct counts for worktrees and tasks', function () {
    $project = Project::factory()
        ->hasWorktrees(3)
        ->hasTasks(5)
        ->create();

    $response = $this->get(route('projects.index'));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('projects/index')
        ->where('projects.0.worktrees_count', 3)
        ->where('projects.0.tasks_count', 5)
    );
});

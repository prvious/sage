<?php

use App\Models\Project;
use Inertia\Testing\AssertableInertia as Assert;

it('shares projects in Inertia shared data', function () {
    Project::factory()->count(3)->create();

    $this->get('/projects')
        ->assertInertia(fn (Assert $page) => $page
            ->has('projects', 3)
        );
});

it('projects array contains expected project data', function () {
    $project = Project::factory()->create([
        'name' => 'Test Project',
        'path' => '/path/to/project',
    ]);

    $this->get('/projects')
        ->assertInertia(fn (Assert $page) => $page
            ->has('projects', 1)
            ->where('projects.0.id', $project->id)
            ->where('projects.0.name', 'Test Project')
            ->where('projects.0.path', '/path/to/project')
        );
});

it('projects are properly serialized as JSON', function () {
    Project::factory()->count(2)->create();

    $this->get('/projects')
        ->assertInertia(fn (Assert $page) => $page
            ->has('projects', 2)
            ->has('projects.0.id')
            ->has('projects.0.name')
            ->has('projects.0.path')
        );
});

it('shared data structure matches expected format', function () {
    Project::factory()->create();

    $this->get('/projects')
        ->assertInertia(fn (Assert $page) => $page
            ->has('name')
            ->has('auth')
            ->has('sidebarOpen')
            ->has('projects')
        );
});

it('returns empty array when no projects exist', function () {
    $this->get('/projects')
        ->assertInertia(fn (Assert $page) => $page
            ->has('projects', 0)
        );
});

it('shares selectedProject when on project route', function () {
    $project = Project::factory()->create([
        'name' => 'Selected Project',
        'path' => '/path/to/selected',
    ]);

    $this->get("/projects/{$project->id}/dashboard")
        ->assertInertia(fn (Assert $page) => $page
            ->has('selectedProject')
            ->where('selectedProject.id', $project->id)
            ->where('selectedProject.name', 'Selected Project')
            ->where('selectedProject.path', '/path/to/selected')
        );
});

it('selectedProject is null when not on project route and no project visited yet', function () {
    $this->get('/projects')
        ->assertInertia(fn (Assert $page) => $page
            ->where('selectedProject', null)
        );
});

it('selectedProject is correctly identified from route parameter', function () {
    $project1 = Project::factory()->create(['name' => 'Project 1']);
    $project2 = Project::factory()->create(['name' => 'Project 2']);

    $this->get("/projects/{$project2->id}/dashboard")
        ->assertInertia(fn (Assert $page) => $page
            ->where('selectedProject.id', $project2->id)
            ->where('selectedProject.name', 'Project 2')
        );
});

it('persists selected project in session when visiting project route', function () {
    $project = Project::factory()->create([
        'name' => 'Persisted Project',
        'path' => '/path/to/persisted',
    ]);

    $this->get("/projects/{$project->id}/dashboard");

    expect(session('last_selected_project_id'))->toBe($project->id);
});

it('maintains selected project from session when visiting non-project route', function () {
    $project = Project::factory()->create([
        'name' => 'Session Project',
        'path' => '/path/to/session',
    ]);

    // First visit a project route to store it in session
    $this->get("/projects/{$project->id}/dashboard");

    // Then visit a non-project route (like /agents)
    $this->get('/agents')
        ->assertInertia(fn (Assert $page) => $page
            ->has('selectedProject')
            ->where('selectedProject.id', $project->id)
            ->where('selectedProject.name', 'Session Project')
            ->where('selectedProject.path', '/path/to/session')
        );
});

it('updates session when switching between projects', function () {
    $project1 = Project::factory()->create(['name' => 'First Project']);
    $project2 = Project::factory()->create(['name' => 'Second Project']);

    // Visit first project
    $this->get("/projects/{$project1->id}/dashboard");
    expect(session('last_selected_project_id'))->toBe($project1->id);

    // Visit second project
    $this->get("/projects/{$project2->id}/dashboard");
    expect(session('last_selected_project_id'))->toBe($project2->id);

    // Visit non-project route should show second project
    $this->get('/agents')
        ->assertInertia(fn (Assert $page) => $page
            ->where('selectedProject.id', $project2->id)
            ->where('selectedProject.name', 'Second Project')
        );
});

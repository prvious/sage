<?php

use App\Models\Project;
use Illuminate\Support\Facades\Route;
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

// Quick Task Feature - Worktree Sharing Tests
it('shares empty selectedProjectWorktrees array when no worktrees exist', function () {
    $project = Project::factory()->create();

    $this->get("/projects/{$project->id}/dashboard")
        ->assertInertia(fn (Assert $page) => $page
            ->has('selectedProjectWorktrees', 0)
        );
});

it('shares active worktrees for selected project', function () {
    $project = Project::factory()->create();
    $worktree = \App\Models\Worktree::factory()->create([
        'project_id' => $project->id,
        'branch_name' => 'feature/test-branch',
        'status' => 'active',
    ]);

    $this->get("/projects/{$project->id}/dashboard")
        ->assertInertia(fn (Assert $page) => $page
            ->has('selectedProjectWorktrees', 1)
            ->where('selectedProjectWorktrees.0.id', $worktree->id)
            ->where('selectedProjectWorktrees.0.branch_name', 'feature/test-branch')
        );
});

it('only shares active worktrees not inactive ones', function () {
    $project = Project::factory()->create();

    // Create active worktree
    $activeWorktree = \App\Models\Worktree::factory()->create([
        'project_id' => $project->id,
        'branch_name' => 'feature/active',
        'status' => 'active',
    ]);

    // Create non-active worktrees
    \App\Models\Worktree::factory()->create([
        'project_id' => $project->id,
        'branch_name' => 'feature/creating',
        'status' => 'creating',
    ]);

    \App\Models\Worktree::factory()->create([
        'project_id' => $project->id,
        'branch_name' => 'feature/error',
        'status' => 'error',
    ]);

    \App\Models\Worktree::factory()->create([
        'project_id' => $project->id,
        'branch_name' => 'feature/cleaning-up',
        'status' => 'cleaning_up',
    ]);

    $this->get("/projects/{$project->id}/dashboard")
        ->assertInertia(fn (Assert $page) => $page
            ->has('selectedProjectWorktrees', 1)
            ->where('selectedProjectWorktrees.0.id', $activeWorktree->id)
            ->where('selectedProjectWorktrees.0.branch_name', 'feature/active')
        );
});

it('shares multiple active worktrees for selected project', function () {
    $project = Project::factory()->create();

    \App\Models\Worktree::factory()->create([
        'project_id' => $project->id,
        'branch_name' => 'feature/branch-1',
        'status' => 'active',
    ]);

    \App\Models\Worktree::factory()->create([
        'project_id' => $project->id,
        'branch_name' => 'feature/branch-2',
        'status' => 'active',
    ]);

    \App\Models\Worktree::factory()->create([
        'project_id' => $project->id,
        'branch_name' => 'bugfix/branch-3',
        'status' => 'active',
    ]);

    $this->get("/projects/{$project->id}/dashboard")
        ->assertInertia(fn (Assert $page) => $page
            ->has('selectedProjectWorktrees', 3)
        );
});

it('does not share worktrees from other projects', function () {
    $project1 = Project::factory()->create();
    $project2 = Project::factory()->create();

    // Create worktree for project1
    $worktree1 = \App\Models\Worktree::factory()->create([
        'project_id' => $project1->id,
        'branch_name' => 'feature/project1-branch',
        'status' => 'active',
    ]);

    // Create worktree for project2
    \App\Models\Worktree::factory()->create([
        'project_id' => $project2->id,
        'branch_name' => 'feature/project2-branch',
        'status' => 'active',
    ]);

    // Visit project1 - should only see project1's worktrees
    $this->get("/projects/{$project1->id}/dashboard")
        ->assertInertia(fn (Assert $page) => $page
            ->has('selectedProjectWorktrees', 1)
            ->where('selectedProjectWorktrees.0.id', $worktree1->id)
            ->where('selectedProjectWorktrees.0.branch_name', 'feature/project1-branch')
        );
});

it('shares empty worktrees array when no project is selected', function () {
    $this->get('/projects')
        ->assertInertia(fn (Assert $page) => $page
            ->where('selectedProjectWorktrees', [])
        );
});

it('shares worktrees for session-stored project on non-project route', function () {
    $project = Project::factory()->create();
    $worktree = \App\Models\Worktree::factory()->create([
        'project_id' => $project->id,
        'branch_name' => 'feature/session-test',
        'status' => 'active',
    ]);

    // Visit project route to store in session
    $this->get("/projects/{$project->id}/dashboard");

    // Visit non-project route - should still have worktrees from session
    $this->get('/agents')
        ->assertInertia(fn (Assert $page) => $page
            ->has('selectedProjectWorktrees', 1)
            ->where('selectedProjectWorktrees.0.id', $worktree->id)
            ->where('selectedProjectWorktrees.0.branch_name', 'feature/session-test')
        );
});

it('selectedProjectWorktrees only contains id and branch_name', function () {
    $project = Project::factory()->create();
    \App\Models\Worktree::factory()->create([
        'project_id' => $project->id,
        'branch_name' => 'feature/minimal-fields',
        'status' => 'active',
        'path' => '/some/path',
        'preview_url' => 'http://preview.url',
    ]);

    $this->get("/projects/{$project->id}/dashboard")
        ->assertInertia(fn (Assert $page) => $page
            ->has('selectedProjectWorktrees', 1)
            ->has('selectedProjectWorktrees.0.id')
            ->has('selectedProjectWorktrees.0.branch_name')
            ->missing('selectedProjectWorktrees.0.path')
            ->missing('selectedProjectWorktrees.0.preview_url')
            ->missing('selectedProjectWorktrees.0.status')
        );
});

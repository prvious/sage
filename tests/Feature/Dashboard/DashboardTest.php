<?php

use App\Models\Project;
use App\Models\Task;
use App\Models\Worktree;

it('displays dashboard page successfully', function () {
    $response = $this->get(route('dashboard'));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page->component('dashboard/index'));
});

it('displays dashboard with empty projects list', function () {
    $response = $this->get(route('dashboard'));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('dashboard/index')
        ->has('projects.data', 0)
    );
});

it('displays dashboard with all projects from database', function () {
    Project::factory()->count(3)->create();

    $response = $this->get(route('dashboard'));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('dashboard/index')
        ->has('projects.data', 3)
    );
});

it('orders projects by name', function () {
    $projectC = Project::factory()->create(['name' => 'Project C']);
    $projectA = Project::factory()->create(['name' => 'Project A']);
    $projectB = Project::factory()->create(['name' => 'Project B']);

    $response = $this->get(route('dashboard'));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('dashboard/index')
        ->where('projects.data.0.name', 'Project A')
        ->where('projects.data.1.name', 'Project B')
        ->where('projects.data.2.name', 'Project C')
    );
});

it('includes only necessary project fields', function () {
    Project::factory()->create();

    $response = $this->get(route('dashboard'));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('dashboard/index')
        ->has('projects.data.0', fn ($project) => $project
            ->has('id')
            ->has('name')
            ->has('path')
            ->has('base_url')
        )
    );
});

it('displays dashboard with tasks data structure', function () {
    $response = $this->get(route('dashboard'));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('dashboard/index')
        ->has('tasks')
        ->has('tasks.idea')
        ->has('tasks.in_progress')
        ->has('tasks.review')
        ->has('tasks.done')
    );
});

it('displays dashboard with empty tasks when no tasks exist', function () {
    $response = $this->get(route('dashboard'));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('dashboard/index')
        ->has('tasks.idea.data', 0)
        ->has('tasks.in_progress.data', 0)
        ->has('tasks.review.data', 0)
        ->has('tasks.done.data', 0)
    );
});

it('groups tasks by status correctly', function () {
    $project = Project::factory()->create();
    $worktree = Worktree::factory()->create(['project_id' => $project->id]);

    Task::factory()->create(['worktree_id' => $worktree->id, 'status' => 'idea']);
    Task::factory()->create(['worktree_id' => $worktree->id, 'status' => 'idea']);
    Task::factory()->create(['worktree_id' => $worktree->id, 'status' => 'in_progress']);
    Task::factory()->create(['worktree_id' => $worktree->id, 'status' => 'review']);
    Task::factory()->create(['worktree_id' => $worktree->id, 'status' => 'done']);

    $response = $this->get(route('dashboard'));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('dashboard/index')
        ->has('tasks.idea.data', 2)
        ->has('tasks.in_progress.data', 1)
        ->has('tasks.review.data', 1)
        ->has('tasks.done.data', 1)
    );
});

it('includes task relationships in dashboard data', function () {
    $project = Project::factory()->create();
    $worktree = Worktree::factory()->create(['project_id' => $project->id]);
    Task::factory()->create(['worktree_id' => $worktree->id, 'status' => 'idea']);

    $response = $this->get(route('dashboard'));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('dashboard/index')
        ->has('tasks.idea.data.0', fn ($task) => $task
            ->has('id')
            ->has('worktree')
            ->etc()
        )
    );
});

it('orders tasks by created_at descending', function () {
    $project = Project::factory()->create();
    $worktree = Worktree::factory()->create(['project_id' => $project->id]);

    $oldTask = Task::factory()->create([
        'worktree_id' => $worktree->id,
        'status' => 'idea',
        'created_at' => now()->subDays(2),
    ]);
    $newTask = Task::factory()->create([
        'worktree_id' => $worktree->id,
        'status' => 'idea',
        'created_at' => now(),
    ]);

    $response = $this->get(route('dashboard'));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('dashboard/index')
        ->where('tasks.idea.data.0.id', $newTask->id)
        ->where('tasks.idea.data.1.id', $oldTask->id)
    );
});

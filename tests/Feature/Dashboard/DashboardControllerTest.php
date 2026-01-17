<?php

declare(strict_types=1);

use App\Models\Project;
use App\Models\Task;
use App\Models\Worktree;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\get;

uses(RefreshDatabase::class);

it('displays project-specific dashboard', function () {
    $project = Project::factory()->create();

    $response = get(route('projects.dashboard', $project));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('projects/dashboard')
        ->has('project.data')
        ->where('project.data.id', $project->id));
});

it('displays only tasks from the specified project', function () {
    $project1 = Project::factory()->create();
    $project2 = Project::factory()->create();

    $worktree1 = Worktree::factory()->create(['project_id' => $project1->id]);
    $worktree2 = Worktree::factory()->create(['project_id' => $project2->id]);

    // Create tasks for project 1
    Task::factory()->count(3)->create(['worktree_id' => $worktree1->id, 'status' => 'idea']);

    // Create tasks for project 2
    Task::factory()->count(2)->create(['worktree_id' => $worktree2->id, 'status' => 'idea']);

    $response = get(route('projects.dashboard', $project1));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->has('tasks.idea.data', 3)
        ->where('project.data.id', $project1->id));
});

it('includes tasks grouped by status for the project', function () {
    $project = Project::factory()->create();
    $worktree = Worktree::factory()->create(['project_id' => $project->id]);

    // Create tasks with different statuses
    Task::factory()->create(['worktree_id' => $worktree->id, 'status' => 'idea']);
    Task::factory()->create(['worktree_id' => $worktree->id, 'status' => 'in_progress']);
    Task::factory()->create(['worktree_id' => $worktree->id, 'status' => 'review']);
    Task::factory()->create(['worktree_id' => $worktree->id, 'status' => 'done']);

    $response = get(route('projects.dashboard', $project));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('projects/dashboard')
        ->has('tasks.idea.data', 1)
        ->has('tasks.in_progress.data', 1)
        ->has('tasks.review.data', 1)
        ->has('tasks.done.data', 1));
});

it('handles project with no tasks gracefully', function () {
    $project = Project::factory()->create();

    $response = get(route('projects.dashboard', $project));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->has('tasks.idea.data', 0)
        ->has('tasks.in_progress.data', 0)
        ->has('tasks.review.data', 0)
        ->has('tasks.done.data', 0));
});

it('multiple projects have isolated task lists', function () {
    $project1 = Project::factory()->create(['name' => 'Project Alpha']);
    $project2 = Project::factory()->create(['name' => 'Project Beta']);

    $worktree1 = Worktree::factory()->create(['project_id' => $project1->id]);
    $worktree2 = Worktree::factory()->create(['project_id' => $project2->id]);

    Task::factory()->count(5)->create(['worktree_id' => $worktree1->id, 'status' => 'idea']);
    Task::factory()->count(3)->create(['worktree_id' => $worktree2->id, 'status' => 'idea']);

    $response1 = get(route('projects.dashboard', $project1));
    $response2 = get(route('projects.dashboard', $project2));

    $response1->assertInertia(fn ($page) => $page->has('tasks.idea.data', 5));
    $response2->assertInertia(fn ($page) => $page->has('tasks.idea.data', 3));
});

it('eager loads task relationships to prevent N+1 queries', function () {
    $project = Project::factory()->create();
    $worktree = Worktree::factory()->create(['project_id' => $project->id]);

    // Create tasks
    Task::factory()->count(5)->create(['worktree_id' => $worktree->id]);

    // Enable query logging
    DB::enableQueryLog();

    get(route('projects.dashboard', $project));

    $queries = DB::getQueryLog();

    // Should have minimal queries with eager loading
    expect(count($queries))->toBeLessThan(10);
});

it('old dashboard route returns 404', function () {
    $response = get('/dashboard');

    $response->assertNotFound();
});

it('includes all projects for navigation', function () {
    $project = Project::factory()->create();
    Project::factory()->count(2)->create(); // Create additional projects

    $response = get(route('projects.dashboard', $project));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->has('projects.data', 3));
});

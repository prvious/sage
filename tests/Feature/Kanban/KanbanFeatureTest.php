<?php

use App\Models\Project;
use App\Models\Task;
use App\Models\Worktree;

it('displays tasks grouped by status', function () {
    $project = Project::factory()->create();

    $ideaTask = Task::factory()->idea()->create(['project_id' => $project->id]);
    $inProgressTask = Task::factory()->inProgress()->create(['project_id' => $project->id]);
    $reviewTask = Task::factory()->review()->create(['project_id' => $project->id]);
    $doneTask = Task::factory()->done()->create(['project_id' => $project->id]);

    $response = $this->get(route('tasks.index'));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('Kanban/Index')
        ->has('tasks.idea', 1)
        ->has('tasks.in_progress', 1)
        ->has('tasks.review', 1)
        ->has('tasks.done', 1)
    );
});

it('can create new task from kanban', function () {
    $project = Project::factory()->create();

    $response = $this->postJson(route('tasks.store'), [
        'project_id' => $project->id,
        'title' => 'New Feature',
        'description' => 'Build a new feature',
        'status' => 'idea',
    ]);

    $response->assertRedirect(route('tasks.index'));

    $this->assertDatabaseHas('tasks', [
        'project_id' => $project->id,
        'title' => 'New Feature',
        'status' => 'idea',
    ]);
});

it('can update task status via API', function () {
    $task = Task::factory()->idea()->create();

    $response = $this->putJson(route('tasks.update', $task), [
        'status' => 'in_progress',
    ]);

    $response->assertRedirect(route('tasks.index'));

    $task->refresh();

    expect($task->status)->toBe('in_progress');
});

it('can delete task from kanban', function () {
    $task = Task::factory()->create();

    $response = $this->deleteJson(route('tasks.destroy', $task));

    $response->assertRedirect(route('tasks.index'));

    $this->assertDatabaseMissing('tasks', [
        'id' => $task->id,
    ]);
});

it('task isRunning method works correctly', function () {
    $notRunningTask = Task::factory()->idea()->create();
    $runningTask = Task::factory()->inProgress()->create([
        'started_at' => now(),
        'completed_at' => null,
    ]);
    $completedTask = Task::factory()->done()->create([
        'started_at' => now()->subHour(),
        'completed_at' => now(),
    ]);

    expect($notRunningTask->isRunning())->toBeFalse();
    expect($runningTask->isRunning())->toBeTrue();
    expect($completedTask->isRunning())->toBeFalse();
});

it('can scope tasks by status', function () {
    Task::factory()->idea()->count(3)->create();
    Task::factory()->inProgress()->count(2)->create();
    Task::factory()->done()->count(1)->create();

    $ideaTasks = Task::byStatus('idea')->get();
    $inProgressTasks = Task::byStatus('in_progress')->get();
    $doneTasks = Task::byStatus('done')->get();

    expect($ideaTasks)->toHaveCount(3);
    expect($inProgressTasks)->toHaveCount(2);
    expect($doneTasks)->toHaveCount(1);
});

it('can assign task to worktree', function () {
    $worktree = Worktree::factory()->create();
    $project = $worktree->project;

    $response = $this->postJson(route('tasks.store'), [
        'project_id' => $project->id,
        'title' => 'Task with worktree',
        'description' => 'Description',
        'status' => 'idea',
        'worktree_id' => $worktree->id,
    ]);

    $response->assertRedirect(route('tasks.index'));

    $this->assertDatabaseHas('tasks', [
        'title' => 'Task with worktree',
        'worktree_id' => $worktree->id,
    ]);
});

it('validates required fields when creating task', function () {
    $response = $this->postJson(route('tasks.store'), [
        'description' => 'Missing required fields',
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['project_id', 'title', 'status']);
});

it('validates status enum values', function () {
    $project = Project::factory()->create();

    $response = $this->postJson(route('tasks.store'), [
        'project_id' => $project->id,
        'title' => 'Test',
        'status' => 'invalid_status',
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['status']);
});

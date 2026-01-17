<?php

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\Worktree;

it('displays tasks grouped by status', function () {
    $project = Project::factory()->create();

    $queuedTask = Task::factory()->queued()->create(['project_id' => $project->id]);
    $inProgressTask = Task::factory()->inProgress()->create(['project_id' => $project->id]);
    $waitingReviewTask = Task::factory()->waitingReview()->create(['project_id' => $project->id]);
    $doneTask = Task::factory()->done()->create(['project_id' => $project->id]);

    $response = $this->get(route('projects.dashboard', $project));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('projects/dashboard')
        ->has('tasks.queued.data', 1)
        ->has('tasks.in_progress.data', 1)
        ->has('tasks.waiting_review.data', 1)
        ->has('tasks.done.data', 1)
    );
});

it('can create new task from kanban', function () {
    $project = Project::factory()->create();

    $response = $this->post(route('tasks.store'), [
        'project_id' => $project->id,
        'description' => 'Build a new feature',
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('tasks', [
        'project_id' => $project->id,
        'description' => 'Build a new feature',
        'status' => TaskStatus::Queued->value,
    ]);
});

it('can update task status via API', function () {
    $task = Task::factory()->queued()->create();

    $response = $this->patch(route('tasks.update', $task), [
        'status' => TaskStatus::InProgress->value,
    ]);

    $response->assertRedirect();

    $task->refresh();

    expect($task->status)->toBe(TaskStatus::InProgress);
});

it('can delete task from kanban', function () {
    $task = Task::factory()->create();

    $response = $this->delete(route('tasks.destroy', $task));

    $response->assertRedirect();

    $this->assertDatabaseMissing('tasks', [
        'id' => $task->id,
    ]);
});

it('task isRunning method works correctly', function () {
    $notRunningTask = Task::factory()->queued()->create();
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
    Task::factory()->queued()->count(3)->create();
    Task::factory()->inProgress()->count(2)->create();
    Task::factory()->done()->count(1)->create();

    $queuedTasks = Task::byStatus(TaskStatus::Queued)->get();
    $inProgressTasks = Task::byStatus(TaskStatus::InProgress)->get();
    $doneTasks = Task::byStatus(TaskStatus::Done)->get();

    expect($queuedTasks)->toHaveCount(3);
    expect($inProgressTasks)->toHaveCount(2);
    expect($doneTasks)->toHaveCount(1);
});

it('can assign task to worktree', function () {
    $worktree = Worktree::factory()->create();
    $project = $worktree->project;

    $response = $this->post(route('tasks.store'), [
        'project_id' => $project->id,
        'description' => 'Task with worktree',
        'worktree_id' => $worktree->id,
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('tasks', [
        'description' => 'Task with worktree',
        'worktree_id' => $worktree->id,
    ]);
});

it('validates required fields when creating task', function () {
    $response = $this->post(route('tasks.store'), [
        'title' => 'Missing description',
    ]);

    $response->assertSessionHasErrors(['project_id', 'description']);
});

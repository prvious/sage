<?php

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;

test('dashboard loads tasks for specific project only', function () {
    $project1 = Project::factory()->create();
    $project2 = Project::factory()->create();

    $task1 = Task::factory()->create([
        'project_id' => $project1->id,
        'status' => TaskStatus::Queued,
    ]);
    $task2 = Task::factory()->create([
        'project_id' => $project2->id,
        'status' => TaskStatus::Queued,
    ]);

    $response = $this->get(route('projects.dashboard', $project1));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('projects/dashboard')
        ->has('tasks.queued.data', 1)
        ->where('tasks.queued.data.0.id', $task1->id)
    );
});

test('tasks are grouped by status correctly', function () {
    $project = Project::factory()->create();

    $queuedTask = Task::factory()->create([
        'project_id' => $project->id,
        'status' => TaskStatus::Queued,
    ]);
    $inProgressTask = Task::factory()->create([
        'project_id' => $project->id,
        'status' => TaskStatus::InProgress,
    ]);
    $waitingReviewTask = Task::factory()->create([
        'project_id' => $project->id,
        'status' => TaskStatus::WaitingReview,
    ]);
    $doneTask = Task::factory()->create([
        'project_id' => $project->id,
        'status' => TaskStatus::Done,
    ]);

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

test('creating task sets default status to queued', function () {
    $project = Project::factory()->create();

    $response = $this->post(route('tasks.store'), [
        'project_id' => $project->id,
        'description' => 'Test task description',
    ]);

    $response->assertRedirect();

    $task = Task::latest()->first();
    expect($task->status)->toBe(TaskStatus::Queued);
});

test('creating task requires description', function () {
    $project = Project::factory()->create();

    $response = $this->post(route('tasks.store'), [
        'project_id' => $project->id,
        'description' => '',
    ]);

    $response->assertSessionHasErrors('description');
});

test('title is generated from description', function () {
    $project = Project::factory()->create();

    $response = $this->post(route('tasks.store'), [
        'project_id' => $project->id,
        'description' => 'This is the first line of the description
And this is the second line',
    ]);

    $response->assertRedirect();

    $task = Task::latest()->first();
    expect($task->title)->toBe('This is the first line of the description');
});

test('task belongs to correct project', function () {
    $project = Project::factory()->create();

    $response = $this->post(route('tasks.store'), [
        'project_id' => $project->id,
        'description' => 'Test task',
    ]);

    $response->assertRedirect();

    $task = Task::latest()->first();
    expect($task->project_id)->toBe($project->id);
});

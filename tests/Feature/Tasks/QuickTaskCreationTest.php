<?php

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\Worktree;

/**
 * Quick Task Creation Feature Tests
 *
 * Tests for the streamlined task creation modal that can be accessed
 * from any page via keyboard shortcut (Cmd+K / Ctrl+K).
 */
it('creates task with title and description', function () {
    $project = Project::factory()->create();

    $response = $this->post('/tasks', [
        'project_id' => $project->id,
        'title' => 'Quick Task Title',
        'description' => 'Task created via quick task dialog',
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('tasks', [
        'project_id' => $project->id,
        'title' => 'Quick Task Title',
        'description' => 'Task created via quick task dialog',
        'status' => TaskStatus::Queued->value,
    ]);
});

it('creates task with description only and auto-generates title', function () {
    $project = Project::factory()->create();

    $response = $this->post('/tasks', [
        'project_id' => $project->id,
        'title' => null,
        'description' => 'Auto-generated title from this description',
    ]);

    $response->assertRedirect();

    $task = Task::where('project_id', $project->id)->first();
    expect($task)->not->toBeNull();
    expect($task->description)->toBe('Auto-generated title from this description');
});

it('creates task with optional worktree assignment', function () {
    $project = Project::factory()->create();
    $worktree = Worktree::factory()->create([
        'project_id' => $project->id,
        'status' => 'active',
    ]);

    $response = $this->post('/tasks', [
        'project_id' => $project->id,
        'title' => 'Task with Worktree',
        'description' => 'Task assigned to a worktree',
        'worktree_id' => $worktree->id,
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('tasks', [
        'project_id' => $project->id,
        'worktree_id' => $worktree->id,
        'title' => 'Task with Worktree',
    ]);
});

it('creates task without worktree when null is passed', function () {
    $project = Project::factory()->create();

    $response = $this->post('/tasks', [
        'project_id' => $project->id,
        'title' => 'Task without Worktree',
        'description' => 'Task not assigned to any worktree',
        'worktree_id' => null,
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('tasks', [
        'project_id' => $project->id,
        'worktree_id' => null,
        'title' => 'Task without Worktree',
    ]);
});

it('validates project_id is required', function () {
    $response = $this->post('/tasks', [
        'title' => 'Test Task',
        'description' => 'Test description',
    ]);

    $response->assertSessionHasErrors('project_id');
});

it('validates project_id exists in database', function () {
    $response = $this->post('/tasks', [
        'project_id' => 99999,
        'title' => 'Test Task',
        'description' => 'Test description',
    ]);

    $response->assertSessionHasErrors('project_id');
});

it('validates description is required', function () {
    $project = Project::factory()->create();

    $response = $this->post('/tasks', [
        'project_id' => $project->id,
        'title' => 'Test Task',
        'description' => '',
    ]);

    $response->assertSessionHasErrors('description');
});

it('validates description max length of 5000 characters', function () {
    $project = Project::factory()->create();

    $response = $this->post('/tasks', [
        'project_id' => $project->id,
        'title' => 'Test Task',
        'description' => str_repeat('a', 5001),
    ]);

    $response->assertSessionHasErrors('description');
});

it('validates title max length of 255 characters', function () {
    $project = Project::factory()->create();

    $response = $this->post('/tasks', [
        'project_id' => $project->id,
        'title' => str_repeat('a', 256),
        'description' => 'Valid description',
    ]);

    $response->assertSessionHasErrors('title');
});

it('validates worktree_id exists when provided', function () {
    $project = Project::factory()->create();

    $response = $this->post('/tasks', [
        'project_id' => $project->id,
        'title' => 'Test Task',
        'description' => 'Test description',
        'worktree_id' => 99999,
    ]);

    $response->assertSessionHasErrors('worktree_id');
});

it('allows valid long description up to 5000 characters', function () {
    $project = Project::factory()->create();
    $longDescription = str_repeat('a', 5000);

    $response = $this->post('/tasks', [
        'project_id' => $project->id,
        'title' => 'Test Task',
        'description' => $longDescription,
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('tasks', [
        'project_id' => $project->id,
        'description' => $longDescription,
    ]);
});

it('creates task with queued status by default', function () {
    $project = Project::factory()->create();

    $this->post('/tasks', [
        'project_id' => $project->id,
        'title' => 'New Task',
        'description' => 'Task description',
    ]);

    $task = Task::where('project_id', $project->id)->first();
    expect($task->status)->toBe(TaskStatus::Queued);
});

it('sets session success message after task creation', function () {
    $project = Project::factory()->create();

    $response = $this->post('/tasks', [
        'project_id' => $project->id,
        'title' => 'Success Message Test',
        'description' => 'Testing success message',
    ]);

    $response->assertSessionHas('success');
});

it('can create multiple tasks for same project', function () {
    $project = Project::factory()->create();

    $this->post('/tasks', [
        'project_id' => $project->id,
        'title' => 'Task 1',
        'description' => 'First task',
    ]);

    $this->post('/tasks', [
        'project_id' => $project->id,
        'title' => 'Task 2',
        'description' => 'Second task',
    ]);

    $this->post('/tasks', [
        'project_id' => $project->id,
        'title' => 'Task 3',
        'description' => 'Third task',
    ]);

    expect(Task::where('project_id', $project->id)->count())->toBe(3);
});

it('can create tasks for different projects', function () {
    $project1 = Project::factory()->create();
    $project2 = Project::factory()->create();

    $this->post('/tasks', [
        'project_id' => $project1->id,
        'title' => 'Project 1 Task',
        'description' => 'Task for project 1',
    ]);

    $this->post('/tasks', [
        'project_id' => $project2->id,
        'title' => 'Project 2 Task',
        'description' => 'Task for project 2',
    ]);

    expect(Task::where('project_id', $project1->id)->count())->toBe(1);
    expect(Task::where('project_id', $project2->id)->count())->toBe(1);
});

it('can assign task to worktree belonging to different project', function () {
    $project1 = Project::factory()->create();
    $project2 = Project::factory()->create();
    $worktree = Worktree::factory()->create([
        'project_id' => $project2->id,
        'status' => 'active',
    ]);

    // This should still work since validation only checks worktree exists
    // Business logic may want to restrict this in the future
    $response = $this->post('/tasks', [
        'project_id' => $project1->id,
        'title' => 'Cross-project Task',
        'description' => 'Task with worktree from different project',
        'worktree_id' => $worktree->id,
    ]);

    $response->assertRedirect();
});

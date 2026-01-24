<?php

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;

it('displays task show page with output viewer', function () {
    $project = Project::factory()->create(['name' => 'Test Project']);
    $task = Task::factory()->create([
        'project_id' => $project->id,
        'title' => 'Test Task Title',
        'description' => 'Test task description',
        'status' => TaskStatus::Done,
        'agent_output' => "Line 1 of output\nLine 2 of output\nLine 3 with Success: message\nError: something failed",
        'started_at' => now()->subMinutes(10),
        'completed_at' => now(),
    ]);

    $page = visit("/tasks/{$task->id}");

    $page->assertSee('Test Task Title')
        ->assertSee('Test task description')
        ->assertSee('Test Project')
        ->assertSee('Agent Status')
        ->assertSee('Done')
        ->assertNoJavascriptErrors();
});

it('displays agent output with syntax highlighting', function () {
    $project = Project::factory()->create();
    $task = Task::factory()->create([
        'project_id' => $project->id,
        'title' => 'Output Test Task',
        'status' => TaskStatus::Done,
        'agent_output' => "$ Running command\nSuccess: Operation completed\nError: Something failed",
        'completed_at' => now(),
    ]);

    $page = visit("/tasks/{$task->id}");

    $page->assertSee('Running command')
        ->assertSee('Operation completed')
        ->assertSee('Something failed')
        ->assertNoJavascriptErrors();
});

it('shows progress indicator for running tasks', function () {
    $project = Project::factory()->create();
    $task = Task::factory()->create([
        'project_id' => $project->id,
        'title' => 'Running Task',
        'status' => TaskStatus::InProgress,
        'started_at' => now()->subMinutes(5),
    ]);

    $page = visit("/tasks/{$task->id}");

    $page->assertSee('Running Task')
        ->assertSee('Running')
        ->assertSee('Streaming')
        ->assertNoJavascriptErrors();
});

it('can navigate back to project dashboard', function () {
    $project = Project::factory()->create(['name' => 'Navigation Test Project']);
    $task = Task::factory()->create([
        'project_id' => $project->id,
        'title' => 'Navigation Test Task',
        'status' => TaskStatus::Done,
        'completed_at' => now(),
    ]);

    $page = visit("/tasks/{$task->id}");

    $page->assertSee('Navigation Test Task')
        ->assertSee('Navigation Test Project')
        ->assertNoJavascriptErrors();
});

it('displays commits when task has commits', function () {
    $project = Project::factory()->create();
    $task = Task::factory()->create([
        'project_id' => $project->id,
        'title' => 'Task with commits',
        'status' => TaskStatus::Done,
        'completed_at' => now(),
    ]);

    $task->commits()->create([
        'sha' => 'abc123def456',
        'message' => 'Add new feature implementation',
        'author' => 'Test Author',
        'created_at' => now(),
    ]);

    $page = visit("/tasks/{$task->id}");

    $page->assertSee('Commits (1)')
        ->assertSee('Add new feature implementation')
        ->assertSee('abc123d')
        ->assertSee('Test Author')
        ->assertNoJavascriptErrors();
});

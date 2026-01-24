<?php

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use Inertia\Testing\AssertableInertia;

it('shows task detail page', function () {
    $project = Project::factory()->create();
    $task = Task::factory()->create([
        'project_id' => $project->id,
        'title' => 'Test Task',
        'description' => 'Test description',
        'status' => TaskStatus::InProgress,
        'agent_output' => 'Some output',
        'started_at' => now()->subMinutes(5),
    ]);

    $response = $this->get("/tasks/{$task->id}");

    $response->assertSuccessful();
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('tasks/show')
        ->has('task')
        ->where('task.id', $task->id)
        ->where('task.title', 'Test Task')
        ->where('task.description', 'Test description')
        ->where('task.status', 'in_progress')
        ->where('task.agent_output', 'Some output')
    );
});

it('shows task with project relationship', function () {
    $project = Project::factory()->create(['name' => 'Test Project']);
    $task = Task::factory()->create([
        'project_id' => $project->id,
        'status' => TaskStatus::Queued,
    ]);

    $response = $this->get("/tasks/{$task->id}");

    $response->assertSuccessful();
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('tasks/show')
        ->has('task.project')
        ->where('task.project.id', $project->id)
        ->where('task.project.name', 'Test Project')
    );
});

it('shows task with commits', function () {
    $project = Project::factory()->create();
    $task = Task::factory()->create([
        'project_id' => $project->id,
        'status' => TaskStatus::Done,
        'completed_at' => now(),
    ]);

    $task->commits()->create([
        'sha' => 'abc123def456',
        'message' => 'Test commit message',
        'author' => 'Test Author',
        'created_at' => now(),
    ]);

    $response = $this->get("/tasks/{$task->id}");

    $response->assertSuccessful();
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('tasks/show')
        ->has('task.commits', 1)
        ->where('task.commits.0.sha', 'abc123def456')
        ->where('task.commits.0.message', 'Test commit message')
        ->where('task.commits.0.author', 'Test Author')
    );
});

it('returns 404 for non-existent task', function () {
    $response = $this->get('/tasks/99999');

    $response->assertNotFound();
});

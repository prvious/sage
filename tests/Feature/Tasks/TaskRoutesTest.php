<?php

use App\Models\Project;
use App\Models\Task;
use App\Models\Worktree;

it('returns 405 when accessing GET /tasks (index route removed)', function () {
    $response = $this->get('/tasks');

    // Expecting 405 Method Not Allowed since GET is not allowed
    expect($response->status())->toBe(405);
});

it('creates task via POST /tasks and redirects to dashboard', function () {
    $project = Project::factory()->create();
    $worktree = Worktree::factory()->create(['project_id' => $project->id]);

    $response = $this->post('/tasks', [
        'project_id' => $project->id,
        'worktree_id' => $worktree->id,
        'title' => 'New Task',
        'description' => 'Test description',
        'status' => 'idea',
    ]);

    $response->assertRedirect(route('dashboard'));
    $response->assertSessionHas('success', 'Task created successfully.');

    $this->assertDatabaseHas('tasks', [
        'project_id' => $project->id,
        'worktree_id' => $worktree->id,
        'title' => 'New Task',
        'description' => 'Test description',
        'status' => 'idea',
    ]);
});

it('updates task via PATCH /tasks/{task} and redirects to dashboard', function () {
    $project = Project::factory()->create();
    $worktree = Worktree::factory()->create(['project_id' => $project->id]);
    $task = Task::factory()->create([
        'project_id' => $project->id,
        'worktree_id' => $worktree->id,
        'title' => 'Old Title',
        'status' => 'idea',
    ]);

    $response = $this->patch("/tasks/{$task->id}", [
        'title' => 'Updated Title',
        'status' => 'in_progress',
    ]);

    $response->assertRedirect(route('dashboard'));
    $response->assertSessionHas('success', 'Task updated successfully.');

    $task->refresh();
    expect($task->title)->toBe('Updated Title');
    expect($task->status)->toBe('in_progress');
});

it('deletes task via DELETE /tasks/{task} and redirects to dashboard', function () {
    $project = Project::factory()->create();
    $worktree = Worktree::factory()->create(['project_id' => $project->id]);
    $task = Task::factory()->create([
        'project_id' => $project->id,
        'worktree_id' => $worktree->id,
    ]);

    $response = $this->delete("/tasks/{$task->id}");

    $response->assertRedirect(route('dashboard'));
    $response->assertSessionHas('success', 'Task deleted successfully.');

    $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
});

it('agent start route still works', function () {
    $project = Project::factory()->create();
    $worktree = Worktree::factory()->create(['project_id' => $project->id]);
    $task = Task::factory()->create([
        'project_id' => $project->id,
        'worktree_id' => $worktree->id,
    ]);

    $response = $this->post("/tasks/{$task->id}/start");

    // Assuming this returns some response (adjust based on actual implementation)
    expect($response->status())->toBeIn([200, 302]);
});

it('agent stop route still works', function () {
    $project = Project::factory()->create();
    $worktree = Worktree::factory()->create(['project_id' => $project->id]);
    $task = Task::factory()->create([
        'project_id' => $project->id,
        'worktree_id' => $worktree->id,
    ]);

    $response = $this->post("/tasks/{$task->id}/stop");

    // Assuming this returns some response (adjust based on actual implementation)
    expect($response->status())->toBeIn([200, 302]);
});

it('agent output route still works', function () {
    $project = Project::factory()->create();
    $worktree = Worktree::factory()->create(['project_id' => $project->id]);
    $task = Task::factory()->create([
        'project_id' => $project->id,
        'worktree_id' => $worktree->id,
    ]);

    $response = $this->get("/tasks/{$task->id}/output");

    // Assuming this returns some response (adjust based on actual implementation)
    expect($response->status())->toBeIn([200, 302]);
});

it('validates task creation', function () {
    $response = $this->post('/tasks', []);

    $response->assertSessionHasErrors(['project_id', 'title', 'status']);
});

it('validates task update', function () {
    $project = Project::factory()->create();
    $worktree = Worktree::factory()->create(['project_id' => $project->id]);
    $task = Task::factory()->create([
        'project_id' => $project->id,
        'worktree_id' => $worktree->id,
    ]);

    $response = $this->patch("/tasks/{$task->id}", [
        'title' => '',
        'status' => 'invalid_status',
    ]);

    $response->assertSessionHasErrors();
});

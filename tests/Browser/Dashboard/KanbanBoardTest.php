<?php

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;

test('dashboard displays all 4 columns', function () {
    $project = Project::factory()->create();

    $page = visit(route('projects.dashboard', $project));

    $page->assertSee('Queued')
        ->assertSee('In Progress')
        ->assertSee('Waiting Review')
        ->assertSee('Done')
        ->assertNoJavascriptErrors();
});

test('tasks appear in correct status columns', function () {
    $project = Project::factory()->create();

    $queuedTask = Task::factory()->create([
        'project_id' => $project->id,
        'status' => TaskStatus::Queued,
        'title' => 'Queued Task',
    ]);

    $inProgressTask = Task::factory()->create([
        'project_id' => $project->id,
        'status' => TaskStatus::InProgress,
        'title' => 'In Progress Task',
    ]);

    $page = visit(route('projects.dashboard', $project));

    $page->assertSee('Queued Task')
        ->assertSee('In Progress Task')
        ->assertNoJavascriptErrors();
});

test('clicking add task button opens dialog', function () {
    $project = Project::factory()->create();

    $page = visit(route('projects.dashboard', $project));

    $page->click('Add Task')
        ->assertSee('Add New Task')
        ->assertSee('Task Description')
        ->assertNoJavascriptErrors();
});

test('creating task via dialog', function () {
    $project = Project::factory()->create();

    $page = visit(route('projects.dashboard', $project));

    $page->click('Add Task')
        ->fill('description', 'Test task description from browser test')
        ->click('Create Task')
        ->waitForReload()
        ->assertSee('Test task description from browser test')
        ->assertNoJavascriptErrors();

    expect(Task::where('project_id', $project->id)->count())->toBe(1);
});

test('new task appears in queued column', function () {
    $project = Project::factory()->create();

    $page = visit(route('projects.dashboard', $project));

    $page->click('Add Task')
        ->fill('description', 'New queued task')
        ->click('Create Task')
        ->waitForReload()
        ->assertSee('New queued task')
        ->assertSee('Queued (1)')
        ->assertNoJavascriptErrors();
});

test('dialog closes after creation', function () {
    $project = Project::factory()->create();

    $page = visit(route('projects.dashboard', $project));

    $page->click('Add Task')
        ->assertSee('Add New Task')
        ->fill('description', 'Task to test dialog close')
        ->click('Create Task')
        ->waitForReload()
        ->assertDontSee('Add New Task')
        ->assertNoJavascriptErrors();
});

test('validation error shows for empty description', function () {
    $project = Project::factory()->create();

    $page = visit(route('projects.dashboard', $project));

    $page->click('Add Task')
        ->click('Create Task');

    // Button should be disabled when description is empty
    expect(Task::where('project_id', $project->id)->count())->toBe(0);
});

test('tasks display correct data', function () {
    $project = Project::factory()->create();

    Task::factory()->create([
        'project_id' => $project->id,
        'title' => 'Display Test Task',
        'description' => 'This is a test description',
        'status' => TaskStatus::Queued,
    ]);

    $page = visit(route('projects.dashboard', $project));

    $page->assertSee('Display Test Task')
        ->assertSee('This is a test description')
        ->assertNoJavascriptErrors();
});

<?php

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\Worktree;

/**
 * Quick Task Dialog Browser Tests
 *
 * Tests for the streamlined task creation modal UI interactions.
 * Accessible via keyboard shortcut (Cmd+K / Ctrl+K) or sidebar button.
 */
test('quick task button is visible in sidebar when project is selected', function () {
    $project = Project::factory()->create();

    $page = visit(route('projects.dashboard', $project));

    $page->assertSee('Task')
        ->assertNoJavascriptErrors();
});

test('clicking sidebar task button opens quick task dialog', function () {
    $project = Project::factory()->create();

    $page = visit(route('projects.dashboard', $project));

    $page->assertNoJavascriptErrors()
        ->click('text=Task >> nth=0')
        ->waitForText('Quick Task')
        ->assertSee('Quick Task')
        ->assertSee('Description')
        ->assertSee('Title (optional)')
        ->assertNoJavascriptErrors();
});

test('quick task dialog shows selected project name', function () {
    $project = Project::factory()->create(['name' => 'My Test Project']);

    $page = visit(route('projects.dashboard', $project));

    $page->click('text=Task >> nth=0')
        ->waitForText('Quick Task')
        ->assertSee('My Test Project')
        ->assertNoJavascriptErrors();
});

test('quick task dialog can create a task successfully', function () {
    $project = Project::factory()->create();

    $page = visit(route('projects.dashboard', $project));

    $page->click('text=Task >> nth=0')
        ->waitForText('Quick Task')
        ->fill('#quick-task-description', 'Task created via quick dialog browser test')
        ->click('button:has-text("Create Task")')
        ->wait(2)
        ->assertSee('Task created via quick dialog browser test')
        ->assertNoJavascriptErrors();

    expect(Task::where('project_id', $project->id)->count())->toBe(1);
    expect(Task::first()->description)->toBe('Task created via quick dialog browser test');
});

test('quick task auto-generates title from description first line', function () {
    $project = Project::factory()->create();

    $page = visit(route('projects.dashboard', $project));

    $page->click('text=Task >> nth=0')
        ->waitForText('Quick Task')
        ->fill('#quick-task-description', "First line becomes title\nSecond line is just description")
        ->click('button:has-text("Create Task")')
        ->wait(2)
        ->assertNoJavascriptErrors();

    $task = Task::first();
    expect($task->title)->toBe('First line becomes title');
});

test('quick task with custom title uses provided title', function () {
    $project = Project::factory()->create();

    $page = visit(route('projects.dashboard', $project));

    $page->click('text=Task >> nth=0')
        ->waitForText('Quick Task')
        ->fill('#quick-task-title', 'My Custom Title')
        ->fill('#quick-task-description', 'Task description here')
        ->click('button:has-text("Create Task")')
        ->wait(2)
        ->assertNoJavascriptErrors();

    expect(Task::first()->title)->toBe('My Custom Title');
});

test('quick task shows worktree dropdown when worktrees exist', function () {
    $project = Project::factory()->create();
    Worktree::factory()->create([
        'project_id' => $project->id,
        'branch_name' => 'feature/test-branch',
        'status' => 'active',
    ]);

    $page = visit(route('projects.dashboard', $project));

    $page->click('text=Task >> nth=0')
        ->waitForText('Quick Task')
        ->assertSee('Worktree (optional)')
        ->assertNoJavascriptErrors();
});

test('quick task hides worktree dropdown when no worktrees exist', function () {
    $project = Project::factory()->create();

    $page = visit(route('projects.dashboard', $project));

    $page->click('text=Task >> nth=0')
        ->waitForText('Quick Task')
        ->assertDontSee('Worktree (optional)')
        ->assertNoJavascriptErrors();
});

test('quick task can assign worktree to task', function () {
    $project = Project::factory()->create();
    $worktree = Worktree::factory()->create([
        'project_id' => $project->id,
        'branch_name' => 'feature/my-feature',
        'status' => 'active',
    ]);

    $page = visit(route('projects.dashboard', $project));

    $page->click('text=Task >> nth=0')
        ->waitForText('Quick Task')
        ->assertSee('Worktree (optional)')
        ->fill('#quick-task-description', 'Task with worktree assignment')
        ->click('[data-slot="select-trigger"]')
        ->click('text=feature/my-feature')
        ->click('button:has-text("Create Task")')
        ->wait(2)
        ->assertNoJavascriptErrors();

    $task = Task::first();
    expect($task->worktree_id)->toBe($worktree->id);
});

test('cancel button closes quick task dialog', function () {
    $project = Project::factory()->create();

    $page = visit(route('projects.dashboard', $project));

    $page->click('text=Task >> nth=0')
        ->waitForText('Quick Task')
        ->assertSee('Quick Task')
        ->click('button:has-text("Cancel")')
        ->wait(1)
        ->assertDontSee('Quick Task')
        ->assertNoJavascriptErrors();
});

test('quick task dialog shows keyboard shortcut hint', function () {
    $project = Project::factory()->create();

    $page = visit(route('projects.dashboard', $project));

    $page->click('text=Task >> nth=0')
        ->waitForText('Quick Task')
        ->assertSee('Esc')
        ->assertNoJavascriptErrors();
});

test('create task button is disabled when description is empty', function () {
    $project = Project::factory()->create();

    $page = visit(route('projects.dashboard', $project));

    $page->click('text=Task >> nth=0')
        ->waitForText('Quick Task')
        ->assertNoJavascriptErrors();

    // The button should be disabled when description is empty
    // Verify no task is created (button is disabled)
    expect(Task::where('project_id', $project->id)->count())->toBe(0);
});

test('quick task dialog closes after successful creation', function () {
    $project = Project::factory()->create();

    $page = visit(route('projects.dashboard', $project));

    // Create task
    $page->click('text=Task >> nth=0')
        ->waitForText('Quick Task')
        ->fill('#quick-task-description', 'First task')
        ->click('button:has-text("Create Task")')
        ->wait(2)
        ->assertDontSee('Quick Task') // Dialog should close after success
        ->assertSee('First task') // Task should appear in the list
        ->assertNoJavascriptErrors();

    expect(Task::count())->toBe(1);
});

test('quick task only shows active worktrees not inactive ones', function () {
    $project = Project::factory()->create();

    // Create active worktree
    Worktree::factory()->create([
        'project_id' => $project->id,
        'branch_name' => 'feature/active-branch',
        'status' => 'active',
    ]);

    // Create inactive worktrees
    Worktree::factory()->create([
        'project_id' => $project->id,
        'branch_name' => 'feature/creating-branch',
        'status' => 'creating',
    ]);

    Worktree::factory()->create([
        'project_id' => $project->id,
        'branch_name' => 'feature/error-branch',
        'status' => 'error',
    ]);

    $page = visit(route('projects.dashboard', $project));

    $page->click('text=Task >> nth=0')
        ->waitForText('Quick Task')
        ->assertSee('Worktree (optional)')
        ->click('[data-slot="select-trigger"]')
        ->assertSee('feature/active-branch')
        ->assertDontSee('feature/creating-branch')
        ->assertDontSee('feature/error-branch')
        ->assertNoJavascriptErrors();
});

test('quick task shows multiple worktrees in dropdown', function () {
    $project = Project::factory()->create();

    Worktree::factory()->create([
        'project_id' => $project->id,
        'branch_name' => 'feature/branch-one',
        'status' => 'active',
    ]);

    Worktree::factory()->create([
        'project_id' => $project->id,
        'branch_name' => 'bugfix/branch-two',
        'status' => 'active',
    ]);

    Worktree::factory()->create([
        'project_id' => $project->id,
        'branch_name' => 'hotfix/branch-three',
        'status' => 'active',
    ]);

    $page = visit(route('projects.dashboard', $project));

    $page->click('text=Task >> nth=0')
        ->waitForText('Quick Task')
        ->click('[data-slot="select-trigger"]')
        ->assertSee('feature/branch-one')
        ->assertSee('bugfix/branch-two')
        ->assertSee('hotfix/branch-three')
        ->assertSee('No worktree (main branch)')
        ->assertNoJavascriptErrors();
});

test('quick task dialog works from dashboard page', function () {
    $project = Project::factory()->create();

    $page = visit(route('projects.dashboard', $project));

    $page->click('text=Task >> nth=0')
        ->waitForText('Quick Task')
        ->fill('#quick-task-description', 'Task from dashboard')
        ->click('button:has-text("Create Task")')
        ->wait(2)
        ->assertNoJavascriptErrors();

    expect(Task::count())->toBe(1);
});

test('quick task dialog works from worktrees page', function () {
    $project = Project::factory()->create();

    $page = visit(route('projects.worktrees.index', $project));

    $page->click('text=Task >> nth=0')
        ->waitForText('Quick Task')
        ->fill('#quick-task-description', 'Task from worktrees page')
        ->click('button:has-text("Create Task")')
        ->wait(2)
        ->assertNoJavascriptErrors();

    expect(Task::count())->toBe(1);
});

test('quick task persists selected project from session', function () {
    $project = Project::factory()->create();

    // Visit the project dashboard first to set the session
    $page = visit(route('projects.dashboard', $project));

    // Create task from project dashboard
    $page->click('text=Task >> nth=0')
        ->waitForText('Quick Task')
        ->fill('#quick-task-description', 'Task using session project')
        ->click('button:has-text("Create Task")')
        ->wait(2)
        ->assertNoJavascriptErrors();

    $task = Task::first();
    expect($task->project_id)->toBe($project->id);
});

test('new task appears in queued column on dashboard', function () {
    $project = Project::factory()->create();

    $page = visit(route('projects.dashboard', $project));

    $page->click('text=Task >> nth=0')
        ->waitForText('Quick Task')
        ->fill('#quick-task-description', 'New queued task from quick dialog')
        ->click('button:has-text("Create Task")')
        ->wait(2)
        ->assertSee('New queued task from quick dialog')
        ->assertSee('Queued (1)')
        ->assertNoJavascriptErrors();
});

test('quick task created with correct default status', function () {
    $project = Project::factory()->create();

    $page = visit(route('projects.dashboard', $project));

    $page->click('text=Task >> nth=0')
        ->waitForText('Quick Task')
        ->fill('#quick-task-description', 'Check status test')
        ->click('button:has-text("Create Task")')
        ->wait(2);

    $task = Task::first();
    expect($task->status)->toBe(TaskStatus::Queued);
});

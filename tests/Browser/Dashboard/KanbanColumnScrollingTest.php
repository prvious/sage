<?php

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;

test('kanban columns have data attributes', function () {
    $project = Project::factory()->create();

    $page = visit(route('projects.dashboard', $project));

    $page->assertNoJavascriptErrors();

    // Verify columns have data-column attributes using assertAttribute
    $page->assertAttribute('[data-column="queued"]', 'data-column', 'queued')
        ->assertAttribute('[data-column="in_progress"]', 'data-column', 'in_progress')
        ->assertAttribute('[data-column="waiting_review"]', 'data-column', 'waiting_review')
        ->assertAttribute('[data-column="done"]', 'data-column', 'done');
});

test('kanban columns have independent scrolling', function () {
    $project = Project::factory()->create();

    // Create many tasks in the queued column to make it scrollable
    Task::factory()->count(20)->create([
        'project_id' => $project->id,
        'status' => TaskStatus::Queued,
    ]);

    // Create just one task in in_progress to compare
    Task::factory()->create([
        'project_id' => $project->id,
        'status' => TaskStatus::InProgress,
    ]);

    $page = visit(route('projects.dashboard', $project));

    $page->assertNoJavascriptErrors();

    // Get the scrollable container for queued column
    $queuedScrollTop = $page->script('document.querySelector("[data-column=queued]").scrollTop');
    expect($queuedScrollTop)->toBe(0);

    // Scroll the queued column
    $page->script('document.querySelector("[data-column=queued]").scrollTop = 100');

    // Verify queued column scrolled
    $queuedScrollTopAfter = $page->script('document.querySelector("[data-column=queued]").scrollTop');
    expect($queuedScrollTopAfter)->toBeGreaterThan(0);

    // Verify other column didn't scroll
    $inProgressScrollTop = $page->script('document.querySelector("[data-column=in_progress]").scrollTop');
    expect($inProgressScrollTop)->toBe(0);

    // Verify page itself didn't scroll
    $pageScrollTop = $page->script('window.scrollY');
    expect($pageScrollTop)->toBe(0);
});

test('column headers remain fixed when scrolling', function () {
    $project = Project::factory()->create();

    // Create many tasks to make column scrollable
    Task::factory()->count(20)->create([
        'project_id' => $project->id,
        'status' => TaskStatus::Queued,
    ]);

    $page = visit(route('projects.dashboard', $project));

    $page->assertNoJavascriptErrors();

    // Get header position before scroll
    $headerTop = $page->script('document.querySelector("[data-column=queued]").parentElement.querySelector("h2").getBoundingClientRect().top');

    // Scroll the column
    $page->script('document.querySelector("[data-column=queued]").scrollTop = 200');

    // Get header position after scroll
    $headerTopAfter = $page->script('document.querySelector("[data-column=queued]").parentElement.querySelector("h2").getBoundingClientRect().top');

    // Header should be in same position (fixed)
    expect($headerTopAfter)->toBe($headerTop);
});

test('all columns display with full height layout', function () {
    $project = Project::factory()->create();

    Task::factory()->create([
        'project_id' => $project->id,
        'status' => TaskStatus::Queued,
    ]);
    Task::factory()->create([
        'project_id' => $project->id,
        'status' => TaskStatus::InProgress,
    ]);
    Task::factory()->create([
        'project_id' => $project->id,
        'status' => TaskStatus::WaitingReview,
    ]);
    Task::factory()->create([
        'project_id' => $project->id,
        'status' => TaskStatus::Done,
    ]);

    $page = visit(route('projects.dashboard', $project));

    $page->assertSee('Queued (1)')
        ->assertSee('In Progress (1)')
        ->assertSee('Waiting Review (1)')
        ->assertSee('Done (1)')
        ->assertNoJavascriptErrors();

    // Verify all columns have scrollable containers
    $page->assertAttribute('[data-column="queued"]', 'data-column', 'queued')
        ->assertAttribute('[data-column="in_progress"]', 'data-column', 'in_progress')
        ->assertAttribute('[data-column="waiting_review"]', 'data-column', 'waiting_review')
        ->assertAttribute('[data-column="done"]', 'data-column', 'done');
});

test('empty columns display correctly', function () {
    $project = Project::factory()->create();

    $page = visit(route('projects.dashboard', $project));

    $page->assertSee('Queued (0)')
        ->assertSee('In Progress (0)')
        ->assertSee('Waiting Review (0)')
        ->assertSee('Done (0)')
        ->assertNoJavascriptErrors();

    // Verify empty state message appears in scrollable area
    $page->assertSeeIn('[data-column="queued"]', 'No tasks');
});

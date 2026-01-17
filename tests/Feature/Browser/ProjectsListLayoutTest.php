<?php

use App\Models\Project;

it('displays centered card layout with sage logo', function () {
    $page = visit('/projects');

    $page->assertSee('Sage')
        ->assertScript('document.querySelector("input[type=\'search\']") !== null', true) // Search input should be visible
        ->assertScript('document.querySelector("button[aria-label=\'Create new project\']") !== null', true) // Plus button should be visible
        ->assertNoJavascriptErrors();
});

it('displays empty state when no projects exist', function () {
    $page = visit('/projects');

    $page->assertSee('No projects yet')
        ->assertSee('Get started by adding your first Laravel project')
        ->assertSee('Add Your First Project')
        ->assertNoJavascriptErrors();
});

it('displays projects in scrollable list', function () {
    Project::factory()->count(3)->create();

    $page = visit('/projects');

    $page->assertNoJavascriptErrors();
});

it('shows plus button in card header', function () {
    $page = visit('/projects');

    $page->assertScript('document.querySelector("button[aria-label=\'Create new project\']") !== null', true)
        ->assertNoJavascriptErrors();
});

it('displays project stats correctly', function () {
    Project::factory()
        ->hasWorktrees(5)
        ->hasTasks(10)
        ->create(['name' => 'Stats Test Project']);

    $page = visit('/projects');

    $page->assertSee('Stats Test Project')
        ->assertSee('5')
        ->assertSee('Worktrees')
        ->assertSee('10')
        ->assertSee('Tasks')
        ->assertNoJavascriptErrors();
});

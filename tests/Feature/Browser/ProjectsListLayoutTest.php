<?php

use App\Models\Project;

it('displays centered card layout with sage logo', function () {
    $page = visit('/projects');

    $page->assertSee('Sage')
        ->assertSee('Projects')
        ->assertSee('Select a project to continue')
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

it('shows new project button in card header', function () {
    $page = visit('/projects');

    $page->assertSee('New Project')
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

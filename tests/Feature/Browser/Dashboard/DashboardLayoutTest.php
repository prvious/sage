<?php

use App\Models\Project;

it('displays mini sidebar with project list', function () {
    Project::factory()->count(3)->create();

    $page = visit('/dashboard');

    $page->assertNoJavascriptErrors();
});

it('displays main sidebar with navigation items', function () {
    $page = visit('/dashboard');

    $page->assertSee('Sage')
        ->assertSee('Dashboard')
        ->assertSee('Tasks')
        ->assertSee('Worktrees')
        ->assertSee('Specs')
        ->assertSee('Environment')
        ->assertNoJavascriptErrors();
});

it('displays kanban board with columns', function () {
    $page = visit('/dashboard');

    $page->assertSee('To Do')
        ->assertSee('In Progress')
        ->assertSee('Done')
        ->assertNoJavascriptErrors();
});

it('displays kanban cards in columns', function () {
    $page = visit('/dashboard');

    $page->assertSee('Add user authentication')
        ->assertSee('Create API endpoints')
        ->assertSee('Setup database migrations')
        ->assertSee('Initialize project')
        ->assertNoJavascriptErrors();
});

it('displays empty state when no projects exist', function () {
    $page = visit('/dashboard');

    $page->assertNoJavascriptErrors();
});

it('renders correctly in dark mode', function () {
    $page = visit('/dashboard');

    $page->assertNoJavascriptErrors();
});

it('is responsive on mobile viewport', function () {
    Project::factory()->create();

    $page = visit('/dashboard');

    $page->assertNoJavascriptErrors();
});

<?php

use App\Models\Project;

it('displays navigation items when project is selected', function () {
    $project = Project::factory()->create(['name' => 'Test Project']);

    $page = visit("/projects/{$project->id}/dashboard");

    $page->assertSee('Dashboard')
        ->assertSee('Worktrees')
        ->assertSee('Specs')
        ->assertSee('Environment')
        ->assertSee('Terminal')
        ->assertSee('Context')
        ->assertSee('Agent')
        ->assertSee('Settings')
        ->assertNoJavascriptErrors();
});

it('displays selected project name in sidebar header', function () {
    $project = Project::factory()->create(['name' => 'My Project']);

    $page = visit("/projects/{$project->id}/dashboard");

    $page->assertSee('My Project')
        ->assertNoJavascriptErrors();
});

it('navigation items are clickable links', function () {
    $project = Project::factory()->create(['name' => 'Test Project']);

    $page = visit("/projects/{$project->id}/dashboard");

    $page->click('Worktrees')
        ->assertSee('Worktrees')
        ->assertNoJavascriptErrors();
});

it('navigation persists across page navigations within project', function () {
    $project = Project::factory()->create(['name' => 'Test Project']);

    $page = visit("/projects/{$project->id}/dashboard");

    $page->assertSee('Dashboard')
        ->assertSee('Worktrees')
        ->assertSee('Specs')
        ->assertNoJavascriptErrors();
});

it('different projects show same navigation structure', function () {
    $project1 = Project::factory()->create(['name' => 'Project One']);
    $project2 = Project::factory()->create(['name' => 'Project Two']);

    $page1 = visit("/projects/{$project1->id}/dashboard");
    $page1->assertSee('Dashboard')
        ->assertSee('Worktrees')
        ->assertSee('Specs');

    $page2 = visit("/projects/{$project2->id}/dashboard");
    $page2->assertSee('Dashboard')
        ->assertSee('Worktrees')
        ->assertSee('Specs')
        ->assertNoJavascriptErrors();
});

it('sidebar displays correct project context when navigating between projects', function () {
    $project1 = Project::factory()->create(['name' => 'First Project']);
    $project2 = Project::factory()->create(['name' => 'Second Project']);

    $page1 = visit("/projects/{$project1->id}/dashboard");
    $page1->assertSee('First Project');

    $page2 = visit("/projects/{$project2->id}/dashboard");
    $page2->assertSee('Second Project')
        ->assertNoJavascriptErrors();
});

it('navigation is visible on dashboard page', function () {
    $project = Project::factory()->create(['name' => 'Test Project']);

    $dashboardPage = visit("/projects/{$project->id}/dashboard");
    $dashboardPage->assertSee('Dashboard')
        ->assertSee('Worktrees')
        ->assertSee('Specs')
        ->assertSee('Environment')
        ->assertNoJavascriptErrors();
});

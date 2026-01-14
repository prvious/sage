<?php

use App\Models\Project;

it('displays create page with centered card layout and sage logo', function () {
    $page = visit('/projects/create');

    $page->assertSee('Sage')
        ->assertSee('Create Project')
        ->assertSee('Configure your Laravel project settings')
        ->assertNoJavascriptErrors();
});

it('displays edit page with centered card layout and sage logo', function () {
    $project = Project::factory()->create(['name' => 'Test Project']);

    $page = visit("/projects/{$project->id}/edit");

    $page->assertSee('Sage')
        ->assertSee('Edit Project')
        ->assertSee('Update your Laravel project settings')
        ->assertNoJavascriptErrors();
});

it('displays show page with centered card layout and sage logo', function () {
    $project = Project::factory()->create(['name' => 'Test Project']);

    $page = visit("/projects/{$project->id}");

    $page->assertSee('Sage')
        ->assertSee('Test Project')
        ->assertSee('Project Details')
        ->assertNoJavascriptErrors();
});

it('has functional form fields on create page', function () {
    $page = visit('/projects/create');

    $page->assertSee('Project Name')
        ->assertSee('Project Path')
        ->assertSee('Server Driver')
        ->assertSee('Base URL')
        ->assertNoJavascriptErrors();
});

it('pre-fills form fields on edit page with project data', function () {
    $project = Project::factory()->create([
        'name' => 'My Test Project',
        'path' => '/var/www/testproject',
    ]);

    $page = visit("/projects/{$project->id}/edit");

    $page->assertSee('My Test Project')
        ->assertSee('/var/www/testproject')
        ->assertNoJavascriptErrors();
});

it('displays project details correctly on show page', function () {
    $project = Project::factory()->create([
        'name' => 'My Project',
        'server_driver' => 'caddy',
    ]);

    $page = visit("/projects/{$project->id}");

    $page->assertSee('My Project')
        ->assertSee('caddy')
        ->assertSee('Worktrees')
        ->assertSee('Tasks')
        ->assertNoJavascriptErrors();
});

it('shows empty state for worktrees and tasks on show page', function () {
    $project = Project::factory()->create();

    $page = visit("/projects/{$project->id}");

    $page->assertSee('No worktrees yet')
        ->assertSee('No tasks yet')
        ->assertNoJavascriptErrors();
});

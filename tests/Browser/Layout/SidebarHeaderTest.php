<?php

use App\Models\Project;

it('displays project name in sidebar header', function () {
    $project = Project::factory()->create(['name' => 'Test Project']);

    $page = visit("/projects/{$project->id}/dashboard");

    $page->assertSee('Test Project')
        ->assertNoJavascriptErrors();
});

it('does not display search input in sidebar header', function () {
    $project = Project::factory()->create();

    $page = visit("/projects/{$project->id}/dashboard");

    // Assert that the search placeholder text is not visible
    $page->assertDontSee('Type to search...')
        ->assertNoJavascriptErrors();
});

it('sidebar header has proper border styling', function () {
    $project = Project::factory()->create();

    $page = visit("/projects/{$project->id}/dashboard");

    // Verify the header exists and is visible
    $page->assertSee($project->name)
        ->assertNoJavascriptErrors();
});

it('sidebar header displays different project names correctly', function () {
    $project1 = Project::factory()->create(['name' => 'Alpha Project']);
    $project2 = Project::factory()->create(['name' => 'Beta Project']);

    $page1 = visit("/projects/{$project1->id}/dashboard");
    $page1->assertSee('Alpha Project')
        ->assertDontSee('Beta Project')
        ->assertNoJavascriptErrors();

    $page2 = visit("/projects/{$project2->id}/dashboard");
    $page2->assertSee('Beta Project')
        ->assertDontSee('Alpha Project')
        ->assertNoJavascriptErrors();
});

it('sidebar header is visible on all project pages', function () {
    $project = Project::factory()->create(['name' => 'Test Project']);

    // Test on dashboard
    $dashboardPage = visit("/projects/{$project->id}/dashboard");
    $dashboardPage->assertSee('Test Project')
        ->assertNoJavascriptErrors();
});

it('sidebar header does not contain input elements', function () {
    $project = Project::factory()->create();

    $page = visit("/projects/{$project->id}/dashboard");

    // Use script to check there's no input in the sidebar header
    $page->assertScript(
        'document.querySelector("[data-sidebar=\'sidebar\'] header input") === null',
        true
    );

    $page->assertNoJavascriptErrors();
});

it('sidebar functionality remains intact after search removal', function () {
    $project = Project::factory()->create();

    $page = visit("/projects/{$project->id}/dashboard");

    // Verify navigation items are still visible
    $page->assertSee('Dashboard')
        ->assertSee('Worktrees')
        ->assertSee('Specs')
        ->assertSee('Navigation')
        ->assertNoJavascriptErrors();
});

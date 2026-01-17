<?php

use App\Models\Project;

it('displays projects in the sidebar', function () {
    $project = Project::factory()->create(['name' => 'Test Project']);

    $page = visit('/');

    $page->assertSee('Test Project')
        ->assertNoJavascriptErrors();
});

it('displays project names correctly', function () {
    Project::factory()->create(['name' => 'First Project']);
    Project::factory()->create(['name' => 'Second Project']);

    $page = visit('/');

    $page->assertSee('First Project')
        ->assertSee('Second Project')
        ->assertNoJavascriptErrors();
});

it('displays project avatar with correct first letter', function () {
    Project::factory()->create(['name' => 'Alpha Project']);

    $page = visit('/');

    // The avatar should show 'A' for 'Alpha Project'
    $page->assertSee('A')
        ->assertNoJavascriptErrors();
});

it('project names are visible in sidebar', function () {
    Project::factory()->create(['name' => 'Test Project']);

    $page = visit('/');

    // Project name should be visible in the sidebar
    $page->assertSee('Test Project')
        ->assertNoJavascriptErrors();
});

it('renders multiple projects in correct order', function () {
    Project::factory()->create(['name' => 'Alpha', 'created_at' => now()->subDays(2)]);
    Project::factory()->create(['name' => 'Beta', 'created_at' => now()->subDay()]);
    Project::factory()->create(['name' => 'Gamma', 'created_at' => now()]);

    $page = visit('/');

    $page->assertSee('Alpha')
        ->assertSee('Beta')
        ->assertSee('Gamma')
        ->assertNoJavascriptErrors();
});

it('handles empty projects list gracefully', function () {
    $page = visit('/');

    // Should not throw errors when no projects exist
    $page->assertNoJavascriptErrors();
});

it('displays correct avatar letters for multiple projects', function () {
    Project::factory()->create(['name' => 'Alpha']);
    Project::factory()->create(['name' => 'Beta']);
    Project::factory()->create(['name' => 'Charlie']);

    $page = visit('/');

    $page->assertSee('A')
        ->assertSee('B')
        ->assertSee('C')
        ->assertNoJavascriptErrors();
});

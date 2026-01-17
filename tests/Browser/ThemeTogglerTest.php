<?php

use App\Models\Project;

it('displays theme toggler in bottom-left corner on project list page', function () {
    Project::factory()->count(2)->create();

    $page = visit('/projects');

    $page->assertSee('System')
        ->assertNoJavascriptErrors();
});

it('displays theme toggler on project create page', function () {
    $page = visit('/projects/create');

    $page->assertSee('System')
        ->assertNoJavascriptErrors();
});

it('displays theme toggler on project edit page', function () {
    $project = Project::factory()->create();

    $page = visit("/projects/{$project->id}/edit");

    $page->assertSee('System')
        ->assertNoJavascriptErrors();
});

it('displays theme toggler on project dashboard page', function () {
    $project = Project::factory()->create();

    $page = visit("/projects/{$project->id}/dashboard");

    $page->assertSee('System')
        ->assertNoJavascriptErrors();
});

it('theme toggler button is interactive and opens dropdown', function () {
    $page = visit('/projects/create');

    // Click theme toggler to open dropdown
    $page->click('button:has-text("System")');

    // Wait longer for dropdown animation to complete
    $page->wait(1000);

    // Verify dropdown options are visible
    $page->assertSee('Light');
    $page->assertSee('Dark');

    $page->assertNoJavascriptErrors();
});

it('can select light theme from dropdown', function () {
    $page = visit('/projects/create');

    // Click theme toggler
    $page->click('button:has-text("System")');
    $page->wait(500);

    // Click Light option
    $page->click('span:has-text("Light")');
    $page->wait(500);

    // Button should update to show Light
    $page->assertSee('Light');

    $page->assertNoJavascriptErrors();
});

it('can select dark theme from dropdown', function () {
    $page = visit('/projects/create');

    // Click theme toggler
    $page->click('button:has-text("System")');
    $page->wait(500);

    // Click Dark option
    $page->click('span:has-text("Dark")');
    $page->wait(500);

    // Button should update to show Dark
    $page->assertSee('Dark');

    $page->assertNoJavascriptErrors();
});

it('shows checkmark indicator on selected theme', function () {
    $page = visit('/projects/create');

    // Click theme toggler to open dropdown
    $page->click('button:has-text("System")');
    $page->wait(500);

    // System should be selected by default, look for checkmark
    $page->assertSee('✓');

    $page->assertNoJavascriptErrors();
});

it('theme preference persists in localStorage', function () {
    $page = visit('/projects/create');

    // Select dark theme
    $page->click('button:has-text("System")');
    $page->wait(500);
    $page->click('span:has-text("Dark")');
    $page->wait(500);

    // Verify localStorage was updated
    $page->assertScript('localStorage.getItem("appearance")', 'dark');

    $page->assertNoJavascriptErrors();
});

it('theme preference persists after page reload', function () {
    $page = visit('/projects/create');

    // Select dark theme
    $page->click('button:has-text("System")');
    $page->wait(500);
    $page->click('span:has-text("Dark")');
    $page->wait(500);

    // Reload page
    $page->navigate('/projects/create');
    $page->wait(500);

    // Button should still show Dark
    $page->assertSee('Dark');

    $page->assertNoJavascriptErrors();
});

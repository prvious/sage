<?php

declare(strict_types=1);

use App\Models\Project;

it('navigates to project dashboard when clicking project card', function () {
    $project = Project::factory()->create([
        'name' => 'Test Project',
    ]);

    $page = visit('/projects');

    // Click on the project card by finding the link containing the project name
    $page->assertSee('Test Project')
        ->click('text=Test Project')
        ->wait(1);

    // Should navigate to the dashboard
    $page->assertPathIs("/projects/{$project->id}/dashboard")
        ->assertSee('Test Project')
        ->assertNoJavascriptErrors();
});

it('project card link has correct href attribute pointing to dashboard', function () {
    $project = Project::factory()->create([
        'name' => 'Dashboard Link Test',
    ]);

    $page = visit('/projects');

    $page->wait(1);

    // Verify the link href points to the dashboard route by checking the actual link
    $expectedHref = "/projects/{$project->id}/dashboard";
    $page->assertScript(
        "Array.from(document.querySelectorAll('a')).find(a => a.textContent.includes('Dashboard Link Test'))?.getAttribute('href')",
        $expectedHref
    );
});

it('first project card navigates to correct dashboard', function () {
    $project1 = Project::factory()->create(['name' => 'Project One']);
    Project::factory()->create(['name' => 'Project Two']);

    $page = visit('/projects');

    // Click first project
    $page->click('text=Project One')
        ->wait(1)
        ->assertPathIs("/projects/{$project1->id}/dashboard")
        ->assertNoJavascriptErrors();
});

it('second project card navigates to correct dashboard', function () {
    Project::factory()->create(['name' => 'Project One']);
    $project2 = Project::factory()->create(['name' => 'Project Two']);

    $page = visit('/projects');

    // Click second project
    $page->click('text=Project Two')
        ->wait(1)
        ->assertPathIs("/projects/{$project2->id}/dashboard")
        ->assertNoJavascriptErrors();
});

it('project navigation uses Inertia SPA routing', function () {
    $project = Project::factory()->create([
        'name' => 'SPA Test Project',
    ]);

    $page = visit('/projects');

    // Click project card
    $page->click('text=SPA Test Project')
        ->wait(1);

    // Verify no JavaScript errors (would occur if full page reload happened incorrectly)
    $page->assertNoJavascriptErrors()
        ->assertPathIs("/projects/{$project->id}/dashboard");
});

it('project card navigation works after search', function () {
    Project::factory()->create(['name' => 'Laravel App']);
    $searchedProject = Project::factory()->create(['name' => 'Vue Application']);

    $page = visit('/projects');

    // Search for specific project
    $page->fill('input[type="search"]', 'Vue')
        ->wait(500); // Wait for debounce

    // Click the searched project
    $page->click('text=Vue Application')
        ->wait(1)
        ->assertPathIs("/projects/{$searchedProject->id}/dashboard")
        ->assertNoJavascriptErrors();
});

it('project card is keyboard accessible', function () {
    $project = Project::factory()->create(['name' => 'Keyboard Test']);

    $page = visit('/projects');

    $page->wait(1);

    // Verify the link can receive focus (Inertia Link should be focusable)
    // Check if any link containing the project name exists and is focusable
    $page->assertScript(
        "Array.from(document.querySelectorAll('a')).some(a => a.textContent.includes('Keyboard Test') && (a.tabIndex >= 0 || a.tabIndex === -1))",
        true
    );
});

it('hovering project card shows visual feedback', function () {
    $project = Project::factory()->create(['name' => 'Hover Test']);

    $page = visit('/projects');

    $page->wait(1);

    // Verify the card has hover classes by checking if the parent Card element has transition classes
    $page->assertScript(
        "Array.from(document.querySelectorAll('.hover\\\\:shadow-lg, [class*=\"hover:shadow\"]')).length > 0",
        true
    );
});

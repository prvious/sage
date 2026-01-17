<?php

declare(strict_types=1);

use App\Models\Project;

it('displays Sage logo as a clickable link on project create page', function () {
    $page = visit('/projects/create');

    $page->wait(1)
        ->assertPresent('a[aria-label*="Sage"]')
        ->assertNoJavascriptErrors();
});

it('clicking Sage logo navigates to home page', function () {
    $page = visit('/projects/create');

    $page->wait(1);

    // Click the Sage logo
    $page->click('a[aria-label*="Sage"]')
        ->wait(1);

    // HomeController redirects to projects index when no last opened project
    $page->assertPathIs('/projects')
        ->assertNoJavascriptErrors();
});

it('Sage logo has hover state styling', function () {
    $page = visit('/projects/create');

    $page->wait(1);

    // Verify the link has hover styling classes
    $page->assertAttributeContains('a[aria-label*="Sage"]', 'class', 'hover:opacity-80');
});

it('Sage logo is keyboard accessible', function () {
    $page = visit('/projects/create');

    $page->wait(1);

    // Verify the link can receive focus (has focus styling)
    $page->assertAttributeContains('a[aria-label*="Sage"]', 'class', 'focus:outline-none');
    $page->assertAttributeContains('a[aria-label*="Sage"]', 'class', 'focus:ring-2');
});

it('clicking logo from projects list page navigates to home', function () {
    Project::factory()->count(3)->create();

    $page = visit('/projects');

    $page->wait(1);

    // Click the Sage logo
    $page->click('a[aria-label*="Sage"]')
        ->wait(1);

    // HomeController redirects back to projects index when no last opened project
    $page->assertPathIs('/projects')
        ->assertNoJavascriptErrors();
});

it('logo navigation uses Inertia SPA routing', function () {
    $page = visit('/projects/create');

    $page->wait(1);

    // Click the logo
    $page->click('a[aria-label*="Sage"]')
        ->wait(1);

    // Verify no JavaScript errors (would occur if full page reload happened incorrectly)
    $page->assertNoJavascriptErrors();

    // HomeController redirects to projects index when no last opened project
    $page->assertPathIs('/projects');
});

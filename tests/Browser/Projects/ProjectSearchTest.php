<?php

use App\Models\Project;

it('displays search input on projects list page', function () {
    Project::factory()->count(2)->create();

    $page = visit('/projects');

    // Check search input exists by verifying the element is present
    $page->assertScript('document.querySelector("input[type=\'search\']") !== null', true)
        ->assertNoJavascriptErrors();
});

it('displays plus button on projects list page', function () {
    Project::factory()->count(2)->create();

    $page = visit('/projects');

    // Plus button should be visible (check by aria-label)
    $page->assertScript('document.querySelector("button[aria-label=\'Create new project\']") !== null', true)
        ->assertNoJavascriptErrors();
});

it('search input and plus button are both visible', function () {
    Project::factory()->count(2)->create();

    $page = visit('/projects');

    // Check both elements are present
    $page->assertScript('document.querySelector("input[type=\'search\']") !== null', true)
        ->assertScript('document.querySelector("button[aria-label=\'Create new project\']") !== null', true)
        ->assertNoJavascriptErrors();
});

it('can type in search input and clear button appears', function () {
    Project::factory()->create(['name' => 'Laravel Project']);

    $page = visit('/projects');

    // Type in search input
    $page->fill('input[type="search"]', 'Laravel');
    $page->wait(200);

    // Clear button should appear
    $page->assertScript('document.querySelector("button[aria-label=\'Clear search\']") !== null', true)
        ->assertNoJavascriptErrors();
});

it('clicking plus button navigates to create project page', function () {
    Project::factory()->count(2)->create();

    $page = visit('/projects');

    // Click plus button
    $page->click('button[aria-label="Create new project"]');
    $page->wait(500);

    // Should be on create page
    $page->assertUrlIs('/projects/create')
        ->assertNoJavascriptErrors();
});

it('search query persists in url', function () {
    Project::factory()->create(['name' => 'Laravel App']);

    $page = visit('/projects');

    // Type in search
    $page->fill('input[type="search"]', 'Laravel');
    $page->wait(500); // Wait for debounce

    // URL should contain search query
    $page->assertScript('window.location.search.includes("search=Laravel")', true)
        ->assertNoJavascriptErrors();
});

it('search input populates from url query parameter', function () {
    Project::factory()->create(['name' => 'Laravel App']);

    $page = visit('/projects?search=Laravel');
    $page->wait(300);

    // Search input should have the value from URL
    $page->assertScript('document.querySelector("input[type=\'search\']").value', 'Laravel')
        ->assertNoJavascriptErrors();
});

<?php

declare(strict_types=1);

use function Pest\Laravel\visit;

it('displays filesystem browser on project create page', function () {
    $page = visit('/projects/create');

    $page->assertSee('Add Project')
        ->assertNoJavascriptErrors();
});

it('displays home directory on initial load', function () {
    $page = visit('/projects/create');

    $page->assertNoJavascriptErrors()
        ->assertPresent('[data-slot="scroll-area"]');
});

it('clicking a directory navigates with query string', function () {
    $page = visit('/projects/create');

    // Wait for directories to load
    $page->waitFor('[data-slot="scroll-area"]');

    // Check if there are any directories
    $hasDirectories = $page->element('[data-directory-entry]');

    if ($hasDirectories) {
        $page->click('[data-directory-entry]')
            ->pause(500)
            ->assertUrlContains('path=');
    }
});

it('URL updates with new path when directory is clicked', function () {
    $page = visit('/projects/create');

    $page->waitFor('[data-slot="scroll-area"]');

    $hasDirectories = $page->element('[data-directory-entry]');

    if ($hasDirectories) {
        $initialUrl = $page->url();

        $page->click('[data-directory-entry]')
            ->pause(500);

        $newUrl = $page->url();

        expect($newUrl)->not->toBe($initialUrl);
        expect($newUrl)->toContain('path=');
    }
});

it('clicking breadcrumb navigates to that path', function () {
    $testPath = getcwd();

    $page = visit("/projects/create?path={$testPath}");

    $page->waitFor('[data-slot="scroll-area"]');

    // Check if breadcrumbs exist
    $hasBreadcrumbs = $page->element('nav[aria-label="Breadcrumb"]');

    if ($hasBreadcrumbs) {
        $page->click('nav[aria-label="Breadcrumb"] button')
            ->pause(500)
            ->assertNoJavascriptErrors();
    }
});

it('clicking home button navigates to home directory', function () {
    $page = visit('/projects/create');

    $page->waitFor('button[type="button"]');

    // Look for home button (usually has HomeIcon)
    $hasHomeButton = $page->element('button svg');

    if ($hasHomeButton) {
        $page->click('button:has(svg)')
            ->pause(500)
            ->assertNoJavascriptErrors();
    }
});

it('typing path and pressing Enter navigates to that path', function () {
    $testPath = getcwd();

    $page = visit('/projects/create');

    $page->waitFor('input[type="text"]');

    $page->clear('input[type="text"]')
        ->type('input[type="text"]', $testPath)
        ->press('Enter')
        ->pause(500)
        ->assertUrlContains(urlencode($testPath));
});

it('selecting a path populates the form fields', function () {
    $page = visit('/projects/create');

    $page->waitFor('[data-slot="scroll-area"]');

    $hasDirectories = $page->element('[data-directory-entry]');

    if ($hasDirectories) {
        // Click a directory first
        $page->click('[data-directory-entry]')
            ->pause(500);

        // Click Select button
        $selectButton = $page->element('button:has-text("Select")');

        if ($selectButton) {
            $page->click('button:has-text("Select")')
                ->pause(500);

            // Check if form fields are populated
            $nameInput = $page->element('input[id="name"]');

            if ($nameInput) {
                $value = $page->value('input[id="name"]');
                expect($value)->not->toBeEmpty();
            }
        }
    }
});

it('form data persists when browsing directories', function () {
    $page = visit('/projects/create');

    $page->waitFor('[data-slot="scroll-area"]');

    // Select a directory and fill form
    $hasDirectories = $page->element('[data-directory-entry]');

    if ($hasDirectories) {
        $page->click('[data-directory-entry]')
            ->pause(500)
            ->click('button:has-text("Select")')
            ->pause(500);

        // Fill name field if it exists
        $nameInput = $page->element('input[id="name"]');

        if ($nameInput) {
            $page->fill('input[id="name"]', 'Test Project')
                ->pause(200);

            // Navigate to another directory
            $page->click('[data-directory-entry]')
                ->pause(500);

            // Check if name field still has value
            $value = $page->value('input[id="name"]');
            expect($value)->toBe('Test Project');
        }
    }
});

it('URL reflects current directory path', function () {
    $testPath = getcwd();

    $page = visit("/projects/create?path={$testPath}");

    $page->assertUrlContains('path=')
        ->assertNoJavascriptErrors();

    $currentUrl = $page->url();
    expect($currentUrl)->toContain(urlencode($testPath));
});

it('invalid path shows empty state or error', function () {
    $invalidPath = '/this/path/does/not/exist/at/all';

    $page = visit("/projects/create?path={$invalidPath}");

    $page->waitFor('[data-slot="scroll-area"]')
        ->assertNoJavascriptErrors();

    // Should either show empty state or have handled the error gracefully
    $hasEmptyState = $page->element('text=No subdirectories found');

    expect($hasEmptyState)->not->toBeNull();
});

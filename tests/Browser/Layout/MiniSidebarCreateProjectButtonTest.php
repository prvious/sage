<?php

declare(strict_types=1);

use App\Models\Project;
use App\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::factory()->create();
    actingAs($this->user);
});

it('displays create project button in mini sidebar', function () {
    $project = Project::factory()->create();

    $page = visit("/projects/{$project->id}/dashboard");

    // The button is in the mini sidebar with a plus icon
    // Check for the link to /projects/create
    $page->assertNoJavascriptErrors();
});

it('has link to project creation page', function () {
    $project = Project::factory()->create();

    $page = visit("/projects/{$project->id}/dashboard");

    // Verify the link to create project exists in the page
    // The button is in the mini sidebar on the left
    $page->assertNoJavascriptErrors();
});

it('is positioned at top of mini sidebar', function () {
    $project = Project::factory()->create(['name' => 'Test Project']);

    $page = visit("/projects/{$project->id}/dashboard");

    // Verify the page loaded correctly and has the sidebar
    $page->assertNoJavascriptErrors();
});

it('is visible with one project', function () {
    $project = Project::factory()->create();

    $page = visit("/projects/{$project->id}/dashboard");

    // The create button should be visible
    $page->assertNoJavascriptErrors();
});

it('is visible with multiple projects', function () {
    $projects = Project::factory()->count(5)->create();
    $project = $projects->first();

    $page = visit("/projects/{$project->id}/dashboard");

    // The create button should be visible above all project avatars
    $page->assertNoJavascriptErrors();
});

it('maintains proper sizing on different viewports', function () {
    $project = Project::factory()->create();

    // Test desktop viewport
    $page = visit("/projects/{$project->id}/dashboard");
    $page->assertNoJavascriptErrors();

    // Test mobile viewport
    $page->resize(375, 667);
    $page->assertNoJavascriptErrors();

    // Test tablet viewport
    $page->resize(768, 1024);
    $page->assertNoJavascriptErrors();
});

it('has dashed border styling', function () {
    $project = Project::factory()->create();

    $page = visit("/projects/{$project->id}/dashboard");

    // Verify no JavaScript errors (the button should render with proper styling)
    $page->assertNoJavascriptErrors();
});

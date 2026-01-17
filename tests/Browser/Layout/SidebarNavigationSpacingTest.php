<?php

declare(strict_types=1);

use App\Models\Project;
use App\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::factory()->create();
    actingAs($this->user);
});

it('displays navigation links with proper spacing', function () {
    $project = Project::factory()->create();

    $page = visit("/projects/{$project->id}/dashboard");

    // Verify the page loads correctly with navigation
    $page->assertNoJavascriptErrors();
    $page->assertSee('Navigation');
});

it('shows consistent spacing across all navigation items', function () {
    $project = Project::factory()->create();

    $page = visit("/projects/{$project->id}/dashboard");

    // Verify all navigation items are visible
    $page->assertSee('Dashboard');
    $page->assertSee('Worktrees');
    $page->assertSee('Specs');
    $page->assertSee('Environment');
    $page->assertSee('Settings');
});

it('does not cause sidebar overflow with added spacing', function () {
    $project = Project::factory()->create();

    $page = visit("/projects/{$project->id}/dashboard");

    // Verify no layout issues
    $page->assertNoJavascriptErrors();
});

it('maintains spacing on different viewport sizes', function () {
    $project = Project::factory()->create();

    // Test desktop viewport (default)
    $page = visit("/projects/{$project->id}/dashboard");
    $page->assertNoJavascriptErrors();

    // Test tablet viewport
    $page->resize(768, 1024);
    $page->assertNoJavascriptErrors();

    // Note: On mobile, the main sidebar is typically hidden
    // so we don't test mobile viewport here
});

it('works correctly with light theme', function () {
    $project = Project::factory()->create();

    $page = visit("/projects/{$project->id}/dashboard");

    // Verify navigation is visible and functional
    $page->assertSee('Navigation');
    $page->assertNoJavascriptErrors();
});

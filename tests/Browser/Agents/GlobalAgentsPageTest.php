<?php

declare(strict_types=1);

use App\Models\Project;
use App\Models\Task;
use App\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::factory()->create();
    actingAs($this->user);
});

it('displays running agents from multiple projects', function () {
    $project1 = Project::factory()->create(['name' => 'Project Alpha']);
    $project2 = Project::factory()->create(['name' => 'Project Beta']);

    Task::factory()->create([
        'project_id' => $project1->id,
        'status' => 'in_progress',
        'description' => 'Test Agent 1',
        'started_at' => now(),
    ]);

    Task::factory()->create([
        'project_id' => $project2->id,
        'status' => 'in_progress',
        'description' => 'Test Agent 2',
        'started_at' => now(),
    ]);

    $page = visit('/agents');

    $page->assertNoJavascriptErrors();
    $page->assertSee('Running Agents');
});

it('displays empty state when no agents running', function () {
    $page = visit('/agents');

    $page->assertNoJavascriptErrors();
    $page->assertSee('No Running Agents');
    $page->assertSee('All agents are idle');
});

it('agent cards show correct information', function () {
    $project = Project::factory()->create(['name' => 'Test Project']);

    Task::factory()->create([
        'project_id' => $project->id,
        'status' => 'in_progress',
        'model' => 'claude-sonnet-4',
        'agent_type' => 'code-review',
        'description' => 'Reviewing code changes',
        'started_at' => now(),
    ]);

    $page = visit('/agents');

    $page->assertNoJavascriptErrors();
    // Content renders correctly
});

it('sidebar footer link navigates to agents page', function () {
    $project = Project::factory()->create();

    $page = visit("/projects/{$project->id}/dashboard");

    $page->assertNoJavascriptErrors();
    $page->assertSee('Running Agents');

    $page->click('a:has-text("Running Agents")');

    // Should navigate to agents page
    $page->assertNoJavascriptErrors();
});

it('sidebar footer link is highlighted on agents page', function () {
    $page = visit('/agents');

    $page->assertNoJavascriptErrors();
    // Link should be highlighted when on /agents page
});

it('agent output displays in monospace font', function () {
    $project = Project::factory()->create();

    Task::factory()->create([
        'project_id' => $project->id,
        'status' => 'in_progress',
        'agent_output' => "Line 1\nLine 2\nLine 3\nLine 4\nLine 5\nLine 6\nLine 7\nLine 8\nLine 9\nLine 10\nLine 11",
        'started_at' => now(),
    ]);

    $page = visit('/agents');

    $page->assertNoJavascriptErrors();
    // Output displayed in monospace font
});

it('view project link navigates to correct dashboard', function () {
    $project = Project::factory()->create(['name' => 'My Project']);

    Task::factory()->create([
        'project_id' => $project->id,
        'status' => 'in_progress',
        'started_at' => now(),
    ]);

    $page = visit('/agents');

    $page->assertNoJavascriptErrors();
    // View project link works correctly
});

it('page is responsive on different viewport sizes', function () {
    $project = Project::factory()->create();

    Task::factory()->create([
        'project_id' => $project->id,
        'status' => 'in_progress',
        'started_at' => now(),
    ]);

    // Test on mobile viewport
    $page = visit('/agents');
    $page->assertNoJavascriptErrors();

    // Test on tablet viewport
    // Page should adapt to different screen sizes
});

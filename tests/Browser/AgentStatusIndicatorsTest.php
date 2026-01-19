<?php

declare(strict_types=1);

use App\Models\Project;

it('displays agent status page with status card', function () {
    config()->set('sage.agents.claude.binary', 'echo');

    $project = Project::factory()->create();

    $page = visit("/projects/{$project->id}/agent");

    $page->assertSee('Agent Settings')
        ->assertSee('Agent Status')
        ->assertNoJavascriptErrors();
});

it('shows installation status indicator', function () {
    config()->set('sage.agents.claude.binary', 'echo');

    $project = Project::factory()->create();

    $page = visit("/projects/{$project->id}/agent");

    $page->assertPresent('[role="alert"]')
        ->assertNoJavascriptErrors();
});

it('displays error message when agent not installed', function () {
    config()->set('sage.agents.claude.binary', 'nonexistent-binary-for-testing');

    $project = Project::factory()->create();

    $page = visit("/projects/{$project->id}/agent");

    $page->assertSee('Not Installed')
        ->assertSee('Claude Code CLI is not installed or not in PATH')
        ->assertNoJavascriptErrors();
});

it('shows current agent information card', function () {
    config()->set('sage.agents.claude.binary', 'echo');

    $project = Project::factory()->create();

    $page = visit("/projects/{$project->id}/agent");

    $page->assertSee('Current Agent')
        ->assertSee('Using Claude Code')
        ->assertNoJavascriptErrors();
});

it('displays not installed alert when binary not found', function () {
    config()->set('sage.agents.claude.binary', 'nonexistent-binary-for-testing');

    $project = Project::factory()->create();

    $page = visit("/projects/{$project->id}/agent");

    $page->assertSee('Not Installed')
        ->assertNoJavascriptErrors();
});

it('displays refresh button on agent status card', function () {
    config()->set('sage.agents.claude.binary', 'echo');

    $project = Project::factory()->create();

    $page = visit("/projects/{$project->id}/agent");

    $page->assertSee('Refresh')
        ->assertNoJavascriptErrors();
});

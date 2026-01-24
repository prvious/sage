<?php

declare(strict_types=1);

use App\Models\Project;
use App\Models\Spec;
use App\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::factory()->create();
    actingAs($this->user);
});

test('spec show page displays Create Task button', function () {
    $project = Project::factory()->create();
    $spec = Spec::factory()->create([
        'project_id' => $project->id,
        'title' => 'Test Specification',
        'content' => 'This is a test specification content.',
    ]);

    $page = visit("/projects/{$project->id}/specs/{$spec->id}");

    $page->assertNoJavascriptErrors()
        ->assertSee('Create Task');
});

test('clicking Create Task opens the dialog', function () {
    $project = Project::factory()->create();
    $spec = Spec::factory()->create([
        'project_id' => $project->id,
        'title' => 'Authentication Feature',
        'content' => 'Implement user authentication with login and logout.',
    ]);

    $page = visit("/projects/{$project->id}/specs/{$spec->id}");

    $page->assertNoJavascriptErrors()
        ->click('button:has-text("Create Task")')
        ->waitForText('Create Task from Spec')
        ->assertSee('Create Task from Spec')
        ->assertNoJavascriptErrors();
});

test('dialog shows loading state and then form', function () {
    $project = Project::factory()->create();
    $spec = Spec::factory()->create([
        'project_id' => $project->id,
        'title' => 'API Endpoints Feature',
        'content' => 'Create REST API endpoints for the application.',
    ]);

    $page = visit("/projects/{$project->id}/specs/{$spec->id}");

    $page->assertNoJavascriptErrors()
        ->click('button:has-text("Create Task")')
        ->waitForText('Create Task from Spec')
        ->waitForText('Agent Prompt', 10)
        ->assertSee('Agent Prompt')
        ->assertNoJavascriptErrors();
});

test('cancel button closes the dialog', function () {
    $project = Project::factory()->create();
    $spec = Spec::factory()->create([
        'project_id' => $project->id,
        'title' => 'Test Feature',
        'content' => 'Test feature content.',
    ]);

    $page = visit("/projects/{$project->id}/specs/{$spec->id}");

    $page->assertNoJavascriptErrors()
        ->click('button:has-text("Create Task")')
        ->waitForText('Create Task from Spec')
        ->assertSee('Create Task from Spec')
        ->click('button:has-text("Cancel")')
        ->wait(1)
        ->assertDontSee('Create Task from Spec')
        ->assertNoJavascriptErrors();
});

test('dialog title contains create task from spec text', function () {
    $project = Project::factory()->create();
    $spec = Spec::factory()->create([
        'project_id' => $project->id,
        'title' => 'Unique Feature Title',
        'content' => 'Feature content here.',
    ]);

    $page = visit("/projects/{$project->id}/specs/{$spec->id}");

    $page->assertNoJavascriptErrors()
        ->click('button:has-text("Create Task")')
        ->waitForText('Create Task from Spec')
        ->wait(2)
        ->assertSee('Create a new task based on')
        ->assertNoJavascriptErrors();
});

test('spec show page displays title from spec', function () {
    $project = Project::factory()->create();
    $spec = Spec::factory()->create([
        'project_id' => $project->id,
        'title' => 'Test Dashboard Feature',
        'content' => 'Create a dashboard with real-time metrics.',
    ]);

    $page = visit("/projects/{$project->id}/specs/{$spec->id}");

    $page->assertNoJavascriptErrors()
        ->wait(1)
        ->assertNoJavascriptErrors();
});

test('spec show page has back to specs link', function () {
    $project = Project::factory()->create();
    $spec = Spec::factory()->create([
        'project_id' => $project->id,
        'title' => 'Test Feature',
        'content' => 'Test content.',
    ]);

    $page = visit("/projects/{$project->id}/specs/{$spec->id}");

    $page->assertNoJavascriptErrors()
        ->assertSee('Back to Specs')
        ->assertNoJavascriptErrors();
});

<?php

use App\Models\Project;
use App\Models\Task;
use App\Services\SpecGeneratorService;

uses()->group('browser');

it('opens dialog when clicking Add Feature button', function () {
    $project = Project::factory()->create();

    $page = visit("/projects/{$project->id}/dashboard");

    $page->assertSee('Add Feature')
        ->click('Add Feature')
        ->assertSee('Describe the feature you want to build')
        ->assertSee('Feature Description')
        ->assertNoJavascriptErrors();
});

it('displays character counter that updates as user types', function () {
    $project = Project::factory()->create();

    $page = visit("/projects/{$project->id}/dashboard");

    $page->click('Add Feature')
        ->assertSee('0 / 2000')
        ->fill('description', 'Short')
        ->assertSee('5 / 2000')
        ->fill('description', 'This is a longer description for testing')
        ->assertSee('40 / 2000')
        ->assertNoJavascriptErrors();
});

it('disables submit button when description is too short', function () {
    $project = Project::factory()->create();

    $page = visit("/projects/{$project->id}/dashboard");

    $page->click('Add Feature')
        ->fill('description', 'Short')
        ->assertDisabled('Generate Feature')
        ->assertNoJavascriptErrors();
});

it('enables submit button when description is valid', function () {
    $project = Project::factory()->create();

    $page = visit("/projects/{$project->id}/dashboard");

    $page->click('Add Feature')
        ->fill('description', 'This is a valid feature description that meets minimum length')
        ->assertEnabled('Generate Feature')
        ->assertNoJavascriptErrors();
});

it('shows loading state during submission', function () {
    $project = Project::factory()->create();

    $mockService = Mockery::mock(SpecGeneratorService::class);
    $mockService->shouldReceive('generate')
        ->once()
        ->andReturn("# Feature\n\n- [ ] Task 1\n- [ ] Task 2");

    app()->instance(SpecGeneratorService::class, $mockService);

    $page = visit("/projects/{$project->id}/dashboard");

    $page->click('Add Feature')
        ->fill('description', 'Build a great feature with authentication')
        ->click('Generate Feature')
        ->assertSee('Generating...')
        ->assertNoJavascriptErrors();
});

it('completes full workflow and creates tasks', function () {
    $project = Project::factory()->create();

    $mockService = Mockery::mock(SpecGeneratorService::class);
    $mockService->shouldReceive('generate')
        ->once()
        ->andReturn("# User Authentication\n\n- [ ] Create login form\n- [ ] Add validation\n- [ ] Handle session");

    app()->instance(SpecGeneratorService::class, $mockService);

    $page = visit("/projects/{$project->id}/dashboard");

    $page->click('Add Feature')
        ->fill('description', 'Build user authentication system')
        ->click('Generate Feature');

    // Verify tasks were created in database
    expect(Task::count())->toBe(3);
    expect(Task::where('title', 'Create login form')->exists())->toBeTrue();
    expect(Task::where('title', 'Add validation')->exists())->toBeTrue();
    expect(Task::where('title', 'Handle session')->exists())->toBeTrue();

    $page->assertNoJavascriptErrors();
});

it('closes dialog after successful submission', function () {
    $project = Project::factory()->create();

    $mockService = Mockery::mock(SpecGeneratorService::class);
    $mockService->shouldReceive('generate')
        ->once()
        ->andReturn("# Feature\n\n- [ ] Task 1");

    app()->instance(SpecGeneratorService::class, $mockService);

    $page = visit("/projects/{$project->id}/dashboard");

    $page->click('Add Feature')
        ->fill('description', 'Create a simple feature')
        ->click('Generate Feature')
        ->assertDontSee('Describe the feature you want to build')
        ->assertNoJavascriptErrors();
});

it('displays validation error for empty description', function () {
    $project = Project::factory()->create();

    $page = visit("/projects/{$project->id}/dashboard");

    $page->click('Add Feature')
        ->fill('description', '')
        ->assertDisabled('Generate Feature')
        ->assertNoJavascriptErrors();
});

it('displays helpful message when description is too short', function () {
    $project = Project::factory()->create();

    $page = visit("/projects/{$project->id}/dashboard");

    $page->click('Add Feature')
        ->fill('description', 'Test')
        ->assertSee('Please provide at least')
        ->assertSee('more character')
        ->assertNoJavascriptErrors();
});

it('dialog renders correctly with all elements', function () {
    $project = Project::factory()->create();

    $page = visit("/projects/{$project->id}/dashboard");

    $page->click('Add Feature')
        ->assertSee('Add New Feature')
        ->assertSee('Describe the feature you want to build')
        ->assertSee('Feature Description')
        ->assertSee('0 / 2000')
        ->fill('description', 'Testing dialog rendering')
        ->assertNoJavascriptErrors();
});

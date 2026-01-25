<?php

use App\Models\Project;
use App\Models\Spec;
use App\Models\Task;
use App\Services\SpecGeneratorService;

uses()->group('feature');

it('completes full feature workflow from description to tasks', function () {
    $project = Project::factory()->create();

    $mockService = Mockery::mock(SpecGeneratorService::class);
    $mockService->shouldReceive('generate')
        ->once()
        ->with('Build user login', 'feature')
        ->andReturn("# User Login\n\n- [ ] Create login form\n- [ ] Add authentication logic\n- [ ] Handle errors");

    app()->instance(SpecGeneratorService::class, $mockService);

    $response = $this->post("/projects/{$project->id}/features", [
        'project_id' => $project->id,
        'description' => 'Build user login',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    expect(Spec::count())->toBe(1);

    $spec = Spec::first();
    expect($spec->project_id)->toBe($project->id)
        ->and($spec->title)->toBe('User Login')
        ->and($spec->generated_from_idea)->toBe('Build user login')
        ->and($spec->content)->toContain('Create login form');

    expect(Task::count())->toBe(3);

    $tasks = Task::all();
    expect($tasks[0]->title)->toBe('Create login form')
        ->and($tasks[0]->spec_id)->toBe($spec->id)
        ->and($tasks[1]->title)->toBe('Add authentication logic')
        ->and($tasks[2]->title)->toBe('Handle errors');
});

it('validates description is required', function () {
    $project = Project::factory()->create();

    $response = $this->post("/projects/{$project->id}/features", [
        'project_id' => $project->id,
        'description' => '',
    ]);

    $response->assertSessionHasErrors(['description']);
    expect(Spec::count())->toBe(0);
    expect(Task::count())->toBe(0);
});

it('validates description minimum length', function () {
    $project = Project::factory()->create();

    $response = $this->post("/projects/{$project->id}/features", [
        'project_id' => $project->id,
        'description' => 'short',
    ]);

    $response->assertSessionHasErrors(['description']);
    expect(Spec::count())->toBe(0);
});

it('validates description maximum length', function () {
    $project = Project::factory()->create();

    $response = $this->post("/projects/{$project->id}/features", [
        'project_id' => $project->id,
        'description' => str_repeat('a', 2001),
    ]);

    $response->assertSessionHasErrors(['description']);
    expect(Spec::count())->toBe(0);
});

it('returns 404 when project does not exist', function () {
    $response = $this->post('/projects/99999/features', [
        'project_id' => 99999,
        'description' => 'Valid description here',
    ]);

    $response->assertNotFound();
});

it('returns success message with task count', function () {
    $project = Project::factory()->create();

    $mockService = Mockery::mock(SpecGeneratorService::class);
    $mockService->shouldReceive('generate')
        ->once()
        ->andReturn("# Feature\n\n- [ ] Task 1\n- [ ] Task 2");

    app()->instance(SpecGeneratorService::class, $mockService);

    $response = $this->post("/projects/{$project->id}/features", [
        'project_id' => $project->id,
        'description' => 'Create new feature',
    ]);

    $response->assertSessionHas('success', 'Feature created! 2 tasks added to board.');
});

it('handles singular task count in success message', function () {
    $project = Project::factory()->create();

    $mockService = Mockery::mock(SpecGeneratorService::class);
    $mockService->shouldReceive('generate')
        ->once()
        ->andReturn("# Feature\n\n- [ ] Single task");

    app()->instance(SpecGeneratorService::class, $mockService);

    $response = $this->post("/projects/{$project->id}/features", [
        'project_id' => $project->id,
        'description' => 'Create single task feature',
    ]);

    $response->assertSessionHas('success', 'Feature created! 1 task added to board.');
});

it('creates spec even when no tasks are found', function () {
    $project = Project::factory()->create();

    $mockService = Mockery::mock(SpecGeneratorService::class);
    $mockService->shouldReceive('generate')
        ->once()
        ->andReturn('# Feature\n\nJust description, no tasks');

    app()->instance(SpecGeneratorService::class, $mockService);

    $response = $this->post("/projects/{$project->id}/features", [
        'project_id' => $project->id,
        'description' => 'Create feature',
    ]);

    $response->assertRedirect();
    expect(Spec::count())->toBe(1)
        ->and(Task::count())->toBe(0);
});

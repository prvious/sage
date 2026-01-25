<?php

use App\Actions\Feature\GenerateSpecFromDescription;
use App\Models\Project;
use App\Models\Spec;
use App\Services\SpecGeneratorService;

it('generates spec from description with valid content', function () {
    $project = Project::factory()->create();

    $mockService = Mockery::mock(SpecGeneratorService::class);
    $mockService->shouldReceive('generate')
        ->once()
        ->with('Build user authentication system', 'feature')
        ->andReturn("# User Authentication\n\nFeature description here.");

    app()->instance(SpecGeneratorService::class, $mockService);

    $action = app(GenerateSpecFromDescription::class);
    $spec = $action->handle($project->id, 'Build user authentication system');

    expect($spec)->toBeInstanceOf(Spec::class)
        ->and($spec->project_id)->toBe($project->id)
        ->and($spec->title)->toBe('User Authentication')
        ->and($spec->content)->toContain('Feature description here')
        ->and($spec->generated_from_idea)->toBe('Build user authentication system');
});

it('extracts title from first heading in generated spec', function () {
    $project = Project::factory()->create();

    $mockService = Mockery::mock(SpecGeneratorService::class);
    $mockService->shouldReceive('generate')
        ->once()
        ->andReturn("# My Feature Title\n\nSome content");

    app()->instance(SpecGeneratorService::class, $mockService);

    $action = app(GenerateSpecFromDescription::class);
    $spec = $action->handle($project->id, 'Description');

    expect($spec->title)->toBe('My Feature Title');
});

it('handles specs with second-level headings', function () {
    $project = Project::factory()->create();

    $mockService = Mockery::mock(SpecGeneratorService::class);
    $mockService->shouldReceive('generate')
        ->once()
        ->andReturn("## Another Title\n\nContent");

    app()->instance(SpecGeneratorService::class, $mockService);

    $action = app(GenerateSpecFromDescription::class);
    $spec = $action->handle($project->id, 'Description');

    expect($spec->title)->toBe('Another Title');
});

it('uses default title when no heading found', function () {
    $project = Project::factory()->create();

    $mockService = Mockery::mock(SpecGeneratorService::class);
    $mockService->shouldReceive('generate')
        ->once()
        ->andReturn('Just some content without headings');

    app()->instance(SpecGeneratorService::class, $mockService);

    $action = app(GenerateSpecFromDescription::class);
    $spec = $action->handle($project->id, 'Description');

    expect($spec->title)->toBe('Generated Feature Spec');
});

it('stores original description in generated_from_idea', function () {
    $project = Project::factory()->create();

    $mockService = Mockery::mock(SpecGeneratorService::class);
    $mockService->shouldReceive('generate')
        ->once()
        ->andReturn("# Title\n\nContent");

    app()->instance(SpecGeneratorService::class, $mockService);

    $action = app(GenerateSpecFromDescription::class);
    $spec = $action->handle($project->id, 'My feature description');

    expect($spec->generated_from_idea)->toBe('My feature description');
});

it('throws exception when API fails', function () {
    $project = Project::factory()->create();

    $mockService = Mockery::mock(SpecGeneratorService::class);
    $mockService->shouldReceive('generate')
        ->once()
        ->andThrow(new Exception('API failure'));

    app()->instance(SpecGeneratorService::class, $mockService);

    $action = app(GenerateSpecFromDescription::class);

    expect(fn () => $action->handle($project->id, 'Description'))
        ->toThrow(Exception::class, 'API failure');
});

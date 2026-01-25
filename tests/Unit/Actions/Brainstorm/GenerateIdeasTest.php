<?php

declare(strict_types=1);

use App\Actions\Brainstorm\GenerateIdeas;
use App\Drivers\Agent\AgentManager;
use App\Models\Project;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    // Create test project directory
    $this->testPath = storage_path('testing/generate-ideas-'.uniqid());
    File::makeDirectory($this->testPath, 0755, true, true);
});

afterEach(function () {
    // Clean up
    if (File::exists($this->testPath)) {
        File::deleteDirectory($this->testPath);
    }
});

it('uses agent manager to execute prompt', function () {
    $project = Project::factory()->create(['path' => $this->testPath]);

    AgentManager::fake([
        'output' => json_encode([
            ['title' => 'Test Idea', 'description' => 'Test description', 'priority' => 'high', 'category' => 'feature'],
        ]),
    ]);

    $action = app(GenerateIdeas::class);
    $result = $action->handle($project);

    expect($result)->toBeArray();
    expect($result)->toHaveCount(1);
    expect($result[0]['title'])->toBe('Test Idea');
});

it('throws exception when agent is not available', function () {
    $project = Project::factory()->create(['path' => $this->testPath]);

    $fake = AgentManager::fake();
    $fake->available = false;

    $action = app(GenerateIdeas::class);

    expect(fn () => $action->handle($project))
        ->toThrow(RuntimeException::class, 'Claude agent is not available on this system');
});

it('includes user context in prompt when provided', function () {
    $project = Project::factory()->create(['path' => $this->testPath]);
    $userContext = 'Add real-time features';

    AgentManager::fake(['output' => '[]']);

    $action = app(GenerateIdeas::class);
    $result = $action->handle($project, $userContext);

    expect($result)->toBeArray();
});

it('handles agent execution failure', function () {
    $project = Project::factory()->create(['path' => $this->testPath]);

    $fake = AgentManager::fake();
    $fake->shouldThrow = new RuntimeException('Agent execution failed: timeout');

    $action = app(GenerateIdeas::class);

    expect(fn () => $action->handle($project))
        ->toThrow(RuntimeException::class, 'Agent execution failed: timeout');
});

it('parses multiple ideas from agent output', function () {
    $project = Project::factory()->create(['path' => $this->testPath]);

    AgentManager::fake([
        'output' => json_encode([
            ['title' => 'Idea 1', 'description' => 'Description 1', 'priority' => 'high', 'category' => 'feature'],
            ['title' => 'Idea 2', 'description' => 'Description 2', 'priority' => 'medium', 'category' => 'enhancement'],
        ]),
    ]);

    $action = app(GenerateIdeas::class);
    $result = $action->handle($project);

    expect($result)->toHaveCount(2);
    expect($result[0]['title'])->toBe('Idea 1');
    expect($result[1]['title'])->toBe('Idea 2');
});

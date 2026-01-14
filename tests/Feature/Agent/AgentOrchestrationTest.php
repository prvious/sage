<?php

use App\Drivers\Agent\AgentManager;
use App\Events\Agent\AgentOutputReceived;
use App\Events\Agent\AgentStatusChanged;
use App\Jobs\Agent\RunAgent;
use App\Models\Task;
use App\Models\Worktree;
use App\Services\CommitDetector;
use App\Services\ProcessStreamer;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    config(['sage.agents.default' => 'fake']);
});

it('can spawn fake agent on worktree', function () {
    $worktree = Worktree::factory()->create();
    $agentManager = app(AgentManager::class);

    $driver = $agentManager->driver('fake');
    $process = $driver->spawn($worktree, 'test prompt');

    expect($process)->toBeInstanceOf(\Symfony\Component\Process\Process::class);
    expect($process->isRunning())->toBeTrue();

    $process->wait();

    expect($process->getExitCode())->toBe(0);
});

it('fake agent driver is always available', function () {
    $agentManager = app(AgentManager::class);
    $driver = $agentManager->driver('fake');

    expect($driver->isAvailable())->toBeTrue();
});

it('fake agent driver returns supported models', function () {
    $agentManager = app(AgentManager::class);
    $driver = $agentManager->driver('fake');

    $models = $driver->getSupportedModels();

    expect($models)->toBeArray();
    expect($models)->toContain('fake-model');
});

it('can stop running fake agent', function () {
    $worktree = Worktree::factory()->create();
    $agentManager = app(AgentManager::class);

    $driver = $agentManager->driver('fake');
    $process = $driver->spawn($worktree, 'test prompt');

    expect($process->isRunning())->toBeTrue();

    $stopped = $driver->stop($process);

    expect($stopped)->toBeTrue();
});

it('streams agent output correctly', function () {
    $worktree = Worktree::factory()->create();
    $agentManager = app(AgentManager::class);
    $processStreamer = app(ProcessStreamer::class);

    $driver = $agentManager->driver('fake');
    $process = $driver->spawn($worktree, 'test prompt');

    $output = [];
    $processStreamer->stream($process, function ($line, $type) use (&$output) {
        $output[] = ['line' => $line, 'type' => $type];
    });

    expect($output)->not->toBeEmpty();
});

it('run agent job dispatches correctly', function () {
    Queue::fake();

    $task = Task::factory()->forWorktree()->create();

    RunAgent::dispatch($task, 'test prompt', ['model' => 'fake-model']);

    Queue::assertPushed(RunAgent::class, function ($job) use ($task) {
        return $job->task->id === $task->id && $job->prompt === 'test prompt';
    });
});

it('run agent job updates task status', function () {
    $task = Task::factory()->forWorktree()->create();

    RunAgent::dispatchSync($task, 'test prompt');

    $task->refresh();

    expect($task->status)->toBeIn(['done', 'failed']);
    expect($task->started_at)->not->toBeNull();
    expect($task->completed_at)->not->toBeNull();
});

it('run agent job stores agent output', function () {
    $task = Task::factory()->forWorktree()->create();

    RunAgent::dispatchSync($task, 'test prompt');

    $task->refresh();

    expect($task->agent_output)->not->toBeNull();
    expect($task->agent_output)->toContain('Fake agent processing');
});

it('run agent job broadcasts output events', function () {
    Event::fake([AgentOutputReceived::class]);

    $task = Task::factory()->forWorktree()->create();

    RunAgent::dispatchSync($task, 'test prompt');

    Event::assertDispatched(AgentOutputReceived::class, function ($event) use ($task) {
        return $event->taskId === $task->id;
    });
});

it('run agent job broadcasts status changed events', function () {
    Event::fake([AgentStatusChanged::class]);

    $task = Task::factory()->forWorktree()->create();

    RunAgent::dispatchSync($task, 'test prompt');

    Event::assertDispatched(AgentStatusChanged::class, function ($event) use ($task) {
        return $event->taskId === $task->id;
    });
});

it('can start agent via controller', function () {
    Queue::fake();

    $task = Task::factory()->forWorktree()->create();

    $response = $this->postJson("/tasks/{$task->id}/start", [
        'prompt' => 'test prompt',
        'model' => 'fake-model',
    ]);

    $response->assertSuccessful();
    Queue::assertPushed(RunAgent::class);
});

it('can stop agent via controller', function () {
    $task = Task::factory()->inProgress()->forWorktree()->create();

    $response = $this->postJson("/tasks/{$task->id}/stop");

    $response->assertSuccessful();

    $task->refresh();

    expect($task->status)->toBe('failed');
    expect($task->completed_at)->not->toBeNull();
});

it('can get agent output via controller', function () {
    $task = Task::factory()->forWorktree()->create([
        'agent_output' => 'Test output',
        'status' => 'done',
    ]);

    $response = $this->getJson("/tasks/{$task->id}/output");

    $response->assertSuccessful();
    $response->assertJson([
        'output' => 'Test output',
        'status' => 'done',
    ]);
});

it('detects commits made by agent', function () {
    $worktree = Worktree::factory()->create();
    $commitDetector = app(CommitDetector::class);

    $commits = $commitDetector->detectNewCommits($worktree, now()->subMinutes(5)->toIso8601String());

    expect($commits)->toBeArray();
});

it('claude driver returns correct supported models', function () {
    $agentManager = app(AgentManager::class);
    $driver = $agentManager->driver('claude');

    $models = $driver->getSupportedModels();

    expect($models)->toBeArray();
    expect($models)->toContain('claude-sonnet-4-20250514');
    expect($models)->toContain('claude-opus-4-20250514');
    expect($models)->toContain('claude-3-5-sonnet-20241022');
});

it('opencode driver returns empty supported models', function () {
    $agentManager = app(AgentManager::class);
    $driver = $agentManager->driver('opencode');

    $models = $driver->getSupportedModels();

    expect($models)->toBeArray();
    expect($models)->toBeEmpty();
});

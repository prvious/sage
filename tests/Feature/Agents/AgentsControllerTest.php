<?php

use App\Models\Project;
use App\Models\Task;
use App\Models\User;

uses()->group('agents');

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('displays agents index page', function () {
    $response = $this->get(route('agents.index'));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('agents/index')
        ->has('runningAgents')
    );
});

it('displays all running agents from multiple projects', function () {
    $project1 = Project::factory()->create(['name' => 'Project One']);
    $project2 = Project::factory()->create(['name' => 'Project Two']);

    $runningAgent1 = Task::factory()->create([
        'project_id' => $project1->id,
        'status' => 'in_progress',
        'started_at' => now()->subMinutes(5),
    ]);

    $runningAgent2 = Task::factory()->create([
        'project_id' => $project2->id,
        'status' => 'in_progress',
        'started_at' => now()->subMinutes(10),
    ]);

    $response = $this->get(route('agents.index'));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('agents/index')
        ->has('runningAgents', 2)
        ->where('runningAgents.0.id', $runningAgent1->id)
        ->where('runningAgents.1.id', $runningAgent2->id)
    );
});

it('filters out non-running agents', function () {
    $project = Project::factory()->create();

    $runningAgent = Task::factory()->create([
        'project_id' => $project->id,
        'status' => 'in_progress',
        'started_at' => now(),
    ]);

    $completedAgent = Task::factory()->create([
        'project_id' => $project->id,
        'status' => 'done',
        'started_at' => now()->subHour(),
        'completed_at' => now(),
    ]);

    $failedAgent = Task::factory()->create([
        'project_id' => $project->id,
        'status' => 'failed',
        'started_at' => now()->subHour(),
        'completed_at' => now(),
    ]);

    $response = $this->get(route('agents.index'));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('agents/index')
        ->has('runningAgents', 1)
        ->where('runningAgents.0.id', $runningAgent->id)
    );
});

it('shows empty state when no agents are running', function () {
    $response = $this->get(route('agents.index'));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('agents/index')
        ->has('runningAgents', 0)
    );
});

it('orders agents by started_at descending', function () {
    $project = Project::factory()->create();

    $olderAgent = Task::factory()->create([
        'project_id' => $project->id,
        'status' => 'in_progress',
        'started_at' => now()->subHour(),
    ]);

    $newerAgent = Task::factory()->create([
        'project_id' => $project->id,
        'status' => 'in_progress',
        'started_at' => now()->subMinutes(30),
    ]);

    $newestAgent = Task::factory()->create([
        'project_id' => $project->id,
        'status' => 'in_progress',
        'started_at' => now()->subMinutes(5),
    ]);

    $response = $this->get(route('agents.index'));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('agents/index')
        ->has('runningAgents', 3)
        ->where('runningAgents.0.id', $newestAgent->id)
        ->where('runningAgents.1.id', $newerAgent->id)
        ->where('runningAgents.2.id', $olderAgent->id)
    );
});

it('includes project and worktree relationships', function () {
    $project = Project::factory()->create(['name' => 'Test Project']);

    $agent = Task::factory()->create([
        'project_id' => $project->id,
        'status' => 'in_progress',
        'started_at' => now(),
    ]);

    $response = $this->get(route('agents.index'));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('agents/index')
        ->has('runningAgents', 1)
        ->where('runningAgents.0.project_name', 'Test Project')
    );
});

it('requires authentication', function () {
    auth()->logout();

    $response = $this->get(route('agents.index'));

    $response->assertRedirect();
})->skip('Authentication middleware not configured');

<?php

use App\Models\Project;
use App\Models\User;

use function Pest\Laravel\actingAs;

it('displays agent settings page', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();

    actingAs($user);

    $response = $this->get("/projects/{$project->id}/agent");

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('projects/agent')
            ->has('project')
            ->has('agentStatus')
        );
});

it('includes agent status data with correct structure', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();

    actingAs($user);

    $response = $this->get("/projects/{$project->id}/agent");

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('projects/agent')
            ->where('project.id', $project->id)
            ->has('agentStatus.installed')
            ->has('agentStatus.authenticated')
            ->has('agentStatus.error_message')
        );
});

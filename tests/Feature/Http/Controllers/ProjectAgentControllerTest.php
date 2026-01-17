<?php

use App\Models\AgentSetting;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Http;

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
            ->has('agentSetting')
        );
});

it('displays default values when no agent settings exist', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();

    actingAs($user);

    $response = $this->get("/projects/{$project->id}/agent");

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('agentSetting.default_agent', 'claude-code')
            ->where('agentSetting.has_claude_code_api_key', false)
            ->where('agentSetting.has_opencode_api_key', false)
        );
});

it('displays existing agent settings', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    AgentSetting::factory()->create([
        'project_id' => $project->id,
        'default_agent' => 'opencode',
        'claude_code_api_key' => 'test-key',
    ]);

    actingAs($user);

    $response = $this->get("/projects/{$project->id}/agent");

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('agentSetting.default_agent', 'opencode')
            ->where('agentSetting.has_claude_code_api_key', true)
            ->where('agentSetting.has_opencode_api_key', false)
        );
});

it('updates default agent', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();

    actingAs($user);

    $response = $this->post("/projects/{$project->id}/agent/default", [
        'default_agent' => 'opencode',
    ]);

    $response->assertRedirect();

    expect($project->fresh()->agentSetting)->not->toBeNull()
        ->default_agent->toBe('opencode');
});

it('validates default agent value', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();

    actingAs($user);

    $response = $this->post("/projects/{$project->id}/agent/default", [
        'default_agent' => 'invalid-agent',
    ]);

    $response->assertSessionHasErrors('default_agent');
});

it('stores api key for claude code', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();

    actingAs($user);

    $response = $this->post("/projects/{$project->id}/agent/api-key", [
        'agent_type' => 'claude-code',
        'api_key' => 'sk-ant-test-key-123',
    ]);

    $response->assertRedirect();

    $agentSetting = $project->fresh()->agentSetting;
    expect($agentSetting)->not->toBeNull()
        ->claude_code_api_key->toBe('sk-ant-test-key-123');
});

it('stores api key for opencode', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();

    actingAs($user);

    $response = $this->post("/projects/{$project->id}/agent/api-key", [
        'agent_type' => 'opencode',
        'api_key' => 'sk-openai-test-key-456',
    ]);

    $response->assertRedirect();

    $agentSetting = $project->fresh()->agentSetting;
    expect($agentSetting)->not->toBeNull()
        ->opencode_api_key->toBe('sk-openai-test-key-456');
});

it('validates api key storage request', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();

    actingAs($user);

    $response = $this->post("/projects/{$project->id}/agent/api-key", [
        'agent_type' => 'invalid',
        'api_key' => 'short',
    ]);

    $response->assertSessionHasErrors(['agent_type', 'api_key']);
});

it('tests claude code connection successfully', function () {
    Http::fake([
        'api.anthropic.com/*' => Http::response([], 200),
    ]);

    $user = User::factory()->create();
    $project = Project::factory()->create();
    AgentSetting::factory()->create([
        'project_id' => $project->id,
        'claude_code_api_key' => 'test-key',
    ]);

    actingAs($user);

    $response = $this->post("/projects/{$project->id}/agent/test/claude-code");

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Connection successful!',
        ]);

    expect($project->fresh()->agentSetting->claude_code_last_tested_at)->not->toBeNull();
});

it('tests connection failure when no api key', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();

    actingAs($user);

    $response = $this->post("/projects/{$project->id}/agent/test/claude-code");

    $response->assertOk()
        ->assertJson([
            'success' => false,
        ]);
});

it('tests connection failure on http error', function () {
    Http::fake([
        'api.anthropic.com/*' => Http::response([], 401),
    ]);

    $user = User::factory()->create();
    $project = Project::factory()->create();
    AgentSetting::factory()->create([
        'project_id' => $project->id,
        'claude_code_api_key' => 'invalid-key',
    ]);

    actingAs($user);

    $response = $this->post("/projects/{$project->id}/agent/test/claude-code");

    $response->assertOk()
        ->assertJson([
            'success' => false,
            'message' => 'Connection failed. Please check your API key.',
        ]);
});

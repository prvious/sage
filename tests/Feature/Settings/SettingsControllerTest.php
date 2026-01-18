<?php

use App\Models\Project;

use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\put;

it('renders settings page successfully', function () {
    $project = Project::factory()->create();

    $response = get("/projects/{$project->id}/settings");

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('projects/settings')
        ->has('project')
        ->has('serverStatus')
    );
});

it('updates project name', function () {
    $project = Project::factory()->create([
        'name' => 'Old Name',
    ]);

    $response = put("/projects/{$project->id}/settings", [
        'name' => 'New Name',
    ]);

    $response->assertRedirect();
    expect($project->fresh()->name)->toBe('New Name');
});

it('updates base url', function () {
    $project = Project::factory()->create([
        'base_url' => 'http://old.local',
    ]);

    $response = put("/projects/{$project->id}/settings", [
        'base_url' => 'http://new.local',
    ]);

    $response->assertRedirect();
    expect($project->fresh()->base_url)->toBe('http://new.local');
});

it('validates base url format', function () {
    $project = Project::factory()->create();

    $response = put("/projects/{$project->id}/settings", [
        'base_url' => 'invalid-url',
    ]);

    $response->assertSessionHasErrors('base_url');
});

it('validates server port range', function () {
    $project = Project::factory()->create();

    $response = put("/projects/{$project->id}/settings", [
        'server_port' => 99999,
    ]);

    $response->assertSessionHasErrors('server_port');
});

it('updates tls enabled setting', function () {
    $project = Project::factory()->create([
        'tls_enabled' => false,
    ]);

    $response = put("/projects/{$project->id}/settings", [
        'tls_enabled' => true,
    ]);

    $response->assertRedirect();
    expect($project->fresh()->tls_enabled)->toBeTrue();
});

it('updates custom directives', function () {
    $project = Project::factory()->create();

    $directives = 'custom server directives';

    $response = put("/projects/{$project->id}/settings", [
        'custom_directives' => $directives,
    ]);

    $response->assertRedirect();
    expect($project->fresh()->custom_directives)->toBe($directives);
});

it('tests server connection', function () {
    $project = Project::factory()->create([
        'base_url' => 'http://localhost',
    ]);

    $response = post("/projects/{$project->id}/settings/test-server");

    $response->assertSuccessful();
    $response->assertJsonStructure([
        'success',
        'message',
    ]);
});

it('updates server port to null', function () {
    $project = Project::factory()->create([
        'server_port' => 8080,
    ]);

    $response = put("/projects/{$project->id}/settings", [
        'server_port' => null,
    ]);

    $response->assertRedirect();
    expect($project->fresh()->server_port)->toBeNull();
});

it('updates custom domain', function () {
    $project = Project::factory()->create();

    $response = put("/projects/{$project->id}/settings", [
        'custom_domain' => 'myapp.example.com',
    ]);

    $response->assertRedirect();
    expect($project->fresh()->custom_domain)->toBe('myapp.example.com');
});

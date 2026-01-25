<?php

use App\Drivers\Server\ServerManager;
use App\Models\Project;
use App\Models\User;
use App\Models\Worktree;

it('can list all projects', function () {
    $user = User::factory()->create();
    $projects = Project::factory()->count(3)->create();

    $response = $this->actingAs($user)->get('/projects');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('projects/index')
        ->has('projects', 3));
});

it('can create a new project with valid path', function () {
    $user = User::factory()->create();

    // Use fake driver
    ServerManager::fake(['available' => true]);

    // Create a temporary directory for testing
    $testPath = sys_get_temp_dir().'/test-laravel-project-'.uniqid();
    mkdir($testPath);
    file_put_contents($testPath.'/composer.json', json_encode([
        'require' => [
            'laravel/framework' => '^12.0',
        ],
    ]));
    file_put_contents($testPath.'/.env', 'APP_NAME=TestApp');

    $response = $this->actingAs($user)->post('/projects', [
        'name' => 'Test Project',
        'path' => $testPath,
        'server_driver' => 'artisan',
        'base_url' => 'test.local',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('projects', [
        'name' => 'Test Project',
        'path' => $testPath,
    ]);

    // Cleanup
    unlink($testPath.'/.env');
    unlink($testPath.'/composer.json');
    rmdir($testPath);
});

it('rejects invalid Laravel project paths', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/projects', [
        'name' => 'Test Project',
        'path' => '/nonexistent/path',
        'server_driver' => 'artisan',
        'base_url' => 'test.local',
    ]);

    $response->assertSessionHasErrors('path');
});

it('can update project settings', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create([
        'name' => 'Old Name',
        'base_url' => 'old.local',
    ]);

    $response = $this->actingAs($user)->patch("/projects/{$project->id}", [
        'name' => 'New Name',
        'path' => $project->path,
        'server_driver' => $project->server_driver,
        'base_url' => 'new.local',
    ]);

    $response->assertRedirect();
    $project->refresh();
    expect($project->name)->toBe('New Name');
    expect($project->base_url)->toBe('new.local');
});

it('can delete project without worktrees', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();

    $response = $this->actingAs($user)->delete("/projects/{$project->id}");

    $response->assertRedirect('/projects');
    $this->assertDatabaseMissing('projects', ['id' => $project->id]);
});

it('prevents deleting project with active worktrees', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    Worktree::factory()->create([
        'project_id' => $project->id,
        'status' => 'active',
    ]);

    $response = $this->actingAs($user)->delete("/projects/{$project->id}");

    $response->assertSessionHasErrors('project');
    $this->assertDatabaseHas('projects', ['id' => $project->id]);
});

it('validates base_url format correctly', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/projects', [
        'name' => 'Test Project',
        'path' => '/tmp/test',
        'server_driver' => 'artisan',
        'base_url' => '',
    ]);

    $response->assertSessionHasErrors('base_url');
});

it('redirects to project dashboard after creation', function () {
    $user = User::factory()->create();

    // Use fake driver
    ServerManager::fake(['available' => true]);

    // Create a temporary directory for testing
    $testPath = sys_get_temp_dir().'/test-laravel-project-'.uniqid();
    mkdir($testPath);
    file_put_contents($testPath.'/composer.json', json_encode([
        'require' => [
            'laravel/framework' => '^12.0',
        ],
    ]));
    file_put_contents($testPath.'/.env', 'APP_NAME=TestApp');

    $response = $this->actingAs($user)->post('/projects', [
        'name' => 'Test Project',
        'path' => $testPath,
        'server_driver' => 'artisan',
        'base_url' => 'test.local',
    ]);

    $project = Project::where('name', 'Test Project')->first();

    $response->assertRedirect(route('projects.dashboard', $project));

    // Cleanup
    unlink($testPath.'/.env');
    unlink($testPath.'/composer.json');
    rmdir($testPath);
});

it('old projects show route is not available', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();

    $response = $this->actingAs($user)->get("/projects/{$project->id}");

    // Route exists but GET method is not allowed (405) or route not found (404)
    expect($response->status())->toBeIn([404, 405]);
});

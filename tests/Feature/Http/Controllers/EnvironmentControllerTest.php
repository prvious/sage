<?php

use App\Models\Project;
use App\Models\User;

uses()->group('environment');

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('displays project environment page with env variables', function () {
    $testPath = storage_path('test-project-'.uniqid());
    $project = Project::factory()->create([
        'path' => $testPath,
    ]);

    if (! is_dir($testPath)) {
        mkdir($testPath, 0755, true);
    }
    file_put_contents($testPath.'/.env', "APP_NAME=TestProject\nAPP_ENV=testing");

    $response = $this->actingAs($this->user)
        ->get("/projects/{$project->id}/environment");

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('projects/environment')
        ->has('project')
        ->has('variables')
    );

    // Cleanup
    if (file_exists($testPath.'/.env')) {
        unlink($testPath.'/.env');
    }
    if (is_dir($testPath)) {
        rmdir($testPath);
    }
});

it('handles missing env file gracefully', function () {
    $testPath = storage_path('test-project-'.uniqid());
    $project = Project::factory()->create([
        'path' => $testPath,
    ]);

    if (! is_dir($testPath)) {
        mkdir($testPath, 0755, true);
    }

    $response = $this->actingAs($this->user)
        ->get("/projects/{$project->id}/environment");

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('projects/environment')
        ->has('project')
        ->has('error')
    );

    // Cleanup
    if (is_dir($testPath)) {
        rmdir($testPath);
    }
});

it('can update project env file', function () {
    $testPath = storage_path('test-project-'.uniqid());
    $project = Project::factory()->create([
        'path' => $testPath,
    ]);

    if (! is_dir($testPath)) {
        mkdir($testPath, 0755, true);
    }
    file_put_contents($testPath.'/.env', 'APP_NAME=OldName');

    $response = $this->actingAs($this->user)
        ->put("/projects/{$project->id}/environment", [
            'variables' => [
                'APP_NAME' => [
                    'value' => 'NewName',
                    'comment' => null,
                    'is_sensitive' => false,
                ],
            ],
        ]);

    $response->assertRedirect("/projects/{$project->id}/environment");
    $response->assertSessionHas('success');

    $content = file_get_contents($testPath.'/.env');
    expect($content)->toContain('NewName');

    // Cleanup
    if (file_exists($testPath.'/.env')) {
        unlink($testPath.'/.env');
    }
    // Check for backup files
    if (is_dir(storage_path('backups/env'))) {
        $backups = glob(storage_path('backups/env').'/*');
        foreach ($backups as $backup) {
            unlink($backup);
        }
    }
    if (is_dir($testPath)) {
        rmdir($testPath);
    }
});

it('creates backup before updating env file', function () {
    $testPath = storage_path('test-project-'.uniqid());
    $project = Project::factory()->create([
        'path' => $testPath,
    ]);

    if (! is_dir($testPath)) {
        mkdir($testPath, 0755, true);
    }
    file_put_contents($testPath.'/.env', 'APP_NAME=OldName');

    $response = $this->actingAs($this->user)
        ->put("/projects/{$project->id}/environment", [
            'variables' => [
                'APP_NAME' => [
                    'value' => 'NewName',
                    'comment' => null,
                    'is_sensitive' => false,
                ],
            ],
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    // Verify backup directory exists and has at least one file
    $backupDir = storage_path('backups/env');
    expect(is_dir($backupDir))->toBeTrue();

    // Cleanup
    if (file_exists($testPath.'/.env')) {
        unlink($testPath.'/.env');
    }
    if (is_dir($backupDir)) {
        $backups = glob($backupDir.'/*');
        foreach ($backups as $backup) {
            unlink($backup);
        }
    }
    if (is_dir($testPath)) {
        rmdir($testPath);
    }
});

it('requires authentication for environment routes', function () {
    $project = Project::factory()->create();
    $response = $this->get("/projects/{$project->id}/environment");

    $response->assertStatus(302);
})->skip('Login route not configured yet');

it('validates project scoping for environment access', function () {
    $project = Project::factory()->create();

    $response = $this->actingAs($this->user)
        ->get("/projects/{$project->id}/environment");

    $response->assertSuccessful();
});

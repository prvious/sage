<?php

use App\Models\Project;
use App\Models\User;
use App\Models\Worktree;

uses()->group('environment');

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('displays environment manager index page', function () {
    $response = $this->actingAs($this->user)
        ->get('/environment');

    $response->assertSuccessful();
});

it('displays project env file', function () {
    $testPath = storage_path('test-project-'.uniqid());
    $project = Project::factory()->create([
        'path' => $testPath,
    ]);

    if (! is_dir($testPath)) {
        mkdir($testPath, 0755, true);
    }
    file_put_contents($testPath.'/.env', "APP_NAME=TestProject\nAPP_ENV=testing");

    $response = $this->actingAs($this->user)
        ->get("/environment/project/{$project->id}");

    $response->assertSuccessful();

    // Cleanup
    if (file_exists($testPath.'/.env')) {
        unlink($testPath.'/.env');
    }
    if (is_dir($testPath)) {
        rmdir($testPath);
    }
});

it('displays worktree env file', function () {
    $project = Project::factory()->create();
    $testPath = storage_path('test-worktree-'.uniqid());
    $worktree = Worktree::factory()->create([
        'project_id' => $project->id,
        'path' => $testPath,
    ]);

    if (! is_dir($testPath)) {
        mkdir($testPath, 0755, true);
    }
    file_put_contents($testPath.'/.env', "APP_NAME=TestWorktree\nAPP_ENV=testing");

    $response = $this->actingAs($this->user)
        ->get("/environment/worktree/{$worktree->id}");

    $response->assertSuccessful();

    // Cleanup
    if (file_exists($testPath.'/.env')) {
        unlink($testPath.'/.env');
    }
    if (is_dir($testPath)) {
        rmdir($testPath);
    }
});

it('requires authentication for environment routes', function () {
    $response = $this->get('/environment');

    $response->assertStatus(302);
})->skip('Login route not configured yet');

it('can update env file', function () {
    $testPath = storage_path('test-project-'.uniqid());
    $project = Project::factory()->create([
        'path' => $testPath,
    ]);

    if (! is_dir($testPath)) {
        mkdir($testPath, 0755, true);
    }
    file_put_contents($testPath.'/.env', 'APP_NAME=OldName');

    $response = $this->actingAs($this->user)
        ->post('/environment/update', [
            'env_path' => $testPath.'/.env',
            'variables' => [
                'APP_NAME' => [
                    'value' => 'NewName',
                    'comment' => null,
                    'is_sensitive' => false,
                ],
            ],
        ]);

    $response->assertRedirect();

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

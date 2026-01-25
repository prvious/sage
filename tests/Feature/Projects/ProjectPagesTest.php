<?php

use App\Models\Project;

it('renders create page successfully', function () {
    $response = $this->get(route('projects.create'));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page->component('projects/create'));
});

it('renders edit page successfully with project data', function () {
    $project = Project::factory()->create([
        'name' => 'Test Project',
        'path' => '/var/www/test',
    ]);

    $response = $this->get(route('projects.edit', $project));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('projects/edit')
        ->where('project.id', $project->id)
        ->where('project.name', 'Test Project')
        ->where('project.path', '/var/www/test')
    );
});

it('renders show page successfully with project data', function () {
    $project = Project::factory()->create([
        'name' => 'Test Project',
    ]);

    $response = $this->get(route('projects.show', $project));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('projects/show')
        ->where('project.id', $project->id)
        ->where('project.name', 'Test Project')
    );
});

it('accepts form submission on create', function () {
    $data = [
        'name' => 'New Project',
        'path' => '/var/www/newproject-'.uniqid(),
        'server_driver' => 'artisan',
        'base_url' => 'newproject.local',
    ];

    $response = $this->post(route('projects.store'), $data);

    $response->assertRedirect();
});

it('updates project with form submission', function () {
    $project = Project::factory()->create([
        'name' => 'Old Name',
    ]);

    $data = [
        'name' => 'Updated Name',
        'path' => $project->path,
        'server_driver' => $project->server_driver,
        'base_url' => $project->base_url,
    ];

    $response = $this->patch(route('projects.update', $project), $data);

    $response->assertRedirect();
    expect($project->fresh()->name)->toBe('Updated Name');
});

it('deletes project successfully', function () {
    $project = Project::factory()->create();

    $response = $this->delete(route('projects.destroy', $project));

    $response->assertRedirect();
    $this->assertDatabaseMissing('projects', ['id' => $project->id]);
});

it('validates required fields on create', function () {
    $response = $this->post(route('projects.store'), []);

    $response->assertSessionHasErrors(['name', 'path']);
});

it('validates required fields on update', function () {
    $project = Project::factory()->create();

    $response = $this->patch(route('projects.update', $project), [
        'name' => '',
        'path' => '',
    ]);

    $response->assertSessionHasErrors(['name', 'path']);
});

it('creates project with artisan server driver', function () {
    $testPath = sys_get_temp_dir().'/test-artisan-project-'.uniqid();
    mkdir($testPath);
    file_put_contents($testPath.'/composer.json', json_encode([
        'require' => ['laravel/framework' => '^12.0'],
    ]));
    file_put_contents($testPath.'/.env', 'APP_NAME=TestApp');

    $data = [
        'name' => 'Artisan Project',
        'path' => $testPath,
        'server_driver' => 'artisan',
        'base_url' => 'artisan.localhost',
    ];

    $response = $this->post(route('projects.store'), $data);

    $response->assertRedirect();
    $this->assertDatabaseHas('projects', [
        'name' => 'Artisan Project',
        'server_driver' => 'artisan',
    ]);

    // Cleanup
    unlink($testPath.'/.env');
    unlink($testPath.'/composer.json');
    rmdir($testPath);
});

it('updates project to artisan server driver', function () {
    $project = Project::factory()->create([
        'server_driver' => 'artisan',
    ]);

    $data = [
        'name' => $project->name,
        'path' => $project->path,
        'server_driver' => 'artisan',
        'base_url' => $project->base_url,
    ];

    $response = $this->patch(route('projects.update', $project), $data);

    $response->assertRedirect();
    expect($project->fresh()->server_driver)->toBe('artisan');
});

it('validates artisan is a valid server driver on create', function () {
    $testPath = sys_get_temp_dir().'/test-artisan-validation-'.uniqid();
    mkdir($testPath);
    file_put_contents($testPath.'/composer.json', json_encode([
        'require' => ['laravel/framework' => '^12.0'],
    ]));
    file_put_contents($testPath.'/.env', 'APP_NAME=TestApp');

    $data = [
        'name' => 'Test Project',
        'path' => $testPath,
        'server_driver' => 'artisan',
        'base_url' => 'test.localhost',
    ];

    $response = $this->post(route('projects.store'), $data);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();

    // Cleanup
    unlink($testPath.'/.env');
    unlink($testPath.'/composer.json');
    rmdir($testPath);
});

it('validates artisan is a valid server driver on update', function () {
    $project = Project::factory()->create();

    $data = [
        'name' => $project->name,
        'path' => $project->path,
        'server_driver' => 'artisan',
        'base_url' => $project->base_url,
    ];

    $response = $this->patch(route('projects.update', $project), $data);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();
});

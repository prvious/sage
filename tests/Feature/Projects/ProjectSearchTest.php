<?php

use App\Models\Project;

test('projects index returns all projects without search', function () {
    $projects = Project::factory()->count(3)->create();

    $response = $this->get('/projects');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('projects/index')
        ->has('projects', 3)
        ->where('search', '')
    );
});

test('projects index filters by name', function () {
    Project::factory()->create(['name' => 'Laravel App']);
    Project::factory()->create(['name' => 'Vue Project']);
    Project::factory()->create(['name' => 'React App']);

    $response = $this->get('/projects?search=Laravel');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('projects/index')
        ->has('projects', 1)
        ->where('projects.0.name', 'Laravel App')
        ->where('search', 'Laravel')
    );
});

test('projects index filters by path', function () {
    Project::factory()->create(['path' => '/var/www/laravel']);
    Project::factory()->create(['path' => '/var/www/vue']);
    Project::factory()->create(['path' => '/home/user/react']);

    $response = $this->get('/projects?search=laravel');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('projects/index')
        ->has('projects', 1)
        ->where('projects.0.path', '/var/www/laravel')
        ->where('search', 'laravel')
    );
});

test('projects index filters by base_url', function () {
    Project::factory()->create(['base_url' => 'https://laravel.test']);
    Project::factory()->create(['base_url' => 'https://vue.test']);
    Project::factory()->create(['base_url' => 'https://react.test']);

    $response = $this->get('/projects?search=vue.test');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('projects/index')
        ->has('projects', 1)
        ->where('projects.0.base_url', 'https://vue.test')
        ->where('search', 'vue.test')
    );
});

test('projects index search is case-insensitive', function () {
    Project::factory()->create(['name' => 'Laravel App']);

    $response = $this->get('/projects?search=LARAVEL');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('projects/index')
        ->has('projects', 1)
        ->where('projects.0.name', 'Laravel App')
    );
});

test('projects index search handles special characters', function () {
    Project::factory()->create(['name' => 'My-Project_Test']);

    $response = $this->get('/projects?search=My-Project_Test');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('projects/index')
        ->has('projects', 1)
        ->where('projects.0.name', 'My-Project_Test')
    );
});

test('projects index search returns empty array when no matches', function () {
    Project::factory()->count(3)->create();

    $response = $this->get('/projects?search=nonexistent');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('projects/index')
        ->has('projects', 0)
        ->where('search', 'nonexistent')
    );
});

test('projects index search parameter is optional', function () {
    Project::factory()->count(3)->create();

    $response = $this->get('/projects');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('projects/index')
        ->has('projects', 3)
    );
});

test('projects index returns search parameter in response', function () {
    Project::factory()->create(['name' => 'Test Project']);

    $response = $this->get('/projects?search=Test');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('projects/index')
        ->where('search', 'Test')
    );
});

test('projects index search matches partial strings', function () {
    Project::factory()->create(['name' => 'Laravel Application']);

    $response = $this->get('/projects?search=App');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('projects/index')
        ->has('projects', 1)
        ->where('projects.0.name', 'Laravel Application')
    );
});

test('projects index search matches across multiple fields', function () {
    $project1 = Project::factory()->create([
        'name' => 'Laravel App',
        'path' => '/var/www/project',
        'base_url' => 'https://example.test',
    ]);

    $project2 = Project::factory()->create([
        'name' => 'Vue App',
        'path' => '/var/www/laravel',
        'base_url' => 'https://test.test',
    ]);

    $project3 = Project::factory()->create([
        'name' => 'React App',
        'path' => '/var/www/react',
        'base_url' => 'https://laravel.test',
    ]);

    $response = $this->get('/projects?search=laravel');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('projects/index')
        ->has('projects', 3) // All three should match
    );
});

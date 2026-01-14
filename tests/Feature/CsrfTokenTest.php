<?php

use App\Models\Project;

it('includes CSRF token meta tag in projects index page', function () {
    Project::factory()->create();

    $response = $this->get('/projects');

    $response->assertSuccessful();
    $response->assertSee('<meta name="csrf-token"', false);
});

it('includes CSRF token meta tag in projects create page', function () {
    $response = $this->get('/projects/create');

    $response->assertSuccessful();
    $response->assertSee('<meta name="csrf-token"', false);
});

it('includes CSRF token meta tag in projects edit page', function () {
    $project = Project::factory()->create();

    $response = $this->get("/projects/{$project->id}/edit");

    $response->assertSuccessful();
    $response->assertSee('<meta name="csrf-token"', false);
});

it('includes CSRF token meta tag in projects show page', function () {
    $project = Project::factory()->create();

    $response = $this->get("/projects/{$project->id}");

    $response->assertSuccessful();
    $response->assertSee('<meta name="csrf-token"', false);
});

it('includes CSRF token meta tag in dashboard', function () {
    $response = $this->get('/dashboard');

    $response->assertSuccessful();
    $response->assertSee('<meta name="csrf-token"', false);
});

it('CSRF token meta tag contains valid token', function () {
    $response = $this->get('/projects/create');

    $response->assertSuccessful();

    // Extract the content
    $content = $response->getContent();

    // Verify the CSRF token format
    expect($content)->toContain('csrf-token');
    expect($content)->toMatch('/<meta name="csrf-token" content="[^"]+"/');
});

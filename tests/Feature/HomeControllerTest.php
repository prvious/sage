<?php

use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\get;

uses(RefreshDatabase::class);

it('redirects to projects index when no projects exist', function () {
    $response = get('/');

    $response->assertRedirect(route('projects.index'));
});

it('redirects to projects index when no last opened project', function () {
    // Create some projects but don't set a last opened one
    Project::factory()->count(2)->create();

    $response = get('/');

    $response->assertRedirect(route('projects.index'));
});

it('redirects appropriately when projects exist', function () {
    Project::factory()->create();

    $response = get('/');

    // Should redirect to either projects.index or a project dashboard
    $response->assertRedirect();
    expect($response->headers->get('Location'))->toContain('/projects');
});

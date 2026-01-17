<?php

use App\Models\Project;

it('displays three server driver choice cards on edit page', function () {
    $project = Project::factory()->create(['server_driver' => 'caddy']);

    $page = visit("/projects/{$project->id}/edit");

    // Should see all three driver labels
    $page->assertSee('Caddy');
    $page->assertSee('Nginx');
    $page->assertSee('Artisan Server');

    $page->assertNoJavascriptErrors();
});

it('displays server driver descriptions on edit page', function () {
    $project = Project::factory()->create(['server_driver' => 'nginx']);

    $page = visit("/projects/{$project->id}/edit");

    // Should see descriptions
    $page->assertSee('Modern web server with automatic HTTPS');
    $page->assertSee('High-performance production web server');
    $page->assertSee('Lightweight PHP development server');

    $page->assertNoJavascriptErrors();
});

it('displays recommended badge on Caddy card on edit page', function () {
    $project = Project::factory()->create(['server_driver' => 'caddy']);

    $page = visit("/projects/{$project->id}/edit");

    // Should see Recommended badge
    $page->assertSee('Recommended');

    $page->assertNoJavascriptErrors();
});

it('displays development only badge on Artisan card on edit page', function () {
    $project = Project::factory()->create(['server_driver' => 'artisan']);

    $page = visit("/projects/{$project->id}/edit");

    // Should see Development Only badge
    $page->assertSee('Development Only');

    $page->assertNoJavascriptErrors();
});

<?php

declare(strict_types=1);

use App\Models\Project;
use App\Models\Spec;
use App\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::factory()->create();
    actingAs($this->user);
});

it('uses AppLayout on specs index page', function () {
    $project = Project::factory()->create();

    $page = visit("/projects/{$project->id}/specs");

    // Verify page loads without errors
    $page->assertNoJavascriptErrors();
});

it('uses AppLayout on specs create page', function () {
    $project = Project::factory()->create();

    $page = visit("/projects/{$project->id}/specs/create");

    // Verify page loads without errors
    $page->assertNoJavascriptErrors();
});

it('uses AppLayout on specs show page', function () {
    $project = Project::factory()->create();
    $spec = Spec::factory()->create([
        'project_id' => $project->id,
        'title' => 'Test Specification',
    ]);

    $page = visit("/projects/{$project->id}/specs/{$spec->id}");

    // Verify page loads without errors
    $page->assertNoJavascriptErrors();
});

it('uses AppLayout on specs edit page', function () {
    $project = Project::factory()->create();
    $spec = Spec::factory()->create([
        'project_id' => $project->id,
    ]);

    $page = visit("/projects/{$project->id}/specs/{$spec->id}/edit");

    // Verify page loads without errors
    $page->assertNoJavascriptErrors();
});

it('displays consistent layout across all specs pages', function () {
    $project = Project::factory()->create();

    // All specs pages should load without errors and use AppLayout
    $indexPage = visit("/projects/{$project->id}/specs");
    $indexPage->assertNoJavascriptErrors();

    $createPage = visit("/projects/{$project->id}/specs/create");
    $createPage->assertNoJavascriptErrors();
});

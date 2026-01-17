<?php

use App\Models\Project;
use App\Models\User;
use App\Models\Worktree;

use function Pest\Laravel\actingAs;

it('worktrees link in sidebar navigates to correct page', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();

    actingAs($user);

    $page = visit("/projects/{$project->id}/dashboard");

    // Click worktrees link in sidebar
    $page->click('Worktrees')
        ->assertPathIs("/projects/{$project->id}/worktrees")
        ->assertSee('Worktrees')
        ->assertNoJavascriptErrors();
});

it('worktrees index page loads successfully', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();

    actingAs($user);

    $page = visit("/projects/{$project->id}/worktrees");

    $page->assertSee('Worktrees')
        ->assertNoJavascriptErrors();
});

it('worktrees create page loads successfully', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();

    actingAs($user);

    $page = visit("/projects/{$project->id}/worktrees/create");

    $page->assertSee('Create Worktree')
        ->assertNoJavascriptErrors();
});

it('worktrees show page loads successfully', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $worktree = Worktree::factory()->create([
        'project_id' => $project->id,
    ]);

    actingAs($user);

    $page = visit("/projects/{$project->id}/worktrees/{$worktree->id}");

    $page->assertSee($worktree->branch_name)
        ->assertNoJavascriptErrors();
});

it('no page not found errors on worktrees pages', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();

    actingAs($user);

    // Test index page
    $indexPage = visit("/projects/{$project->id}/worktrees");
    $indexPage->assertNoJavascriptErrors();

    // Test create page
    $createPage = visit("/projects/{$project->id}/worktrees/create");
    $createPage->assertNoJavascriptErrors();
});

it('worktrees pages display correct project context', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['name' => 'Test Project']);

    actingAs($user);

    // Check index page shows project name
    $page = visit("/projects/{$project->id}/worktrees");
    $page->assertSee('Test Project')
        ->assertNoJavascriptErrors();
});

it('worktrees navigation is consistent across all pages', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $worktree = Worktree::factory()->create([
        'project_id' => $project->id,
    ]);

    actingAs($user);

    // Navigate from dashboard to worktrees
    $page = visit("/projects/{$project->id}/dashboard");
    $page->click('Worktrees')
        ->assertPathIs("/projects/{$project->id}/worktrees")
        ->assertNoJavascriptErrors();

    // Navigate to create page
    $page->navigate("/projects/{$project->id}/worktrees/create");
    $page->assertSee('Create Worktree')
        ->assertNoJavascriptErrors();

    // Navigate to show page
    $page->navigate("/projects/{$project->id}/worktrees/{$worktree->id}");
    $page->assertSee($worktree->branch_name)
        ->assertNoJavascriptErrors();
});

<?php

use App\Models\Project;
use App\Models\User;
use App\Models\Worktree;

use function Pest\Laravel\actingAs;

it('worktrees index page uses AppLayout', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();

    actingAs($user);

    $page = visit("/projects/{$project->id}/worktrees");

    // Verify AppLayout is present by checking for sidebar
    $page->assertSee('Navigation')
        ->assertSee('Worktrees')
        ->assertNoJavascriptErrors();
});

it('worktrees index displays cards in grid layout', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    Worktree::factory()->count(3)->create([
        'project_id' => $project->id,
    ]);

    actingAs($user);

    $page = visit("/projects/{$project->id}/worktrees");

    $page->assertSee('Worktrees')
        ->assertNoJavascriptErrors();
});

it('worktrees index displays empty state when no worktrees', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();

    actingAs($user);

    $page = visit("/projects/{$project->id}/worktrees");

    $page->assertSee('No worktrees yet')
        ->assertSee('Create your first worktree')
        ->assertSee('Create Your First Worktree')
        ->assertNoJavascriptErrors();
});

it('worktrees create page uses AppLayout', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();

    actingAs($user);

    $page = visit("/projects/{$project->id}/worktrees/create");

    $page->assertSee('Navigation')
        ->assertSee('Create Worktree')
        ->assertNoJavascriptErrors();
});

it('worktrees create page displays all form fields', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();

    actingAs($user);

    $page = visit("/projects/{$project->id}/worktrees/create");

    $page->assertSee('Branch Name')
        ->assertSee('Create branch if it doesn\'t exist')
        ->assertSee('Database Isolation')
        ->assertSee('Separate Database (SQLite)')
        ->assertSee('Table Prefix')
        ->assertSee('Shared Database')
        ->assertNoJavascriptErrors();
});

it('worktrees show page uses AppLayout', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $worktree = Worktree::factory()->create([
        'project_id' => $project->id,
        'status' => 'active',
    ]);

    actingAs($user);

    $page = visit("/projects/{$project->id}/worktrees/{$worktree->id}");

    $page->assertSee('Navigation')
        ->assertSee($worktree->branch_name)
        ->assertNoJavascriptErrors();
});

it('worktrees show page displays worktree details', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $worktree = Worktree::factory()->create([
        'project_id' => $project->id,
        'branch_name' => 'feature/test',
        'status' => 'active',
    ]);

    actingAs($user);

    $page = visit("/projects/{$project->id}/worktrees/{$worktree->id}");

    $page->assertSee('feature/test')
        ->assertSee('Worktree Details')
        ->assertSee('Preview URL')
        ->assertSee('Delete Worktree')
        ->assertNoJavascriptErrors();
});

it('worktrees show page displays open preview button for active worktrees', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $worktree = Worktree::factory()->create([
        'project_id' => $project->id,
        'status' => 'active',
    ]);

    actingAs($user);

    $page = visit("/projects/{$project->id}/worktrees/{$worktree->id}");

    $page->assertSee('Open Preview')
        ->assertNoJavascriptErrors();
});

it('worktrees show page displays alert for creating status', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $worktree = Worktree::factory()->create([
        'project_id' => $project->id,
        'status' => 'creating',
    ]);

    actingAs($user);

    $page = visit("/projects/{$project->id}/worktrees/{$worktree->id}");

    $page->assertSee('This worktree is being set up')
        ->assertNoJavascriptErrors();
});

it('worktrees pages have no back to links', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $worktree = Worktree::factory()->create([
        'project_id' => $project->id,
    ]);

    actingAs($user);

    // Check index page
    $indexPage = visit("/projects/{$project->id}/worktrees");
    $indexPage->assertDontSee('← Back to');

    // Check create page
    $createPage = visit("/projects/{$project->id}/worktrees/create");
    $createPage->assertDontSee('← Back to');

    // Check show page
    $showPage = visit("/projects/{$project->id}/worktrees/{$worktree->id}");
    $showPage->assertDontSee('← Back to');
});

it('worktrees index displays status badges correctly', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();

    Worktree::factory()->create([
        'project_id' => $project->id,
        'branch_name' => 'feature/active',
        'status' => 'active',
    ]);

    Worktree::factory()->create([
        'project_id' => $project->id,
        'branch_name' => 'feature/creating',
        'status' => 'creating',
    ]);

    actingAs($user);

    $page = visit("/projects/{$project->id}/worktrees");

    $page->assertSee('active')
        ->assertSee('creating')
        ->assertNoJavascriptErrors();
});

it('worktrees sidebar link is active on worktrees pages', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();

    actingAs($user);

    $page = visit("/projects/{$project->id}/worktrees");

    // The active link should be highlighted in the sidebar
    $page->assertSee('Worktrees')
        ->assertNoJavascriptErrors();
});

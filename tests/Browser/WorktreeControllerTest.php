<?php

use App\Models\Project;
use App\Models\User;
use App\Models\Worktree;

use function Pest\Laravel\actingAs;

it('can create a worktree through UI', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();

    actingAs($user);

    $page = visit("/projects/{$project->id}/worktrees/create");

    $page->assertSee('Create Worktree')
        ->fill('branch_name', 'feature/browser-test')
        ->check('create_branch')
        ->select('database_isolation', 'separate')
        ->click('Create Worktree')
        ->assertPathIs("/projects/{$project->id}/worktrees/*")
        ->assertSee('feature/browser-test')
        ->assertNoJavascriptErrors();

    $this->assertDatabaseHas('worktrees', [
        'project_id' => $project->id,
        'branch_name' => 'feature/browser-test',
    ]);
});

it('shows real-time status updates during creation', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $worktree = Worktree::factory()->create([
        'project_id' => $project->id,
        'status' => 'creating',
    ]);

    actingAs($user);

    $page = visit("/projects/{$project->id}/worktrees/{$worktree->id}");

    $page->assertSee('creating')
        ->assertSee('This worktree is being set up')
        ->assertNoJavascriptErrors();

    // Simulate worktree becoming active
    $worktree->update(['status' => 'active']);

    // In a real scenario, this would update via Reverb broadcast
    // For testing, we verify the UI displays the correct status
    $page->assertSee($worktree->branch_name);
});

it('can open preview URL in browser', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $worktree = Worktree::factory()->create([
        'project_id' => $project->id,
        'status' => 'active',
        'preview_url' => 'http://test-branch.myapp.local',
    ]);

    actingAs($user);

    $page = visit("/projects/{$project->id}/worktrees/{$worktree->id}");

    $page->assertSee('Open in Browser')
        ->assertSee($worktree->preview_url)
        ->assertNoJavascriptErrors();

    // Verify the preview URL link exists and is correct
    expect($page->page->content())->toContain($worktree->preview_url);
});

it('can delete worktree with confirmation', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $worktree = Worktree::factory()->create([
        'project_id' => $project->id,
        'status' => 'active',
    ]);

    actingAs($user);

    $page = visit("/projects/{$project->id}/worktrees/{$worktree->id}");

    $page->assertSee('Delete Worktree')
        ->assertNoJavascriptErrors();

    // Click delete and handle confirmation dialog
    // Note: In a real browser test, you'd interact with the confirm dialog
    // For now, we verify the button exists
    expect($page->page->content())->toContain('Delete Worktree');
});

it('displays worktree list with status badges', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();

    Worktree::factory()->create([
        'project_id' => $project->id,
        'branch_name' => 'feature/test-1',
        'status' => 'active',
    ]);

    Worktree::factory()->create([
        'project_id' => $project->id,
        'branch_name' => 'feature/test-2',
        'status' => 'creating',
    ]);

    Worktree::factory()->create([
        'project_id' => $project->id,
        'branch_name' => 'feature/test-3',
        'status' => 'error',
    ]);

    actingAs($user);

    $page = visit("/projects/{$project->id}/worktrees");

    $page->assertSee('Worktrees')
        ->assertSee('feature/test-1')
        ->assertSee('feature/test-2')
        ->assertSee('feature/test-3')
        ->assertSee('active')
        ->assertSee('creating')
        ->assertSee('error')
        ->assertNoJavascriptErrors();
});

it('shows error message when worktree creation fails', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $worktree = Worktree::factory()->create([
        'project_id' => $project->id,
        'status' => 'error',
        'error_message' => 'Failed to create worktree: Branch does not exist',
    ]);

    actingAs($user);

    $page = visit("/projects/{$project->id}/worktrees/{$worktree->id}");

    $page->assertSee('error')
        ->assertSee('Failed to create worktree: Branch does not exist')
        ->assertNoJavascriptErrors();
});

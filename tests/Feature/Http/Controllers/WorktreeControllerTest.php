<?php

use App\Jobs\SetupWorktreeJob;
use App\Models\Project;
use App\Models\User;
use App\Models\Worktree;
use App\Services\GitService;
use Illuminate\Support\Facades\Queue;

use function Pest\Laravel\mock;

beforeEach(function () {
    Queue::fake();

    // Mock GitService
    $this->gitMock = mock(GitService::class);
    $this->gitMock->shouldReceive('branchExists')->andReturn(true);
    $this->gitMock->shouldReceive('createWorktree')->andReturn(true);
    $this->gitMock->shouldReceive('removeWorktree')->andReturn(true);
});

it('can list all worktrees for a project', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $worktrees = Worktree::factory()->count(3)->create([
        'project_id' => $project->id,
    ]);

    $response = $this->actingAs($user)->get("/projects/{$project->id}/worktrees");

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('worktrees/index')
        ->has('worktrees', 3));
});

it('can create a worktree for existing branch', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();

    $response = $this->actingAs($user)->post("/projects/{$project->id}/worktrees", [
        'branch_name' => 'feature/test',
        'create_branch' => false,
        'database_isolation' => 'separate',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('worktrees', [
        'project_id' => $project->id,
        'branch_name' => 'feature/test',
        'status' => 'creating',
        'database_isolation' => 'separate',
    ]);

    Queue::assertPushed(SetupWorktreeJob::class);
});

it('can create branch and worktree together', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();

    $this->gitMock->shouldReceive('branchExists')->andReturn(false);
    $this->gitMock->shouldReceive('createBranch')->andReturn(true);

    $response = $this->actingAs($user)->post("/projects/{$project->id}/worktrees", [
        'branch_name' => 'feature/new',
        'create_branch' => true,
        'database_isolation' => 'separate',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('worktrees', [
        'project_id' => $project->id,
        'branch_name' => 'feature/new',
    ]);
});

it('prevents duplicate worktrees for same branch', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    Worktree::factory()->create([
        'project_id' => $project->id,
        'branch_name' => 'feature/test',
    ]);

    $response = $this->actingAs($user)->post("/projects/{$project->id}/worktrees", [
        'branch_name' => 'feature/test',
        'create_branch' => false,
        'database_isolation' => 'separate',
    ]);

    $response->assertSessionHasErrors('branch_name');
});

it('generates correct preview URL from branch name', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create([
        'base_url' => 'http://myapp.local',
    ]);

    $response = $this->actingAs($user)->post("/projects/{$project->id}/worktrees", [
        'branch_name' => 'feature/auth-system',
        'create_branch' => false,
        'database_isolation' => 'separate',
    ]);

    $response->assertRedirect();
    $worktree = Worktree::where('branch_name', 'feature/auth-system')->first();
    expect($worktree->preview_url)->toBe('http://feature-auth-system.myapp.local');
});

it('validates branch name format', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();

    $response = $this->actingAs($user)->post("/projects/{$project->id}/worktrees", [
        'branch_name' => 'invalid branch name!',
        'create_branch' => false,
        'database_isolation' => 'separate',
    ]);

    $response->assertSessionHasErrors('branch_name');
});

it('requires database isolation type', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();

    $response = $this->actingAs($user)->post("/projects/{$project->id}/worktrees", [
        'branch_name' => 'feature/test',
        'create_branch' => false,
    ]);

    $response->assertSessionHasErrors('database_isolation');
});

it('can delete a worktree', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $worktree = Worktree::factory()->create([
        'project_id' => $project->id,
    ]);

    $response = $this->actingAs($user)->delete("/projects/{$project->id}/worktrees/{$worktree->id}");

    $response->assertRedirect("/projects/{$project->id}/worktrees");
    $this->assertDatabaseMissing('worktrees', ['id' => $worktree->id]);
});

it('can show worktree details', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $worktree = Worktree::factory()->create([
        'project_id' => $project->id,
    ]);

    $response = $this->actingAs($user)->get("/projects/{$project->id}/worktrees/{$worktree->id}");

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('worktrees/show')
        ->has('worktree'));
});

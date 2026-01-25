<?php

use App\Models\Commit;
use App\Models\Project;
use App\Models\Spec;
use App\Models\Task;
use App\Models\Worktree;

it('can create a project using factory', function () {
    $project = Project::factory()->create();

    expect($project)->toBeInstanceOf(Project::class);
    expect($project->name)->not->toBeEmpty();
    expect($project->path)->not->toBeEmpty();
    expect($project->server_driver)->toBe('artisan');
    expect($project->base_url)->not->toBeEmpty();
});

it('can create a worktree using factory', function () {
    $worktree = Worktree::factory()->create();

    expect($worktree)->toBeInstanceOf(Worktree::class);
    expect($worktree->project)->toBeInstanceOf(Project::class);
    expect($worktree->branch_name)->not->toBeEmpty();
    expect($worktree->path)->not->toBeEmpty();
    expect($worktree->preview_url)->not->toBeEmpty();
    expect($worktree->status)->toBe('active');
});

it('can create a worktree with different statuses', function () {
    $creating = Worktree::factory()->creating()->create();
    $active = Worktree::factory()->active()->create();
    $error = Worktree::factory()->error()->create();
    $cleaningUp = Worktree::factory()->cleaningUp()->create();
    $deleted = Worktree::factory()->deleted()->create();

    expect($creating->status)->toBe('creating');
    expect($active->status)->toBe('active');
    expect($error->status)->toBe('error');
    expect($cleaningUp->status)->toBe('cleaning_up');
    expect($deleted->status)->toBe('deleted');
});

it('can create a worktree with env overrides', function () {
    $worktree = Worktree::factory()->withEnvOverrides()->create();

    expect($worktree->env_overrides)->toBeArray();
    expect($worktree->env_overrides)->toHaveKey('APP_DEBUG');
});

it('can create a task using factory', function () {
    $task = Task::factory()->create();

    expect($task)->toBeInstanceOf(Task::class);
    expect($task->project)->toBeInstanceOf(Project::class);
    expect($task->title)->not->toBeEmpty();
    expect($task->description)->not->toBeEmpty();
    expect($task->status)->toBe('idea');
});

it('can create tasks with different statuses', function () {
    $idea = Task::factory()->idea()->create();
    $inProgress = Task::factory()->inProgress()->create();
    $review = Task::factory()->review()->create();
    $done = Task::factory()->done()->create();
    $failed = Task::factory()->failed()->create();

    expect($idea->status)->toBe('idea');
    expect($inProgress->status)->toBe('in_progress');
    expect($review->status)->toBe('review');
    expect($done->status)->toBe('done');
    expect($failed->status)->toBe('failed');
});

it('can create a task for a worktree', function () {
    $task = Task::factory()->forWorktree()->create();

    expect($task->worktree)->toBeInstanceOf(Worktree::class);
});

it('can create a commit using factory', function () {
    $commit = Commit::factory()->create();

    expect($commit)->toBeInstanceOf(Commit::class);
    expect($commit->task)->toBeInstanceOf(Task::class);
    expect($commit->sha)->not->toBeEmpty();
    expect($commit->message)->not->toBeEmpty();
    expect($commit->author)->not->toBeEmpty();
    expect($commit->created_at)->toBeInstanceOf(\DateTimeInterface::class);
});

it('can create a spec using factory', function () {
    $spec = Spec::factory()->create();

    expect($spec)->toBeInstanceOf(Spec::class);
    expect($spec->project)->toBeInstanceOf(Project::class);
    expect($spec->title)->not->toBeEmpty();
    expect($spec->content)->not->toBeEmpty();
    expect($spec->generated_from_idea)->toBeNull();
});

it('can create a spec from an idea', function () {
    $spec = Spec::factory()->fromIdea()->create();

    expect($spec->generated_from_idea)->not->toBeNull();
});

it('can create complete object graph', function () {
    $project = Project::factory()
        ->has(Worktree::factory()->count(2))
        ->has(Task::factory()->count(3))
        ->has(Spec::factory()->count(2))
        ->create();

    expect($project->worktrees)->toHaveCount(2);
    expect($project->tasks)->toHaveCount(3);
    expect($project->specs)->toHaveCount(2);
});

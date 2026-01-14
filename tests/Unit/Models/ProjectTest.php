<?php

use App\Models\Project;
use App\Models\Spec;
use App\Models\Task;
use App\Models\Worktree;

it('has many worktrees', function () {
    $project = Project::factory()->create();
    $worktrees = Worktree::factory()->count(3)->create(['project_id' => $project->id]);

    expect($project->worktrees)->toHaveCount(3);
    expect($project->worktrees->first())->toBeInstanceOf(Worktree::class);
    expect($project->worktrees->pluck('id')->toArray())->toBe($worktrees->pluck('id')->toArray());
});

it('has many tasks', function () {
    $project = Project::factory()->create();
    $tasks = Task::factory()->count(3)->create(['project_id' => $project->id]);

    expect($project->tasks)->toHaveCount(3);
    expect($project->tasks->first())->toBeInstanceOf(Task::class);
    expect($project->tasks->pluck('id')->toArray())->toBe($tasks->pluck('id')->toArray());
});

it('has many specs', function () {
    $project = Project::factory()->create();
    $specs = Spec::factory()->count(3)->create(['project_id' => $project->id]);

    expect($project->specs)->toHaveCount(3);
    expect($project->specs->first())->toBeInstanceOf(Spec::class);
    expect($project->specs->pluck('id')->toArray())->toBe($specs->pluck('id')->toArray());
});

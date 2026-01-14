<?php

use App\Models\Project;
use App\Models\Task;
use App\Models\Worktree;

it('belongs to a project', function () {
    $project = Project::factory()->create();
    $worktree = Worktree::factory()->create(['project_id' => $project->id]);

    expect($worktree->project)->toBeInstanceOf(Project::class);
    expect($worktree->project->id)->toBe($project->id);
});

it('has many tasks', function () {
    $worktree = Worktree::factory()->create();
    $tasks = Task::factory()->count(3)->create(['worktree_id' => $worktree->id, 'project_id' => $worktree->project_id]);

    expect($worktree->tasks)->toHaveCount(3);
    expect($worktree->tasks->first())->toBeInstanceOf(Task::class);
    expect($worktree->tasks->pluck('id')->toArray())->toBe($tasks->pluck('id')->toArray());
});

it('casts env_overrides as array', function () {
    $worktree = Worktree::factory()->create([
        'env_overrides' => ['APP_DEBUG' => 'true', 'LOG_LEVEL' => 'debug'],
    ]);

    expect($worktree->env_overrides)->toBeArray();
    expect($worktree->env_overrides)->toBe(['APP_DEBUG' => 'true', 'LOG_LEVEL' => 'debug']);
});

<?php

use App\Models\Commit;
use App\Models\Project;
use App\Models\Task;
use App\Models\Worktree;

it('belongs to a project', function () {
    $project = Project::factory()->create();
    $task = Task::factory()->create(['project_id' => $project->id]);

    expect($task->project)->toBeInstanceOf(Project::class);
    expect($task->project->id)->toBe($project->id);
});

it('belongs to a worktree', function () {
    $worktree = Worktree::factory()->create();
    $task = Task::factory()->create([
        'project_id' => $worktree->project_id,
        'worktree_id' => $worktree->id,
    ]);

    expect($task->worktree)->toBeInstanceOf(Worktree::class);
    expect($task->worktree->id)->toBe($worktree->id);
});

it('can have null worktree', function () {
    $task = Task::factory()->create(['worktree_id' => null]);

    expect($task->worktree)->toBeNull();
});

it('has many commits', function () {
    $task = Task::factory()->create();
    $commits = Commit::factory()->count(3)->create(['task_id' => $task->id]);

    expect($task->commits)->toHaveCount(3);
    expect($task->commits->first())->toBeInstanceOf(Commit::class);
    expect($task->commits->pluck('id')->toArray())->toBe($commits->pluck('id')->toArray());
});

it('casts started_at and completed_at as datetime', function () {
    $task = Task::factory()->create([
        'started_at' => now(),
        'completed_at' => now(),
    ]);

    expect($task->started_at)->toBeInstanceOf(\DateTimeInterface::class);
    expect($task->completed_at)->toBeInstanceOf(\DateTimeInterface::class);
});

it('casts status as TaskStatus enum', function () {
    $task = Task::factory()->create(['status' => \App\Enums\TaskStatus::Queued]);

    expect($task->status)->toBeInstanceOf(\App\Enums\TaskStatus::class);
    expect($task->status)->toBe(\App\Enums\TaskStatus::Queued);
});

it('has default status of queued', function () {
    $task = Task::factory()->create();

    expect($task->status)->toBe(\App\Enums\TaskStatus::Queued);
});

it('can scope tasks by status', function () {
    Task::factory()->create(['status' => \App\Enums\TaskStatus::Queued]);
    Task::factory()->create(['status' => \App\Enums\TaskStatus::InProgress]);
    Task::factory()->create(['status' => \App\Enums\TaskStatus::Done]);

    $queuedTasks = Task::byStatus(\App\Enums\TaskStatus::Queued)->get();
    $inProgressTasks = Task::byStatus(\App\Enums\TaskStatus::InProgress)->get();

    expect($queuedTasks)->toHaveCount(1);
    expect($inProgressTasks)->toHaveCount(1);
});

it('can scope tasks by project', function () {
    $project1 = Project::factory()->create();
    $project2 = Project::factory()->create();

    Task::factory()->count(2)->create(['project_id' => $project1->id]);
    Task::factory()->create(['project_id' => $project2->id]);

    $project1Tasks = Task::forProject($project1->id)->get();

    expect($project1Tasks)->toHaveCount(2);
});

<?php

use App\Models\Commit;
use App\Models\Project;
use App\Models\Spec;
use App\Models\Task;
use App\Models\Worktree;

it('deletes worktrees when project is deleted', function () {
    $project = Project::factory()->create();
    $worktrees = Worktree::factory()->count(3)->create(['project_id' => $project->id]);

    expect(Worktree::count())->toBe(3);

    $project->delete();

    expect(Worktree::count())->toBe(0);
});

it('deletes tasks when project is deleted', function () {
    $project = Project::factory()->create();
    $tasks = Task::factory()->count(3)->create(['project_id' => $project->id]);

    expect(Task::count())->toBe(3);

    $project->delete();

    expect(Task::count())->toBe(0);
});

it('deletes specs when project is deleted', function () {
    $project = Project::factory()->create();
    $specs = Spec::factory()->count(3)->create(['project_id' => $project->id]);

    expect(Spec::count())->toBe(3);

    $project->delete();

    expect(Spec::count())->toBe(0);
});

it('sets worktree_id to null on tasks when worktree is deleted', function () {
    $worktree = Worktree::factory()->create();
    $task = Task::factory()->create([
        'project_id' => $worktree->project_id,
        'worktree_id' => $worktree->id,
    ]);

    expect($task->worktree_id)->toBe($worktree->id);

    $worktree->delete();

    $task->refresh();

    expect($task->worktree_id)->toBeNull();
});

it('deletes commits when task is deleted', function () {
    $task = Task::factory()->create();
    $commits = Commit::factory()->count(3)->create(['task_id' => $task->id]);

    expect(Commit::count())->toBe(3);

    $task->delete();

    expect(Commit::count())->toBe(0);
});

it('cascades deletion through entire hierarchy', function () {
    $project = Project::factory()->create();
    $worktree = Worktree::factory()->create(['project_id' => $project->id]);
    $task = Task::factory()->create([
        'project_id' => $project->id,
        'worktree_id' => $worktree->id,
    ]);
    $commits = Commit::factory()->count(3)->create(['task_id' => $task->id]);
    $spec = Spec::factory()->create(['project_id' => $project->id]);

    expect(Project::count())->toBe(1);
    expect(Worktree::count())->toBe(1);
    expect(Task::count())->toBe(1);
    expect(Commit::count())->toBe(3);
    expect(Spec::count())->toBe(1);

    $project->delete();

    expect(Project::count())->toBe(0);
    expect(Worktree::count())->toBe(0);
    expect(Task::count())->toBe(0);
    expect(Commit::count())->toBe(0);
    expect(Spec::count())->toBe(0);
});

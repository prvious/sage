<?php

use App\Models\Commit;
use App\Models\Task;

it('belongs to a task', function () {
    $task = Task::factory()->create();
    $commit = Commit::factory()->create(['task_id' => $task->id]);

    expect($commit->task)->toBeInstanceOf(Task::class);
    expect($commit->task->id)->toBe($task->id);
});

it('casts created_at as datetime', function () {
    $commit = Commit::factory()->create([
        'created_at' => now(),
    ]);

    expect($commit->created_at)->toBeInstanceOf(\DateTimeInterface::class);
});

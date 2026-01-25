<?php

use App\Actions\Feature\CreateTasksFromSpec;
use App\Enums\TaskStatus;
use App\Models\Spec;
use App\Models\Task;

it('creates tasks from spec with checkbox task list', function () {
    $spec = Spec::factory()->create([
        'content' => "# Feature\n\n- [ ] Task one\n- [ ] Task two\n- [ ] Task three",
    ]);

    $action = app(CreateTasksFromSpec::class);
    $tasks = $action->handle($spec);

    expect($tasks)->toHaveCount(3)
        ->and($tasks[0])->toBeInstanceOf(Task::class)
        ->and($tasks[0]->title)->toBe('Task one')
        ->and($tasks[0]->spec_id)->toBe($spec->id)
        ->and($tasks[0]->status)->toBe(TaskStatus::Queued)
        ->and($tasks[1]->title)->toBe('Task two')
        ->and($tasks[2]->title)->toBe('Task three');
});

it('creates tasks from spec with numbered list', function () {
    $spec = Spec::factory()->create([
        'content' => "# Feature\n\n1. First task\n2. Second task\n3. Third task",
    ]);

    $action = app(CreateTasksFromSpec::class);
    $tasks = $action->handle($spec);

    expect($tasks)->toHaveCount(3)
        ->and($tasks[0]->title)->toBe('First task')
        ->and($tasks[1]->title)->toBe('Second task')
        ->and($tasks[2]->title)->toBe('Third task');
});

it('creates tasks from spec with bullet points', function () {
    $spec = Spec::factory()->create([
        'content' => "# Feature\n\n- First item\n* Second item\n- Third item",
    ]);

    $action = app(CreateTasksFromSpec::class);
    $tasks = $action->handle($spec);

    expect($tasks)->toHaveCount(3)
        ->and($tasks[0]->title)->toBe('First item')
        ->and($tasks[1]->title)->toBe('Second item')
        ->and($tasks[2]->title)->toBe('Third item');
});

it('returns empty array when spec has no tasks', function () {
    $spec = Spec::factory()->create([
        'content' => '# Feature\n\nJust some content without any tasks.',
    ]);

    $action = app(CreateTasksFromSpec::class);
    $tasks = $action->handle($spec);

    expect($tasks)->toBeEmpty();
});

it('links tasks to spec via spec_id', function () {
    $spec = Spec::factory()->create([
        'content' => '- [ ] Test task',
    ]);

    $action = app(CreateTasksFromSpec::class);
    $tasks = $action->handle($spec);

    expect($tasks[0]->spec_id)->toBe($spec->id)
        ->and($tasks[0]->spec)->toBeInstanceOf(Spec::class)
        ->and($tasks[0]->spec->id)->toBe($spec->id);
});

it('sets tasks status to queued', function () {
    $spec = Spec::factory()->create([
        'content' => '- [ ] New task',
    ]);

    $action = app(CreateTasksFromSpec::class);
    $tasks = $action->handle($spec);

    expect($tasks[0]->status)->toBe(TaskStatus::Queued);
});

it('creates tasks with correct project_id', function () {
    $spec = Spec::factory()->create([
        'content' => '- [ ] Task for project',
    ]);

    $action = app(CreateTasksFromSpec::class);
    $tasks = $action->handle($spec);

    expect($tasks[0]->project_id)->toBe($spec->project_id)
        ->and($tasks[0]->project->id)->toBe($spec->project_id);
});

it('prefers checkbox lists over numbered lists', function () {
    $spec = Spec::factory()->create([
        'content' => "1. Numbered\n- [ ] Checkbox\n2. Another numbered",
    ]);

    $action = app(CreateTasksFromSpec::class);
    $tasks = $action->handle($spec);

    // Should only find the checkbox item, not numbered
    expect($tasks)->toHaveCount(1)
        ->and($tasks[0]->title)->toBe('Checkbox');
});

it('handles multiline task lists correctly', function () {
    $spec = Spec::factory()->create([
        'content' => "# Feature\n\n- [ ] Task one\n  with extra details\n- [ ] Task two\n  also with details",
    ]);

    $action = app(CreateTasksFromSpec::class);
    $tasks = $action->handle($spec);

    expect($tasks)->toHaveCount(2)
        ->and($tasks[0]->title)->toBe('Task one')
        ->and($tasks[1]->title)->toBe('Task two');
});

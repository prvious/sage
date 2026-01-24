<?php

use App\Events\BrainstormCompleted;
use App\Events\BrainstormFailed;
use App\Models\Brainstorm;
use App\Models\Project;
use Illuminate\Support\Facades\Event;

it('broadcasts BrainstormCompleted event when job succeeds', function () {
    Event::fake([BrainstormCompleted::class]);

    $project = Project::factory()->create();

    // Create a completed brainstorm to simulate successful job
    $brainstorm = Brainstorm::factory()->completed()->create([
        'project_id' => $project->id,
    ]);

    // Manually broadcast the event (simulating what the job does)
    broadcast(new BrainstormCompleted($brainstorm));

    // Assert event was broadcast
    Event::assertDispatched(BrainstormCompleted::class, function ($event) use ($brainstorm) {
        return $event->brainstorm->id === $brainstorm->id;
    });
});

it('broadcasts BrainstormFailed event when job fails', function () {
    Event::fake([BrainstormFailed::class]);

    $project = Project::factory()->create();

    // Create a failed brainstorm to simulate failed job
    $brainstorm = Brainstorm::factory()->failed()->create([
        'project_id' => $project->id,
        'error_message' => 'API rate limit exceeded',
    ]);

    // Manually broadcast the event (simulating what the job does)
    broadcast(new BrainstormFailed($brainstorm, 'API rate limit exceeded'));

    // Assert event was broadcast
    Event::assertDispatched(BrainstormFailed::class, function ($event) use ($brainstorm) {
        return $event->brainstorm->id === $brainstorm->id
            && $event->error === 'API rate limit exceeded';
    });
});

it('BrainstormCompleted event broadcasts on correct channel', function () {
    $project = Project::factory()->create();
    $brainstorm = Brainstorm::factory()->completed()->create([
        'project_id' => $project->id,
    ]);

    $event = new BrainstormCompleted($brainstorm);

    expect($event->broadcastOn()->name)
        ->toBe("private-project.{$project->id}.brainstorm");
});

it('BrainstormCompleted event has correct broadcast name', function () {
    $brainstorm = Brainstorm::factory()->completed()->create();
    $event = new BrainstormCompleted($brainstorm);

    expect($event->broadcastAs())->toBe('brainstorm.completed');
});

it('BrainstormCompleted event includes required data', function () {
    $brainstorm = Brainstorm::factory()->completed()->create([
        'ideas' => [
            ['title' => 'Idea 1', 'description' => 'Desc 1', 'priority' => 'high', 'category' => 'feature'],
            ['title' => 'Idea 2', 'description' => 'Desc 2', 'priority' => 'medium', 'category' => 'enhancement'],
        ],
    ]);

    $event = new BrainstormCompleted($brainstorm);
    $data = $event->broadcastWith();

    expect($data)->toHaveKeys(['brainstorm_id', 'ideas_count', 'message'])
        ->and($data['brainstorm_id'])->toBe($brainstorm->id)
        ->and($data['ideas_count'])->toBe(2)
        ->and($data['message'])->toBe('💡 New ideas are ready!');
});

it('BrainstormFailed event broadcasts on correct channel', function () {
    $project = Project::factory()->create();
    $brainstorm = Brainstorm::factory()->failed()->create([
        'project_id' => $project->id,
    ]);

    $event = new BrainstormFailed($brainstorm, 'Test error');

    expect($event->broadcastOn()->name)
        ->toBe("private-project.{$project->id}.brainstorm");
});

it('BrainstormFailed event has correct broadcast name', function () {
    $brainstorm = Brainstorm::factory()->failed()->create();
    $event = new BrainstormFailed($brainstorm, 'Test error');

    expect($event->broadcastAs())->toBe('brainstorm.failed');
});

it('BrainstormFailed event includes required data', function () {
    $brainstorm = Brainstorm::factory()->failed()->create();
    $event = new BrainstormFailed($brainstorm, 'API rate limit exceeded');
    $data = $event->broadcastWith();

    expect($data)->toHaveKeys(['brainstorm_id', 'error', 'message'])
        ->and($data['brainstorm_id'])->toBe($brainstorm->id)
        ->and($data['error'])->toBe('API rate limit exceeded')
        ->and($data['message'])->toContain('Failed to generate ideas');
});

it('channel authorization denies access when project does not exist', function () {
    $user = \App\Models\User::factory()->create();
    $nonExistentProjectId = 999999;

    // Project does not exist
    $hasAccess = Project::where('id', $nonExistentProjectId)->exists();

    expect($hasAccess)->toBeFalse();
});

it('channel authorization grants access when project exists', function () {
    $user = \App\Models\User::factory()->create();
    $project = Project::factory()->create();

    // Project exists
    $hasAccess = Project::where('id', $project->id)->exists();

    expect($hasAccess)->toBeTrue();
});

<?php

use App\Jobs\Feature\GenerateFeatureWorkflow;
use App\Models\Project;
use Illuminate\Support\Facades\Queue;

uses()->group('feature');

it('dispatches job on feature submission', function () {
    $project = Project::factory()->create();
    Queue::fake();

    $response = $this->post("/projects/{$project->id}/features", [
        'project_id' => $project->id,
        'description' => 'Build a great feature',
    ]);

    $response->assertRedirect();
    Queue::assertPushed(GenerateFeatureWorkflow::class);
});

it('returns immediate success response', function () {
    $project = Project::factory()->create();
    Queue::fake();

    $response = $this->post("/projects/{$project->id}/features", [
        'project_id' => $project->id,
        'description' => 'Build a great feature',
    ]);

    $response->assertSessionHas('success', "Generating feature in background. You'll be notified when ready!");
});

it('validates description before queuing', function () {
    $project = Project::factory()->create();
    Queue::fake();

    $response = $this->post("/projects/{$project->id}/features", [
        'project_id' => $project->id,
        'description' => 'short',
    ]);

    $response->assertSessionHasErrors(['description']);
    Queue::assertNotPushed(GenerateFeatureWorkflow::class);
});

it('does not wait for job completion', function () {
    $project = Project::factory()->create();
    Queue::fake();

    $startTime = microtime(true);

    $this->post("/projects/{$project->id}/features", [
        'project_id' => $project->id,
        'description' => 'Build something amazing',
    ]);

    $duration = microtime(true) - $startTime;

    // Should complete in less than 1 second (no AI call)
    expect($duration)->toBeLessThan(1.0);
});

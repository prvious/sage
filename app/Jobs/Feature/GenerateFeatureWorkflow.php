<?php

declare(strict_types=1);

namespace App\Jobs\Feature;

use App\Actions\Feature\CreateTasksFromSpec;
use App\Actions\Feature\GenerateSpecFromDescription;
use App\Events\Feature\FeatureGenerated;
use App\Events\Feature\FeatureGenerationFailed;
use App\Models\Spec;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateFeatureWorkflow implements ShouldQueue
{
    use Queueable;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 300;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 2;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 30;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $projectId,
        public string $description,
    ) {
        $this->onQueue('features');
    }

    /**
     * Execute the job.
     */
    public function handle(
        GenerateSpecFromDescription $generateSpec,
        CreateTasksFromSpec $createTasks,
    ): void {
        try {
            // Create a pending spec record first
            $spec = Spec::create([
                'project_id' => $this->projectId,
                'title' => 'Generating...',
                'content' => '',
                'generated_from_idea' => $this->description,
                'status' => 'processing',
                'processing_started_at' => now(),
            ]);

            // Generate spec from description
            $generatedSpec = $generateSpec->handle($this->projectId, $this->description);

            // Update the spec with generated content
            $spec->update([
                'title' => $generatedSpec->title,
                'content' => $generatedSpec->content,
            ]);

            // Delete the generated spec since we updated the original
            $generatedSpec->delete();

            // Create tasks from the spec
            $tasks = $createTasks->handle($spec);

            // Mark as completed
            $spec->update([
                'status' => 'completed',
                'processing_completed_at' => now(),
            ]);

            // Broadcast success event
            broadcast(new FeatureGenerated(
                projectId: $this->projectId,
                featureId: $spec->id,
                taskCount: count($tasks),
                message: sprintf(
                    'Feature created! %d %s added to board.',
                    count($tasks),
                    count($tasks) === 1 ? 'task' : 'tasks'
                ),
            ));
        } catch (Exception $e) {
            // Mark spec as failed if it exists
            if (isset($spec)) {
                $spec->update([
                    'status' => 'failed',
                    'processing_completed_at' => now(),
                    'error_message' => $e->getMessage(),
                ]);
            }

            // Broadcast failure event
            broadcast(new FeatureGenerationFailed(
                projectId: $this->projectId,
                error: 'Failed to generate feature: '.$e->getMessage(),
                description: $this->description,
            ));

            // Re-throw for retry logic
            throw $e;
        }
    }
}

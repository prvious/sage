<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\Brainstorm\GenerateIdeas;
use App\Events\BrainstormCompleted;
use App\Events\BrainstormFailed;
use App\Models\Brainstorm;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class GenerateBrainstormIdeas implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 300;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 2;

    /**
     * The number of seconds to wait before retrying.
     */
    public array $backoff = [60, 120];

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Brainstorm $brainstorm
    ) {}

    /**
     * Execute the job.
     */
    public function handle(GenerateIdeas $generateIdeas): void
    {
        // Update status to processing
        $this->brainstorm->update([
            'status' => 'processing',
        ]);

        try {
            // Generate ideas using AI
            $ideas = $generateIdeas->handle(
                $this->brainstorm->project,
                $this->brainstorm->user_context
            );

            // Update brainstorm with results
            $this->brainstorm->update([
                'ideas' => $ideas,
                'status' => 'completed',
                'completed_at' => now(),
                'error_message' => null,
            ]);

            // Broadcast completion event
            broadcast(new BrainstormCompleted($this->brainstorm))->toOthers();

        } catch (Throwable $exception) {
            // Update brainstorm with error
            $this->brainstorm->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
            ]);

            // Broadcast failure event
            broadcast(new BrainstormFailed($this->brainstorm, $exception->getMessage()))->toOthers();

            // Re-throw to trigger job failure handlers
            throw $exception;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        // Ensure status is failed if not already set
        if ($this->brainstorm->status !== 'failed') {
            $this->brainstorm->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
            ]);

            broadcast(new BrainstormFailed($this->brainstorm, $exception->getMessage()))->toOthers();
        }
    }
}

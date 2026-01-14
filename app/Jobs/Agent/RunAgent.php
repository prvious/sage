<?php

namespace App\Jobs\Agent;

use App\Drivers\Agent\AgentManager;
use App\Events\Agent\AgentOutputReceived;
use App\Events\Agent\AgentStatusChanged;
use App\Models\Task;
use App\Services\CommitDetector;
use App\Services\ProcessStreamer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RunAgent implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Task $task,
        public string $prompt,
        public array $options = []
    ) {}

    /**
     * Execute the job.
     */
    public function handle(
        AgentManager $agentManager,
        ProcessStreamer $processStreamer,
        CommitDetector $commitDetector
    ): void {
        $this->task->update([
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        AgentStatusChanged::dispatch($this->task->id, 'in_progress', 'Agent is starting...');

        $driver = $agentManager->driver($this->task->agent_type ?? config('sage.agents.default'));

        if (! $driver->isAvailable()) {
            $this->task->update([
                'status' => 'failed',
                'completed_at' => now(),
                'agent_output' => 'Agent binary is not available on this system.',
            ]);

            AgentStatusChanged::dispatch($this->task->id, 'failed', 'Agent binary is not available.');

            return;
        }

        $startTime = now()->toIso8601String();

        try {
            $process = $driver->spawn($this->task->worktree, $this->prompt, array_merge($this->options, [
                'model' => $this->task->model,
            ]));

            $output = '';

            $processStreamer->stream($process, function ($line, $type) use (&$output) {
                $output .= $line.PHP_EOL;

                AgentOutputReceived::dispatch($this->task->id, $line, $type);
            });

            $exitCode = $process->getExitCode();

            $commits = $commitDetector->detectNewCommits($this->task->worktree, $startTime);

            foreach ($commits as $commitData) {
                $this->task->commits()->create($commitData);
            }

            $this->task->update([
                'status' => $exitCode === 0 ? 'done' : 'failed',
                'completed_at' => now(),
                'agent_output' => $output,
            ]);

            AgentStatusChanged::dispatch(
                $this->task->id,
                $exitCode === 0 ? 'done' : 'failed',
                $exitCode === 0 ? 'Agent completed successfully.' : 'Agent failed with exit code: '.$exitCode
            );
        } catch (\Exception $e) {
            $this->task->update([
                'status' => 'failed',
                'completed_at' => now(),
                'agent_output' => 'Exception: '.$e->getMessage(),
            ]);

            AgentStatusChanged::dispatch($this->task->id, 'failed', 'Exception: '.$e->getMessage());

            throw $e;
        }
    }
}

<?php

namespace App\Drivers\Agent;

use App\Drivers\Agent\Contracts\AgentDriverInterface;
use App\Models\Worktree;
use Symfony\Component\Process\Process;

class ClaudeDriver implements AgentDriverInterface
{
    /**
     * Spawn an agent process on a worktree with a given prompt.
     */
    public function spawn(Worktree $worktree, string $prompt, array $options = []): Process
    {
        $model = $options['model'] ?? config('sage.agents.claude.default_model', 'claude-sonnet-4-20250514');

        $command = [
            $this->getBinaryPath(),
            '--worktree',
            $worktree->path,
            '--prompt',
            $prompt,
            '--model',
            $model,
        ];

        $process = new Process($command, $worktree->path, [
            'ANTHROPIC_API_KEY' => config('services.anthropic.api_key'),
        ]);

        $process->setTimeout(null);
        $process->start();

        return $process;
    }

    /**
     * Stop a running agent process.
     */
    public function stop(Process $process): bool
    {
        if (! $process->isRunning()) {
            return true;
        }

        $process->stop(10);

        if ($process->isRunning()) {
            $process->signal(SIGKILL);
        }

        return ! $process->isRunning();
    }

    /**
     * Check if the agent binary is available on the system.
     */
    public function isAvailable(): bool
    {
        $binaryPath = $this->getBinaryPath();

        $process = new Process([$binaryPath, '--version']);

        try {
            $process->run();

            return $process->isSuccessful();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get the list of supported AI models for this agent.
     */
    public function getSupportedModels(): array
    {
        return [
            'claude-sonnet-4-20250514',
            'claude-opus-4-20250514',
            'claude-3-5-sonnet-20241022',
        ];
    }

    /**
     * Get the binary path for this agent.
     */
    public function getBinaryPath(): string
    {
        return config('sage.agents.claude.binary', 'claude');
    }
}

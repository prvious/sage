<?php

namespace App\Drivers\Agent;

use App\Drivers\Agent\Contracts\AgentDriver;
use App\Models\Worktree;
use App\Support\SystemEnvironment;
use Symfony\Component\Process\Process;

class ClaudeDriver implements AgentDriver
{
    public function __construct(
        private SystemEnvironment $env,
    ) {}

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

        $process = new Process($command, $worktree->path, $this->env->all());

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

        $process = new Process([$binaryPath, '--version'], null, $this->env->all());

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
     * Execute a one-shot prompt and return the output.
     */
    public function executePrompt(string $prompt, array $options = []): string
    {
        $model = $options['model'] ?? config('sage.agents.claude.default_model', 'claude-sonnet-4-20250514');
        $timeout = $options['timeout'] ?? 120;

        $command = [
            $this->getBinaryPath(),
            '--print',
            '--output-format',
            'text',
            '--model',
            $model,
            $prompt,
        ];

        $process = new Process($command, null, $this->env->all());

        $process->setTimeout($timeout);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new \RuntimeException(
                'Agent execution failed: '.$process->getErrorOutput()
            );
        }

        return $process->getOutput();
    }

    /**
     * Get the binary path for this agent.
     */
    public function getBinaryPath(): string
    {
        return config('sage.agents.claude.binary', 'claude');
    }
}

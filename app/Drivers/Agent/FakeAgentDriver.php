<?php

namespace App\Drivers\Agent;

use App\Drivers\Agent\Contracts\AgentDriver;
use App\Models\Worktree;
use Symfony\Component\Process\Process;

class FakeAgentDriver implements AgentDriver
{
    /**
     * Options for configuring fake responses.
     */
    protected array $options = [];

    /**
     * Whether the agent should be available.
     */
    public bool $available = true;

    /**
     * Exception to throw when executing.
     */
    public ?\Throwable $shouldThrow = null;

    /**
     * Create a new fake agent driver instance.
     */
    public function __construct(array $options = [])
    {
        $this->options = $options;
    }

    /**
     * Spawn an agent process on a worktree with a given prompt.
     */
    public function spawn(Worktree $worktree, string $prompt, array $options = []): Process
    {
        $output = $this->options['output'] ?? $options['output'] ?? $this->getDefaultOutput($prompt);

        $command = ['echo', $output];

        $process = new Process($command);

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

        $process->stop();

        return true;
    }

    /**
     * Check if the agent binary is available on the system.
     */
    public function isAvailable(): bool
    {
        return $this->available;
    }

    /**
     * Get the list of supported AI models for this agent.
     */
    public function getSupportedModels(): array
    {
        return ['fake-model'];
    }

    /**
     * Execute a one-shot prompt and return the output.
     */
    public function executePrompt(string $prompt, array $options = []): string
    {
        if ($this->shouldThrow !== null) {
            throw $this->shouldThrow;
        }

        return $this->options['output'] ?? $options['output'] ?? $this->getDefaultOutput($prompt);
    }

    /**
     * Get the binary path for this agent.
     */
    public function getBinaryPath(): string
    {
        return 'fake';
    }

    /**
     * Get default output for the fake agent.
     */
    protected function getDefaultOutput(string $prompt): string
    {
        return "Fake agent processing: {$prompt}\nTask completed successfully!";
    }
}

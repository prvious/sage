<?php

namespace App\Drivers\Agent\Contracts;

use App\Models\Worktree;
use Symfony\Component\Process\Process;

interface AgentDriver
{
    /**
     * Spawn an agent process on a worktree with a given prompt.
     */
    public function spawn(Worktree $worktree, string $prompt, array $options = []): Process;

    /**
     * Execute a one-shot prompt and return the output.
     */
    public function executePrompt(string $prompt, array $options = []): string;

    /**
     * Stop a running agent process.
     */
    public function stop(Process $process): bool;

    /**
     * Check if the agent binary is available on the system.
     */
    public function isAvailable(): bool;

    /**
     * Get the list of supported AI models for this agent.
     */
    public function getSupportedModels(): array;

    /**
     * Get the binary path for this agent.
     */
    public function getBinaryPath(): string;
}

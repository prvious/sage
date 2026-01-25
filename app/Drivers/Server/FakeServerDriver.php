<?php

declare(strict_types=1);

namespace App\Drivers\Server;

use App\Drivers\Server\Contracts\ServerDriver;
use App\Models\Project;
use App\Models\Worktree;

final class FakeServerDriver implements ServerDriver
{
    /**
     * Options for configuring fake responses.
     */
    protected array $options = [];

    /**
     * Whether the server driver is available.
     */
    public bool $available = true;

    /**
     * Exception to throw when executing.
     */
    public ?\Throwable $shouldThrow = null;

    /**
     * Create a new fake server driver instance.
     */
    public function __construct(array $options = [])
    {
        $this->options = $options;
    }

    /**
     * Generate server configuration for a worktree.
     */
    public function generateConfig(Project $project, Worktree $worktree): string
    {
        if ($this->shouldThrow !== null) {
            throw $this->shouldThrow;
        }

        return $this->options['config'] ?? '';
    }

    /**
     * Reload the server configuration.
     */
    public function reload(): void
    {
        if ($this->shouldThrow !== null) {
            throw $this->shouldThrow;
        }

        // Fake reload - no-op
    }

    /**
     * Validate if the server driver is available on the system.
     */
    public function validate(): bool
    {
        if ($this->shouldThrow !== null) {
            throw $this->shouldThrow;
        }

        return $this->available;
    }

    /**
     * Start the server for a specific worktree.
     */
    public function start(Worktree $worktree): void
    {
        if ($this->shouldThrow !== null) {
            throw $this->shouldThrow;
        }

        // Fake start - no-op
    }

    /**
     * Stop the server for a specific worktree.
     */
    public function stop(Worktree $worktree): void
    {
        if ($this->shouldThrow !== null) {
            throw $this->shouldThrow;
        }

        // Fake stop - no-op
    }
}

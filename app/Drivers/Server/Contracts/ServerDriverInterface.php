<?php

namespace App\Drivers\Server\Contracts;

use App\Models\Project;
use App\Models\Worktree;

interface ServerDriverInterface
{
    /**
     * Generate server configuration for a worktree.
     */
    public function generateConfig(Project $project, Worktree $worktree): string;

    /**
     * Reload the server configuration.
     */
    public function reload(): void;

    /**
     * Validate if the server driver is available on the system.
     */
    public function validate(): bool;
}

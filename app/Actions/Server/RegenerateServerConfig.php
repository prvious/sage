<?php

namespace App\Actions\Server;

use App\Drivers\Server\ServerDriverManager;
use App\Models\Project;

final readonly class RegenerateServerConfig
{
    public function __construct(
        private ServerDriverManager $serverDriverManager,
    ) {}

    /**
     * Regenerate server configuration for a project and all its worktrees.
     */
    public function handle(Project $project): void
    {
        $driver = $this->serverDriverManager->driver($project->server_driver);

        foreach ($project->worktrees as $worktree) {
            $config = $driver->generateConfig($project, $worktree);
        }

        $driver->reload();
    }
}

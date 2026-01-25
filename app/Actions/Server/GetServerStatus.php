<?php

namespace App\Actions\Server;

use App\Drivers\Server\ServerManager;
use App\Models\Project;
use App\Support\SystemEnvironment;
use Illuminate\Support\Facades\Process;

final readonly class GetServerStatus
{
    public function __construct(
        private ServerManager $serverDriverManager,
        private SystemEnvironment $env,
    ) {}

    /**
     * Get the status of the server driver for a project.
     *
     * @return array{
     *     driver: string,
     *     installed: bool,
     *     running: bool,
     *     version: ?string,
     *     worktrees_count: int
     * }
     */
    public function handle(Project $project): array
    {
        $driver = $this->serverDriverManager->driver($project->server_driver);

        $installed = $driver->validate();
        $running = false;
        $version = null;

        if ($installed) {
            $running = $this->isRunning($project->server_driver);
            $version = $this->getVersion($project->server_driver);
        }

        return [
            'driver' => $project->server_driver,
            'installed' => $installed,
            'running' => $running,
            'version' => $version,
            'worktrees_count' => $project->worktrees()->count(),
        ];
    }

    /**
     * Check if a server driver is running.
     */
    private function isRunning(string $driver): bool
    {
        return match ($driver) {
            'artisan' => true,
            default => false,
        };
    }

    /**
     * Get the version of a server driver.
     */
    private function getVersion(string $driver): ?string
    {
        $result = match ($driver) {
            'artisan' => Process::env($this->env->all())->run('php artisan --version'),
            default => null,
        };

        if ($result && $result->successful()) {
            return trim($result->output());
        }

        return null;
    }
}

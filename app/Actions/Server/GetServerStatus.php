<?php

namespace App\Actions\Server;

use App\Drivers\Server\ServerDriverManager;
use App\Models\Project;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;

final readonly class GetServerStatus
{
    public function __construct(
        private ServerDriverManager $serverDriverManager,
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
            'caddy' => $this->isCaddyRunning(),
            'nginx' => $this->isNginxRunning(),
            'artisan' => true,
            default => false,
        };
    }

    /**
     * Check if Caddy is running.
     */
    private function isCaddyRunning(): bool
    {
        try {
            $response = Http::timeout(2)->get('http://localhost:2019/config/');

            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if Nginx is running.
     */
    private function isNginxRunning(): bool
    {
        $result = Process::run('pgrep nginx');

        return $result->successful();
    }

    /**
     * Get the version of a server driver.
     */
    private function getVersion(string $driver): ?string
    {
        $result = match ($driver) {
            'caddy' => Process::run('caddy version'),
            'nginx' => Process::run('nginx -v 2>&1'),
            'artisan' => Process::run('php artisan --version'),
            default => null,
        };

        if ($result && $result->successful()) {
            return trim($result->output());
        }

        return null;
    }
}

<?php

declare(strict_types=1);

namespace App\Drivers\Server;

use App\Drivers\Server\Contracts\ServerDriverInterface;
use App\Models\Project;
use App\Models\Worktree;
use Illuminate\Support\Facades\Process;
use RuntimeException;

final class ArtisanDriver implements ServerDriverInterface
{
    /**
     * Generate configuration (not applicable for artisan serve).
     * Returns empty string as artisan serve doesn't need config files.
     */
    public function generateConfig(Project $project, Worktree $worktree): string
    {
        // Artisan serve doesn't require config files
        return '';
    }

    /**
     * Reload server (not applicable for artisan serve).
     * Artisan serve processes are managed per worktree.
     */
    public function reload(): void
    {
        // Artisan serve doesn't have a reload concept
        // Each worktree runs its own process
    }

    /**
     * Validate if PHP is available on the system.
     */
    public function validate(): bool
    {
        $result = Process::run('php -v');

        return $result->successful();
    }

    /**
     * Start artisan serve for a specific worktree.
     */
    public function start(Worktree $worktree): void
    {
        $port = $this->getAvailablePort($worktree);
        $host = config('sage.artisan_server.host', '127.0.0.1');

        Process::path($worktree->path)
            ->start("php artisan serve --host={$host} --port={$port}");

        // Store the port in worktree metadata
        $worktree->update([
            'preview_url' => "http://{$host}:{$port}",
        ]);
    }

    /**
     * Stop artisan serve for a specific worktree.
     */
    public function stop(Worktree $worktree): void
    {
        // Find and kill the process running on the worktree's port
        $url = parse_url($worktree->preview_url);
        $port = $url['port'] ?? null;

        if ($port) {
            // Kill process listening on this port
            if (PHP_OS_FAMILY === 'Windows') {
                Process::run("netstat -ano | findstr :{$port}");
            } else {
                Process::run("lsof -ti:{$port} | xargs kill -9");
            }
        }
    }

    /**
     * Get an available port for the worktree.
     */
    protected function getAvailablePort(Worktree $worktree): int
    {
        $basePort = config('sage.artisan_server.base_port', 8000);
        $maxPort = config('sage.artisan_server.max_port', 8999);

        // Use worktree ID as offset for predictable port assignment
        $port = $basePort + ($worktree->id % 1000);

        // Check if port is available
        while (! $this->isPortAvailable($port) && $port <= $maxPort) {
            $port++;
        }

        if ($port > $maxPort) {
            throw new RuntimeException('No available ports in range '.$basePort.'-'.$maxPort);
        }

        return $port;
    }

    /**
     * Check if a port is available.
     */
    protected function isPortAvailable(int $port): bool
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $result = Process::run("netstat -ano | findstr :{$port}");
        } else {
            $result = Process::run("lsof -i:{$port}");
        }

        return ! $result->successful(); // Port is available if lsof/netstat fails
    }
}

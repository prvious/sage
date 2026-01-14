<?php

namespace App\Drivers\Server;

use App\Drivers\Server\Contracts\ServerDriverInterface;
use App\Models\Project;
use App\Models\Worktree;
use Illuminate\Support\Facades\Process;

class CaddyDriver implements ServerDriverInterface
{
    /**
     * Generate Caddy configuration for a worktree.
     */
    public function generateConfig(Project $project, Worktree $worktree): string
    {
        return <<<CADDY
{$worktree->preview_url} {
    root * {$worktree->path}/public
    php_fastcgi unix//var/run/php/php-fpm.sock
    file_server
    encode gzip
}
CADDY;
    }

    /**
     * Reload Caddy server configuration.
     */
    public function reload(): void
    {
        Process::run('caddy reload');
    }

    /**
     * Validate if Caddy is available on the system.
     */
    public function validate(): bool
    {
        $result = Process::run('which caddy');

        return $result->successful();
    }
}

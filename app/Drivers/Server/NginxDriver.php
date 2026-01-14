<?php

namespace App\Drivers\Server;

use App\Drivers\Server\Contracts\ServerDriverInterface;
use App\Models\Project;
use App\Models\Worktree;
use Illuminate\Support\Facades\Process;

class NginxDriver implements ServerDriverInterface
{
    /**
     * Generate Nginx configuration for a worktree.
     */
    public function generateConfig(Project $project, Worktree $worktree): string
    {
        return <<<NGINX
server {
    listen 80;
    server_name {$worktree->preview_url};
    root {$worktree->path}/public;

    index index.php;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
NGINX;
    }

    /**
     * Reload Nginx server configuration.
     */
    public function reload(): void
    {
        Process::run('nginx -s reload');
    }

    /**
     * Validate if Nginx is available on the system.
     */
    public function validate(): bool
    {
        $result = Process::run('which nginx');

        return $result->successful();
    }
}

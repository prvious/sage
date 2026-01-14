<?php

namespace App\Drivers;

use App\Contracts\ServerDriver;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

final class NginxDriver implements ServerDriver
{
    private string $configPath;

    private string $reloadCommand;

    private string $testCommand;

    public function __construct()
    {
        $this->configPath = config('sage.server.nginx.config_path', '/etc/nginx/sage.d');
        $this->reloadCommand = config('sage.server.nginx.reload_command', 'nginx -s reload');
        $this->testCommand = config('sage.server.nginx.test_command', 'nginx -t');
    }

    /**
     * Add a virtual host configuration
     */
    public function addVirtualHost(string $domain, string $documentRoot, int $port = 8000): bool
    {
        try {
            $configFile = $this->getConfigFilePath($domain);

            // Generate nginx server block
            $config = $this->generateServerBlock($domain, $port);

            // Ensure config directory exists
            if (! is_dir($this->configPath)) {
                if (! @mkdir($this->configPath, 0755, true)) {
                    return false;
                }
            }

            // Write config file
            if (file_put_contents($configFile, $config) === false) {
                return false;
            }

            // Test configuration
            $testResult = Process::run($this->testCommand);

            if (! $testResult->successful()) {
                // Rollback: delete the config file
                @unlink($configFile);

                return false;
            }

            // Reload nginx
            $reloadResult = Process::run($this->reloadCommand);

            if (! $reloadResult->successful()) {
                // Rollback: delete the config file
                @unlink($configFile);

                return false;
            }

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Remove a virtual host configuration
     */
    public function removeVirtualHost(string $domain): bool
    {
        try {
            $configFile = $this->getConfigFilePath($domain);

            if (! file_exists($configFile)) {
                return true; // Already removed
            }

            // Delete config file
            if (! @unlink($configFile)) {
                return false;
            }

            // Test configuration
            $testResult = Process::run($this->testCommand);

            if (! $testResult->successful()) {
                return false;
            }

            // Reload nginx
            $reloadResult = Process::run($this->reloadCommand);

            return $reloadResult->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * List all managed virtual hosts
     */
    public function listVirtualHosts(): array
    {
        if (! is_dir($this->configPath)) {
            return [];
        }

        $files = glob($this->configPath.'/*.conf');

        return array_map(function ($file) {
            return [
                'domain' => basename($file, '.conf'),
                'config_file' => $file,
            ];
        }, $files);
    }

    /**
     * Check if Nginx is available
     */
    public function isAvailable(): bool
    {
        try {
            // Check if nginx is running
            $result = Process::run('pgrep nginx');

            if (! $result->successful()) {
                return false;
            }

            // Check if config directory exists or can be created
            if (! is_dir($this->configPath)) {
                if (! @mkdir($this->configPath, 0755, true)) {
                    return false;
                }
            }

            // Check if directory is writable
            return is_writable($this->configPath);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Test if Nginx configuration is valid
     */
    public function testConfiguration(): bool
    {
        if (! $this->isAvailable()) {
            return false;
        }

        $testDomain = 'test-'.Str::random(8).'.local';

        try {
            // Try to add a test vhost
            $added = $this->addVirtualHost($testDomain, '/tmp', 9999);

            if ($added) {
                // Try to remove it
                $removed = $this->removeVirtualHost($testDomain);

                return $removed;
            }

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get server information
     */
    public function getServerInfo(): array
    {
        try {
            $versionResult = Process::run('nginx -v 2>&1');
            $version = 'unknown';

            if ($versionResult->successful()) {
                preg_match('/nginx\/(.+)/', $versionResult->output(), $matches);
                $version = $matches[1] ?? 'unknown';
            }

            return [
                'name' => 'Nginx',
                'version' => $version,
                'available' => $this->isAvailable(),
                'config_path' => $this->configPath,
            ];
        } catch (\Exception $e) {
            return [
                'name' => 'Nginx',
                'available' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Generate Nginx server block configuration
     */
    private function generateServerBlock(string $domain, int $port): string
    {
        return <<<NGINX
server {
    listen 80;
    server_name {$domain};

    location / {
        proxy_pass http://localhost:{$port};
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
    }
}
NGINX;
    }

    /**
     * Get the config file path for a domain
     */
    private function getConfigFilePath(string $domain): string
    {
        return $this->configPath.'/'.Str::slug($domain).'.conf';
    }
}

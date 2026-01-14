<?php

namespace App\Drivers;

use App\Contracts\ServerDriver;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

final class CaddyDriver implements ServerDriver
{
    private string $adminUrl;

    private string $serverName;

    public function __construct()
    {
        $this->adminUrl = config('sage.server.caddy.admin_url', 'http://localhost:2019');
        $this->serverName = config('sage.server.caddy.server_name', 'sage');
    }

    /**
     * Add a virtual host configuration via Caddy Admin API
     */
    public function addVirtualHost(string $domain, string $documentRoot, int $port = 8000): bool
    {
        try {
            $routeId = 'sage_'.Str::slug($domain);

            $routeConfig = [
                '@id' => $routeId,
                'match' => [
                    [
                        'host' => [$domain],
                    ],
                ],
                'handle' => [
                    [
                        'handler' => 'reverse_proxy',
                        'upstreams' => [
                            ['dial' => "localhost:{$port}"],
                        ],
                        'headers' => [
                            'request' => [
                                'set' => [
                                    'Host' => ['{http.request.host}'],
                                    'X-Real-IP' => ['{http.request.remote_ip}'],
                                    'X-Forwarded-For' => ['{http.request.remote_ip}'],
                                    'X-Forwarded-Proto' => ['{http.request.scheme}'],
                                ],
                            ],
                        ],
                    ],
                ],
            ];

            $response = Http::timeout(5)
                ->post("{$this->adminUrl}/config/apps/http/servers/{$this->serverName}/routes", $routeConfig);

            return $response->successful();
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
            $routes = $this->listVirtualHosts();

            foreach ($routes as $route) {
                if (isset($route['match'][0]['host']) && in_array($domain, $route['match'][0]['host'])) {
                    $routeId = $route['@id'] ?? null;

                    if ($routeId) {
                        $response = Http::timeout(5)
                            ->delete("{$this->adminUrl}/config/apps/http/servers/{$this->serverName}/routes/{$routeId}");

                        return $response->successful();
                    }
                }
            }

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * List all managed virtual hosts
     */
    public function listVirtualHosts(): array
    {
        try {
            $response = Http::timeout(5)
                ->get("{$this->adminUrl}/config/apps/http/servers/{$this->serverName}/routes");

            if ($response->successful()) {
                return $response->json() ?? [];
            }

            return [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Check if Caddy is available
     */
    public function isAvailable(): bool
    {
        try {
            $response = Http::timeout(2)->get("{$this->adminUrl}/config/");

            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Test if Caddy configuration is valid
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
            $response = Http::timeout(2)->get("{$this->adminUrl}/config/");

            if ($response->successful()) {
                $config = $response->json();

                return [
                    'name' => 'Caddy',
                    'version' => $config['version'] ?? 'unknown',
                    'available' => true,
                    'admin_url' => $this->adminUrl,
                ];
            }

            return [
                'name' => 'Caddy',
                'available' => false,
            ];
        } catch (\Exception $e) {
            return [
                'name' => 'Caddy',
                'available' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}

<?php

namespace App\Services;

use App\Drivers\CaddyDriver;
use App\Drivers\NginxDriver;

final class ServerDetector
{
    /**
     * Detect all available server drivers
     */
    public function detectAvailable(): array
    {
        $available = [];

        $drivers = [
            'caddy' => new CaddyDriver,
            'nginx' => new NginxDriver,
        ];

        foreach ($drivers as $name => $driver) {
            if ($driver->isAvailable()) {
                $available[] = [
                    'name' => $name,
                    'driver' => $driver,
                    'info' => $driver->getServerInfo(),
                ];
            }
        }

        return $available;
    }

    /**
     * Suggest the best driver based on what's available
     */
    public function suggestBest(): ?string
    {
        $available = $this->detectAvailable();

        if (empty($available)) {
            return null;
        }

        // Prefer Caddy for its zero-downtime config changes
        foreach ($available as $driver) {
            if ($driver['name'] === 'caddy') {
                return 'caddy';
            }
        }

        // Otherwise use the first available driver
        return $available[0]['name'];
    }

    /**
     * Check if a specific driver is available
     */
    public function isDriverAvailable(string $driver): bool
    {
        $available = $this->detectAvailable();

        foreach ($available as $item) {
            if ($item['name'] === $driver) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get detailed information about all drivers
     */
    public function getAllDriverInfo(): array
    {
        $drivers = [
            'caddy' => new CaddyDriver,
            'nginx' => new NginxDriver,
        ];

        $info = [];

        foreach ($drivers as $name => $driver) {
            $info[$name] = $driver->getServerInfo();
        }

        return $info;
    }
}

<?php

namespace App\Drivers\Server;

use Illuminate\Support\Manager as BaseManager;

class Manager extends BaseManager
{
    /**
     * Get the default driver name.
     */
    public function getDefaultDriver(): string
    {
        return 'caddy';
    }

    /**
     * Create an instance of the Caddy driver.
     */
    public function createCaddyDriver(): CaddyDriver
    {
        return new CaddyDriver;
    }

    /**
     * Create an instance of the Nginx driver.
     */
    public function createNginxDriver(): NginxDriver
    {
        return new NginxDriver;
    }

    /**
     * Create an instance of the Artisan driver.
     */
    public function createArtisanDriver(): ArtisanDriver
    {
        return new ArtisanDriver;
    }
}

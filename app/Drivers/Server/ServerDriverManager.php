<?php

namespace App\Drivers\Server;

use App\Drivers\CaddyDriver;
use App\Drivers\NginxDriver;
use Illuminate\Support\Manager;

class ServerDriverManager extends Manager
{
    /**
     * Create an instance of the Caddy driver
     */
    public function createCaddyDriver(): CaddyDriver
    {
        return $this->container->make(CaddyDriver::class);
    }

    /**
     * Create an instance of the Nginx driver
     */
    public function createNginxDriver(): NginxDriver
    {
        return $this->container->make(NginxDriver::class);
    }

    /**
     * Get the default driver name
     */
    public function getDefaultDriver(): string
    {
        return config('sage.server.default', 'caddy');
    }
}

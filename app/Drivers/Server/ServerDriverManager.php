<?php

namespace App\Drivers\Server;

use Illuminate\Support\Manager;

class ServerDriverManager extends Manager
{
    /**
     * Create an instance of the Artisan driver
     */
    public function createArtisanDriver(): ArtisanDriver
    {
        return $this->container->make(ArtisanDriver::class);
    }

    /**
     * Get the default driver name
     */
    public function getDefaultDriver(): string
    {
        return 'artisan';
    }
}

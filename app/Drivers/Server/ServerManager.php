<?php

namespace App\Drivers\Server;

use Illuminate\Support\Manager;

class ServerManager extends Manager
{
    /**
     * The fake driver instance.
     */
    protected static ?FakeServerDriver $fakeDriver = null;

    /**
     * Swap the server manager to use a fake driver for testing.
     */
    public static function fake(array $options = []): FakeServerDriver
    {
        static::$fakeDriver = new FakeServerDriver($options);

        return static::$fakeDriver;
    }

    /**
     * Create an instance of the Artisan driver
     */
    public function createArtisanDriver(): Contracts\ServerDriver
    {
        if (static::$fakeDriver !== null) {
            return static::$fakeDriver;
        }

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

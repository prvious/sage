<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \App\Drivers\Server\Contracts\ServerDriver driver(string|null $driver = null)
 * @method static bool addVirtualHost(string $domain, string $documentRoot, int $port = 8000)
 * @method static bool removeVirtualHost(string $domain)
 * @method static array listVirtualHosts()
 * @method static bool isAvailable()
 * @method static bool testConfiguration()
 * @method static array getServerInfo()
 *
 * @see \App\Drivers\Server\ServerManager
 */
class ServerDriver extends Facade
{
    /**
     * Get the registered name of the component
     */
    protected static function getFacadeAccessor(): string
    {
        return 'server.driver';
    }
}

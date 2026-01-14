<?php

namespace App\Contracts;

interface ServerDriver
{
    /**
     * Add a virtual host configuration
     */
    public function addVirtualHost(string $domain, string $documentRoot, int $port = 8000): bool;

    /**
     * Remove a virtual host configuration
     */
    public function removeVirtualHost(string $domain): bool;

    /**
     * List all managed virtual hosts
     */
    public function listVirtualHosts(): array;

    /**
     * Check if the server is available and accessible
     */
    public function isAvailable(): bool;

    /**
     * Test if the server configuration is valid and writable
     */
    public function testConfiguration(): bool;

    /**
     * Get server information
     */
    public function getServerInfo(): array;
}

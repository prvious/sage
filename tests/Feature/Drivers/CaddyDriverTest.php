<?php

use App\Drivers\CaddyDriver;
use Illuminate\Support\Facades\Http;

it('can check if caddy is available', function () {
    Http::fake([
        'localhost:2019/config/*' => Http::response(['version' => '2.7.4'], 200),
    ]);

    $driver = new CaddyDriver;

    expect($driver->isAvailable())->toBeTrue();
});

it('returns false when caddy is not available', function () {
    Http::fake([
        'localhost:2019/config/*' => Http::response([], 500),
    ]);

    $driver = new CaddyDriver;

    expect($driver->isAvailable())->toBeFalse();
});

it('can get server info', function () {
    Http::fake([
        'localhost:2019/config/*' => Http::response(['version' => '2.7.4'], 200),
    ]);

    $driver = new CaddyDriver;
    $info = $driver->getServerInfo();

    expect($info)->toBeArray()
        ->and($info)->toHaveKey('name')
        ->and($info['name'])->toBe('Caddy')
        ->and($info)->toHaveKey('available');
});

it('can add virtual host via API', function () {
    Http::fake([
        'localhost:2019/config/apps/http/servers/sage/routes' => Http::response([], 200),
    ]);

    $driver = new CaddyDriver;
    $result = $driver->addVirtualHost('test.local', '/var/www/html', 8000);

    expect($result)->toBeTrue();

    Http::assertSent(function ($request) {
        return $request->url() === 'http://localhost:2019/config/apps/http/servers/sage/routes' &&
               $request->method() === 'POST';
    });
});

it('can list virtual hosts', function () {
    Http::fake([
        'localhost:2019/config/apps/http/servers/sage/routes' => Http::response([
            [
                '@id' => 'sage_test-local',
                'match' => [['host' => ['test.local']]],
            ],
        ], 200),
    ]);

    $driver = new CaddyDriver;
    $vhosts = $driver->listVirtualHosts();

    expect($vhosts)->toBeArray()
        ->and($vhosts)->toHaveCount(1);
});

it('can remove virtual host', function () {
    Http::fake([
        'localhost:2019/config/apps/http/servers/sage/routes' => Http::response([
            [
                '@id' => 'sage_test-local',
                'match' => [['host' => ['test.local']]],
            ],
        ], 200),
        'localhost:2019/config/apps/http/servers/sage/routes/sage_test-local' => Http::response([], 200),
    ]);

    $driver = new CaddyDriver;
    $result = $driver->removeVirtualHost('test.local');

    expect($result)->toBeTrue();

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'sage_test-local') &&
               $request->method() === 'DELETE';
    });
});

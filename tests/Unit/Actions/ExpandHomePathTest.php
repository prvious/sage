<?php

use App\Actions\ExpandHomePath;
use App\Support\SystemEnvironment;

beforeEach(function () {
    SystemEnvironment::clearFake();
});

afterEach(function () {
    SystemEnvironment::clearFake();
});

it('expands tilde to home directory with faked HOME variable', function () {
    SystemEnvironment::fake([
        'HOME' => '/home/testuser',
    ]);

    $action = app(ExpandHomePath::class);
    $result = $action->handle('~');

    expect($result)->toBe('/home/testuser');
});

it('expands tilde with path to home directory with faked HOME variable', function () {
    SystemEnvironment::fake([
        'HOME' => '/home/testuser',
    ]);

    $action = app(ExpandHomePath::class);
    $result = $action->handle('~/Documents');

    expect($result)->toBe('/home/testuser/Documents');
});

it('expands tilde using USERPROFILE on Windows', function () {
    SystemEnvironment::fake([
        'USERPROFILE' => 'C:\\Users\\testuser',
    ]);

    $action = app(ExpandHomePath::class);
    $result = $action->handle('~');

    expect($result)->toBe('C:\\Users\\testuser');
});

it('expands tilde with path using USERPROFILE on Windows', function () {
    SystemEnvironment::fake([
        'USERPROFILE' => 'C:\\Users\\testuser',
    ]);

    $action = app(ExpandHomePath::class);
    $result = $action->handle('~/Documents');

    expect($result)->toBe('C:\\Users\\testuser/Documents');
});

it('prefers HOME over USERPROFILE when both are present', function () {
    SystemEnvironment::fake([
        'HOME' => '/home/testuser',
        'USERPROFILE' => 'C:\\Users\\testuser',
    ]);

    $action = app(ExpandHomePath::class);
    $result = $action->handle('~');

    expect($result)->toBe('/home/testuser');
});

it('returns path unchanged when no HOME or USERPROFILE is available', function () {
    SystemEnvironment::fake([]);

    $action = app(ExpandHomePath::class);
    $result = $action->handle('~/Documents');

    expect($result)->toBe('~/Documents');
});

it('returns absolute path unchanged', function () {
    SystemEnvironment::fake([
        'HOME' => '/home/testuser',
    ]);

    $action = app(ExpandHomePath::class);
    $result = $action->handle('/var/www/myproject');

    expect($result)->toBe('/var/www/myproject');
});

it('returns relative path unchanged', function () {
    SystemEnvironment::fake([
        'HOME' => '/home/testuser',
    ]);

    $action = app(ExpandHomePath::class);
    $result = $action->handle('myproject/folder');

    expect($result)->toBe('myproject/folder');
});

it('handles empty HOME variable gracefully', function () {
    SystemEnvironment::fake([
        'HOME' => '',
    ]);

    $action = app(ExpandHomePath::class);
    $result = $action->handle('~/Documents');

    expect($result)->toBe('~/Documents');
});

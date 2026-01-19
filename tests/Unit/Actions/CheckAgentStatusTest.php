<?php

use App\Actions\CheckAgentAuthenticated;
use App\Actions\CheckAgentInstalled;
use App\Actions\CheckAgentStatus;

it('returns installed false when installation check fails', function () {
    $mockInstalled = Mockery::mock(CheckAgentInstalled::class);
    $mockInstalled->shouldReceive('handle')->andReturn([
        'installed' => false,
        'path' => null,
        'error_message' => 'Binary not found in PATH',
    ]);

    $mockAuthenticated = Mockery::mock(CheckAgentAuthenticated::class);

    $action = new CheckAgentStatus($mockInstalled, $mockAuthenticated);
    $result = $action->handle();

    expect($result['installed'])->toBeFalse()
        ->and($result['authenticated'])->toBeFalse()
        ->and($result['auth_type'])->toBeNull()
        ->and($result['error_message'])->toBe('Binary not found in PATH');
});

it('returns authenticated false when authentication check fails', function () {
    $mockInstalled = Mockery::mock(CheckAgentInstalled::class);
    $mockInstalled->shouldReceive('handle')->andReturn([
        'installed' => true,
        'path' => '/usr/local/bin/claude',
        'error_message' => null,
    ]);

    $mockAuthenticated = Mockery::mock(CheckAgentAuthenticated::class);
    $mockAuthenticated->shouldReceive('handle')->with('/usr/local/bin/claude')->andReturn([
        'authenticated' => false,
        'auth_type' => 'none',
        'error_message' => 'Not authenticated',
    ]);

    $action = new CheckAgentStatus($mockInstalled, $mockAuthenticated);
    $result = $action->handle();

    expect($result['installed'])->toBeTrue()
        ->and($result['authenticated'])->toBeFalse()
        ->and($result['auth_type'])->toBe('none')
        ->and($result['error_message'])->toBe('Not authenticated');
});

it('returns both true when both checks pass', function () {
    $mockInstalled = Mockery::mock(CheckAgentInstalled::class);
    $mockInstalled->shouldReceive('handle')->andReturn([
        'installed' => true,
        'path' => '/usr/local/bin/claude',
        'error_message' => null,
    ]);

    $mockAuthenticated = Mockery::mock(CheckAgentAuthenticated::class);
    $mockAuthenticated->shouldReceive('handle')->with('/usr/local/bin/claude')->andReturn([
        'authenticated' => true,
        'auth_type' => 'cli',
        'error_message' => null,
    ]);

    $action = new CheckAgentStatus($mockInstalled, $mockAuthenticated);
    $result = $action->handle();

    expect($result['installed'])->toBeTrue()
        ->and($result['authenticated'])->toBeTrue()
        ->and($result['auth_type'])->toBe('cli')
        ->and($result['error_message'])->toBeNull();
});

it('includes auth_type in response', function () {
    $mockInstalled = Mockery::mock(CheckAgentInstalled::class);
    $mockInstalled->shouldReceive('handle')->andReturn([
        'installed' => true,
        'path' => '/usr/local/bin/claude',
        'error_message' => null,
    ]);

    $mockAuthenticated = Mockery::mock(CheckAgentAuthenticated::class);
    $mockAuthenticated->shouldReceive('handle')->with('/usr/local/bin/claude')->andReturn([
        'authenticated' => true,
        'auth_type' => 'api_key',
        'error_message' => null,
    ]);

    $action = new CheckAgentStatus($mockInstalled, $mockAuthenticated);
    $result = $action->handle();

    expect($result)->toHaveKey('auth_type')
        ->and($result['auth_type'])->toBe('api_key');
});

it('passes binary path from installation check to authentication check', function () {
    $binaryPath = '/custom/path/to/claude';

    $mockInstalled = Mockery::mock(CheckAgentInstalled::class);
    $mockInstalled->shouldReceive('handle')->andReturn([
        'installed' => true,
        'path' => $binaryPath,
        'error_message' => null,
    ]);

    $mockAuthenticated = Mockery::mock(CheckAgentAuthenticated::class);
    $mockAuthenticated->shouldReceive('handle')->with($binaryPath)->once()->andReturn([
        'authenticated' => true,
        'auth_type' => 'cli',
        'error_message' => null,
    ]);

    $action = new CheckAgentStatus($mockInstalled, $mockAuthenticated);
    $action->handle();
});

it('returns correct structure for all response types', function () {
    $mockInstalled = Mockery::mock(CheckAgentInstalled::class);
    $mockInstalled->shouldReceive('handle')->andReturn([
        'installed' => true,
        'path' => '/usr/local/bin/claude',
        'error_message' => null,
    ]);

    $mockAuthenticated = Mockery::mock(CheckAgentAuthenticated::class);
    $mockAuthenticated->shouldReceive('handle')->andReturn([
        'authenticated' => true,
        'auth_type' => 'cli',
        'error_message' => null,
    ]);

    $action = new CheckAgentStatus($mockInstalled, $mockAuthenticated);
    $result = $action->handle();

    expect($result)->toHaveKeys(['installed', 'authenticated', 'auth_type', 'error_message'])
        ->and($result['installed'])->toBeIn([true, false])
        ->and($result['authenticated'])->toBeIn([true, false]);

    if ($result['error_message'] !== null) {
        expect($result['error_message'])->toBeString();
    }
});

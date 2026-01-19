<?php

use App\Actions\CheckAgentInstalled;
use App\Actions\FindCommandPath;

it('returns installed true when binary found via FindCommandPath', function () {
    $mockFinder = Mockery::mock(\Symfony\Component\Process\ExecutableFinder::class);
    $mockFinder->shouldReceive('find')->with('claude')->andReturn('/usr/local/bin/claude');

    $mockFindCommandPath = new FindCommandPath($mockFinder);
    $action = new CheckAgentInstalled($mockFindCommandPath);

    $result = $action->handle();

    expect($result['installed'])->toBeTrue()
        ->and($result['path'])->toBe('/usr/local/bin/claude')
        ->and($result['error_message'])->toBeNull();
});

it('returns installed false when binary not found', function () {
    $mockFinder = Mockery::mock(\Symfony\Component\Process\ExecutableFinder::class);
    $mockFinder->shouldReceive('find')->with('claude')->andReturn(null);

    $mockFindCommandPath = new FindCommandPath($mockFinder);
    $action = new CheckAgentInstalled($mockFindCommandPath);

    $result = $action->handle();

    expect($result['installed'])->toBeFalse()
        ->and($result['path'])->toBeNull()
        ->and($result['error_message'])->toBe('Binary not found in PATH');
});

it('returns correct path in response', function () {
    $mockFinder = Mockery::mock(\Symfony\Component\Process\ExecutableFinder::class);
    $mockFinder->shouldReceive('find')->with('claude')->andReturn('/custom/path/to/claude');

    $mockFindCommandPath = new FindCommandPath($mockFinder);
    $action = new CheckAgentInstalled($mockFindCommandPath);

    $result = $action->handle();

    expect($result['path'])->toBe('/custom/path/to/claude');
});

it('returns appropriate error messages when not found', function () {
    $mockFinder = Mockery::mock(\Symfony\Component\Process\ExecutableFinder::class);
    $mockFinder->shouldReceive('find')->with('claude')->andReturn(null);

    $mockFindCommandPath = new FindCommandPath($mockFinder);
    $action = new CheckAgentInstalled($mockFindCommandPath);

    $result = $action->handle();

    expect($result['error_message'])->toBeString()
        ->and($result['error_message'])->not->toBeEmpty();
});

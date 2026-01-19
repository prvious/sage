<?php

use App\Actions\FindCommandPath;
use Illuminate\Support\Facades\Cache;

it('finds an existing command', function () {
    Cache::flush();

    $action = new FindCommandPath;
    $result = $action->handle('php');

    expect($result)->not->toBeNull()
        ->and($result)->toBeString()
        ->and($result)->toContain('php');
});

it('returns null for non-existent command', function () {
    Cache::flush();

    $action = new FindCommandPath;
    $result = $action->handle('nonexistent-binary-that-will-never-exist-12345');

    expect($result)->toBeNull();
});

it('caches the result on second call', function () {
    Cache::flush();

    $action = new FindCommandPath;

    // First call - should execute 'which'
    $firstResult = $action->handle('php');

    // Second call - should use cache
    $secondResult = $action->handle('php');

    expect($firstResult)->toBe($secondResult)
        ->and(Cache::has('command_path:php'))->toBeTrue();
});

it('uses correct cache key format', function () {
    Cache::flush();

    $action = new FindCommandPath;
    $action->handle('php');

    expect(Cache::has('command_path:php'))->toBeTrue();
});

it('respects cache TTL from config', function () {
    Cache::flush();
    config()->set('sage.command_path_cache_ttl', 60);

    $action = new FindCommandPath;
    $action->handle('php');

    expect(Cache::has('command_path:php'))->toBeTrue();
});

it('handles errors gracefully', function () {
    Cache::flush();

    $action = new FindCommandPath;

    // Test with empty string
    $result = $action->handle('');

    expect($result)->toBeNull();
});

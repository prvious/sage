<?php

use App\Actions\FindCommandPath;
use Illuminate\Support\Facades\Cache;

it('integrates with real which command', function () {
    Cache::flush();

    $action = new FindCommandPath;
    $result = $action->handle('php');

    expect($result)->not->toBeNull()
        ->and($result)->toStartWith('/')
        ->and(file_exists($result))->toBeTrue();
});

it('finds common binaries', function ($binary) {
    Cache::flush();

    $action = new FindCommandPath;
    $result = $action->handle($binary);

    expect($result)->not->toBeNull()
        ->and($result)->toBeString();
})->with(['php', 'sh']);

it('caches result across multiple calls', function () {
    Cache::flush();

    $action = new FindCommandPath;

    $first = $action->handle('php');
    $second = $action->handle('php');
    $third = $action->handle('php');

    expect($first)->toBe($second)
        ->and($second)->toBe($third);
});

it('can clear cache and re-resolve', function () {
    Cache::flush();

    $action = new FindCommandPath;

    $first = $action->handle('php');

    // Clear cache
    Cache::forget('command_path:php');

    $second = $action->handle('php');

    expect($first)->toBe($second);
});

it('uses custom cache TTL from config', function () {
    Cache::flush();
    config()->set('sage.command_path_cache_ttl', 120);

    $action = new FindCommandPath;
    $action->handle('php');

    expect(Cache::has('command_path:php'))->toBeTrue();
});

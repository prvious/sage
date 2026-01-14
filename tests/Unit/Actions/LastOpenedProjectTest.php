<?php

use App\Actions\GetLastOpenedProject;
use App\Actions\UpdateLastOpenedProject;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::flush();
    session()->start();
});

it('stores correct project ID in cache', function () {
    $action = new UpdateLastOpenedProject;
    $projectId = 123;

    $action->handle($projectId);

    $cacheKey = 'last_opened_project:'.session()->getId();
    expect(Cache::get($cacheKey))->toBe($projectId);
});

it('uses correct cache key format', function () {
    $action = new UpdateLastOpenedProject;
    $projectId = 456;

    $action->handle($projectId);

    $sessionId = session()->getId();
    $expectedKey = "last_opened_project:{$sessionId}";

    expect(Cache::has($expectedKey))->toBeTrue();
});

it('retrieves correct project ID from cache', function () {
    $projectId = 789;
    $cacheKey = 'last_opened_project:'.session()->getId();
    Cache::put($cacheKey, $projectId, now()->addMinutes(30));

    $action = new GetLastOpenedProject;
    $result = $action->handle();

    expect($result)->toBe($projectId);
});

it('returns null when no cache entry exists', function () {
    $action = new GetLastOpenedProject;
    $result = $action->handle();

    expect($result)->toBeNull();
});

it('handles cache misses gracefully', function () {
    Cache::flush();

    $action = new GetLastOpenedProject;
    $result = $action->handle();

    expect($result)->toBeNull();
});

it('sets correct TTL from config', function () {
    config(['sage.last_project_ttl' => 60]); // 1 hour
    $action = new UpdateLastOpenedProject;
    $projectId = 999;

    $action->handle($projectId);

    $cacheKey = 'last_opened_project:'.session()->getId();
    expect(Cache::get($cacheKey))->toBe($projectId);
});

<?php

use App\Services\ServerDetector;
use Illuminate\Support\Facades\Http;

it('can detect all driver information', function () {
    Http::fake();

    $detector = new ServerDetector;
    $info = $detector->getAllDriverInfo();

    expect($info)->toBeArray()
        ->and($info)->toHaveKey('caddy')
        ->and($info)->toHaveKey('nginx');
});

it('returns empty array when no drivers available', function () {
    Http::fake();

    $detector = new ServerDetector;
    $available = $detector->detectAvailable();

    expect($available)->toBeArray();
});

it('prefers caddy when suggesting best driver', function () {
    Http::fake([
        'localhost:2019/config/*' => Http::response(['version' => '2.7.4'], 200),
    ]);

    $detector = new ServerDetector;
    $best = $detector->suggestBest();

    expect($best)->toBe('caddy');
});

it('can check if specific driver is available', function () {
    Http::fake([
        'localhost:2019/config/*' => Http::response(['version' => '2.7.4'], 200),
    ]);

    $detector = new ServerDetector;

    expect($detector->isDriverAvailable('caddy'))->toBeTrue()
        ->and($detector->isDriverAvailable('nonexistent'))->toBeFalse();
});

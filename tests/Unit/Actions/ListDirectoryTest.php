<?php

use App\Actions\ExpandHomePath;
use App\Actions\ListDirectory;

test('lists directories in valid path', function () {
    $expandHomePath = new ExpandHomePath;
    $action = new ListDirectory($expandHomePath);

    $homePath = getenv('HOME') ?: getenv('USERPROFILE');
    $result = $action->handle($homePath);

    expect($result)->toBeArray()
        ->toHaveKeys(['directories', 'breadcrumbs']);
    expect($result['directories'])->toBeArray();
    expect($result['breadcrumbs'])->toBeArray();
});

test('returns empty directories for non-existent path', function () {
    $expandHomePath = new ExpandHomePath;
    $action = new ListDirectory($expandHomePath);

    $result = $action->handle('/nonexistent/path/that/does/not/exist');

    expect($result)->toBeArray()
        ->toHaveKeys(['directories', 'breadcrumbs']);
    expect($result['directories'])->toBe([]);
});

test('generates breadcrumbs correctly', function () {
    $expandHomePath = new ExpandHomePath;
    $action = new ListDirectory($expandHomePath);

    $result = $action->handle('/var/www/html');

    expect($result['breadcrumbs'])->toBeArray();
    if (! empty($result['breadcrumbs'])) {
        expect($result['breadcrumbs'][0])->toHaveKeys(['name', 'path']);
    }
});

test('expands tilde in path before listing', function () {
    $expandHomePath = new ExpandHomePath;
    $action = new ListDirectory($expandHomePath);

    $result = $action->handle('~');

    expect($result)->toBeArray()
        ->toHaveKeys(['directories', 'breadcrumbs']);
});

test('returns only directories not files', function () {
    $expandHomePath = new ExpandHomePath;
    $action = new ListDirectory($expandHomePath);

    $homePath = getenv('HOME') ?: getenv('USERPROFILE');
    $result = $action->handle($homePath);

    foreach ($result['directories'] as $item) {
        expect($item)->toHaveKey('type')
            ->and($item['type'])->toBe('directory');
    }
});

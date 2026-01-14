<?php

use App\Actions\ExpandHomePath;

test('expands tilde to home directory', function () {
    $action = new ExpandHomePath;
    $result = $action->handle('~');

    $homePath = getenv('HOME') ?: getenv('USERPROFILE');
    if ($homePath !== false) {
        expect($result)->toBe($homePath);
    } else {
        expect($result)->toBe('~');
    }
});

test('expands tilde with path to home directory', function () {
    $action = new ExpandHomePath;
    $result = $action->handle('~/Documents');

    $homePath = getenv('HOME') ?: getenv('USERPROFILE');
    if ($homePath !== false) {
        expect($result)->toBe($homePath.'/Documents');
    } else {
        expect($result)->toBe('~/Documents');
    }
});

test('returns absolute path unchanged', function () {
    $action = new ExpandHomePath;
    $result = $action->handle('/var/www/myproject');

    expect($result)->toBe('/var/www/myproject');
});

test('returns relative path unchanged', function () {
    $action = new ExpandHomePath;
    $result = $action->handle('myproject/folder');

    expect($result)->toBe('myproject/folder');
});

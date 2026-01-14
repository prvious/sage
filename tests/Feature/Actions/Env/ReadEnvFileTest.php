<?php

use App\Actions\Env\ReadEnvFile;

it('can read an env file', function () {
    $path = storage_path('test.env');
    file_put_contents($path, "APP_NAME=Sage\nDB_PASSWORD=secret");

    $action = new ReadEnvFile;
    $result = $action->handle($path);

    expect($result)->toBeArray()
        ->and($result)->toHaveKey('APP_NAME')
        ->and($result['APP_NAME']['value'])->toBe('Sage');

    unlink($path);
});

it('throws exception if file does not exist', function () {
    $action = new ReadEnvFile;
    $action->handle('/nonexistent/path/.env');
})->throws(RuntimeException::class, 'Environment file not found');

it('throws exception if file is not readable', function () {
    $path = storage_path('test.env');
    file_put_contents($path, 'APP_NAME=Sage');
    chmod($path, 0000);

    $action = new ReadEnvFile;

    try {
        $action->handle($path);
    } finally {
        chmod($path, 0644);
        unlink($path);
    }
})->throws(RuntimeException::class, 'not readable');

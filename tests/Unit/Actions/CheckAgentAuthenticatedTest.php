<?php

use App\Actions\CheckAgentAuthenticated;
use App\Support\SystemEnvironment;
use Illuminate\Support\Facades\Process;

beforeEach(function () {
    SystemEnvironment::clearFake();
});

afterEach(function () {
    SystemEnvironment::clearFake();
});

it('authenticates via API key when ANTHROPIC_API_KEY is present', function () {
    SystemEnvironment::fake([
        'ANTHROPIC_API_KEY' => 'sk-ant-test-key-12345',
    ]);

    $action = app(CheckAgentAuthenticated::class);
    $result = $action->handle();

    expect($result['authenticated'])->toBeTrue()
        ->and($result['auth_type'])->toBe('api_key')
        ->and($result['error_message'])->toBeNull();
});

it('checks CLI auth when ANTHROPIC_API_KEY is not present', function () {
    SystemEnvironment::fake([
        'PATH' => '/usr/local/bin:/usr/bin',
        'HOME' => '/home/user',
    ]);

    Process::fake([
        'claude hello -p --output-format json' => Process::result('{"success": true}'),
    ]);

    $action = app(CheckAgentAuthenticated::class);
    $result = $action->handle();

    expect($result['authenticated'])->toBeTrue()
        ->and($result['auth_type'])->toBe('cli')
        ->and($result['error_message'])->toBeNull();
});

it('returns authenticated true when claude hello succeeds', function () {
    SystemEnvironment::fake([]);

    Process::fake([
        'claude hello -p --output-format json' => Process::result('{"success": true}'),
    ]);

    $action = app(CheckAgentAuthenticated::class);
    $result = $action->handle();

    expect($result['authenticated'])->toBeTrue()
        ->and($result['auth_type'])->toBe('cli')
        ->and($result['error_message'])->toBeNull();
});

it('returns none when claude hello fails', function () {
    SystemEnvironment::fake([]);

    Process::fake([
        'claude hello -p --output-format json' => Process::result('', 'Error: not authenticated', 1),
    ]);

    $action = app(CheckAgentAuthenticated::class);
    $result = $action->handle();

    expect($result['authenticated'])->toBeFalse()
        ->and($result['auth_type'])->toBe('none')
        ->and($result['error_message'])->toBeString();
});

it('returns none when process times out', function () {
    SystemEnvironment::fake([]);

    Process::fake([
        'claude hello -p --output-format json' => Process::result('', 'Timeout', 1),
    ]);

    $action = app(CheckAgentAuthenticated::class);
    $result = $action->handle();

    expect($result['authenticated'])->toBeFalse()
        ->and($result['auth_type'])->toBe('none');
});

it('uses provided binary path parameter', function () {
    SystemEnvironment::fake([]);

    Process::fake();

    $action = app(CheckAgentAuthenticated::class);
    $result = $action->handle('/custom/path/claude');

    expect($result)->toBeArray()
        ->and($result)->toHaveKeys(['authenticated', 'auth_type', 'error_message']);

    Process::assertRan(fn ($process) => str_contains($process->command, '/custom/path/claude hello'));
});

it('falls back to claude when no path provided', function () {
    SystemEnvironment::fake([]);

    Process::fake([
        'claude hello -p --output-format json' => Process::result('{"success": true}'),
    ]);

    $action = app(CheckAgentAuthenticated::class);
    $result = $action->handle(null);

    expect($result)->toBeArray()
        ->and($result)->toHaveKeys(['authenticated', 'auth_type', 'error_message']);
});

it('returns appropriate error messages on failure', function () {
    SystemEnvironment::fake([]);

    Process::fake([
        'claude hello -p --output-format json' => Process::result('', 'Authentication failed', 1),
    ]);

    $action = app(CheckAgentAuthenticated::class);
    $result = $action->handle();

    expect($result['error_message'])->toBeString()
        ->and($result['error_message'])->not->toBeEmpty();
});

it('passes system environment to process', function () {
    SystemEnvironment::fake([
        'PATH' => '/custom/path',
        'HOME' => '/custom/home',
    ]);

    Process::fake([
        'claude hello -p --output-format json' => Process::result('{"success": true}'),
    ]);

    $action = app(CheckAgentAuthenticated::class);
    $action->handle();

    Process::assertRan(function ($process) {
        return $process->command === 'claude hello -p --output-format json';
    });
});

it('works with empty environment when testing edge cases', function () {
    SystemEnvironment::fake([]);

    Process::fake([
        'claude hello -p --output-format json' => Process::result('', 'not authenticated', 1),
    ]);

    $action = app(CheckAgentAuthenticated::class);
    $result = $action->handle();

    expect($result['authenticated'])->toBeFalse()
        ->and($result['auth_type'])->toBe('none');
});

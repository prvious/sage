<?php

use App\Actions\CheckAgentStatus;

it('checks installation and authentication status', function () {
    $action = app(CheckAgentStatus::class);
    $result = $action->handle();

    expect($result)->toBeArray()
        ->and($result)->toHaveKeys(['installed', 'authenticated', 'auth_type', 'error_message']);
});

it('uses CheckAgentInstalled to resolve binary path', function () {
    $action = app(CheckAgentStatus::class);
    $result = $action->handle();

    expect($result)->toBeArray()
        ->and($result)->toHaveKeys(['installed', 'authenticated', 'auth_type', 'error_message']);
});

it('returns correct structure for all response types', function () {
    $action = app(CheckAgentStatus::class);
    $result = $action->handle();

    expect($result)->toHaveKeys(['installed', 'authenticated', 'auth_type', 'error_message'])
        ->and($result['installed'])->toBeIn([true, false])
        ->and($result['authenticated'])->toBeIn([true, false]);

    if ($result['error_message'] !== null) {
        expect($result['error_message'])->toBeString();
    }
});

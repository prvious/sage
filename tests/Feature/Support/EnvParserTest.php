<?php

use App\Support\EnvParser;

it('can parse env file content', function () {
    $content = <<<'ENV'
# Application Configuration
APP_NAME=Sage
APP_ENV=local

# Database Configuration
DB_CONNECTION=mysql
DB_PASSWORD=secret123
ENV;

    $result = EnvParser::parse($content);

    expect($result)->toBeArray()
        ->and($result)->toHaveKey('APP_NAME')
        ->and($result['APP_NAME']['value'])->toBe('Sage')
        ->and($result['APP_NAME']['comment'])->toBe(' Application Configuration')
        ->and($result['DB_PASSWORD']['is_sensitive'])->toBeTrue();
});

it('can stringify variables to env format', function () {
    $variables = [
        'APP_NAME' => [
            'value' => 'Sage',
            'comment' => 'Application name',
            'is_sensitive' => false,
        ],
        'DB_PASSWORD' => [
            'value' => 'secret123',
            'comment' => null,
            'is_sensitive' => true,
        ],
    ];

    $result = EnvParser::stringify($variables);

    expect($result)->toContain('APP_NAME=Sage')
        ->and($result)->toContain('DB_PASSWORD=secret123')
        ->and($result)->toContain('# Application name');
});

it('detects sensitive variables', function () {
    expect(EnvParser::isSensitive('DB_PASSWORD'))->toBeTrue()
        ->and(EnvParser::isSensitive('API_KEY'))->toBeTrue()
        ->and(EnvParser::isSensitive('SECRET_TOKEN'))->toBeTrue()
        ->and(EnvParser::isSensitive('APP_NAME'))->toBeFalse()
        ->and(EnvParser::isSensitive('DB_CONNECTION'))->toBeFalse();
});

it('groups variables by section', function () {
    $variables = [
        'APP_NAME' => ['value' => 'Sage', 'comment' => null, 'is_sensitive' => false],
        'APP_ENV' => ['value' => 'local', 'comment' => null, 'is_sensitive' => false],
        'DB_CONNECTION' => ['value' => 'mysql', 'comment' => null, 'is_sensitive' => false],
        'CACHE_DRIVER' => ['value' => 'redis', 'comment' => null, 'is_sensitive' => false],
    ];

    $grouped = EnvParser::groupBySection($variables);

    expect($grouped)->toHaveKey('Application')
        ->and($grouped)->toHaveKey('Database')
        ->and($grouped)->toHaveKey('Cache')
        ->and($grouped['Application'])->toHaveKey('APP_NAME')
        ->and($grouped['Database'])->toHaveKey('DB_CONNECTION')
        ->and($grouped['Cache'])->toHaveKey('CACHE_DRIVER');
});

it('handles values with spaces by quoting them', function () {
    $variables = [
        'APP_NAME' => [
            'value' => 'My Application',
            'comment' => null,
            'is_sensitive' => false,
        ],
    ];

    $result = EnvParser::stringify($variables);

    expect($result)->toContain('"My Application"');
});

it('removes quotes from parsed values', function () {
    $content = 'APP_NAME="Sage Application"';

    $result = EnvParser::parse($content);

    expect($result['APP_NAME']['value'])->toBe('Sage Application');
});

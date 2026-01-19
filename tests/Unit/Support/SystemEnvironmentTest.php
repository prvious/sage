<?php

use App\Support\SystemEnvironment;

beforeEach(function () {
    SystemEnvironment::clearFake();
});

afterEach(function () {
    SystemEnvironment::clearFake();
});

it('returns environment variable value from real system', function () {
    $env = new SystemEnvironment;

    // PATH should exist on all systems
    $path = $env->get('PATH');

    expect($path)->not->toBeNull()
        ->and($path)->not->toBe('')
        ->and($path)->toBeString();
});

it('returns default value when variable does not exist', function () {
    $env = new SystemEnvironment;

    $result = $env->get('NONEXISTENT_VAR_12345', 'default-value');

    expect($result)->toBe('default-value');
});

it('returns null when variable does not exist and no default provided', function () {
    $env = new SystemEnvironment;

    $result = $env->get('NONEXISTENT_VAR_12345');

    expect($result)->toBeNull();
});

it('returns all environment variables as array', function () {
    $env = new SystemEnvironment;

    $all = $env->all();

    expect($all)->toBeArray()
        ->and($all)->not->toBeEmpty()
        ->and($all)->toHaveKey('PATH');
});

it('has returns true when variable exists and is not empty', function () {
    $env = new SystemEnvironment;

    // PATH should exist on all systems
    $result = $env->has('PATH');

    expect($result)->toBeTrue();
});

it('has returns false when variable does not exist', function () {
    $env = new SystemEnvironment;

    $result = $env->has('NONEXISTENT_VAR_12345');

    expect($result)->toBeFalse();
});

it('fake method overrides real environment variables', function () {
    SystemEnvironment::fake([
        'TEST_VAR' => 'test-value',
    ]);

    $env = new SystemEnvironment;

    expect($env->get('TEST_VAR'))->toBe('test-value')
        ->and($env->has('TEST_VAR'))->toBeTrue();
});

it('fake with specific variables returns only those variables in all', function () {
    SystemEnvironment::fake([
        'VAR_ONE' => 'value-one',
        'VAR_TWO' => 'value-two',
    ]);

    $env = new SystemEnvironment;
    $all = $env->all();

    expect($all)->toBe([
        'VAR_ONE' => 'value-one',
        'VAR_TWO' => 'value-two',
    ])
        ->and($all)->toHaveKey('VAR_ONE')
        ->and($all)->toHaveKey('VAR_TWO')
        ->and($all)->not->toHaveKey('PATH');
});

it('fake with empty array simulates no environment variables', function () {
    SystemEnvironment::fake([]);

    $env = new SystemEnvironment;

    expect($env->all())->toBe([])
        ->and($env->all())->toBeEmpty()
        ->and($env->has('PATH'))->toBeFalse()
        ->and($env->get('PATH'))->toBeNull();
});

it('clearFake restores normal getenv behavior', function () {
    SystemEnvironment::fake([
        'TEST_VAR' => 'test-value',
    ]);

    $env = new SystemEnvironment;
    expect($env->get('TEST_VAR'))->toBe('test-value');

    SystemEnvironment::clearFake();

    expect($env->get('TEST_VAR'))->toBeNull()
        ->and($env->has('PATH'))->toBeTrue();
});

it('has works correctly with faked environment', function () {
    SystemEnvironment::fake([
        'PRESENT_VAR' => 'value',
        'EMPTY_VAR' => '',
    ]);

    $env = new SystemEnvironment;

    expect($env->has('PRESENT_VAR'))->toBeTrue()
        ->and($env->has('EMPTY_VAR'))->toBeFalse()
        ->and($env->has('MISSING_VAR'))->toBeFalse();
});

it('get with default values works correctly with faked environment', function () {
    SystemEnvironment::fake([
        'PRESENT_VAR' => 'present-value',
    ]);

    $env = new SystemEnvironment;

    expect($env->get('PRESENT_VAR', 'default'))->toBe('present-value')
        ->and($env->get('MISSING_VAR', 'default'))->toBe('default')
        ->and($env->get('MISSING_VAR'))->toBeNull();
});

it('fake persists across multiple instances', function () {
    SystemEnvironment::fake([
        'SHARED_VAR' => 'shared-value',
    ]);

    $env1 = new SystemEnvironment;
    $env2 = new SystemEnvironment;

    expect($env1->get('SHARED_VAR'))->toBe('shared-value')
        ->and($env2->get('SHARED_VAR'))->toBe('shared-value');
});

it('handles null values in faked environment', function () {
    SystemEnvironment::fake([
        'NULL_VAR' => null,
    ]);

    $env = new SystemEnvironment;

    expect($env->has('NULL_VAR'))->toBeFalse()
        ->and($env->get('NULL_VAR', 'default'))->toBe('default');
});

it('can fake ANTHROPIC_API_KEY for testing', function () {
    SystemEnvironment::fake([
        'ANTHROPIC_API_KEY' => 'sk-ant-test-key-12345',
    ]);

    $env = new SystemEnvironment;

    expect($env->has('ANTHROPIC_API_KEY'))->toBeTrue()
        ->and($env->get('ANTHROPIC_API_KEY'))->toBe('sk-ant-test-key-12345');
});

it('can fake HOME and USERPROFILE for testing path expansion', function () {
    SystemEnvironment::fake([
        'HOME' => '/home/testuser',
        'USERPROFILE' => 'C:\\Users\\testuser',
    ]);

    $env = new SystemEnvironment;

    expect($env->get('HOME'))->toBe('/home/testuser')
        ->and($env->get('USERPROFILE'))->toBe('C:\\Users\\testuser');
});

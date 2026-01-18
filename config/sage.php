<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Required Environment Variables
    |--------------------------------------------------------------------------
    |
    | These environment variables must be present in all .env files.
    | The environment manager will warn if any of these are missing.
    |
    */
    'required_env_variables' => [
        'APP_NAME',
        'APP_ENV',
        'APP_KEY',
        'APP_URL',
        'DB_CONNECTION',
    ],

    /*
    |--------------------------------------------------------------------------
    | Server Driver Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the server driver system for managing virtual hosts.
    | Currently only supports the artisan server driver.
    |
    */
    'server' => [
        'default' => 'artisan',
    ],

    /*
    |--------------------------------------------------------------------------
    | Artisan Server Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the Artisan Server driver (php artisan serve).
    | This provides a lightweight development server option.
    |
    */
    'artisan_server' => [
        'host' => env('ARTISAN_SERVER_HOST', '127.0.0.1'),
        'base_port' => env('ARTISAN_SERVER_BASE_PORT', 8000),
        'max_port' => env('ARTISAN_SERVER_MAX_PORT', 8999),
    ],

    /*
    |--------------------------------------------------------------------------
    | Agent Driver Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the agent driver system for spawning and managing AI coding agents.
    | Supported drivers: claude, opencode, fake
    |
    */
    'agents' => [
        'default' => env('SAGE_AGENT', 'claude'),

        'claude' => [
            'binary' => env('CLAUDE_CODE_PATH', 'claude'),
            'default_model' => env('CLAUDE_DEFAULT_MODEL', 'claude-sonnet-4-20250514'),
        ],

        'opencode' => [
            'binary' => env('OPENCODE_PATH', 'opencode'),
        ],

        'fake' => [
            // Fake driver for testing purposes
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Last Opened Project Cache TTL
    |--------------------------------------------------------------------------
    |
    | This value determines how long (in minutes) the last opened project
    | will be remembered in the cache. Default is 30 days.
    |
    */
    'last_project_ttl' => env('LAST_PROJECT_TTL', 60 * 24 * 30), // 30 days
];

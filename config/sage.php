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
    | Supported drivers: caddy, nginx
    |
    */
    'server' => [
        'default' => env('SAGE_SERVER_DRIVER', 'caddy'),

        'caddy' => [
            'admin_url' => env('CADDY_ADMIN_URL', 'http://localhost:2019'),
            'server_name' => 'sage',
        ],

        'nginx' => [
            'config_path' => env('NGINX_SAGE_CONFIG_PATH', '/etc/nginx/sage.d'),
            'reload_command' => env('NGINX_RELOAD_COMMAND', 'nginx -s reload'),
            'test_command' => 'nginx -t',
        ],
    ],
];

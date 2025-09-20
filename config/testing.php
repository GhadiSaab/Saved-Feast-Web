<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Testing Environment Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration specific to the testing environment.
    | These settings override the main configuration when running tests.
    |
    */

    'app' => [
        'url' => 'http://localhost:8000',
        'debug' => true,
    ],

    'database' => [
        'default' => 'mysql',
        'connections' => [
            'mysql' => [
                'driver' => 'mysql',
                'host' => env('DB_HOST', '127.0.0.1'),
                'port' => env('DB_PORT', '3306'),
                'database' => env('DB_DATABASE', 'savedfeast_test'),
                'username' => env('DB_USERNAME', 'root'),
                'password' => env('DB_PASSWORD', ''),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
            ],
        ],
    ],

    'mail' => [
        'default' => 'array',
    ],

    'cache' => [
        'default' => 'array',
    ],

    'session' => [
        'driver' => 'array',
    ],
];

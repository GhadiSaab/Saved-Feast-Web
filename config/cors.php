<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => [
        'http://localhost:3000',
        'http://localhost:5173', // Vite dev server
        'http://127.0.0.1:3000',
        'http://127.0.0.1:5173',
        'http://localhost:8000',
        'https://savedfeast.app', // Production web app
        'https://www.savedfeast.app',
        'http://192.168.1.116:8000', // Mobile app IP
        'http://192.168.1.116:*', // Allow any port from this IP
        // Additional production domains can be added below
    ],

    'allowed_origins_patterns' => [
        // Allow localhost with any port for development
        '/^http:\/\/localhost:\d+$/',
        '/^http:\/\/127\.0\.0\.1:\d+$/',
        // Allow your local IP with any port for mobile development
        '/^http:\/\/192\.168\.1\.116:\d+$/',
    ],

    'allowed_headers' => [
        'Accept',
        'Authorization',
        'Content-Type',
        'X-Requested-With',
        'X-CSRF-TOKEN',
        'X-XSRF-TOKEN',
        'User-Agent',
        'Accept-Language',
        'Accept-Encoding',
        'Cache-Control',
        'Pragma',
    ],

    'exposed_headers' => [
        'X-RateLimit-Limit',
        'X-RateLimit-Remaining',
        'X-RateLimit-Reset',
        'X-Total-Count',
        'X-Page-Count',
    ],

    'max_age' => 86400, // 24 hours

    'supports_credentials' => true, // Enable for mobile apps and SPAs

];


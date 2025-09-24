<?php

/**
 * Test Database Setup Script
 *
 * This script helps set up the test database and environment
 * to avoid domain-related issues when running tests.
 */

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\Artisan;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo 'Setting up test database...'.PHP_EOL;

try {
    // Set testing environment
    putenv('APP_ENV=testing');
    putenv('DB_DATABASE=savedfeast_test');
    putenv('APP_URL=http://localhost:8000');

    // Create test database if it doesn't exist
    $database = 'savedfeast_test';
    $host = env('DB_HOST', '127.0.0.1');
    $username = env('DB_USERNAME', 'root');
    $password = env('DB_PASSWORD', '');

    // Connect to MySQL without specifying database
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$database`");

    echo "Test database '$database' created/verified.".PHP_EOL;

    // Run migrations
    Artisan::call('migrate:fresh', [
        '--database' => 'mysql',
        '--force' => true,
    ]);

    echo 'Migrations completed.'.PHP_EOL;

    // Run seeders
    Artisan::call('db:seed', [
        '--class' => 'Database\\Seeders\\TestSeeder',
        '--force' => true,
    ]);

    echo 'Test seeders completed.'.PHP_EOL;
    echo 'Test database setup complete!'.PHP_EOL;
} catch (Exception $e) {
    echo 'Error setting up test database: '.$e->getMessage().PHP_EOL;
    exit(1);
}

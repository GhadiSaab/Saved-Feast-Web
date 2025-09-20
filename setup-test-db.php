<?php

/**
 * Test Database Setup Script
 * 
 * This script helps set up the test database and environment
 * to avoid domain-related issues when running tests.
 */

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Setting up test database...\n";

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
    
    echo "Test database '$database' created/verified.\n";
    
    // Run migrations
    Artisan::call('migrate:fresh', [
        '--database' => 'mysql',
        '--force' => true,
    ]);
    
    echo "Migrations completed.\n";
    
    // Run seeders
    Artisan::call('db:seed', [
        '--class' => 'Database\\Seeders\\TestSeeder',
        '--force' => true,
    ]);
    
    echo "Test seeders completed.\n";
    echo "Test database setup complete!\n";
    
} catch (Exception $e) {
    echo "Error setting up test database: " . $e->getMessage() . "\n";
    exit(1);
}

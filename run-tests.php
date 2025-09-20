<?php

/**
 * Test Runner Script
 * 
 * This script runs tests with proper environment configuration
 * to avoid domain-related issues.
 */

// Set testing environment variables
putenv('APP_ENV=testing');
putenv('APP_URL=http://localhost:8000');
putenv('DB_DATABASE=savedfeast_test');
putenv('MAIL_MAILER=array');
putenv('CACHE_DRIVER=array');
putenv('SESSION_DRIVER=array');
putenv('QUEUE_CONNECTION=sync');
putenv('SF_REALTIME_ENABLED=false');

echo "Running tests with localhost configuration...\n";
echo "Environment: " . getenv('APP_ENV') . "\n";
echo "App URL: " . getenv('APP_URL') . "\n";
echo "Database: " . getenv('DB_DATABASE') . "\n";

// Run the tests
$command = 'vendor/bin/phpunit --configuration phpunit.xml';
echo "Executing: $command\n";

$output = [];
$returnCode = 0;

exec($command . ' 2>&1', $output, $returnCode);

echo "\n" . implode("\n", $output) . "\n";

if ($returnCode === 0) {
    echo "\n✅ All tests passed!\n";
} else {
    echo "\n❌ Some tests failed. Return code: $returnCode\n";
}

exit($returnCode);

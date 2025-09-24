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

echo 'Running tests with localhost configuration...'.PHP_EOL;
echo 'Environment: '.getenv('APP_ENV').PHP_EOL;
echo 'App URL: '.getenv('APP_URL').PHP_EOL;
echo 'Database: '.getenv('DB_DATABASE').PHP_EOL;

// Run the tests
$command = 'vendor/bin/phpunit --configuration phpunit.xml';
echo 'Executing: '.$command.PHP_EOL;

$output = [];
$returnCode = 0;

exec($command.' 2>&1', $output, $returnCode);

echo PHP_EOL.implode(PHP_EOL, $output).PHP_EOL;

if ($returnCode === 0) {
    echo PHP_EOL.'All tests passed!'.PHP_EOL;
} else {
    echo PHP_EOL.'Some tests failed. Return code: '.$returnCode.PHP_EOL;
}

exit($returnCode);

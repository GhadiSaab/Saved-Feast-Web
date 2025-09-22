<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        // Force localhost URLs for testing
        $this->app['config']->set('app.url', 'http://localhost:8000');
        $this->app['config']->set('app.env', 'testing');

        // Disable external services that might cause issues
        $this->app['config']->set('mail.default', 'array');
        $this->app['config']->set('queue.default', 'sync');
        $this->app['config']->set('cache.default', 'array');
        $this->app['config']->set('session.driver', 'array');

        // Disable real-time features for testing
        $this->app['config']->set('savedfeast.realtime_enabled', false);
    }
}

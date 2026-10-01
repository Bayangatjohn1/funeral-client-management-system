<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Keep PHPUnit isolated from the local database when configuration is cached.
     */
    public function createApplication()
    {
        $app = parent::createApplication();

        $testConnection = $_ENV['DB_CONNECTION'] ?? $_SERVER['DB_CONNECTION'] ?? null;
        $testDatabase = $_ENV['DB_DATABASE'] ?? $_SERVER['DB_DATABASE'] ?? null;

        if ($testConnection !== 'sqlite' || $testDatabase !== ':memory:') {
            throw new \RuntimeException(
                'Unsafe test database configuration. PHPUnit must use sqlite with DB_DATABASE=:memory:.'
            );
        }

        $app['config']->set('app.env', 'testing');
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');
        $app['config']->set('cache.default', 'array');
        $app['config']->set('session.driver', 'array');
        $app['config']->set('mail.default', 'array');
        $app['config']->set('queue.default', 'sync');

        return $app;
    }
}

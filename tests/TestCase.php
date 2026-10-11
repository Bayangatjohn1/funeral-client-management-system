<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function be(\Illuminate\Contracts\Auth\Authenticatable $user, $guard = null)
    {
        $this->withSession(['idle_last_activity' => now()->timestamp, 'idle_session_key' => 'test-session']);
        return parent::be($user, $guard);
    }
    /** Simulate a completed login, including MFA, for authenticated feature fixtures.
     * Use be() with explicit session state to test pending/revoked authentication.
     */
    public function actingAs(\Illuminate\Contracts\Auth\Authenticatable $user, $guard = null)
    {
        if ($user instanceof \App\Models\User) {
            if ($user->requiresMfa() && ! $user->two_factor_confirmed_at) {
                $user->two_factor_secret = (new \PragmaRX\Google2FA\Google2FA)->generateSecretKey();
                $user->two_factor_confirmed_at = now();
                $user->save();
            }
            $this->withSession(['account_security' => \App\Support\AccountSecurity::fingerprint($user), 'mfa_user_id' => $user->requiresMfa() ? $user->id : null]);
        }
        return parent::actingAs($user, $guard);
    }

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

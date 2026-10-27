<?php

namespace Tests\Browser;

use App\Models\Branch;
use App\Models\User;
use App\Services\BranchSync\SyncState;
use Laravel\Dusk\Browser;
use PragmaRX\Google2FA\Google2FA;
use Tests\DuskTestCase;

class OfflineBranchSmokeTest extends DuskTestCase
{
    public function test_staff_and_branch_admin_can_use_local_assets_with_internet_origins_blocked(): void
    {
        if (config('branch_sync.mode') !== 'branch') {
            $this->markTestSkipped('Run with BRANCH_SYNC_MODE=branch and BRANCH_SYNC_CODE=BR001 on an isolated Dusk database.');
        }
        SyncState::put('installation', 'f061636f-7062-44f2-a5ed-f4a435bf9b56');
        SyncState::put('references_at', now()->toIso8601String());
        SyncState::put('last_success_at', now()->subMinutes(10)->toIso8601String());
        $branch = Branch::where('branch_code', 'BR001')->firstOrFail();
        foreach (['staff', 'admin'] as $role) {
            $secret = (new Google2FA)->generateSecretKey();
            $user = User::factory()->create([
                'email' => 'offline-'.$role.'@funeral.test', 'password' => 'OfflineTestPassword123!',
                'role' => $role, 'admin_scope' => $role === 'admin' ? 'branch' : null,
                'branch_id' => $branch->id, 'is_active' => true, 'must_change_password' => false,
            ]);
            $user->two_factor_secret = $secret;
            $user->two_factor_confirmed_at = now();
            $user->save();
            $this->browse(function (Browser $browser) use ($user, $secret, $role) {
                $browser->driver->executeCustomCommand('/session/:sessionId/goog/cdp/execute', 'POST', [
                    'cmd' => 'Network.enable', 'params' => new \stdClass,
                ]);
                // Keep localhost/LAN available while blocking internet HTTPS dependencies.
                $browser->driver->executeCustomCommand('/session/:sessionId/goog/cdp/execute', 'POST', [
                    'cmd' => 'Network.setBlockedURLs', 'params' => ['urls' => ['https://*']],
                ]);
                $browser->visit('/login')->waitUntil('document.readyState === "complete"', 10)->pause(200)
                    ->type('email', $user->email)->type('password', 'OfflineTestPassword123!')
                    ->assertInputValue('email', $user->email)->assertInputValue('password', 'OfflineTestPassword123!')
                    ->press('Sign in')->waitForLocation('/security/challenge', 10)
                    ->type('code', (new Google2FA)->getCurrentOtp($secret))->press('Verify and continue')
                    ->waitForLocation($role === 'staff' ? '/staff' : '/admin', 10)
                    ->waitFor('.branch-sync-banner')->click('.branch-sync-banner summary');
                foreach (['light', 'dark'] as $theme) {
                    $browser->script('localStorage.setItem("app-theme", '.json_encode($theme).'); document.documentElement.dataset.theme = '.json_encode($theme));
                    foreach ([1440, 390] as $width) {
                        $browser->driver->executeCustomCommand('/session/:sessionId/goog/cdp/execute', 'POST', [
                            'cmd' => 'Emulation.setDeviceMetricsOverride',
                            'params' => ['width' => $width, 'height' => 900, 'deviceScaleFactor' => 1, 'mobile' => false],
                        ]);
                        $browser->resize($width, 900)->pause(200)->assertSee('Catalog last checked');
                        $layout = $browser->script('const b=document.querySelector(".branch-sync-banner").getBoundingClientRect(); return {left:b.left,right:b.right,width:innerWidth,external:performance.getEntriesByType("resource").filter(r=>new URL(r.name).origin!==location.origin).map(r=>r.name)}')[0];
                        $this->assertGreaterThanOrEqual(0, $layout['left']);
                        $this->assertLessThanOrEqual($layout['width'] + 1, $layout['right']);
                        $this->assertSame([], $layout['external']);
                        $this->assertReadableBanner($browser);
                        $browser->screenshot('offline-'.$role.'-'.$theme.'-'.$width);
                    }
                }
                if ($role === 'staff') {
                    $browser->visit('/intake/main')->waitFor('#intakeWizardForm', 10)
                        ->waitUntil('typeof window.flatpickr === "function"', 10);
                    $this->assertGreaterThan(0, $browser->script('return document.querySelectorAll(".flatpickr-input").length')[0]);
                    $external = $browser->script('return [...document.scripts].map(s=>s.src).filter(s=>s && new URL(s).origin!==location.origin)')[0];
                    $this->assertSame([], $external);
                } else {
                    $browser->visit('/admin/service-management')->assertSee('Read-only');
                }
                $browser->logout();
            });
        }
    }

    public function test_owner_and_system_admin_see_central_data_freshness_in_both_themes(): void
    {
        if (config('branch_sync.mode') !== 'central') {
            $this->markTestSkipped('Run with BRANCH_SYNC_MODE=central on an isolated Dusk database.');
        }
        \Illuminate\Support\Facades\DB::table('branch_sync_peers')->insert([
            'branch_code' => 'BR001', 'token_hash' => hash('sha256', str_repeat('a', 64)),
            'installation_id' => 'f061636f-7062-44f2-a5ed-f4a435bf9b56', 'enabled' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        foreach (['owner', 'admin'] as $role) {
            $secret = (new Google2FA)->generateSecretKey();
            $user = User::factory()->create([
                'email' => 'central-sync-'.$role.'@funeral.test', 'password' => 'OfflineTestPassword123!',
                'role' => $role, 'admin_scope' => $role === 'admin' ? 'system' : null,
                'branch_id' => null, 'is_active' => true, 'must_change_password' => false,
            ]);
            $user->two_factor_secret = $secret;
            $user->two_factor_confirmed_at = now();
            $user->save();
            $this->browse(function (Browser $browser) use ($user, $secret, $role) {
                $browser->visit('/login')->waitUntil('document.readyState === "complete"', 10)->pause(200)
                    ->type('email', $user->email)->type('password', 'OfflineTestPassword123!')->press('Sign in');
                if ($role === 'admin') {
                    $browser->waitForLocation('/security/challenge', 10)->type('code', (new Google2FA)->getCurrentOtp($secret))->press('Verify and continue');
                }
                $browser->waitForLocation($role === 'owner' ? '/owner' : '/admin', 10)
                    ->waitFor('.branch-sync-banner')->click('.branch-sync-banner summary')->assertSee('Data may be outdated');
                foreach (['light', 'dark'] as $theme) {
                    $browser->script('localStorage.setItem("app-theme", '.json_encode($theme).'); document.documentElement.dataset.theme = '.json_encode($theme));
                    foreach ([1440, 390] as $width) {
                        $browser->driver->executeCustomCommand('/session/:sessionId/goog/cdp/execute', 'POST', [
                            'cmd' => 'Emulation.setDeviceMetricsOverride',
                            'params' => ['width' => $width, 'height' => 900, 'deviceScaleFactor' => 1, 'mobile' => false],
                        ]);
                        $browser->resize($width, 900)->pause(200);
                        $layout = $browser->script('const b=document.querySelector(".branch-sync-banner").getBoundingClientRect(); return {left:b.left,right:b.right,width:innerWidth}')[0];
                        $this->assertGreaterThanOrEqual(0, $layout['left']);
                        $this->assertLessThanOrEqual($width + 1, $layout['right']);
                        $this->assertReadableBanner($browser);
                        $browser->screenshot('sync-central-'.$role.'-'.$theme.'-'.$width);
                    }
                }
                if ($role === 'owner') {
                    $browser->visit('/owner/branch-analytics')->waitUntil('typeof window.Chart === "function"', 10);
                }
                $browser->logout();
            });
        }
    }

    private function assertReadableBanner(Browser $browser): void
    {
        $result = $browser->script('const banner=document.querySelector(".branch-sync-banner"),note=banner.querySelector("p"); const lum=c=>{const a=c.match(/[\d.]+/g).slice(0,3).map(Number).map(v=>v/255).map(v=>v<=.04045?v/12.92:((v+.055)/1.055)**2.4);return a[0]*.2126+a[1]*.7152+a[2]*.0722};const f=lum(getComputedStyle(note).color),b=lum(getComputedStyle(banner).backgroundColor);const menu=document.querySelector(".panel-floating-menu-btn"),text=banner.querySelector("summary span").getBoundingClientRect();return {contrast:(Math.max(f,b)+.05)/(Math.min(f,b)+.05),clear:!menu||getComputedStyle(menu).display==="none"||menu.getBoundingClientRect().right<=text.left}')[0];
        $this->assertGreaterThanOrEqual(4.5, $result['contrast']);
        $this->assertTrue($result['clear'], 'The navigation button must not obscure the sync status text.');
    }
}

<?php

namespace Tests\Browser;

use App\Models\{Branch, CasketCatalog, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class CatalogFilterChipPresentationTest extends DuskTestCase
{
    use RefreshDatabase;

    public function createApplication()
    {
        $app = parent::createApplication();
        if (($_ENV['DB_CONNECTION'] ?? null) !== 'sqlite' || ($_ENV['DB_DATABASE'] ?? null) !== ':memory:') {
            throw new \RuntimeException('Isolated UI database required');
        }
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');

        return $app;
    }

    public function test_service_catalog_chips_clear_action_and_content_gaps_are_consistent(): void
    {
        $branch = Branch::create([
            'branch_code' => 'CAT-CHIP',
            'branch_name' => 'Catalog Chip Branch',
            'address' => 'Test',
            'is_active' => true,
        ]);
        CasketCatalog::create([
            'name' => 'Unavailable Fixture',
            'type_or_material' => 'Wood',
            'description' => 'Filter chip presentation fixture',
            'standard_price' => 1000,
            'is_active' => true,
            'is_available' => false,
        ]);

        $manifest = json_decode(file_get_contents(public_path('build/manifest.json')), true);
        $css = file_get_contents(public_path('build/'.$manifest['resources/css/app.css']['file']));
        foreach ($manifest['resources/js/app.js']['css'] as $file) {
            $css .= file_get_contents(public_path('build/'.$file));
        }
        $css = str_replace('url(./', 'url(file:///'.str_replace('\\', '/', public_path('build/assets')).'/', $css);
        $bundle = file_get_contents(public_path('build/'.$manifest['resources/js/app.js']['file']));

        foreach (['system', 'branch'] as $scope) {
            $actor = User::factory()->create([
                'role' => 'admin',
                'admin_scope' => $scope,
                'branch_id' => $branch->id,
                'is_active' => true,
            ]);
            if ($actor->requiresMfa()) {
                $actor->two_factor_secret = (new \PragmaRX\Google2FA\Google2FA)->generateSecretKey();
                $actor->two_factor_confirmed_at = now();
                $actor->save();
            }
            $this->withSession([
                'idle_last_activity' => now()->timestamp,
                'idle_session_key' => 'catalog-chip',
                'account_security' => \App\Support\AccountSecurity::fingerprint($actor),
                'mfa_user_id' => $actor->requiresMfa() ? $actor->id : null,
            ]);

            $html = $this->actingAs($actor)->get(route('admin.service-management.index', [
                'tab' => 'caskets',
                'status' => 'unavailable',
            ]))->assertOk()->getContent();
            $html = preg_replace('~<link\b[^>]*href="[^"]*/build/[^"]*"[^>]*>~i', '', $html);
            $html = preg_replace('~<script\b[^>]*src="[^"]*/build/[^"]*"[^>]*>\s*</script>~i', '', $html);
            $html = str_replace(rtrim(config('app.url'), '/').'/vendor/', 'file:///'.str_replace('\\', '/', public_path('vendor')).'/', $html);
            $html = str_replace('</head>', '<style>'.$css.'</style><script>window.fetch=async()=>{throw Error("Isolated request")}</script><script type="module">'.str_ireplace('</script', '<\/script', $bundle).'</script></head>', $html);
            $path = tempnam(storage_path('framework'), 'catalog-chip');
            rename($path, $path.'.html');
            $path .= '.html';
            file_put_contents($path, $html);

            try {
                $this->browse(function (Browser $browser) use ($path, $scope) {
                    $browser->driver->get('file:///'.str_replace('\\', '/', $path));
                    $browser->waitUntil('document.querySelector(".catalog-active-filter-chips:not([hidden]) .catalog-filter-chip")!==null');
                    $browser->assertScript('document.querySelector(".catalog-filter-chip").dataset.tone', 'caution');
                    $browser->assertScript('document.querySelector(".catalog-filter-chip").textContent.includes("Status: Unavailable")', true);
                    $browser->assertScript('document.querySelector(".catalog-clear-all").textContent.trim()', 'Clear filters');
                    $browser->assertScript('document.querySelector(".catalog-filter-chip button").getAttribute("aria-label").startsWith("Remove Status: Unavailable")', true);

                    foreach (['light', 'dark'] as $theme) {
                        foreach ([1440, 390] as $width) {
                            $browser->driver->executeCustomCommand('/session/:sessionId/goog/cdp/execute', 'POST', [
                                'cmd' => 'Emulation.setDeviceMetricsOverride',
                                'params' => ['width' => $width, 'height' => 1000, 'deviceScaleFactor' => 1, 'mobile' => $width < 500],
                            ]);
                            $styles = $browser->script('document.documentElement.dataset.theme='.json_encode($theme).';const toolbar=document.querySelector(".service-hub-toolbar"),chips=document.querySelector(".catalog-active-filter-chips"),content=document.querySelector(".service-hub-panels"),clear=document.querySelector(".catalog-clear-all"),chip=document.querySelector(".catalog-filter-chip"),cs=getComputedStyle(clear),ps=getComputedStyle(chip);return {above:Math.round(chips.getBoundingClientRect().top-toolbar.getBoundingClientRect().bottom),below:Math.round(content.getBoundingClientRect().top-chips.getBoundingClientRect().bottom),rowGap:getComputedStyle(chips).rowGap,columnGap:getComputedStyle(chips).columnGap,clearBg:cs.backgroundColor,clearBorder:cs.borderTopColor,clearColor:cs.color,clearWeight:cs.fontWeight,chipBg:ps.backgroundColor,overflow:document.documentElement.scrollWidth>innerWidth+1}')[0];
                            $this->assertSame(12, $styles['above'], "$scope $theme $width toolbar-to-chip gap");
                            $this->assertSame(12, $styles['below'], "$scope $theme $width chip-to-content gap");
                            $this->assertSame('8px', $styles['rowGap']);
                            $this->assertSame('12px', $styles['columnGap']);
                            $this->assertSame('rgba(0, 0, 0, 0)', $styles['clearBg']);
                            $this->assertSame('rgba(0, 0, 0, 0)', $styles['clearBorder']);
                            $this->assertSame('400', $styles['clearWeight']);
                            $this->assertSame($theme === 'dark' ? 'rgb(253, 162, 155)' : 'rgb(180, 35, 24)', $styles['clearColor']);
                            $this->assertSame($theme === 'dark' ? 'rgb(58, 45, 27)' : 'rgb(255, 240, 215)', $styles['chipBg']);
                            $this->assertFalse($styles['overflow'], "$scope $theme $width document overflow");
                        }
                    }

                    $browser->driver->executeCustomCommand('/session/:sessionId/goog/cdp/execute', 'POST', [
                        'cmd' => 'Emulation.setDeviceMetricsOverride',
                        'params' => ['width' => 1440, 'height' => 1000, 'deviceScaleFactor' => 1, 'mobile' => false],
                    ]);
                    $browser->script('document.documentElement.dataset.theme="dark"');
                    $browser->mouseover('.catalog-clear-all');
                    $browser->assertScript('getComputedStyle(document.querySelector(".catalog-clear-all")).backgroundColor', 'rgba(0, 0, 0, 0)');
                    $browser->assertScript('getComputedStyle(document.querySelector(".catalog-clear-all")).borderColor!=="rgba(0, 0, 0, 0)"', true);
                    $browser->screenshot('catalog-filter-chips-'.$scope.'-dark');
                    $browser->driver->get('about:blank');
                });
            } finally {
                $this->browse(fn (Browser $browser) => $browser->driver->get('about:blank'));
                for ($attempt = 0; $attempt < 8 && is_file($path); $attempt++) {
                    if (@unlink($path)) {
                        break;
                    }
                    usleep(50000);
                }
            }
        }
    }
}

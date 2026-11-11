<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class AsyncRecordTabsTest extends DuskTestCase
{
    public function test_payment_header_action_uses_the_existing_async_loader_in_both_directions(): void
    {
        $view = file_get_contents(resource_path('views/staff/payments/history.blade.php'));
        $start = strpos($view, 'window.initPaymentMonitoring = () => {');
        $end = strrpos($view, 'window.initPaymentMonitoring();') + strlen('window.initPaymentMonitoring();');
        $handler = substr($view,$start,$end-$start);
        $motionStart = strpos($view, 'html.pm-section-switching');
        $motionEnd = strpos($view, '.payment-monitoring-page .pc-requests {', $motionStart);
        $motion = substr($view, $motionStart, $motionEnd - $motionStart);
        $html = '<!doctype html><html><style>'.$motion.'</style><body><header><h1 id="heading">Payment Monitoring</h1><div data-pm-header-slot><a id="corrections" class="pm-header-action--corrections" data-pm-section-action href="#corrections">Correction Requests</a></div></header><div class="pm-page payment-monitoring-page" data-pm-view="records"><section class="pc-requests"><p>Original payment content</p></section></div><script>'.$handler.'</script></body></html>';
        $this->browse(function (Browser $browser) use ($html) {
            $browser->driver->get('data:text/html;charset=utf-8,'.rawurlencode($html));
            $browser->waitUntil('window.initPaymentMonitoring !== undefined');
            $browser->script(<<<'JS'
                window.heading = document.getElementById('heading');
                window.fetchCount = 0;
                window.transitionCount = 0;
                const startTransition = document.startViewTransition.bind(document);
                document.startViewTransition = callback => {
                    window.transitionCount++;
                    const transition = startTransition(callback);
                    transition.ready.then(() => {
                        window.morph = document.getAnimations().some(animation =>
                            animation.animationName?.includes('pm-correction-card') && animation.effect.getTiming().duration === 340
                        );
                    }, error => { window.morph = error.message; });
                    return transition;
                };
                window.fetch = url => new Promise((resolve, reject) => {
                    window.fetchCount++;
                    window.complete = () => {
                        const corrections = new URL(url).hash === '#corrections';
                        const open = '<a id="corrections" class="pm-header-action--corrections" href="#corrections" data-pm-section-action>Correction Requests</a>';
                        const heading = '<header><a id="records" href="#records" data-pm-section-action>Back</a><h2>Correction Requests</h2></header>';
                        const slot = corrections ? '<div data-pm-header-slot hidden></div>' : '<div data-pm-header-slot>' + open + '</div>';
                        const page = '<div class="pm-page" data-pm-view="' + (corrections ? 'corrections' : 'records') + '">'
                            + '<section class="pc-requests"' + (corrections ? ' data-pm-correction-card' : '') + '>'
                            + (corrections ? heading : '') + '<p>Updated ' + window.fetchCount + '</p></section></div>';
                        resolve({ ok:true, text:async () => slot + page });
                    };
                    window.fail = () => reject(new Error('Network failure'));
                });
                JS);
            $browser->click('#corrections')->waitUntil('window.fetchCount === 1');
            $browser->script('window.complete()');
            $browser->waitUntil("document.querySelector('.pc-requests p').textContent==='Updated 1'");
            $browser->assertScript('location.hash', '#corrections');
            $browser->assertScript("window.heading === document.getElementById('heading')", true);
            $browser->assertScript("document.activeElement === document.querySelector('[data-pm-correction-card] header #records')", true);
            $browser->assertScript("!document.querySelector('[data-pm-view=records]') && document.querySelector('[data-pm-header-slot]').hidden", true);
            $browser->assertScript('window.transitionCount', 1);
            $browser->waitUntil("window.morph !== undefined");
            $browser->assertScript('window.morph', true);
            $browser->waitUntil("!document.documentElement.classList.contains('pm-section-switching')");
            $browser->click('#records')->waitUntil('window.fetchCount === 2');
            $browser->script('window.complete()');
            $browser->waitUntil("document.querySelector('.pc-requests p').textContent==='Updated 2'");
            $browser->assertScript('location.hash', '#records');
            $browser->assertScript("document.activeElement === document.querySelector('[data-pm-header-slot] #corrections')", true);
            $browser->assertScript('window.transitionCount', 2);
            $browser->waitUntil("!document.documentElement.classList.contains('pm-section-switching')");
            $browser->click('#corrections')->waitUntil('window.fetchCount === 3');
            $browser->script('window.fail()');
            $browser->waitFor('[data-pm-load-error]');
            $browser->assertScript('location.hash', '#records');
            $browser->assertScript("window.heading === document.getElementById('heading')", true);
            $browser->assertScript("document.querySelector('.pc-requests p').textContent", 'Updated 2');
            $browser->back()->waitUntil('window.fetchCount === 4');
            $browser->script('window.complete()');
            $browser->waitUntil("document.querySelector('.pc-requests p').textContent==='Updated 4'");
            $browser->assertScript('location.hash', '#corrections');
            $browser->assertScript("window.heading === document.getElementById('heading')", true);
            $browser->forward()->waitUntil('window.fetchCount === 5');
            $browser->script('window.complete()');
            $browser->waitUntil("document.querySelector('.pc-requests p').textContent==='Updated 5'");
            $browser->assertScript('location.hash', '#records');
            $browser->assertScript("window.heading === document.getElementById('heading')", true);
            $browser->waitUntil("!document.documentElement.classList.contains('pm-section-switching')");
            $browser->driver->executeCustomCommand('/session/:sessionId/goog/cdp/execute', 'POST', [
                'cmd' => 'Emulation.setEmulatedMedia',
                'params' => ['features' => [['name' => 'prefers-reduced-motion', 'value' => 'reduce']]],
            ]);
            $browser->assertScript("matchMedia('(prefers-reduced-motion: reduce)').matches", true);
            $browser->click('#corrections')->waitUntil('window.fetchCount === 6');
            $browser->script('window.complete()');
            $browser->waitUntil("document.querySelector('.pc-requests p').textContent==='Updated 6'");
            $browser->assertScript('window.transitionCount', 4);
        });
    }

    public function test_case_and_deceased_tabs_update_in_place_and_keep_errors_in_place(): void
    {
        $sources = '';
        foreach (['skeleton-lifecycle','skeleton-profiles','skeleton-components','panel-skeleton'] as $name) {
            $sources .= "\n".str_replace('export function','function',preg_replace('/^import .*;\r?\n/m','',file_get_contents(resource_path("js/$name.js"))));
        }
        $css = file_get_contents(resource_path('css/panel-skeleton.css'));
        foreach (['master_case'=>'class="master-records-page owner-master-records-page"', 'deceased'=>'class="records-page" data-deceased-records'] as $type=>$attributes) {
            $file = $type === 'master_case' ? 'master_case_tab_navigation' : 'deceased_tab_navigation';
            $handler = preg_replace('~</?script>~','',file_get_contents(resource_path("views/partials/$file.blade.php")));
            $tabs = $type === 'master_case' ? 'case-records-tabs' : 'table-system-quick-tabs';
            $html = '<!doctype html><html><meta name="viewport" content="width=device-width,initial-scale=1"><style>'.$css.'</style><body class="panel-shell-body"><aside>Sidebar</aside><main class="page-content"><h1 id="heading">Records</h1><section '.$attributes.'><div class="table-system-card"><nav class="'.$tabs.'"><a id="tab">Completed</a><a id="other">Active</a></nav><button id="filter">Filter</button><div class="table-system-list"><div class="table-system-wrap" style="height:260px"><table><thead><tr><th>Case</th></tr></thead><tbody><tr><td>Original record</td></tr></tbody></table></div></div><div class="table-system-pagination">One record</div></div></section></main><script type="module">'.$sources."\n".$handler.'</script>';
            $this->browse(function (Browser $browser) use ($html,$type) {
                $browser->driver->get('data:text/html;charset=utf-8,'.rawurlencode($html));
                $browser->waitUntil('window.PanelSkeleton !== undefined');
                $browser->script("window.originalHeading=document.getElementById('heading');['tab','other'].forEach(id=>document.getElementById(id).href=location.href+'#'+id);const next=document.querySelector('section').cloneNode(true);next.querySelector('td').textContent='Updated record';window.responseHTML=next.outerHTML;window.fetchCount=0;window.fetch=()=>new Promise((resolve,reject)=>{window.fetchCount++;window.complete=()=>resolve({ok:true,text:async()=>window.responseHTML});window.fail=()=>reject(new Error('Network failure'))})");
                $browser->click('#tab')->waitFor('.table-system-wrap .panel-skeleton');
                $browser->assertScript('window.fetchCount', 1);
                $browser->assertScript("document.querySelector('.page-content > .panel-skeleton') === null", true);
                $browser->assertScript("document.querySelector('#filter').inert || document.querySelector('nav').inert", false);
                $browser->script('window.complete()');
                $browser->waitUntil("document.querySelector('td').textContent === 'Updated record'");
                $browser->assertScript("window.originalHeading === document.getElementById('heading')", true);
                $browser->assertScript("location.hash", '#tab');
                $browser->click('#other')->waitUntil('window.fetchCount === 2');
                $browser->script('window.fail()');
                $browser->waitFor('[role=alert]');
                $browser->assertScript("location.hash", '#tab');
                $browser->assertScript("document.querySelector('td').textContent", 'Updated record');
                $browser->assertScript("window.originalHeading === document.getElementById('heading')", true);
                $browser->waitUntil("!document.querySelector('.panel-skeleton')");
            });
        }
    }
}

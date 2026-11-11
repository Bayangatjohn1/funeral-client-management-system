<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class PanelSkeletonTest extends DuskTestCase
{
    public function test_navigation_motion_is_scoped_and_the_destination_heading_is_stable(): void
    {
        $appCss = file_get_contents(resource_path('css/app.css'));
        $skeletonCss = file_get_contents(resource_path('css/panel-skeleton.css'));
        $layout = file_get_contents(resource_path('views/layouts/panel.blade.php'));

        $this->assertStringNotContainsString('animation: panelPageEnter 240ms ease-out both;', $appCss);
        $this->assertStringContainsString('animation: panelPageReveal 260ms ease-in-out both;', $appCss);
        $this->assertStringContainsString("html[data-sidebar-nav-entry='true'] .page-content", $appCss);
        $this->assertStringNotContainsString('@keyframes panelPageEnter', $appCss);
        $this->assertStringNotContainsString('@view-transition', $appCss);
        $this->assertMatchesRegularExpression("/html\\[data-sidebar-nav-entry='true'\\] \\.page-content\\s*\\{\\s*animation:\s*none;/", $appCss);
        $this->assertStringContainsString("html[data-sidebar-nav-entry='true']:not([data-sidebar-nav-settled='true']) .page-content", $appCss);
        $this->assertStringContainsString("document.documentElement.setAttribute('data-sidebar-nav-settled', 'true')", $layout);
        $this->assertStringContainsString('animation:panel-skeleton-navigation-enter 180ms ease-in-out', $skeletonCss);
        $this->assertStringContainsString('animation:panel-skeleton-exit 220ms', $skeletonCss);
        $this->assertStringNotContainsString('view-transition-name: panel-content;', $appCss);
        $this->assertStringNotContainsString('page-nav-transition', $appCss);
        $this->assertStringNotContainsString('case-records-tab-enter', $appCss);
        $this->assertStringNotContainsString("classList.remove('page-nav-transition')", $layout);
        $this->assertStringContainsString('window.SabanganNavigate', $layout);
        $this->assertStringContainsString('.panel-skeleton.is-navigation .panel-skeleton-component.is-header', $skeletonCss);
        $this->assertStringContainsString('justify-items:center;', $skeletonCss);
        $this->assertStringContainsString('animation:panel-skeleton-navigation-enter 180ms ease-in-out both;', $skeletonCss);
        $this->assertStringNotContainsString('transform:', substr($skeletonCss, strpos($skeletonCss, '.panel-skeleton.is-navigation {'), 180));
        $titleSurface = "@include('partials.page-title-surface')";
        $this->assertSame(1, substr_count($layout, $titleSurface));
        $this->assertLessThan(strpos($layout, '</head>'), strpos($layout, $titleSurface));

        foreach (['admin/branches/index.blade.php', 'admin/users/index.blade.php'] as $view) {
            $this->assertStringNotContainsString('x-transition:enter-start="opacity-0 translate-y-2"', file_get_contents(resource_path("views/$view")));
        }
    }

    public function test_tab_navigation_stays_in_results_and_yields_to_later_async_handlers(): void
    {
        $sources = '';
        foreach (['skeleton-lifecycle','skeleton-profiles','skeleton-components','panel-skeleton'] as $name) {
            $sources .= "\n".str_replace('export function','function',preg_replace('/^import .*;\r?\n/m','',file_get_contents(resource_path("js/$name.js"))));
        }
        $layout = file_get_contents(resource_path('views/layouts/panel.blade.php'));
        $start = strpos($layout, '        (function () {', strpos($layout, '// Authenticated pages must not be prefetched'));
        $end = strpos($layout, '        (function () {', $start + 20);
        $navigation = str_replace('window.location.href = targetUrl.href;', 'window.navigationDispatched=true;', substr($layout,$start,$end-$start));
        $css = file_get_contents(resource_path('css/panel-skeleton.css'));
        $html = '<!doctype html><html><meta name="viewport" content="width=device-width,initial-scale=1"><style>body{margin:20px}.page-content{height:700px}.table-system-wrap{height:240px}td{height:80px}.service-hub-panel{height:220px}</style><style>'.$css.'</style><body class="panel-shell-body"><div id="globalLoadingIndicator" hidden></div><aside><a id="sidebar">Dashboard</a></aside><main class="page-content"><h1>Page heading</h1><section class="owner-master-records-page"><nav class="case-records-tabs"><a id="status-tab">Completed</a></nav><button id="toolbar">Filter</button><div class="table-system-wrap"><table><thead><tr><th>Case</th></tr></thead><tbody><tr><td>Fixture case</td></tr></tbody></table></div></section><section data-service-hub><nav class="service-hub-tabs"><a id="service-tab">Caskets</a></nav><div class="service-hub-panel is-active"><article data-skeleton-item><h3>Casket</h3><p>Material</p></article></div></section></main><script type="module">'.$sources."\n".$navigation.'</script>';
        $this->browse(function (Browser $browser) use ($html) {
            $browser->driver->get('data:text/html;charset=utf-8,'.rawurlencode($html));
            $browser->waitUntil('window.PanelSkeleton !== undefined');
            $browser->script("['sidebar','status-tab','service-tab'].forEach(id=>document.getElementById(id).href=location.href+'#'+id);document.addEventListener('click',event=>{if(event.defaultPrevented||event.target.id!=='status-tab')return;event.preventDefault();window.asyncTabHandled=true;document.querySelector('.owner-master-records-page').classList.add('is-updating')},{once:true})");
            $browser->click('#status-tab')->waitFor('.table-system-wrap .panel-skeleton');
            $browser->assertScript('window.asyncTabHandled', true);
            $browser->assertScript('window.navigationDispatched === undefined', true);
            $browser->assertScript("document.querySelector('.page-content > .panel-skeleton') === null", true);
            $browser->script("document.querySelector('.owner-master-records-page').classList.remove('is-updating')");
            $browser->waitUntil("!document.querySelector('.panel-skeleton')");
            // The global handler must never force document navigation for tabs,
            // even when no page handler owns the link in this fixture.
            $browser->script("window.addEventListener('click',event=>{if(event.target.closest('.case-records-tabs,.service-hub-tabs')){window.tabPreventedByGlobal=event.defaultPrevented;event.preventDefault()}})");
            $browser->click('#status-tab');
            $browser->assertScript('window.tabPreventedByGlobal', false);
            $browser->assertScript("document.querySelector('.page-content > .panel-skeleton') === null", true);
            $browser->assertScript("document.querySelector('h1').inert || document.querySelector('#toolbar').inert || document.querySelector('.case-records-tabs').inert", false);
            $browser->click('#service-tab');
            $browser->assertScript('window.navigationDispatched === undefined', true);
            $browser->assertScript("document.querySelector('.page-content > .panel-skeleton') === null", true);
            $browser->assertScript("document.querySelector('.service-hub-tabs').inert", false);
            $browser->click('#sidebar')->waitFor('.page-content > .panel-skeleton.is-navigation');
            $browser->assertScript("document.querySelector('aside').inert", false);
            $browser->script('PanelSkeleton.reset()');
        });
    }

    public function test_refresh_preserves_table_headers_and_individual_card_surfaces(): void
    {
        $sources = '';
        foreach (['skeleton-lifecycle', 'skeleton-profiles', 'skeleton-components', 'panel-skeleton'] as $name) {
            $sources .= "\n".str_replace('export function', 'function', preg_replace('/^import .*;\r?\n/m', '', file_get_contents(resource_path("js/$name.js"))));
        }
        $css = file_get_contents(resource_path('css/panel-skeleton.css'));
        $html = '<!doctype html><html data-theme="light"><meta name="viewport" content="width=device-width,initial-scale=1"><style>body{margin:24px;background:#dce9e1;--card:#f4f8f5;--border:#aec7b7;font:16px Arial}.table-system-wrap{height:240px;overflow:auto;background:var(--card)}table{width:1200px;border-collapse:collapse}th{position:sticky;top:0;z-index:30;background:var(--card);height:48px;text-align:left}td{height:72px}.cards{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:24px;margin-top:24px}.service-package-card{height:220px;background:var(--card);border:1px solid var(--border);border-radius:18px;padding:20px;box-sizing:border-box}.sr-only{position:absolute;width:1px;height:1px;overflow:hidden;clip-path:inset(50%)}html[data-theme=dark] body{background:#14261b;--card:#112218;--border:#29523d;color:white}@media(max-width:600px){.cards{grid-template-columns:1fr}}</style><style>'.$css.'</style><body class="panel-shell-body"><main class="page-content"><section class="records-page"><div class="table-system-wrap"><table><thead><tr><th>Case</th><th>Client</th><th>Actions</th></tr></thead><tbody>'.str_repeat('<tr><td>FC0001</td><td>Fixture client</td><td>View Edit Print</td></tr>',5).'</tbody></table></div></section><section class="cards" id="catalog">'.str_repeat('<article class="service-package-card"><h3>Fixture casket</h3><p>Material and description</p><span class="badge">Available</span></article>',3).'</section></main><script type="module">'.$sources.'</script></body></html>';
        $this->browse(function (Browser $browser) use ($html) {
            $browser->driver->get('data:text/html;charset=utf-8,'.rawurlencode($html));
            $browser->waitUntil('window.PanelSkeleton !== undefined');
            $browser->script("window.rows=PanelSkeleton.begin(document.querySelector('.table-system-wrap'),{immediate:true});window.cards=PanelSkeleton.begin(document.querySelector('#catalog'),{immediate:true})");
            $browser->assertScript("document.querySelectorAll('#catalog [data-skeleton-component=mask]').length", 3);
            $browser->assertScript("getComputedStyle(document.querySelector('#catalog > .panel-skeleton')).backgroundColor", 'rgba(0, 0, 0, 0)');
            $browser->assertScript("[...document.querySelectorAll('.table-system-wrap [data-skeleton-shape]')].every(node=>node.getBoundingClientRect().top>=document.querySelector('tbody').getBoundingClientRect().top)", true);
            $browser->assertScript("document.querySelector('.table-system-wrap [data-skeleton-component=mask]').getBoundingClientRect().top>=document.querySelector('thead').getBoundingClientRect().bottom", true);
            $browser->assertScript("[...document.querySelectorAll('#catalog [data-skeleton-component=mask]')].every((mask,i)=>Math.abs(mask.getBoundingClientRect().width-document.querySelectorAll('.service-package-card')[i].getBoundingClientRect().width)<1)", true);
            $browser->screenshot('panel-skeleton-rows-and-card-gaps-light');
            $browser->script("document.querySelector('.table-system-wrap').scrollTop=100;document.querySelector('.table-system-wrap').scrollLeft=120");
            $browser->pause(100);
            $browser->assertScript("document.elementFromPoint(60,45).closest('th') !== null", true);
            $browser->script("document.documentElement.dataset.theme='dark'");
            $browser->driver->executeCustomCommand('/session/:sessionId/goog/cdp/execute', 'POST', ['cmd'=>'Emulation.setDeviceMetricsOverride','params'=>['width'=>390,'height'=>844,'deviceScaleFactor'=>1,'mobile'=>true]]);
            $browser->waitUntil("document.querySelector('#catalog > .panel-skeleton').clientWidth <= 342");
            $browser->assertScript("getComputedStyle(document.querySelector('#catalog > .panel-skeleton')).backgroundColor", 'rgba(0, 0, 0, 0)');
            $browser->assertScript("getComputedStyle(document.querySelector('#catalog [data-skeleton-component=mask]')).backgroundColor === getComputedStyle(document.querySelector('.service-package-card')).backgroundColor", true);
            $browser->screenshot('panel-skeleton-rows-and-card-gaps-dark-narrow');
            $browser->script('rows();cards()');
            $browser->waitUntil("document.querySelectorAll('.panel-skeleton').length === 0");
            $browser->assertScript("document.querySelector('.service-package-card').inert", false);
        });
    }

    public function test_async_panel_keeps_navigation_and_tab_headers_clickable(): void
    {
        $sources = '';
        foreach (['skeleton-lifecycle', 'skeleton-profiles', 'skeleton-components', 'panel-skeleton'] as $name) {
            $sources .= "\n".str_replace('export function', 'function', preg_replace('/^import .*;\r?\n/m', '', file_get_contents(resource_path("js/$name.js"))));
        }
        $css = file_get_contents(resource_path('css/panel-skeleton.css'));
        $html = '<!doctype html><style>.records-page.is-updating{pointer-events:none;opacity:.72;transform:translateY(2px)}'.$css.'</style><body class="panel-shell-body"><aside><button id="sidebar" onclick="window.sidebarClicked=true">Sidebar</button></aside><header><button id="topbar" onclick="window.topbarClicked=true">Topbar</button></header><main class="page-content"><section class="records-page"><nav><button id="tab" onclick="window.tabClicked=true">Active</button></nav><div class="table-system-wrap" style="height:300px"><table><tr><td>Case content</td></tr></table></div></section><section class="ba-shell" data-reports-module-shell><nav><button id="report-tab" onclick="window.reportClicked=true">Reports</button></nav><article class="ba-panel active" style="height:300px"><h2>Chart content</h2></article></section></main><script type="module">'.$sources.'</script>';
        $this->browse(function (Browser $browser) use ($html) {
            $browser->driver->get('data:text/html;charset=utf-8,'.rawurlencode($html));
            $browser->waitUntil('window.PanelSkeleton !== undefined');
            $browser->script("document.querySelector('.records-page').classList.add('is-updating')");
            $browser->waitFor('.table-system-wrap .panel-skeleton');
            $browser->click('#tab')->click('#sidebar')->click('#topbar');
            $browser->assertScript('window.tabClicked && window.sidebarClicked && window.topbarClicked', true);
            $browser->assertScript("document.querySelector('tbody').inert", true);
            $browser->script("document.documentElement.classList.add('ba-soft-loading')");
            $browser->waitFor('.ba-panel .panel-skeleton')->click('#report-tab');
            $browser->assertScript('window.reportClicked', true);
            $browser->assertScript("document.querySelector('.ba-shell > nav').inert", false);
            $browser->script("document.querySelector('.records-page').classList.remove('is-updating');document.documentElement.classList.remove('ba-soft-loading')");
            $browser->waitUntil("!document.querySelector('.panel-skeleton')");
            $browser->script("window.schedule=PanelSkeleton.begin(document.querySelector('.page-content'),{immediate:true,variant:'navigation',destination:'https://fixture.test/staff/reminders?schedule_section=upcoming'})");
            $browser->assertScript("document.querySelectorAll('[data-skeleton-component=calendar-week]').length", 1);
            $browser->click('#sidebar')->click('#topbar');
            $browser->assertScript("document.querySelector('aside').inert || document.querySelector('header').inert", false);
            $browser->screenshot('panel-skeleton-schedule-week');
            $browser->script('schedule()');
        });
    }

    public function test_sidebar_handler_paints_skeleton_before_dispatching_navigation(): void
    {
        $lifecycle = str_replace('export function', 'function', file_get_contents(resource_path('js/skeleton-lifecycle.js')));
        $script = preg_replace('/^import .*;\r?\n/m', '', file_get_contents(resource_path('js/panel-skeleton.js')));
        $script = str_replace('export function', 'function', file_get_contents(resource_path('js/skeleton-profiles.js')))."\n".str_replace('export function', 'function', file_get_contents(resource_path('js/skeleton-components.js')))."\n".$script;
        $layout = file_get_contents(resource_path('views/layouts/panel.blade.php'));
        $start = strpos($layout, '        (function () {', strpos($layout, '// Authenticated pages must not be prefetched'));
        $end = strpos($layout, '        (function () {', $start + 20);
        $navigation = substr($layout, $start, $end - $start);
        // Keep the real event handler/paint scheduling, replacing only the
        // final location assignment so this fixture cannot leave its page.
        $navigation = str_replace('window.location.href = targetUrl.href;', "window.navigationSawSkeleton = !!document.querySelector('.panel-skeleton');", $navigation);
        $css = file_get_contents(resource_path('css/panel-skeleton.css'));
        // Include the real shell cascade: a DOM-only presence assertion missed
        // the page decoration rule repositioning the overlay below old content.
        $manifest = json_decode(file_get_contents(public_path('build/manifest.json')), true);
        $css .= file_get_contents(public_path('build/'.$manifest['resources/css/app.css']['file']));
        $html = '<!doctype html><html data-theme="light"><head><meta name="viewport" content="width=device-width,initial-scale=1"><style>'.$css.'</style></head><body class="panel-shell-body"><div id="globalLoadingIndicator" hidden></div><aside><a id="sidebar-navigation">Next page</a></aside><main class="page-content" style="height:500px"><h1>Current page</h1></main><script type="module">'.$lifecycle."\n".$script."\n".$navigation.'</script></body></html>';
        $this->browse(function (Browser $browser) use ($html) {
            $browser->driver->get('data:text/html;charset=utf-8,'.rawurlencode($html));
            $browser->waitUntil('window.PanelSkeleton !== undefined');
            $browser->script("document.querySelector('#sidebar-navigation').href=location.href+'#destination'");
            try {
                $browser->driver->executeCustomCommand('/session/:sessionId/goog/cdp/execute', 'POST', ['cmd'=>'Emulation.setDeviceMetricsOverride','params'=>['width'=>1440,'height'=>900,'deviceScaleFactor'=>1,'mobile'=>false]]);
                $browser->click('#sidebar-navigation')->waitUntil('window.navigationSawSkeleton === true');
            } catch (\Throwable $error) {
                file_put_contents(storage_path('logs/skeleton-navigation-browser.json'), json_encode($browser->driver->manage()->getLog('browser'), JSON_PRETTY_PRINT));
                throw $error;
            }
            $browser->assertScript("document.querySelectorAll('.panel-skeleton').length", 1);
            $browser->assertScript("getComputedStyle(document.querySelector('.panel-skeleton')).position", 'absolute');
            $browser->assertScript("Number(getComputedStyle(document.querySelector('.panel-skeleton')).zIndex) >= 100", true);
            $browser->assertScript("getComputedStyle(document.querySelector('.panel-skeleton')).animationName", 'panel-skeleton-navigation-enter');
            $browser->assertScript("getComputedStyle(document.querySelector('.panel-skeleton [data-skeleton-component=header]')).justifyItems", 'center');
            $browser->assertScript("Math.abs(document.querySelector('.panel-skeleton').getBoundingClientRect().top-document.querySelector('.page-content').getBoundingClientRect().top)<1", true);
            $browser->assertScript("(()=>{const pane=document.querySelector('.page-content').getBoundingClientRect();return Boolean(document.elementFromPoint(pane.left+pane.width/2,pane.top+Math.min(100,pane.height/2))?.closest('.panel-skeleton'))})()", true);
            $browser->screenshot('panel-skeleton-navigation-real-shell-cascade');
            $browser->script('PanelSkeleton.reset()');
            $browser->assertScript("document.querySelector('.page-content').hasAttribute('aria-busy')", false);
        });
    }

    public function test_loading_states_restore_content_without_stale_or_duplicate_overlays(): void
    {
        // Standalone browser fixture: no application login, database, or live requests.
        $lifecycle = str_replace('export function', 'function', file_get_contents(resource_path('js/skeleton-lifecycle.js')));
        $script = preg_replace('/^import .*;\r?\n/m', '', file_get_contents(resource_path('js/panel-skeleton.js')));
        $script = str_replace('export function', 'function', file_get_contents(resource_path('js/skeleton-profiles.js')))."\n".str_replace('export function', 'function', file_get_contents(resource_path('js/skeleton-components.js')))."\n".$script;
        $css = file_get_contents(resource_path('css/panel-skeleton.css'));
        $html = '<!doctype html><html data-theme="light"><head><meta name="viewport" content="width=device-width,initial-scale=1"><style>body{margin:0;--card:#F4F8F5;--border:#AEC7B7}.page-content{padding:24px}.table-system-wrap{height:360px;overflow:auto}table{width:100%}td{height:64px}.sr-only{position:absolute;width:1px;height:1px;overflow:hidden;clip-path:inset(50%)}'.$css.'</style></head><body class="panel-shell-body"><main class="page-content"><h1>Case Records</h1><div class="records-page"><div class="table-system-wrap"><table><thead><tr><th>Case</th><th>Client</th><th>Actions</th></tr></thead><tbody><tr><td>Example case</td><td>Example client</td><td><button>View</button></td></tr></tbody></table></div></div><div id="details" style="height:300px"><button>Usable content</button></div></main><script type="module">'.$lifecycle."\n".$script.'</script></body></html>';
        $html = str_replace('.page-content{padding:24px}', 'html[data-theme=dark] body{--card:#112218;--border:#29523D;background:#0E1511;color:#F8FAFC}.page-content{padding:24px}', $html);
        $this->browse(function (Browser $browser) use ($html) {
            $browser->driver->get('data:text/html;charset=utf-8,'.rawurlencode($html));
            $browser->waitUntil('window.PanelSkeleton !== undefined');
            $browser->script("document.querySelector('.records-page').classList.add('is-updating')");
            $browser->waitFor('.panel-skeleton');
            $browser->assertScript("document.querySelectorAll('.panel-skeleton').length", 1);
            $browser->assertScript("document.querySelector('tbody').inert", true);
            $browser->assertScript("document.querySelectorAll('[data-skeleton-component=projection]').length", 1);
            $browser->screenshot('panel-skeleton-light-desktop');
            $browser->script("document.documentElement.dataset.theme='dark'");
            $browser->driver->executeCustomCommand('/session/:sessionId/goog/cdp/execute', 'POST', ['cmd'=>'Emulation.setDeviceMetricsOverride', 'params'=>['width'=>390,'height'=>844,'deviceScaleFactor'=>1,'mobile'=>true]]);
            $browser->waitUntil("document.querySelector('.panel-skeleton').clientWidth <= 342");
            $browser->screenshot('panel-skeleton-dark-narrow');
            $browser->assertScript('document.documentElement.scrollWidth <= window.innerWidth', true);
            $browser->script("document.querySelector('.records-page').classList.remove('is-updating')");
            $browser->waitUntil("!document.querySelector('.panel-skeleton')");
            $browser->assertScript("document.querySelector('tbody').inert", false);
            $browser->assertScript("document.querySelector('.table-system-wrap').hasAttribute('aria-busy')", false);
            $browser->script("window.first=PanelSkeleton.begin(document.querySelector('#details'));window.second=PanelSkeleton.begin(document.querySelector('#details'));first()");
            $browser->waitFor('.panel-skeleton');
            $browser->script('second(); PanelSkeleton.reset()');
            $browser->waitUntil("!document.querySelector('.panel-skeleton')");
            $browser->assertScript("document.querySelector('#details button').inert", false);
            $browser->script("document.querySelector('#details').innerHTML='<article data-skeleton-item style=\"padding:16px;border:1px solid;height:180px\"><span class=\"user-directory-avatar\" aria-hidden=\"true\" style=\"display:inline-block;width:32px;height:32px;border-radius:50%\">X</span><h3>Fixture person</h3><p>Longer fixture description</p><span class=\"status-badge\" style=\"display:inline-block;width:64px;height:24px;border-radius:12px\">Active</span></article>';window.items=PanelSkeleton.begin(document.querySelector('#details'))");
            $browser->waitFor('.panel-skeleton');
            $browser->assertScript("document.querySelectorAll('.panel-skeleton [data-skeleton-shape=circle]').length", 1);
            $browser->assertScript("Math.abs(document.querySelector('.panel-skeleton [data-skeleton-shape=pill]').getBoundingClientRect().width-document.querySelector('#details .status-badge').getBoundingClientRect().width)<1", true);
            $browser->assertScript("document.querySelector('.panel-skeleton').textContent.includes('Fixture person')", false);
            $browser->screenshot('panel-skeleton-measured-items');
            $browser->script('items()');
            $browser->script("window.destination=PanelSkeleton.begin(document.querySelector('.page-content'),{immediate:true,variant:'navigation',destination:'https://fixture.test/admin/service-management'})");
            $browser->assertScript("document.querySelector('.panel-skeleton').dataset.skeletonDestination", '/admin/service-management');
            $browser->assertScript("document.querySelector('.panel-skeleton').dataset.skeletonProfile", 'catalog');
            $browser->assertScript("document.querySelectorAll('.panel-skeleton [data-skeleton-component=header]').length", 1);
            $browser->assertScript("document.querySelectorAll('.panel-skeleton [data-skeleton-component=item]').length", 6);
            $browser->assertScript("document.querySelectorAll('.panel-skeleton [data-skeleton-component=table]').length", 0);
            $browser->screenshot('panel-skeleton-destination-catalog');
            $browser->script("destination();window.dashboard=PanelSkeleton.begin(document.querySelector('.page-content'),{immediate:true,variant:'navigation',destination:'https://fixture.test/staff'})");
            $browser->assertScript("document.querySelectorAll('.panel-skeleton [data-skeleton-component=metric]').length", 4);
            $browser->assertScript("document.querySelectorAll('.panel-skeleton [data-skeleton-component=chart-panel]').length", 2);
            $browser->driver->executeCustomCommand('/session/:sessionId/goog/cdp/execute', 'POST', ['cmd'=>'Emulation.setDeviceMetricsOverride','params'=>['width'=>1440,'height'=>900,'deviceScaleFactor'=>1,'mobile'=>false]]);
            $browser->script("document.documentElement.dataset.theme='light'");
            $browser->screenshot('panel-skeleton-destination-dashboard');
            $browser->script('dashboard()');
            $browser->assertScript("document.querySelector('#details').hasAttribute('aria-busy')", false);
            $browser->script("document.querySelector('#details').setAttribute('aria-busy','true')");
            $browser->waitFor('.panel-skeleton');
            $browser->script("document.querySelector('#details').removeAttribute('aria-busy')");
            $browser->waitUntil("!document.querySelector('.panel-skeleton')");
            $browser->script("window.oldLoad=PanelSkeleton.begin(document.querySelector('#details'))");
            $browser->waitFor('.panel-skeleton');
            $browser->script("document.querySelector('#details').innerHTML='<button>New content</button><table hidden><tr><td>Hidden table</td></tr></table>'; window.newLoad=PanelSkeleton.begin(document.querySelector('#details')); oldLoad()");
            $browser->assertScript("document.querySelectorAll('.panel-skeleton').length", 1);
            $browser->script('newLoad()');
            $browser->waitUntil("!document.querySelector('.panel-skeleton')");
            $browser->assertScript("document.querySelector('#details button').inert", false);
        });
    }
}

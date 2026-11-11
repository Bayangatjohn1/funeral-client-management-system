<?php

namespace Tests\Browser;

use Illuminate\Pagination\LengthAwarePaginator;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class DirectoryEmptySkeletonTest extends DuskTestCase
{
    public function test_empty_grid_and_table_remain_aligned_and_loading_follows_view_switches(): void
    {
        $sources = '';
        foreach (['skeleton-lifecycle','skeleton-profiles','skeleton-components','panel-skeleton'] as $name) {
            $sources .= "\n".str_replace('export function','function',preg_replace('/^import .*;\r?\n/m','',file_get_contents(resource_path("js/$name.js"))));
        }
        $sources .= "\n".file_get_contents(resource_path('js/record-results-navigation.js'));
        $styles = view('partials.directory_empty_states')->render();
        $css = file_get_contents(resource_path('css/panel-skeleton.css'));
        $pagination = (new LengthAwarePaginator([],0,20,1))->links('components.pagination.table')->toHtml();
        foreach (['branches','users'] as $kind) {
            $html = '<!doctype html><html data-theme="light"><meta name="viewport" content="width=device-width,initial-scale=1"><style>*{box-sizing:border-box}body{margin:20px;font:15px Arial;--card:#f4f8f5;--border:#aec7b7;--ink:#081d13;--ink-muted:#456457;--surface-muted:#e8f0eb;background:#dce9e1}html[data-theme=dark] body{--card:#112218;--border:#29523d;--ink:#e4eee8;--ink-muted:#adc4b7;--surface-muted:#20372a;background:#14261b;color:var(--ink)}[hidden]{display:none!important}.table-system-wrap{overflow:auto;border:1px solid var(--border)}table{width:100%;min-width:1000px;border-collapse:collapse}th{text-align:left;height:60px}.directory-empty-icon{font-size:24px}button{padding:10px}.table-paginator{display:flex;justify-content:space-between}</style><style>'.$css.'</style>'.$styles.'<body class="panel-shell-body"><h1 id="heading">Directory</h1><main class="page-content"><section data-record-results-page="'.$kind.'"><form method="GET"><input name="q"><button id="apply">Apply</button><button type="button" id="switch">Switch view</button></form><div data-record-results="cards"><div class="directory-empty-state" data-skeleton-empty><div class="directory-empty-icon" aria-hidden="true">○</div><div><h3>No records found</h3><p>Try changing the search or filters.</p></div><button id="add">Add record</button></div><footer class="table-system-pagination">'.$pagination.'</footer></div><div data-record-results="table" hidden><div class="table-system-list"><div class="table-system-wrap"><table><thead><tr><th>Record</th><th>Status</th></tr></thead><tbody><tr><td colspan="2" class="table-system-empty"><div class="directory-table-empty">No records found.</div></td></tr></tbody></table></div><footer class="table-system-pagination">'.$pagination.'</footer></div></div></section></main><script type="module">'.$sources.'</script>';
            $path = tempnam(storage_path('framework'),'directory-empty-'); rename($path,$path.'.html'); $path .= '.html'; file_put_contents($path,$html);
            try {
                $this->browse(function (Browser $browser) use ($path,$kind) {
                    $browser->driver->get('file:///'.str_replace('\\','/',$path));
                    $browser->driver->executeCustomCommand('/session/:sessionId/goog/cdp/execute','POST',['cmd'=>'Emulation.setDeviceMetricsOverride','params'=>['width'=>1440,'height'=>900,'deviceScaleFactor'=>1,'mobile'=>false]]);
                    $browser->waitUntil('window.PanelSkeleton !== undefined');
                    $browser->script("window.heading=document.getElementById('heading');document.querySelector('form').action=location.href;window.grid=document.querySelector('[data-record-results=cards]');window.tableView=document.querySelector('[data-record-results=table]');window.footerTop=grid.querySelector('footer').getBoundingClientRect().top;document.getElementById('switch').onclick=()=>{grid.hidden=!grid.hidden;tableView.hidden=!tableView.hidden};window.fetch=()=>new Promise(resolve=>window.complete=()=>{const root=document.querySelector('[data-record-results-page]').cloneNode(true);root.querySelectorAll('.panel-skeleton').forEach(node=>node.remove());root.querySelectorAll('[inert]').forEach(node=>node.removeAttribute('inert'));root.querySelectorAll('[data-panel-skeleton-owned-busy]').forEach(node=>{node.removeAttribute('aria-busy');node.removeAttribute('data-panel-skeleton-owned-busy');node.classList.remove('panel-skeleton-host')});resolve({ok:true,text:async()=>root.outerHTML})})");
                    $browser->click('#apply')->waitFor('[data-record-results=cards] > .panel-skeleton');
                    $browser->assertScript("document.querySelectorAll('.panel-skeleton [data-skeleton-shape=circle]').length",1);
                    $browser->assertScript("document.querySelectorAll('.panel-skeleton [data-skeleton-shape=field]').length",1);
                    $browser->assertScript("Math.abs(grid.querySelector('footer').getBoundingClientRect().top-window.footerTop)<1",true);
                    $browser->assertScript("(()=>{const mask=grid.querySelector('[data-skeleton-component=mask]').getBoundingClientRect(),button=document.getElementById('add').getBoundingClientRect();return mask.top<=button.top&&mask.bottom>=button.bottom})()",true);
                    $browser->click('#switch')->waitFor('[data-record-results=table] > .panel-skeleton');
                    $browser->waitUntil("!grid.querySelector('.panel-skeleton')");
                    $browser->assertScript("Math.abs(tableView.querySelector('footer').getBoundingClientRect().top-window.footerTop)<1",true);
                    $browser->assertScript("document.querySelector('.page-content > .panel-skeleton') === null",true);
                    $browser->script('window.complete()');
                    $browser->waitUntil("!document.querySelector('.panel-skeleton')");
                    $browser->assertScript("window.heading===document.getElementById('heading')",true);
                    $browser->driver->executeCustomCommand('/session/:sessionId/goog/cdp/execute','POST',['cmd'=>'Emulation.setDeviceMetricsOverride','params'=>['width'=>390,'height'=>844,'deviceScaleFactor'=>1,'mobile'=>true]]);
                    $browser->script("document.documentElement.dataset.theme='dark'");
                    $browser->assertScript("document.querySelector('.directory-table-empty').getBoundingClientRect().right<=innerWidth",true);
                    $browser->click('#apply')->waitFor('[data-record-results=table] > .panel-skeleton');
                    $browser->click('#switch')->waitFor('[data-record-results=cards] > .panel-skeleton');
                    $browser->screenshot('directory-empty-'.$kind.'-dark-narrow-loading');
                    $browser->script('window.complete()');
                    $browser->waitUntil("!document.querySelector('.panel-skeleton')");
                    $browser->screenshot('directory-empty-'.$kind.'-dark-narrow-ready');
                    $browser->assertScript("document.getElementById('add').inert || document.querySelector('form').inert",false);
                });
            } finally { if(is_file($path)) unlink($path); }
        }
    }
}

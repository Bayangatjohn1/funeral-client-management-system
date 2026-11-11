<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class RecordResultsNavigationTest extends DuskTestCase
{
    public function test_payment_pending_state_leaves_summary_filters_tabs_and_footer_outside_mask(): void
    {
        $sources = '';
        foreach (['skeleton-lifecycle','skeleton-profiles','skeleton-components','panel-skeleton'] as $name) {
            $sources .= "\n".str_replace('export function','function',preg_replace('/^import .*;\r?\n/m','',file_get_contents(resource_path("js/$name.js"))));
        }
        preg_match('~<style>(.*?)</style>~s',file_get_contents(resource_path('views/staff/payments/history.blade.php')),$paymentCss);
        $css = $paymentCss[1].file_get_contents(resource_path('css/panel-skeleton.css'));
        $html = '<!doctype html><html><meta name="viewport" content="width=device-width,initial-scale=1"><style>'.$css.'</style><body class="panel-shell-body"><main class="page-content"><section class="pm-page"><nav class="pm-section-tabs"><button id="tab">Records</button></nav><section class="pm-kpis">Metrics</section><form><button id="filter" type="button">Filter</button></form><div class="pm-panel"><div class="pm-records-body" style="height:240px"><table><thead><tr><th>Case</th></tr></thead><tbody><tr><td>Payment case</td></tr></tbody></table></div><footer>Pagination</footer></div></section></main><script type="module">'.$sources.'</script>';
        $this->browse(function (Browser $browser) use ($html) {
            $browser->driver->get('data:text/html;charset=utf-8,'.rawurlencode($html));
            $browser->waitUntil('window.PanelSkeleton !== undefined');
            $browser->script("document.querySelector('.pm-page').classList.add('is-loading')");
            $browser->waitFor('.pm-records-body > .panel-skeleton');
            $browser->assertScript("document.querySelector('.page-content > .panel-skeleton') === null", true);
            $browser->assertScript("document.querySelector('.pm-kpis').inert || document.querySelector('form').inert || document.querySelector('nav').inert || document.querySelector('footer').inert", false);
            $browser->assertScript("getComputedStyle(document.querySelector('.pm-kpis')).opacity", '1');
            $browser->script("document.querySelector('.pm-page').classList.remove('is-loading')");
            $browser->waitUntil("!document.querySelector('.panel-skeleton')");
        });
    }

    public function test_directory_and_audit_filters_replace_only_results_and_keep_document_controls(): void
    {
        $sources = '';
        foreach (['skeleton-lifecycle','skeleton-profiles','skeleton-components','panel-skeleton'] as $name) {
            $sources .= "\n".str_replace('export function','function',preg_replace('/^import .*;\r?\n/m','',file_get_contents(resource_path("js/$name.js"))));
        }
        $layout = file_get_contents(resource_path('views/layouts/panel.blade.php'));
        $start = strpos($layout, '        (function () {', strpos($layout, '// Authenticated pages must not be prefetched'));
        $end = strpos($layout, '        (function () {', $start+20);
        $sources .= "\n".str_replace('window.location.href = targetUrl.href;', 'window.navigationDispatched=true;', substr($layout,$start,$end-$start));
        $sources .= "\n".file_get_contents(resource_path('js/record-results-navigation.js'));
        preg_match('~<script>(.*?)</script>~s',file_get_contents(resource_path('views/partials/user_information_modals.blade.php')),$dialogScript);
        $sources .= "\nwindow.recordDialogMotion={open:dialog=>dialog.showModal(),close:dialog=>dialog.close()};\n".$dialogScript[1];
        $css = file_get_contents(resource_path('css/panel-skeleton.css'));
        foreach (['branches','users','audit'] as $kind) {
            $parts = $kind === 'audit'
                ? '<div data-record-results="list"><div class="table-system-wrap"><table><thead><tr><th>Record</th></tr></thead><tbody><tr><td>Original</td></tr></tbody></table></div></div>'
                : '<div data-record-results="cards"><article data-skeleton-item><h3>Original</h3><p>Card description</p></article></div><div data-record-results="table" hidden><table><tr><td>Original</td></tr></table></div>';
            if ($kind === 'users') $parts = str_replace('</article>','<button id="user-view" data-user-dialog="user-information-1">View</button></article>',$parts);
            $dialogs = $kind === 'users' ? '<div data-record-results-dialogs="users"><dialog class="user-information-dialog" id="user-information-1"><p>Original user information</p><button data-user-dialog-close>Close</button></dialog></div>' : '';
            $html = '<!doctype html><html data-theme="light"><meta name="viewport" content="width=device-width,initial-scale=1"><style>body{margin:24px} [data-record-results]{min-height:260px}article{height:220px;border:1px solid #aec7b7;padding:16px}td{height:80px}</style><style>'.$css.'</style><body class="panel-shell-body"><h1 id="heading">Directory</h1><main class="page-content"><div data-record-results-page="'.$kind.'"><section id="summary">Summary</section><form method="GET"><input name="q"><select name="status"><option value="">All</option><option value="active">Active</option></select><button type="submit" id="apply">Apply</button><button type="button" id="view">Table view</button></form>'.$parts.'</div></main><script type="module">'.$sources.'</script></body></html>';
            $html = str_replace('</main>','</main>'.$dialogs.'<div id="globalLoadingIndicator" hidden></div>',$html);
            $path = tempnam(storage_path('framework'),'record-results-');
            rename($path,$path.'.html'); $path .= '.html'; file_put_contents($path,$html);
            try {
                $this->browse(function (Browser $browser) use ($path,$kind) {
                    $browser->driver->get('file:///'.str_replace('\\','/',$path));
                    $browser->waitUntil('window.PanelSkeleton !== undefined');
                    $browser->script("window.originalHeading=document.getElementById('heading');window.originalForm=document.querySelector('form');window.originalSummary=document.getElementById('summary');document.querySelector('form').action=location.href;window.fetchCount=0;window.fetch=()=>new Promise((resolve,reject)=>{window.fetchCount++;window.complete=()=>{const root=document.querySelector('[data-record-results-page]').cloneNode(true);root.querySelectorAll('.panel-skeleton').forEach(node=>node.remove());root.querySelectorAll('[inert]').forEach(node=>node.removeAttribute('inert'));root.querySelectorAll('[data-panel-skeleton-owned-busy]').forEach(node=>{node.removeAttribute('aria-busy');node.removeAttribute('data-panel-skeleton-owned-busy');node.classList.remove('panel-skeleton-host')});root.querySelectorAll('td,h3').forEach(node=>node.textContent='Updated '+window.fetchCount);resolve({ok:true,text:async()=>root.outerHTML})};window.fail=()=>reject(new Error('Network failure'))});document.querySelector('[name=q]').value='new query'");
                    $browser->script("window.originalDialog=document.querySelector('dialog');const dialogs=document.querySelector('[data-record-results-dialogs]')?.cloneNode(true);dialogs?.querySelectorAll('[data-result-dialog-bound]').forEach(node=>node.removeAttribute('data-result-dialog-bound'));window.responseDialogs=dialogs?.outerHTML.replace('Original user information','Updated user information')||'';const originalFetch=window.fetch;window.fetch=()=>originalFetch().then(response=>({ok:response.ok,text:async()=>(await response.text())+window.responseDialogs}))");
                    $browser->click('#apply')->waitFor('[data-record-results] > .panel-skeleton');
                    $browser->assertScript("document.querySelector('.page-content > .panel-skeleton') === null", true);
                    $browser->assertScript("document.querySelector('form').inert || document.getElementById('summary').inert", false);
                    $browser->assertScript("document.querySelectorAll('.panel-skeleton').length", 1);
                    $browser->script('window.complete()');
                    $browser->waitUntil("document.querySelector('[data-record-results]').textContent.includes('Updated 1')");
                    $browser->assertScript("window.originalForm===document.querySelector('form') && window.originalHeading===document.getElementById('heading') && window.originalSummary===document.getElementById('summary')", true);
                    $browser->assertScript("new URLSearchParams(location.search).get('q')", 'new query');
                    if ($kind === 'users') {
                        $browser->assertScript("window.originalDialog !== document.querySelector('dialog')", true);
                        $browser->click('#user-view')->waitFor('dialog[open]');
                        $browser->assertScript("document.querySelector('dialog').textContent.includes('Updated user information')", true);
                        $browser->script("document.querySelector('dialog').dispatchEvent(new Event('cancel',{cancelable:true}))");
                        $browser->assertScript("document.querySelector('dialog').open", false);
                    }
                    $browser->script("document.querySelector('[name=status]').value='active'");
                    $browser->click('#apply')->waitUntil('window.fetchCount===2');
                    $browser->script('window.fail()');
                    $browser->waitFor('[data-record-results-error]');
                    $browser->assertScript("document.querySelector('[data-record-results]').textContent.includes('Updated 1')", true);
                    $browser->assertScript("window.originalForm===document.querySelector('form')", true);
                    $browser->waitUntil("!document.querySelector('.panel-skeleton')");
                });
            } finally { if (is_file($path)) unlink($path); }
        }
    }
}

<?php

namespace Tests\Browser;

use Illuminate\Pagination\LengthAwarePaginator;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class RecordsEmptyStateTest extends DuskTestCase
{
    public function test_empty_and_short_results_keep_footer_position_and_disabled_pagination(): void
    {
        $paginator = new LengthAwarePaginator([], 0, 25, 5, ['path'=>'https://fixture.test/cases']);
        $pagination = $paginator->links('components.pagination.table')->toHtml();
        $styles = view('partials.records_results_layout_styles')->render();
        $appCss = file_get_contents(resource_path('css/app.css'));
        $start = strpos($appCss, '    .table-paginator {');
        $end = strpos($appCss, '    .table-system-empty {', $start);
        $paginatorCss = substr($appCss, $start, $end-$start);
        $empty = '<tr><td colspan="3" class="table-system-empty"><div class="records-empty-state">No case records found.</div></td></tr>';
        $html = '<!doctype html><html data-theme="light"><meta name="viewport" content="width=device-width,initial-scale=1"><style>body{margin:24px;font:15px Arial;background:#dce9e1;--card:#f4f8f5;--border:#aec7b7;--ink-muted:#456457;--surface-muted:#e9f1ec;--ink-faint:#74887b}.table-system-wrap{overflow:auto;border:1px solid var(--border);border-radius:6px}table{width:100%;min-width:1200px;border-collapse:collapse}th{height:64px;text-align:left}td{padding:18px}.pm-panel{display:flex;flex-direction:column;overflow:hidden;border:1px solid var(--border)}.table-system-pagination{margin-bottom:24px}.pm-row-list{display:flex;flex-direction:column}html[data-theme=dark] body{--card:#112218;--border:#29523d;--ink-muted:#adc4b7;--surface-muted:#20372a;--ink-faint:#8da599;background:#14261b;color:#e4eee8}'.$paginatorCss.'</style>'.$styles.'<body class="panel-shell-body"><main class="page-content"><section class="master-records-page"><div class="table-system-list"><div class="table-system-wrap"><table><thead><tr><th>Case</th><th>Client</th><th>Actions</th></tr></thead><tbody>'.$empty.'</tbody></table></div></div><footer class="table-system-pagination">'.$pagination.'</footer></section><section class="payment-monitoring-page pm-page"><div class="pm-panel ops-list-panel"><div class="pm-records-body ops-list-body"><div class="pm-row-list"><div class="pm-empty ops-empty records-empty-state">No payment records found for the selected filters.</div></div></div><footer class="pm-foot">'.$pagination.'</footer></div></section></main></body></html>';
        $this->browse(function (Browser $browser) use ($html,$empty) {
            $browser->driver->get('data:text/html;charset=utf-8,'.rawurlencode($html));
            $browser->assertScript("document.querySelectorAll('.table-paginator a').length", 0);
            $browser->assertScript("document.querySelectorAll('.table-paginator .is-active').length", 0);
            $browser->assertScript("document.querySelectorAll('.table-paginator-meta')[0].textContent.trim()", '0 records');
            $browser->assertScript("document.querySelector('.table-system-wrap').clientHeight >= 320", true);
            $browser->script("window.caseFooter=document.querySelector('.table-system-pagination').getBoundingClientRect().top;window.paymentFooter=document.querySelector('.pm-foot').getBoundingClientRect().top;document.querySelector('tbody').innerHTML='<tr><td>FC0001</td><td>Fixture client</td><td>View</td></tr>';document.querySelector('.pm-row-list').innerHTML='<article style=\"height:80px\">Payment case</article>'");
            $browser->assertScript("Math.abs(document.querySelector('.table-system-pagination').getBoundingClientRect().top-window.caseFooter)<1", true);
            $browser->assertScript("Math.abs(document.querySelector('.pm-foot').getBoundingClientRect().top-window.paymentFooter)<1", true);
            $browser->script('document.querySelector("tbody").innerHTML='.json_encode($empty).';document.querySelector(".pm-row-list").innerHTML=\'<div class="pm-empty ops-empty records-empty-state">No payment records found for the selected filters.</div>\'');
            $browser->screenshot('records-empty-footer-light-desktop');
            $browser->driver->executeCustomCommand('/session/:sessionId/goog/cdp/execute', 'POST', ['cmd'=>'Emulation.setDeviceMetricsOverride','params'=>['width'=>390,'height'=>1000,'deviceScaleFactor'=>1,'mobile'=>true]]);
            $browser->script("document.documentElement.dataset.theme='dark'");
            $browser->assertScript('document.documentElement.scrollWidth <= window.innerWidth', true);
            $browser->assertScript("document.querySelector('.records-empty-state').getBoundingClientRect().right <= window.innerWidth", true);
            $browser->assertScript("document.querySelector('.table-system-pagination').getBoundingClientRect().bottom <= document.querySelector('.payment-monitoring-page').getBoundingClientRect().top", true);
            $browser->screenshot('records-empty-footer-dark-narrow');
        });
    }
}

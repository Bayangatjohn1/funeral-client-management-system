<?php

namespace Tests\Browser;

use App\Models\{Branch,User,Client,Deceased,FuneralCase,Package,Payment};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class PaymentUiConsistencyTest extends DuskTestCase
{
    use RefreshDatabase;

    public function createApplication()
    {
        $app = parent::createApplication();
        if (($_ENV['DB_CONNECTION'] ?? null) !== 'sqlite' || ($_ENV['DB_DATABASE'] ?? null) !== ':memory:') throw new \RuntimeException('UI fixtures require in-memory SQLite.');
        $app['config']->set('database.default','sqlite');
        $app['config']->set('database.connections.sqlite.database',':memory:');
        return $app;
    }

    public function test_real_role_views_use_balanced_controls_and_nonmodal_date_ranges(): void
    {
        $branch = Branch::create(['branch_code'=>'PM-VISUAL','branch_name'=>'Visual branch','address'=>'Test','type'=>'main','is_active'=>true]);
        $client = Client::create(['branch_id'=>$branch->id,'full_name'=>'Client with a longer family name for layout review','relationship_to_deceased'=>'Spouse','contact_number'=>'09170000000','address'=>'Test']);
        $deceased = Deceased::create(['branch_id'=>$branch->id,'client_id'=>$client->id,'full_name'=>'Deceased with a longer family name','date_of_death'=>now()->subDay()->toDateString(),'age'=>70]);
        $package = Package::create(['name'=>'UI fixture package','coffin_type'=>'Standard','price'=>10000,'is_active'=>true]);
        $case = FuneralCase::create(['branch_id'=>$branch->id,'client_id'=>$client->id,'deceased_id'=>$deceased->id,'package_id'=>$package->id,'case_number'=>FuneralCase::nextCaseNumber($branch->id),'case_code'=>'UI-CASE-001','service_type'=>'Burial','service_requested_at'=>now()->toDateString(),'wake_location'=>'Test Wake','funeral_service_at'=>now()->addDay()->toDateString(),'service_package'=>$package->name,'coffin_type'=>'Standard','total_amount'=>10000,'subtotal_amount'=>10000,'total_paid'=>0,'balance_amount'=>10000,'payment_status'=>'UNPAID','case_status'=>'ACTIVE','entry_source'=>'MAIN','verification_status'=>'VERIFIED']);
        Payment::create(['funeral_case_id'=>$case->id,'branch_id'=>$branch->id,'amount'=>2500,'reference_number'=>'PM-SEARCH-REF','payment_method'=>'cash','paid_at'=>now(),'paid_date'=>now()->toDateString(),'payment_status_after_payment'=>'PARTIAL','balance_after_payment'=>7500]);
        $manifest = json_decode(file_get_contents(public_path('build/manifest.json')),true);
        $css = file_get_contents(public_path('build/'.$manifest['resources/css/app.css']['file']));
        foreach ($manifest['resources/js/app.js']['css'] as $file) $css .= file_get_contents(public_path('build/'.$file));
        $bundle = file_get_contents(public_path('build/'.$manifest['resources/js/app.js']['file']));
        $css = str_replace('url(./','url(file:///'.str_replace('\\','/',public_path('build/assets')).'/',$css);
        foreach ([['staff',null,'payments.history'],['admin','branch','admin.payments.index'],['admin','system','admin.payments.index']] as [$role,$scope,$route]) {
            $actor = User::factory()->create(['role'=>$role,'admin_scope'=>$scope,'branch_id'=>$branch->id,'is_active'=>true]);
            if ($actor->requiresMfa()) {
                $actor->two_factor_secret = (new \PragmaRX\Google2FA\Google2FA)->generateSecretKey();
                $actor->two_factor_confirmed_at = now(); $actor->save();
            }
            $this->withSession(['idle_last_activity'=>now()->timestamp,'idle_session_key'=>'payment-ui-fixture','account_security'=>\App\Support\AccountSecurity::fingerprint($actor),'mfa_user_id'=>$actor->requiresMfa()?$actor->id:null]);
            foreach (['summary'=>'paymentCases','transactions'=>'transactionCases'] as $tab=>$viewKey) {
                foreach (['UI-CASE-001','Client with a longer','Deceased with a longer','PM-SEARCH-REF'] as $term) {
                    $this->actingAs($actor)->get(route($route,['tab'=>$tab,'q'=>$term]))->assertOk()->assertViewHas($viewKey,fn($rows)=>$rows->getCollection()->contains('id',$case->id));
                }
                $this->actingAs($actor)->get(route($route,['tab'=>$tab,'q'=>'no-such-record']))->assertOk()->assertViewHas($viewKey,fn($rows)=>$rows->isEmpty());
            }
            $this->actingAs($actor)->get(route('payments.history.print',$case))->assertOk()->assertSee('UI-CASE-001');
            $html = $this->followingRedirects()->actingAs($actor)->get(route($route))->assertOk()->assertSee('id="pmFilterForm"',false)->getContent();
            $html = preg_replace('~<link\b[^>]*href="[^"]*/build/[^"]*"[^>]*>~i','',$html);
            $html = preg_replace('~<script\b[^>]*src="[^"]*/build/[^"]*"[^>]*>\s*</script>~i','',$html);
            $html = str_replace(rtrim(config('app.url'),'/').'/vendor/','file:///'.str_replace('\\','/',public_path('vendor')).'/',$html);
            $html = str_replace('</head>','<style>'.$css.'</style><script type="module">'.str_ireplace('</script','<\/script',$bundle).'</script></head>',$html);
            $path = tempnam(storage_path('framework'),'payment-ui-'); rename($path,$path.'.html'); $path .= '.html'; file_put_contents($path,$html);
            try {
                $this->browse(function (Browser $browser) use ($path,$role,$scope) {
                    $browser->driver->get('file:///'.str_replace('\\','/',$path));
                    $browser->driver->executeCustomCommand('/session/:sessionId/goog/cdp/execute','POST',['cmd'=>'Emulation.setDeviceMetricsOverride','params'=>['width'=>1440,'height'=>1000,'deviceScaleFactor'=>1,'mobile'=>false]]);
                    $browser->waitUntil("window.PanelSkeleton !== undefined && document.querySelector('#pmPerPage').dataset.filterSelectReady === '1'");
                    $browser->script("document.documentElement.dataset.theme='light';window.fetchCount=0;window.fetch=async(url)=>{window.lastSearchUrl=url;window.fetchCount++;throw new Error('Isolated fixture has no live requests')}");
                    $browser->script("const e=document.querySelector('[data-pm-search-input]');e.value='Client';e.dispatchEvent(new Event('input',{bubbles:true}))");
                    $browser->assertScript('window.fetchCount',0);
                    $browser->assertScript("document.querySelector('[data-pm-search-suggestions]')===null",true);
                    $browser->assertScript("(()=>{const e=document.querySelector('.pm-search-match'),r=[...e.children].map(x=>x.getBoundingClientRect());return r.every(x=>Math.abs((x.top+x.bottom)/2-(r[0].top+r[0].bottom)/2)<1&&x.height<26)})()",true);
                    $browser->assertScript("(()=>{const e=document.querySelector('.pm-search-match mark');return e.textContent.toLowerCase()==='client'&&getComputedStyle(e).textDecorationLine.includes('underline')})()",true);
                    $browser->assertScript("(()=>{const s=document.querySelector('#pmFilterForm .pm-field.search'),nodes=[...document.querySelectorAll('.pm-more-filters,.pm-sort-control,.pm-page-size')],positions=nodes.map(e=>e.getBoundingClientRect().left),saved=s.getAttribute('style');const rect=s.getBoundingClientRect(),gap=nodes[0].getBoundingClientRect().left-rect.right;s.style.setProperty('width','100%','important');s.style.setProperty('max-width','none','important');const stable=nodes.every((e,i)=>Math.abs(e.getBoundingClientRect().left-positions[i])<1);if(saved===null)s.removeAttribute('style');else s.setAttribute('style',saved);return stable&&rect.width<=560&&gap>=36})()",true);
                    $browser->screenshot('payment-search-simple-'.($scope??$role));
                    $browser->click('[data-pm-match-value]');
                    $browser->assertScript("new URL(window.lastSearchUrl).searchParams.get('q')",'UI-CASE-001');
                    $browser->script("const e=document.querySelector('[data-pm-search-input]');e.value='Client';e.dispatchEvent(new Event('input',{bubbles:true}))" );
                    $browser->click('[data-pm-search-submit]');
                    $browser->assertScript("new URL(window.lastSearchUrl).searchParams.get('q')",'Client');
                    $browser->click('[data-pm-search-clear]');
                    $browser->assertScript("new URL(window.lastSearchUrl).searchParams.has('q')",false);
                    $browser->script("window.fetch=async(url)=>{window.matchQuery=url;return {ok:true,text:async()=>'<div class=\"pm-page\"><div data-pm-match-case=\"REMOTE-002\" data-pm-match-client=\"Remote Client\" data-pm-match-deceased=\"Remote Deceased\"></div></div>'}};const e=document.querySelector('[data-pm-search-input]');e.value='Remote';e.dispatchEvent(new Event('input',{bubbles:true}))");
                    $browser->waitUntil("document.querySelector('[data-pm-match-value]')?.dataset.pmMatchValue==='REMOTE-002'");
                    $browser->assertScript("new URL(window.matchQuery).searchParams.get('q')",'Remote');
                    $browser->assertScript("document.querySelector('.pm-case').textContent",'UI-CASE-001');
                    $browser->script("document.querySelector('[data-pm-search-input]').dispatchEvent(new KeyboardEvent('keydown',{key:'Escape',bubbles:true}));window.fetch=async(url)=>{window.lastSearchUrl=url;window.fetchCount++;throw new Error('Isolated fixture has no live requests')}");
                    $browser->script("const e=document.querySelector('[data-pm-search-input]');e.value='  Deceased  ';e.dispatchEvent(new Event('input',{bubbles:true}));e.dispatchEvent(new KeyboardEvent('keydown',{key:'Enter',bubbles:true}))");
                    $browser->assertScript("new URL(window.lastSearchUrl).searchParams.get('q')",'Deceased');
                    $browser->assertScript("!new URL(window.lastSearchUrl).searchParams.has('page')&&!new URL(window.lastSearchUrl).searchParams.has('transactions_page')",true);
                    $browser->click('[data-pm-search-clear]');
                    $browser->script("window.searchSignals=[];window.fetch=(url,options)=>{window.lastSearchUrl=url;window.searchSignals.push(options.signal);return new Promise((resolve,reject)=>options.signal.addEventListener('abort',()=>reject(new DOMException('Canceled','AbortError'))))};const e=document.querySelector('[data-pm-search-input]');for(const term of ['first-query','latest-query']){e.value=term;e.dispatchEvent(new Event('input',{bubbles:true}));e.dispatchEvent(new KeyboardEvent('keydown',{key:'Enter',bubbles:true}))}");
                    $browser->assertScript('window.searchSignals.length',2);
                    $browser->assertScript('window.searchSignals[0].aborted',true);
                    $browser->assertScript("new URL(window.lastSearchUrl).searchParams.get('q')",'latest-query');
                    $browser->script("window.fetch=async(url)=>{window.lastSearchUrl=url;window.fetchCount++;throw new Error('Isolated fixture has no live requests')}");
                    $browser->click('[data-pm-search-clear]');
                    $browser->assertScript("getComputedStyle(document.querySelector('.pm-row-title')).fontWeight",'400');
                    $browser->assertScript("(()=>{const e=document.querySelector('.pm-date-value');return e.scrollWidth<=e.clientWidth&&e.getBoundingClientRect().height<=parseFloat(getComputedStyle(e).lineHeight)+1})()",true);
                    $browser->assertScript("getComputedStyle(document.querySelector('#pmFilterForm .pm-field.search')).borderTopLeftRadius",'14px');
                    $browser->assertScript("getComputedStyle(document.querySelector('#pmFilterForm .pm-field.search > input')).borderTopLeftRadius",'14px');
                    $browser->assertScript("Math.abs(document.querySelector('.pm-more-filters summary').getBoundingClientRect().width-document.querySelector('.pm-sort-control').getBoundingClientRect().width)<1",true);
                    $browser->assertScript("(()=>{const a=getComputedStyle(document.querySelector('.pm-more-filters summary')).backgroundColor;return a===getComputedStyle(document.querySelector('.pm-sort-control select')).backgroundColor&&a===getComputedStyle(document.querySelector('.pm-page-size-control')).backgroundColor})()",true);
                    $browser->assertScript("(()=>{const e=document.querySelector('[data-pm-open-transactions-modal]');return getComputedStyle(e).color===getComputedStyle(e.querySelector('i')).color})()",true);
                    $browser->assertScript("(()=>{const h=document.querySelector('.pm-record-head'),r=document.querySelector('.pm-case-row');return h.children.length===5&&Array.from(h.children).every((e,i)=>Math.abs(e.getBoundingClientRect().left-r.children[i].getBoundingClientRect().left)<1)})()",true);
                    $browser->assertScript("(()=>{const a=document.querySelector('.pm-row-actions'),p=a.querySelector('.pm-case-history-print');return p.getAttribute('aria-label')==='Print payment history'&&p.querySelector('.pm-action-tooltip').textContent==='Print payment history'&&a.scrollWidth<=a.clientWidth})()",true);
                    $browser->screenshot('payment-row-spacing-'.($scope??$role));
                    $browser->mouseover('[data-pm-open-transactions-modal]');
                    $browser->assertScript("(()=>{const e=document.querySelector('[data-pm-open-transactions-modal] .pm-action-tooltip'),r=e.getBoundingClientRect();return e.matches(':popover-open')&&getComputedStyle(e).visibility==='visible'&&getComputedStyle(e).display!=='none'&&r.bottom<=e.parentElement.getBoundingClientRect().top})()",true);
                    $browser->assertScript("(()=>{const e=document.querySelector('[data-pm-open-transactions-modal]');return getComputedStyle(e).color!==getComputedStyle(e.querySelector('i')).color})()",true);
                    $browser->assertScript("getComputedStyle(document.querySelector('[data-pm-open-transactions-modal]')).backgroundColor",'rgba(0, 0, 0, 0)');
                    $browser->assertScript("getComputedStyle(document.querySelector('[data-pm-open-transactions-modal] > i')).filter",'none');
                    $browser->click('[data-pm-open-transactions-modal]')->waitFor('.pm-transactions-backdrop.open');
                    $browser->assertVisible('.pm-transactions-backdrop.open .pm-ledger-table');
                    $browser->assertScript("document.querySelectorAll('.pm-action-tooltip:popover-open').length",0);
                    $browser->screenshot('payment-history-modal-'.($scope??$role));
                    $browser->mouseover('.pm-transactions-backdrop.open [data-pm-history-detail]');
                    $browser->assertScript("getComputedStyle(document.querySelector('.pm-transactions-backdrop.open [data-pm-history-detail]')).color",'rgb(8, 116, 67)');
                    $browser->script("window.historyModalSize=document.querySelector('.pm-transactions-backdrop.open .pm-transactions-modal').getBoundingClientRect()" );
                    $browser->click('.pm-transactions-backdrop.open [data-pm-history-detail]');
                    $browser->assertScript("(()=>{const size=document.querySelector('.pm-transactions-backdrop.open .pm-transactions-modal').getBoundingClientRect();return Math.abs(size.height-window.historyModalSize.height)<1 && Math.abs(size.width-window.historyModalSize.width)<1})()",true);
                    $browser->assertVisible('.pm-transactions-backdrop.open [data-pm-detail-host] .pm-txn-det');
                    $browser->assertVisible('.pm-transactions-backdrop.open [data-pm-detail-footer]');
                    $browser->assertScript("getComputedStyle(document.querySelector('.pm-transactions-backdrop.open [data-pm-detail-host] .pm-txn-det-cell > span')).fontSize",'16px');
                    $browser->assertScript("getComputedStyle(document.querySelector('.pm-transactions-backdrop.open [data-pm-detail-host] .pm-txn-det-cell > strong')).fontSize",'16px');
                    $browser->assertSeeIn('[data-pm-payment-pane] h2', 'Payment Details');
                    $browser->assertVisible('.pm-transactions-backdrop.open [data-pm-history-footer]');
                    $browser->assertScript("document.querySelector('[data-pm-payment-pane]').getBoundingClientRect().right <= document.querySelector('.pm-transactions-backdrop.open .pm-transactions-modal').getBoundingClientRect().left",true);
                    $browser->assertScript("document.querySelector('.pm-transactions-backdrop.open [data-pm-detail-print]').href===document.querySelector('.pm-transactions-backdrop.open [data-pm-history-detail]').dataset.printUrl",true);
                    $browser->screenshot('payment-detail-modal-'.($scope??$role));
                    $browser->assertVisible('.pm-transactions-backdrop.open [data-pm-history-pane]');
                    $browser->script("document.querySelector('.pm-transactions-backdrop.open').click()");
                    $browser->assertPresent('.pm-transactions-backdrop.open [data-pm-payment-pane][hidden]');
                    $browser->assertScript("document.querySelector('.pm-transactions-backdrop.open [data-pm-history-title]').textContent.startsWith('Payment History')",true);
                    $browser->assertVisible('.pm-transactions-backdrop.open .pm-ledger-table');
                    $browser->assertScript("document.querySelectorAll('.pm-action-tooltip:popover-open').length",0);
                    $browser->screenshot('payment-history-modal-'.($scope??$role));
                    $browser->click('.pm-transactions-backdrop.open [data-pm-close-transactions-modal]');
                    $browser->click('.pm-more-filters summary');
                    $browser->assertScript("document.querySelector('.pm-more-filters').open",true);
                    $browser->script("const p=document.querySelector('.pm-more-panel');p.style.maxHeight='300px';const f=p.querySelector('.pm-filter-fields');f.scrollTop=f.scrollHeight");
                    $browser->assertScript("(()=>{const p=document.querySelector('.pm-more-panel'),f=p.querySelector('.pm-filter-fields'),a=p.querySelector('.pm-filter-apply'),r=a.getBoundingClientRect();return p.getBoundingClientRect().width<=320&&f.scrollHeight>f.clientHeight&&f.getBoundingClientRect().bottom<=r.top+1&&!document.elementsFromPoint(r.left+20,r.top+12).some(e=>f.contains(e))})()",true);
                    $browser->screenshot('payment-filter-scroll-'.($scope??$role));
                    $browser->script("document.querySelector('.pm-more-panel').style.removeProperty('max-height');document.querySelector('.pm-filter-fields').scrollTop=0");
                    $browser->click('#pmPaymentStatus');
                    $browser->assertScript("(()=>{const p=document.querySelector('.pm-more-panel').getBoundingClientRect(),m=document.querySelector('#pmPaymentStatus').parentElement.querySelector('.filter-select-menu'),r=m.getBoundingClientRect();return m.matches(':popover-open')&&Math.abs(r.width-240)<1&&(r.left>=p.right||r.right<=p.left)})()",true);
                    $browser->script("document.querySelector('#pmPaymentStatus').dispatchEvent(new KeyboardEvent('keydown',{key:'Escape',bubbles:true}))");
                    $browser->click('.pm-more-filters summary');
                    $browser->script('window.fetchCount=0');
                    $browser->select('#pmPaymentStatus','PARTIAL');
                    $browser->scrollIntoView('#pmMethod')->select('#pmMethod','cash');
                    $browser->assertScript('window.fetchCount',0);
                    $browser->select('select[name=sort]','balance_high');
                    $browser->assertScript('window.fetchCount',1);
                    $browser->assertScript("!new URL(window.lastSearchUrl).searchParams.has('payment_status')&&!new URL(window.lastSearchUrl).searchParams.has('payment_method')",true);
                    $browser->click('.pm-more-filters summary');
                    $browser->click('[data-pm-apply-filters]');
                    $browser->assertScript('window.fetchCount',2);
                    $browser->assertScript("new URL(window.lastSearchUrl).searchParams.get('payment_status')==='PARTIAL'&&new URL(window.lastSearchUrl).searchParams.get('payment_method')==='cash'",true);
                    $browser->script("document.querySelector('#pmPaymentStatus').value='';document.querySelector('#pmMethod').value='';window.fetchCount=0");
                    $browser->scrollIntoView('#pmDateRange')->select('#pmDateRange','custom')->waitUntil("document.querySelector('#pmDateModal').matches(':popover-open') && document.querySelector('#pmDateModal').getBoundingClientRect().height > 0");
                    $browser->assertScript('window.fetchCount',0);
                    $browser->assertScript("document.querySelector('#pmDateModal [aria-modal=true]')===null",true);
                    $browser->assertScript("(()=>{const a=document.querySelector('.pm-more-panel').getBoundingClientRect(),b=document.querySelector('#pmDateModal').getBoundingClientRect();return b.left>=a.right||b.right<=a.left})()",true);
                    $browser->screenshot('payment-consistency-'.($scope??$role).'-light-desktop');
                    $browser->click('[data-pm-date-cancel]');
                    $browser->waitUntil("!document.querySelector('#pmDateModal:popover-open')");
                    $browser->driver->executeCustomCommand('/session/:sessionId/goog/cdp/execute','POST',['cmd'=>'Emulation.setDeviceMetricsOverride','params'=>['width'=>390,'height'=>844,'deviceScaleFactor'=>1,'mobile'=>true]]);
                    $browser->script("document.documentElement.dataset.theme='dark';document.querySelector('.pm-more-filters').open=false");
                    $browser->scrollIntoView('.pm-toolbar-shell')->pause(250);
                    $browser->assertScript('document.documentElement.scrollWidth<=innerWidth',true);
                    $browser->assertScript("(()=>{const panel=document.querySelector('.pm-panel');const lum=color=>{const c=color.match(/[\\d.]+/g).slice(0,3).map(Number).map(v=>{v/=255;return v<=.04045?v/12.92:((v+.055)/1.055)**2.4});return c[0]*.2126+c[1]*.7152+c[2]*.0722};const bg=lum(getComputedStyle(panel).backgroundColor);return ['paid','partial','unpaid'].every(status=>{const e=document.createElement('span');e.className='pm-status is-'+status;e.textContent=status;panel.append(e);const s=getComputedStyle(e),fg=lum(s.color);const ok=(Math.max(bg,fg)+.05)/(Math.min(bg,fg)+.05)>=4.5&&s.backgroundColor==='rgba(0, 0, 0, 0)';e.remove();return ok})})()",true);
                    $browser->screenshot('payment-consistency-'.($scope??$role).'-dark-narrow');
                    $browser->driver->get('about:blank');
                });
            } finally {
                $this->browse(fn (Browser $browser) => $browser->driver->get('about:blank'));
                for ($attempt=0; $attempt<5 && is_file($path); $attempt++) { if (@unlink($path)) break; usleep(50000); }
            }
        }
    }
}

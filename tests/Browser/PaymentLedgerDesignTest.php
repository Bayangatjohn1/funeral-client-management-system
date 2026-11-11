<?php

namespace Tests\Browser;

use App\Models\{Branch,User,Client,Deceased,FuneralCase,Package,Payment};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class PaymentLedgerDesignTest extends DuskTestCase
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

    public function test_ledger_cards_render_in_both_themes_and_responsive_widths(): void
    {
        $branch = Branch::create(['branch_code'=>'PM-VISUAL','branch_name'=>'Visual branch','address'=>'Test','type'=>'main','is_active'=>true]);
        $client = Client::create(['branch_id'=>$branch->id,'full_name'=>'Client with a longer family name for layout review','relationship_to_deceased'=>'Spouse','contact_number'=>'09170000000','address'=>'Test']);
        $deceased = Deceased::create(['branch_id'=>$branch->id,'client_id'=>$client->id,'full_name'=>'Deceased with a longer family name','date_of_death'=>now()->subDay()->toDateString(),'age'=>70]);
        $package = Package::create(['name'=>'UI fixture package','coffin_type'=>'Standard','price'=>10000,'is_active'=>true]);
        $case = FuneralCase::create(['branch_id'=>$branch->id,'client_id'=>$client->id,'deceased_id'=>$deceased->id,'package_id'=>$package->id,'case_number'=>FuneralCase::nextCaseNumber($branch->id),'case_code'=>'UI-CASE-001','service_type'=>'Burial','service_requested_at'=>now()->toDateString(),'wake_location'=>'Test Wake','funeral_service_at'=>now()->addDay()->toDateString(),'service_package'=>$package->name,'coffin_type'=>'Standard','total_amount'=>10000,'subtotal_amount'=>10000,'total_paid'=>0,'balance_amount'=>10000,'payment_status'=>'UNPAID','case_status'=>'ACTIVE','entry_source'=>'MAIN','verification_status'=>'VERIFIED']);
        Payment::create(['funeral_case_id'=>$case->id,'branch_id'=>$branch->id,'amount'=>2500,'proof_path'=>'proofs/layout-test.png','proof_mime'=>'image/png','reference_number'=>'PM-SEARCH-REF','payment_method'=>'cash','paid_at'=>now(),'paid_date'=>now()->toDateString(),'payment_status_after_payment'=>'PARTIAL','balance_after_payment'=>7500]);
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
                    foreach (['light','dark'] as $theme) {
                        $browser->driver->executeCustomCommand('/session/:sessionId/goog/cdp/execute','POST',['cmd'=>'Emulation.setDeviceMetricsOverride','params'=>['width'=>1440,'height'=>1000,'deviceScaleFactor'=>1,'mobile'=>false]]);
                        $browser->script("document.documentElement.dataset.theme='$theme';document.querySelector('.pm-transactions-backdrop').classList.add('open')");
                        $browser->assertScript("document.querySelectorAll('.pm-ledger-card').length>0",true);
                        $browser->assertScript("document.querySelector('.pm-transactions-modal .pm-drawer-subtitle')===null",true);
                        $browser->assertScript("getComputedStyle(document.querySelector('.pm-ledger-context dd')).fontSize",'14px');
                        $browser->assertScript("getComputedStyle(document.querySelector('.pm-ledger-table td')).fontSize",'14px');
                        $browser->assertScript("document.documentElement.scrollWidth<=innerWidth",true);

                        $browser->assertScript("getComputedStyle(document.querySelector('.pm-ledger-summary')).gridTemplateColumns.split(' ').length",3);
                        $browser->assertScript("getComputedStyle(document.querySelector('.pm-ledger-card strong')).fontSize",'20px');
                        $browser->assertScript("getComputedStyle(document.querySelector('.pm-transactions-modal .pm-modal-body')).display",'block');
                        $browser->assertScript("(()=>{const e=document.querySelector('.pm-ledger-print');return e.getBoundingClientRect().width>=document.querySelector('[data-pm-history-footer]').getBoundingClientRect().width-2})()",true);
                        $browser->script("document.querySelector('.pm-transactions-backdrop.open [data-pm-history-detail]').click()");
                        $browser->assertScript("(()=>{const row=document.querySelector('[data-pm-detail-host] .pm-reference-value');const value=row?.querySelector('strong'), proof=row?.querySelector('a');return !!proof && value.textContent.trim()==='PM-SEARCH-REF' && Math.abs(value.getBoundingClientRect().top-proof.getBoundingClientRect().top)<8 && proof.getBoundingClientRect().left>value.getBoundingClientRect().left})()",true);
                        $browser->assertScript("getComputedStyle(document.querySelector('[data-pm-detail-host] .pm-detail-group-title')).backgroundColor!=='rgba(0, 0, 0, 0)'",true);
                        $browser->script("document.querySelector('.pm-transactions-backdrop.open [data-pm-history-back]').click()");
                        $browser->screenshot('ledger-'.$role.'-'.($scope??'staff').'-'.$theme);
                        $browser->driver->executeCustomCommand('/session/:sessionId/goog/cdp/execute','POST',['cmd'=>'Emulation.setDeviceMetricsOverride','params'=>['width'=>585,'height'=>937,'deviceScaleFactor'=>1,'mobile'=>false]]);
                        $browser->assertScript(<<<'JS'
                            [...document.querySelectorAll('.pm-ledger-card')].every(card => {
                                const amount = card.querySelector('strong').getBoundingClientRect();
                                const label = card.querySelector('span');
                                const text = document.createRange();
                                text.selectNodeContents(label);
                                return label.getBoundingClientRect().top >= amount.bottom
                                    && text.getClientRects().length === 1
                                    && text.getBoundingClientRect().right <= card.getBoundingClientRect().right;
                            })
                            JS, true);
                        $browser->assertScript(<<<'JS'
                            (() => {
                                const background = selector => getComputedStyle(document.querySelector(selector)).backgroundColor;
                                const table = document.querySelector('.pm-ledger-scroll');
                                return background('.pm-ledger-context') === background('.pm-ledger-table th')
                                    && background('.pm-ledger-table th') !== background('.pm-ledger-table')
                                    && table.scrollWidth <= table.clientWidth;
                            })()
                            JS, true);
                        $browser->driver->executeCustomCommand('/session/:sessionId/goog/cdp/execute','POST',['cmd'=>'Emulation.setDeviceMetricsOverride','params'=>['width'=>390,'height'=>844,'deviceScaleFactor'=>1,'mobile'=>true]]);
                        $browser->assertScript("getComputedStyle(document.querySelector('.pm-ledger-summary')).gridTemplateColumns.split(' ').length",1);
                        $browser->assertScript("getComputedStyle(document.querySelector('.pm-transactions-modal .pm-modal-body')).display",'block');
                        $browser->assertScript("(()=>{const e=document.querySelector('.pm-ledger-print');return e.getBoundingClientRect().width>=document.querySelector('[data-pm-history-footer]').getBoundingClientRect().width-2})()",true);
                        $browser->screenshot('ledger-'.$role.'-'.($scope??'staff').'-'.$theme.'-mobile');
                    }
                    $browser->driver->get('about:blank');
                });
            } finally { @unlink($path); }
        }
    }
}

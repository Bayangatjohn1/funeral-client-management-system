<?php

namespace Tests\Browser;

use App\Models\{Branch,User,Client,Deceased,Package,FuneralCase};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class CaseSearchWidthTest extends DuskTestCase
{
    use RefreshDatabase;

    public function createApplication()
    {
        $app=parent::createApplication();
        if (($_ENV['DB_CONNECTION']??null)!=='sqlite' || ($_ENV['DB_DATABASE']??null)!==':memory:') throw new \RuntimeException('UI fixtures require in-memory SQLite.');
        $app['config']->set('database.default','sqlite');
        $app['config']->set('database.connections.sqlite.database',':memory:');
        return $app;
    }

    public function test_case_search_shortens_without_moving_controls_across_roles_and_themes(): void
    {
        $branch=Branch::create(['branch_code'=>'CASE-WIDTH','branch_name'=>'Width fixture','address'=>'Test','type'=>'main','is_active'=>true]);
        $client=Client::create(['branch_id'=>$branch->id,'full_name'=>'Antonnette Gubat','relationship_to_deceased'=>'Spouse','contact_number'=>'09170000000','address'=>'Test']);
        $deceased=Deceased::create(['branch_id'=>$branch->id,'client_id'=>$client->id,'full_name'=>'May Gubat','date_of_death'=>now()->subDay()->toDateString(),'age'=>70]);
        $package=Package::create(['name'=>'Search fixture','coffin_type'=>'Standard','price'=>10000,'is_active'=>true]);
        FuneralCase::create(['branch_id'=>$branch->id,'client_id'=>$client->id,'deceased_id'=>$deceased->id,'package_id'=>$package->id,'case_number'=>FuneralCase::nextCaseNumber($branch->id),'case_code'=>'FCSEARCH-001','service_type'=>'Burial','service_requested_at'=>now()->toDateString(),'wake_location'=>'Test Wake','funeral_service_at'=>now()->addDay()->toDateString(),'service_package'=>$package->name,'coffin_type'=>'Standard','total_amount'=>10000,'subtotal_amount'=>10000,'total_paid'=>0,'balance_amount'=>10000,'payment_status'=>'UNPAID','case_status'=>'ACTIVE','entry_source'=>'MAIN','verification_status'=>'VERIFIED']);
        $manifest=json_decode(file_get_contents(public_path('build/manifest.json')),true);
        $css=file_get_contents(public_path('build/'.$manifest['resources/css/app.css']['file']));
        foreach($manifest['resources/js/app.js']['css'] as $file) $css.=file_get_contents(public_path('build/'.$file));
        $css=str_replace('url(./','url(file:///'.str_replace('\\','/',public_path('build/assets')).'/',$css);
        $bundle=file_get_contents(public_path('build/'.$manifest['resources/js/app.js']['file']));
        foreach ([['staff',null,'funeral-cases.index'],['admin','branch','admin.cases.index'],['admin','system','admin.cases.index'],['owner',null,'owner.history']] as [$role,$scope,$route]) {
            $actor=User::factory()->create(['role'=>$role,'admin_scope'=>$scope,'branch_id'=>$branch->id,'is_active'=>true]);
            if($actor->requiresMfa()) {
                $actor->two_factor_secret=(new \PragmaRX\Google2FA\Google2FA)->generateSecretKey();
                $actor->two_factor_confirmed_at=now(); $actor->save();
            }
            $this->withSession(['idle_last_activity'=>now()->timestamp,'idle_session_key'=>'case-width-fixture','account_security'=>\App\Support\AccountSecurity::fingerprint($actor),'mfa_user_id'=>$actor->requiresMfa()?$actor->id:null]);
            $html=$this->followingRedirects()->actingAs($actor)->get(route($route))->assertOk()->assertSee('data-case-search-input',false)->getContent();
            $searchResponse=json_encode($html,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);
            $html=preg_replace('~<link\b[^>]*href="[^"]*/build/[^"]*"[^>]*>~i','',$html);
            $html=preg_replace('~<script\b[^>]*src="[^"]*/build/[^"]*"[^>]*>\s*</script>~i','',$html);
            $html=str_replace(rtrim(config('app.url'),'/').'/vendor/','file:///'.str_replace('\\','/',public_path('vendor')).'/',$html);
            $html=str_replace('</head>','<style>'.$css.'</style><script>window.caseSearchResponse='. $searchResponse .';window.fetch=async()=>{throw new Error("Isolated fixture")}</script><script type="module">'.str_ireplace('</script','<\/script',$bundle).'</script></head>',$html);
            $path=tempnam(storage_path('framework'),'case-width-'); rename($path,$path.'.html'); $path.='.html'; file_put_contents($path,$html);
            try {
                $this->browse(function(Browser $browser)use($path,$scope,$role){
                    $browser->driver->get('file:///'.str_replace('\\','/',$path));
                    $browser->waitUntil("document.querySelector('[data-case-search-input]').dataset.sharedSearchReady==='1'");
                    foreach(['light','dark'] as $theme) {
                        $browser->script('document.documentElement.dataset.theme='.json_encode($theme));
                        foreach([1440,1920,1100,390] as $width) {
                            $browser->driver->executeCustomCommand('/session/:sessionId/goog/cdp/execute','POST',['cmd'=>'Emulation.setDeviceMetricsOverride','params'=>['width'=>$width,'height'=>1000,'deviceScaleFactor'=>1,'mobile'=>$width===390]]);
                            if($width>1200) {
                                $browser->assertScript("(()=>{const s=document.querySelector('.case-compact-search-field'),c=document.querySelector('.case-compact-search-control'),nodes=[...document.querySelectorAll('.case-compact-more,.case-compact-sort-filter,.case-rows-per-page')],x=nodes.map(e=>e.getBoundingClientRect().left),saved=s.getAttribute('style'),r=c.getBoundingClientRect(),gap=x[0]-r.right;s.style.setProperty('width','100%','important');s.style.setProperty('max-width','none','important');s.style.setProperty('flex','1 1 100%','important');const stable=nodes.every((e,i)=>Math.abs(e.getBoundingClientRect().left-x[i])<1);if(saved===null)s.removeAttribute('style');else s.setAttribute('style',saved);return stable&&r.width<=560&&gap>=36})()",true);
                            } else {
                                $browser->assertScript("(()=>{const r=document.querySelector('.case-compact-search-control').getBoundingClientRect();return r.left>=0&&r.right<=innerWidth&&r.width>200})()",true);
                            }
                            if($theme==='light'&&$width===1440) {
                                $browser->script("window.history.pushState=(state,title,url)=>window.searchHistoryUrl=url;window.fetch=async(url)=>{window.lastCaseSearchUrl=url;throw new Error('Isolated fixture')};const e=document.querySelector('[data-case-search-input]');e.value='Anto';e.dispatchEvent(new Event('input',{bubbles:true}))");
                                $browser->assertScript("document.querySelector('[data-case-match-value]').dataset.caseMatchValue",'FCSEARCH-001');
                                $browser->assertScript("getComputedStyle(document.querySelector('[data-case-search-input]')).fontWeight",'400');
                                $browser->assertScript("getComputedStyle(document.querySelector('[data-case-search-input]')).fontSize",'14px');
                                $browser->assertScript("getComputedStyle(document.querySelector('.case-search-match mark')).textDecorationLine.includes('underline')",true);
                                $browser->assertScript("!document.querySelector('.records-page,.master-records-page').classList.contains('is-updating')",true);
                                $browser->screenshot('case-search-matches-'.($scope??$role));
                                $browser->click('[data-case-match-value]');
                                $browser->assertScript("new URL(window.lastCaseSearchUrl).searchParams.get('q')",'FCSEARCH-001');
                                $browser->click('[data-case-search-clear]');
                                $browser->assertScript("new URL(window.lastCaseSearchUrl).searchParams.has('q')",false);
                                $browser->script("const f=document.querySelector('[data-case-filter]'),e=f.querySelector('[data-case-search-input]');f.querySelector('[name=payment_status]').value='PARTIAL';e.value='May';e.dispatchEvent(new Event('input',{bubbles:true}))");
                                $browser->click('[data-case-search-submit]');
                                $browser->assertScript("new URL(window.lastCaseSearchUrl).searchParams.get('q')==='May'&&!new URL(window.lastCaseSearchUrl).searchParams.has('payment_status')",true);
                                $browser->script("window.fetch=async(url)=>{window.suggestionUrl=url;return {ok:true,text:async()=>'<form data-case-filter></form><table><tbody><tr data-record-search-case=\"REMOTE-002\" data-record-search-client=\"Remote Client\" data-record-search-deceased=\"Remote Deceased\"></tr></tbody></table>'}};const e=document.querySelector('[data-case-search-input]');e.value='Remote';e.dispatchEvent(new Event('input',{bubbles:true}))");
                                $browser->waitUntil("document.querySelector('[data-case-match-value]')?.dataset.caseMatchValue==='REMOTE-002'");
                                $browser->assertScript("new URL(window.suggestionUrl).searchParams.get('q')",'Remote');
                                $browser->script("document.querySelector('[data-case-search-input]').dispatchEvent(new KeyboardEvent('keydown',{key:'Escape',bubbles:true}));window.fetch=async(url)=>{window.lastCaseSearchUrl=url;const d=new DOMParser().parseFromString(window.caseSearchResponse,'text/html');d.querySelector('[data-case-filter] [name=q]').setAttribute('value',new URL(url).searchParams.get('q')||'');return {ok:true,text:async()=>d.documentElement.outerHTML}};const e=document.querySelector('[data-case-search-input]');e.value='Anto';e.dispatchEvent(new Event('input',{bubbles:true}))");
                                $browser->click('[data-case-search-submit]');
                                $browser->waitUntil("document.querySelector('[data-case-filter]').dataset.caseFilterReady==='1'&&document.querySelector('[data-case-search-input]').value==='Anto'");
                                $browser->script("window.fetch=async(url)=>{window.lastCaseSearchUrl=url;throw new Error('Isolated fixture')};const e=document.querySelector('[data-case-search-input]');e.value='May';e.dispatchEvent(new KeyboardEvent('keydown',{key:'Enter',bubbles:true}))");
                                $browser->assertScript("new URL(window.lastCaseSearchUrl).searchParams.get('q')",'May');
                                $browser->click('[data-case-search-clear]');
                            }
                            if($width===1440) $browser->screenshot('case-search-width-'.($scope??$role).'-'.$theme);
                        }
                    }
                    $browser->driver->get('about:blank');
                });
            } finally {
                $this->browse(fn(Browser $browser)=>$browser->driver->get('about:blank'));
                for($attempt=0;$attempt<5&&is_file($path);$attempt++){if(@unlink($path))break;usleep(50000);}
            }
        }
    }
}

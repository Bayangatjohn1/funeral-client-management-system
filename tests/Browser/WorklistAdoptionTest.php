<?php
namespace Tests\Browser;

use App\Models\{Branch,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class WorklistAdoptionTest extends DuskTestCase
{
    use RefreshDatabase;
    public function createApplication(){
        $app=parent::createApplication();
        if(($_ENV['DB_CONNECTION']??null)!=='sqlite'||($_ENV['DB_DATABASE']??null)!==':memory:')throw new \RuntimeException('Isolated UI database required');
        $app['config']->set('database.default','sqlite');$app['config']->set('database.connections.sqlite.database',':memory:');return $app;
    }
    private function fixture(string $html,string $mock=''):string{
        $manifest=json_decode(file_get_contents(public_path('build/manifest.json')),true);
        $css=file_get_contents(public_path('build/'.$manifest['resources/css/app.css']['file']));
        foreach($manifest['resources/js/app.js']['css']as$file)$css.=file_get_contents(public_path('build/'.$file));
        $css=str_replace('url(./','url(file:///'.str_replace('\\','/',public_path('build/assets')).'/',$css);
        $bundle=file_get_contents(public_path('build/'.$manifest['resources/js/app.js']['file']));
        $html=preg_replace('~<link\b[^>]*href="[^"]*/build/[^"]*"[^>]*>~i','',$html);
        $html=preg_replace('~<script\b[^>]*src="[^"]*/build/[^"]*"[^>]*>\s*</script>~i','',$html);
        $html=str_replace(rtrim(config('app.url'),'/').'/vendor/','file:///'.str_replace('\\','/',public_path('vendor')).'/',$html);
        $mock='window.history.pushState=window.history.replaceState=(_s,_t,url)=>window.committedUrl=String(url);window.fetch=async()=>{throw Error("Isolated unrelated request")};'.$mock;
        $html=str_replace('</head>','<style>'.$css.'</style><script>'.$mock.'</script><script type="module">'.str_ireplace('</script','<\/script',$bundle).'</script></head>',$html);
        $path=tempnam(storage_path('framework'),'worklist');rename($path,$path.'.html');$path.='.html';file_put_contents($path,$html);return $path;
    }
    private function actor(Branch $branch,string $scope,string $role='admin'):User{
        $actor=User::factory()->create(['role'=>$role,'admin_scope'=>$role==='admin'?$scope:null,'branch_id'=>$branch->id,'is_active'=>true]);
        if($actor->requiresMfa()){$actor->two_factor_secret=(new \PragmaRX\Google2FA\Google2FA)->generateSecretKey();$actor->two_factor_confirmed_at=now();$actor->save();}
        $this->withSession(['idle_last_activity'=>now()->timestamp,'idle_session_key'=>'worklist','account_security'=>\App\Support\AccountSecurity::fingerprint($actor),'mfa_user_id'=>$actor->requiresMfa()?$actor->id:null]);return$actor;
    }
    private function clean(string $path):void{
        $this->browse(fn(Browser$b)=>$b->driver->get('about:blank'));
        for($n=0;$n<8&&is_file($path);$n++){if(@unlink($path))break;usleep(50000);}
    }
    public function test_directories_and_audit_controls_results_loading_and_detached_footer():void{
        $branch=Branch::create(['branch_code'=>'BR-WORK','branch_name'=>'Needle Branch','address'=>'Needle Street','is_active'=>true]);
        User::factory()->create(['name'=>'Needle Staff','email'=>'needle@example.com','role'=>'staff','branch_id'=>$branch->id,'is_active'=>true]);
        foreach([['system','branches'],['system','users'],['branch','users'],['system','audit'],['branch','audit']]as[$scope,$kind]){
            $actor=$this->actor($branch,$scope);$route=$kind==='audit'?'admin.audit-logs.index':"admin.$kind.index";
            $full=$this->actingAs($actor)->get(route($route))->assertOk()->getContent();
            $filtered=$this->actingAs($actor)->get(route($route,$kind==='audit'?['action_type'=>'create']:['status'=>'inactive']))->assertOk()->getContent();
            $mock='window.docs='.json_encode(['full'=>$full,'filtered'=>$filtered],JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT).';window.requests=[];window.fetch=async(url,options={})=>{if(!new URL(url).pathname.endsWith('.json_encode($kind==='audit'?'/audit-logs':'/'.$kind).'))throw Error("Unrelated request");requests.push(String(url));const html=docs[(new URL(url).searchParams.has("status")||new URL(url).searchParams.has("action_type"))?"filtered":"full"];if(window.deferResults)return new Promise(resolve=>window.finish=()=>resolve({ok:true,text:async()=>html}));return{ok:true,text:async()=>html}};document.addEventListener("submit",event=>{const form=event.target;if(!form.matches("[data-worklist-form]"))return;event.preventDefault();const url=new URL(form.action);url.search=new URLSearchParams(new FormData(form));window.loadRecordResults(form.closest("[data-record-results-page]"),url.href)},true);';
            $path=$this->fixture($full,$mock);
            try{$this->browse(function(Browser$b)use($path,$scope,$kind){
                $b->driver->get('file:///'.str_replace('\\','/',$path));$b->waitUntil('window.loadRecordResults!==undefined&&window.PanelSkeleton!==undefined');
                $b->script('window.originalForm=document.querySelector("[data-worklist-form]");window.originalFooter=document.querySelector("[data-record-results=footer]");window.originalMain=document.querySelector("main");');
                $b->assertScript('getComputedStyle(document.querySelector(".table-system-table th")).backgroundColor','rgba(0, 0, 0, 0)');
                $header=$b->script('return [...document.querySelectorAll(".table-system-table thead,.table-system-table thead tr")].map(e=>[e.tagName,getComputedStyle(e).backgroundColor])')[0];
                foreach($header as[$tag,$color])$this->assertSame('rgba(0, 0, 0, 0)',$color,"$scope $kind $tag");
                foreach(['light','dark']as$theme)foreach([1440,1100,390]as$width){
                    $b->driver->executeCustomCommand('/session/:sessionId/goog/cdp/execute','POST',['cmd'=>'Emulation.setDeviceMetricsOverride','params'=>['width'=>$width,'height'=>1000,'deviceScaleFactor'=>1,'mobile'=>$width<500]]);
                    $b->script('document.documentElement.dataset.theme='.json_encode($theme));
                    $b->assertScript('getComputedStyle(document.querySelector("[data-worklist-filter]>summary")).height','44px');
                    $b->assertScript('document.querySelectorAll("[data-record-results=footer]").length',1);
                    $b->assertScript('!originalFooter.closest(".table-system-wrap,[data-record-results=cards],[data-record-results=table],[data-record-results=list]")',true);
                    if($kind==='audit'){
                        $b->assertScript('originalFooter.getBoundingClientRect().height>=64',true);
                        $b->assertScript('originalFooter.getBoundingClientRect().top>=Math.max(...[...document.querySelectorAll("[data-record-results]:not([data-record-results=footer])")].filter(e=>e.getClientRects().length).map(e=>e.getBoundingClientRect().bottom))-1',true);
                    }else{
                        $footerHidden=$b->script('return originalFooter.classList.contains("is-pagination-hidden")&&originalFooter.getClientRects().length===0')[0];
                        $this->assertTrue($footerHidden,"$scope $kind short directory footer must stay hidden");
                    }
                    $b->assertScript('document.documentElement.scrollWidth<=innerWidth+1',true);
                    if($kind!=='audit'){
                        $colors=$b->script('return [getComputedStyle(document.querySelector("[data-worklist-filter]>summary")).backgroundColor,getComputedStyle(document.querySelector(".worklist-sort-control")).backgroundColor]')[0];
                        $this->assertSame($colors[0],$colors[1],"$scope $kind $theme $width");
                        if($width===1440){$b->assertScript('Math.round(document.querySelector("[data-worklist-filter]").getBoundingClientRect().width)',220);$b->assertScript('Math.round(document.querySelector(".worklist-sort-control").getBoundingClientRect().width)',220);}
                        $b->assertScript('getComputedStyle(document.querySelector(".worklist-search-control")).borderRadius','14px');
                        $b->assertScript('getComputedStyle(document.querySelector(".worklist-search-control")).backgroundColor',$theme==='dark'?'rgb(24, 45, 34)':'rgb(234, 242, 237)');
                    }
                }
                $b->driver->executeCustomCommand('/session/:sessionId/goog/cdp/execute','POST',['cmd'=>'Emulation.setDeviceMetricsOverride','params'=>['width'=>1440,'height'=>1000,'deviceScaleFactor'=>1,'mobile'=>false]]);
                if($kind!=='audit'){
                    $b->script('window.viewToggleTop=document.querySelector(".directory-view-toggle").getBoundingClientRect().top');
                    $b->script('const input=document.querySelector("[data-worklist-search]");input.value="Needle";input.dispatchEvent(new Event("input",{bubbles:true}))');
                    $b->waitUntil('document.querySelector(".worklist-match mark")!==null');
                    $b->assertScript('document.querySelectorAll(".panel-skeleton").length',0);
                    $b->script('document.querySelector("[data-worklist-search]").dispatchEvent(new KeyboardEvent("keydown",{key:"Escape",bubbles:true}));document.querySelector("[data-worklist-search]").value=""');
                }
                $b->script('document.querySelector("[data-worklist-filter]").open=true;window.countBefore=window.requests.length;const select=document.querySelector("[data-worklist-pending]");select.value=select.name==="status"?"inactive":select.options[1]?.value||"";select.dispatchEvent(new Event("change",{bubbles:true}))');
                $b->assertScript('requests.length===countBefore',true);
                if($kind==='audit'){
                    $b->script('const select=document.querySelector("[data-worklist-date-preset]");select.value="CUSTOM";select.dispatchEvent(new Event("change",{bubbles:true}))');
                    $b->waitUntil('document.querySelector("[data-worklist-date-panel]").matches(":popover-open")');
                    $b->assertScript('!document.querySelector("[data-worklist-date-panel]").matches(":modal")',true);
                    $b->script('document.querySelector("[data-date-from]").value="2026-01-01";document.querySelector("[data-date-to]").value="2026-01-31";document.querySelector("[data-date-use]").click()');
                    $b->assertScript('document.querySelector("[name=date_from]").value','2026-01-01');
                    $b->assertScript('requests.length===countBefore',true);
                }
                $b->script('window.deferResults=true;originalForm.requestSubmit()');
                $b->waitUntil('document.querySelector(".panel-skeleton")!==null');
                $b->assertScript('!originalForm.inert&&!originalFooter.inert&&!originalFooter.querySelector(".panel-skeleton")',true);
                $b->script('window.finish()');$b->waitUntil('!document.querySelector(".panel-skeleton")');
                $b->assertScript('originalForm===document.querySelector("[data-worklist-form]")&&originalMain===document.querySelector("main")&&originalFooter===document.querySelector("[data-record-results=footer]")',true);
                $b->assertScript('document.querySelector("[data-worklist-filter]").open',false);
                if($kind!=='audit'){
                    $b->assertScript('Math.abs(document.querySelector(".directory-view-toggle").getBoundingClientRect().top-window.viewToggleTop)<1',true);
                    $b->assertScript('document.querySelector(".worklist-sort-label").textContent.startsWith("Sort: ")',true);
                    $b->assertScript('document.querySelector(".worklist-chip")!==null',true);
                    $b->assertScript('document.querySelector(".worklist-chip").classList.contains("catalog-filter-chip")',true);
                    $b->assertScript('document.querySelector(".worklist-chip").dataset.tone','caution');
                    $b->assertScript('getComputedStyle(document.querySelector(".worklist-chip"),"::before").content','none');
                    $b->assertScript('document.querySelector(".worklist-chip-remove").getAttribute("aria-label").startsWith("Remove ")',true);
                    $b->assertScript('getComputedStyle(document.querySelector(".worklist-clear")).fontWeight','400');
                    $b->assertScript('getComputedStyle(document.querySelector(".worklist-clear")).borderColor','rgba(0, 0, 0, 0)');
                    $b->assertScript('getComputedStyle(document.querySelector(".worklist-clear")).backgroundColor','rgba(0, 0, 0, 0)');
                    $b->mouseover('.worklist-clear');
                    $b->assertScript('getComputedStyle(document.querySelector(".worklist-clear")).borderColor!=="rgba(0, 0, 0, 0)"',true);
                    $b->assertScript('getComputedStyle(document.querySelector(".worklist-clear")).backgroundColor','rgba(0, 0, 0, 0)');
                }
                $b->screenshot('worklist-'.$scope.'-'.$kind);
            });}finally{$this->clean($path);}
        }
    }
    public function test_cleared_directory_scope_is_not_restored_by_later_apply(): void {
        $branch=Branch::create(['branch_code'=>'BR-CLEAR','branch_name'=>'Clear Scope Fixture','is_active'=>true]);
        $actor=$this->actor($branch,'system');
        $route=route('admin.users.index');
        $full=$this->actingAs($actor)->get($route)->assertOk()->getContent();
        $scoped=$this->actingAs($actor)->get($route.'?assignment=unassigned&admin_scope=branch')->assertOk()->getContent();
        $mock='window.clearResponse='.json_encode($full,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT).';window.fetch=async()=>({ok:true,text:async()=>window.clearResponse});';
        $path=$this->fixture($scoped,$mock);
        try {$this->browse(function(Browser $browser)use($path,$route){
            $browser->driver->get('file:///'.str_replace('\\','/',$path));
            $browser->waitUntil('window.loadRecordResults!==undefined');
            $browser->assertScript('document.querySelector("[data-worklist-form] [name=assignment]").value','unassigned');
            $browser->script('window.loadRecordResults(document.querySelector("[data-record-results-page]"),'.json_encode($route).')');
            $browser->waitUntil('document.querySelector("[data-worklist-form] [name=assignment]")===null');
            $browser->assertScript('document.querySelector("[data-worklist-form] [name=admin_scope]")===null',true);
            $browser->assertScript('new URLSearchParams(new FormData(document.querySelector("[data-worklist-form]"))).has("assignment")',false);
            $browser->assertScript('new URLSearchParams(new FormData(document.querySelector("[data-worklist-form]"))).has("admin_scope")',false);
        });}finally{$this->clean($path);}
    }
    public function test_service_reference_search_actions_and_case_tabs(): void {
        $branch=Branch::create(['branch_code'=>'BR-STYLE','branch_name'=>'Style Fixture','is_active'=>true]);
        $reference=[];
        foreach ([['system','admin','admin.service-management.index','service'],['system','admin','admin.branches.index','branches'],['system','admin','admin.users.index','users'],['branch','admin','admin.users.index','users'],['system','admin','admin.payments.index','payments'],['','staff','payments.history','payments'],['','staff','funeral-cases.index','cases'],['system','admin','admin.cases.index','cases'],['branch','admin','admin.cases.index','cases'],['','owner','owner.history','cases']] as [$scope,$role,$route,$kind]) {
            $actor=$this->actor($branch,$scope,$role);
            $html=$this->actingAs($actor)->get(route($route))->assertOk()->getContent();
            $path=$this->fixture($html);
            try {$this->browse(function(Browser $b)use($path,$scope,$role,$kind,&$reference){
                $b->driver->get('file:///'.str_replace('\\','/',$path));
                $b->waitUntil('window.PanelSkeleton!==undefined');
                foreach(['light','dark'] as $theme) foreach([1440,390] as $width) {
                    $b->driver->executeCustomCommand('/session/:sessionId/goog/cdp/execute','POST',['cmd'=>'Emulation.setDeviceMetricsOverride','params'=>['width'=>$width,'height'=>1000,'deviceScaleFactor'=>1,'mobile'=>$width<500]]);
                    $b->script('document.documentElement.dataset.theme='.json_encode($theme).';localStorage.setItem("theme",'.json_encode($theme).');window.paint=e=>{const s=getComputedStyle(e);return [s.backgroundColor,s.color,s.borderRadius,s.fontSize,s.fontWeight]};');
                    $b->pause(100);
                    $key=$theme.'-'.$width;
                    $selector=$kind==='service'?'.service-search-submit':($kind==='payments'?'.pm-search-button':($kind==='cases'?'.case-compact-search-button':'.worklist-search-submit'));
                    if($selector) {
                        $b->mouseover('h1')->pause(250);
                        $paint=$b->script('return paint(document.querySelector('.json_encode($selector).'))')[0];
                        if($kind==='service')$reference[$key]['search']=$paint;
                        else $this->assertSame($reference[$key]['search'],$paint,"$role $scope $kind search $key");
                        $b->mouseover($selector);
                        $paint=$b->script('return paint(document.querySelector('.json_encode($selector).'))')[0];
                        if($kind==='service')$reference[$key]['search-hover']=$paint;
                        else $this->assertSame($reference[$key]['search-hover'],$paint,"$kind search hover $key");
                        $b->mouseover('h1')->pause(250);
                        if($b->script('return !!document.querySelector(".reference-page-action")')[0]) {
                            $this->assertSame('rgb(169, 201, 181)',$b->script('return getComputedStyle(document.querySelector(".reference-page-action")).backgroundColor')[0],"$kind action $key");
                            $b->mouseover('.reference-page-action');
                            $this->assertSame('rgb(146, 184, 159)',$b->script('return getComputedStyle(document.querySelector(".reference-page-action")).backgroundColor')[0],"$kind action hover $key");
                        }
                    }
                    if(in_array($kind,['service','cases'])) {
                        $tabs=$kind==='service'?'.service-hub-tab':'.case-records-tabs .table-quick-tab';
                        $b->mouseover('h1')->pause(250);
                        $paint=$b->script('return [...document.querySelectorAll('.json_encode($tabs).')].slice(0,2).map(e=>[...paint(e),getComputedStyle(e).gap,getComputedStyle(e).padding,getComputedStyle(e.querySelector("i")).fontSize,getComputedStyle(e.querySelector("i")).color])')[0];
                        if($kind==='service')$reference[$key]['tabs']=$paint;
                        else $this->assertSame($reference[$key]['tabs'],$paint,"$role $scope tabs $key reference ".json_encode($reference[$key]));
                        $b->assertScript('[...document.querySelectorAll('.json_encode($tabs).')].every(e=>e.scrollWidth<=e.clientWidth+1)',true);
                        $b->mouseover($tabs.':nth-child(2)')->pause(250);
                        $hover=$b->script('return paint(document.querySelectorAll('.json_encode($tabs).')[1])')[0];
                        if($kind==='service')$reference[$key]['tab-hover']=$hover;
                        else $this->assertSame($reference[$key]['tab-hover'],$hover,"$role tabs hover $key");
                    }
                    $b->screenshot('reference-'.$role.'-'.$scope.'-'.$kind.'-'.$key);
                }
            });}finally{$this->clean($path);}
        }
    }
    public function test_view_rows_click_once_and_tooltips_only_follow_the_eye(): void {
        $rows='';
        foreach(['records-page','master-records-page','directory-page','service-management-hub','payment-monitoring-page'] as $index=>$page) {
            $rows.='<section class="'.$page.'"><table><tbody><tr id="view-row-'.$index.'"><td class="row-label">Record '.$index.'</td><td><button type="button" class="primary-view-action record-action-control" data-action-tooltip="View record '.$index.'" aria-label="View record '.$index.'"><i class="bi bi-eye"></i></button><button class="other-action" type="button">Other action</button></td></tr></tbody></table></section>';
        }
        $html='<!doctype html><html data-theme="light"><head></head><body class="panel-shell-body"><main class="page-content"><h1>View rows</h1>'.$rows.'</main><script>window.views=Array(5).fill(0);document.querySelectorAll(".primary-view-action").forEach((eye,index)=>eye.addEventListener("click",()=>views[index]++));</script></body></html>';
        $path=$this->fixture($html);
        try {$this->browse(function(Browser $b)use($path){
            $b->driver->get('file:///'.str_replace('\\','/',$path));
            $b->waitUntil('document.querySelectorAll("[data-clickable-row]").length===5');
            foreach([['light',1440],['dark',390]] as [$theme,$width]){
                $b->driver->executeCustomCommand('/session/:sessionId/goog/cdp/execute','POST',['cmd'=>'Emulation.setDeviceMetricsOverride','params'=>['width'=>$width,'height'=>1000,'deviceScaleFactor'=>1,'mobile'=>false]]);
                $b->script('document.documentElement.dataset.theme='.json_encode($theme).';views.fill(0);');
                foreach(range(0,4) as $index){
                    $row='#view-row-'.$index;
                    $b->mouseover($row.' .row-label');
                    $b->assertScript('document.getElementById("shared-record-action-tooltip").hidden',true);
                    $this->assertSame($theme==='dark'?'rgb(92, 224, 160)':'rgb(27, 67, 50)',$b->script('return getComputedStyle(document.querySelector('.json_encode($row.' .primary-view-action').')).color')[0]);
                    $b->click($row.' .row-label');
                    $b->assertScript('views['.$index.']',1);
                    $b->mouseover($row.' .primary-view-action');
                    $b->assertScript('document.getElementById("shared-record-action-tooltip").hidden',false);
                    $b->assertScript('document.getElementById("shared-record-action-tooltip").textContent','View record '.$index);
                    $b->click($row.' .primary-view-action');
                    $b->assertScript('views['.$index.']',2);
                    $b->click($row.' .other-action');
                    $b->assertScript('views['.$index.']',2);
                    $b->mouseover('h1');
                    $b->assertScript('document.getElementById("shared-record-action-tooltip").hidden',true);
                    $b->script('document.querySelector('.json_encode($row).').focus();document.activeElement.dispatchEvent(new KeyboardEvent("keydown",{key:"Enter",bubbles:true,cancelable:true}));');
                    $b->assertScript('views['.$index.']',3);
                    $b->assertScript('document.getElementById("shared-record-action-tooltip").hidden',true);
                }
            }
            $b->script('const old=document.querySelector("#view-row-0");const row=old.cloneNode(true);row.removeAttribute("data-clickable-row");row.id="fresh-row";row.querySelector(".primary-view-action").addEventListener("click",()=>window.freshViews=(window.freshViews||0)+1);old.replaceWith(row);document.dispatchEvent(new CustomEvent("panel-ui:reset"));');
            $b->waitUntil('document.querySelector("#fresh-row").hasAttribute("data-clickable-row")');
            $b->click('#fresh-row .row-label')->assertScript('window.freshViews',1);
        });}finally{$this->clean($path);}
    }
    public function test_rendered_view_row_hover_and_payment_history_eye(): void {
        $branch=Branch::create(['branch_code'=>'BR-ROW','branch_name'=>'Row Fixture','is_active'=>true]);
        $client=\App\Models\Client::create(['branch_id'=>$branch->id,'full_name'=>'Row Client','relationship_to_deceased'=>'Other','contact_number'=>'09170000000','address'=>'Test']);
        $deceased=\App\Models\Deceased::create(['branch_id'=>$branch->id,'client_id'=>$client->id,'full_name'=>'Row Deceased','address'=>'Test','died'=>'2026-04-28','date_of_death'=>'2026-04-28','age'=>60]);
        $case=\App\Models\FuneralCase::create(['branch_id'=>$branch->id,'client_id'=>$client->id,'deceased_id'=>$deceased->id,'case_code'=>'FC-ROW','wake_location'=>'Test','funeral_service_at'=>'2026-04-29','interment_at'=>'2026-05-01 09:00:00','service_requested_at'=>'2026-04-28','case_status'=>'ACTIVE','verification_status'=>'VERIFIED','entry_source'=>'MAIN','payment_status'=>'PARTIAL','total_amount'=>10000,'total_paid'=>1000,'balance_amount'=>9000]);
        \App\Models\Payment::create(['funeral_case_id'=>$case->id,'branch_id'=>$branch->id,'amount'=>1000,'method'=>'CASH','payment_mode'=>'cash','payment_method'=>'cash','paid_at'=>now(),'status'=>'POSTED']);
        $secondDeceased=$deceased->replicate();$secondDeceased->full_name='Second Row Deceased';$secondDeceased->save();
        $secondCase=$case->replicate();$secondCase->case_code='FC-ROW-2';$secondCase->deceased_id=$secondDeceased->id;$secondCase->save();
        \App\Models\Payment::create(['funeral_case_id'=>$secondCase->id,'branch_id'=>$branch->id,'amount'=>1000,'method'=>'CASH','payment_mode'=>'cash','payment_method'=>'cash','paid_at'=>now(),'status'=>'POSTED']);
        \App\Models\Package::create(['name'=>'Row Package','coffin_type'=>'Standard','price'=>10000,'is_active'=>true]);
        foreach([['system','admin','admin.branches.index'],['system','admin','admin.users.index'],['branch','admin','admin.users.index'],['system','admin','admin.service-management.index'],['branch','admin','admin.service-management.index'],['system','admin','admin.cases.index'],['branch','admin','admin.cases.index'],['','owner','owner.history'],['','staff','funeral-cases.index'],['','staff','payments.history'],['system','admin','admin.payments.index'],['branch','admin','admin.payments.index']] as [$scope,$role,$route]){
            $actor=$this->actor($branch,$scope,$role);
            User::factory()->create(['role'=>'staff','branch_id'=>$branch->id,'is_active'=>true]);
            $html=$this->actingAs($actor)->get(route($route))->assertOk()->getContent();$path=$this->fixture($html);
            try{$this->browse(function(Browser $b)use($path,$route,$scope){
                $b->driver->get('file:///'.str_replace('\\','/',$path));
                $b->waitUntil('window.PanelSkeleton!==undefined');
                if(in_array($route,['payments.history','admin.payments.index'])){
                    $b->waitFor('.pm-history-row .primary-view-action');
                    $b->assertScript('document.querySelector(".pm-history-row").hasAttribute("data-action-tooltip")',false);
                    $b->script('window.paymentEyeGap=()=>{const row=document.querySelector(".pm-history-row"),s=row.querySelector(".pm-status").getBoundingClientRect(),e=row.querySelector(".primary-view-action").getBoundingClientRect();return e.left-s.right};');
                    $b->driver->executeCustomCommand('/session/:sessionId/goog/cdp/execute','POST',['cmd'=>'Emulation.setDeviceMetricsOverride','params'=>['width'=>1440,'height'=>1000,'deviceScaleFactor'=>1,'mobile'=>false]]);
                    $b->script('document.querySelector(".pm-history-row").scrollIntoView({block:"center",behavior:"instant"});');
                    $b->screenshot('payment-eye-spacing-'.$scope);
                    $b->click('.pm-history-row .primary-view-action')->waitFor('.pm-transactions-backdrop[aria-hidden="false"]');
                    $b->assertScript('document.querySelectorAll(".pm-transactions-backdrop[aria-hidden=false]").length',1);
                    $b->waitUntil('document.querySelector("[data-pm-history-detail]").closest("tr").hasAttribute("data-clickable-row")');
                }else{
                    if(in_array($route,['admin.branches.index','admin.users.index'])) $b->click('.directory-view-toggle button[aria-label="Table view"]');
                    if($route==='admin.service-management.index') $b->click('[data-catalog-view="table"]');
                    $b->waitUntil('document.querySelector("tr[data-clickable-row]")!==null');
                }
                $b->script('window.testRow=[...document.querySelectorAll("tr[data-clickable-row]")].find(e=>e.getClientRects().length);testRow.id="actual-view-row";');
                foreach(['light','dark'] as $theme)foreach([1440,390] as $width){
                    $b->driver->executeCustomCommand('/session/:sessionId/goog/cdp/execute','POST',['cmd'=>'Emulation.setDeviceMetricsOverride','params'=>['width'=>$width,'height'=>1000,'deviceScaleFactor'=>1,'mobile'=>false]]);
                    $b->script('document.documentElement.dataset.theme='.json_encode($theme));
                    if(in_array($route,['payments.history','admin.payments.index'])){
                        $this->assertGreaterThanOrEqual(24,$b->script('return paymentEyeGap()')[0]);
                        $b->assertScript('(()=>{const rows=[...document.querySelectorAll(".pm-row-list>.pm-case-item")];return rows.length>=2&&rows.slice(0,-1).every(e=>{const s=getComputedStyle(e);return s.borderBottomWidth==="1px"&&s.borderBottomStyle==="solid"&&s.borderBottomColor===(document.documentElement.dataset.theme==="dark"?"rgb(53, 82, 65)":"rgb(184, 206, 192)")})})()',true);
                    }
                    $b->waitUntil('document.fonts.status==="loaded"');
                    $b->mouseover('h1')->pause(350);
                    $b->script('window.rowBefore=testRow.getBoundingClientRect().toJSON();window.cellBefore=[...testRow.cells].map(e=>e.getBoundingClientRect().toJSON());');
                    $b->mouseover('#actual-view-row td:first-child')->pause(200);
                    $b->script('[testRow,...testRow.cells].forEach(e=>{getComputedStyle(e).backgroundColor;e.getAnimations().filter(a=>a.transitionProperty==="background-color").forEach(a=>a.finish());});');
                    $b->assertScript('getComputedStyle(testRow).transitionDuration','0.24s');
                    $geometry=$b->script('const r=testRow.getBoundingClientRect();return {stable:Math.abs(r.width-rowBefore.width)<.1&&Math.abs(r.height-rowBefore.height)<.1&&[...testRow.cells].every((e,i)=>Math.abs(e.getBoundingClientRect().width-cellBefore[i].width)<.1),before:rowBefore,after:r.toJSON(),cells:[...testRow.cells].map(e=>e.getBoundingClientRect().toJSON()),cellBefore}')[0];
                    $this->assertTrue($geometry['stable'],$route.' '.$theme.' '.$width.' '.json_encode($geometry));
                    $b->script('const i=testRow.querySelector("[data-row-view-trigger] i");getComputedStyle(i).transform;i.getAnimations().forEach(a=>a.finish());');
                    $b->waitUntil('Math.abs(new DOMMatrixReadOnly(getComputedStyle(testRow.querySelector("[data-row-view-trigger] i")).transform).a-.9)<.001');
                    $scale=$b->script('return new DOMMatrixReadOnly(getComputedStyle(testRow.querySelector("[data-row-view-trigger] i")).transform).a')[0];
                    $this->assertEqualsWithDelta(.9,$scale,.001,$route.' row hover scale');
                    $b->assertScript('getComputedStyle(testRow).outlineStyle','none');
                    $this->assertSame($theme==='dark'?'rgb(45, 72, 56)':'rgb(225, 238, 230)',$b->script('return getComputedStyle(testRow).backgroundColor')[0],$route.' row hover fill');
                    $b->assertScript('[...testRow.cells].every(e=>!getComputedStyle(e).boxShadow.includes("inset"))',true);
                    $b->assertScript('document.getElementById("shared-record-action-tooltip").hidden',true);
                    $this->assertSame($theme==='dark'?'rgb(92, 224, 160)':'rgb(27, 67, 50)',$b->script('return getComputedStyle(testRow.querySelector("[data-row-view-trigger] i")).color')[0],$route.' '.$theme);
                    $b->screenshot('view-row-'.$scope.'-'.$route.'-'.$theme.'-'.$width);
                }
            });}finally{$this->clean($path);static::closeAll();}
        }
    }
    public function test_user_card_eye_preserves_dark_mode_hover_color(): void {
        $branch=Branch::create(['branch_code'=>'BR-EYE','branch_name'=>'Eye Fixture','is_active'=>true]);
        foreach(['system','branch'] as $scope){
            $actor=$this->actor($branch,$scope);
            User::factory()->create(['role'=>'staff','branch_id'=>$branch->id,'is_active'=>true]);
            $html=$this->actingAs($actor)->get(route('admin.users.index'))->assertOk()->getContent();$path=$this->fixture($html);
            try{$this->browse(function(Browser $b)use($path){
                $b->driver->get('file:///'.str_replace('\\','/',$path));
                $b->waitUntil('window.PanelSkeleton!==undefined');
                $b->click('.directory-view-toggle button[aria-label="Grid view"]');
                foreach(['light','dark'] as $theme)foreach([1440,390] as $width){
                    $b->driver->executeCustomCommand('/session/:sessionId/goog/cdp/execute','POST',['cmd'=>'Emulation.setDeviceMetricsOverride','params'=>['width'=>$width,'height'=>1000,'deviceScaleFactor'=>1,'mobile'=>false]]);
                    $b->script('document.documentElement.dataset.theme='.json_encode($theme));
                    $cdp=fn($cmd,$params=[])=>$b->driver->executeCustomCommand('/session/:sessionId/goog/cdp/execute','POST',['cmd'=>$cmd,'params'=>(object)$params]);
                    $cdp('DOM.enable');$cdp('CSS.enable');
                    $root=$cdp('DOM.getDocument',['depth'=>0])['root']['nodeId'];
                    $node=$cdp('DOM.querySelector',['nodeId'=>$root,'selector'=>'.user-view-button--card'])['nodeId'];
                    $cdp('CSS.forcePseudoState',['nodeId'=>$node,'forcedPseudoClasses'=>['hover']]);
                    $this->assertSame($theme==='dark'?'rgb(92, 224, 160)':'rgb(27, 67, 50)',$b->script('return getComputedStyle(document.querySelector(".user-view-button--card .bi-eye")).color')[0]);
                    $cdp('CSS.forcePseudoState',['nodeId'=>$node,'forcedPseudoClasses'=>[]]);
                }
            });}finally{$this->clean($path);}
        }
    }
    public function test_eye_shrink_keeps_the_hit_target_and_reverses_smoothly(): void {
        $html='<!doctype html><html><head></head><body class="panel-shell-body"><main class="page-content"><h1>Eye motion</h1><section class="records-page"><table><tbody><tr><td id="motion-label">Record</td><td><button type="button" class="primary-view-action record-action-control" data-action-tooltip="View record" aria-label="View record"><i class="bi bi-eye"></i></button></td></tr></tbody></table></section></main></body></html>';
        $path=$this->fixture($html);
        try{$this->browse(function(Browser $b)use($path){
            $b->driver->get('file:///'.str_replace('\\','/',$path));
            $b->waitUntil('document.querySelector("[data-clickable-row]")!==null');
            $b->script('window.eye=document.querySelector(".bi-eye");window.target=eye.parentElement;window.hitBefore=target.getBoundingClientRect().toJSON();');
            foreach(['#motion-label','.primary-view-action'] as $hover){
                $b->mouseover($hover);
                $b->script('getComputedStyle(eye).transform;window.motion=eye.getAnimations()[0];window.motionMs=motion?.effect.getTiming().duration;motion?.finish();');
                $b->assertScript('window.motionMs',180);
                $b->waitUntil('Math.abs(new DOMMatrixReadOnly(getComputedStyle(eye).transform).a-.9)<.001');
                $this->assertEqualsWithDelta(.9,$b->script('return new DOMMatrixReadOnly(getComputedStyle(eye).transform).a')[0],.001);
                $b->assertScript('getComputedStyle(eye).transitionDuration','0.18s');
                $b->assertScript('getComputedStyle(eye).transitionProperty','transform');
                $b->assertScript('target.getBoundingClientRect().width===hitBefore.width&&target.getBoundingClientRect().height===hitBefore.height',true);
                $b->mouseover('h1');
                $b->script('getComputedStyle(eye).transform;eye.getAnimations().forEach(a=>a.finish());');
                $b->waitUntil('Math.abs(new DOMMatrixReadOnly(getComputedStyle(eye).transform).a-1)<.001');
                $this->assertEqualsWithDelta(1,$b->script('return new DOMMatrixReadOnly(getComputedStyle(eye).transform).a')[0],.001);
            }
            $b->driver->executeCustomCommand('/session/:sessionId/goog/cdp/execute','POST',['cmd'=>'Emulation.setEmulatedMedia','params'=>['features'=>[['name'=>'prefers-reduced-motion','value'=>'reduce']]]]);
            $b->assertScript('getComputedStyle(eye).transitionDuration','0s');
        });}finally{$this->clean($path);}
    }
    public function test_record_headers_kpis_and_toolbars_use_flat_surfaces(): void {
        $branch=Branch::create(['branch_code'=>'BR-FLAT','branch_name'=>'Surface Fixture','is_active'=>true]);
        foreach([['system','admin','admin.branches.index'],['system','admin','admin.users.index'],['branch','admin','admin.users.index'],['system','admin','admin.service-management.index'],['branch','admin','admin.service-management.index'],['system','admin','admin.cases.index'],['branch','admin','admin.cases.index'],['','owner','owner.history'],['','staff','funeral-cases.index'],['','staff','payments.history'],['system','admin','admin.payments.index'],['branch','admin','admin.payments.index']] as [$scope,$role,$route]){
            $actor=$this->actor($branch,$scope,$role);
            $html=$this->actingAs($actor)->get(route($route))->assertOk()->getContent();$path=$this->fixture($html);
            try{$this->browse(function(Browser $b)use($path,$route,$scope){
                $b->driver->get('file:///'.str_replace('\\','/',$path));
                $b->waitUntil('window.PanelSkeleton!==undefined');
                $b->assertScript('document.body.classList.contains("record-surface-ui")',true);
                foreach(['light','dark'] as $theme)foreach([1440,1100,390] as $width){
                    $b->driver->executeCustomCommand('/session/:sessionId/goog/cdp/execute','POST',['cmd'=>'Emulation.setDeviceMetricsOverride','params'=>['width'=>$width,'height'=>1000,'deviceScaleFactor'=>1,'mobile'=>false]]);
                    $b->script('document.documentElement.dataset.theme='.json_encode($theme));
                    $b->mouseover('h1')->pause(250);
                    if(in_array($route,['admin.branches.index','admin.users.index','payments.history','admin.payments.index'])){
                        $b->assertScript('document.querySelectorAll(".record-toolbar-action>.reference-page-action").length',1);
                        $b->assertScript('document.querySelectorAll(".panel-page-header .reference-page-action").length',0);
                        $b->assertScript('(()=>{const a=document.querySelector(".record-toolbar-action").getBoundingClientRect(),bar=document.querySelector(".directory-page .table-system-toolbar,.pm-toolbar-shell").getBoundingClientRect();return a.left>=bar.left&&a.right<=bar.right+1&&a.top>=bar.top&&a.bottom<=bar.bottom})()',true);
                        $b->assertScript('(()=>{const f=document.querySelector(".worklist-filter,.pm-more-filters").getBoundingClientRect(),s=document.querySelector(".worklist-sort-control,.pm-sort-control").getBoundingClientRect(),a=document.querySelector(".record-toolbar-action").getBoundingClientRect();return Math.abs(f.top-s.top)<1&&Math.abs(f.width-s.width)<1&&Math.abs(f.height-s.height)<1&&f.right<=s.left&&((a.left>=s.right)||(a.top>=s.bottom))})()',true);
                    }
                    $b->assertScript('[...document.querySelectorAll(".branch-kpi-card__value,.user-directory-stat strong,.pm-kpi-value")].every(e=>{const dark=document.documentElement.dataset.theme==="dark",good=e.matches(".good")||e.closest("[data-kpi-tone=success]"),warn=e.matches(".warn")||e.closest("[data-kpi-tone=warning]");const expected=good?(dark?"rgb(74, 222, 128)":"rgb(22, 101, 52)"):warn?(dark?"rgb(251, 191, 36)":"rgb(146, 64, 14)"):(dark?"rgb(231, 240, 233)":"rgb(18, 53, 36)");return getComputedStyle(e).color===expected})',true);
                    $topGap=$b->script('return document.querySelector(".panel-page-header,.service-reference-header").getBoundingClientRect().top-document.querySelector(".page-content").getBoundingClientRect().top')[0];
                    $this->assertEqualsWithDelta(12,$topGap,1,$route.' header top gap');
                    $sections=$b->script('const rect=e=>e.getBoundingClientRect(),header=document.querySelector(".panel-page-header,.service-reference-header"),kpis=document.querySelector(".branch-kpi-strip,.user-directory-stats,.pm-kpis"),tabs=document.querySelector(".case-records-tabs-row"),toolbar=document.querySelector(".case-records-top-wrapper,.master-records-page .admin-table-toolbar,.directory-page .table-system-toolbar,.service-hub-toolbar,.pm-toolbar-shell"),intro=document.querySelector(".service-section-intro");const next=(kpis?document.querySelector(".pm-section-tabs"):null)||kpis||tabs||intro||toolbar,a=rect(header),z=rect(next);return {gap:z.top-a.bottom,left:z.left-a.left,right:z.right-a.right,kpis:!!kpis,kpiBg:kpis?getComputedStyle(kpis).backgroundColor:null,headerBg:getComputedStyle(header).backgroundColor,nextGap:kpis?rect(toolbar).top-rect(kpis).bottom:null,tabsGap:tabs?rect(toolbar).top-rect(tabs).bottom:null}') [0];
                    $this->assertEqualsWithDelta($sections['kpis']||$sections['tabsGap']!==null?0:16,$sections['gap'],1,$scope.' '.$route.' '.$theme.' '.$width.' section gap '.json_encode($sections));
                    if($sections['kpis']){
                        foreach(['left','right','nextGap'] as $edge)$this->assertEqualsWithDelta($edge==='nextGap'?16:0,$sections[$edge],1,$route.' summary '.$edge);
                        $this->assertSame($sections['headerBg'],$sections['kpiBg']);
                    }
                    if($sections['tabsGap']!==null){
                        $this->assertEqualsWithDelta(16,$sections['tabsGap'],1,$route.' tabs to toolbar');
                        foreach(['left','right'] as $edge)$this->assertEqualsWithDelta(0,$sections[$edge],1,$route.' header/tabs '.$edge);
                    }
                    if($sections['kpis']||$sections['tabsGap']!==null){
                        $b->assertScript('(()=>{const e=document.querySelector(".panel-page-header.panel-title-surface"),s=getComputedStyle(e,"::after");return getComputedStyle(e).borderBottomWidth==="0px"&&s.content!=="none"&&s.left==="16px"&&s.right==="16px"&&s.height==="1px"&&s.pointerEvents==="none"&&s.backgroundColor===(document.documentElement.dataset.theme==="dark"?"rgb(53, 82, 65)":"rgb(184, 206, 192)")})()',true);
                        $gap=$b->script('const h=document.querySelector(".panel-page-header.panel-title-surface"),content=document.querySelector(".pm-section-tabs a,.branch-kpi-card,.user-directory-stat,.pm-kpi,.case-records-tabs");return content.getBoundingClientRect().top-h.getBoundingClientRect().bottom')[0];
                        $this->assertEqualsWithDelta(16,$gap,1,$route.' '.$theme.' '.$width.' divider to attached content');
                    }
                    $b->assertScript('[...document.querySelectorAll(".branch-kpi-card,.user-directory-stat,.pm-kpi")].every(e=>{const s=getComputedStyle(e);return s.minHeight==="140px"&&s.paddingTop==="20px"&&s.paddingBottom==="20px"})',true);
                    if(in_array($route,['admin.branches.index','admin.users.index','admin.service-management.index'])){
                        $b->script('window.scrollTo(0,0);document.querySelector("[aria-label=\"Grid view\"]").click();');
                        $b->pause(100);
                        $grid=$b->script('const bar=document.querySelector(".directory-page .table-system-toolbar,.service-hub-toolbar"),tray=document.querySelector("[data-record-results=cards],.service-hub-panels"),card=tray.querySelector(".directory-item-card,.service-package-card"),a=bar.getBoundingClientRect(),z=tray.getBoundingClientRect();return {gap:z.top-a.bottom,left:z.left-a.left,right:z.right-a.right,bg:getComputedStyle(tray).backgroundColor,barBg:getComputedStyle(bar).backgroundColor,pageBg:getComputedStyle(document.querySelector(".page-content")).backgroundColor,cardBg:card?getComputedStyle(card).backgroundColor:null,radius:getComputedStyle(tray).borderBottomLeftRadius,shadow:getComputedStyle(tray).boxShadow,border:getComputedStyle(tray).borderTopWidth,barRadius:getComputedStyle(bar).borderBottomLeftRadius}') [0];
                        foreach(['gap','left','right'] as $edge)$this->assertEqualsWithDelta(0,$grid[$edge],1,$scope.' '.$route.' grid '.$theme.' '.$width.' '.$edge);
                        $this->assertSame($theme==='dark'?'rgb(21, 37, 28)':'rgb(247, 250, 248)',$grid['bg']);
                        $this->assertSame($grid['bg'],$grid['barBg']);$this->assertNotSame($grid['bg'],$grid['pageBg']);
                        if($grid['cardBg']!==null)$this->assertSame($theme==='dark'?'rgb(16, 30, 22)':'rgb(234, 242, 237)',$grid['cardBg']);
                        $this->assertSame('12px',$grid['radius']);$this->assertSame('0px',$grid['barRadius']);$this->assertSame('0px',$grid['border']);$this->assertSame('none',$grid['shadow']);
                        $b->assertScript('(()=>{const s=getComputedStyle(document.querySelector(".directory-page .table-system-toolbar,.service-hub-toolbar"));return s.borderBottomWidth==="1px"&&s.borderBottomStyle==="solid"&&s.borderBottomColor===(document.documentElement.dataset.theme==="dark"?"rgb(53, 82, 65)":"rgb(184, 206, 192)")})()',true);
                        $b->screenshot('connected-grid-'.$scope.'-'.$route.'-'.$theme.'-'.$width);
                    }
                    $b->script('window.scrollTo(0,0);document.querySelector("[aria-label=\"Table view\"]")?.click();');
                    $b->pause(100);
                    $join=$b->script('const bar=document.querySelector(".case-records-top-wrapper,.master-records-page .admin-table-toolbar,.directory-page .table-system-toolbar,.service-hub-toolbar,.pm-toolbar-shell");const table=[...document.querySelectorAll(".table-system-wrap,.pm-panel")].find(e=>e.getClientRects().length);const a=bar.getBoundingClientRect(),z=table.getBoundingClientRect();return {gap:z.top-a.bottom,left:z.left-a.left,right:z.right-a.right,barRadius:getComputedStyle(bar).borderBottomLeftRadius,tableRadius:getComputedStyle(table).borderTopLeftRadius}')[0];
                    foreach(['gap','left','right'] as $edge)$this->assertEqualsWithDelta(0,$join[$edge],1,$scope.' '.$route.' '.$theme.' '.$width.' joined '.$edge);
                    $this->assertSame('0px',$join['barRadius']);
                    $this->assertSame('0px',$join['tableRadius']);
                    $b->assertScript('(()=>{const heads=[...document.querySelectorAll(".table-system-table thead tr:last-child>th,.pm-record-head")];return heads.length>0&&heads.every(e=>{const s=getComputedStyle(e);return s.borderBottomWidth==="1px"&&s.borderBottomStyle==="solid"&&s.borderBottomColor===(document.documentElement.dataset.theme==="dark"?"rgb(53, 82, 65)":"rgb(184, 206, 192)")})})()',true);
                    $b->assertScript('(()=>{const s=getComputedStyle(document.querySelector(".case-records-top-wrapper,.master-records-page .admin-table-toolbar,.directory-page .table-system-toolbar,.service-hub-toolbar,.pm-toolbar-shell"));return s.borderBottomWidth==="1px"&&s.borderBottomStyle==="solid"&&s.borderBottomColor===(document.documentElement.dataset.theme==="dark"?"rgb(53, 82, 65)":"rgb(184, 206, 192)")})()',true);
                    if(!str_contains($route,'payments'))$b->assertScript('[...document.querySelectorAll(".table-system-table thead,.table-system-table thead tr,.table-system-table th")].every(e=>getComputedStyle(e).backgroundColor==="rgba(0, 0, 0, 0)")',true);
                    $paint=$b->script('const visible=s=>[...document.querySelectorAll(s)].filter(e=>e.getClientRects().length);const paint=e=>{const s=getComputedStyle(e);return {tag:e.className,bg:s.backgroundColor,border:s.borderTopColor,shadow:s.boxShadow}};return {page:getComputedStyle(document.querySelector(".page-content")).backgroundColor,headers:visible(".panel-page-header,.service-reference-header").map(paint),cards:visible(".user-directory-stat,.branch-kpi-card,.pm-kpi").map(paint),bars:visible(".table-system-toolbar:not(.case-records-controls),.case-records-top-wrapper,.pm-toolbar-shell,.service-hub-toolbar").map(paint),controls:visible(".worklist-search-control,.service-record-search,.case-compact-search-control,.pm-field.search,.case-compact-more,.case-compact-sort-filter,.worklist-filter>summary,.worklist-sort-control,.catalog-control>summary,.pm-more-filters>summary,.pm-sort-control").map(paint)}')[0];
                    $context=$scope.' '.$route.' '.$theme.' '.$width;
                    $b->assertScript('[...document.querySelectorAll(".directory-item-card,.service-package-card,.table-system-wrap,.pm-panel,.table-system-pagination,.pm-foot")].every(e=>getComputedStyle(e).borderTopColor==="rgba(0, 0, 0, 0)"&&getComputedStyle(e).boxShadow==="none")',true);
                    $native=$b->script('return [...document.querySelectorAll("[name=per_page],select[name=sort]")].filter(e=>e.getClientRects().length).map(e=>[e.name,getComputedStyle(e).backgroundColor,e.outerHTML.slice(0,150)])')[0];
                    foreach($native as $field)$this->assertSame($field[0]==='sort'&&in_array($route,['funeral-cases.index','admin.cases.index','owner.history'])?'rgba(0, 0, 0, 0)':($theme==='dark'?'rgb(34, 57, 44)':'rgb(234, 242, 237)'),$field[1],$context.' '.json_encode($field));

                    $b->assertScript('[...document.querySelectorAll(".directory-item-card,.service-package-card,.row-action-dropdown")].every(e=>getComputedStyle(e).boxShadow==="none")',true);
                    $this->assertSame($theme==='dark'?'rgb(13, 23, 18)':'rgb(230, 238, 232)',$paint['page'],$context.' page');
                    foreach(['headers','cards','bars','controls'] as $kind){
                        if($kind!=='cards')$this->assertNotEmpty($paint[$kind],$context.' '.$kind);
                        $expected=$kind==='headers'?($theme==='dark'?'rgb(28, 48, 37)':'rgb(213, 228, 217)'):($kind==='controls'?($theme==='dark'?'rgb(34, 57, 44)':'rgb(234, 242, 237)'):($theme==='dark'?'rgb(21, 37, 28)':'rgb(247, 250, 248)'));
                        foreach($paint[$kind] as $item){
                            $this->assertSame($expected,$item['bg'],$context.' '.$kind.' '.$item['tag']);
                            $this->assertSame('rgba(0, 0, 0, 0)',$item['border'],$context.' border '.$item['tag']);
                            $this->assertSame('none',$item['shadow'],$context.' shadow '.$item['tag']);
                        }
                    }
                    $b->screenshot('flat-surfaces-'.$scope.'-'.$route.'-'.$theme.'-'.$width);
                    if(in_array($route,['funeral-cases.index','admin.cases.index','owner.history'])){
                        $b->assertScript('[...document.querySelectorAll(".case-records-table th,.master-records-page th")].every(e=>getComputedStyle(e).backgroundColor==="rgba(0, 0, 0, 0)")',true);
                        $b->assertScript('(()=>{const s=document.querySelector(".case-compact-sort-filter>select"),w=s.parentElement,r=w.getBoundingClientRect(),a=s.getBoundingClientRect();return Math.abs(a.width-r.width)<2&&Math.abs(a.height-r.height)<2&&[3,r.width/2,r.width-3].every(x=>document.elementFromPoint(r.left+x,r.top+r.height/2)===s)})()',true);
                        $b->mouseover('.case-compact-sort-filter');
                        $b->assertScript('getComputedStyle(document.querySelector(".case-compact-sort-filter")).outlineStyle','none');
                        $b->assertScript('getComputedStyle(document.querySelector(".case-compact-sort-filter>select")).backgroundColor','rgba(0, 0, 0, 0)');
                        $this->assertSame($theme==='dark'?'rgb(45, 73, 56)':'rgb(216, 231, 221)',$b->script('return getComputedStyle(document.querySelector(".case-compact-sort-filter")).backgroundColor')[0]);
                        $b->mouseover('h1');
                    }
                }
                $b->script('window.surfaceFilter=document.querySelector("[data-case-more-toggle],.worklist-filter>summary,.catalog-control--filter>summary,.pm-more-filters>summary");surfaceFilter.scrollIntoView({block:"center"});');
                $b->script('window.filterArrow=surfaceFilter.querySelector("[data-case-more-icon],.catalog-control-chevron,.bi-chevron-down");getComputedStyle(filterArrow).rotate;filterArrow.getAnimations().forEach(a=>a.finish());');
                $b->assertScript('getComputedStyle(filterArrow).rotate','0deg');
                $b->script('surfaceFilter.click();');
                $b->script('getComputedStyle(filterArrow).rotate;filterArrow.getAnimations().forEach(a=>a.finish());');
                $b->assertScript('getComputedStyle(filterArrow).rotate','180deg');
                $b->assertScript('getComputedStyle(filterArrow).transform','none');
                $b->assertScript('getComputedStyle(filterArrow).transitionDuration','0.16s');
                if(in_array($route,['funeral-cases.index','admin.cases.index','owner.history'])){
                    $b->assertScript('(()=>{const field=document.querySelector(".case-compact-date-filter"),icon=field.querySelector(".case-compact-date-icon"),s=getComputedStyle(icon);return field.querySelectorAll(".shared-record-filter-icon").length===1&&icon.classList.contains("bi-funnel")&&icon.getBoundingClientRect().width===16&&s.visibility==="visible"&&s.opacity==="1"&&s.pointerEvents==="none"&&getComputedStyle(field.querySelector("select")).backgroundColor==="rgba(0, 0, 0, 0)"})()',true);
                }
                $panels=$b->script('return [...document.querySelectorAll(".worklist-filter-panel,.case-compact-drawer,.pm-more-panel,.catalog-control--filter .catalog-control-panel")].filter(e=>e.getClientRects().length).map(e=>{const s=getComputedStyle(e);return {bg:s.backgroundColor,border:s.borderTopColor,shadow:s.boxShadow}})')[0];
                $this->assertNotEmpty($panels,$route.' open filter');
                $b->screenshot('flat-filter-open-'.$scope.'-'.$route);
                foreach($panels as $panel){
                    $this->assertSame('rgb(28, 48, 37)',$panel['bg'],$route.' open background');
                    $this->assertSame('rgba(0, 0, 0, 0)',$panel['border'],$route.' open border');
                    $this->assertSame('none',$panel['shadow'],$route.' open shadow');
                }
                $b->assertScript('[...document.querySelectorAll(".case-compact-pop-apply,.worklist-apply,[data-pm-apply-filters]")].filter(e=>e.getClientRects().length).every(e=>getComputedStyle(e).backgroundColor==="rgb(169, 201, 181)")',true);
                $b->assertScript('[...document.querySelectorAll(".worklist-filter-panel select,.case-compact-drawer select,.pm-more-panel select")].filter(e=>e.getClientRects().length).every(e=>getComputedStyle(e).backgroundColor===(e.matches("[data-case-date-preset-select]")?"rgba(0, 0, 0, 0)":"rgb(34, 57, 44)"))',true);
                $b->script('window.fieldHover=[...document.querySelectorAll(".worklist-filter-panel [data-filter-select-wrap],.case-compact-drawer [data-filter-select-wrap],.pm-more-panel [data-filter-select-wrap]")].find(e=>e.getClientRects().length);if(fieldHover)fieldHover.id="field-hover-probe";');
                $cdp=fn($cmd,$params=[])=>$b->driver->executeCustomCommand('/session/:sessionId/goog/cdp/execute','POST',['cmd'=>$cmd,'params'=>$params?:new \stdClass]);
                $cdp('DOM.enable');$cdp('CSS.enable');$root=$cdp('DOM.getDocument',['depth'=>0])['root']['nodeId'];
                if($b->script('return !!fieldHover')[0]){
                    $fieldNode=$cdp('DOM.querySelector',['nodeId'=>$root,'selector'=>'#field-hover-probe'])['nodeId'];
                    $cdp('CSS.forcePseudoState',['nodeId'=>$fieldNode,'forcedPseudoClasses'=>['hover']]);
                    $b->assertScript('getComputedStyle(fieldHover).backgroundColor','rgb(45, 72, 56)');
                    $cdp('CSS.forcePseudoState',['nodeId'=>$fieldNode,'forcedPseudoClasses'=>[]]);
                }
                $b->script('document.dispatchEvent(new KeyboardEvent("keydown",{key:"Tab",bubbles:true}));document.querySelector("input[name=q]").focus();');
                $b->assertScript('getComputedStyle(document.querySelector("input[name=q]")).outlineStyle','solid');
                $b->assertScript('getComputedStyle(document.querySelector("input[name=q]")).outlineWidth','2px');
                $b->script('window.sortTrigger=document.querySelector(".catalog-control--sort>summary")||document.querySelector("select[name=sort]");sortTrigger.click();');
                $b->script('window.sortArrow=(sortTrigger.matches("select")?sortTrigger.parentElement:sortTrigger).querySelector(".catalog-control-chevron,.case-compact-sort-chev,.pm-sel-chev,.table-toolbar-select-icon");getComputedStyle(sortArrow).rotate;sortArrow.getAnimations().forEach(a=>a.finish());');
                $b->assertScript('getComputedStyle(sortArrow).rotate','180deg');
                $b->assertScript('getComputedStyle(sortArrow).transform','none');
                $menus=$b->script('return [...document.querySelectorAll(".filter-select-menu,.catalog-control--sort .catalog-control-panel")].filter(e=>e.getClientRects().length).map(e=>[e.className,getComputedStyle(e).backgroundColor,getComputedStyle(e).borderTopColor,getComputedStyle(e).boxShadow])')[0];
                $this->assertNotEmpty($menus,$route.' sort opens');
                foreach($menus as $menu){
                    $this->assertSame(str_contains($menu[0],'catalog-control-panel')?'rgb(28, 48, 37)':'rgb(48, 76, 59)',$menu[1],$route.' sort menu');
                    $this->assertSame('rgba(0, 0, 0, 0)',$menu[2]);$this->assertSame('none',$menu[3]);
                }
                $b->script('window.optionHover=[...document.querySelectorAll(".filter-select-menu__item,.catalog-control-option")].find(e=>e.getClientRects().length);optionHover.id="option-hover-probe";');
                $optionNode=$cdp('DOM.querySelector',['nodeId'=>$root,'selector'=>'#option-hover-probe'])['nodeId'];
                $cdp('CSS.forcePseudoState',['nodeId'=>$optionNode,'forcedPseudoClasses'=>['hover']]);
                $b->assertScript('getComputedStyle(optionHover).backgroundColor','rgb(45, 72, 56)');
                $cdp('CSS.forcePseudoState',['nodeId'=>$optionNode,'forcedPseudoClasses'=>[]]);
                $b->assertScript('(()=>{const groups=[...document.querySelectorAll(".filter-select-menu,.catalog-control-choices")].filter(e=>e.getClientRects().length);return groups.length>0&&groups.every(g=>{const items=[...g.querySelectorAll(".filter-select-menu__item,.catalog-control-option")].filter(e=>e.getClientRects().length);return items.slice(1).every((e,i)=>Math.abs(e.getBoundingClientRect().top-items[i].getBoundingClientRect().bottom-4)<1)})})()',true);
                $b->screenshot('flat-sort-open-'.$scope.'-'.$route);
                $b->assertScript('[...document.querySelectorAll(".filter-select-menu-heading")].filter(e=>e.getClientRects().length).every(e=>Math.abs(e.nextElementSibling.getBoundingClientRect().top-e.getBoundingClientRect().bottom-12)<1)',true);
                $b->script('sortTrigger.click();window.sizeTrigger=document.querySelector("[data-case-page-size-options]")||document.querySelector("select[name=per_page]");if(sizeTrigger)sizeTrigger.click();');
                $b->script('getComputedStyle(sortArrow).rotate;sortArrow.getAnimations().forEach(a=>a.finish());');
                $b->assertScript('getComputedStyle(sortArrow).rotate','0deg');
                if($b->script('return !!sizeTrigger')[0]){
                    $sizes=$b->script('return [...document.querySelectorAll(".case-page-size-menu,.filter-select-menu")].filter(e=>e.getClientRects().length).map(e=>[getComputedStyle(e).backgroundColor,getComputedStyle(e).boxShadow])')[0];
                    $this->assertNotEmpty($sizes,$route.' page-size opens');
                    foreach($sizes as $size){$this->assertSame('rgb(48, 76, 59)',$size[0]);$this->assertSame('none',$size[1]);}
                }
            });}finally{$this->clean($path);}
        }
    }
    public function test_record_modals_use_the_updated_flat_surfaces(): void {
        $branch=Branch::create(['branch_code'=>'BR-MODAL','branch_name'=>'Modal Fixture','is_active'=>true]);
        $actor=$this->actor($branch,'system');
        User::factory()->create(['role'=>'staff','branch_id'=>$branch->id,'is_active'=>true]);
        $package=\App\Models\Package::create(['name'=>'Modal Package','coffin_type'=>'Standard','price'=>10000,'is_active'=>true]);
        foreach([
            [route('admin.branches.index'),'#openBranchCreateModal','#branchCreateModalSheet'],
            [route('admin.users.index'),'.user-view-button--card','.user-information-dialog'],
            [route('admin.packages.show',['package'=>$package,'modal'=>1]),null,'.svc-detail'],
        ] as [$url,$trigger,$panel]){
            $html=$this->actingAs($actor)->get($url)->assertOk()->getContent();$path=$this->fixture($html);
            try{$this->browse(function(Browser $b)use($path,$trigger,$panel){
                $b->driver->get('file:///'.str_replace('\\','/',$path));
                $b->waitUntil('document.readyState==="complete"&&window.PanelSkeleton!==undefined');
                if($panel==='#branchCreateModalSheet'){
                    $b->script('const d=document.getElementById("branchCreateModalOverlay");d.classList.remove("hidden");d.showModal();const p=document.getElementById("branchCreateModalSheet");p.classList.remove("opacity-0","scale-95");p.classList.add("opacity-100","scale-100");');
                }elseif($trigger)$b->script('document.querySelector('.json_encode($trigger).').click();');
                $b->waitUntil('document.querySelector('.json_encode($panel).')!==null');
                foreach(['light','dark'] as $theme)foreach([1440,390] as $width){
                    $b->driver->executeCustomCommand('/session/:sessionId/goog/cdp/execute','POST',['cmd'=>'Emulation.setDeviceMetricsOverride','params'=>['width'=>$width,'height'=>1000,'deviceScaleFactor'=>1,'mobile'=>false]]);
                    $b->script('document.documentElement.dataset.theme='.json_encode($theme));
                    $paint=$b->script('const p=document.querySelector('.json_encode($panel).');const s=getComputedStyle(p);return [s.backgroundColor,s.borderTopColor,s.boxShadow]')[0];
                    $this->assertSame($theme==='dark'?'rgb(21, 37, 28)':'rgb(247, 250, 248)',$paint[0],$panel.' '.$theme);
                    $this->assertSame('rgba(0, 0, 0, 0)',$paint[1]);$this->assertSame('none',$paint[2]);
                    $b->screenshot('flat-modal-'.str_replace(['#','.'],'',$panel).'-'.$theme.'-'.$width);
                }
            });}finally{$this->clean($path);}
        }
    }
    public function test_branch_more_actions_reopens_after_edit_cancel_without_replaying_scripts(): void {
        $branch=Branch::create(['branch_code'=>'BR-REOPEN','branch_name'=>'Reopen Fixture','address'=>'Fixture Street','is_active'=>true]);
        $actor=$this->actor($branch,'system');
        $html=$this->actingAs($actor)->get(route('admin.branches.index'))->assertOk()->getContent();
        $edit=$this->actingAs($actor)->get(route('admin.branches.edit',$branch))->assertOk()->getContent().'<script>window.unwantedShellRuns++;</script>';
        $path=$this->fixture($html,'window.unwantedShellRuns=0;window.editLoads=0;window.fetch=async(url)=>{if(!String(url).includes("/edit"))throw Error("Isolated unrelated request");editLoads++;return {ok:true,text:async()=>'.json_encode($edit,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT).'}};');
        try{$this->browse(function(Browser $b)use($path){
            $b->driver->get('file:///'.str_replace('\\','/',$path));
            $b->waitUntil('document.readyState==="complete"&&document.documentElement.dataset.rowActionMenusReady==="1"');
            foreach([['Grid','light',1440],['Table','dark',390]] as [$view,$theme,$width]){
                $b->driver->executeCustomCommand('/session/:sessionId/goog/cdp/execute','POST',['cmd'=>'Emulation.setDeviceMetricsOverride','params'=>['width'=>$width,'height'=>1000,'deviceScaleFactor'=>1,'mobile'=>false]]);
                $b->script('document.documentElement.dataset.theme='.json_encode($theme));
                $b->click('.directory-view-toggle button[aria-label="'.$view.' view"]');
                $root=$view==='Grid'?'.directory-item-card':'[data-record-results=table] tr';
                $trigger=$root.' [data-row-menu-trigger]';
                if($view==='Grid'){
                    $gap=$b->script('const c=document.querySelector(".directory-item-card"),a=c.querySelector(".branch-reference-actions");return c.getBoundingClientRect().bottom-a.getBoundingClientRect().bottom')[0];
                    $this->assertLessThanOrEqual(16,$gap,'No empty area below card actions');
                    $b->assertScript('getComputedStyle(document.querySelector(".directory-item-card")).minHeight','0px');
                }
                for($cycle=0;$cycle<3;$cycle++){
                    $b->script('document.querySelector('.json_encode($trigger).').scrollIntoView({block:"center",inline:"center"});');
                    $b->click($trigger)->assertAttribute($trigger,'aria-expanded','true');
                    $b->assertScript('document.querySelector('.json_encode($root.' .row-action-dropdown').').matches(":popover-open")',true);
                    $b->click($root.' [data-branch-card-action=edit]');
                    $b->waitUntil('document.querySelector("#branchEditForm input[name=branch_name]")!==null');

                    $b->assertScript('window.unwantedShellRuns',0);
                    $b->script('document.querySelector("#branchEditForm .branch-modal-cancel").click();');
                    $b->waitUntil('!document.querySelector("#branchModalOverlay").open');
                    $b->assertAttribute($trigger,'aria-expanded','false');
                }
                $b->click($trigger)->assertAttribute($trigger,'aria-expanded','true');
                $b->click($trigger)->assertAttribute($trigger,'aria-expanded','false');
                $b->click($trigger)->assertAttribute($trigger,'aria-expanded','true');
                $b->script('document.querySelector(".page-content").dispatchEvent(new Event("scroll"));');
                $b->assertAttribute($trigger,'aria-expanded','false');
            }
            $b->assertScript('window.editLoads',6);
        });}finally{$this->clean($path);}
    }
    public function test_report_and_analytics_custom_dates_are_non_modal_and_branch_precedes_date():void{
        $branch=Branch::create(['branch_code'=>'BR-REPORT','branch_name'=>'Reports Fixture','is_active'=>true]);
        foreach([['system','admin'],['branch','admin'],['','owner']]as[$scope,$role]){
            $actor=$this->actor($branch,$scope,$role);
            foreach(['reports','analytics']as$kind){
                $route=$kind==='reports'?'reports.index':($role==='owner'?'owner.analytics':'reports.analytics');
                $html=$this->actingAs($actor)->get(route($route,$kind==='reports'?['report_type'=>'sales']:[]))->assertOk()->getContent();$path=$this->fixture($html);
                try{$this->browse(function(Browser$b)use($path,$kind,$scope,$role){
                    $b->driver->get('file:///'.str_replace('\\','/',$path));$b->waitUntil('window.openReportDatePicker!==undefined');
                    foreach(['light','dark']as$theme)foreach([1440,390]as$width){
                        $b->driver->executeCustomCommand('/session/:sessionId/goog/cdp/execute','POST',['cmd'=>'Emulation.setDeviceMetricsOverride','params'=>['width'=>$width,'height'=>1000,'deviceScaleFactor'=>1,'mobile'=>$width<500]]);
                        $b->script('document.documentElement.dataset.theme='.json_encode($theme));
                        $b->assertScript('document.querySelector("[data-report-filter-controls]")!==null',true);
                        $select=$kind==='reports'?'reportsHeaderDatePreset':'analyticsHeaderRange';
                        $control='document.getElementById('.json_encode($select).').closest("[data-filter-select-wrap]")';
                        $b->assertScript($control.'.classList.contains("reports-module-control-wrap")',true);
                        $b->assertScript($control.'.querySelectorAll(":scope > i.bi-calendar3").length',1);
                        $b->assertScript($control.'.getBoundingClientRect().height',44);
                    }
                    $select=$kind==='reports'?'reportsHeaderDatePreset':'analyticsHeaderRange';
                    $b->script('const select=document.getElementById('.json_encode($select).');select.value="CUSTOM";select.dispatchEvent(new Event("change",{bubbles:true}));');
                    $b->waitUntil('document.querySelector("[data-report-date-panel]:popover-open")!==null');
                    $b->assertScript('document.querySelector("[data-report-date-panel]:modal")===null',true);
                    $b->assertScript('getComputedStyle(document.querySelector("[data-report-date-panel]:popover-open"),"::backdrop").backgroundColor','rgba(0, 0, 0, 0)');
                    $b->assertScript('document.querySelector("[data-report-date-panel]:popover-open").getBoundingClientRect().width<=innerWidth-24',true);
                    $b->screenshot('report-filters-'.$scope.'-'.$role.'-'.$kind);
                });}finally{$this->clean($path);}
            }
        }
    }
}

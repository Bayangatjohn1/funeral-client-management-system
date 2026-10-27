<?php

namespace Tests\Browser;

use App\Models\FuneralCase;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/** Uses run-ui-readability.php's guarded disposable database, never application data. */
class NotificationReadabilityTest extends DuskTestCase
{
    private array $measurements=[];
    private array $checks=[];
    private array $failures=[];
    private string $out;

    public function test_shared_notification_dropdown(): void
    {
        $this->assertSame('sqlite',config('database.default'));
        $this->assertSame(str_replace('\\','/',storage_path('app/ui-corrections-20261026/ui-readability.sqlite')),str_replace('\\','/',config('database.connections.sqlite.database')));
        $before=getenv('NOTIFICATION_QA_STAGE')==='before';
        $this->out=storage_path('app/notification-ui-20261026/'.($before?'before':'after'));
        if(!is_dir($this->out)) mkdir($this->out,0777,true);
        $case=FuneralCase::firstOrFail();
        $case->forceFill(['verification_status'=>'VERIFIED','case_status'=>'ACTIVE','created_at'=>now(),'service_requested_at'=>today(),'wake_start_date'=>today()->subDays(3),'wake_start_time'=>'08:00','wake_end_date'=>today()->subDay(),'wake_end_time'=>'08:00','interment_at'=>now()->subDay(),'interment_time'=>'10:00'])->save();
        for($i=1;$i<8;$i++) {
            $deceased=$case->deceased->replicate();$deceased->full_name='Readability QA deceased with an extended family name '.$i;$deceased->save();
            $copy=$case->replicate();
            $copy->forceFill(['deceased_id'=>$deceased->id,'case_code'=>$i===1?'QA-LONG-CASE-REFERENCE-ABCDEFGHIJKLMNOPQRSTUVWXYZ-0123456789':'QA-NOTIFICATION-'.$i]);
            if($i===2) $copy->forceFill(['funeral_service_at'=>today(),'funeral_service_time'=>'12:00','interment_at'=>now()->addDays(6)]);
            if($i===3) $copy->forceFill(['funeral_service_at'=>now()->addDays(4),'interment_at'=>now()->addDays(6)]);
            if($i===4) $copy->forceFill(['funeral_service_at'=>now()->addDays(30),'interment_at'=>now()->addDays(31),'wake_start_date'=>null,'wake_end_date'=>null]);
            $copy->save();
        }
        foreach(['populated','empty'] as $state) {
            if($before && $state==='empty') break;
            if($state==='empty') DB::table('funeral_cases')->update(['deleted_at'=>now()]);
            foreach(['staff','branch_admin','system_admin'] as $role) {
                $user=User::factory()->create(['role'=>$role==='staff'?'staff':'admin','admin_scope'=>$role==='system_admin'?'system':($role==='branch_admin'?'branch':null),'branch_id'=>$role==='system_admin'?null:$case->branch_id,'is_active'=>true,'must_change_password'=>false]);
                $user->two_factor_secret=(new \PragmaRX\Google2FA\Google2FA)->generateSecretKey();$user->two_factor_confirmed_at=now();$user->save();
                if($user->branch_id) DB::table('user_branches')->insert(['user_id'=>$user->id,'branch_id'=>$user->branch_id,'created_at'=>now(),'updated_at'=>now()]);
                $home=$role==='staff'?'/staff':'/admin';
                $this->browse(function(Browser $b) use($user,$home,$role,$state,$case,$before) {
                    $b->loginAs($user);
                    foreach(DB::table('sessions')->where('user_id',$user->id)->get() as $session) {
                        $payload=unserialize(base64_decode($session->payload),['allowed_classes'=>false]);
                        $payload['mfa_user_id']=(int)$user->id;$payload['account_security']=\App\Support\AccountSecurity::fingerprint($user->fresh());$payload['idle_last_activity']=now()->timestamp;
                        $payload['idle_session_key']=(string)\Illuminate\Support\Str::uuid();
                        DB::table('sessions')->where('id',$session->id)->update(['payload'=>base64_encode(serialize($payload))]);
                    }
                    foreach(['light','dark'] as $theme) foreach([1440,390] as $width) {
                        $this->visit($b,$home);
                        $b->driver->executeCustomCommand('/session/:sessionId/goog/cdp/execute','POST',['cmd'=>'Emulation.setDeviceMetricsOverride','params'=>['width'=>$width,'height'=>$width===390?844:900,'deviceScaleFactor'=>1,'mobile'=>false]]);
                        $b->script('localStorage.setItem("app-theme",'.json_encode($theme).');document.documentElement.dataset.theme='.json_encode($theme));
                        $b->pause(600)->click('[data-notification-toggle]')->waitUntil('!document.querySelector("[data-notification-menu]").hidden')->pause(250);
                        $key="$role-$state-$theme-$width";
                        $this->capture($b,$key,$before);
                        if(!$before) {
                            $this->geometry($b,$key);
                            $linkSelector=$state==='populated'?'.topbar-notification-menu:not([hidden]) .topbar-notification-card:not([hidden])':'.topbar-notification-footer-btn';
                            $href=$b->attribute($linkSelector,'href');
                            $this->assertSame($role==='staff'?'/reminders':'/admin/reminders',parse_url($href,PHP_URL_PATH));
                            if($state==='populated') {
                                parse_str(parse_url($href,PHP_URL_QUERY),$query);
                                $this->assertSame((string)$case->branch_id,(string)($query['branch_id']??null));
                                $this->assertNotEmpty($query['focus_case']??null);
                            }
                            $this->checks[$key.'-link-context']=['target'=>$href,'activation'=>'not claimed: browser navigation attempts timed out'];
                            if($state==='populated') {
                                $b->mouseover('.topbar-notification-menu:not([hidden]) .topbar-notification-card:not([hidden])')->pause(250);
                                $this->capture($b,$key.'-hover',false);
                                $b->keys('[data-notification-toggle]','{tab}')->pause(100);
                                $this->assertTrue($b->script('return document.querySelector("[data-notification-menu]").contains(document.activeElement)')[0],$key.' Tab enters dropdown');
                                $b->script('document.querySelector(".topbar-notification-menu:not([hidden]) .topbar-notification-card:not([hidden])").focus()');
                                $focus=$b->script('const e=document.activeElement,s=getComputedStyle(e);return {visible:e.matches(":focus-visible"),outline:s.outlineColor,width:s.outlineWidth,background:s.backgroundColor}')[0];
                                $this->checks[$key.'-focus']=$focus;
                                $this->assertTrue($focus['visible'],$key.' keyboard focus visible');
                                $this->capture($b,$key.'-focus',false);
                                $wheel=$b->script('const e=document.querySelector("[data-notification-list]"),r=e.getBoundingClientRect();return {x:r.left+r.width/2,y:r.top+r.height/2,page:document.querySelector(".page-content").scrollTop,window:scrollY}')[0];
                                foreach([600,1200,1200] as $delta) {
                                    $b->driver->executeCustomCommand('/session/:sessionId/goog/cdp/execute','POST',['cmd'=>'Input.dispatchMouseEvent','params'=>['type'=>'mouseWheel','x'=>$wheel['x'],'y'=>$wheel['y'],'deltaY'=>$delta,'deltaX'=>0]]);$b->pause(150);
                                }
                                $scroll=$b->script('const e=document.querySelector("[data-notification-list]");return {scroll:e.scrollTop,scrollHeight:e.scrollHeight,clientHeight:e.clientHeight,pageUnmoved:document.querySelector(".page-content").scrollTop==='.json_encode($wheel['page']).'&&scrollY==='.json_encode($wheel['window']).'}')[0];
                                $this->checks[$key.'-scroll']=$scroll;
                                if($scroll['scrollHeight']>$scroll['clientHeight']+1 && $scroll['scroll']<=0) $this->failures[]=$key.' list cannot scroll';
                                if(!$scroll['pageUnmoved'])$this->failures[]=$key.' list scroll moved page';
                                $this->capture($b,$key.'-scrolled',false);$this->geometry($b,$key.'-scrolled');
                            }
                            $b->keys('[data-notification-toggle]','{escape}')->pause(100);
                            $this->assertTrue($b->script('return document.querySelector("[data-notification-menu]").hidden')[0],$key.' Escape dismisses');
                        }
                    }
                    $b->logout();
                });
            }
        }
        file_put_contents($this->out.'/checks.json',json_encode($this->checks,JSON_PRETTY_PRINT));
        file_put_contents($this->out.'/failures.json',json_encode($this->failures,JSON_PRETTY_PRINT));
        if(!$before)$this->assertSame([],$this->failures,implode("\n",$this->failures));
    }

    private function visit(Browser $b,string $url): void
    {
        for($i=0;$i<3;$i++){$b->visit($url)->pause(600);if(parse_url($b->driver->getCurrentURL(),PHP_URL_PATH)===$url)break;}
        $b->waitForLocation($url,10)->waitFor('[data-notification-toggle]',10);
    }

    private function capture(Browser $b,string $key,bool $before): void
    {
        $selector='.topbar-notification > i,.topbar-notification__count,.topbar-notification-menu__head strong,.topbar-notification-menu__head small,.topbar-notification-menu__head a,.topbar-notification-card__title,.topbar-notification-card__text,.topbar-notification-card__meta,.topbar-notification-card__meta > span,.topbar-notification-card__tag,.topbar-notification-card__pill,.topbar-notification-card__icon,.topbar-notification-empty,.topbar-notification-footer-btn';
        $js=file_get_contents(__DIR__.'/ui-contrast-measure.js');
        $data=$b->script('return ('.$js.')('.json_encode($selector).')')[0];
        $this->measurements[$key]=$data;
        file_put_contents($this->out.'/measurements.json',json_encode($this->measurements,JSON_PRETTY_PRINT));
        if(!$before)foreach($data['samples'] as $s)if(!$s['uncertain']&&!$s['disabled']&&$s['ratio']+.01<$s['threshold'])$this->failures[]=$key.' '.json_encode($s);
        $b->driver->takeScreenshot($this->out.'/'.$key.'.png');
    }

    private function geometry(Browser $b,string $key): void
    {
        $d=$b->script('const menu=document.querySelector("[data-notification-menu]"),list=document.querySelector("[data-notification-list]"),footer=menu.querySelector(".topbar-notification-menu__footer"),r=menu.getBoundingClientRect(),f=footer.getBoundingClientRect();return {left:r.left,right:r.right,top:r.top,bottom:r.bottom,width:innerWidth,height:innerHeight,overflow:document.documentElement.scrollWidth>innerWidth+1,listOverflow:list.scrollWidth>list.clientWidth+1,footerVisible:f.top>=r.top&&f.bottom<=Math.min(r.bottom,innerHeight)+1,topmost:[20,r.height/2,r.height-15].every(d=>menu.contains(document.elementFromPoint(r.left+20,Math.min(innerHeight-5,r.top+d))))}')[0];
        $this->checks[$key]=$d;
        file_put_contents($this->out.'/checks.json',json_encode($this->checks,JSON_PRETTY_PRINT));
        if($d['left']<0||$d['right']>$d['width']+1||$d['top']<0||$d['bottom']>$d['height']+1||$d['overflow']||$d['listOverflow']||!$d['footerVisible']||!$d['topmost'])$this->failures[]=$key.' geometry '.json_encode($d);
    }
}

<?php

namespace Tests\Browser;

use App\Models\{Branch,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class FilterSortStatesTest extends DuskTestCase
{
    use RefreshDatabase;

    public function createApplication()
    {
        $app=parent::createApplication();
        if(($_ENV['DB_CONNECTION']??null)!=='sqlite'||($_ENV['DB_DATABASE']??null)!==':memory:') throw new \RuntimeException('Isolated UI database required');
        $app['config']->set('database.default','sqlite');
        $app['config']->set('database.connections.sqlite.database',':memory:');
        return $app;
    }

    public function test_filter_and_sort_states_match_service_management(): void
    {
        $branch=Branch::create(['branch_code'=>'STATES','branch_name'=>'State fixture','address'=>'Test','type'=>'main','is_active'=>true]);
        $manifest=json_decode(file_get_contents(public_path('build/manifest.json')),true);
        $css=file_get_contents(public_path('build/'.$manifest['resources/css/app.css']['file']));
        foreach($manifest['resources/js/app.js']['css'] as $file) $css.=file_get_contents(public_path('build/'.$file));
        $css=str_replace('url(./','url(file:///'.str_replace('\\','/',public_path('build/assets')).'/',$css);
        $bundle=file_get_contents(public_path('build/'.$manifest['resources/js/app.js']['file']));
        $snapshots=[];
        $pages=[
            ['service','admin','system','admin.service-management.index','.catalog-control--filter > summary','.catalog-control--sort > summary'],
            ['staff-cases','staff',null,'funeral-cases.index','.case-record-filters .case-compact-more','.case-record-filters .case-compact-sort-select'],
            ['branch-cases','admin','branch','admin.cases.index','.case-record-filters .case-compact-more','.case-record-filters .case-compact-sort-select'],
            ['system-cases','admin','system','admin.cases.index','.case-record-filters .case-compact-more','.case-record-filters .case-compact-sort-select'],
            ['owner-cases','owner',null,'owner.history','.case-record-filters .case-compact-more','.case-record-filters .case-compact-sort-select'],
            ['staff-payments','staff',null,'payments.history','.pm-more-filters > summary','.pm-sort-control select'],
            ['branch-payments','admin','branch','admin.payments.index','.pm-more-filters > summary','.pm-sort-control select'],
            ['system-payments','admin','system','admin.payments.index','.pm-more-filters > summary','.pm-sort-control select'],
        ];

        foreach($pages as [$name,$role,$scope,$route,$filter,$sort]) {
            $actor=User::factory()->create(['role'=>$role,'admin_scope'=>$scope,'branch_id'=>$branch->id,'is_active'=>true]);
            if($actor->requiresMfa()){$actor->two_factor_secret=(new \PragmaRX\Google2FA\Google2FA)->generateSecretKey();$actor->two_factor_confirmed_at=now();$actor->save();}
            $this->withSession(['idle_last_activity'=>now()->timestamp,'idle_session_key'=>'states','account_security'=>\App\Support\AccountSecurity::fingerprint($actor),'mfa_user_id'=>$actor->requiresMfa()?$actor->id:null]);
            $html=$this->followingRedirects()->actingAs($actor)->get(route($route))->assertOk()->getContent();
            $html=preg_replace('~<link\b[^>]*href="[^"]*/build/[^"]*"[^>]*>~i','',$html);
            $html=preg_replace('~<script\b[^>]*src="[^"]*/build/[^"]*"[^>]*>\s*</script>~i','',$html);
            $html=str_replace(rtrim(config('app.url'),'/').'/vendor/','file:///'.str_replace('\\','/',public_path('vendor')).'/',$html);
            $html=str_replace('</head>','<style>'.$css.'</style><script>window.fetch=async()=>{throw new Error("Isolated")}</script><script type="module">'.str_ireplace('</script','<\/script',$bundle).'</script></head>',$html);
            $path=tempnam(storage_path('framework'),'states-');rename($path,$path.'.html');$path.='.html';file_put_contents($path,$html);
            try {
                $this->browse(function(Browser $browser)use($path,$name,$filter,$sort,&$snapshots){
                    $browser->driver->get('file:///'.str_replace('\\','/',$path));
                    $browser->driver->executeCustomCommand('/session/:sessionId/goog/cdp/execute','POST',['cmd'=>'Emulation.setDeviceMetricsOverride','params'=>['width'=>1600,'height'=>1000,'deviceScaleFactor'=>1,'mobile'=>false]]);
                    $browser->waitUntil('window.PanelSkeleton!==undefined');
                    // Compare the actual browser cascade. Forced pseudo states
                    // keep this CSS regression independent of OS pointer timing;
                    // existing navigation tests own request/open/close behavior.
                    $browser->waitUntil('document.querySelector('.json_encode($filter).')!==null');
                    $cdp=fn($cmd,$params=[])=>$browser->driver->executeCustomCommand('/session/:sessionId/goog/cdp/execute','POST',['cmd'=>$cmd,'params'=>$params?:new \stdClass]);
                    $cdp('DOM.enable');$cdp('CSS.enable');
                    $root=$cdp('DOM.getDocument',['depth'=>0])['root']['nodeId'];
                    $node=fn($selector)=>$cdp('DOM.querySelector',['nodeId'=>$root,'selector'=>$selector])['nodeId'];
                    $pseudo=fn($id,$states)=>$cdp('CSS.forcePseudoState',['nodeId'=>$id,'forcedPseudoClasses'=>$states]);
                    $browser->script('const e=document.querySelector('.json_encode($sort).');if(e.matches("select"))e.click();');
                    $selected=$name==='service'?'.catalog-control--sort .catalog-control-option[aria-pressed="true"]':'.filter-select-menu__item[aria-selected="true"]';
                    $unselected=$name==='service'?'.catalog-control--sort .catalog-control-option[aria-pressed="false"]':'.filter-select-menu__item[aria-selected="false"]';
                    $browser->waitUntil('document.querySelector('.json_encode($selected).')!==null');
                    $browser->script("window.controlStyle=selector=>{let e=document.querySelector(selector);if(e.matches('.case-compact-sort-select'))e=e.parentElement;const s=getComputedStyle(e),r=e.getBoundingClientRect();return {theme:document.documentElement.dataset.theme,token:s.getPropertyValue(\"--record-choice-surface\"),bodyToken:getComputedStyle(document.body).getPropertyValue(\"--record-choice-surface\"),element:e.className,inline:e.getAttribute(\"style\"),hover:e.matches(':hover'),open:e.parentElement.matches('[open],.is-open')||e.getAttribute('aria-expanded')==='true'||e.classList.contains('is-open'),outline:s.outlineStyle,outlineWidth:s.outlineWidth,bg:s.backgroundColor,border:s.borderTopColor,color:s.color,weight:s.fontWeight,shadow:s.boxShadow,transform:s.transform,rect:{x:r.x,y:r.y,w:r.width,h:r.height}}}");
                    $owners=['filter'=>$filter,'sort'=>$name!=='service'&&str_contains($sort,'case-compact-sort-select')?'.case-record-filters .case-compact-sort-filter':$sort];
                    foreach(['light','dark'] as $theme){
                        $browser->script('document.documentElement.dataset.theme='.json_encode($theme).';document.documentElement.dataset.filterInput="keyboard";');
                        foreach(['filter'=>$filter,'sort'=>$sort] as $kind=>$selector){
                            $id=$node($owners[$kind]);$target=$node($selector);
                            $browser->script('const e=document.querySelector('.json_encode($selector).');if(e.matches("summary"))e.parentElement.open=false;else{e.setAttribute("aria-expanded","false");e.parentElement.classList.remove("is-open");}');
                            $pseudo($id,[]);$pseudo($target,[]);$browser->pause(180);
                            $snapshots[$name][$theme][$kind]['default']=$browser->script('return window.controlStyle('.json_encode($selector).')')[0];
                            $pseudo($id,['hover']);$browser->pause(180);
                            $snapshots[$name][$theme][$kind]['hover']=$browser->script('return window.controlStyle('.json_encode($selector).')')[0];
                            $pseudo($id,[]);
                            $browser->script('const e=document.querySelector('.json_encode($selector).');if(e.matches("summary"))e.parentElement.open=true;else{e.setAttribute("aria-expanded","true");if(e.matches("select"))e.parentElement.classList.add("is-open");}');
                            $snapshots[$name][$theme][$kind]['open']=$browser->script('return window.controlStyle('.json_encode($selector).')')[0];
                            $pseudo($target,['focus-visible']);
                            $snapshots[$name][$theme][$kind]['focus']=$browser->script('return window.controlStyle('.json_encode($selector).')')[0];
                            $pseudo($target,[]);
                        }
                        $snapshots[$name][$theme]['options']['selected']=$browser->script('return window.controlStyle('.json_encode($selected).')')[0];
                        $pseudo($node($unselected),['hover']);$browser->pause(180);
                        $snapshots[$name][$theme]['options']['hover']=$browser->script('return window.controlStyle('.json_encode($unselected).')')[0];
                        $pseudo($node($unselected),[]);
                    }

                    file_put_contents(storage_path('logs/filter-sort-states.json'),json_encode($snapshots,JSON_PRETTY_PRINT));
                    fwrite(STDERR,$name." states captured\n");
                    $browser->driver->get('about:blank');
                });
            } finally {
                $this->browse(fn(Browser $browser)=>$browser->driver->get('about:blank'));
                for($i=0;$i<5&&is_file($path);$i++){if(@unlink($path))break;usleep(50000);}
            }
        }


        file_put_contents(storage_path('logs/filter-sort-states.json'),json_encode($snapshots,JSON_PRETTY_PRINT));
        $this->assertCount(8,$snapshots);
        foreach($snapshots as $name=>$themes) foreach($themes as $theme=>$states) {
            foreach(['filter','sort'] as $kind) {
                foreach(['default','hover','open'] as $state) {
                    $actual=$states[$kind][$state];
                    $reference=$snapshots['service'][$theme][$kind][$state];
                    foreach(['bg','border','color','shadow','transform'] as $property) $this->assertSame($reference[$property],$actual[$property],"$name $theme $kind $state $property");
                    $this->assertEquals($states[$kind]['default']['rect'],$actual['rect'],"$name $theme $kind $state must not move");
                }
                $this->assertTrue($states[$kind]['hover']['hover'],"$name $theme $kind actual hover");
                $this->assertTrue($states[$kind]['open']['open'],"$name $theme $kind actual open");
                $this->assertSame('solid',$states[$kind]['focus']['outline'],"$name $theme $kind keyboard focus");
                $this->assertSame('2px',$states[$kind]['focus']['outlineWidth']);
            }
            foreach(['selected','hover'] as $state) foreach(['bg','border','color','weight'] as $property) $this->assertSame($snapshots['service'][$theme]['options'][$state][$property],$states['options'][$state][$property],"$name $theme option $state $property");
        }
    }
}


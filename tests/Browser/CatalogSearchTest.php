<?php

namespace Tests\Browser;

use App\Models\{AddOnCatalog,Branch,CasketCatalog,FreebieCatalog,HearseCatalog,Package,Promotion,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class CatalogSearchTest extends DuskTestCase
{
    use RefreshDatabase;

    public function createApplication()
    {
        $app=parent::createApplication();
        if(($_ENV['DB_CONNECTION']??null)!=='sqlite'||($_ENV['DB_DATABASE']??null)!==':memory:')throw new \RuntimeException('Isolated database required');
        $app['config']->set('database.default','sqlite');$app['config']->set('database.connections.sqlite.database',':memory:');return $app;
    }

    public function test_catalog_search_matches_submits_and_survives_hub_replacement(): void
    {
        $branch=Branch::create(['branch_code'=>'SEARCH','branch_name'=>'Search fixture','is_active'=>true]);
        $catalogs=[
            'packages'=>[Package::class,['coffin_type'=>'Standard','price'=>10000,'short_description'=>'Needle description','is_active'=>true]],
            'caskets'=>[CasketCatalog::class,['type_or_material'=>'Wood','description'=>'Needle material','standard_price'=>1000,'is_active'=>true,'is_available'=>true]],
            'addons'=>[AddOnCatalog::class,['category'=>'General','unit'=>'item','description'=>'Needle extra','price'=>100,'is_active'=>true,'is_available'=>true]],
            'freebies'=>[FreebieCatalog::class,['default_unit'=>'set','is_active'=>true,'is_available'=>true]],
            'hearses'=>[HearseCatalog::class,['description'=>'Needle hearse','reference_value'=>1000,'is_active'=>true,'is_available'=>true]],
            'promotions'=>[Promotion::class,['discount_type'=>'PERCENT','discount_value'=>10,'is_enabled'=>true]],
        ];
        foreach($catalogs as $tab=>[$model,$fields]) {
            $model::create(['name'=>'AAA Catalog Match <Care> '.$tab]+$fields);
            $model::create(['name'=>'ZZZ Offpage Fixture '.$tab]+$fields);
            for($n=0;$n<22;$n++)$model::create(['name'=>sprintf('Catalog filler %02d %s',$n,$tab)]+$fields);
        }
        $manifest=json_decode(file_get_contents(public_path('build/manifest.json')),true);
        $css=file_get_contents(public_path('build/'.$manifest['resources/css/app.css']['file']));
        foreach($manifest['resources/js/app.js']['css'] as $file)$css.=file_get_contents(public_path('build/'.$file));
        $css=str_replace('url(./','url(file:///'.str_replace('\\','/',public_path('build/assets')).'/',$css);
        $bundle=file_get_contents(public_path('build/'.$manifest['resources/js/app.js']['file']));
        foreach(['system','branch'] as $scope) {
            $actor=User::factory()->create(['role'=>'admin','admin_scope'=>$scope,'branch_id'=>$branch->id,'is_active'=>true]);
            if($actor->requiresMfa()){$actor->two_factor_secret=(new \PragmaRX\Google2FA\Google2FA)->generateSecretKey();$actor->two_factor_confirmed_at=now();$actor->save();}
            $this->withSession(['idle_last_activity'=>now()->timestamp,'idle_session_key'=>'catalog-search','account_security'=>\App\Support\AccountSecurity::fingerprint($actor),'mfa_user_id'=>$actor->requiresMfa()?$actor->id:null]);
            foreach(array_keys($catalogs) as $tab) {
                $docs=[];
                foreach(['full'=>'','local'=>'Catalog Match','remote'=>'Offpage'] as $key=>$query)$docs[$key]=$this->followingRedirects()->actingAs($actor)->get(route('admin.service-management.index',['tab'=>$tab,'q'=>$query]))->assertOk()->getContent();
                $html=preg_replace('~<link\b[^>]*href="[^"]*/build/[^"]*"[^>]*>~i','',$docs['full']);
                $html=preg_replace('~<script\b[^>]*src="[^"]*/build/[^"]*"[^>]*>\s*</script>~i','',$html);
                $html=str_replace(rtrim(config('app.url'),'/').'/vendor/','file:///'.str_replace('\\','/',public_path('vendor')).'/',$html);
                $mock='window.fixtureDocs='.json_encode($docs,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT).';window.requests=[];window.history.replaceState=(_s,_t,url)=>window.committedUrl=url;window.fetch=async(url,options={})=>{const u=new URL(url),q=u.searchParams.get("q")||"",suggestion=options.headers?.Accept==="text/html",html=fixtureDocs[q.includes("Offpage")?"remote":q?"local":"full"];if(!u.pathname.endsWith("/service-management"))throw new Error("Unrelated isolated request");requests.push({url:u.href,suggestion,signal:options.signal});if(window.deferResults&&!suggestion)return new Promise(resolve=>{(window.pendingResults||=[]).push({resolve:()=>resolve({ok:true,text:async()=>html}),signal:options.signal})});return {ok:true,text:async()=>html}};';
                $html=str_replace('</head>','<style>'.$css.'</style><script>'.$mock.'</script><script type="module">'.str_ireplace('</script','<\/script',$bundle).'</script></head>',$html);
                $path=tempnam(storage_path('framework'),'csearch');rename($path,$path.'.html');$path.='.html';file_put_contents($path,$html);
                try {
                    $this->browse(function(Browser $browser)use($path,$tab,$scope) {
                        $browser->driver->get('file:///'.str_replace('\\','/',$path));
                        $browser->waitUntil('window.PanelSkeleton!==undefined&&document.querySelector(".catalog-control--filter")!==null');
                        $browser->script('window.typeCatalog=q=>{const e=document.querySelector("[data-service-search]");e.value=q;e.dispatchEvent(new Event("input",{bubbles:true}))};');
                        $local='AAA Catalog Match <Care> '.$tab;$remote='ZZZ Offpage Fixture '.$tab;
                        foreach(['light','dark'] as $theme)foreach([1440,1920,1100,390] as $width) {
                            $browser->driver->executeCustomCommand('/session/:sessionId/goog/cdp/execute','POST',['cmd'=>'Emulation.setDeviceMetricsOverride','params'=>['width'=>$width,'height'=>1000,'deviceScaleFactor'=>1,'mobile'=>$width<500]]);
                            $data=$browser->script('document.documentElement.dataset.theme='.json_encode($theme).';const field=document.querySelector(".service-record-search"),input=field.querySelector("input"),filter=document.querySelector(".catalog-control--filter"),sort=document.querySelector(".catalog-control--sort"),view=document.querySelector(".service-viewbar"),before=[filter,sort,view].map(e=>e.getBoundingClientRect().x),w=field.getBoundingClientRect().width,s=getComputedStyle(field),i=getComputedStyle(input);field.style.setProperty("width","100%","important");const after=[filter,sort,view].map(e=>e.getBoundingClientRect().x);field.style.removeProperty("width");return {w,before,after,radius:s.borderRadius,bg:s.backgroundColor,weight:i.fontWeight,font:i.fontSize,overflow:document.documentElement.scrollWidth>innerWidth+1}')[0];
                            $this->assertSame('14px',$data['radius']);$this->assertSame($theme==='dark'?'rgb(24, 45, 34)':'rgb(234, 242, 237)',$data['bg']);
                            $this->assertSame('400',$data['weight']);$this->assertSame('14px',$data['font']);$this->assertEquals($data['before'],$data['after'],'Search width must not move trailing controls');
                            $this->assertFalse($data['overflow'],"$scope $tab $theme $width document overflow");
                            if($width>=1201)$this->assertLessThanOrEqual(560,$data['w']);
                        }
                        $browser->driver->executeCustomCommand('/session/:sessionId/goog/cdp/execute','POST',['cmd'=>'Emulation.setDeviceMetricsOverride','params'=>['width'=>1440,'height'=>1000,'deviceScaleFactor'=>1,'mobile'=>false]]);
                        $browser->assertScript('document.querySelector("[data-service-search]").placeholder.startsWith("Search")',false);
                        $browser->script('window.originalHub=document.querySelector("[data-service-hub]");typeCatalog("Catalog Match");');
                        $browser->assertScript('document.querySelector("[data-service-search-matches]").hidden',false);
                        $browser->assertScript('document.querySelector(".service-search-match").dataset.catalogMatchValue', $local);
                        $browser->assertScript('document.querySelectorAll(".service-search-match mark").length>0',true);
                        $browser->assertScript('document.querySelector(".service-search-match").querySelector("care,script")===null',true);
                        $browser->assertScript('(()=>{const row=document.querySelector(".service-search-match"),popup=row.parentElement,field=document.querySelector(".service-record-search");return row.getBoundingClientRect().height<=44&&Math.abs(popup.getBoundingClientRect().width-field.getBoundingClientRect().width)<=2})()',true);
                        $browser->pause(450);
                        $browser->assertScript('requests.every(r=>r.suggestion)',true);
                        $browser->assertScript('originalHub===document.querySelector("[data-service-hub]")&&!originalHub.hasAttribute("aria-busy")',true);
                        $browser->assertScript('document.querySelectorAll(".panel-skeleton").length',0);
                        $browser->screenshot('catalog-search-'.$scope.'-'.$tab.'-matches');
                        if(in_array($tab,['packages','promotions'],true)) {
                            $browser->script('document.documentElement.dataset.theme="light"');
                            $browser->screenshot('catalog-search-'.$scope.'-'.$tab.'-light-desktop');
                            $browser->driver->executeCustomCommand('/session/:sessionId/goog/cdp/execute','POST',['cmd'=>'Emulation.setDeviceMetricsOverride','params'=>['width'=>390,'height'=>1000,'deviceScaleFactor'=>1,'mobile'=>true]]);
                            $browser->script('document.documentElement.dataset.theme="dark"');
                            $browser->assertScript('document.querySelector(".service-search-match").getBoundingClientRect().height<=44',true);
                            $browser->assertScript('document.querySelector(".service-match-detail > span").getBoundingClientRect().width>32',true);
                            $browser->screenshot('catalog-search-'.$scope.'-'.$tab.'-dark-narrow');
                            $browser->driver->executeCustomCommand('/session/:sessionId/goog/cdp/execute','POST',['cmd'=>'Emulation.setDeviceMetricsOverride','params'=>['width'=>1440,'height'=>1000,'deviceScaleFactor'=>1,'mobile'=>false]]);
                        }
                        $browser->script('document.querySelector("[data-service-search]").dispatchEvent(new KeyboardEvent("keydown",{key:"Escape",bubbles:true}));');
                        $browser->assertScript('document.querySelector("[data-service-search-matches]").hidden',true);
                        $browser->assertScript('document.querySelector("[data-service-search]").value','Catalog Match');
                        $browser->script('typeCatalog("  Catalog Match  ");document.querySelector("[data-service-search-submit]").click();');
                        $browser->waitUntil('window.committedUrl!==undefined');
                        $browser->assertScript('new URL(committedUrl).searchParams.get("q")','Catalog Match');
                        $browser->assertScript('new URL(committedUrl).searchParams.get("tab")',$tab);
                        $browser->assertScript('document.querySelector("[data-service-hub]")!==originalHub',true);
                        $browser->script('typeCatalog("Offpage")');
                        $browser->waitUntil('document.querySelector("[data-catalog-match-value]")?.dataset.catalogMatchValue==='.json_encode($remote));
                        $browser->assertScript('requests.filter(r=>r.suggestion).at(-1).url.includes("q=Offpage")',true);
                        $browser->script('document.querySelector("[data-service-search]").dispatchEvent(new KeyboardEvent("keydown",{key:"ArrowDown",bubbles:true}));');
                        $browser->assertScript('document.activeElement.matches(".service-search-match")',true);
                        $browser->script('document.activeElement.dispatchEvent(new KeyboardEvent("keydown",{key:"Enter",bubbles:true}));');
                        $browser->waitUntil('new URL(window.committedUrl).searchParams.get("q")==='.json_encode($remote));
                        $browser->assertScript('document.activeElement.matches("[data-service-search]")',true);
                        $browser->script('document.querySelector("[data-service-clear-search]").click()');
                        $browser->waitUntil('!new URL(window.committedUrl).searchParams.has("q")');
                        $browser->assertScript('document.querySelector("[data-service-search]").value','');
                        $browser->script('typeCatalog("  Catalog Match  ");document.querySelector("[data-service-search]").dispatchEvent(new KeyboardEvent("keydown",{key:"Enter",bubbles:true}));');
                        $browser->waitUntil('new URL(window.committedUrl).searchParams.get("q")==="Catalog Match"');
                        $browser->script('window.deferResults=true;window.pendingResults=[];typeCatalog("Catalog Match");document.querySelector("[data-service-search-submit]").click();typeCatalog("Offpage");document.querySelector("[data-service-search-submit]").click();');
                        $browser->assertScript('pendingResults.length',2);$browser->assertScript('pendingResults[0].signal.aborted',true);
                        $browser->pause(220);
                        $browser->assertScript('[...document.querySelectorAll(".panel-skeleton")].every(e=>e.closest(".service-hub-panel"))',true);
                        $browser->script('pendingResults[1].resolve()');
                        $browser->waitUntil('new URL(window.committedUrl).searchParams.get("q")==="Offpage"');
                        $browser->script('pendingResults[0].resolve()');$browser->pause(30);
                        $browser->assertScript('new URL(committedUrl).searchParams.get("q")','Offpage');
                        $browser->assertScript('document.querySelector("[data-service-search]").value','Offpage');
                        $browser->driver->get('about:blank');
                    });
                } finally {
                    $this->browse(fn(Browser $browser)=>$browser->driver->get('about:blank'));
                    for($attempt=0;$attempt<8&&is_file($path);$attempt++){if(@unlink($path))break;usleep(50000);}
                }
            }
        }
    }
}




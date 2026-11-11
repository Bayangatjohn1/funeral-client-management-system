<?php

namespace Tests\Browser;

use App\Models\{AddOnCatalog,Branch,CasketCatalog,FreebieCatalog,HearseCatalog,Package,Promotion,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class CatalogActionSkeletonTest extends DuskTestCase
{
    use RefreshDatabase;

    public function createApplication()
    {
        $app=parent::createApplication();
        if (($_ENV['DB_CONNECTION']??null)!=='sqlite'||($_ENV['DB_DATABASE']??null)!==':memory:') throw new \RuntimeException('Isolated database required');
        $app['config']->set('database.default','sqlite');
        $app['config']->set('database.connections.sqlite.database',':memory:');
        return $app;
    }

    public function test_catalog_action_states_and_promotion_skeletons(): void
    {
        $branch=Branch::create(['branch_code'=>'CAT-UI','branch_name'=>'Catalog fixture','is_active'=>true]);
        $promotion=Promotion::create(['name'=>'Promotion fixture','discount_type'=>'PERCENT','discount_value'=>10,'is_enabled'=>true]);
        Package::create(['name'=>'Package fixture','coffin_type'=>'Standard','price'=>10000,'is_active'=>true,'promotion_id'=>$promotion->id]);
        CasketCatalog::create(['name'=>'Casket fixture','type_or_material'=>'Wood','standard_price'=>1000,'is_active'=>true,'is_available'=>true]);
        FreebieCatalog::create(['name'=>'Freebie fixture','default_unit'=>'item','is_active'=>true,'is_available'=>true]);
        AddOnCatalog::create(['name'=>'Addon fixture','category'=>'General','unit'=>'item','price'=>100,'is_active'=>true,'is_available'=>true]);
        HearseCatalog::create(['name'=>'Hearse fixture','reference_value'=>1000,'is_active'=>true,'is_available'=>true]);
        $manifest=json_decode(file_get_contents(public_path('build/manifest.json')),true);
        $css=file_get_contents(public_path('build/'.$manifest['resources/css/app.css']['file']));
        foreach($manifest['resources/js/app.js']['css'] as $asset) $css.=file_get_contents(public_path('build/'.$asset));
        $css=str_replace('url(./','url(file:///'.str_replace('\\','/',public_path('build/assets')).'/',$css);
        $bundle=file_get_contents(public_path('build/'.$manifest['resources/js/app.js']['file']));
        foreach(['system','branch'] as $scope) {
            $actor=User::factory()->create(['role'=>'admin','admin_scope'=>$scope,'branch_id'=>$branch->id,'is_active'=>true]);
            if($actor->requiresMfa()) {$actor->two_factor_secret=(new \PragmaRX\Google2FA\Google2FA)->generateSecretKey();$actor->two_factor_confirmed_at=now();$actor->save();}
            $this->withSession(['idle_last_activity'=>now()->timestamp,'idle_session_key'=>'catalog-ui','account_security'=>\App\Support\AccountSecurity::fingerprint($actor),'mfa_user_id'=>$actor->requiresMfa()?$actor->id:null]);
            foreach([false,true] as $empty) {
                $html=$this->followingRedirects()->actingAs($actor)->get(route('admin.service-management.index',['tab'=>'promotions','q'=>$empty?'NoSuchPromotionFixture':'']))->assertOk()->getContent();
                $html=preg_replace('~<link\b[^>]*href="[^"]*/build/[^"]*"[^>]*>~i','',$html);
                $html=preg_replace('~<script\b[^>]*src="[^"]*/build/[^"]*"[^>]*>\s*</script>~i','',$html);
                $html=str_replace(rtrim(config('app.url'),'/').'/vendor/','file:///'.str_replace('\\','/',public_path('vendor')).'/',$html);
                $html=str_replace('</head>','<style>'.$css.'</style><script>localStorage.setItem("service-catalog-view-promotions","cards");window.fetch=async()=>{throw new Error("Isolated UI fixture")}</script><script type="module">'.str_ireplace('</script','<\/script',$bundle).'</script></head>',$html);
                $path=tempnam(storage_path('framework'),'cat');rename($path,$path.'.html');$path.='.html';file_put_contents($path,$html);
                try {
                    $this->browse(function(Browser $browser)use($path,$scope,$empty) {
                        $browser->driver->get('file:///'.str_replace('\\','/',$path));
                        $cdp=fn($cmd,$params=[])=>$browser->driver->executeCustomCommand('/session/:sessionId/goog/cdp/execute','POST',['cmd'=>$cmd,'params'=>$params?:new \stdClass]);
                        $cdp('Emulation.setDeviceMetricsOverride',['width'=>1440,'height'=>1000,'deviceScaleFactor'=>1,'mobile'=>false]);
                        $browser->waitUntil('window.PanelSkeleton!==undefined');
                        $browser->script('window.catalogPanel=document.querySelector("[data-service-panel=promotions]");window.pickCatalog=(tab,view)=>{document.querySelectorAll("[data-service-panel]").forEach(p=>{p.classList.toggle("is-active",p.dataset.servicePanel===tab);p.querySelectorAll("[data-catalog-view-panel]").forEach(v=>v.hidden=v.dataset.catalogViewPanel!==view)})};window.actionPaint=e=>{const s=getComputedStyle(e),i=getComputedStyle(e.querySelector("i")),r=e.getBoundingClientRect();return {ink:s.color,bg:s.backgroundColor,shadow:s.boxShadow,filter:i.filter,transform:s.transform,rect:[r.x,r.y,r.width,r.height],ring:s.outlineWidth,ringStyle:s.outlineStyle}};');
                        if(!$empty) {
                            $cdp('DOM.enable');$cdp('CSS.enable');
                            $root=$cdp('DOM.getDocument',['depth'=>0])['root']['nodeId'];
                            foreach(['light','dark'] as $theme) {
                                $browser->script('document.documentElement.dataset.theme='.json_encode($theme).';document.documentElement.dataset.filterInput="keyboard";');
                                foreach(['packages','caskets','addons','freebies','hearses','promotions'] as $tab) foreach(['cards','table'] as $view) {
                                    $browser->script('pickCatalog('.json_encode($tab).','.json_encode($view).');');
                                    $buttons=$browser->script('const p=document.querySelector(".service-hub-panel.is-active"),group=[...p.querySelectorAll(".service-catalog-actions")].find(e=>e.getClientRects().length);return [...group.querySelectorAll("button.row-hover-action")].map((e,i)=>{e.dataset.fixtureAction=i;return {id:i,warning:e.matches(".is-edit-action,.is-archive-action,[aria-label^=\"Mark unavailable\"]"),label:e.getAttribute("aria-label"),tooltip:e.dataset.actionTooltip}})')[0];
                                    $this->assertNotEmpty($buttons,"$scope $tab $view");
                                    foreach($buttons as $button) {
                                        $selector='.service-hub-panel.is-active [data-catalog-view-panel="'.$view.'"] [data-fixture-action="'.$button['id'].'"]';
                                        $normal=$browser->script('return actionPaint(document.querySelector('.json_encode($selector).'))')[0];
                                        $this->assertGreaterThan(0,$normal['rect'][2],"$scope $tab $view action must be visible");
                                        $this->assertSame('rgba(0, 0, 0, 0)',$normal['bg']);
                                        $this->assertNotEmpty($button['label']);$this->assertNotEmpty($button['tooltip']);
                                        $id=$cdp('DOM.querySelector',['nodeId'=>$root,'selector'=>$selector])['nodeId'];
                                        $cdp('CSS.forcePseudoState',['nodeId'=>$id,'forcedPseudoClasses'=>['hover']]);
                                        $hover=$browser->script('return actionPaint(document.querySelector('.json_encode($selector).'))')[0];
                                        $expected=$button['warning']?($theme==='dark'?'rgb(253, 199, 106)':'rgb(161, 92, 0)'):($theme==='dark'?'rgb(92, 224, 160)':'rgb(8, 116, 67)');
                                        $this->assertSame($expected,$hover['ink'],"$scope $tab $view $theme action hover");
                                        foreach(['bg','shadow','filter','transform','rect'] as $property) $this->assertSame($normal[$property],$hover[$property],"$scope $tab $view $property stays stable");
                                        $cdp('CSS.forcePseudoState',['nodeId'=>$id,'forcedPseudoClasses'=>['focus-visible']]);
                                        $this->assertSame('2px',$browser->script('return actionPaint(document.querySelector('.json_encode($selector).')).ring')[0]);
                                        $cdp('CSS.forcePseudoState',['nodeId'=>$id,'forcedPseudoClasses'=>[]]);
                                    }
                                }
                            }
                        }
                        foreach(['light','dark'] as $theme) {
                            $browser->script('document.documentElement.dataset.theme='.json_encode($theme).';pickCatalog("promotions","cards");window.endPromotion=PanelSkeleton.begin(catalogPanel,{immediate:true});');
                            $browser->waitUntil('catalogPanel.querySelector(".panel-skeleton.is-measured")!==null');
                            $this->assertSame(1,$browser->script('return catalogPanel.querySelectorAll("[data-skeleton-component=mask]").length')[0]);
                            $this->assertSame('rgba(0, 0, 0, 0)',$browser->script('return getComputedStyle(catalogPanel.querySelector(".panel-skeleton")).backgroundColor')[0]);
                            $this->assertTrue($browser->script('const card=catalogPanel.querySelector("[data-skeleton-item],[data-skeleton-empty]"),mask=catalogPanel.querySelector("[data-skeleton-component=mask]");return Math.abs(card.getBoundingClientRect().height-mask.getBoundingClientRect().height)<1')[0]);
                            $this->assertGreaterThan($empty?1:8,$browser->script('return catalogPanel.querySelectorAll("[data-skeleton-shape]").length')[0]);
                            $browser->screenshot('promotion-skeleton-'.$scope.'-'.$theme.($empty?'-empty':'-cards'));
                            $browser->script('endPromotion();pickCatalog("promotions","table");window.endPromotion=PanelSkeleton.begin(catalogPanel,{immediate:true});');
                            $browser->waitUntil('catalogPanel.querySelector(".panel-skeleton.is-measured")!==null');
                            $this->assertTrue($browser->script('const mask=catalogPanel.querySelector("[data-skeleton-component=mask]");return mask.getBoundingClientRect().top>=catalogPanel.querySelector("thead").getBoundingClientRect().bottom-1')[0]);
                            $browser->script('endPromotion()');
                            $this->assertSame(0,$browser->script('return document.querySelectorAll(".panel-skeleton").length')[0]);
                        }
                        if(!$empty) {
                            $cdp('Emulation.setDeviceMetricsOverride',['width'=>390,'height'=>844,'deviceScaleFactor'=>1,'mobile'=>true]);
                            $browser->script('pickCatalog("promotions","cards");window.endPromotion=PanelSkeleton.begin(catalogPanel,{immediate:true})');
                            $browser->waitUntil('catalogPanel.querySelector(".panel-skeleton.is-measured")!==null');
                            $browser->screenshot('promotion-skeleton-'.$scope.'-dark-narrow');
                            $browser->script('endPromotion();window.endNavigation=PanelSkeleton.begin(document.querySelector(".page-content"),{variant:"navigation",destination:"https://fixture.test/admin/service-management?tab=promotions",immediate:true})');
                            $this->assertSame(24,$browser->script('return document.querySelectorAll(".panel-skeleton .is-item .is-details .is-copy").length')[0]);
                            $browser->script('endNavigation();localStorage.setItem("service-catalog-view-promotions","table");window.endNavigation=PanelSkeleton.begin(document.querySelector(".page-content"),{variant:"navigation",destination:"https://fixture.test/admin/service-management?tab=promotions",immediate:true})');
                            $this->assertSame(7,$browser->script('return document.querySelectorAll(".panel-skeleton .is-heading-row .is-cell").length')[0]);
                            $browser->script('endNavigation()');
                        }
                        $browser->driver->get('about:blank');
                    });
                } finally {
                    $this->browse(fn(Browser $browser)=>$browser->driver->get('about:blank'));
                    if(is_file($path)) unlink($path);
                }
            }
        }
    }
}

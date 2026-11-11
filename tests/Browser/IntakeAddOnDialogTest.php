<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class IntakeAddOnDialogTest extends DuskTestCase
{
    public function test_custom_asset_popovers_search_select_and_close_on_form_scroll(): void
    {
        $source = file_get_contents(resource_path('views/staff/intake/_form.blade.php'));
        $slice = static function ($start, $end) use ($source) {
            $offset = strpos($source, $start);
            return substr($source, $offset, strpos($source, $end, $offset) - $offset);
        };
        $manifest = json_decode(file_get_contents(public_path('build/manifest.json')), true);
        $styles = '<style>'.file_get_contents(public_path('build/'.$manifest['resources/css/app.css']['file'])).'</style>';
        $styles .= $slice('<style>', '</style>').'</style>'.file_get_contents(resource_path('views/staff/intake/_interaction-styles.blade.php'));
        $markup = '';
        foreach (['casket' => 'custom_casket_catalog_id', 'hearse' => 'hearse_catalog_id'] as $type => $id) {
            $markup .= '<button type="button" class="custom-asset-trigger" data-custom-asset-trigger="'.$type.'" aria-controls="'.$type.'-test" aria-expanded="false">Choose '.$type.'</button><div id="'.$type.'-test" class="inline-upgrade-options" popover></div><select hidden id="'.$id.'"><option value="">Choose</option>';
            for ($i = 1; $i <= 30; $i++) $markup .= '<option value="'.$i.'">'.$type.' '.str_pad($i, 2, '0', STR_PAD_LEFT).'</option>';
            $markup .= '</select>';
        }
        $script = $slice('    const positionIntakePicker =', '    const initScheduleTimePicker =');
        $script .= $slice("    document.querySelectorAll('[data-upgrade-trigger], [data-custom-asset-trigger]')", "    addAdditionalItem?.addEventListener('click'");
        $html = '<!doctype html><html data-theme="light"><head>'.$styles.'</head><body class="panel-shell-body"><form class="intake-root" style="min-height:100vh;padding:40px"><div id="scroll-owner">'.$markup.'</div></form><script>const replacementCasket=null,hearseCatalogSelect=document.querySelector("#hearse_catalog_id");const syncPreferredPackage=()=>{},render=()=>{};'.$script.'</script></body></html>';
        $this->browse(function (Browser $browser) use ($html) {
            $browser->driver->get('data:text/html;charset=utf-8,'.rawurlencode($html));
            foreach (['light', 'dark'] as $theme) {
                $browser->script('document.documentElement.dataset.theme="'.$theme.'";');
                foreach (['casket' => 'custom_casket_catalog_id', 'hearse' => 'hearse_catalog_id'] as $type => $id) {
                    $browser->click('[data-custom-asset-trigger="'.$type.'"]')
                        ->assertScript('document.querySelector("#'.$type.'-test").matches(":popover-open")', true)
                        ->type('[aria-label="Search '.$type.'s"]', $type.' 09')
                        ->assertScript('document.querySelectorAll("#'.$type.'-test .intake-asset-options button:not([hidden])").length', 1)
                        ->click('#'.$type.'-test .intake-asset-options button:not([hidden])')
                        ->assertScript('document.querySelector("#'.$id.'").value', '9')
                        ->assertScript('document.querySelector("#'.$type.'-test").matches(":popover-open")', false)
                        ->click('[data-custom-asset-trigger="'.$type.'"]');
                    $browser->script('document.querySelector("#scroll-owner").dispatchEvent(new Event("scroll"));');
                    $browser->assertScript('document.querySelector("#'.$type.'-test").matches(":popover-open")', false);
                }
            }
        });
    }

    public function test_large_add_on_catalog_supports_card_selection_quantity_and_local_search(): void
    {
        $source = file_get_contents(resource_path('views/staff/intake/_form.blade.php'));
        $slice = static function ($start, $end) use ($source) {
            $offset = strpos($source, $start);
            return substr($source, $offset, strpos($source, $end, $offset) - $offset);
        };
        $dialog = $slice('<div id="optional_add_ons_panel"', "                        @error('selected_add_ons')");
        $manifest = json_decode(file_get_contents(public_path('build/manifest.json')), true);
        $css = '<style>'.file_get_contents(public_path('build/'.$manifest['resources/css/app.css']['file'])).'</style>';
        $css .= $slice('<style>', '</style>').'</style>';
        $css .= file_get_contents(resource_path('views/staff/intake/_interaction-styles.blade.php'));
        $rows = $slice('optionalAddOnsList.innerHTML = addOns.map', ").join('');").").join('');";
        $handlers = $slice("    optionalAddOnsList?.addEventListener('change'", "    selectedAddOnsLines?.addEventListener('input'");
        $search = $slice('    // This dialog already holds the complete authorized catalog:', "    optionalAddOnsPanel?.addEventListener('keydown'");
        $checks = $slice('    const checkedAddOns = () =>', '    const appliedAddOns = () =>');
        $totals = $slice('    const syncDraftAddOnsTotal = () =>', '    const restoreAppliedAddOns = () =>');
        $html = '<!doctype html><html data-theme="light"><head>'.$css.'</head><body class="panel-shell-body"><form class="intake-root" style="min-height:100vh">'.$dialog
            .'</form><script>const optionalAddOnsPanel=document.querySelector("#optional_add_ons_panel"), optionalAddOnsList=document.querySelector("#optional_add_ons_list"), optionalAddOnsSearch=document.querySelector("#optional_add_ons_search"), draftAddOnsTotal=document.querySelector("#draft_add_ons_total");'
            .'const oldSelectedAddOns=new Set(), addOnQuantities=new Map(), draftSelectedAddOns=new Set(), draftAddOnQuantities=new Map();'
            .'const num=v=>Number(v)||0, fmt=v=>num(v).toLocaleString("en-US",{minimumFractionDigits:2,maximumFractionDigits:2}), escapeHtml=v=>String(v).replace(/[&<>"\x27]/g,c=>({"&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;","\x27":"&#39;"}[c]));'
            .'const addOns=Array.from({length:30},(_,i)=>({id:String(i+1),name:"Service "+String(i+1).padStart(2,"0"),price:250,unit:"day",category:"Rental",description:"Catalog service"}));'
            .$rows.$checks.$totals.$handlers.$search
            .'optionalAddOnsPanel.classList.remove("hidden");optionalAddOnsPanel.showPopover();optionalAddOnsPanel.style.setProperty("top","16px","important");optionalAddOnsPanel.style.setProperty("left","8px","important");</script></body></html>';

        $this->browse(function (Browser $browser) use ($html) {
            $browser->driver->get('data:text/html;charset=utf-8,'.rawurlencode($html));
            foreach (['light', 'dark'] as $theme) {
                foreach ([1440, 390] as $width) {
                    $browser->resize($width, 900);
                    $browser->script('document.documentElement.dataset.theme="'.$theme.'";');
                    $browser->assertScript('document.querySelector("#optional_add_ons_list").scrollHeight > document.querySelector("#optional_add_ons_list").clientHeight', true)
                        ->type('#optional_add_ons_search', 'Service 01')
                        ->waitUntil('!document.querySelector("#addon_search_matches").hidden && document.querySelector("#addon_search_matches").getBoundingClientRect().height > 0')
                        ->click('#addon_search_submit')
                        ->assertScript('document.querySelectorAll(".optional-add-on-row:not(.hidden)").length', 1)
                        ->click('.optional-add-on-row:not(.hidden) .font-bold')
                        ->assertScript('document.querySelector("#draft_add_ons_header_count").textContent', '1 selected')
                        ->assertScript('document.querySelector("[data-add-on-quantity-note]:not(.hidden)").getBoundingClientRect().height > 0', true)
                        ->click('[data-optional-quantity-step="1"]')
                        ->assertInputValue('#draft_add_on_1', '2')
                        ->assertScript('document.querySelector("#draft_add_ons_total").textContent', '500.00')
                        ->click('#addon_search_clear')
                        ->assertScript('document.querySelectorAll(".optional-add-on-row:not(.hidden)").length', 30)
                        ->click('.optional-add-on-row:nth-child(2) .font-bold')
                        ->assertScript('document.querySelector("#draft_add_ons_header_count").textContent', '2 selected')
                        ->click('[data-optional-add-on-toggle][aria-pressed="true"]')
                        ->assertScript('document.querySelector("#draft_add_ons_header_count").textContent', '1 selected')
                        ->click('[data-optional-add-on-toggle][aria-pressed="true"]')
                        ->assertScript('document.querySelector("#draft_add_ons_header_count").textContent', '0 selected');
                    $browser->screenshot('addon-popover-'.$theme.'-'.$width);
                }
            }
        });
    }

    public function test_add_on_popover_keeps_form_scroll_and_is_not_modal(): void
    {
        $source = file_get_contents(resource_path('views/staff/intake/_form.blade.php'));
        $start = strpos($source, '    const setAddOnsPanelOpen =');
        $end = strpos($source, '    const syncDraftAddOnsTotal =', $start);
        $lifecycle = substr($source, $start, $end - $start);
        $start = strpos($source, '    const positionIntakePicker =');
        $end = strpos($source, '    const initScheduleTimePicker =', $start);
        $lifecycle = substr($source, $start, $end - $start).$lifecycle;
        $styles = file_get_contents(resource_path('views/staff/intake/_interaction-styles.blade.php'));
        $html = '<!doctype html><html data-theme="light"><head>'.$styles
            .'<style>.main-area{height:500px;overflow:auto}.hidden{display:none}#optional_add_ons_panel{background:white!important}</style>'
            .'</head><body class="panel-shell-body"><div class="main-area"><form class="intake-root">'
            .'<div style="height:900px"></div><button id="open" type="button" popovertarget="optional_add_ons_panel">Choose Add-ons</button>'
            .'<div id="optional_add_ons_panel" popover class="hidden"><input id="search"><button id="close" type="button">Cancel</button></div>'
            .'<div style="height:900px"></div></form></div><script>'
            .'const optionalAddOnsPanel=document.querySelector("#optional_add_ons_panel"),chooseAddOnsBtn=document.querySelector("#open"),optionalAddOnsSearch=document.querySelector("#search"),syncServiceBackdrop=()=>{};'.$lifecycle
            .'document.querySelector("#open").onclick=e=>{e.preventDefault();setAddOnsPanelOpen(true)};'
            .'document.querySelector("#close").onclick=()=>setAddOnsPanelOpen(false);'
            .'</script></body></html>';

        $this->browse(function (Browser $browser) use ($html) {
            $browser->driver->get('data:text/html;charset=utf-8,'.rawurlencode($html));
            foreach (['light', 'dark'] as $theme) {
                $browser->script('document.documentElement.dataset.theme="'.$theme.'";document.querySelector(".main-area").scrollTop=700;');
                $browser->click('#open')->pause(250)
                    ->assertScript('document.querySelector("#optional_add_ons_panel").matches(":popover-open")', true)
                    ->assertScript('document.querySelectorAll("dialog[open]").length', 0)
                    ->assertScript('document.querySelector(".main-area").scrollTop', 700)
                    ->assertScript('document.activeElement.id', 'search')
                    ->click('#close')->waitUntil('!document.querySelector("#optional_add_ons_panel").matches(":popover-open")', 3)
                    ->assertScript('document.querySelector(".main-area").scrollTop', 700)
                    ->assertScript('document.activeElement.id', 'open');
            }
        });
    }
}

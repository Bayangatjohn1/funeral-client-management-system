<?php

namespace Tests\Browser;

use App\Models\FuneralCase;
use App\Models\Package;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;
use PragmaRX\Google2FA\Google2FA;
use Tests\DuskTestCase;

/** Presentation regression checks. Run only through the isolated Dusk runner. */
class UiReadabilityCorrectionTest extends DuskTestCase
{
    private array $results = [];
    private array $failures = [];

    public function test_approved_readability_corrections_across_roles(): void
    {
        $database = (string) config('database.connections.sqlite.database');
        $this->assertSame('sqlite', config('database.default'));
        $this->assertStringContainsString('ui-readability', $database, 'Use a disposable UI database.');
        $case = FuneralCase::firstOrFail();
        $case->forceFill(['verification_status' => 'VERIFIED', 'created_at' => now(), 'service_requested_at' => today(),
            'wake_start_date' => today()->subDays(3), 'wake_start_time' => '08:00',
            'wake_end_date' => today()->subDay(), 'wake_end_time' => '08:00',
            'interment_at' => now()->subDay(), 'interment_time' => '10:00'])->save();
        if (getenv('UI_QA_EXTRA_CASE')) {
            $deceased=$case->deceased->replicate();$deceased->save();
            $case->replicate()->forceFill(['deceased_id'=>$deceased->id,'case_code'=>'QA-UNPAID','payment_status'=>'UNPAID','total_paid'=>0,'balance_amount'=>$case->total_amount])->save();
        }
        $phase = getenv('UI_CORRECTION_PHASE') ?: 'phase1';
        $package = $phase === 'phase2' ? Package::create(['name'=>'Readability QA Package','price'=>150000,'is_active'=>true]) : null;
        $out = storage_path('app/ui-corrections-20261026/'.(getenv('UI_QA_OUTPUT') ?: $phase));
        if (!is_dir($out)) mkdir($out, 0777, true);
        foreach (['staff', 'branch_admin', 'system_admin', 'owner'] as $role) {
            if (getenv('UI_QA_ROLES') && !in_array($role,explode(',',getenv('UI_QA_ROLES')))) continue;
            $secret = (new Google2FA)->generateSecretKey();
            $user = User::factory()->create(['email' => 'readability-'.$role.'@funeral.test',
                'password' => 'ReadabilityFixture123!', 'role' => str_contains($role, 'admin') ? 'admin' : $role,
                'admin_scope' => $role === 'system_admin' ? 'system' : ($role === 'branch_admin' ? 'branch' : null),
                'branch_id' => in_array($role, ['staff','branch_admin']) ? $case->branch_id : null,
                'is_active' => true, 'must_change_password' => false]);
            $user->two_factor_secret = $secret;
            $user->two_factor_confirmed_at = now();
            $user->save();
            if ($role === 'staff') { $case->encoded_by=$user->id; $case->save(); }
            if ($user->branch_id) DB::table('user_branches')->updateOrInsert(['user_id'=>$user->id,'branch_id'=>$user->branch_id], ['created_at'=>now(),'updated_at'=>now()]);
            if (in_array($role,['branch_admin','system_admin'])) for($i=0;$i<30;$i++) \App\Models\AuditLog::create(['actor_id'=>$user->id,'actor_role'=>'admin','action'=>'USER_LOGIN','entity_type'=>'User','entity_id'=>$user->id,'branch_id'=>$case->branch_id,'status'=>'SUCCESS']);
            $this->browse(function (Browser $browser) use ($user, $secret, $role, $case, $out, $phase, $package) {
                // Dusk authentication plus a verified fixture session avoids testing MFA in this UI suite.
                // These writes are guarded above and only touch the disposable QA database.
                $browser->loginAs($user);
                foreach (DB::table('sessions')->where('user_id', $user->id)->get() as $session) {
                    $payload = unserialize(base64_decode($session->payload), ['allowed_classes'=>false]);
                    $payload['mfa_user_id'] = (int) $user->id;
                    $payload['account_security'] = \App\Support\AccountSecurity::fingerprint($user->fresh());
                    $payload['idle_last_activity'] = now()->timestamp;
                    DB::table('sessions')->where('id',$session->id)->update(['payload'=>base64_encode(serialize($payload))]);
                }
                $home = $role === 'owner' ? '/owner' : ($role === 'staff' ? '/staff' : '/admin');
                $browser->visit($home)->waitForLocation($home, 15);
                $pages = [
                    ['profile','/profile','.prof-name,.prof-section-title,.prof-value,.prof-label,.prof-security-copy'],
                    ['records',$role === 'owner' ? '/owner/case-history' : ($role === 'staff' ? '/funeral-cases' : '/admin/cases'),'.table-primary,.table-secondary,.table-paginator-meta,.table-paginator-btn.is-active,.records-view-case .bi-eye,.payment-status,.status-badge'],
                ];
                if ($role !== 'owner') $pages[] = ['payments',$role === 'staff' ? '/payments/history' : '/admin/payments','.pm-page-size,.pm-status,.table-paginator-meta,.table-paginator-btn.is-active'];
                if ($role === 'staff') {
                    $pages[] = ['edit','/funeral-cases/'.$case->id.'/edit','.ec-label,.ec-fin-label,.ec-fin-value,.ec-hint'];
                    $pages[] = ['deceased','/deceased','.page-desc,.table-secondary,.text-muted'];
                    $pages[] = ['dashboard','/staff','.staff-dashboard-description,.text-muted,.staff-empty-state'];
                }
                if ($role === 'owner') {
                    $pages[] = ['details','/owner/cases/'.$case->id,'.cv-card-title,.cv-field-label,.cv-field-value'];
                    $pages[] = ['dashboard','/owner','.oe-header p,.oe-kpi-value,.oe-kpi-label'];
                }
                if ($role === 'system_admin') {
                    $pages[] = ['backup','/admin/backup-recovery','thead th,td[colspan],.table-paginator-meta,.table-paginator-btn.is-active'];
                    $pages[] = ['retention','/admin/record-retention','thead th,td[colspan],.table-paginator-meta,.table-paginator-btn.is-active'];
                }
                if ($phase === 'phase2' && in_array($role,['branch_admin','system_admin'])) {
                    $pages[] = ['package','/admin/packages/'.$package->id.'?modal=1','.svc-detail-kicker,.svc-detail-stat span'];
                    if ($role === 'system_admin') $pages[] = ['package-create','/admin/packages/create?modal=1&service_form=1','.package-wizard-nav [aria-current=step],.package-wizard-nav [aria-current=step] span'];
                }
                if ($role !== 'staff') {
                    $pages[] = ['audit','/admin/audit-logs','thead th,.table-paginator-meta,.table-paginator-btn.is-active'];
                    if ($role === 'owner') array_pop($pages);
                    foreach (['owner_branch_analytics','sales','master_cases'] as $report) $pages[] = ['report-'.$report,'/reports?report_type='.$report,'.reports-table th,.reports-table td span,.reports-metric-label,.reports-metric-value,.reports-metric-help'];
                    foreach (['performance','payment','collection','trend'] as $tab) $pages[] = ['analytics-'.$tab,($role === 'owner' ? '/owner/branch-analytics' : '/reports/analytics').'?range=THIS_MONTH&analytics_tab=ba-panel-'.$tab,'.ba-title,.ba-subtitle,.ba-panel-note,.ba-panel.active small,.ba-payment-chart-hint'];
                }
                foreach ($pages as [$name,$url,$selector]) {
                    if (getenv('UI_QA_PAGES') && !in_array($name,explode(',',getenv('UI_QA_PAGES')))) continue;
                    $expectedPath = parse_url($url, PHP_URL_PATH);
                    for ($attempt=0; $attempt<3; $attempt++) {
                        $browser->visit($url)->pause(600);
                        if (parse_url($browser->driver->getCurrentURL(), PHP_URL_PATH) === $expectedPath) break;
                    }
                    file_put_contents($out.'/navigation.json', json_encode(['requested'=>$url,'actual'=>$browser->driver->getCurrentURL(),'document'=>$browser->script('return {url:location.href,navigation:performance.getEntriesByType("navigation")[0]?.name,errors:[...document.querySelectorAll(".form-error,.text-red-600,[role=alert]")].map(e=>e.textContent)}')[0]], JSON_PRETTY_PRINT));
                    $browser->waitForLocation($expectedPath, 10)->waitFor('.page-content', 10);
                    if (str_starts_with($name, 'report-')) $browser->waitFor('.reports-page', 10)->pause(800);
                    foreach (['light','dark'] as $theme) foreach ([1440,390] as $width) {
                        if ($name === 'profile' || str_starts_with($name,'analytics-')) $this->navigate($browser,$url);
                        $this->viewport($browser,$theme,$width);
                        if (str_starts_with($name,'analytics-')) $browser->waitFor('[data-reports-module-shell][data-module-ready="true"]',15)->pause(300);
                        $key = $role.'-'.$name.'-'.$theme.'-'.$width;
                        $this->capture($browser,$selector,$key,$out);
                        if ($name === 'records') $this->capture($browser,'.case-rows-per-page label',$key.'-page-size',$out);
                        if ($name === 'profile') {
                            $browser->script('document.querySelector("#openEditModal2").click()');
                            $browser->waitFor('#editProfileOverlay.is-open', 5)->pause(500);
                            $browser->waitUntil('getComputedStyle(document.querySelector("#editProfileSheet")).opacity === "1"',5);
                            $this->capture($browser,'.prof-overlay.is-open .prof-modal-title,.prof-overlay.is-open .prof-modal-desc,.prof-overlay.is-open label',$key.'-edit',$out);
                            $browser->script('document.querySelector("#editProfileOverlay input[name=name]").focus()');
                            $this->capture($browser,'#editProfileOverlay label,#editProfileOverlay .btn,#editProfileOverlay .prof-modal-close i',$key.'-focus',$out);
                            $browser->script('document.querySelector("#cancelEditModal").click()');
                            $browser->waitUntil('!document.querySelector("#editProfileOverlay").classList.contains("is-open")')->pause(250);
                        }
                        if (str_starts_with($name,'report-')) {
                            // Every actual summary card, including Collected; hover must retain its color pair.
                            foreach ($browser->elements('.reports-metric') as $index => $card) {
                                $browser->driver->action()->moveToElement($card)->perform();$browser->pause(150);
                                $this->capture($browser,'.reports-metric:hover .reports-metric-label,.reports-metric:hover .reports-metric-value,.reports-metric:hover .reports-metric-help,.reports-metric:hover .reports-metric-icon i',$key.'-card-hover-'.$index,$out);
                            }
                            $this->capture($browser,'.reports-metric-label,.reports-metric-value,.reports-metric-help',$key.'-card-hover',$out);
                            $browser->script('document.querySelectorAll(".reports-metric").forEach(e=>e.classList.add("is-selected"))');
                            $this->capture($browser,'.reports-metric-label,.reports-metric-value,.reports-metric-help',$key.'-card-selected-fixture',$out);
                            $browser->script('document.querySelectorAll(".reports-metric").forEach(e=>e.classList.remove("is-selected"))');
                        }
                        if ($name === 'analytics-payment' && $browser->element('.ba-payment-status-card.tone-amber')) {
                            $browser->script('document.querySelector(".ba-panel.active .ba-payment-status-card.tone-amber")?.click()');
                            $browser->waitFor('#baBranchSummary[open]')->pause(200);
                            $this->capture($browser,'#baBranchSummaryTitle,#baBranchSummary [data-branch-summary-scope],#baBranchSummary [data-branch-summary-metric]',$key.'-summary',$out);
                            $browser->script('document.querySelector("[data-close-branch-summary]").click()');
                            $browser->pause(250);
                        }
                        if ($name === 'dashboard' && $browser->element('.oe-trend-data summary')) {
                            $browser->script('document.querySelector(".oe-trend-data").open=true');
                            $this->capture($browser,'.oe-trend-data[open] th,.oe-trend-data[open] td',$key.'-exact',$out);
                            $browser->script('document.querySelector(".oe-trend-data").open=false');
                        }
                        if ($name === 'details' && $width === 390) {
                            $box=$browser->script('const a=document.querySelector(".owner-case-back").getBoundingClientRect(),b=document.querySelector(".panel-page-header__copy").getBoundingClientRect();return {right:a.right,left:b.left,overlap:a.right>b.left}')[0];
                            file_put_contents($out.'/'.$key.'-geometry.json',json_encode($box,JSON_PRETTY_PRINT));
                            if ($box['overlap']) $this->failures[]=$key.' back-button overlap';
                        }
                    }
                }
                if ($phase === 'phase2' && $role !== 'owner') {
                    foreach (['light','dark'] as $theme) foreach ([1440,390] as $width) {
                        $this->navigate($browser,$home);$this->viewport($browser,$theme,$width);
                        $browser->click('[data-notification-toggle]')->pause(120);
                        $this->capture($browser,'.topbar-notification-card__tag,.topbar-notification-card__pill,.topbar-notification-card__icon',$role.'-notifications-'.$theme.'-'.$width,$out);
                        $box = $browser->script('const e=document.querySelector("[data-notification-menu]"),r=e.getBoundingClientRect();const points=[20,r.height/2,r.height-20].map(v=>({x:r.left+20,y:Math.min(innerHeight-10,r.top+v)}));let p=e,ancestors=[];while(p){let s=getComputedStyle(p);ancestors.push({tag:p.tagName,class:p.className,z:s.zIndex,position:s.position,transform:s.transform,isolation:s.isolation});p=p.parentElement;}return {left:r.left,right:r.right,width:innerWidth,topmost:points.every(v=>e.contains(document.elementFromPoint(v.x,v.y))),points,ancestors}')[0];
                        if ($box['left'] < -1 || $box['right'] > $box['width']+1 || !$box['topmost']) $this->failures[] = 'Notification geometry/stacking '.json_encode($box);
                        file_put_contents($out.'/'.$role.'-notifications-'.$theme.'-'.$width.'-geometry.json',json_encode($box,JSON_PRETTY_PRINT));
                        $this->navigate($browser,($role === 'staff' ? '/reminders' : '/admin/reminders').'?branch_id='.$case->branch_id.'&tab=warnings');
                        $this->viewport($browser,$theme,$width);
                        $this->capture($browser,'.rp-filter-reset,.rp-warning-text',$role.'-reminders-'.$theme.'-'.$width,$out);
                    }
                }
                $browser->logout();
            });
        }
        file_put_contents($out.'/measurements.json', json_encode($this->results, JSON_PRETTY_PRINT));
        file_put_contents($out.'/failures.json', json_encode($this->failures, JSON_PRETTY_PRINT));
        $this->assertSame([], $this->failures, implode("\n", $this->failures));
    }

    private function navigate(Browser $browser,string $url): void
    {
        $path=parse_url($url,PHP_URL_PATH);
        for($attempt=0;$attempt<3;$attempt++) {
            $browser->visit($url)->pause(700);
            if(parse_url($browser->driver->getCurrentURL(),PHP_URL_PATH)===$path) break;
        }
        $browser->waitForLocation($path,10)->waitFor('.page-content',10);
    }

    private function viewport(Browser $browser, string $theme, int $width): void
    {
        $browser->driver->executeCustomCommand('/session/:sessionId/goog/cdp/execute', 'POST', ['cmd'=>'Emulation.setDeviceMetricsOverride','params'=>['width'=>$width,'height'=>$width === 390 ? 844 : 900,'deviceScaleFactor'=>1,'mobile'=>false]]);
        $browser->script('localStorage.setItem("app-theme",'.json_encode($theme).');document.documentElement.dataset.theme='.json_encode($theme));
        $browser->pause(500);
    }

    private function capture(Browser $browser, string $selector, string $key, string $out): void
    {
        $js = file_get_contents(__DIR__.'/ui-contrast-measure.js');
        $data = $browser->script('return ('.$js.')('.json_encode($selector).')')[0];
        $this->results[$key] = $data;
        if (!$data['samples']) {
            file_put_contents($out.'/'.$key.'-empty-diagnostic.json',json_encode($browser->script('return {url:location.href,targets:[...document.querySelectorAll('.json_encode($selector).')].map(e=>({text:e.textContent.slice(0,50),class:e.className,inert:!!e.closest("[inert]"),display:getComputedStyle(e).display,opacity:getComputedStyle(e).opacity,rect:e.getBoundingClientRect().toJSON()})),overlays:[...document.querySelectorAll(".prof-overlay,.prof-sheet")].map(e=>({class:e.className,display:getComputedStyle(e).display,opacity:getComputedStyle(e).opacity,rect:e.getBoundingClientRect().toJSON()}))}')[0],JSON_PRETTY_PRINT));
        }
        file_put_contents($out.'/measurements.json', json_encode($this->results, JSON_PRETTY_PRINT));
        foreach ($data['samples'] as $sample) {
            if ((getenv('UI_CORRECTION_PHASE') ?: 'phase1') === 'phase1' && str_contains($sample['class'], 'pm-status')) continue;
            if (!$sample['uncertain'] && !$sample['disabled'] && $sample['ratio'] + .01 < $sample['threshold']) $this->failures[] = $key.' '.json_encode($sample);
        }
        if ($data['overflow']) $this->failures[] = $key.' document overflow';
        $browser->driver->takeScreenshot($out.'/'.$key.'.png');
    }
}

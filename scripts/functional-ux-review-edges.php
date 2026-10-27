<?php
use App\Models\{Branch,FuneralCase,Payment,User};
if (!isset($root,$browser,$results) || !str_contains($root,'functional-ux-review-')) throw new RuntimeException('Review runner required');
$record=function($name,$passed,$details=[]) use (&$results) { $results['checks'][]=compact('name','passed','details'); echo ($passed?'PASS ':'FINDING ').$name."\n"; };
$fetch=function($url,$payload=null) use($browser) {
    $browser->script('window.edgeResult=null;fetch('.json_encode($url).','.($payload===null?'{headers:{Accept:"application/json"}}':'{method:"POST",headers:{Accept:"application/json","Content-Type":"application/x-www-form-urlencoded"},body:new URLSearchParams({...'.json_encode($payload).',_token:document.querySelector("meta[name=csrf-token]").content})}').').then(async r=>{let t=await r.text();let d=new DOMParser().parseFromString(t,"text/html");d.querySelectorAll("script,style").forEach(e=>e.remove());window.edgeResult={status:r.status,url:r.url,text:d.body.textContent.replace(/\\s+/g," ").slice(0,16000)}})');
    $browser->waitUntil('window.edgeResult!==null',15); return $browser->script('return window.edgeResult')[0];
};
$driver->executeCustomCommand('/session/:sessionId/goog/cdp/execute','POST',['cmd'=>'Emulation.setDeviceMetricsOverride','params'=>['width'=>1440,'height'=>1000,'deviceScaleFactor'=>1,'mobile'=>false]]);
if($role==='staff') {
    $browser->visit('/payments/history'); $browser->script('document.documentElement.dataset.theme="dark"');
    $style=$browser->script('const e=document.querySelector(".pm-page-size");const chain=[];for(let p=e;p;p=p.parentElement){const c=getComputedStyle(p);chain.push({tag:p.tagName,cls:p.className,color:c.color,bg:c.backgroundColor})}return chain;')[0];
    $record('dark payment page-size label computed colors captured',true,$style);
    $otherBranch=$user->branch;
    $browser->visit('/intake/main');
    $header=$browser->script('return {title:document.querySelector("#intake-page-title").innerText,branch:document.querySelector("#branch_id").value}')[0];
    $record('non-main assigned branch intake title',!str_contains($header['title'],'Main Branch'),$header+['assigned_branch'=>$otherBranch->id,'assigned_name'=>$otherBranch->branch_name]);
    $browser->screenshot('edge-second-branch-intake');
    $otherCase=FuneralCase::first(); $r=$fetch('/funeral-cases/'.$otherCase->id); $record('Staff second branch cannot access main case',in_array($r['status'],[403,404],true),['status'=>$r['status']]);
}
if($role==='branch_admin') {
    $browser->visit('/admin/service-management?section=packages&q=NoSuchAcceptancePackage');
    $record('catalog empty search renders without server error',!str_contains($browser->script('return document.body.innerText')[0],'Server Error'));
    $r=$fetch('/admin/packages',['name'=>'Forbidden Catalog','price'=>1]); $record('Branch Admin catalog write denied',$r['status']===403,['status'=>$r['status']]);
}
if($role==='system_admin') {
    $browser->visit('/admin/users');
    $r=$fetch('/admin/users',['first_name'=>'Acceptance','last_name'=>'User','name'=>'Acceptance User','email'=>'acceptance-review-no-mail@gmail.com','role'=>'staff','branch_id'=>$seed->branch_id,'password'=>'AcceptanceOnly123!','password_confirmation'=>'AcceptanceOnly123!','contact_number'=>'09170009988','address'=>'Test Pangasinan']);
    $created=User::where('email','acceptance-review-no-mail@gmail.com')->first();
    $record('System Admin can create assigned Staff account',$created!==null,['response'=>$r]);
    $r=$fetch('/admin/users?status=inactive&q=NoSuchAcceptance'); $record('user filter request succeeds',$r['status']===200,['status'=>$r['status']]);
    $r=$fetch('/admin/branches?status=inactive&q=NoSuchAcceptance'); $record('branch filter request succeeds',$r['status']===200,['status'=>$r['status']]);
    $browser->visit('/admin/backup-recovery'); $browser->click('[data-open-backup-dialog]')->pause(150)->screenshot('edge-backup-password-dialog');
    $record('manual backup confirmation requires password',$browser->script('return !!document.querySelector("input[type=password]")')[0]);
}
if($role==='owner') {
    // Consistent synthetic records: older case, valid receipt today. Change test fixtures only.
    $old=FuneralCase::first(); $old->forceFill(['created_at'=>today()->subMonths(2),'total_amount'=>1000,'total_paid'=>250,'balance_amount'=>750,'verification_status'=>'VERIFIED','payment_status'=>'PARTIAL'])->save();
    Payment::where('funeral_case_id',$old->id)->delete();
    Payment::create(['funeral_case_id'=>$old->id,'branch_id'=>$old->branch_id,'amount'=>250,'paid_at'=>now(),'paid_date'=>today(),'payment_method'=>'cash','status'=>'POSTED','payment_record_no'=>'UX-TODAY-250']);
    $overview=app(App\Support\OwnerDashboardOverview::class)->data(['branch_id'=>null,'range'=>'TODAY','date_from'=>today()->toDateString(),'date_to'=>today()->toDateString()]);
    $analytics=app(App\Services\BranchAnalyticsService::class)->ownerPageData(['range'=>'TODAY']);
    $record('Owner period collection vs case-cohort distinction reproduced',$overview['current']['collections']===250.0 && $analytics['totalCollected']===0.0,['overview_receipts_today'=>$overview['current']['collections'],'analytics_collected_for_cases_recorded_today'=>$analytics['totalCollected']]);
    $browser->visit('/owner?range=TODAY')->screenshot('edge-owner-receipt-date');
    $browser->visit('/owner/branch-analytics?range=TODAY')->screenshot('edge-owner-case-date');
    $browser->visit('/owner/collections?range=TODAY')->assertSee('250.00');
    $record('Owner collection records include receipt on older case',true);
    $browser->visit('/owner/branch-analytics?range=CUSTOM&date_from='.today()->subDays(2)->toDateString().'&date_to='.today()->toDateString().'&analytics_tab=ba-panel-trend');
    $record('Owner custom range and selected analytics tab persist',$browser->script('return document.querySelector("#ba-panel-trend").classList.contains("active")')[0]);
}

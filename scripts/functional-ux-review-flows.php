<?php
use App\Models\FuneralCase;
// Included only by the isolated review runner; not an application endpoint.
if (!isset($root,$browser,$results) || !str_contains($root,'functional-ux-review-')) throw new RuntimeException('Review runner required');
$record = function (string $name, bool $passed, array $details=[]) use (&$results): void {
    $results['checks'][]=compact('name','passed','details'); echo ($passed?'PASS ':'FINDING ').$name."\n";
};
$post = function (string $url, array $payload=[]) use ($browser): array {
    $browser->script('window.reviewResponse=null;fetch('.json_encode($url).',{method:"POST",headers:{"Content-Type":"application/x-www-form-urlencoded","Accept":"application/json"},body:new URLSearchParams({...'.json_encode($payload).',_token:document.querySelector("meta[name=csrf-token]").content})}).then(async r=>{const t=await r.text();const doc=new DOMParser().parseFromString(t,"text/html");doc.querySelectorAll("script,style").forEach(e=>e.remove());window.reviewResponse={status:r.status,url:r.url,text:(doc.body.textContent||t).replace(/\\s+/g," ").slice(0,12000)}}).catch(e=>{window.reviewResponse={status:0,text:String(e)}})');
    $browser->waitUntil('window.reviewResponse!==null',20);
    return $browser->script('return window.reviewResponse')[0];
};
$driver->executeCustomCommand('/session/:sessionId/goog/cdp/execute','POST',['cmd'=>'Emulation.setDeviceMetricsOverride','params'=>['width'=>1440,'height'=>1000,'deviceScaleFactor'=>1,'mobile'=>false]]);
if ($role==='staff') {
    $browser->visit('/intake/main')->click('#wizardNext')->pause(250);
    $record('empty intake remains on Client Info', $browser->script('return !document.querySelector("#intake-step-1").classList.contains("hidden")')[0]);
    $browser->type('client_first_name','Review')->type('client_last_name','Representative')->select('client_relationship','Child')->type('client_contact_number','09170009988')->type('client_address','Barangay Test, Pangasinan')->click('#wizardNext')->pause(250);
    $record('valid Client Info advances to deceased step', $browser->script('return !document.querySelector("#intake-step-2").classList.contains("hidden")')[0]);
    $browser->screenshot('flow-intake-deceased');
    $identity=(string)Illuminate\Support\Str::uuid();
    $package=App\Models\Package::where('is_active',true)->firstOrFail();
    $payload=[
        'branch_id'=>$seed->branch_id,'submission_id'=>$identity,'service_requested_at'=>today()->toDateString(),
        'client_name'=>'Acceptance Representative','client_relationship'=>'Daughter','client_contact_number'=>'09170009988',
        'client_email'=>'acceptance@example.test','client_valid_id_type'=>'National ID','client_valid_id_number'=>'QA-ACCEPTANCE',
        'client_address'=>'Barangay Test, Pangasinan','deceased_name'=>'Acceptance Deceased','deceased_address'=>'Barangay Test, Pangasinan',
        'born'=>today()->subYears(65)->toDateString(),'died'=>today()->subDay()->toDateString(),'civil_status'=>'WIDOWED',
        'wake_location'=>'QA Chapel','wake_start_date'=>today()->toDateString(),'wake_start_time'=>'08:00',
        'wake_end_date'=>today()->addDay()->toDateString(),'wake_end_time'=>'07:00','funeral_service_at'=>today()->addDay()->toDateString(),
        'funeral_service_time'=>'08:00','interment_at'=>today()->addDays(2)->setTime(9,0)->format('Y-m-d H:i:s'),'interment_time'=>'09:00',
        'place_of_cemetery'=>'QA Cemetery','case_status'=>'ACTIVE','transport_option'=>'HEARSE','coffin_length_cm'=>175,
        'coffin_size'=>'LARGE','embalming_required'=>1,'embalming_status'=>'PENDING','package_id'=>$package->id,
        'additional_service_amount'=>0,'senior_citizen_status'=>0,'pwd_status'=>0,'confirm_review'=>1,'mark_as_paid'=>1,
        'payment_type'=>'PARTIAL','payment_method'=>'cash','paid_at'=>now()->format('Y-m-d H:i:s'),'amount_paid'=>5000,
    ];
    $before=FuneralCase::count(); $response=$post('/intake/main',$payload);
    $payment=App\Models\Payment::where('submission_id',$identity)->first();
    $record('intake creation and initial payment persist once', $payment!==null && FuneralCase::count()===$before+1, ['response'=>$response]);
    if (!$payment) return;
    $reviewCase=$payment->funeralCase; $reviewCaseId=$reviewCase->id;
    $record('initial billing balance matches selected package', (float)$reviewCase->total_paid===5000.0 && round((float)$reviewCase->balance_amount,2)===round((float)$reviewCase->total_amount-5000,2), ['package_price'=>$package->price,'total'=>$reviewCase->total_amount,'paid'=>$reviewCase->total_paid,'balance'=>$reviewCase->balance_amount,'status'=>$reviewCase->payment_status]);
    $response=$post('/intake/main',$payload);
    $record('intake retry does not duplicate case/payment', FuneralCase::count()===$before+1 && App\Models\Payment::where('submission_id',$identity)->count()===1,['response'=>$response]);
    $browser->visit('/funeral-cases?q='.$reviewCase->case_code)->assertSee($reviewCase->case_code);
    $browser->visit('/funeral-cases/'.$reviewCaseId)->assertSee('Payment Summary')->assertSee('Transactions')->assertDontSeeLink('Record Payment');
    $browser->visit('/payments/history')->press('Record Payment')->waitFor('#paymentFormModal:not(.hidden)',10)->select('funeral_case_id',(string)$reviewCaseId);
    $browser->type('amount_paid','500')->press('Continue to Payment Method')->waitFor('[data-payment-method-option="cash"]')->click('[data-payment-method-option="cash"]')->press('Continue to Review')->waitFor('#pf_submit_btn');
    $browser->screenshot('flow-payment-review');
    $browser->press('Confirm Payment')->waitForText('Payment recorded successfully',15);
    $record('payment modal adds exactly 500 to saved case', (float)$reviewCase->fresh()->total_paid===5500.0);
    $newPayment=App\Models\Payment::where('funeral_case_id',$reviewCaseId)->latest('id')->firstOrFail();
    $response=$post('/payments/pay',['funeral_case_id'=>$reviewCaseId,'amount_paid'=>99999999,'payment_method'=>'cash','submission_id'=>(string)Illuminate\Support\Str::uuid(),'paid_at'=>now()->format('Y-m-d H:i:s')]);
    $record('overpayment rejected without changing total', (float)$reviewCase->fresh()->total_paid===5500.0 && str_contains($response['text'],'Amount cannot exceed'),['response'=>$response]);
    $response=$post('/payments/'.$newPayment->id.'/correction-request',['correction_type'=>'CORRECT','amount'=>400,'reason'=>'Acceptance review correction of entered amount']);
    $correction=App\Models\PaymentCorrectionRequest::where('payment_id',$newPayment->id)->first();
    $record('staff requests correction without changing original total', $correction!==null && $correction->status==='PENDING' && (float)$reviewCase->fresh()->total_paid===5500.0,['response'=>$response]);
    $response=$post('/admin/branches',['branch_name'=>'Unauthorized Branch','address'=>'Test Address']);
    $record('staff denied branch administration', $response['status']===403,['status'=>$response['status']]);
}
if ($role==='branch_admin') {
    $browser->visit('/admin/payments?tab=corrections')->waitFor('.pc-request',10);
    $browser->click('.pc-review > summary')->press('Approve Correction')->waitFor('#pcApprovalDialog[open]')->screenshot('flow-correction-confirmation')->click('[data-pc-confirm]')->pause(700);
    $record('Branch Admin approval retains voided original and corrects totals', $correction->fresh()->status==='APPROVED' && (float)$reviewCase->fresh()->total_paid===5400.0 && $newPayment->fresh()->status==='VOIDED',['status'=>$correction->fresh()->status,'paid'=>$reviewCase->fresh()->total_paid,'balance'=>$reviewCase->fresh()->balance_amount]);
    foreach (['/admin/backup-recovery','/admin/record-retention','/admin/packages/create'] as $url) {
        $browser->visit('/admin');
        $browser->script('window.denied=null;fetch('.json_encode($url).',{headers:{Accept:"application/json"}}).then(r=>window.denied=r.status)');
        $browser->waitUntil('window.denied!==null',10);
        $record('Branch Admin restricted '.$url,$browser->script('return window.denied')[0]===403);
    }
    $other=FuneralCase::where('branch_id','!=',$seed->branch_id)->first();
    if ($other) { $browser->visit('/admin'); $browser->script('window.denied=null;fetch("/funeral-cases/'.$other->id.'",{headers:{Accept:"application/json"}}).then(r=>window.denied=r.status)'); $browser->waitUntil('window.denied!==null'); $record('Branch Admin cannot read other branch operational case',in_array($browser->script('return window.denied')[0],[403,404],true)); }
}
if ($role==='system_admin') {
    $browser->visit('/admin/branches');
    $response=$post('/admin/branches',['branch_name'=>'Acceptance Branch','address'=>'Test Barangay, Pangasinan','is_active'=>1]);
    $record('System Admin branch creation works',App\Models\Branch::where('branch_name','like','%Acceptance%')->exists(),['response'=>$response]);
    $browser->visit('/admin/record-retention');
    $response=$post('/admin/record-retention/'.$reviewCaseId.'/legal-hold',['reason'=>'Acceptance legal hold verification']);
    $record('System Admin legal hold persists', $reviewCase->fresh()->legal_hold_at!==null,['response'=>$response]);
    $response=$post('/admin/record-retention/'.$reviewCaseId.'/release-hold',['reason'=>'Acceptance authorized release verification']);
    $record('System Admin release hold preserves case/payments', $reviewCase->fresh()->legal_hold_at===null && (float)$reviewCase->fresh()->total_paid===5400.0,['response'=>$response]);
}
if ($role==='owner') {
    $browser->visit('/owner/branch-analytics?range=THIS_MONTH');
    foreach (['ba-panel-payment','ba-panel-trend','ba-panel-collection'] as $panel) {
        $browser->script('document.querySelector("[data-target=\"'.$panel.'\"]").click()'); $browser->pause(250);
        $record('Owner analytics tab '.$panel,$browser->script('return document.querySelector("#'.$panel.'").classList.contains("active")')[0]);
    }
    $response=$post('/payments/pay',['funeral_case_id'=>$reviewCaseId,'amount_paid'=>1]);
    $record('Owner operational payment write denied', $response['status']===403,['status'=>$response['status']]);
}

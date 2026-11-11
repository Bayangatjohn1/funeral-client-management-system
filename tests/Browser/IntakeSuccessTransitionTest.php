<?php

namespace Tests\Browser;

use App\Models\{Branch,User,Client,Deceased,FuneralCase,Package,Payment};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class IntakeSuccessTransitionTest extends DuskTestCase
{
    use RefreshDatabase;

    public function createApplication()
    {
        $app = parent::createApplication();
        if (($_ENV['DB_CONNECTION'] ?? null) !== 'sqlite' || ($_ENV['DB_DATABASE'] ?? null) !== ':memory:') throw new \RuntimeException('UI fixtures require in-memory SQLite.');
        $app['config']->set('database.default','sqlite');
        $app['config']->set('database.connections.sqlite.database',':memory:');
        return $app;
    }

    public function test_saved_dialogs_keep_parent_page_and_switch_smoothly(): void
    {
        $branch = Branch::create(['branch_code'=>'PM-VISUAL','branch_name'=>'Visual branch','address'=>'Test','type'=>'main','is_active'=>true]);
        $client = Client::create(['branch_id'=>$branch->id,'full_name'=>'Client with a longer family name for layout review','relationship_to_deceased'=>'Spouse','contact_number'=>'09170000000','address'=>'Test']);
        $deceased = Deceased::create(['branch_id'=>$branch->id,'client_id'=>$client->id,'full_name'=>'Deceased with a longer family name','date_of_death'=>now()->subDay()->toDateString(),'age'=>70]);
        $package = Package::create(['name'=>'UI fixture package','coffin_type'=>'Standard','price'=>10000,'is_active'=>true]);
        $case = FuneralCase::create(['branch_id'=>$branch->id,'client_id'=>$client->id,'deceased_id'=>$deceased->id,'package_id'=>$package->id,'case_number'=>FuneralCase::nextCaseNumber($branch->id),'case_code'=>'UI-CASE-001','service_type'=>'Burial','service_requested_at'=>now()->toDateString(),'wake_location'=>'Test Wake','funeral_service_at'=>now()->addDay()->toDateString(),'service_package'=>$package->name,'coffin_type'=>'Standard','total_amount'=>10000,'subtotal_amount'=>10000,'total_paid'=>0,'balance_amount'=>10000,'payment_status'=>'UNPAID','case_status'=>'ACTIVE','entry_source'=>'MAIN','verification_status'=>'VERIFIED']);
        Payment::create(['funeral_case_id'=>$case->id,'branch_id'=>$branch->id,'amount'=>2500,'reference_number'=>'PM-SEARCH-REF','payment_method'=>'cash','paid_at'=>now(),'paid_date'=>now()->toDateString(),'payment_status_after_payment'=>'PARTIAL','balance_after_payment'=>7500]);

        $actor = User::factory()->create(['role'=>'staff','branch_id'=>$branch->id,'is_active'=>true]);
        if ($actor->requiresMfa()) {
            $actor->two_factor_secret = (new \PragmaRX\Google2FA\Google2FA)->generateSecretKey();
            $actor->two_factor_confirmed_at=now(); $actor->save();
        }
        $this->actingAs($actor)->withSession([
            'mfa_user_id'=>$actor->requiresMfa()?$actor->id:null,
            'idle_last_activity'=>now()->timestamp,'idle_session_key'=>'intake-success-test','account_security'=>\App\Support\AccountSecurity::fingerprint($actor),
            'intake_payment'=>['id'=>123,'record_no'=>'PAY-TEST','case_code'=>'UI-CASE-001','amount'=>2500,'balance'=>7500,'client'=>'Test client','deceased'=>'Test deceased','method'=>'Cash','received_at'=>'Nov 08, 2026 10:00 AM'],
            'summary'=>['total'=>10000,'package'=>'Test package','payment_status'=>'PARTIAL']
        ]);
        $manifest=json_decode(file_get_contents(public_path('build/manifest.json')),true);
        $css=file_get_contents(public_path('build/'.$manifest['resources/css/app.css']['file']));
        foreach($manifest['resources/js/app.js']['css'] as $file) $css.=file_get_contents(public_path('build/'.$file));
        $bundle=file_get_contents(public_path('build/'.$manifest['resources/js/app.js']['file']));
        $prepare=function($html) use($css,$bundle) {
            $html=preg_replace('~<link\b[^>]*href="[^"]*/build/[^"]*"[^>]*>~i','',$html);
            $html=preg_replace('~<script\b[^>]*src="[^"]*/build/[^"]*"[^>]*>\s*</script>~i','',$html);
            return str_replace('</head>','<style>'.$css.'</style><script type="module">'.str_ireplace('</script','<\/script',$bundle).'</script></head>',$html);
        };
        $child=storage_path('framework/intake-success-child.html');
        $parent=storage_path('framework/intake-success-parent.html');
        file_put_contents($child,$prepare($this->get(route('intake.saved',['intake_modal'=>1]))->assertOk()->getContent()));
        $html=$prepare($this->get(route('payments.history'))->assertOk()->getContent());
        $url='file:///'.str_replace('\\','/',$child);
        $html=preg_replace('~data-intake-trigger="[^"]*"~','data-intake-trigger="'.$url.'"',$html);
        file_put_contents($parent,$html);
        try {
            $this->browse(function(Browser $browser) use($parent,$child) {
                $browser->driver->get('file:///'.str_replace('\\','/',$parent));
                $browser->waitFor('[data-intake-trigger]');
                $browser->script("document.querySelector('[data-intake-trigger]').dataset.intakeTrigger='about:blank'");
                $browser->click('[data-intake-trigger]');
                $browser->script('document.querySelector("[data-intake-frame]").srcdoc='.json_encode(file_get_contents($child)));
                $browser->waitUntil("document.querySelector('#staff-intake-dialog').classList.contains('is-saved')");
                $browser->assertScript("document.querySelector('.pm-page').getBoundingClientRect().height>0",true);
                $browser->withinFrame('[data-intake-frame]',function(Browser $frame) {
                    $frame->waitFor('#intakePaymentSuccess[open]');
                    $frame->assertScript("document.querySelector('#intakePaymentSuccess').matches(':modal')",true);
                    $frame->click('#intakePaymentDone')->waitFor('#intakeSavedConfirmation[open]');
                    $frame->assertMissing('#intakePaymentSuccess');
                    $frame->assertScript("getComputedStyle(document.querySelector('#intakeSavedConfirmation'),'::backdrop').backgroundColor",'rgba(0, 0, 0, 0)');
                    $frame->assertScript("getComputedStyle(document.body).backgroundColor",'rgba(0, 0, 0, 0)');
                });
                $browser->screenshot('intake-success-origin-confirmation');
                $browser->driver->get('about:blank');
            });
        } finally { @unlink($child); @unlink($parent); }
    }
}

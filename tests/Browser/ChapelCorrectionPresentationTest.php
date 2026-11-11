<?php

namespace Tests\Browser;

use App\Models\Branch;
use App\Models\Client;
use App\Models\Deceased;
use App\Models\FuneralCase;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ChapelCorrectionPresentationTest extends DuskTestCase
{
    use RefreshDatabase;

    public function createApplication()
    {
        $app = parent::createApplication();
        if (($_ENV['DB_CONNECTION'] ?? null) !== 'sqlite' || ($_ENV['DB_DATABASE'] ?? null) !== ':memory:') {
            throw new \RuntimeException('Presentation tests require isolated in-memory SQLite.');
        }

        return $app;
    }

    private function fixture(string $html): string
    {
        $manifest = json_decode(file_get_contents(public_path('build/manifest.json')), true);
        $css = file_get_contents(public_path('build/'.$manifest['resources/css/app.css']['file']));
        foreach ($manifest['resources/js/app.js']['css'] as $file) {
            $css .= file_get_contents(public_path('build/'.$file));
        }
        $css = str_replace('url(./', 'url(file:///'.str_replace('\\', '/', public_path('build/assets')).'/', $css);
        $bundle = file_get_contents(public_path('build/'.$manifest['resources/js/app.js']['file']));
        $html = preg_replace('~<link\b[^>]*>~i', '', $html);
        $html = preg_replace('~<script\b[^>]*src="[^"]*"[^>]*>\s*</script>~i', '', $html);
        $html = str_replace('</head>', '<style>'.$css.'</style><script>window.workflowErrors=[];addEventListener("error",e=>workflowErrors.push(e.message));addEventListener("unhandledrejection",e=>workflowErrors.push(String(e.reason)));</script><script type="module">'.str_ireplace('</script', '<\/script', $bundle).'</script></head>', $html);
        $path = tempnam(storage_path('framework'), 'workflow-ui-');
        rename($path, $path.'.html');
        $path .= '.html';
        file_put_contents($path, $html);

        return $path;
    }

    private function authenticate(User $actor): void
    {
        if ($actor->requiresMfa()) {
            $actor->two_factor_secret = (new \PragmaRX\Google2FA\Google2FA)->generateSecretKey();
            $actor->two_factor_confirmed_at = now();
            $actor->save();
        }
        $this->withSession(['idle_last_activity' => now()->timestamp, 'idle_session_key' => 'workflow-presentation', 'account_security' => \App\Support\AccountSecurity::fingerprint($actor), 'mfa_user_id' => $actor->requiresMfa() ? $actor->id : null]);
        $this->actingAs($actor);
    }

    public function test_payment_proof_qr_preview_cancel_and_phone_upload_are_usable(): void
    {
        view()->share('errors', new \Illuminate\Support\ViewErrorBag());
        $widget = \Illuminate\Support\Facades\Blade::render('<x-payment-proof-upload context="payment" />');
        $png = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aWQAAAABJRU5ErkJggg==';
        $mock = '<script>window.proofState="waiting";window.proofCancelled=false;window.fetch=async(url,options={})=>{let data;if(options.method==="POST")data={id:"11111111-1111-4111-8111-111111111111",expires_at:new Date(Date.now()+300000).toISOString(),qr:'.json_encode($png).'};else if(options.method==="DELETE"){window.proofCancelled=true;data={};}else data={state:window.proofState,name:"Receipt OR-1234.png",mime:"image/png",preview:'.json_encode($png).'};return new Response(JSON.stringify(data),{headers:{"Content-Type":"application/json"}});};</script>';
        $path = $this->fixture('<!doctype html><html data-theme="light"><head><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="csrf-token" content="test"></head><body><form style="max-width:560px;margin:auto;padding:16px">'.$widget.'</form>'.$mock.'</body></html>');
        try {
            $this->browse(function (Browser $browser) use ($path) {
                $browser->driver->get('file:///'.str_replace('\\', '/', $path));
                $browser->resize(1440, 1000)->waitUntil('typeof window.validatePaymentProof === "function"');
                $browser->script('Object.defineProperty(document,"hidden",{configurable:true,get:()=>false});document.querySelector("[data-payment-proof]").dataset.base="https://fcms.test/payment-proof/sessions"');
                $browser->assertScript('window.validatePaymentProof(document.querySelector("form"))', false)
                    ->assertSee('Attach a receipt or transaction confirmation before continuing.')
                    ->click('[data-proof-phone]')->waitUntil('!document.querySelector("[data-proof-qr]").hidden')
                    ->waitUntil('getComputedStyle(document.querySelector("[data-proof-qr]")).opacity === "1"')
                    ->assertSee('No phone sign-in needed.')->click('[data-proof-cancel]')
                    ->waitUntil('window.proofCancelled')->assertScript('document.querySelector("[data-proof-qr]").hidden', true)
                    ->click('[data-proof-phone]')->waitUntil('!document.querySelector("[data-proof-qr]").hidden');
                $browser->script('window.proofState="received"');
                $browser->waitUntil('!document.querySelector("[data-proof-received]").hidden', 8)
                    ->waitUntil('getComputedStyle(document.querySelector("[data-proof-received]")).opacity === "1"')->assertSee('Proof received.')
                    ->assertInputValue('payment_proof_id', '11111111-1111-4111-8111-111111111111')
                    ->assertScript('window.validatePaymentProof(document.querySelector("form"))', true);
                $browser->script('const parent=document.createElement("dialog");parent.id="proofParent";parent.append(document.querySelector("form"));document.body.append(parent);parent.showModal();');
                $browser->click('[data-proof-preview]')->waitUntil('Boolean(document.querySelector(".proof-viewer[open] img")?.naturalWidth)')
                    ->assertPresent('.proof-viewer-shared-scrim[open]')
                    ->click('[data-zoom-in]')->assertSeeIn('.proof-viewer-toolbar output', '150%')
                    ->click('[data-zoom-fit]')->assertSeeIn('.proof-viewer-toolbar output', '100%')
                    ->click('[data-proof-viewer-close]')->assertPresent('#proofParent[open]')
                    ->assertFocused('[data-proof-preview]');
                $browser->script('const parent=document.querySelector("#proofParent");parent.close();document.body.append(parent.querySelector("form"));parent.remove();');
                foreach (['light', 'dark'] as $theme) {
                    $browser->script('document.documentElement.dataset.theme="'.$theme.'"');
                    $browser->resize(390, 844)
                        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth', true)
                        ->screenshot('payment-proof-'.$theme.'-mobile');
                    $browser->click('[data-proof-preview]')->waitUntil('Boolean(document.querySelector(".proof-viewer[open] img")?.naturalWidth)')
                        ->screenshot('payment-proof-viewer-'.$theme.'-mobile')->click('[data-proof-viewer-close]');
                }
                $browser->click('[data-proof-remove]')->waitUntil('document.querySelector("[name=payment_proof_id]").value === ""');
            });
        } finally { @unlink($path); }

        $phoneHtml = view('payment-proof.phone')->render();
        $phoneHtml = str_replace('<script>', '<script>window.XMLHttpRequest=class{constructor(){this.upload={};}open(){}setRequestHeader(){}send(){this.status=200;this.onload();}};</script><script>', $phoneHtml);
        $path = $this->fixture($phoneHtml);
        $imagePath = storage_path('framework/proof-browser.png');
        file_put_contents($imagePath, base64_decode(substr($png, strpos($png, ',') + 1)));
        try {
            $this->browse(function (Browser $browser) use ($path, $imagePath) {
                $browser->driver->get('file:///'.str_replace('\\', '/', $path));
                $browser->resize(390, 844)->attach('#file', $imagePath)->assertVisible('#preview')->assertEnabled('#send')
                    ->click('#send')->assertSee('Proof sent. Return to the desktop')->assertScript('document.querySelector("#phoneProofForm").hidden', true)
                    ->screenshot('payment-proof-phone-sent');
            });
        } finally { @unlink($path); @unlink($imagePath); }
    }

    public function test_request_fields_confirmation_and_responsive_layout_preserve_input(): void
    {
        $branch = Branch::create(['branch_code' => 'BR001', 'branch_type' => 'main', 'branch_name' => 'Main Branch', 'is_active' => true]);
        $staff = User::factory()->create(['role' => 'staff', 'branch_id' => $branch->id, 'is_active' => true]);
        $client = Client::create(['branch_id' => $branch->id, 'full_name' => 'Maria Santos', 'relationship_to_deceased' => 'Spouse', 'contact_number' => '09170000000', 'address' => 'Quezon City']);
        $deceased = Deceased::create(['branch_id' => $branch->id, 'client_id' => $client->id, 'full_name' => 'Juan Santos', 'date_of_death' => today(), 'age' => 70]);
        $case = FuneralCase::create(['branch_id' => $branch->id, 'client_id' => $client->id, 'deceased_id' => $deceased->id, 'case_number' => 1, 'case_code' => 'FC0001', 'service_type' => 'Burial', 'service_requested_at' => today(), 'service_package' => 'Standard', 'coffin_type' => 'Standard', 'wake_location' => 'Home', 'funeral_service_at' => today(), 'subtotal_amount' => 10000, 'total_amount' => 10000, 'balance_amount' => 6000, 'total_paid' => 4000, 'payment_status' => 'PARTIAL', 'case_status' => 'ACTIVE', 'entry_source' => 'MAIN']);
        \Illuminate\Support\Facades\Storage::fake('local')->put('payment-proofs/receipt.pdf', "%PDF-1.4\n%%EOF");
        $payment = Payment::create(['branch_id' => $branch->id, 'funeral_case_id' => $case->id, 'amount' => 4000, 'payment_method' => 'cash', 'paid_at' => now(), 'paid_date' => today(), 'recorded_by' => $staff->id, 'status' => 'POSTED', 'receipt_or_no' => 'OR-1234', 'transaction_reference_no' => 'TXN-9876', 'proof_path' => 'payment-proofs/receipt.pdf', 'proof_mime' => 'application/pdf']);
        $this->authenticate($staff);
        $html = $this->get(route('payments.corrections.create', $payment))->assertOk()->getContent();
        $path = $this->fixture($html);
        try {
            $this->browse(function (Browser $browser) use ($path) {
                $browser->driver->get('file:///'.str_replace('\\', '/', $path));
                $browser->resize(1440, 1000)->waitUntil("document.documentElement.dataset.paymentWorkflowReady === '1'");
                $browser->click('[data-correction-request-submit]')
                    ->assertSee('Select at least one detail to correct.')
                    ->check('input[name="changes[]"][value="funeral_case_id"]')
                    ->click('[data-correction-request-submit]')
                    ->assertSee('Select the correct case from the search results.')
                    ->assertFocused('[data-correction-case-search]')
                    ->uncheck('input[name="changes[]"][value="funeral_case_id"]');
                $browser->click('input[name=correction_type][value=VOID_ONLY]')
                    ->assertSee('Payment Record No. to keep')
                    ->assertScript('document.querySelector("[name=evidence_reference]").required', true)
                    ->click('input[name=correction_type][value=CORRECT]');
                $browser->check('input[name="changes[]"][value="amount"]')->type('amount', '3000');
                $browser->assertScript('document.querySelector("[data-correction-impact]").textContent.includes("7,000")', true);
                $browser->check('input[name="changes[]"][value="payment_details"]')->select('payment_method', 'cashless')->select('cashless_type', 'bank_transfer')->select('bank_name', 'Other Bank');
                $browser->assertVisible('input[name=other_bank_name]')->assertScript('document.querySelector("input[name=approval_code]").disabled', true);
                $browser->script('document.querySelector("[data-workflow-close]").click()');
                $browser->waitUntil('Boolean(document.querySelector("dialog.pw-confirm")?.open)', 10)->assertScript('getComputedStyle(document.querySelector("#workflowContent")).display', 'none');
                $browser->script('document.querySelector(".pw-confirm [data-keep]").click()');
                $browser->waitUntil('!document.querySelector("dialog.pw-confirm")', 10)->assertInputValue('amount', '3000');
                foreach (['light', 'dark'] as $theme) {
                    $browser->script('document.documentElement.dataset.theme="'.$theme.'"');
                    $browser->resize(390, 844)->assertScript('document.querySelector("#workflowContent").scrollWidth <= document.querySelector("#workflowContent").clientWidth + 1', true)->screenshot('payment-request-'.$theme);
                }
            });
        } finally {
            @unlink($path);
        }

        Payment::create([
            'branch_id' => $branch->id, 'funeral_case_id' => $case->id, 'amount' => 1500,
            'payment_method' => 'cash', 'paid_at' => now(), 'recorded_by' => $staff->id,
            'status' => 'POSTED', 'receipt_or_no' => 'OR-5678',
        ]);
        $history = $this->get(route('payments.history'))->assertOk()->getContent();
        $path = $this->fixture($history);
        try {
            $this->browse(function (Browser $browser) use ($path, $html, $payment) {
                $browser->driver->get('file:///'.str_replace('\\', '/', $path));
                $browser->resize(1440, 1000)->waitFor('[data-pm-open-transactions-modal]');
                $browser->script('const realFetch = window.fetch; window.fetch = (url, options) => url === '.json_encode(route('payments.corrections.create', $payment)).' ? Promise.resolve(new Response('.json_encode($html, JSON_HEX_TAG | JSON_INVALID_UTF8_SUBSTITUTE).')) : realFetch(url, options);');
                $browser->click('[data-pm-open-transactions-modal]')->waitFor('.pm-transactions-backdrop.open [data-pm-history-detail]')
                    ->click('.pm-transactions-backdrop.open [data-pm-correction-picker-toggle]')
                    ->assertVisible('.pm-transactions-backdrop.open > [data-pm-correction-picker]')
                    ->assertFocused('[data-pm-correction-search]')
                    ->click('.pm-transactions-backdrop.open [data-pm-correction-cancel]')
                    ->assertPresent('.pm-transactions-backdrop.open > [data-pm-correction-picker][hidden]')
                    ->click('.pm-transactions-backdrop.open [data-pm-correction-picker-toggle]')
                    ->type('[data-pm-correction-search]', 'or5678')
                    ->waitUntil("document.querySelector('.pm-transactions-backdrop.open [data-pm-correction-results]').textContent.includes('OR-5678')")
                    ->assertSeeIn('[data-pm-correction-results]', 'OR-5678')
                    ->click('[data-pm-correction-results] button')
                    ->assertSeeIn('[data-pm-detail-host]', 'No proof on file')
                    ->click('[data-pm-history-back]')
                    ->click('.pm-transactions-backdrop.open [data-pm-correction-picker-toggle]')
                    ->type('[data-pm-correction-search]', 'missing-reference')
                    ->waitUntil("document.querySelector('.pm-transactions-backdrop.open [data-pm-correction-search-status]').textContent.includes('No matching payments')")
                    ->assertSeeIn('[data-pm-correction-search-status]', 'No matching payments')
                    ->click('.pm-transactions-backdrop.open [data-pm-correction-picker]:not([hidden]) [data-pm-correction-search-clear]')
                    ->type('[data-pm-correction-search]', 'or-1234')
                    ->waitUntil("document.querySelector('.pm-transactions-backdrop.open [data-pm-correction-results]').textContent.includes('OR-1234')")
                    ->assertSeeIn('[data-pm-correction-results]', 'OR-1234')
                    ->assertSeeIn('[data-pm-correction-results] mark', 'OR-1234')
                    ->assertSeeIn('[data-pm-correction-results]', 'Maria Santos')
                    ->assertSeeIn('[data-pm-correction-results]', '4,000.00')
                    ->assertSeeIn('[data-pm-correction-results]', $payment->paid_at->format('M d, Y'))
                    ->assertSeeIn('[data-pm-correction-results]', 'Posted')
                    ->resize(390, 844)
                    ->assertScript('document.querySelector("[data-pm-correction-picker]").scrollWidth <= document.querySelector("[data-pm-correction-picker]").clientWidth + 1', true)
                    ->resize(1440, 1000)
                    ->click('.pm-transactions-backdrop.open [data-pm-correction-picker]:not([hidden]) [data-pm-correction-search-clear]')
                    ->type('[data-pm-correction-search]', 'txn-9876')
                    ->waitUntil("document.querySelector('.pm-transactions-backdrop.open [data-pm-correction-results]').textContent.includes('TXN-9876')")
                    ->assertSeeIn('[data-pm-correction-results]', 'TXN-9876')
                    ->keys('[data-pm-correction-search]', '{ARROW_DOWN}')
                    ->assertScript('document.querySelector("[data-pm-correction-search]").getAttribute("aria-activedescendant") === document.querySelector("[data-pm-correction-results] [role=option]").id', true)
                    ->keys('[data-pm-correction-search]', '{ENTER}')
                    ->waitFor('.pw-companion [data-correction-form]')
                    ->assertSeeIn('.pw-companion [data-correction-form]', 'OR-1234')
                    ->assertScript('Math.abs(document.querySelector(".pm-transactions-backdrop.open .pm-transactions-modal").getBoundingClientRect().right - window.innerWidth) <= 1', true)
                    ->assertScript('document.querySelector(".pw-companion:has([data-correction-form])").getBoundingClientRect().right <= document.querySelector(".pm-transactions-backdrop.open .pm-transactions-modal").getBoundingClientRect().left', true)
                    ->resize(390, 844)
                    ->assertScript('document.querySelector(".pw-companion:has([data-correction-form])").getBoundingClientRect().top >= document.querySelector(".pm-transactions-backdrop.open .pm-transactions-modal").getBoundingClientRect().bottom', true)
                    ->click('.pw-companion [data-workflow-close]')
                    ->waitUntilMissing('.pw-companion [data-correction-form]')
                    ->assertVisible('.pm-transactions-backdrop.open');
            });
        } finally {
            @unlink($path);
        }

        $request = \App\Models\PaymentCorrectionRequest::create(['payment_id' => $payment->id, 'branch_id' => $branch->id, 'requested_by' => $staff->id, 'correction_type' => 'CORRECT', 'original_values' => (new \App\Models\PaymentCorrectionRequest)->snapshot($payment->fresh()), 'proposed_values' => ['amount' => 3000], 'reason_category' => 'Incorrect amount', 'reason' => 'Receipt shows three thousand received.', 'evidence_reference' => 'Receipt OR-1234']);
        $admin = User::factory()->create(['role' => 'admin', 'admin_scope' => 'branch', 'branch_id' => $branch->id, 'is_active' => true]);
        $this->authenticate($admin);
        $path = $this->fixture($this->get(route('payments.corrections.show', $request))->assertOk()->getContent());
        try {
            $this->browse(function (Browser $browser) use ($path) {
                $browser->driver->get('file:///'.str_replace('\\', '/', $path));
                $browser->resize(1440, 1000)->waitUntil("document.documentElement.dataset.paymentWorkflowReady === '1'");
                $browser->assertSee('Before you approve')->assertSee('Financial impact if approved')->assertSee('7,000.00');
                $browser->click('[data-review-history-open]')->waitFor('.pw-history-standalone[open]')
                    ->assertSeeIn('.pw-history-standalone', 'FC0001')
                    ->click('[data-review-history-close]')->assertFocused('[data-review-history-open]');
                $browser->script('const host=document.createElement("dialog");host.id="reviewLayoutFixture";host.className="pw-dialog";host.append(document.querySelector("#workflowContent"));document.body.append(host);host.showModal();');
                $browser->click('[data-review-history-open]')->waitFor('.pw-review-with-history')
                    ->assertScript('document.querySelector(".pw-review-history-panel").getBoundingClientRect().right <= document.querySelector("#workflowContent").getBoundingClientRect().left', true)
                    ->assertScript('Math.abs(document.querySelector("#workflowContent").getBoundingClientRect().right - window.innerWidth) <= 1', true)
                    ->screenshot('branch-correction-history-comparison')
                    ->type('review_reason', 'Compared the case payment history.')
                    ->click('[data-review-history-close]')->assertInputValue('review_reason', 'Compared the case payment history.')
                    ->assertPresent('#reviewLayoutFixture[open]');
                $browser->click('[data-review-history-open]')->resize(390, 844)
                    ->assertScript('document.querySelector(".pw-review-history-panel").getBoundingClientRect().top >= document.querySelector("#workflowContent").getBoundingClientRect().bottom', true)
                    ->assertScript('document.querySelector("#reviewLayoutFixture").scrollWidth <= document.querySelector("#reviewLayoutFixture").clientWidth', true)
                    ->screenshot('branch-correction-history-narrow')
                    ->click('[data-review-history-close]');
                $browser->click('[data-workflow-reject]')->assertVisible('textarea[name=reason]')->assertScript('document.querySelector("textarea[name=reason]").required', true);
                $browser->screenshot('payment-correction-review');
            });
        } finally {
            @unlink($path);
        }
    }

    public function test_resolved_correction_uses_the_same_compact_hierarchy_for_staff_and_admin(): void
    {
        $branch = Branch::create(['branch_code' => 'BR001', 'branch_type' => 'main', 'branch_name' => 'Main Branch', 'is_active' => true]);
        $staff = User::factory()->create(['role' => 'staff', 'branch_id' => $branch->id, 'is_active' => true]);
        $admin = User::factory()->create(['role' => 'admin', 'admin_scope' => 'branch', 'branch_id' => $branch->id, 'is_active' => true]);
        $client = Client::create(['branch_id' => $branch->id, 'full_name' => 'Maria Santos', 'relationship_to_deceased' => 'Spouse', 'contact_number' => '09170000000', 'address' => 'Quezon City']);
        $deceased = Deceased::create(['branch_id' => $branch->id, 'client_id' => $client->id, 'full_name' => 'Juan Santos', 'date_of_death' => today(), 'age' => 70]);
        $case = FuneralCase::create(['branch_id' => $branch->id, 'client_id' => $client->id, 'deceased_id' => $deceased->id, 'case_number' => 1, 'case_code' => 'FC0001', 'service_type' => 'Burial', 'service_requested_at' => today(), 'service_package' => 'Standard', 'coffin_type' => 'Standard', 'wake_location' => 'Home', 'funeral_service_at' => today(), 'subtotal_amount' => 10000, 'total_amount' => 10000, 'balance_amount' => 6000, 'total_paid' => 4000, 'payment_status' => 'PARTIAL', 'case_status' => 'ACTIVE', 'entry_source' => 'MAIN']);
        $payment = Payment::create(['branch_id' => $branch->id, 'funeral_case_id' => $case->id, 'payment_record_no' => 'PAY-2026-000001', 'receipt_or_no' => 'OR-001234', 'amount' => 4000, 'payment_method' => 'cash', 'paid_at' => now(), 'paid_date' => today(), 'recorded_by' => $staff->id, 'status' => 'POSTED']);
        $request = \App\Models\PaymentCorrectionRequest::create(['payment_id' => $payment->id, 'branch_id' => $branch->id, 'requested_by' => $staff->id, 'correction_type' => 'CORRECT', 'status' => 'REJECTED', 'original_values' => (new \App\Models\PaymentCorrectionRequest)->snapshot($payment->fresh()), 'proposed_values' => ['amount' => 3000], 'reason_category' => 'Incorrect amount', 'reason' => 'The receipt records a different amount.', 'evidence_reference' => 'OR-001234', 'reviewed_by' => $admin->id, 'reviewed_at' => now(), 'review_reason' => 'The collection log confirms the original amount.']);
        foreach (['staff' => $staff, 'branch' => $admin] as $role => $actor) {
            $this->authenticate($actor);
            $path = $this->fixture($this->get(route('payments.corrections.show', $request))->assertOk()->getContent());
            try {
                $this->browse(function (Browser $browser) use ($path, $role) {
                    $browser->driver->get('file:///'.str_replace('\\', '/', $path));
                    $browser->resize(1440, 1000)->waitUntil("document.documentElement.dataset.paymentWorkflowReady === '1'");
                    $browser->script('const host=document.createElement("dialog");host.className="pw-dialog";host.append(document.querySelector("#workflowContent"));document.body.append(host);host.showModal();');
                    $browser->assertSee('Payment Changes')->assertSee('Original Details')->assertSee('Requested Changes')->assertSee('Staff explanation')->assertSee('Decision · Rejected')
                        ->assertSee('The collection log confirms the original amount.')
                        ->assertScript('document.querySelector(".pw-audit-trail").open', false)
                        ->assertMissing('[data-workflow-reject]');
                    foreach (['light', 'dark'] as $theme) {
                        $browser->script('document.documentElement.dataset.theme="'.$theme.'";document.querySelector("#workflowContent > .pw-body").scrollTop=0;');
                        $browser->screenshot('correction-hierarchy-'.$role.'-'.$theme);
                    }
                });
            } finally { @unlink($path); }
        }
    }

    public function test_correction_queue_toolbar_keeps_search_inline_for_both_roles(): void
    {
        $branch = Branch::create(['branch_code' => 'BR001', 'branch_type' => 'main', 'branch_name' => 'Main Branch', 'is_active' => true]);
        foreach (['staff' => 'payments.history', 'admin' => 'admin.payments.index'] as $role => $route) {
            $actor = User::factory()->create(['role' => $role, 'admin_scope' => $role === 'admin' ? 'branch' : null, 'branch_id' => $branch->id, 'is_active' => true]);
            $this->authenticate($actor);
            $path = $this->fixture($this->get(route($route, ['tab' => 'corrections']))->assertOk()->getContent());
            try {
                $this->browse(function (Browser $browser) use ($path) {
                    $browser->driver->get('file:///'.str_replace('\\', '/', $path));
                    foreach ([1920, 1440, 390] as $width) {
                        $browser->resize($width, 900)->assertScript('(() => {
                                const search = document.querySelector("[data-correction-toolbar] .shared-record-search");
                                const field = search.querySelector("input").getBoundingClientRect();
                                const button = search.querySelector("button").getBoundingClientRect();
                                const toolbar = document.querySelector("[data-correction-toolbar]");
                                const bounds = toolbar.getBoundingClientRect();
                                const controls = [...toolbar.querySelectorAll(":scope > .search, :scope > .pm-more-filters, :scope > .pm-sort-control, :scope > .pm-page-size")].map(control => control.getBoundingClientRect());
                                const visible = controls.length === 4 && controls.every(rect => rect.width >= 80 && rect.height >= 40
                                    && rect.left >= bounds.left - 1 && rect.right <= bounds.right + 1);
                                const separated = controls.every((rect, index) => controls.slice(index + 1).every(next =>
                                    rect.right <= next.left + 1 || next.right <= rect.left + 1 || rect.bottom <= next.top + 1 || next.bottom <= rect.top + 1));
                                return visible && separated && field.width > 80
                                    && Math.abs((field.top + field.bottom) / 2 - (button.top + button.bottom) / 2) < 2
                                    && Math.abs(button.right - search.getBoundingClientRect().right) < 3
                                    && toolbar.scrollWidth <= toolbar.clientWidth + 1;
                            })()', true);
                    }
                });
            } finally {
                @unlink($path);
            }
        }
    }

    public function test_chapel_category_reveals_configuration_and_locks_unit_to_day(): void
    {
        Branch::create(['branch_code' => 'BR001', 'branch_type' => 'main', 'branch_name' => 'Main Branch', 'is_active' => true]);
        $admin = User::factory()->create(['role' => 'admin', 'admin_scope' => 'system', 'is_active' => true]);
        $this->authenticate($admin);
        $html = $this->get(route('admin.add-on-catalogs.create'))->assertOk()->getContent();
        $path = $this->fixture($html);
        try {
            $this->browse(function (Browser $browser) use ($path) {
                $browser->driver->get('file:///'.str_replace('\\', '/', $path));
                $browser->resize(1200, 900)->waitUntil("document.documentElement.dataset.paymentWorkflowReady === '1'");
                $browser->script('const category=document.getElementById("addon-category");category.value="Chapel Viewing";category.dispatchEvent(new Event("change",{bubbles:true}));');
                $browser->assertVisible('#addon-branch')->assertVisible('#addon-chapel-address')->assertSelected('unit', 'day')->assertScript('document.querySelector("#addon-branch").required',true);
                $browser->screenshot('chapel-configuration');
            });
        } finally {
            @unlink($path);
        }
    }
}

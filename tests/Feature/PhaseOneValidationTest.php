<?php
namespace Tests\Feature;
use App\Models\{Branch, User, Package, FreebieCatalog};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class PhaseOneValidationTest extends TestCase
{
    use RefreshDatabase;
    private function admin(): User
    {
        $branch = Branch::create(['branch_code'=>'BR001','branch_name'=>'Main Branch','address'=>'Manila','is_active'=>true]);
        $admin = User::factory()->create(['role'=>'admin','admin_scope'=>'system','branch_id'=>$branch->id,'is_active'=>true]);
        $this->actingAs($admin);
        return $admin;
    }
    public function test_enabled_days_reject_zero_on_add_and_edit(): void
    {
        $this->admin();
        $package=Package::create(['name'=>'Existing Package','price'=>1000,'is_active'=>true]);
        foreach (['embalming','home_viewing'] as $service) {
            $payload=['name'=>'Day Package','price'=>1000,'included_services'=>[$service=>['enabled'=>1,'included_days'=>0]]];
            $this->post(route('admin.packages.store'),$payload)->assertSessionHasErrors("included_services.$service.included_days");
            $this->put(route('admin.packages.update',$package),$payload)->assertSessionHasErrors("included_services.$service.included_days");
            $this->assertSame('Existing Package',$package->fresh()->name);
        }
    }

    public function test_branch_add_and_edit_accept_corrections_and_restrict_return_destinations(): void
    {
        $this->admin();
        $fallback=route('admin.branches.index');
        foreach (['https://evil.example/path','//evil.example','/\\evil.example','javascript:alert(1)','http://localhost.evil.example/admin','/%2fevil.example',"/admin%0d%0aLocation:evil",'/../outside'] as $unsafe) {
            $this->assertSame($fallback, \App\Support\LocalReturnUrl::resolve($unsafe,$fallback));
        }
        foreach ([$fallback, '/admin/branches?status=active', '/admin/branches?q=Main%20Branch'] as $safe) {
            $this->assertSame($safe, \App\Support\LocalReturnUrl::resolve($safe,$fallback));
        }
        $this->post(route('admin.branches.store'),['branch_name'=>'North Branch','address'=>'Manila','is_active'=>1,'return_to'=>'https://evil.example'])
            ->assertSessionHasNoErrors()->assertRedirect($fallback);
        $branch=Branch::where('branch_name','North Branch')->firstOrFail();
        $this->put(route('admin.branches.update',$branch),['branch_name'=>'West Branch','address'=>'Quezon City','is_active'=>1,'return_to'=>'//evil.example'])
            ->assertSessionHasNoErrors()->assertRedirect($fallback);
        foreach ([route('admin.branches.store'),route('admin.branches.update',$branch)] as $i=>$url) {
            $payload=['branch_name'=>str_repeat('A',256),'address'=>str_repeat('B',256),'is_active'=>1];
            ($i ? $this->put($url,$payload) : $this->post($url,$payload))->assertSessionHasErrors(['branch_name','address']);
        }
        $this->get(route('admin.branches.create',['return_to'=>'https://evil.example']))->assertOk()->assertDontSee('href="https://evil.example"',false)->assertSee('maxlength="255"',false);
        $this->get(route('admin.branches.edit',$branch))->assertOk()->assertSee('maxlength="255"',false);
    }
    public function test_names_require_letters_on_add_and_edit_and_preserve_saved_user(): void
    {
        $admin=$this->admin();
        $user=User::factory()->create(['role'=>'staff','branch_id'=>$admin->branch_id,'first_name'=>'Juan','last_name'=>'Cruz']);
        $before=$user->fresh()->name;
        $payload=['first_name'=>'---','last_name'=>"...",'email'=>'person@gmail.com','role'=>'staff','branch_id'=>$admin->branch_id,'password'=>'secret123'];
        $this->post(route('admin.users.store'),$payload)->assertSessionHasErrors(['first_name'=>'First name must contain at least one letter.','last_name'=>'Last name must contain at least one letter.']);
        unset($payload['password']);$payload['email']=$user->email;
        $this->put(route('admin.users.update',$user),$payload)->assertSessionHasErrors(['first_name','last_name']);
        $this->assertSame($before,$user->fresh()->name);
        $this->get(route('admin.users.create'))->assertOk()->assertSee('maxlength="100"',false)->assertSee('maxlength="50"',false)->assertSee('maxlength="255"',false);
        $this->get(route('admin.users.edit',$user))->assertOk()->assertSee('maxlength="100"',false)->assertSee('maxlength="50"',false);
    }
    public function test_privileged_password_and_errors_match_existing_minimum(): void
    {
        $admin=$this->admin();
        $this->post(route('admin.system-admins.store'),['password'=>'12345678901','reason'=>'short','confirmed'=>0,'current_password'=>'wrong'])
            ->assertSessionHasErrors(['password'=>'Password must be at least 12 characters.','reason','confirmed','current_password']);
        $this->get(route('admin.system-admins.create'))->assertOk()->assertSee('minlength="12"',false)
            ->assertSee('data-field-error="password"',false)->assertSee('Reason for System Admin access must contain at least 10 characters.')
            ->assertSee('data-validation-for="reason"',false)->assertSee('data-validation-for="confirmed"',false)->assertSee('data-validation-for="current_password"',false);
        $admin->password=\Illuminate\Support\Facades\Hash::make('current-secret'); $admin->save(); $this->actingAs($admin);
        $this->post(route('admin.system-admins.store'), ['first_name'=>'Phase','last_name'=>'Administrator','email'=>'phase.validation@gmail.com',
            'password'=>'123456789012','current_password'=>'current-secret','reason'=>'Validation boundary verification','confirmed'=>1])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users',['email'=>'phase.validation@gmail.com','admin_scope'=>'system']);
    }
    public function test_package_amount_bounds_precision_and_required_enabled_service_rates_on_add_and_edit(): void
    {
        $this->admin();$package=Package::create(['name'=>'Saved Package','price'=>1000,'is_active'=>true]);
        foreach (['123.456','123123123123123','-1'] as $amount) {
            $payload=['name'=>'Rate Package','price'=>1000,'included_services'=>['body_retrieval'=>['enabled'=>1,'included_kilometers'=>0,'price_per_excess_kilometer'=>$amount], 'embalming'=>['enabled'=>1,'included_days'=>1,'price_per_extended_day'=>$amount]]];
            $fields=['included_services.body_retrieval.price_per_excess_kilometer','included_services.embalming.price_per_extended_day'];
            $this->post(route('admin.packages.store'),$payload)->assertSessionHasErrors($fields);
            $this->put(route('admin.packages.update',$package),$payload)->assertSessionHasErrors($fields);
            $this->assertSame('Saved Package',$package->fresh()->name);
        }
        $payload=['name'=>'Valid Rates','price'=>1000,'included_services'=>['embalming'=>['enabled'=>1,'included_days'=>1,'price_per_extended_day'=>0]]];
        $this->post(route('admin.packages.store'),$payload)->assertSessionHasNoErrors();
        $this->put(route('admin.packages.update',$package),$payload)->assertSessionHasNoErrors();
        $this->assertSame('0.00',$package->fresh()->packageInclusions->first()->price_per_extended_day);
        $payload['included_services']['embalming']['price_per_extended_day']='9999999999.99';
        $this->put(route('admin.packages.update',$package),$payload)->assertSessionHasNoErrors();
        $this->assertSame('9999999999.99',$package->fresh()->packageInclusions->first()->price_per_extended_day);
    }
    public function test_package_nested_errors_render_inline_on_add_and_edit_without_array_conversion(): void
    {
        $this->admin();
        $package=Package::create(['name'=>'Existing','price'=>1000,'is_active'=>true]);
        $book=FreebieCatalog::create(['name'=>'Book','default_unit'=>'pcs','is_active'=>true,'is_available'=>true]);
        $old=['included_services'=>['embalming'=>['enabled'=>1,'included_days'=>0]],'freebies'=>[7=>['freebie_catalog_id'=>$book->id,'freebie_name'=>'Book','quantity'=>0,'unit'=>str_repeat('x',41)],9=>['freebie_catalog_id'=>99999,'freebie_name'=>'Missing Item']]];
        $errors=['included_services.embalming.included_days'=>'Embalming Included Days must be at least 1.', 'included_services.embalming.price_per_extended_day'=>'Embalming Extra Day Rate can have up to two decimal places.', 'freebies.7.quantity'=>'Quantity error', 'freebies.7.unit'=>'Unit error', 'freebies.9.freebie_catalog_id'=>'Selected freebie is unavailable.'];
        foreach ([route('admin.packages.create'),route('admin.packages.edit',$package)] as $url) {
            $response=$this->withSession(['_old_input'=>$old, 'errors'=>(new \Illuminate\Support\ViewErrorBag)->put('default',new \Illuminate\Support\MessageBag($errors))])->get($url)->assertOk();
            $dom=new \DOMDocument; @$dom->loadHTML($response->getContent()); $xpath=new \DOMXPath($dom);
            foreach (array_keys($errors) as $field) {
                $nodes=$xpath->query('//*[@data-validation-for="'.$field.'"]');
                $this->assertSame(1,$nodes->length,'Missing or repeated inline error: '.$field);
                $this->assertSame($errors[$field],trim($nodes->item(0)->textContent));
            }
            $response->assertSee('max="9999999999.99"',false)->assertSee('maxlength="150"',false)->assertSee('maxlength="40"',false);
        }
    }
}

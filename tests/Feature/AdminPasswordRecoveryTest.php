<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\User;
use App\Support\AccountSecurity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AdminPasswordRecoveryTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role = 'staff', ?int $branch = null): User
    {
        $branch ??= Branch::firstOrCreate(['branch_code' => 'BR001'], ['branch_name' => 'Main', 'is_active' => true])->id;
        return User::factory()->create(['role' => $role === 'staff' ? 'staff' : 'admin', 'admin_scope' => $role === 'system' ? 'system' : ($role === 'branch' ? 'branch' : null), 'branch_id' => $role === 'system' ? null : $branch, 'is_active' => true, 'password' => 'OriginalPassword123!']);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge(['password' => 'TemporaryPassword456!', 'password_confirmation' => 'TemporaryPassword456!', 'current_password' => 'OriginalPassword123!', 'reason' => 'Verified employee identity in person.'], $overrides);
    }

    public function test_branch_admin_resets_own_staff_and_records_reason_without_secrets(): void
    {
        $actor = $this->user('branch'); $staff = $this->user();
        $staff->two_factor_secret = 'ABCDEFGHIJKLMNOP'; $staff->two_factor_confirmed_at = now(); $staff->save();
        $token = Password::createToken($staff);
        $this->actingAs($actor)->patch(route('admin.users.resetPassword', $staff), $this->payload())->assertSessionHasNoErrors();
        $staff->refresh();
        $this->assertTrue(Hash::check('TemporaryPassword456!', $staff->password));
        $this->assertTrue($staff->must_change_password);
        $this->assertEquals(1, $staff->security_version);
        $this->assertEquals('ABCDEFGHIJKLMNOP', $staff->two_factor_secret);
        $this->assertFalse(Password::tokenExists($staff, $token));
        $audit = AuditLog::where('action', 'user.password_reset')->firstOrFail();
        $this->assertEquals($actor->id, $audit->actor_id);
        $this->assertEquals($this->payload()['reason'], $audit->metadata['reason']);
        $this->assertStringNotContainsString('TemporaryPassword456!', $audit->toJson());
    }

    public function test_branch_admin_cannot_reset_other_branch_or_admin_accounts(): void
    {
        $actor = $this->user('branch');
        $other = Branch::create(['branch_code' => 'BR002', 'branch_name' => 'Other', 'is_active' => true]);
        $this->actingAs($actor);
        foreach ([$this->user('staff', $other->id), $this->user('branch'), $this->user('system')] as $target) {
            $this->patch(route('admin.users.resetPassword', $target), $this->payload())->assertForbidden();
        }
    }

    public function test_system_admin_resets_branch_admin_but_not_system_admin(): void
    {
        $actor = $this->user('system'); $branchAdmin = $this->user('branch');
        $this->actingAs($actor)->patch(route('admin.users.resetPassword', $branchAdmin), $this->payload())->assertSessionHasNoErrors();
        $this->patch(route('admin.users.resetPassword', $this->user('system')), $this->payload())->assertForbidden();
    }

    public function test_wrong_admin_password_missing_reason_and_weak_password_do_not_reset(): void
    {
        $actor = $this->user('branch'); $target = $this->user(); $original = $target->password;
        $this->actingAs($actor);
        foreach ([['current_password' => 'wrong'], ['reason' => ''], ['password' => 'short', 'password_confirmation' => 'short']] as $invalid) {
            $this->patch(route('admin.users.resetPassword', $target), $this->payload($invalid))->assertSessionHasErrors();
            $this->assertEquals($original, $target->fresh()->password);
        }
        $this->assertDatabaseMissing('audit_logs', ['action' => 'user.password_reset']);
    }

    public function test_audit_failure_rolls_back_reset_and_token_deletion(): void
    {
        $actor = $this->user('system'); $target = $this->user(); $original = $target->password;
        $token = Password::createToken($target);
        AuditLog::creating(fn () => throw new \RuntimeException('Audit unavailable'));
        $this->actingAs($actor)->patch(route('admin.users.resetPassword', $target), $this->payload())->assertSessionHasErrors('password_reset');
        $this->assertEquals($original, $target->fresh()->password);
        $this->assertTrue(Password::tokenExists($target, $token));
        AuditLog::flushEventListeners();
    }

    public function test_temporary_password_cannot_bypass_mfa_or_change_password_gate(): void
    {
        $staff = $this->user(); $staff->must_change_password = true; $staff->save();
        $this->actingAs($staff)->withSession(['mfa_user_id' => null]);
        $this->get('/staff')->assertRedirect(route('security.challenge'));
        $this->post(route('security.password.update'), ['password' => 'PersonalPassword789!', 'password_confirmation' => 'PersonalPassword789!'])->assertRedirect(route('security.challenge'));
        $this->withSession(['mfa_user_id' => $staff->id])->get('/staff')->assertRedirect(route('security.password'));
        $this->put(route('password.update'), [])->assertRedirect(route('security.password'));
        $this->get(route('security.password'))->assertOk()->assertSee('Set your personal password')->assertDontSee('name="current_password"', false);
        $this->post(route('security.password.update'), ['password' => 'OriginalPassword123!', 'password_confirmation' => 'OriginalPassword123!'])->assertSessionHasErrors('password');
        $this->post(route('security.password.update'), ['password' => 'PersonalPassword789!', 'password_confirmation' => 'MismatchPassword789!'])->assertSessionHasErrors('password');
        $this->post(route('security.password.update'), ['password' => 'PersonalPassword789!', 'password_confirmation' => 'PersonalPassword789!'])->assertRedirect('/staff');
        $this->assertFalse($staff->fresh()->must_change_password);
        $this->assertTrue(Hash::check('PersonalPassword789!', $staff->fresh()->password));
        $this->post(route('security.password.update'), ['password' => 'AnotherPassword789!', 'password_confirmation' => 'AnotherPassword789!'])->assertRedirect('/staff');
        $this->get(route('security.password'))->assertRedirect('/staff');
        $this->assertTrue(Hash::check('PersonalPassword789!', $staff->fresh()->password));
    }

    public function test_staff_login_mfa_password_change_and_dashboard_flow(): void
    {
        $staff = $this->user();
        $staff->must_change_password = true;
        $staff->two_factor_secret = (new \PragmaRX\Google2FA\Google2FA)->generateSecretKey();
        $staff->two_factor_confirmed_at = now();
        $staff->two_factor_recovery_codes = [Hash::make('staff-test-recovery-code')];
        $staff->save();
        $this->post('/login', ['email' => $staff->email, 'password' => 'OriginalPassword123!'])->assertRedirect(route('security.challenge'));
        $this->post(route('security.verify'), ['code' => 'staff-test-recovery-code'])->assertRedirect(route('security.password'));
        $this->get(route('security.password'))->assertOk();
        $this->post(route('security.password.update'), ['password' => 'StaffPersonalPassword789!', 'password_confirmation' => 'StaffPersonalPassword789!'])->assertRedirect('/staff')->assertSessionHas('personal_password_set', true);
        $this->get('/staff')->assertOk()->assertSee('Your password is ready!')->assertSee('Continue to dashboard');
        $this->get('/staff')->assertOk()->assertDontSee('id="password-welcome-dialog"', false);
        $this->get(route('security.password'))->assertRedirect('/staff');
    }

    public function test_json_reset_keeps_validation_out_of_page_and_flashes_success_toast(): void
    {
        $actor = $this->user('branch'); $staff = $this->user();
        $this->actingAs($actor);
        $this->patchJson(route('admin.users.resetPassword', $staff), $this->payload(['current_password' => 'wrong']))
            ->assertStatus(422)->assertJsonValidationErrors('current_password');
        $this->assertFalse($staff->fresh()->must_change_password);
        $this->assertNull(session('_old_input.password'));
        $this->patchJson(route('admin.users.resetPassword', $staff), $this->payload())
            ->assertOk()->assertJsonPath('message', 'Password reset successful. Share the temporary password privately. The user must change it after sign-in and MFA.');
        $this->get(route('admin.users.edit', $staff))->assertOk()->assertSee('flash-success')->assertSee('Password reset successful.');
    }
}

<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\User;
use App\Support\AccountSecurity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class AccountSecurityWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function account(string $role = 'staff'): User
    {
        $branch = Branch::firstOrCreate(['branch_code' => 'BR001'], ['branch_name' => 'Main', 'is_active' => true]);
        $user = User::factory()->create(['role' => $role === 'staff' ? 'staff' : 'admin', 'admin_scope' => $role === 'system' ? 'system' : ($role === 'branch' ? 'branch' : null), 'branch_id' => $role === 'system' ? null : $branch->id, 'is_active' => true, 'password' => Hash::make('TestPassword123!')]);
        return $user;
    }

    private function verified(User $user): static
    {
        if ($user->isSystemAdmin()) {
            $user->two_factor_secret = (new Google2FA)->generateSecretKey();
            $user->two_factor_confirmed_at = now();
            $user->save();
        }
        return $this->actingAs($user)->withSession(['account_security' => AccountSecurity::fingerprint($user), 'mfa_user_id' => $user->id]);
    }

    private function change(User $target, array $override = []): array
    {
        return array_merge(['role' => 'branch_admin', 'expected_role' => 'staff', 'branch_id' => $target->branch_id, 'current_password' => 'TestPassword123!', 'reason' => 'Assigned to manage this branch.', 'confirmed' => '1'], $override);
    }

    public function test_staff_must_enroll_and_verify_mfa(): void
    {
        $this->exerciseOperationalMfa('staff', '/staff');
    }

    public function test_branch_admin_must_enroll_and_verify_mfa(): void
    {
        $this->exerciseOperationalMfa('branch', '/admin');
    }

    private function exerciseOperationalMfa(string $role, string $dashboard): void
    {
        $actor = $this->account($role);
        $originalPassword = $actor->password;
        $this->post('/login', ['email' => $actor->email, 'password' => 'TestPassword123!'])->assertRedirect(route('security.setup'));
        $this->get($dashboard)->assertRedirect(route('security.setup'));
        $this->put('/password', ['current_password' => 'TestPassword123!'])->assertRedirect(route('security.setup'));
        $setup = $this->get(route('security.setup'))->assertOk()->assertSee('data:image/svg+xml;base64,', false);
        $secret = $setup->viewData('secret');
        $this->post(route('security.enroll'), ['current_password' => 'TestPassword123!', 'code' => (new Google2FA)->getCurrentOtp($secret)])->assertRedirect(route('security.recovery'));
        $actor->refresh();
        $this->assertSame($originalPassword, $actor->password);
        $this->assertNotNull($actor->two_factor_confirmed_at);
        $this->get(route('security.recovery'))->assertOk()->assertSee(url($dashboard), false);
        $recovery = 'operational-recovery-code';
        $actor->two_factor_recovery_codes = [Hash::make($recovery)];
        $actor->save();
        $this->post('/logout');
        $this->post('/login', ['email' => $actor->email, 'password' => 'TestPassword123!'])->assertRedirect(route('security.challenge'));
        $this->get($dashboard)->assertRedirect(route('security.challenge'));
        $this->post(route('security.verify'), ['code' => 'wrong-code'])->assertSessionHasErrors('code');
        $this->post(route('security.verify'), ['code' => $recovery])->assertRedirect($dashboard);
        $this->assertSame([], $actor->fresh()->two_factor_recovery_codes);
        $this->get('/dashboard')->assertRedirect($dashboard);
    }

    public function test_inactive_branch_cannot_complete_mfa(): void
    {
        $actor = $this->account('staff');
        $actor->branch->update(['is_active' => false]);
        $this->be($actor)->withSession(['account_security' => AccountSecurity::fingerprint($actor)])->get(route('security.setup'))->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_promotion_is_scoped_audited_and_revokes_sessions(): void
    {
        $actor = $this->account('system'); $target = $this->account();
        $old = AccountSecurity::fingerprint($target);
        $this->verified($actor)->patch(route('admin.users.role.update', $target), $this->change($target))->assertRedirect(route('admin.users.edit', $target));
        $target->refresh();
        $this->assertTrue($target->isBranchAdmin());
        $this->assertSame(1, $target->security_version);
        $this->assertNotSame($old, AccountSecurity::fingerprint($target));
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.role_changed', 'actor_id' => $actor->id, 'entity_id' => $target->id]);
        $this->actingAs($target)->withSession(['account_security' => $old])->get('/admin/users')->assertRedirect(route('login'));
    }

    public function test_branch_admin_cannot_promote_or_assign_system_access(): void
    {
        $actor = $this->account('branch'); $target = $this->account();
        $this->verified($actor)->patch(route('admin.users.role.update', $target), $this->change($target))->assertForbidden();
        $this->verified($actor)->get(route('admin.system-admins.create'))->assertForbidden();
        $this->assertTrue($target->fresh()->isStaff());
    }

    public function test_system_promotion_and_missing_confirmation_or_wrong_password_are_rejected(): void
    {
        $actor = $this->account('system'); $target = $this->account();
        foreach ([['role' => 'system_admin'], ['current_password' => 'wrong'], ['confirmed' => '0']] as $invalid) {
            $this->verified($actor)->patch(route('admin.users.role.update', $target), $this->change($target, $invalid))->assertSessionHasErrors();
        }
        $this->assertTrue($target->fresh()->isStaff());
    }

    public function test_ordinary_edit_and_create_cannot_assign_system_admin(): void
    {
        $actor = $this->account('system'); $target = $this->account();
        $this->verified($actor)->put(route('admin.users.update', $target), ['role' => 'system_admin'])->assertSessionHasErrors('role');
        $this->verified($actor)->post(route('admin.users.store'), ['role' => 'system_admin'])->assertSessionHasErrors('role');
    }

    public function test_demotion_is_allowed_but_self_changes_and_system_targets_are_blocked(): void
    {
        $actor = $this->account('system'); $target = $this->account('branch');
        $this->verified($actor)->patch(route('admin.users.role.update', $target), $this->change($target, ['role' => 'staff', 'expected_role' => 'branch_admin']))->assertSessionHasNoErrors();
        $this->assertTrue($target->fresh()->isStaff());
        $this->verified($actor)->patch(route('admin.users.role.update', $actor), $this->change($target))->assertForbidden();
        $other = $this->account('system');
        $this->verified($actor)->patch(route('admin.users.role.update', $other), $this->change($target))->assertForbidden();
    }

    public function test_inactive_branches_and_stale_role_forms_are_rejected(): void
    {
        $actor = $this->account('system'); $target = $this->account();
        $this->verified($actor)->patch(route('admin.users.role.update', $target), $this->change($target, ['expected_role' => 'branch_admin']))->assertSessionHasErrors('role');
        $target->branch->update(['is_active' => false]);
        $this->verified($actor)->patch(route('admin.users.role.update', $target), $this->change($target))->assertSessionHasErrors('branch_id');
    }

    public function test_audit_failure_rolls_back_the_role_change(): void
    {
        $actor = $this->account('system'); $target = $this->account();
        AuditLog::creating(fn () => throw new \RuntimeException('Audit storage unavailable'));
        try {
            $this->verified($actor)->patch(route('admin.users.role.update', $target), $this->change($target))->assertStatus(500);
            $this->assertTrue($target->fresh()->isStaff());
            $this->assertSame(0, $target->fresh()->security_version);
        } finally { AuditLog::flushEventListeners(); }
    }

    public function test_admin_access_is_blocked_before_mfa_enrollment_or_challenge(): void
    {
        $actor = $this->account('system');
        $this->be($actor)->withSession(['account_security' => AccountSecurity::fingerprint($actor), 'mfa_user_id' => null])->get('/admin/users')->assertRedirect(route('security.setup'));
        $actor->two_factor_secret = (new Google2FA)->generateSecretKey(); $actor->two_factor_confirmed_at = now(); $actor->save();
        $this->get('/admin/users')->assertRedirect(route('security.challenge'));
    }

    public function test_mfa_enrollment_encrypts_secret_and_consumes_codes_once(): void
    {
        $actor = $this->account('system'); $actor->must_change_password = true; $actor->save();
        $secret = (new Google2FA)->generateSecretKey();
        $this->be($actor)->withSession(['account_security' => AccountSecurity::fingerprint($actor), 'mfa_setup_secret' => Crypt::encryptString($secret)])
            ->post(route('security.enroll'), ['current_password' => 'TestPassword123!', 'password' => 'PersonalPassword456!', 'password_confirmation' => 'PersonalPassword456!', 'code' => (new Google2FA)->getCurrentOtp($secret)])
            ->assertRedirect(route('security.recovery'));
        $actor->refresh();
        $this->assertTrue($actor->must_change_password);
        $this->post(route('security.password.update'), ['password' => 'PersonalPassword456!', 'password_confirmation' => 'PersonalPassword456!'])->assertRedirect('/admin');
        $this->assertNotSame($secret, $actor->getRawOriginal('two_factor_secret'));
        $this->get(route('security.recovery'))->assertOk();
        $this->post(route('security.verify'), ['code' => (new Google2FA)->getCurrentOtp($secret)])->assertSessionHasErrors('code');
    }

    public function test_setup_renders_a_local_qr_code_with_manual_fallback(): void
    {
        $actor = $this->account('system');
        $response = $this->be($actor)->withSession(['account_security' => AccountSecurity::fingerprint($actor)])
            ->get(route('security.setup'));
        $response->assertOk()->assertSee('data:image/svg+xml;base64,', false)
            ->assertSee('Enter the setup key manually')->assertSee('<details', false);
        $secret = $response->viewData('secret');
        $uri = (new Google2FA)->getQRCodeUrl(config('app.name', 'Funeral System'), $actor->email, $secret);
        $expected = (new \BaconQrCode\Writer(new \BaconQrCode\Renderer\ImageRenderer(
            new \BaconQrCode\Renderer\RendererStyle\RendererStyle(240),
            new \BaconQrCode\Renderer\Image\SvgImageBackEnd)))->writeString($uri);
        $this->assertSame('data:image/svg+xml;base64,'.base64_encode($expected), $response->viewData('qrCode'));
        $this->assertSame($secret, Crypt::decryptString(session('mfa_setup_secret')));
    }

    public function test_reset_privileged_password_requires_mfa_before_replacement(): void
    {
        $actor = $this->account('system');
        $this->verified($actor);
        $actor->must_change_password = true;
        $actor->save();
        $this->withSession(['mfa_user_id' => null])->get(route('security.setup'))
            ->assertRedirect(route('security.challenge'));
        $this->post(route('security.password.update'), ['current_password' => 'TestPassword123!',
            'password' => 'ReplacementPassword456!', 'password_confirmation' => 'ReplacementPassword456!'])
            ->assertRedirect(route('security.challenge'));
        $this->withSession(['mfa_user_id' => $actor->id])->get(route('security.password'))
            ->assertOk()->assertDontSee($actor->two_factor_secret, false);
        $this->post(route('security.password.update'), ['current_password' => 'TestPassword123!',
            'password' => 'ReplacementPassword456!', 'password_confirmation' => 'ReplacementPassword456!'])
            ->assertRedirect('/admin');
        $this->assertFalse($actor->fresh()->must_change_password);
        $this->assertTrue(Hash::check('ReplacementPassword456!', $actor->fresh()->password));
    }

    public function test_recovery_code_is_single_use(): void
    {
        $actor = $this->account('system');
        $this->verified($actor);
        $actor->two_factor_recovery_codes = [Hash::make('recovery-code-test')]; $actor->save();
        $this->post(route('security.verify'), ['code' => 'recovery-code-test'])->assertRedirect('/admin');
        $this->post(route('security.verify'), ['code' => 'recovery-code-test'])->assertSessionHasErrors('code');
    }

    public function test_role_form_and_separate_admin_form_render_without_role_editing(): void
    {
        $actor = $this->account('system'); $target = $this->account();
        $this->verified($actor)->get(route('admin.users.edit', $target))->assertOk()->assertSee('Change Role');
        $this->get(route('admin.users.role.edit', $target))->assertOk()->assertSee('Confirm role change');
        $this->get(route('admin.system-admins.create'))->assertOk()->assertSee('Link existing account (optional)');
    }

    public function test_separate_admin_account_is_linked_and_requires_password_change_and_mfa(): void
    {
        $actor = $this->account('system'); $regular = $this->account();
        $payload = ['first_name' => 'Juan', 'last_name' => 'Santos', 'email' => 'juan.privileged.test@gmail.com', 'password' => 'TemporaryPassword123!', 'privileged_for_user_id' => $regular->id, 'current_password' => 'TestPassword123!', 'confirmed' => '1', 'reason' => 'Responsible for system administration.'];
        $this->verified($actor)->post(route('admin.system-admins.store'), $payload)->assertSessionHasNoErrors()->assertRedirect();
        $created = User::where('email', $payload['email'])->firstOrFail();
        $this->assertTrue($created->isSystemAdmin());
        $this->assertNull($created->branch_id);
        $this->assertTrue($created->must_change_password);
        $this->assertNull($created->two_factor_confirmed_at);
        $this->assertEquals($regular->id, $created->privileged_for_user_id);
        $this->post(route('admin.system-admins.store'), array_merge($payload, ['email' => 'other.privileged.test@gmail.com']))->assertSessionHasErrors('privileged_for_user_id');
    }

    public function test_system_admin_can_create_an_unlinked_personal_admin_account(): void
    {
        $actor = $this->account('system');
        $this->verified($actor)->post(route('admin.system-admins.store'), [
            'first_name' => 'Maria', 'last_name' => 'Reyes', 'email' => 'maria.admin.test@gmail.com',
            'password' => 'TemporaryPassword123!', 'current_password' => 'TestPassword123!',
            'reason' => 'Dedicated technical administrator.', 'confirmed' => '1',
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.users.index'));
        $created = User::where('email', 'maria.admin.test@gmail.com')->firstOrFail();
        $this->assertTrue($created->isSystemAdmin());
        $this->assertNull($created->privileged_for_user_id);
        $this->assertTrue($created->must_change_password);
    }

    public function test_role_selection_forms_preserve_permissions(): void
    {
        $actor = $this->account('system');
        $this->verified($actor)->get(route('admin.users.index'))->assertOk()->assertSee('Choose the account type to continue.');
        $this->get(route('admin.users.create', ['role' => 'staff']))->assertOk()->assertSee('Create Staff Account')->assertDontSee('<select id="role"', false);
        $this->get(route('admin.users.create', ['role' => 'branch_admin']))->assertOk()->assertSee('Create Branch Admin Account')->assertDontSee('<select id="role"', false);
        $this->get(route('admin.users.create', ['role' => 'owner']))->assertNotFound();
        $branchAdmin = $this->account('branch');
        $this->verified($branchAdmin)->get(route('admin.users.create', ['role' => 'branch_admin']))->assertForbidden();
        $this->get(route('admin.system-admins.create'))->assertForbidden();
        $this->get(route('admin.users.create'))->assertOk()->assertSee('Create Staff Account');
    }

    public function test_system_admin_self_deletion_is_blocked(): void
    {
        $actor = $this->account('system');
        $this->verified($actor)->delete(route('profile.destroy'), ['password' => 'TestPassword123!'])->assertForbidden();
        $this->assertDatabaseHas('users', ['id' => $actor->id]);
    }

    public function test_login_requires_mfa_and_rate_limits_wrong_codes(): void
    {
        $actor = $this->account('system');
        $actor->two_factor_secret = (new Google2FA)->generateSecretKey(); $actor->two_factor_confirmed_at = now(); $actor->save();
        $this->post('/login', ['email' => $actor->email, 'password' => 'TestPassword123!'])->assertRedirect(route('security.challenge'));
        $this->get('/admin/users')->assertRedirect(route('security.challenge'));
        for ($i = 0; $i < 6; $i++) $this->post(route('security.verify'), ['code' => 'invalid'])->assertSessionHasErrors('code');
        $this->post(route('security.verify'), ['code' => 'invalid'])->assertStatus(429);
    }

    public function test_regular_edit_preserves_role_and_can_assign_an_active_branch(): void
    {
        $actor = $this->account('system'); $target = $this->account();
        $branch = Branch::create(['branch_code' => 'BR002', 'branch_name' => 'North', 'is_active' => true]);
        $this->verified($actor)->put(route('admin.users.update', $target), ['first_name' => 'Juan', 'last_name' => 'Santos', 'email' => 'juan.regular.edit@gmail.com', 'role' => 'staff', 'branch_id' => $branch->id])->assertSessionHasNoErrors();
        $this->assertTrue($target->fresh()->isStaff());
        $this->assertEquals($branch->id, $target->fresh()->branch_id);
        $this->assertSame(1, $target->fresh()->security_version);
    }

    public function test_deactivation_invalidates_sessions_and_cannot_target_self(): void
    {
        $actor = $this->account('system'); $target = $this->account();
        $this->verified($actor)->patch(route('admin.users.toggleActive', $actor))->assertSessionHas('error');
        $this->patch(route('admin.users.toggleActive', $target))->assertSessionHasNoErrors();
        $this->assertFalse($target->fresh()->is_active);
        $this->assertSame(1, $target->fresh()->security_version);
    }
}

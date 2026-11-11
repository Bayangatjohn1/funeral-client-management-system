<?php

namespace Tests\Unit;

use App\Models\FuneralCase;
use App\Models\User;
use App\Policies\FuneralCasePolicy;
use PHPUnit\Framework\TestCase;

class DraftCaseAccessTest extends TestCase
{
    public function test_drafts_are_denied_to_monitoring_roles_for_every_case_ability(): void
    {
        $policy = new FuneralCasePolicy;
        $draft = new FuneralCase(['case_status' => 'DRAFT']);
        foreach (['owner', 'admin'] as $role) {
            foreach (['branch', 'system'] as $scope) {
                $user = new User(['role' => $role, 'admin_scope' => $scope]);
                foreach (['view', 'update', 'delete', 'generateFuneralContract'] as $ability) {
                    $this->assertFalse($policy->before($user, $ability, $draft));
                }
            }
        }
        $this->assertNull($policy->before(new User(['role' => 'staff']), 'view', $draft));
        $this->assertNull($policy->before(new User(['role' => 'admin']), 'view', new FuneralCase(['case_status' => 'ACTIVE'])));
    }
}

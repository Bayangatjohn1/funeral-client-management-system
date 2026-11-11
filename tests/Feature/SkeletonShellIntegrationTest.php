<?php

namespace Tests\Feature;

use App\Models\{Branch, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class SkeletonShellIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_shared_loading_integration_preserves_valid_page_scripts_across_roles(): void
    {
        $branch = Branch::create(['branch_code'=>'BR001','branch_name'=>'Main','branch_type'=>'main','is_active'=>true]);
        $roles = [
            ['staff', null, ['funeral-cases.index','clients.index','deceased.index','payments.history']],
            ['admin', 'system', ['admin.cases.index','admin.service-management.index','admin.branches.index','admin.audit-logs.index','reports.index']],
            ['admin', 'branch', ['admin.cases.index','admin.service-management.index','admin.payments.index','reports.index']],
            ['owner', null, ['owner.history','owner.analytics','owner.dashboard']],
        ];
        $file = tempnam(sys_get_temp_dir(), 'skeleton-inline-');
        rename($file, $file.'.mjs');
        $file .= '.mjs';
        try {
            foreach ($roles as [$role,$scope,$routes]) {
                $user = User::factory()->create(['role'=>$role,'admin_scope'=>$scope,'branch_id'=>$role === 'owner' ? null : $branch->id,'is_active'=>true]);
                foreach ($routes as $route) {
                    $response = $this->followingRedirects()->actingAs($user)->get(route($route))->assertOk();
                    // Read raw script text: libxml's HTML4 parser truncates
                    // JavaScript template strings containing closing HTML tags.
                    preg_match_all('~<script\b([^>]*)>(.*?)</script\s*>~si', $response->getContent(), $scripts, PREG_SET_ORDER);
                    foreach ($scripts as $script) {
                        if (preg_match('/\bsrc\s*=/i', $script[1]) || preg_match('/\btype\s*=\s*["\']application\//i', $script[1])) continue;
                        file_put_contents($file, $script[2]);
                        $check = new Process(['node', '--check', $file]);
                        $check->run();
                        $this->assertTrue($check->isSuccessful(), $route.': '.$check->getErrorOutput());
                    }
                }
            }
        } finally { if (is_file($file)) unlink($file); }
    }
}

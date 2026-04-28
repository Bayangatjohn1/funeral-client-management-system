<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Client;
use App\Models\Deceased;
use App\Models\FuneralCase;
use App\Models\Package;
use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReportsModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys=OFF');
        }
    }

    public function test_staff_cannot_access_reports_routes(): void
    {
        $staff = $this->user('staff', $this->branch('BR001', 'Main Branch'));

        $this->actingAs($staff)->get('/reports')->assertForbidden();
        $this->actingAs($staff)->getJson('/reports/preview?report_type=sales')->assertForbidden();
        $this->actingAs($staff)->get('/reports/print?report_type=sales')->assertForbidden();
    }

    public function test_admin_and_owner_can_access_reports_index(): void
    {
        $this->actingAs($this->user('admin'))->get('/reports')->assertOk();
        $this->actingAs($this->user('owner'))->get('/reports')->assertOk();
    }

    public function test_old_owner_sales_route_redirects_to_central_reports_module(): void
    {
        $owner = $this->user('owner');

        $this->actingAs($owner)
            ->get('/owner/sales-per-branch')
            ->assertRedirect(route('reports.index', ['report_type' => 'owner_branch_analytics'], absolute: false));
    }

    public function test_staff_cannot_access_old_owner_sales_route_or_csv_export(): void
    {
        $staff = $this->user('staff', $this->branch('BR001', 'Main Branch'));

        $this->actingAs($staff)->get('/owner/sales-per-branch')->assertForbidden();
        $this->actingAs($staff)->get('/reports/export-csv?report_type=owner_branch_analytics')->assertForbidden();
    }

    public function test_preview_filters_by_payment_status(): void
    {
        $admin = $this->user('admin');
        $branch = $admin->branch;

        $this->case($branch, ['case_code' => 'PAID-001', 'payment_status' => 'PAID']);
        $this->case($branch, ['case_code' => 'UNPAID-001', 'payment_status' => 'UNPAID']);

        $response = $this->actingAs($admin)->getJson('/reports/preview?report_type=sales&payment_status=PAID');

        $response->assertOk()
            ->assertJsonPath('summary.total_records', 1)
            ->assertJsonPath('rows.0.payment_status', 'PAID');
    }

    public function test_preview_filters_by_branch_id(): void
    {
        $admin = $this->user('admin');
        $main = $admin->branch;
        $other = $this->branch('BR002', 'Second Branch');

        $this->case($main, ['case_code' => 'MAIN-001']);
        $this->case($other, ['case_code' => 'OTHER-001']);

        $response = $this->actingAs($admin)->getJson('/reports/preview?report_type=sales&branch_id=' . $other->id);

        $response->assertOk()
            ->assertJsonPath('summary.total_records', 1)
            ->assertJsonPath('rows.0.branch', 'BR002 - Second Branch');
    }

    public function test_branch_admin_reports_are_forced_to_assigned_branch(): void
    {
        $assigned = $this->branch('BR002', 'Second Branch');
        $other = $this->branch('BR001', 'Main Branch');
        $branchAdmin = $this->branchAdmin($assigned);

        $this->case($assigned, ['case_code' => 'ASSIGNED-001']);
        $this->case($other, ['case_code' => 'OTHER-001']);

        $response = $this->actingAs($branchAdmin)
            ->getJson('/reports/preview?report_type=sales&branch_id=' . $other->id);

        $response->assertOk()
            ->assertJsonPath('summary.total_records', 1)
            ->assertJsonPath('rows.0.branch', 'BR002 - Second Branch')
            ->assertJsonPath('filters.branch_id', 'BR002 - Second Branch');
    }

    public function test_branch_admin_print_and_csv_are_forced_to_assigned_branch(): void
    {
        $assigned = $this->branch('BR002', 'Second Branch');
        $other = $this->branch('BR001', 'Main Branch');
        $branchAdmin = $this->branchAdmin($assigned);

        $this->case($assigned, ['case_code' => 'ASSIGNED-001']);
        $this->case($other, ['case_code' => 'OTHER-001']);

        $print = $this->actingAs($branchAdmin)
            ->get('/reports/print?report_type=sales&branch_id=' . $other->id);

        $print->assertOk()
            ->assertSee('BR002 - Second Branch')
            ->assertDontSee('BR001 - Main Branch');

        $csv = $this->actingAs($branchAdmin)
            ->get('/reports/export-csv?report_type=sales&branch_id=' . $other->id);

        $csv->assertOk();
        $content = $csv->streamedContent();

        $this->assertStringContainsString('BR002 - Second Branch', $content);
        $this->assertStringNotContainsString('BR001 - Main Branch', $content);
    }

    public function test_branch_admin_owner_analytics_is_forced_to_assigned_branch(): void
    {
        $assigned = $this->branch('BR002', 'Second Branch');
        $other = $this->branch('BR001', 'Main Branch');
        $branchAdmin = $this->branchAdmin($assigned);

        $this->case($assigned, ['case_code' => 'ASSIGNED-001']);
        $this->case($other, ['case_code' => 'OTHER-001']);

        $response = $this->actingAs($branchAdmin)
            ->getJson('/reports/preview?report_type=owner_branch_analytics');

        $response->assertOk()
            ->assertJsonPath('summary.total_cases', 1)
            ->assertJsonCount(1, 'rows')
            ->assertJsonPath('rows.0.branch', 'BR002 - Second Branch');
    }

    public function test_branch_admin_audit_logs_are_forced_to_assigned_branch(): void
    {
        $assigned = $this->branch('BR002', 'Second Branch');
        $other = $this->branch('BR001', 'Main Branch');
        $branchAdmin = $this->branchAdmin($assigned);

        AuditLog::create([
            'actor_id' => $branchAdmin->id,
            'actor_role' => 'admin',
            'action' => 'assigned.action',
            'action_label' => 'Assigned Action',
            'action_type' => 'create',
            'entity_type' => 'funeral_case',
            'entity_id' => 1,
            'branch_id' => $assigned->id,
            'status' => 'success',
        ]);
        AuditLog::create([
            'actor_id' => $branchAdmin->id,
            'actor_role' => 'admin',
            'action' => 'other.action',
            'action_label' => 'Other Action',
            'action_type' => 'create',
            'entity_type' => 'funeral_case',
            'entity_id' => 2,
            'branch_id' => $other->id,
            'status' => 'success',
        ]);

        $response = $this->actingAs($branchAdmin)
            ->getJson('/reports/preview?report_type=audit_logs&branch_id=' . $other->id);

        $response->assertOk()
            ->assertJsonPath('summary.total_records', 1)
            ->assertJsonPath('rows.0.branch', 'BR002 - Second Branch')
            ->assertJsonPath('rows.0.action', 'Assigned Action');
    }

    public function test_invalid_date_range_returns_validation_error(): void
    {
        $admin = $this->user('admin');

        $this->actingAs($admin)
            ->getJson('/reports/preview?report_type=sales&date_from=2026-04-20&date_to=2026-04-01')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['date_to']);
    }

    public function test_owner_branch_analytics_returns_grouped_branch_rows(): void
    {
        $owner = $this->user('owner');
        $main = $this->branch('BR001', 'Main Branch');
        $other = $this->branch('BR002', 'Second Branch');

        $this->case($main, ['case_code' => 'MAIN-001', 'payment_status' => 'PAID', 'total_amount' => 10000, 'total_paid' => 10000, 'balance_amount' => 0]);
        $this->case($other, ['case_code' => 'OTHER-001', 'payment_status' => 'PARTIAL', 'total_amount' => 15000, 'total_paid' => 5000, 'balance_amount' => 10000]);

        $response = $this->actingAs($owner)->getJson('/reports/preview?report_type=owner_branch_analytics');

        $response->assertOk()
            ->assertJsonPath('summary.total_cases', 2)
            ->assertJsonCount(2, 'rows');
    }

    public function test_owner_can_export_branch_analytics_csv(): void
    {
        $owner = $this->user('owner');
        $branch = $this->branch('BR001', 'Main Branch');

        $this->case($branch, ['case_code' => 'CSV-001', 'payment_status' => 'PAID', 'total_amount' => 10000, 'total_paid' => 10000, 'balance_amount' => 0]);

        $response = $this->actingAs($owner)
            ->get('/reports/export-csv?report_type=owner_branch_analytics');

        $response->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $csv = $response->streamedContent();
        $lines = array_values(array_filter(preg_split('/\r\n|\r|\n/', trim($csv))));

        $this->assertSame([
            'Branch',
            'Total Cases',
            'Paid Cases',
            'Partial Cases',
            'Unpaid Cases',
            'Gross Amount',
            'Collected Amount',
            'Remaining Balance',
        ], str_getcsv($lines[0]));
        $this->assertSame('BR001 - Main Branch', str_getcsv($lines[1])[0]);
    }

    private function user(string $role, ?Branch $branch = null): User
    {
        if ($role === 'admin' && ! $branch) {
            $branch = $this->branch('BR001', 'Main Branch');
        }

        return User::factory()->create([
            'role' => $role,
            'admin_scope' => $role === 'admin' ? 'main' : null,
            'branch_id' => $branch?->id,
            'is_active' => true,
        ]);
    }

    private function branchAdmin(Branch $branch): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'admin_scope' => 'branch',
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);
    }

    private function branch(string $code, string $name): Branch
    {
        return Branch::firstOrCreate(
            ['branch_code' => $code],
            ['branch_name' => $name, 'address' => 'Test Address', 'is_active' => true]
        );
    }

    private function case(Branch $branch, array $overrides = []): FuneralCase
    {
        $client = Client::create([
            'branch_id' => $branch->id,
            'full_name' => 'Client ' . $branch->branch_code . uniqid(),
            'relationship_to_deceased' => 'Spouse',
            'contact_number' => '09170000000',
            'address' => 'Test Address',
        ]);

        $deceased = Deceased::create([
            'branch_id' => $branch->id,
            'client_id' => $client->id,
            'full_name' => 'Deceased ' . $branch->branch_code . uniqid(),
            'date_of_death' => now()->subDay()->toDateString(),
            'age' => 70,
        ]);

        $package = Package::firstOrCreate(
            ['name' => 'Basic Package'],
            ['coffin_type' => 'Standard', 'price' => 10000, 'is_active' => true]
        );

        return FuneralCase::create(array_merge([
            'branch_id' => $branch->id,
            'client_id' => $client->id,
            'deceased_id' => $deceased->id,
            'package_id' => $package->id,
            'case_number' => FuneralCase::nextCaseNumber($branch->id),
            'case_code' => 'CASE-' . uniqid(),
            'service_type' => 'Burial',
            'service_requested_at' => now()->toDateString(),
            'service_package' => 'Basic Package',
            'coffin_type' => 'Standard',
            'wake_location' => 'Test Wake',
            'funeral_service_at' => now()->addDay()->toDateString(),
            'subtotal_amount' => 10000,
            'discount_type' => 'NONE',
            'discount_value_type' => 'AMOUNT',
            'discount_value' => 0,
            'discount_amount' => 0,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'total_amount' => 10000,
            'total_paid' => 0,
            'balance_amount' => 10000,
            'payment_status' => 'UNPAID',
            'case_status' => 'ACTIVE',
            'entry_source' => 'MAIN',
            'verification_status' => 'VERIFIED',
            'encoded_by' => null,
        ], $overrides));
    }
}

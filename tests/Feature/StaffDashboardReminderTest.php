<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Client;
use App\Models\Deceased;
use App\Models\FuneralCase;
use App\Models\Payment;
use App\Models\User;
use App\Services\ReminderService;
use App\Support\TopbarNotificationBuilder;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffDashboardReminderTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_needs_attention_contains_only_staff_awareness_reminders(): void
    {
        $now = Carbon::parse('2026-09-26 10:00:00');
        Carbon::setTestNow($now);
        $branch = $this->branch();

        $completed = $this->case($branch, [
            'case_code' => 'CASE-COMPLETED',
            'interment_at' => '2026-09-26 09:00:00',
            'interment_time' => '09:00:00',
            'funeral_service_at' => '2026-09-24',
            'case_status' => 'COMPLETED',
        ]);
        $approaching = $this->case($branch, [
            'case_code' => 'CASE-APPROACHING',
            'interment_at' => '2026-09-27 09:00:00',
            'interment_time' => '09:00:00',
            'funeral_service_at' => '2026-09-25',
        ]);
        $ordinaryBalance = $this->case($branch, [
            'case_code' => 'CASE-ORDINARY',
            'interment_at' => '2026-10-10 09:00:00',
            'interment_time' => '09:00:00',
            'funeral_service_at' => '2026-10-09',
        ]);
        $sameDayOne = $this->case($branch, [
            'case_code' => 'CASE-SAME-1',
            'funeral_service_at' => '2026-09-29',
            'interment_at' => '2026-10-12 09:00:00',
            'payment_status' => 'PAID',
            'balance_amount' => 0,
            'total_paid' => 10000,
        ]);
        $sameDayTwo = $this->case($branch, [
            'case_code' => 'CASE-SAME-2',
            'funeral_service_at' => '2026-09-29',
            'interment_at' => '2026-10-13 09:00:00',
            'payment_status' => 'PAID',
            'balance_amount' => 0,
            'total_paid' => 10000,
        ]);

        $attention = app(ReminderService::class)->buildDashboard($branch->id, $now)['attention'];

        $this->assertSame('interment_completed', $attention->first()['type']);
        $this->assertTrue($attention->contains(fn ($item) => $item['case_id'] === $completed->id
            && $item['type'] === 'interment_completed'
            && $item['label'] === 'Balance After Interment'
            && $item['message'] === 'Interment has been completed, but no payment has been recorded. Please review the case.'));
        $this->assertTrue($attention->contains(fn ($item) => $item['case_id'] === $approaching->id
            && $item['type'] === 'interment_approaching'
            && $item['label'] === 'Upcoming Interment With Balance'
            && $item['message'] === 'Interment is scheduled tomorrow, and no payment has been recorded. Please review the case.'));
        $this->assertTrue($attention->contains(fn ($item) => $item['case_id'] === $sameDayOne->id && $item['type'] === 'same_day_schedule'));
        $this->assertTrue($attention->contains(fn ($item) => $item['case_id'] === $sameDayTwo->id && $item['type'] === 'same_day_schedule'));
        $this->assertFalse($attention->contains(fn ($item) => $item['case_id'] === $ordinaryBalance->id));
        $this->assertEmpty($attention->whereIn('type', ['balance', 'service_today', 'interment_today', 'upcoming_service', 'upcoming_interment']));
    }

    public function test_reminder_cards_follow_schedule_and_payment_lifecycle(): void
    {
        $now = Carbon::parse('2026-09-26 10:00:00');
        Carbon::setTestNow($now);
        $branch = $this->branch();

        $todayInterment = $this->case($branch, [
            'case_code' => 'CASE-TODAY',
            'funeral_service_at' => '2026-09-25 09:00:00',
            'interment_at' => '2026-09-26 15:00:00',
            'interment_time' => '15:00:00',
            'payment_status' => 'PAID',
            'balance_amount' => 0,
            'total_paid' => 10000,
        ]);
        $futureInterment = $this->case($branch, [
            'case_code' => 'CASE-FUTURE',
            'funeral_service_at' => '2026-09-26 08:00:00',
            'interment_at' => '2026-09-27 09:00:00',
            'interment_time' => '09:00:00',
            'payment_status' => 'PAID',
            'balance_amount' => 0,
            'total_paid' => 10000,
        ]);
        $settledCase = $this->case($branch, [
            'case_code' => 'CASE-SETTLED',
            'funeral_service_at' => '2026-09-20 08:00:00',
            'interment_at' => '2026-09-21 09:00:00',
            'interment_time' => '09:00:00',
            'payment_status' => 'PAID',
            'balance_amount' => 0,
            'total_paid' => 10000,
            'case_status' => 'COMPLETED',
        ]);

        $reminders = app(ReminderService::class)->buildFullList($branch->id, [], $now);

        $this->assertTrue($reminders->contains(fn ($item) => $item['case_id'] === $todayInterment->id && $item['type'] === 'interment_today'));
        $this->assertFalse($reminders->contains(fn ($item) => $item['case_id'] === $todayInterment->id && $item['type'] === 'upcoming_interment'));
        $this->assertTrue($reminders->contains(fn ($item) => $item['case_id'] === $futureInterment->id && $item['type'] === 'upcoming_interment'));
        $this->assertFalse($reminders->contains(fn ($item) => $item['case_id'] === $settledCase->id));
        $this->assertFalse($reminders->contains(fn ($item) => $item['type'] === 'balance' && in_array($item['case_id'], [$todayInterment->id, $futureInterment->id, $settledCase->id], true)));
    }

    public function test_upcoming_schedule_counts_events_and_is_independent_of_payment_and_completion(): void
    {
        $now = Carbon::parse('2026-09-28 10:00:00');
        Carbon::setTestNow($now);
        $branch = $this->branch();

        $twoEvents = $this->case($branch, [
            'case_code' => 'FC0007',
            'funeral_service_at' => '2026-09-30 09:00:00',
            'interment_at' => '2026-09-30 14:00:00',
            'payment_status' => 'PAID',
            'balance_amount' => 0,
            'total_paid' => 10000,
        ]);
        $completedPaid = $this->case($branch, [
            'case_code' => 'COMPLETED-FUTURE',
            'funeral_service_at' => '2026-09-27 09:00:00',
            'interment_at' => '2026-10-01 09:00:00',
            'case_status' => 'COMPLETED',
            'payment_status' => 'PAID',
            'balance_amount' => 0,
            'total_paid' => 10000,
        ]);
        $pastOnly = $this->case($branch, [
            'case_code' => 'PAST-ONLY',
            'funeral_service_at' => '2026-09-26 09:00:00',
            'interment_at' => '2026-09-27 09:00:00',
        ]);

        $upcoming = app(ReminderService::class)
            ->buildFullList($branch->id, [], $now)
            ->whereIn('type', ['upcoming_service', 'upcoming_interment'])
            ->values();

        $this->assertCount(2, $upcoming->where('case_id', $twoEvents->id));
        $this->assertEqualsCanonicalizing(
            ['upcoming_service', 'upcoming_interment'],
            $upcoming->where('case_id', $twoEvents->id)->pluck('type')->all()
        );
        $this->assertCount(1, $upcoming->where('case_id', $completedPaid->id));
        $this->assertSame('upcoming_interment', $upcoming->firstWhere('case_id', $completedPaid->id)['type']);
        $this->assertCount(0, $upcoming->where('case_id', $pastOnly->id));

        $admin = User::factory()->create([
            'role' => 'admin',
            'admin_scope' => 'branch',
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Funeral Ceremony')
            ->assertSee('Interment')
            ->assertViewHas('upcomingSchedule', fn ($items) => $items->where('case_id', $twoEvents->id)->count() === 2
                && $items->contains(fn ($item) => $item['case_id'] === $completedPaid->id
                    && $item['type'] === 'upcoming_interment'));
    }

    public function test_needs_attention_uses_clear_payment_and_schedule_descriptions(): void
    {
        $now = Carbon::parse('2026-09-26 10:00:00');
        Carbon::setTestNow($now);
        $branch = $this->branch();

        $partialToday = $this->case($branch, [
            'case_code' => 'CASE-PARTIAL-TODAY',
            'interment_at' => '2026-09-26 15:00:00',
            'interment_time' => '15:00:00',
            'payment_status' => 'PARTIAL',
            'balance_amount' => 5000,
            'total_paid' => 5000,
        ]);
        $partialCompleted = $this->case($branch, [
            'case_code' => 'CASE-PARTIAL-COMPLETED',
            'interment_at' => '2026-09-26 09:00:00',
            'interment_time' => '09:00:00',
            'payment_status' => 'PARTIAL',
            'balance_amount' => 5000,
            'total_paid' => 5000,
            'case_status' => 'COMPLETED',
        ]);

        $attention = app(ReminderService::class)->buildDashboard($branch->id, $now)['attention'];

        $this->assertTrue($attention->contains(fn ($item) => $item['case_id'] === $partialToday->id
            && $item['message'] === 'Interment is scheduled today, and a remaining balance is recorded. Please review the payment record.'));
        $this->assertTrue($attention->contains(fn ($item) => $item['case_id'] === $partialCompleted->id
            && $item['message'] === 'Interment has been completed, and a remaining balance is still recorded. Please review the payment record.'));
    }

    public function test_current_wake_is_case_based_and_schedule_is_event_based(): void
    {
        $now = Carbon::parse('2026-09-28 10:00:00');
        Carbon::setTestNow($now);
        $branch = $this->branch();
        $activeWake = $this->case($branch, [
            'case_code' => 'ACTIVE-WAKE',
            'wake_start_date' => '2026-09-26',
            'wake_start_time' => '08:00:00',
            'wake_end_date' => '2026-09-30',
            'wake_end_time' => '07:00:00',
            'funeral_service_at' => '2026-09-30',
            'funeral_service_time' => '08:00:00',
            'interment_at' => '2026-09-30 10:00:00',
            'interment_time' => '10:00:00',
        ]);
        $this->case($branch, [
            'case_code' => 'ENDED-WAKE',
            'wake_start_date' => '2026-09-25',
            'wake_start_time' => '08:00:00',
            'wake_end_date' => '2026-09-28',
            'wake_end_time' => '10:00:00',
        ]);

        $service = app(ReminderService::class);
        $current = $service->currentlyInWake($branch->id, $now);
        $events = $service->buildFullList($branch->id, [], $now)
            ->whereIn('type', ['upcoming_wake_end', 'upcoming_service', 'upcoming_interment'])
            ->where('case_id', $activeWake->id)
            ->values();

        $this->assertCount(1, $current);
        $this->assertSame($activeWake->id, $current->first()['case_id']);
        $this->assertSame(3, $current->first()['current_day']);
        $this->assertSame(5, $current->first()['total_days']);
        $this->assertCount(3, $events);
        $this->assertEqualsCanonicalizing(
            ['upcoming_wake_end', 'upcoming_service', 'upcoming_interment'],
            $events->pluck('type')->all()
        );

        $branchAdmin = User::factory()->create([
            'role' => 'admin',
            'admin_scope' => 'branch',
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);

        $this->actingAs($branchAdmin)
            ->get(route('admin.reminders.index', ['tab' => 'current_wake']))
            ->assertOk()
            ->assertSee('Currently in Wake')
            ->assertSee('ACTIVE-WAKE')
            ->assertDontSee('ENDED-WAKE');

        $this->actingAs($branchAdmin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('data-case-entry', false)
            ->assertSee(route('funeral-cases.show', $activeWake), false)
            ->assertSee('focus_section=wake', false)
            ->assertSee('focus_events=', false)
            ->assertSee('View Case Details')
            ->assertDontSee(route('admin.cases.edit', $activeWake), false);
    }

    public function test_admin_notifications_follow_the_selected_or_assigned_branch(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-26 10:00:00'));
        $mainBranch = $this->branch();
        $otherBranch = Branch::create([
            'branch_code' => 'BR002',
            'branch_name' => 'Other Branch',
            'address' => 'Test Address',
            'is_active' => true,
        ]);

        $this->case($mainBranch, ['case_code' => 'MAIN-ALERT']);
        $this->case($otherBranch, ['case_code' => 'OTHER-ALERT']);

        $mainAdmin = User::factory()->create([
            'role' => 'admin',
            'admin_scope' => 'main',
            'branch_id' => $mainBranch->id,
            'is_active' => true,
        ]);
        $branchAdmin = User::factory()->create([
            'role' => 'admin',
            'admin_scope' => 'branch',
            'branch_id' => $otherBranch->id,
            'is_active' => true,
        ]);

        $builder = app(TopbarNotificationBuilder::class);
        $selectedBranchPayload = $builder->forUser($mainAdmin, 'admin', $otherBranch->id);
        $branchAdminPayload = $builder->forUser($branchAdmin, 'admin', $mainBranch->id);

        $this->assertNotEmpty($selectedBranchPayload['items']);
        $this->assertSame(1, $selectedBranchPayload['counts']['all']);
        $this->assertSame(1, $selectedBranchPayload['counts']['with_balance']);
        $this->assertSame([$otherBranch->id], collect($selectedBranchPayload['items'])->pluck('branch_id')->unique()->values()->all());
        $this->assertNotEmpty($branchAdminPayload['items']);
        $this->assertSame(1, $branchAdminPayload['counts']['all']);
        $this->assertSame(1, $branchAdminPayload['counts']['with_balance']);
        $this->assertSame([$otherBranch->id], collect($branchAdminPayload['items'])->pluck('branch_id')->unique()->values()->all());
    }

    public function test_notification_target_case_is_subtly_highlighted_on_reminders_page(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-26 10:00:00'));
        $branch = $this->branch();
        $case = $this->case($branch, ['case_code' => 'FOCUSED-CASE']);
        $admin = User::factory()->create([
            'role' => 'admin',
            'admin_scope' => 'main',
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.reminders.index', [
                'branch_id' => $branch->id,
                'tab' => 'all',
                'focus_case' => $case->id,
            ]))
            ->assertOk()
            ->assertSee('id="case-reminder-'.$case->id.'"', false)
            ->assertSee('class="rp-item rp-item--focused"', false);
    }

    public function test_admin_payment_summary_uses_one_consistent_case_cohort(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-28 10:00:00'));
        $branch = $this->branch();
        $admin = User::factory()->create(['role' => 'admin', 'admin_scope' => 'main', 'branch_id' => $branch->id, 'is_active' => true]);
        $currentCase = $this->case($branch, [
            'case_code' => 'CURRENT-COHORT',
            'total_amount' => 10000,
            'total_paid' => 2000,
            'balance_amount' => 8000,
            'payment_status' => 'PARTIAL',
            'created_at' => now(),
        ]);
        $olderCase = $this->case($branch, [
            'case_code' => 'OLDER-COHORT',
            'total_amount' => 10000,
            'total_paid' => 5000,
            'balance_amount' => 5000,
            'payment_status' => 'PARTIAL',
            'created_at' => now()->subMonth(),
        ]);
        $olderCase->forceFill([
            'created_at' => now()->subMonth(),
            'updated_at' => now()->subMonth(),
        ])->saveQuietly();
        $this->case($branch, [
            'case_code' => 'COMPLETED-UNSETTLED-COHORT',
            'case_status' => 'COMPLETED',
            'total_amount' => 12000,
            'total_paid' => 4000,
            'balance_amount' => 8000,
            'payment_status' => 'PARTIAL',
        ]);
        $this->case($branch, [
            'case_code' => 'COMPLETED-SETTLED-EXCLUDED',
            'case_status' => 'COMPLETED',
            'total_amount' => 10000,
            'total_paid' => 10000,
            'balance_amount' => 0,
            'payment_status' => 'PAID',
        ]);
        $draft = $this->case($branch, [
            'case_code' => 'DRAFT-EXCLUDED',
            'case_status' => 'DRAFT',
            'total_amount' => 9000,
            'total_paid' => 0,
            'balance_amount' => 9000,
            'payment_status' => 'UNPAID',
        ]);
        Payment::create([
            'funeral_case_id' => $olderCase->id,
            'branch_id' => $branch->id,
            'payment_record_no' => 'PAY-COHORT-001',
            'payment_method' => 'cash',
            'payment_mode' => 'cash',
            'amount' => 5000,
            'paid_at' => now(),
            'status' => 'VALID',
        ]);

        $this->actingAs($admin)
            ->get('/admin?branch_id='.$branch->id)
            ->assertOk()
            ->assertViewHas('totalServiceValue', 42000.0)
            ->assertViewHas('summaryCollectedTotal', 21000.0)
            ->assertViewHas('totalOutstanding', 21000.0)
            ->assertViewHas('partialCases', 3)
            ->assertViewHas('paidCases', 1)
            ->assertViewHas('unpaidCases', 0)
            ->assertViewHas('balanceReminders', fn ($items) => ! $items->contains('case_id', $draft->id))
            ->assertViewHas('totalCollected', 5000.0);
    }

    public function test_settled_cases_leave_balance_overview_and_upcoming_is_limited_to_seven_days(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-28 10:00:00'));
        $branch = $this->branch();
        $admin = User::factory()->create(['role' => 'admin', 'admin_scope' => 'main', 'branch_id' => $branch->id, 'is_active' => true]);
        $openBalance = $this->case($branch, ['case_code' => 'OPEN-BALANCE']);
        $settled = $this->case($branch, [
            'case_code' => 'SETTLED-BALANCE',
            'payment_status' => 'PAID',
            'balance_amount' => 0,
            'total_paid' => 10000,
        ]);
        $staleZeroBalance = $this->case($branch, [
            'case_code' => 'STALE-ZERO-BALANCE',
            'payment_status' => 'UNPAID',
            'balance_amount' => 0,
            'total_paid' => 10000,
        ]);
        $nearSchedule = $this->case($branch, [
            'case_code' => 'NEAR-SCHEDULE',
            'funeral_service_at' => now()->addDays(3),
            'interment_at' => now()->addDays(4),
            'payment_status' => 'PAID',
            'balance_amount' => 0,
            'total_paid' => 10000,
        ]);
        $farSchedule = $this->case($branch, [
            'case_code' => 'FAR-SCHEDULE',
            'funeral_service_at' => now()->addDays(10),
            'interment_at' => now()->addDays(11),
            'payment_status' => 'PAID',
            'balance_amount' => 0,
            'total_paid' => 10000,
        ]);

        $this->actingAs($admin)
            ->get('/admin?branch_id='.$branch->id)
            ->assertOk()
            ->assertViewHas('paidCases', 3)
            ->assertViewHas('partialCases', 0)
            ->assertViewHas('unpaidCases', 1)
            ->assertViewHas('balanceReminders', fn ($items) => $items->contains('case_id', $openBalance->id)
                && ! $items->contains('case_id', $settled->id)
                && ! $items->contains('case_id', $staleZeroBalance->id))
            ->assertViewHas('upcomingSchedule', fn ($items) => $items->contains('case_id', $nearSchedule->id)
                && ! $items->contains('case_id', $farSchedule->id));

        $this->actingAs($admin)
            ->get(route('admin.payment-monitoring', [
                'branch_id' => $branch->id,
                'payment_status' => 'WITH_BALANCE',
                'tab' => 'summary',
            ]))
            ->assertOk()
            ->assertSee('OPEN-BALANCE')
            ->assertDontSee('SETTLED-BALANCE')
            ->assertDontSee('STALE-ZERO-BALANCE');
    }

    private function branch(): Branch
    {
        return Branch::create([
            'branch_code' => 'BR001',
            'branch_name' => 'Main Branch',
            'address' => 'Test Address',
            'is_active' => true,
        ]);
    }

    private function case(Branch $branch, array $overrides): FuneralCase
    {
        static $sequence = 0;
        $sequence++;

        $client = Client::create([
            'branch_id' => $branch->id,
            'full_name' => "Client {$sequence}",
            'relationship_to_deceased' => 'Other',
            'contact_number' => '09170000000',
            'address' => 'Test Address',
        ]);
        $deceased = Deceased::create([
            'branch_id' => $branch->id,
            'client_id' => $client->id,
            'full_name' => "Deceased {$sequence}",
            'address' => 'Test Address',
            'died' => '2026-09-20',
            'date_of_death' => '2026-09-20',
            'age' => 70,
            'interment_at' => $overrides['interment_at'] ?? '2026-10-01 09:00:00',
        ]);

        return FuneralCase::create(array_merge([
            'branch_id' => $branch->id,
            'client_id' => $client->id,
            'deceased_id' => $deceased->id,
            'case_code' => "CASE-{$sequence}",
            'service_requested_at' => '2026-09-20',
            'funeral_service_at' => '2026-09-28',
            'interment_at' => '2026-10-01 09:00:00',
            'interment_time' => '09:00:00',
            'wake_location' => 'Family Residence',
            'discount_type' => 'NONE',
            'discount_value_type' => 'AMOUNT',
            'discount_value' => 0,
            'discount_amount' => 0,
            'total_amount' => 10000,
            'total_paid' => 0,
            'balance_amount' => 10000,
            'payment_status' => 'UNPAID',
            'case_status' => 'ACTIVE',
            'reported_branch_id' => $branch->id,
            'reported_at' => now(),
            'entry_source' => 'MAIN',
            'verification_status' => 'VERIFIED',
        ], $overrides));
    }
}

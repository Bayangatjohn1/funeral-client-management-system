<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Client;
use App\Models\Deceased;
use App\Models\FuneralCase;
use App\Models\Payment;
use App\Models\User;
use App\Models\UserNotificationState;
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
            && $item['message'] === 'Scheduled interment time has passed, but no payment has been recorded. Please review the case.'));
        $this->assertTrue($attention->contains(fn ($item) => $item['case_id'] === $approaching->id
            && $item['type'] === 'interment_approaching'
            && $item['label'] === 'Upcoming Interment With Balance'
            && $item['message'] === 'Interment is scheduled tomorrow, and no payment has been recorded. Please review the case.'));
        $this->assertFalse($attention->contains(fn ($item) => $item['case_id'] === $sameDayOne->id && $item['type'] === 'same_day_schedule'));
        $this->assertFalse($attention->contains(fn ($item) => $item['case_id'] === $sameDayTwo->id && $item['type'] === 'same_day_schedule'));
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

    public function test_branch_schedule_drawer_expands_the_wake_dates_between_milestones(): void
    {
        $this->travelTo(Carbon::parse('2026-09-26 10:00:00'));
        $branch = $this->branch();
        $case = $this->case($branch, [
            'wake_start_date' => '2026-09-25',
            'wake_start_time' => '08:00:00',
            'wake_end_date' => '2026-09-28',
            'wake_end_time' => '10:00:00',
            'funeral_service_at' => '2026-09-28',
            'funeral_service_time' => '11:00:00',
        ]);
        $sameDay = $this->case($branch, [
            'wake_start_date' => '2026-09-26',
            'wake_start_time' => '08:00:00',
            'wake_end_date' => '2026-09-26',
            'wake_end_time' => '18:00:00',
        ]);
        $admin = User::factory()->create(['role' => 'admin', 'admin_scope' => 'branch', 'branch_id' => $branch->id, 'is_active' => true]);

        $response = $this->actingAs($admin)->get('/admin')->assertOk()
            ->assertSee('data-wake-date="2026-09-26" data-wake-progress="Day 2 of 4"', false);
        $timeline = str($response->getContent())->after('<template id="branch-schedule-'.$case->id.'">')->before('</template>');
        $singleDayTimeline = str($response->getContent())->after('<template id="branch-schedule-'.$sameDay->id.'">')->before('</template>');
        $this->assertMatchesRegularExpression('/Wake Start.*Wake Day 2.*2026-09-26.*Wake Day 3.*2026-09-27.*Wake End.*Funeral Ceremony.*Interment/s', (string) $timeline);
        $this->assertStringContainsString('4 wake days · 3 nights', (string) $timeline);
        $this->assertStringContainsString('1 wake day · 0 nights', (string) $singleDayTimeline);
        $this->assertStringNotContainsString('Wake Day 2', (string) $singleDayTimeline);
    }

    public function test_branch_balance_drawer_shows_latest_valid_payment_and_interment_context(): void
    {
        $this->travelTo(Carbon::parse('2026-09-26 10:00:00'));
        $branch = $this->branch();
        $case = $this->case($branch, [
            'payment_status' => 'PARTIAL', 'total_paid' => 5250, 'balance_amount' => 4750,
            'interment_at' => '2026-09-25', 'interment_time' => '09:00:00',
        ]);
        $this->case($branch, ['interment_at' => null]);
        $payments = [
            ['amount' => 2000, 'paid_at' => '2026-09-22 12:00:00', 'status' => 'POSTED'],
            ['amount' => 3250, 'paid_date' => '2026-09-24', 'status' => 'VALID'],
            ['amount' => 9000, 'paid_at' => '2026-09-25 12:00:00', 'status' => 'VOIDED'],
        ];
        foreach ($payments as $index => $payment) {
            Payment::create($payment + [
                'funeral_case_id' => $case->id, 'branch_id' => $branch->id,
                'payment_record_no' => 'PAY-BALANCE-'.($index + 1), 'payment_method' => 'cash',
            ]);
        }
        $admin = User::factory()->create(['role' => 'admin', 'admin_scope' => 'branch', 'branch_id' => $branch->id, 'is_active' => true]);

        $this->actingAs($admin)->get('/admin')->assertOk()
            ->assertViewHas('lastBalancePayments', fn ($latest) => $latest->count() === 1 && $latest->get($case->id)->amount === '3250.00')
            ->assertSee('Partially Paid')
            ->assertSee('PHP 3,250.00')
            ->assertSee('Sep 24, 2026')
            ->assertSee('Balance after scheduled interment')
            ->assertSee('No payments recorded')
            ->assertSee('Date not set');
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

        $staff = User::factory()->create(['role' => 'staff', 'branch_id' => $branch->id, 'is_active' => true]);
        $this->actingAs($staff)->get(route('staff.reminders.index', ['tab' => 'warnings']))
            ->assertOk()->assertDontSee('Remaining Balances')->assertSee('View in Payment Monitoring')
            ->assertSee(route('payments.history', ['q' => $partialToday->case_code, 'payment_status' => 'WITH_BALANCE', 'tab' => 'summary']));
        $this->get(route('staff.reminders.index', ['tab' => 'unpaid']))
            ->assertRedirect(route('payments.history', ['tab' => 'summary', 'payment_status' => 'WITH_BALANCE', 'branch_id' => $branch->id]));

        $attention = app(ReminderService::class)->buildDashboard($branch->id, $now)['attention'];

        $this->assertTrue($attention->contains(fn ($item) => $item['case_id'] === $partialToday->id
            && $item['message'] === 'Interment is scheduled today, and a remaining balance is recorded. Please review the payment record.'));
        $this->assertTrue($attention->contains(fn ($item) => $item['case_id'] === $partialCompleted->id
            && $item['message'] === 'Scheduled interment time has passed, and a remaining balance is still recorded. Please review the payment record.'));
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
            ->assertSee('Ongoing Wakes')
            ->assertViewHas('calendarWakes', fn ($items) => $items->count() === 1 && $items->first()['case_id'] === $activeWake->id)
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
            ->assertViewHas('focusedCaseId', $case->id)
            ->assertViewHas('calendarEvents', fn ($items) => $items->contains('case_id', $case->id))
            ->assertSee('ceremony is-focused', false)
            ->assertSee('data-calendar-event="event-'.$case->id.'-service_today"', false);
    }

    public function test_notification_focus_cannot_expose_another_branch_case(): void
    {
        Carbon::setTestNow('2026-09-26 10:00:00');
        $own = $this->branch();
        $other = Branch::create(['branch_code' => 'BR002', 'branch_name' => 'Other', 'is_active' => true]);
        $case = $this->case($other, ['case_code' => 'FOREIGN-FOCUS']);
        $staff = User::factory()->create(['role' => 'staff', 'branch_id' => $own->id, 'is_active' => true]);
        $this->actingAs($staff)->get(route('staff.reminders.index', ['tab' => 'all', 'focus_case' => $case->id, 'branch_id' => $other->id]))
            ->assertOk()->assertDontSee('FOREIGN-FOCUS')
            ->assertViewHas('modalCases', fn ($items) => $items->isEmpty())
            ->assertViewHas('calendarEvents', fn ($items) => $items->isEmpty());
    }

    public function test_admin_payment_summary_uses_one_consistent_case_cohort(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-28 10:00:00'));
        $branch = $this->branch();
        $admin = User::factory()->create(['role' => 'admin', 'admin_scope' => 'branch', 'branch_id' => $branch->id, 'is_active' => true]);
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
        $admin = User::factory()->create(['role' => 'admin', 'admin_scope' => 'branch', 'branch_id' => $branch->id, 'is_active' => true]);
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

    public function test_timeline_uses_saved_times_excludes_drafts_and_groups_cross_event_schedules(): void
    {
        Carbon::setTestNow('2026-10-04 10:00:00');
        $branch = $this->branch();
        $one = $this->case($branch, ['case_code' => 'TIMELINE-ONE', 'funeral_service_at' => '2026-10-09', 'funeral_service_time' => '09:30:00', 'interment_at' => '2026-10-09', 'interment_time' => '10:30:00']);
        $two = $this->case($branch, ['case_code' => 'TIMELINE-TWO', 'funeral_service_at' => '2026-10-08', 'funeral_service_time' => '08:30:00', 'interment_at' => '2026-10-09', 'interment_time' => '09:30:00']);
        $draft = $this->case($branch, ['case_code' => 'TIMELINE-DRAFT', 'case_status' => 'DRAFT', 'funeral_service_at' => '2026-10-09', 'funeral_service_time' => '09:30:00']);
        $groups = app(ReminderService::class)->scheduleGroups($branch->id);
        $day = $groups->firstWhere('date', '2026-10-09');
        $this->assertSame(2, $day['case_count']);
        $this->assertCount(3, $day['events']);
        $this->assertSame(['09:30', '09:30', '10:30'], $day['events']->pluck('date')->map(fn ($date) => $date->format('H:i'))->all());
        $this->assertFalse($groups->flatMap(fn ($group) => $group['events'])->contains('case_id', $draft->id));
        $this->assertTrue($day['events']->every(fn ($event) => $event['has_time']));
        $staff = User::factory()->create(['role' => 'staff', 'branch_id' => $branch->id, 'is_active' => true]);
        $this->actingAs($staff)->get(route('staff.reminders.index', ['tab' => 'schedules', 'schedule_section' => 'upcoming', 'calendar_date' => '2026-10-09']))
            ->assertOk()->assertSee('Same time')->assertSee('9:30 AM')->assertSee('10:30 AM')->assertDontSee('TIMELINE-DRAFT');
        $this->get('/staff')->assertOk()->assertSee('Schedules')->assertSee('View All Schedules')
            ->assertViewHas('attentionReminders', fn ($items) => ! $items->contains('type', 'same_day_schedule'));
        $this->get(route('staff.reminders.index', ['tab' => 'warnings']))->assertOk()->assertDontSee('TIMELINE-ONE')->assertDontSee('TIMELINE-TWO');
        $this->get(route('staff.reminders.index', ['tab' => 'schedules', 'schedule_section' => 'upcoming', 'calendar_date' => '2026-10-09']))->assertOk()->assertSee('data-calendar-event', false)->assertDontSee('also scheduled with:');
    }

    public function test_calendar_includes_past_events_and_wakes_overlapping_the_week(): void
    {
        Carbon::setTestNow('2026-10-04 14:00:00');
        $branch = $this->branch();
        $past = $this->case($branch, ['case_code' => 'CALENDAR-PAST', 'funeral_service_at' => '2026-09-23', 'funeral_service_time' => '07:30', 'interment_at' => '2026-09-23', 'interment_time' => '08:30', 'wake_start_date' => '2026-09-19', 'wake_start_time' => '08:00', 'wake_end_date' => '2026-09-24', 'wake_end_time' => '09:00', 'case_status' => 'COMPLETED', 'payment_status' => 'PAID', 'balance_amount' => 0]);
        $draft = $this->case($branch, ['case_code' => 'CALENDAR-DRAFT', 'case_status' => 'DRAFT', 'funeral_service_at' => '2026-09-23']);
        $other = Branch::create(['branch_code' => 'BR002', 'branch_name' => 'Other', 'is_active' => true]);
        $this->case($other, ['case_code' => 'CALENDAR-OTHER', 'funeral_service_at' => '2026-09-23']);
        $data = app(ReminderService::class)->calendarData($branch->id, Carbon::parse('2026-09-21'), Carbon::parse('2026-09-27')->endOfDay());
        $this->assertSame(['CALENDAR-PAST'], $data['events']->pluck('case_code')->unique()->values()->all());
        $this->assertCount(1, $data['wakes']);
        $this->assertFalse($data['wakes']->first()['active']);
        $this->assertSame('07:30', $data['events']->firstWhere('type', 'service_today')['date']->format('H:i'));
        $staff = User::factory()->create(['role' => 'staff', 'branch_id' => $branch->id, 'is_active' => true]);
        $this->actingAs($staff)->get(route('staff.reminders.index', ['calendar_date' => '2026-09-23']))
            ->assertOk()->assertViewHas('activeTab', 'schedules')->assertViewHas('scheduleSection', 'today')->assertDontSee('CALENDAR-PAST')->assertDontSee('CALENDAR-DRAFT')->assertDontSee('CALENDAR-OTHER');
        $this->get(route('staff.reminders.index', ['calendar_date' => '2026-09-23', 'case_status' => 'ACTIVE']))->assertOk()->assertDontSee('CALENDAR-PAST');
        $this->get(route('staff.reminders.index', ['tab' => 'today']))->assertOk()->assertViewHas('activeTab', 'schedules');
        $this->get(route('staff.reminders.index', ['calendar_date' => '2026-02-31']))->assertSessionHasErrors('calendar_date');
    }

    public function test_schedule_sections_separate_ongoing_wakes_today_and_future_events(): void
    {
        Carbon::setTestNow('2026-10-04 14:00:00');
        $branch = $this->branch();
        $wake = $this->case($branch, ['case_code' => 'ONGOING-ONLY', 'wake_start_date' => '2026-10-03', 'wake_start_time' => '08:00', 'wake_end_date' => '2026-10-06', 'wake_end_time' => '08:00']);
        $today = $this->case($branch, ['case_code' => 'TODAY-ONLY', 'funeral_service_at' => '2026-10-04', 'funeral_service_time' => '09:30']);
        $future = $this->case($branch, ['case_code' => 'FUTURE-ONLY', 'funeral_service_at' => '2026-10-05', 'funeral_service_time' => '10:30']);
        $staff = User::factory()->create(['role' => 'staff', 'branch_id' => $branch->id, 'is_active' => true]);
        $this->actingAs($staff)->get(route('staff.reminders.index'))->assertOk()
            ->assertViewHas('scheduleSection', 'today')->assertViewHas('calendarView', 'day')
            ->assertSee('TODAY-ONLY')->assertDontSee('ONGOING-ONLY')->assertDontSee('FUTURE-ONLY')
            ->assertSee('Wake starts and ends, funeral ceremonies, and interments scheduled today.');
        $this->get(route('staff.reminders.index', ['schedule_section' => 'ongoing']))->assertOk()
            ->assertSee('ONGOING-ONLY')->assertDontSee('TODAY-ONLY')->assertDontSee('FUTURE-ONLY')
            ->assertSee('Day 2 of 4')->assertSee('Cases with a wake period currently in progress.');
        $this->get(route('staff.reminders.index', ['schedule_section' => 'upcoming']))->assertOk()
            ->assertSee('FUTURE-ONLY')->assertDontSee('TODAY-ONLY')->assertSee('ONGOING-ONLY')
            ->assertViewHas('calendarWakes', fn ($items) => $items->isEmpty())
            ->assertViewHas('calendarEvents', fn ($items) => $items->every(fn ($event) => $event['date']->toDateString() > '2026-10-04'))
            ->assertSee('Wake starts and ends, funeral ceremonies, and interments scheduled after today.');
        $this->get(route('staff.reminders.index', ['schedule_section' => 'upcoming', 'calendar_date' => '2026-09-23']))->assertOk()->assertViewHas('calendarDate', fn ($date) => $date->toDateString() === '2026-10-05');
    }

    public function test_complete_case_schedule_search_and_by_case_are_branch_scoped(): void
    {
        Carbon::setTestNow('2026-10-04 14:00:00');
        $branch = $this->branch();
        $case = $this->case($branch, ['case_code' => 'COMPLETE-SCHED', 'wake_start_date' => '2026-10-05', 'wake_start_time' => '08:00', 'wake_end_date' => '2026-10-25', 'wake_end_time' => '08:30', 'funeral_service_at' => '2026-10-26', 'funeral_service_time' => '09:00', 'interment_at' => '2026-10-27', 'interment_time' => '10:30']);
        $case->deceased->update(['first_name' => 'Grace', 'middle_name' => null, 'last_name' => 'Santos', 'suffix' => null]);
        $this->case($branch, ['case_code' => 'OTHER-SCHED', 'funeral_service_at' => '2026-10-06', 'funeral_service_time' => '09:00']);
        $this->case($branch, ['case_code' => 'DRAFT-SCHED', 'case_status' => 'DRAFT', 'funeral_service_at' => '2026-10-06']);
        $deleted = $this->case($branch, ['case_code' => 'DELETED-SCHED', 'funeral_service_at' => '2026-10-06']);
        $deleted->delete();
        $other = Branch::create(['branch_code' => 'BR002', 'branch_name' => 'Other', 'is_active' => true]);
        $this->case($other, ['case_code' => 'OUTSIDE-SCHED', 'funeral_service_at' => '2026-10-06']);
        $staff = User::factory()->create(['role' => 'staff', 'branch_id' => $branch->id, 'is_active' => true]);
        $query = ['schedule_section' => 'upcoming', 'calendar_date' => '2026-10-05', 'schedule_q' => 'complete'];
        $this->actingAs($staff)->get(route('staff.reminders.index', $query))->assertOk()
            ->assertViewHas('calendarEvents', fn ($events) => $events->count() === 1 && $events->first()['label'] === 'Wake Begins')
            ->assertViewHas('modalCases', fn ($cases) => $cases->count() === 1 && $cases->first()['events']->count() === 4)
            ->assertSee('Case Schedule')->assertSee('Oct 25, 2026')->assertSee('Oct 26, 2026')->assertSee('Oct 27, 2026')->assertSee('10:30 AM')
            ->assertDontSee('OTHER-SCHED')->assertDontSee('OUTSIDE-SCHED')->assertDontSee('DRAFT-SCHED')->assertDontSee('DELETED-SCHED');
        $this->get(route('staff.reminders.index', array_merge($query, ['upcoming_view' => 'cases', 'calendar_date' => '2026-12-01', 'schedule_q' => 'grace'])))
            ->assertOk()->assertViewHas('calendarEvents', fn ($events) => $events->isEmpty())
            ->assertViewHas('upcomingCases', fn ($cases) => $cases->count() === 1 && $cases->first()['case']->id === $case->id)
            ->assertSee('Grace Santos')->assertSee('View Case Schedule');
        $this->get(route('staff.reminders.index', array_merge($query, ['schedule_q' => 'unknown'])))
            ->assertOk()->assertSee('No matching upcoming cases')->assertViewHas('upcomingCases', fn ($cases) => $cases->isEmpty());
        $this->get(route('staff.reminders.index', ['schedule_q' => str_repeat('x', 121)]))->assertSessionHasErrors('schedule_q');
    }

    public function test_dashboard_paginates_balances_and_counts_all_past_interments_with_balance(): void
    {
        Carbon::setTestNow('2026-10-10 10:00:00');
        $branch = $this->branch();
        $staff = User::factory()->create(['role' => 'staff', 'branch_id' => $branch->id, 'is_active' => true]);
        $past = $this->case($branch, ['case_code' => 'OVERDUE-BALANCE', 'case_status' => 'COMPLETED']);
        foreach (range(1, 8) as $number) {
            $this->case($branch, ['case_code' => 'OVERDUE-'.$number]);
        }
        $wake = $this->case($branch, [
            'case_code' => 'IN-WAKE', 'wake_start_date' => '2026-10-09', 'wake_end_date' => '2026-10-11',
            'interment_at' => '2026-10-11 09:00:00',
        ]);
        $other = Branch::create(['branch_code' => 'BR002', 'branch_name' => 'Other', 'is_active' => true]);
        $this->case($other, ['case_code' => 'OTHER-BRANCH-BALANCE']);

        $this->actingAs($staff)->get('/staff')->assertOk()
            ->assertViewHas('unpaidCases', fn ($page) => $page->count() === 5 && $page->total() === 10)
            ->assertViewHas('attentionReminders', fn ($items) => $items->count() === 9 && !$items->contains('case_id', $wake->id))
            ->assertSeeInOrder(['Deceased Name', 'Case ID', 'Case Status', 'Balance', 'Action'])
            ->assertSee('class="staff-balance-status" data-case-status="ACTIVE">Active', false)
            ->assertDontSee('staff-balance-stage', false)
            ->assertSee('Showing 1–5 of 10 cases')->assertSee('9 Cases')
            ->assertDontSee('OTHER-BRANCH-BALANCE');

        $this->get('/staff?balances_page=2')->assertOk()
            ->assertViewHas('unpaidCases', fn ($page) => $page->getCollection()->contains('id', $past->id))
            ->assertSee('class="staff-balance-status" data-case-status="COMPLETED">Completed', false)
            ->assertSee('Showing 6–10 of 10 cases');

        $this->get('/staff?balances_page=2', ['X-Staff-Dashboard-Fragment' => 'balances'])->assertOk()
            ->assertHeader('X-Staff-Dashboard-Fragment', 'balances')
            ->assertViewIs('staff.partials.dashboard-outstanding-balances')
            ->assertSee('Showing 6–10 of 10 cases')
            ->assertDontSee('Needs Attention')
            ->assertDontSee('OTHER-BRANCH-BALANCE');

        $past->update(['payment_status' => 'PAID', 'total_paid' => 10000, 'balance_amount' => 0]);
        $this->get('/staff?balances_page=2')->assertOk()
            ->assertViewHas('attentionReminders', fn ($items) => $items->count() === 8)
            ->assertViewHas('unpaidCases', fn ($page) => !$page->getCollection()->contains('id', $past->id))
            ->assertSee('Showing 6–9 of 9 cases')->assertDontSee('Record Payment for OVERDUE-BALANCE');
    }

    public function test_dashboard_payment_suggestions_only_offer_recordable_branch_cases(): void
    {
        $branch = $this->branch();
        $other = Branch::create(['branch_code' => 'BR002', 'branch_name' => 'Other', 'is_active' => true]);
        $staff = User::factory()->create(['role' => 'staff', 'branch_id' => $branch->id, 'is_active' => true]);
        $case = $this->case($branch, ['case_code' => 'FAST-10023', 'payment_status' => 'PARTIAL', 'total_paid' => 2500, 'balance_amount' => 7500]);
        $case->client->update(['first_name' => 'Juan', 'middle_name' => null, 'last_name' => 'Dela Cruz']);
        $case->deceased->update(['first_name' => 'Ana', 'middle_name' => null, 'last_name' => 'Santos']);
        $this->case($branch, ['case_code' => 'FAST-PAID', 'payment_status' => 'PAID', 'total_paid' => 10000, 'balance_amount' => 0]);
        $this->case($branch, ['case_code' => 'FAST-EXTERNAL', 'entry_source' => 'OTHER_BRANCH']);
        $this->case($other, ['case_code' => 'FAST-PRIVATE']);

        $this->actingAs($staff);
        $target = route('payments.history', ['case_id' => $case->id, 'record_payment' => 1, 'dashboard_search' => 1]);
        foreach (['fast', '10023', 'dela cruz', 'santos'] as $term) {
            $this->getJson(route('payments.recordable-cases', ['q' => $term]))
                ->assertOk()->assertExactJson([[
                    'caseCode' => 'FAST-10023',
                    'client' => 'Juan Dela Cruz',
                    'deceased' => 'Ana Santos',
                    'url' => $target,
                ]]);
        }
        $this->getJson(route('payments.recordable-cases', ['q' => '%%']))->assertOk()->assertExactJson([]);
        $this->get($target)->assertOk()
            ->assertViewHas('autoOpenPayment', true)
            ->assertViewHas('fromDashboardSearch', true)
            ->assertViewHas('preselectCase', fn (FuneralCase $selected) => $selected->is($case))
            ->assertSee('id="pf_selected_deceased"', false)
            ->assertSee('data-balance="7500.00"', false)
            ->assertSee('data-deceased="Ana Santos"', false);

        $case->update(['payment_status' => 'PAID', 'total_paid' => 10000, 'balance_amount' => 0]);
        $this->get($target)->assertRedirect(route('payments.history'))->assertSessionHas('warning');

        $admin = User::factory()->create(['role' => 'admin', 'branch_id' => $branch->id, 'is_active' => true]);
        $this->actingAs($admin)->getJson(route('payments.recordable-cases', ['q' => 'fast']))->assertForbidden();
    }

    public function test_notification_read_and_dismiss_states_persist_per_user(): void
    {
        Carbon::setTestNow('2026-09-26 10:00:00');
        $branch = $this->branch();
        $this->case($branch, [
            'case_code' => 'NOTICE-STATE',
            'interment_at' => '2026-09-27 09:00:00',
            'interment_time' => '09:00:00',
        ]);
        $staff = User::factory()->create(['role' => 'staff', 'branch_id' => $branch->id, 'is_active' => true]);
        $builder = app(TopbarNotificationBuilder::class);

        $initial = $builder->forUser($staff, 'staff', $branch->id);
        $this->assertSame(1, $initial['unread']);
        $key = $initial['items'][0]['key'];

        $this->actingAs($staff)->postJson(route('notifications.read-all'), ['branch_id' => $branch->id])
            ->assertOk()->assertJsonPath('unread', 0);
        $this->assertTrue($builder->forUser($staff, 'staff', $branch->id)['items'][0]['read']);

        $this->postJson(route('notifications.state'), [
            'key' => $key,
            'action' => 'dismiss',
            'branch_id' => $branch->id,
        ])->assertOk()->assertJsonPath('unread', 0);

        $this->assertSame([], $builder->forUser($staff, 'staff', $branch->id)['items']);
        $state = UserNotificationState::where('user_id', $staff->id)->where('notification_key', $key)->firstOrFail();
        $this->assertNotNull($state->read_at);
        $this->assertNotNull($state->dismissed_at);

        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);
        $this->actingAs($owner)->postJson(route('notifications.read-all'))->assertForbidden();
    }

    public function test_notification_order_prioritizes_actionable_and_nearest_events(): void
    {
        Carbon::setTestNow('2026-09-26 10:00:00');
        $branch = $this->branch();
        $this->case($branch, ['case_code' => 'URGENT', 'funeral_service_at' => '2026-09-24', 'interment_at' => '2026-09-27 09:00:00']);
        $this->case($branch, ['case_code' => 'TODAY', 'funeral_service_at' => '2026-09-26', 'interment_at' => '2026-10-12 09:00:00']);
        $this->case($branch, ['case_code' => 'UPCOMING', 'funeral_service_at' => '2026-09-28', 'interment_at' => '2026-10-13 09:00:00']);
        $this->case($branch, ['case_code' => 'BALANCE', 'funeral_service_at' => '2026-10-18', 'interment_at' => '2026-10-20 09:00:00']);
        $staff = User::factory()->create(['role' => 'staff', 'branch_id' => $branch->id, 'is_active' => true]);

        $items = collect(app(TopbarNotificationBuilder::class)->forUser($staff, 'staff', $branch->id)['items']);

        $this->assertSame(['URGENT', 'TODAY', 'UPCOMING', 'BALANCE'], $items->pluck('case_code')->all());
        $this->assertSame(['needs_attention', 'today', 'upcoming', 'with_balance'], $items->pluck('bucket')->all());
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

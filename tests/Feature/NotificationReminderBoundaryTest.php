<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Client;
use App\Models\Deceased;
use App\Models\FuneralCase;
use App\Services\ReminderService;
use App\Support\TopbarNotificationCache;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class NotificationReminderBoundaryTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_recorded_interment_remains_approaching_at_its_exact_second_then_becomes_after_interment(): void
    {
        $branch = $this->branch();
        $case = $this->case($branch, [
            'interment_at' => '2026-10-26 12:00:00',
            'interment_time' => '12:00:00',
        ]);
        $service = app(ReminderService::class);

        Carbon::setTestNow('2026-10-26 11:59:59');
        $this->assertSame('interment_approaching', $service->buildDashboard($branch->id, now())['attention']->firstWhere('case_id', $case->id)['type']);

        Carbon::setTestNow('2026-10-26 12:00:00');
        $this->assertSame('interment_approaching', $service->buildDashboard($branch->id, now())['attention']->firstWhere('case_id', $case->id)['type']);

        Carbon::setTestNow('2026-10-26 12:00:01');
        $this->assertSame('interment_completed', $service->buildDashboard($branch->id, now())['attention']->firstWhere('case_id', $case->id)['type']);
    }

    public function test_date_only_interment_never_uses_midnight_as_a_real_event_time(): void
    {
        $branch = $this->branch();
        $case = $this->case($branch, ['interment_at' => '2026-10-26', 'interment_time' => null]);
        $service = app(ReminderService::class);

        Carbon::setTestNow('2026-10-26 18:00:00');
        $today = $service->buildDashboard($branch->id, now())['attention']->firstWhere('case_id', $case->id);
        $this->assertSame('interment_date_today_balance', $today['type']);
        $this->assertFalse($today['has_time']);

        Carbon::setTestNow('2026-10-27 00:00:00');
        $this->assertSame('interment_date_completed', $service->buildDashboard($branch->id, now())['attention']->firstWhere('case_id', $case->id)['type']);
    }

    public function test_same_day_events_sort_timed_then_untimed_then_event_kind_and_case_id(): void
    {
        $branch = $this->branch();
        $service = app(ReminderService::class);
        $date = Carbon::parse('2026-10-26');
        $items = collect([
            ['case_id' => 3, 'type' => 'interment_today', 'event_kind' => 'interment', 'severity_rank' => 2, 'has_time' => false, 'date' => $date, 'sort_date' => $date],
            ['case_id' => 2, 'type' => 'service_today', 'event_kind' => 'service', 'severity_rank' => 2, 'has_time' => true, 'date' => $date->copy()->setTime(10, 0), 'sort_date' => $date->copy()->setTime(10, 0)],
            ['case_id' => 1, 'type' => 'wake_start_today', 'event_kind' => 'wake_start', 'severity_rank' => 2, 'has_time' => true, 'date' => $date->copy()->setTime(9, 0), 'sort_date' => $date->copy()->setTime(9, 0)],
            ['case_id' => 4, 'type' => 'wake_end_today', 'event_kind' => 'wake_end', 'severity_rank' => 2, 'has_time' => false, 'date' => $date, 'sort_date' => $date],
        ]);

        $this->assertSame([1, 2, 4, 3], $service->sortReminders($items)->pluck('case_id')->all());
    }

    public function test_notification_cache_expiry_crosses_the_first_second_after_recorded_interment(): void
    {
        $branch = $this->branch();
        $this->case($branch, ['interment_at' => '2026-10-26 12:00:00', 'interment_time' => '12:00:00']);
        $now = Carbon::parse('2026-10-26 12:00:00');

        $this->assertSame('2026-10-26 12:00:01', app(ReminderService::class)
            ->nextNotificationTransitionAt([$branch->id], $now)->format('Y-m-d H:i:s'));
    }

    public function test_rolled_back_mutation_does_not_invalidate_notification_scope(): void
    {
        $branch = $this->branch();
        $key = 'topbar:notifications:branch:'.$branch->id.':version';
        Cache::forever($key, 7);

        try {
            DB::transaction(function () use ($branch): void {
                TopbarNotificationCache::invalidateAfterCommit([$branch->id]);
                throw new \RuntimeException('rollback');
            });
        } catch (\RuntimeException) {
            // Expected: the cache callback must not run.
        }

        $this->assertSame(7, Cache::get($key));
    }

    private function branch(): Branch
    {
        return Branch::create(['branch_code' => 'BR001', 'branch_name' => 'Main Branch', 'is_active' => true]);
    }

    private function case(Branch $branch, array $overrides): FuneralCase
    {
        $client = Client::create(['branch_id' => $branch->id, 'full_name' => 'Client', 'relationship_to_deceased' => 'Other', 'contact_number' => '09170000000', 'address' => 'Address']);
        $deceased = Deceased::create(['branch_id' => $branch->id, 'client_id' => $client->id, 'full_name' => 'Deceased', 'address' => 'Address', 'died' => '2026-10-20', 'date_of_death' => '2026-10-20', 'age' => 70]);

        return FuneralCase::create(array_merge([
            'branch_id' => $branch->id, 'client_id' => $client->id, 'deceased_id' => $deceased->id,
            'case_code' => 'FC'.str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT),
            'service_requested_at' => '2026-10-20',
            'wake_location' => 'Family Residence',
            'funeral_service_at' => '2026-10-25',
            'entry_source' => 'MAIN', 'verification_status' => 'VERIFIED', 'case_status' => 'ACTIVE',
            'payment_status' => 'UNPAID', 'total_amount' => 10000, 'total_paid' => 0, 'balance_amount' => 10000,
            'interment_at' => '2026-10-26 12:00:00', 'interment_time' => '12:00:00',
        ], $overrides));
    }
}

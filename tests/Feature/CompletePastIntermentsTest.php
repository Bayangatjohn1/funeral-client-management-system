<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Client;
use App\Models\Deceased;
use App\Models\FuneralCase;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompletePastIntermentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_case_with_past_interment_datetime_becomes_completed(): void
    {
        $this->travelTo(Carbon::parse('2026-04-30 14:01:00'));

        $case = $this->createCase([
            'case_status' => 'ACTIVE',
            'interment_at' => '2026-04-30 14:00:00',
        ]);

        $this->assertSame(1, FuneralCase::completePastInterments());

        $case->refresh();
        $this->assertSame('COMPLETED', $case->case_status);
    }

    public function test_active_case_with_todays_future_interment_time_stays_active(): void
    {
        $this->travelTo(Carbon::parse('2026-04-30 10:00:00'));

        $case = $this->createCase([
            'case_status' => 'ACTIVE',
            'interment_at' => '2026-04-30 14:00:00',
        ]);

        $this->assertSame(0, FuneralCase::completePastInterments());

        $this->assertSame('ACTIVE', $case->refresh()->case_status);
    }

    public function test_active_case_with_todays_past_interment_time_becomes_completed(): void
    {
        $this->travelTo(Carbon::parse('2026-04-30 14:01:00'));

        $case = $this->createCase([
            'case_status' => 'ACTIVE',
            'interment_at' => '2026-04-30 13:59:00',
        ]);

        $this->assertSame(1, FuneralCase::completePastInterments());

        $this->assertSame('COMPLETED', $case->refresh()->case_status);
    }

    public function test_active_case_with_future_interment_datetime_stays_active(): void
    {
        $this->travelTo(Carbon::parse('2026-04-30 14:01:00'));

        $case = $this->createCase([
            'case_status' => 'ACTIVE',
            'interment_at' => '2026-05-01 09:00:00',
        ]);

        $this->assertSame(0, FuneralCase::completePastInterments());

        $this->assertSame('ACTIVE', $case->refresh()->case_status);
    }

    public function test_draft_case_with_past_interment_datetime_stays_draft(): void
    {
        $this->travelTo(Carbon::parse('2026-04-30 14:01:00'));

        $case = $this->createCase([
            'case_status' => 'DRAFT',
            'interment_at' => '2026-04-30 13:00:00',
        ]);

        $this->assertSame(0, FuneralCase::completePastInterments());

        $this->assertSame('DRAFT', $case->refresh()->case_status);
    }

    public function test_cancelled_case_with_past_interment_datetime_stays_cancelled_when_supported(): void
    {
        $this->travelTo(Carbon::parse('2026-04-30 14:01:00'));

        $case = $this->createCase([
            'case_status' => 'CANCELLED',
            'interment_at' => '2026-04-30 13:00:00',
        ]);

        $this->assertSame(0, FuneralCase::completePastInterments());

        $this->assertSame('CANCELLED', $case->refresh()->case_status);
    }

    public function test_completed_case_stays_completed(): void
    {
        $this->travelTo(Carbon::parse('2026-04-30 14:01:00'));

        $case = $this->createCase([
            'case_status' => 'COMPLETED',
            'interment_at' => '2026-04-30 13:00:00',
        ]);

        $this->assertSame(0, FuneralCase::completePastInterments());

        $this->assertSame('COMPLETED', $case->refresh()->case_status);
    }

    public function test_case_with_missing_interment_time_does_not_auto_complete(): void
    {
        $this->travelTo(Carbon::parse('2026-04-30 14:01:00'));

        $case = $this->createCase([
            'case_status' => 'ACTIVE',
            'interment_at' => '2026-04-30 00:00:00',
        ]);

        $this->assertSame(0, FuneralCase::completePastInterments());

        $this->assertSame('ACTIVE', $case->refresh()->case_status);
    }

    public function test_payment_status_and_amounts_remain_unchanged(): void
    {
        $this->travelTo(Carbon::parse('2026-04-30 14:01:00'));

        $case = $this->createCase([
            'case_status' => 'ACTIVE',
            'payment_status' => 'PARTIAL',
            'total_amount' => 10000,
            'total_paid' => 2500,
            'balance_amount' => 7500,
            'interment_at' => '2026-04-30 13:00:00',
        ]);

        $this->assertSame(1, FuneralCase::completePastInterments());

        $case->refresh();
        $this->assertSame('COMPLETED', $case->case_status);
        $this->assertSame('PARTIAL', $case->payment_status);
        $this->assertSame('2500.00', (string) $case->total_paid);
        $this->assertSame('7500.00', (string) $case->balance_amount);
    }

    public function test_artisan_command_completes_past_interments(): void
    {
        $this->travelTo(Carbon::parse('2026-04-30 14:01:00'));

        $case = $this->createCase([
            'case_status' => 'ACTIVE',
            'interment_at' => '2026-04-30 13:00:00',
        ]);

        $this->artisan('cases:complete-past-interments')
            ->expectsOutput('Completed 1 past interment case(s).')
            ->assertSuccessful();

        $this->assertSame('COMPLETED', $case->refresh()->case_status);
    }

    private function createCase(array $overrides = []): FuneralCase
    {
        static $sequence = 0;
        $sequence++;

        $branch = Branch::create([
            'branch_code' => 'BR' . str_pad((string) $sequence, 3, '0', STR_PAD_LEFT),
            'branch_name' => 'Branch ' . $sequence,
            'branch_type' => $sequence === 1 ? 'main' : 'branch',
            'is_active' => true,
        ]);

        $client = Client::create([
            'branch_id' => $branch->id,
            'full_name' => 'Client ' . $sequence,
            'relationship_to_deceased' => 'Other',
            'contact_number' => '09170000000',
            'address' => 'Client Address',
        ]);

        $deceased = Deceased::create([
            'branch_id' => $branch->id,
            'client_id' => $client->id,
            'full_name' => 'Deceased ' . $sequence,
            'address' => 'Client Address',
            'died' => '2026-04-28',
            'date_of_death' => '2026-04-28',
            'age' => 60,
            'interment_at' => $overrides['interment_at'] ?? '2026-05-01 09:00:00',
        ]);

        return FuneralCase::create(array_merge([
            'branch_id' => $branch->id,
            'client_id' => $client->id,
            'deceased_id' => $deceased->id,
            'case_code' => 'FC' . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT),
            'service_requested_at' => '2026-04-28',
            'funeral_service_at' => '2026-04-29',
            'interment_at' => '2026-05-01 09:00:00',
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

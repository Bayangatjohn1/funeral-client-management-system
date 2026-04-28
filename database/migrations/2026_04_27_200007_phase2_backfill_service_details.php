<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Carbon;

/**
 * Backfills the service_details table from funeral_cases and deceased records.
 *
 * Column mapping:
 *   service_details.start_of_wake   ← funeral_cases.service_requested_at
 *   service_details.internment_date ← funeral_cases.interment_at (date part)
 *                                     fallback: deceased.interment
 *   service_details.wake_days       ← deceased.wake_days
 *   service_details.wake_location   ← funeral_cases.wake_location
 *   service_details.cemetery_place  ← deceased.place_of_cemetery
 *   service_details.case_status     ← mapped from funeral_cases.case_status
 *                                     DRAFT → pending
 *                                     ACTIVE → ongoing
 *                                     COMPLETED → completed
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('service_details')) {
            return;
        }

        DB::table('funeral_cases')
            ->join('deceased', 'funeral_cases.deceased_id', '=', 'deceased.id')
            ->select([
                'funeral_cases.id        as fc_id',
                'funeral_cases.service_requested_at',
                'funeral_cases.interment_at',
                'funeral_cases.wake_location',
                'funeral_cases.case_status',
                'deceased.wake_days',
                'deceased.interment      as deceased_interment',
                'deceased.place_of_cemetery',
            ])
            ->orderBy('funeral_cases.id')
            ->chunk(200, function ($rows) {
                $now = now();

                foreach ($rows as $row) {
                    // Skip if already seeded.
                    if (DB::table('service_details')->where('funeral_case_id', $row->fc_id)->exists()) {
                        continue;
                    }

                    // Resolve internment_date (prefer funeral_cases.interment_at, fall back to deceased.interment).
                    $internmentDate = null;
                    if (! empty($row->interment_at)) {
                        try {
                            $internmentDate = Carbon::parse($row->interment_at)->toDateString();
                        } catch (\Throwable) {}
                    }

                    if (! $internmentDate && ! empty($row->deceased_interment)) {
                        try {
                            $internmentDate = Carbon::parse($row->deceased_interment)->toDateString();
                        } catch (\Throwable) {}
                    }

                    DB::table('service_details')->insert([
                        'funeral_case_id'  => $row->fc_id,
                        'start_of_wake'    => $row->service_requested_at ?? null,
                        'internment_date'  => $internmentDate,
                        'wake_days'        => $row->wake_days ?? null,
                        'wake_location'    => $row->wake_location ?? null,
                        'cemetery_place'   => $row->place_of_cemetery ?? null,
                        'case_status'      => $this->mapCaseStatus($row->case_status ?? 'DRAFT'),
                        'created_at'       => $now,
                        'updated_at'       => $now,
                    ]);
                }
            });
    }

    public function down(): void
    {
        DB::table('service_details')->delete();
    }

    private function mapCaseStatus(string $status): string
    {
        return match (strtoupper($status)) {
            'ACTIVE'     => 'ongoing',
            'COMPLETED'  => 'completed',
            default      => 'pending', // DRAFT → pending
        };
    }
};

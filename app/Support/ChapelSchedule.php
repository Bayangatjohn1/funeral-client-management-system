<?php

namespace App\Support;

use App\Models\AddOnCatalog;
use App\Models\FuneralCase;
use Carbon\Carbon;

class ChapelSchedule
{
    public function context(AddOnCatalog $chapel, string $start, string $end, ?int $exceptCaseId = null, bool $lockForUpdate = false): array
    {
        $from = Carbon::parse($start)->format('Y-m-d H:i:s');
        $to = Carbon::parse($end)->format('Y-m-d H:i:s');
        $events = $this->overlappingCases((int) $chapel->branch_id, [$chapel->id], $from, $to, $exceptCaseId, $lockForUpdate)
            ->sortBy('id')->map(fn ($case) => [
                'id' => $case->id, 'case_code' => $case->case_code,
                'start' => $case->wake_start_date->format('Y-m-d').' '.substr($case->wake_start_time, 0, 5),
                'end' => $case->wake_end_date->format('Y-m-d').' '.substr($case->wake_end_time, 0, 5),
            ])->values()->all();

        return ['events' => $events, 'signature' => hash_hmac('sha256', json_encode([$chapel->id, $from, $to, $events]), config('app.key'))];
    }

    public function availableChapelIds(int $branchId, string $start, string $end, ?int $exceptChapelId): array
    {
        $ids = AddOnCatalog::where('branch_id', $branchId)->where('category', AddOnCatalog::CHAPEL_VIEWING)
            ->where('is_active', true)->where('is_available', true)->where('price', '>=', 0)
            ->when($exceptChapelId !== null, fn ($query) => $query->whereKeyNot($exceptChapelId))
            ->get()->filter(fn (AddOnCatalog $chapel) => $chapel->chapel_ready)->modelKeys();
        $busy = $this->overlappingCases($branchId, $ids, Carbon::parse($start)->format('Y-m-d H:i:s'), Carbon::parse($end)->format('Y-m-d H:i:s'))
            ->pluck('chapel_catalog_id')->all();

        return array_values(array_diff($ids, $busy));
    }

    private function overlappingCases(int $branchId, array $chapelIds, string $from, string $to, ?int $exceptCaseId = null, bool $lockForUpdate = false): \Illuminate\Support\Collection
    {
        return FuneralCase::withoutGlobalScope('branch_scope')
            ->where('branch_id', $branchId)->whereIn('chapel_catalog_id', $chapelIds)
            ->whereIn('case_status', ['ACTIVE', 'COMPLETED'])->whereNotNull('wake_start_time')->whereNotNull('wake_end_time')
            ->when($exceptCaseId !== null, fn ($query) => $query->whereKeyNot($exceptCaseId))
            ->whereDate('wake_start_date', '<=', substr($to, 0, 10))->whereDate('wake_end_date', '>=', substr($from, 0, 10))
            ->when($lockForUpdate, fn ($query) => $query->lockForUpdate())
            ->get()->filter(function ($case) use ($from, $to) {
                $start = $case->wake_start_date->format('Y-m-d').' '.substr($case->wake_start_time, 0, 8);
                $end = $case->wake_end_date->format('Y-m-d').' '.substr($case->wake_end_time, 0, 8);

                return Carbon::parse($start)->lt($to) && Carbon::parse($end)->gt($from);
            });
    }

    public static function conflictMessage(array $event): string
    {
        $end = Carbon::parse($event['end'])->format('M d, Y g:i A');

        return "This chapel overlaps with case {$event['case_code']}, whose wake is scheduled to end {$end}. Adjust the wake schedule before saving.";
    }
}

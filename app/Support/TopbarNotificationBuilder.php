<?php

namespace App\Support;

use App\Models\Branch;
use App\Models\FuneralCase;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class TopbarNotificationBuilder
{
    public function forUser(?User $user, ?string $role): array
    {
        if (!$user) {
            return $this->emptyPayload();
        }

        $scopeBranchIds = $this->resolveScopeBranchIds($user, $role);
        if ($scopeBranchIds === []) {
            return $this->emptyPayload();
        }

        sort($scopeBranchIds);

        $scopeKey = sha1(implode(',', $scopeBranchIds));
        $cacheKey = 'topbar:notifications:v3:user:' . $user->id . ':role:' . ($role ?? 'guest') . ':scope:' . $scopeKey;

        return Cache::remember($cacheKey, now()->addSeconds(120), function () use ($scopeBranchIds) {
            $today = now()->startOfDay();
            $todayEnd = $today->copy()->endOfDay();
            $tomorrow = $today->copy()->addDay();
            $upcomingEnd = $today->copy()->addDays(7)->endOfDay();
            $todayDate = $today->toDateString();
            $upcomingDate = $upcomingEnd->toDateString();

            $countRow = $this->baseQuery($scopeBranchIds)
                ->selectRaw("
                    SUM(CASE WHEN (funeral_cases.payment_status IN ('UNPAID', 'PARTIAL') OR COALESCE(funeral_cases.balance_amount, 0) > 0) THEN 1 ELSE 0 END) as due_count
                ")
                ->selectRaw("
                    SUM(CASE WHEN funeral_cases.case_status != 'COMPLETED' AND funeral_cases.funeral_service_at = ? THEN 1 ELSE 0 END) as service_today_count
                ", [$todayDate])
                ->selectRaw("
                    SUM(CASE WHEN funeral_cases.case_status != 'COMPLETED' AND funeral_cases.interment_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as interment_today_count
                ", [$today, $todayEnd])
                ->selectRaw("
                    SUM(CASE WHEN funeral_cases.case_status != 'COMPLETED' AND funeral_cases.funeral_service_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as upcoming_service_count
                ", [$tomorrow->toDateString(), $upcomingDate])
                ->selectRaw("
                    SUM(CASE WHEN funeral_cases.case_status != 'COMPLETED' AND funeral_cases.interment_at > ? AND funeral_cases.interment_at <= ? THEN 1 ELSE 0 END) as upcoming_interment_count
                ", [$todayEnd, $upcomingEnd])
                ->first();

            $items = collect()
                ->merge($this->dueItems($scopeBranchIds, $today, 8))
                ->merge($this->todayServiceItems($scopeBranchIds, $todayDate, 8))
                ->merge($this->todayIntermentItems($scopeBranchIds, $today, $todayEnd, 8))
                ->merge($this->upcomingServiceItems($scopeBranchIds, $tomorrow->toDateString(), $upcomingDate, 8))
                ->merge($this->upcomingIntermentItems($scopeBranchIds, $todayEnd, $upcomingEnd, 8))
                ->sortBy([
                    ['priority', 'desc'],
                    ['sort_at', 'asc'],
                ])
                ->take(8)
                ->values()
                ->map(function (array $item) {
                    unset($item['priority'], $item['sort_at']);
                    return $item;
                });

            $dueCount = (int) ($countRow->due_count ?? 0);
            $todayCount = (int) (($countRow->service_today_count ?? 0) + ($countRow->interment_today_count ?? 0));
            $upcomingCount = (int) (($countRow->upcoming_service_count ?? 0) + ($countRow->upcoming_interment_count ?? 0));

            return [
                'items' => $items->all(),
                'counts' => [
                    'all' => $dueCount + $todayCount + $upcomingCount,
                    'due' => $dueCount,
                    'today' => $todayCount,
                    'upcoming' => $upcomingCount,
                ],
            ];
        });
    }

    private function emptyPayload(): array
    {
        return [
            'items' => [],
            'counts' => [
                'all' => 0,
                'due' => 0,
                'today' => 0,
                'upcoming' => 0,
            ],
        ];
    }

    private function resolveScopeBranchIds(User $user, ?string $role): array
    {
        if ($user->isMainBranchAdmin() || $user->isOwner()) {
            return Branch::query()
                ->where('is_active', true)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        if (in_array($role, ['staff', 'admin'], true)) {
            $operationalBranchId = (int) ($user->operationalBranchId() ?? 0);
            return $operationalBranchId > 0 ? [$operationalBranchId] : [];
        }

        return [];
    }

    private function baseQuery(array $scopeBranchIds): Builder
    {
        return FuneralCase::query()
            ->leftJoin('clients', 'clients.id', '=', 'funeral_cases.client_id')
            ->leftJoin('deceased', 'deceased.id', '=', 'funeral_cases.deceased_id')
            ->whereIn('funeral_cases.branch_id', $scopeBranchIds)
            ->where(function ($query) {
                $query->where('funeral_cases.entry_source', 'MAIN')
                    ->orWhereNull('funeral_cases.entry_source');
            });
    }

    private function dueItems(array $scopeBranchIds, Carbon $today, int $limit): Collection
    {
        return $this->baseQuery($scopeBranchIds)
            ->select([
                'funeral_cases.case_code',
                'clients.full_name as client_name',
                'deceased.full_name as deceased_name',
                'funeral_cases.funeral_service_at',
                'funeral_cases.interment_at',
            ])
            ->where(function ($query) {
                $query->whereIn('funeral_cases.payment_status', ['UNPAID', 'PARTIAL'])
                    ->orWhere('funeral_cases.balance_amount', '>', 0);
            })
            ->orderByRaw('COALESCE(funeral_cases.interment_at, funeral_cases.funeral_service_at, funeral_cases.created_at) asc')
            ->limit($limit)
            ->get()
            ->map(function ($row) use ($today) {
                $date = $row->interment_at ?? $row->funeral_service_at ?? now();

                return $this->makeItem([
                    'bucket' => 'due',
                    'priority' => 4,
                    'title' => $row->interment_at && $row->interment_at->isSameDay($today)
                        ? 'Payment due today (Interment)'
                        : 'Balance pending',
                    'date' => $date,
                    'tab' => 'unpaid',
                    'alert_type' => 'balance',
                    'case_code' => $row->case_code,
                    'deceased_name' => $row->deceased_name,
                    'client_name' => $row->client_name,
                ]);
            });
    }

    private function todayServiceItems(array $scopeBranchIds, string $todayDate, int $limit): Collection
    {
        return $this->baseQuery($scopeBranchIds)
            ->select([
                'funeral_cases.case_code',
                'clients.full_name as client_name',
                'deceased.full_name as deceased_name',
                'funeral_cases.funeral_service_at',
            ])
            ->where('funeral_cases.case_status', '!=', 'COMPLETED')
            ->whereDate('funeral_cases.funeral_service_at', $todayDate)
            ->orderBy('funeral_cases.funeral_service_at')
            ->limit($limit)
            ->get()
            ->map(function ($row) {
                return $this->makeItem([
                    'bucket' => 'today',
                    'priority' => 3,
                    'title' => 'Funeral service today',
                    'date' => $row->funeral_service_at,
                    'tab' => 'today',
                    'alert_type' => 'service_today',
                    'case_code' => $row->case_code,
                    'deceased_name' => $row->deceased_name,
                    'client_name' => $row->client_name,
                ]);
            });
    }

    private function todayIntermentItems(array $scopeBranchIds, Carbon $todayStart, Carbon $todayEnd, int $limit): Collection
    {
        return $this->baseQuery($scopeBranchIds)
            ->select([
                'funeral_cases.case_code',
                'clients.full_name as client_name',
                'deceased.full_name as deceased_name',
                'funeral_cases.interment_at',
            ])
            ->where('funeral_cases.case_status', '!=', 'COMPLETED')
            ->whereBetween('funeral_cases.interment_at', [$todayStart, $todayEnd])
            ->orderBy('funeral_cases.interment_at')
            ->limit($limit)
            ->get()
            ->map(function ($row) {
                return $this->makeItem([
                    'bucket' => 'today',
                    'priority' => 3,
                    'title' => 'Interment today',
                    'date' => $row->interment_at,
                    'tab' => 'today',
                    'alert_type' => 'interment_today',
                    'case_code' => $row->case_code,
                    'deceased_name' => $row->deceased_name,
                    'client_name' => $row->client_name,
                ]);
            });
    }

    private function upcomingServiceItems(array $scopeBranchIds, string $fromDate, string $toDate, int $limit): Collection
    {
        return $this->baseQuery($scopeBranchIds)
            ->select([
                'funeral_cases.case_code',
                'clients.full_name as client_name',
                'deceased.full_name as deceased_name',
                'funeral_cases.funeral_service_at',
            ])
            ->where('funeral_cases.case_status', '!=', 'COMPLETED')
            ->whereBetween('funeral_cases.funeral_service_at', [$fromDate, $toDate])
            ->orderBy('funeral_cases.funeral_service_at')
            ->limit($limit)
            ->get()
            ->map(function ($row) {
                return $this->makeItem([
                    'bucket' => 'upcoming',
                    'priority' => 2,
                    'title' => 'Upcoming funeral service',
                    'date' => $row->funeral_service_at,
                    'tab' => 'upcoming',
                    'alert_type' => 'upcoming_service',
                    'case_code' => $row->case_code,
                    'deceased_name' => $row->deceased_name,
                    'client_name' => $row->client_name,
                ]);
            });
    }

    private function upcomingIntermentItems(array $scopeBranchIds, Carbon $todayEnd, Carbon $upcomingEnd, int $limit): Collection
    {
        return $this->baseQuery($scopeBranchIds)
            ->select([
                'funeral_cases.case_code',
                'clients.full_name as client_name',
                'deceased.full_name as deceased_name',
                'funeral_cases.interment_at',
            ])
            ->where('funeral_cases.case_status', '!=', 'COMPLETED')
            ->where('funeral_cases.interment_at', '>', $todayEnd)
            ->where('funeral_cases.interment_at', '<=', $upcomingEnd)
            ->orderBy('funeral_cases.interment_at')
            ->limit($limit)
            ->get()
            ->map(function ($row) {
                return $this->makeItem([
                    'bucket' => 'upcoming',
                    'priority' => 2,
                    'title' => 'Upcoming interment',
                    'date' => $row->interment_at,
                    'tab' => 'upcoming',
                    'alert_type' => 'upcoming_interment',
                    'case_code' => $row->case_code,
                    'deceased_name' => $row->deceased_name,
                    'client_name' => $row->client_name,
                ]);
            });
    }

    private function makeItem(array $item): array
    {
        $date = $item['date'] instanceof Carbon ? $item['date']->copy() : Carbon::parse($item['date']);
        $caseCode = $item['case_code'] ?: 'N/A';
        $deceasedName = $item['deceased_name'] ?: 'Unknown';

        return [
            'bucket' => $item['bucket'],
            'priority' => $item['priority'],
            'sort_at' => $date->timestamp,
            'title' => $item['title'],
            'subtitle' => $caseCode . ' - ' . $deceasedName,
            'date' => $date,
            'tab' => $item['tab'],
            'alert_type' => $item['alert_type'],
            'case_code' => $caseCode,
            'deceased_name' => $deceasedName,
            'client_name' => $item['client_name'] ?: 'N/A',
        ];
    }
}

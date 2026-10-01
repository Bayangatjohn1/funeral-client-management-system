<?php

namespace App\Support;

use App\Models\Branch;
use App\Models\User;
use App\Services\ReminderService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class TopbarNotificationBuilder
{
    public function __construct(private readonly ReminderService $reminderService) {}

    public function forUser(?User $user, ?string $role, ?int $requestedBranchId = null): array
    {
        if (! $user) return $this->emptyPayload();

        $scopeBranchIds = $this->resolveScopeBranchIds($user, $role);
        if ($scopeBranchIds === []) return $this->emptyPayload();

        if ($requestedBranchId !== null && in_array($requestedBranchId, $scopeBranchIds, true)) {
            $scopeBranchIds = [$requestedBranchId];
        } elseif ($user->isAdmin()) {
            $branchId = (int) ($user->operationalBranchId() ?? 0);
            if ($branchId > 0 && in_array($branchId, $scopeBranchIds, true)) $scopeBranchIds = [$branchId];
        }

        sort($scopeBranchIds);
        $scopeKey = sha1(implode(',', $scopeBranchIds));
        $cacheKey = 'topbar:notifications:v4:user:'.$user->id.':role:'.($role ?? 'guest').':scope:'.$scopeKey;

        return Cache::remember($cacheKey, now()->addSeconds(120), function () use ($scopeBranchIds) {
            $today = now()->startOfDay();
            $upcomingEnd = $today->copy()->addDays(7)->endOfDay();
            $reminders = collect($scopeBranchIds)->flatMap(fn (int $branchId) => $this->reminderService
                ->buildFullList($branchId, ['alert_type' => 'all'], now())
                ->map(fn (array $item) => array_merge($item, ['branch_id' => $branchId])));

            $caseGroups = $reminders->groupBy('case_id')->map(function (Collection $items) use ($today, $upcomingEnd) {
                $buckets = collect();
                $types = $items->pluck('type');
                if ($types->intersect(['interment_approaching', 'interment_completed', 'same_day_schedule'])->isNotEmpty()) $buckets->push('needs_attention');
                if ($types->intersect(['service_today', 'interment_today'])->isNotEmpty()) $buckets->push('today');
                if ($items->contains(fn (array $item) => in_array($item['type'], ['upcoming_service', 'upcoming_interment'], true)
                    && $item['date']?->betweenIncluded($today, $upcomingEnd))) $buckets->push('upcoming');
                if ($types->contains('balance')) $buckets->push('with_balance');

                return ['items' => $items, 'buckets' => $buckets->unique()->values()];
            })->filter(fn (array $group) => $group['buckets']->isNotEmpty());

            $counts = ['all' => $caseGroups->count()];
            foreach (['needs_attention', 'today', 'upcoming', 'with_balance'] as $bucket) {
                $counts[$bucket] = $caseGroups->filter(fn (array $group) => $group['buckets']->contains($bucket))->count();
            }

            $bucketPriority = ['needs_attention', 'today', 'upcoming', 'with_balance'];
            $typePriority = ['interment_completed', 'interment_approaching', 'same_day_schedule', 'service_today', 'interment_today', 'upcoming_interment', 'upcoming_service', 'balance'];
            $items = $caseGroups->map(function (array $group) use ($bucketPriority, $typePriority) {
                $bucket = collect($bucketPriority)->first(fn (string $name) => $group['buckets']->contains($name));
                $item = collect($typePriority)->map(fn (string $type) => $group['items']->firstWhere('type', $type))
                    ->first(fn ($candidate) => $candidate !== null) ?? $group['items']->first();

                return [
                    'bucket' => $bucket,
                    'buckets' => $group['buckets']->all(),
                    'title' => $item['label'],
                    'date' => $item['date'],
                    'tab' => match ($bucket) {
                        'needs_attention' => 'warnings', 'today' => 'today', 'upcoming' => 'upcoming', default => 'unpaid',
                    },
                    'branch_id' => $item['branch_id'],
                    'case_id' => $item['case_id'],
                    'case_code' => $item['case_code'],
                    'deceased_name' => $item['deceased_name'],
                    'client_name' => $item['case']->client?->full_name ?? 'N/A',
                    'message' => $item['message'],
                    'sort_rank' => array_search($bucket, $bucketPriority, true),
                    'sort_at' => $item['sort_date']?->timestamp ?? PHP_INT_MAX,
                ];
            })->sortBy([['sort_rank', 'asc'], ['sort_at', 'asc']])->take(8)->values()->map(function (array $item) {
                unset($item['sort_rank'], $item['sort_at']);
                return $item;
            });

            return ['items' => $items->all(), 'counts' => $counts];
        });
    }

    private function emptyPayload(): array
    {
        return ['items' => [], 'counts' => ['all' => 0, 'needs_attention' => 0, 'today' => 0, 'upcoming' => 0, 'with_balance' => 0]];
    }

    private function resolveScopeBranchIds(User $user, ?string $role): array
    {
        if ($user->isMainBranchAdmin() || $user->isOwner()) {
            return Branch::query()->where('is_active', true)->pluck('id')->map(fn ($id) => (int) $id)->all();
        }
        if (in_array($role, ['staff', 'admin'], true)) {
            $branchId = (int) ($user->operationalBranchId() ?? 0);
            return $branchId > 0 ? [$branchId] : [];
        }
        return [];
    }
}

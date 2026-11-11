<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowNotice extends Model
{
    public const CORRECTION_FOLLOW_UP_HOURS = 24;
    public const CORRECTION_REMINDER_COOLDOWN_MINUTES = 15;
    public const AUTOMATIC_CORRECTION_REMINDER = 'overdue_reminder';
    public const MANUAL_CORRECTION_REMINDER_PREFIX = 'reminded:';

    protected $guarded = [];

    protected $casts = ['read_at' => 'datetime'];

    public function correction()
    {
        return $this->belongsTo(PaymentCorrectionRequest::class, 'payment_correction_request_id');
    }

    public static function recordOverdueCorrectionsFor(User $admin): void
    {
        $branchAdmin = $admin->isBranchAdmin();
        if ($branchAdmin && !$admin->branch_id) {
            return;
        }

        $event = $branchAdmin ? self::AUTOMATIC_CORRECTION_REMINDER : 'overdue';
        $now = now();
        PaymentCorrectionRequest::query()
            ->where('status', 'PENDING')
            ->when($branchAdmin, fn ($query) => $query->where('branch_id', $admin->branch_id))
            ->where('created_at', '<=', $now->copy()->subHours(self::CORRECTION_FOLLOW_UP_HOURS))
            ->whereNotIn('id', static::query()->select('payment_correction_request_id')
                ->where('user_id', $admin->id)->where('event', $event))
            ->select(['id', 'branch_id'])
            ->chunkById(200, function ($requests) use ($admin, $event, $now) {
                static::query()->insertOrIgnore($requests->map(fn ($request) => [
                    'user_id' => $admin->id,
                    'branch_id' => $request->branch_id,
                    'payment_correction_request_id' => $request->id,
                    'event' => $event,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all());
            });
    }

    public static function latestManualCorrectionReminder(PaymentCorrectionRequest $request): ?self
    {
        return static::where('payment_correction_request_id', $request->id)
            ->where('event', 'like', self::MANUAL_CORRECTION_REMINDER_PREFIX.'%')
            ->latest()->first();
    }

    public static function correctionFeedFor(User $user): array
    {
        $systemAdmin = $user->isSystemAdmin();
        $query = static::correctionQueryFor($user);
        $notices = $query->with(['correction.payment', 'correction.branch'])
            ->orderByRaw('CASE WHEN read_at IS NULL THEN 0 ELSE 1 END')
            ->latest()->orderByDesc('id')->take(50)->get();
        $stateKeys = $notices->map(fn ($notice) => 'workflow:'.$notice->id);
        $states = UserNotificationState::query()->where('user_id', $user->id)
            ->whereIn('notification_key', $stateKeys)->get()->keyBy('notification_key');
        $visible = $notices->reject(fn ($notice) => $states->get('workflow:'.$notice->id)?->dismissed_at !== null)->take(12);

        return PaymentCorrectionRequest::pendingSummaryFor($user) + [
            'unread' => (clone $query)->whereNull('read_at')->count(),
            'items' => $visible->map(function ($notice) use ($systemAdmin, $states) {
                    $event = str_starts_with($notice->event, self::MANUAL_CORRECTION_REMINDER_PREFIX)
                        ? 'reminded'
                        : $notice->event;

                    return [
                        'key' => 'workflow:'.$notice->id,
                        'id' => $notice->id,
                        'read' => (bool) ($notice->read_at ?? $states->get('workflow:'.$notice->id)?->read_at),
                        'tone' => match ($event) {
                            'overdue', 'rejected' => 'danger',
                            self::AUTOMATIC_CORRECTION_REMINDER, 'reminded', 'requested' => 'warning',
                            'approved' => 'success',
                            'withdrawn' => 'neutral',
                            default => 'info',
                        },
                        'title' => match ($event) {
                            'overdue' => 'Payment correction needs follow-up',
                            self::AUTOMATIC_CORRECTION_REMINDER => 'Payment correction awaiting review for '.self::CORRECTION_FOLLOW_UP_HOURS.' hours',
                            'reminded' => 'System Admin reminder: Review payment correction',
                            default => 'Payment correction '.$event,
                        },
                        'event' => $event,
                        'reference' => $notice->correction->payment?->display_payment_record_no,
                        'branch' => $systemAdmin ? $notice->correction->branch?->branch_name : null,
                        'at' => $notice->created_at->format('M d, Y g:i A'),
                        'created_at' => $notice->created_at->toISOString(),
                        'url' => $systemAdmin
                            ? route('admin.payments.index', ['tab' => 'corrections', 'open_correction' => $notice->payment_correction_request_id])
                            : route('payments.corrections.show', $notice->correction),
                    ];
                })->values(),
        ];
    }

    public static function correctionQueryFor(User $user)
    {
        $systemAdmin = $user->isSystemAdmin();
        if ($systemAdmin || $user->isBranchAdmin()) {
            static::recordOverdueCorrectionsFor($user);
        }

        return static::where('user_id', $user->id)
            ->when($systemAdmin, fn ($q) => $q->where('event', 'overdue')
                ->whereHas('correction', fn ($q) => $q->where('status', 'PENDING')))
            ->when(!$systemAdmin, fn ($q) => $q->where('branch_id', $user->branch_id)->where('event', '!=', 'overdue'))
            ->when($user->isStaff(), fn ($q) => $q->whereHas('correction', fn ($q) => $q->where('requested_by', $user->id)))
            ->when($user->isBranchAdmin(), fn ($q) => $q->where(fn ($q) => $q
                ->where(fn ($q) => $q->where('event', '!=', self::AUTOMATIC_CORRECTION_REMINDER)
                    ->where('event', 'not like', self::MANUAL_CORRECTION_REMINDER_PREFIX.'%'))
                ->orWhereHas('correction', fn ($q) => $q->where('status', 'PENDING'))));
    }

    public static function correctionEvent(PaymentCorrectionRequest $request, string $event): int
    {
        $notifyBranch = $event === 'requested' || str_starts_with($event, self::MANUAL_CORRECTION_REMINDER_PREFIX);
        $users = $notifyBranch
            ? User::where('branch_id', $request->branch_id)->where('is_active', true)->get()->filter(fn ($user) => $user->isBranchAdmin())
            : User::whereKey($request->requested_by)->get();
        foreach ($users as $user) {
            static::firstOrCreate(['user_id' => $user->id, 'payment_correction_request_id' => $request->id, 'event' => $event], ['branch_id' => $request->branch_id]);
        }

        return $users->count();
    }
}

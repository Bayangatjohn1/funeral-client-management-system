<?php

namespace App\Http\Controllers;

use App\Models\UserNotificationState;
use App\Models\User;
use App\Models\WorkflowNotice;
use App\Support\TopbarNotificationBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationStateController extends Controller
{
    public function markAllRead(Request $request, TopbarNotificationBuilder $builder): JsonResponse
    {
        $user = $this->authorizedUser($request);
        $validated = $request->validate(['branch_id' => ['nullable', 'integer', 'min:1']]);
        $branchId = isset($validated['branch_id']) ? (int) $validated['branch_id'] : null;
        $keys = $builder->activeKeysForUser($user, $user->role, $branchId);
        $now = now();

        if ($keys !== []) {
            UserNotificationState::query()->upsert(
                collect($keys)->map(fn (string $key) => [
                    'user_id' => $user->id,
                    'notification_key' => $key,
                    'read_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all(),
                ['user_id', 'notification_key'],
                ['read_at', 'updated_at'],
            );
        }

        WorkflowNotice::correctionQueryFor($user)->whereNull('read_at')->update(['read_at' => $now]);

        return response()->json($this->counts($user, $builder, $branchId));
    }

    public function update(Request $request, TopbarNotificationBuilder $builder): JsonResponse
    {
        $user = $this->authorizedUser($request);
        $validated = $request->validate([
            'key' => ['required', 'string', 'max:191'],
            'action' => ['required', 'in:read,dismiss'],
            'branch_id' => ['nullable', 'integer', 'min:1'],
        ]);
        $key = $validated['key'];
        $branchId = isset($validated['branch_id']) ? (int) $validated['branch_id'] : null;

        if (str_starts_with($key, 'workflow:')) {
            $noticeId = filter_var(substr($key, strlen('workflow:')), FILTER_VALIDATE_INT);
            abort_unless($noticeId && WorkflowNotice::correctionQueryFor($user)->whereKey($noticeId)->exists(), 404);
            WorkflowNotice::whereKey($noticeId)->where('user_id', $user->id)->update(['read_at' => now()]);
        } else {
            abort_unless(in_array($key, $builder->activeKeysForUser($user, $user->role, $branchId), true), 404);
        }

        UserNotificationState::query()->updateOrCreate(
            ['user_id' => $user->id, 'notification_key' => $key],
            [
                'read_at' => now(),
                'dismissed_at' => $validated['action'] === 'dismiss' ? now() : null,
            ],
        );

        return response()->json($this->counts($user, $builder, $branchId));
    }

    private function authorizedUser(Request $request): User
    {
        $user = $request->user();
        abort_unless($user && ($user->isStaff() || $user->isBranchAdmin() || $user->isSystemAdmin()), 403);

        return $user;
    }

    private function counts(User $user, TopbarNotificationBuilder $builder, ?int $branchId): array
    {
        $reminders = $builder->forUser($user, $user->role, $branchId);
        $workflow = WorkflowNotice::correctionFeedFor($user);

        return [
            'reminder_unread' => $reminders['unread'] ?? 0,
            'workflow_unread' => $workflow['unread'] ?? 0,
            'unread' => ($reminders['unread'] ?? 0) + ($workflow['unread'] ?? 0),
        ];
    }
}

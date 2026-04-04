<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use App\Jobs\LogAuditEntry;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class AuditLogger
{
    private static ?string $requestTransaction = null;

    /**
     * Store an audit log entry. Never throw; failures are swallowed.
     */
    public static function log(
        string $action,
        string $actionType,
        string $entityType,
        ?int $entityId,
        array $metadata = [],
        ?int $branchId = null,
        ?int $targetBranchId = null,
        string $status = 'success',
        ?string $remarks = null,
        ?string $actionLabel = null,
        ?string $transactionId = null
    ): void
    {
        $user = Auth::user();
        $request = request();

        $resolvedLabel = $actionLabel ?: self::humanizeAction($action);
        $resolvedTransaction = $transactionId
            ?: (self::$requestTransaction ??= (string) Str::uuid());

        $payload = [
            'actor_id' => $user?->id,
            'actor_role' => $user?->role,
            'action' => $action,
            'action_label' => $resolvedLabel,
            'action_type' => $actionType,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'branch_id' => $branchId,
            'target_branch_id' => $targetBranchId ?? $branchId,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'status' => $status,
            'remarks' => $remarks,
            'transaction_id' => $resolvedTransaction,
            'metadata' => $metadata,
        ];

        try {
            LogAuditEntry::dispatch($payload);
        } catch (\Throwable $e) {
            // Fallback to synchronous write if queue dispatch fails.
            try {
                AuditLog::create($payload);
            } catch (\Throwable $inner) {
                // swallow to avoid blocking user flow
            }
        }
    }

    /**
     * Convenience helper to mark a reason-required log.
     */
    public static function requireReason(array $validated, string $field = 'reason'): ?string
    {
        $reason = $validated[$field] ?? null;
        if (!$reason || trim((string) $reason) === '') {
            throw new \RuntimeException('A reason is required for this action.');
        }
        return trim((string) $reason);
    }

    private static function humanizeAction(string $action): string
    {
        // Example: payment.created -> Payment created
        $normalized = str_replace(['.', '_'], ' ', $action);
        return Str::headline($normalized);
    }
}

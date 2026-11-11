<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    public const FALLBACK_ACTION_LABELS = [
        'case.created' => 'Case created',
        'case.updated' => 'Case updated',
        'case.status_changed' => 'Case status changed',
        'payment.created' => 'Payment recorded',
        'payment.voided' => 'Payment voided',
        'package.created' => 'Package created',
        'package.updated' => 'Package updated',
        'package.price_changed' => 'Package price updated',
    ];

    protected $fillable = [
        'actor_id',
        'actor_role',
        'action',
        'action_label',
        'action_type',
        'entity_type',
        'entity_id',
        'branch_id',
        'target_branch_id',
        'ip_address',
        'user_agent',
        'status',
        'remarks',
        'transaction_id',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        // Preserve audit integrity: block edits or deletions of stored logs.
        static::updating(function () {
            return false;
        });

        static::deleting(function () {
            return false;
        });
    }

    public function actor()
    {
        return $this->belongsTo(\App\Models\User::class, 'actor_id');
    }

    public function scopeMatchingSearch(Builder $query, ?string $search): Builder
    {
        $search = trim($search ?? '');
        if ($search === '') {
            return $query;
        }

        $eventId = str_starts_with($search, '#') ? substr($search, 1) : $search;
        $pattern = '%' . $search . '%';

        $fallbackActions = array_keys(array_filter(self::FALLBACK_ACTION_LABELS, fn (string $label) => stripos($label, $search) !== false));

        return $query->where(function (Builder $matches) use ($eventId, $pattern, $fallbackActions) {
            $matches->where('action', 'like', $pattern)
                ->orWhere('action_label', 'like', $pattern)
                ->orWhereHas('actor', fn (Builder $actor) => $actor->where('name', 'like', $pattern))
                ->orWhere(fn (Builder $fallback) => $fallback->whereNull('action_label')
                    ->where(fn (Builder $action) => $action->whereIn('action', $fallbackActions)
                        ->orWhereRaw("REPLACE(REPLACE(action, '.', ' '), '_', ' ') LIKE ?", [$pattern])));

            if (ctype_digit($eventId) && filter_var($eventId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) !== false) {
                $matches->orWhere($matches->getModel()->getQualifiedKeyName(), (int) $eventId);
            }
        });
    }

    public function branch()
    {
        return $this->belongsTo(\App\Models\Branch::class);
    }
}

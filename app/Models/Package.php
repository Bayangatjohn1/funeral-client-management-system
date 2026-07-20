<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Package extends Model
{
    protected $fillable = [
        'name',          // legacy; package_name is the canonical alias via accessor
        'short_description',
        'coffin_type',
        'price',
        'inclusions',    // legacy TEXT; normalized rows are in package_inclusions table
        'freebies',      // legacy TEXT; normalized rows are in package_freebies table
        'promo_label',
        'promo_value_type',
        'promo_value',
        'promo_starts_at',
        'promo_ends_at',
        'promo_is_active',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'promo_value' => 'decimal:2',
        'promo_starts_at' => 'datetime',
        'promo_ends_at' => 'datetime',
        'promo_is_active' => 'boolean',
        'is_active' => 'boolean',
    ];

    public const SERVICE_BODY_RETRIEVAL = 'body_retrieval';
    public const SERVICE_EMBALMING = 'embalming';
    public const SERVICE_CASKET = 'casket';
    public const SERVICE_HOME_VIEWING = 'home_viewing';
    public const SERVICE_HEARSE = 'hearse';
    public const SERVICE_CUSTOM = 'custom';

    public static function normalizeServiceType(?string $value): string
    {
        $key = strtolower(trim((string) $value));
        $key = str_replace(['-', ' '], '_', $key);
        $key = preg_replace('/_+/', '_', $key) ?: '';

        return match ($key) {
            'retrieval', 'body_retrieval_service', 'body_retrievals' => self::SERVICE_BODY_RETRIEVAL,
            'hearse_service', 'hearse_services', 'funeral_hearse' => self::SERVICE_HEARSE,
            'home_viewing_service', 'viewing', 'wake_viewing' => self::SERVICE_HOME_VIEWING,
            'embalming_service' => self::SERVICE_EMBALMING,
            'coffin', 'included_casket', 'casket_coffin' => self::SERVICE_CASKET,
            'other', 'other_inclusion', 'custom_inclusion' => self::SERVICE_CUSTOM,
            default => $key,
        };
    }

    public static function serviceTypeOptions(): array
    {
        return [
            self::SERVICE_BODY_RETRIEVAL => 'Body Retrieval',
            self::SERVICE_EMBALMING => 'Embalming',
            self::SERVICE_CASKET => 'Casket',
            self::SERVICE_HOME_VIEWING => 'Home Viewing',
            self::SERVICE_HEARSE => 'Hearse',
            self::SERVICE_CUSTOM => 'Custom Inclusion',
        ];
    }

    /**
     * Canonical package_name accessor — wraps the legacy name column.
     * Allows new code to reference $package->package_name uniformly.
     */
    public function getPackageNameAttribute(): string
    {
        return $this->attributes['name'] ?? '';
    }

    public function setPackageNameAttribute(string $value): void
    {
        $this->attributes['name'] = $value;
    }

    /**
     * Normalized inclusions (Phase 1+).
     * Falls back to parsing the TEXT column if the table is empty for this package.
     */
    public function packageInclusions()
    {
        return $this->hasMany(\App\Models\PackageInclusion::class)->with('casketCatalog')->orderBy('sort_order');
    }

    public function inclusions()
    {
        return $this->packageInclusions();
    }

    /**
     * Normalized freebies (Phase 1+).
     */
    public function packageFreebies()
    {
        return $this->hasMany(\App\Models\PackageFreebie::class)->orderBy('sort_order');
    }

    public function freebies()
    {
        return $this->packageFreebies();
    }

    public function packageAddOns()
    {
        return $this->hasMany(\App\Models\PackageAddOn::class)->orderBy('id');
    }

    public function activeAddOns()
    {
        return $this->packageAddOns()->where('is_active', true);
    }

    public function inclusionNames(): array
    {
        return $this->normalizedItemNames('packageInclusions', 'inclusion_name', $this->attributes['inclusions'] ?? null);
    }

    public function freebieNames(): array
    {
        return $this->normalizedItemNames('packageFreebies', 'freebie_name', $this->attributes['freebies'] ?? null);
    }

    public static function parseLegacyItems(?string $raw): array
    {
        $raw = trim((string) $raw);

        if ($raw === '') {
            return [];
        }

        if (str_starts_with($raw, '[')) {
            $decoded = json_decode($raw, true);

            if (is_array($decoded)) {
                return array_values(array_filter(array_map(fn ($item) => trim((string) $item), $decoded)));
            }
        }

        foreach (["\r\n", "\n", "\r", ';'] as $delimiter) {
            if (str_contains($raw, $delimiter)) {
                return array_values(array_filter(array_map('trim', explode($delimiter, $raw))));
            }
        }

        return [$raw];
    }

    private function normalizedItemNames(string $relation, string $column, ?string $fallback): array
    {
        $items = $this->relationLoaded($relation)
            ? $this->getRelation($relation)
            : $this->{$relation}()->get();

        if ($items instanceof Collection && $items->isNotEmpty()) {
            return $items
                ->pluck($column)
                ->map(fn ($value) => trim((string) $value))
                ->filter()
                ->values()
                ->all();
        }

        return self::parseLegacyItems($fallback);
    }

    /**
     * True only when the promo toggle is ON and the current time falls within
     * the configured start/end window. Use this everywhere the UI or logic
     * needs to know whether a promo is actually live right now.
     */
    public function getIsPromoEffectiveAttribute(): bool
    {
        if (!$this->promo_is_active || !$this->promo_value_type || !$this->promo_value) {
            return false;
        }

        $now = now();

        if ($this->promo_starts_at && $now->lt($this->promo_starts_at)) {
            return false;
        }

        if ($this->promo_ends_at && $now->gt($this->promo_ends_at)) {
            return false;
        }

        return true;
    }

    /**
     * Promo toggle is ON but the end date has already passed — effectively expired.
     */
    public function getIsPromoExpiredAttribute(): bool
    {
        return $this->promo_is_active
            && $this->promo_ends_at !== null
            && now()->gt($this->promo_ends_at);
    }

    /**
     * Promo toggle is ON but the start date hasn't arrived yet — scheduled/pending.
     */
    public function getIsPromoScheduledAttribute(): bool
    {
        return $this->promo_is_active
            && $this->promo_starts_at !== null
            && now()->lt($this->promo_starts_at);
    }

    public function getPromoStatusAttribute(): string
    {
        if (! $this->promo_is_active || ! $this->promo_value_type || ! $this->promo_value) {
            return 'Inactive';
        }

        $now = now('Asia/Manila');
        $startsAt = $this->promo_starts_at?->copy()->timezone('Asia/Manila');
        $endsAt = $this->promo_ends_at?->copy()->timezone('Asia/Manila');

        if ($startsAt && $now->lt($startsAt)) {
            return 'Scheduled';
        }

        if ($endsAt && $now->gt($endsAt)) {
            return 'Expired';
        }

        return 'Active';
    }

    public function funeralCases()
    {
        return $this->hasMany(\App\Models\FuneralCase::class);
    }
}

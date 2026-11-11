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
        'promo_manually_stopped_at',
        'promo_manually_stopped_by',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'promo_value' => 'decimal:2',
        'promo_starts_at' => 'datetime',
        'promo_ends_at' => 'datetime',
        'promo_is_active' => 'boolean',
        'promo_manually_stopped_at' => 'datetime',
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

    public static function isBasicServiceType(?string $value): bool
    {
        $type = self::normalizeServiceType($value);

        return $type !== self::SERVICE_CUSTOM && isset(self::serviceTypeOptions()[$type]);
    }

    public static function isLegacyBasicInclusion(string $label): bool
    {
        $name = preg_split('/\s*[:(]|\s+[-–—]\s+/u', trim($label), 2)[0];

        return self::isBasicServiceType($name) || in_array(mb_strtolower($name), ['regular hearse', 'casket / coffin'], true);
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

    public function addOnAllowanceFingerprint(): string
    {
        $quantities = $this->packageInclusions->whereNotNull('add_on_catalog_id')
            ->pluck('included_quantity', 'add_on_catalog_id')->sortKeys()->all();

        return hash_hmac('sha256', json_encode([$this->getKey(), $quantities], JSON_THROW_ON_ERROR), config('app.key'));
    }

    public function intakeConfigurationFingerprint(): string
    {
        $this->loadMissing(['packageInclusions.casketCatalog', 'packageInclusions.hearseCatalog', 'packageFreebies.catalog']);
        $inclusions = $this->packageInclusions->sortBy('id')->map(fn ($item) => [
            $item->getAttributes(),
            $item->casketCatalog?->only(['name', 'type_or_material', 'standard_price', 'is_active', 'is_available']),
            $item->hearseCatalog?->only(['name', 'reference_value', 'is_active', 'is_available']),
        ])->values()->all();
        $freebies = $this->packageFreebies->sortBy('id')->map(fn ($item) => [
            $item->getAttributes(), $item->catalog?->only(['name', 'default_unit', 'is_active', 'is_available']),
        ])->values()->all();

        return hash_hmac('sha256', json_encode([
            $this->only(['id', 'name', 'price', 'coffin_type', 'inclusions', 'freebies']), $inclusions, $freebies,
        ], JSON_THROW_ON_ERROR), config('app.key'));
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

    public function promotion()
    {
        return $this->hasOne(\App\Models\Promotion::class);
    }

    public function activeAddOns()
    {
        return $this->packageAddOns()->where('is_active', true);
    }

    public function inclusionNames(): array
    {
        return $this->normalizedItemNames('packageInclusions', 'inclusion_name', $this->attributes['inclusions'] ?? null);
    }

    /**
     * These generic labels were seeded into the legacy inclusions text as
     * package descriptions, not as individual services. Keep the stored value
     * for compatibility, but omit it from service summaries and detail lists.
     */
    public static function isLegacyPackageDescription(?string $value): bool
    {
        $normalized = mb_strtolower(rtrim(trim((string) $value), '.'));

        return in_array($normalized, ['full service package', 'standard service package'], true);
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
    public function promotionStatusAt(?\Carbon\CarbonInterface $referenceAt = null): string
    {
        $promotion = $this->relationLoaded('promotion') ? $this->getRelation('promotion') : $this->promotion()->first();
        if ($promotion) return $promotion->statusAt($referenceAt, $this);
        $hasLegacyPromotionData = filled($this->promo_label)
            || filled($this->promo_value_type)
            || $this->promo_value !== null
            || $this->promo_starts_at !== null
            || $this->promo_ends_at !== null
            || $this->promo_manually_stopped_at !== null;
        if (! $hasLegacyPromotionData) {
            return 'No Promotion';
        }
        if ($this->legacyPromotionReviewFields() !== []) {
            return 'Incomplete Setup';
        }
        $now = ($referenceAt ?? now('Asia/Manila'))->copy()->timezone('Asia/Manila');
        $startsAt = $this->promo_starts_at?->copy()->timezone('Asia/Manila')->startOfDay();
        $endsAt = $this->promo_ends_at?->copy()->timezone('Asia/Manila')->endOfDay();
        if ($endsAt && $now->gt($endsAt)) {
            return 'Expired';
        }
        if ($this->promo_manually_stopped_at) {
            return 'Turned Off';
        }
        // An old false toggle does not establish who disabled it or why.
        // Only the recorded stop event is reliable evidence of manual action.
        if (! $this->promo_is_active) {
            return 'Incomplete Setup';
        }
        if ($startsAt && $now->lt($startsAt)) {
            return 'Scheduled';
        }
        return 'Active';
    }

    /** @return list<string> Human-readable fields requiring legacy promotion review. */
    public function legacyPromotionReviewFields(): array
    {
        $issues = [];
        if (blank($this->promo_label)) $issues[] = 'Promotion Name';
        $type = strtoupper((string) $this->promo_value_type);
        if (! in_array($type, ['AMOUNT', 'PERCENT'], true)) $issues[] = 'Discount Type';
        if (! is_numeric($this->promo_value) || (float) $this->promo_value <= 0) {
            $issues[] = 'Discount Value';
        } elseif ($type === 'PERCENT' && (float) $this->promo_value > 100) {
            $issues[] = 'Discount Value';
        } elseif ($type === 'AMOUNT' && (float) $this->promo_value > (float) $this->price) {
            $issues[] = 'Discount Value';
        }
        if ($this->promo_starts_at && $this->promo_ends_at && $this->promo_ends_at->lt($this->promo_starts_at)) {
            $issues[] = 'End Date';
        }
        if (! $this->promo_is_active && ! $this->promo_manually_stopped_at) {
            $issues[] = 'Promotion Status';
        }

        return array_values(array_unique($issues));
    }

    public function hasConfiguredPromotion(): bool
    {
        return filled($this->promo_label)
            && in_array($this->promo_value_type, ['PERCENT', 'AMOUNT'], true)
            && (float) $this->promo_value > 0;
    }

    public function promotionStoppedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'promo_manually_stopped_by');
    }

    public function isPromoEffectiveAt(?\Carbon\CarbonInterface $referenceAt = null): bool
    {
        $promotion = $this->relationLoaded('promotion') ? $this->getRelation('promotion') : $this->promotion()->first();
        if ($promotion) return $promotion->isEffectiveAt($referenceAt, $this);
        return $this->promotionStatusAt($referenceAt) === 'Active'
            && filled($this->promo_value_type) && (float) $this->promo_value > 0;
    }

    public function getIsPromoEffectiveAttribute(): bool
    {
        return $this->isPromoEffectiveAt();
    }

    public function getIsPromoExpiredAttribute(): bool
    {
        return $this->promo_status === 'Expired';
    }

    public function getIsPromoScheduledAttribute(): bool
    {
        return $this->promo_status === 'Scheduled';
    }

    public function getPromoStatusAttribute(): string
    {
        return $this->promotionStatusAt();
    }

    public function funeralCases()
    {
        return $this->hasMany(\App\Models\FuneralCase::class);
    }
}

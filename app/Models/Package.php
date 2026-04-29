<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Package extends Model
{
    protected $fillable = [
        'name',          // legacy; package_name is the canonical alias via accessor
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
        return $this->hasMany(\App\Models\PackageInclusion::class)->orderBy('sort_order');
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

    public function funeralCases()
    {
        return $this->hasMany(\App\Models\FuneralCase::class);
    }
}

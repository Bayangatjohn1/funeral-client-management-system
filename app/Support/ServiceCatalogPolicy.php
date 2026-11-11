<?php

namespace App\Support;

use App\Models\CasketCatalog;
use App\Models\FreebieCatalog;
use App\Models\Package;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ServiceCatalogPolicy
{
    public function intakePackages(Builder $query, bool $allowCasketReplacement = false): Builder
    {
        $allowCasketReplacement = $allowCasketReplacement && CasketCatalog::where('is_active', true)->where('is_available', true)->where('standard_price', '>', 0)->exists();
        $query->where('is_active', true);
        $query->whereDoesntHave('packageInclusions', function (Builder $items) use ($allowCasketReplacement) {
            $items->where('service_type', Package::SERVICE_CASKET)->whereNotNull('casket_catalog_id')
                ->where(function (Builder $dependency) use ($allowCasketReplacement) {
                    $dependency->whereDoesntHave('casketCatalog');
                    $dependency->orWhereHas('casketCatalog', fn (Builder $c) => $c->where('is_active', false));
                    if (! $allowCasketReplacement) $dependency->orWhereHas('casketCatalog', fn (Builder $c) => $c->where('is_active', false)->orWhere('is_available', false));
                    else $dependency->orWhereHas('casketCatalog', fn (Builder $c) => $c->where('standard_price', '<=', 0)->where(fn (Builder $bad) => $bad->where('is_active', false)->orWhere('is_available', false)));
                });
        });
        return $query->whereDoesntHave('packageInclusions', fn (Builder $items) => $items->where('service_type', Package::SERVICE_HEARSE)
            ->whereDoesntHave('hearseCatalog', fn (Builder $c) => $c->where('is_active', true)->where('is_available', true)))
            ->whereDoesntHave('packageFreebies', fn (Builder $items) => $items->whereNotNull('freebie_catalog_id')
                ->whereDoesntHave('catalog', fn (Builder $c) => $c->where('is_active', true)->where('is_available', true)));
    }

    public function activePackagesUsingCasket(CasketCatalog $casket): Collection
    {
        return Package::query()->where('is_active', true)
            ->whereHas('packageInclusions', fn (Builder $items) => $items->where('casket_catalog_id', $casket->id))
            ->orderBy('name')->get(['id', 'name']);
    }

    public function activePackagesUsingFreebie(FreebieCatalog $freebie): Collection
    {
        return Package::query()->where('is_active', true)
            ->whereHas('packageFreebies', fn (Builder $items) => $items->where('freebie_catalog_id', $freebie->id))
            ->orderBy('name')->get(['id', 'name']);
    }

    public function packageBlockers(Package $package, bool $ignoreArchived = false): array
    {
        $package = $package->fresh(['packageInclusions.casketCatalog', 'packageInclusions.hearseCatalog', 'packageFreebies.catalog']) ?? $package;
        if (! $package->is_active && ! $ignoreArchived) {
            return ['Package is archived.'];
        }

        // Always resolve the latest saved selections, including after catalog status changes.
        $reasons = [];
        foreach ($package->packageInclusions as $item) {
            if ($item->service_type === Package::SERVICE_HEARSE) {
                $catalog = $item->hearseCatalog;
                if (! $catalog) $reasons[] = 'No hearse selected. Edit this package to choose one.';
                elseif (! $catalog->is_active) $reasons[] = 'Included hearse "'.$catalog->name.'" is archived. Edit this package and choose an active hearse.';
                elseif (! $catalog->is_available) $reasons[] = 'Included hearse "'.$catalog->name.'" is unavailable. Edit this package and choose an available hearse.';
            }
            if ($item->service_type === Package::SERVICE_CASKET && $item->casket_catalog_id) {
                $catalog = $item->casketCatalog;
                $name = $catalog?->name ?: $item->casket_type ?: 'Unknown casket';
                if (! $catalog?->is_active) $reasons[] = 'Included casket "'.$name.'" is archived. Choose an active casket in package settings.';
                elseif (! $catalog->is_available) $reasons[] = 'Included casket "'.$name.'" is unavailable. Choose an available casket or replace it during intake.';
            }
        }
        foreach ($package->packageFreebies as $item) {
            if (! $item->freebie_catalog_id) continue;
            $catalog = $item->catalog;
            $name = $catalog?->name ?: $item->freebie_name;
            if (! $catalog?->is_active) $reasons[] = 'Included freebie "'.$name.'" is archived. Edit this package to replace or remove it.';
            elseif (! $catalog->is_available) $reasons[] = 'Included freebie "'.$name.'" is unavailable. Edit this package to replace or remove it.';
        }
        return $reasons;
    }

    public function activeRequiredPackages($catalog): Collection
    {
        // Disabled service inputs are not persisted by PackageController.
        // Add-ons are optional selections, never required package dependencies.
        if ($catalog instanceof \App\Models\AddOnCatalog) return collect();
        if ($catalog instanceof FreebieCatalog) {
            return Package::where('is_active', true)->whereHas('packageFreebies', fn (Builder $items) => $items->where('freebie_catalog_id', $catalog->id))->orderBy('name')->get(['id', 'name']);
        }
        $type = $catalog instanceof CasketCatalog ? Package::SERVICE_CASKET : Package::SERVICE_HEARSE;
        $field = $catalog instanceof CasketCatalog ? 'casket_catalog_id' : 'hearse_catalog_id';
        return Package::where('is_active', true)->whereHas('packageInclusions', fn (Builder $items) => $items->where('service_type', $type)->where($field, $catalog->id))->orderBy('name')->get(['id', 'name']);
    }

    public function archiveBlockMessage($catalog): ?string
    {
        if (! $catalog->is_active) return null;
        $names = $this->activeRequiredPackages($catalog)->pluck('name');
        return $names->isEmpty() ? null : 'Cannot archive this item. It is still used by these active packages: '.$names->implode(', ').'. Update those packages first.';
    }
}

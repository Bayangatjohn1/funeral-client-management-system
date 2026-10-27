<?php

namespace App\Support;

use App\Models\CasketCatalog;
use App\Models\FreebieCatalog;
use App\Models\Package;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ServiceCatalogPolicy
{
    public function intakePackages(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->whereDoesntHave('packageInclusions', fn (Builder $items) => $items
                ->whereNotNull('casket_catalog_id')
                ->whereHas('casketCatalog', fn (Builder $catalog) => $catalog
                    ->where('is_active', false)->orWhere('is_available', false)))
            ->whereDoesntHave('packageFreebies', fn (Builder $items) => $items
                ->whereNotNull('freebie_catalog_id')
                ->whereHas('catalog', fn (Builder $catalog) => $catalog->where('is_active', false)));
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
        if (! $package->is_active && ! $ignoreArchived) {
            return ['Package is archived.'];
        }

        $package->loadMissing(['packageInclusions.casketCatalog', 'packageFreebies.catalog']);
        $reasons = [];
        foreach ($package->packageInclusions as $item) {
            if ($item->casket_catalog_id && (! $item->casketCatalog?->is_active || ! $item->casketCatalog?->is_available)) {
                $reasons[] = 'Included casket is unavailable: '.($item->casketCatalog?->name ?: $item->casket_type ?: 'Unknown casket').'.';
            }
        }
        foreach ($package->packageFreebies as $item) {
            if ($item->freebie_catalog_id && ! $item->catalog?->is_active) {
                $reasons[] = 'Included freebie is archived: '.$item->freebie_name.'.';
            }
        }

        return $reasons;
    }
}

<?php

namespace App\Support;

use App\Models\AddOnCatalog;
use App\Models\CasketCatalog;
use App\Models\HearseCatalog;
use App\Models\Package;
use App\Support\Discount\CaseDiscountResolver;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class IntakePricingService
{
    public function __construct(private readonly CaseDiscountResolver $discountResolver)
    {
    }

    public function price(?Package $package, array $validated, bool $isCustomPackage = false, ?Carbon $referenceAt = null): array
    {
        $referenceAt ??= now();
        $wakeDuration = WakeDuration::calculate($validated['wake_start_date'] ?? null, $validated['wake_end_date'] ?? null);
        $wakeDays = $wakeDuration['days'] ?? 0;
        $wakeNights = $wakeDuration['nights'] ?? 0;
        $basePrice = round((float) ($isCustomPackage ? ($validated['custom_package_price'] ?? 0) : ($package?->price ?? 0)), 2);
        $additionalItems = $this->additionalItems($validated);
        $additionalTotal = round(collect($additionalItems)->sum('amount'), 2);

        if ($isCustomPackage) {
            [$selectedAddOns, $addOnsTotal] = $this->selectedAddOns($validated['selected_add_ons'] ?? [], (array) ($validated['add_on_quantities'] ?? []));
            $discount = $this->automaticDiscount($validated, $basePrice, null, $referenceAt);
            $configuration = $validated['custom_package_configuration'] ?? null;
            $charges = $configuration['charges'] ?? [];
            $subtotal = round($basePrice + $addOnsTotal + $additionalTotal + collect($charges)->sum('amount'), 2);
            $result = $this->buildResult(null, $basePrice, $charges, $selectedAddOns, $addOnsTotal, $additionalItems, $additionalTotal, $subtotal, $discount, (float) ($validated['tax_rate'] ?? 0), $wakeDays, $wakeNights, true, null, referenceAt: $referenceAt);
            $result['snapshot']['inclusions'] = $configuration['inclusions'] ?? $this->customItems($validated['custom_package_inclusions'] ?? null, true);
            $result['snapshot']['freebies'] = $configuration['freebies'] ?? $this->customItems($validated['custom_package_freebies'] ?? null, false);
            $result['included_casket'] = $configuration['included_casket'] ?? null;
            $result['snapshot']['included_casket'] = $configuration['included_casket'] ?? null;
            $result['snapshot']['selected_hearse'] = $validated['selected_hearse'] ?? null;
            return $result;
        }

        $package?->loadMissing(['packageInclusions.casketCatalog', 'packageInclusions.hearseCatalog', 'packageInclusions.addOnCatalog', 'packageFreebies', 'promotion']);
        $serviceCharges = $this->serviceCharges($package, $validated, $wakeDays);
        if ($casketCharge = $this->casketUpgradeCharge($package, $validated)) {
            $serviceCharges[] = $casketCharge;
        }
        if ($hearseCharge = $this->hearseUpgradeCharge($package, $validated)) {
            $serviceCharges[] = $hearseCharge;
        }
        $adjustments = $this->adjustmentSnapshot($package, $validated, $serviceCharges, $casketCharge);

        [$selectedAddOns, $addOnsTotal] = $this->selectedAddOns($validated['selected_add_ons'] ?? [], (array) ($validated['add_on_quantities'] ?? []));
        $serviceChargesTotal = round(collect($serviceCharges)->sum('amount'), 2);
        $subtotal = round($basePrice + $serviceChargesTotal + $addOnsTotal + $additionalTotal, 2);
        $discount = $this->automaticDiscount($validated, $basePrice, $package, $referenceAt);

        $result = $this->buildResult($package, $basePrice, $serviceCharges, $selectedAddOns, $addOnsTotal, $additionalItems, $additionalTotal, $subtotal, $discount, (float) ($validated['tax_rate'] ?? 0), $wakeDays, $wakeNights, false, $casketCharge['selected_casket'] ?? null, $adjustments, $referenceAt);
        $result['snapshot']['selected_hearse'] = $validated['selected_hearse'] ?? null;
        $result['snapshot']['included_hearse'] = $this->includedHearse($package);
        $result['snapshot']['included_add_ons'] = $package?->packageInclusions
            ->whereNotNull('add_on_catalog_id')
            ->map(fn ($row) => [
                'id' => $row->add_on_catalog_id,
                'name' => $row->inclusion_name,
                'included_quantity' => $row->included_quantity,
                'requested_quantity' => (int) ($validated['included_add_on_requested'][$row->add_on_catalog_id] ?? $row->included_quantity),
                'unit' => $row->addOnCatalog?->unit ?: 'item',
            ])->values()->all() ?? [];
        return $result;
    }

    public function validateSelections(?Package $package, array $validated, bool $isCustomPackage): array
    {
        $ids = collect($validated['selected_add_ons'] ?? [])->filter()->map(fn ($id) => (int) $id)->values();
        if ($ids->duplicates()->isNotEmpty()) {
            return ['selected_add_ons' => 'Duplicate add-ons are not allowed.'];
        }

        foreach ((array) ($validated['add_on_quantities'] ?? []) as $id => $quantity) {
            if (! $ids->contains((int) $id)) {
                continue;
            }
            if (filter_var($quantity, FILTER_VALIDATE_INT) === false || (int) $quantity < 1) {
                return ["add_on_quantities.{$id}" => 'Quantity must be a positive whole number.'];
            }
        }

        if ($ids->isNotEmpty() && AddOnCatalog::whereIn('id', $ids)->where('is_active', true)->where('is_available', true)->count() !== $ids->count()) {
            return ['selected_add_ons' => 'Selected add-on is no longer available.'];
        }

        if (! $isCustomPackage && $package) {
            $package->loadMissing(['packageInclusions.casketCatalog', 'packageInclusions.addOnCatalog']);
            $allowances = $package->packageInclusions->whereNotNull('add_on_catalog_id')->keyBy('add_on_catalog_id');
            if ($ids->intersect($allowances->keys())->isNotEmpty()) {
                return ['selected_add_ons' => 'Use Other Inclusions in the selected package to request extra units.'];
            }
            foreach ($validated['included_add_on_requested'] ?? [] as $id => $requested) {
                if ($requested === null) {
                    continue;
                }
                $allowance = $allowances->get($id);
                if (! $allowance) {
                    return ['included_add_on_requested' => 'The package inclusions changed. Review the selected package and enter the quantities again.'];
                }
                $catalog = $allowance->addOnCatalog;
                if ($requested > $allowance->included_quantity && (! $catalog?->is_active || ! $catalog->is_available
                    || ((float) $catalog->price <= 0 && $catalog->category !== AddOnCatalog::CHAPEL_VIEWING))) {
                    return ["included_add_on_requested.$id" => 'Extra units require an available add-on with a configured rate.'];
                }
            }
            $row = ($package->fresh(['packageInclusions.casketCatalog']) ?? $package)
                ->packageInclusions->firstWhere('service_type', Package::SERVICE_CASKET);
            if ($row?->casket_catalog_id && ! $row->casketCatalog?->is_active) {
                return ['package_id' => 'Included casket is archived. Choose an active casket in package settings.'];
            }
            if ($row?->casket_catalog_id && (! $row->casketCatalog?->is_active || ! $row->casketCatalog?->is_available)
                && empty($validated['replacement_casket_catalog_id'])) {
                return ['replacement_casket_catalog_id' => 'The included casket is unavailable. Select an available replacement before saving this case.'];
            }
        }

        if (! $isCustomPackage && ! empty($validated['replacement_casket_catalog_id'])) {
            $included = $this->includedCasket($package);
            $replacement = CasketCatalog::whereKey((int) $validated['replacement_casket_catalog_id'])
                ->where('is_active', true)
                ->where('is_available', true)
                ->first();

            if (! $replacement) {
                return ['replacement_casket_catalog_id' => 'Selected casket is unavailable.'];
            }
            if ((float) ($included['reference_value'] ?? 0) <= 0) {
                return ['replacement_casket_catalog_id' => 'Included casket reference value must be configured before upgrade calculation.'];
            }
            if ((float) $replacement->standard_price <= 0) {
                return ['replacement_casket_catalog_id' => 'Selected casket reference value is not configured.'];
            }
        }

        if (! $isCustomPackage && $package && ! empty($validated['hearse_catalog_id'])) {
            $included = $this->includedHearse($package);
            $selected = HearseCatalog::whereKey((int) $validated['hearse_catalog_id'])->where('is_active', true)->where('is_available', true)->first();
            if (! $included || ! $selected) return ['hearse_catalog_id' => 'Choose an available hearse included in this service.'];
            if ((int) $included['id'] !== $selected->id && ($included['reference_value'] === null || $selected->reference_value === null)) {
                return ['hearse_catalog_id' => 'Configure reference values for both hearses before selecting an upgrade.'];
            }
        }

        return [];
    }

    private function serviceCharges(?Package $package, array $validated, int $wakeDays): array
    {
        if (! $package) {
            return [];
        }

        $charges = [];
        $rows = $this->rowsByServiceType($package);
        foreach ([
            Package::SERVICE_BODY_RETRIEVAL => ['apply_retrieval_excess', 'retrieval_excess_kilometers', 'actual_retrieval_kilometers'],
            Package::SERVICE_HEARSE => ['apply_hearse_excess', 'hearse_excess_kilometers', 'actual_hearse_kilometers'],
        ] as $type => [$applyField, $excessField, $legacyActualField]) {
            $row = $rows->get($type);
            $included = (float) ($row?->included_kilometers ?? 0);
            $rate = (float) ($row?->price_per_excess_kilometer ?? 0);
            $usesExplicitAdjustment = array_key_exists($applyField, $validated) || array_key_exists($excessField, $validated);
            $applied = (bool) ($validated[$applyField] ?? false);
            $excess = $usesExplicitAdjustment
                ? ($applied ? round((float) ($validated[$excessField] ?? 0), 2) : 0.00)
                : max(round((float) ($validated[$legacyActualField] ?? 0), 2) - $included, 0);
            $amount = round($excess * $rate, 2);
            if ($row && $amount > 0) {
                $charges[] = ['type' => $type, 'label' => Package::serviceTypeOptions()[$type] ?? $type, 'applied' => true, 'included' => $included, 'rate' => $rate, 'excess' => $excess, 'amount' => $amount];
            }
        }

        foreach ([Package::SERVICE_EMBALMING, Package::SERVICE_HOME_VIEWING] as $type) {
            if ($type === Package::SERVICE_HOME_VIEWING && ($validated['viewing_type'] ?? null) === 'chapel') continue;
            $row = $rows->get($type);
            $included = (int) ($row?->included_days ?? 0);
            $rate = (float) ($row?->price_per_extended_day ?? 0);
            $extended = max($wakeDays - $included, 0);
            $amount = round($extended * $rate, 2);
            if ($row && $amount > 0) {
                $charges[] = ['type' => $type, 'label' => Package::serviceTypeOptions()[$type] ?? $type, 'actual' => $wakeDays, 'included' => $included, 'rate' => $rate, 'excess' => $extended, 'amount' => $amount];
            }
        }

        foreach ($package->packageInclusions->whereNotNull('add_on_catalog_id') as $row) {
            $catalog = $row->addOnCatalog;
            $included = (int) $row->included_quantity;
            $requested = (int) ($validated['included_add_on_requested'][$row->add_on_catalog_id] ?? $included);
            $extra = max(0, $requested - $included);
            if ($extra > 0 && $catalog?->is_active && $catalog->is_available && (float) $catalog->price > 0) {
                $charges[] = [
                    'type' => 'included_add_on_extra',
                    'label' => 'Extra ' . $catalog->name,
                    'add_on_catalog_id' => $catalog->id,
                    'unit' => $catalog->unit ?: 'item',
                    'actual' => $requested,
                    'included' => $included,
                    'rate' => (float) $catalog->price,
                    'excess' => $extra,
                    'amount' => round($extra * (float) $catalog->price, 2),
                ];
            }
        }

        return $charges;
    }

    private function casketUpgradeCharge(?Package $package, array $validated): ?array
    {
        if (! $package || empty($validated['replacement_casket_catalog_id'])) {
            return null;
        }

        $included = $this->includedCasket($package);
        $selected = CasketCatalog::find((int) $validated['replacement_casket_catalog_id']);
        if (! $included || ! $selected) {
            return null;
        }

        $amount = round(max((float) $selected->standard_price - (float) ($included['reference_value'] ?? 0), 0), 2);

        return [
            'type' => 'casket_upgrade',
            'label' => 'Casket replacement / upgrade',
            'included' => (float) ($included['reference_value'] ?? 0),
            'selected' => (float) $selected->standard_price,
            'amount' => $amount,
            'selected_casket' => ['id' => $selected->id, 'name' => $selected->name, 'material' => $selected->type_or_material, 'reference_value' => (float) $selected->standard_price],
        ];
    }

    private function includedHearse(?Package $package): ?array
    {
        $catalog = $package?->packageInclusions->firstWhere('service_type', Package::SERVICE_HEARSE)?->hearseCatalog;
        return $catalog ? ['id' => $catalog->id, 'name' => $catalog->name, 'reference_value' => $catalog->reference_value !== null ? (float) $catalog->reference_value : null] : null;
    }

    private function hearseUpgradeCharge(?Package $package, array $validated): ?array
    {
        $included = $this->includedHearse($package);
        if (! $included || empty($validated['hearse_catalog_id']) || (int) $validated['hearse_catalog_id'] === (int) $included['id']) return null;
        $selected = HearseCatalog::find((int) $validated['hearse_catalog_id']);
        if (! $selected || $included['reference_value'] === null || $selected->reference_value === null) return null;
        return ['type' => 'hearse_upgrade', 'label' => 'Hearse upgrade', 'included' => $included['reference_value'], 'selected' => (float) $selected->reference_value,
            'amount' => round(max(0, (float) $selected->reference_value - $included['reference_value']), 2),
            'included_hearse' => $included,
            'selected_hearse' => ['id' => $selected->id, 'name' => $selected->name, 'reference_value' => (float) $selected->reference_value]];
    }

    private function selectedAddOns(array $ids, array $quantities = []): array
    {
        $ids = collect($ids)->filter()->map(fn ($id) => (int) $id)->unique()->values();
        if ($ids->isEmpty()) {
            return [collect(), 0.00];
        }

        $catalogs = AddOnCatalog::whereIn('id', $ids)->where('is_active', true)->where('is_available', true)->get()->keyBy('id');
        $ordered = $ids->map(function ($id) use ($catalogs, $quantities) {
            $addOn = $catalogs->get($id);
            if (! $addOn) {
                return null;
            }

            $quantity = (int) ($quantities[$id] ?? 1);
            $lineTotal = round((float) $addOn->price * $quantity, 2);
            $addOn->setAttribute('quantity', $quantity);
            $addOn->setAttribute('line_total', $lineTotal);

            return $addOn;
        })->filter()->values();

        return [$ordered, round($ordered->sum(fn ($addOn) => (float) $addOn->line_total), 2)];
    }

    private function additionalItems(array $validated): array
    {
        $items = [];
        foreach (($validated['additional_service_items'] ?? []) as $item) {
            $description = trim((string) ($item['description'] ?? ''));
            $amount = round((float) ($item['amount'] ?? 0), 2);
            if ($description !== '' && $amount > 0) {
                $items[] = ['description' => $description, 'amount' => $amount];
            }
        }

        if ($items === [] && (float) ($validated['additional_service_amount'] ?? 0) > 0) {
            $items[] = ['description' => trim((string) ($validated['additional_services'] ?? 'Additional services')), 'amount' => round((float) $validated['additional_service_amount'], 2)];
        }

        return $items;
    }

    private function automaticDiscount(array $validated, float $packagePrice, ?Package $package, Carbon $referenceAt): array
    {
        if ((bool) ($validated['pwd_status'] ?? false)) {
            return $this->discountResolver->resolveSelected(new Package(['name' => 'Automatic PWD Discount']), 'PWD', $packagePrice, $referenceAt);
        }
        if ((bool) ($validated['senior_citizen_status'] ?? false)) {
            return $this->discountResolver->resolveSelected(new Package(['name' => 'Automatic Senior Discount']), 'SENIOR', $packagePrice, $referenceAt);
        }
        if ($package) {
            return $this->discountResolver->resolve($package, null, $packagePrice, $referenceAt);
        }

        return ['discount_type' => 'NONE', 'discount_value_type' => 'AMOUNT', 'discount_value' => 0, 'discount_amount' => 0, 'discount_note' => null, 'source' => 'None'];
    }

    private function buildResult(?Package $package, float $basePrice, array $serviceCharges, Collection $selectedAddOns, float $addOnsTotal, array $additionalItems, float $additionalTotal, float $subtotal, array $discountPayload, float $taxRate, int $wakeDays, int $wakeNights, bool $isCustomPackage, ?array $selectedCasket, array $adjustments = [], ?Carbon $referenceAt = null): array
    {
        $discountAmount = round((float) ($discountPayload['discount_amount'] ?? 0), 2);
        $net = round(max($subtotal - $discountAmount, 0), 2);
        $taxRate = round(max(min($taxRate, 100), 0), 2);
        $taxAmount = $taxRate > 0 ? round($net * ($taxRate / 100), 2) : 0.00;
        $total = round($net + $taxAmount, 2);
        $includedCasket = $package ? $this->includedCasket($package) : null;

        return [
            'base_price' => $basePrice,
            'service_charges' => $serviceCharges,
            'service_charges_total' => round(collect($serviceCharges)->sum('amount'), 2),
            'selected_add_ons' => $selectedAddOns,
            'add_ons_total' => $addOnsTotal,
            'additional_items' => $additionalItems,
            'additional_total' => $additionalTotal,
            'subtotal' => $subtotal,
            'discount' => $discountPayload,
            'discount_amount' => $discountAmount,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'total' => $total,
            'wake_days' => $wakeDays,
            'wake_nights' => $wakeNights,
            'wake_duration' => WakeDuration::labelFromDays($wakeDays),
            'included_casket' => $includedCasket,
            'selected_casket' => $selectedCasket,
            'snapshot' => [
                'version' => 1,
                'catalog_sync' => config('branch_sync.mode') === 'branch' ? [
                    'version' => \App\Services\BranchSync\SyncState::get('reference_hash'),
                    'last_synced_at' => \App\Services\BranchSync\SyncState::get('references_at'),
                ] : null,
                'custom_package' => $isCustomPackage,
                'package' => $package ? ['id' => $package->id, 'name' => $package->name, 'base_price' => $basePrice, 'description' => $package->short_description] : null,
                'inclusions' => $package ? $package->packageInclusions->map(fn ($row) => ['service_type' => Package::normalizeServiceType($row->service_type), 'name' => $row->inclusion_name, 'included_kilometers' => $row->included_kilometers, 'price_per_excess_kilometer' => (float) $row->price_per_excess_kilometer, 'included_days' => $row->included_days, 'price_per_extended_day' => (float) $row->price_per_extended_day, 'casket_type' => $row->casket_type, 'casket_catalog_id' => $row->casket_catalog_id])->values()->all() : [],
                'freebies' => $package ? $package->packageFreebies->map(fn ($row) => ['name' => $row->freebie_name, 'quantity' => $row->quantity, 'unit' => $row->unit])->values()->all() : [],
                'included_casket' => $includedCasket,
                'selected_casket' => $selectedCasket,
                'promo' => $this->promoSnapshot($package, $referenceAt),
                'adjustments' => $adjustments,
                'wake_days' => $wakeDays,
                'wake_nights' => $wakeNights,
                'wake_duration' => WakeDuration::labelFromDays($wakeDays),
                'service_charges' => $serviceCharges,
                'add_ons' => $selectedAddOns->map(fn ($addOn) => ['id' => $addOn->id, 'name' => $addOn->name, 'category' => $addOn->category, 'description' => $addOn->description, 'price' => (float) $addOn->price, 'unit' => $addOn->unit, 'quantity' => (int) $addOn->quantity, 'line_total' => (float) $addOn->line_total])->values()->all(),
                'additional_items' => $additionalItems,
                'discount' => $discountPayload,
                'totals' => ['subtotal' => $subtotal, 'discount' => $discountAmount, 'tax' => $taxAmount, 'total' => $total],
            ],
        ];
    }

    private function includedCasket(?Package $package): ?array
    {
        $row = $package ? $this->rowsByServiceType($package)->get(Package::SERVICE_CASKET) : null;
        if (! $row) {
            return null;
        }

        $catalog = $row->casketCatalog;

        return ['id' => $catalog?->id, 'name' => $catalog?->name ?: $row->casket_type, 'material' => $catalog?->type_or_material, 'reference_value' => (float) ($catalog?->standard_price ?? 0), 'legacy_text' => $row->casket_type];
    }

    private function promoSnapshot(?Package $package, ?Carbon $referenceAt): ?array
    {
        if (! $package || ! $package->isPromoEffectiveAt($referenceAt)) {
            return null;
        }

        $promotion = $package->relationLoaded('promotion') ? $package->promotion : $package->promotion()->first();
        $label = $promotion?->name ?? $package->promo_label ?: 'Package Promo';
        $valueType = $promotion?->discount_type ?? $package->promo_value_type;
        $value = $promotion?->discount_value ?? $package->promo_value;

        return [
            'promotion_id' => $promotion?->id,
            'label' => $label,
            'value_type' => $valueType,
            'value' => (float) $value,
            'starts_at' => $promotion?->starts_at?->format('Y-m-d') ?? $package->promo_starts_at?->copy()->timezone('Asia/Manila')->toDateTimeString(),
            'ends_at' => $promotion?->ends_at?->format('Y-m-d') ?? $package->promo_ends_at?->copy()->timezone('Asia/Manila')->toDateTimeString(),
            'status' => 'Applied at intake',
        ];
    }

    private function adjustmentSnapshot(?Package $package, array $validated, array $serviceCharges, ?array $casketCharge): array
    {
        if (! $package) {
            return [];
        }

        $rows = $this->rowsByServiceType($package);
        $charges = collect($serviceCharges)->keyBy('type');
        $distanceAdjustment = function (string $type, string $applyField, string $excessField, string $legacyActualField) use ($rows, $charges, $validated): array {
            $row = $rows->get($type);
            $usesExplicitAdjustment = array_key_exists($applyField, $validated) || array_key_exists($excessField, $validated);
            $applied = $usesExplicitAdjustment
                ? (bool) ($validated[$applyField] ?? false)
                : $charges->has($type);
            $included = (float) ($row?->included_kilometers ?? 0);
            $rate = (float) ($row?->price_per_excess_kilometer ?? 0);
            $excess = $usesExplicitAdjustment
                ? ($applied ? round((float) ($validated[$excessField] ?? 0), 2) : 0.00)
                : max(round((float) ($validated[$legacyActualField] ?? 0), 2) - $included, 0);
            $amount = round($excess * $rate, 2);

            return [
                'applied' => $applied,
                'included_kilometers' => $included,
                'excess_kilometers' => $excess,
                'rate' => $rate,
                'charge' => $amount,
            ];
        };

        return [
            'casket' => [
                'applied' => $casketCharge !== null,
                'included_reference_value' => (float) ($casketCharge['included'] ?? $this->includedCasket($package)['reference_value'] ?? 0),
                'selected_reference_value' => (float) ($casketCharge['selected'] ?? 0),
                'charge' => (float) ($casketCharge['amount'] ?? 0),
            ],
            'body_retrieval' => $distanceAdjustment(Package::SERVICE_BODY_RETRIEVAL, 'apply_retrieval_excess', 'retrieval_excess_kilometers', 'actual_retrieval_kilometers'),
            'hearse' => $distanceAdjustment(Package::SERVICE_HEARSE, 'apply_hearse_excess', 'hearse_excess_kilometers', 'actual_hearse_kilometers'),
        ];
    }

    private function rowsByServiceType(Package $package): Collection
    {
        return $package->packageInclusions->keyBy(fn ($row) => Package::normalizeServiceType($row->service_type));
    }

    private function customItems(?string $raw, bool $inclusions): array
    {
        $items = preg_split('/[,;\r\n]+/u', (string) $raw) ?: [];

        return collect($items)
            ->map(fn ($item) => trim($item))
            ->filter()
            ->map(fn ($item) => $inclusions
                ? ['service_type' => Package::SERVICE_CUSTOM, 'name' => $item]
                : ['name' => $item, 'quantity' => null, 'unit' => ''])
            ->values()
            ->all();
    }
}

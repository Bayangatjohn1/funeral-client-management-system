<?php

namespace App\Support;

use App\Models\AddOnCatalog;
use App\Models\CasketCatalog;
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
        $wakeDays = $this->inclusiveDays($validated['wake_start_date'] ?? null, $validated['interment_at'] ?? null);
        $basePrice = round((float) ($isCustomPackage ? ($validated['custom_package_price'] ?? 0) : ($package?->price ?? 0)), 2);
        $additionalItems = $this->additionalItems($validated);
        $additionalTotal = round(collect($additionalItems)->sum('amount'), 2);

        if ($isCustomPackage) {
            $discount = $this->automaticDiscount($validated, $basePrice, null, $referenceAt);
            return $this->buildResult(null, $basePrice, [], collect(), 0.00, $additionalItems, $additionalTotal, round($basePrice + $additionalTotal, 2), $discount, (float) ($validated['tax_rate'] ?? 0), $wakeDays, true, null);
        }

        $package?->loadMissing(['packageInclusions.casketCatalog', 'packageFreebies']);
        $serviceCharges = $this->serviceCharges($package, $validated, $wakeDays);
        if ($casketCharge = $this->casketUpgradeCharge($package, $validated)) {
            $serviceCharges[] = $casketCharge;
        }

        [$selectedAddOns, $addOnsTotal] = $this->selectedAddOns($validated['selected_add_ons'] ?? []);
        $serviceChargesTotal = round(collect($serviceCharges)->sum('amount'), 2);
        $subtotal = round($basePrice + $serviceChargesTotal + $addOnsTotal + $additionalTotal, 2);
        $discount = $this->automaticDiscount($validated, $basePrice, $package, $referenceAt);

        return $this->buildResult($package, $basePrice, $serviceCharges, $selectedAddOns, $addOnsTotal, $additionalItems, $additionalTotal, $subtotal, $discount, (float) ($validated['tax_rate'] ?? 0), $wakeDays, false, $casketCharge['selected_casket'] ?? null);
    }

    public function validateSelections(?Package $package, array $validated, bool $isCustomPackage): array
    {
        if ($isCustomPackage) {
            return [];
        }

        $ids = collect($validated['selected_add_ons'] ?? [])->filter()->map(fn ($id) => (int) $id)->values();
        if ($ids->duplicates()->isNotEmpty()) {
            return ['selected_add_ons' => 'Duplicate add-ons are not allowed.'];
        }

        if ($ids->isNotEmpty() && AddOnCatalog::whereIn('id', $ids)->where('is_active', true)->count() !== $ids->count()) {
            return ['selected_add_ons' => 'Selected add-on is no longer available.'];
        }

        if (! empty($validated['replacement_casket_catalog_id'])) {
            $included = $this->includedCasket($package);
            $replacement = CasketCatalog::whereKey((int) $validated['replacement_casket_catalog_id'])
                ->where('is_active', true)
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

        return [];
    }

    private function serviceCharges(?Package $package, array $validated, int $wakeDays): array
    {
        if (! $package) {
            return [];
        }

        $charges = [];
        $rows = $this->rowsByServiceType($package);
        foreach ([Package::SERVICE_BODY_RETRIEVAL => 'actual_retrieval_kilometers', Package::SERVICE_HEARSE => 'actual_hearse_kilometers'] as $type => $field) {
            $row = $rows->get($type);
            $actual = round((float) ($validated[$field] ?? 0), 2);
            $included = (float) ($row?->included_kilometers ?? 0);
            $rate = (float) ($row?->price_per_excess_kilometer ?? 0);
            $excess = max($actual - $included, 0);
            $amount = round($excess * $rate, 2);
            if ($row && $amount > 0) {
                $charges[] = ['type' => $type, 'label' => Package::serviceTypeOptions()[$type] ?? $type, 'actual' => $actual, 'included' => $included, 'rate' => $rate, 'excess' => $excess, 'amount' => $amount];
            }
        }

        foreach ([Package::SERVICE_EMBALMING, Package::SERVICE_HOME_VIEWING] as $type) {
            $row = $rows->get($type);
            $included = (int) ($row?->included_days ?? 0);
            $rate = (float) ($row?->price_per_extended_day ?? 0);
            $extended = max($wakeDays - $included, 0);
            $amount = round($extended * $rate, 2);
            if ($row && $amount > 0) {
                $charges[] = ['type' => $type, 'label' => Package::serviceTypeOptions()[$type] ?? $type, 'actual' => $wakeDays, 'included' => $included, 'rate' => $rate, 'excess' => $extended, 'amount' => $amount];
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

    private function selectedAddOns(array $ids): array
    {
        $ids = collect($ids)->filter()->map(fn ($id) => (int) $id)->unique()->values();
        if ($ids->isEmpty()) {
            return [collect(), 0.00];
        }

        $catalogs = AddOnCatalog::whereIn('id', $ids)->where('is_active', true)->get()->keyBy('id');
        $ordered = $ids->map(fn ($id) => $catalogs->get($id))->filter()->values();

        return [$ordered, round($ordered->sum(fn ($addOn) => (float) $addOn->price), 2)];
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

    private function buildResult(?Package $package, float $basePrice, array $serviceCharges, Collection $selectedAddOns, float $addOnsTotal, array $additionalItems, float $additionalTotal, float $subtotal, array $discountPayload, float $taxRate, int $wakeDays, bool $isCustomPackage, ?array $selectedCasket): array
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
            'included_casket' => $includedCasket,
            'selected_casket' => $selectedCasket,
            'snapshot' => [
                'version' => 1,
                'custom_package' => $isCustomPackage,
                'package' => $package ? ['id' => $package->id, 'name' => $package->name, 'base_price' => $basePrice, 'description' => $package->short_description] : null,
                'inclusions' => $package ? $package->packageInclusions->map(fn ($row) => ['service_type' => Package::normalizeServiceType($row->service_type), 'name' => $row->inclusion_name, 'included_kilometers' => $row->included_kilometers, 'price_per_excess_kilometer' => (float) $row->price_per_excess_kilometer, 'included_days' => $row->included_days, 'price_per_extended_day' => (float) $row->price_per_extended_day, 'casket_type' => $row->casket_type, 'casket_catalog_id' => $row->casket_catalog_id])->values()->all() : [],
                'freebies' => $package ? $package->packageFreebies->map(fn ($row) => ['name' => $row->freebie_name, 'quantity' => $row->quantity, 'unit' => $row->unit])->values()->all() : [],
                'included_casket' => $includedCasket,
                'selected_casket' => $selectedCasket,
                'wake_days' => $wakeDays,
                'service_charges' => $serviceCharges,
                'add_ons' => $selectedAddOns->map(fn ($addOn) => ['id' => $addOn->id, 'name' => $addOn->name, 'category' => $addOn->category, 'description' => $addOn->description, 'price' => (float) $addOn->price, 'unit' => $addOn->unit])->values()->all(),
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

    private function inclusiveDays(?string $start, ?string $end): int
    {
        if (! $start || ! $end) {
            return 0;
        }

        $startDate = Carbon::parse($start)->startOfDay();
        $endDate = Carbon::parse($end)->startOfDay();
        if ($endDate->lt($startDate)) {
            return 0;
        }

        return min(365, $startDate->diffInDays($endDate) + 1);
    }

    private function rowsByServiceType(Package $package): Collection
    {
        return $package->packageInclusions->keyBy(fn ($row) => Package::normalizeServiceType($row->service_type));
    }
}

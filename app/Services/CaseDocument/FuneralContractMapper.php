<?php

namespace App\Services\CaseDocument;

use App\Models\Package;
use Illuminate\Support\Arr;

class FuneralContractMapper
{
    public function map(array $snapshot): array
    {
        $isLegacy = (bool) Arr::get($snapshot, 'legacy', false);
        $inclusions = collect(Arr::get($snapshot, 'structured_inclusions', []))
            ->filter(fn ($row) => is_array($row))
            ->mapWithKeys(function (array $row): array {
                $type = Package::normalizeServiceType($row['service_type'] ?? null);

                return $type ? [$type => $row] : [];
            });

        $serviceCharges = $this->canonicalServiceCharges($snapshot);
        $fixedRows = $this->fixedAdditionalRows($snapshot, $serviceCharges);
        $automaticRows = collect($fixedRows)->filter(fn ($row) => (float) ($row['amount'] ?? 0) > 0)->values()->all();

        $addOns = collect(Arr::get($snapshot, 'add_ons', []))
            ->filter(fn ($row) => is_array($row))
            ->map(fn (array $row): array => $this->addOnLine($row))
            ->unique(fn ($row) => strtolower($row['label']) . '|' . $row['quantity'] . '|' . $row['unit'] . '|' . $row['rate'] . '|' . $row['amount'])
            ->values()
            ->all();

        $itemized = collect(Arr::get($snapshot, 'additional_items', []))
            ->filter(fn ($row) => is_array($row) && trim((string) ($row['description'] ?? '')) !== '')
            ->map(fn (array $row): array => $this->itemizedLine($row))
            ->values()
            ->all();

        $freebies = collect(Arr::get($snapshot, 'freebies', []))
            ->filter()
            ->values()
            ->all();

        $basicOther = collect(Arr::get($snapshot, 'basic_inclusions', []))
            ->filter()
            ->reject(fn ($item) => preg_match('/body retrieval|embalming|casket|coffin|home viewing|hearse/i', (string) $item))
            ->reject(fn ($item) => $this->isVaguePackageDescription((string) $item))
            ->values()
            ->all();

        $includedCasket = Arr::get($snapshot, 'included_casket') ?: [];
        $selectedCasket = Arr::get($snapshot, 'selected_casket') ?: [];
        $discount = Arr::get($snapshot, 'discount', []);
        $promo = Arr::get($snapshot, 'promo', []);
        $discountAmount = (float) Arr::get($snapshot, 'totals.discount', Arr::get($discount, 'discount_amount', 0));
        $discountLabel = $this->discountLabel($discount, $promo, (string) Arr::get($snapshot, 'remarks', ''), $discountAmount);
        $automaticTotal = round(collect($automaticRows)->sum(fn ($row) => (float) ($row['amount'] ?? 0)), 2);
        $addOnsTotal = round(collect($addOns)->sum(fn ($row) => (float) ($row['amount'] ?? 0)), 2);
        $itemizedTotal = round(collect($itemized)->sum(fn ($row) => (float) ($row['amount'] ?? 0)), 2);
        $totalAdditionalServices = round($automaticTotal + $addOnsTotal + $itemizedTotal, 2);
        $basePackagePrice = (float) Arr::get($snapshot, 'package.base_price', 0);
        $savedSubtotal = (float) Arr::get($snapshot, 'totals.subtotal', round($basePackagePrice + $totalAdditionalServices, 2));
        $savedTax = (float) Arr::get($snapshot, 'totals.tax', 0);
        $contractTotal = (float) Arr::get($snapshot, 'totals.total', 0);
        $reconciliation = $this->reconciliation($isLegacy, $basePackagePrice, $totalAdditionalServices, $savedSubtotal, $discountAmount, $savedTax, $contractTotal);

        return [
            'contract_number' => (string) Arr::get($snapshot, 'contract_number', ''),
            'case_number' => (string) (Arr::get($snapshot, 'case.case_code') ?: Arr::get($snapshot, 'case.case_number') ?: ''),
            'contract_date' => Arr::get($snapshot, 'contract_date'),
            'selected_package' => [
                'name' => $this->selectedPackageName($snapshot),
                'base_price' => $basePackagePrice,
            ],
            'basic_rows' => [
                [
                    'label' => 'Body Retrieval',
                    'value' => $this->kmCoverage($inclusions->get(Package::SERVICE_BODY_RETRIEVAL), $isLegacy),
                ],
                [
                    'label' => 'Embalming',
                    'value' => $this->dayCoverage($inclusions->get(Package::SERVICE_EMBALMING), $isLegacy),
                ],
                [
                    'label' => 'Casket',
                    'value' => $this->casketName($includedCasket) ?: ($isLegacy ? 'Not available in historical record' : 'Not included'),
                ],
                [
                    'label' => 'Home Viewing Service',
                    'value' => $this->dayCoverage($inclusions->get(Package::SERVICE_HOME_VIEWING), $isLegacy),
                ],
                [
                    'label' => 'Regular Hearse',
                    'value' => $this->kmCoverage($inclusions->get(Package::SERVICE_HEARSE), $isLegacy),
                ],
            ],
            'other_inclusions' => $basicOther,
            'freebies' => $freebies,
            'automatic_charges' => $automaticRows,
            'physical_additional_rows' => $fixedRows,
            'add_ons' => $addOns,
            'itemized_services' => $itemized,
            'additional_service_lines' => array_values(array_merge($fixedRows, $addOns, $itemized)),
            'included_casket' => [
                'name' => $this->casketName($includedCasket),
                'reference_value' => (float) Arr::get($includedCasket, 'reference_value', 0),
            ],
            'selected_casket' => [
                'name' => $this->casketName($selectedCasket),
                'reference_value' => (float) Arr::get($selectedCasket, 'reference_value', 0),
            ],
            'totals' => [
                'base_package_price' => $basePackagePrice,
                'automatic_adjustments' => $automaticTotal,
                'add_ons' => $addOnsTotal,
                'itemized_services' => $itemizedTotal,
                'total_additional_services' => $totalAdditionalServices,
                'subtotal' => $savedSubtotal,
                'discount_label' => $discountLabel,
                'discount_amount' => $discountAmount,
                'tax' => $savedTax,
                'contract_total' => $contractTotal,
                'initial_deposit' => (float) Arr::get($snapshot, 'totals.deposit', 0),
                'total_paid' => (float) Arr::get($snapshot, 'payment.total_paid', 0),
                'remaining_balance' => (float) Arr::get($snapshot, 'totals.balance', 0),
            ],
            'reconciliation' => $reconciliation,
            'stipulations' => Arr::get($snapshot, 'stipulations', $this->pendingStipulations()),
        ];
    }

    private function kmCoverage(?array $row, bool $isLegacy): string
    {
        if (! $row || ($row['included_kilometers'] ?? null) === null) {
            return $isLegacy ? 'Not available in historical record' : 'Not configured';
        }

        return number_format((float) $row['included_kilometers'], 2) . ' km included';
    }

    private function dayCoverage(?array $row, bool $isLegacy): string
    {
        if (! $row || ($row['included_days'] ?? null) === null) {
            return $isLegacy ? 'Not available in historical record' : 'Not configured';
        }

        $days = (int) $row['included_days'];

        return $days . 'D/' . max($days - 1, 0) . 'N included';
    }

    private function canonicalServiceCharges(array $snapshot)
    {
        $charges = collect(Arr::get($snapshot, 'service_charges', []))
            ->filter(fn ($row) => is_array($row) && (float) ($row['amount'] ?? 0) > 0)
            ->map(function (array $row): array {
                $type = $this->chargeType($row);
                $row['type'] = $type;

                return $row;
            })
            ->filter(fn ($row) => $row['type'] !== '')
            ->unique(fn ($row) => $row['type'])
            ->values();

        if ($charges->isNotEmpty()) {
            return $charges;
        }

        $adjustments = Arr::get($snapshot, 'adjustments', []);
        if (! is_array($adjustments)) {
            return collect();
        }

        $fallback = [];
        foreach ([
            Package::SERVICE_BODY_RETRIEVAL => ['body_retrieval', 'excess_kilometers', 'km'],
            Package::SERVICE_HEARSE => ['hearse', 'excess_kilometers', 'km'],
        ] as $type => [$key, $quantityKey]) {
            $row = Arr::get($adjustments, $key, []);
            $amount = (float) ($row['charge'] ?? $row['amount'] ?? 0);
            if ($amount <= 0) {
                continue;
            }

            $fallback[] = [
                'type' => $type,
                'label' => Package::serviceTypeOptions()[$type] ?? $type,
                'excess' => (float) ($row[$quantityKey] ?? $row['quantity'] ?? 0),
                'rate' => (float) ($row['rate'] ?? 0),
                'amount' => $amount,
                'source' => 'pricing_snapshot.adjustments',
            ];
        }

        $casket = Arr::get($adjustments, 'casket', []);
        $casketAmount = (float) ($casket['charge'] ?? $casket['amount'] ?? 0);
        if ($casketAmount > 0) {
            $fallback[] = [
                'type' => Package::SERVICE_CASKET,
                'label' => 'Casket replacement / upgrade',
                'amount' => $casketAmount,
                'source' => 'pricing_snapshot.adjustments',
            ];
        }

        return collect($fallback);
    }

    private function fixedAdditionalRows(array $snapshot, $serviceCharges): array
    {
        return [
            $this->fixedRow(
                'body_retrieval_excess',
                'Excess # of Km.',
                $this->distanceChargeRow($serviceCharges, Package::SERVICE_BODY_RETRIEVAL, 'Excess # of Km.'),
                10
            ),
            $this->fixedRow(
                'embalming_extension',
                'Extended # of days',
                $this->dayChargeRow($serviceCharges, Package::SERVICE_EMBALMING, 'Extended # of days'),
                20
            ),
            $this->fixedRow(
                'casket_upgrade',
                'Type of Casket (Replacement / Upgrade)',
                $this->casketChargeRow($snapshot, $serviceCharges),
                30
            ),
            $this->fixedRow(
                'home_viewing_extension',
                'Extended # of days',
                $this->dayChargeRow($serviceCharges, Package::SERVICE_HOME_VIEWING, 'Extended # of days'),
                40
            ),
            $this->fixedRow(
                'hearse_excess',
                'Excess # of Km.',
                $this->distanceChargeRow($serviceCharges, Package::SERVICE_HEARSE, 'Excess # of Km.'),
                50
            ),
            $this->fixedRow('others', 'Others', null, 60),
        ];
    }

    private function fixedRow(string $type, string $label, ?array $charge, int $sortOrder): array
    {
        $amount = (float) ($charge['amount'] ?? 0);

        return [
            'type' => $type,
            'label' => $label,
            'details' => (string) ($charge['quantity_detail'] ?? $this->noChargeDetail($type)),
            'quantity' => $charge['quantity'] ?? null,
            'unit' => $charge['unit'] ?? null,
            'rate' => $charge['rate'] ?? null,
            'amount' => $amount,
            'source' => (string) ($charge['source'] ?? 'pricing_snapshot.service_charges'),
            'sort_order' => $sortOrder,
        ];
    }

    private function noChargeDetail(string $type): string
    {
        return match ($type) {
            'body_retrieval_excess', 'hearse_excess' => 'No excess kilometers',
            'embalming_extension', 'home_viewing_extension' => 'No extended days',
            'casket_upgrade' => 'No casket replacement/upgrade',
            'others' => 'No other additional services',
            default => 'No additional charge',
        };
    }

    private function distanceChargeRow($charges, string $type, string $label): ?array
    {
        $row = $this->chargeForType($charges, $type);
        if (! $row) {
            return null;
        }

        $excess = (float) ($row['excess'] ?? $row['excess_kilometers'] ?? $row['quantity'] ?? 0);
        $rate = (float) ($row['rate'] ?? 0);
        $amount = (float) ($row['amount'] ?? 0);

        return [
            'type' => $type,
            'label' => $label,
            'quantity' => $excess,
            'rate' => $rate,
            'amount' => $amount,
            'unit' => 'km',
            'source' => (string) ($row['source'] ?? 'pricing_snapshot.service_charges'),
            'quantity_detail' => $this->formatQuantity($excess) . ' km x ' . $this->money($rate) . '/km',
            'display' => $this->formatQuantity($excess) . ' km x ' . $this->money($rate) . '/km - ' . $this->money($amount),
        ];
    }

    private function dayChargeRow($charges, string $type, string $label): ?array
    {
        $row = $this->chargeForType($charges, $type);
        if (! $row) {
            return null;
        }

        $excess = (float) ($row['excess'] ?? $row['extended_days'] ?? $row['excess_days'] ?? $row['quantity'] ?? 0);
        $rate = (float) ($row['rate'] ?? 0);
        $amount = (float) ($row['amount'] ?? 0);

        return [
            'type' => $type,
            'label' => $label,
            'quantity' => $excess,
            'rate' => $rate,
            'amount' => $amount,
            'unit' => 'day',
            'source' => (string) ($row['source'] ?? 'pricing_snapshot.service_charges'),
            'quantity_detail' => $this->formatQuantity($excess) . ' day(s) x ' . $this->money($rate) . '/day',
            'display' => $this->formatQuantity($excess) . ' day(s) x ' . $this->money($rate) . '/day - ' . $this->money($amount),
        ];
    }

    private function casketChargeRow(array $snapshot, $charges): ?array
    {
        $row = $this->chargeForType($charges, Package::SERVICE_CASKET);
        $included = Arr::get($snapshot, 'included_casket', []);
        $selected = Arr::get($snapshot, 'selected_casket', []);
        $amount = (float) ($row['amount'] ?? max(((float) Arr::get($selected, 'reference_value', 0)) - ((float) Arr::get($included, 'reference_value', 0)), 0));

        if ($amount <= 0 && $this->casketName($selected) === '') {
            return null;
        }

        return [
            'type' => Package::SERVICE_CASKET,
            'label' => 'Casket replacement / upgrade',
            'quantity' => 1,
            'rate' => $amount,
            'amount' => $amount,
            'unit' => 'upgrade',
            'source' => (string) ($row['source'] ?? 'pricing_snapshot.service_charges'),
            'quantity_detail' => $this->casketUpgradeDetail($included, $selected),
            'display' => trim(
                'Included: ' . ($this->casketName($included) ?: 'Not included')
                . ' (' . $this->money((float) Arr::get($included, 'reference_value', 0)) . '); '
                . 'Selected: ' . ($this->casketName($selected) ?: 'Not selected')
                . ' (' . $this->money((float) Arr::get($selected, 'reference_value', 0)) . ') - '
                . $this->money($amount)
            ),
        ];
    }

    private function casketUpgradeDetail(array $included, array $selected): string
    {
        $selectedName = $this->casketName($selected) ?: 'Selected casket';
        $includedName = $this->casketName($included) ?: 'included casket';

        return $selectedName . ' instead of ' . $includedName;
    }

    private function chargeForType($charges, string $type): ?array
    {
        return $charges
            ->first(fn ($row) => $this->chargeType($row) === $type && (float) ($row['amount'] ?? 0) > 0);
    }

    private function chargeType(array $row): string
    {
        $raw = (string) ($row['type'] ?? $row['service_type'] ?? '');
        $normalized = Package::normalizeServiceType($raw);

        return match ($normalized) {
            'casket_upgrade', 'casket_replacement', 'replacement_casket', 'upgrade_casket' => Package::SERVICE_CASKET,
            default => $normalized,
        };
    }

    private function casketName(?array $row): string
    {
        if (! $row) {
            return '';
        }

        return trim((string) ($row['name'] ?? '') . (($row['material'] ?? '') ? ' - ' . $row['material'] : ''));
    }

    private function quantityRateAmount(string $label, int $quantity, string $unit, float $rate, float $amount): string
    {
        return trim($label . ' - ' . $quantity . ' ' . $unit) . ' x ' . $this->money($rate) . ' - ' . $this->money($amount);
    }

    private function addOnLine(array $row): array
    {
        $quantity = (float) ($row['quantity'] ?? 1);
        $unit = (string) ($row['unit'] ?? '');
        $rate = (float) ($row['price'] ?? $row['unit_price'] ?? $row['add_on_price_snapshot'] ?? 0);
        $amount = round((float) (($row['line_total'] ?? null) ?? ($rate * max($quantity, 1))), 2);
        $label = (string) ($row['name'] ?? $row['add_on_name_snapshot'] ?? 'Add-on');

        return [
            'type' => 'global_add_on',
            'label' => $label,
            'details' => $this->quantityRateDetail($quantity, $unit, $rate),
            'quantity' => $quantity,
            'unit' => $unit,
            'rate' => $rate,
            'amount' => $amount,
            'source' => 'saved_add_on_snapshot',
            'sort_order' => 70,
            'category' => (string) ($row['category'] ?? ''),
            'display' => $this->quantityRateAmount($label, (int) $quantity, $unit, $rate, $amount),
        ];
    }

    private function itemizedLine(array $row): array
    {
        $quantity = isset($row['quantity']) ? (float) $row['quantity'] : null;
        $unit = (string) ($row['unit'] ?? '');
        $rate = isset($row['rate']) ? (float) $row['rate'] : null;
        $amount = (float) ($row['amount'] ?? 0);

        return [
            'type' => 'itemized_additional_service',
            'label' => (string) ($row['description'] ?? 'Additional service'),
            'description' => (string) ($row['description'] ?? 'Additional service'),
            'details' => $quantity !== null && $rate !== null ? $this->quantityRateDetail($quantity, $unit, $rate) : '',
            'quantity' => $quantity,
            'unit' => $unit,
            'rate' => $rate,
            'amount' => $amount,
            'source' => 'pricing_snapshot.additional_items',
            'sort_order' => 80,
        ];
    }

    private function quantityRateDetail(float $quantity, string $unit, float $rate): string
    {
        return trim($this->formatQuantity($quantity) . ' ' . $unit) . ' x ' . $this->money($rate);
    }

    private function formatQuantity(float $quantity): string
    {
        return fmod($quantity, 1.0) === 0.0
            ? number_format($quantity, 0)
            : number_format($quantity, 2);
    }

    private function reconciliation(bool $isLegacy, float $basePackagePrice, float $totalAdditionalServices, float $savedSubtotal, float $discountAmount, float $savedTax, float $contractTotal): array
    {
        if ($isLegacy) {
            return [
                'reconciled' => true,
                'legacy' => true,
                'expected_subtotal' => $savedSubtotal,
                'saved_subtotal' => $savedSubtotal,
                'expected_total' => $contractTotal,
                'saved_total' => $contractTotal,
            ];
        }

        $expectedSubtotal = round($basePackagePrice + $totalAdditionalServices, 2);
        $expectedTotal = round($savedSubtotal - $discountAmount + $savedTax, 2);

        return [
            'reconciled' => abs($expectedSubtotal - $savedSubtotal) < 0.01 && abs($expectedTotal - $contractTotal) < 0.01,
            'legacy' => false,
            'expected_subtotal' => $expectedSubtotal,
            'saved_subtotal' => $savedSubtotal,
            'expected_total' => $expectedTotal,
            'saved_total' => $contractTotal,
        ];
    }

    private function discountLabel(array $discount, array $promo, string $remarks, float $discountAmount): string
    {
        $source = strtoupper((string) ($discount['source'] ?? ''));
        if ($source === 'PROMO' || $promo !== []) {
            return (string) ($promo['label'] ?? $discount['discount_note'] ?? 'Applied Promo');
        }

        if ($source === 'SENIOR') {
            return 'Senior Discount';
        }

        $note = trim((string) ($discount['discount_note'] ?? $remarks));
        if ($note !== '') {
            return $note;
        }

        return $discountAmount > 0 ? 'Discount' : 'No discount';
    }

    private function selectedPackageName(array $snapshot): string
    {
        $name = trim((string) Arr::get($snapshot, 'package.name', ''));

        if ((bool) Arr::get($snapshot, 'package.custom_package', false)) {
            return $name !== '' && ! preg_match('/^custom package$/i', $name)
                ? 'Custom - ' . $name
                : 'Custom Package';
        }

        return $name !== '' ? $name : 'Not available';
    }

    private function isVaguePackageDescription(string $item): bool
    {
        $normalized = strtolower(trim(preg_replace('/\s+/', ' ', $item)));
        $normalized = rtrim($normalized, ". \t\n\r\0\x0B");

        return in_array($normalized, [
            'full service package',
            'standard service package',
        ], true);
    }

    private function pendingStipulations(): array
    {
        return [
            'The funeral home\'s official stipulations are pending approval and will be printed here once finalized.',
        ];
    }

    private function money(float $amount): string
    {
        return '₱' . number_format($amount, 2);
    }
}

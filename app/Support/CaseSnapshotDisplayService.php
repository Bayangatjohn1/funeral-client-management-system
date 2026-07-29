<?php

namespace App\Support;

use App\Models\FuneralCase;
use App\Models\Package;
use Illuminate\Support\Arr;

class CaseSnapshotDisplayService
{
    public function data(FuneralCase $case): array
    {
        $snapshot = is_array($case->pricing_snapshot) ? $case->pricing_snapshot : [];
        $hasSnapshot = $snapshot !== [];

        return [
            'has_snapshot' => $hasSnapshot,
            'package_name' => $this->packageName($case, $snapshot),
            'package_price' => $this->packagePrice($case, $snapshot),
            'inclusions' => $hasSnapshot ? $this->snapshotInclusions($snapshot) : $this->legacyInclusions($case),
            'freebies' => $hasSnapshot ? $this->snapshotFreebies($snapshot) : $this->legacyFreebies($case),
            'included_casket' => $hasSnapshot ? Arr::get($snapshot, 'included_casket') : null,
            'selected_casket' => $hasSnapshot ? Arr::get($snapshot, 'selected_casket') : null,
            'service_charges' => $hasSnapshot ? $this->snapshotServiceCharges($snapshot) : [],
            'add_ons' => $hasSnapshot ? $this->snapshotAddOns($snapshot) : $this->legacyAddOns($case),
            'additional_items' => $hasSnapshot ? $this->snapshotAdditionalItems($snapshot) : $this->legacyAdditionalItems($case),
            'add_ons_total' => (float) ($case->add_ons_total_amount ?? 0),
            'additional_total' => $hasSnapshot
                ? round((float) collect(Arr::get($snapshot, 'additional_items', []))->sum(fn ($item) => (float) ($item['amount'] ?? 0)), 2)
                : (float) ($case->additional_service_amount ?? 0),
            'discount' => $this->discountDetails($case, $snapshot),
            'wake_duration' => $hasSnapshot
                ? (string) (Arr::get($snapshot, 'wake_duration') ?: WakeDuration::labelFromDays(Arr::get($snapshot, 'wake_days')))
                : WakeDuration::labelFromDays($case->deceased?->wake_days),
        ];
    }

    private function packageName(FuneralCase $case, array $snapshot): string
    {
        return (string) (
            Arr::get($snapshot, 'package.name')
            ?: $case->package_name_snapshot
            ?: $case->custom_package_name
            ?: $case->service_package
            ?: 'Not available'
        );
    }

    private function packagePrice(FuneralCase $case, array $snapshot): float
    {
        return round((float) (
            Arr::get($snapshot, 'package.base_price')
            ?? $case->package_price_snapshot
            ?? $case->custom_package_price
            ?? 0
        ), 2);
    }

    private function snapshotInclusions(array $snapshot): array
    {
        return collect(Arr::get($snapshot, 'inclusions', []))
            ->filter(fn ($row) => is_array($row))
            ->map(function (array $row): string {
                $type = Package::normalizeServiceType($row['service_type'] ?? null);
                $label = (string) ($row['name'] ?? (Package::serviceTypeOptions()[$type] ?? 'Inclusion'));

                if ($type === Package::SERVICE_CASKET) {
                    $casket = trim((string) ($row['casket_type'] ?? ''));
                    return $casket !== '' ? "{$label}: {$casket}" : $label;
                }

                if (in_array($type, [Package::SERVICE_BODY_RETRIEVAL, Package::SERVICE_HEARSE], true)) {
                    $km = $row['included_kilometers'] ?? null;
                    $rate = (float) ($row['price_per_excess_kilometer'] ?? 0);
                    $parts = [];
                    if ($km !== null) {
                        $parts[] = 'Includes ' . number_format((float) $km, 2) . ' km';
                    }
                    if ($rate > 0) {
                        $parts[] = 'excess ' . number_format($rate, 2) . '/km';
                    }
                    return $parts ? "{$label} (" . implode(', ', $parts) . ')' : $label;
                }

                if (in_array($type, [Package::SERVICE_EMBALMING, Package::SERVICE_HOME_VIEWING], true)) {
                    $days = $row['included_days'] ?? null;
                    $rate = (float) ($row['price_per_extended_day'] ?? 0);
                    $parts = [];
                    if ($days !== null) {
                        $parts[] = 'Includes ' . (int) $days . ' day(s)';
                    }
                    if ($rate > 0) {
                        $parts[] = 'extended ' . number_format($rate, 2) . '/day';
                    }
                    return $parts ? "{$label} (" . implode(', ', $parts) . ')' : $label;
                }

                return $label;
            })
            ->filter()
            ->values()
            ->all();
    }

    private function legacyInclusions(FuneralCase $case): array
    {
        if ($case->package_inclusions_snapshot) {
            return Package::parseLegacyItems($case->package_inclusions_snapshot);
        }

        if ($case->custom_package_inclusions) {
            return Package::parseLegacyItems($case->custom_package_inclusions);
        }

        return [];
    }

    private function snapshotFreebies(array $snapshot): array
    {
        return collect(Arr::get($snapshot, 'freebies', []))
            ->filter(fn ($row) => is_array($row))
            ->map(function (array $row): string {
                $name = trim((string) ($row['name'] ?? ''));
                $quantity = $row['quantity'] ?? null;
                $unit = trim((string) ($row['unit'] ?? ''));

                if ($name === '') {
                    return '';
                }

                return $quantity !== null
                    ? trim($name . ' - ' . $quantity . ' ' . $unit)
                    : $name;
            })
            ->filter()
            ->values()
            ->all();
    }

    private function legacyFreebies(FuneralCase $case): array
    {
        if ($case->package_freebies_snapshot) {
            return Package::parseLegacyItems($case->package_freebies_snapshot);
        }

        if ($case->custom_package_freebies) {
            return Package::parseLegacyItems($case->custom_package_freebies);
        }

        return [];
    }

    private function snapshotServiceCharges(array $snapshot): array
    {
        return collect(Arr::get($snapshot, 'service_charges', []))
            ->filter(fn ($row) => is_array($row) && (float) ($row['amount'] ?? 0) > 0)
            ->values()
            ->all();
    }

    private function snapshotAddOns(array $snapshot): array
    {
        return collect(Arr::get($snapshot, 'add_ons', []))
            ->filter(fn ($row) => is_array($row))
            ->map(fn (array $row) => [
                'name' => (string) ($row['name'] ?? 'Add-on'),
                'description' => (string) ($row['description'] ?? ''),
                'quantity' => (int) ($row['quantity'] ?? 1),
                'unit_price' => (float) ($row['price'] ?? 0),
                'line_total' => (float) (($row['line_total'] ?? null) ?? ($row['price'] ?? 0)),
                'unit' => (string) ($row['unit'] ?? ''),
            ])
            ->values()
            ->all();
    }

    private function legacyAddOns(FuneralCase $case): array
    {
        return ($case->caseAddOns ?? collect())
            ->map(fn ($row) => [
                'name' => (string) $row->add_on_name_snapshot,
                'description' => (string) $row->add_on_description_snapshot,
                'quantity' => (int) $row->quantity,
                'unit_price' => (float) $row->add_on_price_snapshot,
                'line_total' => (float) $row->line_total,
                'unit' => '',
            ])
            ->values()
            ->all();
    }

    private function snapshotAdditionalItems(array $snapshot): array
    {
        return collect(Arr::get($snapshot, 'additional_items', []))
            ->filter(fn ($row) => is_array($row) && trim((string) ($row['description'] ?? '')) !== '')
            ->map(fn (array $row) => [
                'description' => (string) $row['description'],
                'amount' => (float) ($row['amount'] ?? 0),
            ])
            ->values()
            ->all();
    }

    private function legacyAdditionalItems(FuneralCase $case): array
    {
        if (! $case->additional_services && (float) ($case->additional_service_amount ?? 0) <= 0) {
            return [];
        }

        return [[
            'description' => (string) ($case->additional_services ?: 'Additional services'),
            'amount' => (float) ($case->additional_service_amount ?? 0),
        ]];
    }

    private function discountDetails(FuneralCase $case, array $snapshot): array
    {
        $discount = is_array(Arr::get($snapshot, 'discount')) ? Arr::get($snapshot, 'discount') : [];
        $promo = is_array(Arr::get($snapshot, 'promo')) ? Arr::get($snapshot, 'promo') : $this->legacyPromoSnapshot($case);
        $source = strtoupper((string) ($discount['source'] ?? $case->discount_type ?? 'NONE'));
        $amount = round((float) ($discount['discount_amount'] ?? $case->discount_amount ?? 0), 2);
        $valueType = (string) ($discount['discount_value_type'] ?? $promo['value_type'] ?? $case->discount_value_type ?? 'AMOUNT');
        $value = (float) ($discount['discount_value'] ?? $promo['value'] ?? $case->discount_value ?? 0);
        $note = (string) ($discount['discount_note'] ?? $case->discount_note ?? '');

        if ($source === 'PROMO' || $promo !== []) {
            return [
                'source' => 'PROMO',
                'label' => (string) ($promo['label'] ?? $this->labelFromPromoNote($note) ?? 'Package Promo'),
                'type' => $valueType,
                'value' => $value,
                'amount' => $amount,
                'note' => $note,
                'starts_at' => $promo['starts_at'] ?? null,
                'ends_at' => $promo['ends_at'] ?? null,
                'status' => (string) ($promo['status'] ?? 'Applied at intake'),
            ];
        }

        return [
            'source' => $source ?: 'NONE',
            'label' => $note ?: ($source !== 'NONE' ? str_replace('_', ' ', $source) : 'No discount'),
            'type' => $valueType,
            'value' => $value,
            'amount' => $amount,
            'note' => $note,
            'starts_at' => null,
            'ends_at' => null,
            'status' => $amount > 0 ? 'Applied' : 'None',
        ];
    }

    private function legacyPromoSnapshot(FuneralCase $case): array
    {
        $raw = trim((string) ($case->package_promo_snapshot ?? ''));
        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function labelFromPromoNote(string $note): ?string
    {
        if (preg_match('/\(([^)]+)\)/', $note, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }
}

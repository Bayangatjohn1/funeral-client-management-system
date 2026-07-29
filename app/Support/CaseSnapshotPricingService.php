<?php

namespace App\Support;

use App\Models\CaseDocument;
use App\Models\FuneralCase;
use App\Models\Package;
use Illuminate\Support\Arr;

class CaseSnapshotPricingService
{
    public function isFinalized(FuneralCase $case): bool
    {
        if (($case->case_status ?? null) === 'COMPLETED') {
            return true;
        }

        if ($case->relationLoaded('funeralContract')) {
            return $case->funeralContract !== null;
        }

        return $case->funeralContract()
            ->where('document_type', CaseDocument::TYPE_FUNERAL_CONTRACT)
            ->exists();
    }

    public function packageName(FuneralCase $case): string
    {
        $snapshot = $this->snapshot($case);

        return (string) (
            Arr::get($snapshot, 'package.name')
            ?: $case->package_name_snapshot
            ?: $case->service_package
            ?: $case->package?->name
            ?: 'Saved Package'
        );
    }

    public function packagePrice(FuneralCase $case): float
    {
        $snapshot = $this->snapshot($case);

        return round((float) (
            Arr::get($snapshot, 'package.base_price')
            ?? $case->package_price_snapshot
            ?? $case->subtotal_amount
            ?? 0
        ), 2);
    }

    public function pricingAttributesForSchedule(FuneralCase $case, ?string $wakeStartDate, ?string $intermentDate, bool $finalized = false): array
    {
        if ($finalized || ! $this->hasStructuredSnapshot($case)) {
            return $this->preservedFinancialAttributes($case);
        }

        $snapshot = $this->snapshot($case);
        $wakeDuration = WakeDuration::calculate($wakeStartDate, $intermentDate);
        $wakeDays = (int) ($wakeDuration['days'] ?? 0);
        $wakeNights = (int) ($wakeDuration['nights'] ?? 0);
        $wakeLabel = $wakeDuration['label'] ?? WakeDuration::labelFromDays($wakeDays);

        $oldCharges = collect($snapshot['service_charges'] ?? [])->filter(fn ($charge) => is_array($charge))->values();
        $extensionTypes = [Package::SERVICE_EMBALMING, Package::SERVICE_HOME_VIEWING];
        $oldExtensionTotal = round((float) $oldCharges
            ->whereIn('type', $extensionTypes)
            ->sum(fn ($charge) => (float) ($charge['amount'] ?? 0)), 2);

        $keptCharges = $oldCharges
            ->reject(fn ($charge) => in_array((string) ($charge['type'] ?? ''), $extensionTypes, true))
            ->values()
            ->all();

        $newExtensionCharges = $this->extensionChargesFromSnapshot($snapshot, $wakeDays);
        $newExtensionTotal = round(collect($newExtensionCharges)->sum('amount'), 2);
        $delta = round($newExtensionTotal - $oldExtensionTotal, 2);

        $newTotal = round(max((float) $case->total_amount + $delta, 0), 2);
        $newSubtotal = round(max((float) $case->subtotal_amount + $delta, 0), 2);
        $payment = $this->paymentFields($newTotal, (float) $case->total_paid);

        $snapshot['wake_days'] = $wakeDays;
        $snapshot['wake_nights'] = $wakeNights;
        $snapshot['wake_duration'] = $wakeLabel;
        $snapshot['service_charges'] = array_values(array_merge($keptCharges, $newExtensionCharges));
        $snapshot['service_charges_total'] = round(collect($snapshot['service_charges'])->sum('amount'), 2);
        Arr::set($snapshot, 'totals.subtotal', $newSubtotal);
        Arr::set($snapshot, 'totals.discount', round((float) $case->discount_amount, 2));
        Arr::set($snapshot, 'totals.tax', round((float) $case->tax_amount, 2));
        Arr::set($snapshot, 'totals.total', $newTotal);

        return array_merge($this->preservedDiscountAndTaxAttributes($case), [
            'subtotal_amount' => $newSubtotal,
            'total_amount' => $newTotal,
            'total_paid' => $payment['total_paid'],
            'balance_amount' => $payment['balance'],
            'payment_status' => $payment['status'],
            'pricing_snapshot' => $snapshot,
            'wake_days' => $wakeDays,
            'wake_duration' => $wakeLabel,
        ]);
    }

    public function hasStructuredSnapshot(FuneralCase $case): bool
    {
        return is_array($case->pricing_snapshot) && $case->pricing_snapshot !== [];
    }

    private function snapshot(FuneralCase $case): array
    {
        return is_array($case->pricing_snapshot) ? $case->pricing_snapshot : [];
    }

    private function extensionChargesFromSnapshot(array $snapshot, int $wakeDays): array
    {
        $inclusions = collect($snapshot['inclusions'] ?? [])
            ->filter(fn ($row) => is_array($row))
            ->mapWithKeys(fn ($row) => [Package::normalizeServiceType($row['service_type'] ?? null) => $row]);

        $charges = [];
        foreach ([Package::SERVICE_EMBALMING, Package::SERVICE_HOME_VIEWING] as $type) {
            $row = $inclusions->get($type);
            if (! $row) {
                continue;
            }

            $included = (int) ($row['included_days'] ?? 0);
            $rate = round((float) ($row['price_per_extended_day'] ?? 0), 2);
            $extended = max($wakeDays - $included, 0);
            $amount = round($extended * $rate, 2);

            if ($amount <= 0) {
                continue;
            }

            $charges[] = [
                'type' => $type,
                'label' => Package::serviceTypeOptions()[$type] ?? $type,
                'actual' => $wakeDays,
                'included' => $included,
                'rate' => $rate,
                'excess' => $extended,
                'amount' => $amount,
            ];
        }

        return $charges;
    }

    private function preservedFinancialAttributes(FuneralCase $case): array
    {
        return array_merge($this->preservedDiscountAndTaxAttributes($case), [
            'subtotal_amount' => (float) $case->subtotal_amount,
            'total_amount' => (float) $case->total_amount,
            'total_paid' => (float) $case->total_paid,
            'balance_amount' => (float) $case->balance_amount,
            'payment_status' => (string) $case->payment_status,
            'pricing_snapshot' => $case->pricing_snapshot,
            'wake_days' => $case->deceased?->wake_days,
            'wake_duration' => WakeDuration::labelFromDays($case->deceased?->wake_days),
        ]);
    }

    private function preservedDiscountAndTaxAttributes(FuneralCase $case): array
    {
        return [
            'discount_type' => $case->discount_type,
            'discount_value_type' => $case->discount_value_type,
            'discount_value' => $case->discount_value,
            'discount_amount' => $case->discount_amount,
            'discount_note' => $case->discount_note,
            'tax_rate' => $case->tax_rate,
            'tax_amount' => $case->tax_amount,
        ];
    }

    private function paymentFields(float $totalAmount, float $currentPaid): array
    {
        $total = round(max($totalAmount, 0), 2);
        $paid = round(max($currentPaid, 0), 2);

        if ($total <= 0) {
            return ['total_paid' => $paid, 'balance' => 0.00, 'status' => 'PAID'];
        }

        if ($paid >= $total) {
            return ['total_paid' => $paid, 'balance' => 0.00, 'status' => 'PAID'];
        }

        if ($paid > 0) {
            return ['total_paid' => $paid, 'balance' => round($total - $paid, 2), 'status' => 'PARTIAL'];
        }

        return ['total_paid' => 0.00, 'balance' => $total, 'status' => 'UNPAID'];
    }
}

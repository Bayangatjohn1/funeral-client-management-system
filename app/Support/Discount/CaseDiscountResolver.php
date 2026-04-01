<?php

namespace App\Support\Discount;

use App\Models\Package;
use Carbon\Carbon;

class CaseDiscountResolver
{
    public function resolveSelected(
        Package $package,
        string $selection,
        float $subtotal,
        ?Carbon $referenceAt = null,
        ?float $customAmount = null
    ): array {
        $subtotal = round(max($subtotal, 0), 2);
        $selection = strtoupper(trim($selection));
        $referenceAt = $referenceAt ?: now();

        if ($selection === 'SENIOR') {
            return $this->buildPercentDiscount('SENIOR', (float) config('funeral.senior_discount_percent', 20), $subtotal);
        }

        if ($selection === 'PWD') {
            return $this->buildPercentDiscount('PWD', (float) config('funeral.pwd_discount_percent', 20), $subtotal);
        }

        if ($selection === 'OTHER') {
            $amount = round(min(max((float) $customAmount, 0), $subtotal), 2);

            return [
                'discount_type' => $amount > 0 ? 'CUSTOM' : 'NONE',
                'discount_value_type' => 'AMOUNT',
                'discount_value' => $amount,
                'discount_amount' => $amount,
                'discount_note' => $amount > 0 ? 'Manual intake discount' : null,
                'source' => $amount > 0 ? 'OTHER' : 'NONE',
            ];
        }

        $promo = $this->resolvePromo($package, $subtotal, $referenceAt);
        if ($promo['amount'] > 0) {
            return [
                'discount_type' => 'CUSTOM',
                'discount_value_type' => $promo['value_type'],
                'discount_value' => $promo['value'],
                'discount_amount' => $promo['amount'],
                'discount_note' => $promo['note'],
                'source' => 'PROMO',
            ];
        }

        return [
            'discount_type' => 'NONE',
            'discount_value_type' => 'AMOUNT',
            'discount_value' => 0,
            'discount_amount' => 0,
            'discount_note' => null,
            'source' => 'NONE',
        ];
    }

    public function resolve(Package $package, ?int $deceasedAge, float $subtotal, ?Carbon $referenceAt = null): array
    {
        $subtotal = round(max($subtotal, 0), 2);
        $referenceAt = $referenceAt ?: now();

        $seniorPercent = (float) config('funeral.senior_discount_percent', 20);
        $seniorPercent = max(min($seniorPercent, 100), 0);
        $seniorAmount = 0.0;
        if ($deceasedAge !== null && $deceasedAge >= 60) {
            $seniorAmount = round(($subtotal * $seniorPercent) / 100, 2);
        }

        $promo = $this->resolvePromo($package, $subtotal, $referenceAt);
        $promoAmount = $promo['amount'];

        if ($seniorAmount <= 0 && $promoAmount <= 0) {
            return [
                'discount_type' => 'NONE',
                'discount_value_type' => 'AMOUNT',
                'discount_value' => 0,
                'discount_amount' => 0,
                'discount_note' => null,
                'source' => 'NONE',
            ];
        }

        if ($seniorAmount >= $promoAmount) {
            return [
                'discount_type' => 'SENIOR',
                'discount_value_type' => 'PERCENT',
                'discount_value' => round($seniorPercent, 2),
                'discount_amount' => $seniorAmount,
                'discount_note' => "Auto senior discount ({$seniorPercent}%)",
                'source' => 'SENIOR',
            ];
        }

        return [
            // Existing enum supports CUSTOM but not PROMO.
            'discount_type' => 'CUSTOM',
            'discount_value_type' => $promo['value_type'],
            'discount_value' => $promo['value'],
            'discount_amount' => $promoAmount,
            'discount_note' => $promo['note'],
            'source' => 'PROMO',
        ];
    }

    public function preview(Package $package, ?int $deceasedAge, float $subtotal, ?Carbon $referenceAt = null): array
    {
        $subtotal = round(max($subtotal, 0), 2);
        $referenceAt = $referenceAt ?: now();
        $resolved = $this->resolve($package, $deceasedAge, $subtotal, $referenceAt);
        $total = round(max($subtotal - (float) $resolved['discount_amount'], 0), 2);

        return array_merge($resolved, [
            'subtotal' => $subtotal,
            'total' => $total,
        ]);
    }

    private function buildPercentDiscount(string $type, float $percent, float $subtotal): array
    {
        $percent = max(min($percent, 100), 0);
        $amount = round(($subtotal * $percent) / 100, 2);

        return [
            'discount_type' => $type,
            'discount_value_type' => 'PERCENT',
            'discount_value' => round($percent, 2),
            'discount_amount' => $amount,
            'discount_note' => $amount > 0 ? "{$type} discount ({$percent}%)" : null,
            'source' => $type,
        ];
    }

    private function resolvePromo(Package $package, float $subtotal, Carbon $referenceAt): array
    {
        if (!$package->promo_is_active || !$package->promo_value_type || $package->promo_value === null) {
            return $this->emptyPromo();
        }

        if ($package->promo_starts_at && $referenceAt->lt($package->promo_starts_at)) {
            return $this->emptyPromo();
        }

        if ($package->promo_ends_at && $referenceAt->gt($package->promo_ends_at)) {
            return $this->emptyPromo();
        }

        $valueType = strtoupper((string) $package->promo_value_type);
        $value = round((float) $package->promo_value, 2);
        if ($value <= 0) {
            return $this->emptyPromo();
        }

        $amount = 0.0;
        if ($valueType === 'PERCENT') {
            $value = max(min($value, 100), 0);
            $amount = round(($subtotal * $value) / 100, 2);
        } else {
            $valueType = 'AMOUNT';
            $amount = round(min($value, $subtotal), 2);
        }

        if ($amount <= 0) {
            return $this->emptyPromo();
        }

        $label = trim((string) ($package->promo_label ?: 'Package Promo'));

        return [
            'value_type' => $valueType,
            'value' => $value,
            'amount' => $amount,
            'note' => "Auto promo discount ({$label})",
        ];
    }

    private function emptyPromo(): array
    {
        return [
            'value_type' => 'AMOUNT',
            'value' => 0,
            'amount' => 0,
            'note' => null,
        ];
    }
}

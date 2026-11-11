<?php

namespace App\Support\Payments;

use App\Models\Payment;

class PaymentIdentifier
{
    public static function providerFor(array $details): ?string
    {
        return match ($details['cashless_type'] ?? null) {
            'bank_transfer' => ($details['bank_name'] ?? null) === 'Other Bank'
                ? ($details['other_bank_name'] ?? 'Other Bank')
                : ($details['bank_name'] ?? null),
            'gcash' => 'GCash',
            'maya' => 'Maya',
            'card' => $details['terminal_provider'] ?? null,
            'other' => $details['payment_channel'] ?? null,
            default => null,
        };
    }

    public static function duplicate(
        int $branchId,
        string $identifierType,
        string $value,
        ?string $cashlessType = null,
        ?string $provider = null,
        ?int $exceptPaymentId = null,
    ): ?Payment {
        $normalizedValue = mb_strtolower(trim($value));
        if ($normalizedValue === '') {
            return null;
        }

        $query = Payment::withTrashed()
            ->with(['funeralCase:id,case_code'])
            ->where('branch_id', $branchId);

        if ($exceptPaymentId !== null) {
            $query->where('id', '!=', $exceptPaymentId);
        }

        if ($identifierType === 'receipt_or_no') {
            $query->whereRaw('LOWER(TRIM(receipt_or_no)) = ?', [$normalizedValue]);
        } else {
            $columns = $identifierType === 'approval_code'
                ? ['approval_code']
                : ['reference_number', 'transaction_reference_no'];

            $query->where(function ($identifierQuery) use ($columns, $normalizedValue) {
                foreach ($columns as $index => $column) {
                    $method = $index === 0 ? 'whereRaw' : 'orWhereRaw';
                    $identifierQuery->{$method}("LOWER(TRIM({$column})) = ?", [$normalizedValue]);
                }
            });

            $cashlessType = trim((string) $cashlessType);
            $query->where(function ($methodQuery) use ($cashlessType) {
                if ($cashlessType !== '') {
                    $methodQuery->where('cashless_type', $cashlessType)
                        ->orWhere(function ($legacyQuery) use ($cashlessType) {
                            $legacyQuery->whereNull('cashless_type')
                                ->where(function ($typeQuery) use ($cashlessType) {
                                    if ($cashlessType === 'bank_transfer') {
                                        $typeQuery->where('payment_mode', 'bank_transfer')
                                            ->orWhere('method', 'BANK_TRANSFER');
                                    } else {
                                        $typeQuery->where('payment_mode', 'cashless')
                                            ->orWhere('payment_method', 'cashless');
                                    }
                                });
                        });
                }
            });

            $provider = trim((string) $provider);
            if ($provider !== '' && in_array($cashlessType, ['bank_transfer', 'gcash', 'maya', 'card', 'other'], true)) {
                $providerColumns = match ($cashlessType) {
                    'bank_transfer' => ['bank_name', 'other_bank_name', 'bank_or_channel', 'other_bank_or_channel'],
                    'gcash', 'maya' => ['wallet_provider', 'bank_or_channel'],
                    'card' => ['terminal_provider', 'bank_or_channel'],
                    'other' => ['payment_channel', 'bank_or_channel'],
                };

                $normalizedProvider = mb_strtolower($provider);
                $query->where(function ($providerQuery) use ($providerColumns, $normalizedProvider) {
                    foreach ($providerColumns as $index => $column) {
                        $method = $index === 0 ? 'whereRaw' : 'orWhereRaw';
                        $providerQuery->{$method}("LOWER(TRIM({$column})) = ?", [$normalizedProvider]);
                    }
                });
            }
        }

        return $query->first();
    }

    public static function duplicateMessage(string $identifierType, ?Payment $duplicate): string
    {
        $caseCode = $duplicate?->funeralCase?->case_code;
        $location = $caseCode ? "case {$caseCode}" : 'another payment record';

        return match ($identifierType) {
            'receipt_or_no' => "This Receipt / OR number is already used by {$location} in this branch.",
            'approval_code' => "This approval code is already used by {$location} for this card provider in this branch.",
            default => "This transaction reference is already used by {$location} for this payment provider in this branch.",
        };
    }
}

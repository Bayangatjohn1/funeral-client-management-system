<?php

namespace App\Support\Payments;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentDetails
{
    public const CASHLESS_TYPES = ['bank_transfer', 'gcash', 'maya', 'card', 'other'];
    public const CARD_TYPES = ['debit', 'credit'];

    public const BANKS = [
        'BDO',
        'BPI',
        'Metrobank',
        'Landbank',
        'Security Bank',
        'UnionBank',
        'RCBC',
        'PNB',
        'China Bank',
        'Other Bank',
    ];

    public static function rules(string $amountField = 'amount_paid', string $dateField = 'paid_at'): array
    {
        return [
            'payment_method' => ['required_with:' . $amountField, Rule::in(['cash', 'cashless'])],
            'cashless_type' => ['nullable', Rule::in(self::CASHLESS_TYPES)],
            'bank_name' => ['nullable', Rule::in(self::BANKS)],
            'other_bank_name' => ['nullable', 'string', 'min:2', 'max:80', 'regex:/^[A-Za-z0-9 .&()\\-]+$/'],
            'wallet_provider' => ['nullable', 'string', 'max:50'],
            'account_name' => ['nullable', 'string', 'min:2', 'max:100', 'regex:/^(?=.*[\\pL])[\\pL\\pM .\'\\-]+$/u'],
            'mobile_number' => ['nullable', 'string', 'regex:/^(09\\d{9}|\\+?639\\d{9})$/'],
            'reference_number' => ['nullable', 'string', 'min:3', 'max:60', 'regex:/^[A-Za-z0-9 _\\/\\-]+$/'],
            'approval_code' => ['nullable', 'string', 'min:4', 'max:40', 'regex:/^[A-Za-z0-9_\\/\\-]+$/'],
            'card_type' => ['nullable', Rule::in(self::CARD_TYPES)],
            'terminal_provider' => ['nullable', 'string', 'max:80', 'regex:/^[A-Za-z0-9 .()\\-]+$/'],
            'payment_channel' => ['nullable', 'string', 'min:2', 'max:80', 'regex:/^[A-Za-z0-9 ._&()\\-]+$/'],
            'payment_notes' => ['nullable', 'string', 'max:255', 'not_regex:/<[^>]*>|[<>]/'],
            'bank_or_channel' => ['nullable', 'string', 'max:100'],
            'other_bank_or_channel' => ['nullable', 'string', 'max:100'],
            'transaction_reference_no' => ['nullable', 'string', 'max:100'],
            'sender_name' => ['nullable', 'string', 'max:120'],
            'transfer_datetime' => ['nullable', 'date'],
        ];
    }

    public static function amountRules(): array
    {
        return ['required', 'regex:/^\\d+(\\.\\d{1,2})?$/', 'numeric', 'gt:0'];
    }

    public static function dateRules(): array
    {
        return ['required', 'date', 'before_or_equal:now'];
    }

    public static function messages(): array
    {
        return [
            'amount_paid.required' => 'Amount received is required.',
            'amount_paid.regex' => 'Amount can only have up to 2 decimal places.',
            'amount_paid.numeric' => 'Amount received is required.',
            'amount_paid.gt' => 'Amount must be greater than ₱0.00.',
            'paid_at.required' => 'Date received is required.',
            'paid_at.date' => 'Date received is required.',
            'paid_at.before_or_equal' => 'Date received cannot be in the future.',
            'payment_method.required_with' => 'Please select a valid payment method.',
            'payment_method.in' => 'Please select a valid payment method.',
            'cashless_type.in' => 'Please select a valid cashless type.',
            'bank_name.in' => 'Please select a valid bank.',
            'other_bank_name.required' => 'Please enter the bank name.',
            'other_bank_name.min' => 'Bank name must be between 2 and 80 characters.',
            'other_bank_name.max' => 'Bank name must be between 2 and 80 characters.',
            'other_bank_name.regex' => 'Bank name must be between 2 and 80 characters.',
            'reference_number.required' => 'Reference number is required.',
            'reference_number.min' => 'Receipt number must be 3 to 60 characters.',
            'reference_number.max' => 'Reference number must be 4 to 60 characters.',
            'reference_number.regex' => 'Reference number contains invalid characters.',
            'approval_code.min' => 'Approval code contains invalid characters.',
            'approval_code.max' => 'Approval code contains invalid characters.',
            'approval_code.regex' => 'Approval code contains invalid characters.',
            'account_name.min' => 'Account name must be between 2 and 100 characters.',
            'account_name.max' => 'Account name must be between 2 and 100 characters.',
            'account_name.regex' => 'Account name should contain letters only and must not include numbers or special characters.',
            'mobile_number.regex' => 'Please enter a valid Philippine mobile number.',
            'payment_channel.required' => 'Payment channel is required.',
            'payment_channel.min' => 'Payment channel must be between 2 and 80 characters.',
            'payment_channel.max' => 'Payment channel must be between 2 and 80 characters.',
            'payment_channel.regex' => 'Payment channel must be between 2 and 80 characters.',
            'payment_notes.max' => 'Notes must not exceed 255 characters.',
            'payment_notes.not_regex' => 'Notes must not exceed 255 characters.',
            'card_type.in' => 'Please select a valid card type.',
            'terminal_provider.max' => 'Terminal/provider must not exceed 80 characters.',
            'terminal_provider.regex' => 'Terminal/provider must not exceed 80 characters.',
        ];
    }

    public static function normalize(Request|array $source): array
    {
        $input = $source instanceof Request ? $source->all() : $source;

        $method = self::trim($input['payment_method'] ?? 'cash');
        $method = strtolower((string) $method);
        if ($method === 'bank_transfer') {
            $method = 'cashless';
            $input['cashless_type'] = $input['cashless_type'] ?? 'bank_transfer';
        }
        if ($method === 'cash') {
            $cashlessType = null;
        } else {
            $method = 'cashless';
            $cashlessType = strtolower((string) self::trim($input['cashless_type'] ?? ''));
            if ($cashlessType === '') {
                $cashlessType = null;
            }
        }

        $referenceNumber = self::trim($input['reference_number'] ?? null)
            ?: self::trim($input['transaction_reference_no'] ?? null)
            ?: self::trim($input['bank_reference'] ?? null);

        $bankName = self::trim($input['bank_name'] ?? null)
            ?: self::trim($input['bank_or_channel'] ?? null);
        if ($bankName === 'Other') {
            $bankName = 'Other Bank';
        }

        $otherBankName = self::trim($input['other_bank_name'] ?? null)
            ?: self::trim($input['other_bank_or_channel'] ?? null);

        $walletProvider = self::trim($input['wallet_provider'] ?? null);
        if ($method === 'cashless' && $cashlessType === 'gcash') {
            $walletProvider = 'GCash';
        } elseif ($method === 'cashless' && $cashlessType === 'maya') {
            $walletProvider = 'Maya';
        }

        $paymentChannel = self::trim($input['payment_channel'] ?? null);
        $approvalCode = self::trim($input['approval_code'] ?? null);
        $cardType = strtolower((string) self::trim($input['card_type'] ?? ''));
        $cardType = $cardType === '' ? null : $cardType;
        $terminalProvider = self::trim($input['terminal_provider'] ?? null);
        $accountName = self::trim($input['account_name'] ?? null)
            ?: self::trim($input['sender_name'] ?? null);
        $mobileNumber = self::normalizeMobileNumber(self::trim($input['mobile_number'] ?? null));

        if ($method === 'cash') {
            return [
                'payment_method' => 'cash',
                'cashless_type' => null,
                'bank_name' => null,
                'other_bank_name' => null,
                'wallet_provider' => null,
                'account_name' => null,
                'mobile_number' => null,
                'reference_number' => $referenceNumber,
                'approval_code' => null,
                'card_type' => null,
                'terminal_provider' => null,
                'payment_channel' => null,
                'payment_notes' => self::trim($input['payment_notes'] ?? null),
                'legacy_payment_mode' => 'cash',
                'legacy_method' => 'CASH',
                'legacy_bank_or_channel' => null,
                'legacy_other_bank_or_channel' => null,
                'legacy_transaction_reference_no' => $referenceNumber,
                'legacy_sender_name' => null,
            ];
        }

        if ($cashlessType === 'bank_transfer') {
            $legacyChannel = $bankName === 'Other Bank' ? 'Other' : $bankName;
        } elseif (in_array($cashlessType, ['gcash', 'maya'], true)) {
            $legacyChannel = $walletProvider;
        } elseif ($cashlessType === 'other') {
            $legacyChannel = $paymentChannel;
        } else {
            $legacyChannel = $input['bank_or_channel'] ?? null;
        }

        return [
            'payment_method' => 'cashless',
            'cashless_type' => $cashlessType,
            'bank_name' => $cashlessType === 'bank_transfer' ? $bankName : null,
            'other_bank_name' => $cashlessType === 'bank_transfer' && $bankName === 'Other Bank' ? $otherBankName : null,
            'wallet_provider' => in_array($cashlessType, ['gcash', 'maya'], true) ? $walletProvider : null,
            'account_name' => $accountName,
            'mobile_number' => $mobileNumber,
            'reference_number' => $referenceNumber,
            'approval_code' => $cashlessType === 'card' ? $approvalCode : null,
            'card_type' => $cashlessType === 'card' ? $cardType : null,
            'terminal_provider' => $cashlessType === 'card' ? $terminalProvider : null,
            'payment_channel' => $cashlessType === 'other' ? $paymentChannel : null,
            'payment_notes' => self::trim($input['payment_notes'] ?? null),
            'legacy_payment_mode' => $cashlessType === 'bank_transfer' ? 'bank_transfer' : 'cash',
            'legacy_method' => $cashlessType === 'bank_transfer' ? 'BANK_TRANSFER' : 'CASH',
            'legacy_bank_or_channel' => $legacyChannel,
            'legacy_other_bank_or_channel' => $cashlessType === 'bank_transfer' && $bankName === 'Other Bank' ? $otherBankName : null,
            'legacy_transaction_reference_no' => $referenceNumber,
            'legacy_sender_name' => $accountName,
        ];
    }

    public static function validateNormalized(array $details): array
    {
        $errors = [];

        if (($details['payment_method'] ?? null) !== 'cashless') {
            return $errors;
        }

        $type = $details['cashless_type'] ?? null;
        if (! in_array($type, self::CASHLESS_TYPES, true)) {
            $errors['cashless_type'] = 'Cashless type is required.';
            return $errors;
        }

        if ($type === 'bank_transfer') {
            if (empty($details['bank_name'])) {
                $errors['bank_name'] = 'Please select a valid bank.';
            }
            if (($details['bank_name'] ?? null) === 'Other Bank' && empty($details['other_bank_name'])) {
                $errors['other_bank_name'] = 'Please enter the bank name.';
            }
            if (empty($details['reference_number'])) {
                $errors['reference_number'] = 'Reference number is required.';
            } elseif (strlen($details['reference_number']) < 4 || strlen($details['reference_number']) > 60) {
                $errors['reference_number'] = 'Reference number must be 4 to 60 characters.';
            }
        }

        if (in_array($type, ['gcash', 'maya'], true)) {
            if (empty($details['reference_number'])) {
                $errors['reference_number'] = 'Reference number is required.';
            } elseif (strlen($details['reference_number']) < 4 || strlen($details['reference_number']) > 60) {
                $errors['reference_number'] = 'Reference number must be 4 to 60 characters.';
            }
        }

        if ($type === 'card' && empty($details['approval_code']) && empty($details['reference_number'])) {
            $errors['approval_code'] = 'Approval code or reference number is required for card payments.';
        } elseif ($type === 'card' && ! empty($details['reference_number']) && (strlen($details['reference_number']) < 4 || strlen($details['reference_number']) > 60)) {
            $errors['reference_number'] = 'Reference number must be 4 to 60 characters.';
        }

        if ($type === 'other') {
            if (empty($details['payment_channel'])) {
                $errors['payment_channel'] = 'Payment channel is required.';
            }
            if (empty($details['reference_number'])) {
                $errors['reference_number'] = 'Reference number is required.';
            } elseif (strlen($details['reference_number']) < 4 || strlen($details['reference_number']) > 60) {
                $errors['reference_number'] = 'Reference number must be 4 to 60 characters.';
            }
        }

        return $errors;
    }

    public static function normalizeMobileNumber(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);
        if (preg_match('/^09\\d{9}$/', $value)) {
            return $value;
        }
        if (preg_match('/^\\+?639(\\d{9})$/', $value, $matches)) {
            return '09' . $matches[1];
        }

        return $value;
    }

    public static function label(object $payment): string
    {
        $method = $payment->payment_method ?: $payment->payment_mode ?: 'cash';
        $type = $payment->cashless_type ?: ($method === 'bank_transfer' || $payment->payment_mode === 'bank_transfer' ? 'bank_transfer' : null);

        if ($method !== 'cashless' && $type !== 'bank_transfer') {
            return 'Cash';
        }

        return match ($type) {
            'bank_transfer' => 'Bank Transfer' . (($payment->bank_name ?: self::legacyChannel($payment)) ? ' - ' . ($payment->bank_name ?: self::legacyChannel($payment)) : ''),
            'gcash' => 'GCash',
            'maya' => 'Maya',
            'card' => 'Card',
            'other' => 'Other' . (($payment->payment_channel ?: self::legacyChannel($payment)) ? ': ' . ($payment->payment_channel ?: self::legacyChannel($payment)) : ''),
            default => 'Cashless',
        };
    }

    public static function referenceLabel(object $payment): ?string
    {
        if (! empty($payment->approval_code)) {
            return 'Approval: ' . $payment->approval_code;
        }

        $reference = $payment->reference_number ?: $payment->transaction_reference_no;

        return $reference ? 'Ref: ' . $reference : null;
    }

    private static function legacyChannel(object $payment): ?string
    {
        if (($payment->bank_or_channel ?? null) === 'Other') {
            return $payment->other_bank_or_channel ?: 'Other Bank';
        }

        return $payment->bank_or_channel ?? null;
    }

    private static function trim(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}

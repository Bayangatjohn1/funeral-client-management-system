<?php

namespace App\Services\CaseDocument;

use App\Models\FuneralCase;
use App\Models\Package;
use App\Support\CaseSnapshotDisplayService;
use Illuminate\Support\Arr;

class FuneralContractSnapshotService
{
    public function __construct(private readonly CaseSnapshotDisplayService $displayService)
    {
    }

    public function make(FuneralCase $case, string $contractNumber, array $details = []): array
    {
        $case->loadMissing([
            'branch',
            'client',
            'deceased',
            'payments',
            'serviceDetail',
        ]);

        $display = $this->displayService->data($case);
        $pricingSnapshot = is_array($case->pricing_snapshot) ? $case->pricing_snapshot : [];
        $hasPricingSnapshot = $pricingSnapshot !== [];
        $contractDetails = $this->normalizeDetails($details);
        $additionalServices = $this->additionalServices($display, $contractDetails);
        $firstPayment = $this->firstPayment($case);

        return [
            'version' => 1,
            'legacy' => ! $hasPricingSnapshot,
            'contract_number' => $contractNumber,
            'contract_date' => $this->dateString($case->service_requested_at),
            'case' => [
                'id' => $case->id,
                'case_code' => (string) $case->case_code,
                'case_number' => $case->case_number,
            ],
            'branch' => [
                'name' => (string) ($case->branch?->branch_name ?: 'Sabangan-Caguioa Funeral Services'),
                'address' => (string) ($case->branch?->address ?: ''),
                'contact_number' => (string) ($case->branch?->contact_number ?: ''),
            ],
            'client' => [
                'name' => (string) ($case->client?->full_name ?: ''),
                'address' => (string) ($case->client?->address ?: ''),
                'contact_number' => (string) ($case->client?->contact_number ?: ''),
                'relationship' => (string) ($case->client?->relationship ?: $case->client?->relationship_to_deceased ?: ''),
            ],
            'deceased' => [
                'name' => (string) ($case->deceased?->full_name ?: ''),
                'address' => (string) ($case->deceased?->address ?: ''),
                'date_of_death' => $this->dateString($case->deceased?->date_of_death ?: $case->deceased?->died),
                'age' => $case->deceased?->age,
                'cemetery' => (string) ($case->deceased?->place_of_cemetery ?: $case->serviceDetail?->cemetery_place ?: ''),
            ],
            'package' => [
                'name' => $this->packageName($case, $display),
                'base_price' => (float) $display['package_price'],
                'custom_package' => (bool) Arr::get($pricingSnapshot, 'custom_package', false),
            ],
            'basic_inclusions' => $this->basicInclusions($case, $display),
            'structured_inclusions' => array_values(Arr::get($pricingSnapshot, 'inclusions', [])),
            'freebies' => array_values($display['freebies'] ?? []),
            'included_casket' => $display['included_casket'],
            'selected_casket' => $display['selected_casket'],
            'service_charges' => array_values(Arr::get($pricingSnapshot, 'service_charges', [])),
            'add_ons' => $hasPricingSnapshot ? array_values(Arr::get($pricingSnapshot, 'add_ons', [])) : array_values($display['add_ons'] ?? []),
            'additional_items' => $hasPricingSnapshot ? array_values(Arr::get($pricingSnapshot, 'additional_items', [])) : array_values($display['additional_items'] ?? []),
            'contract_only_additional_services' => [],
            'discount' => Arr::get($pricingSnapshot, 'discount', []),
            'promo' => Arr::get($pricingSnapshot, 'promo', []),
            'additional_services' => $additionalServices,
            'schedule' => [
                'wake_location' => (string) ($case->wake_location ?: $case->serviceDetail?->wake_location ?: ''),
                'wake_start_date' => $this->dateString($case->wake_start_date ?: $case->serviceDetail?->start_of_wake),
                'wake_start_time' => (string) ($case->wake_start_time ?: ''),
                'funeral_service_date' => $this->dateString($case->funeral_service_at),
                'funeral_service_time' => (string) ($case->funeral_service_time ?: ''),
                'interment_date' => $this->dateString($case->interment_at ?: $case->serviceDetail?->internment_date ?: $case->deceased?->interment_at ?: $case->deceased?->interment),
                'interment_time' => $contractDetails['interment_time'] ?: (string) ($case->interment_time ?: ''),
                'cemetery' => (string) ($case->deceased?->place_of_cemetery ?: $case->serviceDetail?->cemetery_place ?: ''),
                'wake_duration' => (string) ($display['wake_duration'] ?? ''),
            ],
            'totals' => [
                'subtotal' => (float) ($case->subtotal_amount ?? Arr::get($pricingSnapshot, 'totals.subtotal', 0)),
                'discount' => (float) ($case->discount_amount ?? Arr::get($pricingSnapshot, 'totals.discount', 0)),
                'tax' => (float) ($case->tax_amount ?? Arr::get($pricingSnapshot, 'totals.tax', 0)),
                'total' => (float) ($case->total_amount ?? Arr::get($pricingSnapshot, 'totals.total', 0)),
                'deposit' => (float) ($firstPayment['amount'] ?? 0),
                'balance' => (float) ($case->balance_amount ?? 0),
                'payment_status' => (string) ($case->payment_status ?: ''),
            ],
            'payment' => [
                'initial_payment' => $firstPayment,
                'total_paid' => (float) ($case->total_paid ?? 0),
            ],
            'remarks' => $contractDetails['remarks'] ?: (string) ($case->discount_note ?: ''),
            'details' => $contractDetails,
            'stipulations' => [
                'The funeral home\'s official stipulations are pending approval and will be printed here once finalized.',
            ],
        ];
    }

    public function previewData(FuneralCase $case): array
    {
        $case->loadMissing(['funeralContract']);

        $contractNumber = $case->funeralContract?->contract_number
            ?: 'FCN-' . now()->format('Y') . '-' . str_pad((string) $case->id, 6, '0', STR_PAD_LEFT);

        return $case->funeralContract?->contract_snapshot
            ?: $this->make($case, $contractNumber);
    }

    private function packageName(FuneralCase $case, array $display): string
    {
        if ((bool) data_get($case->pricing_snapshot, 'custom_package', false)) {
            return (string) ($case->custom_package_name ?: $display['package_name'] ?: 'Custom Package');
        }

        return (string) ($display['package_name'] ?: 'Package');
    }

    private function basicInclusions(FuneralCase $case, array $display): array
    {
        $items = array_values(array_filter($display['inclusions'] ?? []));

        if ($items === [] && $case->custom_package_inclusions) {
            $items = Package::parseLegacyItems($case->custom_package_inclusions);
        }

        return $items;
    }

    private function additionalServices(array $display, array $details): array
    {
        $items = [];

        foreach ($display['service_charges'] ?? [] as $row) {
            $amount = (float) ($row['amount'] ?? 0);
            if ($amount <= 0) {
                continue;
            }

            $items[] = [
                'description' => (string) ($row['label'] ?? $row['type'] ?? 'Service charge'),
                'amount' => $amount,
                'source' => 'pricing_snapshot',
            ];
        }

        foreach ($display['add_ons'] ?? [] as $row) {
            $quantity = (int) ($row['quantity'] ?? 1);
            $name = (string) ($row['name'] ?? 'Add-on');
            $items[] = [
                'description' => trim($name . ($quantity > 1 ? ' x ' . $quantity : '')),
                'amount' => (float) ($row['line_total'] ?? 0),
                'source' => 'add_on',
            ];
        }

        foreach ($display['additional_items'] ?? [] as $row) {
            $items[] = [
                'description' => (string) ($row['description'] ?? 'Additional service'),
                'amount' => (float) ($row['amount'] ?? 0),
                'source' => 'additional_item',
            ];
        }

        return $items;
    }

    private function normalizeDetails(array $details): array
    {
        return [
            'interment_time' => trim((string) ($details['interment_time'] ?? '')),
            'remarks' => trim((string) ($details['remarks'] ?? '')),
        ];
    }

    private function firstPayment(FuneralCase $case): ?array
    {
        $payment = $case->payments
            ->filter(fn ($row) => ($row->status ?? 'VALID') !== 'VOID')
            ->sortBy(fn ($row) => sprintf(
                '%012d-%012d',
                optional($row->paid_at ?: $row->paid_date ?: $row->created_at)->timestamp ?? 0,
                (int) $row->id
            ))
            ->first();

        if (! $payment) {
            return null;
        }

        return [
            'id' => $payment->id,
            'amount' => (float) $payment->amount,
            'paid_at' => optional($payment->paid_at ?: $payment->paid_date ?: $payment->created_at)->toDateTimeString(),
            'method' => (string) ($payment->payment_method ?: $payment->payment_mode ?: $payment->method ?: ''),
            'record_no' => (string) ($payment->payment_record_no ?: $payment->receipt_number ?: ''),
        ];
    }

    private function dateString(mixed $value): ?string
    {
        if (! $value) {
            return null;
        }

        return \Carbon\Carbon::parse($value)->toDateString();
    }
}

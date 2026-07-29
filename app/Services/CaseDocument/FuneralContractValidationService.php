<?php

namespace App\Services\CaseDocument;

use App\Models\FuneralCase;

class FuneralContractValidationService
{
    /**
     * @return array<string, string>
     */
    public function missingFields(FuneralCase $case): array
    {
        $case->loadMissing([
            'client',
            'deceased',
            'package.packageInclusions',
            'package.packageFreebies',
            'caseAddOns',
            'payments',
            'serviceDetail',
        ]);

        $missing = [];
        $client = $case->client;
        $deceased = $case->deceased;

        $this->require($missing, 'client.full_name', 'Client Name', $client?->full_name);
        $this->require($missing, 'client.contact_number', 'Client Contact Number', $client?->contact_number);
        $this->require($missing, 'client.address', 'Client Address', $client?->address);

        $this->require($missing, 'deceased.full_name', 'Deceased Name', $deceased?->full_name);
        $this->require($missing, 'deceased.date_of_death', 'Deceased Date of Death', $deceased?->died ?? $deceased?->date_of_death);
        $this->require($missing, 'deceased.age', 'Deceased Age', $deceased?->age);

        $displayPackageName = $case->package_name_snapshot
            ?: ($case->service_package ?? $case->package?->name ?? $case->custom_package_name);
        $this->require($missing, 'case.service_requested_at', 'Request Date', $case->service_requested_at);
        $this->require($missing, 'case.package', 'Package', $displayPackageName);

        if ($this->packageAmount($case) <= 0) {
            $missing['case.package_amount'] = 'Package Price or Subtotal Amount';
        }

        if ((float) ($case->total_amount ?? 0) <= 0) {
            $missing['case.total_amount'] = 'Total Charges';
        }

        $this->require($missing, 'case.wake_location', 'Wake Location', $case->wake_location ?? $case->serviceDetail?->wake_location);
        $this->require($missing, 'case.wake_start_date', 'Wake Start Schedule', $case->wake_start_date ?? $case->serviceDetail?->start_of_wake);
        $this->require($missing, 'case.funeral_service_at', 'Funeral Service Schedule', $case->funeral_service_at);
        $this->require($missing, 'case.interment_at', 'Burial Schedule', $case->interment_at ?? $case->serviceDetail?->internment_date ?? $deceased?->interment_at ?? $deceased?->interment);

        $this->require($missing, 'case.payment_status', 'Payment Status', $case->payment_status);
        $this->require($missing, 'case.total_paid', 'Total Paid Amount', $case->total_paid);
        $this->require($missing, 'case.balance_amount', 'Balance Amount', $case->balance_amount);

        return $missing;
    }

    public function isComplete(FuneralCase $case): bool
    {
        return $this->missingFields($case) === [];
    }

    private function require(array &$missing, string $key, string $label, mixed $value): void
    {
        if (blank($value) && $value !== 0 && $value !== '0') {
            $missing[$key] = $label;
        }
    }

    private function packageAmount(FuneralCase $case): float
    {
        if ($case->package_price_snapshot !== null) {
            return (float) $case->package_price_snapshot;
        }

        if ($case->custom_package_price !== null) {
            return (float) $case->custom_package_price;
        }

        if ($case->subtotal_amount !== null) {
            $derivedPackagePrice = (float) $case->subtotal_amount
                - (float) ($case->add_ons_total_amount ?? 0)
                - (float) ($case->additional_service_amount ?? 0);

            return max($derivedPackagePrice, (float) $case->subtotal_amount);
        }

        return (float) ($case->total_amount ?? 0);
    }
}

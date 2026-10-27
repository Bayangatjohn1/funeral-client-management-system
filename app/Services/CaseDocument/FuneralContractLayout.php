<?php

namespace App\Services\CaseDocument;

class FuneralContractLayout
{
    public const CONDENSED = 'condensed';
    public const DETAILED = 'detailed';

    public function forNewContract(array $contractView, array $snapshot = []): string
    {
        if ((bool) data_get($snapshot, 'legacy', false) || count(data_get($snapshot, 'additional_services', [])) > 0) {
            return self::DETAILED;
        }

        foreach (['automatic_charges', 'add_ons', 'itemized_services'] as $key) {
            if (count(data_get($contractView, $key, [])) > 0) {
                return self::DETAILED;
            }
        }

        return self::CONDENSED;
    }

    public function forIssuedContract(array $snapshot): string
    {
        // Contracts issued before layout selection retain their original detailed format.
        return data_get($snapshot, 'document_layout') === self::CONDENSED
            ? self::CONDENSED
            : self::DETAILED;
    }

    public function viewName(string $layout): string
    {
        return $layout === self::CONDENSED
            ? 'pdf.funeral_contract_condensed'
            : 'pdf.funeral_contract_physical';
    }
}

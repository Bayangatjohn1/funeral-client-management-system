<?php

namespace App\Support;

use App\Models\CasketCatalog;
use App\Models\FreebieCatalog;
use App\Models\CustomPackageDefault;
use App\Models\Package;
use Illuminate\Validation\ValidationException;

class CustomPackageSelection
{
    public function resolve(array $input): array
    {
        // Defaults are authoritative for new cases; saved snapshots remain unchanged.
        $defaults = CustomPackageDefault::find(1);
        $coverage = $defaults?->coverage() ?? [];
        $rates = $defaults?->rates() ?? [];
        $charges = [];
        $types = array_values(array_unique($input['custom_service_types'] ?? []));
        if (($input['viewing_type'] ?? null) === 'chapel') {
            $types = array_values(array_diff($types, [Package::SERVICE_HOME_VIEWING]));
        }
        $other = array_values(array_filter(array_map('trim', preg_split('/[\r\n]+/u', (string) ($input['custom_other_inclusions'] ?? '')) ?: [])));
        if (collect($other)->contains(fn ($description) => mb_strlen($description) > 150)) {
            throw ValidationException::withMessages(['custom_other_inclusions' => 'Keep each other inclusion under 150 characters.']);
        }
        if ($types === [] && $other === []) {
            throw ValidationException::withMessages(['custom_service_types' => 'Select a service or describe at least one other inclusion.']);
        }

        $rows = [];
        $includedCasket = null;
        foreach ($types as $type) {
            $row = ['service_type' => $type, 'name' => Package::serviceTypeOptions()[$type]];
            if (isset(CustomPackageDefault::SERVICE_FIELDS[$type])) {
                $days = CustomPackageDefault::SERVICE_FIELDS[$type][2] === 'days';
                $included = $input['custom_included'][$type] ?? $coverage[$type] ?? null;
                $requested = $input['custom_requested'][$type] ?? null;
                $rate = $rates[$type] ?? null;
                if ($days && ($included === null || $included < 1 || floor((float) $included) !== (float) $included)) {
                    throw ValidationException::withMessages(["custom_service_coverage.$type" => 'Enter the number of included days.']);
                }
                if ($days && $requested !== null && floor((float) $requested) !== (float) $requested) {
                    throw ValidationException::withMessages(["custom_requested.$type" => 'Enter a whole number of requested days.']);
                }
                if ($included !== null) $row[$days ? 'included_days' : 'included_kilometers'] = $days ? (int) $included : (float) $included;
                $row[$days ? 'price_per_extended_day' : 'price_per_excess_kilometer'] = $rate === null ? null : (float) $rate;
                $row['requested_quantity'] = $requested === null ? null : (float) $requested;
                $excess = $included === null || $requested === null ? 0 : round(max(0, $requested - $included), 2);
                $amount = round($excess * (float) $rate, 2);
                if ($amount > 0) {
                    $charges[] = ['type' => $type, 'label' => $row['name'].($days ? ' extension' : ' excess distance'),
                        'included' => (float) $included, 'actual' => (float) $requested, 'excess' => $excess,
                        'rate' => (float) $rate, 'amount' => $amount];
                }
            } elseif ($type === Package::SERVICE_CASKET) {
                $casket = CasketCatalog::whereKey((int) ($input['custom_casket_catalog_id'] ?? 0))
                    ->where('is_active', true)->where('is_available', true)->first();
                if (! $casket) {
                    throw ValidationException::withMessages(['custom_casket_catalog_id' => 'Choose an available casket from the catalog.']);
                }
                $row['casket_catalog_id'] = $casket->id;
                $row['casket_type'] = $casket->name;
                $includedCasket = [
                    'id' => $casket->id,
                    'name' => $casket->name,
                    'material' => $casket->type_or_material,
                    'reference_value' => (float) $casket->standard_price,
                    'legacy_text' => $casket->name,
                ];
            }
            $rows[] = $row;
        }

        foreach ($other as $description) {
            $rows[] = ['service_type' => Package::SERVICE_CUSTOM, 'name' => $description];
        }

        $freebieIds = array_values(array_unique(array_map('intval', $input['custom_freebie_catalog_ids'] ?? [])));
        $freebies = FreebieCatalog::whereIn('id', $freebieIds)->where('is_active', true)->where('is_available', true)->get()->keyBy('id');
        if ($freebies->count() !== count($freebieIds)) {
            throw ValidationException::withMessages(['custom_freebie_catalog_ids' => 'A selected freebie is no longer active. Choose again.']);
        }
        $freebieRows = array_map(fn ($id) => [
            'freebie_catalog_id' => $id,
            'name' => $freebies[$id]->name,
            'quantity' => null,
            'unit' => $freebies[$id]->default_unit,
        ], $freebieIds);

        return [
            'charges' => $charges,
            'inclusions' => $rows,
            'freebies' => $freebieRows,
            'included_casket' => $includedCasket,
            'inclusions_text' => implode("\n", array_map(fn ($row) => $row['service_type'] === Package::SERVICE_CASKET ? 'Casket - '.$row['casket_type'] : $row['name'], $rows)),
            'freebies_text' => implode("\n", array_column($freebieRows, 'name')),
        ];
    }
}

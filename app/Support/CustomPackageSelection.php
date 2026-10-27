<?php

namespace App\Support;

use App\Models\CasketCatalog;
use App\Models\FreebieCatalog;
use App\Models\Package;
use Illuminate\Validation\ValidationException;

class CustomPackageSelection
{
    public function resolve(array $input): array
    {
        $types = array_values(array_unique($input['custom_service_types'] ?? []));
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
            if (in_array($type, [Package::SERVICE_BODY_RETRIEVAL, Package::SERVICE_HEARSE], true)) {
                // Preserve older saved coverage without requiring it for new arrangements.
                if (($distance = (float) ($input['custom_service_coverage'][$type] ?? 0)) > 0) {
                    $row['included_kilometers'] = $distance;
                }
                $row['price_per_excess_kilometer'] = 0;
            } elseif (in_array($type, [Package::SERVICE_EMBALMING, Package::SERVICE_HOME_VIEWING], true)) {
                $days = (int) ($input['custom_service_coverage'][$type] ?? 0);
                if ($days <= 0) {
                    throw ValidationException::withMessages(["custom_service_coverage.$type" => 'Enter the number of included days.']);
                }
                $row['included_days'] = $days;
                $row['price_per_extended_day'] = 0;
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
        $freebies = FreebieCatalog::whereIn('id', $freebieIds)->where('is_active', true)->get()->keyBy('id');
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
            'inclusions' => $rows,
            'freebies' => $freebieRows,
            'included_casket' => $includedCasket,
            'inclusions_text' => implode("\n", array_map(fn ($row) => $row['service_type'] === Package::SERVICE_CASKET ? 'Casket - '.$row['casket_type'] : $row['name'], $rows)),
            'freebies_text' => implode("\n", array_column($freebieRows, 'name')),
        ];
    }
}

@php
    $searchFields = match (true) {
        $searchRecord instanceof \App\Models\Package => [$searchRecord->name, $searchRecord->short_description, $searchRecord->coffin_type, ...$searchRecord->packageInclusions->pluck('inclusion_name')->all(), ...$searchRecord->packageFreebies->pluck('freebie_name')->all()],
        $searchRecord instanceof \App\Models\CasketCatalog => [$searchRecord->name, $searchRecord->type_or_material, $searchRecord->description],
        $searchRecord instanceof \App\Models\AddOnCatalog => [$searchRecord->name, $searchRecord->category, $searchRecord->unit, $searchRecord->description],
        $searchRecord instanceof \App\Models\FreebieCatalog => [$searchRecord->name, $searchRecord->default_unit],
        $searchRecord instanceof \App\Models\Promotion => [$searchRecord->name, $searchRecord->package?->name, $searchRecord->discount_type, (string) $searchRecord->discount_value],
        default => [$searchRecord->name, $searchRecord->description],
    };
    $searchFields = array_values(array_filter($searchFields, fn ($value) => filled($value)));
@endphp
data-catalog-search-id="{{ class_basename($searchRecord) . ':' . $searchRecord->id }}"
data-catalog-search-name="{{ $searchRecord->name }}"
data-catalog-search-fields="{{ json_encode($searchFields, JSON_UNESCAPED_UNICODE) }}"

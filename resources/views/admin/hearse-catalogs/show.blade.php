@extends(request()->boolean('modal') ? 'layouts.modal-frame' : 'layouts.panel')
@section('title', 'View Hearse')
@section('content')
@php
    $returnTo = request('return_to', route('admin.service-management.index', ['tab' => 'hearses']));
    $editUrl = route('admin.hearse-catalogs.edit', ['hearse_catalog' => $catalog, 'modal' => 1, 'return_to' => $returnTo]);
    $statusActions = [[
        'label' => $catalog->is_active ? 'Archive hearse' : 'Restore hearse',
        'title' => $catalog->is_active ? 'Archive this hearse?' : 'Restore this hearse?',
        'icon' => $catalog->is_active ? 'archive' : 'arrow-counterclockwise',
        'message' => 'Existing cases keep their saved hearse assignment. New intake uses active hearses.',
        'affected' => $catalog->is_active ? $catalog->packageInclusions->pluck('package')->filter()->where('is_active', true)->unique('id') : collect(),
        'url' => route('admin.hearse-catalogs.toggleActive', $catalog),
    ]];
@endphp
@include('admin.service-management.partials.detail-styles')
<section class="svc-detail">@include('admin.service-management.partials.status-feedback')<article class="svc-detail-card">
    @include('admin.service-management.partials.detail-header', ['detailTitle' => 'Hearse Coach Details', 'detailRecord' => 'Catalog Record #' . $catalog->getKey(), 'detailName' => $catalog->name, 'detailActive' => $catalog->is_active, 'detailAvailable' => $catalog->is_available, 'detailPromo' => null, 'detailSubtitle' => 'Included in the package base price'])
    <section class="svc-detail-section"><h3>Reference Value</h3><p class="svc-detail-note">{{ $catalog->reference_value !== null ? 'PHP ' . number_format((float) $catalog->reference_value, 2) : 'Not configured' }}</p></section>
    <section class="svc-detail-section">
        <h3>Hearse Details</h3>
        <p class="svc-detail-note">{{ filled($catalog->description) ? $catalog->description : 'No hearse details provided.' }}</p>
    </section>
    <section class="svc-detail-section"><h3>Package Assignment</h3><p class="svc-detail-note">Included in the package base price. Regular intake upgrades charge only the positive reference-value difference. Custom packages use the agreed all-in price.</p></section>
    @include('admin.service-management.partials.package-usage', ['usagePackages' => $catalog->packageInclusions->pluck('package')->filter()->unique('id')])
    @unless(request()->boolean('modal'))
    <footer class="svc-detail-actions">
        @if($canManage)
            <a class="svc-detail-secondary" href="{{ $editUrl }}" data-service-modal-url="{{ $editUrl }}" data-service-modal-title="Edit Hearse"><i class="bi bi-pencil" aria-hidden="true"></i> Edit Hearse</a>
            @include('admin.service-management.partials.status-actions', ['canManage' => $canManage])
        @endif
        <a class="svc-detail-secondary" href="{{ $returnTo }}"><i class="bi bi-arrow-left" aria-hidden="true"></i> Back</a>
    </footer>
    @endunless
</article></section>
@endsection

@extends(request()->boolean('modal') ? 'layouts.modal-frame' : 'layouts.panel')

@section('title', 'View Casket')
@section('page_title', 'View Casket')
@section('page_desc', 'Review casket details, price, status, and package mapping.')

@section('content')
@php
    $returnTo = request('return_to', route('admin.service-management.index', ['tab' => 'caskets']));
    $editUrl = route('admin.casket-catalogs.edit', ['casket_catalog' => $catalog, 'modal' => 1, 'return_to' => $returnTo]);
    $money = fn ($amount) => 'PHP ' . number_format((float) $amount, 2);
    $statusActions = [];
    if ($catalog->is_active) {
        $statusActions[] = [
            'label' => $catalog->is_available ? 'Mark unavailable' : 'Mark available',
            'title' => $catalog->is_available ? 'Mark this casket unavailable?' : 'Mark this casket available?',
            'icon' => $catalog->is_available ? 'pause-circle' : 'check-circle',
            'message' => $catalog->is_available
                ? 'Package status stays unchanged. New intake must select an available replacement casket before saving. Existing cases keep their saved details.'
                : 'Packages using this casket can be available for new intake again if their other catalog items are active.',
            'affected' => $catalog->is_available ? $affectedPackages : collect(),
            'url' => route('admin.casket-catalogs.toggleAvailability', $catalog),
        ];
    }
    $statusActions[] = [
        'label' => $catalog->is_active ? 'Archive casket' : 'Restore casket',
        'title' => $catalog->is_active ? 'Archive this casket?' : 'Restore this casket?',
        'icon' => $catalog->is_active ? 'archive' : 'arrow-counterclockwise',
        'message' => $catalog->is_active && $affectedPackages->isNotEmpty()
            ? 'Affected packages keep their Active/Archived status. Required unavailable inclusions need resolution before new intake. Update the package inclusion or restore catalog availability.'
            : ($catalog->is_active ? 'This casket will be removed from new package setup. Existing cases keep their saved details.' : 'This casket will return to the catalog. Its availability setting will stay the same.'),
        'affected' => $catalog->is_active ? $affectedPackages : collect(),
        'blocked' => false,
        'url' => route('admin.casket-catalogs.toggleActive', $catalog),
    ];
@endphp

@include('admin.service-management.partials.detail-styles')

<section class="svc-detail">
    @include('admin.service-management.partials.status-feedback')
    <article class="svc-detail-card">
        @include('admin.service-management.partials.detail-header', ['detailTitle' => 'Casket Details', 'detailRecord' => 'Catalog Record #' . $catalog->getKey(), 'detailName' => $catalog->name, 'detailActive' => $catalog->is_active, 'detailAvailable' => $catalog->is_available, 'detailPromo' => null, 'detailSubtitle' => $catalog->type_or_material ? 'Material: ' . $catalog->type_or_material : ''])

        <div class="svc-detail-overview">
            <div class="svc-detail-overview-price"><span>Reference Value</span><strong>{{ (float) $catalog->standard_price > 0 ? $money($catalog->standard_price) : 'Price needed' }}</strong></div>
        </div>

        <div class="svc-detail-stat-grid">
            <div class="svc-detail-stat">
                <span>Material</span>
                <strong>{{ $catalog->type_or_material ?: '-' }}</strong>
            </div>
            <div class="svc-detail-stat">
                <span>Used In Packages</span>
                <strong>{{ (int) ($catalog->package_inclusions_count ?? 0) }}</strong>
            </div>
        </div>

        <section class="svc-detail-section">
            <h3>Notes</h3>
            @if($catalog->description)
                <p class="svc-detail-note">{{ $catalog->description }}</p>
            @else
                <p class="svc-detail-empty">No description added.</p>
            @endif
        </section>

        @include('admin.service-management.partials.package-usage', ['usagePackages' => $catalog->packageInclusions->pluck('package')->filter()->unique('id')])
        @unless(request()->boolean('modal'))
<footer class="svc-detail-actions">
            @if($canManage)
                <a class="svc-detail-secondary" href="{{ $editUrl }}" data-service-modal-url="{{ $editUrl }}" data-service-modal-title="Edit Casket"><i class="bi bi-pencil" aria-hidden="true"></i> Edit Casket</a>
                @include('admin.service-management.partials.status-actions', ['canManage' => $canManage])
            @endif
            @unless(request()->boolean('modal'))<a class="svc-detail-secondary" href="{{ $returnTo }}" data-parent-modal-close>
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
                Back to Service Management
            </a>@endunless
        </footer>
@endunless
    </article>
</section>
@endsection

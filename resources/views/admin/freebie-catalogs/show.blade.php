@extends(request()->boolean('modal') ? 'layouts.modal-frame' : 'layouts.panel')

@section('title', 'View Freebie')
@section('page_title', 'View Freebie')
@section('page_desc', 'Review freebie details, status, and package usage notes.')

@section('content')
@php
    $returnTo = request('return_to', route('admin.service-management.index', ['tab' => 'freebies']));
    $editUrl = route('admin.freebie-catalogs.edit', ['freebie_catalog' => $catalog, 'modal' => 1, 'return_to' => $returnTo]);
    $statusActions = [[
        'label' => $catalog->is_active ? 'Archive freebie' : 'Restore freebie',
        'title' => $catalog->is_active ? 'Archive this freebie?' : 'Restore this freebie?',
        'icon' => $catalog->is_active ? 'archive' : 'arrow-counterclockwise',
        'message' => $catalog->is_active && $affectedPackages->isNotEmpty()
            ? 'Affected packages keep their Active/Archived status. Required unavailable inclusions need resolution before new intake. Update the package inclusion or restore catalog availability.'
            : ($catalog->is_active ? 'This freebie will be removed from new package setup. Existing cases keep their saved details.' : 'This freebie will be available for package setup again.'),
        'affected' => $catalog->is_active ? $affectedPackages : collect(),
        'blocked' => false,
        'url' => route('admin.freebie-catalogs.toggleActive', $catalog),
    ]];
@endphp

@include('admin.service-management.partials.detail-styles')

<section class="svc-detail">
    @include('admin.service-management.partials.status-feedback')
    <article class="svc-detail-card">
        @include('admin.service-management.partials.detail-header', ['detailTitle' => 'Freebie Details', 'detailRecord' => 'Complimentary Item Record #' . $catalog->getKey(), 'detailName' => $catalog->name, 'detailActive' => $catalog->is_active, 'detailAvailable' => $catalog->is_available, 'detailPromo' => null, 'detailSubtitle' => 'Default unit: ' . ($catalog->default_unit ?: 'item')])

        <div class="svc-detail-overview">
            <span class="svc-detail-badge {{ $catalog->is_active ? 'is-active' : 'is-muted' }}">
                <i class="bi bi-{{ $catalog->is_active ? 'check-circle' : 'archive' }}" aria-hidden="true"></i>
                {{ $catalog->is_active ? 'Active' : 'Archived' }}
            </span>
        </div>

        <div class="svc-detail-stat-grid">
            <div class="svc-detail-stat">
                <span>Default Unit</span>
                <strong>{{ $catalog->default_unit ?: 'item' }}</strong>
            </div>
            <div class="svc-detail-stat">
                <span>Used In Packages</span>
                <strong>{{ (int) ($catalog->package_freebies_count ?? 0) }}</strong>
            </div>
            <div class="svc-detail-stat">
                <span>Status</span>
                <strong>{{ !$catalog->is_active ? 'Archived' : ($catalog->is_available ? 'Available' : 'Unavailable') }}</strong>
            </div>
        </div>

        <section class="svc-detail-section">
            <h3>Usage</h3>
            <p class="svc-detail-note">This freebie can be attached to service packages and reused during package setup. All freebies are complimentary.</p>
        </section>

        @include('admin.service-management.partials.package-usage', ['usagePackages' => $catalog->packageFreebies->pluck('package')->filter()->unique('id')])
        @unless(request()->boolean('modal'))
<footer class="svc-detail-actions">
            @if($canManage)
                <a class="svc-detail-secondary" href="{{ $editUrl }}" data-service-modal-url="{{ $editUrl }}" data-service-modal-title="Edit Freebie"><i class="bi bi-pencil" aria-hidden="true"></i> Edit Freebie</a>
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

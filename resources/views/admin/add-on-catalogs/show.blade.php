@extends(request()->boolean('modal') ? 'layouts.modal-frame' : 'layouts.panel')

@section('title', 'View Add-on')
@section('page_title', 'View Add-on')
@section('page_desc', 'Review add-on details, pricing, status, and usage notes.')

@section('content')
@php
    $returnTo = request('return_to', route('admin.service-management.index', ['tab' => 'addons']));
    $editUrl = route('admin.add-on-catalogs.edit', ['add_on_catalog' => $catalog, 'modal' => 1, 'return_to' => $returnTo]);
    $money = fn ($amount) => 'PHP ' . number_format((float) $amount, 2);
    $statusActions = [[
        'label' => $catalog->is_active ? 'Archive add-on' : 'Restore add-on',
        'title' => $catalog->is_active ? 'Archive this add-on?' : 'Restore this add-on?',
        'icon' => $catalog->is_active ? 'archive' : 'arrow-counterclockwise',
        'message' => $catalog->is_active
            ? 'This add-on will not be available for new intake. Existing cases keep their saved details.'
            : 'This add-on will be available for new intake again.',
        'affected' => $catalog->is_active ? $catalog->legacyPackageAddOns->pluck('package')->filter()->where('is_active', true)->unique('id') : collect(),
        'reason' => 'Optional add-on reference only. Package eligibility is unchanged.',
        'url' => route('admin.add-on-catalogs.toggleActive', $catalog),
    ]];
@endphp

@include('admin.service-management.partials.detail-styles')

<section class="svc-detail">
    @include('admin.service-management.partials.status-feedback')
    <article class="svc-detail-card">
        @include('admin.service-management.partials.detail-header', ['detailTitle' => 'Add-on Service Details', 'detailRecord' => 'Catalog Record #' . $catalog->getKey(), 'detailName' => $catalog->name, 'detailActive' => $catalog->is_active, 'detailAvailable' => $catalog->is_available, 'detailPromo' => null, 'detailSubtitle' => $catalog->category ?: 'General'])

        <div class="svc-detail-overview">
            <span class="svc-detail-badge {{ $catalog->is_active ? 'is-active' : 'is-muted' }}">
                <i class="bi bi-{{ $catalog->is_active ? 'check-circle' : 'archive' }}" aria-hidden="true"></i>
                {{ $catalog->is_active ? 'Active' : 'Archived' }}
            </span>
            <div class="svc-detail-overview-price"><span>Unit Price (PHP)</span><strong>{{ $money($catalog->price) }}</strong></div>
        </div>

        <div class="svc-detail-stat-grid">
            @if($catalog->category === \App\Models\AddOnCatalog::CHAPEL_VIEWING)
            <div class="svc-detail-stat"><span>Branch</span><strong>{{ $catalog->branch?->branch_name ?: 'Needs Setup' }}</strong></div>
            <div class="svc-detail-stat"><span>Chapel Address</span><strong>{{ $catalog->chapel_address ?: 'Needs Setup' }}</strong></div>
            @endif
            <div class="svc-detail-stat">
                <span>Billing Unit</span>
                <strong>{{ $catalog->unit ?: 'item' }}</strong>
            </div>
            <div class="svc-detail-stat">
                <span>Linked Package Add-ons</span>
                <strong>{{ (int) ($catalog->legacy_package_add_ons_count ?? 0) }}</strong>
            </div>
        </div>

        <section class="svc-detail-section">
            <h3>Description</h3>
            @if($catalog->description)
                <p class="svc-detail-note">{{ $catalog->description }}</p>
            @else
                <p class="svc-detail-empty">No description added.</p>
            @endif
        </section>

        @unless(request()->boolean('modal'))
<footer class="svc-detail-actions">
            @if($canManage)
                <a class="svc-detail-secondary" href="{{ $editUrl }}" data-service-modal-url="{{ $editUrl }}" data-service-modal-title="Edit Add-on"><i class="bi bi-pencil" aria-hidden="true"></i> Edit Add-on</a>
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

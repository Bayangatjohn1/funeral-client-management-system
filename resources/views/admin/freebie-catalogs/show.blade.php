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
        'title' => $catalog->is_active && $affectedPackages->isNotEmpty() ? 'This freebie cannot be archived yet' : ($catalog->is_active ? 'Archive this freebie?' : 'Restore this freebie?'),
        'icon' => $catalog->is_active ? 'archive' : 'arrow-counterclockwise',
        'message' => $catalog->is_active && $affectedPackages->isNotEmpty()
            ? 'Update or archive the active packages using this freebie first.'
            : ($catalog->is_active ? 'This freebie will be removed from new package setup. Existing cases keep their saved details.' : 'This freebie will be available for package setup again.'),
        'affected' => $catalog->is_active ? $affectedPackages : collect(),
        'blocked' => $catalog->is_active && $affectedPackages->isNotEmpty(),
        'url' => route('admin.freebie-catalogs.toggleActive', $catalog),
    ]];
@endphp

@include('admin.service-management.partials.detail-styles')

<section class="svc-detail">
    @include('admin.service-management.partials.status-feedback')
    <article class="svc-detail-card">
        <header class="svc-detail-head">
            <div>
                <span class="svc-detail-kicker"><i class="bi bi-gift" aria-hidden="true"></i> Freebie Details</span>
                <h1>{{ $catalog->name }}</h1>
                <p class="svc-detail-subtitle">Default unit: {{ $catalog->default_unit ?: 'item' }}</p>
            </div>
        </header>

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
                <strong>{{ $catalog->is_active ? 'Available' : 'Archived' }}</strong>
            </div>
        </div>

        <section class="svc-detail-section">
            <h3>Usage</h3>
            <p class="svc-detail-note">This freebie can be attached to service packages and reused during package setup.</p>
        </section>

        <footer class="svc-detail-actions">
            @if($canManage)
                <a class="svc-detail-primary" href="{{ $editUrl }}" data-service-modal-url="{{ $editUrl }}" data-service-modal-title="Edit Freebie">
                    <i class="bi bi-pencil-square" aria-hidden="true"></i>
                    Edit Freebie
                </a>
            @endif
            @include('admin.service-management.partials.status-actions')
            @if(request()->boolean('modal'))<button type="button" class="svc-detail-secondary" data-parent-modal-close>Close</button>@endif
            @unless(request()->boolean('modal'))<a class="svc-detail-secondary" href="{{ $returnTo }}" data-parent-modal-close>
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
                Back to Service Management
            </a>@endunless
        </footer>
    </article>
</section>
@endsection

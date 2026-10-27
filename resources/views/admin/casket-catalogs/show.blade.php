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
                ? 'Packages using this casket will not be available for new intake until an available casket is selected. Existing cases keep their saved details.'
                : 'Packages using this casket can be available for new intake again if their other catalog items are active.',
            'affected' => $catalog->is_available ? $affectedPackages : collect(),
            'url' => route('admin.casket-catalogs.toggleAvailability', $catalog),
        ];
    }
    $statusActions[] = [
        'label' => $catalog->is_active ? 'Archive casket' : 'Restore casket',
        'title' => $catalog->is_active && $affectedPackages->isNotEmpty() ? 'This casket cannot be archived yet' : ($catalog->is_active ? 'Archive this casket?' : 'Restore this casket?'),
        'icon' => $catalog->is_active ? 'archive' : 'arrow-counterclockwise',
        'message' => $catalog->is_active && $affectedPackages->isNotEmpty()
            ? 'Update or archive the active packages using this casket first.'
            : ($catalog->is_active ? 'This casket will be removed from new package setup. Existing cases keep their saved details.' : 'This casket will return to the catalog. Its availability setting will stay the same.'),
        'affected' => $catalog->is_active ? $affectedPackages : collect(),
        'blocked' => $catalog->is_active && $affectedPackages->isNotEmpty(),
        'url' => route('admin.casket-catalogs.toggleActive', $catalog),
    ];
@endphp

@include('admin.service-management.partials.detail-styles')

<section class="svc-detail">
    @include('admin.service-management.partials.status-feedback')
    <article class="svc-detail-card">
        <header class="svc-detail-head">
            <div>
                <span class="svc-detail-kicker"><i class="bi bi-box2-heart" aria-hidden="true"></i> Casket Details</span>
                <h1>{{ $catalog->name }}</h1>
                <p class="svc-detail-subtitle">{{ $catalog->type_or_material ?: 'Material not set' }}</p>
            </div>
        </header>

        <div class="svc-detail-overview">
            <div class="svc-detail-badges">
                <span class="svc-detail-badge {{ $catalog->is_active ? 'is-active' : 'is-muted' }}">
                    <i class="bi bi-{{ $catalog->is_active ? 'check-circle' : 'archive' }}" aria-hidden="true"></i>
                    {{ $catalog->is_active ? 'Active' : 'Archived' }}
                </span>
                @if($catalog->is_active)
                    <span class="svc-detail-badge {{ $catalog->is_available ? 'is-active' : 'is-muted' }}">
                        <i class="bi bi-{{ $catalog->is_available ? 'check-circle' : 'x-circle' }}" aria-hidden="true"></i>
                        {{ $catalog->is_available ? 'Available' : 'Unavailable' }}
                    </span>
                @endif
            </div>
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

        <footer class="svc-detail-actions">
            @if($canManage)
                <a class="svc-detail-primary" href="{{ $editUrl }}" data-service-modal-url="{{ $editUrl }}" data-service-modal-title="Edit Casket">
                    <i class="bi bi-pencil-square" aria-hidden="true"></i>
                    Edit Casket
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

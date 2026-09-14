@extends(request()->boolean('modal') ? 'layouts.modal-frame' : 'layouts.panel')

@section('title', 'View Freebie')
@section('page_title', 'View Freebie')

@section('content')
@php
    $returnTo = request('return_to', route('admin.service-management.index', ['tab' => 'freebies']));
    $editUrl = route('admin.freebie-catalogs.edit', ['freebie_catalog' => $catalog, 'modal' => 1, 'return_to' => $returnTo]);
@endphp

@include('admin.service-management.partials.detail-styles')

<section class="svc-detail">
    <article class="svc-detail-card">
        <header class="svc-detail-head">
            <div>
                <span class="svc-detail-kicker"><i class="bi bi-gift" aria-hidden="true"></i> Freebie Details</span>
                <h1>{{ $catalog->name }}</h1>
                <p class="svc-detail-subtitle">Default unit: {{ $catalog->default_unit ?: 'item' }}</p>
            </div>
            <span class="svc-detail-badge {{ $catalog->is_active ? 'is-active' : 'is-muted' }}">
                <i class="bi bi-{{ $catalog->is_active ? 'check-circle' : 'archive' }}" aria-hidden="true"></i>
                {{ $catalog->is_active ? 'Active' : 'Archived' }}
            </span>
        </header>

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
            <a class="svc-detail-secondary" href="{{ $returnTo }}" data-parent-modal-close>
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
                Back to Service Management
            </a>
        </footer>
    </article>
</section>
@endsection

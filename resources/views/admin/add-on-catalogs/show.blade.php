@extends(request()->boolean('modal') ? 'layouts.modal-frame' : 'layouts.panel')

@section('title', 'View Add-on')
@section('page_title', 'View Add-on')

@section('content')
@php
    $returnTo = request('return_to', route('admin.service-management.index', ['tab' => 'addons']));
    $editUrl = route('admin.add-on-catalogs.edit', ['add_on_catalog' => $catalog, 'modal' => 1, 'return_to' => $returnTo]);
    $money = fn ($amount) => 'PHP ' . number_format((float) $amount, 2);
@endphp

@include('admin.service-management.partials.detail-styles')

<section class="svc-detail">
    <article class="svc-detail-card">
        <header class="svc-detail-head">
            <div>
                <span class="svc-detail-kicker"><i class="bi bi-plus-circle" aria-hidden="true"></i> Add-on Details</span>
                <h1>{{ $catalog->name }}</h1>
                <p class="svc-detail-subtitle">{{ $catalog->category ?: 'General' }}</p>
            </div>
            <span class="svc-detail-badge {{ $catalog->is_active ? 'is-active' : 'is-muted' }}">
                <i class="bi bi-{{ $catalog->is_active ? 'check-circle' : 'archive' }}" aria-hidden="true"></i>
                {{ $catalog->is_active ? 'Active' : 'Archived' }}
            </span>
        </header>

        <div class="svc-detail-stat-grid">
            <div class="svc-detail-stat">
                <span>Standard Price</span>
                <strong>{{ $money($catalog->price) }}</strong>
            </div>
            <div class="svc-detail-stat">
                <span>Unit</span>
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

        <footer class="svc-detail-actions">
            @if($canManage)
                <a class="svc-detail-primary" href="{{ $editUrl }}" data-service-modal-url="{{ $editUrl }}" data-service-modal-title="Edit Add-on">
                    <i class="bi bi-pencil-square" aria-hidden="true"></i>
                    Edit Add-on
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

@extends(request()->boolean('modal') ? 'layouts.modal-frame' : 'layouts.panel')

@section('title', 'View Package')
@section('page_title', 'View Package')
@section('page_desc', 'Review package pricing, inclusions, freebies, add-ons, and availability.')

@section('content')
@php
    $returnTo = request('return_to', route('admin.service-management.index', ['tab' => 'packages']));
    $editUrl = route('admin.packages.edit', ['package' => $package, 'modal' => 1, 'return_to' => $returnTo]);
    $money = fn ($amount) => 'PHP ' . number_format((float) $amount, 2);
    $serviceLabels = \App\Models\Package::serviceTypeOptions();
    $casket = $package->packageInclusions->firstWhere('service_type', \App\Models\Package::SERVICE_CASKET)?->casketCatalog?->display_name
        ?: ($package->coffin_type ?: 'No casket selected');
    $statusActions = [[
        'label' => $package->is_active ? 'Archive package' : 'Restore package',
        'title' => ! $package->is_active && $restoreBlockers !== [] ? 'This package cannot be restored yet' : ($package->is_active ? 'Archive this package?' : 'Restore this package?'),
        'icon' => $package->is_active ? 'archive' : 'arrow-counterclockwise',
        'message' => ! $package->is_active && $restoreBlockers !== []
            ? 'Update the catalog items below before restoring this package for new intake.'
            : ($package->is_active ? 'This package will no longer be available for new intake. Existing cases keep their saved details.' : 'This package will be available for new intake again.'),
        'affected' => collect(),
        'details' => ! $package->is_active ? $restoreBlockers : [],
        'blocked' => ! $package->is_active && $restoreBlockers !== [],
        'url' => route('admin.packages.toggleActive', $package),
    ]];
@endphp

@include('admin.service-management.partials.detail-styles')

<section class="svc-detail">
    @include('admin.service-management.partials.status-feedback')
    <article class="svc-detail-card">
        <header class="svc-detail-head">
            <div>
                <span class="svc-detail-kicker"><i class="bi bi-box2-heart" aria-hidden="true"></i> Package Details</span>
                <h1>{{ $package->name }}</h1>
                <p class="svc-detail-subtitle">Service Package Details</p>
            </div>
        </header>

        <div class="svc-detail-overview">
            <div class="svc-detail-badges">
                <span class="svc-detail-badge {{ $package->is_active ? 'is-active' : 'is-muted' }}">
                    <i class="bi bi-{{ $package->is_active ? 'check-circle' : 'archive' }}" aria-hidden="true"></i>
                    {{ $package->is_active ? 'Active' : 'Archived' }}
                </span>
                <span class="svc-detail-badge {{ $package->promo_is_active ? 'is-active' : '' }}">
                    <i class="bi bi-tag" aria-hidden="true"></i>
                    {{ $package->promo_is_active ? ($package->promo_label ?: 'Promo active') : 'No active promo' }}
                </span>
            </div>
            <div class="svc-detail-overview-price"><span>Base Price</span><strong>{{ $money($package->price) }}</strong></div>
        </div>

        <section class="svc-detail-section svc-intake-readiness" aria-label="New intake availability">
            <h3>New intake</h3>
            @if($intakeBlockers === [])
                <p class="svc-detail-note"><i class="bi bi-check-circle" aria-hidden="true"></i> Ready for new intake.</p>
            @else
                <p class="svc-detail-note">Needs attention before new intake:</p>
                <ul>@foreach($intakeBlockers as $reason)<li>{{ $reason }}</li>@endforeach</ul>
            @endif
        </section>

        <section class="svc-detail-section svc-detail-description">
            <h3>Description and Scope</h3>
            <p class="svc-detail-note">{{ $package->short_description ?: 'Reusable package setup for service intake.' }}</p>
        </section>

        <div class="svc-detail-stat-grid">
            <div class="svc-detail-stat">
                <span>Included Casket</span>
                <strong>{{ $casket }}</strong>
            </div>
            <div class="svc-detail-stat">
                <span>Contents</span>
                <strong>{{ $package->packageInclusions->count() }} inclusions / {{ $package->packageFreebies->count() }} freebies</strong>
            </div>
        </div>

        <section class="svc-detail-section">
            <h3>Package Contents</h3>
            <div class="svc-detail-list-grid">
                <div class="svc-detail-list">
                    <h3>Inclusions</h3>
                    @if($package->packageInclusions->isNotEmpty())
                        <ul>
                            @foreach($package->packageInclusions as $item)
                                <li>
                                    {{ $item->inclusion_name ?: ($serviceLabels[$item->service_type] ?? 'Included service') }}
                                    @if($item->casketCatalog)
                                        - {{ $item->casketCatalog->display_name }}
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="svc-detail-empty">No inclusions listed.</p>
                    @endif
                </div>

                <div class="svc-detail-list">
                    <h3>Freebies</h3>
                    @if($package->packageFreebies->isNotEmpty())
                        <ul>
                            @foreach($package->packageFreebies as $item)
                                <li>{{ $item->freebie_name }} @if($item->quantity)({{ $item->quantity }} {{ $item->unit ?: 'item' }})@endif</li>
                            @endforeach
                        </ul>
                    @else
                        <p class="svc-detail-empty">No freebies listed.</p>
                    @endif
                </div>
            </div>
        </section>

        <section class="svc-detail-section">
            <h3>Promo</h3>
            @if($package->promo_is_active)
                <p class="svc-detail-note">
                    {{ $package->promo_label ?: 'Promo active' }}
                    @if($package->promo_value)
                        - {{ $package->promo_value_type === 'percentage' ? number_format((float) $package->promo_value, 2) . '%' : $money($package->promo_value) }}
                    @endif
                    @if($package->promo_starts_at || $package->promo_ends_at)
                        ({{ $package->promo_starts_at?->format('M d, Y') ?: 'No start date' }} - {{ $package->promo_ends_at?->format('M d, Y') ?: 'No end date' }})
                    @endif
                </p>
            @else
                <p class="svc-detail-empty">No active promo for this package.</p>
            @endif
        </section>

        <footer class="svc-detail-actions">
            @if($canManage)
                <a class="svc-detail-primary" href="{{ $editUrl }}" data-service-modal-url="{{ $editUrl }}" data-service-modal-title="Edit Package">
                    <i class="bi bi-pencil-square" aria-hidden="true"></i>
                    Edit Package
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

@php
    $usage = $record instanceof \App\Models\CasketCatalog ? $record->packageInclusions->pluck('package') : ($record instanceof \App\Models\FreebieCatalog ? $record->packageFreebies->pluck('package') : ($record instanceof \App\Models\HearseCatalog ? $record->packageInclusions->pluck('package') : ($record instanceof \App\Models\AddOnCatalog ? $record->legacyPackageAddOns->pluck('package') : collect())));
    $dependencyReason = $record instanceof \App\Models\CasketCatalog ? 'Included casket dependency: new intake needs an available replacement where supported; otherwise update package configuration.' : ($record instanceof \App\Models\HearseCatalog ? 'Included hearse dependency: new intake is blocked until this package has an eligible hearse.' : ($record instanceof \App\Models\FreebieCatalog ? 'Promised freebie dependency: update the package inclusion or restore this item before new intake.' : 'Optional add-on: package eligibility is unchanged.'));
    $archivePackages = $record instanceof \App\Models\Package ? collect() : app(\App\Support\ServiceCatalogPolicy::class)->activeRequiredPackages($record);
    $archiveLinks = $archivePackages->map(fn ($p) => ['name' => $p->name, 'url' => route('admin.packages.edit', ['package' => $p, 'return_to' => request()->fullUrl()])]);
    $affectedNames = $record->is_active ? $usage->filter()->where('is_active', true)->unique('id')->pluck('name')->values() : collect();
@endphp
<div class="service-catalog-actions">
    <button type="button" class="row-hover-action is-view-action service-card-view service-primary-view primary-view-action {{ ($cardAction ?? false) ? 'primary-view-action--card' : '' }} record-action-control" data-action-tooltip="View {{ strtolower($catalogLabel) }}" data-service-modal-url="{{ route('admin.'.$catalogRoute.'.show', [$catalogParameter => $record, 'modal' => 1, 'return_to' => request()->fullUrl()]) }}" data-service-modal-title="View {{ $catalogLabel }}" aria-label="View {{ $catalogLabel }} details for {{ $record->name }}">
        <i class="bi bi-eye" aria-hidden="true"></i>
        @if($cardAction ?? false)<span>View</span>@endif
    </button>
    @if($canManage)
        <div class="row-action-menu compact-action-menu service-actions-menu" data-row-menu>
            <button type="button" class="row-action-trigger compact-action-trigger record-action-control" data-row-menu-trigger data-action-tooltip="More actions" aria-label="More {{ $catalogLabel }} actions for {{ $record->name }}" aria-haspopup="menu" aria-expanded="false"><i class="bi bi-three-dots-vertical" aria-hidden="true"></i></button>
            <div class="row-action-dropdown service-catalog-write-actions" role="menu">
            <button type="button" class="row-action-item compact-action-item is-edit-action" data-row-menu-item data-service-modal-url="{{ route('admin.'.$catalogRoute.'.edit', [$catalogParameter => $record, 'modal' => 1, 'return_to' => request()->fullUrl()]) }}" data-service-modal-title="Edit {{ $catalogLabel }}" aria-label="Edit {{ $catalogLabel }}"><i class="bi bi-pencil" aria-hidden="true"></i><span>Edit</span></button>
            <button type="button" class="row-action-item compact-action-item is-edit-action" data-row-menu-item data-service-photo-upload
                data-service-modal-url="{{ route('admin.catalog-photo.edit', ['type' => array_search($record::class, \App\Support\CatalogPhotos::TYPES, true), 'id' => $record->getKey()]) }}"
                data-service-modal-title="{{ $record->photo_path ? 'Change Photo' : 'Upload Photo' }}">
                <i class="bi bi-image" aria-hidden="true"></i><span>{{ $record->photo_path ? 'Change Photo' : 'Upload Photo' }}</span>
            </button>
            @if($record->is_active && !($record instanceof \App\Models\Package))
            <form data-service-status-confirm data-affected-packages="{{ $affectedNames->toJson() }}" data-dependency-reason="{{ $dependencyReason }}" method="POST" action="{{ route('admin.'.$catalogRoute.'.toggleAvailability', [$catalogParameter => $record]) }}">
                @csrf @method('PATCH')
                <button type="submit" class="row-action-item compact-action-item {{ $record->is_available ? 'is-caution' : 'is-positive' }}" data-row-menu-item aria-label="{{ $record->is_available ? 'Mark unavailable' : 'Mark available' }} {{ $catalogLabel }}"><i class="bi bi-{{ $record->is_available ? 'pause-circle' : 'check-circle' }}" aria-hidden="true"></i><span>{{ $record->is_available ? 'Unavailable' : 'Available' }}</span></button>
            </form>
            @endif
            <form data-service-status-confirm data-package-links="{{ $archiveLinks->toJson() }}" data-archive-block="{{ $record instanceof \App\Models\Package ? '' : app(\App\Support\ServiceCatalogPolicy::class)->archiveBlockMessage($record) }}" data-dependency-reason="{{ $dependencyReason }}" data-affected-packages="{{ $affectedNames->toJson() }}" method="POST" action="{{ route('admin.'.$catalogRoute.'.toggleActive', [$catalogParameter => $record]) }}">
                @csrf
                @method('PATCH')
                <button type="submit" class="row-action-item compact-action-item {{ $record->is_active ? 'is-caution' : 'is-positive' }}" data-row-menu-item aria-label="{{ $record->is_active ? 'Archive' : 'Restore' }} {{ $catalogLabel }}"><i class="bi {{ $record->is_active ? 'bi-archive' : 'bi-arrow-counterclockwise' }}" aria-hidden="true"></i><span>{{ $record->is_active ? 'Archive' : 'Restore' }}</span></button>
            </form>
            </div>
        </div>
    @endif
</div>


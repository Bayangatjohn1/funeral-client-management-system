@if($photoRecord && ($catalogPhotoUrl = \App\Support\CatalogPhotos::url($photoRecord)))
    <a class="catalog-photo catalog-photo--{{ $photoSize ?? 'thumbnail' }}" href="{{ $catalogPhotoUrl }}" data-proof-viewer data-proof-mime="{{ $photoRecord->photo_mime }}" data-proof-title="Catalog Photo" aria-label="View photo of {{ $photoName ?? $photoRecord->name }}">
        <img src="{{ $catalogPhotoUrl }}" alt="{{ $photoName ?? $photoRecord->name }}" loading="lazy" width="{{ ($photoSize ?? '') === 'card' ? 480 : 80 }}" height="{{ ($photoSize ?? '') === 'card' ? 270 : 60 }}">
    </a>
@endif

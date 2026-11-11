<div class="service-card-media" data-card-media>
    @if($mediaRecord->photo_path)
        @include('admin.service-management.partials.photo', ['photoRecord' => $mediaRecord, 'photoSize' => 'card', 'photoName' => $mediaName ?? $mediaRecord->name])
    @elseif($allowPhotoUpload ?? false)
        <button type="button" class="service-card-media__upload" @if($branchPhoto ?? false) data-branch-photo-upload @else data-service-photo-upload @endif
            data-service-modal-url="{{ route('admin.catalog-photo.edit', ['type' => array_search($mediaRecord::class, \App\Support\CatalogPhotos::TYPES, true), 'id' => $mediaRecord->getKey()]) }}"
            data-service-modal-title="Upload Photo" aria-label="Upload photo for {{ $mediaName ?? $mediaRecord->name }}">
            <i class="bi bi-cloud-arrow-up" aria-hidden="true"></i>
            <span>Upload Photo</span>
            <small>Or drop an image here</small>
        </button>
    @else
        <span class="service-card-media__empty"><i class="bi bi-image" aria-hidden="true"></i><span>Photo not added</span></span>
    @endif
</div>

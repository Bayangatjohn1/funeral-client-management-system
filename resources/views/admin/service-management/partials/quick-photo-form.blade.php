<form class="catalog-quick-photo" data-quick-catalog-photo method="POST" enctype="multipart/form-data"
    action="{{ route('admin.catalog-photo.update', ['type' => $type, 'id' => $catalog->getKey()]) }}">
    @csrf
    @method('PATCH')
    <div class="catalog-quick-photo__record">
        <h2>{{ $photoName }}</h2>
        <p>Photo changes apply to this item when you save.</p>
    </div>
    @include('admin.service-management.partials.photo-field', ['photoDropZone' => true, 'photoCaption' => false, 'photoName' => $photoName])
    <p class="catalog-quick-photo__error proof-error" data-quick-photo-error role="alert" hidden></p>
    <footer class="catalog-quick-photo__actions">
        <a class="btn btn-outline" data-service-form-cancel href="{{ $returnTo }}">Cancel</a>
        <button class="btn btn-primary-custom" data-catalog-photo-save type="submit" disabled>Save Photo</button>
    </footer>
</form>

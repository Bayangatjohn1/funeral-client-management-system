@php($photoInputId = 'catalog-photo-'.$catalog->getTable().'-'.($catalog->getKey() ?? 'new'))
<section class="catalog-photo-editor" data-catalog-photo>
    <div class="catalog-photo-heading">Photo <small>(Optional)</small></div>
    <input type="file" id="{{ $photoInputId }}" name="catalog_photo" accept="image/jpeg,image/png" hidden>
    <input type="hidden" name="catalog_photo_remove" value="{{ old('catalog_photo_remove', '0') }}" data-catalog-photo-remove>
    @if($photoUrl = \App\Support\CatalogPhotos::url($catalog))
        <div class="catalog-photo-current" data-catalog-photo-current>
            <a href="{{ $photoUrl }}" data-proof-viewer data-proof-mime="{{ $catalog->photo_mime }}" data-proof-title="Catalog Photo" aria-label="View current photo of {{ $photoName ?? $catalog->name }}"><img src="{{ $photoUrl }}" alt="{{ $photoName ?? $catalog->name }}" width="120" height="90"></a>
            <div><span>Current photo</span><button type="button" class="catalog-photo-remove" data-catalog-photo-remove-saved><i class="bi bi-trash3" aria-hidden="true"></i> Remove Photo</button></div>
        </div>
        <p class="catalog-photo-pending" data-catalog-photo-undo hidden>Photo will be removed on Save. <button type="button" data-catalog-photo-undo-button>Keep Current Photo</button></p>
    @endif
    <x-payment-proof-upload context="catalog" :target="$photoInputId" label="Photo" accept="image/jpeg,image/png" :restore-old="false" :drop-zone="$photoDropZone ?? false" />
    @if($photoCaption ?? true)
        <p class="catalog-photo-caption">{{ $photoCaption ?? 'A clear item photo helps clients compare services. Changes apply when you save.' }}</p>
    @endif
    @error('catalog_photo')<p class="form-error" data-validation-for="catalog_photo" role="alert">{{ $message }}</p>@enderror
    @error('catalog_photo_remove')<p class="form-error" role="alert">{{ $message }}</p>@enderror
</section>

<div class="catalog-name-with-photo">
    @include('admin.service-management.partials.photo', ['photoRecord' => $catalog, 'photoSize' => 'thumbnail'])
    <div><span class="catalog-primary-cell">{{ $catalog->name }}</span> <small class="service-catalog-record-id">ID #{{ $catalog->getKey() }}</small></div>
</div>

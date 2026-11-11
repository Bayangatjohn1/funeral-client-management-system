@php
    $adviceState = $adviceCatalog ? (!$adviceCatalog->is_active ? 'archived' : (!$adviceCatalog->is_available ? 'unavailable' : null)) : null;
@endphp
@if($adviceState)
    <div class="package-catalog-advice is-{{ $adviceState }}">{{ $adviceKind === 'freebie' ? 'Replace or remove this freebie.' : 'Choose an ' . ($adviceState === 'archived' ? 'active' : 'available') . ' ' . $adviceKind . '.' }}</div>
@endif

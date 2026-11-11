@if($catalog->category === \App\Models\AddOnCatalog::CHAPEL_VIEWING)
<p class="service-row-note">{{ $catalog->branch?->branch_name ?: 'Branch not assigned' }}@if(!$catalog->branch_id || blank($catalog->chapel_address)) · <strong>Needs Setup</strong> — configure branch and chapel address.@endif</p>
@endif

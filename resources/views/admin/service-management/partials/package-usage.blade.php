<section class="svc-detail-section"><h3>Packages using this item</h3>
@if($usagePackages->isNotEmpty())
<ul class="svc-package-usage">@foreach($usagePackages as $usagePackage)<li><span>{{ $usagePackage->name }}</span> <small>{{ $usagePackage->is_active ? 'Active' : 'Archived' }}</small></li>@endforeach</ul>
@else<p class="svc-detail-note">No packages linked.</p>@endif
</section>
<style>.svc-package-usage { list-style:none; margin:0; padding:0; }.svc-package-usage li { display:flex; justify-content:space-between; gap:12px; padding:7px 0; border-bottom:1px solid var(--border); font-size:13px; }.svc-package-usage small { color:var(--ink-muted); }</style>

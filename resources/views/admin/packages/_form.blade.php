@php
    $isEdit = isset($package);
    $action = $isEdit ? route('admin.packages.update', $package) : route('admin.packages.store');
    $buttonText = $isEdit ? 'Save Changes' : 'Save Package';
    $returnTo = old('return_to', request('return_to', route('admin.packages.index')));
    $serviceLabels = $serviceTypeOptions ?? \App\Models\Package::serviceTypeOptions();
    $standardTypes = [
        \App\Models\Package::SERVICE_BODY_RETRIEVAL,
        \App\Models\Package::SERVICE_EMBALMING,
        \App\Models\Package::SERVICE_CASKET,
        \App\Models\Package::SERVICE_HOME_VIEWING,
        \App\Models\Package::SERVICE_HEARSE,
    ];
    $serviceIcons = [
        \App\Models\Package::SERVICE_BODY_RETRIEVAL => 'bi-truck',
        \App\Models\Package::SERVICE_EMBALMING => 'bi-droplet',
        \App\Models\Package::SERVICE_CASKET => 'bi-box2-heart',
        \App\Models\Package::SERVICE_HOME_VIEWING => 'bi-house-heart',
        \App\Models\Package::SERVICE_HEARSE => 'bi-car-front',
    ];
    $existingServices = collect(old('included_services') ?? [])
        ->map(fn ($row) => is_array($row) ? $row : [])
        ->all();
    $existingCustom = collect(old('custom_inclusions') ?? [])
        ->map(fn ($row) => is_array($row) ? $row : ['description' => (string) $row])
        ->all();

    if ($isEdit && old('included_services') === null) {
        $existingServices = [];
        $existingCustom = [];
        foreach ($package->packageInclusions as $inclusion) {
            $type = $inclusion->service_type ?: \App\Models\Package::SERVICE_CUSTOM;
            if ($type === \App\Models\Package::SERVICE_CUSTOM || $inclusion->is_custom) {
                $existingCustom[] = ['description' => $inclusion->inclusion_name];
                continue;
            }
            $existingServices[$type] = [
                'enabled' => 1,
                'casket_catalog_id' => $inclusion->casket_catalog_id,
                'included_kilometers' => $inclusion->included_kilometers,
                'price_per_excess_kilometer' => $inclusion->price_per_excess_kilometer,
                'included_days' => $inclusion->included_days,
                'price_per_extended_day' => $inclusion->price_per_extended_day,
                'casket_type' => $inclusion->casket_type,
            ];
        }
    }

    if (! $isEdit && old('included_services') === null) {
        $existingServices = collect($standardTypes)
            ->mapWithKeys(fn ($type) => [$type => ['enabled' => 1]])
            ->all();
    }

    $existingFreebies = old('freebies');
    if (! is_array($existingFreebies)) {
        $existingFreebies = $isEdit
            ? $package->packageFreebies->map(fn ($freebie) => [
                'freebie_catalog_id' => $freebie->freebie_catalog_id,
                'freebie_name' => $freebie->freebie_name,
                'quantity' => $freebie->quantity ?: 1,
                'unit' => $freebie->unit ?: 'item',
            ])->all()
            : [];
    }
    $freebieCatalogIds = $freebieCatalogs->pluck('id')->map(fn ($id) => (int) $id);
    $selectedFreebiesByCatalog = collect($existingFreebies)
        ->filter(fn ($freebie) => ! empty($freebie['freebie_catalog_id']) && $freebieCatalogIds->contains((int) $freebie['freebie_catalog_id']))
        ->keyBy(fn ($freebie) => (int) $freebie['freebie_catalog_id']);
    $customFreebies = collect($existingFreebies)
        ->filter(fn ($freebie) => empty($freebie['freebie_catalog_id']) || ! $freebieCatalogIds->contains((int) $freebie['freebie_catalog_id']))
        ->values();
    $enabledServiceCount = collect($standardTypes)
        ->filter(fn ($type) => ! empty($existingServices[$type]['enabled']))
        ->count();
    $selectedFreebieCount = $selectedFreebiesByCatalog->count() + $customFreebies->count();
    $promoEnabled = (bool) old('promo_is_active', $package->promo_is_active ?? false);

@endphp

<style>
    html:not([data-theme='dark']) .page-content { background:#E8E4DC !important; }
    .pkg-workspace { color:#2F302C; max-width:1320px; padding-top:24px; padding-bottom:34px; }
    .pkg-hero { border:1px solid #C9C5BB; background:#FAFAF7; border-radius:8px; padding:22px 24px; display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:16px; }
    .pkg-eyebrow { display:flex; align-items:center; gap:7px; font-size:.74rem; line-height:1.25; font-weight:900; text-transform:uppercase; letter-spacing:.08em; color:#5F685F; }
    .pkg-title { margin:5px 0 0; font-size:1.38rem; line-height:1.2; font-weight:950; color:#2F302C; }
    .pkg-subtitle { margin-top:7px; max-width:780px; color:#5E695B; font-size:.92rem; line-height:1.55; font-weight:650; }
    .pkg-status-chip { display:inline-flex; align-items:center; gap:7px; border:1px solid #D8D2C7; border-radius:999px; padding:8px 12px; background:#FFFFFF; color:#3E4A3D; font-size:.74rem; line-height:1.2; font-weight:900; text-transform:uppercase; letter-spacing:.055em; }
    .pkg-layout { display:grid; grid-template-columns:minmax(0,1fr) 370px; gap:22px; align-items:start; }
    .pkg-main { display:grid; gap:18px; min-width:0; }
    .pkg-panel { border:1px solid #C9C5BB; background:#FAFAF7; border-radius:8px; overflow:hidden; }
    .pkg-panel-head { padding:18px 20px; border-bottom:1px solid #E1DDD5; display:flex; align-items:flex-start; justify-content:space-between; gap:16px; }
    .pkg-panel.is-collapsible .pkg-panel-head { align-items:center; cursor:pointer; }
    .pkg-panel.is-collapsed .pkg-panel-head { border-bottom:0; }
    .pkg-panel.is-collapsed .pkg-panel-body { display:none; }
    .pkg-panel-head-actions { display:flex; align-items:center; gap:9px; flex-wrap:wrap; justify-content:flex-end; }
    .pkg-panel-toggle { width:36px; height:36px; border:1px solid #C9C5BB; border-radius:8px; background:#FFFFFF; color:#3E4A3D; display:inline-flex; align-items:center; justify-content:center; cursor:pointer; transition:background .15s ease, border-color .15s ease, transform .15s ease; }
    .pkg-panel-toggle:hover { background:#F3F0E8; border-color:#8C9786; }
    .pkg-panel-toggle i { transition:transform .15s ease; }
    .pkg-panel.is-collapsed .pkg-panel-toggle i { transform:rotate(-90deg); }
    .pkg-panel-title { display:flex; align-items:center; gap:11px; color:#2F302C; font-size:1.04rem; line-height:1.25; font-weight:950; }
    .pkg-panel-title .pkg-icon { width:34px; height:34px; border-radius:7px; display:inline-flex; align-items:center; justify-content:center; background:#E6ECE3; color:#3E4A3D; flex:0 0 auto; }
    .pkg-panel-copy { margin-top:5px; color:#5F6B5C; font-size:.86rem; line-height:1.55; font-weight:650; }
    .pkg-panel-body { padding:20px; }
    .pkg-grid { display:grid; gap:16px; }
    .pkg-grid-2 { grid-template-columns:1fr 220px; }
    .pkg-label { display:block; margin-bottom:8px; font-size:.74rem; line-height:1.25; font-weight:950; text-transform:uppercase; letter-spacing:.07em; color:#53604F; }
    .pkg-required { color:#9E4B3F; }
    .pkg-workspace .form-input, .pkg-workspace .form-select, .pkg-workspace textarea { width:100%; border:1px solid #C9C5BB; border-radius:7px; background:#FFFFFF; color:#2F302C; font-size:.92rem; line-height:1.45; font-weight:650; min-height:44px; padding-left:12px; padding-right:12px; }
    .pkg-workspace .form-input:focus, .pkg-workspace .form-select:focus, .pkg-workspace textarea:focus { border-color:#3E4A3D; box-shadow:0 0 0 4px rgba(62,74,61,.13); }
    .pkg-money { display:flex; align-items:center; border:1px solid #C9C5BB; border-radius:7px; background:#FFFFFF; overflow:hidden; }
    .pkg-money span { padding:0 0 0 12px; color:#5F685F; font-weight:950; }
    .pkg-money .form-input { border:0; border-radius:0; box-shadow:none; min-height:42px; }
    .pkg-casket-summary { margin-top:10px; display:grid; gap:8px; border:1px solid #D9D1C4; border-radius:8px; background:#FFFFFF; padding:12px; color:#2F302C; }
    .pkg-casket-summary strong { font-size:.94rem; line-height:1.2; }
    .pkg-casket-summary span { color:#5F6B5C; font-size:.8rem; font-weight:750; }
    .pkg-casket-meta { display:flex; flex-wrap:wrap; gap:6px; }
    .pkg-casket-meta span { border:1px solid #E4DED2; border-radius:999px; background:#F8F5EE; padding:5px 8px; color:#4E584B; }
    .pkg-casket-summary-top { display:flex; align-items:flex-start; justify-content:space-between; gap:10px; }
    .pkg-casket-edit { border:0; background:transparent; padding:0; display:inline-flex; align-items:center; gap:5px; color:#6F4D18; font-size:.78rem; font-weight:950; text-decoration:none; white-space:nowrap; cursor:pointer; }
    .pkg-casket-price-missing { color:#9A5A00 !important; }
    .pkg-modal-backdrop { position:fixed; inset:0; z-index:70; display:none; align-items:center; justify-content:center; background:rgba(18,24,20,.48); padding:18px; }
    .pkg-modal-backdrop.is-open { display:flex; }
    .pkg-modal { width:min(560px,100%); border:1px solid #C9C5BB; border-radius:8px; background:#FAFAF7; box-shadow:0 24px 70px rgba(18,24,20,.28); overflow:hidden; color:#2F302C; }
    .pkg-modal-head { display:flex; align-items:flex-start; justify-content:space-between; gap:14px; padding:18px 20px; border-bottom:1px solid #E1DDD5; }
    .pkg-modal-title { font-size:1.03rem; font-weight:950; line-height:1.25; color:#2F302C; display:flex; align-items:center; gap:9px; }
    .pkg-modal-copy { margin-top:4px; color:#5F6B5C; font-size:.84rem; font-weight:650; line-height:1.45; }
    .pkg-modal-close { width:36px; height:36px; border:1px solid #D8D2C7; border-radius:8px; background:#fff; color:#3E4A3D; display:inline-flex; align-items:center; justify-content:center; }
    .pkg-modal-body { padding:18px 20px; display:grid; gap:15px; }
    .pkg-modal-actions { padding:15px 20px; border-top:1px solid #E1DDD5; background:#F8F4EC; display:flex; justify-content:flex-end; gap:10px; }
    .pkg-modal-error { display:none; border:1px solid #F2C7BF; border-radius:8px; background:#FFF2EF; color:#914137; padding:10px 12px; font-size:.82rem; font-weight:800; line-height:1.4; }
    .pkg-modal-error.is-visible { display:block; }
    .pkg-field-error { display:none; margin-top:6px; color:#9E4B3F; font-size:.78rem; font-weight:800; }
    .pkg-field-error.is-visible { display:block; }
    .pkg-modal-help { margin-top:7px; color:#687466; font-size:.77rem; line-height:1.4; font-weight:700; }
    .pkg-service-list { display:grid; gap:12px; }
    .pkg-service-card { border:1px solid #D4CEC2; border-radius:8px; background:#FFFFFF; overflow:hidden; transition:border-color .16s ease, box-shadow .16s ease, background .16s ease; }
    .pkg-service-card.is-enabled { border-color:#819078; box-shadow:0 2px 10px rgba(62,74,61,.08); }
    .pkg-service-card[data-service-row] { cursor:pointer; }
    .pkg-service-top { width:100%; display:flex; align-items:center; justify-content:space-between; gap:14px; padding:15px 16px; }
    .pkg-service-name { display:flex; align-items:center; gap:11px; min-width:0; }
    .pkg-service-icon { width:34px; height:34px; border-radius:7px; display:inline-flex; align-items:center; justify-content:center; background:#F0EEE8; color:#3E4A3D; flex:0 0 auto; }
    .pkg-service-card.is-enabled .pkg-service-icon { background:#E6ECE3; }
    .pkg-service-label { display:block; font-weight:950; font-size:.96rem; color:#2F302C; line-height:1.3; }
    .pkg-service-note { display:block; margin-top:3px; font-size:.78rem; line-height:1.4; color:#657360; font-weight:700; }
    .pkg-switch { position:relative; width:42px; height:24px; display:inline-flex; flex:0 0 auto; }
    .pkg-switch input { opacity:0; width:0; height:0; }
    .pkg-slider { position:absolute; inset:0; border-radius:999px; background:#D9D3C8; transition:.18s ease; }
    .pkg-slider:before { content:""; position:absolute; width:18px; height:18px; left:3px; top:3px; border-radius:999px; background:#FFFFFF; transition:.18s ease; box-shadow:0 1px 3px rgba(0,0,0,.18); }
    .pkg-switch input:checked + .pkg-slider { background:#3E4A3D; }
    .pkg-switch input:checked + .pkg-slider:before { transform:translateX(18px); }
    .pkg-service-fields { display:none; border-top:1px solid #E8E3DA; padding:16px; background:#FBFAF6; }
    .pkg-service-card.is-enabled .pkg-service-fields { display:block; }
    .pkg-service-optional { display:none; border-top:1px solid #E8E3DA; padding:12px 16px 16px; background:#FBFAF6; }
    .pkg-service-card.is-enabled .pkg-service-optional { display:block; }
    .pkg-service-extra { display:none; margin-top:12px; }
    .pkg-service-card.is-extra-open .pkg-service-extra { display:block; }
    .pkg-service-extra-toggle { min-height:38px; display:inline-flex; align-items:center; gap:7px; border:1px solid #C9C5BB; border-radius:7px; background:#FFFFFF; color:#3E4A3D; padding:8px 11px; font-size:.78rem; line-height:1.25; font-weight:850; cursor:pointer; transition:background .15s ease, border-color .15s ease; }
    .pkg-service-extra-toggle:hover { background:#F3F0E8; border-color:#8C9786; }
    .pkg-badge { display:inline-flex; align-items:center; gap:6px; border-radius:999px; padding:5px 9px; font-size:.66rem; font-weight:950; text-transform:uppercase; letter-spacing:.06em; white-space:nowrap; }
    .pkg-badge-included { background:#E6EFE5; color:#2F5B35; }
    .pkg-badge-free { background:#FFF3D7; color:#7A560A; }
    .pkg-badge-charge { background:#F7E5DF; color:#92463D; }
    .pkg-muted-box { border:1px dashed #C9C5BB; border-radius:8px; background:#F6F3EC; padding:13px; }
    .pkg-list-head { display:flex; align-items:flex-start; justify-content:space-between; gap:14px; margin-bottom:14px; }
    .pkg-subhead-title { color:#2F302C; font-size:.9rem; line-height:1.3; font-weight:950; }
    .pkg-inline-title { color:#2F302C; font-size:.9rem; line-height:1.3; font-weight:950; }
    .pkg-inline-help { display:block; color:#687466; font-size:.78rem; line-height:1.4; font-weight:750; margin-top:4px; }
    .pkg-list-actions { display:flex; flex-wrap:wrap; align-items:center; justify-content:flex-end; gap:9px; }
    .pkg-row-list { display:grid; gap:12px; }
    .pkg-row-list:empty::before { content:attr(data-empty); display:block; border:1px dashed #D4CEC2; border-radius:8px; background:#FFFFFF; padding:16px; color:#6F7A6B; font-size:.86rem; line-height:1.45; font-weight:750; text-align:center; }
    .pkg-row-card { position:relative; border:1px solid #E0DAD0; border-radius:8px; background:#FFFFFF; transition:border-color .15s ease, box-shadow .15s ease, background .15s ease; }
    .pkg-row-card:hover, .pkg-row-card:focus-within { border-color:#AEB7A8; box-shadow:0 2px 10px rgba(62,74,61,.08); background:#FFFDF8; }
    .pkg-custom-row { display:grid; grid-template-columns:36px minmax(0,1fr) 38px; gap:10px; align-items:center; padding:12px; }
    .pkg-freebie-row { display:grid; grid-template-columns:36px minmax(0,1.2fr) 104px 104px 38px; gap:10px; align-items:end; padding:12px; }
    .pkg-freebie-name { grid-column:2 / -1; }
    .pkg-freebie-checklist { display:grid; gap:10px; }
    .pkg-freebie-card { display:grid; grid-template-columns:1fr; gap:12px; align-items:start; border:1px solid #E0DAD0; border-radius:8px; background:#FFFFFF; padding:12px; transition:border-color .15s ease, background .15s ease, box-shadow .15s ease; }
    .pkg-freebie-card.is-selected { border-color:#819078; background:#F7FAF3; box-shadow:0 2px 10px rgba(62,74,61,.08); }
    .pkg-freebie-choice { display:flex; align-items:flex-start; gap:11px; min-width:0; cursor:pointer; }
    .pkg-freebie-choice input { position:absolute; opacity:0; pointer-events:none; }
    .pkg-freebie-mark { width:22px; height:22px; margin-top:1px; border:1px solid #AEB7A8; border-radius:6px; background:#EEF3E8; color:transparent; display:inline-grid; place-items:center; flex:0 0 auto; transition:background .15s ease, border-color .15s ease, color .15s ease, box-shadow .15s ease; }
    .pkg-freebie-choice input:checked + .pkg-freebie-mark { background:#344333; border-color:#344333; color:#FFFFFF; box-shadow:0 0 0 3px rgba(52,67,51,.12); }
    .pkg-freebie-choice input:focus-visible + .pkg-freebie-mark { outline:2px solid #344333; outline-offset:2px; }
    .pkg-freebie-mark i { display:block; font-size:1rem; line-height:1; transform:translateY(.5px); }
    .pkg-freebie-choice strong { display:block; color:#2F302C; font-size:.93rem; line-height:1.35; font-weight:850; }
    .pkg-freebie-choice span { display:block; margin-top:3px; color:#687466; font-size:.76rem; line-height:1.35; font-weight:700; }
    .pkg-freebie-config-grid { display:grid; grid-template-columns:96px minmax(120px,1fr); gap:12px; padding-left:33px; }
    .pkg-freebie-card:not(.is-selected) .pkg-freebie-config { opacity:.52; }
    .pkg-freebie-legacy { margin-top:14px; display:grid; gap:10px; }
    .pkg-freebie-legacy-title { color:#566653; font-size:.73rem; font-weight:850; text-transform:uppercase; letter-spacing:.06em; }
    .pkg-freebie-actions { display:inline-flex; align-items:center; gap:0; border:1px solid #C9C5BB; border-radius:8px; background:#FFFFFF; overflow:hidden; }
    .pkg-freebie-action { min-height:36px; display:inline-flex; align-items:center; justify-content:center; gap:6px; border:0; border-right:1px solid #D8D2C7; background:transparent; color:#3E4A3D; cursor:pointer; padding:0 10px; font-size:.74rem; line-height:1.2; font-weight:850; transition:background .15s ease, color .15s ease; }
    .pkg-freebie-action:last-child { border-right:0; }
    .pkg-freebie-action:hover { background:#F3F0E8; color:#2F392E; }
    .pkg-row-field { display:grid; gap:5px; min-width:0; }
    .pkg-row-label { font-size:.7rem; line-height:1.25; font-weight:950; text-transform:uppercase; letter-spacing:.065em; color:#5F6B5C; }
    .pkg-row-grip { width:36px; height:44px; border-radius:7px; display:inline-flex; align-items:center; justify-content:center; background:#F0EEE8; color:#6B7466; align-self:end; }
    .pkg-custom-row .pkg-row-grip { align-self:center; }
    .pkg-row-meta { grid-column:2 / -1; color:#6B7466; font-size:.73rem; font-weight:800; margin-top:-2px; }
    .pkg-tool-btn { min-height:42px; display:inline-flex; align-items:center; justify-content:center; gap:7px; border:1px solid #C9C5BB; border-radius:7px; background:#FFFFFF; color:#3E4A3D; padding:9px 12px; font-size:.82rem; line-height:1.25; font-weight:950; cursor:pointer; transition:background .15s ease, border-color .15s ease; }
    .pkg-tool-btn:hover { background:#F3F0E8; border-color:#8C9786; }
    .pkg-remove-all-btn { color:#8B4A41; background:#FFF9F7; border-color:#E1C9C2; }
    .pkg-remove-all-btn:hover { background:#F7E5DF; border-color:#C68F82; }
    .pkg-remove-btn { min-height:44px; width:38px; padding:0; color:#8B4A41; background:#FFF9F7; border-color:#E7D0C9; opacity:0; transform:scale(.92); transition:opacity .15s ease, transform .15s ease, background .15s ease, border-color .15s ease; align-self:end; }
    .pkg-row-card:hover .pkg-remove-btn, .pkg-row-card:focus-within .pkg-remove-btn { opacity:1; transform:scale(1); }
    .pkg-promo-toggle { display:flex; align-items:center; justify-content:space-between; gap:12px; border:1px solid #D7D1C6; background:#FFFFFF; border-radius:8px; padding:12px 14px; margin-bottom:14px; }
    .pkg-promo-fields { border-top:1px solid #E5DFD5; padding-top:14px; }
    .pkg-review { position:sticky; top:96px; border:1px solid #BEB7AA; background:#FEFDF9; border-radius:8px; overflow:hidden; }
    .pkg-review-head { padding:18px; border-bottom:1px solid #E1DDD5; background:#F3F0E8; }
    .pkg-review-title { display:flex; align-items:center; gap:10px; font-size:1.04rem; line-height:1.25; font-weight:950; color:#2F302C; }
    .pkg-review-list { padding:16px 18px; display:grid; gap:14px; font-size:.9rem; color:#343631; }
    .pkg-review-item { display:grid; gap:7px; }
    .pkg-review-kicker { font-size:.72rem; line-height:1.25; font-weight:950; color:#5F6B5C; letter-spacing:.07em; text-transform:uppercase; }
    .pkg-review-value { font-weight:850; line-height:1.5; }
    .pkg-review-lines { display:grid; gap:6px; margin:0; padding:0; list-style:none; }
    .pkg-review-lines li { display:flex; align-items:flex-start; gap:8px; padding:8px 10px; border:1px solid #E5DFD5; border-radius:7px; background:#FFFFFF; color:#343631; line-height:1.45; }
    .pkg-review-lines i { color:#3E4A3D; margin-top:2px; flex:0 0 auto; }
    .pkg-review-empty { color:#7A8275; font-weight:750; }
    .pkg-review-actions { padding:16px; display:grid; gap:10px; border-top:1px solid #D8D0C2; background:#F8F4EC; }
    .pkg-primary { border:1px solid #2F392E; background:#3E4A3D; color:#FFFFFF; min-height:48px; justify-content:center; border-radius:8px; font-size:.92rem; font-weight:950; box-shadow:0 8px 18px rgba(62,74,61,.20); }
    .pkg-primary:hover { background:#2F392E; color:#FFFFFF; box-shadow:0 10px 22px rgba(62,74,61,.24); }
    .pkg-link-btn { min-height:44px; display:inline-flex; align-items:center; justify-content:center; border:1px solid #BDB5A8; border-radius:8px; color:#3E4A3D; background:#FFFFFF; font-weight:950; text-decoration:none; }
    .pkg-link-btn:hover { background:#F3F0E8; border-color:#8C9786; color:#2F392E; }
    .pkg-empty { color:#7A8275; font-weight:650; }
    .form-error { margin-top:6px; color:#9E4B3F; font-size:.78rem; font-weight:800; }
    @media (max-width: 1024px) {
        .pkg-layout { grid-template-columns:1fr; }
        .pkg-review { position:static; }
    }
    html[data-theme='dark'] .page-content { background:#0f1a2e !important; }
    html[data-theme='dark'] .pkg-workspace { color:#e2ecf9; }
    html[data-theme='dark'] .pkg-hero,
    html[data-theme='dark'] .pkg-panel,
    html[data-theme='dark'] .pkg-review { background:#111e33; border-color:#243954; }
    html[data-theme='dark'] .pkg-panel-head,
    html[data-theme='dark'] .pkg-review-head { background:#13263d; border-color:#243954; }
    html[data-theme='dark'] .pkg-title,
    html[data-theme='dark'] .pkg-panel-title,
    html[data-theme='dark'] .pkg-service-label,
    html[data-theme='dark'] .pkg-review-title,
    html[data-theme='dark'] .pkg-subhead-title,
    html[data-theme='dark'] .pkg-inline-title,
    html[data-theme='dark'] .pkg-review-value,
    html[data-theme='dark'] .pkg-row-card { color:#e2ecf9; }
    html[data-theme='dark'] .pkg-subtitle,
    html[data-theme='dark'] .pkg-panel-copy,
    html[data-theme='dark'] .pkg-service-note,
    html[data-theme='dark'] .pkg-inline-help,
    html[data-theme='dark'] .pkg-review-empty { color:#9fb6d2; }
    html[data-theme='dark'] .pkg-eyebrow,
    html[data-theme='dark'] .pkg-label,
    html[data-theme='dark'] .pkg-row-label,
    html[data-theme='dark'] .pkg-review-kicker { color:#7fa6cf; }
    html[data-theme='dark'] .pkg-status-chip,
    html[data-theme='dark'] .pkg-service-card,
    html[data-theme='dark'] .pkg-casket-summary,
    html[data-theme='dark'] .pkg-row-card,
    html[data-theme='dark'] .pkg-freebie-actions,
    html[data-theme='dark'] .pkg-row-list:empty::before,
    html[data-theme='dark'] .pkg-promo-toggle,
    html[data-theme='dark'] .pkg-review-lines li,
    html[data-theme='dark'] .pkg-tool-btn,
    html[data-theme='dark'] .pkg-panel-toggle,
    html[data-theme='dark'] .pkg-link-btn { background:#152035; border-color:#2a3f5f; color:#d8ecff; }
    html[data-theme='dark'] .pkg-muted-box,
    html[data-theme='dark'] .pkg-service-fields,
    html[data-theme='dark'] .pkg-service-optional,
    html[data-theme='dark'] .pkg-review-actions { background:#102033; border-color:#243954; }
    html[data-theme='dark'] .pkg-service-card.is-enabled { border-color:#4a82c0; box-shadow:0 2px 14px rgba(74,130,192,.14); }
    html[data-theme='dark'] .pkg-row-card:hover,
    html[data-theme='dark'] .pkg-row-card:focus-within,
    html[data-theme='dark'] .pkg-tool-btn:hover,
    html[data-theme='dark'] .pkg-panel-toggle:hover,
    html[data-theme='dark'] .pkg-service-extra-toggle:hover,
    html[data-theme='dark'] .pkg-freebie-action:hover,
    html[data-theme='dark'] .pkg-link-btn:hover { background:#1a2b41; border-color:#3f5b7f; color:#e8f2ff; }
    html[data-theme='dark'] .pkg-freebie-action { color:#d8ecff; border-color:#2a3f5f; }
    html[data-theme='dark'] .pkg-service-extra-toggle { background:#152035; border-color:#2a3f5f; color:#d8ecff; }
    html[data-theme='dark'] .pkg-freebie-mark { background:#1a2b41; border-color:#3f5b7f; }
    html[data-theme='dark'] .pkg-freebie-choice input:checked + .pkg-freebie-mark { background:#3b82f6; border-color:#60a5fa; color:#ffffff; }
    html[data-theme='dark'] .pkg-panel-title .pkg-icon,
    html[data-theme='dark'] .pkg-service-icon,
    html[data-theme='dark'] .pkg-row-grip { background:#1a2b41; color:#7ec0ff; }
    html[data-theme='dark'] .pkg-workspace .form-input,
    html[data-theme='dark'] .pkg-workspace .form-select,
    html[data-theme='dark'] .pkg-workspace textarea,
    html[data-theme='dark'] .pkg-money { background:#1a2b41 !important; border-color:#2a3f5f !important; color:#d8ecff !important; }
    html[data-theme='dark'] .pkg-workspace .form-input::placeholder,
    html[data-theme='dark'] .pkg-workspace textarea::placeholder { color:#7891ad; }
    html[data-theme='dark'] .pkg-money span { color:#7fa6cf; }
    html[data-theme='dark'] .pkg-casket-summary span { color:#9fb6d2; }
    html[data-theme='dark'] .pkg-casket-meta span { background:#152035; border-color:#2a3f5f; color:#d8ecff; }
    html[data-theme='dark'] .pkg-casket-edit { color:#fcd34d; }
    html[data-theme='dark'] .pkg-casket-price-missing { color:#fcd34d !important; }
    html[data-theme='dark'] .pkg-modal { background:#111e33; border-color:#243954; color:#e2ecf9; }
    html[data-theme='dark'] .pkg-modal-head, html[data-theme='dark'] .pkg-modal-actions { background:#13263d; border-color:#243954; }
    html[data-theme='dark'] .pkg-modal-title { color:#e2ecf9; }
    html[data-theme='dark'] .pkg-modal-copy, html[data-theme='dark'] .pkg-modal-help { color:#9fb6d2; }
    html[data-theme='dark'] .pkg-modal-close { background:#152035; border-color:#2a3f5f; color:#d8ecff; }
    html[data-theme='dark'] .pkg-modal-error { background:rgba(127,29,29,.24); border-color:#7f1d1d; color:#fecaca; }
    html[data-theme='dark'] .pkg-workspace .form-input:focus,
    html[data-theme='dark'] .pkg-workspace .form-select:focus,
    html[data-theme='dark'] .pkg-workspace textarea:focus { border-color:#4a82c0 !important; box-shadow:0 0 0 4px rgba(74,130,192,.16) !important; }
    html[data-theme='dark'] .pkg-badge-included { background:rgba(34,197,94,.14); color:#86efac; }
    html[data-theme='dark'] .pkg-badge-free { background:rgba(245,158,11,.16); color:#fcd34d; }
    html[data-theme='dark'] .pkg-badge-charge,
    html[data-theme='dark'] .pkg-remove-all-btn,
    html[data-theme='dark'] .pkg-remove-btn { background:rgba(248,113,113,.12); border-color:#7f1d1d; color:#fca5a5; }
    html[data-theme='dark'] .pkg-primary { background:#3b82f6; border-color:#60a5fa; color:#ffffff; box-shadow:0 8px 18px rgba(59,130,246,.22); }
    html[data-theme='dark'] .pkg-primary:hover { background:#2563eb; color:#ffffff; box-shadow:0 10px 22px rgba(59,130,246,.26); }
    html[data-theme='dark'] .pkg-slider { background:#30465f; }
    html[data-theme='dark'] .pkg-switch input:checked + .pkg-slider { background:#3b82f6; }
    @media (max-width: 680px) {
        .pkg-workspace { padding:18px 1rem 28px; }
        .pkg-grid-2, .pkg-freebie-row, .pkg-custom-row, .pkg-freebie-card, .pkg-freebie-config-grid { grid-template-columns:1fr; }
        .pkg-freebie-config-grid { padding-left:0; }
        .pkg-freebie-name, .pkg-row-meta { grid-column:auto; }
        .pkg-row-grip { display:none; }
        .pkg-remove-btn { width:100%; opacity:1; transform:none; }
        .pkg-list-head { display:grid; }
        .pkg-list-actions { justify-content:stretch; }
        .pkg-list-actions .pkg-tool-btn { flex:1; }
        .pkg-panel-head, .pkg-panel-body, .pkg-hero { padding:16px; }
        .pkg-service-top { align-items:flex-start; }
    }
    html:not([data-theme='dark']) .page-content {
        background:
            linear-gradient(90deg, rgba(73,87,69,0.04) 0 1px, transparent 1px),
            linear-gradient(180deg, rgba(73,87,69,0.034) 0 1px, transparent 1px),
            repeating-linear-gradient(135deg, rgba(73,87,69,0.02) 0 1px, transparent 1px 12px),
            #C4D2BE !important;
        background-size:44px 44px,44px 44px,16px 16px,auto;
    }
    .pkg-workspace { color:var(--ink); }
    .pkg-hero,
    .pkg-panel,
    .pkg-review,
    .pkg-modal {
        background:#D3DEC9 !important;
        border-color:var(--border) !important;
        box-shadow:none !important;
    }
    .pkg-panel-head,
    .pkg-review-head,
    .pkg-modal-head,
    .pkg-modal-actions,
    .pkg-review-actions {
        background:#C7D5BE !important;
        border-color:var(--border) !important;
    }
    .pkg-title,
    .pkg-panel-title,
    .pkg-review-title,
    .pkg-modal-title,
    .pkg-service-label,
    .pkg-subhead-title,
    .pkg-inline-title,
    .pkg-review-value {
        color:var(--ink) !important;
        font-weight:750 !important;
    }
    .pkg-eyebrow,
    .pkg-label,
    .pkg-row-label,
    .pkg-review-kicker {
        color:#566653 !important;
        font-weight:650 !important;
    }
    .pkg-subtitle,
    .pkg-panel-copy,
    .pkg-service-note,
    .pkg-inline-help,
    .pkg-review-empty,
    .pkg-modal-copy,
    .pkg-modal-help {
        color:var(--ink-muted) !important;
        font-weight:600 !important;
    }
    .pkg-workspace .form-input,
    .pkg-workspace .form-select,
    .pkg-workspace textarea,
    .pkg-money,
    .pkg-service-card,
    .pkg-casket-summary,
    .pkg-row-card,
    .pkg-row-list:empty::before,
    .pkg-promo-toggle,
    .pkg-review-lines li,
    .pkg-status-chip,
    .pkg-muted-box,
    .pkg-service-fields {
        background:#E1E7D9 !important;
        border-color:var(--border) !important;
        box-shadow:none !important;
        color:var(--ink) !important;
    }
    .pkg-workspace .form-input:hover,
    .pkg-workspace .form-select:hover,
    .pkg-workspace textarea:hover,
    .pkg-tool-btn:hover,
    .pkg-link-btn:hover,
    .pkg-row-card:hover,
    .pkg-row-card:focus-within,
    .pkg-service-card:hover,
    .pkg-service-card:focus-within {
        background:#EEF3E8 !important;
        border-color:#8EA083 !important;
        box-shadow:none !important;
    }
    .pkg-workspace .form-input:focus,
    .pkg-workspace .form-select:focus,
    .pkg-workspace textarea:focus {
        background:#EEF3E8 !important;
        border-color:#8EA083 !important;
        box-shadow:none !important;
    }
    .pkg-panel-title .pkg-icon,
    .pkg-service-icon,
    .pkg-row-grip {
        background:transparent !important;
        color:#566653 !important;
    }
    .pkg-primary,
    .pkg-tool-btn,
    .pkg-link-btn,
    .pkg-modal-close,
    .pkg-remove-btn,
    .pkg-casket-edit,
    .pkg-switch,
    .pkg-switch * {
        cursor:pointer;
    }
    .pkg-primary {
        background:#344333 !important;
        border-color:#344333 !important;
        color:#fff !important;
        box-shadow:none !important;
    }
    .pkg-primary:hover {
        background:#2F3A2E !important;
        border-color:#2F3A2E !important;
        box-shadow:none !important;
    }
    .pkg-tool-btn,
    .pkg-link-btn,
    .pkg-modal-close {
        background:#E1E7D9 !important;
        border-color:var(--border) !important;
        color:var(--ink) !important;
        box-shadow:none !important;
    }
    .pkg-tool-btn:hover,
    .pkg-link-btn:hover,
    .pkg-modal-close:hover {
        background:#C7D5BE !important;
        border-color:#8EA083 !important;
        color:var(--ink) !important;
    }
</style>

<form id="packageForm" method="POST" action="{{ $action }}" class="pkg-workspace {{ $isEdit ? 'is-edit-mode' : '' }} w-full mx-auto px-4 sm:px-6 lg:px-8 grid gap-5">
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif
    <input type="hidden" name="return_to" value="{{ $returnTo }}">
    @if(session('success'))
        <div class="flash-success">{{ session('success') }}</div>
    @endif

    <header class="pkg-hero">
        <div>
            <div class="pkg-eyebrow"><i class="bi bi-box-seam"></i> Main Branch Package Setup</div>
            <h2 class="pkg-title">{{ $isEdit ? 'Edit Service Package' : 'Create Service Package' }}</h2>
            <p class="pkg-subtitle">Set the base package, service limits, casket, freebies, and promo rules in one clean workflow. Optional add-ons stay outside this form.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <span class="pkg-status-chip"><i class="bi bi-check-circle"></i> New packages active</span>
            <span class="pkg-status-chip"><i class="bi bi-clock-history"></i> Asia/Manila promo time</span>
        </div>
    </header>

    <div class="pkg-layout">
        <div class="pkg-main">
            <section class="pkg-panel {{ $isEdit ? 'is-collapsible is-collapsed' : '' }}" data-package-panel>
                <div class="pkg-panel-head" data-panel-head>
                    <div>
                        <div class="pkg-panel-title"><span class="pkg-icon"><i class="bi bi-card-heading"></i></span> Basic Details</div>
                        <p class="pkg-panel-copy">Name the package and set the base price.</p>
                    </div>
                </div>
                <div class="pkg-panel-body">
                    <div class="pkg-grid pkg-grid-2">
                        <div>
                            <label class="pkg-label">Package Name <span class="pkg-required">*</span></label>
                            <input type="text" name="name" value="{{ old('name', $package->name ?? '') }}" class="form-input" placeholder="e.g. First Class Package" required>
                            @error('name') <div class="form-error">{{ $message }}</div> @enderror
                        </div>
                        <div>
                            <label class="pkg-label">Base Price <span class="pkg-required">*</span></label>
                            <div class="pkg-money">
                                <span>&#8369;</span>
                                <input type="number" step="0.01" min="0" name="price" value="{{ old('price', $package->price ?? '') }}" class="form-input" placeholder="0.00" required>
                            </div>
                            @error('price') <div class="form-error">{{ $message }}</div> @enderror
                        </div>
                        <div class="md:col-span-2">
                            <label class="pkg-label">Short Description <span class="normal-case tracking-normal font-bold">(optional)</span></label>
                            <textarea name="short_description" rows="2" class="form-input" maxlength="500" placeholder="Short internal note shown to admins.">{{ old('short_description', $package->short_description ?? '') }}</textarea>
                            @error('short_description') <div class="form-error">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
            </section>

            <section class="pkg-panel {{ $isEdit ? 'is-collapsible is-collapsed' : '' }}" data-package-panel>
                <div class="pkg-panel-head" data-panel-head>
                    <div>
                        <div class="pkg-panel-title"><span class="pkg-icon"><i class="bi bi-check2-square"></i></span> Included Services</div>
                        <p class="pkg-panel-copy">Turn on services included in the base price.</p>
                    </div>
                    <div class="pkg-panel-head-actions">
                        <span class="pkg-badge pkg-badge-included">{{ $enabledServiceCount }} service{{ $enabledServiceCount === 1 ? '' : 's' }}</span>
                        @if($isEdit)
                            <button type="button" class="pkg-panel-toggle" data-panel-toggle aria-expanded="false" aria-label="Expand Included Services">
                                <i class="bi bi-chevron-down"></i>
                            </button>
                        @endif
                    </div>
                </div>
                <div class="pkg-panel-body">
                    <div class="pkg-service-list">
                        @foreach($standardTypes as $type)
                            @php
                                $row = $existingServices[$type] ?? [];
                                $isKmService = in_array($type, [\App\Models\Package::SERVICE_BODY_RETRIEVAL, \App\Models\Package::SERVICE_HEARSE], true);
                                $isDayService = in_array($type, [\App\Models\Package::SERVICE_EMBALMING, \App\Models\Package::SERVICE_HOME_VIEWING], true);
                                $hasExtraConfig = $isKmService
                                    ? filled($row['included_kilometers'] ?? null) || filled($row['price_per_excess_kilometer'] ?? null)
                                    : ($isDayService ? filled($row['included_days'] ?? null) || filled($row['price_per_extended_day'] ?? null) : false);
                            @endphp
                            <div class="pkg-service-card {{ !empty($row['enabled']) ? 'is-enabled' : '' }} {{ $hasExtraConfig ? 'is-extra-open' : '' }}" data-service-row role="button" tabindex="0">
                                <div class="pkg-service-top">
                                    <div class="pkg-service-name">
                                        <span class="pkg-service-icon"><i class="bi {{ $serviceIcons[$type] ?? 'bi-check2' }}"></i></span>
                                        <div>
                                            <span class="pkg-service-label">{{ $serviceLabels[$type] }}</span>
                                            <span class="pkg-service-note">
                                                @if($isKmService)
                                                    Includes kilometers; excess km can have an extra rate.
                                                @elseif($isDayService)
                                                    Includes days; extended days can have an extra rate.
                                                @else
                                                    Included base casket for this package.
                                                @endif
                                            </span>
                                        </div>
                                    </div>
                                    <label class="pkg-switch" title="Enable {{ $serviceLabels[$type] }}">
                                        <input type="hidden" name="included_services[{{ $type }}][enabled]" value="0">
                                        <input type="checkbox" name="included_services[{{ $type }}][enabled]" value="1" data-service-toggle {{ !empty($row['enabled']) ? 'checked' : '' }}>
                                        <span class="pkg-slider"></span>
                                    </label>
                                </div>
                                @if($isKmService || $isDayService)
                                    <div class="pkg-service-optional" data-service-optional>
                                        <button type="button" class="pkg-service-extra-toggle" data-service-extra-toggle aria-expanded="{{ $hasExtraConfig ? 'true' : 'false' }}">
                                            <i class="bi bi-sliders"></i>
                                            <span>{{ $isKmService ? 'Configure included km / excess rate' : 'Configure included days / extended rate' }}</span>
                                        </button>
                                        <div class="pkg-service-extra" data-service-extra>
                                            @if($isKmService)
                                                <div class="pkg-grid pkg-grid-2">
                                                    <div>
                                                        <label class="pkg-label">Included Kilometers</label>
                                                        <input type="number" min="0" name="included_services[{{ $type }}][included_kilometers]" value="{{ $row['included_kilometers'] ?? '' }}" class="form-input" placeholder="0" data-service-extra-field>
                                                    </div>
                                                    <div>
                                                        <label class="pkg-label">Excess Kilometer Rate</label>
                                                        <div class="pkg-money"><span>&#8369;</span><input type="number" step="0.01" min="0" name="included_services[{{ $type }}][price_per_excess_kilometer]" value="{{ $row['price_per_excess_kilometer'] ?? '' }}" class="form-input" placeholder="0.00" data-service-extra-field></div>
                                                    </div>
                                                </div>
                                            @else
                                                <div class="pkg-grid pkg-grid-2">
                                                    <div>
                                                        <label class="pkg-label">Included Days</label>
                                                        <input type="number" min="0" max="365" name="included_services[{{ $type }}][included_days]" value="{{ $row['included_days'] ?? '' }}" class="form-input" placeholder="0" data-service-extra-field>
                                                    </div>
                                                    <div>
                                                        <label class="pkg-label">Extended-Day Rate</label>
                                                        <div class="pkg-money"><span>&#8369;</span><input type="number" step="0.01" min="0" name="included_services[{{ $type }}][price_per_extended_day]" value="{{ $row['price_per_extended_day'] ?? '' }}" class="form-input" placeholder="0.00" data-service-extra-field></div>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @else
                                    <div class="pkg-service-fields">
                                        <div class="flex flex-wrap items-end justify-between gap-3">
                                            <div class="min-w-[220px] flex-1">
                                                <label class="pkg-label">Casket</label>
                                                <input type="search" class="form-input mb-2" data-casket-search placeholder="Search casket catalog">
                                                <select name="included_services[{{ $type }}][casket_catalog_id]" class="form-select" data-casket-select data-casket-required>
                                                    <option value="">Select casket</option>
                                                    @foreach($casketCatalogs as $catalog)
                                                        <option
                                                            value="{{ $catalog->id }}"
                                                            data-name="{{ $catalog->name }}"
                                                            data-material="{{ $catalog->type_or_material }}"
                                                            data-price="{{ (float) $catalog->standard_price }}"
                                                            data-description="{{ $catalog->description }}"
                                                            data-update-url="{{ route('admin.casket-catalogs.update', $catalog, absolute: false) }}"
                                                            @selected((int)($row['casket_catalog_id'] ?? 0) === $catalog->id)
                                                        >
                                                            {{ $catalog->display_name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <div class="form-error hidden" data-casket-required-error>Please select a casket before saving.</div>
                                                @error('included_services.casket.casket_catalog_id') <div class="form-error">{{ $message }}</div> @enderror
                                                <input type="hidden" name="included_services[{{ $type }}][casket_type]" value="{{ $row['casket_type'] ?? old('coffin_type', $package->coffin_type ?? '') }}" data-casket-snapshot>
                                            </div>
                                        </div>
                                        <div class="pkg-casket-summary" data-casket-summary>
                                            <strong>No casket selected</strong>
                                            <span>Select a catalog casket for this package.</span>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    <div class="pkg-muted-box mt-4">
                        <div class="pkg-list-head">
                            <div>
                                <div class="pkg-subhead-title">Other Package Inclusions</div>
                                <p class="pkg-panel-copy">Items included in the base package price.</p>
                            </div>
                            <div class="pkg-list-actions">
                                <button type="button" class="pkg-tool-btn pkg-remove-all-btn" data-clear-custom><i class="bi bi-trash3"></i> Remove All</button>
                                <button type="button" class="pkg-tool-btn" data-add-custom><i class="bi bi-plus-circle"></i> Add Other Inclusion</button>
                            </div>
                        </div>
                        <div class="pkg-row-list" data-custom-list data-empty="No other inclusions yet. Use Add Other Inclusion for items outside the standard services.">
                            @foreach($existingCustom as $i => $custom)
                                <div class="pkg-row-card pkg-custom-row" data-custom-row>
                                    <span class="pkg-row-grip"><i class="bi bi-list"></i></span>
                                    <input type="text" name="custom_inclusions[{{ $i }}][description]" value="{{ $custom['description'] ?? '' }}" class="form-input" placeholder="e.g. Memorial folder setup">
                                    <button type="button" class="pkg-tool-btn pkg-remove-btn" data-remove-row aria-label="Remove other inclusion"><i class="bi bi-x-lg"></i></button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    @error('included_services') <div class="form-error">{{ $message }}</div> @enderror
                </div>
            </section>

            <section class="pkg-panel {{ $isEdit ? 'is-collapsible is-collapsed' : '' }}" data-package-panel>
                <div class="pkg-panel-head" data-panel-head>
                    <div>
                        <div class="pkg-panel-title"><span class="pkg-icon"><i class="bi bi-gift"></i></span> Freebies</div>
                        <p class="pkg-panel-copy">Free items. They never change the package price.</p>
                    </div>
                    <div class="pkg-panel-head-actions">
                        <span class="pkg-badge pkg-badge-free" data-freebie-count>{{ $selectedFreebieCount }} selected</span>
                        <div class="pkg-freebie-actions" aria-label="Freebie selection actions">
                            <button type="button" class="pkg-freebie-action" data-select-all-freebies title="Select all freebies" aria-label="Select all freebies">
                                <i class="bi bi-check2-square" aria-hidden="true"></i>
                                <span>Select all</span>
                            </button>
                            <button type="button" class="pkg-freebie-action" data-unselect-all-freebies title="Unselect all freebies" aria-label="Unselect all freebies">
                                <i class="bi bi-square" aria-hidden="true"></i>
                                <span>Unselect all</span>
                            </button>
                        </div>
                        @if($isEdit)
                            <button type="button" class="pkg-panel-toggle" data-panel-toggle aria-expanded="false" aria-label="Expand Freebies">
                                <i class="bi bi-chevron-down"></i>
                            </button>
                        @endif
                    </div>
                </div>
                <div class="pkg-panel-body">
                    @if($freebieCatalogs->isNotEmpty())
                        <div class="pkg-freebie-checklist" data-freebie-list>
                            @foreach($freebieCatalogs as $i => $catalog)
                                @php
                                    $selectedFreebie = $selectedFreebiesByCatalog->get((int) $catalog->id);
                                    $isSelectedFreebie = (bool) $selectedFreebie;
                                    $freebieUnit = $selectedFreebie['unit'] ?? $catalog->default_unit ?? 'item';
                                @endphp
                                <div class="pkg-freebie-card {{ $isSelectedFreebie ? 'is-selected' : '' }}" data-freebie-card role="button" tabindex="0">
                                    <label class="pkg-freebie-choice">
                                        <input type="checkbox" data-freebie-check {{ $isSelectedFreebie ? 'checked' : '' }}>
                                        <span class="pkg-freebie-mark" aria-hidden="true"><i class="bi bi-check2"></i></span>
                                        <span>
                                            <strong>{{ $catalog->name }}</strong>
                                            <span>Default unit: {{ $catalog->default_unit ?: 'item' }}{{ $catalog->is_active ? '' : ' · archived' }}</span>
                                        </span>
                                    </label>
                                    <input type="hidden" name="freebies[{{ $i }}][freebie_catalog_id]" value="{{ $catalog->id }}" data-freebie-field @disabled(! $isSelectedFreebie)>
                                    <input type="hidden" name="freebies[{{ $i }}][freebie_name]" value="{{ $catalog->name }}" data-freebie-field @disabled(! $isSelectedFreebie)>
                                    <div class="pkg-freebie-config-grid">
                                        <div class="pkg-row-field pkg-freebie-config">
                                            <span class="pkg-row-label">Qty</span>
                                            <input type="number" min="1" name="freebies[{{ $i }}][quantity]" value="{{ $selectedFreebie['quantity'] ?? 1 }}" class="form-input" placeholder="1" data-freebie-field @disabled(! $isSelectedFreebie)>
                                        </div>
                                        <div class="pkg-row-field pkg-freebie-config">
                                            <span class="pkg-row-label">Unit</span>
                                            <input type="text" name="freebies[{{ $i }}][unit]" value="{{ $freebieUnit }}" class="form-input" placeholder="item" data-freebie-field @disabled(! $isSelectedFreebie)>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="pkg-row-list" data-empty="No configured freebies yet. Add freebies in Service Management first."></div>
                    @endif

                    @if($customFreebies->isNotEmpty())
                        <div class="pkg-freebie-legacy">
                            <div class="pkg-freebie-legacy-title">Existing Custom Freebies</div>
                            <div class="pkg-row-list" data-custom-freebie-list>
                                @foreach($customFreebies as $customIndex => $freebie)
                                    @php $rowIndex = $freebieCatalogs->count() + $customIndex; @endphp
                                    <div class="pkg-row-card pkg-freebie-row" data-custom-freebie-row>
                                        <span class="pkg-row-grip"><i class="bi bi-gift"></i></span>
                                        <div class="pkg-row-field pkg-freebie-name">
                                            <span class="pkg-row-label">Name</span>
                                            <input type="text" name="freebies[{{ $rowIndex }}][freebie_name]" value="{{ $freebie['freebie_name'] ?? '' }}" class="form-input" placeholder="e.g. Prayer cards">
                                        </div>
                                        <div class="pkg-row-field">
                                            <span class="pkg-row-label">Qty</span>
                                            <input type="number" min="1" name="freebies[{{ $rowIndex }}][quantity]" value="{{ $freebie['quantity'] ?? 1 }}" class="form-input" placeholder="1">
                                        </div>
                                        <div class="pkg-row-field">
                                            <span class="pkg-row-label">Unit</span>
                                            <input type="text" name="freebies[{{ $rowIndex }}][unit]" value="{{ $freebie['unit'] ?? 'item' }}" class="form-input" placeholder="item">
                                        </div>
                                        <button type="button" class="pkg-tool-btn pkg-remove-btn" data-remove-row aria-label="Remove custom freebie"><i class="bi bi-x-lg"></i></button>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    <div class="mt-3"><span class="pkg-badge pkg-badge-free">Free</span></div>
                    @error('freebies') <div class="form-error">{{ $message }}</div> @enderror
                </div>
            </section>

            <section class="pkg-panel">
                <div class="pkg-panel-head">
                    <div>
                        <div class="pkg-panel-title"><span class="pkg-icon"><i class="bi bi-tag"></i></span> Promo Settings</div>
                        <p class="pkg-panel-copy">Optional discount with automatic status.</p>
                    </div>
                    <div class="pkg-panel-head-actions">
                        <span class="pkg-badge {{ $promoEnabled ? 'pkg-badge-included' : 'pkg-badge-charge' }}">{{ $promoEnabled ? 'Promo active' : 'No promo' }}</span>
                        @if($isEdit)
                            <button type="button" class="pkg-panel-toggle" data-panel-toggle aria-expanded="false" aria-label="Expand Promo Settings">
                                <i class="bi bi-chevron-down"></i>
                            </button>
                        @endif
                    </div>
                </div>
                <div class="pkg-panel-body">
                    <label class="pkg-promo-toggle cursor-pointer">
                        <span>
                            <span class="pkg-inline-title block">Enable promo</span>
                            <span class="pkg-inline-help">The end date remains valid until 11:59:59 PM.</span>
                        </span>
                        <span class="pkg-switch">
                            <input type="hidden" name="promo_is_active" value="0">
                            <input type="checkbox" name="promo_is_active" value="1" data-promo-toggle {{ old('promo_is_active', $package->promo_is_active ?? false) ? 'checked' : '' }}>
                            <span class="pkg-slider"></span>
                        </span>
                    </label>
                    <div class="pkg-grid md:grid-cols-2 pkg-promo-fields" data-promo-fields>
                        <div>
                            <label class="pkg-label">Promo Label</label>
                            <input type="text" name="promo_label" value="{{ old('promo_label', $package->promo_label ?? '') }}" class="form-input" placeholder="e.g. Senior Discount">
                        </div>
                        <div>
                            <label class="pkg-label">Discount Type</label>
                            <select name="promo_value_type" class="form-select">
                                <option value="">Select type</option>
                                <option value="PERCENT" {{ old('promo_value_type', $package->promo_value_type ?? '') === 'PERCENT' ? 'selected' : '' }}>Percentage</option>
                                <option value="AMOUNT" {{ old('promo_value_type', $package->promo_value_type ?? '') === 'AMOUNT' ? 'selected' : '' }}>Fixed Amount</option>
                            </select>
                        </div>
                        <div>
                            <label class="pkg-label">Value</label>
                            <input type="number" step="0.01" min="0" name="promo_value" value="{{ old('promo_value', $package->promo_value ?? '') }}" class="form-input" placeholder="0.00">
                        </div>
                        <div class="pkg-grid md:grid-cols-2">
                            <div>
                                <label class="pkg-label">Start Date</label>
                                <input type="date" name="promo_starts_at" value="{{ old('promo_starts_at', isset($package) ? $package->promo_starts_at?->format('Y-m-d') : '') }}" class="form-input">
                            </div>
                            <div>
                                <label class="pkg-label">End Date</label>
                                <input type="date" name="promo_ends_at" value="{{ old('promo_ends_at', isset($package) ? $package->promo_ends_at?->format('Y-m-d') : '') }}" class="form-input">
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <aside class="pkg-review">
            <div class="pkg-review-head">
                <div class="pkg-review-title"><i class="bi bi-clipboard-check"></i> Review and Save</div>
                <p class="pkg-panel-copy">Check the saved package setup.</p>
            </div>
            <div class="pkg-review-list" data-review-list></div>
            <div class="pkg-review-actions">
                <button class="btn pkg-primary" type="submit" data-package-submit><i class="bi bi-check2-circle"></i> <span data-package-submit-label>{{ $buttonText }}</span></button>
                <a href="{{ $returnTo }}" class="pkg-link-btn"><i class="bi bi-x-circle"></i> Cancel</a>
            </div>
        </aside>
    </div>
</form>

<script>
(function () {
    const form = document.getElementById('packageForm');
    if (!form) return;

    function showToast(message, type = 'flash-success') {
        const meta = {
            'flash-success': { icon: 'bi-check-circle-fill', colorClass: 'flash-icon-success' },
            'flash-error': { icon: 'bi-x-circle-fill', colorClass: 'flash-icon-error' },
            'flash-info': { icon: 'bi-info-circle-fill', colorClass: 'flash-icon-info' },
        };
        const toastMeta = meta[type] || meta['flash-info'];
        let stack = document.querySelector('.flash-toast-stack');
        if (!stack) {
            stack = document.createElement('div');
            stack.className = 'flash-toast-stack';
            stack.setAttribute('aria-live', 'polite');
            stack.setAttribute('aria-atomic', 'true');
            document.body.appendChild(stack);
        }

        const toast = document.createElement('div');
        toast.className = type;
        toast.innerHTML = '<i class="bi ' + toastMeta.icon + ' flash-icon ' + toastMeta.colorClass + '"></i><span class="flash-body">' + escapeHtml(message) + '</span><button type="button" class="flash-close" aria-label="Dismiss"><i class="bi bi-x-lg"></i></button>';
        stack.appendChild(toast);

        const dismiss = () => {
            toast.classList.remove('flash-show');
            toast.classList.add('flash-dismiss');
            setTimeout(() => toast.remove(), 250);
        };
        const timer = setTimeout(dismiss, 5000);
        toast.querySelector('.flash-close')?.addEventListener('click', () => {
            clearTimeout(timer);
            dismiss();
        });
        requestAnimationFrame(() => requestAnimationFrame(() => toast.classList.add('flash-show')));
    }
    const money = (value) => Number(value || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
    const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]));

    function syncServices() {
        form.querySelectorAll('[data-service-row]').forEach(row => {
            const enabled = !!row.querySelector('[data-service-toggle]')?.checked;
            const extraOpen = row.classList.contains('is-extra-open');
            row.classList.toggle('is-enabled', enabled);
            row.querySelectorAll('[data-service-extra-field]').forEach(field => {
                field.disabled = !enabled || !extraOpen;
            });
            const extraToggle = row.querySelector('[data-service-extra-toggle]');
            if (extraToggle) extraToggle.setAttribute('aria-expanded', enabled && extraOpen ? 'true' : 'false');
        });
    }

    function syncPackagePanels() {
        form.querySelectorAll('[data-package-panel]').forEach(panel => {
            const expanded = !panel.classList.contains('is-collapsed');
            const toggle = panel.querySelector('[data-panel-toggle]');
            if (toggle) toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        });
    }

    function togglePackagePanel(panel) {
        if (!panel || !panel.classList.contains('is-collapsible')) return;
        panel.classList.toggle('is-collapsed');
        syncPackagePanels();
    }

    function openPackagePanel(panel) {
        if (!panel || !panel.classList.contains('is-collapsible')) return;
        panel.classList.remove('is-collapsed');
        syncPackagePanels();
    }

    function toggleServiceRow(row) {
        const checkbox = row?.querySelector('[data-service-toggle]');
        if (!checkbox) return;
        checkbox.checked = !checkbox.checked;
        syncServices();
    }

    function toggleServiceExtra(row) {
        if (!row || !row.querySelector('[data-service-toggle]')?.checked) return;
        row.classList.toggle('is-extra-open');
        syncServices();
    }

    function syncPromo() {
        const enabled = form.querySelector('[data-promo-toggle]')?.checked;
        const fields = form.querySelector('[data-promo-fields]');
        if (fields) fields.style.display = enabled ? 'grid' : 'none';
    }

    function casketText(option) {
        if (!option || !option.value) return '';
        const name = option.dataset.name || option.textContent.trim();
        const material = option.dataset.material || '';
        return material ? name + ' (' + material + ')' : name;
    }

    function normalizeCasketOptionLabels() {
        form.querySelectorAll('[data-casket-select] option').forEach(option => {
            if (!option.value) return;
            option.textContent = casketText(option);
        });
    }

    function syncCasketSelect(select) {
        if (!select) return;
        const option = select.selectedOptions[0];
        const row = select.closest('[data-service-row]');
        const snapshot = row?.querySelector('[data-casket-snapshot]');
        const summary = row?.querySelector('[data-casket-summary]');
        const selectedText = casketText(option);

        if (snapshot) snapshot.value = selectedText;

        if (!summary) return;
        if (!option || !option.value) {
            summary.innerHTML = '<strong>No casket selected</strong><span>Select a catalog casket for this package.</span>';
            return;
        }

        const price = Number(option.dataset.price || 0);
        const material = option.dataset.material || 'No material specified';
        const valueText = price > 0 ? '&#8369;' + money(price) : '<span class="pkg-casket-price-missing">Ref. not set</span>';
        summary.innerHTML = [
            '<div class="pkg-casket-summary-top"><strong>' + escapeHtml(option.dataset.name || selectedText) + '</strong></div>',
            '<div class="pkg-casket-meta"><span>' + escapeHtml(material) + '</span><span>' + valueText + '</span><span>Included in package price</span></div>'
        ].join('');
        return;
        summary.innerHTML = '<strong>' + escapeHtml(option.dataset.name || selectedText) + '</strong><span>' + escapeHtml(material) + ' · ' + priceText + '</span>';
    }

    function validateRequiredCasket() {
        const casketSelect = form.querySelector('[data-casket-required]');
        const row = casketSelect?.closest('[data-service-row]');
        const casketEnabled = !!row?.querySelector('[data-service-toggle]')?.checked;
        const valid = !casketEnabled || !!casketSelect?.value;
        const error = row?.querySelector('[data-casket-required-error]');

        if (error) error.classList.toggle('hidden', valid);
        casketSelect?.classList.toggle('border-red-500', !valid);

        if (!valid) {
            openPackagePanel(row.closest('[data-package-panel]'));
            casketSelect.focus();
        }

        return valid;
    }

    function filterCasketOptions(input) {
        const row = input.closest('[data-service-row]');
        const select = row?.querySelector('[data-casket-select]');
        if (!select) return;

        const term = input.value.trim().toLowerCase();
        Array.from(select.options).forEach(option => {
            if (!option.value) {
                option.hidden = false;
                return;
            }
            const haystack = [option.dataset.name, option.dataset.material, option.textContent].join(' ').toLowerCase();
            option.hidden = term !== '' && !haystack.includes(term);
        });
    }

    function reindexRows(selector, prefix) {
        form.querySelectorAll(selector).forEach((row, index) => {
            row.querySelectorAll('[name]').forEach(input => {
                input.name = input.name.replace(new RegExp(prefix + '\\[\\d+\\]'), prefix + '[' + index + ']');
            });
        });
    }

    function addCustom() {
        const list = form.querySelector('[data-custom-list]');
        const index = list.querySelectorAll('[data-custom-row]').length;
        list.insertAdjacentHTML('beforeend', '<div class="pkg-row-card pkg-custom-row" data-custom-row><span class="pkg-row-grip"><i class="bi bi-list"></i></span><input type="text" name="custom_inclusions[' + index + '][description]" class="form-input" placeholder="e.g. Memorial folder setup"><button type="button" class="pkg-tool-btn pkg-remove-btn" data-remove-row aria-label="Remove other inclusion"><i class="bi bi-x-lg"></i></button></div>');
        list.querySelector('[data-custom-row]:last-child input')?.focus();
    }

    function syncFreebieCard(card) {
        if (!card) return;
        const checked = card.querySelector('[data-freebie-check]')?.checked ?? false;
        card.classList.toggle('is-selected', checked);
        card.querySelectorAll('[data-freebie-field]').forEach(field => {
            field.disabled = !checked;
        });
        syncFreebieCount();
    }

    function syncFreebieCount() {
        const target = form.querySelector('[data-freebie-count]');
        if (!target) return;
        const configuredCount = form.querySelectorAll('[data-freebie-card].is-selected').length;
        const customCount = form.querySelectorAll('[data-custom-freebie-row]').length;
        const count = configuredCount + customCount;
        target.textContent = count + ' selected';
    }

    function setAllConfiguredFreebies(checked) {
        form.querySelectorAll('[data-freebie-card]').forEach(card => {
            const checkbox = card.querySelector('[data-freebie-check]');
            if (checkbox) checkbox.checked = checked;
            syncFreebieCard(card);
        });
    }

    function toggleFreebieCard(card) {
        const checkbox = card?.querySelector('[data-freebie-check]');
        if (!checkbox) return;
        checkbox.checked = !checkbox.checked;
        syncFreebieCard(card);
    }

    function reviewLines(items, icon, emptyText) {
        if (!items.length) {
            return '<span class="pkg-review-empty">' + escapeHtml(emptyText) + '</span>';
        }

        return '<ul class="pkg-review-lines">' + items.map(item => (
            '<li><i class="bi ' + icon + '"></i><span>' + escapeHtml(item) + '</span></li>'
        )).join('') + '</ul>';
    }

    function review() {
        const list = form.querySelector('[data-review-list]');
        if (!list) return;

        const name = form.querySelector('[name="name"]')?.value || 'Unnamed package';
        const price = form.querySelector('[name="price"]')?.value || 0;
        const services = Array.from(form.querySelectorAll('[data-service-toggle]:checked')).map(input => {
            const row = input.closest('[data-service-row]');
            const label = row?.querySelector('.pkg-service-label')?.textContent.trim() || '';
            const casketSelect = row?.querySelector('[data-casket-select]');
            const casket = casketText(casketSelect?.selectedOptions[0]) || row?.querySelector('[name$="[casket_type]"]')?.value.trim();
            return casket ? label + ': ' + casket : label;
        }).filter(Boolean);
        const custom = Array.from(form.querySelectorAll('[data-custom-row] [name$="[description]"]')).map(input => input.value.trim()).filter(Boolean);
        const configuredFreebies = Array.from(form.querySelectorAll('[data-freebie-card].is-selected')).map(row => {
            const qty = row.querySelector('[name$="[quantity]"]')?.value || 1;
            const unit = row.querySelector('[name$="[unit]"]')?.value || 'item';
            const freebie = row.querySelector('[name$="[freebie_name]"]')?.value || row.querySelector('.pkg-freebie-choice strong')?.textContent || '';
            return freebie.trim() ? qty + ' ' + unit + ' ' + freebie.trim() : '';
        }).filter(Boolean);
        const customFreebies = Array.from(form.querySelectorAll('[data-custom-freebie-row]')).map(row => {
            const qty = row.querySelector('[name$="[quantity]"]')?.value || 1;
            const unit = row.querySelector('[name$="[unit]"]')?.value || 'item';
            const freebie = row.querySelector('[name$="[freebie_name]"]')?.value || '';
            return freebie.trim() ? qty + ' ' + unit + ' ' + freebie.trim() : '';
        }).filter(Boolean);
        const freebies = [...configuredFreebies, ...customFreebies];
        const promo = form.querySelector('[data-promo-toggle]')?.checked
            ? (form.querySelector('[name="promo_label"]')?.value || 'Promo enabled')
            : 'Inactive';
        const includedItems = [...services, ...custom];

        list.innerHTML = [
            '<div class="pkg-review-item"><span class="pkg-review-kicker">Package</span><span class="pkg-review-value">' + escapeHtml(name) + '<br>&#8369;' + money(price) + '</span></div>',
            '<div class="pkg-review-item"><span class="pkg-review-kicker">Included</span>' + reviewLines(includedItems, 'bi-check2-circle', 'No services selected yet') + '</div>',
            '<div class="pkg-review-item"><span class="pkg-review-kicker">Freebies</span>' + reviewLines(freebies, 'bi-gift', 'No freebies') + '</div>',
            '<div class="pkg-review-item"><span class="pkg-review-kicker">Promo</span><span class="pkg-review-value">' + escapeHtml(promo) + '</span></div>',
            '<div class="pkg-review-item"><span class="pkg-badge pkg-badge-charge">Additional Charge</span><span class="pkg-review-value">Optional add-ons are handled outside this package form. The selected casket is part of this package.</span></div>'
        ].join('');
    }

    form.addEventListener('change', event => {
        if (event.target.matches('[data-service-toggle]')) {
            syncServices();
            validateRequiredCasket();
        }
        if (event.target.matches('[data-promo-toggle]')) syncPromo();
        if (event.target.matches('[data-freebie-check]')) syncFreebieCard(event.target.closest('[data-freebie-card]'));
        if (event.target.matches('[data-casket-select]')) {
            syncCasketSelect(event.target);
            validateRequiredCasket();
        }
        review();
    });
    form.addEventListener('input', event => {
        if (event.target.matches('[data-casket-search]')) filterCasketOptions(event.target);
        review();
    });
    form.addEventListener('submit', event => {
        if (!validateRequiredCasket()) {
            event.preventDefault();
            return;
        }
        const submit = form.querySelector('[data-package-submit]');
        const label = form.querySelector('[data-package-submit-label]');
        if (submit) {
            submit.disabled = true;
            submit.classList.add('opacity-70');
        }
        if (label) label.textContent = 'Saving...';
    });
    form.addEventListener('click', event => {
        const panelToggle = event.target.closest('[data-panel-toggle]');
        if (panelToggle) {
            event.preventDefault();
            togglePackagePanel(panelToggle.closest('[data-package-panel]'));
            return;
        }

        const panelHead = event.target.closest('[data-panel-head]');
        const panel = panelHead?.closest('[data-package-panel]');
        if (panel?.classList.contains('is-collapsible') && !event.target.closest('button, a, input, textarea, select, label')) {
            event.preventDefault();
            togglePackagePanel(panel);
            return;
        }

        const freebieCard = event.target.closest('[data-freebie-card]');
        if (freebieCard && !event.target.closest('input, textarea, select, button')) {
            event.preventDefault();
            toggleFreebieCard(freebieCard);
            review();
            return;
        }

        const serviceCard = event.target.closest('[data-service-row]');
        if (serviceCard && !event.target.closest('input, textarea, select, button, label')) {
            event.preventDefault();
            toggleServiceRow(serviceCard);
            review();
            return;
        }

        const serviceExtraTrigger = event.target.closest('[data-service-extra-toggle]');
        if (serviceExtraTrigger) {
            toggleServiceExtra(serviceExtraTrigger.closest('[data-service-row]'));
            review();
            return;
        }

        if (event.target.closest('[data-add-custom]')) { addCustom(); review(); }
        if (event.target.closest('[data-clear-custom]')) {
            form.querySelector('[data-custom-list]')?.replaceChildren();
            review();
        }
        if (event.target.closest('[data-select-all-freebies]')) {
            setAllConfiguredFreebies(true);
            review();
        }
        if (event.target.closest('[data-unselect-all-freebies]')) {
            setAllConfiguredFreebies(false);
            review();
        }
        if (event.target.closest('[data-remove-row]')) {
            const row = event.target.closest('[data-custom-row], [data-custom-freebie-row]');
            row?.remove();
            reindexRows('[data-custom-row]', 'custom_inclusions');
            syncFreebieCount();
            review();
        }
    });

    form.querySelectorAll('[data-freebie-card]').forEach(card => {
        card.addEventListener('keydown', event => {
            if (event.target.closest('input, textarea, select, button')) return;
            if (event.key !== 'Enter' && event.key !== ' ') return;
            event.preventDefault();
            toggleFreebieCard(card);
            review();
        });
    });
    form.querySelectorAll('[data-service-row]').forEach(row => {
        row.addEventListener('keydown', event => {
            if (event.target.closest('input, textarea, select, button, label')) return;
            if (event.key !== 'Enter' && event.key !== ' ') return;
            event.preventDefault();
            toggleServiceRow(row);
            review();
        });
    });
    form.querySelectorAll('[data-package-panel]').forEach(panel => {
        const head = panel.querySelector('[data-panel-head]');
        head?.addEventListener('keydown', event => {
            if (event.target.closest('button, a, input, textarea, select, label')) return;
            if (event.key !== 'Enter' && event.key !== ' ') return;
            event.preventDefault();
            togglePackagePanel(panel);
        });
    });

    normalizeCasketOptionLabels();
    syncServices();
    syncPackagePanels();
    syncPromo();
    form.querySelectorAll('[data-casket-select]').forEach(syncCasketSelect);
    form.querySelectorAll('[data-freebie-card]').forEach(syncFreebieCard);
    syncFreebieCount();
    review();
})();
</script>

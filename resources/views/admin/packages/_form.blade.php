@php
    $isEdit = isset($package);
    $assignedPromotion = $isEdit ? $package->promotion : null;
    $action = $isEdit ? route('admin.packages.update', $package) : route('admin.packages.store');
    $buttonText = $isEdit ? 'Save Changes' : 'Save Package';
    $returnTo = \App\Support\LocalReturnUrl::resolve(
        old('return_to', request('return_to')),
        route('admin.service-management.index', ['tab' => 'packages']),
    );
    $serviceLabels = $serviceTypeOptions ?? \App\Models\Package::serviceTypeOptions();
    $serviceLabels = array_replace($serviceLabels, [
        \App\Models\Package::SERVICE_EMBALMING => 'Embalming & Body Preparation',
        \App\Models\Package::SERVICE_CASKET => 'Included Casket',
        \App\Models\Package::SERVICE_HOME_VIEWING => 'Home / Chapel Viewing',
        \App\Models\Package::SERVICE_HEARSE => 'Included Hearse',
    ]);
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
                if (! $inclusion->add_on_catalog_id) {
                    $existingCustom[] = ['description' => $inclusion->inclusion_name];
                }
                continue;
            }
            $existingServices[$type] = [
                'enabled' => 1,
                'casket_catalog_id' => $inclusion->casket_catalog_id,
                'hearse_catalog_id' => $inclusion->hearse_catalog_id,
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
    $includedAddOnQuantities = old('configure_included_add_ons')
        ? old('included_add_on_quantities', [])
        : ($isEdit ? $package->packageInclusions->whereNotNull('add_on_catalog_id')->pluck('included_quantity', 'add_on_catalog_id')->all() : []);
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
    $existingFreebies = collect($existingFreebies)->map(fn ($row, $index) => array_merge($row, ['_error_index' => $index]))->all();
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
    .pkg-promotion-assignment-summary { display:grid; grid-template-columns:repeat(auto-fit,minmax(130px,1fr)); gap:12px; margin:12px 0; padding:13px 14px; border:1px solid var(--border); border-radius:9px; background:var(--surface-muted); }
    .pkg-promotion-assignment-summary dt { color:var(--ink-muted); font-size:.76rem; }
    .pkg-promotion-assignment-summary dd { margin:3px 0 0; color:var(--ink); font-size:.88rem; font-weight:650; overflow-wrap:anywhere; }
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
    .pkg-modal-backdrop { position:fixed; inset:0; z-index:70; display:none; align-items:center; justify-content:center; background:var(--modal-scrim); padding:18px; }
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
    .pkg-freebie-choice > span:last-child > span { display:block; margin-top:3px; color:#687466; font-size:.76rem; line-height:1.35; font-weight:700; }
    .pkg-freebie-config-grid { display:grid; grid-template-columns:96px minmax(120px,1fr); gap:12px; padding-left:33px; }
    .pkg-freebie-card:not(.is-selected) .pkg-freebie-config { opacity:.52; }
    .pkg-included-addon-config {
        display: grid;
        grid-column: 1 / -1;
        grid-template-columns: minmax(0, 1fr);
        align-items: start;
        gap: 12px;
        width: 100%;
        padding-top: 12px;
        border-top: 1px solid var(--pkg-line, var(--border));
    }
    .pkg-included-addon-config[hidden] { display: none; }
    .pkg-included-addon-config .form-input { width: 100%; min-width: 0; }
    .pkg-included-addon-config .pkg-row-label { color: var(--pkg-muted, var(--ink-muted)) !important; }
    .pkg-included-addon-summary { display:flex;align-items:center;justify-content:space-between;gap:10px;width:100%;font-size:13px; }
    .pkg-included-addon-summary[hidden] { display:none; }
    .pkg-included-addon-done { justify-self:end; }
    [data-included-addon-card].is-selected,
    [data-included-addon-card] .pkg-freebie-choice input:checked + .pkg-freebie-mark { box-shadow: none; }
    .pkg-freebie-legacy { margin-top:14px; display:grid; gap:10px; }
    .pkg-freebie-legacy-title { color:#566653; font-size:.73rem; font-weight:850; text-transform:uppercase; letter-spacing:.06em; }
    .pkg-freebie-actions { display:inline-flex; align-items:center; gap:0; border:1px solid #C9C5BB; border-radius:8px; background:#FFFFFF; overflow:hidden; }
    .pkg-freebie-action { min-height:36px; display:inline-flex; align-items:center; justify-content:center; gap:6px; border:0; border-right:1px solid #D8D2C7; background:transparent; color:#3E4A3D; cursor:pointer; padding:0 10px; font-size:.74rem; line-height:1.2; font-weight:850; transition:background .15s ease, color .15s ease; }
    .pkg-freebie-action:last-child { border-right:0; }
    .pkg-freebie-action:hover { background:#F3F0E8; color:#2F392E; }
    .pkg-row-field { display:grid; gap:5px; min-width:0; }
    .pkg-row-label { font-size:.7rem; line-height:1.25; font-weight:950; text-transform:uppercase; letter-spacing:.065em; color:#5F6B5C; }
    .pkg-row-grip { width:36px; height:44px; border-radius:7px; display:inline-flex; align-items:center; justify-content:center; background:#F0EEE8; color:#6B7466; align-self:end; }
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
        .pkg-grid-2, .pkg-freebie-row, .pkg-freebie-card, .pkg-freebie-config-grid { grid-template-columns:1fr; }
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

@include('admin.packages.partials.page-design')

<form id="packageForm" method="POST" enctype="multipart/form-data" action="{{ $action }}" data-package-page class="pkg-workspace {{ $isEdit ? 'is-edit-mode' : '' }} w-full mx-auto px-4 sm:px-6 lg:px-8 grid gap-5">
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
            <span class="pkg-hero-icon" aria-hidden="true"><i class="bi bi-box-seam"></i></span>
            <h2 class="pkg-title">{{ $isEdit ? 'Edit Funeral Package' : 'Create Funeral Package' }}</h2>
            <p class="pkg-subtitle">Configure bundled memorial services, catalog inclusions, and complimentary freebies.</p>
        </div>
        <div class="pkg-page-actions"><a href="{{ $returnTo }}" class="btn btn-outline" data-service-form-cancel>Cancel</a><button type="submit" class="btn btn-primary-custom" data-package-submit><span data-package-submit-label>{{ $buttonText }}</span></button></div>
    </header>


    @if(isset($package) && ($dependencyWarnings = app(\App\Support\ServiceCatalogPolicy::class)->packageBlockers($package, ignoreArchived: true)) !== [])
    <div class="pkg-configuration-errors" role="status"><ul>
        @foreach($dependencyWarnings as $warning)
            @php
                $shortWarning = str_replace([
                    'No hearse selected. Edit this package to choose one.',
                    'Choose an available casket or replace it during intake.',
                    'Choose an active casket in package settings.',
                    'Edit this package and choose an available hearse.',
                    'Edit this package and choose an active hearse.',
                    'Edit this package to replace or remove it.',
                ], [
                    'No hearse selected. Choose one.',
                    'Choose another casket.',
                    'Choose another casket.',
                    'Choose another hearse.',
                    'Choose another hearse.',
                    'Replace or remove it.',
                ], $warning);
                $shortWarning = str_replace(['Included casket ', 'Included hearse ', 'Included freebie '], ['Casket ', 'Hearse ', 'Freebie '], $shortWarning);
            @endphp
            <li>{{ $shortWarning }}</li>
        @endforeach
    </ul></div>
    @endif
    @if($errors->any())
        <div class="pkg-configuration-errors" role="alert">
            <strong>Please correct the following configuration errors:</strong>
            <ul>@foreach($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul>
        </div>
    @endif

    <nav class="package-wizard-nav" aria-label="Package setup steps" hidden>
        @foreach(['Details', 'Services', 'Inclusions', 'Freebies', 'Promo', 'Review'] as $stepLabel)<button type="button" data-wizard-go="{{ $loop->iteration }}"><span>{{ $loop->iteration }}</span>{{ $stepLabel }}</button>@endforeach
    </nav>
    <div class="pkg-layout">
        <div class="pkg-main">
            <section class="pkg-panel " data-package-panel>
                <div class="pkg-panel-head" data-panel-head>
                    <div>
                        <div class="pkg-panel-title"><span class="pkg-icon"><i class="bi bi-card-heading"></i></span> Basic Details</div>
                        <p class="pkg-panel-copy">Define package name, base commercial price, and customer-facing service summary.</p>
                    </div>
                </div>
                <div class="pkg-panel-body">
                    <div class="pkg-grid pkg-grid-2">
                        <div>
                            <label class="pkg-label" for="package-name">Package Name <span class="pkg-required">*</span></label>
                            <input id="package-name" type="text" name="name" value="{{ old('name', $package->name ?? '') }}" class="form-input" placeholder="e.g. San Isidro Classic Memorial Package" required maxlength="150" data-text-case="words" autocapitalize="words" autocorrect="off" spellcheck="false">
                            <p class="pkg-inline-help mt-2">Display title shown across package catalogs and case intake wizard.</p>
                            @error('name') <div class="form-error">{{ $message }}</div> @enderror
                        </div>
                        <div>
                            <label class="pkg-label" for="package-price">Base Package Price (PHP) <span class="pkg-required">*</span></label>
                            <div class="pkg-money">
                                <span>&#8369;</span>
                                <input id="package-price" max="9999999999.99" type="number" step="0.01" min="0" name="price" value="{{ old('price', $package->price ?? '') }}" class="form-input" placeholder="0.00" required>
                            </div>
                            <p class="pkg-inline-help mt-2">Baseline bundled commercial amount.</p>
                            @error('price') <div class="form-error">{{ $message }}</div> @enderror
                        </div>
                        <div class="md:col-span-2">
                            <label class="pkg-label" for="package-description">Package Description <span class="normal-case tracking-normal font-bold">(optional)</span></label>
                            <textarea id="package-description" name="short_description" rows="2" class="form-input" maxlength="500" placeholder="e.g. Dignified memorial service with a solid wood casket and ceremonial support." data-text-case="sentences" autocapitalize="sentences" autocorrect="on" spellcheck="true">{{ old('short_description', $package->short_description ?? '') }}</textarea>
                            @error('short_description') <div class="form-error">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    @include('admin.service-management.partials.photo-field', ['catalog' => $package ?? new \App\Models\Package(), 'photoCaption' => 'This photo appears on the package card and in its printed brochure. Changes apply when you save.'])
                </div>
            </section>

            <section class="pkg-panel " data-package-panel>
                <div class="pkg-panel-head" data-panel-head>
                    <div>
                        <div class="pkg-panel-title"><span class="pkg-icon"><i class="bi bi-check2-square"></i></span> Included Services</div>
                        <p class="pkg-panel-copy">Enable supported services intentionally and configure distance allowances, duration caps, and catalog selections.</p>
                    </div>
                    <div class="pkg-panel-head-actions">
                        @if($basicDefaults = \App\Models\CustomPackageDefault::find(1))
                            <button type="button" class="pkg-link-btn" data-apply-basic-defaults>Apply Basic Defaults</button>
                        @endif
                        <span class="pkg-badge pkg-badge-included" data-service-count>{{ $enabledServiceCount }} service{{ $enabledServiceCount === 1 ? '' : 's' }}</span>
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
                            <div class="pkg-service-card {{ !empty($row['enabled']) ? 'is-enabled' : '' }}" data-service-row>
                                <div class="pkg-service-top">
                                    <div class="pkg-service-name">
                                        <span class="pkg-service-icon"><i class="bi {{ $serviceIcons[$type] ?? 'bi-check2' }}"></i></span>
                                        <span class="pkg-service-copy">
                                            <span class="pkg-service-label">{{ $loop->iteration }}. {{ $serviceLabels[$type] }}</span>
                                            <span class="pkg-service-note">@if($type === \App\Models\Package::SERVICE_BODY_RETRIEVAL)Hospital or residential pick-up and initial conveyance to preparation facility.@elseif($type === \App\Models\Package::SERVICE_EMBALMING)Hygienic preservation, dressing, cosmetic restoration, and casket placement.@elseif($type === \App\Models\Package::SERVICE_CASKET)Primary casket option bundled into package base price.@elseif($type === \App\Models\Package::SERVICE_HOME_VIEWING)Viewing setup at home or in a chapel, including curtains, candle stands, and viewing equipment.@else Ceremonial funeral coach coverage included in this package.@endif
                                            </span>
                                        </span>
                                    </div>
                                    <div class="pkg-service-controls"><span class="pkg-service-state" data-service-status>{{ !empty($row['enabled']) ? 'Included' : 'Not included' }}</span><label class="pkg-switch"><input type="hidden" name="included_services[{{ $type }}][enabled]" value="0"><input id="package-service-{{ $type }}" type="checkbox" name="included_services[{{ $type }}][enabled]" value="1" role="switch" data-service-toggle aria-label="Include {{ $serviceLabels[$type] }}" {{ !empty($row['enabled']) ? 'checked' : '' }}><span class="pkg-slider"></span></label><button type="button" class="pkg-service-disclosure" data-service-disclosure aria-expanded="{{ !empty($row['enabled']) ? 'true' : 'false' }}" aria-controls="package-service-fields-{{ $type }}" ><i class="bi bi-chevron-down" aria-hidden="true"></i><span data-service-disclosure-label>{{ $isKmService || $isDayService ? 'Limits & rates' : 'Casket details' }}</span></button></div>
                                </div>
                                @if($isKmService || $isDayService)
                                    <div class="pkg-service-fields" id="package-service-fields-{{ $type }}">
                                        <div class="pkg-service-extra" data-service-extra>
                                            @if($isKmService)
                                                <div class="pkg-grid pkg-grid-2">
                                                    <div>
                                                        <label class="pkg-label pkg-limit-label" for="service-{{ $type }}-included_kilometers">Included Distance (km) <span class="pkg-required">*</span></label>
                                                        <div class="pkg-unit-input"><input type="number" min="0" max="999999" step="0.01" id="service-{{ $type }}-included_kilometers" name="included_services[{{ $type }}][included_kilometers]" value="{{ $row['included_kilometers'] ?? '' }}" class="form-input" placeholder="0" data-service-extra-field data-service-limit required><span aria-hidden="true">km</span></div><p class="pkg-field-help">{{ $type === \App\Models\Package::SERVICE_HEARSE ? 'Cortège route allowance covered by package base price.' : 'Standard allowance included in package base price.' }}</p>
                                                        <x-validation-error :field="'included_services.'.$type.'.included_kilometers'" />
                                                    </div>
                                                    <div>
                                                        <label class="pkg-label pkg-excess-label" for="service-{{ $type }}-price_per_excess_kilometer">Excess Rate per Additional Kilometer (PHP) <span class="pkg-required">*</span></label>
                                                        <div class="pkg-money"><span>&#8369;</span><input type="number" step="0.01" min="0" max="9999999999.99" id="service-{{ $type }}-price_per_excess_kilometer" name="included_services[{{ $type }}][price_per_excess_kilometer]" value="{{ $row['price_per_excess_kilometer'] ?? '' }}" class="form-input" placeholder="0.00" data-service-extra-field required></div><p class="pkg-field-help">Enter 0 for no extra distance charge.</p>
                                                        <x-validation-error :field="'included_services.'.$type.'.price_per_excess_kilometer'" />
                                                    </div>
                                                </div>
                                                @if($type === \App\Models\Package::SERVICE_HEARSE)
                                                    <label class="pkg-label" for="package-hearse">Included Hearse <span class="pkg-required">*</span></label>
                                                    <select id="package-hearse" name="included_services[hearse][hearse_catalog_id]" class="form-select" data-service-extra-field required>
                                                        <option value="">Choose an available hearse</option>
                                                        @foreach($hearseCatalogs as $hearse)<option value="{{ $hearse->id }}" @selected((int)($row['hearse_catalog_id'] ?? 0) === $hearse->id)>{{ $hearse->name }}{{ $hearse->reference_value !== null ? ' - PHP ' . number_format((float) $hearse->reference_value, 2) : ' - Value not configured' }}</option>@endforeach
                                                    </select>
                                                    <p class="pkg-field-help">Included in the package base price. Regular intake can select an available upgrade and charge only the positive reference-value difference. If unavailable, update this package before new intake.</p>
                                                    @error('included_services.hearse.hearse_catalog_id')<p class="form-error">{{ $message }}</p>@enderror
                                                @endif

                                            @else
                                                <div class="pkg-grid pkg-grid-2">
                                                    <div>
                                                        <label class="pkg-label pkg-limit-label" for="service-{{ $type }}-included_days">{{ $type === \App\Models\Package::SERVICE_HOME_VIEWING ? 'Included Viewing Days' : 'Included Duration (days)' }} <span class="pkg-required">*</span></label>
                                                        <div class="pkg-unit-input"><input type="number" min="1" max="365" id="service-{{ $type }}-included_days" name="included_services[{{ $type }}][included_days]" value="{{ $row['included_days'] ?? '' }}" class="form-input" placeholder="0" data-service-extra-field data-service-limit required><span aria-hidden="true">days</span></div><p class="pkg-field-help">{{ $type === \App\Models\Package::SERVICE_EMBALMING ? 'Preservation duration covered by baseline fee.' : 'Number of viewing days covered by the package price.' }}</p>
                                                        <x-validation-error :field="'included_services.'.$type.'.included_days'" />
                                                    </div>
                                                    <div>
                                                        <label class="pkg-label pkg-excess-label" for="service-{{ $type }}-price_per_extended_day">{{ $type === \App\Models\Package::SERVICE_EMBALMING ? 'Additional-Day Preservation Rate (PHP)' : 'Extra Viewing Day Rate (PHP)' }} <span class="pkg-required">*</span></label>
                                                        <div class="pkg-money"><span>&#8369;</span><input type="number" step="0.01" min="0" max="9999999999.99" id="service-{{ $type }}-price_per_extended_day" name="included_services[{{ $type }}][price_per_extended_day]" value="{{ $row['price_per_extended_day'] ?? '' }}" class="form-input" placeholder="0.00" data-service-extra-field required></div><p class="pkg-field-help">Enter 0 for no extra-day charge.</p>
                                                        <x-validation-error :field="'included_services.'.$type.'.price_per_extended_day'" />
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @else
                                    <div class="pkg-service-fields" id="package-service-fields-{{ $type }}">
                                        <div class="flex flex-wrap items-end justify-between gap-3">
                                            <div class="min-w-[220px] flex-1">
                                                <label class="pkg-label" for="package-casket">Select Included Casket <span class="pkg-required">*</span></label>
                                                <details class="pkg-casket-search-tools"><summary>Search catalog</summary><input type="search" aria-label="Search casket catalog" class="form-input mb-2" data-casket-search placeholder="Search casket catalog"></details>
                                                <select id="package-casket" name="included_services[{{ $type }}][casket_catalog_id]" class="form-select" data-casket-select data-casket-required>
                                                    <option value="">— Choose eligible casket from catalog —</option>
                                                    @foreach($casketCatalogs as $catalog)
                                                        <option
                                                            value="{{ $catalog->id }}"
                                                            data-name="{{ $catalog->name }}"
                                                            data-material="{{ $catalog->type_or_material }}"
                                                            data-price="{{ (float) $catalog->standard_price }}"
                                                            data-description="{{ $catalog->description }}"
                                                            data-available="{{ $catalog->is_active && $catalog->is_available ? '1' : '0' }}"
                                                            data-update-url="{{ route('admin.casket-catalogs.update', $catalog, absolute: false) }}"
                                                            @selected((int)($row['casket_catalog_id'] ?? 0) === $catalog->id)
                                                            @disabled(!$catalog->is_available || !$catalog->is_active)
                                                        >
                                                            {{ $catalog->display_name }}{{ $catalog->is_available ? '' : ' — Unavailable' }}
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
                                        <p class="pkg-service-help">The included casket is covered by the package price. Its reference value is used for applicable replacement or upgrade calculations and is not added separately to the base price.</p>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    @foreach($existingCustom as $i => $custom)
                        <input type="hidden" name="custom_inclusions[{{ $i }}][description]" value="{{ $custom['description'] ?? '' }}">
                        <x-validation-error :field="'custom_inclusions.'.$i.'.description'" />
                    @endforeach
                    @error('included_services') <div class="form-error">{{ $message }}</div> @enderror
                    <div class="mt-5 border-t border-current/10 pt-4">
                        <h3 class="pkg-panel-title">Other Inclusions</h3>
                        <p class="pkg-panel-copy">Select the add-ons covered by this package and set how much is included. Billing units and extra rates come from the Add-ons catalog.</p>
                        <input type="hidden" name="configure_included_add_ons" value="1">
                        <div class="mt-3 grid max-h-72 items-start gap-3 overflow-y-auto overscroll-contain sm:grid-cols-2">
                            @forelse(($includedAddOnCatalogs ?? collect()) as $catalog)
                                @php
                                    $quantity = $includedAddOnQuantities[$catalog->id] ?? null;
                                    $selected = $quantity !== null && $quantity !== '';
                                    $unit = $catalog->unit ?: 'item';
                                    $includedUnitLabel = match ($unit) {
                                        'day' => 'Included Days',
                                        'set' => 'Included Sets',
                                        'trip' => 'Included Trips',
                                        'service' => 'Included Services',
                                        default => 'Included Items',
                                    };
                                @endphp
                                <div class="pkg-freebie-card {{ $selected ? 'is-selected' : '' }}" data-included-addon-card>
                                    <label class="pkg-freebie-choice">
                                        <input type="checkbox" aria-label="Include {{ $catalog->name }}" aria-controls="included-addon-config-{{ $catalog->id }}" data-included-addon-check @checked($selected)>
                                        <span class="pkg-freebie-mark" aria-hidden="true"><i class="bi bi-check2"></i></span>
                                        <span>
                                            <strong>{{ $catalog->name }}@if($catalog->category === \App\Models\AddOnCatalog::CHAPEL_VIEWING) · {{ $catalog->branch?->branch_name ?: 'Needs Setup' }}@endif</strong>
                                            <span>{{ $catalog->category }} · {{ (float) $catalog->price > 0 ? '₱'.number_format((float) $catalog->price, 2).' per extra '.$unit : ($catalog->category === \App\Models\AddOnCatalog::CHAPEL_VIEWING ? 'No extra charge' : 'Extra rate unavailable') }}{{ $catalog->is_active && $catalog->is_available ? '' : ' · Unavailable' }}</span>
                                        </span>
                                    </label>
                                    <div class="pkg-included-addon-summary" data-included-addon-summary hidden>
                                        <span data-included-addon-coverage></span>
                                        <button type="button" class="service-field-adjust" data-included-addon-adjust aria-controls="included-addon-config-{{ $catalog->id }}">Adjust</button>
                                    </div>
                                    <div class="pkg-included-addon-config" id="included-addon-config-{{ $catalog->id }}" @if(!$selected) hidden @endif>
                                        <div class="pkg-row-field">
                                            <label class="pkg-row-label" for="included-addon-{{ $catalog->id }}">{{ $includedUnitLabel }}</label>
                                            <div class="pkg-unit-input">
                                                <input id="included-addon-{{ $catalog->id }}" class="form-input" type="number" min="1" max="999" step="1"
                                                    name="included_add_on_quantities[{{ $catalog->id }}]" data-included-add-on-name="{{ $catalog->name }}" data-included-add-on-unit="{{ $unit }}"
                                                    value="{{ $quantity ?? 1 }}" @disabled(!$selected) @required($selected)>
                                                <span aria-hidden="true">{{ $unit }}</span>
                                            </div>
                                            <x-validation-error :field="'included_add_on_quantities.'.$catalog->id" />
                                        </div>
                                        <button type="button" class="service-field-chip service-popover-done pkg-included-addon-done" data-included-addon-done>Done</button>
                                    </div>
                                </div>
                            @empty
                                <p class="pkg-panel-copy">No add-ons are available in the catalog.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </section>

            <section class="pkg-panel " data-package-panel>
                <div class="pkg-panel-head" data-panel-head>
                    <div>
                        <div class="pkg-panel-title"><span class="pkg-icon"><i class="bi bi-gift"></i></span> Included Freebies</div>
                        <p class="pkg-panel-copy">Select included items, then set their quantity and unit.</p>
                    </div>
                    <div class="pkg-panel-head-actions">
                        <span class="pkg-badge pkg-badge-free" data-freebie-count>{{ $selectedFreebieCount }} selected</span>

                        @if($isEdit)
                            <button type="button" class="pkg-panel-toggle" data-panel-toggle aria-expanded="false" aria-label="Expand Included Freebies">
                                <i class="bi bi-chevron-down"></i>
                            </button>
                        @endif
                    </div>
                </div>
                <div class="pkg-panel-body">
                    @if($freebieCatalogs->isNotEmpty())
                        <div class="pkg-freebie-actions" aria-label="Freebie selection actions"><button type="button" class="pkg-freebie-action" data-select-all-freebies>Select all</button><button type="button" class="pkg-freebie-action" data-unselect-all-freebies>Unselect all</button></div>
                        <div class="pkg-freebie-checklist" data-freebie-list>
                            @foreach($freebieCatalogs as $i => $catalog)
                                @php
                                    $selectedFreebie = $selectedFreebiesByCatalog->get((int) $catalog->id);
                                    $isSelectedFreebie = (bool) $selectedFreebie;
                                    $freebieUnit = $selectedFreebie['unit'] ?? $catalog->default_unit ?? 'item';
                                @endphp
                                <div class="pkg-freebie-card {{ $isSelectedFreebie ? 'is-selected' : '' }}" data-freebie-card data-catalog-active="{{ $catalog->is_active ? '1' : '0' }}" role="button" tabindex="0">
                                    <div class="pkg-freebie-choice">
                                        <input type="checkbox" aria-label="Include {{ $catalog->name }}" data-freebie-check {{ $isSelectedFreebie ? 'checked' : '' }}>
                                        <span class="pkg-freebie-mark" aria-hidden="true"><i class="bi bi-check2"></i></span>
                                        <span>
                                            <strong>{{ $catalog->name }}</strong>
                                            <span>Unit: {{ $catalog->default_unit ?: 'item' }}</span>
                                        </span>
                                    </div>
                                    <x-validation-error :field="'freebies.'.($selectedFreebie['_error_index'] ?? $i).'.freebie_catalog_id'" />
                                    <x-validation-error :field="'freebies.'.($selectedFreebie['_error_index'] ?? $i).'.freebie_name'" />
                                    <input type="hidden" name="freebies[{{ $i }}][freebie_catalog_id]" value="{{ $catalog->id }}" data-freebie-field @disabled(! $isSelectedFreebie)>
                                    <input type="hidden" name="freebies[{{ $i }}][freebie_name]" value="{{ $catalog->name }}" data-freebie-field @disabled(! $isSelectedFreebie)>
                                    <div class="pkg-freebie-config-grid">
                                        <div class="pkg-row-field pkg-freebie-config">
                                            <label class="pkg-row-label" for="freebie-{{ $i }}-quantity">Qty</label>
                                            <input id="freebie-{{ $i }}-quantity" type="number" min="1" max="999" step="1" name="freebies[{{ $i }}][quantity]" value="{{ $selectedFreebie['quantity'] ?? 1 }}" class="form-input" placeholder="1" data-freebie-field @disabled(! $isSelectedFreebie)>
                                            <x-validation-error :field="'freebies.'.($selectedFreebie['_error_index'] ?? $i).'.quantity'" />
                                        </div>
                                        <div class="pkg-row-field pkg-freebie-config pkg-unit-options">
                                            <label class="pkg-row-label" for="freebie-{{ $i }}-unit">Unit</label>
                                            <input id="freebie-{{ $i }}-unit" type="text" name="freebies[{{ $i }}][unit]" value="{{ $freebieUnit }}" class="form-input" placeholder="item" data-freebie-field @disabled(! $isSelectedFreebie) maxlength="40">
                                            <x-validation-error :field="'freebies.'.($selectedFreebie['_error_index'] ?? $i).'.unit'" />
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="pkg-freebies-empty"><i class="bi bi-gift" aria-hidden="true"></i><div><p>No freebies added yet.</p><span>You can add complimentary items in Service Management whenever needed.</span></div></div>
                    @endif

                    @if($customFreebies->isNotEmpty())
                        <div class="pkg-freebie-legacy">
                            <div class="pkg-freebie-legacy-title">Existing Custom Freebies</div>
                            <div class="pkg-row-list" data-custom-freebie-list>
                                @foreach($customFreebies as $customIndex => $freebie)
                                    @php $rowIndex = $freebieCatalogs->count() + $customIndex; @endphp
                                    <div class="pkg-row-card pkg-freebie-row" data-custom-freebie-row>
                                        @if(!empty($freebie['freebie_catalog_id']))
                                            <input type="hidden" name="freebies[{{ $rowIndex }}][freebie_catalog_id]" value="{{ $freebie['freebie_catalog_id'] }}">
                                        @endif
                                        <x-validation-error :field="'freebies.'.($freebie['_error_index'] ?? $rowIndex).'.freebie_catalog_id'" />
                                        <span class="pkg-row-grip"><i class="bi bi-gift"></i></span>
                                        <div class="pkg-row-field pkg-freebie-name">
                                            <span class="pkg-row-label">Name</span>
                                            <input type="text" name="freebies[{{ $rowIndex }}][freebie_name]" value="{{ $freebie['freebie_name'] ?? '' }}" class="form-input" placeholder="e.g. Prayer cards" maxlength="255" data-text-case="words" autocapitalize="words" autocorrect="off" spellcheck="false">
                                            <x-validation-error :field="'freebies.'.($freebie['_error_index'] ?? $rowIndex).'.freebie_name'" />
                                        </div>
                                        <div class="pkg-row-field">
                                            <span class="pkg-row-label">Qty</span>
                                            <input type="number" min="1" max="999" step="1" name="freebies[{{ $rowIndex }}][quantity]" value="{{ $freebie['quantity'] ?? 1 }}" class="form-input" placeholder="1">
                                            <x-validation-error :field="'freebies.'.($freebie['_error_index'] ?? $rowIndex).'.quantity'" />
                                        </div>
                                        <div class="pkg-row-field">
                                            <span class="pkg-row-label">Unit</span>
                                            <input type="text" name="freebies[{{ $rowIndex }}][unit]" value="{{ $freebie['unit'] ?? 'item' }}" class="form-input" placeholder="item" maxlength="40">
                                            <x-validation-error :field="'freebies.'.($freebie['_error_index'] ?? $rowIndex).'.unit'" />
                                        </div>
                                        <button type="button" class="pkg-tool-btn pkg-remove-btn" data-remove-row aria-label="Remove custom freebie"><i class="bi bi-x-lg"></i></button>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    @if($freebieCatalogs->isNotEmpty() || $customFreebies->isNotEmpty())<p class="pkg-complimentary-note">All freebies are complimentary.</p>@endif
                    @error('freebies') <div class="form-error">{{ $message }}</div> @enderror
                </div>
            </section>

            <section class="pkg-panel pkg-promo-panel" data-package-panel>
                <div class="pkg-panel-head" data-panel-head>
                    <div>
                        <div class="pkg-panel-title"><span class="pkg-icon"><i class="bi bi-tag"></i></span> Promo <span class="pkg-optional">(Optional)</span></div>
                        <p class="pkg-panel-copy">Set an optional package discount and its validity dates.</p>
                    </div>
                    @if(isset($package) && $package->promotion)
                        <span class="pkg-badge" data-promotion-assignment-status>{{ $package->promotion->statusAt(now('Asia/Manila'), $package) }}</span>
                    @else
                        <span class="pkg-badge" data-promotion-assignment-status>No Promotion</span>
                    @endif
                </div>
                <div class="pkg-panel-body">
                    <label class="pkg-label" for="package-promotion_id">Assigned Promotion</label>
                    <select id="package-promotion_id" name="promotion_id" class="form-select">
                        <option value="">No Promotion</option>
                        @foreach(($promotions ?? collect()) as $promotion)
                            @php($promotionStatus = $promotion->statusAt(now('Asia/Manila'), $package ?? null))
                            <option value="{{ $promotion->id }}" data-promotion-status="{{ $promotionStatus }}" data-promotion-name="{{ $promotion->name ?: 'Unnamed promotion' }}" data-discount="{{ strtoupper((string) $promotion->discount_type) === 'PERCENT' ? number_format((float) $promotion->discount_value, 2).'%' : ($promotion->discount_value !== null ? '₱'.number_format((float) $promotion->discount_value, 2) : 'Not configured') }}" data-start-date="{{ $promotion->starts_at?->format('M d, Y') ?? 'No start date' }}" data-end-date="{{ $promotion->ends_at?->format('M d, Y') ?? 'No end date' }}" data-review-details="{{ implode(' ', array_values($promotion->reviewFields($package ?? null))) }}" @selected((string) old('promotion_id', optional($package ?? null)->promotion?->id ?? '') === (string) $promotion->id)>
                                {{ $promotion->name ?: 'Unnamed promotion' }} · {{ $promotionStatus }}
                                · {{ strtoupper((string) $promotion->discount_type) === 'PERCENT' ? number_format((float) $promotion->discount_value, 2).'%' : '₱'.number_format((float) $promotion->discount_value, 2) }}
                            </option>
                        @endforeach
                    </select>
                    <x-validation-error field="promotion_id" />
                    <dl class="pkg-promotion-assignment-summary" data-promotion-assignment-summary aria-live="polite">
                        <div><dt>Promotion Name</dt><dd data-promotion-summary-name>{{ $assignedPromotion?->name ?? 'No promotion assigned' }}</dd></div>
                        <div><dt>Discount</dt><dd data-promotion-summary-discount>{{ $assignedPromotion ? (strtoupper((string) $assignedPromotion->discount_type) === 'PERCENT' ? number_format((float) $assignedPromotion->discount_value, 2).'%' : '₱'.number_format((float) $assignedPromotion->discount_value, 2)) : '—' }}</dd></div>
                        <div><dt>Start Date</dt><dd data-promotion-summary-start>{{ $assignedPromotion?->starts_at?->format('M d, Y') ?? '—' }}</dd></div>
                        <div><dt>End Date</dt><dd data-promotion-summary-end>{{ $assignedPromotion?->ends_at?->format('M d, Y') ?? '—' }}</dd></div>
                        <div><dt>Status</dt><dd data-promotion-summary-status>{{ $assignedPromotion?->statusAt(now('Asia/Manila'), $package ?? null) ?? 'No Promotion' }}</dd></div>
                    </dl>
                    @if(isset($package) && $package->promotion && $package->promotion->statusAt(now('Asia/Manila'), $package) === 'Incomplete Setup')
                        <p class="pkg-field-help" role="status" data-promotion-assignment-help>This assigned legacy promotion is retained but cannot be newly assigned elsewhere. {{ implode(' ', array_values($package->promotion->reviewFields($package))) }} Correct it in Promotions Management before it can be used.</p>
                    @else
                        <p class="pkg-field-help" data-promotion-assignment-help>Only Active promotions apply to new cases. Changing this assignment affects future cases; saved case prices remain unchanged.</p>
                    @endif
                </div>
            </section>
        </div>


    </div>
    <footer class="package-wizard-footer" hidden><span data-wizard-progress role="status">Step 1 of 6</span><div><button type="button" class="btn btn-outline" data-wizard-back>Back</button><a href="{{ $returnTo }}" class="btn btn-outline" data-service-form-cancel>Cancel</a><button type="button" class="btn btn-primary-custom" data-wizard-next>Next <i class="bi bi-arrow-right" aria-hidden="true"></i></button></div></footer>
</form>

<style>
    #packageForm:is(.is-package-discard-hidden,.is-discard-confirming),
    body:has(dialog.service-discard-dialog[open]) #packageForm {
        opacity:0 !important;
        visibility:hidden !important;
    }
    #packageForm:is(.is-package-discard-hidden,.is-discard-confirming) .pkg-service-controls,
    body:has(dialog.service-discard-dialog[open]) #packageForm .pkg-service-controls {
        display:none !important;
    }
    body:has(#packageForm) dialog.service-discard-dialog {
        --package-keep-bg:#dcebe2;
        --package-keep-hover:#c9e0d2;
        --package-keep-ink:#234a36;
        --package-discard-bg:#b42318;
        --package-discard-hover:#912018;
        --package-discard-ink:#fff;
        box-shadow:none !important;
    }
    html[data-theme='dark'] body:has(#packageForm) dialog.service-discard-dialog {
        --package-keep-bg:#2e5240;
        --package-keep-hover:#3a6650;
        --package-keep-ink:#e1f4e8;
        --package-discard-bg:#fda29b;
        --package-discard-hover:#ffb8b1;
        --package-discard-ink:#3d100d;
    }
    body:has(#packageForm) dialog.service-discard-dialog [data-keep] {
        border-color:transparent !important;
        background:var(--package-keep-bg) !important;
        color:var(--package-keep-ink) !important;
        transition:background-color 150ms ease;
    }
    body:has(#packageForm) dialog.service-discard-dialog [data-keep]:hover {
        background:var(--package-keep-hover) !important;
    }
    body:has(#packageForm) dialog.service-discard-dialog [data-discard] {
        border-color:transparent !important;
        background:var(--package-discard-bg) !important;
        color:var(--package-discard-ink) !important;
        transition:background-color 150ms ease;
    }
    body:has(#packageForm) dialog.service-discard-dialog [data-discard]:hover {
        background:var(--package-discard-hover) !important;
    }
    @media (prefers-reduced-motion: reduce) {
        body:has(#packageForm) dialog.service-discard-dialog :is([data-keep],[data-discard]) { transition:none; }
    }
</style>
<dialog id="packageDiscardDialog" class="service-discard-dialog service-feedback-dialog" aria-labelledby="packageDiscardTitle" aria-describedby="packageDiscardCopy">
    <span class="service-discard-dialog__icon" aria-hidden="true"><i class="bi bi-exclamation-triangle"></i></span>
    <h2 id="packageDiscardTitle">Discard unsaved changes?</h2>
    <p id="packageDiscardCopy">Your changes have not been saved.</p>
    <form method="dialog">
        <footer>
            <button type="submit" value="keep" data-keep autofocus>Keep Editing</button>
            <button type="submit" value="discard" data-discard>Discard Changes</button>
        </footer>
    </form>
</dialog>

@include('partials.service-field-popover')
<style>
    html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-service-fields.service-field-popover[popover]:popover-open {
        display:block !important;
        margin:0;
        padding:16px;
        border:0;
        background:var(--card,#f4f8f5) !important;
        box-shadow:var(--service-popover-shadow) !important;
    }
    html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-service-disclosure.service-field-adjust {
        width:auto; padding:4px 0; border:0; background:transparent;
    }
    #packageForm .pkg-service-top > .service-field-chip { grid-column:2/-1; grid-row:2; justify-self:start; max-width:100%; overflow-wrap:anywhere; }
    #packageForm .pkg-service-top [data-service-tooltip]::after { top:calc(100% + 5px); bottom:auto; }
    html[data-theme] body #packageForm[data-package-page] .pkg-service-card:has([data-service-tooltip]:is(:hover,:focus-visible)) { overflow:visible; }
    html[data-theme] body #packageForm .pkg-service-fields.service-field-popover .pkg-grid-2 { gap:12px; align-items:start; }
    #packageForm .pkg-service-fields.service-field-popover .pkg-grid-2 > div { min-width:0; }
    #packageForm .pkg-service-fields.service-field-popover .pkg-grid-2 label { display:flex; flex-wrap:wrap; align-content:end; gap:0 4px; min-height:2.8em; margin:0 0 6px; }
    #packageForm .pkg-service-fields.service-field-popover .pkg-field-help { margin-top:6px; }
    html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-service-fields.service-field-popover :is(.pkg-money,.pkg-unit-input):focus-within {
        background:var(--service-field-focus-bg) !important;
        outline:1px solid var(--service-field-focus-ring) !important;
        outline-offset:0;
        box-shadow:none !important;
    }
    html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-service-fields.service-field-popover :is(.pkg-money,.pkg-unit-input) input:focus {
        background:transparent !important;
        outline:none !important;
        box-shadow:none !important;
    }
    @media(max-width:600px) {
        #packageForm .pkg-service-fields.service-field-popover .pkg-grid-2 label { min-height:0; }
    }
</style>
<script>
(function () {
    const form = document.getElementById('packageForm');
    if (!form) return;

    // Keep text normalization available in this form even when the shared Vite
    // bundle is unavailable in a local/offline session. Only normalize fields
    // the user has edited; existing package values stay untouched on open.
    const normalizeEditedText = (field) => {
        const mode = field.dataset.textCase;
        const wordPattern = /[\p{L}][\p{L}\p{M}\p{N}]*/gu;
        const capitalizeWord = (word) => {
            const letters = [...word].filter(character => /\p{L}/u.test(character));
            const mixedCase = letters.slice(1).some(character => character !== character.toLowerCase())
                && letters.some(character => character !== character.toUpperCase());
            if (mixedCase) return word;
            const [first, ...rest] = [...word];
            return first.toUpperCase() + rest.join('');
        };

        if (mode === 'words') return field.value.replace(wordPattern, capitalizeWord);
        if (mode === 'sentences') {
            return field.value.replace(/(^\s*|[.!?]+["'”’)]*\s+)([\p{L}][\p{L}\p{M}\p{N}]*)/gu,
                (match, prefix, word) => prefix + capitalizeWord(word));
        }
        return field.value;
    };
    form.querySelectorAll('[data-text-case]').forEach(field => {
        let initialValue = field.value;
        let edited = false;
        field.addEventListener('input', () => { edited = true; });
        field.addEventListener('blur', () => {
            if (!edited || field.value === initialValue) return;
            field.value = normalizeEditedText(field);
            initialValue = field.value;
            edited = false;
        });
        form.addEventListener('submit', () => {
            if (!edited || field.value === initialValue) return;
            field.value = normalizeEditedText(field);
            initialValue = field.value;
            edited = false;
        }, true);
    });

    // Guard package Cancel links at the form itself so full-page navigation
    // cannot bypass the shared discard confirmation.
    const packageFormSnapshot = () => JSON.stringify(Array.from(form.elements)
        .filter(control => control.name && !['_token', '_service_modal'].includes(control.name))
        .map(control => {
            let value = control.value;
            if (control.type === 'checkbox' || control.type === 'radio') value = [control.checked, control.value];
            else if (control.tagName === 'SELECT' && control.multiple) value = Array.from(control.selectedOptions, option => option.value);
            else if (control.type === 'file') value = Array.from(control.files || [], file => [file.name, file.size, file.lastModified]);
            return [control.name, control.type || control.tagName, control.disabled, value];
        }).sort((left, right) => JSON.stringify(left).localeCompare(JSON.stringify(right))));
    let packageFormBaseline = '';
    let packageFormHasInitialErrors = false;
    let pendingDiscard;
    const animatePackageDiscard = async (element, keyframes, duration, easing, complete) => {
        if (matchMedia('(prefers-reduced-motion: reduce)').matches || typeof element.animate !== 'function') {
            complete?.();
            return;
        }
        const animation = element.animate(keyframes, { duration, easing, fill: 'both' });
        await animation.finished.catch(() => {});
        complete?.();
        animation.cancel();
    };
    const confirmPackageDiscard = () => {
        if (pendingDiscard) return pendingDiscard;
        if (typeof window.confirmFormDiscard === 'function') return window.confirmFormDiscard(form);
        const dialog = document.getElementById('packageDiscardDialog');
        const trigger = document.activeElement;
        const formHidden = 'is-package-discard-hidden';
        const dialogFrames = [{ opacity: 0, transform: 'translateY(6px)' }, { opacity: 1, transform: 'translateY(0)' }];
        const dialogForm = dialog.querySelector('form');
        pendingDiscard = new Promise(resolve => {
            let closing = false;
            const finish = async choice => {
                if (closing) return;
                closing = true;
                await animatePackageDiscard(dialog, [...dialogFrames].reverse(), 140, 'ease-in', () => dialog.close(choice));
            };
            const submit = event => {
                event.preventDefault();
                finish(event.submitter?.value || 'keep');
            };
            const cancel = event => {
                event.preventDefault();
                finish('keep');
            };
            dialogForm.addEventListener('submit', submit);
            dialog.addEventListener('cancel', cancel);
            dialog.addEventListener('close', async () => {
                dialogForm.removeEventListener('submit', submit);
                dialog.removeEventListener('cancel', cancel);
                const discarded = dialog.returnValue === 'discard';
                if (discarded) {
                    window.addEventListener('pageshow', () => form.classList.remove(formHidden), { once: true });
                } else {
                    form.classList.remove(formHidden);
                    trigger?.focus({ preventScroll: true });
                    await animatePackageDiscard(form, [{ opacity: 0 }, { opacity: 1 }], 180, 'ease-out');
                }
                pendingDiscard = null;
                resolve(discarded);
            }, { once: true });
            dialog.returnValue = '';
            animatePackageDiscard(form, [{ opacity: 1 }, { opacity: 0 }], 150, 'ease-in', () => {
                form.classList.add(formHidden);
                dialog.showModal();
                animatePackageDiscard(dialog, dialogFrames, 180, 'ease-out');
            });
        });
        return pendingDiscard;
    };
    window.requestServiceFormClose = async () => {
        const changed = packageFormSnapshot() !== packageFormBaseline || packageFormHasInitialErrors;
        if (changed && !await confirmPackageDiscard()) return false;
        if (changed) form.reset();
        return true;
    };
    form.querySelectorAll('[data-service-form-cancel]').forEach(cancel => {
        cancel.dataset.discardGuardBound = 'true';
        cancel.addEventListener('click', async event => {
            if (event.defaultPrevented) return;
            event.preventDefault();
            event.stopImmediatePropagation();
            // In a service-management iframe, guard locally first. The parent
            // calls requestServiceFormClose before closing; that guard is also
            // defined here so it remains available if shared modules fail.
            if (window.parent !== window && window.parent.cancelServiceCatalogModal) {
                await window.parent.cancelServiceCatalogModal();
                return;
            }
            if (!await window.requestServiceFormClose()) return;
            window.location.assign(cancel.href);
        }, true);
    });

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
            row.classList.toggle('is-enabled', enabled);
            row.querySelectorAll('[data-service-extra-field], [data-casket-select], [data-casket-snapshot], [data-casket-search]').forEach(field => { field.disabled = !enabled; });
            const status = row.querySelector('[data-service-status]');
            if (status) status.textContent = enabled ? 'Included' : 'Not included';
            const disclosure = row.querySelector('[data-service-disclosure]');
            if (disclosure) {
                if (disclosure.classList.contains('service-field-adjust')) {
                    disclosure.disabled = !enabled;
                    if (!enabled) row.querySelector('.pkg-service-fields[popover]:popover-open')?.hidePopover();
                    return;
                }
                disclosure.disabled = false;
                disclosure.setAttribute('aria-expanded', row.classList.contains('is-expanded') ? 'true' : 'false');
                const text = disclosure.querySelector('[data-service-disclosure-label]');
                if (text) text.textContent = row.classList.contains('is-expanded') ? 'Hide details' : (row.querySelector('[data-service-extra]') ? 'Limits & rates' : 'Casket details');
            }
        });
        const count = form.querySelectorAll('[data-service-toggle]:checked').length;
        const countBadge = form.querySelector('[data-service-count]');
        if (countBadge) countBadge.textContent = count + (count === 1 ? ' service' : ' services');
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

    function toggleServiceDisclosure(row) {
        if (!row) return;
        row.classList.toggle('is-expanded');
        syncServices();
    }

    function syncPromo() {
        const enabled = form.querySelector('[data-promo-toggle]')?.checked;
        const fields = form.querySelector('[data-promo-fields]');
        if (fields) { fields.style.display = enabled ? 'grid' : 'none'; fields.closest('.pkg-panel-body').hidden = !enabled; }
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
        casketSelect?.setAttribute('aria-invalid', valid ? 'false' : 'true');

        if (!valid) {
            row.classList.add('is-expanded');
            syncServices();
            openPackagePanel(row.closest('[data-package-panel]'));
            if (row.openServiceConfiguration) {
                row.openServiceConfiguration();
                row.querySelector('.service-field-chip')?.focus();
            } else {
                casketSelect.focus();
            }
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
        if (!card) return;
        card.classList.toggle('is-expanded');
        const options = card.querySelector('.pkg-unit-options');
        if (options) options.open = card.classList.contains('is-expanded');
    }

    function reviewLines(items, icon, emptyText) {
        if (!items.length) {
            return '<span class="pkg-review-empty">' + escapeHtml(emptyText) + '</span>';
        }

        return '<ul class="pkg-review-lines">' + items.map(item => (
            '<li><i class="bi ' + icon + '"></i><span>' + escapeHtml(item) + '</span></li>'
        )).join('') + '</ul>';
    }

    function syncPromotionAssignment() {
        const select = form.querySelector('[name="promotion_id"]');
        const status = form.querySelector('[data-promotion-assignment-status]');
        const help = form.querySelector('[data-promotion-assignment-help]');
        const summary = form.querySelector('[data-promotion-assignment-summary]');
        if (!select || !status) return;
        const option = select.selectedOptions[0];
        const value = option?.value ? (option.dataset.promotionStatus || 'Incomplete Setup') : 'No Promotion';
        status.textContent = value;
        if (summary) {
            summary.querySelector('[data-promotion-summary-name]').textContent = option?.value ? option.dataset.promotionName : 'No promotion assigned';
            summary.querySelector('[data-promotion-summary-discount]').textContent = option?.value ? option.dataset.discount : '—';
            summary.querySelector('[data-promotion-summary-start]').textContent = option?.value ? option.dataset.startDate : '—';
            summary.querySelector('[data-promotion-summary-end]').textContent = option?.value ? option.dataset.endDate : '—';
            summary.querySelector('[data-promotion-summary-status]').textContent = value;
        }
        if (help) {
            const copy = {
                'No Promotion': 'No promotion is assigned. Only Active promotions can apply to new cases.',
                'Active': 'This promotion applies to new cases. Changing the assignment does not alter saved case prices.',
                'Scheduled': 'This promotion is scheduled for a future date. Assignment changes affect future cases only.',
                'Expired': 'This is the package’s existing expired promotion. It cannot be newly assigned; update its schedule in Promotions Management first.',
                'Turned Off': 'This promotion is manually turned off and will not apply to new cases. Existing case prices remain unchanged.',
                'Incomplete Setup': option?.dataset.reviewDetails
                    ? `This assigned promotion cannot be used. ${option.dataset.reviewDetails} Correct it in Promotions Management.`
                    : 'This promotion cannot be used or assigned until a System Admin corrects its configuration.'
            };
            help.textContent = copy[value] || 'Only Active promotions apply to new cases. Saved case prices remain unchanged.';
            help.setAttribute('role', value === 'Incomplete Setup' ? 'alert' : 'status');
        }
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
        const custom = Array.from(form.querySelectorAll('input[type=hidden][name^="custom_inclusions["]')).map(input => input.value.trim()).filter(Boolean);
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
        const promoSelect = form.querySelector('[name="promotion_id"]');
        const promo = promoSelect?.selectedOptions?.[0]?.textContent?.trim() || 'No Promotion';
        const catalogInclusions = Array.from(form.querySelectorAll('[data-included-add-on-name]'))
            .filter(input => !input.disabled && Number(input.value) > 0)
            .map(input => `${input.dataset.includedAddOnName} × ${input.value} ${input.dataset.includedAddOnUnit}`);
        const includedItems = [...services, ...custom, ...catalogInclusions];

        const casketToggle = form.querySelector('input[name="included_services[casket][enabled]"]');
        const selectedCasket = form.querySelector('[data-casket-select]')?.selectedOptions[0];
        const unavailableCasket = casketToggle?.checked && selectedCasket?.value && selectedCasket.dataset.available === '0';
        const archivedFreebies = Array.from(form.querySelectorAll('[data-freebie-card].is-selected[data-catalog-active="0"]'));
        const blockers = [
            ...(unavailableCasket ? ['Included casket "' + selectedCasket.textContent.trim().replace(/ — Unavailable$/, '') + '" is unavailable. Choose another casket.'] : []),
            ...archivedFreebies.map(row => 'Remove or replace archived freebie: ' + (row.querySelector('.pkg-freebie-choice strong')?.textContent.trim() || 'Unknown freebie') + '.')
        ];

        list.innerHTML = [
            '<div class="pkg-review-item" role="status"><span class="pkg-review-kicker">New intake</span><span class="pkg-review-value">' + (blockers.length ? blockers.map(escapeHtml).join(' ') : 'Ready for new intake when saved.') + '</span></div>',
            '<div class="pkg-review-item"><span class="pkg-review-kicker">Package</span><span class="pkg-review-value">' + escapeHtml(name) + '<br>&#8369;' + money(price) + '</span></div>',
            '<div class="pkg-review-item"><span class="pkg-review-kicker">Included</span>' + reviewLines(includedItems, 'bi-check2-circle', 'No services selected yet') + '</div>',
            '<div class="pkg-review-item"><span class="pkg-review-kicker">Freebies</span>' + reviewLines(freebies, 'bi-gift', 'No freebies added yet.') + '</div>',
            '<div class="pkg-review-item"><span class="pkg-review-kicker">Promo</span><span class="pkg-review-value">' + escapeHtml(promo) + '</span></div>',
            '<div class="pkg-review-item"><span class="pkg-badge pkg-badge-charge">Additional Charge</span><span class="pkg-review-value">Optional add-ons are handled outside this package form. The selected casket is part of this package.</span></div>'
        ].join('');
    }

    const setIncludedAddOnExpanded = (card, expanded) => {
        const selected = card.querySelector('[data-included-addon-check]').checked;
        const quantity = card.querySelector('[data-included-add-on-name]');
        const summary = card.querySelector('[data-included-addon-summary]');
        const unit = quantity.dataset.includedAddOnUnit;
        card.querySelector('.pkg-included-addon-config').hidden = !selected || !expanded;
        summary.hidden = !selected || expanded;
        summary.querySelector('[data-included-addon-coverage]').textContent =
            `${quantity.value} ${unit}${Number(quantity.value) === 1 ? '' : 's'} included`;
    };
    form.addEventListener('change', event => {
        if (event.target.matches('[data-service-toggle]')) {
            event.target.closest('[data-service-row]')?.classList.toggle('is-expanded', event.target.checked);
            syncServices();
            validateRequiredCasket();
        }
        if (event.target.name?.startsWith('promo_')) syncPromo();
        if (event.target.matches('[data-freebie-check]')) syncFreebieCard(event.target.closest('[data-freebie-card]'));
        if (event.target.matches('[data-included-addon-check]')) {
            const card = event.target.closest('[data-included-addon-card]');
            const quantity = card.querySelector('[data-included-add-on-name]');
            const selected = event.target.checked;
            card.classList.toggle('is-selected', selected);
            quantity.disabled = !selected;
            quantity.required = selected;
            setIncludedAddOnExpanded(card, selected);
        }
        if (event.target.matches('[data-casket-select]')) {
            syncCasketSelect(event.target);
            validateRequiredCasket();
        }
        if (event.target.matches('[name="promotion_id"]')) syncPromotionAssignment();
        review();
    });
    form.addEventListener('input', event => {
        if (event.target.name?.startsWith('promo_')) syncPromo();
        if (event.target.matches('[data-casket-search]')) filterCasketOptions(event.target);
        review();
    });
    syncPromotionAssignment();
    form.addEventListener('invalid', event => {
        const addOnCard = event.target.closest('[data-included-addon-card]');
        if (addOnCard) setIncludedAddOnExpanded(addOnCard, true);
        const row = event.target.closest('[data-service-row]');
        if (row) {
            row.classList.add('is-expanded');
            syncServices();
            requestAnimationFrame(() => row.openServiceConfiguration?.());
        }
    }, true);
    form.addEventListener('submit', event => {
        if (form.classList.contains('package-wizard') && form.querySelector('[data-wizard-go][aria-current="step"]')?.dataset.wizardGo !== '6') {
            return;
        }
        if (!validateRequiredCasket()) {
            event.preventDefault();
            return;
        }
        if (form.getAttribute('aria-busy') === 'true') { event.preventDefault(); return; }
        form.setAttribute('aria-busy', 'true');
        form.querySelectorAll('[data-package-submit]').forEach(submit => {
            submit.disabled = true;
            submit.classList.add('opacity-70');
        });
        form.querySelectorAll('[data-package-submit-label]').forEach(label => { label.textContent = 'Saving...'; });
    });
    form.addEventListener('click', event => {
        const addOnAction = event.target.closest('[data-included-addon-done],[data-included-addon-adjust]');
        if (addOnAction) {
            const card = addOnAction.closest('[data-included-addon-card]');
            const quantity = card.querySelector('[data-included-add-on-name]');
            const editing = addOnAction.hasAttribute('data-included-addon-adjust');
            if (!editing && !quantity.reportValidity()) return;
            setIncludedAddOnExpanded(card, editing);
            (editing ? quantity : card.querySelector('[data-included-addon-adjust]')).focus({ preventScroll:true });
            return;
        }
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
        if (freebieCard && !event.target.closest('input, textarea, select, button, label[for], summary')) {
            event.preventDefault();
            toggleFreebieCard(freebieCard);
            review();
            return;
        }

        const serviceDisclosure = event.target.closest('[data-service-disclosure]');
        if (serviceDisclosure) {
            if (!serviceDisclosure.classList.contains('service-field-adjust')) toggleServiceDisclosure(serviceDisclosure.closest('[data-service-row]'));
            return;
        }

        const serviceCard = event.target.closest('[data-service-row]');
        if (serviceCard && !serviceCard.querySelector('[popover].is-configuration') && !event.target.closest('input, select, textarea, button, a, label, summary')) {
            toggleServiceDisclosure(serviceCard);
            return;
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
            const row = event.target.closest('[data-custom-freebie-row]');
            row?.remove();
            syncFreebieCount();
            review();
        }
    });

    form.querySelectorAll('[data-freebie-card]').forEach(card => {
        card.addEventListener('keydown', event => {
            if (event.target.closest('input, textarea, select, button, summary')) return;
            if (event.key !== 'Enter' && event.key !== ' ') return;
            event.preventDefault();
            toggleFreebieCard(card);
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
    if (typeof HTMLElement.prototype.showPopover === 'function') {
        form.querySelectorAll('[data-service-row]').forEach(row => {
            const trigger = row.querySelector('[data-service-disclosure]');
            const fields = row.querySelector('.pkg-service-fields');
            if (!trigger || !fields) return;
            trigger.classList.add('service-field-adjust');
            trigger.dataset.serviceTooltip = 'Adjust';
            trigger.textContent = 'Adjust';
            fields.classList.add('service-field-popover', 'is-configuration');
            fields.setAttribute('popover', 'auto');
            fields.setAttribute('aria-label', row.querySelector('.pkg-service-title')?.textContent.trim() || 'Included service configuration');
            row.openServiceConfiguration = window.bindServiceFieldPopover(trigger, fields);
        });
        for (const [id, label] of [['package-casket', 'Choose casket'], ['package-hearse', 'Choose hearse']]) {
            const select = document.getElementById(id);
            const picker = window.bindServiceFieldSelection(select, label);
            if (!picker) continue;
            const row = select.closest('[data-service-row]');
            row.querySelector('.pkg-service-top').append(picker.trigger, picker.panel);
        }
        form.querySelector('.pkg-casket-search-tools')?.remove();
    }
    form.querySelector('[data-apply-basic-defaults]')?.addEventListener('click', () => {
        const coverage = @json($basicDefaults?->coverage() ?? []);
        const rates = @json($basicDefaults?->rates() ?? []);
        Object.entries(coverage).forEach(([type, amount]) => {
            if (amount === null) return;
            const days = ['embalming', 'home_viewing'].includes(type);
            const limit = document.getElementById(`service-${type}-${days ? 'included_days' : 'included_kilometers'}`);
            const rate = document.getElementById(`service-${type}-${days ? 'price_per_extended_day' : 'price_per_excess_kilometer'}`);
            const toggle = document.getElementById(`package-service-${type}`);
            if (!limit || !rate || !toggle) return;
            limit.value = amount;
            rate.value = rates[type] ?? '';
            toggle.checked = true;
        });
        syncServices();
        review();
    });
    syncServices();
    syncPackagePanels();
    syncPromo();
    form.querySelectorAll('[data-casket-select]').forEach(syncCasketSelect);
    form.querySelectorAll('[data-freebie-card]').forEach(syncFreebieCard);
    syncFreebieCount();
    review();
    // Capture after package controls have reached their initial enabled/disabled
    // state, so untouched optional-service fields are not treated as edits.
    packageFormBaseline = packageFormSnapshot();
    packageFormHasInitialErrors = !!form.querySelector('[aria-invalid="true"], .form-error:not(:empty), [data-field-error]:not(.hidden)');
})();
</script>

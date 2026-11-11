@if($canManage)
    @php
        if (!(($catalog ?? null) instanceof \App\Models\CasketCatalog) && isset($catalog) && $catalog->is_active) {
            $catalogRoute = $catalog instanceof \App\Models\HearseCatalog ? 'hearse-catalogs' : ($catalog instanceof \App\Models\FreebieCatalog ? 'freebie-catalogs' : 'add-on-catalogs');
            $availabilityAffected = $affectedPackages ?? ($catalog instanceof \App\Models\HearseCatalog ? $catalog->packageInclusions->pluck('package')->filter()->where('is_active', true)->unique('id') : ($catalog instanceof \App\Models\AddOnCatalog ? $catalog->legacyPackageAddOns->pluck('package')->filter()->where('is_active', true)->unique('id') : collect()));
            array_unshift($statusActions, ['label' => $catalog->is_available ? 'Mark unavailable' : 'Mark available', 'title' => ($catalog->is_available ? 'Mark unavailable: ' : 'Mark available: ').$catalog->name.'?', 'icon' => 'pause-circle', 'reason' => $catalog instanceof \App\Models\AddOnCatalog ? 'Optional add-on reference; package eligibility is unchanged.' : 'Required inclusion; choose an eligible item in package configuration.', 'message' => 'Package status is unchanged. Required inclusions must be available for new intake; optional add-ons do not block packages. Resolve by updating the package inclusion or restoring catalog availability.', 'affected' => $catalog->is_available ? $availabilityAffected : collect(), 'url' => route('admin.'.$catalogRoute.'.toggleAvailability', $catalog)]);
        }
    @endphp
    @foreach($statusActions as $index => $action)
        @php
            $dialogId = 'catalog-status-confirm-' . $index;
            if (isset($catalog) && str_contains(strtolower($action['label']), 'archive')) {
                $archiveMessage = app(\App\Support\ServiceCatalogPolicy::class)->archiveBlockMessage($catalog);
                if ($archiveMessage) { $action['blocked'] = true; $action['message'] = $archiveMessage; $action['affected'] = app(\App\Support\ServiceCatalogPolicy::class)->activeRequiredPackages($catalog); $action['reason'] = 'Replace or remove this inclusion in package settings before archiving.'; }
            }
            $positiveFeedback = str_contains(strtolower($action['label']), 'restore') || (str_contains(strtolower($action['label']), 'available') && !str_contains(strtolower($action['label']), 'unavailable'));
            $feedbackKind = ($action['blocked'] ?? false) ? 'error' : ($positiveFeedback ? 'success' : 'warning');
            $feedbackIcon = $feedbackKind === 'error' ? 'exclamation-circle' : ($feedbackKind === 'success' ? 'check-circle' : 'exclamation-triangle');
        @endphp
        <button type="button" class="svc-detail-secondary" data-status-open="{{ $dialogId }}">
            <i class="bi bi-{{ $action['icon'] }}" aria-hidden="true"></i> {{ $action['label'] }}
        </button>
        <dialog class="svc-status-dialog" data-feedback-kind="{{ $feedbackKind }}" id="{{ $dialogId }}" aria-labelledby="{{ $dialogId }}-title">
            <div class="svc-status-dialog-head">
                <span class="svc-status-dialog-icon" aria-hidden="true"><i class="bi bi-{{ $feedbackIcon }}"></i></span>
                <div>
                    <h2 id="{{ $dialogId }}-title">{{ $action['title'] }}</h2>
                    <p><strong>{{ $catalog->name ?? $package->name ?? '' }}</strong></p><p>{{ $action['message'] }}</p>@if(!str_contains(strtolower($action['message']), 'status'))<p class="svc-detail-note">Package Active/Archived status stays unchanged.</p>@endif
                </div>
            </div>
            @if(!str_contains(strtolower($action['label']), 'restore') && count($action['affected'] ?? []) > 0)
                <div class="svc-status-affected">
                    <strong>Affected active packages ({{ count($action['affected']) }})</strong>
                    <ul>
                        @foreach($action['affected'] as $affectedPackage)
                            <li><a href="{{ route('admin.packages.edit', ['package' => $affectedPackage, 'return_to' => $returnTo]) }}" target="_top">{{ $affectedPackage->name }}</a><small> — {{ $action['reason'] ?? 'Required inclusion: update package configuration or restore catalog availability before new intake.' }}</small></li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @if(!empty($action['details']))
                <div class="svc-status-affected"><strong>What to fix</strong><ul>@foreach($action['details'] as $detail)<li>{{ $detail }}</li>@endforeach</ul></div>
            @endif
            <div class="svc-status-dialog-actions">
                <button type="button" class="svc-detail-secondary" data-status-cancel>{{ ($action['blocked'] ?? false) ? 'Close' : 'Cancel' }}</button>
                @unless($action['blocked'] ?? false)
                    <form method="POST" action="{{ $action['url'] }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="svc-detail-primary">{{ str_contains(strtolower($action['label']), 'unavailable') ? 'Mark unavailable' : (str_contains(strtolower($action['label']), 'available') ? 'Mark available' : (str_contains(strtolower($action['label']), 'restore') ? 'Restore' : 'Archive')) }}</button>
                    </form>
                @endunless
            </div>
        </dialog>
    @endforeach
    @once
        <script>
            document.addEventListener('click', event => {
                const opener = event.target.closest('[data-status-open]');
                if (opener) document.getElementById(opener.dataset.statusOpen)?.showModal();
                const cancel = event.target.closest('[data-status-cancel]');
                if (cancel) cancel.closest('dialog')?.close();
            });
            document.addEventListener('click', event => {
                if (event.target instanceof HTMLDialogElement && event.target.matches('.svc-status-dialog')) event.target.close();
            });
        </script>
    @endonce
@endif

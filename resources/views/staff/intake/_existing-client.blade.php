@include('partials.worklist_consistency_styles')
<style>
    #intakeWizardForm .existing-client-picker {
        display:grid;
        gap:8px;
        width:100%;
        max-width:560px;
        min-width:0;
        margin-block-end:clamp(18px, 3vw, 26px);
    }
    #intakeWizardForm .existing-client-picker [hidden] { display:none !important; }
    #intakeWizardForm .existing-client-search { display:flex; flex-wrap:wrap; align-items:center; gap:8px; }
    #intakeWizardForm .existing-client-picker p { margin:0; color:var(--intake-muted); }
    #intakeWizardForm .existing-client-picker .worklist-search-control {
        --record-search-action:var(--reference-action-bg);
        --record-search-action-hover:var(--reference-action-hover);
        --record-search-action-active:var(--reference-action-hover);
        --record-search-action-text:var(--reference-action-text);
        display:flex;
        align-items:center;
        max-width:560px;
        margin:6px 0;
    }
    #intakeWizardForm .existing-client-picker .worklist-search-control > .bi-search {
        inset:0 auto 0 0;
        display:grid;
        width:40px;
        place-items:center;
        line-height:1;
        translate:none;
    }
    #intakeWizardForm .existing-client-picker .worklist-search-control > input {
        min-height:0;
        min-width:0;
        margin:0;
        line-height:1.5;
        background:transparent !important;
        border:0 !important;
    }
    #intakeWizardForm .existing-client-picker .worklist-search-control:has(.worklist-search-clear[hidden]) > input { padding-inline-end:94px !important; }
    #intakeWizardForm .existing-client-picker .worklist-search-control > input:focus { outline:none !important; box-shadow:none !important; }
    html:not([data-filter-input='pointer']) #intakeWizardForm .existing-client-picker .worklist-search-control:has(> input:focus-visible) {
        outline:2px solid var(--filter-ui-focus);
        outline-offset:2px;
    }
    html[data-filter-input='pointer'] #intakeWizardForm .existing-client-picker .worklist-search-control:has(> input:focus) {
        border-color:var(--filter-ui-border) !important;
        outline:none;
    }
    #intakeWizardForm .existing-client-picker .worklist-match { display:grid; grid-template-columns:minmax(0,1fr) auto; gap:2px 10px; }
    #intakeWizardForm .existing-client-picker .worklist-match > span:first-child { max-width:none; }
    #intakeWizardForm .existing-client-picker .worklist-match .existing-client-match-code { font-size:12px; font-weight:650; }
    #intakeWizardForm .existing-client-picker .worklist-match .existing-client-match-detail { grid-column:1 / -1; display:block; color:var(--intake-muted); }
    #intakeWizardForm .existing-client-picker .worklist-match > span::before { content:none; }
    html[data-theme] body.panel-shell-body #intakeWizardForm .existing-client-picker .worklist-match[aria-selected="true"] { background:var(--intake-hover) !important; }
    #intakeWizardForm [data-existing-client-selected] { justify-content:space-between; }
</style>
<div class="existing-client-picker" data-existing-client-picker data-worklist-consistent data-url="{{ route('intake.existing-clients') }}">
    <input type="hidden" name="existing_client_id" value="{{ $intakeValue('existing_client_id') }}">
    <div data-existing-client-search>
        <label class="field-label" for="intake-client-search">Find Existing Client</label>
        <div class="worklist-search-control">
            <i class="bi bi-search" aria-hidden="true"></i>
            <input type="search" id="intake-client-search" data-shared-search-ready="1" role="combobox" placeholder="Name, mobile number, or ID" autocomplete="off" maxlength="100" aria-autocomplete="list" aria-expanded="false" aria-controls="intake-client-search-matches" aria-describedby="intake-client-search-help">
            <button type="button" class="worklist-search-clear" data-existing-client-clear aria-label="Clear client search" hidden><i class="bi bi-x" aria-hidden="true"></i></button>
            <button type="button" class="worklist-search-submit" data-existing-client-find>Search</button>
            <div id="intake-client-search-matches" class="worklist-search-matches" data-existing-client-results role="listbox" aria-label="Matching clients" hidden></div>
        </div>
        <p id="intake-client-search-help">Search by name, mobile number, Directory ID, or Case ID.</p>
        <p id="intake-client-search-status" role="status"></p>
    </div>
    <div class="existing-client-search" data-existing-client-selected hidden>
        <div><strong data-existing-client-name></strong><p>Choose the relationship for this case below.</p></div>
        <button type="button" class="service-field-chip" data-existing-client-change>Change Client</button>
    </div>
    @if($errors->has('existing_client_id') || ($intakeValue('existing_client_id') && empty($existingClientSelection)))
        <p class="intake-field-error" role="alert" data-existing-client-error>{{ $errors->first('existing_client_id') ?: 'This saved client is unavailable. Choose another client or enter a new one.' }}</p>
    @endif
</div>

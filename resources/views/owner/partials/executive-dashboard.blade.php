@php
    $o = $overview;
    $scopeLabel = $selectedBranch ? $selectedBranch->branch_code.' — '.$selectedBranch->branch_name : 'All Branches';
    $periodLabel = $o['start']->format('M d, Y').' – '.$o['end']->format('M d, Y');
    $clearBranchUrl = route('owner.dashboard',['range'=>$range,'date_from'=>$dateFrom,'date_to'=>$dateTo]);
    $reportUrl = function ($kind) use ($branchId, $dateFrom, $dateTo) {
        $params = ['branch_id'=>$branchId, 'report_type'=>$kind === 'collections' ? 'sales' : 'master_cases'];
        if ($kind !== 'collections') $params += ['verification_status'=>'VERIFIED', 'date_basis'=>'recording'];
        if (in_array($kind, ['outstanding', 'risks'])) $params['collection_state'] = $kind === 'risks' ? 'AFTER_INTERMENT' : 'WITH_BALANCE';
        else $params += ['date_from'=>$dateFrom, 'date_to'=>$dateTo];
        return route('reports.index', $params);
    };
    $cards = [
        ['key'=>'cases','label'=>'Cases Recorded','icon'=>'file-earmark-text','basis'=>'Verified cases · recording date','money'=>false],
        ['key'=>'service','label'=>'Total Service Value','icon'=>'cash-stack','basis'=>'Agreed value of these cases','money'=>true],
        ['key'=>'collections','label'=>'Collections Received','icon'=>'wallet2','basis'=>'Valid receipts · payment date','money'=>true],
        ['key'=>'outstanding','label'=>'Current Outstanding','icon'=>'hourglass-split','basis'=>'Current balances · all dates','money'=>true],
    ];
@endphp
<div class="oe" @if($range!=='ALL_TIME') data-comparison-url="{{ route('owner.dashboard',['branch_id'=>$branchId,'range'=>'CUSTOM','date_from'=>$o['previousStart']->toDateString(),'date_to'=>$o['previousEnd']->toDateString()]) }}" @endif data-owner-executive data-history-url="{{ route('owner.history') }}" data-details-url="{{ route('owner.dashboard.details') }}" data-branch="{{ $branchId }}" data-range="{{ $range }}" data-from="{{ $dateFrom }}" data-to="{{ $dateTo }}" data-scope="{{ $scopeLabel }}" data-period="{{ $periodLabel }}">
    <span class="sr-only">Owner Overview</span>
    <section class="oe-executive-banner oe-card">
    <header class="oe-header eb-greeting eb-greeting--flat">
        <div><span class="oe-eyebrow oe-suite-label"><i class="bi bi-briefcase" aria-hidden="true"></i>Executive overview</span><h1>Owner Dashboard</h1><p>Good day, {{ \Illuminate\Support\Str::before(auth()->user()->name, ' ') }}. Review performance, compare branches, and identify collection exposure.</p></div>
        <div class="oe-header-actions">@include('partials.dashboard-header-controls')</div>
    </header>

    <section class="oe-filter-card" aria-label="Dashboard filters">
        <form id="ownerExecutiveFilters" method="GET" action="{{ route('owner.dashboard') }}" data-owner-overview-filter>
            <label>Branch<span class="ba-branch-select-wrap oe-shared-select"><i class="bi bi-buildings" aria-hidden="true"></i><select class="ba-branch-select" name="branch_id" aria-label="Dashboard branch"><option value="">All Branches</option>@foreach($branches as $branch)<option value="{{ $branch->id }}" @selected((string)$branchId === (string)$branch->id)>{{ $branch->branch_code }} — {{ $branch->branch_name }}</option>@endforeach</select><i class="bi bi-chevron-down ba-branch-select-chev" aria-hidden="true"></i></span></label>
            <label>Period<span class="ba-period-select-wrap oe-shared-select"><i class="bi bi-calendar3" aria-hidden="true"></i><select class="ba-period-select" name="range" aria-label="Dashboard period">@foreach(['TODAY'=>'Today','THIS_WEEK'=>'This Week','THIS_MONTH'=>'This Month','LAST_MONTH'=>'Last Month','THIS_QUARTER'=>'This Quarter','THIS_YEAR'=>'This Year','LAST_30_DAYS'=>'Last 30 Days','ALL_TIME'=>'All Time','CUSTOM'=>'Custom Range'] as $value=>$label)<option value="{{ $value }}" @selected($range===$value)>{{ $label }}</option>@endforeach</select><i class="bi bi-chevron-down" data-filter-select-icon aria-hidden="true"></i></span></label>
            <input type="hidden" name="date_from" value="{{ $dateFrom }}"><input type="hidden" name="date_to" value="{{ $dateTo }}">
            <div class="oe-actions"><button type="button" class="oe-button" data-hide-amounts aria-pressed="false" title="Hide monetary amounts on this dashboard"><i class="bi bi-eye-slash" aria-hidden="true"></i><span>Hide amounts</span></button></div>
            <noscript><button class="oe-button" type="submit">Apply filters</button></noscript>
        </form>
        <div class="oe-filter-context" aria-label="Applied dashboard filters"><span class="oe-scope-chip"><i class="bi bi-buildings" aria-hidden="true"></i><span>{{ $scopeLabel }}</span>@if($branchId)<a class="oe-clear-branch" href="{{ $clearBranchUrl }}" aria-label="Remove branch filter" title="Remove branch filter"><i class="bi bi-x-lg" aria-hidden="true"></i></a>@endif</span><span class="oe-scope-date"><i class="bi bi-calendar3" aria-hidden="true"></i>{{ $periodLabel }}</span></div>
    </section>
    </section>
    <div class="oe-kpis" aria-label="Business performance summary">
        @foreach($cards as $card)
        <article class="oe-kpi oe-card" data-kpi="{{ $card['key'] }}" @if($card['key'] === 'outstanding' && (float)$o['current']['outstanding'] <= 0) data-zero-outstanding @endif>
            <div class="oe-kpi-top"><i class="bi bi-{{ $card['icon'] }}" aria-hidden="true"></i><div><span class="oe-kpi-label">{{ $card['label'] }}</span></div></div>
            <div class="oe-kpi-measure"><strong class="oe-kpi-value {{ $card['money'] ? 'oe-amount' : '' }}">{{ $card['money'] ? '₱' : '' }}{{ number_format($o['current'][$card['key']], $card['money'] ? 2 : 0) }}</strong>
            @if($card['key'] !== 'outstanding')
                @if($range !== 'ALL_TIME' && $o['comparison'][$card['key']] !== null)<span class="oe-comparison {{ $o['comparison'][$card['key']] < 0 ? 'is-down' : 'is-up' }}"><i class="bi bi-{{ $o['comparison'][$card['key']] < 0 ? 'arrow-down-right' : 'arrow-up-right' }}" aria-hidden="true"></i> {{ $o['comparison'][$card['key']] > 0 ? '+' : '' }}{{ $o['comparison'][$card['key']] }}% vs previous comparable period</span>@endif
            @else<span class="oe-comparison">{{ number_format($o['balanceCount']) }} cases with balance</span>@endif
            </div>
            <a class="oe-kpi-action" href="{{ $reportUrl($card['key']) }}" aria-label="View {{ $card['label'] }} report"><span class="sr-only">{{ $card['key'] === 'collections' ? 'View Collection Report' : 'View Case Monitoring Report' }}</span></a>
        </article>
        @endforeach
    </div>
    <section class="oe-card" aria-labelledby="owner-trend-title">
        <div class="oe-section-head"><div><h2 id="owner-trend-title"><i class="bi bi-bar-chart-line oe-title-icon" aria-hidden="true"></i>Collections &amp; Case Volume Trends</h2><p>Collections by payment date. Case volume by recording date. Select a bar for a summary, then view its records.</p></div><div class="oe-segment" role="group" aria-label="Trend measure"><button type="button" data-trend="collections" aria-pressed="true">Collections Trend (₱)</button><button type="button" data-trend="cases" aria-pressed="false">Case Volume Trend</button></div></div>
        @foreach(['collections'=>'Collections Received','cases'=>'Cases Recorded'] as $measure=>$title)
        @php
            $points = collect($o['points']); $peak = max((float)$points->max($measure), 1);
            $stepBase = pow(10, floor(log10($peak / 4))); $rawStep = $peak / 4 / $stepBase;
            $step = collect([1,2,5,10])->first(fn($n) => $n >= $rawStep) * $stepBase;
            if($measure==='cases') $step = max(1,ceil($step));
            $max = $step * 4;
        @endphp
        <div class="oe-trend-panel" data-chart-series="{{ json_encode($o['points']) }}" data-trend-panel="{{ $measure }}" @if($measure==='cases') hidden @endif>
            <div class="oe-trend-comparison">
                <div><small>Current Period</small><strong class="{{ $measure==='collections'?'oe-amount':'' }}">{{ $measure==='collections'?'₱':'' }}{{ number_format($o['current'][$measure],$measure==='collections'?2:0) }}</strong><span>{{ $periodLabel }}</span></div>
                <div><small>Previous Period</small><strong class="{{ $measure==='collections'?'oe-amount':'' }}">{{ $range==='ALL_TIME'?'Unavailable':($measure==='collections'?'₱':'').number_format($o['previous'][$measure],$measure==='collections'?2:0) }}</strong><span>{{ $range==='ALL_TIME'?'All-time view':$o['previousStart']->format('M d').' – '.$o['previousEnd']->format('M d, Y') }}</span></div>
                <div><small>Change vs Previous Period</small><strong>{{ $range==='ALL_TIME' || $o['previous'][$measure]<=0?'Not available':($o['comparison'][$measure]>0?'+':'').$o['comparison'][$measure].'%' }}</strong><span>{{ $range==='ALL_TIME'?'Choose a reporting period to compare.':($o['previous'][$measure]<=0?($measure==='collections'?'No collections in previous period.':'No cases in previous period.'):'Compared with the previous period.') }}</span></div>
            </div>
            <div class="oe-chart-legend"><span><i class="oe-legend-current" aria-hidden="true"></i>Current period</span>@if($range!=='ALL_TIME')<span><i class="oe-legend-previous" aria-hidden="true"></i>Previous comparable period</span><span data-comparison-status role="status">Loading comparison…</span>@endif</div>
            @if($points->sum($measure)>0 || ($range!=='ALL_TIME' && $o['previous'][$measure]>0))
            <div class="oe-chart-frame"><div class="oe-axis-title">{{ $measure==='collections'?'Collections Received (PHP)':'Number of Cases' }}</div><div class="oe-y-axis" aria-label="Y-axis scale">@for($tick=4;$tick>=0;$tick--)<span style="top:{{ (4-$tick)*25 }}%" class="{{ $measure==='collections'?'oe-amount':'' }}">{{ $measure==='collections'?'₱':'' }}{{ number_format($step*$tick,$measure==='collections' && $step<1?2:0) }}</span>@endfor</div>
            <div class="oe-bar-scroll" tabindex="0" aria-label="{{ $title }} chart; scroll horizontally for more dates">
                <div class="oe-bars" style="--bar-count:{{ $points->count() }}" role="group" aria-label="{{ $title }} by period">
                @foreach($points as $point)
                    <div class="oe-bar-item" data-point-value="{{ $point[$measure] }}">
                        <div class="oe-bar-track oe-bar-pair">
                            <button type="button" class="oe-chart-bar oe-bar-fill {{ $measure==='collections'?'oe-money-bar':'' }}" @disabled($point[$measure]<=0) style="height:{{ round($point[$measure]/$max*100,4) }}%" data-drilldown="{{ $measure }}" data-title="{{ $title }} · {{ $point['label'] }}" data-period-label="{{ $point['label'] }}" data-basis="{{ $measure==='collections'?'Valid receipts · payment date':'Verified cases · recording date' }}" data-point-from="{{ $point['from'] }}" data-point-to="{{ $point['to'] }}" aria-label="Current period · {{ $point['label'] }}: {{ $measure==='collections'?'₱':'' }}{{ number_format($point[$measure],$measure==='collections'?2:0) }}; view summary">
                                <span class="oe-bar-tooltip" aria-hidden="true"><span>Current period · {{ $point['label'] }}</span><strong class="{{ $measure==='collections'?'oe-amount':'' }}">{{ $measure==='collections'?'₱':'' }}{{ number_format($point[$measure],$measure==='collections'?2:0) }}</strong></span>
                            </button>
                            <button type="button" class="oe-chart-bar oe-bar-previous {{ $measure==='collections'?'oe-money-bar':'' }}" hidden disabled data-drilldown="{{ $measure }}" data-basis="{{ $measure==='collections'?'Valid receipts · payment date':'Verified cases · recording date' }}" aria-label="Previous period comparison loading"><span class="oe-bar-tooltip" aria-hidden="true"></span></button>
                        </div>
                        <span class="oe-bar-label">{{ $point['label'] }}</span>
                    </div>
                @endforeach
                </div>
            </div>
            </div><div class="oe-axis-caption">{{ $o['start']->diffInDays($o['end'])>62?'Month':'Date' }}</div>
            <details class="oe-trend-data"><summary>View exact values</summary><div class="oe-table-scroll"><table><thead><tr><th>Period</th><th class="oe-number">{{ $title }}</th></tr></thead><tbody>@foreach($points as $point)<tr><td>{{ $point['label'] }}</td><td class="oe-number {{ $measure==='collections'?'oe-amount':'' }}">{{ $measure==='collections'?'₱':'' }}{{ number_format($point[$measure],$measure==='collections'?2:0) }}</td></tr>@endforeach</tbody></table></div></details>
            @else<div class="oe-empty"><i class="bi bi-bar-chart" aria-hidden="true"></i><p>No {{ strtolower($title) }} in this period.</p><span>Select another period or branch.</span></div>@endif
        </div>
        @endforeach
    </section>
    <section class="oe-card" aria-labelledby="owner-branches-title">
        <div class="oe-section-head"><div><h2 id="owner-branches-title"><i class="bi bi-buildings oe-title-icon" aria-hidden="true"></i>Branch Performance</h2><p data-ranking-description>Which branch generated the most service value?</p><p class="oe-ranking-scope" data-ranking-scope></p></div><div class="oe-actions"><label class="oe-rank-label">Measure<span class="ba-period-select-wrap oe-shared-select oe-compare-select"><i class="bi bi-bar-chart" aria-hidden="true"></i><select class="ba-period-select" data-branch-measure aria-label="Branch comparison metric"><option value="service">Total Service Value</option><option value="rate">Collection Rate</option><option value="cases">Cases Recorded</option></select><i class="bi bi-chevron-down" aria-hidden="true"></i></span></label></div></div>
        <div class="oe-branch-chart" data-branch-chart role="list" aria-label="Branches ranked by selected metric">
        @foreach(collect($o['branchRows'])->sortByDesc('service') as $row)
            <article class="oe-branch-row" role="listitem" data-branch-row data-code="{{ $row['branch']->branch_code }}" data-collections="{{ $row['collections'] }}" data-service="{{ $row['service'] }}" data-cases="{{ $row['cases'] }}" data-outstanding="{{ $row['outstanding'] }}" data-rate="{{ $row['rate'] ?? '' }}" data-collected="{{ $row['cohortCollected'] }}" data-remaining="{{ $row['cohortRemaining'] }}">
                <span class="oe-rank" data-branch-rank></span>
                <div class="oe-ranking-name"><span>{{ $row['branch']->branch_name }}</span><small>{{ $row['branch']->branch_code }}</small></div>
                <div class="oe-branch-bar"><div class="oe-ranking-bar-wrap"><span class="oe-horizontal-track" aria-hidden="true"><span data-branch-fill class="oe-horizontal-fill"></span><span data-branch-remaining class="oe-remaining-fill" hidden></span></span><small data-branch-share></small></div></div>
                <div class="oe-ranking-metric"><small data-branch-metric-label>Total Service Value</small><strong data-branch-value class="oe-ranking-value oe-amount">₱{{ number_format($row['collections'],2) }}</strong></div>
                <div class="oe-ranking-context" data-branch-context></div>
            </article>
        @endforeach
        </div><p class="oe-branch-footnote"><span data-ranking-footnote>Bars show each branch’s share of the selected metric across the branches shown. Tied values share the same rank.</span></p>
    </section>
    <section class="oe-card oe-risk" aria-labelledby="owner-risk-title"><div><span class="oe-eyebrow">Current collection exposure</span><h2 id="owner-risk-title"><i class="bi bi-exclamation-triangle oe-title-icon" aria-hidden="true"></i>Balances Past Interment Date</h2><p>Current follow-ups across all recording dates in {{ $scopeLabel }}.</p></div><div class="oe-risk-summary"><div class="oe-risk-stat"><small>Cases to review</small><strong>{{ number_format($o['riskCount']) }} cases</strong></div><div class="oe-risk-stat"><small>Current balance</small><span class="oe-amount">₱{{ number_format($o['riskAmount'],2) }}</span></div></div>@if($o['riskCount'])<a class="oe-button" href="{{ $reportUrl('risks') }}">View Case Monitoring Report <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>@else<span class="oe-basis">No items needing follow-up.</span>@endif</section>
</div>
<dialog class="oe-dialog oe-summary-dialog" id="ownerExecutiveDetails" aria-labelledby="owner-details-title"><header><div><div class="oe-summary-heading"><i class="bi bi-file-earmark-text oe-title-icon" data-summary-icon aria-hidden="true"></i><h2 id="owner-details-title">Dashboard summary</h2></div></div><button type="button" class="oe-icon-button" data-close-details aria-label="Close summary"><i class="bi bi-x-lg" aria-hidden="true"></i></button></header><div class="oe-summary-body"><div class="oe-drawer-scope"><p data-details-context></p></div><div data-details-results aria-live="polite"></div></div><footer><a class="oe-button oe-primary" data-summary-destination hidden>View Records <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a></footer></dialog>
<dialog class="oe-dialog oe-date-dialog" id="ownerExecutiveDates" aria-labelledby="owner-date-title"><header><h2 id="owner-date-title"><i class="bi bi-calendar-range oe-title-icon" aria-hidden="true"></i>Custom Date Range</h2><button class="oe-icon-button" type="button" data-close-dates aria-label="Close custom date range"><i class="bi bi-x-lg" aria-hidden="true"></i></button></header><form data-owner-custom-dates><p class="oe-date-help">Choose a start and end date. The end date must be on or after the start date.</p><label>From<input type="date" name="from" value="{{ $dateFrom }}" required aria-describedby="owner-date-error"></label><label>To<input type="date" name="to" value="{{ $dateTo }}" required min="{{ $dateFrom }}" aria-describedby="owner-date-error"></label><p id="owner-date-error" class="oe-date-error" role="alert" hidden></p><div class="oe-actions"><button type="button" class="oe-button" data-close-dates>Cancel</button><button type="submit" class="oe-button oe-primary">Apply Range</button></div></form></dialog>
@include('owner.partials.executive-styles')
@include('owner.partials.executive-script')

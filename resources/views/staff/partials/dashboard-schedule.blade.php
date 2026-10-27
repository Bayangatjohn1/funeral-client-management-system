<div class="ref-schedule-card">
    <div class="ref-schedule-top"><span class="ref-schedule-type">{{ $item['label'] }}</span><time>@if($upcoming ?? false){{ $item['date']?->format('M d') }} · @endif{{ !empty($item['has_time']) ? $item['date']?->format('g:i A') : 'Time not recorded' }}</time></div>
    <div class="ref-schedule-person"><strong>{{ $item['deceased_name'] }}</strong><span class="ref-meta">{{ $item['case_code'] }}</span></div>
    @php $sameTimeCount = !empty($item['has_time']) && $item['date'] ? ($sameTimeCounts->get($item['date']->format('Y-m-d H:i')) ?? 0) : 0; @endphp
    @if($sameTimeCount > 1)<div class="ref-schedule-coordination"><i class="bi bi-clock" aria-hidden="true"></i> {{ $sameTimeCount }} events at the same time</div>@endif
<button type="button" class="dashboard-view-icon" data-dashboard-schedule="{{ $item['case_id'] }}" data-schedule-event="{{ $item['type'] }}" aria-label="View Case Schedule" data-tip="View Case Schedule"><i class="bi bi-calendar3" aria-hidden="true"></i></button>
</div>

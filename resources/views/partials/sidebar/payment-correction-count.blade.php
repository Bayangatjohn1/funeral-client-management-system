@php
    $correctionUser = auth()->user();
    $pendingCorrections = \App\Models\PaymentCorrectionRequest::pendingSummaryFor($correctionUser);
    $onPaymentMonitoring = request()->routeIs('payments.history', 'payments.index', 'admin.payments.index', 'admin.payment-monitoring');
@endphp
<small class="correction-request-count sidebar-correction-count"
       data-sidebar-correction-count
       data-user="{{ $correctionUser->id }}"
       data-latest-pending-id="{{ $pendingCorrections['latest_pending_id'] }}"
       data-current="{{ $onPaymentMonitoring ? 'true' : 'false' }}"
       data-attention="{{ !$onPaymentMonitoring && $pendingCorrections['pending'] > 0 ? 'true' : 'false' }}"
       aria-label="{{ $pendingCorrections['pending'] }} pending correction requests"
       @if(!$pendingCorrections['pending']) hidden @endif>{{ $pendingCorrections['pending'] }}</small>

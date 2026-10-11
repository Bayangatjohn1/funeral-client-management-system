@extends('layouts.panel')

@section('page_title', 'Payment Corrections')
@section('page_desc', 'Review payment correction requests for your assigned branch.')
@section('hide_layout_topbar', '1')

@section('content')
<div class="admin-table-page payment-corrections-page">
<div class="payment-corrections-head"><h1>Payment Corrections</h1><p>Review payment details, proposed changes, and request status.</p></div>
@if(session('success'))<div class="mb-4 rounded-xl bg-emerald-50 p-4 text-emerald-800">{{ session('success') }}</div>@endif
<div class="overflow-hidden rounded-2xl bg-white shadow-sm"><div class="overflow-x-auto"><table class="min-w-full divide-y text-sm"><thead class="bg-gray-50"><tr><th class="p-4 text-left">Payment</th><th class="p-4 text-left">Request</th><th class="p-4 text-left">Original / Proposed</th><th class="p-4 text-left">Status</th><th class="p-4 text-left">Actions</th></tr></thead><tbody class="divide-y">
@forelse($requests as $item)<tr><td class="p-4">{{ $item->payment?->display_payment_record_no }}<div class="text-gray-500">{{ $item->payment?->funeralCase?->case_code }}</div></td><td class="p-4">{{ str_replace('_', ' ', $item->correction_type) }}<div class="text-gray-500">{{ $item->requester?->name }} · {{ $item->created_at->format('M d, Y g:i A') }}</div><div class="mt-1">{{ $item->reason }}</div></td><td class="p-4"><div>Amount: ₱{{ number_format((float)($item->original_values['amount'] ?? 0), 2) }}</div>@if($item->proposed_values)<div class="text-blue-700">Proposed: ₱{{ number_format((float)($item->proposed_values['amount'] ?? $item->original_values['amount'] ?? 0), 2) }}</div>@else<div class="text-amber-700">Void without replacement</div>@endif</td><td class="p-4">{{ $item->status }}</td><td class="p-4">@if($item->status === 'PENDING')<div class="flex flex-col gap-2"><form method="POST" action="{{ route('admin.payment-corrections.approve', $item) }}">@csrf<button class="rounded-lg bg-emerald-700 px-3 py-2 text-white">Approve</button></form><form method="POST" action="{{ route('admin.payment-corrections.reject', $item) }}" class="flex gap-2">@csrf<input name="reason" required placeholder="Rejection reason" class="rounded-lg border-gray-300"><button class="rounded-lg border px-3 py-2">Reject</button></form></div>@else<span class="text-gray-500">Reviewed {{ $item->reviewed_at?->format('M d, Y') }}</span>@endif</td></tr>
@empty<tr><td colspan="5" class="p-8 text-center text-gray-500">No correction requests.</td></tr>@endforelse
</tbody></table></div><div class="p-4">{{ $requests->links() }}</div></div></div>
@endsection

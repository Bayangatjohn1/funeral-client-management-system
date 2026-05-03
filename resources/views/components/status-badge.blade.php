@props([
    'status' => null,
    'label' => null,
])

@php
    $text = trim((string) ($label ?? $status ?? '-'));
    $normalized = strtoupper(trim((string) ($status ?? $text)));

    if (in_array($normalized, ['PAID', 'COMPLETED', 'APPROVED', 'VERIFIED'], true)) {
        $variant = 'status-badge-success';
    } elseif (in_array($normalized, ['SUBMITTED', 'ONGOING', 'ACTIVE'], true)) {
        $variant = 'status-badge-info';
    } elseif (in_array($normalized, ['PARTIAL', 'PENDING', 'DRAFT'], true)) {
        $variant = 'status-badge-warning';
    } elseif (in_array($normalized, ['UNPAID', 'REJECTED', 'DISPUTED'], true)) {
        $variant = 'status-badge-danger';
    } else {
        $variant = 'status-badge-neutral';
    }
@endphp

<span {{ $attributes->merge(['class' => 'status-badge ' . $variant]) }}>
    {{ $text !== '' ? $text : '-' }}
</span>

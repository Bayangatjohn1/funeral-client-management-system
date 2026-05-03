@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'font-medium text-sm', 'style' => 'color: var(--color-success-text);']) }}>
        {{ $status }}
    </div>
@endif

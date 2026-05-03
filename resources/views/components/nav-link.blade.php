@props([
    'active' => false,
    'href' => null,
    'icon' => null,
])

@php
$activeStyle = 'background: rgba(62, 74, 61, 0.10); color: var(--color-primary); border-left-color: var(--color-primary);';
$inactiveStyle = 'background: transparent; color: var(--color-text-secondary); border-left-color: transparent;';

$classes = 'group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all border-l-4';

$iconStyle = ($active ?? false)
                ? 'color: var(--color-primary);'
                : 'color: var(--color-text-muted);';
@endphp

<a {{ $attributes->merge(['class' => $classes, 'href' => $href, 'style' => $active ? $activeStyle : $inactiveStyle]) }}>
    @if($icon)
        <span class="w-5 h-5 mr-3 flex-shrink-0" style="{{ $iconStyle }}" aria-hidden="true">
            {!! $icon !!}
        </span>
    @endif
    <span class="truncate">
        {{ $slot }}
    </span>
</a>

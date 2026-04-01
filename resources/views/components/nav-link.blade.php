@props([
    'active' => false,
    'href' => null,
    'icon' => null,
])

@php
$classes = ($active ?? false)
            ? 'group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg bg-orange-50 text-[#9C5A1A] transition-all border-l-4 border-[#9C5A1A]'
            : 'group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-all border-l-4 border-transparent';

$iconClasses = ($active ?? false)
                ? 'w-5 h-5 mr-3 flex-shrink-0 text-[#9C5A1A]'
                : 'w-5 h-5 mr-3 flex-shrink-0 text-slate-400 group-hover:text-slate-600';
@endphp

<a {{ $attributes->merge(['class' => $classes, 'href' => $href]) }}>
    @if($icon)
        <span class="{{ $iconClasses }}" aria-hidden="true">
            {!! $icon !!}
        </span>
    @endif
    <span class="truncate">
        {{ $slot }}
    </span>
</a>


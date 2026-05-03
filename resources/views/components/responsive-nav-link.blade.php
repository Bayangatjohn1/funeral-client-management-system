@props(['active'])

@php
$activeStyle = 'border-left-color: var(--color-primary); color: var(--color-primary); background: rgba(62, 74, 61, 0.10);';
$inactiveStyle = 'border-left-color: transparent; color: var(--color-text-secondary); background: transparent;';

$classes = 'block w-full ps-3 pe-4 py-2 border-l-4 text-start text-base font-medium focus:outline-none transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes, 'style' => $active ? $activeStyle : $inactiveStyle]) }}>
    {{ $slot }}
</a>

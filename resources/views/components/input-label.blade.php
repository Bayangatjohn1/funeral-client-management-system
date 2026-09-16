@props(['value'])

<label {{ $attributes->merge(['class' => 'ui-label block font-medium text-sm']) }}>
    {{ $value ?? $slot }}
</label>

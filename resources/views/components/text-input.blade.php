@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'rounded-md shadow-sm', 'style' => 'background: var(--color-bg-surface); border: 1px solid var(--color-border); color: var(--color-text-primary);', 'onfocus' => "this.style.borderColor='var(--color-primary)'; this.style.boxShadow='0 0 0 3px var(--color-focus-ring)'", 'onblur' => "this.style.borderColor='var(--color-border)'; this.style.boxShadow=''"]) }}>

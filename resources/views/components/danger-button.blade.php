<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2 border border-transparent rounded-md font-semibold text-xs uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-offset-2 transition ease-in-out duration-150', 'style' => 'background: var(--color-danger); color: #FAFAF7;', 'onmouseover' => "this.style.filter='brightness(0.92)'", 'onmouseout' => "this.style.filter=''"]) }}>
    {{ $slot }}
</button>

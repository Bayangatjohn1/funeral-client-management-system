@extends('layouts.panel')

@section('page_title', 'Package Management')

@section('content')
@if(session('success'))
    <div class="mb-4 bg-green-50 border p-3 text-green-700 rounded">
        {{ session('success') }}
    </div>
@endif

<div class="flex items-center justify-between">
    <h2 class="text-lg font-semibold">Service Packages</h2>
    <a href="{{ route('admin.packages.create') }}" class="inline-flex items-center gap-2 bg-[var(--brand-mid)] text-white px-4 py-2 rounded-lg text-sm font-semibold shadow-sm">
        <i class="bi bi-plus-circle"></i>
        Add Package
    </a>
</div>

<div class="mt-4 bg-white border rounded overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-gray-100">
            <tr>
                <th class="p-2 border text-left">Name</th>
                <th class="p-2 border text-left">Coffin Type</th>
                <th class="p-2 border text-left">Price</th>
                <th class="p-2 border text-left">Inclusions</th>
                <th class="p-2 border text-left">Freebies</th>
                <th class="p-2 border text-left">Promo</th>
                <th class="p-2 border text-left">Status</th>
                <th class="p-2 border text-left">Actions</th>
            </tr>
        </thead>
        <tbody>
        @forelse($packages as $package)
            <tr class="hover:bg-gray-50">
                <td class="p-2 border">{{ $package->name }}</td>
                <td class="p-2 border">{{ $package->coffin_type ?? '-' }}</td>
                <td class="p-2 border">
                    <form method="POST" action="{{ route('admin.packages.quickPrice', $package) }}" class="flex items-center gap-2">
                        @csrf
                        @method('PATCH')
                        <input type="number" step="0.01" min="0.01" name="price" value="{{ number_format($package->price, 2, '.', '') }}" class="w-28 border rounded px-2 py-1 text-sm">
                        <button class="border rounded px-2 py-1 text-xs">Save</button>
                    </form>
                </td>
                <td class="p-2 border">{{ \Illuminate\Support\Str::limit($package->inclusions ?? '-', 80) }}</td>
                <td class="p-2 border">{{ \Illuminate\Support\Str::limit($package->freebies ?? '-', 80) }}</td>
                <td class="p-2 border">
                    @if($package->promo_is_active && $package->promo_value_type && $package->promo_value)
                        <div class="text-xs font-semibold text-emerald-700">{{ $package->promo_label ?: 'Promo' }}</div>
                        <div class="text-xs text-gray-600">
                            {{ $package->promo_value_type === 'PERCENT'
                                ? number_format((float) $package->promo_value, 2) . '%'
                                : number_format((float) $package->promo_value, 2) }}
                        </div>
                        <div class="text-[11px] text-gray-500">
                            {{ $package->promo_starts_at?->format('Y-m-d H:i') ?? 'No start' }} -
                            {{ $package->promo_ends_at?->format('Y-m-d H:i') ?? 'No end' }}
                        </div>
                    @else
                        <span class="text-xs text-gray-500">No active promo</span>
                    @endif
                </td>
                <td class="p-2 border">
                    @if($package->is_active)
                        <span class="px-2 py-1 rounded text-xs bg-emerald-100 text-emerald-700">Active</span>
                    @else
                        <span class="px-2 py-1 rounded text-xs bg-gray-200 text-gray-700">Inactive</span>
                    @endif
                </td>
                <td class="p-2 border">
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('admin.packages.edit', $package) }}" data-url="{{ route('admin.packages.edit', $package) }}" class="action-chip action-chip-primary open-package-modal">
                            <i class="bi bi-pencil-square"></i><span>Edit</span>
                        </a>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="8" class="p-3 text-center text-gray-500">No packages yet.</td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>

<!-- Package modal -->
<div id="packageModalOverlay" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/50 backdrop-blur-sm transition-opacity duration-200">
    <div id="packageModalSheet" class="relative w-[92vw] max-w-5xl max-h-[92vh] bg-white rounded-2xl shadow-2xl overflow-hidden transform transition-all duration-200 scale-95 opacity-0 border border-slate-100">
        <button id="packageModalClose" type="button" class="absolute top-3 right-3 z-10 inline-flex items-center justify-center w-9 h-9 rounded-full bg-white shadow border text-slate-400 hover:text-black focus:outline-none">
            <i class="bi bi-x-lg"></i>
        </button>
        <div id="packageModalContent" class="overflow-y-auto max-h-[84vh] p-5 bg-slate-50">
            <div class="flex items-center justify-center py-8 text-slate-500 gap-2 text-sm">
                <i class="bi bi-arrow-repeat animate-spin"></i>
                <span>Loading...</span>
            </div>
        </div>
    </div>
</div>

<script>
    (() => {
        const overlay = document.getElementById('packageModalOverlay');
        const sheet = document.getElementById('packageModalSheet');
        const content = document.getElementById('packageModalContent');
        const closeBtn = document.getElementById('packageModalClose');
        const links = [...document.querySelectorAll('.open-package-modal')];

        const show = () => {
            overlay.classList.remove('hidden');
            requestAnimationFrame(() => {
                sheet.classList.remove('scale-95', 'opacity-0');
                sheet.classList.add('scale-100', 'opacity-100');
                overlay.classList.add('opacity-100');
            });
        };

        const hide = () => {
            sheet.classList.add('scale-95', 'opacity-0');
            sheet.classList.remove('scale-100', 'opacity-100');
            overlay.classList.remove('opacity-100');
            setTimeout(() => {
                overlay.classList.add('hidden');
                content.innerHTML = `
                    <div class="flex items-center justify-center py-8 text-slate-500 gap-2 text-sm">
                        <i class="bi bi-arrow-repeat animate-spin"></i>
                        <span>Loading...</span>
                    </div>`;
            }, 180);
        };

        const load = async (url) => {
            content.innerHTML = `
                <div class="flex items-center justify-center py-8 text-slate-500 gap-2 text-sm">
                    <i class="bi bi-arrow-repeat animate-spin"></i>
                    <span>Loading...</span>
                </div>`;
            try {
                const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                const html = await res.text();
                const doc = new DOMParser().parseFromString(html, 'text/html');
                const form = doc.querySelector('#packageEditForm');
                if (form) {
                    content.innerHTML = form.outerHTML;
                    const scripts = [...doc.querySelectorAll('script')];
                    scripts.forEach((old) => {
                        const s = document.createElement('script');
                        if (old.src) s.src = old.src; else s.textContent = old.textContent;
                        content.appendChild(s);
                    });
                } else {
                    content.innerHTML = html;
                }
            } catch (e) {
                content.innerHTML = `<div class="p-4 text-sm text-rose-600">Unable to load content.</div>`;
            }
        };

        links.forEach((link) => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const url = link.dataset.url || link.href;
                show();
                load(url);
            });
        });

        if (closeBtn) closeBtn.addEventListener('click', hide);
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) hide();
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !overlay.classList.contains('hidden')) hide();
        });
    })();
</script>
@endsection

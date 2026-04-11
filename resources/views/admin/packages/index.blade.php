@extends('layouts.panel')

@section('page_title', 'Package Management')
@section('page_desc', 'Manage funeral service packages, pricing, and availability.')

@section('content')
<div class="admin-table-page">
    @if(session('success'))
        <div class="flash-success">
            {{ session('success') }}
        </div>
    @endif

    <section class="table-system-card admin-table-card">
        <div class="table-system-head">
            <div class="admin-table-head-row">
                <div>
                    <h2 class="table-system-title">Service Packages</h2>
                    <p class="admin-table-head-copy">Keep package pricing and promo details aligned in one clean table workflow.</p>
                </div>
                <div class="admin-table-head-actions">
                    <a href="{{ route('admin.packages.create') }}" class="btn btn-primary-custom btn-sm bg-[var(--brand-mid)] border-[var(--brand-mid)] hover:bg-[var(--brand-hover)] hover:border-[var(--brand-hover)] text-white inline-flex items-center gap-2">
                        <i class="bi bi-plus-circle"></i>
                        <span>Add Package</span>
                    </a>
                </div>
            </div>
        </div>

        <div class="table-system-list">
            <div class="table-wrapper table-system-wrap">
                <table class="table-base table-system-table">
                    <thead>
                        <tr>
                            <th class="text-left">Name</th>
                            <th class="text-left">Coffin Type</th>
                            <th class="text-left">Price</th>
                            <th class="text-left">Inclusions</th>
                            <th class="text-left">Freebies</th>
                            <th class="text-left">Promo</th>
                            <th class="text-left">Status</th>
                            <th class="table-col-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($packages as $package)
                        <tr>
                            <td class="table-primary">{{ $package->name }}</td>
                            <td>{{ $package->coffin_type ?? '-' }}</td>
                            <td>
                                <form method="POST" action="{{ route('admin.packages.quickPrice', $package) }}" class="flex items-center gap-2">
                                    @csrf
                                    @method('PATCH')
                                    <input type="number" step="0.01" min="0.01" name="price" value="{{ number_format($package->price, 2, '.', '') }}" class="admin-table-input-inline">
                                    <button class="admin-table-save-btn" type="submit">Save</button>
                                </form>
                            </td>
                            <td class="table-secondary">{{ \Illuminate\Support\Str::limit($package->inclusions ?? '-', 80) }}</td>
                            <td class="table-secondary">{{ \Illuminate\Support\Str::limit($package->freebies ?? '-', 80) }}</td>
                            <td>
                                @if($package->promo_is_active && $package->promo_value_type && $package->promo_value)
                                    <div class="text-xs font-semibold text-emerald-700">{{ $package->promo_label ?: 'Promo' }}</div>
                                    <div class="table-secondary">
                                        {{ $package->promo_value_type === 'PERCENT'
                                            ? number_format((float) $package->promo_value, 2) . '%'
                                            : number_format((float) $package->promo_value, 2) }}
                                    </div>
                                    <div class="text-[11px] text-slate-500">
                                        {{ $package->promo_starts_at?->format('Y-m-d H:i') ?? 'No start' }} -
                                        {{ $package->promo_ends_at?->format('Y-m-d H:i') ?? 'No end' }}
                                    </div>
                                @else
                                    <span class="table-secondary">No active promo</span>
                                @endif
                            </td>
                            <td>
                                @if($package->is_active)
                                    <span class="status-badge status-badge-success">Active</span>
                                @else
                                    <span class="status-badge status-badge-neutral">Inactive</span>
                                @endif
                            </td>
                            <td class="table-col-actions">
                                <div class="row-action-menu" data-row-menu>
                                    <button
                                        type="button"
                                        class="row-action-trigger"
                                        data-row-menu-trigger
                                        aria-haspopup="menu"
                                        aria-expanded="false"
                                        aria-label="Open row actions"
                                    >
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>

                                    <div class="row-action-dropdown" role="menu">
                                        <a href="{{ route('admin.packages.edit', $package) }}" data-url="{{ route('admin.packages.edit', $package) }}" class="row-action-item open-package-modal" data-row-menu-item>
                                            <i class="bi bi-pencil-square"></i>
                                            <span>Edit package</span>
                                        </a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="table-system-empty">No packages yet.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="table-system-pagination">
                {{ $packages->links() }}
            </div>
        </div>
    </section>
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

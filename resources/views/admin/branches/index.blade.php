@extends('layouts.panel')

@section('page_title','Branch Management')

@section('content')
@if (session('success'))
    <div class="mb-4 rounded bg-green-50 border border-green-200 p-3 text-green-800">
        {{ session('success') }}
    </div>
@endif

<a href="{{ route('admin.branches.create', ['return_to' => request()->fullUrl()]) }}" class="inline-flex items-center gap-2 bg-[var(--brand-mid)] text-white px-4 py-2 rounded-lg text-sm font-semibold shadow-sm">
    <i class="bi bi-plus-circle"></i>
    Add Branch
</a>

<div class="mt-4 overflow-x-auto bg-white border rounded">
    <table class="w-full text-sm">
        <thead class="bg-gray-100">
            <tr>
                <th class="p-2 border text-left">Branch ID</th>
                <th class="p-2 border text-left">Branch Name</th>
                <th class="p-2 border text-left">Address</th>
                <th class="p-2 border text-left">Total Records Encoded</th>
                <th class="p-2 border text-left">Status</th>
                <th class="p-2 border text-left">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($branches as $branch)
                <tr class="hover:bg-gray-50">
                    <td class="p-2 border">{{ $branch->branch_code }}</td>
                    <td class="p-2 border">{{ $branch->branch_name }}</td>
                    <td class="p-2 border">{{ $branch->address ?? '-' }}</td>
                    <td class="p-2 border">{{ $branch->funeral_cases_count }}</td>
                    <td class="p-2 border">
                        @if($branch->is_active)
                            <span class="text-green-700 font-medium">Active</span>
                        @else
                            <span class="text-red-700 font-medium">Inactive</span>
                        @endif
                    </td>
                    <td class="p-2 border">
                        <div class="flex flex-wrap gap-2">
                            <a class="action-chip action-chip-primary open-branch-modal" data-url="{{ route('admin.branches.edit', ['branch' => $branch, 'return_to' => request()->fullUrl()]) }}" href="{{ route('admin.branches.edit', ['branch' => $branch, 'return_to' => request()->fullUrl()]) }}">
                                <i class="bi bi-pencil-square"></i><span>Edit</span>
                            </a>
                            <form method="POST" action="{{ route('admin.branches.toggleStatus', $branch) }}" class="inline">
                                @csrf
                                @method('PATCH')
                                <button class="action-chip" type="submit">
                                    <i class="bi bi-toggle-{{ $branch->is_active ? 'off' : 'on' }}"></i>
                                    <span>{{ $branch->is_active ? 'Disable' : 'Enable' }}</span>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="p-3 text-center text-gray-500">No branches found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Branch edit modal -->
<div id="branchModalOverlay" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/50 backdrop-blur-sm transition-opacity duration-200">
    <div id="branchModalSheet" class="relative w-[92vw] max-w-4xl max-h-[92vh] bg-white rounded-2xl shadow-2xl overflow-hidden transform transition-all duration-200 scale-95 opacity-0 border border-slate-100">
        <button id="branchModalClose" type="button" class="absolute top-3 right-3 z-10 inline-flex items-center justify-center w-9 h-9 rounded-full bg-white shadow border text-slate-400 hover:text-black focus:outline-none">
            <i class="bi bi-x-lg"></i>
        </button>
        <div id="branchModalContent" class="overflow-y-auto max-h-[84vh] p-5 bg-slate-50">
            <div class="flex items-center justify-center py-8 text-slate-500 gap-2 text-sm">
                <i class="bi bi-arrow-repeat animate-spin"></i>
                <span>Loading...</span>
            </div>
        </div>
    </div>
</div>

<script>
    (() => {
        const overlay = document.getElementById('branchModalOverlay');
        const sheet = document.getElementById('branchModalSheet');
        const content = document.getElementById('branchModalContent');
        const closeBtn = document.getElementById('branchModalClose');
        const links = [...document.querySelectorAll('.open-branch-modal')];

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
                const form = doc.querySelector('#branchEditForm');
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


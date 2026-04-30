@extends('layouts.panel')

@section('page_title','User Management')
@section('page_desc', 'Manage system users, roles, and account access.')

@section('content')
<div class="admin-table-page font-ui-body">
    @if (session('success'))
        <div class="flash-success">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="flash-error">
            {{ session('error') }}
        </div>
    @endif

    <section class="table-system-card admin-table-card">
        <div class="table-system-head">
            <div class="admin-table-head-row">
                <div>
                    <h2 class="table-system-title">User Management</h2>
                    <p class="admin-table-head-copy">Manage accounts, branch assignments, and activation status in one consistent workspace.</p>
                </div>
                <div class="admin-table-head-actions">
                    <a
                        href="{{ route('admin.users.create', ['return_to' => request()->fullUrl()]) }}"
                        data-user-modal-trigger
                        data-url="{{ route('admin.users.create', ['return_to' => request()->fullUrl()]) }}"
                        class="btn btn-primary-custom btn-sm bg-[var(--brand-mid)] border-[var(--brand-mid)] hover:bg-[var(--brand-hover)] hover:border-[var(--brand-hover)] text-white inline-flex items-center gap-2"
                    >
                        <i class="bi bi-plus-circle"></i>
                        <span>Add User</span>
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
                            <th class="text-left">Email</th>
                            <th class="text-left">Role</th>
                            <th class="text-left">Branch</th>
                            <th class="text-left">Position</th>
                            <th class="text-left">Contact</th>
                            <th class="text-left">Status</th>
                            <th class="table-col-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            <tr>
                                <td class="table-primary">{{ $user->name }}</td>
                                <td class="table-secondary">{{ $user->email }}</td>
                                <td>{{ $user->roleLabel() }}</td>
                                <td>{{ $user->branch->branch_name ?? '-' }}</td>
                                <td class="table-secondary">{{ $user->position ?? '-' }}</td>
                                <td>{{ $user->contact_number ?? '-' }}</td>
                                <td>
                                    @if($user->is_active)
                                        <span class="status-badge status-badge-success">Active</span>
                                    @else
                                        <span class="status-badge status-badge-danger">Inactive</span>
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
                                            <a
                                                class="row-action-item"
                                                data-user-modal-trigger
                                                data-row-menu-item
                                                data-url="{{ route('admin.users.edit', ['user' => $user, 'return_to' => request()->fullUrl()]) }}"
                                                href="{{ route('admin.users.edit', ['user' => $user, 'return_to' => request()->fullUrl()]) }}"
                                            >
                                                <i class="bi bi-pencil-square"></i>
                                                <span>Edit user</span>
                                            </a>

                                            <form method="POST" action="{{ route('admin.users.toggleActive', $user) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button class="row-action-item" type="submit" data-row-menu-item>
                                                    <i class="bi bi-toggle-{{ $user->is_active ? 'off' : 'on' }}"></i>
                                                    <span>{{ $user->is_active ? 'Deactivate user' : 'Activate user' }}</span>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="table-system-empty">No users found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="table-system-pagination">
                {{ $users->links() }}
            </div>
        </div>
    </section>
</div>

<!-- User modal -->
<div id="userModalOverlay" class="fixed inset-0 hidden flex items-center justify-center bg-black/60 backdrop-blur-sm transition-opacity duration-200 font-ui-body" style="z-index: 1300;">
    <div id="userModalSheet" class="relative w-[92vw] max-w-4xl max-h-[92vh] bg-white rounded-2xl shadow-2xl overflow-hidden transform transition-all duration-200 scale-95 opacity-0 border border-slate-200 font-ui-body">
        <button id="userModalClose" type="button" class="absolute top-4 right-4 z-20 inline-flex items-center justify-center w-9 h-9 rounded-xl bg-white border border-slate-200 text-slate-500 hover:text-slate-900 hover:bg-slate-50 transition-colors focus:outline-none shadow-sm">
            <i class="bi bi-x-lg" style="font-size:.8rem"></i>
        </button>
        <div id="userModalContent" class="overflow-y-auto max-h-[84vh] p-0 bg-white font-ui-body">
            <div class="flex flex-col items-center justify-center py-16 gap-3">
                <div class="w-7 h-7 rounded-full border-2 border-slate-200 border-t-slate-500 animate-spin"></div>
                <span class="text-sm text-slate-400">Loading...</span>
            </div>
        </div>
    </div>
</div>

<script>
    (() => {
        const overlay = document.getElementById('userModalOverlay');
        const sheet = document.getElementById('userModalSheet');
        const content = document.getElementById('userModalContent');
        const closeBtn = document.getElementById('userModalClose');
        const links = [...document.querySelectorAll('[data-user-modal-trigger]')];
        const normalizeEmbeddedForm = (form) => {
            form.classList.remove('mx-auto', 'max-w-4xl', 'max-w-3xl');
            form.classList.add('w-full');
            form.querySelectorAll('.modal-shell-card').forEach((card) => {
                card.classList.remove('rounded-2xl', 'border', 'border-slate-200', 'shadow-sm');
                card.classList.add('rounded-none', 'border-0', 'shadow-none', 'bg-transparent');
            });
            form.querySelectorAll('.modal-shell-card > .px-6.py-5, .modal-shell-card > .p-5, .modal-shell-card > .p-6').forEach((head) => {
                head.classList.add('pr-20');
            });
        };
        const lockScroll = () => {
            document.documentElement.classList.add('overflow-hidden');
            document.body.classList.add('overflow-hidden');
        };
        const unlockScroll = () => {
            document.documentElement.classList.remove('overflow-hidden');
            document.body.classList.remove('overflow-hidden');
        };

        const show = () => {
            overlay.classList.remove('hidden');
            lockScroll();
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
                unlockScroll();
                content.innerHTML = `
                    <div class="flex flex-col items-center justify-center py-16 gap-3">
                        <div class="w-7 h-7 rounded-full border-2 border-slate-200 border-t-slate-500 animate-spin"></div>
                        <span class="text-sm text-slate-400">Loading...</span>
                    </div>`;
            }, 180);
        };

        const load = async (url) => {
            content.innerHTML = `
                <div class="flex flex-col items-center justify-center py-16 gap-3">
                    <div class="w-7 h-7 rounded-full border-2 border-slate-200 border-t-slate-500 animate-spin"></div>
                    <span class="text-sm text-slate-400">Loading...</span>
                </div>`;
            try {
                const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                const html = await res.text();
                const doc = new DOMParser().parseFromString(html, 'text/html');
                const form = doc.querySelector('#userCreateForm, #userEditForm');
                if (form) {
                    content.innerHTML = form.outerHTML;
                    const embeddedForm = content.querySelector('#userCreateForm, #userEditForm');
                    if (embeddedForm) normalizeEmbeddedForm(embeddedForm);
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

<dialog id="create-role-dialog" class="create-role-dialog" aria-labelledby="create-role-title" aria-describedby="create-role-description">
    <header class="create-role-header">
        <div><h2 id="create-role-title">Create user account</h2><p id="create-role-description">Choose the account type to continue.</p></div>
        <button type="button" data-close-role-dialog aria-label="Close account type selection"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
    </header>
    <div class="create-role-options">
        @foreach([
            ['role' => 'staff', 'title' => 'Staff', 'icon' => 'bi-person', 'description' => 'Handle daily operations in an assigned branch.'],
            ['role' => 'branch_admin', 'title' => 'Branch Admin', 'icon' => 'bi-people', 'description' => 'Manage staff and operations in an assigned branch.'],
            ['role' => 'system_admin', 'title' => 'System Admin', 'icon' => 'bi-shield-lock', 'description' => 'Manage users, branches, and system settings. Uses a separate personal login.'],
        ] as $choice)
            <a class="create-role-option" href="{{ $choice['role'] === 'system_admin' ? route('admin.system-admins.create', ['return_to' => request()->fullUrl()]) : route('admin.users.create', ['role' => $choice['role'], 'return_to' => request()->fullUrl()]) }}">
                <i class="bi {{ $choice['icon'] }} create-role-icon" aria-hidden="true"></i>
                <span><strong>{{ $choice['title'] }}</strong><span>{{ $choice['description'] }}</span></span>
                <i class="bi bi-arrow-right" aria-hidden="true"></i>
            </a>
        @endforeach
    </div>
    <p class="create-role-note"><i class="bi bi-lock" aria-hidden="true"></i> All three roles require authenticator MFA at sign-in.</p>
</dialog>
<style>
.create-role-dialog { width: min(540px, calc(100% - 32px)); max-height: calc(100dvh - 48px); margin: auto; padding: 24px; border: 1px solid var(--border); border-radius: 16px; background: var(--card); color: var(--ink); overflow-y: auto; box-shadow: 0 24px 70px rgb(0 0 0 / .2); }
.create-role-dialog::backdrop { background: rgb(0 0 0 / .45); }
.create-role-header { display: flex; justify-content: space-between; gap: 16px; margin-bottom: 22px; }
.create-role-header h2 { font-size: 22px; font-weight: 700; line-height: 1.3; }
.create-role-header p, .create-role-option span span, .create-role-note { color: var(--ink-muted); font-size: 14px; line-height: 1.5; }
.create-role-header p { margin-top: 6px; }
.create-role-header button { width: 44px; height: 44px; border-radius: 8px; flex-shrink: 0; }
.create-role-options { display: grid; gap: 12px; }
.create-role-option { display: flex; align-items: center; gap: 16px; border: 1px solid var(--border); border-radius: 10px; padding: 18px 16px; color: var(--ink); text-decoration: none; }
.create-role-option > span { flex: 1; min-width: 0; }
.create-role-option strong, .create-role-option span span { display: block; }
.create-role-option strong { font-size: 16px; margin-bottom: 4px; }
.create-role-option:hover, .create-role-header button:hover { background: var(--surface-muted); }
.create-role-option:focus-visible, .create-role-header button:focus-visible { outline: 2px solid var(--brand-mid); outline-offset: 3px; }
.create-role-icon { font-size: 24px; color: var(--ink-muted); }
.create-role-note { display: flex; align-items: flex-start; gap: 8px; margin-top: 20px; font-size: 12px; }
@media(max-width: 480px) { .create-role-dialog { padding: 20px; } .create-role-option { gap: 12px; padding: 14px 12px; } }
</style>
<script>
(() => {
    const dialog = document.getElementById('create-role-dialog');
    if (!dialog || typeof dialog.showModal !== 'function') return;
    let trigger;
    document.querySelectorAll('[data-create-role-trigger]').forEach(link => link.addEventListener('click', event => {
        event.preventDefault(); trigger = link; dialog.showModal();
    }));
    dialog.querySelector('[data-close-role-dialog]').addEventListener('click', () => dialog.close());
    dialog.addEventListener('click', event => {
        const bounds = dialog.getBoundingClientRect();
        if (event.target === dialog && (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom)) dialog.close();
    });
    dialog.addEventListener('close', () => trigger?.focus());
    if (window.location.hash === '#create-user') {
        trigger = document.querySelector('[data-create-role-trigger]');
        dialog.showModal();
        window.history.replaceState(window.history.state, '', window.location.pathname + window.location.search);
    }
})();
</script>

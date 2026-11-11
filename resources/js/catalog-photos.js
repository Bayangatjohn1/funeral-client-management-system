import '../css/catalog-photos.css';

const mounted = new WeakSet();
function mount() {
    document.querySelectorAll('[data-catalog-photo]').forEach(editor => {
        if (mounted.has(editor)) return;
        mounted.add(editor);
        const file = editor.querySelector('input[name=catalog_photo]');
        const remove = editor.querySelector('[data-catalog-photo-remove]');
        const current = editor.querySelector('[data-catalog-photo-current]');
        const undo = editor.querySelector('[data-catalog-photo-undo]');
        const save = editor.closest('form')?.querySelector('[data-catalog-photo-save]');
        const sync = () => {
            if (save) save.disabled = remove.value !== '1' && !file.files.length;
            if (current) current.hidden = remove.value === '1' || file.files.length > 0;
            if (undo) undo.hidden = remove.value !== '1' || file.files.length > 0;
            const label = editor.querySelector('[data-proof-file-label]');
            if (label && current && !current.hidden) label.textContent = 'Replace Photo';
        };
        editor.querySelector('[data-catalog-photo-remove-saved]')?.addEventListener('click', () => {
            remove.value = '1'; remove.dispatchEvent(new Event('input', {bubbles:true})); sync();
        });
        editor.querySelector('[data-catalog-photo-undo-button]')?.addEventListener('click', () => {
            remove.value = '0'; remove.dispatchEvent(new Event('input', {bubbles:true})); sync();
        });
        file.addEventListener('change', () => queueMicrotask(sync));
        sync();
    });
}
document.addEventListener('DOMContentLoaded', mount);
new MutationObserver(mount).observe(document.documentElement, {childList:true, subtree:true});
mount();

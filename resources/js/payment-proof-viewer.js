import '../css/payment-proof-viewer.css';

let activePreview;

function openPreview(link) {
    if (activePreview) return;
    const mime = link.dataset.proofMime;
    const pdf = mime === 'application/pdf';
    const catalogPhoto = link.dataset.proofTitle === 'Catalog Photo';
    const dialog = document.createElement('dialog');
    dialog.className = 'proof-viewer';
    dialog.setAttribute('aria-labelledby', 'proofViewerTitle');
    if (document.querySelector('dialog[open],.pm-transactions-backdrop.open,.pm-modal-backdrop.open') || window.frameElement?.closest('dialog[open]')) {
        dialog.classList.add('proof-viewer-shared-scrim');
    }
    dialog.innerHTML = '<header><h2 id="proofViewerTitle">Payment Proof</h2><button type="button" data-proof-viewer-close aria-label="Close proof preview"><i class="bi bi-x-lg" aria-hidden="true"></i></button></header><div class="proof-viewer-toolbar"><button type="button" data-zoom-out aria-label="Zoom out">−</button><output aria-live="polite">100%</output><button type="button" data-zoom-in aria-label="Zoom in">+</button><button type="button" data-zoom-fit>Fit to View</button></div><div class="proof-viewer-body" aria-busy="true"><p role="status">Loading proof…</p></div><footer><span></span></footer>';
    const body = dialog.querySelector('.proof-viewer-body');
    if (catalogPhoto) {
        dialog.querySelector('h2').textContent = 'Catalog Photo';
        dialog.querySelector('[data-proof-viewer-close]').setAttribute('aria-label', 'Close photo preview');
        body.querySelector('p').textContent = 'Loading photo…';
    }
    const message = body.querySelector('p');
    const zoomOut = dialog.querySelector('[data-zoom-out]');
    const zoomIn = dialog.querySelector('[data-zoom-in]');
    const output = dialog.querySelector('output');
    const media = document.createElement(pdf ? 'iframe' : 'img');
    let scale = 1;
    const fit = () => Math.min(media.naturalWidth, body.clientWidth - 32, (body.clientHeight - 32) * media.naturalWidth / media.naturalHeight);
    const zoom = value => {
        scale = Math.max(1, Math.min(4, value));
        media.style.width = `${fit() * scale}px`;
        output.textContent = `${Math.round(scale * 100)}%`;
        zoomOut.disabled = scale === 1;
        zoomIn.disabled = scale === 4;
    };
    const resize = new ResizeObserver(() => {
        if (!pdf && media.naturalWidth) zoom(scale);
    });
    resize.observe(body);
    const close = () => {
        resize.disconnect();
        dialog.close();
        media.src = '';
        dialog.remove();
        activePreview = null;
        if (link.isConnected) link.focus({preventScroll:true});
    };
    dialog.querySelector('[data-proof-viewer-close]').onclick = close;
    dialog.addEventListener('cancel', event => { event.preventDefault(); close(); });
    dialog.addEventListener('keydown', event => event.stopPropagation());
    dialog.addEventListener('click', event => {
        if (event.target !== dialog) return;
        const rect = dialog.getBoundingClientRect();
        if (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom) close();
    });
    media.onload = () => {
        message.hidden = true;
        body.setAttribute('aria-busy', 'false');
        if (!pdf) {
            zoom(1);
            zoomIn.disabled = false;
            dialog.querySelector('[data-zoom-fit]').disabled = false;
        }
    };
    media.onerror = () => {
        body.setAttribute('aria-busy', 'false');
        media.hidden = true;
        message.hidden = false;
        message.setAttribute('role', 'alert');
        message.textContent = catalogPhoto ? 'Unable to display this photo. Close the preview and try again.' : 'Unable to display this proof. Close the preview and try again.';
    };
    dialog.querySelectorAll('.proof-viewer-toolbar button').forEach(button => button.disabled = true);
    if (pdf) {
        dialog.querySelector('.proof-viewer-toolbar').hidden = true;
        media.title = 'Payment proof PDF';
        dialog.querySelector('footer span').textContent = 'Use the PDF viewer controls to zoom or change pages.';
        const fallback = document.createElement('a');
        fallback.href = link.href;
        fallback.target = '_blank';
        fallback.rel = 'noopener';
        fallback.textContent = 'Open PDF';
        dialog.querySelector('footer').append(fallback);
    } else {
        media.alt = catalogPhoto ? 'Catalog item photo' : 'Receipt or transaction confirmation';
        dialog.querySelector('footer span').textContent = catalogPhoto ? 'Use zoom or Fit to View to inspect the photo.' : 'Zoom in to read the reference, amount and date. Scroll to move around the image.';
    }
    zoomOut.onclick = () => zoom(scale - .5);
    zoomIn.onclick = () => zoom(scale + .5);
    dialog.querySelector('[data-zoom-fit]').onclick = () => { zoom(1); body.scrollTo(0, 0); };
    body.append(media);
    document.body.append(dialog);
    activePreview = dialog;
    dialog.showModal();
    dialog.querySelector('[data-proof-viewer-close]').focus({preventScroll:true});
    media.src = link.href;
}

document.addEventListener('click', event => {
    const link = event.target.closest('a[data-proof-viewer],a[data-proof-preview],a[data-proof-review]');
    if (!link || !link.hasAttribute('href') || !['image/jpeg', 'image/png', 'application/pdf'].includes(link.dataset.proofMime)
        || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || event.button !== 0) return;
    event.preventDefault();
    openPreview(link);
});

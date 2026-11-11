@php
    $attachmentLabel = match ($upload->context) {
        'senior' => 'Senior ID / Certificate',
        'deceased' => 'Deceased Photo',
        'catalog' => 'Catalog Photo',
        default => 'Payment Proof',
    };
    $isIntakeAttachment = in_array($upload->context, ['senior', 'deceased'], true);
    $formats = ['image/jpeg', 'image/png'];
    if ($isIntakeAttachment) $formats[] = 'image/webp';
    if (!in_array($upload->context, ['deceased', 'catalog', 'tarpaulin'], true)) $formats[] = 'application/pdf';
    $limit = $upload->context === 'deceased' ? 3 : 5;
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="referrer" content="no-referrer">
    <title>Upload {{ $attachmentLabel }}</title>
    <style>
        *{box-sizing:border-box} body{margin:0;background:#e4eee7;color:#214633;font:16px/1.5 system-ui,sans-serif;padding:24px 16px}
        main{max-width:480px;margin:auto;background:#f5f9f6;border-radius:16px;padding:24px}h1{font-size:23px;margin:0 0 12px}p{font-size:14px}
        .actions{display:flex;flex-wrap:wrap;gap:10px}button{font:inherit;cursor:pointer;min-height:46px;border:0;border-radius:8px;padding:10px 16px;color:#214633;background:#d4e6da}
        button[type=submit]{background:#b1d0bc;font-weight:600;width:100%;margin-top:16px}button:focus-visible{outline:2px solid #214633;outline-offset:3px}button:disabled{opacity:.5;cursor:wait}
        img{max-width:100%;max-height:45dvh;object-fit:contain;margin-top:16px}#name{overflow-wrap:anywhere}#error{padding:10px;border-radius:8px;background:#fbe8e6;color:#8d2925;font-weight:600}
        progress{width:100%;accent-color:#438561}[hidden]{display:none!important}
    </style>
</head>
<body><main>
    <h1>Upload {{ $attachmentLabel }}</h1>
    <p id="instructions">{{ $upload->context === 'catalog' ? 'Take a clear photo of the item or choose an existing photo. Review it before sending it to the catalog form.' : ($isIntakeAttachment ? 'Take a clear photo or choose a file to send to the intake form.' : 'Photograph the receipt or choose a transaction confirmation. Keep the reference, amount and date readable.') }}</p>
    <form id="phoneProofForm">
        <div class="actions">
            <button type="button" id="camera">Take Photo</button>
            <button type="button" id="choose">Choose File</button>
        </div>
        <input id="capture" type="file" accept="image/jpeg,image/png" capture="environment" hidden>
        <input id="file" type="file" accept="{{ implode(',', $formats) }}" hidden>
        <p>{{ $upload->context === 'catalog' ? 'JPG or PNG' : ($isIntakeAttachment ? 'JPG, PNG or WebP'.($limit === 5 ? ', or PDF' : '') : 'JPG, PNG or PDF') }} · up to {{ $limit }} MB.</p>
        <img id="preview" alt="Selected proof preview" hidden>
        <p id="name"></p>
        <p id="error" role="alert" hidden></p>
        <progress id="progress" max="100" aria-label="Uploading proof" hidden></progress>
        <button type="submit" id="send" disabled>Send to Form</button>
    </form>
    <p id="status" role="status" aria-live="polite"></p>
</main>
<script>
(() => {
    const form = document.getElementById('phoneProofForm');
    const preview = document.getElementById('preview');
    const error = document.getElementById('error');
    const status = document.getElementById('status');
    const send = document.getElementById('send');
    const progress = document.getElementById('progress');
    let selected, previewUrl, sending = false;
    const fail = message => { error.hidden = false; error.textContent = message; };
    document.getElementById('camera').onclick = () => document.getElementById('capture').click();
    document.getElementById('choose').onclick = () => document.getElementById('file').click();
    for (const field of form.querySelectorAll('input')) field.onchange = () => {
        selected = field.files[0]; error.hidden = true; send.disabled = true;
        preview.hidden = true; document.getElementById('name').textContent = '';
        if (previewUrl) URL.revokeObjectURL(previewUrl);
        if (!selected) return;
        if (!@json($formats).includes(selected.type)) return fail('Choose a supported file type for this attachment.');
        if (selected.size > {{ $limit }} * 1024 * 1024) return fail('The file must be {{ $limit }} MB or smaller.');
        document.getElementById('name').textContent = selected.name;
        if (selected.type.startsWith('image/')) { previewUrl = URL.createObjectURL(selected); preview.src = previewUrl; preview.hidden = false; }
        document.getElementById('camera').textContent = 'Retake Photo';
        send.disabled = false;
    };
    form.onsubmit = event => {
        event.preventDefault();
        if (!selected || sending) return;
        sending = true; error.hidden = true; progress.hidden = false; progress.value = 0;
        form.querySelectorAll('button').forEach(button => button.disabled = true);
        const body = new FormData(); body.append('proof', selected);
        const xhr = new XMLHttpRequest(); xhr.open('POST', location.href); xhr.timeout = 120000;
        xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name=csrf-token]').content);
        xhr.setRequestHeader('Accept','application/json');
        const retry = message => {
            sending = false; progress.hidden = true;
            form.querySelectorAll('button').forEach(button => button.disabled = false);
            send.textContent = 'Retry Upload'; fail(message);
        };
        xhr.upload.onprogress = e => { if (e.lengthComputable) progress.value = e.loaded / e.total * 100; };
        xhr.onerror = xhr.ontimeout = () => retry('Connection interrupted. Check the desktop for the proof, or retry while the QR is valid.');
        xhr.onload = () => {
            if (xhr.status >= 200 && xhr.status < 300) {
                form.hidden = true; document.getElementById('instructions').hidden = true;
                status.textContent = @json($upload->context === 'catalog' ? 'Photo sent. Return to the desktop to review it and save the form. You can close this page.' : ($isIntakeAttachment ? 'File sent. Return to the desktop to review it and save the case.' : 'Proof sent. Return to the desktop to check it and save the payment. You can close this page.'));
                if (previewUrl) URL.revokeObjectURL(previewUrl);
            } else {
                let data = {}; try { data = JSON.parse(xhr.responseText); } catch {}
                retry([403,410,419].includes(xhr.status) ? 'This link expired or was already used. Check the desktop or generate a new QR.' : Object.values(data.errors || {}).flat()[0] || data.message || 'Upload failed. Try again.');
            }
        };
        xhr.send(body);
    };
})();
</script>
</body></html>

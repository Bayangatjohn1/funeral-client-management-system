@extends('layouts.panel')

@section('page_title', 'Client Record')
@section('page_desc', 'Review client profile, linked cases, and contact details.')

@section('content')
@include('staff.clients.partials.show-content')

<script>
    (() => {
        const btn = document.getElementById('printClientBtn');
        const source = document.getElementById('clientViewContent');
        if (!btn || !source) return;
        btn.addEventListener('click', () => {
            const iframe = document.createElement('iframe');
            iframe.style.position = 'fixed';
            iframe.style.right = '0';
            iframe.style.bottom = '0';
            iframe.style.width = '0';
            iframe.style.height = '0';
            iframe.style.border = '0';
            document.body.appendChild(iframe);
            const doc = iframe.contentWindow.document;
            doc.open();
            doc.write('<!doctype html><html><head>');
            document.querySelectorAll('link[rel="stylesheet"]').forEach((link) => {
                if (link.href) doc.write(`<link rel="stylesheet" href="${link.href}">`);
            });
            document.querySelectorAll('style').forEach((style) => {
                doc.write('<style>' + style.innerHTML + '</style>');
            });
            doc.write('</head><body>');
            doc.write(source.outerHTML);
            doc.write('</body></html>');
            doc.close();
            iframe.onload = () => {
                iframe.contentWindow.focus();
                iframe.contentWindow.print();
                setTimeout(() => iframe.remove(), 500);
            };
        });
    })();
</script>
@endsection

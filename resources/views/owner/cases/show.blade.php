@extends('layouts.panel')

@section('page_title', 'Case Full Information')
@section('hide_layout_topbar', '1')
@section('hide_page_title_icon', '1')
@section('page_desc', 'Full case information, financials, and transaction history.')

@section('content')
@php
    $defaultReturnUrl = route('owner.history');
    $requestedReturnUrl = request()->query('return_to');
    $previousUrl = url()->previous();
    $currentUrl = request()->fullUrl();
    $returnUrl = is_string($requestedReturnUrl) && $requestedReturnUrl !== ''
        ? $requestedReturnUrl
        : ($previousUrl !== $currentUrl ? $previousUrl : $defaultReturnUrl);

    if (
        !is_string($returnUrl)
        || $returnUrl === ''
        || !\Illuminate\Support\Str::startsWith($returnUrl, [url('/'), '/'])
    ) {
        $returnUrl = $defaultReturnUrl;
    }
@endphp

@section('page_back')
<a href="{{ $returnUrl }}" class="btn-outline owner-case-back" aria-label="Back to Case Records"><i class="bi bi-arrow-left" aria-hidden="true"></i><span class="owner-case-back-tooltip" role="tooltip">Back to Case Records</span></a>
@endsection
<div class="owner-page-shell owner-case-details-shell case-detail-shell owner-dossier">

    @if(session('success'))
        <div class="flash-success">{{ session('success') }}</div>
    @endif

    <nav class="case-section-nav no-print" aria-label="Case detail sections">
        <a href="#case-overview">Overview</a><a href="#case-client">Client</a><a href="#case-deceased">Deceased</a><a href="#case-service">Package</a><a href="#wake-schedule">Schedule</a><a href="#case-photo">Photo</a><a href="#case-payments">Payments</a><a href="#case-documents">Documents</a>
        @if($funeral_case->entry_source === 'OTHER_BRANCH' || $funeral_case->reporter_name)<a href="#case-source">Record Information</a>@endif
    </nav>
    @include('partials.case_view_content', ['staffCaseVisual' => true])
    @include('staff.partials.case-details-visual-system')
    @include('owner.partials.case-dossier')


</div>
<script>
    (() => {
        const nav = document.querySelector('.case-section-nav');
        const scroller = document.querySelector('.page-content');
        if (!nav || !scroller) return;
        let animation;
        const cancel = () => cancelAnimationFrame(animation);
        scroller.addEventListener('wheel', cancel, { passive: true });
        scroller.addEventListener('touchstart', cancel, { passive: true });
        const animateTo = end => {
            cancel();
            const start = scroller.scrollTop;
            if (matchMedia('(prefers-reduced-motion: reduce)').matches) { scroller.scrollTop = end; return; }
            const started = performance.now();
            const step = now => {
                const progress = Math.min(1, (now - started) / 1000);
                const eased = progress < .5 ? 4 * progress ** 3 : 1 - (-2 * progress + 2) ** 3 / 2;
                scroller.scrollTop = start + (end - start) * eased;
                if (progress < 1) animation = requestAnimationFrame(step);
            };
            animation = requestAnimationFrame(step);
        };
        nav.addEventListener('click', event => {
            const link = event.target.closest('a[href^="#"]');
            const target = link && document.getElementById(link.hash.slice(1));
            if (!target) return;
            event.preventDefault();
            cancel();
            const start = scroller.scrollTop;
            const end = Math.max(0, Math.min(scroller.scrollHeight - scroller.clientHeight,
                start + target.getBoundingClientRect().top - scroller.getBoundingClientRect().top - 24));
            history.replaceState(null, '', link.hash);
            animateTo(end);
        });
    })();
</script>
@endsection

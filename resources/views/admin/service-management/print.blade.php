<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>{{ $items->count() === 1 ? $items->first()['name'].' — ' : '' }}{{ $title }} · Sabangan Caguioa</title>
    <link rel="stylesheet" href="{{ asset('vendor/offline/fonts.css') }}">
    @vite('resources/js/service-brochure.js')
</head>
<body class="service-brochure" data-brochure-tab="{{ $tab }}" data-brochure-count="{{ $items->count() }}">
<svg class="brochure-symbols" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"><defs>
    <symbol id="menu-package" viewBox="0 0 24 24"><path d="M3 7h18v14H3zM3 7l3-4h12l3 4M9 11h6"/></symbol>
    <symbol id="menu-check" viewBox="0 0 24 24"><path d="m5 12 4 4L19 6"/></symbol>
    <symbol id="menu-gift" viewBox="0 0 24 24"><path d="M3 8h18v4H3zM5 12v9h14v-9M12 8v13M12 8C2 8 5 0 9 3l3 5Zm0 0c10 0 7-8 3-5l-3 5Z"/></symbol>
    <symbol id="menu-tag" viewBox="0 0 24 24"><path d="M3 3h8l10 10-8 8L3 11zM7 7h.01"/></symbol>
    <symbol id="menu-print" viewBox="0 0 24 24"><path d="M6 8V3h12v5M6 17H3V8h18v9h-3M6 14h12v7H6zM17 11h1"/></symbol>
</defs></svg>
<header class="brochure-controls" data-no-print>
    <div class="brochure-controls-heading"><div><span class="control-kicker">Client copy · Print Preview</span><h1>{{ $title }}</h1></div><div class="brochure-control-buttons"><button type="button" class="menu-secondary" data-close-preview data-return-url="{{ route('admin.service-management.index',['tab'=>$tab]) }}">Close Preview</button><button type="button" class="menu-primary" data-print-brochure disabled><svg><use href="#menu-print"/></svg> Print / Save as PDF</button></div></div>
    @if($notice)<p class="brochure-notice" role="status">{{ $notice }}</p>@endif
    @if($photoNotice)<p class="brochure-photo-notice" role="status">{{ $photoNotice }}</p>@endif
    <form method="GET" action="{{ route('admin.service-management.print') }}" data-brochure-form>
        <input type="hidden" name="tab" value="{{ $tab }}">
        @foreach($filters as $key=>$value) @if(is_scalar($value))<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif @endforeach
        <div class="brochure-scope-row"><label>Include <span class="menu-select"><select name="scope" data-print-scope><option value="all" @selected($scope==='all')>All Available</option><option value="filtered" @selected($scope==='filtered')>Filtered Results</option><option value="selected" @selected($scope==='selected')>Selected Items</option></select><span aria-hidden="true">⌄</span></span></label><button type="submit" class="menu-secondary">Update Preview</button><span class="brochure-preview-state" data-preview-state role="status">Preparing pages…</span></div>
        <section class="brochure-picker" data-print-picker @if($scope!=='selected') hidden @endif aria-label="Items to print">
            <div class="brochure-picker-head"><div class="menu-search" role="search"><svg aria-hidden="true" viewBox="0 0 24 24"><circle cx="10" cy="10" r="6"/><path d="m15 15 6 6"/></svg><input type="text" placeholder="Item name" aria-label="Search available items" autocomplete="off" data-picker-search><button type="button" class="menu-search-clear" aria-label="Clear item search" data-picker-clear hidden>×</button><button type="button" data-picker-search-button>Search</button><div class="menu-search-matches" data-picker-matches hidden></div></div><div class="brochure-picker-actions"><span data-selected-count>{{ count($ids) }} selected</span><button type="button" data-select-matches>Select visible</button><button type="button" data-clear-selection>Clear selection</button></div></div>
            <div class="brochure-picker-items">
                @foreach($choices as $choice)<label class="brochure-picker-item" data-picker-item data-name="{{ $choice->name }}"><input type="checkbox" name="ids[]" value="{{ $choice->id }}" @checked(in_array((int)$choice->id,$ids,true))><span>{{ $choice->name }}</span><span class="picker-selected-label" aria-hidden="true">Selected</span></label>@endforeach
            </div>
            <p data-picker-empty hidden>No available items match your search.</p>
            <p class="picker-hint">Selection includes items from every page. Choose items, then select Update Preview.</p>
        </section>
    </form>
    @if($scope==='filtered' && collect($filters)->except('sort')->filter(fn($v)=>filled($v) && $v!=='all')->isNotEmpty())<p class="brochure-filter-context">Filtered by @foreach(collect($filters)->except('sort')->filter(fn($v)=>filled($v) && $v!=='all') as $key=>$value)<span>{{ \Illuminate\Support\Str::headline($key) }}: {{ \Illuminate\Support\Str::headline($value) }}</span>@endforeach</p>@endif
    <p class="brochure-controls-note">Available services only. Prices and offers are current as of {{ $preparedAt->format('M d, Y') }}. In the print dialog, use A4 at 100% scale and turn off browser headers and footers.</p>
</header>
@if($items->isEmpty())
    <main class="brochure-empty"><svg><use href="#menu-package"/></svg><h2>No available items to print</h2><p>Your filters may include archived or unavailable items. Choose All Available or return to Service Management to review the catalog.</p><a href="{{ route('admin.service-management.index',['tab'=>$tab]) }}">Return to {{ $title }}</a></main>
@else
    <main class="brochure-pages" data-brochure-pages aria-label="{{ $title }} print pages" aria-busy="true"></main>
@endif
<template data-page-template>
    <section class="brochure-page">
        <header class="brochure-page-header">
            <div class="menu-brand"><img src="{{ asset('images/login-logo.png') }}" alt="Sabangan Caguioa logo" width="48" height="48"><div><strong>Sabangan Caguioa</strong><span>FUNERAL HOME</span></div></div>
            <div class="menu-contact">@if($branch)<strong>{{ $branch->branch_code }} · {{ $branch->branch_name }}</strong>@endif @if($branch?->address)<span>{{ $branch->address }}</span>@endif<span>Prepared {{ $preparedAt->format('M d, Y') }}</span></div>
            <div class="menu-document-heading"><div><span class="menu-eyebrow">A guide for you and your family</span><h2>{{ $title }}</h2><p>{{ $tab==='packages' ? 'Thoughtfully arranged services, with clear inclusions and choices.' : ($tab==='promotions' ? 'Current package offers and their terms.' : 'Explore the available choices for your selected service.') }}</p></div><svg class="menu-botanical" aria-hidden="true" viewBox="0 0 120 80"><path d="M10 75Q75 50 100 8M42 62Q10 40 20 15Q55 25 42 62M65 44Q40 15 54 5Q82 15 65 44M75 34Q110 40 116 20Q90 14 75 34M54 54Q95 70 103 47Q76 35 54 54"/></svg></div>
        </header>
        <div class="brochure-page-content {{ $tab==='packages' ? 'is-packages' : 'is-catalog' }}{{ $items->count()===1 ? ' is-single' : '' }}" data-page-content></div>
        <footer class="brochure-page-footer"><span>Final charges depend on selected services and confirmed quantities.</span><span data-page-number></span></footer>
    </section>
</template>
<div data-brochure-source hidden>
@foreach($items as $item)
    <article class="menu-item {{ $tab==='packages' ? 'is-package' : '' }}" data-print-item>
        <header class="menu-item-header" data-item-heading><div>@if($item['tag'])<span class="menu-item-tag">{{ $item['tag'] }}</span>@endif<h3>{{ $item['name'] }}</h3></div><div class="menu-price">@if($item['price']!==null)<span>{{ $item['price_label'] }}</span><strong>₱{{ number_format((float)$item['price'],2) }}</strong>@elseif($item['price_label'])<span>{{ $item['price_label'] }}</span><strong>Quotation on request</strong>@endif</div></header>
        <div class="menu-item-body" data-item-body>
            @if($item['description'])<section class="menu-description" data-print-group><p data-print-row>{{ $item['description'] }}</p></section>@endif
            @if($item['photos'])
                <section class="menu-photos" data-print-group>
                    @foreach($item['photos'] as $photo)
                        <figure data-print-row>
                            <img src="{{ $photo['url'] }}" alt="{{ $photo['alt'] }}" width="384" height="216" loading="eager">
                        </figure>
                    @endforeach
                </section>
            @endif
            @foreach($item['sections'] as $heading=>$rows)
            <section class="menu-section{{ $heading==='Current Promotion' ? ' is-offer' : '' }}" data-section="{{ \Illuminate\Support\Str::slug($heading) }}" data-print-group><h4><svg aria-hidden="true"><use href="#{{ match($heading){'Freebies'=>'menu-gift','Current Promotion','Offer'=>'menu-tag',default=>'menu-check'} }}"/></svg>{{ $heading }}</h4><dl>@foreach($rows as $row)<div data-print-row><dt>{{ $row['label'] }}</dt><dd>{{ $row['value'] }}</dd></div>@endforeach</dl></section>
            @endforeach
            @if(in_array($tab,['caskets','hearses'],true))<section class="menu-description menu-reference-note" data-print-group><p data-print-row>Reference values help determine applicable package replacement or upgrade charges. Confirm the final quotation with the branch.</p></section>@endif
        </div>
    </article>
@endforeach
</div>
<noscript><style>[data-brochure-source]{display:block!important}.brochure-pages{display:none}.brochure-controls{display:none}@media print{[data-brochure-source]{display:block!important}}</style><p>Use your browser’s Print command to print this service guide.</p></noscript>
</body>
</html>

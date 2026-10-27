@props(['id', 'title', 'fields' => [], 'url', 'label', 'description' => '', 'secondaryUrl' => null, 'secondaryLabel' => null])
<button type="button" class="sa-eye" data-sa-dialog="{{ $id }}" data-sa-tip="View {{ $title }} overview" aria-label="View {{ $title }} overview" aria-haspopup="dialog" aria-controls="{{ $id }}"><i class="bi bi-eye" aria-hidden="true"></i></button>
<dialog id="{{ $id }}" class="sa-dialog" aria-labelledby="{{ $id }}-title" aria-describedby="{{ $id }}-description">
    <div class="sa-dialog-head"><div><h2 id="{{ $id }}-title">{{ $title }}</h2><p id="{{ $id }}-description">{{ $description ?: 'Review this summary before opening the management page.' }}</p></div><button type="button" class="sa-eye" data-sa-close aria-label="Close overview" autofocus><i class="bi bi-x-lg" aria-hidden="true"></i></button></div>
    <dl class="sa-dialog-fields">@foreach($fields as $name => $value)<div><dt>{{ $name }}</dt><dd>{{ $value }}</dd></div>@endforeach</dl>
    <div class="sa-dialog-footer">@if($secondaryUrl)<a class="sa-link" href="{{ $secondaryUrl }}">{{ $secondaryLabel }}</a>@endif<a class="sa-link sa-primary" href="{{ $url }}">{{ $label }} <i class="bi bi-arrow-right" aria-hidden="true"></i></a></div>
</dialog>

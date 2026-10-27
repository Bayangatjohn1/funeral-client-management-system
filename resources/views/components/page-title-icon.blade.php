@props(['icon' => null, 'inline' => false])
@php
    $title = strtolower(trim($__env->yieldContent('page_title', 'Dashboard')));
    $resolvedIcon = $icon ?? match (true) {
        str_contains($title, 'dashboard') => 'grid',
        str_contains($title, 'audit') => 'journal-text',
        str_contains($title, 'backup') => 'database-check',
        str_contains($title, 'retention') => 'archive',
        str_contains($title, 'report'), str_contains($title, 'analytics') => 'bar-chart',
        str_contains($title, 'reminder'), str_contains($title, 'schedule') => 'schedule-reminders',
        str_contains($title, 'payment'), str_contains($title, 'sales') => 'cash-coin',
        str_contains($title, 'profile') => 'person-circle',
        str_contains($title, 'user') => 'people',
        str_contains($title, 'branch') && !str_contains($title, 'intake') => 'building',
        str_contains($title, 'client') => 'person-vcard',
        str_contains($title, 'deceased') => 'flower1',
        str_contains($title, 'contract') => 'file-earmark-text',
        str_contains($title, 'draft') => 'file-earmark',
        str_contains($title, 'intake') => 'file-earmark-plus',
        str_contains($title, 'case') => 'folder2-open',
        str_contains($title, 'casket') => 'box',
        str_contains($title, 'freebie') => 'gift',
        str_contains($title, 'add-on') => 'plus-square',
        str_contains($title, 'package'), str_contains($title, 'service') => 'box-seam',
        default => 'file-earmark-text',
    };
@endphp
<span {{ $attributes->class(['page-title-icon', 'page-title-icon--inline' => $inline]) }} aria-hidden="true">
    @if($resolvedIcon === 'schedule-reminders')
        <x-schedule-reminders-icon />
    @else
        <i class="bi bi-{{ $resolvedIcon }}"></i>
    @endif
</span>

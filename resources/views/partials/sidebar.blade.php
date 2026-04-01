@php
    $role = auth()->user()->role ?? null;

    $linkBase = 'nav-link';
    $iconBase = 'nav-link__icon';

    $isActive = function ($condition) use ($linkBase) {
        return $condition ? $linkBase . ' active' : $linkBase;
    };

    $iconState = function ($condition) use ($iconBase) {
        return $condition ? $iconBase . ' active' : $iconBase;
    };
@endphp

@if ($role === 'staff')
    @include('partials.sidebar.staff', ['isActive' => $isActive, 'iconState' => $iconState])
@elseif ($role === 'admin')
    @include('partials.sidebar.admin', ['isActive' => $isActive, 'iconState' => $iconState])
@elseif ($role === 'owner')
    @include('partials.sidebar.owner', ['isActive' => $isActive, 'iconState' => $iconState])
@endif
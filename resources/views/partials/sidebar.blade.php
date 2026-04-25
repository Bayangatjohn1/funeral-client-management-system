@php
    $user = auth()->user();
    $role = $user->role ?? null;

    $linkBase = 'nav-link';
    $iconBase = 'nav-link__icon';

    $isActive = function ($condition) use ($linkBase) {
        return $condition ? $linkBase . ' active' : $linkBase;
    };

    $iconState = function ($condition) use ($iconBase) {
        return $condition ? $iconBase . ' active' : $iconBase;
    };
@endphp

@if ($role === 'staff' || ($user && method_exists($user, 'isBranchAdmin') && $user->isBranchAdmin()))
    @include('partials.sidebar.staff', ['isActive' => $isActive, 'iconState' => $iconState])
@elseif ($user && method_exists($user, 'isMainBranchAdmin') && $user->isMainBranchAdmin())
    @include('partials.sidebar.admin', ['isActive' => $isActive, 'iconState' => $iconState])
@elseif ($role === 'owner')
    @include('partials.sidebar.owner', ['isActive' => $isActive, 'iconState' => $iconState])
@endif

@php
    $activePage = (string) ($currentPage ?? '');
    $hasUser = !empty($currentUser);
    $userRole = (string) ($currentUser['role'] ?? 'guest');

    $links = [
        ['key' => 'home', 'label' => 'Inicio', 'url' => url('/'), 'visible' => true],
        ['key' => 'foro', 'label' => 'Foro', 'url' => url('/foro'), 'visible' => true],
        ['key' => 'sugerencias', 'label' => 'Sugerencias', 'url' => url('/sugerencias'), 'visible' => true],
        ['key' => 'contenedor', 'label' => 'Contenedor', 'url' => url('/contenedor'), 'visible' => $hasUser],
        ['key' => 'admin', 'label' => 'Admin', 'url' => url('/admin/users'), 'visible' => $userRole === 'admin'],
    ];
@endphp

<nav class="navbar">
    @foreach ($links as $link)
        @if (!($link['visible'] ?? false))
            @continue
        @endif

        @if ($activePage === ($link['key'] ?? '') && !($link['external'] ?? false))
            <a href="{{ $link['url'] }}" class="navbar-link active" aria-current="page">
                {{ $link['label'] }}
            </a>
        @else
            <a href="{{ $link['url'] }}" 
               class="navbar-link"
               @if ($link['external'] ?? false) target="_blank" rel="noopener noreferrer" @endif>
                {{ $link['label'] }}
            </a>
        @endif
    @endforeach
</nav>

<style>
.navbar {
    display: flex;
    gap: 12px;
    padding: 0 20px;
    align-items: center;
    flex-wrap: wrap;
    background-color: transparent;
    backdrop-filter: none;
    border: none;
    box-shadow: none;
}

.navbar-link {
    padding: 8px 14px;
    border: 1px solid var(--vh-border);
    border-radius: 10px;
    background-color: var(--vh-button-bg);
    color: var(--vh-text);
    text-decoration: none;
    cursor: pointer;
    font-family: Monocraft Nerd Font, monospace;
    font-size: 13px;
    font-weight: 500;
    transition: background-color 0.25s ease, color 0.25s ease, transform 0.2s ease;
    display: inline-block;
    white-space: nowrap;
}

.navbar-link:hover {
    background-color: var(--vh-button-hover);
    color: var(--vh-panel-text);
    transform: translateY(-1px);
}

.navbar-link.active {
    background-color: var(--vh-button-hover);
    color: var(--vh-panel-text);
    border-color: rgba(117, 225, 160, 0.5);
    box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.1);
}

.navbar-link.active:hover {
    transform: translateY(-2px);
}

@media (max-width: 768px) {
    .navbar {
        padding: 0 12px;
        gap: 8px;
    }

    .navbar-link {
        padding: 6px 12px;
        font-size: 12px;
    }
}
</style>
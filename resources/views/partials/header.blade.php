<header>
    <h1>{{ $pageTitle ?? 'VirtHub' }}</h1>
    @include('partials.navigation-menu', ['currentUser' => $currentUser ?? null, 'currentPage' => $currentPage ?? 'home'])
    <div class="header-controls">
        <div class="theme-toggle" onclick="toggleTheme()" id="themeToggle" title="Cambiar tema" aria-label="Cambiar tema">
            <span class="theme-icon" aria-hidden="true"></span>
        </div>
        @if (!empty($currentUser))
            @php
                $profileImage = (string) ($currentUser['profile_image_path'] ?? '');
                $frameColor = (string) ($currentUser['profile_frame_color'] ?? '#6ea8ff');
                $userInitial = strtoupper(substr((string) ($currentUser['username'] ?? 'U'), 0, 1));
            @endphp
            <div class="header-profile-dock toggleable-profile-menu" onclick="toggleProfileMenu(event)" title="Menu de perfil" aria-label="Menu de perfil">
                <div class="profile-aero-frame profile-aero-frame-sm" style="--profile-frame-color: {{ $frameColor }};">
                    @if ($profileImage !== '')
                        <img src="{{ asset($profileImage) }}" alt="Foto de perfil de {{ $currentUser['username'] }}" loading="lazy">
                    @else
                        <span>{{ $userInitial }}</span>
                    @endif
                </div>
                <div class="profile-menu" onclick="event.stopPropagation()">
                    @if (($currentUser['role'] ?? 'guest') !== 'guest')
                        <button type="button" onclick="location.href='{{ url('/configuracion') }}'">Configuracion</button>
                    @endif
                    <form method="POST" action="/logout">
                        @csrf
                        <button type="submit">Cerrar Sesion</button>
                    </form>
                </div>
            </div>
        @endif
    </div>
</header>
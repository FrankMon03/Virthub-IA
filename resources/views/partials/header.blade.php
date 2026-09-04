<header>
    <h1>{{ $pageTitle ?? 'VirtHub' }}</h1>
    @include('partials.navigation-menu', ['currentUser' => $currentUser ?? null, 'currentPage' => $currentPage ?? 'home'])
    <div class="header-controls">
        @if (!empty($currentUser) && ($currentUser['role'] ?? 'guest') !== 'guest')
            <form method="GET" action="{{ url('/buscar-amigos') }}" class="header-friend-search" role="search">
                <input type="search" name="q" placeholder="Buscar amigos" aria-label="Buscar amigos">
                <button type="submit" title="Buscar amigos" aria-label="Buscar amigos">⌕</button>
            </form>
        @endif
        <div class="theme-toggle" onclick="toggleTheme()" id="themeToggle" title="Cambiar tema" aria-label="Cambiar tema">
            <span class="theme-icon" aria-hidden="true"></span>
        </div>
        <button type="button" class="retro-toggle" onclick="toggleRetroMode()" id="retroToggle" title="Activar tema retro" aria-label="Activar tema retro">Retro</button>
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
                        <button type="button" onclick="location.href='{{ url('/perfil/' . rawurlencode((string) $currentUser['username'])) }}'">Mi Perfil</button>
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

<script>
    (() => {
        const retroStorageKey = 'virthub_retro_mode';
        const secretSequence = 'win95';
        let typedSequence = '';

        function applyRetroMode(enabled) {
            document.body.classList.toggle('retro-mode', enabled);
            localStorage.setItem(retroStorageKey, enabled ? '1' : '0');
        }

        window.toggleRetroMode = () => {
            applyRetroMode(!document.body.classList.contains('retro-mode'));
        };

        document.addEventListener('DOMContentLoaded', () => {
            applyRetroMode(localStorage.getItem(retroStorageKey) === '1');
        });

        document.addEventListener('keydown', event => {
            if (event.target.matches('input, textarea, select, [contenteditable="true"]')) {
                return;
            }

            typedSequence = (typedSequence + event.key.toLowerCase()).slice(-secretSequence.length);
            if (typedSequence === secretSequence) {
                window.toggleRetroMode();
                typedSequence = '';
            }
        });
    })();
</script>
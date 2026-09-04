<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Perfil de {{ $profile['username'] }}</title>
    <link rel="stylesheet" href="{{ asset('style.css') }}?v={{ filemtime(public_path('style.css')) }}">
    <link rel="stylesheet" href="{{ asset('container.css') }}?v={{ filemtime(public_path('container.css')) }}">
    <style>
        .profile-page {
            margin: 5px;
            display: grid;
            grid-template-columns: minmax(240px, 320px) minmax(0, 1fr);
            gap: 12px;
            color: var(--vh-panel-text);
        }

        .profile-page-panel,
        .profile-post {
            background-color: var(--vh-surface);
            border: 1px solid var(--vh-border);
            padding: 14px;
            font-family: Monocraft Nerd Font, monospace;
        }

        .profile-page-panel h2,
        .profile-page-panel h3 {
            margin: 0 0 10px;
            color: var(--vh-text);
        }

        .profile-hero {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 14px;
        }

        .profile-hero .profile-aero-frame {
            flex: 0 0 auto;
        }

        .profile-role,
        .profile-date,
        .profile-empty {
            color: var(--vh-text-soft);
            font-size: 12px;
        }

        .profile-role,
        .profile-date {
            margin: 4px 0;
        }

        .profile-post-form textarea {
            width: 100%;
            min-height: 120px;
            box-sizing: border-box;
            resize: vertical;
            margin: 0 0 8px;
            padding: 10px;
            border: 1px solid var(--vh-border);
            background-color: var(--vh-button-bg);
            color: var(--vh-text);
            font: inherit;
        }

        .profile-post-form button {
            padding: 8px 12px;
            border: 1px solid var(--vh-border);
            background-color: var(--vh-button-bg);
            color: var(--vh-text);
            font: inherit;
            cursor: pointer;
        }

        .profile-posts {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .profile-post h3 {
            margin: 0 0 6px;
            font-size: 14px;
        }

        .profile-post time {
            display: block;
            margin-bottom: 8px;
            color: var(--vh-text-soft);
            font-size: 11px;
        }

        .profile-post-content {
            margin: 0;
            color: var(--vh-panel-text);
            line-height: 1.55;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
        }

        .profile-status {
            margin: 0 0 12px;
            padding: 10px;
            border: 1px solid var(--vh-border);
            color: var(--vh-panel-text);
        }

        @media (max-width: 760px) {
            .profile-page {
                grid-template-columns: 1fr;
            }

            .profile-hero {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    @php
        $profileImage = (string) ($profile['profile_image_path'] ?? '');
        $frameColor = (string) ($profile['profile_frame_color'] ?? '#6ea8ff');
        $userInitial = strtoupper(substr((string) ($profile['username'] ?? 'U'), 0, 1));
    @endphp

    @include('partials.header', [
        'pageTitle' => 'Perfil de ' . $profile['username'],
        'currentUser' => $currentUser ?? null,
        'currentPage' => 'perfil'
    ])

    <main class="profile-page">
        <section class="profile-page-panel">
            @if (session('error'))
                <p class="profile-status auth-error">{{ session('error') }}</p>
            @endif
            @if (session('success'))
                <p class="profile-status auth-success">{{ session('success') }}</p>
            @endif

            <div class="profile-hero">
                <div class="profile-aero-frame" style="--profile-frame-color: {{ $frameColor }};">
                    @if ($profileImage !== '')
                        <img src="{{ asset($profileImage) }}" alt="Foto de perfil de {{ $profile['username'] }}" loading="lazy">
                    @else
                        <span>{{ $userInitial }}</span>
                    @endif
                </div>
                <div>
                    <h2>{{ $profile['username'] }}</h2>
                    <p class="profile-role">{{ $profile['role'] === 'admin' ? 'Administrador' : 'Usuario' }}</p>
                    @if (!empty($profile['created_at']))
                        <p class="profile-date">Miembro desde {{ date('d/m/Y', strtotime($profile['created_at'])) }}</p>
                    @endif
                </div>
            </div>

            @if ($isOwner)
                <h3>Publicar en mi perfil</h3>
                <form method="POST" action="{{ url('/perfil/' . rawurlencode($profile['username']) . '/publicar') }}" class="profile-post-form">
                    @csrf
                    <textarea name="content" maxlength="2000" placeholder="Comparte algo en tu perfil..." required></textarea>
                    <button type="submit">Publicar</button>
                </form>
            @endif
        </section>

        <section class="profile-page-panel">
            <h2>Publicaciones</h2>
            @if (count($profilePosts) > 0)
                <div class="profile-posts">
                    @foreach ($profilePosts as $post)
                        <article class="profile-post">
                            <h3>{{ $profile['username'] }}</h3>
                            <time datetime="{{ $post['created_at'] }}">{{ date('d/m/Y H:i', strtotime($post['created_at'])) }}</time>
                            <p class="profile-post-content">{{ $post['content'] }}</p>
                        </article>
                    @endforeach
                </div>
            @else
                <p class="profile-empty">Todavia no hay publicaciones en este perfil.</p>
            @endif
        </section>
    </main>

    <footer>Virthub 1.0</footer>

    <script>
        function getThemeStorageKey() {
            return 'virthub_dark_mode_' + @json($currentUser['username'] ?? 'guest');
        }

        function applyThemeState() {
            const isDark = localStorage.getItem(getThemeStorageKey()) === '1';
            document.body.classList.toggle('dark-mode', isDark);

            const themeToggle = document.getElementById('themeToggle');
            if (themeToggle) {
                themeToggle.classList.toggle('is-dark', isDark);
                themeToggle.title = isDark ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro';
            }
        }

        function toggleTheme() {
            const isDark = document.body.classList.toggle('dark-mode');
            localStorage.setItem(getThemeStorageKey(), isDark ? '1' : '0');

            const themeToggle = document.getElementById('themeToggle');
            if (themeToggle) {
                themeToggle.classList.toggle('is-dark', isDark);
                themeToggle.title = isDark ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro';
            }
        }

        function toggleProfileMenu(event) {
            if (event) {
                event.stopPropagation();
            }

            const launcher = document.querySelector('.toggleable-profile-menu');
            if (!launcher) return;

            launcher.classList.toggle('is-open');
        }

        window.addEventListener('DOMContentLoaded', applyThemeState);
        window.addEventListener('DOMContentLoaded', () => {
            document.addEventListener('click', () => {
                document.querySelector('.toggleable-profile-menu')?.classList.remove('is-open');
            });
        });
    </script>
</body>
</html>

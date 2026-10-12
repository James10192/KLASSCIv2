<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover, interactive-widget=resizes-content">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ \App\Helpers\SettingsHelper::get('school_name', 'KLASSCI') }} — Connexion</title>

    {{-- PWA : installable des la page de connexion (manifest servi hors auth), couleur de barre = couleur primaire de l'ecole --}}
    @php
        $pwaPrimaire = \App\Helpers\SettingsHelper::get('pdf_primary_color', '#0453cb');
        $pwaThemeColor = preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', (string) $pwaPrimaire)
            ? $pwaPrimaire : '#0453cb';
    @endphp
    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="theme-color" content="{{ $pwaThemeColor }}">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">

    @include('partials.og-meta')

    <!-- Fonts — same as landing page -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Serif:wght@300;400;500&family=IBM+Plex+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    @include('auth.partials.login-styles')
</head>
<body>

<!-- Floating background elements -->
<div class="floating-elements">
    <div class="floating-icon fi-1"><i class="fas fa-graduation-cap"></i></div>
    <div class="floating-icon fi-2"><i class="fas fa-book-open"></i></div>
    <div class="floating-icon fi-3"><i class="fas fa-users"></i></div>
    <div class="floating-icon fi-4"><i class="fas fa-chart-line"></i></div>
    <div class="floating-icon fi-5"><i class="fas fa-lightbulb"></i></div>
    <div class="floating-icon fi-6"><i class="fas fa-trophy"></i></div>
</div>
<div class="geo-ring ring-1"></div>
<div class="geo-ring ring-2"></div>
<div class="geo-ring ring-3"></div>

<div class="login-card">
    <!-- Left panel -->
    <div class="panel-left">
        <div class="panel-left-content">
            <div class="logo-row">
                <img src="{{ asset('images/LOGO-KLASSCI-PNG.png') }}" alt="KLASSCI">
                <span>KLASSCI</span>
            </div>

            <h2>Gérez votre établissement en toute simplicité.</h2>
            <p class="tagline">Notes, bulletins, paiements, emplois du temps — un seul outil pour tout piloter.</p>

            <div class="stats">
                <div class="stat">
                    <span class="stat-value">5+</span>
                    <span class="stat-label">Établissements</span>
                </div>
                <div class="stat">
                    <span class="stat-value">5k+</span>
                    <span class="stat-label">Étudiants</span>
                </div>
                <div class="stat">
                    <span class="stat-value">98%</span>
                    <span class="stat-label">Satisfaction</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Right panel — Form -->
    <div class="panel-right">
        <div class="form-header">
            <h1>Connexion</h1>
            <p>Accédez à votre espace KLASSCI</p>
        </div>

        <!-- Alerts -->
        @if(session('status'))
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span>{{ session('status') }}</span>
            </div>
        @endif
        @if(session('warning'))
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                <span>{{ session('warning') }}</span>
            </div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <div>
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div class="field">
                <label for="username">Nom d'utilisateur</label>
                <div class="input-wrap">
                    <input
                        id="username"
                        type="text"
                        name="username"
                        value="{{ old('username') }}"
                        required
                        autofocus
                        autocomplete="username"
                        inputmode="text"
                        autocapitalize="none"
                        autocorrect="off"
                        spellcheck="false"
                        enterkeyhint="next"
                        placeholder="Votre identifiant"
                        class="@error('username') is-invalid @enderror"
                    >
                    <i class="fas fa-user icon"></i>
                    @error('username')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="field">
                <label for="password">Mot de passe</label>
                <div class="input-wrap">
                    <input
                        id="password"
                        type="password"
                        name="password"
                        required
                        autocomplete="current-password"
                        enterkeyhint="go"
                        placeholder="Votre mot de passe"
                        class="@error('password') is-invalid @enderror"
                        style="padding-right: 2.5rem;"
                    >
                    <i class="fas fa-lock icon"></i>
                    <i class="fas fa-eye password-toggle" id="togglePassword" role="button" tabindex="0" aria-label="Afficher le mot de passe" title="Afficher le mot de passe"></i>
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="form-options">
                <div class="remember-row">
                    <input type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                    <label for="remember">Se souvenir de moi</label>
                </div>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="forgot-link">Mot de passe oublié ?</a>
                @endif
            </div>

            <button type="submit" class="btn-login">
                <i class="fas fa-arrow-right" style="font-size:0.8rem"></i>
                Se connecter
            </button>
        </form>

        <div class="login-footer">
            &copy; {{ date('Y') }} KLASSCI &mdash; African Digital Consulting
        </div>
    </div>
</div>

<script>
    // CSRF token refresh every 30 minutes
    setInterval(function() {
        fetch('/csrf-token-refresh', { headers: { 'Accept': 'application/json' } })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.token) {
                    document.querySelectorAll('input[name="_token"]').forEach(function(f) { f.value = data.token; });
                    var meta = document.querySelector('meta[name="csrf-token"]');
                    if (meta) meta.setAttribute('content', data.token);
                }
            })
            .catch(function() {});
    }, 30 * 60 * 1000);

    // Toggle password visibility
    (function() {
        var toggle = document.getElementById('togglePassword');
        var pw = document.getElementById('password');
        if (toggle && pw) {
            toggle.addEventListener('click', function() {
                var isPassword = pw.type === 'password';
                pw.type = isPassword ? 'text' : 'password';
                this.classList.toggle('fa-eye', !isPassword);
                this.classList.toggle('fa-eye-slash', isPassword);
                this.title = isPassword ? 'Masquer le mot de passe' : 'Afficher le mot de passe';
                this.setAttribute('aria-label', this.title);
            });
            // Accessible au clavier (role=button)
            toggle.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggle.click(); }
            });
        }
    })();
</script>
</body>
</html>

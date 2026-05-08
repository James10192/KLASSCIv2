<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ \App\Helpers\SettingsHelper::get('school_name', 'KLASSCI') }} — Connexion</title>

    <!-- Fonts — same as landing page -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Serif:wght@300;400;500&family=IBM+Plex+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --accent: #0453cb;
            --accent-hover: #0340a0;
            --accent-light: rgba(4,83,203,0.08);
            --text: #1a1a1a;
            --text-secondary: #3a3d43;
            --text-muted: #8a8a8a;
            --bg: #f6f4f0;
            --bg-card: #fff;
            --border: #dadde2;
            --border-strong: #c5c9d0;
            --radius: 4px;
            --ease-out: cubic-bezier(0.22, 1, 0.36, 1);
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'IBM Plex Sans', system-ui, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background:
                linear-gradient(135deg, rgba(4,53,130,0.88) 0%, rgba(4,83,203,0.82) 50%, rgba(4,53,130,0.9) 100%),
                url('{{ asset('images/Images landingPage/Sans titre - 2-03.png') }}');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
        }

        /* Dot grid overlay */
        body::after {
            content: '';
            position: fixed;
            inset: 0;
            pointer-events: none;
            background-image: radial-gradient(circle, rgba(255,255,255,0.06) 1px, transparent 1px);
            background-size: 20px 20px;
            z-index: 1;
        }

        /* ─── Floating elements ─── */
        .floating-elements {
            position: fixed;
            inset: 0;
            z-index: 2;
            pointer-events: none;
            overflow: hidden;
        }

        .floating-icon {
            position: absolute;
            color: rgba(255,255,255,0.12);
            font-size: 1.5rem;
            animation: float 8s ease-in-out infinite;
        }

        .fi-1 { top: 12%; left: 6%; animation-delay: 0s; }
        .fi-2 { top: 22%; right: 10%; animation-delay: 2s; font-size: 1.3rem; }
        .fi-3 { bottom: 25%; left: 8%; animation-delay: 4s; }
        .fi-4 { bottom: 12%; right: 12%; animation-delay: 6s; font-size: 1.8rem; }
        .fi-5 { top: 55%; left: 4%; animation-delay: 1s; font-size: 1.2rem; }
        .fi-6 { top: 72%; right: 6%; animation-delay: 3s; }

        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            25% { transform: translateY(-15px) rotate(5deg); }
            50% { transform: translateY(-30px) rotate(0deg); }
            75% { transform: translateY(-15px) rotate(-5deg); }
        }

        /* Geometric rings */
        .geo-ring {
            position: fixed;
            border: 1.5px solid rgba(255,255,255,0.08);
            border-radius: 50%;
            animation: ring-spin 25s linear infinite;
            pointer-events: none;
            z-index: 2;
        }

        .ring-1 { width: 280px; height: 280px; top: 8%; left: -8%; }
        .ring-2 { width: 180px; height: 180px; top: 55%; right: -4%; animation-delay: -8s; animation-duration: 20s; }
        .ring-3 { width: 140px; height: 140px; bottom: 15%; left: 12%; animation-delay: -15s; animation-duration: 30s; }

        @keyframes ring-spin {
            0% { transform: rotate(0deg); opacity: 0.3; }
            50% { transform: rotate(180deg); opacity: 0.08; }
            100% { transform: rotate(360deg); opacity: 0.3; }
        }

        /* ─── Main card ─── */
        .login-card {
            position: relative;
            z-index: 10;
            display: flex;
            max-width: 880px;
            width: 100%;
            margin: 2rem;
            background: var(--bg-card);
            border-radius: 8px;
            overflow: hidden;
            box-shadow:
                0 1px 0 rgba(255,255,255,0.1),
                0 24px 60px rgba(0,0,0,0.3),
                0 2px 6px rgba(0,0,0,0.15);
            animation: cardIn 0.7s var(--ease-out) both;
        }

        @keyframes cardIn {
            from { opacity: 0; transform: translateY(24px) scale(0.98); }
            to { opacity: 1; transform: none; }
        }

        /* ─── Left panel ─── */
        .panel-left {
            flex: 1;
            background:
                linear-gradient(160deg, #043582 0%, var(--accent) 60%, #0a5fd4 100%);
            padding: 3rem 2.5rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
            color: #fff;
        }

        /* Subtle grid pattern on left panel */
        .panel-left::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image: radial-gradient(circle, rgba(255,255,255,0.07) 1px, transparent 1px);
            background-size: 18px 18px;
            pointer-events: none;
        }

        .panel-left-content {
            position: relative;
            z-index: 2;
        }

        .panel-left .logo-row {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            margin-bottom: 2.5rem;
        }

        .panel-left .logo-row img {
            height: 32px;
        }

        .panel-left .logo-row span {
            font-family: 'IBM Plex Sans', sans-serif;
            font-weight: 700;
            font-size: 1.1rem;
            letter-spacing: -0.02em;
        }

        .panel-left h2 {
            font-family: 'IBM Plex Serif', serif;
            font-weight: 300;
            font-size: 2rem;
            line-height: 1.2;
            letter-spacing: -0.02em;
            margin-bottom: 1rem;
        }

        .panel-left .tagline {
            font-size: 0.95rem;
            line-height: 1.6;
            color: rgba(255,255,255,0.8);
            max-width: 300px;
            margin-bottom: 2.5rem;
        }

        /* Stats */
        .stats {
            display: flex;
            gap: 2rem;
            padding-top: 2rem;
            border-top: 1px solid rgba(255,255,255,0.15);
        }

        .stat {
            display: flex;
            flex-direction: column;
        }

        .stat-value {
            font-family: 'IBM Plex Serif', serif;
            font-weight: 400;
            font-size: 1.75rem;
            letter-spacing: -0.02em;
            line-height: 1;
        }

        .stat-label {
            font-size: 0.78rem;
            color: rgba(255,255,255,0.6);
            margin-top: 0.35rem;
        }

        /* Animated gradient line on left panel bottom */
        .panel-left::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            animation: shimmer 3s ease-in-out infinite;
        }

        @keyframes shimmer {
            0%, 100% { opacity: 0.3; transform: translateX(-30%); }
            50% { opacity: 0.8; transform: translateX(30%); }
        }

        /* Stat values animate in */
        .stat-value {
            animation: countUp 0.8s var(--ease-out) both;
        }
        .stat:nth-child(1) .stat-value { animation-delay: 0.4s; }
        .stat:nth-child(2) .stat-value { animation-delay: 0.6s; }
        .stat:nth-child(3) .stat-value { animation-delay: 0.8s; }

        @keyframes countUp {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: none; }
        }

        /* ─── Right panel — Form ─── */
        .panel-right {
            flex: 1;
            padding: 3rem 2.5rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: var(--bg-card);
        }

        .form-header {
            margin-bottom: 2rem;
        }

        .form-header h1 {
            font-family: 'IBM Plex Serif', serif;
            font-weight: 300;
            font-size: 1.75rem;
            color: var(--accent);
            letter-spacing: -0.02em;
            margin-bottom: 0.4rem;
        }

        .form-header p {
            font-size: 0.9rem;
            color: var(--text-muted);
        }

        /* Form fields */
        .field {
            margin-bottom: 1.25rem;
        }

        .field label {
            display: block;
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 0.4rem;
        }

        .field .input-wrap {
            position: relative;
        }

        .field .input-wrap i.icon {
            position: absolute;
            left: 0.85rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 0.85rem;
            pointer-events: none;
            transition: color 0.2s;
        }

        .field input {
            width: 100%;
            padding: 0.7rem 0.85rem 0.7rem 2.5rem;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            font-family: 'IBM Plex Sans', sans-serif;
            font-size: 0.9rem;
            color: var(--text);
            background: var(--bg);
            transition: all 0.2s var(--ease-out);
            outline: none;
        }

        .field input::placeholder {
            color: var(--text-muted);
        }

        .field input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--accent-light);
            background: #fff;
        }

        .field input:focus + i.icon,
        .field input:focus ~ i.icon {
            color: var(--accent);
        }

        .password-toggle {
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            cursor: pointer;
            padding: 0.25rem;
            font-size: 0.85rem;
            transition: color 0.2s;
            z-index: 2;
        }

        .password-toggle:hover { color: var(--accent); }

        /* Remember + forgot */
        .form-options {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin: 1.25rem 0 1.5rem;
        }

        .remember-row {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .remember-row input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: var(--accent);
            cursor: pointer;
        }

        .remember-row label {
            font-size: 0.85rem;
            color: var(--text-secondary);
            cursor: pointer;
        }

        .forgot-link {
            font-size: 0.82rem;
            font-weight: 500;
            color: var(--accent);
            text-decoration: none;
            transition: color 0.2s;
        }

        .forgot-link:hover { color: var(--accent-hover); }

        /* Submit button */
        .btn-login {
            width: 100%;
            padding: 0.75rem;
            background: var(--accent);
            color: #fff;
            border: 1px solid var(--accent);
            border-radius: var(--radius);
            font-family: 'IBM Plex Sans', sans-serif;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s var(--ease-out);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            letter-spacing: -0.02em;
        }

        .btn-login:hover {
            background: var(--accent-hover);
            border-color: var(--accent-hover);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(4,83,203,0.3);
        }

        .btn-login:active { transform: none; }

        /* Footer */
        .login-footer {
            text-align: center;
            margin-top: 2rem;
            padding-top: 1.25rem;
            border-top: 1px solid var(--border);
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        /* Alerts */
        .alert {
            padding: 0.75rem 1rem;
            border-radius: var(--radius);
            font-size: 0.85rem;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
        }

        .alert-danger {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .alert-success {
            background: #f0fdf4;
            color: #16a34a;
            border: 1px solid #bbf7d0;
        }

        .alert-warning {
            background: #fffbeb;
            color: #d97706;
            border: 1px solid #fde68a;
        }

        .invalid-feedback {
            font-size: 0.78rem;
            color: #dc2626;
            margin-top: 0.3rem;
        }

        /* ─── Responsive ─── */
        @media (max-width: 768px) {
            body { background-attachment: scroll; }

            .login-card {
                flex-direction: column;
                margin: 1rem;
            }

            .panel-left {
                padding: 2rem 1.5rem;
            }

            .panel-left h2 { font-size: 1.5rem; }

            .stats { gap: 1.5rem; }
            .stat-value { font-size: 1.4rem; }

            .panel-right {
                padding: 2rem 1.5rem;
            }
        }

        @media (max-width: 480px) {
            .login-card { margin: 0.5rem; border-radius: 6px; }
            .stats { flex-wrap: wrap; gap: 1rem; }
        }
    </style>
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
                        placeholder="Votre mot de passe"
                        class="@error('password') is-invalid @enderror"
                        style="padding-right: 2.5rem;"
                    >
                    <i class="fas fa-lock icon"></i>
                    <i class="fas fa-eye password-toggle" id="togglePassword" title="Afficher le mot de passe"></i>
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
            });
        }
    })();
</script>
</body>
</html>

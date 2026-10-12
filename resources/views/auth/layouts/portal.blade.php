<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover, interactive-widget=resizes-content">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex,nofollow">
    <meta name="theme-color" content="#0453cb">
    <title>@yield('title', 'KLASSCI — Sécurité du compte')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Serif:wght@300;400;500&family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @include('auth.partials.login-styles')
    <style>
        .portal-help { margin: -.35rem 0 1rem; color: #64748b; font-size: .81rem; line-height: 1.5; }
        .portal-security { padding: .85rem 1rem; margin: 1.2rem 0; background: #f0f5ff; border-left: 3px solid #0453cb; color: #334155; border-radius: 4px; font-size: .83rem; line-height: 1.55; }
        .portal-link { display: inline-flex; align-items: center; gap: .5rem; margin-top: 1.25rem; color: #0453cb; text-decoration: none; font-weight: 600; font-size: .86rem; }
        .portal-link:hover { text-decoration: underline; }
        .field input[readonly] { background: #f0f3f7; color: #475569; }
        .field input.is-invalid { border-color: #dc2626; }
        .portal-toggle { border: 0; background: transparent; position: absolute; right: .65rem; top: 50%; transform: translateY(-50%); color: #64748b; cursor: pointer; padding: .35rem; z-index: 2; }
        .portal-toggle:hover, .portal-toggle:focus-visible { color: #0453cb; }
        .portal-form .field { margin-bottom: 1rem; }
        .portal-form .field input { min-height: 45px; padding-right: 2.6rem; }
        .portal-footnote { font-size: .77rem; margin-top: 1.1rem; color: #64748b; line-height: 1.5; }
        @media (max-width: 767.98px) { .portal-form .field input { font-size: 16px; } }
    </style>
</head>
<body>
    <div class="login-card" role="main">
        <aside class="panel-left">
            <div class="panel-left-content">
                <div class="logo-row">
                    <img src="{{ asset('images/LOGO-KLASSCI-PNG.png') }}" alt="Logo KLASSCI">
                    <span>KLASSCI</span>
                </div>
                <h2>Votre espace, en toute confiance.</h2>
                <p class="tagline">Retrouvez votre accès à l'écosystème numérique de votre établissement, avec la même sécurité partout.</p>
            </div>
        </aside>
        <div class="panel-right">
            <div class="form-header">
                <h1>@yield('heading', 'Sécurité du compte')</h1>
                <p>@yield('subtitle', 'Un accès protégé à votre espace')</p>
            </div>
            @if(session('status'))
                <div class="alert alert-success" role="status"><i class="fas fa-check-circle" aria-hidden="true"></i><span>{{ session('status') }}</span></div>
            @endif
            @if(session('warning'))
                <div class="alert alert-warning" role="alert"><i class="fas fa-exclamation-triangle" aria-hidden="true"></i><span>{{ session('warning') }}</span></div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
                    <div>@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
                </div>
            @endif
            @yield('content')
            <div class="login-footer">© {{ date('Y') }} KLASSCI · Votre sécurité est notre priorité</div>
        </div>
    </div>
    <script>
        document.querySelectorAll('[data-password-toggle]').forEach(function(button) {
            button.addEventListener('click', function() {
                const input = document.getElementById(button.dataset.passwordToggle);
                if (!input) return;
                const visible = input.type === 'password';
                input.type = visible ? 'text' : 'password';
                button.setAttribute('aria-label', visible ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
                button.setAttribute('aria-pressed', visible ? 'true' : 'false');
                const icon = button.querySelector('i');
                if (icon) { icon.classList.toggle('fa-eye', !visible); icon.classList.toggle('fa-eye-slash', visible); }
            });
        });
        const pass = document.getElementById('password'), confirm = document.getElementById('password_confirmation');
        if (pass && confirm) {
            const check = () => confirm.setCustomValidity(confirm.value && pass.value !== confirm.value ? 'Les deux mots de passe doivent être identiques.' : '');
            pass.addEventListener('input', check);
            confirm.addEventListener('input', check);
        }
    </script>
</body>
</html>

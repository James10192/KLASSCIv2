{{-- KLASSCI — Editorial public layout
     Used by: /docs, /docs/{slug}, /api-reference, /changelog
     (and progressively /, once welcome.blade.php is refactored).

     Yields:
       - title         : page <title>
       - description   : meta description
     Sections (all optional):
       - meta          : extra meta tags (canonical override, OG image, hreflang, JSON-LD)
       - body_class    : extra class on <body>
       - content       : main page content
     Stacks:
       - styles        : page-specific CSS pushed after landing.css
       - scripts       : page-specific JS pushed after landing.js
--}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'KLASSCI') — Gestion scolaire, repensée.</title>
    <meta name="description" content="@yield('description', 'KLASSCI digitalise la gestion de votre établissement scolaire. Notes, emplois du temps, paiements, bulletins — un seul outil, zéro paperasse.')">

    <meta name="keywords" content="gestion scolaire, logiciel école, KLASSCI, notes, bulletins, emploi du temps, paiements, présences, SaaS éducation, Côte d'Ivoire, Afrique">
    <meta name="author" content="African Digital Consulting">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ url()->current() }}">

    {{-- Open Graph --}}
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="@yield('title', 'KLASSCI') — Gestion scolaire, repensée.">
    <meta property="og:description" content="@yield('description', 'KLASSCI digitalise la gestion de votre établissement.')">
    <meta property="og:image" content="@yield('og_image', asset('images/landing/hero_section.png'))">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:locale" content="fr_FR">
    <meta property="og:site_name" content="KLASSCI">

    {{-- Twitter --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('title', 'KLASSCI')">
    <meta name="twitter:description" content="@yield('description', 'KLASSCI digitalise la gestion de votre établissement.')">
    <meta name="twitter:image" content="@yield('og_image', asset('images/landing/hero_section.png'))">

    @yield('meta')

    {{-- Favicon --}}
    <link rel="icon" href="{{ asset('images/LOGO-KLASSCI-PNG.png') }}" type="image/png">
    <link rel="apple-touch-icon" href="{{ asset('images/LOGO-KLASSCI-PNG.png') }}">
    <meta name="theme-color" content="#0453cb">

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Serif:wght@300;400;500&family=IBM+Plex+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    {{-- Shared chrome (cacheable) --}}
    <link rel="stylesheet" href="{{ asset('css/landing.css') }}?v={{ config('app.asset_version', '1') }}">

    @stack('styles')

    {{-- Prevent FOUC on theme --}}
    <script>
        (function () {
            var t = localStorage.getItem('klassci-theme');
            var h = document.documentElement;
            if (t === 'dark') { h.classList.add('dark'); h.classList.remove('light'); }
            else { h.classList.add('light'); h.classList.remove('dark'); }
        })();
    </script>
</head>
<body class="@yield('body_class')">

<a href="#main-content" class="skip-link">Aller au contenu principal</a>

{{-- NAV --}}
<nav class="nav" aria-label="Navigation principale">
    <div class="container nav-inner">
        <a href="{{ route('welcome') }}" class="nav-logo" aria-label="Accueil KLASSCI">
            <img src="{{ asset('images/LOGO-KLASSCI-PNG.png') }}" alt="KLASSCI">
            <span>KLASSCI</span>
        </a>
        <ul class="nav-links">
            <li><a href="{{ route('welcome') }}#fonctionnalites">Fonctionnalités</a></li>
            <li><a href="{{ route('welcome') }}#tarifs">Tarifs</a></li>
            <li><a href="{{ Route::has('docs.index') ? route('docs.index') : '#' }}">Documentation</a></li>
            <li><a href="{{ Route::has('changelog') ? route('changelog') : '#' }}">Changelog</a></li>
            <li><a href="{{ route('login') }}" class="nav-cta">Se connecter</a></li>
            <li>
                <button class="theme-toggle" id="themeToggle" aria-label="Changer le thème">
                    <span class="moon-group">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
                        <span>Sombre</span>
                    </span>
                    <span class="sun-group">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
                        <span>Clair</span>
                    </span>
                </button>
            </li>
        </ul>
        <div class="nav-mobile-actions">
            <button class="theme-toggle-mini" id="themeToggleMobile" aria-label="Changer le thème">
                <svg class="moon-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
                <svg class="sun-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
            </button>
            <button class="nav-hamburger" id="hamburger" aria-label="Menu" aria-controls="mobileNav" aria-expanded="false">
                <span></span><span></span><span></span>
            </button>
        </div>
    </div>
</nav>

<div class="mobile-nav" id="mobileNav">
    <a href="{{ route('welcome') }}#fonctionnalites" onclick="closeMobile()">Fonctionnalités</a>
    <a href="{{ route('welcome') }}#tarifs" onclick="closeMobile()">Tarifs</a>
    <a href="{{ Route::has('docs.index') ? route('docs.index') : '#' }}" onclick="closeMobile()">Documentation</a>
    <a href="{{ Route::has('changelog') ? route('changelog') : '#' }}" onclick="closeMobile()">Changelog</a>
    <a href="{{ route('welcome') }}#contact" onclick="closeMobile()">Contact</a>
    <a href="{{ route('login') }}" class="mobile-nav-cta" onclick="closeMobile()">Se connecter</a>
</div>

<main id="main-content">
    @yield('content')
</main>

{{-- FOOTER --}}
<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-brand">
                <a href="{{ route('welcome') }}" class="nav-logo" aria-label="Accueil KLASSCI">
                    <img src="{{ asset('images/LOGO-KLASSCI-PNG.png') }}" alt="KLASSCI" style="height:24px">
                    <span style="font-weight:700;font-size:0.95rem;color:var(--footer-text)">KLASSCI</span>
                </a>
                <p>Plateforme de gestion scolaire conçue pour les établissements d'enseignement supérieur en Afrique.</p>
            </div>
            <div class="footer-col">
                <h4>Produit</h4>
                <ul>
                    <li><a href="{{ route('welcome') }}#fonctionnalites">Fonctionnalités</a></li>
                    <li><a href="{{ route('welcome') }}#tarifs">Tarifs</a></li>
                    <li><a href="{{ route('login') }}">Connexion</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Ressources</h4>
                <ul>
                    <li><a href="{{ Route::has('docs.index') ? route('docs.index') : '#' }}">Documentation</a></li>
                    <li><a href="{{ Route::has('api-reference') ? route('api-reference') : '#' }}">API Reference</a></li>
                    <li><a href="{{ Route::has('changelog') ? route('changelog') : '#' }}">Changelog</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Contact</h4>
                <ul>
                    <li><a href="mailto:contact@klassci.com">contact@klassci.com</a></li>
                    <li><a href="{{ route('welcome') }}#contact">Demander une démo</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; {{ date('Y') }} KLASSCI — African Digital Consulting</p>
            <div class="footer-social">
                <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                <a href="#" aria-label="X"><i class="fab fa-x-twitter"></i></a>
            </div>
        </div>
    </div>
</footer>

<script src="{{ asset('js/landing.js') }}?v={{ config('app.asset_version', '1') }}" defer></script>
@stack('scripts')
</body>
</html>

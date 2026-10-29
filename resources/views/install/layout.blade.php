<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') - Installation KLASSCI</title>
    <link rel="shortcut icon" href="{{ asset('images/LOGO-KLASSCI-PNG.png') }}" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/vue@2.6.14/dist/vue.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <style>
        :root {
            --primary: #0453cb;
            --primary-dark: #073b8c;
            --primary-soft: #edf4ff;
            --ink: #10213d;
            --muted: #64748b;
            --canvas: #f3f6fb;
            --surface: #ffffff;
            --line: #dce4ef;
            --success: #0f9f6e;
            --danger: #d92d20;
            --warning: #b45309;
            --shadow-sm: 0 0 0 1px rgba(15, 23, 42, .06), 0 2px 8px rgba(15, 23, 42, .05);
            --shadow-lg: 0 0 0 1px rgba(15, 23, 42, .05), 0 22px 60px rgba(30, 64, 175, .10);
        }

        * { box-sizing: border-box; }

        html {
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        body {
            margin: 0;
            min-height: 100vh;
            color: var(--ink);
            background: var(--canvas);
            font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        a { color: inherit; text-decoration: none; }
        button, input { font: inherit; }
        code { font-family: "SFMono-Regular", Consolas, "Liberation Mono", monospace; }
        [v-cloak] { display: none !important; }

        .install-shell {
            width: min(1180px, calc(100% - 32px));
            margin: 28px auto 48px;
        }

        .install-topbar {
            min-height: 52px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            margin-bottom: 18px;
        }

        .brand {
            min-width: 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand-logo {
            width: 44px;
            height: 44px;
            flex: 0 0 auto;
            padding: 7px;
            border-radius: 12px;
            background: #fff;
            box-shadow: var(--shadow-sm);
            outline: 1px solid rgba(0, 0, 0, .1);
            outline-offset: -1px;
        }

        .brand-title {
            margin: 0;
            font-size: .95rem;
            font-weight: 800;
        }

        .brand-subtitle {
            margin: 3px 0 0;
            color: var(--muted);
            font-size: .8rem;
            text-wrap: pretty;
        }

        .install-console {
            overflow: hidden;
            border-radius: 20px;
            background: var(--surface);
            box-shadow: var(--shadow-lg);
        }

        .install-hero {
            position: relative;
            padding: 28px 32px 24px;
            color: #fff;
            background: var(--primary);
        }

        .install-hero::after {
            content: "";
            position: absolute;
            inset: 0 0 0 auto;
            width: 38%;
            pointer-events: none;
            opacity: .16;
            background-image: linear-gradient(90deg, transparent 0 31px, rgba(255, 255, 255, .42) 32px);
            background-size: 32px 100%;
            mask-image: linear-gradient(90deg, transparent, #000);
        }

        .hero-main {
            position: relative;
            z-index: 1;
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 32px;
            align-items: end;
        }

        .hero-kicker {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 0 0 10px;
            color: rgba(255, 255, 255, .76);
            font-size: .74rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .hero-kicker strong {
            max-width: 440px;
            overflow: hidden;
            color: #fff;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .hero-title {
            margin: 0;
            max-width: 680px;
            font-size: clamp(1.65rem, 3vw, 2.45rem);
            line-height: 1.08;
            font-weight: 800;
            text-wrap: balance;
        }

        .hero-copy {
            max-width: 620px;
            margin: 10px 0 0;
            color: rgba(255, 255, 255, .78);
            font-size: .9rem;
            line-height: 1.55;
            text-wrap: pretty;
        }

        .hero-progress {
            min-width: 190px;
            padding: 14px 16px;
            border-radius: 14px;
            background: rgba(0, 0, 0, .12);
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, .16);
        }

        .hero-progress-label {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 16px;
            color: rgba(255, 255, 255, .72);
            font-size: .72rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .hero-progress-label strong {
            color: #fff;
            font-size: .9rem;
            font-variant-numeric: tabular-nums;
        }

        .hero-meter {
            height: 5px;
            margin-top: 11px;
            overflow: hidden;
            border-radius: 999px;
            background: rgba(255, 255, 255, .22);
        }

        .hero-meter > i {
            display: block;
            width: var(--install-progress);
            height: 100%;
            border-radius: inherit;
            background: #fff;
        }

        .server-passport {
            position: relative;
            z-index: 1;
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0;
            margin-top: 24px;
            padding-top: 18px;
            border-top: 1px solid rgba(255, 255, 255, .18);
        }

        .passport-item {
            min-width: 0;
            padding: 0 18px;
            border-left: 1px solid rgba(255, 255, 255, .16);
        }

        .passport-item:first-child { padding-left: 0; border-left: 0; }
        .passport-item span { display: block; color: rgba(255, 255, 255, .62); font-size: .7rem; font-weight: 700; text-transform: uppercase; }
        .passport-item strong, .passport-item code { display: block; margin-top: 6px; overflow-wrap: anywhere; color: #fff; font-size: .8rem; font-weight: 600; }

        .stepper {
            position: relative;
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            padding: 0 24px;
            border-bottom: 1px solid var(--line);
            background: #fff;
        }

        .step {
            position: relative;
            min-height: 70px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 10px;
            color: #8995a8;
            font-size: .78rem;
            font-weight: 700;
        }

        .step::after {
            content: "";
            position: absolute;
            right: 10px;
            bottom: -1px;
            left: 10px;
            height: 2px;
            background: transparent;
        }

        .step-icon {
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
            border-radius: 10px;
            color: #7f8ca1;
            background: #eef2f7;
        }

        .step.is-active { color: var(--ink); }
        .step.is-active::after { background: var(--primary); }
        .step.is-active .step-icon { color: #fff; background: var(--primary); box-shadow: 0 7px 16px rgba(4, 83, 203, .24); }
        .step.is-complete { color: #4b5c75; }
        .step.is-complete .step-icon { color: var(--primary); background: var(--primary-soft); }

        .install-stage { padding: 34px 40px 40px; }
        .stage-header { max-width: 760px; margin-bottom: 28px; }
        .stage-number { display: flex; align-items: center; gap: 8px; margin-bottom: 9px; color: var(--primary); font-size: .74rem; font-weight: 800; text-transform: uppercase; }
        .stage-title h1 { margin: 0; font-size: clamp(1.45rem, 2.6vw, 2rem); line-height: 1.15; font-weight: 800; text-wrap: balance; }
        .stage-title p { margin: 9px 0 0; color: var(--muted); font-size: .9rem; line-height: 1.6; text-wrap: pretty; }
        .install-content { min-width: 0; }

        .grid-2 { display: grid; grid-template-columns: minmax(0, 1.55fr) minmax(260px, .75fr); gap: 32px; align-items: start; }
        .card { min-width: 0; background: #fff; }
        .card-pad { padding: 0; }
        .grid-2 > aside.card { padding: 20px; border-radius: 14px; background: #f7f9fc; box-shadow: inset 0 0 0 1px rgba(15, 23, 42, .06); }
        .section-title { margin: 0; font-size: 1.02rem; font-weight: 800; text-wrap: balance; }
        .section-copy { margin: 7px 0 0; color: var(--muted); font-size: .86rem; line-height: 1.55; text-wrap: pretty; }

        .grid-fields { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 18px; }
        .field label { display: block; margin-bottom: 7px; color: #31445f; font-size: .72rem; font-weight: 800; text-transform: uppercase; }
        .input { width: 100%; min-height: 46px; padding: 0 13px; border: 1px solid #cfd9e7; border-radius: 9px; outline: none; color: var(--ink); background: #fff; transition-property: border-color, box-shadow, background-color; transition-duration: 160ms; transition-timing-function: ease-out; }
        .input:hover { border-color: #aebdd1; }
        .input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(4, 83, 203, .12); }
        .hint { margin: 6px 0 0; color: var(--muted); font-size: .76rem; line-height: 1.45; text-wrap: pretty; }

        .btn-row { display: flex; align-items: center; justify-content: space-between; gap: 14px; margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--line); }
        .btn { min-height: 44px; display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 0 16px 0 18px; border: 0; border-radius: 9px; cursor: pointer; font-size: .82rem; font-weight: 800; transition-property: scale, box-shadow, background-color, color; transition-duration: 150ms; transition-timing-function: ease-out; }
        .btn:hover { box-shadow: 0 7px 18px rgba(15, 23, 42, .10); }
        .btn:active { scale: .96; }
        .btn:focus-visible { outline: 3px solid rgba(4, 83, 203, .24); outline-offset: 2px; }
        .btn:disabled, .btn.is-disabled { cursor: not-allowed; opacity: .52; box-shadow: none; scale: 1; }
        .btn.is-disabled { pointer-events: auto; }
        .btn-primary { color: #fff; background: var(--primary); box-shadow: 0 8px 20px rgba(4, 83, 203, .20); }
        .btn-primary:hover { background: #0349b5; }
        .btn-secondary { color: #29405f; background: #fff; box-shadow: var(--shadow-sm); }
        .btn-success { color: #fff; background: var(--success); }
        .site-link { white-space: nowrap; }

        .notice { display: flex; align-items: flex-start; gap: 11px; margin-top: 16px; padding: 13px 14px; border-radius: 10px; color: #29405f; background: #f6f8fb; box-shadow: inset 0 0 0 1px rgba(15, 23, 42, .07); }
        .notice > i { width: 18px; margin-top: 2px; text-align: center; }
        .notice.success { color: #07533d; background: #ecfdf5; box-shadow: inset 0 0 0 1px rgba(15, 159, 110, .24); }
        .notice.error { color: #8a1f16; background: #fff1f0; box-shadow: inset 0 0 0 1px rgba(217, 45, 32, .22); }
        .notice.warning { color: #7c3a04; background: #fff7ed; box-shadow: inset 0 0 0 1px rgba(180, 83, 9, .22); }

        .check-list { display: grid; gap: 0; margin-top: 14px; }
        .check-item { display: flex; align-items: flex-start; gap: 11px; padding: 13px 0; border-bottom: 1px solid var(--line); }
        .check-item:last-child { border-bottom: 0; }
        .check-icon { width: 30px; height: 30px; display: inline-flex; align-items: center; justify-content: center; flex: 0 0 auto; border-radius: 9px; color: var(--primary); background: var(--primary-soft); }
        .check-item.ok .check-icon { color: var(--success); background: #e7f8f2; }
        .check-item.fail .check-icon { color: var(--danger); background: #fff0ef; }

        .terminal { min-height: 260px; max-height: 420px; overflow: auto; padding: 16px; border-radius: 10px; color: #dbeafe; background: #07111f; font-family: "SFMono-Regular", Consolas, "Liberation Mono", monospace; font-size: .8rem; line-height: 1.6; white-space: pre-wrap; box-shadow: inset 0 0 0 1px rgba(255, 255, 255, .08); }
        .terminal-line { margin: 0; }
        .muted { color: var(--muted); }

        .install-footer { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 17px 32px; border-top: 1px solid var(--line); color: var(--muted); background: #fbfcfe; font-size: .74rem; }
        .install-footer strong { color: #40516a; }

        @media (max-width: 900px) {
            .hero-main, .grid-2 { grid-template-columns: 1fr; }
            .hero-progress { min-width: 0; }
            .stepper { overflow-x: auto; grid-template-columns: repeat(5, minmax(150px, 1fr)); }
        }

        @media (max-width: 640px) {
            .install-shell { width: min(100% - 16px, 1180px); margin: 8px auto 24px; }
            .install-topbar { align-items: stretch; flex-direction: column; margin: 0 8px 14px; }
            .site-link { width: 100%; }
            .install-console { border-radius: 14px; }
            .install-hero { padding: 24px 20px 20px; }
            .hero-main { gap: 22px; }
            .server-passport { grid-template-columns: 1fr; gap: 12px; }
            .passport-item, .passport-item:first-child { padding: 0; border: 0; }
            .stepper { padding: 0 10px; }
            .install-stage { padding: 28px 20px 30px; }
            .stage-header { margin-bottom: 24px; }
            .grid-fields { grid-template-columns: 1fr; }
            .btn-row { align-items: stretch; flex-direction: column; }
            .btn-row > div { width: 100%; }
            .btn { width: 100%; }
            .install-footer { align-items: flex-start; flex-direction: column; padding: 16px 20px; }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { scroll-behavior: auto !important; transition-duration: .01ms !important; animation-duration: .01ms !important; animation-iteration-count: 1 !important; }
        }
    </style>
</head>
<body>
    @php
        $steps = [
            'install.index' => ['label' => 'Préparer', 'icon' => 'fa-clipboard-check'],
            'install.database' => ['label' => 'Base de données', 'icon' => 'fa-database'],
            'install.migration' => ['label' => 'Initialiser', 'icon' => 'fa-terminal'],
            'install.admin' => ['label' => 'Administrateur', 'icon' => 'fa-user-shield'],
            'install.complete' => ['label' => 'Finaliser', 'icon' => 'fa-lock'],
        ];
        $stepKeys = array_keys($steps);
        $currentRoute = collect($stepKeys)->first(fn ($route) => request()->routeIs($route)) ?? 'install.index';
        $currentIndex = array_search($currentRoute, $stepKeys, true);
        $currentIndex = $currentIndex === false ? 0 : $currentIndex;
        $progress = (($currentIndex + 1) / count($steps)) * 100;
        $host = request()->getHost();
        $tenantCode = strtolower(preg_replace('/[^a-z0-9-]+/', '-', explode('.', $host)[0] ?? 'tenant'));
        $tenantCode = trim($tenantCode, '-') ?: 'tenant';
        $tenantName = config('app.name');
        if (!$tenantName || in_array(strtolower($tenantName), ['laravel', 'klassci'], true)) {
            $tenantName = strtoupper($tenantCode) . ' KLASSCI';
        }
        $tenantUrl = request()->getSchemeAndHttpHost();
        $tenantDocumentRoot = 'public_html/' . $tenantCode . '/public';
    @endphp

    <main class="install-shell">
        <div class="install-topbar">
            <div class="brand">
                <img class="brand-logo" src="{{ asset('images/LOGO-KLASSCI-PNG.png') }}" alt="Logo KLASSCI">
                <div>
                    <p class="brand-title">KLASSCI</p>
                    <p class="brand-subtitle">Assistant de mise en service établissement</p>
                </div>
            </div>
            <a class="btn btn-secondary site-link" href="https://klassci.com" target="_blank" rel="noreferrer">
                Découvrir KLASSCI
                <i class="fas fa-arrow-up-right-from-square"></i>
            </a>
        </div>

        <section class="install-console">
            <header class="install-hero">
                <div class="hero-main">
                    <div>
                        <p class="hero-kicker"><i class="fas fa-building-columns"></i> Espace à configurer <strong>{{ $tenantName }}</strong></p>
                        <h1 class="hero-title">Dossier de mise en service</h1>
                        <p class="hero-copy">Un parcours guidé pour vérifier le serveur, connecter les données et ouvrir le premier accès de l’établissement.</p>
                    </div>
                    <div class="hero-progress" style="--install-progress: {{ $progress }}%;">
                        <div class="hero-progress-label">
                            <span>Progression</span>
                            <strong>{{ $currentIndex + 1 }} / {{ count($steps) }}</strong>
                        </div>
                        <div class="hero-meter"><i></i></div>
                    </div>
                </div>

                <aside class="server-passport" aria-label="Contexte du serveur">
                    <div class="passport-item"><span>Domaine détecté</span><strong>{{ $host }}</strong></div>
                    <div class="passport-item"><span>Dossier public attendu</span><code>{{ $tenantDocumentRoot }}</code></div>
                    <div class="passport-item"><span>URL publique</span><code>{{ $tenantUrl }}</code></div>
                </aside>
            </header>

            <nav class="stepper" aria-label="Étapes d’installation">
                @foreach($steps as $route => $step)
                    @php
                        $index = array_search($route, $stepKeys, true);
                        $isActive = request()->routeIs($route);
                        $isComplete = $index !== false && $index < $currentIndex;
                    @endphp
                    <div class="step {{ $isActive ? 'is-active' : '' }} {{ $isComplete ? 'is-complete' : '' }}" aria-current="{{ $isActive ? 'step' : 'false' }}">
                        <span class="step-icon"><i class="fas {{ $isComplete ? 'fa-check' : $step['icon'] }}"></i></span>
                        <span>{{ $step['label'] }}</span>
                    </div>
                @endforeach
            </nav>

            <section class="install-stage">
                <header class="stage-header">
                    <div class="stage-number"><i class="fas {{ $steps[$currentRoute]['icon'] }}"></i> Étape {{ $currentIndex + 1 }} sur {{ count($steps) }}</div>
                    <div class="stage-title">
                        <h1>@yield('hero_title', 'Installation du tenant')</h1>
                        <p>@yield('hero_copy', 'Configurez cet espace après la création du sous-domaine, du dossier cPanel et de la base MySQL.')</p>
                    </div>
                </header>

                <div class="install-content">@yield('content')</div>
            </section>

            <footer class="install-footer">
                <span><strong>Installation sécurisée</strong> · Les secrets ne sont jamais affichés dans les réponses.</span>
                <span>Les opérations techniques retournent un état JSON vérifiable.</span>
            </footer>
        </section>
    </main>

    <script>
        axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
        axios.defaults.headers.common['Accept'] = 'application/json';
        axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]')?.content;

        window.klassciInstall = {
            defaults: {
                appName: @json($tenantName),
                appUrl: @json($tenantUrl),
                tenantCode: @json($tenantCode),
                documentRoot: @json($tenantDocumentRoot),
                host: @json($host)
            },
            message(error, fallback) {
                const data = error?.response?.data;
                if (data?.message) return data.message;
                if (data?.errors) return Object.values(data.errors).flat().join(' ');
                return fallback || 'Une erreur est survenue.';
            }
        };
    </script>
    @yield('scripts')
</body>
</html>

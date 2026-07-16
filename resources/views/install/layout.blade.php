<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') - Installation KLASSCI</title>
    <link rel="shortcut icon" href="{{ asset('images/LOGO-KLASSCI-PNG.png') }}" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/vue@2.6.14/dist/vue.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <style>
        :root {
            --primary: #0453cb;
            --primary-ink: #06357d;
            --primary-soft: #eaf2ff;
            --ink: #0f172a;
            --muted: #64748b;
            --quiet: #f6f9fe;
            --line: #d8e2f1;
            --surface: #ffffff;
            --success: #0f9f6e;
            --danger: #d92d20;
            --warning: #b45309;
            --shadow-ring: 0 0 0 1px rgba(0, 0, 0, .06), 0 12px 28px rgba(15, 23, 42, .07);
            --shadow-lift: 0 0 0 1px rgba(0, 0, 0, .06), 0 24px 60px rgba(15, 23, 42, .13);
        }

        * { box-sizing: border-box; }

        html {
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: var(--ink);
            background:
                linear-gradient(90deg, rgba(4, 83, 203, .055) 1px, transparent 1px),
                linear-gradient(180deg, rgba(4, 83, 203, .055) 1px, transparent 1px),
                linear-gradient(180deg, #f9fbff 0%, #edf4ff 100%);
            background-size: 44px 44px, 44px 44px, auto;
        }

        a { color: inherit; text-decoration: none; }
        button, input { font: inherit; }
        code { font-family: "SFMono-Regular", Consolas, "Liberation Mono", monospace; }

        .install-shell {
            width: min(1240px, calc(100% - 28px));
            margin: 20px auto;
        }

        .install-topbar {
            min-height: 56px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 14px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }

        .brand-logo {
            width: 48px;
            height: 48px;
            border-radius: 16px;
            padding: 8px;
            background: #fff;
            box-shadow: var(--shadow-ring);
            outline: 1px solid rgba(0, 0, 0, .1);
            outline-offset: -1px;
        }

        .brand-title {
            margin: 0;
            font-size: 1rem;
            font-weight: 900;
            letter-spacing: 0;
        }

        .brand-subtitle {
            margin: 3px 0 0;
            color: var(--muted);
            font-size: .86rem;
            text-wrap: pretty;
        }

        .site-link {
            white-space: nowrap;
        }

        .install-console {
            display: grid;
            grid-template-columns: 292px minmax(0, 1fr);
            min-height: calc(100vh - 104px);
            overflow: hidden;
            border-radius: 28px;
            background: rgba(255, 255, 255, .86);
            box-shadow: var(--shadow-lift);
        }

        .install-rail {
            position: relative;
            padding: 22px;
            color: #fff;
            background:
                linear-gradient(180deg, rgba(4, 83, 203, .98), rgba(6, 53, 125, .98));
        }

        .rail-kicker {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-height: 32px;
            padding: 0 10px;
            border-radius: 999px;
            background: rgba(255, 255, 255, .13);
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, .18);
            font-size: .76rem;
            font-weight: 800;
        }

        .rail-title {
            margin: 18px 0 8px;
            font-size: 1.55rem;
            line-height: 1.05;
            font-weight: 900;
            letter-spacing: 0;
            text-wrap: balance;
        }

        .rail-copy {
            margin: 0;
            color: rgba(255, 255, 255, .78);
            font-size: .9rem;
            line-height: 1.55;
            text-wrap: pretty;
        }

        .rail-progress {
            margin-top: 22px;
            padding: 14px;
            border-radius: 18px;
            background: rgba(255, 255, 255, .11);
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, .16);
        }

        .rail-progress span {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            color: rgba(255, 255, 255, .74);
            font-size: .76rem;
            font-weight: 800;
            text-transform: uppercase;
        }

        .rail-progress strong {
            color: #fff;
            font-variant-numeric: tabular-nums;
        }

        .rail-meter {
            height: 8px;
            margin-top: 10px;
            overflow: hidden;
            border-radius: 999px;
            background: rgba(255, 255, 255, .18);
        }

        .rail-meter > i {
            display: block;
            width: var(--install-progress);
            height: 100%;
            border-radius: inherit;
            background: #fff;
        }

        .stepper {
            display: grid;
            gap: 8px;
            margin-top: 18px;
        }

        .step {
            min-height: 48px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 10px;
            border-radius: 15px;
            color: rgba(255, 255, 255, .72);
            font-size: .82rem;
            font-weight: 800;
        }

        .step-icon {
            width: 30px;
            height: 30px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
            border-radius: 11px;
            background: rgba(255, 255, 255, .11);
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, .14);
        }

        .step.is-active {
            color: var(--primary-ink);
            background: #fff;
            box-shadow: 0 12px 24px rgba(0, 0, 0, .16);
        }

        .step.is-active .step-icon {
            color: #fff;
            background: var(--primary);
            box-shadow: none;
        }

        .step.is-complete {
            color: #fff;
        }

        .rail-note {
            margin-top: 18px;
            padding: 14px;
            border-radius: 18px;
            background: rgba(255, 255, 255, .09);
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, .13);
        }

        .rail-note strong {
            display: block;
            font-size: .86rem;
        }

        .rail-note p {
            margin: 6px 0 0;
            color: rgba(255, 255, 255, .73);
            font-size: .8rem;
            line-height: 1.5;
            text-wrap: pretty;
        }

        .install-stage {
            min-width: 0;
            padding: 24px;
        }

        .stage-header {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(280px, 360px);
            gap: 18px;
            align-items: stretch;
            margin-bottom: 18px;
        }

        .stage-title {
            padding: 22px;
            border-radius: 22px;
            background: #fff;
            box-shadow: var(--shadow-ring);
        }

        .stage-title span {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-height: 30px;
            padding: 0 10px;
            border-radius: 999px;
            color: var(--primary);
            background: var(--primary-soft);
            font-size: .76rem;
            font-weight: 900;
        }

        .stage-title h1 {
            margin: 14px 0 8px;
            max-width: 760px;
            font-size: clamp(1.7rem, 3.3vw, 3.25rem);
            line-height: 1.02;
            font-weight: 900;
            letter-spacing: 0;
            text-wrap: balance;
        }

        .stage-title p {
            margin: 0;
            max-width: 760px;
            color: var(--muted);
            line-height: 1.55;
            text-wrap: pretty;
        }

        .server-passport {
            display: grid;
            gap: 10px;
            padding: 16px;
            border-radius: 22px;
            background: var(--primary-ink);
            color: #fff;
            box-shadow: var(--shadow-ring);
        }

        .passport-item {
            padding: 12px;
            border-radius: 15px;
            background: rgba(255, 255, 255, .09);
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, .11);
        }

        .passport-item span {
            display: block;
            color: rgba(255, 255, 255, .66);
            font-size: .72rem;
            font-weight: 900;
            text-transform: uppercase;
        }

        .passport-item strong,
        .passport-item code {
            display: block;
            margin-top: 7px;
            color: #fff;
            font-size: .9rem;
            font-weight: 800;
            white-space: normal;
            overflow-wrap: anywhere;
        }

        .install-content {
            min-width: 0;
        }

        .card {
            background: var(--surface);
            border-radius: 20px;
            box-shadow: var(--shadow-ring);
        }

        .card-pad { padding: 22px; }

        .section-title {
            margin: 0;
            font-size: 1.12rem;
            font-weight: 900;
            letter-spacing: 0;
            text-wrap: balance;
        }

        .section-copy {
            margin: 7px 0 0;
            color: var(--muted);
            font-size: .92rem;
            line-height: 1.55;
            text-wrap: pretty;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: minmax(0, 1.25fr) minmax(280px, .75fr);
            gap: 18px;
        }

        .grid-fields {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        .field label {
            display: block;
            margin-bottom: 7px;
            color: #18335f;
            font-size: .76rem;
            font-weight: 900;
            text-transform: uppercase;
        }

        .input {
            width: 100%;
            min-height: 46px;
            padding: 0 14px;
            border: 1px solid var(--line);
            border-radius: 13px;
            background: #fff;
            color: var(--ink);
            outline: none;
            transition-property: border-color, box-shadow, background-color;
            transition-duration: .18s;
            transition-timing-function: ease;
        }

        .input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(4, 83, 203, .12);
        }

        .hint {
            margin: 6px 0 0;
            color: var(--muted);
            font-size: .78rem;
            line-height: 1.45;
            text-wrap: pretty;
        }

        .btn-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-top: 20px;
        }

        .btn {
            min-height: 44px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 0 16px;
            border-radius: 13px;
            border: 1px solid transparent;
            cursor: pointer;
            font-weight: 900;
            transition-property: transform, box-shadow, background-color, border-color, color;
            transition-duration: .16s;
            transition-timing-function: ease;
        }

        .btn:hover { transform: translateY(-1px); }
        .btn:active { transform: scale(.96); }
        .btn:disabled { cursor: not-allowed; opacity: .58; transform: none; }
        .btn.is-disabled { cursor: not-allowed; opacity: .58; transform: none; pointer-events: auto; }
        .btn-primary { background: var(--primary); color: #fff; box-shadow: 0 12px 22px rgba(4, 83, 203, .22); }
        .btn-secondary { background: #fff; color: var(--primary); border-color: var(--line); }
        .btn-success { background: var(--success); color: #fff; }

        .notice {
            display: flex;
            gap: 12px;
            padding: 14px;
            border-radius: 16px;
            background: var(--quiet);
            box-shadow: 0 0 0 1px rgba(0, 0, 0, .06);
            color: #18335f;
            margin-top: 14px;
        }

        .notice.success { box-shadow: 0 0 0 1px rgba(15,159,110,.25); background: #ecfdf5; color: #07533d; }
        .notice.error { box-shadow: 0 0 0 1px rgba(217,45,32,.25); background: #fff1f0; color: #8a1f16; }
        .notice.warning { box-shadow: 0 0 0 1px rgba(180,83,9,.25); background: #fff7ed; color: #7c3a04; }

        .check-list {
            display: grid;
            gap: 10px;
            margin-top: 16px;
        }

        .check-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 14px;
            border-radius: 16px;
            background: #fff;
            box-shadow:
                0 0 0 1px rgba(0, 0, 0, .06),
                0 1px 2px rgba(15, 23, 42, .04);
        }

        .check-icon {
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background: var(--primary-soft);
            color: var(--primary);
            flex: 0 0 auto;
        }

        .check-item.ok .check-icon { background: #dcfce7; color: var(--success); }
        .check-item.fail .check-icon { background: #fee2e2; color: var(--danger); }

        .terminal {
            min-height: 260px;
            max-height: 420px;
            overflow: auto;
            padding: 16px;
            border-radius: 16px;
            background: #07111f;
            color: #dbeafe;
            font-family: "SFMono-Regular", Consolas, "Liberation Mono", monospace;
            font-size: .82rem;
            line-height: 1.55;
            white-space: pre-wrap;
        }

        .terminal-line { margin: 0; }
        .muted { color: var(--muted); }

        @media (max-width: 980px) {
            .install-console,
            .stage-header,
            .grid-2,
            .grid-fields {
                grid-template-columns: 1fr;
            }

            .install-console {
                min-height: auto;
            }

            .install-rail {
                padding: 18px;
            }

            .stepper {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 640px) {
            .install-shell {
                width: min(100% - 16px, 1240px);
                margin-top: 8px;
            }

            .install-topbar,
            .btn-row {
                align-items: stretch;
                flex-direction: column;
            }

            .site-link,
            .btn {
                width: 100%;
            }

            .install-stage {
                padding: 14px;
            }

            .stage-title,
            .card-pad {
                padding: 18px;
            }

            .stepper {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    @php
        $steps = [
            'install.index' => ['label' => 'Préparer', 'icon' => 'fa-clipboard-check'],
            'install.database' => ['label' => 'Base', 'icon' => 'fa-database'],
            'install.migration' => ['label' => 'Initialiser', 'icon' => 'fa-terminal'],
            'install.admin' => ['label' => 'Admin', 'icon' => 'fa-user-shield'],
            'install.complete' => ['label' => 'Terminer', 'icon' => 'fa-lock'],
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
                <img class="brand-logo" src="{{ asset('images/LOGO-KLASSCI-PNG.png') }}" alt="KLASSCI">
                <div>
                    <p class="brand-title">KLASSCI</p>
                    <p class="brand-subtitle">Mise en service d’un espace établissement</p>
                </div>
            </div>
            <a class="btn btn-secondary site-link" href="https://klassci.com" target="_blank" rel="noreferrer">
                <i class="fas fa-arrow-up-right-from-square"></i>
                Site KLASSCI
            </a>
        </div>

        <section class="install-console">
            <aside class="install-rail">
                <span class="rail-kicker"><i class="fas fa-building-columns"></i> {{ $tenantName }}</span>
                <h1 class="rail-title">Dossier de mise en service</h1>
                <p class="rail-copy">Suivez les contrôles dans l’ordre. Chaque étape écrit ou vérifie un élément nécessaire au tenant.</p>

                <div class="rail-progress" style="--install-progress: {{ $progress }}%;">
                    <span>
                        <em>Avancement</em>
                        <strong>{{ $currentIndex + 1 }}/{{ count($steps) }}</strong>
                    </span>
                    <div class="rail-meter"><i></i></div>
                </div>

                <nav class="stepper" aria-label="Étapes d’installation">
                    @foreach($steps as $route => $step)
                        @php
                            $index = array_search($route, $stepKeys, true);
                            $isActive = request()->routeIs($route);
                            $isComplete = $index !== false && $index < $currentIndex;
                        @endphp
                        <div class="step {{ $isActive ? 'is-active' : '' }} {{ $isComplete ? 'is-complete' : '' }}">
                            <span class="step-icon">
                                <i class="fas {{ $isComplete ? 'fa-check' : $step['icon'] }}"></i>
                            </span>
                            {{ $step['label'] }}
                        </div>
                    @endforeach
                </nav>

                <div class="rail-note">
                    <strong>Installation sans rechargement inutile</strong>
                    <p>Les contrôles et mutations utilisent des réponses JSON lisibles, avec messages d’erreur actionnables.</p>
                </div>
            </aside>

            <section class="install-stage">
                <header class="stage-header">
                    <div class="stage-title">
                        <span><i class="fas {{ $steps[$currentRoute]['icon'] }}"></i> Étape {{ $currentIndex + 1 }}</span>
                        <h1>@yield('hero_title', 'Installation tenant')</h1>
                        <p>@yield('hero_copy', 'Configurez le tenant Laravel après la création du sous-domaine, du dossier cPanel et de la base MySQL.')</p>
                    </div>
                    <aside class="server-passport" aria-label="Contexte serveur">
                        <div class="passport-item">
                            <span>Domaine détecté</span>
                            <strong>{{ $host }}</strong>
                        </div>
                        <div class="passport-item">
                            <span>Dossier public attendu</span>
                            <code>{{ $tenantDocumentRoot }}</code>
                        </div>
                        <div class="passport-item">
                            <span>URL publique</span>
                            <code>{{ $tenantUrl }}</code>
                        </div>
                    </aside>
                </header>

                <div class="install-content">
                    @yield('content')
                </div>
            </section>
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

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
            --primary-2: #2f7fe7;
            --ink: #0f172a;
            --muted: #64748b;
            --line: #d8e2f1;
            --surface: #ffffff;
            --soft: #f4f8ff;
            --success: #0f9f6e;
            --danger: #d92d20;
            --warning: #b45309;
            --radius: 16px;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: var(--ink);
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            background:
                radial-gradient(circle at top left, rgba(4, 83, 203, .12), transparent 32rem),
                linear-gradient(180deg, #f7fbff 0%, #edf4ff 100%);
        }

        a { color: inherit; text-decoration: none; }
        button, input { font: inherit; }

        .install-shell {
            width: min(1180px, calc(100% - 28px));
            margin: 24px auto;
        }

        .install-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 18px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand-logo {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            background: var(--surface);
            box-shadow:
                0 0 0 1px rgba(0, 0, 0, .06),
                0 14px 30px rgba(15, 23, 42, .12);
            padding: 8px;
            outline: 1px solid rgba(0, 0, 0, .1);
            outline-offset: -1px;
        }

        .brand-title {
            margin: 0;
            font-size: 1rem;
            font-weight: 800;
            letter-spacing: 0;
        }

        .brand-subtitle {
            margin: 3px 0 0;
            color: var(--muted);
            font-size: .875rem;
        }

        .install-frame {
            background: rgba(255, 255, 255, 0.82);
            border-radius: 24px;
            box-shadow:
                0 0 0 1px rgba(0, 0, 0, .06),
                0 24px 70px rgba(15, 23, 42, .12);
            overflow: hidden;
        }

        .install-hero {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(280px, 360px);
            gap: 18px;
            align-items: center;
            padding: 24px;
            background:
                linear-gradient(135deg, rgba(4, 83, 203, .98), rgba(47, 127, 231, .96));
            color: #fff;
        }

        .hero-main {
            min-width: 0;
        }

        .hero-kicker {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-height: 32px;
            padding: 0 12px;
            border: 1px solid rgba(255,255,255,.28);
            border-radius: 999px;
            background: rgba(255,255,255,.12);
            font-size: .82rem;
            font-weight: 700;
            max-width: 100%;
        }

        .install-hero h1 {
            margin: 14px 0 8px;
            font-size: clamp(1.8rem, 4vw, 3.2rem);
            line-height: 1.04;
            letter-spacing: 0;
            text-wrap: balance;
        }

        .install-hero p {
            margin: 0;
            max-width: 720px;
            color: rgba(255,255,255,.88);
            text-wrap: pretty;
        }

        .hero-domain {
            display: grid;
            gap: 12px;
            padding: 16px;
            border-radius: 18px;
            background: rgba(255,255,255,.12);
            box-shadow:
                inset 0 0 0 1px rgba(255,255,255,.22),
                0 18px 40px rgba(0,0,0,.1);
            font-variant-numeric: tabular-nums;
        }

        .hero-domain-item span {
            display: block;
            color: rgba(255,255,255,.72);
            font-size: .78rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .hero-domain-item strong {
            display: block;
            margin-top: 8px;
            font-size: 1rem;
            overflow-wrap: anywhere;
        }

        .hero-domain-item code {
            display: block;
            margin-top: 8px;
            font-size: .9rem;
            color: #fff;
            white-space: normal;
            overflow-wrap: anywhere;
        }

        .install-body { padding: 24px; }

        .stepper {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 8px;
            margin-bottom: 20px;
        }

        .step {
            min-height: 44px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            border-radius: 14px;
            color: var(--muted);
            background: var(--soft);
            box-shadow:
                0 0 0 1px rgba(0, 0, 0, .06),
                0 1px 2px rgba(15, 23, 42, .04);
            font-size: .82rem;
            font-weight: 700;
        }

        .step.is-active {
            color: #fff;
            background: var(--primary);
            box-shadow:
                0 0 0 1px rgba(4, 83, 203, .35),
                0 10px 18px rgba(4, 83, 203, .18);
        }

        .card {
            background: var(--surface);
            border-radius: var(--radius);
            box-shadow:
                0 0 0 1px rgba(0, 0, 0, .06),
                0 14px 32px rgba(15, 23, 42, .06);
        }

        .card-pad { padding: 22px; }

        .section-title {
            margin: 0;
            font-size: 1.12rem;
            font-weight: 800;
            letter-spacing: 0;
            text-wrap: balance;
        }

        .section-copy {
            margin: 6px 0 0;
            color: var(--muted);
            font-size: .92rem;
            text-wrap: pretty;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: minmax(0, 1.35fr) minmax(300px, .65fr);
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
            font-size: .78rem;
            font-weight: 800;
            color: #18335f;
            text-transform: uppercase;
        }

        .input {
            width: 100%;
            min-height: 44px;
            padding: 0 13px;
            border: 1px solid var(--line);
            border-radius: 12px;
            background: #fff;
            color: var(--ink);
            outline: none;
            transition: border-color .18s ease, box-shadow .18s ease, background-color .18s ease;
        }

        .input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(4, 83, 203, .12);
        }

        .hint {
            margin: 6px 0 0;
            color: var(--muted);
            font-size: .78rem;
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
            border-radius: 12px;
            border: 1px solid transparent;
            cursor: pointer;
            font-weight: 800;
            transition-property: transform, box-shadow, background-color, border-color;
            transition-duration: .18s;
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
            border-radius: 14px;
            background: var(--soft);
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
            border-radius: 14px;
            background: #fff;
            box-shadow:
                0 0 0 1px rgba(0, 0, 0, .06),
                0 1px 2px rgba(15, 23, 42, .04);
        }

        .check-icon {
            width: 30px;
            height: 30px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            background: #eaf2ff;
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
            border-radius: 14px;
            background: #07111f;
            color: #dbeafe;
            font-family: "SFMono-Regular", Consolas, "Liberation Mono", monospace;
            font-size: .82rem;
            line-height: 1.55;
            white-space: pre-wrap;
        }

        .terminal-line { margin: 0; }
        .muted { color: var(--muted); }

        .setup-strip {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
            margin-bottom: 18px;
        }

        .setup-fact {
            min-height: 72px;
            padding: 14px;
            border-radius: 16px;
            background: #fff;
            box-shadow:
                0 0 0 1px rgba(0, 0, 0, .06),
                0 10px 22px rgba(15, 23, 42, .05);
        }

        .setup-fact span {
            display: block;
            color: var(--muted);
            font-size: .72rem;
            font-weight: 800;
            text-transform: uppercase;
        }

        .setup-fact strong,
        .setup-fact code {
            display: block;
            margin-top: 8px;
            color: var(--ink);
            font-size: .92rem;
            font-weight: 800;
            white-space: normal;
            overflow-wrap: anywhere;
        }

        @media (max-width: 860px) {
            .install-hero, .grid-2, .grid-fields { grid-template-columns: 1fr; }
            .stepper { grid-template-columns: 1fr; }
            .setup-strip { grid-template-columns: 1fr; }
            .install-shell { width: min(100% - 18px, 1180px); margin-top: 10px; }
            .install-body, .install-hero { padding: 18px; }
        }
    </style>
</head>
<body>
    @php
        $steps = [
            'install.index' => 'Préparer',
            'install.database' => 'Base',
            'install.migration' => 'Initialiser',
            'install.admin' => 'Admin',
            'install.complete' => 'Terminer',
        ];
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
                    <p class="brand-subtitle">Assistant d’installation tenant</p>
                </div>
            </div>
            <a class="btn btn-secondary" href="https://klassci.com" target="_blank" rel="noreferrer">
                <i class="fas fa-arrow-up-right-from-square"></i>
                Site KLASSCI
            </a>
        </div>

        <section class="install-frame">
            <header class="install-hero">
                <div class="hero-main">
                    <span class="hero-kicker"><i class="fas fa-building-columns"></i> {{ $tenantName }}</span>
                    <h1>@yield('hero_title', 'Installation tenant')</h1>
                    <p>@yield('hero_copy', 'Configurez le tenant Laravel après la création du sous-domaine, du dossier cPanel et de la base MySQL.')</p>
                </div>
                <div class="hero-domain">
                    <div class="hero-domain-item">
                        <span>Domaine détecté</span>
                        <strong>{{ $host }}</strong>
                    </div>
                    <div class="hero-domain-item">
                        <span>Dossier public attendu</span>
                        <code>{{ $tenantDocumentRoot }}</code>
                    </div>
                </div>
            </header>

            <div class="install-body">
                <div class="setup-strip">
                    <div class="setup-fact">
                        <span>Tenant</span>
                        <strong>{{ $tenantCode }}</strong>
                    </div>
                    <div class="setup-fact">
                        <span>URL publique</span>
                        <code>{{ $tenantUrl }}</code>
                    </div>
                    <div class="setup-fact">
                        <span>Mode</span>
                        <strong>Installation contrôlée</strong>
                    </div>
                </div>

                <nav class="stepper" aria-label="Étapes d’installation">
                    @foreach($steps as $route => $label)
                        <div class="step {{ request()->routeIs($route) ? 'is-active' : '' }}">
                            <i class="fas fa-circle-dot"></i>
                            {{ $label }}
                        </div>
                    @endforeach
                </nav>

                @yield('content')
            </div>
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

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
            --primary-2: #1d6fe8;
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
            background:
                linear-gradient(135deg, rgba(4, 83, 203, 0.08), rgba(255, 255, 255, 0.82) 42%),
                #edf4ff;
        }

        a { color: inherit; text-decoration: none; }
        button, input { font: inherit; }

        .install-shell {
            width: min(1180px, calc(100% - 32px));
            margin: 28px auto;
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
            box-shadow: 0 14px 30px rgba(15, 23, 42, 0.12);
            padding: 8px;
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
            border: 1px solid rgba(216, 226, 241, 0.9);
            border-radius: 24px;
            box-shadow: 0 24px 70px rgba(15, 23, 42, 0.12);
            overflow: hidden;
        }

        .install-hero {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 24px;
            align-items: center;
            padding: 28px;
            background: linear-gradient(135deg, var(--primary), var(--primary-2));
            color: #fff;
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
        }

        .install-hero h1 {
            margin: 14px 0 8px;
            font-size: clamp(1.8rem, 4vw, 3.2rem);
            line-height: 1.02;
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
            min-width: 230px;
            padding: 16px;
            border-radius: 18px;
            background: rgba(255,255,255,.12);
            border: 1px solid rgba(255,255,255,.22);
            font-variant-numeric: tabular-nums;
        }

        .hero-domain span {
            display: block;
            color: rgba(255,255,255,.72);
            font-size: .78rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .hero-domain strong {
            display: block;
            margin-top: 8px;
            font-size: 1rem;
        }

        .install-body { padding: 28px; }

        .stepper {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 10px;
            margin-bottom: 22px;
        }

        .step {
            min-height: 44px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            border-radius: 14px;
            color: var(--muted);
            background: var(--soft);
            border: 1px solid var(--line);
            font-size: .82rem;
            font-weight: 700;
        }

        .step.is-active {
            color: #fff;
            background: var(--primary);
            border-color: var(--primary);
        }

        .card {
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: var(--radius);
            box-shadow: 0 14px 32px rgba(15, 23, 42, 0.06);
        }

        .card-pad { padding: 22px; }

        .section-title {
            margin: 0;
            font-size: 1.12rem;
            font-weight: 800;
            letter-spacing: 0;
        }

        .section-copy {
            margin: 6px 0 0;
            color: var(--muted);
            font-size: .92rem;
            text-wrap: pretty;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: minmax(0, 1.3fr) minmax(320px, .7fr);
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
            transition: transform .18s ease, box-shadow .18s ease, background-color .18s ease, border-color .18s ease;
        }

        .btn:hover { transform: translateY(-1px); }
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
            border: 1px solid var(--line);
            background: var(--soft);
            color: #18335f;
            margin-top: 14px;
        }

        .notice.success { border-color: rgba(15,159,110,.25); background: #ecfdf5; color: #07533d; }
        .notice.error { border-color: rgba(217,45,32,.25); background: #fff1f0; color: #8a1f16; }
        .notice.warning { border-color: rgba(180,83,9,.25); background: #fff7ed; color: #7c3a04; }

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
            border: 1px solid var(--line);
            border-radius: 14px;
            background: #fff;
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

        @media (max-width: 860px) {
            .install-hero, .grid-2, .grid-fields { grid-template-columns: 1fr; }
            .stepper { grid-template-columns: 1fr; }
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
                <div>
                    <span class="hero-kicker"><i class="fas fa-building-columns"></i> Université internationale de Cocody</span>
                    <h1>@yield('hero_title', 'Installation UIC')</h1>
                    <p>@yield('hero_copy', 'Configurez le tenant Laravel après la création du sous-domaine, du dossier cPanel et de la base MySQL.')</p>
                </div>
                <div class="hero-domain">
                    <span>Domaine cible</span>
                    <strong>uic.klassci.com</strong>
                </div>
            </header>

            <div class="install-body">
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

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Vérifier un document officiel · KLASSCI</title>
    <style>
        :root {
            --accent: #0453cb;
            --text: #14213d;
            --muted: #607089;
            --line: #d9e2ef;
            --surface: #ffffff;
            --bg: #f4f7fb;
            --ok: #047857;
            --bad: #b42318;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text);
            background:
                linear-gradient(135deg, rgba(4, 83, 203, .08), transparent 36%),
                linear-gradient(180deg, #fff, var(--bg));
        }
        main {
            width: min(980px, calc(100% - 32px));
            margin: 0 auto;
            padding: 48px 0;
        }
        .shell {
            display: grid;
            grid-template-columns: 1fr 420px;
            gap: 28px;
            align-items: start;
        }
        .hero, .card {
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: 24px;
            box-shadow: 0 24px 70px rgba(20, 33, 61, .08);
        }
        .hero {
            padding: 34px;
            min-height: 360px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .brand {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-weight: 800;
            letter-spacing: .08em;
            color: var(--accent);
            text-transform: uppercase;
        }
        .mark {
            display: grid;
            place-items: center;
            width: 38px;
            height: 38px;
            border-radius: 12px;
            color: #fff;
            background: var(--accent);
        }
        h1 {
            max-width: 680px;
            margin: 42px 0 14px;
            font-size: clamp(2rem, 5vw, 4rem);
            line-height: .98;
            letter-spacing: -.05em;
        }
        .lead {
            max-width: 620px;
            margin: 0;
            color: var(--muted);
            font-size: 1.05rem;
            line-height: 1.7;
        }
        .facts {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-top: 36px;
        }
        .fact {
            padding: 14px;
            border: 1px solid var(--line);
            border-radius: 16px;
            background: #fbfdff;
            font-size: .82rem;
            color: var(--muted);
        }
        .fact strong {
            display: block;
            margin-bottom: 5px;
            color: var(--text);
            font-size: .95rem;
        }
        .card { padding: 24px; }
        label {
            display: block;
            margin: 0 0 8px;
            color: var(--muted);
            font-size: .78rem;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
        }
        input {
            width: 100%;
            min-height: 48px;
            padding: 12px 14px;
            border: 1px solid var(--line);
            border-radius: 14px;
            color: var(--text);
            background: #fff;
            font: inherit;
        }
        input:focus {
            outline: 3px solid rgba(4, 83, 203, .14);
            border-color: var(--accent);
        }
        .field { margin-bottom: 16px; }
        button {
            width: 100%;
            min-height: 50px;
            border: 0;
            border-radius: 16px;
            color: #fff;
            background: var(--accent);
            font-weight: 800;
            cursor: pointer;
        }
        .result {
            margin-top: 18px;
            padding: 18px;
            border-radius: 18px;
            border: 1px solid var(--line);
            background: #fbfdff;
        }
        .result.valid {
            border-color: rgba(4, 120, 87, .26);
            background: #f0fdf4;
        }
        .result.invalid {
            border-color: rgba(180, 35, 24, .26);
            background: #fff7f7;
        }
        .status {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
            font-weight: 900;
        }
        .status .dot {
            width: 12px;
            height: 12px;
            border-radius: 999px;
            background: var(--bad);
        }
        .valid .status .dot { background: var(--ok); }
        dl {
            display: grid;
            grid-template-columns: 110px 1fr;
            gap: 8px 12px;
            margin: 14px 0 0;
            font-size: .9rem;
        }
        dt { color: var(--muted); }
        dd { margin: 0; font-weight: 700; overflow-wrap: anywhere; }
        .errors {
            margin: 0 0 18px;
            padding: 12px 14px;
            border-radius: 14px;
            color: var(--bad);
            background: #fff7f7;
            border: 1px solid rgba(180, 35, 24, .24);
        }
        .hint {
            margin-top: 16px;
            color: var(--muted);
            font-size: .86rem;
            line-height: 1.6;
        }
        @media (max-width: 840px) {
            main { padding: 24px 0; }
            .shell { grid-template-columns: 1fr; }
            .hero { padding: 26px; min-height: auto; }
            .facts { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<main>
    <div class="shell">
        <section class="hero" aria-labelledby="verify-title">
            <div>
                <div class="brand"><span class="mark">K</span><span>KLASSCI CEV</span></div>
                <h1 id="verify-title">Vérifier un document officiel.</h1>
                <p class="lead">Saisissez la référence et le code imprimés sur le procès-verbal ou le document officiel. La réponse confirme uniquement l’authenticité du document, sans exposer les notes, les identités étudiantes ni les fichiers internes.</p>
            </div>
            <div class="facts" aria-label="Garanties de vérification">
                <div class="fact"><strong>Public</strong>Accessible sans compte KLASSCI.</div>
                <div class="fact"><strong>Sûr</strong>Code brut non stocké, non renvoyé.</div>
                <div class="fact"><strong>Sobre</strong>Aucune donnée académique détaillée exposée.</div>
            </div>
        </section>

        <section class="card" aria-label="Formulaire de vérification">
            @if ($errors->any())
                <div class="errors">
                    Les informations saisies sont incomplètes ou invalides.
                </div>
            @endif

            <form method="POST" action="{{ route('official-documents.verify') }}">
                <div class="field">
                    <label for="reference">Référence officielle</label>
                    <input id="reference" name="reference" value="{{ old('reference', $reference ?? '') }}" maxlength="96" autocomplete="off" required>
                </div>
                <div class="field">
                    <label for="code">Code de vérification</label>
                    <input id="code" name="code" value="{{ old('code', $code ?? '') }}" minlength="32" maxlength="128" autocomplete="off" required>
                </div>
                <button type="submit">Vérifier le document</button>
            </form>

            @if(is_array($result ?? null))
                <div class="result {{ $result['valid'] ? 'valid' : 'invalid' }}" role="status">
                    <div class="status">
                        <span class="dot"></span>
                        <span>{{ $result['valid'] ? 'Document officiel valide' : 'Document non vérifié' }}</span>
                    </div>
                    @if($result['valid'])
                        <p class="hint">La référence et le code correspondent à un document officiel KLASSCI valide et intact.</p>
                        <dl>
                            <dt>Référence</dt>
                            <dd>{{ $result['reference'] }}</dd>
                            <dt>Type</dt>
                            <dd>{{ $result['document_type'] }}</dd>
                            <dt>Émis le</dt>
                            <dd>{{ $result['issued_at'] }}</dd>
                        </dl>
                    @else
                        <p class="hint">La référence ou le code ne correspond pas à un document officiel valide. Le même message est affiché pour les documents inconnus, expirés, révoqués, remplacés ou altérés.</p>
                    @endif
                </div>
            @endif

            <p class="hint">En cas de doute, demandez à l’établissement de vous transmettre la référence et le code directement depuis le document officiel.</p>
        </section>
    </div>
</main>
</body>
</html>

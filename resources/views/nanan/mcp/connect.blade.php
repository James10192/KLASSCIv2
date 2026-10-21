<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Connecter Nanan — KLASSCI</title>
    <style>
        :root { color-scheme: light; font-family: system-ui, -apple-system, sans-serif; background: #f5f8fd; color: #182b46; }
        * { box-sizing: border-box; }
        body { margin: 0; padding: 2rem 1rem; }
        main { max-width: 570px; margin: 2rem auto; padding: 2rem; border: 1px solid #d8e2f0; border-radius: 18px; background: #fff; box-shadow: 0 15px 40px #002a5f10; }
        .brand { color: #0453CB; font-size: .8rem; font-weight: 800; letter-spacing: .14em; }
        h1 { line-height: 1.15; margin: .8rem 0; font-size: 1.8rem; }
        p,li { color: #45556d; line-height: 1.55; }
        label { display: block; margin: 1.3rem 0 .4rem; font-weight: 700; }
        input[type=text] { display: block; width: 100%; padding: .8rem; border: 1px solid #b9c8db; border-radius: 9px; font-family: ui-monospace, monospace; font-size: .95rem; }
        input:focus { outline: 2px solid #0453CB; }
        .check { display: flex; align-items: flex-start; gap: .8rem; font-weight: 400; margin: 1.3rem 0; }
        .check input { width: 20px; height: 20px; flex: 0 0 20px; }
        button { background: #0453CB; color: white; border: 0; border-radius: 9px; padding: .85rem 1.2rem; font-weight: 700; cursor: pointer; }
        button:hover { background: #0346a9; }
        .note { background: #f2f6fe; border-radius: 10px; padding: 1rem; }
        .success { color: #18603b; background: #e9f8ee; padding: .75rem; border-radius: 8px; }
        .error { color: #84252c; background: #fff0f0; padding: .75rem; border-radius: 8px; }
        footer { font-size: .85rem; margin-top: 1.5rem; }
        a { color: #0453CB; }
    </style>
</head>
<body>
<main>
    <div class="brand">KLASSCI · NANAN CONNECT</div>
    <h1>Connecter votre compte à ChatGPT</h1>
    <p>Vous êtes connecté à KLASSCI en tant que <strong>{{ auth()->user()->name }}</strong>.</p>
    <div class="note">
        <p><strong>Avant de continuer :</strong> ouvrez Nanan dans ChatGPT, connectez-vous à votre organisation OAuth, puis demandez à générer un code de liaison.</p>
        <p>Le code expire après 10 minutes et fonctionne une seule fois. Un administrateur habilité doit d'abord vérifier votre identité OAuth et votre compte KLASSCI, puis approuver explicitement ce code pour <strong>votre identifiant utilisateur</strong>.</p>
        <p>Ne communiquez pas votre code en dehors de ce contrôle d'identité. Le code seul ne suffit pas à vous identifier.</p>
    </div>
    @if(session('pairing_success'))
        <p class="success" role="status">{{ session('pairing_success') }}</p>
    @endif
    @if(session('pairing_error'))
        <p class="error" role="alert">{{ session('pairing_error') }}</p>
    @endif
    @if($errors->any())
        <p class="error" role="alert">Vérifiez le code et le consentement avant de valider.</p>
    @endif
    <form method="post" action="{{ route('nanan.mcp.connections.claim') }}" autocomplete="off">
        @csrf
        <label for="pairing-code">Votre code de liaison</label>
        <input id="pairing-code" name="code" type="text" inputmode="text"
            minlength="43" maxlength="43" pattern="[A-Za-z0-9_-]{43}"
            spellcheck="false" autocomplete="off" required
            aria-describedby="code-help">
        <p id="code-help"><small>Copiez le code complet affiché par Nanan Connect dans ChatGPT.</small></p>
        <label class="check">
            <input type="checkbox" name="consent" value="1" required>
            <span>J'autorise Nanan Connect à consulter, depuis ce compte externe, uniquement les informations et fonctionnalités déjà autorisées pour mon compte KLASSCI. Je comprends que je pourrai révoquer cet accès, mais que cela n'effacera pas les conversations déjà enregistrées dans ChatGPT.</span>
        </label>
        <button type="submit">Confirmer la liaison</button>
    </form>
    <footer>Les données des autres établissements et les opérations d'écriture ne sont pas accessibles par cette liaison. <a href="{{ route('nanan.mcp.connections.index') }}">Consulter mes connexions</a></footer>
</main>
</body>
</html>

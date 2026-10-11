<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Export enseignants indisponible — KLASSCI</title>
    <style>
        body { margin:0; background:#f6f8fc; color:#1e293b; font-family:Arial, sans-serif; }
        main { max-width:610px; margin:9vh auto; padding:32px; background:#fff; border:1px solid #e2e8f0; border-radius:16px; box-shadow:0 12px 32px rgba(0,0,0,.06); }
        h1 { color:#0453cb; font-size:23px; margin:0 0 12px; }
        p,li { line-height:1.6; }
        ul { background:#fff7ed; border-left:4px solid #ea580c; padding:16px 20px 16px 36px; border-radius:8px; }
        a { display:inline-block; margin-top:15px; background:#0453cb; color:#fff; border-radius:9px; padding:11px 18px; text-decoration:none; font-weight:600; }
        a:hover { background:#0843a0; }
        small { display:block; margin-top:18px; color:#64748b; }
    </style>
</head>
<body>
<main role="alert">
    <h1>Le document n'a pas pu être préparé</h1>
    <p>Le serveur a refusé cet export avec les filtres sélectionnés. Voici la raison :</p>
    <ul>
        @foreach($messages as $message)
            <li>{{ $message }}</li>
        @endforeach
    </ul>
    <p>Vous pouvez ajuster la classe, le semestre, l'année universitaire ou télécharger la liste en Excel.</p>
    <a href="{{ route('esbtp.enseignants.index') }}">Retour à la gestion des enseignants</a>
    <small>Ce message ne modifie aucune donnée de l'établissement.</small>
</main>
</body>
</html>

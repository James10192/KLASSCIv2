{{--
    Le lien ne s'ouvre plus.

    Une seule page pour les trois cas — expiré, déjà utilisé, inconnu — et c'est
    délibéré : distinguer « ce jeton n'existe pas » de « ce jeton a expiré »
    offrirait à qui essaie des jetons au hasard le moyen de savoir lesquels ont
    existé.
--}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Lien expiré — {{ config('app.name', 'KLASSCI') }}</title>
    <style>
        body {
            margin: 0; min-height: 100vh;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(160deg, #0a3d8f 0%, #0453cb 45%, #3b7ddb 100%);
            color: #fff; display: flex; align-items: center; justify-content: center;
            padding: 1.5rem;
        }
        .carte {
            background: #fff; color: #1e293b; border-radius: 18px;
            max-width: 420px; padding: 2rem 1.5rem; text-align: center;
            box-shadow: 0 18px 50px rgba(4,15,40,.28);
        }
        .icone { font-size: 3rem; }
        h1 { font-size: 1.15rem; margin: .8rem 0 .5rem; }
        p { font-size: .9rem; color: #64748b; line-height: 1.6; margin: 0; }
    </style>
</head>
<body>
    <div class="carte">
        <div class="icone">&#9203;</div>
        <h1>Ce lien n'est plus valable</h1>
        <p>
            Il a expiré, ou la photo a déjà été envoyée.
            Demandez au guichet d'afficher un nouveau code.
        </p>
    </div>
</body>
</html>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Relevé BTS non disponible</title>
    <style>
        body { font-family: system-ui, sans-serif; color: #25334b; background: #f3f7fc; margin: 0; padding: 8vh 20px; }
        main { max-width: 620px; background: white; margin: auto; border-radius: 16px; padding: 32px; box-shadow: 0 8px 32px #16376914; }
        h1 { color: #0453cb; }
        a { color: #0453cb; font-weight: 600; }
    </style>
</head>
<body>
<main>
    <h1>Relevé BTS pas encore disponible</h1>
    <p>Aucun bulletin publié ni aucune note exploitable n'est disponible pour la période sélectionnée
       de l'année {{ $annee->name }}.</p>
    <p>Le relevé provisoire apparaît dès que des notes sont enregistrées ; le relevé interne publié
       prend le relais lorsque les bulletins sont publiés. Aucun document n'a été généré.</p>
    <a href="{{ route('esbtp.notes.index', ['annee_universitaire_id' => $annee->id]) }}">Retour à la gestion des notes</a>
</main>
</body>
</html>

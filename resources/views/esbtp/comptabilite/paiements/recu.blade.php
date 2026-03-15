<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Reçu de paiement</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; margin: 0; padding: 0; color: #333; }
        .container { padding: 20px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 24px; }
        .header p { margin: 5px 0; }
        .content { margin-bottom: 30px; }
        .content table { width: 100%; border-collapse: collapse; }
        .content th, .content td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .content th { background-color: #f2f2f2; }
        .footer { text-align: center; font-size: 12px; color: #777; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ \App\Helpers\SettingsHelper::get('school_name', config('app.name')) }}</h1>
            <p>Reçu de Paiement</p>
        </div>

        <div class="content">
            <table>
                <tr>
                    <th>Date du paiement</th>
                    <td>{{ $paiement->date_paiement->format('d/m/Y') }}</td>
                </tr>
                <tr>
                    <th>Montant payé</th>
                    <td>{{ number_format($paiement->montant, 2, ',', ' ') }} FCFA</td>
                </tr>
                <tr>
                    <th>Étudiant</th>
                    <td>{{ $paiement->inscription->etudiant->nom_complet }}</td>
                </tr>
                <tr>
                    <th>Matricule</th>
                    <td>{{ $paiement->inscription->etudiant->matricule }}</td>
                </tr>
                <tr>
                    <th>Filière</th>
                    <td>{{ $paiement->inscription->filiere->libelle }}</td>
                </tr>
                <tr>
                    <th>Niveau</th>
                    <td>{{ $paiement->inscription->niveau->libelle }}</td>
                </tr>
                <tr>
                    <th>Année Universitaire</th>
                    <td>{{ $paiement->inscription->anneeUniversitaire->name }}</td>
                </tr>
                <tr>
                    <th>Reçu émis par</th>
                    <td>{{ $paiement->createdBy->name }}</td>
                </tr>
            </table>
        </div>

        <div class="footer">
            <p>Merci pour votre paiement.</p>
        </div>
    </div>
</body>
</html> 
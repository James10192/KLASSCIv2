<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificat de Scolarité</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        /* Styles pour le certificat de scolarité */
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 2cm;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            max-width: 150px;
            margin-bottom: 20px;
        }
        .title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 30px;
            text-align: center;
            text-decoration: underline;
        }
        .content {
            margin-bottom: 30px;
        }
        .footer {
            margin-top: 50px;
            text-align: right;
        }
        .signature {
            margin-top: 30px;
        }

        /* En-tête avec logo */
        .header {
            display: flex;
            align-items: flex-start;
            margin-bottom: 20px;
        }

        .logo {
            width: 100px;
            margin-right: 20px;
        }

        .header-content {
            flex: 1;
        }

        .header-title {
            color: #00a651;
            font-weight: bold;
            text-align: center;
            font-size: 1.5rem;
            border-bottom: 2px solid #00a651;
            padding-bottom: 5px;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        .contact-info {
            font-size: 0.8rem;
            text-align: center;
        }

        /* Séparateur */
        .divider {
            height: 10px;
            background: repeating-linear-gradient(45deg, #888, #888 10px, #fff 10px, #fff 20px);
            margin: 15px 0;
        }

        /* Titre du certificat */
        .certificate-title {
            font-size: 2.5rem;
            font-weight: bold;
            text-align: center;
            border: 3px double #000;
            border-radius: 10px;
            padding: 10px;
            margin: 20px auto;
            max-width: 90%;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.2);
            background-color: #fff;
            position: relative;
            text-transform: uppercase;
        }

        .certificate-title::before {
            content: '';
            position: absolute;
            top: -5px;
            left: -5px;
            right: -5px;
            bottom: -5px;
            border: 1px solid #000;
            border-radius: 15px;
            z-index: -1;
            opacity: 0.5;
        }

        /* Contenu principal */
        .main-content {
            line-height: 1.6;
        }

        /* Tableau */
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }

        table, th, td {
            border: 1px solid black;
        }

        th, td {
            padding: 8px;
            text-align: center;
        }

        th {
            background-color: #f8f9fa;
            font-weight: bold;
        }

        /* Signature */
        .signature {
            text-align: right;
            margin-top: 20px;
            padding-right: 50px;
            position: relative;
        }

        /* Note de bas de page */
        .footer-note {
            font-size: 0.8rem;
            font-style: italic;
            margin-top: 30px;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }

        /* Responsive */
        @media print {
            body {
                padding: 0;
            }

            .container {
                box-shadow: none;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>ÉCOLE SUPÉRIEURE DU BÂTIMENT ET DES TRAVAUX PUBLICS</h1>
            <h2>ESBTP Yamoussoukro</h2>
        </div>

        <div class="divider"></div>

        <!-- Certificate Title -->
        <div class="title">
            CERTIFICAT DE SCOLARITÉ
        </div>

        <!-- Certificate Content -->
        <div class="content">
            <p>Je soussigné(e), Directeur de l'École Supérieure du Bâtiment et des Travaux Publics de Yamoussoukro, certifie que :</p>

            <p>L'étudiant(e) <strong>{{ $etudiant->nom }} {{ $etudiant->prenoms }}</strong></p>

            <p>Né(e) le <strong>{{ date('d/m/Y', strtotime($etudiant->date_naissance)) }}</strong> à <strong>{{ $etudiant->lieu_naissance }}</strong></p>

            <p>Matricule : <strong>{{ $etudiant->matricule }}</strong></p>

            <p>Est régulièrement inscrit(e) en <strong>{{ $inscription->niveau->nom }}</strong> de la filière <strong>{{ $inscription->filiere->nom }}</strong> pour l'année universitaire <strong>{{ $inscription->anneeUniversitaire->nom }}</strong>.</p>

            <p>Ce certificat est délivré à l'intéressé(e) pour servir et valoir ce que de droit.</p>
        </div>

        <div class="footer">
            <p>Fait à Yamoussoukro, le {{ date('d/m/Y') }}</p>

            <div class="signature">
                <p>Le Directeur</p>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<!DOCTYPE html>
<html>
<head>
    @include('pdf.partials.theme')
    <meta charset="utf-8">
    <title>Bulletin de Notes</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .logo {
            max-width: 100px;
            margin-bottom: 10px;
        }
        .school-info {
            margin-bottom: 10px;
        }
        .student-info {
            margin-bottom: 20px;
        }
        .student-info table {
            width: 100%;
            border-collapse: collapse;
        }
        .student-info td {
            padding: 5px;
        }
        .grades-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .grades-table th, .grades-table td {
            border: 1px solid #000;
            padding: 5px;
            text-align: center;
        }
        .grades-table th {
            background-color: #f0f0f0;
        }
        .summary {
            margin-bottom: 20px;
        }
        .summary table {
            width: 100%;
            border-collapse: collapse;
        }
        .summary td {
            padding: 5px;
        }
        .footer {
            text-align: right;
            margin-top: 30px;
        }
        .signature {
            margin-top: 50px;
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="{{ public_path('images/logo.png') }}" alt="Logo" class="logo">
        <div class="school-info">
            <h2>ÉCOLE SUPÉRIEURE DU BÂTIMENT ET DES TRAVAUX PUBLICS</h2>
            <p>Année Universitaire {{ $bulletin->annee_universitaire->annee }}</p>
            <h3>BULLETIN DE NOTES - {{ strtoupper($bulletin->periode) }}</h3>
        </div>
    </div>

    <div class="student-info">
        <table>
            <tr>
                <td><strong>Nom et Prénoms:</strong> {{ $etudiant->nom_complet }}</td>
                <td><strong>Matricule:</strong> {{ $etudiant->matricule }}</td>
            </tr>
            <tr>
                <td><strong>Classe:</strong> {{ $classe->name }}</td>
                <td><strong>Effectif:</strong> {{ $bulletin->effectif_classe }}</td>
            </tr>
        </table>
    </div>

    <table class="grades-table">
        <thead>
            <tr>
                <th>Matières</th>
                <th>Coef</th>
                <th>Moyenne</th>
                <th>Moy × Coef</th>
                <th>Rang</th>
                <th>Observations</th>
            </tr>
        </thead>
        <tbody>
            @php $totalCoef = 0; $totalPoints = 0; @endphp
            @foreach($resultats as $resultat)
                <tr>
                    <td>{{ $resultat->matiere->name }}</td>
                    <td>{{ $resultat->coefficient }}</td>
                    <td>{{ number_format($resultat->moyenne, 2) }}</td>
                    <td>{{ number_format($resultat->moyenne * $resultat->coefficient, 2) }}</td>
                    <td>{{ $resultat->rang }}/{{ $bulletin->effectif_classe }}</td>
                    <td>{{ $resultat->appreciation }}</td>
                </tr>
                @php
                    $totalCoef += $resultat->coefficient;
                    $totalPoints += $resultat->moyenne * $resultat->coefficient;
                @endphp
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="1"><strong>TOTAUX</strong></td>
                <td><strong>{{ $totalCoef }}</strong></td>
                <td></td>
                <td><strong>{{ number_format($totalPoints, 2) }}</strong></td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>

    <div class="summary">
        <table>
            <tr>
                <td><strong>Moyenne Générale:</strong> {{ number_format($bulletin->moyenne_generale, 2) }}/20 ({{ $moyenne_en_lettres }})</td>
                <td><strong>Rang:</strong> {{ $bulletin->rang }}/{{ $bulletin->effectif_classe }}</td>
            </tr>
            <tr>
                <td><strong>Mention:</strong> {{ $bulletin->mention }}</td>
                <td><strong>Décision:</strong> {{ $bulletin->decision ?? 'En attente de délibération' }}</td>
            </tr>
            <tr>
                <td colspan="2">
                    <strong>Absences:</strong>
                    Total: {{ $absences['total'] }}h -
                    Justifiées: {{ $absences['justifiees'] }}h -
                    Non justifiées: {{ $absences['non_justifiees'] }}h
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        <p>Fait à Yamoussoukro, le {{ $date_edition }}</p>
    </div>

    <div class="signature">
        <p>Le Directeur</p>
        <br><br>
        <p>Dr. KOUAME Koffi</p>
    </div>
</body>
</html>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Liste d'appel - {{ $classe->name }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header h1 {
            font-size: 20px;
            margin: 0 0 10px 0;
        }

        .header h2 {
            font-size: 16px;
            margin: 0 0 20px 0;
        }

        .info-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
        }

        .attendance-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .attendance-table th,
        .attendance-table td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }

        .attendance-table th {
            background-color: #f0f0f0;
            font-weight: bold;
        }

        .checkbox-cell {
            width: 30px;
            text-align: center;
        }

        .number-cell {
            width: 40px;
            text-align: center;
        }

        .footer {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
        }

        .signature-box {
            text-align: right;
        }

        .signature-line {
            height: 50px;
            border-bottom: 1px solid #000;
            width: 200px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <!-- En-tête -->
    <div class="header">
        <h1>ESBTP-yAKRO</h1>
        <h2>FEUILLE D'APPEL</h2>

        <div style="display: flex; justify-content: space-between; margin-bottom: 20px;">
            <div><strong>Classe :</strong> {{ $classe->name }}</div>
            <div><strong>Date :</strong> _______________</div>
        </div>

        <div style="display: flex; justify-content: space-between; margin-bottom: 20px;">
            <div><strong>Filière :</strong> {{ $classe->filiere->name ?? 'N/A' }}</div>
            <div><strong>Niveau :</strong> {{ $classe->niveau->name ?? 'N/A' }}</div>
            <div><strong>Année :</strong> {{ $anneeCourante->name ?? 'N/A' }}</div>
        </div>
    </div>

    <!-- Liste des étudiants -->
    @if($etudiants->count() > 0)
        <table class="attendance-table">
            <thead>
                <tr>
                    <th class="number-cell">N°</th>
                    <th>Matricule</th>
                    <th>Nom et Prénoms</th>
                    <th class="checkbox-cell">Présent</th>
                    <th class="checkbox-cell">Absent</th>
                    <th style="width: 120px;">Observations</th>
                </tr>
            </thead>
            <tbody>
                @foreach($etudiants as $index => $etudiant)
                <tr>
                    <td class="number-cell">{{ $index + 1 }}</td>
                    <td>{{ $etudiant->matricule ?? 'N/A' }}</td>
                    <td>{{ $etudiant->nom }} {{ $etudiant->prenom }}</td>
                    <td class="checkbox-cell">☐</td>
                    <td class="checkbox-cell">☐</td>
                    <td></td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Résumé -->
        <div class="footer">
            <div>
                <p><strong>Total étudiants :</strong> {{ $etudiants->count() }}</p>
                <p><strong>Présents :</strong> _____ / {{ $etudiants->count() }}</p>
                <p><strong>Absents :</strong> _____ / {{ $etudiants->count() }}</p>
            </div>
            <div class="signature-box">
                <p><strong>Enseignant :</strong> _____________________</p>
                <p><strong>Signature :</strong></p>
                <div class="signature-line"></div>
            </div>
        </div>
    @else
        <div style="text-align: center; padding: 40px;">
            <p>Aucun étudiant inscrit dans cette classe pour l'année {{ $anneeCourante->name ?? 'courante' }}.</p>
        </div>
    @endif
</body>
</html>
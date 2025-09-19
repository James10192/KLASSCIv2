<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Liste complète - {{ $classe->name }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            margin: 15px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .header h1 {
            font-size: 18px;
            margin: 0 0 8px 0;
        }

        .header h2 {
            font-size: 14px;
            margin: 0 0 15px 0;
        }

        .students-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            font-size: 9px;
        }

        .students-table th,
        .students-table td {
            border: 1px solid #000;
            padding: 4px;
            text-align: left;
        }

        .students-table th {
            background-color: #f0f0f0;
            font-weight: bold;
            font-size: 8px;
        }

        .students-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .number-cell {
            width: 25px;
            text-align: center;
        }

        .text-center {
            text-align: center;
        }

        .footer {
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
            font-size: 9px;
            page-break-inside: avoid;
        }

        .page-break {
            page-break-before: always;
        }

        @page {
            margin: 1cm;
            size: A4 landscape;
        }
    </style>
</head>
<body>
    <!-- En-tête -->
    <div class="header">
        <h1>ESBTP-yAKRO</h1>
        <h2>LISTE COMPLÈTE DES ÉTUDIANTS</h2>

        <div style="display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 11px;">
            <div><strong>Classe :</strong> {{ $classe->name }}</div>
            <div><strong>Code :</strong> {{ $classe->code }}</div>
            <div><strong>Date :</strong> {{ date('d/m/Y H:i') }}</div>
        </div>

        <div style="display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 10px;">
            <div><strong>Filière :</strong> {{ $classe->filiere->name ?? 'N/A' }}</div>
            <div><strong>Niveau :</strong> {{ $classe->niveau->name ?? 'N/A' }}</div>
            <div><strong>Année :</strong> {{ $anneeCourante->name ?? 'N/A' }}</div>
        </div>
    </div>

    <!-- Liste des étudiants -->
    @if($etudiants->count() > 0)
        <table class="students-table">
            <thead>
                <tr>
                    <th class="number-cell">N°</th>
                    <th>Matricule</th>
                    <th>Nom et Prénoms</th>
                    <th>Sexe</th>
                    <th>Date naiss.</th>
                    <th>Téléphone</th>
                    <th>Email</th>
                    <th>Adresse</th>
                    <th>Parent/Tuteur</th>
                    <th>Tél. Parent</th>
                </tr>
            </thead>
            <tbody>
                @foreach($etudiants as $index => $etudiant)
                <tr>
                    <td class="number-cell">{{ $index + 1 }}</td>
                    <td>{{ $etudiant->matricule ?? 'N/A' }}</td>
                    <td><strong>{{ $etudiant->nom }} {{ $etudiant->prenom }}</strong></td>
                    <td class="text-center">{{ $etudiant->sexe ?? 'N/A' }}</td>
                    <td>{{ $etudiant->date_naissance ? \Carbon\Carbon::parse($etudiant->date_naissance)->format('d/m/Y') : 'N/A' }}</td>
                    <td>{{ $etudiant->telephone ?? 'N/A' }}</td>
                    <td>{{ $etudiant->email ?? 'N/A' }}</td>
                    <td>{{ \Str::limit($etudiant->adresse ?? 'N/A', 40) }}</td>
                    <td>{{ $etudiant->parent ? $etudiant->parent->nom . ' ' . $etudiant->parent->prenom : 'N/A' }}</td>
                    <td>{{ $etudiant->parent ? $etudiant->parent->telephone : 'N/A' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Résumé -->
        <div class="footer">
            <div>
                <p><strong>Résumé statistique :</strong></p>
                <p><strong>Total étudiants :</strong> {{ $etudiants->count() }}</p>
                <p><strong>Hommes :</strong> {{ $etudiants->where('sexe', 'M')->count() }} ({{ $etudiants->count() > 0 ? round(($etudiants->where('sexe', 'M')->count() / $etudiants->count()) * 100, 1) : 0 }}%)</p>
                <p><strong>Femmes :</strong> {{ $etudiants->where('sexe', 'F')->count() }} ({{ $etudiants->count() > 0 ? round(($etudiants->where('sexe', 'F')->count() / $etudiants->count()) * 100, 1) : 0 }}%)</p>
                <p><strong>Places disponibles :</strong> {{ ($classe->places_totales ?? 0) - $etudiants->count() }}</p>
            </div>
            <div style="text-align: right;">
                <p><strong>Document généré le :</strong> {{ date('d/m/Y à H:i') }}</p>
                <p><strong>Par :</strong> {{ auth()->user()->name }}</p>
                <p><strong>ESBTP-yAKRO</strong></p>
            </div>
        </div>
    @else
        <div style="text-align: center; padding: 40px;">
            <p style="font-size: 14px;">Aucun étudiant inscrit dans cette classe pour l'année {{ $anneeCourante->name ?? 'courante' }}.</p>
        </div>
    @endif
</body>
</html>
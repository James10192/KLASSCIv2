<!DOCTYPE html>
<html>
<head>
    @include('pdf.partials.theme')
    <meta charset="utf-8">
    <title>PV de Délibération</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            line-height: 1.3;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .logo {
            max-width: 80px;
            margin-bottom: 10px;
        }
        .school-info {
            margin-bottom: 10px;
        }
        .class-info {
            margin-bottom: 20px;
            text-align: center;
        }
        .grades-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 9px;
        }
        .grades-table th, .grades-table td {
            border: 1px solid #000;
            padding: 3px;
            text-align: center;
        }
        .grades-table th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        .student-name {
            text-align: left;
            font-weight: bold;
        }
        .footer {
            margin-top: 30px;
        }
        .signatures {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
        }
        .signature-block {
            width: 30%;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="{{ public_path('images/logo.png') }}" alt="Logo" class="logo">
        <div class="school-info">
            <h2>ÉCOLE SUPÉRIEURE DU BÂTIMENT ET DES TRAVAUX PUBLICS</h2>
            <p>Année Universitaire {{ $bulletins->first()->annee_universitaire->annee }}</p>
            <h3>PROCÈS VERBAL DE DÉLIBÉRATION - {{ strtoupper($periode) }}</h3>
        </div>
    </div>

    <div class="class-info">
        <p><strong>Classe:</strong> {{ $classe->name }} | <strong>Effectif:</strong> {{ $bulletins->first()->effectif_classe }}</p>
    </div>

    <table class="grades-table">
        <thead>
            <tr>
                <th rowspan="2">N°</th>
                <th rowspan="2">Nom et Prénoms</th>
                <th rowspan="2">Matricule</th>
                @foreach($matieres as $matiere)
                    <th colspan="2">{{ $matiere->name }}</th>
                @endforeach
                <th colspan="3">Résultats</th>
                <th rowspan="2">Décision</th>
            </tr>
            <tr>
                @foreach($matieres as $matiere)
                    <th>Moy</th>
                    <th>Rang</th>
                @endforeach
                <th>MG</th>
                <th>Rang</th>
                <th>Mention</th>
            </tr>
        </thead>
        <tbody>
            @foreach($bulletins as $index => $bulletin)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td class="student-name">{{ $bulletin->etudiant->nom_complet }}</td>
                    <td>{{ $bulletin->etudiant->matricule }}</td>
                    @foreach($matieres as $matiere)
                        @php
                            $resultat = $bulletin->resultats->where('matiere_id', $matiere->id)->first();
                        @endphp
                        @if($resultat)
                            <td>{{ number_format($resultat->moyenne, 2) }}</td>
                            <td>{{ $resultat->rang }}</td>
                        @else
                            <td>-</td>
                            <td>-</td>
                        @endif
                    @endforeach
                    <td>{{ number_format($bulletin->moyenne_generale, 2) }}</td>
                    <td>{{ $bulletin->rang }}</td>
                    <td>{{ $bulletin->mention }}</td>
                    <td>{{ $bulletin->decision ?? 'En attente' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>Fait à Yamoussoukro, le {{ $date_edition }}</p>
    </div>

    <div class="signatures">
        <div class="signature-block">
            <p>Le Chef de Département</p>
            <br><br>
            <p>________________</p>
        </div>
        <div class="signature-block">
            <p>Le Directeur des Études</p>
            <br><br>
            <p>________________</p>
        </div>
        <div class="signature-block">
            <p>Le Directeur</p>
            <br><br>
            <p>Dr. KOUAME Koffi</p>
        </div>
    </div>
</body>
</html>

<!DOCTYPE html>
<html>
<head>
    @include('pdf.partials.theme')
    <meta charset="utf-8">
    <title>Relevé de Notes</title>
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
        .period-header {
            background-color: #e0e0e0;
            font-weight: bold;
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
            <p>Année Universitaire {{ $bulletins->first()->annee_universitaire->annee }}</p>
            <h3>RELEVÉ DE NOTES</h3>
        </div>
    </div>

    <div class="student-info">
        <table>
            <tr>
                <td><strong>Nom et Prénoms:</strong> {{ $etudiant->nom_complet }}</td>
                <td><strong>Matricule:</strong> {{ $etudiant->matricule }}</td>
            </tr>
            <tr>
                <td><strong>Classe:</strong> {{ $bulletins->first()->classe->name }}</td>
                <td><strong>Effectif:</strong> {{ $bulletins->first()->effectif_classe }}</td>
            </tr>
        </table>
    </div>

    <table class="grades-table">
        <thead>
            <tr>
                <th rowspan="2">Matières</th>
                <th rowspan="2">Coef</th>
                @foreach($bulletins as $bulletin)
                    <th colspan="3">{{ mb_strtoupper($bulletin->periode ?? '', 'UTF-8') }}</th>
                @endforeach
            </tr>
            <tr>
                @foreach($bulletins as $bulletin)
                    <th>Moy</th>
                    <th>M×C</th>
                    <th>Rang</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @php
                $matieres = collect();
                foreach($bulletins as $bulletin) {
                    foreach($bulletin->resultats as $resultat) {
                        $matieres->push($resultat->matiere);
                    }
                }
                $matieres = $matieres->unique('id')->sortBy('name');
            @endphp

            @foreach($matieres as $matiere)
                <tr>
                    <td>{{ $matiere->name }}</td>
                    <td>{{ $matiere->coefficient }}</td>
                    @foreach($bulletins as $bulletin)
                        @php
                            $resultat = $bulletin->resultats->where('matiere_id', $matiere->id)->first();
                        @endphp
                        @if($resultat)
                            <td>{{ number_format($resultat->moyenne, 2) }}</td>
                            <td>{{ number_format($resultat->moyenne * $resultat->coefficient, 2) }}</td>
                            <td>{{ $resultat->rang }}/{{ $bulletin->effectif_classe }}</td>
                        @else
                            <td>-</td>
                            <td>-</td>
                            <td>-</td>
                        @endif
                    @endforeach
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="period-header">
                <td colspan="2"><strong>Moyenne Générale</strong></td>
                @foreach($bulletins as $bulletin)
                    <td colspan="2">{{ number_format($bulletin->moyenne_generale, 2) }}/20</td>
                    <td>{{ $bulletin->rang }}/{{ $bulletin->effectif_classe }}</td>
                @endforeach
            </tr>
            <tr>
                <td colspan="2"><strong>Mention</strong></td>
                @foreach($bulletins as $bulletin)
                    <td colspan="3">{{ $bulletin->mention }}</td>
                @endforeach
            </tr>
            <tr>
                <td colspan="2"><strong>Absences (heures)</strong></td>
                @foreach($bulletins as $bulletin)
                    <td colspan="3">
                        Total: {{ $bulletin->absences_justifiees + $bulletin->absences_non_justifiees }}h
                        (J: {{ $bulletin->absences_justifiees }}h, NJ: {{ $bulletin->absences_non_justifiees }}h)
                    </td>
                @endforeach
            </tr>
        </tfoot>
    </table>

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

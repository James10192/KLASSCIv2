<!DOCTYPE html>
<html>
<head>
    @include('pdf.partials.theme')
    <meta charset="utf-8">
    <title>Rapport d'Absences</title>
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
        .absences-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 9px;
        }
        .absences-table th, .absences-table td {
            border: 1px solid #000;
            padding: 3px;
            text-align: center;
        }
        .absences-table th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        .student-name {
            text-align: left;
            font-weight: bold;
        }
        .warning {
            color: #ff0000;
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
            width: 45%;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="{{ public_path('images/logo.png') }}" alt="Logo" class="logo">
        <div class="school-info">
            <h2>ÉCOLE SUPÉRIEURE DU BÂTIMENT ET DES TRAVAUX PUBLICS</h2>
            <h3>RAPPORT D'ABSENCES - {{ strtoupper($periode) }}</h3>
        </div>
    </div>

    <div class="class-info">
        <p><strong>Classe:</strong> {{ $classe->name }}</p>
    </div>

    <table class="absences-table">
        <thead>
            <tr>
                <th rowspan="2">N°</th>
                <th rowspan="2">Nom et Prénoms</th>
                <th rowspan="2">Matricule</th>
                @foreach($rapport[0]['matieres'] as $matiere => $data)
                    <th colspan="4">{{ $matiere }}</th>
                @endforeach
                <th colspan="3">Total</th>
            </tr>
            <tr>
                @foreach($rapport[0]['matieres'] as $matiere => $data)
                    <th>J</th>
                    <th>NJ</th>
                    <th>Tot</th>
                    <th>%</th>
                @endforeach
                <th>J</th>
                <th>NJ</th>
                <th>Tot</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rapport as $index => $etudiant)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td class="student-name">{{ $etudiant['etudiant'] }}</td>
                    <td>{{ $etudiant['matricule'] }}</td>
                    @foreach($etudiant['matieres'] as $matiere => $data)
                        <td>{{ $data['justifiees'] }}</td>
                        <td>{{ $data['non_justifiees'] }}</td>
                        <td>{{ $data['total'] }}</td>
                        <td @if($data['pourcentage'] > 25) class="warning" @endif>
                            {{ number_format($data['pourcentage'], 1) }}%
                        </td>
                    @endforeach
                    <td>{{ $etudiant['absences_justifiees'] }}</td>
                    <td>{{ $etudiant['absences_non_justifiees'] }}</td>
                    <td>{{ $etudiant['total_absences'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>Fait à Yamoussoukro, le {{ $date_edition }}</p>
    </div>

    <div class="signatures">
        <div class="signature-block">
            <p>Le Responsable de la Scolarité</p>
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

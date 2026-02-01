<!DOCTYPE html>
<html>
<head>
    @include('pdf.partials.theme')
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Évaluation - {{ $evaluation->titre }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11pt;
            line-height: 1.3;
            color: #333;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 100%;
            padding: 10px;
        }

        .header {
            background-color: #01632f;
            color: white;
            padding: 15px;
            margin-bottom: 20px;
            text-align: center;
            position: relative;
        }

        .logo {
            position: absolute;
            top: 15px;
            left: 15px;
            height: 60px;
        }

        .school-name {
            font-size: 18pt;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .school-info {
            font-size: 9pt;
            margin-bottom: 5px;
        }

        .document-title {
            font-size: 14pt;
            font-weight: bold;
            margin-top: 10px;
        }

        .info-section {
            margin-bottom: 20px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .section-title {
            font-size: 12pt;
            font-weight: bold;
            color: #01632f;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 2px solid #f29400;
        }

        .info-box {
            display: inline-block;
            width: 45%;
            vertical-align: top;
            margin-bottom: 10px;
        }

        .info-label {
            font-weight: bold;
            color: #555;
        }

        .grades-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 10pt;
        }

        .grades-table th {
            background-color: #01632f;
            color: white;
            padding: 8px;
            text-align: center;
            font-weight: bold;
        }

        .grades-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
        }

        .grades-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .grades-table .student-name {
            text-align: left;
        }

        .stats-section {
            margin-bottom: 20px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #f9f9f9;
        }

        .stats-item {
            display: inline-block;
            width: 19%;
            text-align: center;
            padding: 10px 0;
        }

        .stats-value {
            font-size: 14pt;
            font-weight: bold;
            color: #01632f;
        }

        .stats-label {
            font-size: 9pt;
            color: #555;
        }

        .observations {
            margin-bottom: 20px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            min-height: 100px;
        }

        .signatures {
            margin-top: 40px;
            width: 100%;
        }

        .signature-box {
            display: inline-block;
            width: 45%;
            text-align: center;
        }

        .signature-line {
            margin: 0 auto;
            width: 80%;
            border-top: 1px solid #000;
            padding-top: 5px;
            margin-top: 50px;
        }

        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            padding: 10px 0;
            text-align: center;
            font-size: 8pt;
            color: #555;
            border-top: 1px solid #ddd;
        }

        .page-number {
            text-align: right;
            font-size: 9pt;
            color: #555;
            margin-top: 10px;
        }

        .pass {
            color: #01632f;
        }

        .fail {
            color: #d9534f;
        }

        .absent {
            font-style: italic;
            color: #777;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="{{ public_path($config['school_logo']) }}" alt="Logo" class="logo">
            <div class="school-name">{{ $config['school_name'] }}</div>
            <div class="school-info">{{ $config['school_address'] }} | Tel: {{ $config['school_phone'] }} | Email: {{ $config['school_email'] }}</div>
            <div class="document-title">ÉVALUATION - {{ strtoupper($evaluation->type) }}</div>
        </div>

        <div class="info-section">
            <div class="section-title">Informations sur l'évaluation</div>
            <div class="info-box">
                <p><span class="info-label">Titre:</span> {{ $evaluation->titre }}</p>
                <p><span class="info-label">Type:</span> {{ ucfirst($evaluation->type) }}</p>
                <p><span class="info-label">Date:</span> {{ $evaluation->date_evaluation->format('d/m/Y') }}</p>
                <p><span class="info-label">Durée:</span> {{ $evaluation->duree_minutes }} minutes</p>
            </div>
            <div class="info-box">
                <p><span class="info-label">Matière:</span> {{ $evaluation->matiere->name }}</p>
                <p><span class="info-label">Classe:</span> {{ $evaluation->classe->name }}</p>
                <p><span class="info-label">Coefficient:</span> {{ $evaluation->coefficient }}</p>
                <p><span class="info-label">Barème:</span> {{ $evaluation->bareme }} points</p>
            </div>
        </div>

        <div class="section-title">Notes des étudiants</div>
        <table class="grades-table">
            <thead>
                <tr>
                    <th style="width: 5%;">N°</th>
                    <th style="width: 10%;">Matricule</th>
                    <th style="width: 35%;">Nom et Prénom</th>
                    <th style="width: 10%;">Note /{{ $evaluation->bareme }}</th>
                    <th style="width: 10%;">Note /20</th>
                    <th style="width: 15%;">Observation</th>
                    <th style="width: 15%;">Statut</th>
                </tr>
            </thead>
            <tbody>
                @forelse($notes as $index => $note)
                    @php
                        $noteSur20 = ($note->note / $evaluation->bareme) * 20;
                        $status = $note->note >= ($evaluation->bareme / 2) ? 'pass' : 'fail';

                        if ($note->is_absent) {
                            $observation = 'Absent(e)';
                            $status = 'absent';
                        } elseif ($noteSur20 >= 16) {
                            $observation = 'Excellent';
                        } elseif ($noteSur20 >= 14) {
                            $observation = 'Très bien';
                        } elseif ($noteSur20 >= 12) {
                            $observation = 'Bien';
                        } elseif ($noteSur20 >= 10) {
                            $observation = 'Assez bien';
                        } elseif ($noteSur20 >= 8) {
                            $observation = 'Passable';
                        } else {
                            $observation = 'Insuffisant';
                        }
                    @endphp
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $note->etudiant->matricule }}</td>
                        <td class="student-name">{{ $note->etudiant->nom }} {{ $note->etudiant->prenom }}</td>
                        <td>{{ $note->is_absent ? 'ABS' : number_format($note->note, 2) }}</td>
                        <td>{{ $note->is_absent ? 'ABS' : number_format($noteSur20, 2) }}</td>
                        <td>{{ $note->commentaire ?? $observation }}</td>
                        <td class="{{ $status }}">{{ $note->is_absent ? 'Absent(e)' : ($note->note >= ($evaluation->bareme / 2) ? 'Admis(e)' : 'Non admis(e)') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 20px;">Aucune note n'a encore été saisie pour cette évaluation.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="stats-section">
            <div class="section-title">Statistiques</div>
            <div class="stats-item">
                <div class="stats-value">{{ number_format($stats['moyenne'], 2) }}</div>
                <div class="stats-label">Moyenne /{{ $evaluation->bareme }}</div>
            </div>
            <div class="stats-item">
                <div class="stats-value">{{ number_format($stats['max'], 2) }}</div>
                <div class="stats-label">Note max /{{ $evaluation->bareme }}</div>
            </div>
            <div class="stats-item">
                <div class="stats-value">{{ number_format($stats['min'], 2) }}</div>
                <div class="stats-label">Note min /{{ $evaluation->bareme }}</div>
            </div>
            <div class="stats-item">
                <div class="stats-value">{{ $stats['total_notes'] }}</div>
                <div class="stats-label">Nombre de notes</div>
            </div>
            <div class="stats-item">
                <div class="stats-value">{{ number_format($stats['reussite'], 1) }}%</div>
                <div class="stats-label">Taux de réussite</div>
            </div>
        </div>

        <div class="observations">
            <div class="section-title">Observations</div>
            <p>{{ $evaluation->description ?? 'Aucune observation particulière pour cette évaluation.' }}</p>
        </div>

        <div class="signatures">
            <div class="signature-box">
                <div class="signature-line"></div>
                <p>Professeur</p>
            </div>
            <div class="signature-box">
                <div class="signature-line"></div>
                <p>Direction des études</p>
            </div>
        </div>

        <div class="page-number">Page 1</div>

        <div class="footer">
            Document généré le {{ $date_edition }} | {{ $config['school_name'] }}
        </div>
    </div>
</body>
</html>

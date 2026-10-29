<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11pt; color: #1f2937; }
        h1 { font-size: 18pt; margin: 0 0 6px; color: #0453cb; }
        .meta { color: #64748b; font-size: 9pt; margin-bottom: 18px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #d1d5db; padding: 8px 10px; text-align: left; }
        th { background: #0453cb; color: #fff; width: 60%; }
        td { font-weight: 700; }
        .note { margin-top: 18px; font-size: 9pt; color: #64748b; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <div class="meta">
        {{ $school['name'] ?? 'KLASSCI' }}
        @if(!empty($report['annee'])) · Année {{ $report['annee'] }} @endif
        · Généré le {{ now()->format('d/m/Y') }}
    </div>
    <table>
        <tr><th>Inscrits valides</th><td>{{ $report['inscrits_valides'] }}</td></tr>
        <tr><th>Classes</th><td>{{ $report['classes'] }}</td></tr>
        <tr><th>Enseignants</th><td>{{ $report['enseignants'] }}</td></tr>
        <tr><th>Emplois du temps manquants</th><td>{{ $report['edt_manquants'] }}</td></tr>
        <tr><th>Étudiants non soldés</th><td>{{ $report['etudiants_non_soldes'] }}</td></tr>
        <tr><th>Notes manquantes</th><td>{{ $report['notes_manquantes'] }}</td></tr>
    </table>
    <p class="note">Ce rapport est pédagogique. Il affiche des effectifs, jamais de montants.</p>
</body>
</html>

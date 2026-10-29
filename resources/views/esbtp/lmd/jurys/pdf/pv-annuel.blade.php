<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>PV annuel</title>
    @include('pdf.partials.theme')
    <style>
        @page { margin: 10mm 8mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 8px; color: #1e293b; margin: 0; }
        table.data { width: 100%; border-collapse: collapse; margin-top: 3mm; }
        table.data th, table.data td { border: 0.4pt solid #94a3b8; padding: 1.2mm 1.5mm; }
        table.data th { background: {{ $pdfSettings['primary_color'] ?? '#0453cb' }}; color: #fff; font-size: 7px; text-transform: uppercase; }
        table.data td { font-size: 8px; }
    </style>
</head>
<body>
@php
    $school = $school ?? \App\Helpers\SettingsHelper::getSchoolInfo();
    $pdfSettings = \App\Helpers\SettingsHelper::getPdfSettings();
@endphp
@include('pdf.partials.banner', [
    'title' => 'Procès-verbal annuel',
    'subtitle' => trim(($payload['annee'] ?? '').' · '.($payload['parcours'] ?? '').' · '.($payload['niveau'] ?? ''), ' ·'),
    'compact' => true,
    'school' => $school,
    'pdfSettings' => $pdfSettings,
])
    <table class="data">
        <thead>
            <tr>
                <th>N°</th><th>IP</th><th>Nom</th><th>Prénoms</th>
                <th>Né(e) le</th><th>Lieu</th><th>Sexe</th>
                <th>Moy S{{ $payload['semestres']['premier'] ?? 1 }}</th><th>Moy S{{ $payload['semestres']['second'] ?? 2 }}</th><th>Moy. ann.</th><th>Crédits</th><th>Décision</th>
            </tr>
        </thead>
        <tbody>
        @forelse ($payload['rows'] as $row)
            <tr>
                <td>{{ $row['ordre'] }}</td>
                <td>{{ $row['matricule'] }}</td>
                <td>{{ $row['nom'] }}</td>
                <td>{{ $row['prenoms'] }}</td>
                <td>{{ $row['date_naissance'] }}</td>
                <td>{{ $row['lieu_naissance'] }}</td>
                <td>{{ $row['sexe'] }}</td>
                <td>{{ $row['moy_s1'] }}</td>
                <td>{{ $row['moy_s2'] }}</td>
                <td>{{ $row['moy_annuelle'] }}</td>
                <td>{{ $row['credits_annuels'] }}</td>
                <td>{{ $row['decision'] }}</td>
            </tr>
        @empty
            <tr><td colspan="12">Aucune donnée de bulletin pour ce jury.</td></tr>
        @endforelse
        </tbody>
    </table>
</body>
</html>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>PV annuel</title>
    @include('pdf.partials.theme')
    <style>
        @page { margin: 8mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 8px; color: #1e293b; }
        h1 { font-size: 13px; margin: 0 0 2mm; color: #0453cb; }
        .sub { color: #64748b; margin-bottom: 3mm; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 0.4pt solid #94a3b8; padding: 1.2mm 1.5mm; }
        th { background: #0453cb; color: #fff; font-size: 7px; text-transform: uppercase; }
        td { font-size: 8px; }
        .naq { color: #b91c1c; font-weight: bold; }
    </style>
</head>
<body>
    <h1>Procès-verbal annuel</h1>
    <div class="sub">
        {{ $school['name'] ?? $school['nom'] ?? '' }}
        · {{ $payload['annee'] }}
        · {{ $payload['parcours'] }}
        · {{ $payload['niveau'] }}
    </div>
    <table>
        <thead>
            <tr>
                <th>N°</th><th>IP</th><th>Nom</th><th>Prénoms</th>
                <th>Né(e) le</th><th>Lieu</th><th>Sexe</th>
                <th>Moy S1</th><th>Moy S2</th><th>Moy. ann.</th><th>Crédits</th><th>Décision</th>
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

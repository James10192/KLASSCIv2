<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>PV de délibération — semestre {{ $payload['semestre'] }}</title>
    @include('pdf.partials.theme')
    @php
        $school = $school ?? \App\Helpers\SettingsHelper::getSchoolInfo();
        $pdfSettings = \App\Helpers\SettingsHelper::getPdfSettings();
        $primary = $pdfSettings['primary_color'] ?? '#0453cb';
        $fmt = fn ($n) => $n === null ? '' : number_format((float) $n, 2, ',', ' ');
        $lieu = trim((string) ($school['city'] ?? ''));
    @endphp
    <style>
        @page { margin: 8mm 7mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 6.5px; color: #1e293b; margin: 0; }
        .meta td { padding: .6mm 2mm; font-size: 8px; }
        .meta td.k { color: #64748b; text-transform: uppercase; font-size: 7px; }
        table.pv { width: 100%; border-collapse: collapse; margin-top: 2.5mm; }
        table.pv th, table.pv td { border: .35pt solid #94a3b8; padding: .8mm .6mm; text-align: center; }
        table.pv th { background: {{ $primary }}; color: #fff; font-size: 6px; font-weight: bold; }
        table.pv td.g { text-align: left; }
        table.pv td.moy { font-weight: bold; background: #f1f5f9; }
        table.pv td.ko { color: #b91c1c; }
        table.pied { border-collapse: collapse; margin-top: 4mm; }
        table.pied th, table.pied td { border: .35pt solid #94a3b8; padding: .8mm 2mm; font-size: 7px; }
        table.pied th { background: #e2e8f0; }
        .signature { margin-top: 4mm; text-align: right; font-size: 8px; font-weight: bold; }
    </style>
</head>
<body>
@include('pdf.partials.banner', [
    'title' => 'Procès-verbal de délibération',
    'subtitle' => 'Semestre '.$payload['semestre'].' · '.($payload['annee'] ?? ''),
    'compact' => true,
    'school' => $school,
    'pdfSettings' => $pdfSettings,
])

<table class="meta">
    <tr><td class="k">Année universitaire</td><td>{{ $payload['annee'] }}</td><td class="k">Département</td><td>{{ $payload['departement'] }}</td></tr>
    <tr><td class="k">Parcours</td><td>{{ $payload['parcours'] }}</td><td class="k">Niveau</td><td>{{ trim(($payload['niveau'] ?? '').' · '.($payload['classe'] ?? ''), ' ·') }}</td></tr>
</table>

<table class="pv">
    <thead>
        <tr>
            <th colspan="8"></th>
            @foreach($payload['ues'] as $i => $ue)
                <th colspan="{{ count($ue['ecues']) + 3 }}">UE {{ $i + 1 }} : {{ $ue['code'] }} {{ $ue['name'] }}</th>
            @endforeach
            <th colspan="5">Résultats semestriels</th>
        </tr>
        <tr>
            <th>N°</th><th>IP</th><th>Nom</th><th>Prénoms</th><th>Né(e) le</th><th>Lieu</th><th>Sexe</th><th>Nationalité</th>
            @foreach($payload['ues'] as $ue)
                @foreach($ue['ecues'] as $ecue)<th>{{ $ecue['name'] }}</th>@endforeach
                <th>Moy.</th><th>Année</th><th>Session</th>
            @endforeach
            <th>Moy. sem.</th><th>Crédits</th><th>Décision</th><th>Année</th><th>Session</th>
        </tr>
    </thead>
    <tbody>
        @foreach($payload['rows'] as $row)
            <tr>
                <td>{{ $row['ordre'] }}</td><td>{{ $row['matricule'] }}</td><td class="g">{{ $row['nom'] }}</td><td class="g">{{ $row['prenoms'] }}</td>
                <td>{{ $row['date_naissance'] }}</td><td class="g">{{ $row['lieu_naissance'] }}</td><td>{{ $row['sexe'] }}</td><td>{{ $row['nationalite'] }}</td>
                @foreach($row['ues'] as $ue)
                    @foreach($ue['notes'] as $note)<td>{{ $fmt($note) }}</td>@endforeach
                    <td class="moy {{ $ue['moyenne'] !== null && ! $ue['validee'] ? 'ko' : '' }}">{{ $fmt($ue['moyenne']) }}</td>
                    <td>{{ $ue['annee'] }}</td><td>{{ $ue['session'] }}</td>
                @endforeach
                <td class="moy">{{ $fmt($row['moyenne']) }}</td>
                <td>{{ $row['credits'] }} / {{ $row['credits_totaux'] }}</td>
                <td>{{ $row['decision'] }}</td><td>{{ $row['annee'] }}</td><td>{{ $row['session'] }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<table style="width:100%;"><tr>
    <td style="vertical-align:top;width:55%;">
        <table class="pied">
            <tr><th>UE</th><th>Intitulé</th><th>Crédits</th></tr>
            @foreach($payload['ues'] as $ue)
                <tr><td>{{ $ue['code'] }}</td><td>{{ $ue['name'] }}</td><td style="text-align:center;">{{ $ue['credit'] }}</td></tr>
            @endforeach
        </table>
    </td>
    <td style="vertical-align:top;">
        <table class="pied">
            <tr><th>Statistiques du semestre {{ $payload['semestre'] }}</th><th>Nombre</th><th>%</th></tr>
            @foreach($payload['statistiques'] as $stat)
                <tr><td>{{ $stat['libelle'] }}</td><td style="text-align:center;">{{ $stat['nombre'] }}</td><td style="text-align:center;">{{ number_format($stat['pourcentage'], 1, ',', ' ') }}</td></tr>
            @endforeach
        </table>
        <div class="signature">{{ mb_strtoupper(($lieu !== '' ? $lieu.', ' : '').'le '.($payload['date_jury'] ?? '…'), 'UTF-8') }}</div>
    </td>
</tr></table>
</body>
</html>

@php
    $fmt = fn ($n) => $n === null ? '' : number_format((float) $n, 2, ',', ' ');
    $lieu = trim((string) ($payload['ecole']['city'] ?? ''));
    $primary = \App\Helpers\SettingsHelper::getPdfSettings()['primary_color'] ?? '#0453cb';
    $_cadre = array_filter([
        'Département' => $payload['departement'] ?? null,
        'Parcours' => $payload['parcours'] ?? null,
        'Niveau' => trim(($payload['niveau'] ?? '').' · '.($payload['classe'] ?? ''), ' ·') ?: null,
    ]);
@endphp
<x-pdf-document
    :title="$reportTitle ?? 'Procès-verbal de délibération'"
    :subtitle="$reportSubtitle ?? null"
    :filters="$_cadre"
    orientation="landscape">
    <style>
        table.pv { width: 100%; border-collapse: collapse; margin-top: 2mm; }
        table.pv th, table.pv td { border: .35pt solid #94a3b8; padding: .8mm .6mm; text-align: center; font-size: 6.5px; }
        table.pv th { background: {{ $primary }}; color: #fff; font-size: 6px; font-weight: bold; }
        table.pv td.g { text-align: left; }
        table.pv td.moy { font-weight: bold; background: #f1f5f9; }
        table.pv td.ko { color: #b91c1c; }
        table.pied { border-collapse: collapse; margin-top: 4mm; }
        table.pied th, table.pied td { border: .35pt solid #94a3b8; padding: .8mm 2mm; font-size: 7px; }
        table.pied th { background: #e2e8f0; }
        .pv-note { font-size: 6.5px; color: #64748b; margin-top: 1mm; }
        .pv-lieu { margin-top: 4mm; text-align: right; font-size: 8px; font-weight: bold; }
    </style>

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
                    @foreach($ue['ecues'] as $ecue)<th>{{ $ecue['code'] ?: $ecue['name'] }}</th>@endforeach
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
    <div class="pv-note">Notes des éléments : session normale, comme sur les bulletins. SN : session normale · SR : un élément de l’UE est passé au rattrapage.</div>

    <table style="width:100%;"><tr>
        <td style="vertical-align:top;width:55%;">
            <table class="pied">
                <tr><th>UE</th><th>Intitulé</th><th>Éléments</th><th>Crédits</th></tr>
                @foreach($payload['ues'] as $ue)
                    <tr>
                        <td>{{ $ue['code'] }}</td><td>{{ $ue['name'] }}</td>
                        <td>{{ collect($ue['ecues'])->map(fn ($e) => trim(($e['code'] ? $e['code'].' : ' : '').$e['name']))->implode(' · ') }}</td>
                        <td style="text-align:center;">{{ $ue['credit'] }}</td>
                    </tr>
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
            <div class="pv-note">Présent : au moins une note au semestre.</div>
            <div class="pv-lieu">{{ mb_strtoupper(($lieu !== '' ? $lieu.', ' : '').'le '.($payload['date_jury'] ?? '…'), 'UTF-8') }}</div>
        </td>
    </tr></table>
</x-pdf-document>

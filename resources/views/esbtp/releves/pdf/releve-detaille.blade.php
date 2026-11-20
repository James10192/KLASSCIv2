@php
    $fmt = fn ($n) => $n === null || $n === '' ? '—' : number_format((float) $n, 2, ',', ' ');
    $primary = \App\Helpers\SettingsHelper::getPdfSettings()['primary_color'] ?? '#0453cb';
    $_cadre = array_filter(['Classe' => $payload['classe'] ?? null, 'Année' => $payload['annee'] ?? null, 'Période' => $payload['periode'] ?? null]);
@endphp
<x-pdf-document
    :title="$reportTitle ?? 'Relevé de notes détaillé'"
    :subtitle="$reportSubtitle ?? null"
    :filters="$_cadre">
    <style>
        .rd-page { page-break-after: always; }
        .rd-page:last-child { page-break-after: auto; }
        .rd-id { border: .5pt solid #cbd5e1; padding: 2mm 3mm; margin: 2mm 0 3mm; font-size: 9px; }
        .rd-id b { font-size: 10.5px; }
        table.rd { width: 100%; border-collapse: collapse; }
        table.rd th, table.rd td { border: .35pt solid #94a3b8; padding: 1mm 1.5mm; font-size: 8px; text-align: center; }
        table.rd th { background: {{ $primary }}; color: #fff; }
        table.rd td.g { text-align: left; }
        table.rd tr.bloc td { background: #e2e8f0; font-weight: bold; }
        table.rd td.moy { font-weight: bold; }
        .rd-notes { text-align: left; }
        .rd-res { margin-top: 3mm; font-size: 9px; }
        .rd-res span { margin-right: 6mm; }
        .rd-mention { margin-top: 3mm; font-size: 7px; color: #64748b; font-style: italic; }
    </style>

    @forelse($payload['etudiants'] as $e)
        <div class="rd-page">
            <div class="rd-id">
                <b>{{ $e['nom'] }}</b> &nbsp;·&nbsp; Matricule : {{ $e['matricule'] ?: '—' }}
                @if($e['naissance']) &nbsp;·&nbsp; Né(e) le {{ $e['naissance'] }} @endif
            </div>
            <table class="rd">
                <thead>
                    <tr>
                        <th style="width:14%">Code</th>
                        <th style="width:28%">{{ $payload['systeme'] === 'LMD' ? 'Élément' : 'Matière' }}</th>
                        <th style="width:8%">{{ $payload['systeme'] === 'LMD' ? 'Crédits' : 'Coef.' }}</th>
                        <th>Notes des évaluations (sur 20)</th>
                        <th style="width:10%">Moyenne</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($e['blocs'] as $bloc)
                        @if($bloc['libelle'])
                            <tr class="bloc">
                                <td class="g">{{ $bloc['code'] }}</td>
                                <td class="g" colspan="2">{{ $bloc['libelle'] }} ({{ $bloc['credit'] }} crédits)</td>
                                <td class="g">{{ $bloc['acquis'] ? 'UE acquise' : 'UE non acquise' }}</td>
                                <td class="moy">{{ $fmt($bloc['moyenne']) }}</td>
                            </tr>
                        @endif
                        @foreach($bloc['elements'] as $el)
                            <tr>
                                <td class="g">{{ $el['code'] }}</td>
                                <td class="g">{{ $el['libelle'] }}</td>
                                <td>{{ $el['poids'] !== null ? rtrim(rtrim(number_format((float) $el['poids'], 2, ',', ''), '0'), ',') : '' }}</td>
                                <td class="rd-notes">
                                    @forelse($el['notes'] as $n)
                                        <span>{{ $n['titre'] }} : <b>{{ $fmt($n['note']) }}</b>{{ $n['coefficient'] != 1 ? ' (coef. '.rtrim(rtrim(number_format($n['coefficient'], 2, ',', ''), '0'), ',').')' : '' }}{{ $loop->last ? '' : ' | ' }}</span>
                                    @empty
                                        Aucune note saisie
                                    @endforelse
                                </td>
                                <td class="moy">{{ $fmt($el['moyenne']) }}</td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
            <div class="rd-res">
                @foreach($e['resultat'] as $libelle => $valeur)
                    <span>{{ $libelle }} : <b>{{ is_numeric($valeur) ? $fmt($valeur) : $valeur }}</b></span>
                @endforeach
            </div>
            <div class="rd-mention">Document de travail destiné au jury, sans valeur officielle. Les moyennes sont celles calculées par KLASSCI pour les bulletins.</div>
        </div>
    @empty
        <p>Aucun bulletin calculé pour cette classe et cette période : générez d'abord les bulletins.</p>
    @endforelse
</x-pdf-document>

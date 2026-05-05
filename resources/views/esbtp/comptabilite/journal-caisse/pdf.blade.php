@php
    $totalCount = $totals['count'];
    $totalAmount = $totals['total'];
    $pdfCfg = \App\Helpers\SettingsHelper::getPdfSettings();
    $jcPrimary = $pdfCfg['primary_color'] ?? '#0453cb';
@endphp

<x-pdf-document
    title="Journal de caisse"
    :subtitle="html_entity_decode('Livre des recettes chronologique &mdash; conforme OHADA')"
    :filters="$appliedFilters"
    orientation="landscape">

    <style>
        .jc-pdf-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5pt;
            margin-top: 8px;
        }
        .jc-pdf-table th {
            background: {{ $jcPrimary }};
            color: #fff;
            padding: 5px 6px;
            text-align: left;
            font-size: 7.5pt;
            font-weight: bold;
            text-transform: uppercase;
            border: 1px solid {{ $jcPrimary }};
        }
        .jc-pdf-table td {
            padding: 4px 6px;
            border-bottom: 1px solid #e2e8f0;
            color: #1e293b;
        }
        .jc-pdf-table tr:nth-child(even) td {
            background: #f8fafc;
        }
        .jc-pdf-table .num {
            font-weight: bold;
            color: {{ $jcPrimary }};
        }
        .jc-pdf-table .amount {
            font-weight: bold;
            text-align: right;
            white-space: nowrap;
        }
        .jc-pdf-table .amount-rejected {
            color: #b91c1c;
        }
        .jc-pdf-table .amount-pending {
            color: #b45309;
        }
        .jc-pdf-table .meta {
            color: #64748b;
            font-size: 7.5pt;
        }
        .jc-pdf-empty {
            text-align: center;
            padding: 30px 10px;
            color: #94a3b8;
            font-style: italic;
            font-size: 10pt;
        }
    </style>

    <table class="pdf-kpi-table">
        <tr>
            <td class="pdf-kpi-cell" style="width:25%;">
                <div class="pdf-kpi-label">Nombre de lignes</div>
                <div class="pdf-kpi-value">{{ number_format($totalCount, 0, ',', ' ') }}</div>
                <div class="pdf-kpi-sub">Paiements listes</div>
            </td>
            <td class="pdf-kpi-cell" style="width:25%;">
                <div class="pdf-kpi-label">Total encaiss&eacute;</div>
                <div class="pdf-kpi-value">{{ number_format($totalAmount, 0, ',', ' ') }}</div>
                <div class="pdf-kpi-sub">FCFA</div>
            </td>
            <td class="pdf-kpi-cell" style="width:25%;">
                <div class="pdf-kpi-label">Modes utilis&eacute;s</div>
                <div class="pdf-kpi-value">{{ count($totals['by_mode']) }}</div>
                <div class="pdf-kpi-sub">Canaux</div>
            </td>
            <td class="pdf-kpi-cell" style="width:25%;">
                <div class="pdf-kpi-label">P&eacute;riode</div>
                <div class="pdf-kpi-value" style="font-size:10pt;">{{ $dateDebut->format('d/m') }} - {{ $dateFin->format('d/m') }}</div>
                <div class="pdf-kpi-sub">{{ $dateFin->format('Y') }}</div>
            </td>
        </tr>
    </table>

    @if(!empty($totals['by_mode']))
        <table class="pdf-detail-table">
            <thead>
                <tr>
                    <th>Mode de paiement</th>
                    <th style="width:20%; text-align:right;">Lignes</th>
                    <th style="width:26%; text-align:right;">Montant</th>
                </tr>
            </thead>
            <tbody>
                @foreach($totals['by_mode'] as $mode => $stat)
                    <tr>
                        <td>{{ $mode ?: html_entity_decode('Mode non renseign&eacute;') }}</td>
                        <td class="right">{{ number_format($stat['count'], 0, ',', ' ') }}</td>
                        <td class="right">{{ number_format($stat['total'], 0, ',', ' ') }} FCFA</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if($paiements->isEmpty())
        <div class="jc-pdf-empty">
            Aucun paiement enregistr&eacute; pour cette p&eacute;riode ni ces crit&egrave;res.
        </div>
    @else
        <table class="jc-pdf-table">
            <thead>
                <tr>
                    <th style="width:7%;">Date</th>
                    <th style="width:9%;">N&deg; Re&ccedil;u</th>
                    <th style="width:18%;">&Eacute;tudiant</th>
                    <th style="width:14%;">Cat&eacute;gorie</th>
                    <th style="width:10%;">Mode</th>
                    <th style="width:11%; text-align:right;">Montant</th>
                    <th style="width:13%;">Encaiss&eacute; par</th>
                    <th style="width:13%;">Valid&eacute; par</th>
                    <th style="width:5%;">Statut</th>
                </tr>
            </thead>
            <tbody>
                @foreach($paiements as $p)
                    <tr>
                        <td>{{ optional($p->date_paiement)->format('d/m/Y') ?? '-' }}</td>
                        <td class="num">{{ $p->numero_recu ?: '#'.$p->id }}</td>
                        <td>
                            @if($p->inscription && $p->inscription->etudiant)
                                {{ trim(($p->inscription->etudiant->prenoms ?? '') . ' ' . ($p->inscription->etudiant->nom ?? '')) }}<br>
                                <span class="meta">{{ $p->inscription->etudiant->matricule ?? '' }}{{ $p->inscription->classe ? ' - ' . $p->inscription->classe->name : '' }}</span>
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $p->fraisCategory->name ?? $p->motif ?? '-' }}</td>
                        <td>{{ $p->mode_paiement ?? '-' }}</td>
                        <td class="amount {{ in_array($p->status, ['rejete', html_entity_decode('rejet&eacute;')], true) ? 'amount-rejected' : ($p->status === 'en_attente' ? 'amount-pending' : '') }}">
                            {{ number_format((float) $p->montant, 0, ',', ' ') }} F
                        </td>
                        <td>{{ $p->createdBy->name ?? '-' }}</td>
                        <td>
                            @if($p->validatedBy)
                                {{ $p->validatedBy->name }}<br>
                                <span class="meta">{{ optional($p->date_validation)->format('d/m/Y H:i') }}</span>
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ ucfirst(str_replace('_', ' ', $p->status)) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

</x-pdf-document>

@php
    $totalCount = $totals['count'];
    $totalAmount = $totals['total'];
@endphp

<x-pdf-document
    title="Journal de caisse"
    subtitle="Livre des recettes chronologique — conforme OHADA"
    :filters="$appliedFilters"
    orientation="landscape">

    <style>
        .jc-pdf-table { width: 100%; border-collapse: collapse; font-size: 8.5pt; margin-top: 8px; }
        .jc-pdf-table th {
            background: #0453cb; color: #fff;
            padding: 5px 6px; text-align: left;
            font-size: 7.5pt; font-weight: bold; text-transform: uppercase;
            border: 1px solid #033a8e;
        }
        .jc-pdf-table td {
            padding: 4px 6px;
            border-bottom: 1px solid #e2e8f0;
            color: #1e293b;
        }
        .jc-pdf-table tr:nth-child(even) td { background: #f8fafc; }
        .jc-pdf-table .num { font-weight: bold; color: #0453cb; }
        .jc-pdf-table .amount { font-weight: bold; text-align: right; white-space: nowrap; }
        .jc-pdf-table .amount-rejected { color: #b91c1c; }
        .jc-pdf-table .amount-pending { color: #b45309; }
        .jc-pdf-table .meta { color: #64748b; font-size: 7.5pt; }

        .jc-kpi-table { width: 100%; border-collapse: collapse; margin: 12px 0 8px; }
        .jc-kpi-cell {
            width: 25%; background: #0453cb; color: #fff;
            padding: 8px 10px; text-align: center;
            border-right: 1px solid rgba(255,255,255,.25);
        }
        .jc-kpi-label {
            font-size: 7.5pt; font-weight: bold; text-transform: uppercase;
            letter-spacing: .3px; opacity: .78; margin-bottom: 3px;
        }
        .jc-kpi-value { font-size: 14pt; font-weight: bold; line-height: 1.1; }
        .jc-kpi-sub { font-size: 7.5pt; opacity: .72; margin-top: 3px; }
        .jc-mode-table { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 8.5pt; }
        .jc-mode-table th {
            background: #f1f5f9; color: #334155; text-align: left;
            padding: 5px 7px; border: 1px solid #dbe3ef;
            text-transform: uppercase; font-size: 7.2pt;
        }
        .jc-mode-table td { padding: 5px 7px; border: 1px solid #e2e8f0; }
        .jc-mode-table .right { text-align: right; font-weight: bold; }

        .jc-pdf-empty {
            text-align: center; padding: 30px 10px;
            color: #94a3b8; font-style: italic; font-size: 10pt;
        }
    </style>

    @if($paiements->isEmpty())
    <div class="jc-pdf-empty">
        Aucun paiement enregistré pour cette période ni ces critères.
    </div>
    @else
    <table class="jc-pdf-table">
        <thead>
            <tr>
                <th style="width:7%;">Date</th>
                <th style="width:9%;">N° Reçu</th>
                <th style="width:18%;">Étudiant</th>
                <th style="width:14%;">Catégorie</th>
                <th style="width:10%;">Mode</th>
                <th style="width:11%; text-align:right;">Montant</th>
                <th style="width:13%;">Encaissé par</th>
                <th style="width:13%;">Validé par</th>
                <th style="width:5%;">Statut</th>
            </tr>
        </thead>
        <tbody>
            @foreach($paiements as $p)
            <tr>
                <td>{{ optional($p->date_paiement)->format('d/m/Y') ?? '—' }}</td>
                <td class="num">{{ $p->numero_recu ?: '#'.$p->id }}</td>
                <td>
                    @if($p->inscription && $p->inscription->etudiant)
                    {{ trim(($p->inscription->etudiant->prenoms ?? '') . ' ' . ($p->inscription->etudiant->nom ?? '')) }}<br>
                    <span class="meta">{{ $p->inscription->etudiant->matricule ?? '' }}{{ $p->inscription->classe ? ' · ' . $p->inscription->classe->name : '' }}</span>
                    @else
                    —
                    @endif
                </td>
                <td>{{ $p->fraisCategory->name ?? $p->motif ?? '—' }}</td>
                <td>{{ $p->mode_paiement ?? '—' }}</td>
                <td class="amount {{ $p->status === 'rejeté' ? 'amount-rejected' : ($p->status === 'en_attente' ? 'amount-pending' : '') }}">
                    {{ number_format((float) $p->montant, 0, ',', ' ') }} F
                </td>
                <td>{{ $p->createdBy->name ?? '—' }}</td>
                <td>
                    @if($p->validatedBy)
                    {{ $p->validatedBy->name }}<br>
                    <span class="meta">{{ optional($p->date_validation)->format('d/m/Y H:i') }}</span>
                    @else
                    —
                    @endif
                </td>
                <td>{{ ucfirst(str_replace('_', ' ', $p->status)) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <table class="jc-kpi-table">
        <tr>
            <td class="jc-kpi-cell">
                <div class="jc-kpi-label">Nombre de lignes</div>
                <div class="jc-kpi-value">{{ number_format($totalCount, 0, ',', ' ') }}</div>
                <div class="jc-kpi-sub">Paiements listes</div>
            </td>
            <td class="jc-kpi-cell">
                <div class="jc-kpi-label">Total encaisse</div>
                <div class="jc-kpi-value">{{ number_format($totalAmount, 0, ',', ' ') }}</div>
                <div class="jc-kpi-sub">FCFA</div>
            </td>
            <td class="jc-kpi-cell">
                <div class="jc-kpi-label">Modes utilises</div>
                <div class="jc-kpi-value">{{ count($totals['by_mode']) }}</div>
                <div class="jc-kpi-sub">Canaux</div>
            </td>
            <td class="jc-kpi-cell" style="border-right:0;">
                <div class="jc-kpi-label">Periode</div>
                <div class="jc-kpi-value" style="font-size:10pt;">{{ $dateDebut->format('d/m') }} - {{ $dateFin->format('d/m') }}</div>
                <div class="jc-kpi-sub">{{ $dateFin->format('Y') }}</div>
            </td>
        </tr>
    </table>

    @if(!empty($totals['by_mode']))
        <table class="jc-mode-table">
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
                        <td>{{ $mode ?: 'Mode non renseigne' }}</td>
                        <td class="right">{{ number_format($stat['count'], 0, ',', ' ') }}</td>
                        <td class="right">{{ number_format($stat['total'], 0, ',', ' ') }} FCFA</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
    @endif

</x-pdf-document>

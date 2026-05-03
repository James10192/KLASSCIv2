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

        .jc-pdf-totals {
            margin-top: 12px; padding: 10px;
            background: #f0f5ff; border: 1px solid #bfdbfe; border-radius: 6px;
        }
        .jc-pdf-totals-title { font-weight: bold; color: #0453cb; font-size: 9.5pt; margin-bottom: 6px; }
        .jc-pdf-totals-row { display: flex; justify-content: space-between; font-size: 9pt; padding: 2px 0; }
        .jc-pdf-totals-row strong { color: #0f172a; }

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

    <div class="jc-pdf-totals">
        <div class="jc-pdf-totals-title">Récapitulatif de la période</div>
        <div class="jc-pdf-totals-row"><span>Nombre de lignes</span><strong>{{ number_format($totalCount, 0, ',', ' ') }}</strong></div>
        <div class="jc-pdf-totals-row"><span>Total encaissé</span><strong>{{ number_format($totalAmount, 0, ',', ' ') }} FCFA</strong></div>
        @foreach($totals['by_mode'] as $mode => $stat)
        <div class="jc-pdf-totals-row" style="font-size:8.5pt; color:#475569;">
            <span>· {{ $mode ?: 'Mode non renseigné' }} ({{ $stat['count'] }} ligne{{ $stat['count'] > 1 ? 's' : '' }})</span>
            <span>{{ number_format($stat['total'], 0, ',', ' ') }} FCFA</span>
        </div>
        @endforeach
    </div>
    @endif

</x-pdf-document>

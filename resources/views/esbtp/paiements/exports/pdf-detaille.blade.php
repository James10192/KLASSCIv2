<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Tableau détaillé des paiements</title>
    <style>
        @page {
            margin: 0.55cm 0.5cm 0.7cm;
        }
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: {{ $showCreator ? '9px' : '10px' }};
            margin: 0; padding: 0;
            color: #1f2937;
            line-height: 1.3;
        }

        /* ─── Header ─── */
        .header {
            background: #0453cb;
            color: #fff;
            padding: 10px 12px;
            border-radius: 4px;
            margin-bottom: 8px;
        }
        .header h1 {
            font-size: 14px; font-weight: 700;
            margin: 0 0 3px; letter-spacing: 0.5px;
        }
        .header .subtitle {
            font-size: 9.5px;
            opacity: .9;
            margin: 0;
        }
        .header .meta {
            font-size: 8.5px;
            opacity: .75;
            margin-top: 4px;
        }

        /* ─── Filters bar ─── */
        .filters-bar {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 6px 10px;
            margin-bottom: 8px;
            font-size: 8.5px;
        }
        .filters-bar .filter-item { display: inline-block; margin-right: 12px; }
        .filters-bar .filter-label {
            font-weight: 600; color: #475569;
            text-transform: uppercase; font-size: 7.5px;
            letter-spacing: 0.3px;
        }
        .filters-bar .filter-value { color: #0f172a; font-weight: 600; }

        /* ─── Table ─── */
        table.payments {
            width: 100%;
            border-collapse: collapse;
            font-size: {{ $showCreator ? '8.5px' : '9.5px' }};
        }
        table.payments thead th {
            background: #0453cb;
            color: #fff;
            font-weight: 700;
            text-align: left;
            padding: 6px 5px;
            font-size: {{ $showCreator ? '8px' : '9px' }};
            text-transform: uppercase;
            letter-spacing: 0.3px;
            border-right: 1px solid rgba(255,255,255,.15);
        }
        table.payments thead th:last-child { border-right: 0; }
        table.payments tbody td {
            padding: 5px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: middle;
        }
        table.payments tbody tr:nth-child(even) td { background: #f8fafc; }

        .col-date { width: 9%; }
        .col-recu { width: 10%; font-family: 'Courier New', monospace; font-size: {{ $showCreator ? '8px' : '8.5px' }}; }
        .col-etudiant { width: {{ $showCreator ? '22%' : '26%' }}; }
        .col-classe { width: {{ $showCreator ? '12%' : '14%' }}; }
        .col-mode { width: 10%; }
        .col-montant { width: 11%; text-align: right; font-weight: 700; }
        .col-status { width: 9%; text-align: center; }
        .col-creator { width: 17%; }

        .matricule {
            font-family: 'Courier New', monospace;
            font-size: {{ $showCreator ? '7.5px' : '8.5px' }};
            color: #475569;
            display: block;
        }
        .nom {
            font-weight: 600;
            color: #1f2937;
            display: block;
        }

        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 99px;
            font-size: 7px;
            font-weight: 700;
            color: #fff;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .badge-valid { background: #16a34a; }
        .badge-pending { background: #f59e0b; }
        .badge-rejected { background: #dc2626; }
        .badge-default { background: #64748b; }

        /* ─── Footer total ─── */
        .totals {
            margin-top: 10px;
            background: #eff6ff;
            border-radius: 4px;
            padding: 8px 10px;
            border-left: 4px solid #0453cb;
        }
        .totals .label {
            font-size: 9px;
            font-weight: 600;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        .totals .value {
            font-size: 12px;
            font-weight: 700;
            color: #0453cb;
        }
        .totals .row { display: table-row; }
        .totals .cell { display: table-cell; padding: 2px 0; }

        .footer {
            margin-top: 8px;
            text-align: center;
            font-size: 8px;
            color: #94a3b8;
            border-top: 1px solid #e5e7eb;
            padding-top: 5px;
        }
    </style>
</head>
<body>
@php
    $statusBadge = function ($status) {
        $normalized = strtolower(trim($status ?? ''));
        return match ($normalized) {
            'validé', 'valide' => ['Validé', 'badge-valid'],
            'en_attente', 'en attente' => ['En attente', 'badge-pending'],
            'rejeté', 'rejete' => ['Rejeté', 'badge-rejected'],
            'annulé', 'annule' => ['Annulé', 'badge-default'],
            '' => ['—', 'badge-default'],
            default => [ucfirst($status), 'badge-default'],
        };
    };
    $formatMontant = fn ($m) => number_format((float) $m, 0, ',', ' ');
@endphp

<div class="header">
    <h1>{{ $context['title'] ?? 'Tableau détaillé des paiements' }}</h1>
    @if(! empty($context['subtitle_creator']))
        <p class="subtitle">{{ $context['subtitle_creator'] }}</p>
    @endif
    <p class="meta">
        Généré le {{ $dateGeneration->format('d/m/Y à H:i') }}
        — {{ $count }} ligne(s)
        — Total : {{ $formatMontant($totalMontant) }} FCFA
    </p>
</div>

@if(! empty($filtersSummary))
<div class="filters-bar">
    @foreach($filtersSummary as $f)
        <span class="filter-item">
            <span class="filter-label">{{ $f['label'] }}:</span>
            <span class="filter-value">{{ $f['value'] }}</span>
        </span>
    @endforeach
</div>
@endif

<table class="payments">
    <thead>
        <tr>
            <th class="col-date">Date</th>
            <th class="col-recu">N° Reçu</th>
            <th class="col-etudiant">Étudiant</th>
            <th class="col-classe">Classe</th>
            <th class="col-mode">Mode</th>
            <th class="col-montant">Montant</th>
            <th class="col-status">Statut</th>
            @if($showCreator)
                <th class="col-creator">Encaissé par</th>
            @endif
        </tr>
    </thead>
    <tbody>
        @forelse($paiements as $p)
            @php
                [$statusLabel, $statusClass] = $statusBadge($p->status ?? $p->statut ?? '');
                $etu = $p->etudiant;
                $cls = optional($p->inscription)->classe;
                $creator = $p->createdBy;
            @endphp
            <tr>
                <td class="col-date">{{ $p->date_paiement ? $p->date_paiement->format('d/m/Y') : '—' }}</td>
                <td class="col-recu">{{ $p->numero_recu ?? '—' }}</td>
                <td class="col-etudiant">
                    @if($etu)
                        <span class="matricule">{{ $etu->matricule }}</span>
                        <span class="nom">{{ $etu->nom }} {{ $etu->prenoms }}</span>
                    @else
                        <span style="color:#94a3b8;">—</span>
                    @endif
                </td>
                <td class="col-classe">{{ $cls?->name ?? '—' }}</td>
                <td class="col-mode">{{ $p->mode_paiement ?? '—' }}</td>
                <td class="col-montant">{{ $formatMontant($p->montant) }}</td>
                <td class="col-status">
                    <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
                </td>
                @if($showCreator)
                    <td class="col-creator">{{ $creator?->name ?? '—' }}</td>
                @endif
            </tr>
        @empty
            <tr>
                <td colspan="{{ $showCreator ? 8 : 7 }}" style="text-align:center; padding:20px; color:#94a3b8;">
                    Aucun paiement à afficher.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>

<div class="totals">
    <div class="row">
        <div class="cell"><span class="label">Nombre de paiements :</span></div>
        <div class="cell" style="text-align:right;"><span class="value">{{ $count }}</span></div>
    </div>
    <div class="row">
        <div class="cell"><span class="label">Total encaissé :</span></div>
        <div class="cell" style="text-align:right;"><span class="value">{{ $formatMontant($totalMontant) }} FCFA</span></div>
    </div>
</div>

<div class="footer">
    KLASSCI — {{ config('app.name', 'Établissement') }}
    · Document confidentiel généré le {{ $dateGeneration->format('d/m/Y H:i') }}
</div>
</body>
</html>

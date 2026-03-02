<!DOCTYPE html>
<html lang="fr">
<head>
    @include('pdf.partials.theme')
    <meta charset="UTF-8">
    <title>Liste des Paiements - {{ $settings['school_name'] ?? 'Etablissement' }}</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            font-size: 10px;
            margin: 0;
            padding: 0;
            background: #f3f4f6;
            color: #1f2937;
            line-height: 1.4;
        }

        .page { padding: 12px; }

        .container {
            background: #ffffff;
            border-radius: 12px;
            padding: 10px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.04);
        }

        /* HEADER */
        .header-section {
            background: #0453cb;
            color: #ffffff;
            padding: 14px;
            border-radius: 12px;
            text-align: center;
            margin-bottom: 14px;
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }

        .header-logo {
            max-height: 40px;
            margin-bottom: 8px;
            display: block;
            margin-left: auto;
            margin-right: auto;
        }

        .school-name {
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 0.4px;
            margin-bottom: 4px;
        }

        .school-meta {
            font-size: 9px;
            opacity: 0.9;
            margin-bottom: 2px;
        }

        .document-title {
            display: inline-block;
            margin-top: 10px;
            background: rgba(255, 255, 255, 0.18);
            padding: 5px 16px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.8px;
            text-transform: uppercase;
        }

        /* TABLE */
        .payments-table {
            width: 100%;
            border-collapse: collapse;
            background: #ffffff;
            table-layout: fixed;
        }

        .payments-table thead th {
            background: #0453cb;
            color: #ffffff;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            font-size: 7.5px;
            padding: 6px 4px;
            text-align: center;
            border-right: 1px solid rgba(255, 255, 255, 0.2);
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }

        .payments-table thead th:last-child { border-right: none; }

        .payments-table tbody td {
            padding: 5px 4px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: middle;
            font-size: 8.5px;
            word-break: break-word;
        }

        /* Alternating rows */
        .payments-table tbody tr:nth-child(odd) td  { background: #ffffff; }
        .payments-table tbody tr:nth-child(even) td {
            background: #f3f4f6;
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }

        /* STATUS BADGES */
        .status-badge {
            display: inline-block;
            padding: 2px 7px;
            border-radius: 999px;
            font-weight: 700;
            font-size: 7px;
            color: #ffffff;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }

        .status-valid    { background: #16a34a; }
        .status-pending  { background: #f59e0b; }
        .status-rejected { background: #dc2626; }
        .status-default  { background: #6b7280; }

        /* FOOTER */
        .export-footer {
            margin-top: 16px;
            text-align: center;
            font-size: 8px;
            color: #9ca3af;
            border-top: 1px solid #e5e7eb;
            padding-top: 8px;
        }
    </style>
</head>
<body>
@php
    $formatMontant = function ($montant) {
        return number_format((float) $montant, 0, ',', ' ') . ' FCFA';
    };

    $formatDate = function ($date) {
        if (!$date) return 'N/A';
        if ($date instanceof \Carbon\Carbon) return $date->format('d/m/Y');
        try { return \Carbon\Carbon::parse($date)->format('d/m/Y'); }
        catch (\Exception $e) { return (string) $date; }
    };

    $resolveStatus = function ($status) {
        switch ((string) $status) {
            case 'validé': case 'valide':
                return ['label' => 'Validé', 'class' => 'status-valid'];
            case 'rejeté': case 'rejete':
                return ['label' => 'Rejeté', 'class' => 'status-rejected'];
            case 'en_attente': case 'en attente': case 'pending':
                return ['label' => 'En attente', 'class' => 'status-pending'];
            default:
                return ['label' => $status ? ucfirst($status) : 'Inconnu', 'class' => 'status-default'];
        }
    };
@endphp

<div class="page">
    <div class="container">

        {{-- HEADER --}}
        <div class="header-section">
            @if(($settings['show_logo'] ?? false) && !empty($settings['logo_base64']))
                <img src="{{ $settings['logo_base64'] }}" alt="Logo" class="header-logo">
            @endif
            <div class="school-name">{{ $settings['school_name'] ?? 'Etablissement' }}</div>
            @if(!empty($settings['school_address']) || !empty($settings['school_phone']))
                <div class="school-meta">
                    @if(!empty($settings['school_address'])){{ $settings['school_address'] }}@endif
                    @if(!empty($settings['school_phone'])) &bull; Tél : {{ $settings['school_phone'] }}@endif
                </div>
            @endif
            <div class="document-title">Liste des Paiements</div>
        </div>

        {{-- TABLE --}}
        @if(isset($paiements) && $paiements->count() > 0)
            <table class="payments-table">
                <thead>
                    <tr>
                        <th style="width:28px;">N°</th>
                        <th style="width:135px;">Étudiant</th>
                        <th style="width:75px;">Montant</th>
                        <th style="width:65px;">Mode</th>
                        <th style="width:58px;">Date</th>
                        <th style="width:60px;">Statut</th>
                        <th style="width:95px;">Catégorie</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($paiements as $index => $paiement)
                        @php
                            $statusInfo = $resolveStatus($paiement->status ?? null);
                            $nomEtudiant = trim(($paiement->etudiant->prenoms ?? '') . ' ' . ($paiement->etudiant->nom ?? ''));
                            if (!$nomEtudiant) $nomEtudiant = 'N/A';
                            $matricule    = $paiement->etudiant->matricule ?? null;
                            $categorie    = $paiement->fraisCategory->name ?? ($paiement->motif ?? 'N/A');
                            $modePaiement = $paiement->mode_paiement ?? 'N/A';
                            $datePaiement = $formatDate($paiement->date_paiement ?? null);
                            $montant      = $formatMontant($paiement->montant ?? 0);
                        @endphp
                        <tr>
                            <td style="text-align:center; color:#9ca3af;">{{ $index + 1 }}</td>
                            <td>
                                {{ $nomEtudiant }}
                                @if($matricule)
                                    <br><span style="font-size:7.5px;color:#6b7280;">{{ $matricule }}</span>
                                @endif
                            </td>
                            <td style="text-align:right; font-weight:bold; color:#0453cb;">{{ $montant }}</td>
                            <td style="text-align:center;">{{ $modePaiement }}</td>
                            <td style="text-align:center;">{{ $datePaiement }}</td>
                            <td style="text-align:center;">
                                <span class="status-badge {{ $statusInfo['class'] }}">{{ $statusInfo['label'] }}</span>
                            </td>
                            <td>{{ $categorie }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div style="background:#fef3c7; border:1px solid #fcd34d; border-radius:8px; padding:20px; text-align:center; color:#92400e; font-size:10px; margin-top:12px;">
                Aucun paiement trouvé.
            </div>
        @endif

        {{-- FOOTER --}}
        <div class="export-footer">
            <strong>Généré le {{ now()->format('d/m/Y à H:i') }}</strong>
            &bull; {{ $settings['school_name'] ?? 'Etablissement' }}
        </div>

    </div>
</div>
</body>
</html>

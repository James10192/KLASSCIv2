<!DOCTYPE html>
<html lang="fr">
<head>
    @include('pdf.partials.theme')
    <meta charset="UTF-8">
    <title>Export paiements - {{ $etablissement['nom'] ?? $settings['school_name'] ?? 'Établissement' }}</title>
    <style>
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 10px;
            margin: 0;
            padding: 8px;
            color: #1f2937;
            line-height: 1.3;
            background: white;
        }

        .container {
            max-width: 100%;
            background: white;
            padding: 10px;
        }

        .header-section {
            border-radius: 6px;
            margin-bottom: 10px;
            overflow: hidden;
        }

        /* ── KPI row ── */
        .kpi-label {
            font-size: 7.5px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: white;
            opacity: 0.8;
            margin-bottom: 3px;
        }
        .kpi-value {
            font-size: 16px;
            font-weight: 700;
            color: white;
            line-height: 1.1;
            margin-bottom: 2px;
        }
        .kpi-sub {
            font-size: 7px;
            color: white;
            opacity: 0.65;
        }

        /* ── Table ── */
        .payments-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
            background: white;
            font-size: 9.5px;
        }

        .payments-table td {
            padding: 5px 4px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: middle;
        }

        .payments-table tbody tr:nth-child(even) td {
            background-color: #f8f9fa;
        }

        .num-col {
            font-weight: bold;
            font-size: 9px;
            min-width: 16px;
            display: inline-block;
        }

        .matricule-col {
            font-family: 'Courier New', monospace;
            font-size: 8.5px;
            background: #f3f4f6;
            padding: 2px 3px;
            border-radius: 2px;
            color: #374151;
        }

        .student-name {
            font-weight: 600;
            font-size: 9.5px;
            color: #1f2937;
        }

        .montant-col {
            font-weight: 700;
            text-align: right;
            font-size: 10px;
        }

        .status-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 999px;
            font-weight: 600;
            font-size: 7.5px;
            color: #ffffff;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .status-valid { background: #16a34a; }
        .status-pending { background: #f59e0b; }
        .status-rejected { background: #dc2626; }
        .status-default { background: #6b7280; }

        /* ── Stats section ── */
        .section-title {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            color: #1f2937;
            margin: 12px 0 6px;
        }

        .filter-row td {
            padding: 4px 6px;
            font-size: 9px;
            border-bottom: 1px solid #f1f5f9;
        }
        .filter-label {
            font-size: 8px;
            text-transform: uppercase;
            color: #64748b;
            letter-spacing: 0.3px;
        }
        .filter-value {
            font-size: 9.5px;
            color: #1f2937;
            font-weight: 600;
        }

        .export-footer {
            margin-top: 12px;
            text-align: center;
            font-size: 8.5px;
            color: #64748b;
            padding-top: 6px;
            border-top: 1px solid #e5e7eb;
        }

        @page {
            margin: 0.5cm;
            size: A4 landscape;
        }
    </style>
</head>
<body>
@php
    $pdfCfgLocal = $pdfCfg ?? \App\Helpers\SettingsHelper::getPdfSettings();
    $hdrBg   = $pdfCfgLocal['header_bg_color'] ?? $pdfCfgLocal['primary_color'] ?? '#0453cb';
    $hdrText = $pdfCfgLocal['header_text_color'] ?? '#ffffff';
    $primary = $pdfCfgLocal['primary_color'] ?? '#0453cb';
    $etab    = $etablissement ?? [];

    $formatMontant = function ($montant) {
        return number_format((float) $montant, 0, ',', ' ') . ' FCFA';
    };

    $formatDate = function ($date, $withTime = false) {
        if (!$date) return 'N/A';
        if ($date instanceof \Carbon\Carbon) {
            return $withTime ? $date->format('d/m/Y H:i') : $date->format('d/m/Y');
        }
        try {
            $parsed = \Carbon\Carbon::parse($date);
            return $withTime ? $parsed->format('d/m/Y H:i') : $parsed->format('d/m/Y');
        } catch (\Exception $e) {
            return $date;
        }
    };

    $totalPaiements = $stats['total'] ?? $paiements->count();
    $montantTotal = $stats['montant_total'] ?? $paiements->sum('montant');
    $valides = $stats['valides'] ?? 0;
    $montantValide = $stats['montant_valide'] ?? 0;
    $enAttente = $stats['en_attente'] ?? 0;
    $montantEnAttente = $stats['montant_en_attente'] ?? 0;
    $recoveryRate = $stats['recovery_rate'] ?? null;

    $filterItems = [];
    if (!empty($filters['search'])) $filterItems[] = ['label' => 'Recherche', 'value' => $filters['search']];
    if (!empty($filters['status'])) {
        $statusMap = ['en_attente' => 'En attente', 'validé' => 'Validé', 'valide' => 'Validé', 'rejeté' => 'Rejeté', 'rejete' => 'Rejeté'];
        $filterItems[] = ['label' => 'Statut', 'value' => $statusMap[$filters['status']] ?? ucfirst($filters['status'])];
    }
    if (!empty($filters['date_debut'])) $filterItems[] = ['label' => 'Date début', 'value' => $formatDate($filters['date_debut'])];
    if (!empty($filters['date_fin'])) $filterItems[] = ['label' => 'Date fin', 'value' => $formatDate($filters['date_fin'])];
    if (empty($filterItems)) $filterItems[] = ['label' => 'Filtres', 'value' => 'Aucun filtre appliqué'];
@endphp

<div class="container">

    {{-- ═══ HEADER — même pattern que liste-complete-pdf ═══ --}}
    <div class="header-section">
        <table width="100%" border="0" cellspacing="0" cellpadding="0">
            <tr>
                {{-- Logo --}}
                <td width="15%" style="background-color: {{ $hdrBg }}; padding: 14px 10px; text-align: center; vertical-align: middle; border-right: 2px solid rgba(255,255,255,0.25);">
                    @if(($etab['logo'] ?? '') && file_exists(storage_path('app/public/' . $etab['logo'])))
                        <img src="data:image/{{ pathinfo($etab['logo'], PATHINFO_EXTENSION) }};base64,{{ base64_encode(file_get_contents(storage_path('app/public/' . $etab['logo']))) }}"
                             style="max-height: 70px; max-width: 120px;" alt="Logo">
                    @elseif(($settings['logo_base64'] ?? null))
                        <img src="{{ $settings['logo_base64'] }}" style="max-height: 70px; max-width: 120px;" alt="Logo">
                    @else
                        <div style="font-size: 32px; font-weight: 900; color: {{ $hdrText }}; opacity: 0.4; letter-spacing: -2px;">K</div>
                    @endif
                </td>
                {{-- Infos école + titre --}}
                <td width="85%" style="background-color: {{ $hdrBg }}; padding: 12px 16px; vertical-align: middle;">
                    <div style="font-size: 16px; font-weight: 700; color: {{ $hdrText }}; margin-bottom: 2px;">{{ $etab['nom'] ?? $settings['school_name'] ?? 'KLASSCI' }}</div>
                    @if(($etab['adresse'] ?? '') || ($etab['telephone'] ?? '') || ($etab['email'] ?? ''))
                    <div style="font-size: 9px; color: {{ $hdrText }}; opacity: 0.85; margin-bottom: 8px;">
                        @if($etab['adresse'] ?? ''){{ $etab['adresse'] }}@endif
                        @if($etab['telephone'] ?? '')
                            @if($etab['adresse'] ?? '') &nbsp;|&nbsp; @endif
                            Tél: {{ $etab['telephone'] }}
                        @endif
                        @if($etab['email'] ?? '')
                            @if(($etab['adresse'] ?? '') || ($etab['telephone'] ?? '')) &nbsp;|&nbsp; @endif
                            Email: {{ $etab['email'] }}
                        @endif
                    </div>
                    @endif
                    <div style="border-top: 1px solid rgba(255,255,255,0.35); padding-top: 7px;">
                        <div style="font-size: 13px; font-weight: 700; color: {{ $hdrText }}; letter-spacing: 0.5px; margin-bottom: 4px;">TABLEAU DÉTAILLÉ DES PAIEMENTS</div>
                        <table width="100%" border="0" cellspacing="0" cellpadding="0">
                            <tr>
                                <td width="33%" style="font-size: 9px; color: {{ $hdrText }};">
                                    <span style="opacity: 0.75;">Total :</span>
                                    <strong>{{ $totalPaiements }} paiement(s)</strong>
                                </td>
                                <td width="33%" style="font-size: 9px; color: {{ $hdrText }}; text-align: center;">
                                    <span style="opacity: 0.75;">Date :</span>
                                    <strong>{{ now()->format('d/m/Y') }}</strong>
                                </td>
                                <td width="34%" style="font-size: 9px; color: {{ $hdrText }}; text-align: right;">
                                    <span style="opacity: 0.75;">Montant :</span>
                                    <strong>{{ $formatMontant($montantTotal) }}</strong>
                                </td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    {{-- ═══ KPIs ═══ --}}
    <table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-bottom: 10px;">
        <tr>
            <td width="20%" style="background-color: {{ $primary }}; padding: 8px 6px; text-align: center; vertical-align: middle; border-right: 1px solid rgba(255,255,255,0.25);">
                <div class="kpi-label">TOTAL</div>
                <div class="kpi-value">{{ $totalPaiements }}</div>
                <div class="kpi-sub">Paiements</div>
            </td>
            <td width="25%" style="background-color: {{ $primary }}; padding: 8px 6px; text-align: center; vertical-align: middle; border-right: 1px solid rgba(255,255,255,0.25);">
                <div class="kpi-label">MONTANT CUMULÉ</div>
                <div class="kpi-value" style="font-size:13px;">{{ $formatMontant($montantTotal) }}</div>
                <div class="kpi-sub">Tous paiements</div>
            </td>
            <td width="20%" style="background-color: {{ $primary }}; padding: 8px 6px; text-align: center; vertical-align: middle; border-right: 1px solid rgba(255,255,255,0.25);">
                <div class="kpi-label">VALIDÉS</div>
                <div class="kpi-value">{{ $valides }}</div>
                <div class="kpi-sub">{{ $formatMontant($montantValide) }}</div>
            </td>
            <td width="20%" style="background-color: {{ $primary }}; padding: 8px 6px; text-align: center; vertical-align: middle; {{ !is_null($recoveryRate) ? 'border-right: 1px solid rgba(255,255,255,0.25);' : '' }}">
                <div class="kpi-label">EN ATTENTE</div>
                <div class="kpi-value">{{ $enAttente }}</div>
                <div class="kpi-sub">{{ $formatMontant($montantEnAttente) }}</div>
            </td>
            @if(!is_null($recoveryRate))
            <td width="15%" style="background-color: {{ $primary }}; padding: 8px 6px; text-align: center; vertical-align: middle;">
                <div class="kpi-label">RECOUVREMENT</div>
                <div class="kpi-value">{{ $recoveryRate }}%</div>
                <div class="kpi-sub">Taux</div>
            </td>
            @endif
        </tr>
    </table>

    {{-- ═══ TABLE PAIEMENTS ═══ --}}
    @if($paiements->count() > 0)
        <table class="payments-table">
            <thead>
                <tr>
                    <td style="width:4%; background-color:{{ $primary }}; color:{{ $hdrText }}; font-weight:700; text-align:center; font-size:9px; padding:6px 3px;">N°</td>
                    <td style="width:8%; background-color:{{ $primary }}; color:{{ $hdrText }}; font-weight:700; text-align:center; font-size:9px; padding:6px 3px;">Date</td>
                    <td style="width:10%; background-color:{{ $primary }}; color:{{ $hdrText }}; font-weight:700; text-align:center; font-size:9px; padding:6px 3px;">Matricule</td>
                    <td style="width:16%; background-color:{{ $primary }}; color:{{ $hdrText }}; font-weight:700; font-size:9px; padding:6px 3px;">Nom complet</td>
                    <td style="width:12%; background-color:{{ $primary }}; color:{{ $hdrText }}; font-weight:700; font-size:9px; padding:6px 3px;">Classe</td>
                    <td style="width:14%; background-color:{{ $primary }}; color:{{ $hdrText }}; font-weight:700; font-size:9px; padding:6px 3px;">Catégorie</td>
                    <td style="width:10%; background-color:{{ $primary }}; color:{{ $hdrText }}; font-weight:700; text-align:right; font-size:9px; padding:6px 3px;">Montant</td>
                    <td style="width:8%; background-color:{{ $primary }}; color:{{ $hdrText }}; font-weight:700; text-align:center; font-size:9px; padding:6px 3px;">Mode</td>
                    <td style="width:8%; background-color:{{ $primary }}; color:{{ $hdrText }}; font-weight:700; text-align:center; font-size:9px; padding:6px 3px;">Statut</td>
                    <td style="width:10%; background-color:{{ $primary }}; color:{{ $hdrText }}; font-weight:700; text-align:center; font-size:9px; padding:6px 3px;">N° reçu</td>
                </tr>
            </thead>
            <tbody>
                @foreach($paiements as $index => $paiement)
                    @php
                        $status = $paiement->status;
                        $statusLabel = 'En attente';
                        $statusClass = 'status-pending';
                        switch ($status) {
                            case 'validé': case 'valide': $statusLabel = 'Validé'; $statusClass = 'status-valid'; break;
                            case 'rejeté': case 'rejete': $statusLabel = 'Rejeté'; $statusClass = 'status-rejected'; break;
                            case 'en_attente': case 'en attente': case 'pending': $statusLabel = 'En attente'; $statusClass = 'status-pending'; break;
                            default: $statusLabel = $status ? ucfirst($status) : 'N/D'; $statusClass = 'status-default'; break;
                        }
                    @endphp
                    <tr>
                        <td style="text-align:center;"><span class="num-col">{{ $index + 1 }}</span></td>
                        <td style="text-align:center; font-size:9px;">{{ $formatDate($paiement->date_paiement ?? null) }}</td>
                        <td style="text-align:center;"><span class="matricule-col">{{ $paiement->etudiant->matricule ?? 'N/A' }}</span></td>
                        <td><span class="student-name">{{ trim(($paiement->etudiant->nom ?? '') . ' ' . ($paiement->etudiant->prenoms ?? '')) ?: 'N/A' }}</span></td>
                        <td style="font-size:9px;">{{ optional(optional($paiement->inscription)->classe)->name ?? 'N/A' }}</td>
                        <td style="font-size:9px;">{{ $paiement->fraisCategory->name ?? ($paiement->categorie->nom ?? ($paiement->motif ?? 'N/A')) }}</td>
                        <td class="montant-col">{{ $formatMontant($paiement->montant ?? 0) }}</td>
                        <td style="text-align:center; font-size:9px;">{{ $paiement->mode_paiement ?? 'N/A' }}</td>
                        <td style="text-align:center;"><span class="status-badge {{ $statusClass }}">{{ $statusLabel }}</span></td>
                        <td style="text-align:center; font-family:'Courier New',monospace; font-size:8.5px;">{{ $paiement->numero_recu ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div style="background:#fef3c7; border:1px solid #fcd34d; border-radius:6px; padding:14px; text-align:center; color:#92400e; margin-top:10px; font-size:10px;">
            Aucun paiement correspondant aux critères sélectionnés.
        </div>
    @endif

    {{-- ═══ FILTRES APPLIQUÉS ═══ --}}
    @if(count($filterItems) > 0)
    <div class="section-title">Filtres appliqués</div>
    <table width="100%" border="0" cellspacing="0" cellpadding="0" style="border:1px solid #e5e7eb; border-radius:4px;">
        @foreach($filterItems as $filter)
        <tr class="filter-row">
            <td width="25%" style="background:#f8fafc;"><span class="filter-label">{{ $filter['label'] }}</span></td>
            <td width="75%"><span class="filter-value">{{ $filter['value'] ?: 'N/A' }}</span></td>
        </tr>
        @endforeach
    </table>
    @endif

    {{-- ═══ FOOTER ═══ --}}
    <div class="export-footer">
        <strong>Export généré le {{ $formatDate($dateExport ?? now(), true) }}</strong>
        &nbsp;—&nbsp; {{ $etab['nom'] ?? $settings['school_name'] ?? 'KLASSCI' }}
    </div>

</div>
</body>
</html>

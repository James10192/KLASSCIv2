<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Suivi Paiements – {{ $category->name ?? '' }} – {{ $statutLabel ?? '' }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        @page {
            size: A4 portrait;
            margin: 0;
        }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 10px;
            color: #1f2937;
            line-height: 1.4;
            background: #ffffff;
        }

        /* Wrapper principal — remplace les marges @page */
        .page-wrapper {
            padding: 22mm 18mm 20mm 18mm;
        }

        /* ── Barre accent décorative ── */
        .accent-bar {
            height: 5px;
            margin-bottom: 10px;
            border-radius: 2px;
            -webkit-print-color-adjust: exact;
        }

        /* ── Header ── */
        .header-section {
            border-radius: 6px;
            margin-bottom: 10px;
            overflow: hidden;
            -webkit-print-color-adjust: exact;
        }

        .header-main {
            padding: 12px 14px 10px;
        }

        .header-logo {
            max-height: 40px;
            max-width: 90px;
        }

        .school-name {
            font-size: 15px;
            font-weight: bold;
            letter-spacing: 0.5px;
            margin-bottom: 3px;
        }

        .school-info {
            font-size: 8.5px;
            opacity: 0.88;
        }

        .header-date {
            font-size: 8.5px;
            opacity: 0.85;
            text-align: right;
        }

        /* ── Title band ── */
        .title-band {
            margin-bottom: 10px;
            border-radius: 3px;
            overflow: hidden;
            -webkit-print-color-adjust: exact;
        }

        .title-band-inner {
            padding: 7px 12px;
        }

        .title-text {
            font-size: 11.5px;
            font-weight: bold;
            color: #0f172a;
        }

        .statut-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 10px;
            font-size: 9px;
            font-weight: bold;
        }

        .badge-non-payes {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }

        .badge-en-retard {
            background-color: #fef3c7;
            color: #92400e;
            border: 1px solid #fcd34d;
        }

        .badge-a-jour {
            background-color: #d1fae5;
            color: #065f46;
            border: 1px solid #6ee7b7;
        }

        /* ── KPI cards ── */
        .kpi-section {
            margin-bottom: 12px;
        }

        .kpi-card {
            border: 1px solid #e5e7eb;
            border-radius: 5px;
            overflow: hidden;
            -webkit-print-color-adjust: exact;
        }

        .kpi-card-header {
            background-color: #f1f5f9;
            padding: 5px 10px;
            font-size: 7.5px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            font-weight: bold;
            border-bottom: 1px solid #e2e8f0;
        }

        .kpi-card-value {
            padding: 7px 10px 8px;
            font-size: 14px;
            font-weight: bold;
        }

        /* ── Table ── */
        .students-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
            margin-bottom: 12px;
        }

        .students-table thead td {
            color: #ffffff;
            padding: 6px 7px;
            font-weight: bold;
            font-size: 8.5px;
            -webkit-print-color-adjust: exact;
        }

        .students-table tbody tr:nth-child(even) td {
            background-color: #f8fafc;
            -webkit-print-color-adjust: exact;
        }

        .students-table tbody td {
            padding: 5px 7px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: middle;
        }

        .students-table tfoot td {
            background-color: #eff6ff;
            font-weight: bold;
            padding: 6px 7px;
            font-size: 9px;
            -webkit-print-color-adjust: exact;
        }

        .row-num {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            text-align: center;
            line-height: 20px;
            font-size: 8px;
            font-weight: bold;
            display: inline-block;
            -webkit-print-color-adjust: exact;
        }

        .text-right  { text-align: right; }
        .text-center { text-align: center; }

        /* ── Empty state ── */
        .empty-state td {
            text-align: center;
            color: #9ca3af;
            font-style: italic;
            padding: 28px;
            font-size: 10px;
        }

        /* ── Footer ── */
        .pdf-footer {
            margin-top: 12px;
            padding-top: 8px;
            border-top: 1px solid #e2e8f0;
        }

        .pdf-footer-left {
            font-size: 8px;
            color: #94a3b8;
        }

        .pdf-footer-right {
            text-align: right;
            font-size: 8px;
            color: #94a3b8;
        }
    </style>
    @include('pdf.partials.theme')
</head>
<body>

@php
    $primaryColor   = $pdfSettings['primary_color']   ?? '#0453cb';
    $headerBg       = $pdfSettings['header_bg_color'] ?? $primaryColor;
    $headerText     = $pdfSettings['header_text_color'] ?? '#ffffff';
    $schoolName     = $schoolInfo['name']    ?? config('app.name');
    $schoolAddress  = $schoolInfo['address'] ?? '';
    $schoolPhone    = $schoolInfo['phone']   ?? $schoolInfo['mobile'] ?? '';
    $schoolEmail    = $schoolInfo['email']   ?? '';
    $schoolLogo     = $schoolInfo['logo']    ?? '';

    // Calcul couleur accent (version légèrement plus claire du primary)
    $accentColor = $primaryColor;

    $logoBase64 = null;
    if ($schoolLogo) {
        $paths = [
            storage_path('app/public/' . $schoolLogo),
            public_path('storage/' . $schoolLogo),
            public_path($schoolLogo),
        ];
        foreach ($paths as $p) {
            if (file_exists($p)) {
                $mime = mime_content_type($p);
                $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($p));
                break;
            }
        }
    }

    $total        = $stats['total']              ?? $etudiants->count();
    $montantDu    = $stats['montant_total_du']   ?? $etudiants->sum(fn($e) => $e['montant_attendu'] ?? 0);
    $montantPaye  = $stats['montant_total_paye'] ?? $etudiants->sum(fn($e) => $e['montant_paye'] ?? 0);
    $taux         = $stats['taux_recouvrement']  ?? ($montantDu > 0 ? round(($montantPaye / $montantDu) * 100, 1) : 0);
    $soldeTotal   = $montantDu - $montantPaye;

    $statut = $stats['statut'] ?? '';
    $badgeClass = match($statut) {
        'non_payes' => 'badge-non-payes',
        'en_retard' => 'badge-en-retard',
        'a_jour'    => 'badge-a-jour',
        default     => 'badge-en-retard',
    };

    $contactParts = array_filter([
        $schoolAddress ?: null,
        $schoolPhone ? 'Tél : ' . $schoolPhone : null,
        $schoolEmail ?: null,
    ]);
@endphp

{{-- ── BARRE ACCENT (fixed, top) ── --}}
<div class="accent-bar" style="background-color: {{ $accentColor }};"></div>

{{-- ── WRAPPER ── --}}
<div class="page-wrapper">

    {{-- ── HEADER ── --}}
    <div class="header-section" style="background-color: {{ $headerBg }};">
        <table width="100%" border="0" cellspacing="0" cellpadding="0" class="header-main">
            <tr>
                {{-- Logo --}}
                <td width="14%" style="vertical-align: middle; padding-right: 8px;">
                    @if($logoBase64)
                        <img src="{{ $logoBase64 }}"
                             style="max-height: 40px; max-width: 90px; filter: brightness(0) invert(1);"
                             alt="Logo">
                    @endif
                </td>

                {{-- Nom + coordonnées --}}
                <td width="72%" style="text-align: center; vertical-align: middle;">
                    <div class="school-name" style="color: {{ $headerText }};">
                        {{ strtoupper($schoolName) }}
                    </div>
                    @if(!empty($contactParts))
                    <div class="school-info" style="color: {{ $headerText }};">
                        {{ implode(' • ', $contactParts) }}
                    </div>
                    @endif
                </td>

                {{-- Date --}}
                <td width="14%" style="vertical-align: top; text-align: right;">
                    <div class="header-date" style="color: {{ $headerText }};">
                        {{ now()->format('d/m/Y') }}
                    </div>
                </td>
            </tr>
        </table>
    </div>

    {{-- ── TITLE BAND ── --}}
    <div class="title-band" style="background-color: #e8edfd; border-left: 4px solid {{ $primaryColor }};">
        <table width="100%" border="0" cellspacing="0" cellpadding="0" class="title-band-inner">
            <tr>
                <td style="vertical-align: middle;">
                    <span class="title-text">
                        Suivi Paiements —
                        <span style="color: {{ $primaryColor }};">{{ $category->name ?? 'Catégorie' }}</span>
                    </span>
                </td>
                <td style="text-align: right; vertical-align: middle;">
                    <span class="statut-badge {{ $badgeClass }}">{{ $statutLabel }}</span>
                </td>
            </tr>
        </table>
    </div>

    {{-- ── KPI CARDS ── --}}
    <table width="100%" border="0" cellspacing="0" cellpadding="0" class="kpi-section">
        <tr>
            {{-- Total étudiants --}}
            <td width="25%" style="padding-right: 5px;">
                <div class="kpi-card">
                    <div class="kpi-card-header">TOTAL ÉTUDIANTS</div>
                    <div class="kpi-card-value" style="color: {{ $primaryColor }};">{{ $total }}</div>
                </div>
            </td>
            {{-- Montant dû --}}
            <td width="25%" style="padding: 0 5px;">
                <div class="kpi-card">
                    <div class="kpi-card-header">MONTANT DÛ</div>
                    <div class="kpi-card-value" style="color: #0f172a; font-size: 11px;">
                        {{ number_format($montantDu, 0, ',', ' ') }} FCFA
                    </div>
                </div>
            </td>
            {{-- Montant payé --}}
            <td width="25%" style="padding: 0 5px;">
                <div class="kpi-card">
                    <div class="kpi-card-header">MONTANT PAYÉ</div>
                    <div class="kpi-card-value" style="color: #059669; font-size: 11px;">
                        {{ number_format($montantPaye, 0, ',', ' ') }} FCFA
                    </div>
                </div>
            </td>
            {{-- Taux recouvrement --}}
            <td width="25%" style="padding-left: 5px;">
                <div class="kpi-card">
                    <div class="kpi-card-header">TAUX RECOUVREMENT</div>
                    <div class="kpi-card-value"
                         style="color: {{ $taux >= 80 ? '#059669' : ($taux >= 50 ? '#d97706' : '#dc2626') }};">
                        {{ $taux }}%
                    </div>
                </div>
            </td>
        </tr>
    </table>

    {{-- ── STUDENT TABLE ── --}}
    <table class="students-table">
        <thead>
            <tr style="background-color: {{ $primaryColor }}; -webkit-print-color-adjust: exact;">
                <td class="text-center" style="width: 26px; border-radius: 3px 0 0 0;">N°</td>
                <td style="width: 72px;">Matricule</td>
                <td>Nom & Prénom(s)</td>
                <td style="width: 82px;">Classe</td>
                <td style="width: 62px;">Filière</td>
                <td class="text-right" style="width: 82px;">Montant Dû</td>
                <td class="text-right" style="width: 72px;">Payé</td>
                <td class="text-right" style="width: 72px;">Solde</td>
                <td class="text-center" style="width: 34px; border-radius: 0 3px 0 0;">%</td>
            </tr>
        </thead>
        <tbody>
            @forelse($etudiants as $i => $item)
            @php
                $inscription = $item['inscription'];
                $etudiant    = $inscription->etudiant ?? null;
                $pct         = $item['pourcentage'] ?? 0;
                $pctColor    = $pct >= 100 ? '#059669' : ($pct > 0 ? '#d97706' : '#dc2626');
            @endphp
            <tr>
                <td class="text-center">
                    <span class="row-num"
                          style="background-color: {{ $primaryColor }}; color: #ffffff; -webkit-print-color-adjust: exact;">
                        {{ $i + 1 }}
                    </span>
                </td>
                <td style="font-size: 8.5px; color: #374151;">
                    {{ $etudiant?->matricule ?? 'N/A' }}
                </td>
                <td style="font-weight: bold; font-size: 9px;">
                    {{ $etudiant ? strtoupper($etudiant->nom ?? '') . ' ' . ($etudiant->prenoms ?? '') : '—' }}
                </td>
                <td style="font-size: 8.5px; color: #374151;">
                    {{ $inscription->classe->name ?? ($inscription->niveauEtude->name ?? '—') }}
                </td>
                <td style="font-size: 8.5px; color: #374151;">
                    {{ $inscription->filiere->name ?? '—' }}
                </td>
                <td class="text-right" style="font-size: 8.5px;">
                    {{ number_format($item['montant_attendu'] ?? 0, 0, ',', ' ') }}
                </td>
                <td class="text-right" style="font-size: 8.5px; color: #059669; font-weight: bold;">
                    {{ number_format($item['montant_paye'] ?? 0, 0, ',', ' ') }}
                </td>
                <td class="text-right"
                    style="font-size: 8.5px; font-weight: bold;
                           color: {{ ($item['solde'] ?? 0) > 0 ? '#dc2626' : '#059669' }};">
                    {{ number_format($item['solde'] ?? 0, 0, ',', ' ') }}
                </td>
                <td class="text-center" style="font-weight: bold; font-size: 8.5px; color: {{ $pctColor }};">
                    {{ $pct }}%
                </td>
            </tr>
            @empty
            <tr class="empty-state">
                <td colspan="9">Aucun étudiant dans cette liste.</td>
            </tr>
            @endforelse
        </tbody>
        @if($etudiants->count() > 0)
        <tfoot>
            <tr>
                <td colspan="5"
                    style="text-align: right; background-color: #eff6ff; padding: 6px 7px;
                           border-top: 2px solid {{ $primaryColor }}; font-weight: bold; font-size: 9px;
                           -webkit-print-color-adjust: exact;">
                    TOTAUX
                </td>
                <td class="text-right"
                    style="background-color: #eff6ff; padding: 6px 7px;
                           border-top: 2px solid {{ $primaryColor }}; font-weight: bold; font-size: 9px;
                           -webkit-print-color-adjust: exact;">
                    {{ number_format($montantDu, 0, ',', ' ') }}
                </td>
                <td class="text-right"
                    style="background-color: #eff6ff; padding: 6px 7px;
                           border-top: 2px solid {{ $primaryColor }}; font-weight: bold; font-size: 9px;
                           color: #059669; -webkit-print-color-adjust: exact;">
                    {{ number_format($montantPaye, 0, ',', ' ') }}
                </td>
                <td class="text-right"
                    style="background-color: #eff6ff; padding: 6px 7px;
                           border-top: 2px solid {{ $primaryColor }}; font-weight: bold; font-size: 9px;
                           color: {{ $soldeTotal > 0 ? '#dc2626' : '#059669' }};
                           -webkit-print-color-adjust: exact;">
                    {{ number_format($soldeTotal, 0, ',', ' ') }}
                </td>
                <td class="text-center"
                    style="background-color: #eff6ff; padding: 6px 7px;
                           border-top: 2px solid {{ $primaryColor }}; font-weight: bold; font-size: 9px;
                           -webkit-print-color-adjust: exact;">
                    {{ $taux }}%
                </td>
            </tr>
        </tfoot>
        @endif
    </table>

    {{-- ── FOOTER ── --}}
    <table width="100%" border="0" cellspacing="0" cellpadding="0" class="pdf-footer">
        <tr>
            <td class="pdf-footer-left">
                Catégorie : <strong>{{ $category->name ?? '—' }}</strong>
                @if(!empty($stats['filiere_name']))
                    — Filière : <strong>{{ $stats['filiere_name'] }}</strong>
                @endif
                @if(!empty($stats['niveau_name']))
                    — Niveau : <strong>{{ $stats['niveau_name'] }}</strong>
                @endif
            </td>
            <td class="pdf-footer-right">
                Généré le {{ now()->format('d/m/Y à H:i') }} — {{ $schoolName }}
            </td>
        </tr>
    </table>

</div>{{-- end .page-wrapper --}}

</body>
</html>

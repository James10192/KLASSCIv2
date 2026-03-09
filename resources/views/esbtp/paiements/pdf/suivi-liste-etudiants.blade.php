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
            margin: 18mm 15mm 18mm 15mm;
        }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 10px;
            color: #1f2937;
            line-height: 1.3;
            background: #ffffff;
        }

        /* ── Header ── */
        .header-section {
            background-color: #0453cb;
            color: #ffffff;
            padding: 8px 10px 6px;
            border-radius: 4px;
            margin-bottom: 6px;
            -webkit-print-color-adjust: exact;
        }

        .header-logo {
            max-height: 32px;
            max-width: 80px;
            filter: brightness(0) invert(1);
        }

        .school-name {
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 1px;
        }

        .school-info {
            font-size: 8px;
            opacity: 0.9;
        }

        /* ── Title band ── */
        .title-band {
            margin-bottom: 6px;
        }

        .title-band td {
            background-color: #e8edfd;
            border-left: 4px solid #0453cb;
            padding: 5px 10px;
            border-radius: 0 3px 3px 0;
            font-size: 11px;
            font-weight: bold;
            color: #0f172a;
        }

        .statut-badge {
            display: inline-block;
            padding: 2px 8px;
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
            margin-bottom: 8px;
        }

        .kpi-section td {
            vertical-align: top;
            padding: 0 3px;
        }

        .kpi-section td:first-child { padding-left: 0; }
        .kpi-section td:last-child  { padding-right: 0; }

        .kpi-card {
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
        }

        .kpi-card-header {
            background-color: #f8fafc;
            padding: 4px 8px;
            font-size: 8px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            font-weight: bold;
            border-bottom: 1px solid #e5e7eb;
            -webkit-print-color-adjust: exact;
        }

        .kpi-card-value {
            padding: 5px 8px;
            font-size: 13px;
            font-weight: bold;
            color: #0453cb;
        }

        /* ── Table ── */
        .students-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
            margin-bottom: 10px;
        }

        .students-table thead td {
            background-color: #0453cb;
            color: #ffffff;
            padding: 5px 6px;
            font-weight: bold;
            font-size: 8.5px;
            border-bottom: 2px solid #0343ab;
            -webkit-print-color-adjust: exact;
        }

        .students-table tbody tr:nth-child(even) td {
            background-color: #f8fafc;
            -webkit-print-color-adjust: exact;
        }

        .students-table tbody td {
            padding: 4px 6px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: middle;
        }

        .students-table tfoot td {
            background-color: #eff6ff;
            font-weight: bold;
            padding: 5px 6px;
            border-top: 2px solid #0453cb;
            font-size: 9px;
            -webkit-print-color-adjust: exact;
        }

        .row-num {
            width: 22px;
            background-color: #0453cb;
            color: #ffffff;
            border-radius: 10px;
            text-align: center;
            padding: 1px 4px;
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
            padding: 20px;
            font-size: 10px;
        }

        /* ── Footer ── */
        .pdf-footer {
            margin-top: 10px;
            padding-top: 6px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            font-size: 8px;
            color: #9ca3af;
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
    $schoolPhone    = $schoolInfo['phone']   ?? '';
    $schoolEmail    = $schoolInfo['email']   ?? '';
    $schoolLogo     = $schoolInfo['logo']    ?? '';

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

    // Badge CSS class based on statut
    $statut = $stats['statut'] ?? '';
    $badgeClass = match($statut) {
        'non_payes' => 'badge-non-payes',
        'en_retard' => 'badge-en-retard',
        'a_jour'    => 'badge-a-jour',
        default     => 'badge-en-retard',
    };
@endphp

{{-- ── HEADER ── --}}
<table width="100%" border="0" cellspacing="0" cellpadding="0"
       style="background-color: {{ $headerBg }}; border-radius: 4px; margin-bottom: 6px; -webkit-print-color-adjust: exact;">
    <tr>
        {{-- Logo --}}
        <td width="15%" style="padding: 8px 6px 6px 10px; vertical-align: middle;">
            @if($logoBase64)
                <img src="{{ $logoBase64 }}"
                     style="max-height: 36px; max-width: 90px; filter: brightness(0) invert(1);"
                     alt="Logo">
            @endif
        </td>

        {{-- School name & info --}}
        <td width="70%" style="padding: 8px 6px 6px; text-align: center; vertical-align: middle;">
            <div style="font-size: 14px; font-weight: bold; color: {{ $headerText }}; margin-bottom: 2px;">
                {{ strtoupper($schoolName) }}
            </div>
            @if($schoolAddress || $schoolPhone || $schoolEmail)
            <div style="font-size: 8px; color: {{ $headerText }}; opacity: 0.9;">
                {{ implode(' • ', array_filter([$schoolAddress, $schoolPhone ? 'Tél : '.$schoolPhone : null, $schoolEmail])) }}
            </div>
            @endif
        </td>

        {{-- Date --}}
        <td width="15%" style="padding: 8px 10px 6px 6px; text-align: right; vertical-align: top;">
            <div style="font-size: 8px; color: {{ $headerText }}; opacity: 0.85;">
                {{ now()->format('d/m/Y') }}
            </div>
        </td>
    </tr>
</table>

{{-- ── TITLE BAND ── --}}
<table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-bottom: 7px;">
    <tr>
        <td style="background-color: #e8edfd;
                   border-left: 4px solid {{ $primaryColor }};
                   padding: 5px 10px;
                   -webkit-print-color-adjust: exact;">
            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                <tr>
                    <td style="vertical-align: middle;">
                        <span style="font-size: 11px; font-weight: bold; color: #0f172a;">
                            Suivi Paiements —
                            <span style="color: {{ $primaryColor }};">{{ $category->name ?? 'Catégorie' }}</span>
                        </span>
                    </td>
                    <td style="text-align: right; vertical-align: middle;">
                        <span class="statut-badge {{ $badgeClass }}">
                            {{ $statutLabel }}
                        </span>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

{{-- ── KPI CARDS ── --}}
<table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-bottom: 8px;">
    <tr>
        <td width="25%" style="padding-right: 4px;">
            <table width="100%" border="0" cellspacing="0" cellpadding="0"
                   style="border: 1px solid #e5e7eb; border-radius: 4px;">
                <tr>
                    <td style="background-color: #f8fafc; padding: 4px 8px;
                               font-size: 8px; color: #6b7280; text-transform: uppercase;
                               font-weight: bold; border-bottom: 1px solid #e5e7eb;
                               -webkit-print-color-adjust: exact;">
                        TOTAL ÉTUDIANTS
                    </td>
                </tr>
                <tr>
                    <td style="padding: 5px 8px; font-size: 15px; font-weight: bold; color: {{ $primaryColor }};">
                        {{ $total }}
                    </td>
                </tr>
            </table>
        </td>
        <td width="25%" style="padding: 0 4px;">
            <table width="100%" border="0" cellspacing="0" cellpadding="0"
                   style="border: 1px solid #e5e7eb; border-radius: 4px;">
                <tr>
                    <td style="background-color: #f8fafc; padding: 4px 8px;
                               font-size: 8px; color: #6b7280; text-transform: uppercase;
                               font-weight: bold; border-bottom: 1px solid #e5e7eb;
                               -webkit-print-color-adjust: exact;">
                        MONTANT DÛ
                    </td>
                </tr>
                <tr>
                    <td style="padding: 5px 8px; font-size: 11px; font-weight: bold; color: #0f172a;">
                        {{ number_format($montantDu, 0, ',', ' ') }} FCFA
                    </td>
                </tr>
            </table>
        </td>
        <td width="25%" style="padding: 0 4px;">
            <table width="100%" border="0" cellspacing="0" cellpadding="0"
                   style="border: 1px solid #e5e7eb; border-radius: 4px;">
                <tr>
                    <td style="background-color: #f8fafc; padding: 4px 8px;
                               font-size: 8px; color: #6b7280; text-transform: uppercase;
                               font-weight: bold; border-bottom: 1px solid #e5e7eb;
                               -webkit-print-color-adjust: exact;">
                        MONTANT PAYÉ
                    </td>
                </tr>
                <tr>
                    <td style="padding: 5px 8px; font-size: 11px; font-weight: bold; color: #059669;">
                        {{ number_format($montantPaye, 0, ',', ' ') }} FCFA
                    </td>
                </tr>
            </table>
        </td>
        <td width="25%" style="padding-left: 4px;">
            <table width="100%" border="0" cellspacing="0" cellpadding="0"
                   style="border: 1px solid #e5e7eb; border-radius: 4px;">
                <tr>
                    <td style="background-color: #f8fafc; padding: 4px 8px;
                               font-size: 8px; color: #6b7280; text-transform: uppercase;
                               font-weight: bold; border-bottom: 1px solid #e5e7eb;
                               -webkit-print-color-adjust: exact;">
                        TAUX RECOUVREMENT
                    </td>
                </tr>
                <tr>
                    <td style="padding: 5px 8px; font-size: 15px; font-weight: bold;
                               color: {{ $taux >= 80 ? '#059669' : ($taux >= 50 ? '#d97706' : '#dc2626') }};">
                        {{ $taux }}%
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

{{-- ── STUDENT TABLE ── --}}
<table class="students-table">
    <thead>
        <tr>
            <td class="text-center" style="width: 28px;">N°</td>
            <td style="width: 70px;">Matricule</td>
            <td>Nom & Prénom(s)</td>
            <td style="width: 80px;">Classe</td>
            <td style="width: 60px;">Filière</td>
            <td class="text-right" style="width: 80px;">Montant Dû</td>
            <td class="text-right" style="width: 80px;">Payé</td>
            <td class="text-right" style="width: 80px;">Solde</td>
            <td class="text-center" style="width: 36px;">%</td>
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
                <span class="row-num">{{ $i + 1 }}</span>
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
            <td class="text-right" style="font-size: 8.5px; color: {{ ($item['solde'] ?? 0) > 0 ? '#dc2626' : '#059669' }}; font-weight: bold;">
                {{ number_format($item['solde'] ?? 0, 0, ',', ' ') }}
            </td>
            <td class="text-center" style="font-weight: bold; font-size: 8.5px; color: {{ $pctColor }};">
                {{ $pct }}%
            </td>
        </tr>
        @empty
        <tr class="empty-state">
            <td colspan="9" style="text-align: center; color: #9ca3af; font-style: italic; padding: 20px; font-size: 10px;">
                Aucun étudiant dans cette liste.
            </td>
        </tr>
        @endforelse
    </tbody>
    @if($etudiants->count() > 0)
    <tfoot>
        <tr>
            <td colspan="5" style="text-align: right; font-size: 9px; background-color: #eff6ff; padding: 5px 6px;
                                    border-top: 2px solid #0453cb; font-weight: bold; -webkit-print-color-adjust: exact;">
                TOTAUX
            </td>
            <td class="text-right" style="background-color: #eff6ff; padding: 5px 6px;
                                          border-top: 2px solid #0453cb; font-weight: bold; font-size: 9px;
                                          -webkit-print-color-adjust: exact;">
                {{ number_format($montantDu, 0, ',', ' ') }}
            </td>
            <td class="text-right" style="background-color: #eff6ff; padding: 5px 6px;
                                          border-top: 2px solid #0453cb; font-weight: bold; font-size: 9px;
                                          color: #059669; -webkit-print-color-adjust: exact;">
                {{ number_format($montantPaye, 0, ',', ' ') }}
            </td>
            <td class="text-right" style="background-color: #eff6ff; padding: 5px 6px;
                                          border-top: 2px solid #0453cb; font-weight: bold; font-size: 9px;
                                          color: {{ $soldeTotal > 0 ? '#dc2626' : '#059669' }};
                                          -webkit-print-color-adjust: exact;">
                {{ number_format($soldeTotal, 0, ',', ' ') }}
            </td>
            <td class="text-center" style="background-color: #eff6ff; padding: 5px 6px;
                                           border-top: 2px solid #0453cb; font-weight: bold; font-size: 9px;
                                           -webkit-print-color-adjust: exact;">
                {{ $taux }}%
            </td>
        </tr>
    </tfoot>
    @endif
</table>

{{-- ── FOOTER ── --}}
<table width="100%" border="0" cellspacing="0" cellpadding="0"
       style="margin-top: 8px; padding-top: 6px; border-top: 1px solid #e5e7eb;">
    <tr>
        <td style="font-size: 8px; color: #9ca3af;">
            Catégorie : <strong>{{ $category->name ?? '—' }}</strong>
            @if(!empty($stats['filiere_name']))
                — Filière : <strong>{{ $stats['filiere_name'] }}</strong>
            @endif
            @if(!empty($stats['niveau_name']))
                — Niveau : <strong>{{ $stats['niveau_name'] }}</strong>
            @endif
        </td>
        <td style="text-align: right; font-size: 8px; color: #9ca3af;">
            Généré le {{ now()->format('d/m/Y à H:i') }}
            — {{ $schoolName }}
        </td>
    </tr>
</table>

</body>
</html>

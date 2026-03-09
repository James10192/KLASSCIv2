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
            margin: {{ $pdfSettings['margin_top'] ?? 18 }}mm
                    {{ $pdfSettings['margin_right'] ?? 15 }}mm
                    {{ $pdfSettings['margin_bottom'] ?? 18 }}mm
                    {{ $pdfSettings['margin_left'] ?? 15 }}mm;
        }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 10px;
            color: {{ $pdfSettings['text_color'] ?? '#1f2937' }};
            line-height: 1.4;
            background: #ffffff;
        }

        /* ── Header ── */
        .header-table td {
            background-color: {{ $pdfSettings['header_bg_color'] ?? '#0453cb' }};
            padding: 12px 14px 10px;
            -webkit-print-color-adjust: exact;
        }

        .school-name {
            font-size: 15px;
            font-weight: bold;
            letter-spacing: 0.5px;
            color: {{ $pdfSettings['header_text_color'] ?? '#ffffff' }};
            margin-bottom: 3px;
        }

        .school-info {
            font-size: 8.5px;
            opacity: 0.88;
            color: {{ $pdfSettings['header_text_color'] ?? '#ffffff' }};
        }

        /* ── Title band ── */
        .title-band td {
            background-color: #e8edfd;
            border-left: 4px solid {{ $pdfSettings['primary_color'] ?? '#0453cb' }};
            padding: 7px 12px;
            -webkit-print-color-adjust: exact;
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
        .kpi-header td {
            background-color: #f1f5f9;
            padding: 6px 10px;
            font-size: 7.5px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            font-weight: bold;
            border-bottom: 1px solid #e2e8f0;
            -webkit-print-color-adjust: exact;
        }

        .kpi-value td {
            padding: 8px 10px 9px;
            font-size: 14px;
            font-weight: bold;
        }

        /* ── Table ── */
        .students-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
            margin-bottom: 14px;
        }

        .students-table thead td {
            background-color: {{ $pdfSettings['primary_color'] ?? '#0453cb' }};
            color: #ffffff;
            padding: 7px 7px;
            font-weight: bold;
            font-size: 8.5px;
            -webkit-print-color-adjust: exact;
        }

        .students-table tbody tr:nth-child(even) td {
            background-color: #f8fafc;
            -webkit-print-color-adjust: exact;
        }

        .students-table tbody tr:nth-child(odd) td {
            background-color: #ffffff;
        }

        .students-table tbody td {
            padding: 6px 7px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: middle;
        }

        .students-table tfoot td {
            background-color: #eff6ff;
            font-weight: bold;
            padding: 7px 7px;
            border-top: 2px solid {{ $pdfSettings['primary_color'] ?? '#0453cb' }};
            font-size: 9px;
            -webkit-print-color-adjust: exact;
        }

        .row-num {
            width: 20px;
            height: 20px;
            background-color: {{ $pdfSettings['primary_color'] ?? '#0453cb' }};
            color: #ffffff;
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

        /* ── Footer ── */
        .pdf-footer td {
            padding-top: 8px;
            border-top: 1px solid #e2e8f0;
            font-size: 8px;
            color: #94a3b8;
        }
    </style>
    @include('pdf.partials.theme')
</head>
<body>

@php
    $primaryColor  = $pdfSettings['primary_color']    ?? '#0453cb';
    $headerBg      = $pdfSettings['header_bg_color']  ?? $primaryColor;
    $headerText    = $pdfSettings['header_text_color'] ?? '#ffffff';
    $schoolName    = $schoolInfo['name']    ?? config('app.name');
    $schoolAddress = $schoolInfo['address'] ?? '';
    $schoolPhone   = $schoolInfo['phone']   ?? $schoolInfo['mobile'] ?? '';
    $schoolEmail   = $schoolInfo['email']   ?? '';
    $schoolLogo    = $schoolInfo['logo']    ?? '';

    $logoBase64 = null;
    if ($schoolLogo) {
        $paths = [
            storage_path('app/public/' . $schoolLogo),
            public_path('storage/' . $schoolLogo),
            public_path($schoolLogo),
        ];
        foreach ($paths as $p) {
            if (file_exists($p)) {
                $mime       = mime_content_type($p);
                $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($p));
                break;
            }
        }
    }

    $total       = $stats['total']              ?? $etudiants->count();
    $montantDu   = $stats['montant_total_du']   ?? $etudiants->sum(fn($e) => $e['montant_attendu'] ?? 0);
    $montantPaye = $stats['montant_total_paye'] ?? $etudiants->sum(fn($e) => $e['montant_paye'] ?? 0);
    $taux        = $stats['taux_recouvrement']  ?? ($montantDu > 0 ? round(($montantPaye / $montantDu) * 100, 1) : 0);
    $soldeTotal  = $montantDu - $montantPaye;

    $statut     = $stats['statut'] ?? '';
    $badgeClass = match($statut) {
        'non_payes' => 'badge-non-payes',
        'en_retard' => 'badge-en-retard',
        'a_jour'    => 'badge-a-jour',
        default     => 'badge-en-retard',
    };

    $contactParts = array_filter([
        $schoolAddress ?: null,
        $schoolPhone   ? 'Tél : ' . $schoolPhone : null,
        $schoolEmail   ?: null,
    ]);
@endphp

{{-- ── HEADER ── --}}
<table class="header-table" width="100%" border="0" cellspacing="0" cellpadding="0"
       style="border-radius: 6px; margin-bottom: 10px; overflow: hidden;">
    <tr>
        {{-- Logo --}}
        <td width="14%" style="text-align: left; vertical-align: middle;
                                background-color: {{ $headerBg }}; padding: 12px 8px 10px 14px;">
            @if($logoBase64)
                <img src="{{ $logoBase64 }}"
                     style="max-height: 40px; max-width: 90px; filter: brightness(0) invert(1);"
                     alt="Logo">
            @endif
        </td>

        {{-- Nom + coordonnées --}}
        <td width="72%" style="text-align: center; vertical-align: middle;
                                background-color: {{ $headerBg }}; padding: 12px 6px 10px;">
            <div class="school-name">{{ strtoupper($schoolName) }}</div>
            @if(!empty($contactParts))
                <div class="school-info">{{ implode(' • ', $contactParts) }}</div>
            @endif
        </td>

        {{-- Date --}}
        <td width="14%" style="text-align: right; vertical-align: top;
                                background-color: {{ $headerBg }}; padding: 12px 14px 10px 6px;">
            <div style="font-size: 8.5px; color: {{ $headerText }}; opacity: 0.85;">
                {{ now()->format('d/m/Y') }}
            </div>
        </td>
    </tr>
</table>

{{-- ── TITLE BAND ── --}}
<table class="title-band" width="100%" border="0" cellspacing="0" cellpadding="0"
       style="margin-bottom: 10px; border-radius: 3px;">
    <tr>
        <td>
            <table width="100%" border="0" cellspacing="0" cellpadding="0">
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
        </td>
    </tr>
</table>

{{-- ── KPI CARDS ── --}}
<table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-bottom: 12px;">
    <tr>
        {{-- Total étudiants --}}
        <td width="25%" style="padding-right: 5px; vertical-align: top;">
            <table width="100%" border="0" cellspacing="0" cellpadding="0"
                   style="border: 1px solid #e5e7eb; border-radius: 5px; overflow: hidden;">
                <tr class="kpi-header"><td>TOTAL ÉTUDIANTS</td></tr>
                <tr class="kpi-value">
                    <td style="color: {{ $primaryColor }};">{{ $total }}</td>
                </tr>
            </table>
        </td>
        {{-- Montant dû --}}
        <td width="25%" style="padding: 0 5px; vertical-align: top;">
            <table width="100%" border="0" cellspacing="0" cellpadding="0"
                   style="border: 1px solid #e5e7eb; border-radius: 5px; overflow: hidden;">
                <tr class="kpi-header"><td>MONTANT DÛ</td></tr>
                <tr class="kpi-value">
                    <td style="color: #0f172a; font-size: 11px;">
                        {{ number_format($montantDu, 0, ',', ' ') }} FCFA
                    </td>
                </tr>
            </table>
        </td>
        {{-- Montant payé --}}
        <td width="25%" style="padding: 0 5px; vertical-align: top;">
            <table width="100%" border="0" cellspacing="0" cellpadding="0"
                   style="border: 1px solid #e5e7eb; border-radius: 5px; overflow: hidden;">
                <tr class="kpi-header"><td>MONTANT PAYÉ</td></tr>
                <tr class="kpi-value">
                    <td style="color: #059669; font-size: 11px;">
                        {{ number_format($montantPaye, 0, ',', ' ') }} FCFA
                    </td>
                </tr>
            </table>
        </td>
        {{-- Taux recouvrement --}}
        <td width="25%" style="padding-left: 5px; vertical-align: top;">
            <table width="100%" border="0" cellspacing="0" cellpadding="0"
                   style="border: 1px solid #e5e7eb; border-radius: 5px; overflow: hidden;">
                <tr class="kpi-header"><td>TAUX RECOUVREMENT</td></tr>
                <tr class="kpi-value">
                    <td style="color: {{ $taux >= 80 ? '#059669' : ($taux >= 50 ? '#d97706' : '#dc2626') }};">
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
            <td class="text-center" style="width: 26px;">N°</td>
            <td style="width: 72px;">Matricule</td>
            <td>Nom & Prénom(s)</td>
            <td style="width: 82px;">Classe</td>
            <td style="width: 62px;">Filière</td>
            <td class="text-right" style="width: 82px;">Montant Dû</td>
            <td class="text-right" style="width: 72px;">Payé</td>
            <td class="text-right" style="width: 72px;">Solde</td>
            <td class="text-center" style="width: 34px;">%</td>
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
        <tr>
            <td colspan="9"
                style="text-align: center; color: #9ca3af; font-style: italic; padding: 28px; font-size: 10px;">
                Aucun étudiant dans cette liste.
            </td>
        </tr>
        @endforelse
    </tbody>
    @if($etudiants->count() > 0)
    <tfoot>
        <tr>
            <td colspan="5" style="text-align: right;">TOTAUX</td>
            <td class="text-right">{{ number_format($montantDu, 0, ',', ' ') }}</td>
            <td class="text-right" style="color: #059669;">
                {{ number_format($montantPaye, 0, ',', ' ') }}
            </td>
            <td class="text-right" style="color: {{ $soldeTotal > 0 ? '#dc2626' : '#059669' }};">
                {{ number_format($soldeTotal, 0, ',', ' ') }}
            </td>
            <td class="text-center">{{ $taux }}%</td>
        </tr>
    </tfoot>
    @endif
</table>

{{-- ── FOOTER ── --}}
<table class="pdf-footer" width="100%" border="0" cellspacing="0" cellpadding="0"
       style="margin-top: 12px;">
    <tr>
        <td style="padding-top: 8px; border-top: 1px solid #e2e8f0; font-size: 8px; color: #94a3b8;">
            Catégorie : <strong>{{ $category->name ?? '—' }}</strong>
            @if(!empty($stats['filiere_name']))
                — Filière : <strong>{{ $stats['filiere_name'] }}</strong>
            @endif
            @if(!empty($stats['niveau_name']))
                — Niveau : <strong>{{ $stats['niveau_name'] }}</strong>
            @endif
        </td>
        <td style="padding-top: 8px; border-top: 1px solid #e2e8f0; text-align: right;
                   font-size: 8px; color: #94a3b8;">
            Généré le {{ now()->format('d/m/Y à H:i') }} — {{ $schoolName }}
        </td>
    </tr>
</table>

</body>
</html>

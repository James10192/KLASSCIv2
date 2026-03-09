<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Suivi Paiements – {{ $category->name ?? '' }} – {{ $statutLabel ?? '' }}</title>
    <style>
        /* ── Reset ── */
        * { margin: 0; padding: 0; box-sizing: border-box; }

        @page {
            size: A4 portrait;
            margin: 20mm 15mm 20mm 15mm;
        }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 10px;
            color: {{ $pdfSettings['text_color'] ?? '#1f2937' }};
            line-height: 1.4;
            background: #ffffff;
            padding: 8px;
        }

        .container {
            max-width: 100%;
            background: white;
            padding: 10px;
        }

        /* ── Header ── */
        .header-section {
            border-radius: 6px;
            margin-bottom: 12px;
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
            overflow: hidden;
        }

        /* ── Table data ── */
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
            text-transform: uppercase;
            letter-spacing: 0.2px;
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }

        .students-table tbody tr:nth-child(even) td {
            background-color: #f8fafc;
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
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
            padding: 8px 7px;
            border-top: 2px solid {{ $pdfSettings['primary_color'] ?? '#0453cb' }};
            font-size: 9px;
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
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
            color-adjust: exact;
        }

        .student-matricule {
            font-family: 'Courier New', monospace;
            background: #f3f4f6;
            padding: 2px 3px;
            border-radius: 2px;
            font-size: 8px;
            color: #374151;
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }

        /* ── Badges ── */
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

        /* ── Footer ── */
        .footer-section {
            margin-top: 12px;
        }

        .summary-card {
            background: #f8f9fa;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            padding: 9px;
        }

        .summary-title {
            font-size: 10px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 0.2px;
        }

        .info-card {
            background: #f8f9fa;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            padding: 9px;
        }

        .info-label {
            font-size: 8px;
            color: #6b7280;
            margin-bottom: 1px;
        }

        .info-value {
            font-size: 9px;
            font-weight: 600;
            color: #374151;
        }

        .generation-info {
            text-align: center;
            font-size: 8px;
            color: #6b7280;
            margin-top: 10px;
            padding-top: 6px;
            border-top: 1px solid #e5e7eb;
        }

        /* ── Utilities ── */
        .text-right  { text-align: right; }
        .text-center { text-align: center; }

        /* ── Print optimizations ── */
        @media print {
            body { background: white; padding: 4px; }
            .container { padding: 8px; }
            .header-section { margin-bottom: 10px; }
            .footer-section { margin-top: 10px; }
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
    $schoolPhone   = $schoolInfo['phone']   ?? ($schoolInfo['mobile'] ?? '');
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
@endphp

<div class="container">

    {{-- ── HEADER SECTION — Logo | Infos école + Titre document ── --}}
    <div class="header-section">
        <table width="100%" border="0" cellspacing="0" cellpadding="0">
            <tr>
                {{-- Logo --}}
                <td width="18%" style="background-color: {{ $headerBg }}; padding: 14px 10px; text-align: center; vertical-align: middle; border-right: 2px solid rgba(255,255,255,0.25);">
                    @if($logoBase64)
                        <img src="{{ $logoBase64 }}"
                             style="max-height: 55px; max-width: 100px; filter: brightness(0) invert(1);"
                             alt="Logo">
                    @else
                        <div style="font-size: 30px; font-weight: 900; color: {{ $headerText }}; opacity: 0.4; letter-spacing: -2px;">K</div>
                    @endif
                </td>

                {{-- Nom + coordonnées + titre document --}}
                <td width="82%" style="background-color: {{ $headerBg }}; padding: 12px 16px; vertical-align: middle;">
                    {{-- Nom établissement --}}
                    <div style="font-size: 15px; font-weight: 700; color: {{ $headerText }}; margin-bottom: 2px;">
                        {{ strtoupper($schoolName) }}
                    </div>

                    {{-- Adresse | Tél | Email --}}
                    @if($schoolAddress || $schoolPhone || $schoolEmail)
                    <div style="font-size: 8.5px; color: {{ $headerText }}; opacity: 0.85; margin-bottom: 8px;">
                        @if($schoolAddress){{ $schoolAddress }}@endif
                        @if($schoolPhone)
                            @if($schoolAddress) &nbsp;|&nbsp; @endif
                            Tél: {{ $schoolPhone }}
                        @endif
                        @if($schoolEmail)
                            @if($schoolAddress || $schoolPhone) &nbsp;|&nbsp; @endif
                            Email: {{ $schoolEmail }}
                        @endif
                    </div>
                    @endif

                    {{-- Séparateur + titre document + sous-infos --}}
                    <div style="border-top: 1px solid rgba(255,255,255,0.35); padding-top: 7px;">
                        <div style="font-size: 12px; font-weight: 700; color: {{ $headerText }}; letter-spacing: 0.5px; margin-bottom: 5px;">
                            SUIVI DES PAIEMENTS
                        </div>
                        <table width="100%" border="0" cellspacing="0" cellpadding="0">
                            <tr>
                                <td width="33%" style="font-size: 9px; color: {{ $headerText }};">
                                    <span style="color: {{ $headerText }}; opacity: 0.75;">Catégorie :</span>
                                    <strong style="color: {{ $headerText }};">{{ $category->name ?? 'N/A' }}</strong>
                                </td>
                                <td width="33%" style="font-size: 9px; color: {{ $headerText }}; text-align: center;">
                                    <span style="color: {{ $headerText }}; opacity: 0.75;">Statut :</span>
                                    <strong style="color: {{ $headerText }};">{{ $statutLabel }}</strong>
                                </td>
                                <td width="34%" style="font-size: 9px; color: {{ $headerText }}; text-align: right;">
                                    <span style="color: {{ $headerText }}; opacity: 0.75;">Date :</span>
                                    <strong style="color: {{ $headerText }};">{{ now()->format('d/m/Y') }}</strong>
                                </td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    {{-- ── KPI SECTION — 4 cellules uniformes fond bleu ── --}}
    <table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-bottom: 12px;">
        <tr>
            {{-- Total étudiants --}}
            <td width="25%" style="background-color: {{ $primaryColor }}; padding: 9px 8px; text-align: center; vertical-align: middle; border-right: 1px solid rgba(255,255,255,0.25); -webkit-print-color-adjust: exact; color-adjust: exact;">
                <div style="font-size: 7.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: white; opacity: 0.8; margin-bottom: 4px;">TOTAL ÉTUDIANTS</div>
                <div style="font-size: 18px; font-weight: 700; color: white; line-height: 1.1; margin-bottom: 4px;">{{ $total }}</div>
                <div style="font-size: 7px; color: white; opacity: 0.65;">{{ $statutLabel }}</div>
            </td>
            {{-- Montant dû --}}
            <td width="25%" style="background-color: {{ $primaryColor }}; padding: 9px 8px; text-align: center; vertical-align: middle; border-right: 1px solid rgba(255,255,255,0.25); -webkit-print-color-adjust: exact; color-adjust: exact;">
                <div style="font-size: 7.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: white; opacity: 0.8; margin-bottom: 4px;">MONTANT DÛ</div>
                <div style="font-size: 11px; font-weight: 700; color: white; line-height: 1.25; margin-bottom: 4px;">{{ number_format($montantDu, 0, ',', ' ') }} F</div>
                <div style="font-size: 7px; color: white; opacity: 0.65;">Total attendu</div>
            </td>
            {{-- Montant payé --}}
            <td width="25%" style="background-color: {{ $primaryColor }}; padding: 9px 8px; text-align: center; vertical-align: middle; border-right: 1px solid rgba(255,255,255,0.25); -webkit-print-color-adjust: exact; color-adjust: exact;">
                <div style="font-size: 7.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: white; opacity: 0.8; margin-bottom: 4px;">MONTANT PAYÉ</div>
                <div style="font-size: 11px; font-weight: 700; color: white; line-height: 1.25; margin-bottom: 4px;">{{ number_format($montantPaye, 0, ',', ' ') }} F</div>
                <div style="font-size: 7px; color: white; opacity: 0.65;">Total reçu</div>
            </td>
            {{-- Taux recouvrement --}}
            <td width="25%" style="background-color: {{ $primaryColor }}; padding: 9px 8px; text-align: center; vertical-align: middle; -webkit-print-color-adjust: exact; color-adjust: exact;">
                <div style="font-size: 7.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: white; opacity: 0.8; margin-bottom: 4px;">RECOUVREMENT</div>
                <div style="font-size: 18px; font-weight: 700; color: white; line-height: 1.1; margin-bottom: 4px;">{{ $taux }}%</div>
                <div style="font-size: 7px; color: white; opacity: 0.65;">Taux de paiement</div>
            </td>
        </tr>
    </table>

    {{-- ── Filtres actifs (si filière ou niveau spécifié) ── --}}
    @if(!empty($stats['filiere_name']) || !empty($stats['niveau_name']))
    <table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-bottom: 10px;">
        <tr>
            <td style="background-color: #f0f9ff; border-left: 3px solid {{ $primaryColor }}; padding: 6px 10px; font-size: 9px; color: #1e40af; border-radius: 3px; -webkit-print-color-adjust: exact; color-adjust: exact;">
                <strong>Filtres :</strong>
                @if(!empty($stats['filiere_name']))
                    Filière : <strong>{{ $stats['filiere_name'] }}</strong>
                @endif
                @if(!empty($stats['niveau_name']))
                    @if(!empty($stats['filiere_name'])) — @endif
                    Niveau : <strong>{{ $stats['niveau_name'] }}</strong>
                @endif
            </td>
        </tr>
    </table>
    @endif

    {{-- ── TABLE DES ÉTUDIANTS ── --}}
    <table class="students-table">
        <thead>
            <tr>
                <td class="text-center" style="width: 26px;">N°</td>
                <td style="width: 72px;">Matricule</td>
                <td>Nom & Prénom(s)</td>
                <td style="width: 80px;">Classe</td>
                <td style="width: 60px;">Filière</td>
                <td class="text-right" style="width: 80px;">Montant Dû</td>
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
                <td>
                    <span class="student-matricule">{{ $etudiant?->matricule ?? 'N/A' }}</span>
                </td>
                <td style="font-weight: bold; font-size: 9px; text-align: left;">
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

    {{-- ── FOOTER SECTION — 2 colonnes : Résumé | Infos document ── --}}
    @if($etudiants->count() > 0)
    <div class="footer-section">
        <table width="100%" border="0" cellspacing="0" cellpadding="0">
            <tr>
                {{-- Résumé financier --}}
                <td width="48%" style="vertical-align: top; padding-right: 6px;">
                    <div class="summary-card">
                        <div class="summary-title">Résumé financier</div>
                        <table width="100%" border="0" cellspacing="0" cellpadding="3">
                            <tr>
                                <td width="50%" style="text-align: center; vertical-align: top;">
                                    <div style="font-size: 13px; font-weight: bold; color: {{ $primaryColor }};">{{ number_format($montantDu, 0, ',', ' ') }}</div>
                                    <div style="font-size: 8px; color: #6b7280;">Montant total dû (FCFA)</div>
                                </td>
                                <td width="50%" style="text-align: center; vertical-align: top;">
                                    <div style="font-size: 13px; font-weight: bold; color: #059669;">{{ number_format($montantPaye, 0, ',', ' ') }}</div>
                                    <div style="font-size: 8px; color: #6b7280;">Montant total payé (FCFA)</div>
                                </td>
                            </tr>
                            <tr>
                                <td style="text-align: center; vertical-align: top; padding-top: 6px;">
                                    <div style="font-size: 13px; font-weight: bold; color: {{ $soldeTotal > 0 ? '#dc2626' : '#059669' }};">{{ number_format($soldeTotal, 0, ',', ' ') }}</div>
                                    <div style="font-size: 8px; color: #6b7280;">Solde restant (FCFA)</div>
                                </td>
                                <td style="text-align: center; vertical-align: top; padding-top: 6px;">
                                    <div style="font-size: 13px; font-weight: bold; color: {{ $taux >= 80 ? '#059669' : ($taux >= 50 ? '#d97706' : '#dc2626') }};">{{ $taux }}%</div>
                                    <div style="font-size: 8px; color: #6b7280;">Taux de recouvrement</div>
                                </td>
                            </tr>
                        </table>
                    </div>
                </td>

                {{-- Infos document --}}
                <td width="4%"></td>
                <td width="48%" style="vertical-align: top; padding-left: 6px;">
                    <div class="info-card">
                        <div class="summary-title">Informations document</div>
                        <table width="100%" border="0" cellspacing="0" cellpadding="2">
                            <tr>
                                <td>
                                    <div class="info-label">Document généré le :</div>
                                    <div class="info-value">{{ now()->format('d/m/Y à H:i') }}</div>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding-top: 4px;">
                                    <div class="info-label">Par :</div>
                                    <div class="info-value">{{ auth()->user()->name ?? 'Système' }}</div>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding-top: 4px;">
                                    <div class="info-label">Établissement :</div>
                                    <div class="info-value">{{ $schoolName }}</div>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding-top: 4px;">
                                    <div class="info-label">Catégorie :</div>
                                    <div class="info-value">{{ $category->name ?? '—' }}</div>
                                </td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>
        </table>
    </div>
    @endif

    {{-- ── GENERATION INFO ── --}}
    <div class="generation-info">
        <strong>Document généré automatiquement le {{ now()->format('d/m/Y à H:i') }}</strong><br>
        {{ $schoolName }} — Système de Gestion KLASSCI
    </div>

</div>

</body>
</html>

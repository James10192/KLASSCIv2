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
            margin: 20mm 15mm 20mm 15mm;
        }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 10px;
            color: {{ $pdfSettings['text_color'] ?? '#1f2937' }};
            line-height: 1.4;
            background: #ffffff;
        }

        /* ── Table data ── */
        .students-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
            margin-bottom: 10px;
        }

        .students-table thead td {
            background-color: {{ $pdfSettings['primary_color'] ?? '#0453cb' }};
            color: #ffffff;
            padding: 6px 6px;
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

        .students-table tbody td {
            padding: 5px 6px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: middle;
        }

        .students-table tfoot td {
            background-color: #eff6ff;
            font-weight: bold;
            padding: 7px 6px;
            border-top: 2px solid {{ $pdfSettings['primary_color'] ?? '#0453cb' }};
            font-size: 9px;
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }

        /* Row number — same pattern as liste-complete-pdf.blade.php (works in DomPDF) */
        .student-number {
            background: {{ $pdfSettings['primary_color'] ?? '#0453cb' }};
            color: white;
            padding: 2px 4px;
            border-radius: 50%;
            font-weight: bold;
            font-size: 8px;
            min-width: 16px;
            display: inline-block;
            text-align: center;
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }

        .student-matricule {
            font-family: 'Courier New', monospace;
            font-size: 8px;
            color: #374151;
        }

        /* ── Utilities ── */
        .text-right  { text-align: right; }
        .text-center { text-align: center; }

        .summary-card {
            background: #f8f9fa;
            border: 1px solid #e5e7eb;
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

    // Chunk support: variables passées par le controller pour les gros exports
    $isFirstChunk = $isFirstChunk ?? true;
    $isLastChunk  = $isLastChunk ?? true;
    $rowOffset    = $rowOffset ?? 0;

    $logoBase64 = null;
    if ($isFirstChunk && $schoolLogo) {
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
@endphp

{{-- ── HEADER + KPI — Only on first chunk ── --}}
@if($isFirstChunk)
<table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-bottom: 12px;">
    <tr>
        <td width="18%" style="background-color: {{ $headerBg }}; padding: 14px 10px; text-align: center; vertical-align: middle; border-right: 2px solid rgba(255,255,255,0.25); -webkit-print-color-adjust: exact; color-adjust: exact;">
            @if($logoBase64)
                <img src="{{ $logoBase64 }}"
                     style="max-height: 55px; max-width: 100px; filter: brightness(0) invert(1);"
                     alt="Logo">
            @else
                <span style="font-size: 30px; font-weight: 900; color: {{ $headerText }}; opacity: 0.4; letter-spacing: -2px;">K</span>
            @endif
        </td>
        <td width="82%" style="background-color: {{ $headerBg }}; padding: 12px 16px; vertical-align: middle; -webkit-print-color-adjust: exact; color-adjust: exact;">
            <span style="font-size: 15px; font-weight: 700; color: {{ $headerText }};">{{ mb_strtoupper($schoolName ?? '', 'UTF-8') }}</span>
            @if($schoolAddress || $schoolPhone || $schoolEmail)
            <br><span style="font-size: 8.5px; color: {{ $headerText }}; opacity: 0.85;">@if($schoolAddress){{ $schoolAddress }}@endif @if($schoolPhone)@if($schoolAddress) | @endif Tel: {{ $schoolPhone }}@endif @if($schoolEmail)@if($schoolAddress || $schoolPhone) | @endif Email: {{ $schoolEmail }}@endif</span>
            @endif
            <br><span style="font-size: 1px; color: {{ $headerBg }};">.</span>
            <table width="100%" border="0" cellspacing="0" cellpadding="0" style="border-top: 1px solid rgba(255,255,255,0.35); margin-top: 4px; padding-top: 4px;">
                <tr>
                    <td style="font-size: 12px; font-weight: 700; color: {{ $headerText }}; padding-top: 5px;">SUIVI DES PAIEMENTS</td>
                </tr>
                <tr>
                    <td>
                        <table width="100%" border="0" cellspacing="0" cellpadding="0">
                            <tr>
                                <td width="33%" style="font-size: 9px; color: {{ $headerText }};">
                                    <span style="opacity: 0.75;">Catégorie :</span> <strong>{{ $category->name ?? 'N/A' }}</strong>
                                </td>
                                <td width="33%" style="font-size: 9px; color: {{ $headerText }}; text-align: center;">
                                    <span style="opacity: 0.75;">Statut :</span> <strong>{{ $statutLabel }}</strong>
                                </td>
                                <td width="34%" style="font-size: 9px; color: {{ $headerText }}; text-align: right;">
                                    <span style="opacity: 0.75;">Date :</span> <strong>{{ now()->format('d/m/Y') }}</strong>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

{{-- KPI --}}
<table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-bottom: 12px;">
    <tr>
        <td width="25%" style="background-color: {{ $primaryColor }}; padding: 9px 8px; text-align: center; vertical-align: middle; border-right: 1px solid rgba(255,255,255,0.25); -webkit-print-color-adjust: exact; color-adjust: exact;">
            <span style="font-size: 7.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: white; opacity: 0.8;">TOTAL ÉTUDIANTS</span><br>
            <span style="font-size: 18px; font-weight: 700; color: white;">{{ $total }}</span><br>
            <span style="font-size: 7px; color: white; opacity: 0.65;">{{ $statutLabel }}</span>
        </td>
        <td width="25%" style="background-color: {{ $primaryColor }}; padding: 9px 8px; text-align: center; vertical-align: middle; border-right: 1px solid rgba(255,255,255,0.25); -webkit-print-color-adjust: exact; color-adjust: exact;">
            <span style="font-size: 7.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: white; opacity: 0.8;">MONTANT DÛ</span><br>
            <span style="font-size: 11px; font-weight: 700; color: white;">{{ number_format($montantDu, 0, ',', ' ') }} F</span><br>
            <span style="font-size: 7px; color: white; opacity: 0.65;">Total attendu</span>
        </td>
        <td width="25%" style="background-color: {{ $primaryColor }}; padding: 9px 8px; text-align: center; vertical-align: middle; border-right: 1px solid rgba(255,255,255,0.25); -webkit-print-color-adjust: exact; color-adjust: exact;">
            <span style="font-size: 7.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: white; opacity: 0.8;">MONTANT PAYÉ</span><br>
            <span style="font-size: 11px; font-weight: 700; color: white;">{{ number_format($montantPaye, 0, ',', ' ') }} F</span><br>
            <span style="font-size: 7px; color: white; opacity: 0.65;">Total reçu</span>
        </td>
        <td width="25%" style="background-color: {{ $primaryColor }}; padding: 9px 8px; text-align: center; vertical-align: middle; -webkit-print-color-adjust: exact; color-adjust: exact;">
            <span style="font-size: 7.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: white; opacity: 0.8;">RECOUVREMENT</span><br>
            <span style="font-size: 18px; font-weight: 700; color: white;">{{ $taux }}%</span><br>
            <span style="font-size: 7px; color: white; opacity: 0.65;">Taux de paiement</span>
        </td>
    </tr>
</table>

{{-- Filtres actifs --}}
@if(!empty($stats['filiere_name']) || !empty($stats['niveau_name']))
<table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-bottom: 10px;">
    <tr>
        <td style="background-color: #f0f9ff; border-left: 3px solid {{ $primaryColor }}; padding: 6px 10px; font-size: 9px; color: #1e40af; -webkit-print-color-adjust: exact; color-adjust: exact;">
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
@else
{{-- Non-first chunk: light continuation header --}}
<table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-bottom: 8px;">
    <tr>
        <td style="font-size: 9px; color: #6b7280; padding: 4px 0; border-bottom: 1px solid #e5e7eb;">
            {{ $category->name ?? '' }} — {{ $statutLabel }} — <strong>{{ $total }}</strong> étudiants (suite)
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
            $rowNum      = $rowOffset + $i + 1;
        @endphp
        <tr>
            <td class="text-center">
                <span class="student-number">{{ $rowNum }}</span>
            </td>
            <td>
                <span class="student-matricule">{{ $etudiant?->matricule ?? 'N/A' }}</span>
            </td>
            <td style="font-weight: bold; font-size: 9px;">
                {{ $etudiant ? mb_strtoupper($etudiant->nom ?? '', 'UTF-8') . ' ' . ($etudiant->prenoms ?? '') : '—' }}
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
            <td class="text-right" style="font-size: 8.5px; font-weight: bold; color: {{ ($item['solde'] ?? 0) > 0 ? '#dc2626' : '#059669' }};">
                {{ number_format($item['solde'] ?? 0, 0, ',', ' ') }}
            </td>
            <td class="text-center" style="font-weight: bold; font-size: 8.5px; color: {{ $pctColor }};">
                {{ $pct }}%
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="9" style="text-align: center; color: #9ca3af; font-style: italic; padding: 28px; font-size: 10px;">
                Aucun étudiant dans cette liste.
            </td>
        </tr>
        @endforelse
    </tbody>
    @if($isLastChunk && $etudiants->count() > 0)
    <tfoot>
        <tr>
            <td colspan="5" style="text-align: right;">TOTAUX</td>
            <td class="text-right">{{ number_format($montantDu, 0, ',', ' ') }}</td>
            <td class="text-right" style="color: #059669;">{{ number_format($montantPaye, 0, ',', ' ') }}</td>
            <td class="text-right" style="color: {{ $soldeTotal > 0 ? '#dc2626' : '#059669' }};">{{ number_format($soldeTotal, 0, ',', ' ') }}</td>
            <td class="text-center">{{ $taux }}%</td>
        </tr>
    </tfoot>
    @endif
</table>

{{-- ── FOOTER — Only on last chunk ── --}}
@if($isLastChunk && $etudiants->count() > 0)
<table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-top: 12px;">
    <tr>
        <td width="48%" style="vertical-align: top; padding-right: 6px;">
            <table width="100%" border="0" cellspacing="0" cellpadding="0" style="background: #f8f9fa; border: 1px solid #e5e7eb;">
                <tr>
                    <td colspan="2" style="padding: 8px 9px 4px; font-size: 10px; font-weight: 600; color: #374151; text-transform: uppercase; letter-spacing: 0.2px;">Résumé financier</td>
                </tr>
                <tr>
                    <td width="50%" style="text-align: center; padding: 6px;">
                        <span style="font-size: 13px; font-weight: bold; color: {{ $primaryColor }};">{{ number_format($montantDu, 0, ',', ' ') }}</span><br>
                        <span style="font-size: 8px; color: #6b7280;">Montant total dû (FCFA)</span>
                    </td>
                    <td width="50%" style="text-align: center; padding: 6px;">
                        <span style="font-size: 13px; font-weight: bold; color: #059669;">{{ number_format($montantPaye, 0, ',', ' ') }}</span><br>
                        <span style="font-size: 8px; color: #6b7280;">Montant total payé (FCFA)</span>
                    </td>
                </tr>
                <tr>
                    <td style="text-align: center; padding: 6px;">
                        <span style="font-size: 13px; font-weight: bold; color: {{ $soldeTotal > 0 ? '#dc2626' : '#059669' }};">{{ number_format($soldeTotal, 0, ',', ' ') }}</span><br>
                        <span style="font-size: 8px; color: #6b7280;">Solde restant (FCFA)</span>
                    </td>
                    <td style="text-align: center; padding: 6px;">
                        <span style="font-size: 13px; font-weight: bold; color: {{ $taux >= 80 ? '#059669' : ($taux >= 50 ? '#d97706' : '#dc2626') }};">{{ $taux }}%</span><br>
                        <span style="font-size: 8px; color: #6b7280;">Taux de recouvrement</span>
                    </td>
                </tr>
            </table>
        </td>
        <td width="4%"></td>
        <td width="48%" style="vertical-align: top; padding-left: 6px;">
            <table width="100%" border="0" cellspacing="0" cellpadding="0" style="background: #f8f9fa; border: 1px solid #e5e7eb;">
                <tr>
                    <td style="padding: 8px 9px 4px; font-size: 10px; font-weight: 600; color: #374151; text-transform: uppercase; letter-spacing: 0.2px;">Informations document</td>
                </tr>
                <tr><td style="padding: 4px 9px;"><span class="info-label">Généré le :</span> <span class="info-value">{{ now()->format('d/m/Y à H:i') }}</span></td></tr>
                @if(($pdfSettings['show_generator_name'] ?? true) && auth()->check())
                    <tr><td style="padding: 4px 9px;"><span class="info-label">Par :</span> <span class="info-value">{{ auth()->user()->name }}</span></td></tr>
                @endif
                <tr><td style="padding: 4px 9px;"><span class="info-label">Établissement :</span> <span class="info-value">{{ $schoolName }}</span></td></tr>
                <tr><td style="padding: 4px 9px 8px;"><span class="info-label">Catégorie :</span> <span class="info-value">{{ $category->name ?? '—' }}</span></td></tr>
            </table>
        </td>
    </tr>
</table>

<table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-top: 10px; border-top: 1px solid #e5e7eb; padding-top: 6px;">
    <tr>
        <td style="text-align: center; font-size: 8px; color: #6b7280;">
            <strong>Document généré automatiquement le {{ now()->format('d/m/Y à H:i') }}</strong> — {{ $schoolName }} — Système de Gestion KLASSCI
        </td>
    </tr>
</table>
@endif

</body>
</html>

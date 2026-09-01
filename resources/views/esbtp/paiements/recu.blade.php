<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Reçu de Paiement - {{ $paiement->numero_recu }}</title>
    @php
        $pdfCfg  = \App\Helpers\SettingsHelper::getPdfSettings();
        $primary = $pdfCfg['primary_color'] ?? '#0453cb';
        $hdrBg   = $pdfCfg['header_bg_color'] ?? $primary;
        $hdrText = $pdfCfg['header_text_on_bg'] ?? $pdfCfg['header_text_color'] ?? '#ffffff';
        $barText = $pdfCfg['header_text_on_primary'] ?? $hdrText;
        $categoryName = null;
        if ($paiement->fraisCategory) {
            $categoryName = $paiement->fraisCategory->name;
        } elseif ($paiement->categorie) {
            $categoryName = $paiement->categorie->nom ?? null;
        }
        $copies = [
            'EXEMPLAIRE ÉLÈVE / PARENT — à conserver',
            'EXEMPLAIRE CAISSE — à archiver',
        ];
    @endphp
    <style>
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 0;
            color: #1e293b;
            line-height: 1.28;
            background: white;
        }

        @page {
            margin: 4mm 6mm;
            size: A4 portrait;
        }

        .document-watermark {
            position: fixed;
            top: 30%;
            left: 15%;
            width: 70%;
            opacity: 0.08;
            z-index: 0;
            text-align: center;
        }
        .document-watermark img { max-width: 100%; }

        .sheet { width: 100%; border-collapse: collapse; page-break-inside: avoid; }
        .copy-cell {
            vertical-align: top;
            padding: 0 0 2mm 0;
            page-break-inside: avoid;
        }
        .cut-cell {
            height: 6mm;
            text-align: center;
            font-size: 10px;
            color: #64748b;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            border-top: 1px dashed #94a3b8;
            border-bottom: 1px dashed #94a3b8;
            vertical-align: middle;
        }

        .header-section {
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 2.5mm;
        }
        .header-section img {
            max-height: 52px !important;
            max-width: 110px !important;
        }

        .copy-bar { margin-bottom: 2mm; }
        .copy-tag {
            font-size: 11px;
            font-weight: 700;
            color: {{ $primary }};
            letter-spacing: 0.04em;
            text-transform: uppercase;
            vertical-align: middle;
        }
        .copy-num {
            text-align: right;
            font-size: 11px;
            font-weight: bold;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            vertical-align: middle;
        }
        .copy-num-val {
            font-size: 16px;
            font-weight: 900;
            color: {{ $primary }};
            background-color: #f8fafc;
            padding: 2px 8px;
            border: 1.5px solid {{ $primary }};
            border-radius: 3px;
            letter-spacing: 0.4px;
        }

        .meta { border-collapse: collapse; margin-bottom: 2mm; }
        .meta td {
            width: 25%;
            border: 0.4pt solid #cbd5e1;
            padding: 1.2mm 2mm;
            vertical-align: top;
        }
        .lbl {
            font-size: 10px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 0;
        }
        .val { font-size: 12px; font-weight: 700; color: #1e293b; }
        .mono { font-family: 'Courier New', monospace; }

        .badge {
            display: inline-block;
            padding: 1px 6px;
            border-radius: 8px;
            font-size: 10.5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .badge-success { background-color: #dcfce7; color: #166534; border: 1px solid #86efac; }
        .badge-warning { background-color: #fef3c7; color: #92400e; border: 1px solid #fcd34d; }
        .badge-danger { background-color: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }

        .amount-section { border-collapse: collapse; margin-bottom: 2.5mm; }
        .amount-label {
            width: 22%;
            background-color: #059669;
            color: white;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            padding: 1.6mm 2.2mm;
            vertical-align: middle;
        }
        .amount-value {
            width: 28%;
            background-color: #ecfdf5;
            font-size: 17px;
            font-weight: 900;
            color: #059669;
            padding: 1.6mm 2.2mm;
            vertical-align: middle;
        }
        .amount-value span { font-size: 11px; font-weight: 600; opacity: 0.75; }
        .amount-words {
            background-color: #ecfdf5;
            font-size: 11px;
            font-style: italic;
            color: #64748b;
            padding: 1.6mm 2.2mm;
            vertical-align: middle;
        }

        .encaissed { margin-bottom: 2mm; border-collapse: collapse; }
        .encaissed-lbl {
            width: 22%;
            font-size: 10.5px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            background-color: #f8fafc;
            border-left: 3px solid {{ $primary }};
            padding: 1.8mm 2.5mm;
        }
        .encaissed-val {
            font-size: 13px;
            font-weight: 700;
            color: {{ $primary }};
            background-color: #f8fafc;
            padding: 1.8mm 2.5mm;
        }

        .fees { width: 100%; border-collapse: collapse; margin: 2mm 0; }
        .fees td {
            width: 33.33%;
            border: 0.4pt solid #cbd5e1;
            padding: 1.1mm 1.8mm;
            font-size: 11px;
            vertical-align: middle;
        }
        .fees .chk { font-size: 12px; font-weight: 700; color: {{ $primary }}; padding-right: 1mm; }
        .fees .chk-off { color: #94a3b8; }
        .fees .fee-now { font-weight: 700; }
        .fees .fee-note { color: #64748b; font-size: 10px; }
        .vers { width: 100%; border-collapse: collapse; margin: 2mm 0; }
        .vers td {
            width: 50%;
            border: 0.4pt solid #cbd5e1;
            padding: 1.1mm 1.8mm;
            font-size: 11px;
            vertical-align: top;
        }
        .vers-lbl {
            font-size: 10px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 0;
            font-weight: 700;
        }
        .vers-ligne { color: #1e293b; font-size: 11px; line-height: 1.35; }
        .reste { width: 100%; border-collapse: collapse; margin-bottom: 2mm; }
        .reste-lbl {
            width: 18%;
            background-color: {{ $primary }};
            color: {{ $barText }};
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            padding: 2.2mm 2.5mm;
        }
        .reste-val {
            width: 32%;
            background-color: #eff6ff;
            font-size: 16px;
            font-weight: 900;
            color: {{ $primary }};
            padding: 2.2mm 2.5mm;
        }

        .signs { margin-top: 3mm; }
        .signs td { width: 50%; text-align: center; vertical-align: top; padding: 0 6mm; }
        .sign-title {
            font-size: 11px;
            font-weight: 700;
            color: {{ $primary }};
            text-transform: uppercase;
            letter-spacing: 0.4px;
            margin-bottom: 7mm;
        }
        .sign-line {
            border-top: 1.5px solid {{ $primary }};
            padding-top: 1.2mm;
            font-size: 12px;
            font-weight: 600;
        }

        .footer-section {
            margin-top: 3mm;
            padding-top: 1.5mm;
            border-top: 1.5px solid {{ $primary }};
        }
        .footer-warning {
            text-align: center;
            font-size: 11px;
            font-weight: bold;
            color: #dc2626;
            margin-bottom: 0.8mm;
        }
        .footer-contact {
            text-align: center;
            font-size: 11px;
            color: #64748b;
        }
    </style>
</head>
<body>
    @php
        $logoPath   = \App\Helpers\SettingsHelper::get('school_logo');
        $logoBase64Wm = null;
        if ($logoPath) {
            foreach ([
                storage_path('app/public/' . $logoPath),
                public_path($logoPath),
            ] as $wmPath) {
                if (file_exists($wmPath)) {
                    $wmExt       = pathinfo($wmPath, PATHINFO_EXTENSION);
                    $logoBase64Wm = 'data:image/' . $wmExt . ';base64,' . base64_encode(file_get_contents($wmPath));
                    break;
                }
            }
        }
    @endphp

    @if($logoBase64Wm)
        <div class="document-watermark">
            <img src="{{ $logoBase64Wm }}" alt="">
        </div>
    @endif

    <table class="sheet" width="100%" border="0" cellspacing="0" cellpadding="0">
        @foreach ($copies as $copyTag)
            <tr>
                <td class="copy-cell">
            @include('esbtp.paiements.partials.recu-exemplaire', [
                'copyTag' => $copyTag,
                'paiement' => $paiement,
                'settings' => $settings,
                'primary' => $primary,
                'hdrBg' => $hdrBg,
                'hdrText' => $hdrText,
                'barText' => $barText,
                'categoryName' => $categoryName,
                'fraisLignes' => $fraisLignes ?? collect(),
                'resteAPayer' => $resteAPayer ?? 0,
                'totalVerse' => $totalVerse ?? 0,
                'versementsAvant' => $versementsAvant ?? collect(),
                'versementsApres' => $versementsApres ?? collect(),
                'affectationLabel' => $affectationLabel ?? '—',
            ])
                </td>
            </tr>
            @if (! $loop->last)
            <tr>
                <td class="cut-cell">✂ Couper ici — exemplaire élève (haut) · exemplaire caisse (bas)</td>
            </tr>
            @endif
        @endforeach
    </table>
</body>
</html>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Avoir {{ $paiement->numero_avoir }}</title>
    @php
        $pdfCfg  = \App\Helpers\SettingsHelper::getPdfSettings();
        $primary = $pdfCfg['primary_color'] ?? '#0453cb';
        $hdrBg   = $pdfCfg['header_bg_color'] ?? $primary;
        $hdrText = $pdfCfg['header_text_on_bg'] ?? $pdfCfg['header_text_color'] ?? '#ffffff';
        $kindLabel = $paiement->avoir_kind === 'refund' ? 'Remboursement caisse' : 'Crédit sur compte';
        $copies = [
            'EXEMPLAIRE ÉLÈVE / PARENT — à conserver',
            'EXEMPLAIRE CAISSE — à archiver',
        ];
    @endphp
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; margin: 0; color: #1e293b; }
        @page { margin: 6mm 8mm; size: A4 portrait; }
        .sheet { width: 100%; border-collapse: collapse; }
        .copy-cell { height: 128mm; vertical-align: top; }
        .cut-cell { height: 7mm; text-align: center; font-size: 8px; color: #64748b; letter-spacing: 0.12em; text-transform: uppercase; border-top: 1px dashed #94a3b8; border-bottom: 1px dashed #94a3b8; }
        .header-section { border-radius: 6px; overflow: hidden; margin-bottom: 2mm; }
        .header-section img { max-height: 48px !important; max-width: 90px !important; }
        .copy-tag { font-size: 9px; font-weight: 700; color: {{ $primary }}; text-transform: uppercase; }
        .copy-num-val { font-size: 13px; font-weight: 900; color: {{ $primary }}; border: 1.5px solid {{ $primary }}; padding: 2px 10px; }
        .meta { border-collapse: collapse; margin: 2mm 0; width: 100%; }
        .meta td { width: 25%; border: 0.4pt solid #cbd5e1; padding: 2mm; vertical-align: top; }
        .lbl { font-size: 7px; color: #64748b; text-transform: uppercase; }
        .val { font-size: 10px; font-weight: 700; }
        .amount-label { background: {{ $primary }}; color: #fff; font-size: 8px; font-weight: 700; text-transform: uppercase; padding: 3mm; }
        .amount-value { background: #eff6ff; font-size: 18px; font-weight: 900; color: {{ $primary }}; padding: 2.5mm 3mm; }
        .signs { margin-top: 4mm; width: 100%; }
        .signs td { width: 50%; text-align: center; padding: 0 8mm; }
        .sign-title { font-size: 9px; font-weight: 700; color: {{ $primary }}; text-transform: uppercase; margin-bottom: 14mm; }
        .sign-line { border-top: 1.5px solid {{ $primary }}; padding-top: 2mm; font-size: 10px; font-weight: 600; }
        .footer-warning { text-align: center; font-size: 8px; font-weight: bold; color: #dc2626; margin-top: 3mm; }
    </style>
</head>
<body>
<table class="sheet" width="100%" border="0" cellspacing="0" cellpadding="0">
@foreach ($copies as $copyTag)
    <tr>
        <td class="copy-cell">
            <div class="header-section">
                <table width="100%" border="0" cellspacing="0" cellpadding="0">
                    <tr>
                        <td width="16%" style="background-color: {{ $hdrBg }}; padding: 12px 10px; text-align: center; vertical-align: middle; border-right: 2px solid rgba(255,255,255,0.2);">
                            @if(isset($settings['show_logo']) && $settings['show_logo'] && isset($settings['logo_base64']))
                                <img src="{{ $settings['logo_base64'] }}" style="max-height: 70px; max-width: 120px;" alt="Logo">
                            @else
                                <div style="font-size: 36px; font-weight: 900; color: {{ $hdrText }}; opacity: 0.4;">K</div>
                            @endif
                        </td>
                        <td width="84%" style="background-color: {{ $hdrBg }}; padding: 10px 16px; vertical-align: middle;">
                            <div style="font-size: 19px; font-weight: 700; color: {{ $hdrText }}; margin-bottom: 2px;">{{ $settings['school_name'] ?? 'KLASSCI' }}</div>
                            <div style="font-size: 12px; color: {{ $hdrText }}; opacity: 0.8; margin-bottom: 6px;">
                                @if($settings['school_address'] ?? false){{ $settings['school_address'] }}@endif
                                @if($settings['school_phone'] ?? false) &nbsp;|&nbsp; Tél: {{ $settings['school_phone'] }}@endif
                            </div>
                            <div style="border-top: 1px solid rgba(255,255,255,0.3); padding-top: 6px;">
                                <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                    <tr>
                                        <td style="font-size: 18px; font-weight: 700; color: {{ $hdrText }};">AVOIR</td>
                                        <td style="font-size: 13px; color: {{ $hdrText }}; opacity: 0.75; text-align: right;">{{ $paiement->inscription->anneeUniversitaire->name ?? '' }}</td>
                                    </tr>
                                </table>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                <tr>
                    <td class="copy-tag">{{ $copyTag }}</td>
                    <td style="text-align:right"><span class="copy-num-val">{{ $paiement->numero_avoir }}</span></td>
                </tr>
            </table>
            <table class="meta">
                <tr>
                    <td><div class="lbl">Étudiant</div><div class="val">{{ $paiement->etudiant->user->name ?? $paiement->etudiant->nom_complet ?? 'N/A' }}</div></td>
                    <td><div class="lbl">Matricule</div><div class="val">{{ $paiement->etudiant->matricule ?? '—' }}</div></td>
                    <td><div class="lbl">Type</div><div class="val">{{ $kindLabel }}</div></td>
                    <td><div class="lbl">Reçu d'origine</div><div class="val">{{ $paiement->parentPaiement->numero_recu ?? '—' }}</div></td>
                </tr>
                <tr>
                    <td><div class="lbl">Catégorie</div><div class="val">{{ $paiement->fraisCategory->name ?? $paiement->motif }}</div></td>
                    <td><div class="lbl">Date</div><div class="val">{{ $paiement->date_paiement?->format('d/m/Y') }}</div></td>
                    <td colspan="2"><div class="lbl">Motif</div><div class="val">{{ $paiement->motif }}</div></td>
                </tr>
            </table>
            <table width="100%" border="0" cellspacing="0" cellpadding="0" style="border-collapse:collapse;margin-bottom:2mm">
                <tr>
                    <td class="amount-label">Montant de l'avoir</td>
                    <td class="amount-value">{{ number_format($paiement->montant, 0, ',', ' ') }} FCFA</td>
                </tr>
            </table>
            <table class="signs">
                <tr>
                    <td>
                        <div class="sign-title">Date d'émission</div>
                        <div class="sign-line">{{ date('d/m/Y') }}</div>
                    </td>
                    <td>
                        <div class="sign-title">Signature et Cachet</div>
                        <div class="sign-line">{{ $paiement->validatedBy->name ?? $paiement->creator->name ?? 'Le Comptable' }}</div>
                    </td>
                </tr>
            </table>
            <div class="footer-warning">Document officiel. Toute falsification constitue un délit.</div>
        </td>
    </tr>
    @if (! $loop->last)
    <tr><td class="cut-cell">✂ Couper ici — exemplaire élève (haut) · exemplaire caisse (bas)</td></tr>
    @endif
@endforeach
</table>
</body>
</html>

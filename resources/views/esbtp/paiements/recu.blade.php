<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Reçu de Paiement - {{ $paiement->numero_recu }}</title>
    @php
        $print = $receiptPrint ?? \App\Support\ReceiptPrintSettings::read();
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
        @page { size: A4 portrait; margin: {{ $print['margin_top'] }}mm {{ $print['margin_right'] }}mm {{ $print['margin_bottom'] }}mm {{ $print['margin_left'] }}mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: {{ $print['font_student'] }}pt; margin: 0; padding: 0; color: #1e293b; line-height: {{ $print['line_height'] }}; }
        table { width: 100%; border-collapse: collapse; }
        .copy-wrap { page-break-inside: avoid; position: relative; }
        .document-watermark { position: absolute; top: 20%; left: {{ (100 - $print['watermark_size']) / 2 }}%; width: {{ $print['watermark_size'] }}%; opacity: {{ $print['watermark_opacity'] / 100 }}; z-index: -1; text-align: center; }
        .document-watermark img { width: 100%; }
        .cut-cell { height: {{ $print['cut_height'] }}mm; line-height: {{ $print['cut_height'] }}mm; text-align: center; font-size: {{ $print['font_cut'] }}pt; color: #475569; border-top: 0.5pt dashed #94a3b8; border-bottom: 0.5pt dashed #94a3b8; }
        .header-section, .copy-bar, .meta, .amount-section, .fees, .reste, .vers, .encaissed, .signs { margin-bottom: {{ $print['gap'] }}mm; }
        .header-section img { max-height: {{ $print['logo_height'] }}mm; max-width: {{ $print['logo_width'] }}mm; }
        .header-section td, .meta td, .fees td, .vers td, .amount-section td, .reste td, .encaissed td { padding: {{ $print['padding'] }}mm 1.2mm; vertical-align: middle; }
        .meta td, .fees td, .vers td { border: 0.4pt solid #cbd5e1; vertical-align: top; }
        .copy-tag { font-size: {{ $print['font_copy'] }}pt; font-weight: bold; color: {{ $primary }}; text-transform: uppercase; }
        .copy-num { text-align: right; font-size: {{ $print['font_label'] }}pt; color: #475569; }
        .copy-num-val { font-size: {{ $print['font_number'] }}pt; font-weight: bold; color: {{ $primary }}; }
        .lbl { font-size: {{ $print['font_label'] }}pt; color: #475569; text-transform: uppercase; }
        .val { font-weight: bold; overflow-wrap: break-word; }
        .mono { font-family: DejaVu Sans, sans-serif; }
        .badge { font-size: {{ $print['font_status'] }}pt; font-weight: bold; text-transform: uppercase; }
        .badge-success { color: #166534; } .badge-warning { color: #92400e; } .badge-danger { color: #991b1b; }
        .amount-label { width: 23%; background: #047857; color: white; font-size: {{ $print['font_amount_label'] }}pt; font-weight: bold; text-transform: uppercase; }
        .amount-value { width: 29%; background: #ecfdf5; color: #065f46; font-size: {{ $print['font_amount'] }}pt; font-weight: bold; }
        .amount-value span { font-size: {{ $print['font_currency'] }}pt; }
        .amount-words { background: #ecfdf5; color: #334155; font-size: {{ $print['font_words'] }}pt; }
        .fees td { font-size: {{ $print['font_fee'] }}pt; width: {{ 100 / $print['fee_columns'] }}%; }
        .chk { font-size: {{ $print['font_check'] }}pt; font-weight: bold; color: {{ $primary }}; }
        .chk-off { color: #475569; } .fee-now { font-weight: bold; }
        .fee-note { font-size: {{ $print['font_fee_note'] }}pt; color: #475569; }
        .vers td { width: 50%; }
        .vers-lbl { font-size: {{ $print['font_history_label'] }}pt; color: #475569; font-weight: bold; text-transform: uppercase; }
        .vers-ligne { font-size: {{ $print['font_history'] }}pt; }
        .reste-lbl { width: 18%; background: {{ $primary }}; color: {{ $barText }}; font-size: {{ $print['font_total_label'] }}pt; font-weight: bold; text-transform: uppercase; }
        .reste-val { width: 32%; background: #f1f5f9; color: {{ $primary }}; font-size: {{ $print['font_total'] }}pt; font-weight: bold; }
        .reste-val.rf-balance { font-size: {{ $print['font_balance'] }}pt; }
        .encaissed-lbl { width: 22%; font-size: {{ $print['font_cashier_label'] }}pt; color: #475569; font-weight: bold; }
        .encaissed-val { font-size: {{ $print['font_cashier'] }}pt; color: {{ $primary }}; font-weight: bold; }
        .signs td { width: 50%; text-align: center; vertical-align: top; padding: 0 4mm; }
        .sign-title { font-size: {{ $print['font_signature_title'] }}pt; font-weight: bold; color: {{ $primary }}; margin-bottom: {{ $print['signature_space'] }}mm; }
        .sign-line { border-top: 0.7pt solid {{ $primary }}; font-size: {{ $print['font_signature'] }}pt; }
        .footer-section { border-top: 0.5pt solid {{ $primary }}; text-align: center; }
        .footer-warning { font-size: {{ $print['font_notice'] }}pt; font-weight: bold; color: #334155; }
        .footer-contact { font-size: {{ $print['font_footer'] }}pt; color: #475569; }
        .rf-school { font-size: {{ $print['font_school'] }}pt; }
        .rf-contact { font-size: {{ $print['font_contact'] }}pt; }
        .rf-title { font-size: {{ $print['font_title'] }}pt; }
        .rf-year { font-size: {{ $print['font_year'] }}pt; }
        .rf-copy { font-size: {{ $print['font_copy'] }}pt; }
        .rf-number { font-size: {{ $print['font_number'] }}pt; }
        .rf-label { font-size: {{ $print['font_label'] }}pt; }
        .rf-student { font-size: {{ $print['font_student'] }}pt; }
        .rf-matricule { font-size: {{ $print['font_matricule'] }}pt; }
        .rf-filiere { font-size: {{ $print['font_filiere'] }}pt; }
        .rf-niveau { font-size: {{ $print['font_niveau'] }}pt; }
        .rf-date { font-size: {{ $print['font_date'] }}pt; }
        .rf-timestamp { font-size: {{ $print['font_timestamp'] }}pt; }
        .rf-affectation { font-size: {{ $print['font_affectation'] }}pt; }
        .rf-mode { font-size: {{ $print['font_mode'] }}pt; }
        .rf-status { font-size: {{ $print['font_status'] }}pt; }
        .rf-category { font-size: {{ $print['font_category'] }}pt; }
        .rf-reglement { font-size: {{ $print['font_reglement'] }}pt; }
        .rf-tranche { font-size: {{ $print['font_tranche'] }}pt; }
        .rf-reference { font-size: {{ $print['font_reference'] }}pt; }
        .rf-amount_label { font-size: {{ $print['font_amount_label'] }}pt; }
        .rf-amount { font-size: {{ $print['font_amount'] }}pt; }
        .rf-currency { font-size: {{ $print['font_currency'] }}pt; }
        .rf-words { font-size: {{ $print['font_words'] }}pt; }
        .rf-fee { font-size: {{ $print['font_fee'] }}pt; }
        .rf-check { font-size: {{ $print['font_check'] }}pt; }
        .rf-fee_note { font-size: {{ $print['font_fee_note'] }}pt; }
        .rf-total_label { font-size: {{ $print['font_total_label'] }}pt; }
        .rf-total { font-size: {{ $print['font_total'] }}pt; }
        .rf-balance { font-size: {{ $print['font_balance'] }}pt; }
        .rf-history_label { font-size: {{ $print['font_history_label'] }}pt; }
        .rf-history { font-size: {{ $print['font_history'] }}pt; }
        .rf-cashier_label { font-size: {{ $print['font_cashier_label'] }}pt; }
        .rf-cashier { font-size: {{ $print['font_cashier'] }}pt; }
        .rf-signature_title { font-size: {{ $print['font_signature_title'] }}pt; }
        .rf-signature { font-size: {{ $print['font_signature'] }}pt; }
        .rf-notice { font-size: {{ $print['font_notice'] }}pt; }
        .rf-footer { font-size: {{ $print['font_footer'] }}pt; }
        .rf-cut { font-size: {{ $print['font_cut'] }}pt; }
    </style>
</head>
<body>
    @foreach ($copies as $copyTag)
        <div class="copy-wrap">
            @if($print['watermark_opacity'] > 0 && !empty($settings['logo_base64']))
                <div class="document-watermark"><img src="{{ $settings['logo_base64'] }}" alt=""></div>
            @endif
            @include('esbtp.paiements.partials.recu-exemplaire', [
                'copyTag' => ($receiptDemo ?? false) ? $copyTag.' — DÉMONSTRATION NON VALABLE' : $copyTag,
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
        </div>
        @if (! $loop->last)
            <div class="cut-cell">✂ Couper ici — exemplaire élève (haut) · exemplaire caisse (bas)</div>
        @endif
    @endforeach
</body>
</html>

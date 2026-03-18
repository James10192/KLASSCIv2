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
        $hdrText = $pdfCfg['header_text_color'] ?? '#ffffff';
    @endphp
    <style>
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 10px;
            margin: 0;
            padding: 8px;
            color: #1e293b;
            line-height: 1.4;
            background: white;
        }

        @page {
            margin: 0.8cm;
            size: A4 portrait;
        }

        .container {
            max-width: 100%;
            background: white;
            padding: 10px;
        }

        /* ── Header Banner ── */
        .header-section {
            border-radius: 6px;
            overflow: hidden;
            margin-bottom: 14px;
        }

        /* ── Receipt Number ── */
        .receipt-number-section {
            text-align: center;
            margin-bottom: 16px;
        }

        /* ── Card Style ── */
        .card-section {
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            overflow: hidden;
            margin-bottom: 16px;
        }

        /* ── Key-Value Table ── */
        .kv-table {
            width: 100%;
            border-collapse: collapse;
        }

        .kv-table td {
            padding: 8px 14px;
            font-size: 10px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }

        .kv-table tr:last-child td {
            border-bottom: none;
        }

        .kv-label {
            width: 38%;
            font-weight: bold;
            color: #64748b;
            background-color: #f8fafc;
            border-right: 1px solid #f1f5f9;
        }

        .kv-value {
            font-weight: 500;
            color: #1e293b;
        }

        /* ── Badge ── */
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .badge-success {
            background-color: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
        }

        .badge-warning {
            background-color: #fef3c7;
            color: #92400e;
            border: 1px solid #fcd34d;
        }

        .badge-danger {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }

        .badge-info {
            background-color: #dbeafe;
            color: #1e40af;
            border: 1px solid #93c5fd;
        }

        /* ── Amount Section ── */
        .amount-section {
            border: 2px solid #059669;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 16px;
        }

        /* ── Footer ── */
        .footer-section {
            margin-top: 14px;
            padding-top: 10px;
            border-top: 2px solid {{ $primary }};
        }

        .footer-warning {
            text-align: center;
            font-size: 9px;
            font-weight: bold;
            color: #dc2626;
            margin-bottom: 5px;
        }

        .footer-contact {
            text-align: center;
            font-size: 8.5px;
            color: #64748b;
            line-height: 1.5;
        }
    </style>
</head>
<body>
    <div class="container">

        <!-- ═══ HEADER BANNER ═══ -->
        <div class="header-section">
            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                <tr>
                    <!-- Logo Column -->
                    <td width="16%" style="background-color: {{ $hdrBg }}; padding: 14px 10px; text-align: center; vertical-align: middle; border-right: 2px solid rgba(255,255,255,0.2);">
                        @if(isset($settings['show_logo']) && $settings['show_logo'] && isset($settings['logo_base64']))
                            <img src="{{ $settings['logo_base64'] }}"
                                 style="max-height: 50px; max-width: 90px;"
                                 alt="Logo">
                        @else
                            <div style="font-size: 28px; font-weight: 900; color: {{ $hdrText }}; opacity: 0.4;">K</div>
                        @endif
                    </td>
                    <!-- Info Column -->
                    <td width="84%" style="background-color: {{ $hdrBg }}; padding: 12px 16px; vertical-align: middle;">
                        <!-- School Name -->
                        <div style="font-size: 14px; font-weight: 700; color: {{ $hdrText }}; margin-bottom: 2px;">
                            {{ $settings['school_name'] ?? 'KLASSCI' }}
                        </div>
                        <!-- Contact -->
                        <div style="font-size: 8px; color: {{ $hdrText }}; opacity: 0.8; margin-bottom: 8px;">
                            @if($settings['school_address'] ?? false){{ $settings['school_address'] }}@endif
                            @if($settings['school_phone'] ?? false) &nbsp;|&nbsp; Tél: {{ $settings['school_phone'] }}@endif
                            @if($settings['school_email'] ?? false) &nbsp;|&nbsp; Email: {{ $settings['school_email'] }}@endif
                        </div>
                        <!-- Divider + Title -->
                        <div style="border-top: 1px solid rgba(255,255,255,0.3); padding-top: 7px;">
                            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td width="60%" style="font-size: 12px; font-weight: 700; color: {{ $hdrText }}; letter-spacing: 0.5px;">
                                        REÇU DE PAIEMENT
                                    </td>
                                    <td width="40%" style="font-size: 9px; color: {{ $hdrText }}; opacity: 0.75; text-align: right;">
                                        {{ $paiement->inscription->anneeUniversitaire->libelle ?? '' }}
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- ═══ RECEIPT NUMBER ═══ -->
        <div class="receipt-number-section">
            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                <tr>
                    <td style="text-align: center; padding: 10px 0;">
                        <table border="0" cellspacing="0" cellpadding="0" style="margin: 0 auto;">
                            <tr>
                                <td style="font-size: 10px; font-weight: bold; color: #64748b; text-transform: uppercase; letter-spacing: 1px; padding-right: 12px; vertical-align: middle;">
                                    Reçu N°
                                </td>
                                <td style="font-size: 15px; font-weight: 900; color: {{ $primary }}; background-color: #f8fafc; padding: 6px 20px; border: 2px solid {{ $primary }}; border-radius: 6px; letter-spacing: 1px;">
                                    {{ $paiement->numero_recu }}
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </div>

        <!-- ═══ STUDENT INFO CARD ═══ -->
        <div class="card-section">
            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                <tr>
                    <td style="background-color: {{ $primary }}; color: {{ $hdrText }}; padding: 8px 14px; font-size: 11px; font-weight: 700; letter-spacing: 0.3px;">
                        INFORMATIONS DE L'ÉTUDIANT
                    </td>
                </tr>
                <tr>
                    <td style="padding: 0;">
                        <table class="kv-table">
                            <tr>
                                <td class="kv-label">Matricule</td>
                                <td class="kv-value" style="font-family: 'Courier New', monospace; font-weight: 700;">{{ $paiement->etudiant->matricule }}</td>
                            </tr>
                            <tr>
                                <td class="kv-label">Nom et Prénoms</td>
                                <td class="kv-value" style="font-weight: 700;">{{ $paiement->etudiant->user->name ?? $paiement->etudiant->nom_complet ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td class="kv-label">Filière</td>
                                <td class="kv-value">{{ $paiement->inscription->filiere->name ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td class="kv-label">Niveau</td>
                                <td class="kv-value">{{ $paiement->inscription->niveauEtude->name ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td class="kv-label">Année Universitaire</td>
                                <td class="kv-value">{{ $paiement->inscription->anneeUniversitaire->libelle ?? 'N/A' }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </div>

        <!-- ═══ PAYMENT DETAILS CARD ═══ -->
        <div class="card-section">
            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                <tr>
                    <td style="background-color: {{ $primary }}; color: {{ $hdrText }}; padding: 8px 14px; font-size: 11px; font-weight: 700; letter-spacing: 0.3px;">
                        DÉTAILS DU PAIEMENT
                    </td>
                </tr>
                <tr>
                    <td style="padding: 0;">
                        <table class="kv-table">
                            <tr>
                                <td class="kv-label">Date de paiement</td>
                                <td class="kv-value">{{ $paiement->date_paiement->format('d/m/Y') }}</td>
                            </tr>
                            <tr>
                                <td class="kv-label">Motif</td>
                                <td class="kv-value">{{ $paiement->motif }}</td>
                            </tr>
                            @php
                                $categoryInfo = null;
                                $categoryColors = [
                                    'academic' => 'success',
                                    'service' => 'warning',
                                    'administrative' => 'info'
                                ];

                                if ($paiement->fraisCategory) {
                                    $categoryInfo = [
                                        'name' => $paiement->fraisCategory->name,
                                        'type' => $paiement->fraisCategory->category_type ?? 'academic',
                                    ];
                                } elseif ($paiement->categorie) {
                                    $categoryInfo = [
                                        'name' => $paiement->categorie->nom ?? 'Catégorie ancienne',
                                        'type' => $paiement->categorie->nom && str_contains(strtolower($paiement->categorie->nom), 'cantine') ? 'service' : 'academic',
                                    ];
                                } elseif ($paiement->motif) {
                                    $motifLower = strtolower($paiement->motif);
                                    $type = 'academic';
                                    if (str_contains($motifLower, 'cantine') || str_contains($motifLower, 'transport')) {
                                        $type = 'service';
                                    } elseif (str_contains($motifLower, 'documentation') || str_contains($motifLower, 'examen')) {
                                        $type = 'administrative';
                                    }
                                    $categoryInfo = [
                                        'name' => $paiement->motif,
                                        'type' => $type,
                                    ];
                                }

                                $color = $categoryColors[$categoryInfo['type'] ?? 'academic'] ?? 'secondary';
                                $typeLabel = [
                                    'academic' => 'Académique',
                                    'service' => 'Service',
                                    'administrative' => 'Administratif'
                                ][$categoryInfo['type'] ?? 'academic'] ?? 'Académique';
                            @endphp
                            @if($categoryInfo)
                            <tr>
                                <td class="kv-label">Catégorie</td>
                                <td class="kv-value">
                                    {{ $categoryInfo['name'] }}
                                    <span class="badge badge-{{ $color }}" style="margin-left: 6px;">{{ $typeLabel }}</span>
                                </td>
                            </tr>
                            @endif
                            @if($paiement->tranche)
                            <tr>
                                <td class="kv-label">Tranche</td>
                                <td class="kv-value">{{ $paiement->tranche }}</td>
                            </tr>
                            @endif
                            <tr>
                                <td class="kv-label">Mode de paiement</td>
                                <td class="kv-value">{{ $paiement->mode_paiement }}</td>
                            </tr>
                            @if($paiement->reference_paiement)
                            <tr>
                                <td class="kv-label">Référence</td>
                                <td class="kv-value" style="font-family: 'Courier New', monospace;">{{ $paiement->reference_paiement }}</td>
                            </tr>
                            @endif
                            <tr>
                                <td class="kv-label">Statut</td>
                                <td class="kv-value">
                                    <span class="badge badge-{{ $paiement->status === 'validé' ? 'success' : ($paiement->status === 'en_attente' ? 'warning' : 'danger') }}">
                                        {{ $paiement->status_formatte }}
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </div>

        <!-- ═══ AMOUNT SECTION ═══ -->
        <div class="amount-section">
            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                <tr>
                    <td style="background-color: #059669; color: white; padding: 7px 14px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; text-align: center;">
                        Montant du Paiement
                    </td>
                </tr>
                <tr>
                    <td style="padding: 18px 14px; text-align: center; background-color: #ecfdf5;">
                        <div style="font-size: 26px; font-weight: 900; color: #059669; line-height: 1; margin-bottom: 2px;">
                            {{ number_format($paiement->montant, 0, ',', ' ') }}
                        </div>
                        <div style="font-size: 13px; font-weight: 600; color: #059669; opacity: 0.7;">
                            FCFA
                        </div>
                        <div style="margin-top: 10px; padding-top: 10px; border-top: 1px dashed rgba(5,150,105,0.25); font-size: 10px; font-style: italic; color: #64748b;">
                            {{ ucfirst(\App\Services\NumberToWords::convert($paiement->montant)) }} Francs CFA
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- ═══ SIGNATURES ═══ -->
        <table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-bottom: 14px; margin-top: 20px;">
            <tr>
                <td width="45%" style="text-align: center; vertical-align: top; padding-right: 20px;">
                    <div style="font-size: 10px; font-weight: 700; color: {{ $primary }}; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 35px;">
                        Date d'émission
                    </div>
                    <div style="border-top: 2px solid {{ $primary }}; padding-top: 7px;">
                        <div style="font-size: 10px; font-weight: 600; color: #1e293b;">
                            {{ $paiement->date_validation ? $paiement->date_validation->format('d/m/Y') : date('d/m/Y') }}
                        </div>
                    </div>
                </td>
                <td width="10%"></td>
                <td width="45%" style="text-align: center; vertical-align: top; padding-left: 20px;">
                    <div style="font-size: 10px; font-weight: 700; color: {{ $primary }}; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 35px;">
                        Signature et Cachet
                    </div>
                    <div style="border-top: 2px solid {{ $primary }}; padding-top: 7px;">
                        <div style="font-size: 10px; font-weight: 600; color: #1e293b;">
                            {{ $paiement->validatedBy ? $paiement->validatedBy->name : 'Le Comptable' }}
                        </div>
                    </div>
                </td>
            </tr>
        </table>

        <!-- ═══ FOOTER ═══ -->
        <div class="footer-section">
            <div class="footer-warning">
                Ce reçu est un document officiel. Toute falsification constitue un délit passible de poursuites judiciaires.
            </div>
            <div class="footer-contact">
                {{ $settings['school_name'] ?? 'KLASSCI' }} — {{ $settings['school_address'] ?? '' }}<br>
                Email: {{ $settings['school_email'] ?? '' }} — Tél: {{ $settings['school_phone'] ?? '' }}
            </div>
        </div>

    </div>
</body>
</html>

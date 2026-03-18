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
            font-size: 14px;
            margin: 0;
            padding: 6px;
            color: #1e293b;
            line-height: 1.3;
            background: white;
        }

        @page {
            margin: 0.7cm;
            size: A4 portrait;
        }

        .container {
            max-width: 100%;
            background: white;
            padding: 8px;
            position: relative;
        }

        /* ── Watermark ── */
        .document-watermark {
            position: fixed;
            top: 30%;
            left: 15%;
            width: 70%;
            opacity: 0.10;
            z-index: 0;
            text-align: center;
        }
        .document-watermark img { max-width: 100%; }
        .document-content { position: relative; z-index: 1; }

        /* ── Header Banner ── */
        .header-section {
            border-radius: 6px;
            overflow: hidden;
            margin-bottom: 10px;
        }

        /* ── Receipt Number ── */
        .receipt-number-section {
            text-align: center;
            margin-bottom: 12px;
        }

        /* ── Card Style ── */
        .card-section {
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            overflow: hidden;
            margin-bottom: 12px;
        }

        /* ── Key-Value Table ── */
        .kv-table {
            width: 100%;
            border-collapse: collapse;
        }

        .kv-table td {
            padding: 8px 14px;
            font-size: 15px;
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
            font-size: 9px;
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
            margin-bottom: 12px;
        }

        /* ── Footer ── */
        .footer-section {
            margin-top: 10px;
            padding-top: 8px;
            border-top: 2px solid {{ $primary }};
        }

        .footer-warning {
            text-align: center;
            font-size: 13px;
            font-weight: bold;
            color: #dc2626;
            margin-bottom: 4px;
        }

        .footer-contact {
            text-align: center;
            font-size: 12px;
            color: #64748b;
            line-height: 1.4;
        }
    </style>
</head>
<body>
    <div class="container">

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

        <div class="document-content">

        <!-- ═══ HEADER BANNER ═══ -->
        <div class="header-section">
            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                <tr>
                    <!-- Logo Column -->
                    <td width="16%" style="background-color: {{ $hdrBg }}; padding: 12px 10px; text-align: center; vertical-align: middle; border-right: 2px solid rgba(255,255,255,0.2);">
                        @if(isset($settings['show_logo']) && $settings['show_logo'] && isset($settings['logo_base64']))
                            <img src="{{ $settings['logo_base64'] }}"
                                 style="max-height: 70px; max-width: 120px;"
                                 alt="Logo">
                        @else
                            <div style="font-size: 36px; font-weight: 900; color: {{ $hdrText }}; opacity: 0.4;">K</div>
                        @endif
                    </td>
                    <!-- Info Column -->
                    <td width="84%" style="background-color: {{ $hdrBg }}; padding: 10px 16px; vertical-align: middle;">
                        <!-- School Name -->
                        <div style="font-size: 19px; font-weight: 700; color: {{ $hdrText }}; margin-bottom: 2px;">
                            {{ $settings['school_name'] ?? 'KLASSCI' }}
                        </div>
                        <!-- Contact -->
                        <div style="font-size: 12px; color: {{ $hdrText }}; opacity: 0.8; margin-bottom: 6px;">
                            @if($settings['school_address'] ?? false){{ $settings['school_address'] }}@endif
                            @if($settings['school_phone'] ?? false) &nbsp;|&nbsp; Tél: {{ $settings['school_phone'] }}@endif
                            @if($settings['school_email'] ?? false) &nbsp;|&nbsp; Email: {{ $settings['school_email'] }}@endif
                        </div>
                        <!-- Divider + Title -->
                        <div style="border-top: 1px solid rgba(255,255,255,0.3); padding-top: 6px;">
                            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td width="60%" style="font-size: 18px; font-weight: 700; color: {{ $hdrText }}; letter-spacing: 0.5px;">
                                        REÇU DE PAIEMENT
                                    </td>
                                    <td width="40%" style="font-size: 13px; color: {{ $hdrText }}; opacity: 0.75; text-align: right;">
                                        {{ $paiement->inscription->anneeUniversitaire->name ?? '' }}
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
                                <td style="font-size: 14px; font-weight: bold; color: #64748b; text-transform: uppercase; letter-spacing: 1px; padding-right: 12px; vertical-align: middle;">
                                    Reçu N°
                                </td>
                                <td style="font-size: 22px; font-weight: 900; color: {{ $primary }}; background-color: #f8fafc; padding: 5px 20px; border: 2px solid {{ $primary }}; border-radius: 6px; letter-spacing: 1px;">
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
                    <td style="background-color: {{ $primary }}; color: {{ $hdrText }}; padding: 8px 14px; font-size: 15px; font-weight: 700; letter-spacing: 0.3px;">
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
                                <td class="kv-value">{{ $paiement->inscription->anneeUniversitaire->name ?? 'N/A' }}</td>
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
                    <td style="background-color: {{ $primary }}; color: {{ $hdrText }}; padding: 8px 14px; font-size: 15px; font-weight: 700; letter-spacing: 0.3px;">
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
                                $categoryName = null;
                                if ($paiement->fraisCategory) {
                                    $categoryName = $paiement->fraisCategory->name;
                                } elseif ($paiement->categorie) {
                                    $categoryName = $paiement->categorie->nom ?? null;
                                }
                            @endphp
                            @if($categoryName)
                            <tr>
                                <td class="kv-label">Catégorie de frais</td>
                                <td class="kv-value">{{ $categoryName }}</td>
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
                    <td style="background-color: #059669; color: white; padding: 6px 14px; font-size: 15px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; text-align: center;">
                        Montant du Paiement
                    </td>
                </tr>
                <tr>
                    <td style="padding: 12px 14px; text-align: center; background-color: #ecfdf5;">
                        <div style="font-size: 36px; font-weight: 900; color: #059669; line-height: 1; margin-bottom: 2px;">
                            {{ number_format($paiement->montant, 0, ',', ' ') }}
                        </div>
                        <div style="font-size: 18px; font-weight: 600; color: #059669; opacity: 0.7;">
                            FCFA
                        </div>
                        <div style="margin-top: 6px; padding-top: 6px; border-top: 1px dashed rgba(5,150,105,0.25); font-size: 14px; font-style: italic; color: #64748b;">
                            {{ ucfirst(\App\Services\NumberToWords::convert($paiement->montant)) }} Francs CFA
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- ═══ SIGNATURES ═══ -->
        <table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-bottom: 10px; margin-top: 16px;">
            <tr>
                <td width="45%" style="text-align: center; vertical-align: top; padding-right: 20px;">
                    <div style="font-size: 14px; font-weight: 700; color: {{ $primary }}; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 28px;">
                        Date d'émission
                    </div>
                    <div style="border-top: 2px solid {{ $primary }}; padding-top: 6px;">
                        <div style="font-size: 14px; font-weight: 600; color: #1e293b;">
                            {{ $paiement->date_validation ? $paiement->date_validation->format('d/m/Y') : date('d/m/Y') }}
                        </div>
                    </div>
                </td>
                <td width="10%"></td>
                <td width="45%" style="text-align: center; vertical-align: top; padding-left: 20px;">
                    <div style="font-size: 14px; font-weight: 700; color: {{ $primary }}; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 28px;">
                        Signature et Cachet
                    </div>
                    <div style="border-top: 2px solid {{ $primary }}; padding-top: 6px;">
                        <div style="font-size: 14px; font-weight: 600; color: #1e293b;">
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

        </div>{{-- /document-content --}}
    </div>
</body>
</html>

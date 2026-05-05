@props([
    'title',
    'subtitle' => null,
    'filters' => [],
    'orientation' => 'portrait',
    'overrides' => [],
    'signatureBlock' => null, // null|'director'|'secretary'|'both'
])
@php
    $school = \App\Helpers\SettingsHelper::getSchoolInfo();
    $pdf = array_merge(\App\Helpers\SettingsHelper::getPdfSettings(), $overrides);

    // Logo base64 inline pour DomPDF.
    $logoBase64 = '';
    $logoExt = 'png';
    $logoMime = 'image/png';
    $logoPath = $school['logo'] ?? '';
    $normalizedLogoPath = ltrim((string) $logoPath, '/');
    $normalizedLogoPath = str_replace('\\', '/', $normalizedLogoPath);
    $storageRelativeLogoPath = preg_replace('#^storage/#', '', $normalizedLogoPath);
    $logoBasename = basename($storageRelativeLogoPath);
    $shouldShowLogo = !empty($pdf['show_logo']) || \App\Helpers\SettingsHelper::get('bulletin_show_logo', '1') === '1';

    if ($shouldShowLogo && $logoPath) {
        foreach ([
            storage_path('app/public/' . $normalizedLogoPath),
            storage_path('app/public/' . $storageRelativeLogoPath),
            storage_path('app/public/logos/' . $logoBasename),
            public_path('storage/' . $normalizedLogoPath),
            public_path('storage/' . $storageRelativeLogoPath),
            public_path('storage/logos/' . $logoBasename),
            public_path($normalizedLogoPath),
            public_path('images/esbtp_logo.png'),
            public_path('images/LOGO-KLASSCI-PNG.png'),
        ] as $candidate) {
            if (file_exists($candidate)) {
                $logoBase64 = base64_encode(file_get_contents($candidate));
                $logoExt = pathinfo($candidate, PATHINFO_EXTENSION) ?: 'png';
                $logoMime = match (strtolower($logoExt)) {
                    'jpg', 'jpeg' => 'image/jpeg',
                    'svg' => 'image/svg+xml',
                    'webp' => 'image/webp',
                    default => 'image/png',
                };
                break;
            }
        }
    }

    // Signature images
    $signatureFiles = [];
    if (!empty($pdf['show_director_signature']) && $signatureBlock !== null) {
        foreach (['director' => 'signature_director', 'secretary' => 'signature_secretary'] as $sigKey => $settingKey) {
            if (in_array($signatureBlock, [$sigKey, 'both'], true) && !empty($pdf[$settingKey])) {
                $sigPath = storage_path('app/public/' . ltrim($pdf[$settingKey], '/'));
                if (file_exists($sigPath)) {
                    $signatureFiles[$sigKey] = [
                        'b64' => base64_encode(file_get_contents($sigPath)),
                        'ext' => pathinfo($sigPath, PATHINFO_EXTENSION) ?: 'png',
                    ];
                }
            }
        }
    }

    $logoMaxHeight = max(20, min(120, (int) ($pdf['logo_size'] ?? 60)));
    $signatureHeight = max(40, min(200, (int) ($pdf['signature_height'] ?? 80)));
    $watermarkOpacity = max(0, min(0.5, (float) ($pdf['watermark_opacity'] ?? 0.05)));
    $watermarkRotation = (int) ($pdf['watermark_rotation'] ?? -30);
    $footerText = trim((string) ($pdf['footer_custom_text'] ?? '')) !== ''
        ? $pdf['footer_custom_text']
        : ($pdf['footer_text'] ?? ($school['name'] ?? config('app.name')));
    $hdrBg = $pdf['header_bg_color'] ?? $pdf['primary_color'] ?? '#0453cb';
    $hdrText = $pdf['header_text_color'] ?? '#ffffff';
    $primary = $pdf['primary_color'] ?? '#0453cb';
    $secondary = $pdf['secondary_color'] ?? '#64748b';
    // Dans l'UI settings, "Couleur d'accent" est stockée dans pdf_primary_color.
    $accent = $primary;
    $textColor = $pdf['text_color'] ?? '#1f2937';
    $titleUpper = mb_strtoupper((string) $title, 'UTF-8');
    $directorName = $school['director_name'] ?? '';
    $directorTitle = $school['director_title'] ?? 'Directeur Général';
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    @include('pdf.partials.theme')
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        @page {
            margin: {{ ($pdf['margin_top'] ?? 20) }}mm
                    {{ ($pdf['margin_right'] ?? 15) }}mm
                    {{ ($pdf['margin_bottom'] ?? 20) }}mm
                    {{ ($pdf['margin_left'] ?? 15) }}mm;
        }
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: {{ ($pdf['font_size'] ?? 11) }}px;
            color: {{ $textColor }};
            margin: 0;
            line-height: 1.4;
        }

        /* ===== Header banner premium (pattern liste-complete-pdf) ===== */
        .pdf-banner {
            width: 100%;
            border-collapse: collapse;
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }
        .pdf-banner-logo-cell {
            width: 18%;
            background-color: {{ $hdrBg }};
            padding: 14px 10px;
            text-align: center;
            vertical-align: middle;
            border-right: 2px solid rgba(255,255,255,0.25);
        }
        .pdf-banner-logo-frame {
            display: inline-block;
            background: #ffffff;
            border-radius: 6px;
            padding: 6px;
            border: 1px solid rgba(255,255,255,0.35);
        }
        .pdf-banner-logo {
            max-height: {{ $logoMaxHeight }}px;
            max-width: 100px;
            display: block;
        }
        .pdf-banner-logo-fallback {
            font-size: 32px;
            font-weight: 900;
            color: {{ $hdrText }};
            opacity: 0.4;
            letter-spacing: -2px;
        }
        .pdf-banner-info-cell {
            width: 82%;
            background-color: {{ $hdrBg }};
            padding: 12px 16px;
            vertical-align: middle;
        }
        .pdf-school-name {
            font-size: 16px;
            font-weight: 700;
            color: {{ $hdrText }};
            margin: 0 0 3px;
        }
        .pdf-school-meta {
            font-size: 8.5px;
            color: {{ $hdrText }};
            opacity: 0.85;
            margin: 0 0 8px;
            line-height: 1.5;
        }
        .pdf-banner-divider {
            border-top: 1px solid rgba(255,255,255,0.35);
            padding-top: 7px;
        }
        .pdf-banner-title {
            font-size: 13px;
            font-weight: 700;
            color: {{ $hdrText }};
            letter-spacing: 0.5px;
            margin: 0 0 4px;
        }
        .pdf-banner-subtitle {
            font-size: 9px;
            color: {{ $hdrText }};
            opacity: 0.85;
            font-style: italic;
            margin: 0;
        }

        /* ===== Méta-bar sous le banner (Référence | Date | Auteur) ===== */
        .pdf-meta-bar {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }
        .pdf-meta-bar td {
            background-color: {{ $hdrBg }};
            padding: 6px 16px 10px;
            font-size: 8.5px;
            color: {{ $hdrText }};
            border-top: 1px solid rgba(255,255,255,0.18);
        }
        .pdf-meta-bar td.spacer {
            width: 18%;
            border-right: 2px solid rgba(255,255,255,0.25);
            padding: 6px 10px 10px;
        }
        .pdf-meta-label {
            color: {{ $hdrText }};
            opacity: 0.75;
        }
        .pdf-meta-value {
            color: {{ $hdrText }};
            font-weight: 700;
        }

        /* ===== Filtres recap ===== */
        .pdf-filters-recap {
            background: #f1f5f9;
            border-left: 3px solid {{ $accent }};
            padding: 8px 12px;
            margin-bottom: 14px;
            font-size: 9px;
            border-radius: 2px;
        }
        .pdf-filters-recap-title {
            font-weight: 700;
            color: {{ $accent }};
            margin-right: 6px;
        }
        .pdf-filters-recap-item {
            margin-right: 12px;
            color: {{ $textColor }};
        }
        .pdf-filters-recap-item strong {
            color: {{ $secondary }};
            font-weight: 600;
        }

        /* ===== Body ===== */
        .pdf-body { margin-top: 8px; }

        /* ===== KPI blocks reutilisables ===== */
        .pdf-kpi-table {
            width: 100%;
            border-collapse: collapse;
            margin: 12px 0 10px;
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }
        .pdf-kpi-cell {
            background-color: {{ $accent }} !important;
            color: {{ $hdrText }} !important;
            padding: 8px 10px;
            text-align: center;
            border-right: 1px solid rgba(255,255,255,.28);
        }
        .pdf-kpi-cell:last-child {
            border-right: 0;
        }
        .pdf-kpi-label {
            font-size: 7.5pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: .3px;
            opacity: .78;
            margin-bottom: 3px;
            color: {{ $hdrText }} !important;
            background: transparent !important;
        }
        .pdf-kpi-value {
            font-size: 14pt;
            font-weight: bold;
            line-height: 1.1;
            color: {{ $hdrText }} !important;
            background: transparent !important;
        }
        .pdf-kpi-sub {
            font-size: 7.5pt;
            opacity: .72;
            margin-top: 3px;
            color: {{ $hdrText }} !important;
            background: transparent !important;
        }
        .pdf-detail-table {
            width: 100%;
            border-collapse: collapse;
            margin: 8px 0 10px;
            font-size: 8.5pt;
        }
        .pdf-detail-table th {
            background-color: {{ $accent }} !important;
            color: {{ $hdrText }} !important;
            text-align: left;
            padding: 5px 7px;
            border: 1px solid {{ $accent }};
            text-transform: uppercase;
            font-size: 7.2pt;
        }
        .pdf-detail-table td {
            padding: 5px 7px;
            border: 1px solid #e2e8f0;
        }
        .pdf-detail-table .right {
            text-align: right;
            font-weight: bold;
        }

        /* ===== Signature block (zone réservée) ===== */
        .pdf-signature-block {
            margin-top: 28px;
            page-break-inside: avoid;
        }
        .pdf-signature-grid {
            width: 100%;
            border-collapse: collapse;
        }
        .pdf-signature-cell {
            vertical-align: top;
            padding: 0 8px;
        }
        .pdf-signature-card {
            text-align: center;
            padding: 0 14px;
            min-height: {{ $signatureHeight + 30 }}px;
        }
        .pdf-signature-img {
            max-height: {{ $signatureHeight }}px;
            max-width: 220px;
            display: block;
            margin: 0 auto 4px;
        }
        .pdf-signature-line {
            border-top: 1px solid {{ $secondary }};
            margin: 0 30px 4px;
            padding-top: 4px;
        }
        .pdf-signature-name {
            font-size: 10px;
            font-weight: 700;
            color: {{ $textColor }};
        }
        .pdf-signature-title-text {
            font-size: 9px;
            color: {{ $secondary }};
            margin-top: 1px;
        }

        /* ===== Footer paginé ===== */
        .pdf-footer {
            position: fixed;
            bottom: -10mm;
            left: 0;
            right: 0;
            font-size: 8px;
            color: {{ $secondary }};
            border-top: 1px solid #e5e7eb;
            padding-top: 4px;
            text-align: center;
        }
        .pdf-footer-page::after {
            content: counter(page);
        }

        @if(!empty($pdf['watermark']))
            .pdf-watermark {
                position: fixed; top: 50%; left: 50%;
                transform: translate(-50%, -50%) rotate({{ $watermarkRotation }}deg);
                font-size: 90px; color: rgba(0,0,0,{{ $watermarkOpacity }});
                font-weight: 900; z-index: -1;
                white-space: nowrap;
            }
        @endif
    </style>
</head>
<body>
    @if(!empty($pdf['watermark']))
        <div class="pdf-watermark">{{ $pdf['watermark'] }}</div>
    @endif

    {{-- Footer paginé : placé avant le contenu pour que DomPDF l'affiche aussi sur la première page. --}}
    <div class="pdf-footer">
        {{ $footerText }}
        @if(!empty($pdf['show_director_signature']) && !empty($school['director_name']))
            · {{ $directorTitle }} : {{ $school['director_name'] }}
        @endif
        @if(!empty($pdf['show_pagination']))
            · Page <span class="pdf-footer-page"></span>
        @endif
    </div>

    {{-- Header banner premium : table 2-col (logo carré bg primary | infos école + titre intégré) --}}
    <table class="pdf-banner">
        <tr>
            <td class="pdf-banner-logo-cell">
                @if($logoBase64)
                    <span class="pdf-banner-logo-frame">
                        <img src="data:{{ $logoMime }};base64,{{ $logoBase64 }}" alt="logo" class="pdf-banner-logo">
                    </span>
                @else
                    <div class="pdf-banner-logo-fallback">{{ mb_substr($school['acronym'] ?? 'K', 0, 1, 'UTF-8') }}</div>
                @endif
            </td>
            <td class="pdf-banner-info-cell">
                <div class="pdf-school-name">{{ $school['name'] ?? config('app.name') }}</div>
                @if($school['address'] || $school['city'] || $school['phone'] || $school['email'])
                    <div class="pdf-school-meta">
                        @if($school['address']){{ $school['address'] }}@endif
                        @if($school['city']) · {{ $school['city'] }}@endif
                        @if($school['country']) · {{ $school['country'] }}@endif
                        @if($school['phone'] || $school['email'])
                            <br>
                            @if($school['phone']) Tél : {{ $school['phone'] }}@endif
                            @if($school['email']) · {{ $school['email'] }}@endif
                            @if($school['website']) · {{ $school['website'] }}@endif
                        @endif
                    </div>
                @endif
                <div class="pdf-banner-divider">
                    <div class="pdf-banner-title">{{ $titleUpper }}</div>
                    @if($subtitle)
                        <div class="pdf-banner-subtitle">{{ $subtitle }}</div>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    {{-- Méta-bar : Généré le | (Généré par) — pas de "Référence : <acronym>"
         car l'acronyme tenant n'est pas une référence document utile et donne
         l'impression qu'il s'agit d'un identifiant pertinent. Le nom complet
         de l'établissement est déjà visible dans le banner. --}}
    <table class="pdf-meta-bar">
        <tr>
            <td class="spacer"></td>
            <td style="text-align: left; width: 50%;">
                <span class="pdf-meta-label">Généré le :</span>
                <span class="pdf-meta-value">{{ now()->locale('fr')->translatedFormat('d F Y à H:i') }}</span>
            </td>
            <td style="text-align: right; width: 32%;">
                @if(!empty($pdf['show_generator_name']) && auth()->check())
                    <span class="pdf-meta-label">Par :</span>
                    <span class="pdf-meta-value">{{ auth()->user()->name }}</span>
                @endif
            </td>
        </tr>
    </table>

    {{-- Filtres appliqués --}}
    @if(!empty($filters))
        <x-pdf-filters-recap :filters="$filters" />
    @endif

    {{-- Slot body --}}
    <div class="pdf-body">
        {{ $slot }}
    </div>

    {{-- Signature block — pattern document officiel : zone libre pour signature
         manuscrite/cachet, séparateur ligne fine, nom du signataire en BAS.
         Pas de "Espace réservé" — un vrai bulletin n'écrit jamais ça. --}}
    @if(in_array($signatureBlock, ['director', 'secretary', 'both'], true))
        <div class="pdf-signature-block">
            <table class="pdf-signature-grid">
                <tr>
                    @if(in_array($signatureBlock, ['director', 'both'], true))
                        <td class="pdf-signature-cell" style="width: {{ $signatureBlock === 'both' ? 50 : 100 }}%;">
                            <div class="pdf-signature-card">
                                @if(!empty($signatureFiles['director']))
                                    <img src="data:image/{{ $signatureFiles['director']['ext'] }};base64,{{ $signatureFiles['director']['b64'] }}"
                                         alt="Signature directeur" class="pdf-signature-img">
                                @endif
                                <div class="pdf-signature-line">
                                    <div class="pdf-signature-name">{{ $directorName ?: $directorTitle }}</div>
                                    @if($directorName)
                                        <div class="pdf-signature-title-text">{{ $directorTitle }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                    @endif
                    @if(in_array($signatureBlock, ['secretary', 'both'], true))
                        <td class="pdf-signature-cell" style="width: {{ $signatureBlock === 'both' ? 50 : 100 }}%;">
                            <div class="pdf-signature-card">
                                @if(!empty($signatureFiles['secretary']))
                                    <img src="data:image/{{ $signatureFiles['secretary']['ext'] }};base64,{{ $signatureFiles['secretary']['b64'] }}"
                                         alt="Signature secrétaire" class="pdf-signature-img">
                                @endif
                                <div class="pdf-signature-line">
                                    <div class="pdf-signature-name">Le Secrétariat</div>
                                </div>
                            </div>
                        </td>
                    @endif
                </tr>
            </table>
        </div>
    @endif

</body>
</html>

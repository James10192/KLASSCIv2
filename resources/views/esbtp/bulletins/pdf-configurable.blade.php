<!DOCTYPE html>
<html lang="fr">
<head>
    @include('pdf.partials.theme')
    @php
        $pdfSettings   = \App\Helpers\SettingsHelper::getPdfSettings();
        $pdfHeaderBg   = $pdfSettings['header_bg_color']   ?? '#0453cb';
        $pdfHeaderText = $pdfSettings['header_text_color'] ?? '#ffffff';
        $pdfPrimary    = $pdfSettings['primary_color']     ?? $pdfHeaderBg;
        $pdfText       = $pdfSettings['text_color']        ?? '#1f2937';
        $pdfSecondary  = $pdfSettings['secondary_color']   ?? '#64748b';
        $anneeAffichee = ($bulletin ?? null)?->anneeUniversitaire ?? ($anneeUniversitaire ?? null);
        $anneeLabel = $anneeAffichee?->display_name ?? '';
        $typeScale = \App\Services\BulletinTypography::scale($settings['bulletin_font_size'] ?? 13);
        $headerScale = max(70, min(220, (int) ($settings['bulletin_header_scale'] ?? 100)));
        $headerRatio = $headerScale / 100;
        $headerSetting = static function (string $key, float $fallback, float $min, float $max) use ($settings, $headerRatio): float {
            $raw = $settings[$key] ?? null;
            $base = is_numeric($raw) && (float) $raw > 0 ? (float) $raw : $fallback;
            return round(max($min, min($max, $base * $headerRatio)), 2);
        };
        $headerLeftFont = $headerSetting('bulletin_header_left_font_size', $typeScale['table_head'], 6, 32);
        $headerSchoolNameFont = $headerSetting('bulletin_header_school_name_font_size', $typeScale['heading'], 8, 34);
        $headerSchoolMetaFont = $headerSetting('bulletin_header_school_meta_font_size', $typeScale['meta'], 6, 24);
        $headerTitleFont = $headerSetting('bulletin_header_title_font_size', $typeScale['title'], 8, 34);
        $headerRightFont = $headerSetting('bulletin_header_right_font_size', $typeScale['info'], 6, 26);
        $logoBaseHeight = is_numeric($settings['bulletin_header_logo_height'] ?? null) ? (int) $settings['bulletin_header_logo_height'] : 72;
        $headerLogoSize = max(40, min(180, (int) round($logoBaseHeight * $headerRatio)));
        $signatureHeight = max(70, min(240, (int) ($settings['bulletin_signature_height'] ?? 70)));
        $signatureWidth = max(180, min(520, (int) ($settings['bulletin_signature_width'] ?? 250)));
        $signatureFontSize = max(6, min(20, (int) (($settings['bulletin_signature_font_size'] ?? '') ?: $typeScale['signature'])));
        $editionFontSize = max(6, min(18, (int) (($settings['bulletin_edition_font_size'] ?? '') ?: $typeScale['body'])));
        $editionOpacity = max(10, min(100, (int) ($settings['bulletin_edition_opacity'] ?? 100))) / 100;
        $authenticityFontSize = max(6, min(18, (int) (($settings['bulletin_authenticity_font_size'] ?? '') ?: $typeScale['body'])));
        $authenticityOpacity = max(10, min(100, (int) ($settings['bulletin_authenticity_opacity'] ?? 100))) / 100;
        $marginVertical = max(2, min(25, (int) ($settings['bulletin_margin_vertical'] ?? 5)));
        $marginHorizontal = max(2, min(25, (int) ($settings['bulletin_margin_horizontal'] ?? 5)));
    @endphp
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulletin de Notes - {{ $etudiant->nom }} {{ $etudiant->prenoms ?? $etudiant->prenom }}</title>
    <style>
        /* ── Base ─────────────────────────────────────────────── */
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: {{ $typeScale['body'] }}px;
            margin: 0;
            padding: 0;
            background: #fff;
            color: {{ $pdfText }};
            line-height: 1.25;
        }
        /* Compaction auto-fit 1 page : sections critiques évitent coupure */
        .student-info, .header, .signature-container,
        tr.section-header, tr.summary-row { page-break-inside: avoid; }
        .container {
            width: 100%;
            max-width: 100%;
            margin: 0;
            background: #fff;
            padding: 0;
        }
        .edition-footer {
            margin-top: 10px;
            font-size: {{ $editionFontSize }}px;
            color: {{ $pdfText }};
            opacity: {{ $editionOpacity }};
            text-align: left;
        }
        .edition-authenticity {
            margin-top: 4px;
            font-size: {{ $authenticityFontSize }}px;
            color: {{ $pdfText }};
            opacity: {{ $authenticityOpacity }};
            text-align: center;
        }

        /* ── Header principal ─────────────────────────────────── */
        .header {
            width: 100%;
            margin-bottom: 5px;
            border-bottom: 2px solid {{ $pdfPrimary }};
            padding-bottom: 4px;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .header-table td {
            border: none;
            padding: 4px 5px;
            vertical-align: middle;
            word-wrap: break-word;
        }
        .header-left {
            width: 26%;
            font-size: {{ $headerLeftFont }}px;
            line-height: 1.5;
            color: {{ $pdfText }};
            border-right: 1px solid #e5e7eb;
            padding-right: 8px;
        }
        .header-center {
            width: 48%;
            text-align: center;
            padding: 0 8px;
            vertical-align: middle;
        }
        .header-right {
            width: 26%;
            font-size: {{ $headerRightFont }}px;
            line-height: 1.5;
            text-align: right;
            padding-left: 8px;
            border-left: 1px solid #e5e7eb;
        }
        .logo {
            width: {{ $headerLogoSize }}px;
            height: {{ $headerLogoSize }}px;
            object-fit: contain;
            margin-bottom: 4px;
        }
        .school-name {
            font-weight: 700;
            font-size: {{ $headerSchoolNameFont }}px;
            color: {{ $pdfPrimary }};
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 3px;
        }
        .school-address {
            font-size: {{ $headerSchoolMetaFont }}px;
            color: {{ $pdfText }};
        }
        .header-right .title {
            font-weight: 700;
            font-size: {{ $headerTitleFont }}px;
            line-height: 1.15;
            letter-spacing: 0.04em;
            text-decoration: underline;
            color: {{ $pdfPrimary }};
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .header-right .period {
            display: inline-block;
            font-size: {{ $headerRightFont }}px;
            font-weight: 700;
            color: {{ $pdfText }};
            margin: 3px 0 5px;
            padding: 3px 7px;
            border: 1.5px solid {{ $pdfPrimary }};
            border-radius: 4px;
            background-color: #f8fafc;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .header-right .year {
            font-size: {{ $headerRightFont }}px;
            color: {{ $pdfText }};
        }

        /* ── Fiche étudiant ───────────────────────────────────── */
        .student-info {
            width: 100%;
            margin-bottom: 6px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            background: #ffffff;
            overflow: hidden;
        }
        .student-info-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin: 0;
        }
        .student-info-table > tbody > tr > td {
            border: none;
            word-wrap: break-word;
        }
        .student-info-table td.student-photo-cell {
            width: 136px;
            min-width: 136px;
            text-align: center;
            vertical-align: middle;
            padding: 2px 3px;
            background-color: #f8fafc;
            border-right: 2px solid {{ $pdfPrimary }};
        }
        .student-photo-shell {
            width: 116px;
            height: 124px;
            margin: 0 auto;
            border: 1.5px solid {{ $pdfPrimary }};
            border-radius: 7px;
            background-color: #ffffff;
            overflow: hidden;
        }
        .student-photo-shell img.student-photo {
            width: 116px;
            height: 124px;
            display: block;
            margin: 0;
            object-fit: cover;
        }
        .avatar-initials-fallback {
            width: 116px;
            height: 124px;
            display: table;
            background-color: #eef2f7;
        }
        .avatar-initials-fallback span {
            display: table-cell;
            vertical-align: middle;
            text-align: center;
            font-size: {{ $typeScale['avatar'] }}px;
            color: {{ $pdfPrimary }};
            font-weight: 700;
        }
        .matricule-badge {
            display: inline-block;
            margin-top: 3px;
            padding: 1px 4px;
            border: 1px solid #d8e0e8;
            border-radius: 4px;
            background-color: #ffffff;
            color: #334155;
            font-weight: 700;
            font-size: {{ $typeScale['meta'] }}px;
            text-align: center;
            white-space: nowrap;
        }
        .student-info-table td.info-group {
            width: 43%;
            vertical-align: top;
            padding: 3px 6px 2px;
            background-color: #ffffff;
        }
        .student-info-table td.student-academic-group {
            border-left: 1px solid #e5e7eb;
        }
        .info-section-title {
            margin: 0 0 3px;
            padding-bottom: 3px;
            border-bottom: 1px solid #e2e8f0;
            color: {{ $pdfPrimary }};
            font-size: {{ $typeScale['meta'] }}px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
        }
        .info-table td {
            padding: 2px 0;
            border: none;
            border-bottom: 1px solid #eef2f7;
            vertical-align: middle;
        }
        .info-table tr:last-child td { border-bottom: none; }
        .info-table td.info-label {
            width: 41%;
            padding-right: 8px;
            color: {{ $pdfSecondary }};
            font-size: {{ $typeScale['label'] }}px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.045em;
            white-space: nowrap;
        }
        .info-table td.info-value {
            color: {{ $pdfText }};
            font-size: {{ $typeScale['table'] }}px;
            font-weight: 600;
            word-wrap: break-word;
        }
        .info-table td.info-value--primary {
            color: {{ $pdfText }};
            font-weight: 700;
        }

        /* ── Tableau matières ─────────────────────────────────── */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
            font-size: {{ $typeScale['table'] }}px;
        }
        th, td {
            border: 1px solid #d1d5db;
            padding: 3px 5px;
            text-align: left;
        }
        th {
            background: #f3f4f6;
            font-weight: 700;
            text-align: center;
            font-size: {{ $typeScale['table_head'] }}px;
            color: #111827;
        }
        .center { text-align: center; }

        /* Lignes "Moyenne enseignement général/technique" — gris bold pour ressortir */
        .summary-row td {
            background-color: #e5e7eb !important;
            font-weight: 700;
            font-size: {{ $typeScale['body'] }}px;
        }

        /* DomPDF ne supporte pas background-color sur <tr>.
           Le background doit toujours être posé sur les <td> enfants. */
        .section-header td {
            background-color: {{ $pdfPrimary }};
            color: {{ $pdfHeaderText }};
            font-weight: 700;
            text-align: center;
            padding: 3px 6px;
            font-size: {{ $typeScale['table'] }}px;
        }
        /* Zebrage : la parite est marquee a la source ($loop->even) plutot
           qu'avec nth-child, qui compterait aussi les bandeaux de section et
           les lignes de synthese du tbody et inverserait le rythme selon le
           nombre de matieres. Teinte plus claire que .summary-row pour rester
           en second plan. */
        .subject-row-even td { background-color: #f8fafb; }

        /* Absences */
        .absences-table { width: 100%; margin-bottom: 8px; }

        .result-value-box {
            border: 1px solid #d1d5db;
            border-radius: 4px;
            padding: 2px 7px;
            min-width: 58px;
            display: inline-block;
            text-align: center;
            font-weight: 700;
            background: #f8fafb;
            font-size: {{ $typeScale['body'] }}px;
        }
        .appreciation-badge {
            display: inline-block;
            border-radius: 4px;
            padding: 2px 6px;
            font-size: {{ $typeScale['signature'] }}px;
            font-weight: 700;
            white-space: nowrap;
            border: 1px solid #d1d5db;
            background: #f8fafc;
            color: #475569;
        }
        .appreciation-badge--success { background: #ecfdf5; color: #047857; border-color: #a7f3d0; }
        .appreciation-badge--primary { background: #eff6ff; color: #0453cb; border-color: #bfdbfe; }
        .appreciation-badge--warning { background: #fffbeb; color: #b45309; border-color: #fde68a; }
        .appreciation-badge--danger { background: #fef2f2; color: #b91c1c; border-color: #fecaca; }
        .appreciation-badge--neutral { background: #f8fafc; color: #475569; border-color: #d1d5db; }
        .absences-table { width: 100%; margin-bottom: 4px; font-size: {{ $typeScale['table'] }}px; }
        .absences-table td { padding: 3px 5px; }


        /* ── Bilan : résultats, statistiques + absences, mentions ── */
        .bln-grid { width: 100%; border-collapse: collapse; margin: 6px 0 0; page-break-inside: avoid; }
        .bln-grid > tbody > tr > td.bln-col, .bln-grid > tr > td.bln-col { vertical-align: top; border: none; padding: 0 4px; }
        .bln-grid td.bln-col--first { padding-left: 0; }
        .bln-grid td.bln-col--last { padding-right: 0; }
        .bln-card { width: 100%; border-collapse: separate; border-spacing: 0; border: 1px solid #d5dce6; border-radius: 7px; font-size: {{ $typeScale['body'] }}px; background: #fff; }
        .bln-card--suite { margin-top: 5px; }
        .bln-card th {
            background: {{ $pdfPrimary }}; color: {{ $pdfHeaderText }};
            text-align: left; padding: 4px 8px; font-size: {{ $typeScale['info'] }}px;
            letter-spacing: .4px; text-transform: uppercase; border: none;
            border-radius: 6px 6px 0 0;
        }
        .bln-card td { padding: 3px 8px; border: none; border-bottom: 1px solid #eef1f5; color: #1f2937; }
        .bln-card tr:last-child td { border-bottom: none; }
        .bln-card td.bln-val { text-align: right; white-space: nowrap; font-weight: 700; width: 1%; }
        .bln-card .result-value-box { min-width: 46px; padding: 1px 6px; background: #f3f6fb; border-color: #d5dce6; }
        .bln-mentions td { padding: 3px 8px; }
        .bln-check { width: 1%; text-align: right; }
        .bln-box {
            display: inline-block; width: 11px; height: 11px; line-height: 11px;
            border: 1.2px solid #94a3b8; border-radius: 2px; text-align: center;
            font-size: 9px; font-weight: 700; color: #fff; vertical-align: middle;
        }
        .bln-mention--on td { font-weight: 700; color: {{ $pdfPrimary }}; }
        .bln-mention--on .bln-box { background: {{ $pdfPrimary }}; border-color: {{ $pdfPrimary }}; }

        /* ── Décision du conseil et signature, côte à côte ── */
        .cs-band { width: 100%; border-collapse: collapse; margin-top: 6px; page-break-inside: avoid; }
        .cs-band td.cs-cell { vertical-align: top; border: none; padding: 0; }
        .cs-band td.cs-cell--sign { padding-left: 8px; }
        .decision-container {
            border: 1px solid #d5dce6; border-left: 3px solid {{ $pdfPrimary }};
            border-radius: 7px; padding: 5px 10px 6px; background: #f8fafc;
            min-height: {{ max(50, $signatureHeight - 6) }}px;
        }
        .decision-title {
            font-weight: 700; margin-bottom: 4px; font-size: {{ $typeScale['info'] }}px;
            text-transform: uppercase; letter-spacing: .4px; color: {{ $pdfPrimary }};
        }
        .decision-text { line-height: 1.35; color: #1f2937; }
        .decision-lignes div { border-bottom: 1px dotted #94a3b8; height: 16px; }
        .signature-container {
            border: 1px solid #d5dce6; border-radius: 7px; padding: 5px 10px 6px;
            text-align: center; min-height: {{ max(50, $signatureHeight - 6) }}px;
        }
        .signature-title { font-weight: 700; text-transform: uppercase; letter-spacing: .4px; color: #334155; }
        .signature-space { border-bottom: 1.2px solid {{ $pdfPrimary }}; margin: 0 8px; }
        .signature-name { margin-top: 3px; font-weight: 700; color: #1f2937; }

        /* ── Mode PDF export ──────────────────────────────────── */
        @page {
            size: A4 portrait;
            margin: {{ $marginVertical }}mm {{ $marginHorizontal }}mm;
        }
        @if($isPdfExport ?? false)
        body.pdf-export {
            margin: 0;
            padding: 0;
            background: #fff;
        }
        body.pdf-export .container {
            width: 100%;
            max-width: none;
            padding: 0;
        }
        @else
        body .container {
            padding: {{ $marginVertical }}mm {{ $marginHorizontal }}mm;
        }
        @endif

        @media print {
            body { margin: 0; padding: 0; background: #fff; }
            .container { box-shadow: none; width: 100%; max-width: none; padding: 0 !important; }
            .print-button, .pdf-toggle { display: none !important; }
        }

        /* Bouton mode PDF preview */
        .pdf-toggle {
            position: fixed;
            top: 10px; right: 10px;
            background: #28a745;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            z-index: 1000;
            font-size: {{ $typeScale['info'] }}px;
        }
    </style>
</head>
<body @if($isPdfExport ?? false)class="pdf-export"@endif>
    {{-- Bouton 'Mode PDF' debug retiré (artefact dev affiché en preview) --}}

    <div class="container">
        @if(($showInscriptionWorkflowAlert ?? false) && !($isPdfExport ?? false))
            @include('esbtp.partials.inscription-workflow-alert', [
                'inscriptionWorkflowAlert' => $inscriptionWorkflowAlert ?? null,
                'redirectTo' => 'resultats_etudiant',
            ])
        @endif

        {{-- Header 3 colonnes --}}
        @if(($settings['bulletin_show_header'] ?? '1') == '1')
        <div class="header">
            <table class="header-table">
                <tr>
                    <td class="header-left">
                        @if(($settings['bulletin_show_republic_info'] ?? '1') == '1')
                        <div>{{ $settings['bulletin_republic_text'] ?? 'République de Côte d\'Ivoire' }}</div>
                        <div>{{ $settings['bulletin_union_text'] ?? 'Union-Discipline-Travail' }}</div>
                        @endif
                        @if(($settings['bulletin_show_ministry_info'] ?? '1') == '1')
                        <div style="margin-top: 4px;">
                            {{-- bulletin_ministry_text supporte les sauts de ligne (configurable dans /esbtp/settings) --}}
                            {!! nl2br(e($settings['bulletin_ministry_text'] ?? "Ministère de l'Enseignement Supérieur\net de la Recherche Scientifique")) !!}
                        </div>
                        @endif
                    </td>
                    <td class="header-center">
                        @if(($settings['bulletin_show_logo'] ?? '1') == '1' && isset($logoBase64) && $logoBase64)
                        <img src="{{ $logoBase64 }}" alt="Logo" class="logo">
                        @endif
                        @if(($settings['bulletin_show_school_info'] ?? '1') == '1')
                        <div class="school-name">
                            {{ $settings['bulletin_school_name_custom'] ?: $settings['school_name'] }}
                        </div>
                        <div class="school-address">
                            {{ $settings['school_address'] }}
                            @if($settings['school_phone'] ?? null) &bull; Tél : {{ $settings['school_phone'] }}@endif
                            @if($settings['school_email'] ?? null) &bull; {{ $settings['school_email'] }}@endif
                        </div>
                        @endif
                    </td>
                    <td class="header-right">
                        <div class="title">Bulletin de Notes</div>
                        <div class="period">
                            @if($periode == 'semestre1') Premier Semestre
                            @elseif($periode == 'semestre2') Deuxième Semestre
                            @else Annuel
                            @endif
                        </div>

                        @if(($settings['bulletin_show_cycle_info'] ?? '1') == '1')
                        <div class="year">{{ $settings['bulletin_cycle_text'] ?? 'Brevet de Technicien Supérieur' }}</div>
                        <div class="year">{{ $settings['bulletin_cycle_abbreviation'] ?? 'BTS' }}</div>
                        @endif
                        <div class="year" style="font-weight: 700;">Année universitaire : {{ $anneeLabel }}</div>
                    </td>
                </tr>
            </table>
        </div>
        @endif

        {{-- Fiche étudiant --}}
        @php
            $prenom = $etudiant->prenoms ?? $etudiant->prenom ?? '';
            $initials = strtoupper(substr($etudiant->nom ?? 'E', 0, 1) . substr($prenom ?: 'T', 0, 1));
            $avatarFallbackPath = public_path('images/placeholders/student-avatar-fallback.png');
            $avatarFallbackBase64 = is_file($avatarFallbackPath)
                ? 'data:image/png;base64,'.base64_encode(file_get_contents($avatarFallbackPath))
                : null;
        @endphp
        <div class="student-info">
            <table class="student-info-table">
                <tr>
                    <td class="student-photo-cell">
                        <div class="student-photo-shell">
                            @if(isset($photoEtudiantBase64) && $photoEtudiantBase64)
                                <img src="{{ $photoEtudiantBase64 }}" alt="Photo de l'étudiant" class="student-photo">
                            @elseif($avatarFallbackBase64)
                                <img src="{{ $avatarFallbackBase64 }}" alt="Avatar étudiant" class="student-photo">
                            @else
                                <div class="avatar-initials-fallback"><span>{{ $initials }}</span></div>
                            @endif
                        </div>
                        @if(($settings['bulletin_show_matricule'] ?? '1') == '1')
                            <div class="matricule-badge">{{ $etudiant->matricule }}</div>
                        @endif
                    </td>
                    <td class="info-group">
                        <table class="info-table">
                            <tr>
                                <td class="info-label">Nom et Prénoms</td>
                                <td class="info-value info-value--primary">{{ $etudiant->nom }} {{ $etudiant->prenoms ?? $etudiant->prenom }}</td>
                            </tr>
                            @if(($settings['bulletin_show_birth_date'] ?? '1') == '1')
                            <tr>
                                <td class="info-label">Date de Naissance</td>
                                <td class="info-value">{{ $etudiant->date_naissance ? \Carbon\Carbon::parse($etudiant->date_naissance)->format('d/m/Y') : 'Non renseignée' }}</td>
                            </tr>
                            @endif
                            <tr>
                                <td class="info-label">Lieu de Naissance</td>
                                <td class="info-value">{{ $etudiant->lieu_naissance ?? 'Non renseigné' }}</td>
                            </tr>
                            <tr>
                                <td class="info-label">Genre</td>
                                <td class="info-value">{{ $etudiant->genre == 'M' ? 'Masculin' : 'Féminin' }}</td>
                            </tr>
                            @if(($settings['bulletin_show_redoublant'] ?? '1') == '1')
                            <tr>
                                <td class="info-label">Redoublant</td>
                                <td class="info-value">{{ ($inscription?->is_redoublant ?? false) ? 'Oui' : 'Non' }}</td>
                            </tr>
                            @endif
                            @if(($settings['bulletin_show_student_phone'] ?? '1') == '1')
                            <tr>
                                <td class="info-label">Téléphone</td>
                                <td class="info-value">{{ $etudiant->telephone ?? 'Non renseigné' }}</td>
                            </tr>
                            @endif
                        </table>
                    </td>
                    <td class="info-group student-academic-group">
                        <table class="info-table">
                            <tr>
                                <td class="info-label">Classe</td>
                                <td class="info-value info-value--primary">{{ $classe->libelle ?? $classe->name }}</td>
                            </tr>
                            @if(!empty($isSpecialisation) && !empty($classeTroncCommun) && ($settings['tronc_commun_bulletin_show_origin'] ?? '1') == '1')
                            <tr>
                                <td class="info-label">Classe S1 (TC)</td>
                                <td class="info-value">{{ $classeTroncCommun->libelle ?? $classeTroncCommun->name }}</td>
                            </tr>
                            @endif
                            <tr>
                                <td class="info-label">Année d'étude</td>
                                <td class="info-value">{{ $classe->niveau->libelle ?? $classe->niveau->name ?? ($classe->annee ?? 'N/A') }}</td>
                            </tr>
                            <tr>
                                <td class="info-label">Filière</td>
                                <td class="info-value">{{ $classe->filiere->name ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td class="info-label">Effectif</td>
                                <td class="info-value">{{ $effectif }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </div>

        {{-- Tableau des matières --}}
        @if(($settings['bulletin_show_subjects_table'] ?? '1') == '1')
        @php
            $showSubjectAverage = ($settings['bulletin_show_subject_average'] ?? '1') == '1';
            $showCoefficient = ($settings['bulletin_show_coefficient'] ?? '1') == '1';
            $showWeightedAverage = ($settings['bulletin_show_weighted_average'] ?? '1') == '1';
            $showRankPerSubject = ($settings['bulletin_show_rank_per_subject'] ?? '1') == '1';
            $showAbsencesParMatiere = ($settings['bulletin_show_absences_par_matiere'] ?? '0') == '1'
                && ($settings['bulletin_conduite_enabled'] ?? '0') == '1';
            $showTeachers = ($settings['bulletin_show_teachers'] ?? '1') == '1';
            $showAppreciations = ($settings['bulletin_show_appreciations'] ?? '1') == '1';
            $subjectColumnCount = 1
                + ($showSubjectAverage ? 1 : 0)
                + ($showCoefficient ? 1 : 0)
                + ($showWeightedAverage ? 1 : 0)
                + ($showRankPerSubject ? 1 : 0)
                + ($showAbsencesParMatiere ? 1 : 0)
                + ($showTeachers ? 1 : 0)
                + ($showAppreciations ? 1 : 0);
            $sections = \App\Services\BulletinSectionSummary::forView(
                $settings ?? [],
                $resultatsGeneraux ?? collect(),
                $resultatsTechniques ?? collect(),
                $absencesParMatiere ?? [],
                isset($moyenneGenerale) ? (float) $moyenneGenerale : null,
                isset($moyenneTechnique) ? (float) $moyenneTechnique : null,
                $showRankPerSubject ? [
                    'etudiant_id' => (int) ($etudiant->id ?? 0),
                    'classe_id' => (int) ($classe->id ?? 0),
                    'annee_id' => (int) ($anneeUniversitaire->id ?? $bulletin->annee_universitaire_id ?? 0),
                    'periode' => (string) $periode,
                ] : null
            );
        @endphp
        <table>
            <thead>
                <tr>
                    <th>Matière</th>
                    @if($showSubjectAverage)<th>Moyenne M</th>@endif
                    @if($showCoefficient)<th>Coef C</th>@endif
                    @if($showWeightedAverage)<th>Moy Pond&eacute;r&eacute;e M&times;C</th>@endif
                    @if($showRankPerSubject)<th>Rang</th>@endif
                    @if($showAbsencesParMatiere)<th>Abs. (h)</th>@endif
                    @if($showTeachers)<th>Professeurs</th>@endif
                    @if($showAppreciations)<th>Appr&eacute;ciations</th>@endif
                </tr>
                @if(($settings['bulletin_show_general_subjects'] ?? '1') == '1')
                <tr class="section-header">
                    <td colspan="{{ $subjectColumnCount }}">Enseignement G&eacute;n&eacute;ral</td>
                </tr>
                @endif
            </thead>
            <tbody>
                @if(($settings['bulletin_show_general_subjects'] ?? '1') == '1')
                    @if(isset($resultatsGeneraux) && $resultatsGeneraux->count() > 0)
                        @foreach($resultatsGeneraux as $resultat)
                            <tr class="subject-row{{ $loop->even ? ' subject-row-even' : '' }}">
                                <td>{{ $resultat->matiere->name ?? $resultat->matiere->nom ?? 'N/A' }}</td>
                                @include('esbtp.bulletins.partials.subject-row-cells', ['resultat' => $resultat])
                                @if($showAbsencesParMatiere)<td class="center">{{ isset($absencesParMatiere[$resultat->matiere_id]) ? $absencesParMatiere[$resultat->matiere_id]['total_heures'] : 0 }}</td>@endif
                                @if($showTeachers)<td>{{ trim((string) ($professeurs[$resultat->matiere_id] ?? '')) ?: 'Non attribué' }}</td>@endif
                                @if($showAppreciations)
                                    <td class="center">
                                        @include('esbtp.bulletins.partials.appreciation', [
                                            'moyenne' => \App\Models\ESBTPResultatMatiere::ligneNotee($resultat) ? $resultat->moyenne : null,
                                            'emptyLabel' => \App\Models\ESBTPResultatMatiere::libelleEtat($resultat),
                                            'badgeClass' => 'appreciation-badge',
                                        ])
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    @else
                        <tr><td colspan="{{ $subjectColumnCount }}" class="center">Aucune mati&egrave;re d'enseignement g&eacute;n&eacute;ral</td></tr>
                    @endif
                    @if(($settings['bulletin_show_section_averages'] ?? '1') == '1')
                        @include('esbtp.bulletins.partials.section-summary-row', [
                            'label' => 'Moyenne enseignement général',
                            'summary' => $sections['general'],
                        ])
                    @endif
                @endif

                @if(($settings['bulletin_show_technical_subjects'] ?? '1') == '1')
                <tr class="section-header">
                    <td colspan="{{ $subjectColumnCount }}">Enseignement Technique</td>
                </tr>
                @if(isset($resultatsTechniques) && $resultatsTechniques->count() > 0)
                    @foreach($resultatsTechniques as $resultat)
                        <tr class="subject-row{{ $loop->even ? ' subject-row-even' : '' }}">
                            <td>{{ $resultat->matiere->name ?? $resultat->matiere->nom ?? 'N/A' }}</td>
                            @include('esbtp.bulletins.partials.subject-row-cells', ['resultat' => $resultat])
                            @if($showAbsencesParMatiere)<td class="center">{{ isset($absencesParMatiere[$resultat->matiere_id]) ? $absencesParMatiere[$resultat->matiere_id]['total_heures'] : 0 }}</td>@endif
                            @if($showTeachers)<td>{{ trim((string) ($professeurs[$resultat->matiere_id] ?? '')) ?: 'Non attribué' }}</td>@endif
                            @if($showAppreciations)
                                <td class="center">
                                    @include('esbtp.bulletins.partials.appreciation', [
                                        'moyenne' => \App\Models\ESBTPResultatMatiere::ligneNotee($resultat) ? $resultat->moyenne : null,
                                        'emptyLabel' => \App\Models\ESBTPResultatMatiere::libelleEtat($resultat),
                                        'badgeClass' => 'appreciation-badge',
                                    ])
                                </td>
                            @endif
                        </tr>
                    @endforeach
                @else
                    <tr><td colspan="{{ $subjectColumnCount }}" class="center">Aucune mati&egrave;re d'enseignement technique</td></tr>
                @endif
                @if(($settings['bulletin_show_section_averages'] ?? '1') == '1')
                    @include('esbtp.bulletins.partials.section-summary-row', [
                        'label' => 'Moyenne enseignement technique',
                            'summary' => $sections['technical'],
                    ])
                @endif
                @endif
            </tbody>
        </table>
        @endif

        @include('esbtp.bulletins.partials.dispenses-note')

        {{-- Note de Conduite --}}
        @if(($settings['bulletin_conduite_enabled'] ?? '0') == '1' && isset($noteConduite))
        <table class="absences-table">
            <thead>
                <tr class="section-header">
                    <td colspan="2">Note de Conduite</td>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Total heures d'absences</td>
                    <td class="center" style="width: 50%;">{{ $totalHeuresAbsencesParMatiere ?? 0 }} Heure(s)</td>
                </tr>
                <tr>
                    <td>Note de conduite</td>
                    <td class="center"><strong>{{ number_format($noteConduite, 2) }} / 20</strong></td>
                </tr>
                @if(!empty($mentionConduite))
                <tr>
                    <td>Mention conduite</td>
                    <td class="center"><strong style="color: #c0392b;">{{ $mentionConduite }}</strong></td>
                </tr>
                @endif
            </tbody>
        </table>
        @endif

        {{-- Bilan (résultats, statistiques et absences, mentions) puis décision
             du conseil et signature sur toute la largeur. --}}
        @include('esbtp.bulletins.partials.bilan')

        @include('esbtp.bulletins.partials.edition-footer')

    </div>

    @unless($isPdfExport ?? false)
    <script>
        function togglePDFMode() {
            const body = document.body;
            const button = document.getElementById('pdfToggle');
            if (body.classList.contains('pdf-mode')) {
                body.classList.remove('pdf-mode');
                button.textContent = 'Mode PDF';
                button.style.backgroundColor = '#28a745';
            } else {
                body.classList.add('pdf-mode');
                button.textContent = 'Mode Web';
                button.style.backgroundColor = '#dc3545';
            }
        }
        if (window.location.search.includes('preview=pdf')) { togglePDFMode(); }
    </script>
    @endunless
</body>
</html>

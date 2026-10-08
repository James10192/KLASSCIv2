@inject('vocabulaire', 'App\Services\LMD\VocabulaireStructure')
<!DOCTYPE html>
<html lang="fr">
<head>
    @include('pdf.partials.theme')
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Bulletin LMD - {{ $etudiant->nom ?? '' }} {{ $etudiant->prenoms ?? '' }}</title>

    @php
        $hdrBg = $pdfCfg['header_bg_color'] ?? $pdfCfg['primary_color'] ?? '#0453cb';
        // Le picker « Texte dans l'en-tête établissement » est une décision de
        // charte de l'école : nom de l'établissement, titre du document et
        // métadonnées doivent reprendre EXACTEMENT la valeur enregistrée.
        // Le contraste automatique reste réservé aux en-têtes de tableaux.
        $hdrText = $pdfCfg['header_text_color_raw'] ?? $pdfCfg['header_text_color'] ?? '#ffffff';
        $primary = $pdfCfg['primary_color'] ?? '#0453cb';
        $tableHeaderText = $pdfCfg['header_text_on_primary']
            ?? \App\Helpers\SettingsHelper::contrastingText($primary, $pdfCfg['header_text_color_raw'] ?? '#ffffff');
        $bodyText = $pdfCfg['text_color'] ?? '#1f2937';
        $secondary = $pdfCfg['secondary_color'] ?? '#64748b';
        $etab = $etablissement ?? [];
        $bCfg = $bulletinCfg ?? [];

        // La page de configuration reste maître des tailles. Le plafond plus
        // généreux permet désormais une vraie mise en page lisible ; le gabarit
        // est conçu pour déborder proprement sur une seconde page plutôt que de
        // forcer tout le bulletin à tenir dans une typographie minuscule.
        $font = static function (string $key, float $default): float {
            $raw = \App\Helpers\SettingsHelper::get($key, $default);
            $value = is_numeric($raw) ? (float) $raw : $default;
            return max(6, min(32, $value));
        };
        $fontRepublic = $font('lmd_bulletin_font_republic', 9);
        $fontSchoolName = $font('lmd_bulletin_font_school_name', 15);
        $fontSchoolMeta = $font('lmd_bulletin_font_school_meta', 8.5);
        $fontTitle = $font('lmd_bulletin_font_title', 14);
        $fontHeaderMeta = $font('lmd_bulletin_font_header_meta', 9);
        $fontEstablishment = $font('lmd_bulletin_font_establishment', 9.5);
        $fontStudent = $font('lmd_bulletin_font_student', 10.5);
        $fontStructure = $font('lmd_bulletin_font_structure', 10);
        $fontTableHeader = $font('lmd_bulletin_font_table_header', 9);
        $fontTable = $font('lmd_bulletin_font_table', 9.5);
        $fontTeacher = $font('lmd_bulletin_font_teacher', 8.5);
        $fontSummary = $font('lmd_bulletin_font_summary', 13);
        $fontDecision = $font('lmd_bulletin_font_decision', 10.5);
        $fontNotice = $font('lmd_bulletin_font_notice', 8.5);
        $fontSignature = $font('lmd_bulletin_font_signature', 10);
        $fontLegend = $font('lmd_bulletin_font_legend', 8);
        $fontBottom = $font('lmd_bulletin_font_bottom', 8.5);

        // Plus le semestre contient de lignes, plus on réduit légèrement le
        // padding vertical, jamais la taille choisie par l'école. Un bulletin
        // court respire ; un bulletin long s'étale proprement sur deux pages.
        $academicRowCount = collect($resultats_ues ?? [])->sum(function ($resUE) {
            return 1 + ($resUE->resultatsECUEs?->count() ?? 0);
        });
        $rowPadding = $academicRowCount <= 28 ? 4.2 : ($academicRowCount <= 40 ? 3.5 : 2.8);
        $rowLineHeight = $academicRowCount <= 28 ? 1.28 : ($academicRowCount <= 40 ? 1.22 : 1.16);

        $appreciationScale = app(\App\Services\AppreciationScaleService::class);
        $anneeLabel = $annee?->display_name ?? $annee?->name ?? '';
        $formatScaleNumber = static fn (float $value): string => rtrim(rtrim(number_format($value, 2, ',', ''), '0'), ',');
        $lmdAppreciationLegend = collect($appreciationScale->scale('lmd'))
            ->sortByDesc('min')
            ->map(fn (array $range) => $formatScaleNumber($range['min']) . '-' . $formatScaleNumber($range['max']) . ' : ' . $range['label'])
            ->implode(' ; ');
        $noticeText = trim((string) ($bCfg['notice_text'] ?? '')) ?: \App\Services\LMDBulletinService::NOTICE_DEFAUT;
        $bottomText = trim((string) ($bCfg['bottom_text'] ?? '')) ?: 'Conservez soigneusement ce bulletin de notes. Aucun duplicata ne sera délivré.';
        $statutEtablissement = trim((string) ($bCfg['statut'] ?? 'Privé')) ?: 'Privé';
        $directionEtablissement = trim((string) ($bCfg['direction'] ?? ''));
        $codeEtablissement = trim((string) ($bCfg['code_etablissement'] ?? ''));

        // Le bandeau officiel ne doit jamais afficher de rubrique vide.
        // Une valeur absente supprime à la fois son libellé et sa colonne ;
        // les rubriques restantes se repartagent automatiquement toute la largeur.
        $officialBandItems = collect([
            ['label' => 'Code établissement', 'value' => $codeEtablissement],
            ['label' => 'Statut', 'value' => $statutEtablissement],
            ['label' => 'Direction', 'value' => $directionEtablissement],
        ])->filter(fn (array $item) => trim((string) $item['value']) !== '')->values();
        $officialColumnWidth = $officialBandItems->isNotEmpty()
            ? 100 / $officialBandItems->count()
            : 100;
        // Comme le bulletin BTS, la date imprimée est la date d'édition du PDF,
        // pas la dernière mise à jour du bulletin en base.
        $editionDate = now()->format('d/m/Y');
        // Dimensions spécifiques au bulletin LMD. Elles restent indépendantes
        // des autres documents PDF : agrandir le logo ici ne modifie ni reçus,
        // ni listes d'appel, ni attestations.
        $layoutNumber = static function (string $key, float $default, float $min, float $max): float {
            $raw = \App\Helpers\SettingsHelper::get($key, $default);
            $value = is_numeric($raw) ? (float) $raw : $default;
            return max($min, min($max, $value));
        };
        $fallbackLogo = is_numeric($pdfCfg['logo_size'] ?? null) ? (float) $pdfCfg['logo_size'] : 72;
        $logoHeight = $layoutNumber('lmd_bulletin_logo_height', $fallbackLogo, 40, 140);
        $logoWidth = (int) round($logoHeight * 1.8);
        $headerPaddingY = $layoutNumber('lmd_bulletin_header_padding_y', 6, 2, 14);
        $headerMetaPaddingY = $layoutNumber('lmd_bulletin_header_meta_padding_y', 2, 0, 8);
        $signatureSpaceHeight = $layoutNumber('lmd_bulletin_signature_space_height', 42, 20, 120);
        $bottomWidthPercent = $layoutNumber('lmd_bulletin_bottom_width_percent', 104, 90, 108);
        $bottomHorizontalOffset = (100 - $bottomWidthPercent) / 2;
        $officialHeaderBg = \App\Helpers\SettingsHelper::get('lmd_bulletin_official_header_bg', '#ffffff');
        $officialLabelColor = \App\Helpers\SettingsHelper::get('lmd_bulletin_header_label_color', '#1f2937');
        $officialLabelBold = \App\Helpers\SettingsHelper::get('lmd_bulletin_header_label_bold', '0') == '1';
        $ministryBold = \App\Helpers\SettingsHelper::get('lmd_bulletin_ministry_bold', '0') == '1';
        $headerMetaLabelColor = \App\Helpers\SettingsHelper::get('lmd_bulletin_meta_label_color', '#1f2937');
        $headerMetaLabelBold = \App\Helpers\SettingsHelper::get('lmd_bulletin_meta_label_bold', '0') == '1';
        $paysEtablissement = trim((string) ($etab['pays'] ?? '')) ?: 'Côte d\'Ivoire';
    @endphp

    <style>
        @page { margin: 0.42cm 0.46cm 0.50cm; size: A4 portrait; }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: {{ $fontTable }}px;
            margin: 0;
            padding: 2px;
            color: {{ $bodyText }};
            line-height: 1.28;
            background: #ffffff;
        }
        .container { width: 100%; max-width: 100%; background: #fff; padding: 4px 5px; box-sizing: border-box; }
        .num { text-align: center; }
        .keep-together { page-break-inside: avoid; }

        /* En-tête compact 50/50 : identité établissement à gauche,
         * document + métadonnées à droite. On gagne une ligne complète
         * par rapport à l'ancien empilement logo/école puis titre/métadonnées. */
        .lmd-document-header {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            border: 1px solid {{ $primary }};
            margin-bottom: 4px;
            page-break-inside: avoid;
            border-radius: 7px;
        }
        .lmd-header-school,
        .lmd-header-document {
            width: 50%;
            vertical-align: middle;
            background-color: {{ $hdrBg }};
        }
        .lmd-header-school {
            padding: {{ $headerPaddingY }}px 7px;
            border-right: 1px solid rgba(255,255,255,0.28);
            border-radius: 7px 0 0 7px;
        }
        .lmd-header-document { padding: {{ $headerPaddingY }}px 9px; border-radius: 0 7px 7px 0; }
        .lmd-header-title {
            color: {{ $hdrText }};
            font-size: {{ $fontTitle }}px;
            font-weight: 800;
            letter-spacing: .30px;
            line-height: 1.14;
            margin-bottom: 5px;
        }
        .lmd-header-meta {
            width: 100%;
            border-collapse: collapse;
        }
        .lmd-header-meta td {
            width: 50%;
            padding: {{ $headerMetaPaddingY }}px 4px;
            color: {{ $hdrText }};
            font-size: {{ $fontHeaderMeta }}px;
            line-height: 1.18;
        }
        .lmd-header-meta td + td { border-left: 1px solid rgba(255,255,255,0.22); }
        .lmd-header-meta tr + tr td { border-top: 1px solid rgba(255,255,255,0.18); }
        .lmd-header-meta-label { color: {{ $headerMetaLabelColor }}; font-weight: {{ $headerMetaLabelBold ? '700' : '400' }}; }
        .lmd-header-meta-value { font-weight: 800; }

        .signature-title {
            font-weight: 800;
            text-align: center;
        }
        .signature-space {
            height: {{ $signatureSpaceHeight }}px;
            line-height: {{ $signatureSpaceHeight }}px;
        }
        .signature-name {
            font-size: {{ min(32, $fontSignature + 1) }}px;
            font-weight: 800;
            text-align: center;
        }

        .official-band {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            border: 1px solid #cbd5e1;
            border-radius: 5px;
            margin: 0 0 4px;
        }
        .official-band td {
            padding: 4px 7px;
            vertical-align: middle;
            font-size: {{ $fontEstablishment }}px;
        }
        .official-band td + td { border-left: 1px solid #dbe3ea; }
        .official-label {
            display: block;
            margin-bottom: 1px;
            font-size: {{ max(6, $fontEstablishment - 1.6) }}px;
            font-weight: 700;
            letter-spacing: .35px;
            text-transform: uppercase;
            color: {{ $secondary }};
        }
        .official-value { font-weight: 700; color: {{ $bodyText }}; }

        .identity-grid { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        .identity-grid > tbody > tr > td { vertical-align: top; }
        .identity-grid > tbody > tr > td:first-child { padding-right: 13px; }
        .identity-grid > tbody > tr > td:last-child { padding-left: 13px; border-left: 1px solid #e2e8f0; }
        .identity-grid table td { padding: 1.8px 0; line-height: 1.20; }

        .bulletin-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
            page-break-inside: auto;
        }
        .bulletin-table thead { display: table-header-group; }
        .bulletin-table tbody { display: table-row-group; }
        .bulletin-table tr { page-break-inside: avoid; page-break-after: auto; }
        .bulletin-table td,
        .bulletin-table th {
            border: 1px solid {{ $bodyText }};
            padding: {{ $rowPadding }}px 4px;
            font-size: {{ $fontTable }}px;
            line-height: {{ $rowLineHeight }};
            vertical-align: middle;
        }
        .bulletin-table .ue-row td { background-color: #f8fafc; font-weight: 700; }

        .summary-table { width: 100%; border-collapse: separate; border-spacing: 0; margin-top: 8px; page-break-inside: avoid; }
        .summary-table td { vertical-align: middle; }
        .moyenne-box,
        .credits-box {
            border: 1.2px solid #94a3b8;
            padding: 6px 10px;
            text-align: center;
            font-weight: 700;
            font-size: {{ $fontSummary }}px;
            border-radius: 5px;
        }
        .summary-label { color: {{ $secondary }}; font-size: {{ max(6, $fontSummary - 2.2) }}px; letter-spacing: .25px; }
        .summary-value { color: {{ $primary }}; font-size: {{ min(32, $fontSummary + 2) }}px; font-weight: 800; }

        .decision-box {
            border: 1px solid #94a3b8;
            padding: 6px 10px;
            margin-top: 6px;
            width: 100%;
            box-sizing: border-box;
            text-align: left;
            font-size: {{ $fontDecision }}px;
            page-break-inside: avoid;
        }
        .decision-label { font-size: {{ max(6, $fontDecision - 1) }}px; font-weight: 700; text-decoration: underline; }
        .decision-value { font-size: {{ $fontDecision }}px; font-weight: 800; margin-left: 16px; }
        .notice {
            border: 1px solid #94a3b8;
            padding: 5px 8px;
            margin-top: 6px;
            font-size: {{ $fontNotice }}px;
            page-break-inside: avoid;
        }

        .closing-grid {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            page-break-inside: avoid;
        }
        .closing-grid td { vertical-align: top; }
        .legend {
            padding: 5px 12px 0 0;
            font-size: {{ $fontLegend }}px;
            line-height: 1.35;
            color: {{ $secondary }};
        }
        .signature-block {
            border-left: 1px solid #e2e8f0;
            padding: 4px 0 0 16px;
            font-size: {{ $fontSignature }}px;
            line-height: 1.30;
            min-height: 68px;
        }
        .bottom-note {
            width: {{ $bottomWidthPercent }}%;
            margin-left: {{ $bottomHorizontalOffset }}%;
            text-align: center;
            font-size: {{ $fontBottom }}px;
            color: {{ $secondary }};
            margin-top: 5px;
            line-height: 1.32;
            page-break-inside: avoid;
        }
        .bottom-note-line {
            display: block;
            white-space: normal;
            word-wrap: break-word;
        }
    </style>
</head>
<body>
<div class="container">

@if(($bCfg['show_republic_info'] ?? true) || ($bCfg['show_ministry_info'] ?? true))
<table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-bottom: 4px; border-collapse: collapse;">
    <tr>
        <td style="text-align: center; font-size: {{ $fontRepublic }}px; color: {{ $bodyText }}; line-height: 1.32; padding: 3px 10px; background-color: {{ $officialHeaderBg }};">
            @if($bCfg['show_republic_info'] ?? true)
                <div style="font-weight: {{ $officialLabelBold ? 800 : 400 }}; color: {{ $officialLabelColor }}; font-size: {{ max(6, $fontRepublic + .5) }}px;">{{ $bCfg['republic_text'] ?? 'REPUBLIQUE DE COTE D\'IVOIRE' }}</div>
                <div style="font-size: {{ max(6, $fontRepublic - 1) }}px; font-style: italic; color: {{ $secondary }};">{{ $bCfg['union_text'] ?? 'Union - Discipline - Travail' }}</div>
            @endif
            @if($bCfg['show_ministry_info'] ?? true)
                <div style="font-size: {{ $fontRepublic }}px; margin-top: 1px; color: {{ $officialLabelColor }}; font-weight: {{ $ministryBold ? 800 : 400 }};">{{ $bCfg['ministry_text'] ?? 'MINISTERE DE L\'ENSEIGNEMENT SUPERIEUR ET DE LA RECHERCHE SCIENTIFIQUE' }}</div>
            @endif
        </td>
    </tr>
</table>
@endif

<table class="lmd-document-header" border="0" cellspacing="0" cellpadding="0">
    <tr>
        <td class="lmd-header-school">
            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                <tr>
                    <td width="27%" style="text-align:center; vertical-align:middle; padding-right:7px;">
                        @if(isset($logoBase64) && $logoBase64)
                            <img src="{{ $logoBase64 }}" style="max-height: {{ $logoHeight }}px; max-width: {{ $logoWidth }}px;" alt="Logo">
                        @else
                            <div style="font-size: 30px; font-weight: 900; color: {{ $hdrText }}; opacity: 0.4; letter-spacing: -2px;">K</div>
                        @endif
                    </td>
                    <td width="73%" style="vertical-align:middle;">
                        <div style="font-size: {{ $fontSchoolName }}px; font-weight: 800; color: {{ $hdrText }}; line-height:1.12; margin-bottom:3px;">
                            {{ $etab['nom'] ?? 'KLASSCI' }}
                        </div>
                        @if(($etab['adresse'] ?? '') || ($etab['telephone'] ?? '') || ($etab['email'] ?? ''))
                            <div style="font-size: {{ $fontSchoolMeta }}px; color: {{ $hdrText }}; opacity:0.86; line-height:1.25;">
                                @if($etab['adresse'] ?? ''){{ $etab['adresse'] }}@endif
                                @if($etab['telephone'] ?? '')
                                    @if($etab['adresse'] ?? '') &nbsp;|&nbsp; @endif
                                    Tél: {{ $etab['telephone'] }}
                                @endif
                                @if($etab['email'] ?? '')
                                    @if(($etab['adresse'] ?? '') || ($etab['telephone'] ?? ''))<br>@endif
                                    {{ $etab['email'] }}
                                @endif
                            </div>
                        @endif
                    </td>
                </tr>
            </table>
        </td>
        <td class="lmd-header-document">
            <div class="lmd-header-title">
                BULLETIN SEMESTRIEL DE NOTES — {{ $semestre }}{{ $semestre == 1 ? 'er' : 'ème' }} semestre
            </div>
            <table class="lmd-header-meta" border="0" cellspacing="0" cellpadding="0">
                <tr>
                    <td>
                        <span class="lmd-header-meta-label">Année universitaire :</span>
                        <span class="lmd-header-meta-value">{{ $anneeLabel }}</span>
                    </td>
                    <td>
                        <span class="lmd-header-meta-label">{{ \App\Services\BulletinMentionResolver::editionLabel() }}</span>
                        <span class="lmd-header-meta-value">{{ $editionDate }}</span>
                    </td>
                </tr>
                <tr>
                    <td>
                        <span class="lmd-header-meta-label">Niveau :</span>
                        <span class="lmd-header-meta-value">{{ $niveau ?? '' }}</span>
                    </td>
                    <td>
                        <span class="lmd-header-meta-label">Semestre :</span>
                        <span class="lmd-header-meta-value">{{ $semestre ?? '' }}</span>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

@if(($bCfg['show_etablissement_box'] ?? true) && $officialBandItems->isNotEmpty())
<table class="official-band" cellspacing="0" cellpadding="0">
    <tr>
        @foreach($officialBandItems as $officialItem)
            <td style="width: {{ $officialColumnWidth }}%;">
                <span class="official-label">{{ $officialItem['label'] }}</span>
                <span class="official-value">{{ $officialItem['value'] }}</span>
            </td>
        @endforeach
    </tr>
</table>
@endif

<table class="identity-grid" border="0" cellspacing="0" cellpadding="0">
    <tr>
        <td width="55%">
            <table width="100%" cellspacing="0" cellpadding="0">
                <tr>
                    <td width="25%" style="font-size: {{ $fontStudent }}px; font-weight: 800;">NOM :</td>
                    <td style="font-size: {{ $fontStudent }}px;">{{ mb_strtoupper($etudiant->nom ?? '', 'UTF-8') }}</td>
                </tr>
                <tr>
                    <td style="font-size: {{ $fontStudent }}px; font-weight: 800;">PRENOMS :</td>
                    <td style="font-size: {{ $fontStudent }}px;">{{ mb_strtoupper($etudiant->prenoms ?? '', 'UTF-8') }}</td>
                </tr>
                <tr>
                    <td style="font-size: {{ $fontStudent }}px; font-weight: 800;">DATE NAISS. :</td>
                    <td style="font-size: {{ $fontStudent }}px;">{{ $etudiant->date_naissance ? \Carbon\Carbon::parse($etudiant->date_naissance)->format('d/m/Y') : '' }}</td>
                </tr>
                <tr>
                    <td style="font-size: {{ $fontStudent }}px; font-weight: 800;">MATRICULE :</td>
                    <td style="font-size: {{ $fontStudent }}px;">{{ $etudiant->matricule ?? '' }}</td>
                </tr>
                @if($bCfg['show_redoublant'] ?? false)
                <tr>
                    <td style="font-size: {{ $fontStudent }}px; font-weight: 800;">REDOUBLANT :</td>
                    <td style="font-size: {{ $fontStudent }}px;">{{ $bCfg['redoublant'] === null ? 'Non renseigné' : ($bCfg['redoublant'] ? 'Oui' : 'Non') }}</td>
                </tr>
                @endif
                @if(($bCfg['show_effectif'] ?? false) && $bulletin->effectif !== null)
                <tr>
                    <td style="font-size: {{ $fontStudent }}px; font-weight: 800;">EFFECTIF :</td>
                    <td style="font-size: {{ $fontStudent }}px;">{{ $bulletin->effectif }}</td>
                </tr>
                @endif
                <tr>
                    <td style="font-size: {{ $fontStudent }}px; font-weight: 800;">AFFECTATION :</td>
                    <td style="font-size: {{ $fontStudent }}px;">{{ $bulletin->affectation_label }}</td>
                </tr>
            </table>
        </td>
        <td width="45%">
            <table width="100%" cellspacing="0" cellpadding="0">
                @if(isset($bulletin_fields))
                    @foreach($bulletin_fields as $field)
                        @if($field['show'] && $field['value'])
                        <tr>
                            <td width="40%" style="font-size: {{ $fontStructure }}px; font-weight: 800;">{{ $field['label'] }} :</td>
                            <td style="font-size: {{ $fontStructure }}px;">{{ $field['value'] }}</td>
                        </tr>
                        @endif
                    @endforeach
                @else
                    <tr>
                        <td width="40%" style="font-size: {{ $fontStructure }}px; font-weight: 800;">{{ mb_strtoupper($vocabulaire->rang('domaine'), 'UTF-8') }} :</td>
                        <td style="font-size: {{ $fontStructure }}px;">{{ $domaine ?? '' }}</td>
                    </tr>
                    <tr>
                        <td style="font-size: {{ $fontStructure }}px; font-weight: 800;">{{ mb_strtoupper($vocabulaire->rang('mention'), 'UTF-8') }} :</td>
                        <td style="font-size: {{ $fontStructure }}px;">{{ $mention ?? '' }}</td>
                    </tr>
                    <tr>
                        <td style="font-size: {{ $fontStructure }}px; font-weight: 800;">{{ mb_strtoupper($vocabulaire->rang('parcours'), 'UTF-8') }} :</td>
                        <td style="font-size: {{ $fontStructure }}px;">{{ $parcours_label ?? '' }}</td>
                    </tr>
                @endif
            </table>
        </td>
    </tr>
</table>

<table class="bulletin-table">
    <thead>
        <tr>
            <td style="width: 10%; background-color: {{ $primary }}; color: {{ $tableHeaderText }}; font-weight: 800; text-align: center; font-size: {{ $fontTableHeader }}px; padding: 4px 3px;">Code</td>
            <td style="width: 28%; background-color: {{ $primary }}; color: {{ $tableHeaderText }}; font-weight: 800; text-align: center; font-size: {{ $fontTableHeader }}px; padding: 4px 3px;">Intitulés</td>
            <td style="width: 7%; background-color: {{ $primary }}; color: {{ $tableHeaderText }}; font-weight: 800; text-align: center; font-size: {{ $fontTableHeader }}px; padding: 4px 3px;">Moy /<br>20</td>
            <td style="width: 8%; background-color: {{ $primary }}; color: {{ $tableHeaderText }}; font-weight: 800; text-align: center; font-size: {{ $fontTableHeader }}px; padding: 4px 3px;">AQ,APC,<br>NAQ</td>
            <td style="width: 5%; background-color: {{ $primary }}; color: {{ $tableHeaderText }}; font-weight: 800; text-align: center; font-size: {{ $fontTableHeader }}px; padding: 4px 3px;">Appr.</td>
            <td style="width: 5%; background-color: {{ $primary }}; color: {{ $tableHeaderText }}; font-weight: 800; text-align: center; font-size: {{ $fontTableHeader }}px; padding: 4px 3px;">CECT</td>
            <td style="width: 7%; background-color: {{ $primary }}; color: {{ $tableHeaderText }}; font-weight: 800; text-align: center; font-size: {{ $fontTableHeader }}px; padding: 4px 3px;">min</td>
            <td style="width: 7%; background-color: {{ $primary }}; color: {{ $tableHeaderText }}; font-weight: 800; text-align: center; font-size: {{ $fontTableHeader }}px; padding: 4px 3px;">moy</td>
            <td style="width: 7%; background-color: {{ $primary }}; color: {{ $tableHeaderText }}; font-weight: 800; text-align: center; font-size: {{ $fontTableHeader }}px; padding: 4px 3px;">max</td>
            <td style="width: 16%; background-color: {{ $primary }}; color: {{ $tableHeaderText }}; font-weight: 800; text-align: center; font-size: {{ $fontTableHeader }}px; padding: 4px 3px;">Nom et prénoms<br>enseignant</td>
        </tr>
    </thead>
    <tbody>
        @foreach($resultats_ues as $resUE)
            @php
                $ue = $resUE->uniteEnseignement;
                $ecues = $resUE->resultatsECUEs;
            @endphp
            <tr class="ue-row">
                <td>{{ $ue->code_affiche ?? '' }}</td>
                <td>{{ $ue->name ?? '' }}</td>
                <td class="num">{{ $resUE->moyenne !== null ? number_format($resUE->moyenne, 2) : '' }}</td>
                <td class="num">{{ $resUE->statut }}</td>
                <td class="num">{{ $resUE->mention }}</td>
                <td class="num">{{ $resUE->credit }}</td>
                <td class="num" style="font-weight: 400;">{{ $resUE->stat_min !== null ? number_format($resUE->stat_min, 2) : '' }}</td>
                <td class="num" style="font-weight: 400;">{{ $resUE->stat_moy !== null ? number_format($resUE->stat_moy, 2) : '' }}</td>
                <td class="num" style="font-weight: 400;">{{ $resUE->stat_max !== null ? number_format($resUE->stat_max, 2) : '' }}</td>
                <td></td>
            </tr>

            @foreach($ecues as $resECUE)
                @php $mat = $resECUE->matiere; @endphp
                <tr>
                    <td>{{ $mat->code_affiche ?? '' }}</td>
                    <td>{{ $mat->name ?? '' }}</td>
                    <td class="num">{{ $resECUE->moyenne !== null ? number_format($resECUE->moyenne, 2) : '' }}</td>
                    <td class="num"></td>
                    <td class="num"></td>
                    <td class="num">{{ $resECUE->credit > 0 ? $resECUE->credit : '' }}</td>
                    <td class="num">{{ $resECUE->stat_min !== null ? number_format($resECUE->stat_min, 2) : '' }}</td>
                    <td class="num">{{ $resECUE->stat_moy !== null ? number_format($resECUE->stat_moy, 2) : '' }}</td>
                    <td class="num">{{ $resECUE->stat_max !== null ? number_format($resECUE->stat_max, 2) : '' }}</td>
                    <td style="font-size: {{ $fontTeacher }}px;">{{ $resECUE->enseignant_affiche }}</td>
                </tr>
            @endforeach
        @endforeach
    </tbody>
</table>

<table class="summary-table keep-together" border="0" cellspacing="0" cellpadding="0">
    <tr>
        <td width="55%" style="padding-right: 6px;">
            <div class="moyenne-box">
                <span class="summary-label">MOYENNE GENERALE</span>&nbsp;&nbsp;
                <span class="summary-value">{{ $moyenne_generale !== null ? number_format($moyenne_generale, 2) : '--' }}</span>
            </div>
        </td>
        <td width="45%" style="padding-left: 0;">
            <div class="credits-box">
                <span class="summary-label">Crédits capitalisés</span>&nbsp;&nbsp;
                <span class="summary-value" style="font-size: {{ min(32, $fontSummary + 1) }}px;">{{ $credits_capitalises }} / {{ $credits_totaux }}</span>
            </div>
        </td>
    </tr>
</table>

<div class="decision-box">
    <span class="decision-label">Décision lors de la délibération :</span>
    <span class="decision-value" style="color: {{ $primary }};">{{ $decision ?? $bulletin->decision_deliberation ?? '' }}</span>
</div>

<div class="notice">
    <strong>Très important :</strong> {{ $noticeText }}
</div>

<table class="closing-grid" border="0" cellspacing="0" cellpadding="0">
    <tr>
        <td width="62%">
            <div class="legend">
                <strong>UE:</strong> Unité d'Enseignement -
                <strong>ECUE:</strong> Elément Constitutif de l'Unité d'Enseignement -
                <strong>CECT:</strong> Crédit d'Evaluation Capitalisable et Transférable -
                <strong>AQ:</strong> Acquis -
                <strong>NAQ:</strong> Non Acquis -
                <strong>APC:</strong> Acquis Par Compensation -
                <strong>Moy:</strong> Moyenne -
                <strong>TB:</strong> Très bien -
                <strong>B:</strong> Bien -
                <strong>AB:</strong> Assez Bien -
                <strong>P:</strong> Passable -
                <strong>INS:</strong> Insuffisant -
                <strong>F:</strong> Faible
                @if($lmdAppreciationLegend)
                    <br><strong>Barème des appréciations :</strong> {{ $lmdAppreciationLegend }}
                @endif
            </div>
        </td>
        <td width="38%">
            <div class="signature-block">
                <div class="signature-title">Le Directeur des Études</div>
                <div class="signature-space">&nbsp;</div>
                <div class="signature-name">{{ $etab['directeur'] ?? '' }}</div>
            </div>
        </td>
    </tr>
</table>

<div class="bottom-note">
    <span class="bottom-note-line">{{ $bottomText }}</span>
    <span class="bottom-note-line">{{ $etab['nom'] ?? 'KLASSCI' }}, Etablissement {{ mb_strtolower($statutEtablissement, 'UTF-8') }}, {{ $paysEtablissement }}</span>
</div>
<div style="text-align: center; font-size: {{ min(32, $fontBottom + .5) }}px; font-weight: 800; margin-top: 3px; page-break-inside: avoid;">
    {{ \App\Services\BulletinMentionResolver::authenticityText() }}
</div>

</div>
</body>
</html>

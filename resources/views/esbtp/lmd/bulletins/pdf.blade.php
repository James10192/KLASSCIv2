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
        $editionDate = ($bulletin->updated_at ?? now())->format('d/m/Y');
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

        .official-band {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            border: 1px solid #cbd5e1;
            border-radius: 5px;
            margin: 0 0 6px;
        }
        .official-band td {
            width: 33.333%;
            padding: 5px 8px;
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

        .identity-grid { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        .identity-grid > tbody > tr > td { vertical-align: top; }
        .identity-grid > tbody > tr > td:first-child { padding-right: 13px; }
        .identity-grid > tbody > tr > td:last-child { padding-left: 13px; border-left: 1px solid #e2e8f0; }
        .identity-grid table td { padding: 2.3px 0; line-height: 1.23; }

        .bulletin-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
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
            text-align: center;
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
            text-align: right;
            font-size: {{ $fontSignature }}px;
            line-height: 1.35;
        }
        .bottom-note {
            text-align: center;
            font-size: {{ $fontBottom }}px;
            color: {{ $secondary }};
            margin-top: 5px;
            line-height: 1.32;
            page-break-inside: avoid;
        }
    </style>
</head>
<body>
<div class="container">

@if(($bCfg['show_republic_info'] ?? true) || ($bCfg['show_ministry_info'] ?? true))
<table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-bottom: 4px;">
    <tr>
        <td style="text-align: center; font-size: {{ $fontRepublic }}px; color: {{ $bodyText }}; line-height: 1.32;">
            @if($bCfg['show_republic_info'] ?? true)
                <div style="font-weight: 800; font-size: {{ max(6, $fontRepublic + .5) }}px;">{{ $bCfg['republic_text'] ?? 'REPUBLIQUE DE COTE D\'IVOIRE' }}</div>
                <div style="font-size: {{ max(6, $fontRepublic - 1) }}px; font-style: italic; color: {{ $secondary }};">{{ $bCfg['union_text'] ?? 'Union - Discipline - Travail' }}</div>
            @endif
            @if($bCfg['show_ministry_info'] ?? true)
                <div style="font-size: {{ $fontRepublic }}px; margin-top: 1px;">{{ $bCfg['ministry_text'] ?? 'MINISTERE DE L\'ENSEIGNEMENT SUPERIEUR ET DE LA RECHERCHE SCIENTIFIQUE' }}</div>
            @endif
        </td>
    </tr>
</table>
@endif

<table width="100%" border="0" cellspacing="0" cellpadding="0" style="border-radius: 6px; overflow: hidden; margin-bottom: 6px; page-break-inside: avoid;">
    <tr>
        <td width="16%" style="background-color: {{ $hdrBg }}; padding: 10px 8px; text-align: center; vertical-align: middle; border-right: 1px solid rgba(255,255,255,0.25);">
            @if(isset($logoBase64) && $logoBase64)
                <img src="{{ $logoBase64 }}" style="max-height: 54px; max-width: 94px;" alt="Logo">
            @else
                <div style="font-size: 28px; font-weight: 900; color: {{ $hdrText }}; opacity: 0.4; letter-spacing: -2px;">K</div>
            @endif
        </td>
        <td width="84%" style="background-color: {{ $hdrBg }}; padding: 8px 13px; vertical-align: middle;">
            <div style="font-size: {{ $fontSchoolName }}px; font-weight: 800; color: {{ $hdrText }}; margin-bottom: 1px;">
                {{ $etab['nom'] ?? 'KLASSCI' }}
            </div>
            @if(($etab['adresse'] ?? '') || ($etab['telephone'] ?? '') || ($etab['email'] ?? ''))
            <div style="font-size: {{ $fontSchoolMeta }}px; color: {{ $hdrText }}; opacity: 0.86; margin-bottom: 5px;">
                @if($etab['adresse'] ?? ''){{ $etab['adresse'] }}@endif
                @if($etab['telephone'] ?? '')
                    @if($etab['adresse'] ?? '') &nbsp;|&nbsp; @endif
                    Tél: {{ $etab['telephone'] }}
                @endif
                @if($etab['email'] ?? '')
                    @if(($etab['adresse'] ?? '') || ($etab['telephone'] ?? '')) &nbsp;|&nbsp; @endif
                    {{ $etab['email'] }}
                @endif
            </div>
            @endif
            <div style="border-top: 1px solid rgba(255,255,255,0.35); padding-top: 5px;">
                <div style="font-size: {{ $fontTitle }}px; font-weight: 800; color: {{ $hdrText }}; letter-spacing: 0.35px; margin-bottom: 2px;">
                    BULLETIN SEMESTRIEL DE NOTES — {{ $semestre }}{{ $semestre == 1 ? 'er' : 'ème' }} semestre
                </div>
                <table width="100%" border="0" cellspacing="0" cellpadding="0">
                    <tr>
                        <td width="40%" style="font-size: {{ $fontHeaderMeta }}px; color: {{ $hdrText }};">
                            <span style="opacity: 0.75;">Année universitaire :</span>
                            <strong>{{ $anneeLabel }}</strong>
                        </td>
                        <td width="30%" style="font-size: {{ $fontHeaderMeta }}px; color: {{ $hdrText }}; text-align: center;">
                            <span style="opacity: 0.75;">Niveau :</span>
                            <strong>{{ $niveau ?? '' }}</strong>
                        </td>
                        <td width="30%" style="font-size: {{ $fontHeaderMeta }}px; color: {{ $hdrText }}; text-align: right;">
                            <span style="opacity: 0.75;">Semestre :</span>
                            <strong>{{ $semestre ?? '' }}</strong>
                        </td>
                    </tr>
                </table>
            </div>
        </td>
    </tr>
</table>

@if($bCfg['show_etablissement_box'] ?? true)
<table class="official-band" cellspacing="0" cellpadding="0">
    <tr>
        <td>
            <span class="official-label">Code établissement</span>
            <span class="official-value">{{ $codeEtablissement !== '' ? $codeEtablissement : '—' }}</span>
        </td>
        <td>
            <span class="official-label">Statut</span>
            <span class="official-value">{{ $statutEtablissement }}</span>
        </td>
        <td>
            <span class="official-label">Direction</span>
            <span class="official-value">{{ $directionEtablissement !== '' ? $directionEtablissement : '—' }}</span>
        </td>
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
                <div style="font-weight: 800;">
                    Nom / Signature et cachet du chef<br>d'Etablissement
                </div>
                <div style="margin-top: 4px;">
                    {{ $etab['ville'] ?? 'Abidjan' }}, le {{ $editionDate }}
                </div>
                <div style="margin-top: 2px;">Le Directeur des Etudes</div>
                <div style="font-size: {{ min(32, $fontSignature + 1) }}px; font-weight: 800; margin-top: 14px;">{{ $etab['directeur'] ?? '' }}</div>
            </div>
        </td>
    </tr>
</table>

<div class="bottom-note">
    {{ $bottomText }}<br>
    {{ $etab['nom'] ?? 'KLASSCI' }}, Etablissement {{ mb_strtolower($statutEtablissement, 'UTF-8') }}, Côte d'Ivoire
</div>
<div style="text-align: center; font-size: {{ min(32, $fontBottom + .5) }}px; font-weight: 800; margin-top: 3px; page-break-inside: avoid;">
    {{ \App\Services\BulletinMentionResolver::authenticityText() }}
</div>

</div>
</body>
</html>

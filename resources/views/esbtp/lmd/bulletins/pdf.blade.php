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
        $hdrText = $pdfCfg['header_text_on_bg'] ?? $pdfCfg['header_text_color'] ?? '#ffffff';
        $primary = $pdfCfg['primary_color'] ?? '#0453cb';
        $tableHeaderText = $pdfCfg['header_text_on_primary']
            ?? \App\Helpers\SettingsHelper::contrastingText($primary, $pdfCfg['header_text_color_raw'] ?? '#ffffff');
        $bodyText = $pdfCfg['text_color'] ?? '#1f2937';
        $etab = $etablissement ?? [];
        $bCfg = $bulletinCfg ?? [];

        $font = static function (string $key, float $default): float {
            $raw = \App\Helpers\SettingsHelper::get($key, $default);
            $value = is_numeric($raw) ? (float) $raw : $default;
            return max(6, min(24, $value));
        };
        $fontRepublic = $font('lmd_bulletin_font_republic', 8.5);
        $fontSchoolName = $font('lmd_bulletin_font_school_name', 13);
        $fontSchoolMeta = $font('lmd_bulletin_font_school_meta', 7.5);
        $fontTitle = $font('lmd_bulletin_font_title', 12);
        $fontHeaderMeta = $font('lmd_bulletin_font_header_meta', 8);
        $fontEstablishment = $font('lmd_bulletin_font_establishment', 8.5);
        $fontStudent = $font('lmd_bulletin_font_student', 9.5);
        $fontStructure = $font('lmd_bulletin_font_structure', 9);
        $fontTableHeader = $font('lmd_bulletin_font_table_header', 8);
        $fontTable = $font('lmd_bulletin_font_table', 8.5);
        $fontTeacher = $font('lmd_bulletin_font_teacher', 7.5);
        $fontSummary = $font('lmd_bulletin_font_summary', 12);
        $fontDecision = $font('lmd_bulletin_font_decision', 10);
        $fontNotice = $font('lmd_bulletin_font_notice', 8);
        $fontSignature = $font('lmd_bulletin_font_signature', 9);
        $fontLegend = $font('lmd_bulletin_font_legend', 7.5);
        $fontBottom = $font('lmd_bulletin_font_bottom', 8);

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
        $editionDate = ($bulletin->updated_at ?? now())->format('d/m/Y');
    @endphp

    <style>
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: {{ $fontTable }}px;
            margin: 0;
            padding: 8px;
            color: {{ $bodyText }};
            line-height: 1.35;
            background: #ffffff;
        }
        .container { max-width: 100%; background: white; padding: 10px; }
        @page { margin: 0.5cm; size: A4 portrait; }

        .bulletin-table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        .bulletin-table td,
        .bulletin-table th {
            border: 1px solid {{ $bodyText }};
            padding: 3px 4px;
            font-size: {{ $fontTable }}px;
            vertical-align: middle;
        }
        .num { text-align: center; }

        .moyenne-box {
            border: 2px solid {{ $bodyText }};
            padding: 6px 12px;
            text-align: center;
            font-weight: bold;
            font-size: {{ $fontSummary }}px;
        }
        .credits-box {
            border: 2px solid {{ $bodyText }};
            padding: 6px 12px;
            text-align: center;
            font-weight: bold;
            font-size: {{ max(6, $fontSummary - 1) }}px;
        }
        .decision-box {
            border: 1px solid {{ $bodyText }};
            padding: 8px 12px;
            margin-top: 8px;
            text-align: center;
            font-size: {{ $fontDecision }}px;
        }
        .decision-label { font-size: {{ max(6, $fontDecision - 1) }}px; font-weight: bold; text-decoration: underline; }
        .decision-value { font-size: {{ $fontDecision }}px; font-weight: bold; margin-left: 20px; }
        .notice {
            border: 1.5px solid {{ $bodyText }};
            padding: 6px 10px;
            margin-top: 8px;
            font-size: {{ $fontNotice }}px;
        }
        .legend {
            margin-top: 10px;
            border-top: 1px solid #d1d5db;
            padding-top: 6px;
            font-size: {{ $fontLegend }}px;
            color: {{ $pdfCfg['secondary_color'] ?? '#6b7280' }};
        }
    </style>
</head>
<body>
<div class="container">

@if(($bCfg['show_republic_info'] ?? true) || ($bCfg['show_ministry_info'] ?? true))
<table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-bottom: 6px;">
    <tr>
        <td style="text-align: center; font-size: {{ $fontRepublic }}px; color: {{ $bodyText }}; line-height: 1.4;">
            @if($bCfg['show_republic_info'] ?? true)
                <div style="font-weight: bold; font-size: {{ max(6, $fontRepublic + .5) }}px;">{{ $bCfg['republic_text'] ?? 'REPUBLIQUE DE COTE D\'IVOIRE' }}</div>
                <div style="font-size: {{ max(6, $fontRepublic - 1) }}px; font-style: italic; color: {{ $pdfCfg['secondary_color'] ?? '#6b7280' }};">{{ $bCfg['union_text'] ?? 'Union - Discipline - Travail' }}</div>
            @endif
            @if($bCfg['show_ministry_info'] ?? true)
                <div style="font-size: {{ $fontRepublic }}px; margin-top: 2px;">{{ $bCfg['ministry_text'] ?? 'MINISTERE DE L\'ENSEIGNEMENT SUPERIEUR ET DE LA RECHERCHE SCIENTIFIQUE' }}</div>
            @endif
        </td>
    </tr>
</table>
@endif

<table width="100%" border="0" cellspacing="0" cellpadding="0" style="border-radius: 6px; overflow: hidden; margin-bottom: 8px;">
    <tr>
        <td width="16%" style="background-color: {{ $hdrBg }}; padding: 12px 8px; text-align: center; vertical-align: middle; border-right: 2px solid rgba(255,255,255,0.25);">
            @if(isset($logoBase64) && $logoBase64)
                <img src="{{ $logoBase64 }}" style="max-height: 50px; max-width: 90px;" alt="Logo">
            @else
                <div style="font-size: 26px; font-weight: 900; color: {{ $hdrText }}; opacity: 0.4; letter-spacing: -2px;">K</div>
            @endif
        </td>
        <td width="84%" style="background-color: {{ $hdrBg }}; padding: 10px 14px; vertical-align: middle;">
            <div style="font-size: {{ $fontSchoolName }}px; font-weight: 700; color: {{ $hdrText }}; margin-bottom: 1px;">
                {{ $etab['nom'] ?? 'KLASSCI' }}
            </div>
            @if(($etab['adresse'] ?? '') || ($etab['telephone'] ?? '') || ($etab['email'] ?? ''))
            <div style="font-size: {{ $fontSchoolMeta }}px; color: {{ $hdrText }}; opacity: 0.85; margin-bottom: 6px;">
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
            <div style="border-top: 1px solid rgba(255,255,255,0.35); padding-top: 6px;">
                <div style="font-size: {{ $fontTitle }}px; font-weight: 700; color: {{ $hdrText }}; letter-spacing: 0.5px; margin-bottom: 3px;">
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
<table width="100%" cellspacing="0" cellpadding="0" style="margin-bottom: 6px;">
    <tr>
        <td width="33%" style="padding: 2px 0; font-size: {{ $fontEstablishment }}px;">
            <strong>Code :</strong> {{ $bCfg['code_etablissement'] ?? '' }}
        </td>
        <td width="34%" style="padding: 2px 0; font-size: {{ $fontEstablishment }}px; text-align: center;">
            <strong>Statut :</strong> {{ $statutEtablissement }}
        </td>
        <td width="33%" style="padding: 2px 0; font-size: {{ $fontEstablishment }}px; text-align: right;">
            <strong>Direction :</strong> {{ $bCfg['direction'] ?? $etab['directeur'] ?? '' }}
        </td>
    </tr>
</table>
@endif

<table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-bottom: 6px;">
    <tr>
        <td width="55%" style="vertical-align: top;">
            <table width="100%" cellspacing="0" cellpadding="2">
                <tr>
                    <td width="25%" style="font-size: {{ $fontStudent }}px; font-weight: bold;">NOM :</td>
                    <td style="font-size: {{ $fontStudent }}px;">{{ mb_strtoupper($etudiant->nom ?? '', 'UTF-8') }}</td>
                </tr>
                <tr>
                    <td style="font-size: {{ $fontStudent }}px; font-weight: bold;">PRENOMS :</td>
                    <td style="font-size: {{ $fontStudent }}px;">{{ mb_strtoupper($etudiant->prenoms ?? '', 'UTF-8') }}</td>
                </tr>
                <tr>
                    <td style="font-size: {{ $fontStudent }}px; font-weight: bold;">DATE NAISS. :</td>
                    <td style="font-size: {{ $fontStudent }}px;">{{ $etudiant->date_naissance ? \Carbon\Carbon::parse($etudiant->date_naissance)->format('d/m/Y') : '' }}</td>
                </tr>
                <tr>
                    <td style="font-size: {{ $fontStudent }}px; font-weight: bold;">MATRICULE :</td>
                    <td style="font-size: {{ $fontStudent }}px;">{{ $etudiant->matricule ?? '' }}</td>
                </tr>
                <tr>
                    <td style="font-size: {{ $fontStudent }}px; font-weight: bold;">AFFECTATION :</td>
                    <td style="font-size: {{ $fontStudent }}px;">{{ $bulletin->affectation_label }}</td>
                </tr>
            </table>
        </td>
        <td width="45%" style="vertical-align: top;">
            <table width="100%" cellspacing="0" cellpadding="2">
                @if(isset($bulletin_fields))
                    @foreach($bulletin_fields as $field)
                        @if($field['show'] && $field['value'])
                        <tr>
                            <td width="40%" style="font-size: {{ $fontStructure }}px; font-weight: bold;">{{ $field['label'] }} :</td>
                            <td style="font-size: {{ $fontStructure }}px;">{{ $field['value'] }}</td>
                        </tr>
                        @endif
                    @endforeach
                @else
                    <tr>
                        <td width="40%" style="font-size: {{ $fontStructure }}px; font-weight: bold;">{{ mb_strtoupper($vocabulaire->rang('domaine'), 'UTF-8') }} :</td>
                        <td style="font-size: {{ $fontStructure }}px;">{{ $domaine ?? '' }}</td>
                    </tr>
                    <tr>
                        <td style="font-size: {{ $fontStructure }}px; font-weight: bold;">{{ mb_strtoupper($vocabulaire->rang('mention'), 'UTF-8') }} :</td>
                        <td style="font-size: {{ $fontStructure }}px;">{{ $mention ?? '' }}</td>
                    </tr>
                    <tr>
                        <td style="font-size: {{ $fontStructure }}px; font-weight: bold;">{{ mb_strtoupper($vocabulaire->rang('parcours'), 'UTF-8') }} :</td>
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
            <td style="width: 10%; background-color: {{ $primary }}; color: {{ $tableHeaderText }}; font-weight: bold; text-align: center; font-size: {{ $fontTableHeader }}px; padding: 4px 3px;">Code</td>
            <td style="width: 28%; background-color: {{ $primary }}; color: {{ $tableHeaderText }}; font-weight: bold; text-align: center; font-size: {{ $fontTableHeader }}px; padding: 4px 3px;">Intitulés</td>
            <td style="width: 7%; background-color: {{ $primary }}; color: {{ $tableHeaderText }}; font-weight: bold; text-align: center; font-size: {{ $fontTableHeader }}px; padding: 4px 3px;">Moy /<br>20</td>
            <td style="width: 8%; background-color: {{ $primary }}; color: {{ $tableHeaderText }}; font-weight: bold; text-align: center; font-size: {{ $fontTableHeader }}px; padding: 4px 3px;">AQ,APC,<br>NAQ</td>
            <td style="width: 5%; background-color: {{ $primary }}; color: {{ $tableHeaderText }}; font-weight: bold; text-align: center; font-size: {{ $fontTableHeader }}px; padding: 4px 3px;">Appr.</td>
            <td style="width: 5%; background-color: {{ $primary }}; color: {{ $tableHeaderText }}; font-weight: bold; text-align: center; font-size: {{ $fontTableHeader }}px; padding: 4px 3px;">CECT</td>
            <td style="width: 7%; background-color: {{ $primary }}; color: {{ $tableHeaderText }}; font-weight: bold; text-align: center; font-size: {{ $fontTableHeader }}px; padding: 4px 3px;">min</td>
            <td style="width: 7%; background-color: {{ $primary }}; color: {{ $tableHeaderText }}; font-weight: bold; text-align: center; font-size: {{ $fontTableHeader }}px; padding: 4px 3px;">moy</td>
            <td style="width: 7%; background-color: {{ $primary }}; color: {{ $tableHeaderText }}; font-weight: bold; text-align: center; font-size: {{ $fontTableHeader }}px; padding: 4px 3px;">max</td>
            <td style="width: 16%; background-color: {{ $primary }}; color: {{ $tableHeaderText }}; font-weight: bold; text-align: center; font-size: {{ $fontTableHeader }}px; padding: 4px 3px;">Nom et prénoms<br>enseignant</td>
        </tr>
    </thead>
    <tbody>
        @foreach($resultats_ues as $resUE)
            @php
                $ue = $resUE->uniteEnseignement;
                $ecues = $resUE->resultatsECUEs;
            @endphp
            <tr>
                <td style="background-color: #f3f4f6; font-weight: bold; font-size: {{ $fontTable }}px;">{{ $ue->code_affiche ?? '' }}</td>
                <td style="background-color: #f3f4f6; font-weight: bold; font-size: {{ $fontTable }}px;">{{ $ue->name ?? '' }}</td>
                <td class="num" style="background-color: #f3f4f6; font-weight: bold;">{{ $resUE->moyenne !== null ? number_format($resUE->moyenne, 2) : '' }}</td>
                <td class="num" style="background-color: #f3f4f6; font-weight: bold;">{{ $resUE->statut }}</td>
                <td class="num" style="background-color: #f3f4f6; font-weight: bold;">{{ $resUE->mention }}</td>
                <td class="num" style="background-color: #f3f4f6; font-weight: bold;">{{ $resUE->credit }}</td>
                <td class="num" style="background-color: #f3f4f6;">{{ $resUE->stat_min !== null ? number_format($resUE->stat_min, 2) : '' }}</td>
                <td class="num" style="background-color: #f3f4f6;">{{ $resUE->stat_moy !== null ? number_format($resUE->stat_moy, 2) : '' }}</td>
                <td class="num" style="background-color: #f3f4f6;">{{ $resUE->stat_max !== null ? number_format($resUE->stat_max, 2) : '' }}</td>
                <td style="background-color: #f3f4f6;"></td>
            </tr>

            @foreach($ecues as $resECUE)
                @php $mat = $resECUE->matiere; @endphp
                <tr>
                    <td style="font-size: {{ $fontTable }}px;">{{ $mat->code_affiche ?? '' }}</td>
                    <td style="font-size: {{ $fontTable }}px;">{{ $mat->name ?? '' }}</td>
                    <td class="num" style="font-size: {{ $fontTable }}px;">{{ $resECUE->moyenne !== null ? number_format($resECUE->moyenne, 2) : '' }}</td>
                    <td class="num"></td>
                    <td class="num"></td>
                    <td class="num" style="font-size: {{ $fontTable }}px;">{{ $resECUE->credit > 0 ? $resECUE->credit : '' }}</td>
                    <td class="num" style="font-size: {{ $fontTable }}px;">{{ $resECUE->stat_min !== null ? number_format($resECUE->stat_min, 2) : '' }}</td>
                    <td class="num" style="font-size: {{ $fontTable }}px;">{{ $resECUE->stat_moy !== null ? number_format($resECUE->stat_moy, 2) : '' }}</td>
                    <td class="num" style="font-size: {{ $fontTable }}px;">{{ $resECUE->stat_max !== null ? number_format($resECUE->stat_max, 2) : '' }}</td>
                    <td style="font-size: {{ $fontTeacher }}px;">{{ $resECUE->enseignant_affiche }}</td>
                </tr>
            @endforeach
        @endforeach
    </tbody>
</table>

<table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-top: 10px;">
    <tr>
        <td width="55%" style="vertical-align: middle; padding-right: 10px;">
            <table width="100%" cellspacing="0" cellpadding="0">
                <tr>
                    <td class="moyenne-box">
                        MOYENNE GENERALE &nbsp;&nbsp;
                        <span style="font-size: {{ min(24, $fontSummary + 2) }}px; color: {{ $primary }};">{{ $moyenne_generale !== null ? number_format($moyenne_generale, 2) : '--' }}</span>
                    </td>
                </tr>
            </table>
        </td>
        <td width="45%" style="vertical-align: middle;">
            <table width="100%" cellspacing="0" cellpadding="0">
                <tr>
                    <td class="credits-box">
                        Crédits capitalisés &nbsp;&nbsp;
                        <span style="font-size: {{ min(24, $fontSummary + 1) }}px; color: {{ $primary }};">{{ $credits_capitalises }} / {{ $credits_totaux }}</span>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<div class="decision-box">
    <span class="decision-label">Décision lors de la délibération :</span>
    <span class="decision-value" style="color: {{ $primary }};">{{ $decision ?? $bulletin->decision_deliberation ?? '' }}</span>
</div>

<div class="notice">
    <strong>Très important:</strong> {{ $noticeText }}
</div>

<table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-top: 12px;">
    <tr>
        <td width="55%"></td>
        <td width="45%" style="text-align: right; font-size: {{ $fontSignature }}px;">
            <div style="font-size: {{ $fontSignature }}px; font-weight: bold;">
                Nom / Signature et cachet du chef<br>d'Etablissement
            </div>
            <div style="font-size: {{ $fontSignature }}px; margin-top: 4px;">
                {{ $etab['ville'] ?? 'Abidjan' }}, le {{ $editionDate }}
            </div>
            <div style="font-size: {{ $fontSignature }}px; margin-top: 2px;">Le Directeur des Etudes</div>
            <div style="font-size: {{ min(24, $fontSignature + 1) }}px; font-weight: bold; margin-top: 16px;">{{ $etab['directeur'] ?? '' }}</div>
        </td>
    </tr>
</table>

<div class="legend">
    <strong>UE:</strong> Unité d'Enseignement -
    <strong>ECUE:</strong> Elément Constitutif de l'Unité d'Enseignement –
    <strong>CECT:</strong> Crédit d'Evaluation Capitalisable et Transférable -
    <strong>AQ:</strong> Acquis -
    <strong>NAQ:</strong> Non Acquis –
    <strong>APC:</strong> Acquis Par Compensation –
    <strong>Moy:</strong> Moyenne –
    <strong>TB:</strong> Très bien –
    <strong>B:</strong> Bien –
    <strong>AB:</strong> Assez Bien –
    <strong>P:</strong> Passable –
    <strong>INS:</strong> Insuffisant –
    <strong>F:</strong> Faible
    @if($lmdAppreciationLegend)
        <br><strong>Barème des appréciations :</strong> {{ $lmdAppreciationLegend }}
    @endif
</div>

<div style="text-align: center; font-size: {{ $fontBottom }}px; color: {{ $pdfCfg['secondary_color'] ?? '#6b7280' }}; margin-top: 6px;">
    {{ $bottomText }}<br>
    {{ $etab['nom'] ?? 'KLASSCI' }}, Etablissement {{ mb_strtolower($statutEtablissement, 'UTF-8') }}, Côte d'Ivoire
</div>
<div style="text-align: center; font-size: {{ min(24, $fontBottom + .5) }}px; font-weight: bold; margin-top: 4px;">
    {{ \App\Services\BulletinMentionResolver::authenticityText() }}
</div>

</div>
</body>
</html>

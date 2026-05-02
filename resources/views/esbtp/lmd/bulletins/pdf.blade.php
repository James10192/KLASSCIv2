<!DOCTYPE html>
<html lang="fr">
<head>
    @include('pdf.partials.theme')
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Bulletin LMD - {{ $etudiant->nom ?? '' }} {{ $etudiant->prenoms ?? '' }}</title>
    <style>
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 9px;
            margin: 0;
            padding: 8px;
            color: #1f2937;
            line-height: 1.35;
            background: #ffffff;
        }

        .container {
            max-width: 100%;
            background: white;
            padding: 10px;
        }

        @page {
            margin: 0.5cm;
            size: A4 portrait;
        }

        /* ── Main table ── */
        .bulletin-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }

        .bulletin-table td,
        .bulletin-table th {
            border: 1px solid #1f2937;
            padding: 3px 4px;
            font-size: 8.5px;
            vertical-align: middle;
        }

        /* Numeric cells */
        .num { text-align: center; }

        /* ── Footer section ── */
        .moyenne-box {
            border: 2px solid #1f2937;
            padding: 6px 12px;
            text-align: center;
            font-weight: bold;
            font-size: 12px;
        }
        .credits-box {
            border: 2px solid #1f2937;
            padding: 6px 12px;
            text-align: center;
            font-weight: bold;
            font-size: 11px;
        }
        .decision-box {
            border: 1px solid #1f2937;
            padding: 8px 12px;
            margin-top: 8px;
            text-align: center;
        }
        .decision-label { font-size: 9px; font-weight: bold; text-decoration: underline; }
        .decision-value { font-size: 10px; font-weight: bold; margin-left: 20px; }
        .notice {
            border: 1.5px solid #1f2937;
            padding: 6px 10px;
            margin-top: 8px;
            font-size: 8px;
        }
        .legend {
            margin-top: 10px;
            border-top: 1px solid #d1d5db;
            padding-top: 6px;
            font-size: 7.5px;
            color: #6b7280;
        }
    </style>
</head>
<body>
<div class="container">

@php
    $hdrBg   = $pdfCfg['header_bg_color']  ?? $pdfCfg['primary_color'] ?? '#0453cb';
    $hdrText = $pdfCfg['header_text_color'] ?? '#ffffff';
    $primary = $pdfCfg['primary_color']     ?? '#0453cb';
    $etab    = $etablissement ?? [];
    $bCfg    = $bulletinCfg ?? [];
@endphp

{{-- ═══════════════════════════════════════════════════════
     BARRE REPUBLIQUE / MINISTERE (configurable)
     ═══════════════════════════════════════════════════════ --}}
@if(($bCfg['show_republic_info'] ?? true) || ($bCfg['show_ministry_info'] ?? true))
<table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-bottom: 6px;">
    <tr>
        <td style="text-align: center; font-size: 8.5px; color: #374151; line-height: 1.4;">
            @if($bCfg['show_republic_info'] ?? true)
                <div style="font-weight: bold; font-size: 9px;">{{ $bCfg['republic_text'] ?? 'REPUBLIQUE DE COTE D\'IVOIRE' }}</div>
                <div style="font-size: 7.5px; font-style: italic; color: #6b7280;">{{ $bCfg['union_text'] ?? 'Union - Discipline - Travail' }}</div>
            @endif
            @if($bCfg['show_ministry_info'] ?? true)
                <div style="font-size: 8px; margin-top: 2px;">{{ $bCfg['ministry_text'] ?? 'MINISTERE DE L\'ENSEIGNEMENT SUPERIEUR ET DE LA RECHERCHE SCIENTIFIQUE' }}</div>
            @endif
        </td>
    </tr>
</table>
@endif

{{-- ═══════════════════════════════════════════════════════
     HEADER — Logo | Nom école + contact + titre bulletin
     (même pattern que liste-complete-pdf)
     ═══════════════════════════════════════════════════════ --}}
<table width="100%" border="0" cellspacing="0" cellpadding="0" style="border-radius: 6px; overflow: hidden; margin-bottom: 8px;">
    <tr>
        {{-- Colonne gauche : Logo --}}
        <td width="16%" style="background-color: {{ $hdrBg }}; padding: 12px 8px; text-align: center; vertical-align: middle; border-right: 2px solid rgba(255,255,255,0.25);">
            @if(isset($logoBase64) && $logoBase64)
                <img src="{{ $logoBase64 }}" style="max-height: 50px; max-width: 90px;" alt="Logo">
            @else
                <div style="font-size: 26px; font-weight: 900; color: {{ $hdrText }}; opacity: 0.4; letter-spacing: -2px;">K</div>
            @endif
        </td>
        {{-- Colonne droite : Nom école + contact + titre bulletin --}}
        <td width="84%" style="background-color: {{ $hdrBg }}; padding: 10px 14px; vertical-align: middle;">
            {{-- Nom établissement --}}
            <div style="font-size: 13px; font-weight: 700; color: {{ $hdrText }}; margin-bottom: 1px;">
                {{ $etab['nom'] ?? 'KLASSCI' }}
            </div>
            {{-- Adresse | Tél | Email --}}
            @if(($etab['adresse'] ?? '') || ($etab['telephone'] ?? '') || ($etab['email'] ?? ''))
            <div style="font-size: 7.5px; color: {{ $hdrText }}; opacity: 0.85; margin-bottom: 6px;">
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
            {{-- Séparateur + titre document --}}
            <div style="border-top: 1px solid rgba(255,255,255,0.35); padding-top: 6px;">
                <div style="font-size: 12px; font-weight: 700; color: {{ $hdrText }}; letter-spacing: 0.5px; margin-bottom: 3px;">
                    BULLETIN SEMESTRIEL DE NOTES — {{ $semestre }}{{ $semestre == 1 ? 'er' : 'ème' }} semestre
                </div>
                <table width="100%" border="0" cellspacing="0" cellpadding="0">
                    <tr>
                        <td width="40%" style="font-size: 8px; color: {{ $hdrText }};">
                            <span style="opacity: 0.75;">Année scolaire :</span>
                            <strong>{{ $annee->name ?? '' }}</strong>
                        </td>
                        <td width="30%" style="font-size: 8px; color: {{ $hdrText }}; text-align: center;">
                            <span style="opacity: 0.75;">Niveau :</span>
                            <strong>{{ $niveau ?? '' }}</strong>
                        </td>
                        <td width="30%" style="font-size: 8px; color: {{ $hdrText }}; text-align: right;">
                            <span style="opacity: 0.75;">Semestre :</span>
                            <strong>{{ $semestre ?? '' }}</strong>
                        </td>
                    </tr>
                </table>
            </div>
        </td>
    </tr>
</table>

{{-- ═══════════════════════════════════════════════════════
     ENCADRÉ ÉTABLISSEMENT — Code / Statut / Direction
     ═══════════════════════════════════════════════════════ --}}
@if($bCfg['show_etablissement_box'] ?? true)
<table width="100%" cellspacing="0" cellpadding="0" style="margin-bottom: 6px;">
    <tr>
        <td width="33%" style="padding: 2px 0; font-size: 8.5px;">
            <strong>Code :</strong> {{ $bCfg['code_etablissement'] ?? '' }}
        </td>
        <td width="34%" style="padding: 2px 0; font-size: 8.5px; text-align: center;">
            <strong>Statut :</strong> {{ $bCfg['statut'] ?? 'Privé' }}
        </td>
        <td width="33%" style="padding: 2px 0; font-size: 8.5px; text-align: right;">
            <strong>Direction :</strong> {{ $bCfg['direction'] ?? $etab['directeur'] ?? '' }}
        </td>
    </tr>
</table>
@endif

{{-- ═══════════════════════════════════════════════════════
     STUDENT INFO — 2 colonnes
     ═══════════════════════════════════════════════════════ --}}
<table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-bottom: 6px;">
    <tr>
        {{-- Gauche : Identité étudiant --}}
        <td width="55%" style="vertical-align: top;">
            <table width="100%" cellspacing="0" cellpadding="2">
                <tr>
                    <td width="25%" style="font-size: 9.5px; font-weight: bold;">NOM :</td>
                    <td style="font-size: 9.5px;">{{ mb_strtoupper($etudiant->nom ?? '', 'UTF-8') }}</td>
                </tr>
                <tr>
                    <td style="font-size: 9.5px; font-weight: bold;">PRENOMS :</td>
                    <td style="font-size: 9.5px;">{{ mb_strtoupper($etudiant->prenoms ?? '', 'UTF-8') }}</td>
                </tr>
                <tr>
                    <td style="font-size: 9.5px; font-weight: bold;">DATE NAISS. :</td>
                    <td style="font-size: 9.5px;">{{ $etudiant->date_naissance ? \Carbon\Carbon::parse($etudiant->date_naissance)->format('d/m/Y') : '' }}</td>
                </tr>
                <tr>
                    <td style="font-size: 9.5px; font-weight: bold;">MATRICULE :</td>
                    <td style="font-size: 9.5px;">{{ $etudiant->matricule ?? '' }}</td>
                </tr>
            </table>
        </td>
        {{-- Droite : Domaine / Mention / Parcours (configurables) --}}
        <td width="45%" style="vertical-align: top;">
            <table width="100%" cellspacing="0" cellpadding="2">
                @if(isset($bulletin_fields))
                    @foreach($bulletin_fields as $field)
                        @if($field['show'] && $field['value'])
                        <tr>
                            <td width="40%" style="font-size: 9px; font-weight: bold;">{{ $field['label'] }} :</td>
                            <td style="font-size: 9px;">{{ $field['value'] }}</td>
                        </tr>
                        @endif
                    @endforeach
                @else
                    <tr>
                        <td width="40%" style="font-size: 9px; font-weight: bold;">DOMAINE :</td>
                        <td style="font-size: 9px;">{{ $domaine ?? '' }}</td>
                    </tr>
                    <tr>
                        <td style="font-size: 9px; font-weight: bold;">MENTION :</td>
                        <td style="font-size: 9px;">{{ $mention ?? '' }}</td>
                    </tr>
                    <tr>
                        <td style="font-size: 9px; font-weight: bold;">PARCOURS :</td>
                        <td style="font-size: 9px;">{{ $parcours_label ?? '' }}</td>
                    </tr>
                @endif
            </table>
        </td>
    </tr>
</table>

{{-- ═══════════════════════════════════════════════════════
     MAIN RESULTS TABLE — UE / ECUE
     ═══════════════════════════════════════════════════════ --}}
<table class="bulletin-table">
    <thead>
        <tr>
            <td style="width: 10%; background-color: {{ $primary }}; color: {{ $hdrText }}; font-weight: bold; text-align: center; font-size: 8px; padding: 4px 3px;">Code</td>
            <td style="width: 28%; background-color: {{ $primary }}; color: {{ $hdrText }}; font-weight: bold; text-align: center; font-size: 8px; padding: 4px 3px;">Intitulés</td>
            <td style="width: 7%; background-color: {{ $primary }}; color: {{ $hdrText }}; font-weight: bold; text-align: center; font-size: 8px; padding: 4px 3px;">Moy /<br>20</td>
            <td style="width: 8%; background-color: {{ $primary }}; color: {{ $hdrText }}; font-weight: bold; text-align: center; font-size: 8px; padding: 4px 3px;">AQ,APC,<br>NAQ</td>
            <td style="width: 5%; background-color: {{ $primary }}; color: {{ $hdrText }}; font-weight: bold; text-align: center; font-size: 8px; padding: 4px 3px;">Appr.</td>
            <td style="width: 5%; background-color: {{ $primary }}; color: {{ $hdrText }}; font-weight: bold; text-align: center; font-size: 8px; padding: 4px 3px;">CECT</td>
            <td style="width: 7%; background-color: {{ $primary }}; color: {{ $hdrText }}; font-weight: bold; text-align: center; font-size: 8px; padding: 4px 3px;">min</td>
            <td style="width: 7%; background-color: {{ $primary }}; color: {{ $hdrText }}; font-weight: bold; text-align: center; font-size: 8px; padding: 4px 3px;">moy</td>
            <td style="width: 7%; background-color: {{ $primary }}; color: {{ $hdrText }}; font-weight: bold; text-align: center; font-size: 8px; padding: 4px 3px;">max</td>
            <td style="width: 16%; background-color: {{ $primary }}; color: {{ $hdrText }}; font-weight: bold; text-align: center; font-size: 8px; padding: 4px 3px;">Nom et prénoms<br>enseignant</td>
        </tr>
    </thead>
    <tbody>
        @foreach($resultats_ues as $resUE)
            @php
                $ue = $resUE->uniteEnseignement;
                $ecues = $resUE->resultatsECUEs;
            @endphp

            {{-- UE ROW --}}
            <tr>
                <td style="background-color: #f3f4f6; font-weight: bold; font-size: 8.5px;">{{ $ue->code ?? '' }}</td>
                <td style="background-color: #f3f4f6; font-weight: bold; font-size: 8.5px;">{{ $ue->name ?? '' }}</td>
                <td class="num" style="background-color: #f3f4f6; font-weight: bold;">{{ $resUE->moyenne !== null ? number_format($resUE->moyenne, 2) : '' }}</td>
                <td class="num" style="background-color: #f3f4f6; font-weight: bold;">{{ $resUE->statut }}</td>
                <td class="num" style="background-color: #f3f4f6; font-weight: bold;">{{ $resUE->mention }}</td>
                <td class="num" style="background-color: #f3f4f6; font-weight: bold;">{{ $resUE->credit }}</td>
                <td class="num" style="background-color: #f3f4f6;">{{ $resUE->stat_min !== null ? number_format($resUE->stat_min, 2) : '' }}</td>
                <td class="num" style="background-color: #f3f4f6;">{{ $resUE->stat_moy !== null ? number_format($resUE->stat_moy, 2) : '' }}</td>
                <td class="num" style="background-color: #f3f4f6;">{{ $resUE->stat_max !== null ? number_format($resUE->stat_max, 2) : '' }}</td>
                <td style="background-color: #f3f4f6;"></td>
            </tr>

            {{-- ECUE ROWS --}}
            @foreach($ecues as $resECUE)
                @php $mat = $resECUE->matiere; @endphp
                <tr>
                    <td style="font-size: 8.5px;">{{ $mat->code ?? '' }}</td>
                    <td style="font-size: 8.5px;">{{ $mat->name ?? '' }}</td>
                    <td class="num" style="font-size: 8.5px;">{{ $resECUE->moyenne !== null ? number_format($resECUE->moyenne, 2) : '' }}</td>
                    <td class="num"></td>
                    <td class="num"></td>
                    <td class="num" style="font-size: 8.5px;">{{ $resECUE->credit > 0 ? $resECUE->credit : '' }}</td>
                    <td class="num" style="font-size: 8.5px;">{{ $resECUE->stat_min !== null ? number_format($resECUE->stat_min, 2) : '' }}</td>
                    <td class="num" style="font-size: 8.5px;">{{ $resECUE->stat_moy !== null ? number_format($resECUE->stat_moy, 2) : '' }}</td>
                    <td class="num" style="font-size: 8.5px;">{{ $resECUE->stat_max !== null ? number_format($resECUE->stat_max, 2) : '' }}</td>
                    <td style="font-size: 7.5px;">
                        @if($resECUE->enseignant)
                            {{ $resECUE->enseignant->name ?? '' }}
                        @endif
                    </td>
                </tr>
            @endforeach
        @endforeach
    </tbody>
</table>

{{-- ═══════════════════════════════════════════════════════
     MOYENNE GENERALE + CREDITS CAPITALISES
     ═══════════════════════════════════════════════════════ --}}
<table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-top: 10px;">
    <tr>
        <td width="55%" style="vertical-align: middle; padding-right: 10px;">
            <table width="100%" cellspacing="0" cellpadding="0">
                <tr>
                    <td class="moyenne-box">
                        MOYENNE GENERALE &nbsp;&nbsp;
                        <span style="font-size: 14px; color: {{ $primary }};">{{ $moyenne_generale !== null ? number_format($moyenne_generale, 2) : '--' }}</span>
                    </td>
                </tr>
            </table>
        </td>
        <td width="45%" style="vertical-align: middle;">
            <table width="100%" cellspacing="0" cellpadding="0">
                <tr>
                    <td class="credits-box">
                        Crédits capitalisés &nbsp;&nbsp;
                        <span style="font-size: 13px; color: {{ $primary }};">{{ $credits_capitalises }} / {{ $credits_totaux }}</span>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

{{-- ═══════════════════════════════════════════════════════
     DECISION DELIBERATION
     ═══════════════════════════════════════════════════════ --}}
<div class="decision-box">
    <span class="decision-label">Décision lors de la délibération :</span>
    <span class="decision-value" style="color: {{ $primary }};">{{ $decision ?? $bulletin->decision_deliberation ?? '' }}</span>
</div>

{{-- ═══════════════════════════════════════════════════════
     IMPORTANT NOTICE
     ═══════════════════════════════════════════════════════ --}}
<div class="notice">
    <strong>Très important:</strong> {{ $bCfg['notice_text'] ?? 'Pour les UE non acquises il vous sera délivré une attestation de réussite après validation de celles-ci. Un ECUE n\'est ni transférable ni capitalisable.' }}
</div>

{{-- ═══════════════════════════════════════════════════════
     SIGNATURE
     ═══════════════════════════════════════════════════════ --}}
<table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-top: 12px;">
    <tr>
        <td width="55%"></td>
        <td width="45%" style="text-align: right;">
            <div style="font-size: 9px; font-weight: bold;">
                Nom / Signature et cachet du chef<br>d'Etablissement
            </div>
            <div style="font-size: 9px; margin-top: 4px;">
                {{ $etab['ville'] ?? 'Abidjan' }}, le {{ now()->format('d/m/Y') }}
            </div>
            <div style="font-size: 9px; margin-top: 2px;">
                Le Directeur des Etudes
            </div>
            <div style="font-size: 10px; font-weight: bold; margin-top: 16px;">
                {{ $etab['directeur'] ?? '' }}
            </div>
        </td>
    </tr>
</table>

{{-- ═══════════════════════════════════════════════════════
     LEGEND
     ═══════════════════════════════════════════════════════ --}}
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
</div>

{{-- ═══════════════════════════════════════════════════════
     BOTTOM
     ═══════════════════════════════════════════════════════ --}}
<div style="text-align: center; font-size: 8px; color: #6b7280; margin-top: 6px;">
    {{ $etab['nom'] ?? 'KLASSCI' }}, Etablissement privé, Côte d'Ivoire
</div>
<div style="text-align: center; font-size: 8.5px; font-weight: bold; margin-top: 4px;">
    {{ $bCfg['bottom_text'] ?? 'Conservez soigneusement ce bulletin de notes. Aucun duplicata ne sera délivré.' }}
</div>

</div>{{-- /.container --}}
</body>
</html>

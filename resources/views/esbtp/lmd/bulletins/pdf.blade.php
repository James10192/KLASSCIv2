<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Bulletin LMD - {{ $etudiant->nom ?? '' }} {{ $etudiant->prenoms ?? '' }}</title>
    <style>
        /* ── Reset ── */
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 9px;
            color: #1f2937;
            line-height: 1.35;
            background: #ffffff;
        }

        @page {
            margin: 12mm 8mm 12mm 8mm;
            size: A4 portrait;
        }

        /* ── Typography ── */
        .title-republique { font-size: 9px; text-align: center; font-weight: normal; }
        .title-ministere { font-size: 8px; text-align: center; font-weight: normal; }
        .title-bulletin { font-size: 14px; font-weight: bold; text-align: center; }
        .title-semestre { font-size: 12px; font-weight: bold; text-align: center; }

        /* ── Header box ── */
        .header-box {
            border: 1.5px solid #1f2937;
            padding: 4px 8px;
            margin-bottom: 4px;
        }
        .header-box td { font-size: 8.5px; padding: 1px 0; }

        /* ── Student info ── */
        .info-block td { font-size: 9.5px; padding: 2px 0; }
        .info-label { font-weight: bold; }
        .info-value { }

        /* ── Right-aligned labels ── */
        .annee-label { text-align: right; font-size: 10px; font-weight: bold; }
        .niveau-label { text-align: right; font-size: 9px; }

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

        /* Header row */
        .bulletin-table thead td {
            background-color: #e5e7eb;
            font-weight: bold;
            text-align: center;
            font-size: 8px;
            padding: 4px 3px;
        }

        /* UE row (bold, gray background) */
        .ue-row td {
            background-color: #f3f4f6;
            font-weight: bold;
            font-size: 8.5px;
        }

        /* ECUE row (indented, normal) */
        .ecue-row td {
            font-weight: normal;
            font-size: 8.5px;
            background-color: #ffffff;
        }

        /* Numeric cells */
        .num { text-align: center; }
        .num-right { text-align: right; padding-right: 6px; }

        /* ── Footer section ── */
        .footer-section {
            margin-top: 8px;
        }
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

        /* ── Signature ── */
        .signature-block {
            margin-top: 12px;
            text-align: right;
        }
        .signature-block td { font-size: 9px; padding: 1px 0; }

        /* ── Legend ── */
        .legend {
            margin-top: 10px;
            border-top: 1px solid #d1d5db;
            padding-top: 6px;
            font-size: 7.5px;
            color: #6b7280;
        }

        /* ── Important notice ── */
        .notice {
            border: 1.5px solid #1f2937;
            padding: 6px 10px;
            margin-top: 8px;
            font-size: 8px;
        }

        /* ── Bottom text ── */
        .bottom-text {
            text-align: center;
            font-size: 8px;
            color: #6b7280;
            margin-top: 6px;
        }
        .bottom-warning {
            text-align: center;
            font-size: 8.5px;
            font-weight: bold;
            margin-top: 4px;
        }
    </style>
</head>
<body>

{{-- ═══════════════════════════════════════════════════════
     HEADER — Republique + Ministere + Bulletin Title
     ═══════════════════════════════════════════════════════ --}}
<table width="100%" border="0" cellspacing="0" cellpadding="0">
    <tr>
        {{-- Left: Republic + Ministry --}}
        <td width="40%" style="vertical-align: top;">
            <div class="title-republique">REPUBLIQUE DE COTE D'IVOIRE</div>
            <div class="title-ministere">MINISTERE DE L'ENSEIGNEMENT</div>
            <div class="title-ministere">SUPERIEUR ET DE LA RECHERCHE</div>
            <div class="title-ministere" style="border-bottom: 1px solid #1f2937; display: inline-block; padding-bottom: 1px;">SCIENTIFIQUE</div>
            <div style="text-align: center; font-size: 9px; font-weight: bold; margin-top: 3px;">
                ANNEE SCOLAIRE {{ $annee->name ?? '' }}
            </div>
        </td>

        {{-- Center: Bulletin title --}}
        <td width="35%" style="text-align: center; vertical-align: middle;">
            <div style="border: 2px solid #1f2937; padding: 8px 12px;">
                <div class="title-bulletin">BULLETIN SEMESTRIEL DE NOTES</div>
                <div class="title-semestre">{{ $semestre }}{{ $semestre == 1 ? 'er' : 'ème' }} semestre</div>
            </div>
        </td>

        {{-- Right: Logo --}}
        <td width="25%" style="text-align: right; vertical-align: top;">
            @if(isset($logoBase64) && $logoBase64)
                <img src="{{ $logoBase64 }}" style="max-height: 65px; max-width: 120px;" alt="Logo">
            @else
                @if(file_exists(public_path('images/esbtp_logo.png')))
                    <img src="{{ public_path('images/esbtp_logo.png') }}" style="max-height: 65px; max-width: 120px;" alt="Logo">
                @endif
            @endif
        </td>
    </tr>
</table>

{{-- ═══════════════════════════════════════════════════════
     ESTABLISHMENT INFO BOX
     ═══════════════════════════════════════════════════════ --}}
<table width="100%" class="header-box" cellspacing="0" cellpadding="0" style="margin-top: 6px;">
    <tr>
        <td width="70%">
            <strong>Etablissement :</strong>
            {{ $settings['school_name'] ?? 'ECOLE SPECIALE DU BATIMENT ET TRAVAUX PUBLICS' }}
        </td>
        <td width="15%"><strong>Code :</strong></td>
        <td width="15%"><strong>Statut :</strong> Privé</td>
    </tr>
    <tr>
        <td colspan="3">
            <strong>Adresse postale :</strong>
            {{ $settings['school_address'] ?? '01 BP 3694 Abidjan 01' }}
            &nbsp;&nbsp; <strong>Tél. :</strong> {{ $settings['school_phone'] ?? '' }}
            &nbsp;&nbsp; <strong>Email :</strong> {{ $settings['school_email'] ?? '' }}
        </td>
    </tr>
    <tr>
        <td colspan="2"></td>
        <td><strong>Direction :</strong> {{ $settings['director_name'] ?? '' }}</td>
    </tr>
</table>

{{-- ═══════════════════════════════════════════════════════
     STUDENT INFO + YEAR/LEVEL
     ═══════════════════════════════════════════════════════ --}}
<table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-top: 8px;">
    <tr>
        <td width="55%" style="vertical-align: top;">
            <table width="100%" class="info-block" cellspacing="0" cellpadding="0">
                <tr>
                    <td width="25%"><span class="info-label">NOM :</span></td>
                    <td width="75%">{{ strtoupper($etudiant->nom ?? '') }}</td>
                </tr>
                <tr>
                    <td><span class="info-label">PRENOMS :</span></td>
                    <td>{{ strtoupper($etudiant->prenoms ?? '') }}</td>
                </tr>
                <tr>
                    <td><span class="info-label">DATE NAISS. :</span></td>
                    <td>{{ $etudiant->date_naissance ? \Carbon\Carbon::parse($etudiant->date_naissance)->format('d/m/Y') : '' }}</td>
                </tr>
                <tr>
                    <td><span class="info-label">MATRICULE :</span></td>
                    <td>{{ $etudiant->matricule ?? '' }}</td>
                </tr>
            </table>
        </td>
        <td width="45%" style="vertical-align: top;">
            <table width="100%" class="info-block" cellspacing="0" cellpadding="0">
                <tr>
                    <td class="annee-label" colspan="2">
                        Année scolaire: {{ $annee->name ?? '' }}
                    </td>
                </tr>
                <tr>
                    <td class="niveau-label" colspan="2" style="text-align: right;">
                        <strong>Niveau:</strong> {{ $niveau ?? '' }}
                    </td>
                </tr>
                <tr>
                    <td class="niveau-label" colspan="2" style="text-align: right;">
                        <strong>Semestre:</strong> {{ $semestre ?? '' }}
                    </td>
                </tr>
                <tr>
                    <td width="35%" style="font-size: 9px;"><strong>DOMAINE :</strong></td>
                    <td style="font-size: 9px;">{{ $domaine ?? '' }}</td>
                </tr>
                <tr>
                    <td style="font-size: 9px;"><strong>MENTION :</strong></td>
                    <td style="font-size: 9px;">{{ $mention ?? '' }}</td>
                </tr>
                <tr>
                    <td style="font-size: 9px;"><strong>PARCOURS :</strong></td>
                    <td style="font-size: 9px;">{{ $parcours_label ?? '' }}</td>
                </tr>
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
            <td style="width: 10%;">Code</td>
            <td style="width: 28%;">Intitulés</td>
            <td style="width: 7%;">Moy /<br>20</td>
            <td style="width: 8%;">AQ,APC,<br>NAQ</td>
            <td style="width: 5%;">Appr.</td>
            <td style="width: 5%;">CECT</td>
            <td style="width: 7%;">min</td>
            <td style="width: 7%;">moy</td>
            <td style="width: 7%;">max</td>
            <td style="width: 16%;">Nom et prénoms<br>enseignant</td>
        </tr>
    </thead>
    <tbody>
        @foreach($resultats_ues as $resUE)
            @php
                $ue = $resUE->uniteEnseignement;
                $ecues = $resUE->resultatsECUEs;
            @endphp

            {{-- UE ROW (bold, gray background) --}}
            <tr class="ue-row">
                <td>{{ $ue->code ?? '' }}</td>
                <td>{{ $ue->name ?? '' }}</td>
                <td class="num">{{ $resUE->moyenne !== null ? number_format($resUE->moyenne, 2) : '' }}</td>
                <td class="num">{{ $resUE->statut }}</td>
                <td class="num">{{ $resUE->mention }}</td>
                <td class="num">{{ $resUE->credit }}</td>
                <td class="num">{{ $resUE->stat_min !== null ? number_format($resUE->stat_min, 2) : '' }}</td>
                <td class="num">{{ $resUE->stat_moy !== null ? number_format($resUE->stat_moy, 2) : '' }}</td>
                <td class="num">{{ $resUE->stat_max !== null ? number_format($resUE->stat_max, 2) : '' }}</td>
                <td></td>
            </tr>

            {{-- ECUE ROWS (indented, white background) --}}
            @foreach($ecues as $resECUE)
                @php $mat = $resECUE->matiere; @endphp
                <tr class="ecue-row">
                    <td>{{ $mat->code ?? '' }}</td>
                    <td>{{ $mat->name ?? '' }}</td>
                    <td class="num">{{ $resECUE->moyenne !== null ? number_format($resECUE->moyenne, 2) : '' }}</td>
                    <td class="num"></td>
                    <td class="num"></td>
                    <td class="num">{{ $resECUE->credit > 0 ? $resECUE->credit : '' }}</td>
                    <td class="num">{{ $resECUE->stat_min !== null ? number_format($resECUE->stat_min, 2) : '' }}</td>
                    <td class="num">{{ $resECUE->stat_moy !== null ? number_format($resECUE->stat_moy, 2) : '' }}</td>
                    <td class="num">{{ $resECUE->stat_max !== null ? number_format($resECUE->stat_max, 2) : '' }}</td>
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
                        <span style="font-size: 14px;">{{ $moyenne_generale !== null ? number_format($moyenne_generale, 2) : '--' }}</span>
                    </td>
                </tr>
            </table>
        </td>
        <td width="45%" style="vertical-align: middle;">
            <table width="100%" cellspacing="0" cellpadding="0">
                <tr>
                    <td class="credits-box">
                        Crédits capitalisés &nbsp;&nbsp;
                        <span style="font-size: 13px;">{{ $credits_capitalises }} / {{ $credits_totaux }}</span>
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
    <span class="decision-value">{{ $decision ?? $bulletin->decision_deliberation ?? '' }}</span>
</div>

{{-- ═══════════════════════════════════════════════════════
     IMPORTANT NOTICE
     ═══════════════════════════════════════════════════════ --}}
<div class="notice">
    <strong>Très important:</strong> Pour les UE non acquises il vous sera délivré une attestation de
    réussite après validation de celles-ci.<br>
    Un ECUE n'est ni transférable ni capitalisable.
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
                {{ $settings['school_city'] ?? 'Abidjan' }}, le {{ now()->format('d/m/Y') }}
            </div>
            <div style="font-size: 9px; margin-top: 2px;">
                Le Directeur des Etudes
            </div>
            <div style="font-size: 10px; font-weight: bold; margin-top: 16px;">
                {{ $settings['director_name'] ?? '' }}
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
<div class="bottom-text">
    {{ $settings['school_name'] ?? 'ESBTP' }}, Etablissement privé, Côte d'Ivoire
</div>
<div class="bottom-warning">
    Conservez soigneusement ce bulletin de notes. Aucun duplicata ne sera délivré.
</div>

</body>
</html>

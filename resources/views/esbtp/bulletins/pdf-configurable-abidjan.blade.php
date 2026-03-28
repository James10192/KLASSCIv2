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
    @endphp
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulletin de Notes - {{ $etudiant->nom }} {{ $etudiant->prenoms ?? $etudiant->prenom }}</title>
    <style>
        /* ── Base ─────────────────────────────────────────────── */
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: {{ $settings['bulletin_font_size'] ?? '11' }}px;
            margin: 0;
            padding: 0;
            background: #fff;
            color: #111827;
            line-height: 1.35;
        }
        .container {
            width: 210mm;
            max-width: 794px;
            margin: 0 auto;
            background: #fff;
            padding: 10px 14px 14px;
        }

        /* ── En-tête institution ──────────────────────────────── */
        .top-entete {
            text-align: center;
            font-size: 9.5px;
            color: #374151;
            padding-bottom: 5px;
            margin-bottom: 8px;
            border-bottom: 1px solid #d1d5db;
        }
        .top-entete .line-strong { font-weight: 700; }

        /* ── Header principal ─────────────────────────────────── */
        .header {
            width: 100%;
            margin-bottom: 10px;
            border: 1.5px solid {{ $pdfPrimary }};
            border-radius: 10px;
            overflow: hidden;
            background: #f9fafb;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .header-table td {
            border: none;
            padding: 0;
            vertical-align: middle;
        }

        /* Colonne gauche : logo — largeur % explicite requise par DomPDF */
        .header-logo-cell {
            width: 16%;
            padding: 10px 8px 10px 12px;
            vertical-align: middle;
            text-align: center;
            border-right: 1.5px solid #e5e7eb;
        }
        /* width fixe + height auto = ratio préservé ; max-height = protection logo portrait */
        .logo {
            width: 80px;
            height: auto;
            max-height: 80px;
            display: block;
            margin: 0 auto;
        }

        /* Colonne droite : infos école + titre bulletin — largeur % explicite requise par DomPDF */
        .header-info-cell {
            width: 84%;
            padding: 10px 12px 10px 14px;
            vertical-align: top;
        }
        .school-name {
            font-weight: 700;
            font-size: 13px;
            color: {{ $pdfPrimary }};
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 2px;
        }
        .school-contact {
            font-size: 8.5px;
            color: #4b5563;
            margin-bottom: 6px;
        }
        .header-divider {
            height: 1px;
            background: #e5e7eb;
            margin: 6px 0;
        }
        .bulletin-title {
            font-weight: 700;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: {{ $pdfPrimary }};
            margin-bottom: 2px;
        }
        .bulletin-period {
            font-size: 9.5px;
            color: #374151;
            margin-bottom: 1px;
        }
        .academic-year {
            font-size: 9.5px;
            font-weight: 700;
            color: #111827;
        }

        /* ── Fiche étudiant ───────────────────────────────────── */
        .student-info {
            width: 100%;
            margin-bottom: 10px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #fff;
            overflow: hidden;
        }
        .student-info-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .student-info-table td {
            border: none;
            padding: 7px 8px;
            vertical-align: top;
            word-wrap: break-word;
        }

        /* Colonne photo */
        .student-info-table td:first-child {
            width: 118px;
            min-width: 118px;
            text-align: center;
            vertical-align: middle;
            padding: 8px;
            background: #f8fafb;
            border-right: 1px solid #e5e7eb;
            display: table-cell;
        }
        .student-info-table td:first-child img {
            width: 90px;
            height: 90px;
            border-radius: 8px;
            object-fit: cover;
            border: 2px solid {{ $pdfPrimary }};
            display: block;
            margin: 0 auto;
        }
        .avatar-fallback {
            width: 90px;
            height: 90px;
            border-radius: 8px;
            border: 2px solid {{ $pdfPrimary }};
            display: table;
            margin: 0 auto;
            background: #e5e7eb;
        }
        .avatar-fallback span {
            display: table-cell;
            vertical-align: middle;
            text-align: center;
            font-size: 26px;
            color: {{ $pdfPrimary }};
            font-weight: 700;
        }
        .matricule-text {
            margin-top: 5px;
            font-weight: 700;
            font-size: 8.5px;
            text-align: center;
            color: #374151;
        }

        /* Colonnes infos */
        .info-group {
            width: 40%;
            vertical-align: top;
        }
        .info-row { margin-bottom: 4px; font-size: 10px; }
        .info-label {
            font-weight: 700;
            display: inline-block;
            width: 115px;
            color: #374151;
            font-size: 9.5px;
        }
        .info-value { color: #111827; font-size: 10px; }

        /* ── Tableau matières ─────────────────────────────────── */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            font-size: 9.5px;
        }
        th, td {
            border: 1px solid #d1d5db;
            padding: 4px 5px;
            text-align: left;
        }
        th {
            background: #f3f4f6;
            font-weight: 700;
            text-align: center;
            font-size: 9px;
            color: #111827;
        }
        .center { text-align: center; }

        .section-header {
            background: {{ $pdfPrimary }};
            color: {{ $pdfHeaderText }};
            font-weight: 700;
            text-align: center;
            padding: 5px 8px;
            font-size: 9.5px;
        }
        .subject-row:nth-child(even) { background: #f8fafb; }
        .summary-row {
            background: #e5e7eb;
            font-weight: 700;
        }

        /* Absences */
        .absences-table { width: 100%; margin-bottom: 8px; }

        /* ── Résultats & Statistiques ─────────────────────────── */
        .results-container { width: 100%; margin-bottom: 10px; }
        .results-container-table { width: 100%; border-collapse: collapse; }
        .results-container-table td {
            border: none;
            padding: 0;
            vertical-align: top;
        }
        .results-left { width: 50%; padding-right: 5px; }
        .results-right { width: 50%; padding-left: 5px; }

        .results-card, .stats-card {
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #fff;
            overflow: hidden;
        }
        .results-table, .stats-table {
            width: 100%;
            font-size: 9.5px;
            border-collapse: collapse;
            background: #fff;
        }
        .results-table th, .stats-table th {
            background: {{ $pdfPrimary }};
            color: {{ $pdfHeaderText }};
            padding: 5px 8px;
            font-size: 9px;
            border: none;
            text-align: left;
        }
        .results-table td, .stats-table td {
            padding: 4px 8px;
            border-bottom: 1px solid #f3f4f6;
            border-left: none;
            border-right: none;
            border-top: none;
        }
        .results-table tr:last-child td, .stats-table tr:last-child td {
            border-bottom: none;
        }
        .result-value-box {
            border: 1px solid #d1d5db;
            border-radius: 4px;
            padding: 2px 6px;
            min-width: 52px;
            display: inline-block;
            text-align: center;
            font-weight: 700;
            background: #f8fafb;
            font-size: 10px;
        }

        /* ── Mentions ─────────────────────────────────────────── */
        .mention-box {
            width: 100%;
            margin-bottom: 4px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 9px;
            background: #fff;
            overflow: hidden;
        }
        .mention-table { width: 100%; border-collapse: collapse; }
        .mention-table td {
            padding: 4px 8px;
            border-bottom: none;
            border-left: none;
            border-right: none;
            border-top: none;
        }
        .mention-label { font-weight: 600; color: #111827; }
        .mention-value {
            width: 28px;
            text-align: right;
        }

        /* ── Décision conseil ─────────────────────────────────── */
        .decision-container {
            margin: 10px 0;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 8px 10px;
            min-height: 52px;
            background: #f9fafb;
        }
        .decision-title {
            font-weight: 700;
            margin-bottom: 5px;
            text-transform: uppercase;
            font-size: 9.5px;
            color: {{ $pdfPrimary }};
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 3px;
        }

        /* ── Signature ────────────────────────────────────────── */
        .signature-container {
            margin-top: 12px;
            text-align: right;
        }
        .signature-box {
            display: inline-block;
            text-align: center;
            min-width: 200px;
        }
        .signature-line {
            width: 200px;
            height: 44px;
            border-bottom: 1.5px solid {{ $pdfPrimary }};
            margin-top: 4px;
        }

        /* ── Mode PDF export ──────────────────────────────────── */
        @if($isPdfExport ?? false)
        @page {
            size: A4 portrait;
            margin: 12mm 10mm;
        }
        body.pdf-export {
            margin: 0;
            padding: 0;
            background: #fff;
        }
        body.pdf-export .container {
            width: 100%;
            max-width: none;
            padding: 0;
            border: none;
        }
        @endif

        @media print {
            body { margin: 0; padding: 0; background: #fff; }
            .container { box-shadow: none; width: 100%; max-width: none; }
            .print-button, .pdf-toggle { display: none !important; }
        }
    </style>
</head>
<body @if($isPdfExport ?? false)class="pdf-export"@endif>
    <div class="container">

        {{-- Entête ministère / république --}}
        @if(($settings['bulletin_show_header'] ?? '1') == '1')
            @if(($settings['bulletin_show_ministry_info'] ?? '1') == '1' || ($settings['bulletin_show_republic_info'] ?? '1') == '1')
                <div class="top-entete">
                    @if(($settings['bulletin_show_ministry_info'] ?? '1') == '1')
                        <div class="line-strong">{{ $settings['bulletin_ministry_text'] ?? "Ministere de l'Enseignement Superieur" }}</div>
                    @endif
                    @if(($settings['bulletin_show_republic_info'] ?? '1') == '1')
                        <div>{{ $settings['bulletin_union_text'] ?? 'Union - Travail - Progres' }}</div>
                    @endif
                </div>
            @endif

            @php
                $bulletin = $bulletin ?? null;
                $anneeAffichee = $bulletin && $bulletin->anneeUniversitaire ? $bulletin->anneeUniversitaire : $anneeUniversitaire;
                $anneeLabel = $anneeAffichee->name ?? null;
                if (! $anneeLabel && $anneeAffichee && $anneeAffichee->start_date && $anneeAffichee->end_date) {
                    $anneeLabel = $anneeAffichee->start_date->format('Y').'-'.$anneeAffichee->end_date->format('Y');
                }
                if (! $anneeLabel && isset($anneeAffichee->annee_debut, $anneeAffichee->annee_fin)) {
                    $anneeLabel = $anneeAffichee->annee_debut.'-'.$anneeAffichee->annee_fin;
                }
                if (! $anneeLabel) {
                    $anneeLabel = $anneeAffichee ? ('Annee '.$anneeAffichee->id) : '';
                }
            @endphp

            {{-- Header : logo à gauche | infos école + titre à droite --}}
            <div class="header">
                <table class="header-table">
                    <tr>
                        <td class="header-logo-cell">
                            @if(($settings['bulletin_show_logo'] ?? '1') == '1' && isset($logoBase64) && $logoBase64)
                                <img src="{{ $logoBase64 }}" alt="Logo" class="logo">
                            @endif
                        </td>
                        <td class="header-info-cell">
                            @if(($settings['bulletin_show_school_info'] ?? '1') == '1')
                                <div class="school-name">
                                    {{ $settings['bulletin_school_name_custom'] ?: $settings['school_name'] }}
                                </div>
                                <div class="school-contact">
                                    {{ $settings['school_address'] }}
                                    @if($settings['school_phone'] ?? null) &bull; Tél : {{ $settings['school_phone'] }}@endif
                                    @if($settings['school_email'] ?? null) &bull; {{ $settings['school_email'] }}@endif
                                </div>
                            @endif
                            <div class="header-divider"></div>
                            <div class="bulletin-title">Bulletin de Notes</div>
                            <div class="bulletin-period">
                                @if($periode == 'semestre1') Premier Semestre
                                @elseif($periode == 'semestre2') Deuxième Semestre
                                @else Annuel
                                @endif
                            </div>
                            @if(($settings['bulletin_show_edition_date'] ?? '1') == '1')
                                <div class="bulletin-period">Édition du : {{ $date_edition }}</div>
                            @endif
                            <div class="academic-year">Année Scolaire : {{ $anneeLabel }}</div>
                        </td>
                    </tr>
                </table>
            </div>
        @endif

        {{-- Fiche étudiant --}}
        @php
            $prenom = $etudiant->prenoms ?? $etudiant->prenom ?? '';
            $initials = strtoupper(substr($etudiant->nom ?? 'E', 0, 1) . substr($prenom ?: 'T', 0, 1));
        @endphp
        <div class="student-info">
            <table class="student-info-table">
                <tr>
                    <td>
                        @if(isset($photoEtudiantBase64) && $photoEtudiantBase64)
                            <img src="{{ $photoEtudiantBase64 }}" alt="Photo">
                        @else
                            <div class="avatar-fallback"><span>{{ $initials }}</span></div>
                        @endif
                        @if(($settings['bulletin_show_matricule'] ?? '1') == '1')
                            <div class="matricule-text">{{ $etudiant->matricule }}</div>
                        @endif
                    </td>
                    <td class="info-group">
                        <div class="info-row">
                            <span class="info-label">Nom et Prénoms :</span>
                            <span class="info-value">{{ $etudiant->nom }} {{ $etudiant->prenoms ?? $etudiant->prenom }}</span>
                        </div>
                        @if(($settings['bulletin_show_birth_date'] ?? '1') == '1')
                            <div class="info-row">
                                <span class="info-label">Date de Naissance :</span>
                                <span class="info-value">{{ $etudiant->date_naissance ? \Carbon\Carbon::parse($etudiant->date_naissance)->format('d/m/Y') : 'Non renseignée' }}</span>
                            </div>
                        @endif
                        <div class="info-row">
                            <span class="info-label">Lieu de Naissance :</span>
                            <span class="info-value">{{ $etudiant->lieu_naissance ?? 'Non renseigné' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Genre :</span>
                            <span class="info-value">{{ $etudiant->genre == 'M' ? 'Masculin' : 'Féminin' }}</span>
                        </div>
                        @if(($settings['bulletin_show_redoublant'] ?? '1') == '1')
                            <div class="info-row">
                                <span class="info-label">Redoublant :</span>
                                <span class="info-value">{{ $etudiant->inscriptions->first()->is_redoublant ?? false ? 'Oui' : 'Non' }}</span>
                            </div>
                        @endif
                        <div class="info-row">
                            <span class="info-label">Téléphone :</span>
                            <span class="info-value">{{ $etudiant->telephone ?? 'Non renseigné' }}</span>
                        </div>
                    </td>
                    <td class="info-group">
                        <div class="info-row">
                            <span class="info-label">Classe :</span>
                            <span class="info-value">{{ $classe->libelle ?? $classe->name }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Année d'étude :</span>
                            <span class="info-value">{{ $classe->niveau->libelle ?? $classe->niveau->name ?? ($classe->annee ?? 'N/A') }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Filière :</span>
                            <span class="info-value">{{ $classe->filiere->name ?? 'N/A' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Effectif :</span>
                            <span class="info-value">{{ $effectif }}</span>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        {{-- Tableau des matières --}}
        @if(($settings['bulletin_show_subjects_table'] ?? '1') == '1')
            <table>
                <thead>
                    <tr>
                        <th>Matière</th>
                        @if(($settings['bulletin_show_subject_average'] ?? '1') == '1')<th>Moyenne M</th>@endif
                        @if(($settings['bulletin_show_coefficient'] ?? '1') == '1')<th>Coef C</th>@endif
                        @if(($settings['bulletin_show_weighted_average'] ?? '1') == '1')<th>Moy Pondérée M×C</th>@endif
                        @if(($settings['bulletin_show_rank_per_subject'] ?? '1') == '1')<th>Rang</th>@endif
                        @if(($settings['bulletin_show_absences_par_matiere'] ?? '0') == '1' && ($settings['bulletin_conduite_enabled'] ?? '0') == '1')<th>Abs. (h)</th>@endif
                        @if(($settings['bulletin_show_teachers'] ?? '1') == '1')<th>Professeurs</th>@endif
                        @if(($settings['bulletin_show_appreciations'] ?? '1') == '1')<th>Appréciations</th>@endif
                    </tr>
                    @if(($settings['bulletin_show_general_subjects'] ?? '1') == '1')
                        <tr class="section-header">
                            <td colspan="7">Enseignement Général</td>
                        </tr>
                    @endif
                </thead>
                <tbody>
                    @if(($settings['bulletin_show_general_subjects'] ?? '1') == '1')
                        @if(isset($resultatsGeneraux) && $resultatsGeneraux->count() > 0)
                            @foreach($resultatsGeneraux as $resultat)
                                <tr class="subject-row">
                                    <td>{{ $resultat->matiere->name ?? $resultat->matiere->nom ?? 'N/A' }}</td>
                                    @if(($settings['bulletin_show_subject_average'] ?? '1') == '1')<td class="center">{{ number_format($resultat->moyenne, 2) }}</td>@endif
                                    @if(($settings['bulletin_show_coefficient'] ?? '1') == '1')<td class="center">{{ $resultat->coefficient }}</td>@endif
                                    @if(($settings['bulletin_show_weighted_average'] ?? '1') == '1')<td class="center">{{ number_format($resultat->moyenne * $resultat->coefficient, 2) }}</td>@endif
                                    @if(($settings['bulletin_show_rank_per_subject'] ?? '1') == '1')<td class="center">{{ $resultat->rang ?: '-' }}</td>@endif
                                    @if(($settings['bulletin_show_absences_par_matiere'] ?? '0') == '1' && ($settings['bulletin_conduite_enabled'] ?? '0') == '1')<td class="center">{{ isset($absencesParMatiere[$resultat->matiere_id]) ? $absencesParMatiere[$resultat->matiere_id]['total_heures'] : 0 }}</td>@endif
                                    @if(($settings['bulletin_show_teachers'] ?? '1') == '1')<td>{{ $professeurs[$resultat->matiere_id] ?? 'M.' }}</td>@endif
                                    @if(($settings['bulletin_show_appreciations'] ?? '1') == '1')<td>{{ $resultat->appreciation ?? 'Nul' }}</td>@endif
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="8" class="center">Aucune matière d'enseignement général</td>
                            </tr>
                        @endif
                        @if(($settings['bulletin_show_section_averages'] ?? '1') == '1')
                            <tr class="summary-row">
                                <td>Moyenne enseignement général</td>
                                <td colspan="2" class="center">{{ number_format($moyenneGenerale, 2) }}</td>
                                <td colspan="4"></td>
                            </tr>
                        @endif
                    @endif

                    @if(($settings['bulletin_show_technical_subjects'] ?? '1') == '1')
                        <tr class="section-header">
                            <td colspan="7">Enseignement Technique</td>
                        </tr>
                        @if(isset($resultatsTechniques) && $resultatsTechniques->count() > 0)
                            @foreach($resultatsTechniques as $resultat)
                                <tr class="subject-row">
                                    <td>{{ $resultat->matiere->name ?? $resultat->matiere->nom ?? 'N/A' }}</td>
                                    @if(($settings['bulletin_show_subject_average'] ?? '1') == '1')<td class="center">{{ number_format($resultat->moyenne, 2) }}</td>@endif
                                    @if(($settings['bulletin_show_coefficient'] ?? '1') == '1')<td class="center">{{ $resultat->coefficient }}</td>@endif
                                    @if(($settings['bulletin_show_weighted_average'] ?? '1') == '1')<td class="center">{{ number_format($resultat->moyenne * $resultat->coefficient, 2) }}</td>@endif
                                    @if(($settings['bulletin_show_rank_per_subject'] ?? '1') == '1')<td class="center">{{ $resultat->rang ?: '-' }}</td>@endif
                                    @if(($settings['bulletin_show_absences_par_matiere'] ?? '0') == '1' && ($settings['bulletin_conduite_enabled'] ?? '0') == '1')<td class="center">{{ isset($absencesParMatiere[$resultat->matiere_id]) ? $absencesParMatiere[$resultat->matiere_id]['total_heures'] : 0 }}</td>@endif
                                    @if(($settings['bulletin_show_teachers'] ?? '1') == '1')<td>{{ $professeurs[$resultat->matiere_id] ?? 'M.' }}</td>@endif
                                    @if(($settings['bulletin_show_appreciations'] ?? '1') == '1')<td>{{ $resultat->appreciation ?? 'Nul' }}</td>@endif
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="8" class="center">Aucune matière d'enseignement technique</td>
                            </tr>
                        @endif
                        @if(($settings['bulletin_show_section_averages'] ?? '1') == '1')
                            <tr class="summary-row">
                                <td>Moyenne enseignement technique</td>
                                <td colspan="2" class="center">{{ number_format($moyenneTechnique, 2) }}</td>
                                <td colspan="4"></td>
                            </tr>
                        @endif
                    @endif
                </tbody>
            </table>
        @endif

        {{-- Absences --}}
        @if(($settings['bulletin_show_absences'] ?? '1') == '1')
            <table class="absences-table">
                <thead>
                    <tr class="section-header">
                        <td colspan="2">Nombre d'heures d'absence</td>
                    </tr>
                </thead>
                <tbody>
                    @if(($settings['bulletin_show_justified_absences'] ?? '1') == '1')
                        <tr>
                            <td>Absences justifiées</td>
                            <td class="center" style="width: 50%;">{{ isset($absencesJustifiees) ? $absencesJustifiees : (isset($absences_justifiees) ? $absences_justifiees : (isset($bulletin->absences_justifiees) ? $bulletin->absences_justifiees : '00')) }} Heure(s)</td>
                        </tr>
                    @endif
                    @if(($settings['bulletin_show_unjustified_absences'] ?? '1') == '1')
                        <tr>
                            <td>Absences non justifiées</td>
                            <td class="center">{{ isset($absencesNonJustifiees) ? $absencesNonJustifiees : (isset($absences_non_justifiees) ? $absences_non_justifiees : (isset($bulletin->absences_non_justifiees) ? $bulletin->absences_non_justifiees : '00')) }} Heure(s)</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        @endif

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

        {{-- Résultats & Statistiques --}}
        @if(($settings['bulletin_show_results_section'] ?? '1') == '1')
            <div class="results-container">
                <table class="results-container-table">
                    <tr>
                        <td class="results-left">
                            <div class="results-card">
                                <table class="results-table">
                                    <thead>
                                        <tr><th colspan="2">RÉSULTATS</th></tr>
                                    </thead>
                                    <tbody>
                                        @if(($settings['bulletin_show_raw_average'] ?? '1') == '1')
                                            <tr>
                                                <td>Moyenne Brute</td>
                                                <td class="center"><span class="result-value-box">{{ number_format($moyenneGlobale, 2) }}</span></td>
                                            </tr>
                                        @endif
                                        @if(($settings['bulletin_show_attendance_note'] ?? '1') == '1')
                                            <tr>
                                                <td>Note d'assiduité</td>
                                                <td class="center"><span class="result-value-box">{{ $note_assiduite > 0 ? '+'.number_format($note_assiduite, 2) : number_format($note_assiduite, 2) }}</span></td>
                                            </tr>
                                        @endif
                                        @if(($settings['bulletin_show_semester_average'] ?? '1') == '1')
                                            <tr>
                                                <td>Moyenne {{ $periode == 'semestre1' ? '1er' : '2e' }} Semestre</td>
                                                <td class="center"><span class="result-value-box">{{ number_format($moyenneAvecAssiduite, 2) }}</span></td>
                                            </tr>
                                            @if($periode == 'semestre2')
                                                <tr>
                                                    <td>Moyenne Semestre 1</td>
                                                    <td class="center"><span class="result-value-box">{{ $moyenneSemestre1 !== null ? number_format($moyenneSemestre1, 2) : '-' }}</span></td>
                                                </tr>
                                                <tr>
                                                    <td>Moyenne Annuelle</td>
                                                    <td class="center"><span class="result-value-box">{{ $moyenneAnnuelle !== null ? number_format($moyenneAnnuelle, 2) : '-' }}</span></td>
                                                </tr>
                                            @endif
                                        @endif
                                        @if(($settings['bulletin_show_student_rank'] ?? '1') == '1')
                                            <tr>
                                                <td>Rang</td>
                                                <td class="center"><span class="result-value-box">{{ $rang }}</span></td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>

                            @if(($settings['bulletin_show_mentions'] ?? '1') == '1')
                                <div style="margin-top: 8px;">
                                    @if(($settings['bulletin_show_felicitation'] ?? '1') == '1')
                                        @php $felicitationThreshold = floatval($settings['bulletin_felicitation_threshold'] ?? 16); $isChecked = ($settings['bulletin_auto_calculate_mention'] ?? '1') == '1' ? ($moyenneGlobale >= $felicitationThreshold) : false; @endphp
                                        <div class="mention-box"><table class="mention-table"><tr><td class="mention-label">Félicitation</td><td class="mention-value"><input type="checkbox" {{ $isChecked ? 'checked' : '' }}></td></tr></table></div>
                                    @endif
                                    @if(($settings['bulletin_show_encouragement'] ?? '1') == '1')
                                        @php $encouragementThreshold = floatval($settings['bulletin_encouragement_threshold'] ?? 14); $felicitationThreshold = floatval($settings['bulletin_felicitation_threshold'] ?? 16); $isChecked = ($settings['bulletin_auto_calculate_mention'] ?? '1') == '1' ? ($moyenneGlobale >= $encouragementThreshold && $moyenneGlobale < $felicitationThreshold) : false; @endphp
                                        <div class="mention-box"><table class="mention-table"><tr><td class="mention-label">Encouragement</td><td class="mention-value"><input type="checkbox" {{ $isChecked ? 'checked' : '' }}></td></tr></table></div>
                                    @endif
                                    @if(($settings['bulletin_show_honor_roll'] ?? '1') == '1')
                                        @php $honorRollThreshold = floatval($settings['bulletin_honor_roll_threshold'] ?? 12); $encouragementThreshold = floatval($settings['bulletin_encouragement_threshold'] ?? 14); $isChecked = ($settings['bulletin_auto_calculate_mention'] ?? '1') == '1' ? ($moyenneGlobale >= $honorRollThreshold && $moyenneGlobale < $encouragementThreshold) : false; @endphp
                                        <div class="mention-box"><table class="mention-table"><tr><td class="mention-label">Tableau d'honneur</td><td class="mention-value"><input type="checkbox" {{ $isChecked ? 'checked' : '' }}></td></tr></table></div>
                                    @endif
                                    @if(($settings['bulletin_show_work_warning'] ?? '1') == '1')
                                        @php $workWarningThreshold = floatval($settings['bulletin_work_warning_threshold'] ?? 8); $isChecked = ($settings['bulletin_auto_calculate_mention'] ?? '1') == '1' ? ($moyenneGlobale >= $workWarningThreshold && $moyenneGlobale < 10) : false; @endphp
                                        <div class="mention-box"><table class="mention-table"><tr><td class="mention-label">Avertissement (Travail)</td><td class="mention-value"><input type="checkbox" {{ $isChecked ? 'checked' : '' }}></td></tr></table></div>
                                    @endif
                                    @if(($settings['bulletin_show_conduct_blame'] ?? '1') == '1')
                                        <div class="mention-box"><table class="mention-table"><tr><td class="mention-label">Blâme (Conduite)</td><td class="mention-value"><input type="checkbox"></td></tr></table></div>
                                    @endif
                                </div>
                            @endif
                        </td>

                        @if(($settings['bulletin_show_statistics'] ?? '1') == '1')
                            <td class="results-right">
                                <div class="stats-card">
                                    <table class="stats-table">
                                        <thead>
                                            <tr><th colspan="2">STATISTIQUES — {{ $periode == 'semestre2' ? 'SEMESTRE 2' : 'SEMESTRE 1' }}</th></tr>
                                        </thead>
                                        <tbody>
                                            @if(($settings['bulletin_show_highest_average'] ?? '1') == '1')
                                                <tr><td>Plus forte moyenne</td><td class="center">{{ number_format($meilleure_moyenne, 2) }}</td></tr>
                                            @endif
                                            @if(($settings['bulletin_show_lowest_average'] ?? '1') == '1')
                                                <tr><td>Plus faible moyenne</td><td class="center">{{ number_format($plus_faible_moyenne, 2) }}</td></tr>
                                            @endif
                                            @if(($settings['bulletin_show_class_average'] ?? '1') == '1')
                                                <tr><td>Moyenne de la classe</td><td class="center">{{ number_format($moyenne_classe, 2) }}</td></tr>
                                            @endif
                                        </tbody>
                                    </table>
                                </div>
                            </td>
                        @endif
                    </tr>
                </table>
            </div>
        @endif

        {{-- Décision du conseil --}}
        @if(($settings['bulletin_show_council_decision'] ?? '1') == '1')
            <div class="decision-container">
                <div class="decision-title">Décision du conseil de classe</div>
                <div style="min-height: 36px; font-size: 10px;">{{ $appreciation }}</div>
            </div>
        @endif

        {{-- Signature --}}
        @if(($settings['bulletin_show_signature'] ?? '1') == '1' || ($settings['bulletin_show_director_signature'] ?? '1') == '1')
            @php
                $directorTitle = $settings['director_title'] ?? \App\Helpers\SettingsHelper::get('director_title', 'Directeur');
                $directorName  = $settings['director_name']  ?? \App\Helpers\SettingsHelper::get('director_name', '');
            @endphp
            <div class="signature-container">
                <div class="signature-box">
                    <div style="font-size: 10px;">{{ $directorTitle }}</div>
                    <div class="signature-line"></div>
                    @if($directorName)
                        <div style="margin-top: 4px; font-weight: 700; font-size: 9.5px;">{{ $directorName }}</div>
                    @endif
                </div>
            </div>
        @endif

    </div>
</body>
</html>

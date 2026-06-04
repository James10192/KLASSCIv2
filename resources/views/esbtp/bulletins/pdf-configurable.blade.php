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
            font-size: {{ $settings['bulletin_font_size'] ?? '13' }}px;
            margin: 0;
            padding: 0;
            background: #fff;
            color: #111827;
            line-height: 1.25;
        }
        /* Compaction auto-fit 1 page : sections critiques évitent coupure */
        .student-info, .header, .results-container, .signature-container,
        tr.section-header, tr.summary-row { page-break-inside: avoid; }
        .container {
            width: 100%;
            max-width: 100%;
            margin: 0;
            background: #fff;
            padding: 0;
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
            font-size: 11.5px;
            line-height: 1.5;
            color: #374151;
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
            font-size: 12px;
            line-height: 1.5;
            text-align: right;
            padding-left: 8px;
            border-left: 1px solid #e5e7eb;
        }
        .logo {
            width: 72px;
            height: 72px;
            object-fit: contain;
            margin-bottom: 4px;
        }
        .school-name {
            font-weight: 700;
            font-size: 16px;
            color: {{ $pdfPrimary }};
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 3px;
        }
        .school-address {
            font-size: 10.5px;
            color: #6b7280;
        }
        .header-right .title {
            font-weight: 700;
            font-size: 15px;
            text-decoration: underline;
            color: {{ $pdfPrimary }};
            text-transform: uppercase;
            margin-bottom: 4px;
        }
        .header-right .period {
            font-size: 12.5px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 2px;
        }
        .header-right .year {
            font-size: 12px;
            color: #374151;
        }

        /* ── Fiche étudiant ───────────────────────────────────── */
        .student-info {
            width: 100%;
            margin-bottom: 5px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            background: #f9fafb;
            overflow: hidden;
        }
        .student-info-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .student-info-table td {
            border: none;
            padding: 4px 6px;
            vertical-align: top;
            word-wrap: break-word;
        }

        /* Colonne photo */
        .student-info-table td:first-child {
            width: 90px;
            min-width: 90px;
            text-align: center;
            vertical-align: middle;
            padding: 5px;
            background: #eff6ff;
            border-right: 1px solid #dbeafe;
            display: table-cell;
        }
        .student-info-table td:first-child img {
            width: 70px;
            height: 70px;
            border-radius: 5px;
            object-fit: cover;
            border: 2px solid {{ $pdfPrimary }};
            display: block;
            margin: 0 auto;
        }
        .avatar-fallback {
            width: 70px;
            height: 70px;
            border-radius: 5px;
            border: 2px solid {{ $pdfPrimary }};
            display: table;
            margin: 0 auto;
            background: #e5e7eb;
        }
        .avatar-fallback span {
            display: table-cell;
            vertical-align: middle;
            text-align: center;
            font-size: 22px;
            color: {{ $pdfPrimary }};
            font-weight: 700;
        }
        .matricule-text {
            margin-top: 3px;
            font-weight: 700;
            font-size: 10px;
            text-align: center;
            color: #374151;
        }

        /* Colonnes infos premium — style fiche élève sans ":", labels uppercase muted +
           valeurs bold primary. Table 2 colonnes pour alignement garanti DomPDF. */
        .info-group {
            width: 42%;
            vertical-align: top;
            padding: 3px 6px;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
        }
        .info-table td {
            padding: 3px 0;
            border: none;
            border-bottom: 1px dotted #e5e7eb;
            vertical-align: middle;
        }
        .info-table tr:last-child td { border-bottom: none; }
        .info-table td.info-label {
            font-weight: 700;
            white-space: nowrap;
            padding-right: 10px;
            color: #6b7280;
            font-size: 9.5px;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            width: 1%; /* shrink to content */
        }
        .info-table td.info-value {
            color: {{ $pdfPrimary }};
            font-size: 12.5px;
            font-weight: 700;
            word-wrap: break-word;
        }

        /* ── Tableau matières ─────────────────────────────────── */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
            font-size: 12.5px;
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
            font-size: 11.5px;
            color: #111827;
        }
        .center { text-align: center; }

        /* Lignes "Moyenne enseignement général/technique" — gris bold pour ressortir */
        .summary-row td {
            background-color: #e5e7eb !important;
            font-weight: 700;
            font-size: 13px;
        }

        /* DomPDF ne supporte pas background-color sur <tr>.
           Le background doit toujours être posé sur les <td> enfants. */
        .section-header td {
            background-color: {{ $pdfPrimary }};
            color: {{ $pdfHeaderText }};
            font-weight: 700;
            text-align: center;
            padding: 3px 6px;
            font-size: 12.5px;
        }
        /* :nth-child n'est pas supporté par DomPDF — on utilise .subject-row-even
           posée via $loop->even dans le template Blade (voir tbody ci-dessous). */

        /* Absences */
        .absences-table { width: 100%; margin-bottom: 8px; }

        /* ── Résultats & Statistiques ─────────────────────────── */
        .results-container { width: 100%; margin-bottom: 5px; }
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
            font-size: 13px;
            border-collapse: collapse;
        }
        .results-table th, .stats-table th {
            background: {{ $pdfPrimary }};
            color: {{ $pdfHeaderText }};
            padding: 3px 7px;
            font-size: 12px;
            border: none;
            text-align: left;
        }
        .results-table td, .stats-table td {
            padding: 3px 7px;
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
            padding: 2px 7px;
            min-width: 58px;
            display: inline-block;
            text-align: center;
            font-weight: 700;
            background: #f8fafb;
            font-size: 13px;
        }
        .absences-table { width: 100%; margin-bottom: 4px; font-size: 12.5px; }
        .absences-table td { padding: 3px 5px; }

        /* ── Mentions — grid 2 colonnes (compaction) ──────────── */
        .mentions-grid {
            width: 100%;
            border-collapse: separate;
            border-spacing: 5px 4px;
            margin: 0;
        }
        .mentions-grid > tbody > tr > td.mention-cell {
            width: 50%;
            border: 1px solid #d1d5db;
            border-radius: 5px;
            padding: 0;
            background: #fff;
            font-size: 12px;
            vertical-align: middle;
        }
        .mentions-grid > tbody > tr > td.mention-cell--empty {
            background: transparent;
            border: none;
        }
        /* Mini-table interne : label gauche + checkbox droite centrée verticalement */
        .mention-inner {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
        }
        .mention-inner td {
            border: none;
            padding: 5px 8px;
            vertical-align: middle;
        }
        .mention-inner td.mention-label-cell {
            font-weight: 600;
            color: #111827;
            text-align: left;
        }
        .mention-inner td.mention-check-cell {
            width: 24px;
            text-align: center;
            padding-right: 10px;
        }
        .mention-inner input[type="checkbox"] { vertical-align: middle; margin: 0; }
        /* Legacy mention-* CSS retained for any other call sites */
        .mention-box { display: none; }

        /* ── Décision conseil ─────────────────────────────────── */
        .decision-container {
            margin: 5px 0;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            padding: 5px 8px;
            min-height: 36px;
            background: #f9fafb;
        }
        .decision-title {
            font-weight: 700;
            margin-bottom: 3px;
            text-decoration: underline;
            font-size: 12px;
            text-transform: uppercase;
            color: {{ $pdfPrimary }};
        }

        /* ── Signature ────────────────────────────────────────── */
        .signature-container {
            margin-top: 12px;
            text-align: right;
            page-break-inside: avoid;
        }
        .signature-box {
            display: inline-block;
            text-align: center;
            min-width: 250px;
        }
        .signature-line {
            width: 250px;
            height: 70px;
            border-bottom: 1.5px solid {{ $pdfPrimary }};
            margin-top: 4px;
        }

        /* ── Mode PDF export ──────────────────────────────────── */
        @if($isPdfExport ?? false)
        @page {
            size: A4 portrait;
            margin: 2mm 2mm;
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
        }
        @endif

        @media print {
            body { margin: 0; padding: 0; background: #fff; }
            .container { box-shadow: none; width: 100%; max-width: none; }
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
            font-size: 12px;
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
                        @if(($settings['bulletin_show_edition_date'] ?? '1') == '1')
                        <div class="year">Édition : {{ $date_edition }}</div>
                        @endif
                        @if(($settings['bulletin_show_cycle_info'] ?? '1') == '1')
                        <div class="year">{{ $settings['bulletin_cycle_text'] ?? 'Brevet de Technicien Supérieur' }}</div>
                        <div class="year">{{ $settings['bulletin_cycle_abbreviation'] ?? 'BTS' }}</div>
                        @endif
                        <div class="year" style="font-weight: 700;">Année Scolaire : {{ $anneeUniversitaire->annee_debut }}-{{ $anneeUniversitaire->annee_fin }}</div>
                    </td>
                </tr>
            </table>
        </div>
        @endif

        {{-- Fiche étudiant --}}
        @php
            $prenom   = $etudiant->prenoms ?? $etudiant->prenom ?? '';
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
                        <table class="info-table">
                            <tr>
                                <td class="info-label">Nom et Prénoms</td>
                                <td class="info-value">{{ $etudiant->nom }} {{ $etudiant->prenoms ?? $etudiant->prenom }}</td>
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
                                <td class="info-value">{{ $etudiant->inscriptions->first()->is_redoublant ?? false ? 'Oui' : 'Non' }}</td>
                            </tr>
                            @endif
                            <tr>
                                <td class="info-label">Téléphone</td>
                                <td class="info-value">{{ $etudiant->telephone ?? 'Non renseigné' }}</td>
                            </tr>
                        </table>
                    </td>
                    <td class="info-group">
                        <table class="info-table">
                            <tr>
                                <td class="info-label">Classe</td>
                                <td class="info-value">{{ $classe->libelle ?? $classe->name }}</td>
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
                                @if($showSubjectAverage)<td class="center">{{ number_format($resultat->moyenne, 2) }}</td>@endif
                                @if($showCoefficient)<td class="center">{{ $resultat->coefficient }}</td>@endif
                                @if($showWeightedAverage)<td class="center">{{ number_format($resultat->moyenne * $resultat->coefficient, 2) }}</td>@endif
                                @if($showRankPerSubject)<td class="center">{{ $resultat->rang ?: '-' }}</td>@endif
                                @if($showAbsencesParMatiere)<td class="center">{{ isset($absencesParMatiere[$resultat->matiere_id]) ? $absencesParMatiere[$resultat->matiere_id]['total_heures'] : 0 }}</td>@endif
                                @if($showTeachers)<td>{{ $professeurs[$resultat->matiere_id] ?? 'M.' }}</td>@endif
                                @if($showAppreciations)<td>{{ $resultat->appreciation ?? '-' }}</td>@endif
                            </tr>
                        @endforeach
                    @else
                        <tr><td colspan="{{ $subjectColumnCount }}" class="center">Aucune mati&egrave;re d'enseignement g&eacute;n&eacute;ral</td></tr>
                    @endif
                    @if(($settings['bulletin_show_section_averages'] ?? '1') == '1')
                    <tr class="summary-row">
                        <td colspan="{{ max($subjectColumnCount - 1, 1) }}">Moyenne enseignement g&eacute;n&eacute;ral</td>
                        <td class="center">{{ number_format($moyenneGenerale, 2) }}</td>
                    </tr>
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
                            @if($showSubjectAverage)<td class="center">{{ number_format($resultat->moyenne, 2) }}</td>@endif
                            @if($showCoefficient)<td class="center">{{ $resultat->coefficient }}</td>@endif
                            @if($showWeightedAverage)<td class="center">{{ number_format($resultat->moyenne * $resultat->coefficient, 2) }}</td>@endif
                            @if($showRankPerSubject)<td class="center">{{ $resultat->rang ?: '-' }}</td>@endif
                            @if($showAbsencesParMatiere)<td class="center">{{ isset($absencesParMatiere[$resultat->matiere_id]) ? $absencesParMatiere[$resultat->matiere_id]['total_heures'] : 0 }}</td>@endif
                            @if($showTeachers)<td>{{ $professeurs[$resultat->matiere_id] ?? 'M.' }}</td>@endif
                            @if($showAppreciations)<td>{{ $resultat->appreciation ?? '-' }}</td>@endif
                        </tr>
                    @endforeach
                @else
                    <tr><td colspan="{{ $subjectColumnCount }}" class="center">Aucune mati&egrave;re d'enseignement technique</td></tr>
                @endif
                @if(($settings['bulletin_show_section_averages'] ?? '1') == '1')
                <tr class="summary-row">
                    <td colspan="{{ max($subjectColumnCount - 1, 1) }}">Moyenne enseignement technique</td>
                    <td class="center">{{ number_format($moyenneTechnique, 2) }}</td>
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
                                <thead><tr><th colspan="2">RÉSULTATS</th></tr></thead>
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
                                    @if($periode == 'semestre1' || $periode == 'semestre2')
                                    <tr>
                                        <td>Moyenne {{ $periode == 'semestre1' ? '1er' : '2e' }} Semestre</td>
                                        <td class="center"><span class="result-value-box">{{ number_format($moyenneAvecAssiduite, 2) }}</span></td>
                                    </tr>
                                    @endif
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
                                    @if($periode == 'annuel')
                                    <tr>
                                        <td>Moyenne Semestre 1</td>
                                        <td class="center"><span class="result-value-box">{{ $moyenneSemestre1 !== null ? number_format($moyenneSemestre1, 2) : '-' }}</span></td>
                                    </tr>
                                    <tr>
                                        <td>Moyenne Semestre 2</td>
                                        <td class="center"><span class="result-value-box">{{ $moyenneSemestre2 !== null ? number_format($moyenneSemestre2, 2) : '-' }}</span></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Moyenne Annuelle</strong></td>
                                        <td class="center"><span class="result-value-box"><strong>{{ $moyenneAnnuelle !== null ? number_format($moyenneAnnuelle, 2) : '-' }}</strong></span></td>
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
                        @php
                            // Collecte des mentions actives → grid 2 colonnes pour économiser l'espace
                            $_mentions = [];
                            $_autoCalc = ($settings['bulletin_auto_calculate_mention'] ?? '1') == '1';
                            $_felThresh = floatval($settings['bulletin_felicitation_threshold'] ?? 16);
                            $_encThresh = floatval($settings['bulletin_encouragement_threshold'] ?? 14);
                            $_honThresh = floatval($settings['bulletin_honor_roll_threshold'] ?? 12);
                            $_warnThresh = floatval($settings['bulletin_work_warning_threshold'] ?? 8);
                            if (($settings['bulletin_show_felicitation'] ?? '1') == '1') {
                                $_mentions[] = ['label' => 'Félicitation', 'checked' => $_autoCalc && $moyenneGlobale >= $_felThresh];
                            }
                            if (($settings['bulletin_show_encouragement'] ?? '1') == '1') {
                                $_mentions[] = ['label' => 'Encouragement', 'checked' => $_autoCalc && $moyenneGlobale >= $_encThresh && $moyenneGlobale < $_felThresh];
                            }
                            if (($settings['bulletin_show_honor_roll'] ?? '1') == '1') {
                                $_mentions[] = ['label' => 'Tableau d\'honneur', 'checked' => $_autoCalc && $moyenneGlobale >= $_honThresh && $moyenneGlobale < $_encThresh];
                            }
                            if (($settings['bulletin_show_work_warning'] ?? '1') == '1') {
                                $_mentions[] = ['label' => 'Avertissement (Travail)', 'checked' => $_autoCalc && $moyenneGlobale >= $_warnThresh && $moyenneGlobale < 10];
                            }
                            if (($settings['bulletin_show_conduct_blame'] ?? '1') == '1') {
                                $_mentions[] = ['label' => 'Blâme (Conduite)', 'checked' => false];
                            }
                            $_mentionsChunks = array_chunk($_mentions, 2);
                        @endphp
                        <div style="margin-top: 5px;">
                            <table class="mentions-grid">
                                @foreach($_mentionsChunks as $row)
                                <tr>
                                    @foreach($row as $m)
                                        <td class="mention-cell">
                                            <table class="mention-inner">
                                                <tr>
                                                    <td class="mention-label-cell">{{ $m['label'] }}</td>
                                                    <td class="mention-check-cell"><input type="checkbox" {{ $m['checked'] ? 'checked' : '' }}></td>
                                                </tr>
                                            </table>
                                        </td>
                                    @endforeach
                                    {{-- Cellule vide si row impaire pour préserver l'alignement --}}
                                    @if(count($row) === 1)<td class="mention-cell mention-cell--empty"></td>@endif
                                </tr>
                                @endforeach
                            </table>
                        </div>
                        @endif
                    </td>

                    @if(($settings['bulletin_show_statistics'] ?? '1') == '1')
                    <td class="results-right">
                        <div class="stats-card">
                            <table class="stats-table">
                                <thead><tr><th colspan="2">STATISTIQUES — {{ $periode == 'semestre1' ? 'SEMESTRE 1' : ($periode == 'semestre2' ? 'SEMESTRE 2' : 'ANNUEL') }}</th></tr></thead>
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
            <div style="min-height: 36px; font-size: 11.5px;">{{ $appreciation }}</div>
        </div>
        @endif

        {{-- Signature --}}
        @if(($settings['bulletin_show_signature'] ?? '1') == '1' || ($settings['bulletin_show_director_signature'] ?? '1') == '1')
        @php
            $directorTitle = $settings['director_title'] ?? \App\Helpers\SettingsHelper::get('director_title', 'Directeur');
            $directorName  = $settings['director_name']  ?? \App\Helpers\SettingsHelper::get('director_name', '');
        @endphp
        <div class="signature-container">
            @if(($settings['bulletin_show_director_signature'] ?? '1') == '1')
            <div class="signature-box">
                <div style="font-size: 11.5px;">{{ $directorTitle }}</div>
                <div class="signature-line"></div>
                @if($directorName)
                    <div style="margin-top: 4px; font-weight: 700; font-size: 11px;">{{ $directorName }}</div>
                @endif
            </div>
            @endif
        </div>
        @endif

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

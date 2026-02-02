<!DOCTYPE html>
<html lang="fr">
<head>
    @include('pdf.partials.theme')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulletin de Notes - {{ $etudiant->nom }} {{ $etudiant->prenoms ?? $etudiant->prenom }}</title>
    <style>
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: {{ $settings['bulletin_font_size'] ?? '11' }}px;
            margin: 0;
            padding: 0;
            background-color: #ffffff;
            line-height: 1.2;
            color: #111827;
        }
        * {
            box-sizing: border-box;
        }
        .container {
            width: 100%;
            max-width: 820px;
            margin: 0 auto;
            background-color: #ffffff;
            padding: 14px 16px;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
        }
        .top-entete {
            width: 100%;
            text-align: center;
            font-size: 10px;
            color: #1f2937;
            padding-bottom: 6px;
            margin-bottom: 10px;
            border-bottom: 1px solid #e5e7eb;
        }
        .top-entete .line-strong {
            font-weight: bold;
        }
        .header {
            width: 100%;
            margin-bottom: 14px;
            padding: 10px;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            background: #f8fafc;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .header-table td {
            border: none;
            padding: 4px 6px;
            vertical-align: middle;
            word-wrap: break-word;
        }
        .header-logo {
            width: 33%;
            text-align: left;
        }
        .header-info {
            width: 67%;
            text-align: right;
        }
        .logo {
            width: 90px;
            height: 90px;
            object-fit: contain;
            border-radius: 10px;
        }
        .school-name {
            font-weight: bold;
            font-size: 14px;
            color: #0f5132;
            text-transform: uppercase;
        }
        .school-contact {
            font-size: 9px;
            color: #4b5563;
        }
        .bulletin-title {
            font-weight: bold;
            font-size: 12px;
            text-transform: uppercase;
            margin-top: 6px;
            color: #1f2937;
        }
        .bulletin-period {
            font-size: 10px;
            color: #1f2937;
            margin-top: 2px;
        }
        .academic-year {
            font-size: 10px;
            font-weight: bold;
            color: #1f2937;
            margin-top: 6px;
        }
        .student-info {
            width: 100%;
            margin-bottom: 12px;
            background-color: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 8px;
        }
        .student-info-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .student-info-table td {
            border: none;
            padding: 6px;
            vertical-align: top;
            word-wrap: break-word;
        }
        .student-info-table td:first-child {
            width: 150px;
            text-align: center;
            vertical-align: top;
            padding: 8px;
            min-width: 150px;
            display: table-cell;
        }
        .student-info-table td:first-child img {
            width: 96px;
            height: 96px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #0f5132;
            display: block;
            margin: 0 auto;
        }
        .student-info-table td:first-child .avatar-fallback {
            width: 96px;
            height: 96px;
            border-radius: 50%;
            border: 2px solid #0f5132;
            display: block;
            margin: 0 auto;
            background: #eef2f7;
            text-align: center;
            line-height: 96px;
            font-size: 28px;
            color: #475569;
            font-weight: bold;
        }
        .student-info-table td:first-child .matricule-text {
            margin-top: 8px;
            font-weight: bold;
            font-size: 9px;
            text-align: center;
            width: 100%;
            white-space: nowrap;
        }
        .info-group {
            width: 40%;
            vertical-align: top;
        }
        .info-row {
            margin-bottom: 4px;
        }
        .info-label {
            font-weight: bold;
            display: inline-block;
            width: 120px;
            color: #111827;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            font-size: 10px;
        }
        th, td {
            border: 1px solid #d1d5db;
            padding: 5px;
            text-align: left;
        }
        th {
            background-color: #f3f4f6;
            font-weight: bold;
            text-align: center;
        }
        .center {
            text-align: center;
        }
        .section-header {
            background-color: #0f5132;
            color: #ffffff;
            font-weight: bold;
            text-align: center;
            padding: 8px 10px;
        }
        .subject-row:nth-child(even) {
            background-color: #f8fafc;
        }
        .summary-row {
            background-color: #f3f4f6;
            font-weight: bold;
        }
        .results-container {
            width: 100%;
            margin-bottom: 14px;
        }
        .results-container-table {
            width: 100%;
            border-collapse: collapse;
        }
        .results-container-table td {
            border: none;
            padding: 0;
            vertical-align: top;
        }
        .results-left {
            width: 50%;
            padding-right: 6px;
        }
        .results-right {
            width: 50%;
            padding-left: 6px;
        }
        .results-card,
        .stats-card {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            background: #ffffff;
            padding: 8px;
        }
        .results-table, .stats-table {
            width: 100%;
            font-size: 10px;
            border-collapse: separate;
            border-spacing: 0;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            overflow: hidden;
            background: #ffffff;
        }
        .results-table th,
        .results-table td,
        .stats-table th,
        .stats-table td {
            padding: 6px 8px;
            border-bottom: 1px solid #e5e7eb;
        }
        .results-table tr:last-child td,
        .stats-table tr:last-child td {
            border-bottom: none;
        }
        .results-table th,
        .stats-table th {
            background: #f3f4f6;
            text-align: left;
        }
        .mention-box {
            width: 100%;
            margin-bottom: 5px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            font-size: 9px;
            background: #ffffff;
        }
        .mention-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 6px 10px;
            border-bottom: 1px solid #e5e7eb;
        }
        .mention-row:last-child {
            border-bottom: none;
        }
        .mention-label {
            font-weight: 600;
            color: #111827;
        }
        .mention-value {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            min-width: 28px;
        }
        .mention-value input {
            width: 14px;
            height: 14px;
        }
        .decision-container {
            margin: 16px 0;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            padding: 10px;
            min-height: 60px;
            background: #f9fafb;
        }
        .decision-title {
            font-weight: bold;
            margin-bottom: 8px;
            text-transform: uppercase;
        }
        .signature-container {
            margin-top: 16px;
            text-align: right;
        }
        .signature-box {
            display: inline-block;
            text-align: center;
            min-width: 220px;
        }
        .signature-line {
            width: 220px;
            height: 50px;
            border-bottom: 1px solid #111827;
            margin-top: 6px;
        }
        @if($isPdfExport ?? false)
        @page {
            margin: 18mm 12mm !important;
        }
        body.pdf-export {
            margin: 0;
            padding: 0;
            background-color: white;
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: {{ $settings['bulletin_font_size'] ?? '11' }}px;
        }
        body.pdf-export .container {
            width: 100%;
            max-width: none;
            margin: 0 auto;
            padding: 0;
            border: none;
        }
        @endif
    </style>
</head>
<body @if($isPdfExport ?? false)class="pdf-export"@endif>
    <div class="container">
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
                $anneeAffichee = $bulletin && $bulletin->anneeUniversitaire ? $bulletin->anneeUniversitaire : $anneeUniversitaire;
            @endphp
            <div class="header">
                <table class="header-table">
                    <tr>
                        <td class="header-logo">
                            @if(($settings['bulletin_show_logo'] ?? '1') == '1' && isset($logoBase64) && $logoBase64)
                                <img src="{{ $logoBase64 }}" alt="Logo" class="logo">
                            @endif
                        </td>
                        <td class="header-info">
                            @if(($settings['bulletin_show_school_info'] ?? '1') == '1')
                                <div class="school-name">
                                    {{ $settings['bulletin_school_name_custom'] ?: $settings['school_name'] }}
                                </div>
                                <div class="school-contact">
                                    {{ $settings['school_address'] }} • Tel: {{ $settings['school_phone'] }} • {{ $settings['school_email'] }}
                                </div>
                            @endif
                            <div class="bulletin-title">BULLETIN DE NOTES</div>
                            <div class="bulletin-period">
                                @if($periode == 'semestre1')
                                    PREMIER SEMESTRE
                                @elseif($periode == 'semestre2')
                                    DEUXIEME SEMESTRE
                                @else
                                    ANNUEL
                                @endif
                            </div>
                            @if(($settings['bulletin_show_edition_date'] ?? '1') == '1')
                                <div class="bulletin-period">Edition du: {{ $date_edition }}</div>
                            @endif
                            <div class="academic-year">Annee Scolaire: {{ $anneeAffichee->annee_debut }}-{{ $anneeAffichee->annee_fin }}</div>
                        </td>
                    </tr>
                </table>
            </div>
        @endif

        @if(true)
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
                                <div class="avatar-fallback">{{ $initials }}</div>
                            @endif
                            @if(($settings['bulletin_show_matricule'] ?? '1') == '1')
                                <div class="matricule-text">{{ $etudiant->matricule }}</div>
                            @endif
                        </td>
                        <td class="info-group">
                            <div class="info-row">
                                <span class="info-label">Nom et Prenoms :</span>
                                <span class="info-value">{{ $etudiant->nom }} {{ $etudiant->prenoms ?? $etudiant->prenom }}</span>
                            </div>
                            @if(($settings['bulletin_show_birth_date'] ?? '1') == '1')
                                <div class="info-row">
                                    <span class="info-label">Date de Naissance :</span>
                                    <span class="info-value">{{ $etudiant->date_naissance ? \Carbon\Carbon::parse($etudiant->date_naissance)->format('d/m/Y') : 'Non renseignee' }}</span>
                                </div>
                            @endif
                            <div class="info-row">
                                <span class="info-label">Lieu de Naissance :</span>
                                <span class="info-value">{{ $etudiant->lieu_naissance ?? 'Non renseigne' }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Genre :</span>
                                <span class="info-value">{{ $etudiant->genre == 'M' ? 'Masculin' : 'Feminin' }}</span>
                            </div>
                            @if(($settings['bulletin_show_redoublant'] ?? '1') == '1')
                                <div class="info-row">
                                    <span class="info-label">Redoublant :</span>
                                    <span class="info-value">{{ $etudiant->inscriptions->first()->is_redoublant ?? false ? 'Oui' : 'Non' }}</span>
                                </div>
                            @endif
                            <div class="info-row">
                                <span class="info-label">Telephone :</span>
                                <span class="info-value">{{ $etudiant->telephone ?? 'Non renseigne' }}</span>
                            </div>
                        </td>
                        <td class="info-group">
                            <div class="info-row">
                                <span class="info-label">Classe :</span>
                                <span class="info-value">{{ $classe->libelle ?? $classe->name }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Annee d'etude :</span>
                                <span class="info-value">{{ $classe->niveau->libelle ?? $classe->niveau->name ?? ($classe->annee ?? 'N/A') }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Filiere :</span>
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
        @endif

        @if(($settings['bulletin_show_subjects_table'] ?? '1') == '1')
            <table>
                <thead>
                    <tr>
                        <th>Matiere</th>
                        @if(($settings['bulletin_show_subject_average'] ?? '1') == '1')<th>Moyenne M</th>@endif
                        @if(($settings['bulletin_show_coefficient'] ?? '1') == '1')<th>Coef C</th>@endif
                        @if(($settings['bulletin_show_weighted_average'] ?? '1') == '1')<th>Moy Ponderee M*C</th>@endif
                        @if(($settings['bulletin_show_rank_per_subject'] ?? '1') == '1')<th>Rang</th>@endif
                        @if(($settings['bulletin_show_teachers'] ?? '1') == '1')<th>Professeurs</th>@endif
                        @if(($settings['bulletin_show_appreciations'] ?? '1') == '1')<th>Appreciations</th>@endif
                    </tr>
                    @if(($settings['bulletin_show_general_subjects'] ?? '1') == '1')
                        <tr class="section-header">
                            <td colspan="7">Enseignement General</td>
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
                                    @if(($settings['bulletin_show_teachers'] ?? '1') == '1')<td>{{ $professeurs[$resultat->matiere_id] ?? 'M.' }}</td>@endif
                                    @if(($settings['bulletin_show_appreciations'] ?? '1') == '1')<td>{{ $resultat->appreciation ?? 'Nul' }}</td>@endif
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="7" class="center">Aucune matiere d'enseignement general</td>
                            </tr>
                        @endif
                        @if(($settings['bulletin_show_section_averages'] ?? '1') == '1')
                            <tr class="summary-row">
                                <td>Moyenne enseignement general</td>
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
                                    @if(($settings['bulletin_show_teachers'] ?? '1') == '1')<td>{{ $professeurs[$resultat->matiere_id] ?? 'M.' }}</td>@endif
                                    @if(($settings['bulletin_show_appreciations'] ?? '1') == '1')<td>{{ $resultat->appreciation ?? 'Nul' }}</td>@endif
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="7" class="center">Aucune matiere d'enseignement technique</td>
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
                            <td>Absences justifiees</td>
                            <td class="center" style="width: 50%;">{{ isset($absencesJustifiees) ? $absencesJustifiees : (isset($absences_justifiees) ? $absences_justifiees : (isset($bulletin->absences_justifiees) ? $bulletin->absences_justifiees : '00')) }} Heure(s)</td>
                        </tr>
                    @endif
                    @if(($settings['bulletin_show_unjustified_absences'] ?? '1') == '1')
                        <tr>
                            <td>Absences non justifiees</td>
                            <td class="center">{{ isset($absencesNonJustifiees) ? $absencesNonJustifiees : (isset($absences_non_justifiees) ? $absences_non_justifiees : (isset($bulletin->absences_non_justifiees) ? $bulletin->absences_non_justifiees : '00')) }} Heure(s)</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        @endif

        @if(($settings['bulletin_show_results_section'] ?? '1') == '1')
            <div class="results-container">
                <table class="results-container-table">
                    <tr>
                        <td class="results-left">
                            <div class="results-card">
                            <table class="results-table">
                                <thead>
                                    <tr>
                                        <th colspan="2">RESULTATS</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if(($settings['bulletin_show_raw_average'] ?? '1') == '1')
                                        <tr>
                                            <td>Moyenne Brute</td>
                                            <td class="center" style="width: 140px;">
                                                <div style="border: 1px solid #111827; padding: 4px; width: 60px; display: inline-block;">{{ number_format($moyenneGlobale, 2) }}</div>
                                            </td>
                                        </tr>
                                    @endif
                                    @if(($settings['bulletin_show_attendance_note'] ?? '1') == '1')
                                        <tr>
                                            <td>Note d'assiduite</td>
                                            <td class="center">
                                                <div style="border: 1px solid #111827; padding: 4px; width: 60px; display: inline-block;">
                                                    {{ $note_assiduite > 0 ? '+' . number_format($note_assiduite, 2) : number_format($note_assiduite, 2) }}
                                                </div>
                                            </td>
                                        </tr>
                                    @endif
                                    @if(($settings['bulletin_show_semester_average'] ?? '1') == '1')
                                        <tr>
                                            <td>Moyenne {{ $periode == 'semestre1' ? '1er' : '2e' }} Semestre</td>
                                            <td class="center">
                                                <div style="border: 1px solid #111827; padding: 4px; width: 60px; display: inline-block;">{{ number_format($moyenneAvecAssiduite, 2) }}</div>
                                            </td>
                                        </tr>
                                    @endif
                                    @if(($settings['bulletin_show_student_rank'] ?? '1') == '1')
                                        <tr>
                                            <td>Rang</td>
                                            <td class="center">
                                                <div style="border: 1px solid #111827; padding: 4px; width: 60px; display: inline-block;">{{ $rang }}</div>
                                            </td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                            </div>

                            @if(($settings['bulletin_show_mentions'] ?? '1') == '1')
                                <div style="margin-top: 12px;">
                                    @if(($settings['bulletin_show_felicitation'] ?? '1') == '1')
                                        <div class="mention-box">
                                            @php
                                                $felicitationThreshold = floatval($settings['bulletin_felicitation_threshold'] ?? 16);
                                                $isChecked = ($settings['bulletin_auto_calculate_mention'] ?? '1') == '1' ? ($moyenneGlobale >= $felicitationThreshold) : false;
                                            @endphp
                                            <div class="mention-row">
                                                <span class="mention-label">Felicitation</span>
                                                <span class="mention-value"><input type="checkbox" {{ $isChecked ? 'checked' : '' }}></span>
                                            </div>
                                        </div>
                                    @endif
                                    @if(($settings['bulletin_show_encouragement'] ?? '1') == '1')
                                        <div class="mention-box">
                                            @php
                                                $encouragementThreshold = floatval($settings['bulletin_encouragement_threshold'] ?? 14);
                                                $felicitationThreshold = floatval($settings['bulletin_felicitation_threshold'] ?? 16);
                                                $isChecked = ($settings['bulletin_auto_calculate_mention'] ?? '1') == '1' ? ($moyenneGlobale >= $encouragementThreshold && $moyenneGlobale < $felicitationThreshold) : false;
                                            @endphp
                                            <div class="mention-row">
                                                <span class="mention-label">Encouragement</span>
                                                <span class="mention-value"><input type="checkbox" {{ $isChecked ? 'checked' : '' }}></span>
                                            </div>
                                        </div>
                                    @endif
                                    @if(($settings['bulletin_show_honor_roll'] ?? '1') == '1')
                                        <div class="mention-box">
                                            @php
                                                $honorRollThreshold = floatval($settings['bulletin_honor_roll_threshold'] ?? 12);
                                                $encouragementThreshold = floatval($settings['bulletin_encouragement_threshold'] ?? 14);
                                                $isChecked = ($settings['bulletin_auto_calculate_mention'] ?? '1') == '1' ? ($moyenneGlobale >= $honorRollThreshold && $moyenneGlobale < $encouragementThreshold) : false;
                                            @endphp
                                            <div class="mention-row">
                                                <span class="mention-label">Tableau d'honneur</span>
                                                <span class="mention-value"><input type="checkbox" {{ $isChecked ? 'checked' : '' }}></span>
                                            </div>
                                        </div>
                                    @endif
                                    @if(($settings['bulletin_show_work_warning'] ?? '1') == '1')
                                        <div class="mention-box">
                                            @php
                                                $workWarningThreshold = floatval($settings['bulletin_work_warning_threshold'] ?? 8);
                                                $isChecked = ($settings['bulletin_auto_calculate_mention'] ?? '1') == '1' ? ($moyenneGlobale >= $workWarningThreshold && $moyenneGlobale < 10) : false;
                                            @endphp
                                            <div class="mention-row">
                                                <span class="mention-label">Avertissement (Travail)</span>
                                                <span class="mention-value"><input type="checkbox" {{ $isChecked ? 'checked' : '' }}></span>
                                            </div>
                                        </div>
                                    @endif
                                    @if(($settings['bulletin_show_conduct_blame'] ?? '1') == '1')
                                        <div class="mention-box">
                                            <div class="mention-row">
                                                <span class="mention-label">Blame (Conduite)</span>
                                                <span class="mention-value"><input type="checkbox"></span>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </td>
                        @if(($settings['bulletin_show_statistics'] ?? '1') == '1')
                            <td class="results-right">
                                <div class="stats-card">
                                <table class="stats-table">
                                    <thead>
                                        <tr>
                                            <th colspan="2">STATISTIQUES</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if(($settings['bulletin_show_highest_average'] ?? '1') == '1')
                                            <tr>
                                                <td>Plus forte moyenne</td>
                                                <td class="center" style="width: 60px;">{{ number_format($meilleure_moyenne, 2) }}</td>
                                            </tr>
                                        @endif
                                        @if(($settings['bulletin_show_lowest_average'] ?? '1') == '1')
                                            <tr>
                                                <td>Plus faible moyenne</td>
                                                <td class="center">{{ number_format($plus_faible_moyenne, 2) }}</td>
                                            </tr>
                                        @endif
                                        @if(($settings['bulletin_show_class_average'] ?? '1') == '1')
                                            <tr>
                                                <td>Moyenne de la classe</td>
                                                <td class="center">{{ number_format($moyenne_classe, 2) }}</td>
                                            </tr>
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

        @if(($settings['bulletin_show_council_decision'] ?? '1') == '1')
            <div class="decision-container">
                <div class="decision-title">Decision du conseil de classe</div>
                <div style="min-height: 40px;">
                    {{ $appreciation }}
                </div>
            </div>
        @endif

        @if(($settings['bulletin_show_signature'] ?? '1') == '1' || ($settings['bulletin_show_director_signature'] ?? '1') == '1')
            @php
                $directorTitle = $settings['director_title'] ?? \App\Helpers\SettingsHelper::get('director_title', 'Directeur');
                $directorName = $settings['director_name'] ?? \App\Helpers\SettingsHelper::get('director_name', '');
            @endphp
            <div class="signature-container">
                <div class="signature-box">
                    <div>{{ $directorTitle }}</div>
                    <div class="signature-line"></div>
                    @if($directorName)
                        <div style="margin-top: 6px; font-weight: bold;">{{ $directorName }}</div>
                    @endif
                </div>
            </div>
        @endif
    </div>
</body>
</html>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulletin de Notes - {{ $etudiant->nom }} {{ $etudiant->prenoms ?? $etudiant->prenom }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: {{ $settings['bulletin_font_size'] ?? '11' }}px;
            margin: 0;
            padding: 0;
            background-color: white;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            padding: 10px;
            box-shadow: none;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }
        .header-left, .header-right {
            flex: 1;
            font-size: 10px;
            line-height: 1.2;
        }
        .header-center {
            flex: 2;
            text-align: center;
        }
        .logo {
            width: 80px;
            height: 80px;
            margin-bottom: 10px;
        }
        .school-name {
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 5px;
        }
        .school-address {
            font-size: 9px;
        }
        .title {
            font-weight: bold;
            font-size: 12px;
            text-decoration: underline;
            margin-bottom: 5px;
        }
        .student-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            background-color: #f9f9f9;
            padding: 10px;
            border: 1px solid #ddd;
        }
        .info-group {
            flex: 1;
        }
        .info-row {
            display: flex;
            margin-bottom: 5px;
        }
        .info-label {
            font-weight: bold;
            width: 120px;
        }
        .info-value {
            flex: 1;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 10px;
        }
        th, td {
            border: 1px solid #000;
            padding: 5px;
            text-align: left;
        }
        th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }
        .center {
            text-align: center;
        }
        .section-header {
            background-color: #e0e0e0;
            font-weight: bold;
            text-align: center;
        }
        .subject-row:nth-child(even) {
            background-color: #f9f9f9;
        }
        .summary-row {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        .absences-table {
            width: 100%;
            margin-bottom: 15px;
        }
        .results-container {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .results-left, .results-right {
            flex: 1;
            margin-right: 10px;
        }
        .results-right {
            margin-right: 0;
            margin-left: 10px;
        }
        .results-table, .stats-table {
            width: 100%;
            font-size: 10px;
        }
        .mention-box {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 5px;
            padding: 2px 5px;
            border: 1px solid #ccc;
            font-size: 9px;
        }
        .mention-label {
            flex: 1;
        }
        .mention-value {
            width: 20px;
            text-align: center;
        }
        .decision-container {
            margin: 20px 0;
            border: 1px solid #000;
            padding: 10px;
            min-height: 60px;
        }
        .decision-title {
            font-weight: bold;
            margin-bottom: 10px;
            text-decoration: underline;
        }
        .signature-container {
            margin-top: 30px;
            text-align: right;
        }
        .signature-box {
            display: inline-block;
            text-align: center;
            margin-left: 50px;
        }
        .signature-line {
            width: 200px;
            height: 60px;
            border-bottom: 1px solid #000;
            margin-top: 10px;
        }
        .print-button {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        @media print {
            body {
                margin: 0;
                padding: 0;
                background-color: white;
            }
            .container {
                box-shadow: none;
            }
            .print-button {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        @if(($settings['bulletin_show_header'] ?? '1') == '1')
        <div class="header">
            <div class="header-left">
                @if(($settings['bulletin_show_republic_info'] ?? '1') == '1')
                <div>{{ $settings['bulletin_republic_text'] ?? 'République de Côte d\'Ivoire' }}</div>
                <div>{{ $settings['bulletin_union_text'] ?? 'Union-Discipline-Travail' }}</div>
                @endif
                @if(($settings['bulletin_show_ministry_info'] ?? '1') == '1')
                <div>{{ $settings['bulletin_ministry_text'] ?? 'Ministère de l\'Enseignement Supérieur' }}</div>
                <div>et de la Recherche Scientifique</div>
                @endif
            </div>
            <div class="header-center">
                @if(($settings['bulletin_show_logo'] ?? '1') == '1' && isset($logoBase64) && $logoBase64)
                <img src="{{ $logoBase64 }}" alt="Logo ESBTP" class="logo">
                @endif
                @if(($settings['bulletin_show_school_info'] ?? '1') == '1')
                <div class="school-name">
                    {{ $settings['bulletin_school_name_custom'] ?: $settings['school_name'] }}
                </div>
                <div class="school-address">{{ $settings['school_address'] }} • Tel: {{ $settings['school_phone'] }} • {{ $settings['school_email'] }}</div>
                @endif
            </div>
            <div class="header-right">
                <div class="title">BULLETIN DE NOTES</div>
                <div>
                    @if($periode == 'semestre1')
                        PREMIER SEMESTRE
                    @elseif($periode == 'semestre2')
                        DEUXIÈME SEMESTRE
                    @else
                        ANNUEL
                    @endif
                </div>
                @if(($settings['bulletin_show_edition_date'] ?? '1') == '1')
                <div>Édition du: {{ $date_edition }}</div>
                @endif
                @if(($settings['bulletin_show_cycle_info'] ?? '1') == '1')
                <div>Cycle: {{ $settings['bulletin_cycle_text'] ?? 'Brevet de Technicien Supérieur' }}</div>
                <div>{{ $settings['bulletin_cycle_abbreviation'] ?? 'BTS' }}</div>
                @endif
                <div>Année Scolaire: {{ $anneeUniversitaire->libelle ?? $anneeUniversitaire->name ?? '2022-2023' }}</div>
            </div>
        </div>
        @endif

        @if(($settings['bulletin_show_student_info'] ?? '1') == '1')
        <div class="student-info">
            <div class="info-group">
                @if(($settings['bulletin_show_matricule'] ?? '1') == '1')
                <div class="info-row">
                    <div class="info-label">Matricule :</div>
                    <div class="info-value">{{ $etudiant->matricule }}</div>
                </div>
                @endif
                <div class="info-row">
                    <div class="info-label">Nom et Prénoms :</div>
                    <div class="info-value">{{ $etudiant->nom }} {{ $etudiant->prenoms ?? $etudiant->prenom }}</div>
                </div>
                @if(($settings['bulletin_show_birth_date'] ?? '1') == '1')
                <div class="info-row">
                    <div class="info-label">Date de Naissance :</div>
                    <div class="info-value">{{ \Carbon\Carbon::parse($etudiant->date_naissance)->format('d/m/Y') }}</div>
                </div>
                @endif
                @if(($settings['bulletin_show_redoublant'] ?? '1') == '1')
                <div class="info-row">
                    <div class="info-label">Redoublant :</div>
                    <div class="info-value">{{ $etudiant->inscriptions->first()->is_redoublant ?? false ? 'Oui' : 'Non' }}</div>
                </div>
                @endif
            </div>
            <div class="info-group">
                @if(($settings['bulletin_show_class_info'] ?? '1') == '1')
                <div class="info-row">
                    <div class="info-label">Classe :</div>
                    <div class="info-value">{{ $classe->libelle ?? $classe->name }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Année d'étude :</div>
                    <div class="info-value">{{ $classe->niveau->libelle ?? $classe->niveau->name ?? ($classe->annee ?? 'N/A') }}</div>
                </div>
                @endif
                @if(($settings['bulletin_show_effectif'] ?? '1') == '1')
                <div class="info-row">
                    <div class="info-label">Effectif :</div>
                    <div class="info-value">{{ $effectif }}</div>
                </div>
                @endif
            </div>
        </div>
        @endif

        @if(($settings['bulletin_show_subjects_table'] ?? '1') == '1')
        <table>
            <thead>
                <tr>
                    <th>Matière</th>
                    @if(($settings['bulletin_show_subject_average'] ?? '1') == '1')<th>Moyenne M</th>@endif
                    @if(($settings['bulletin_show_coefficient'] ?? '1') == '1')<th>Coef C</th>@endif
                    @if(($settings['bulletin_show_weighted_average'] ?? '1') == '1')<th>Moy Pondérée M*C</th>@endif
                    @if(($settings['bulletin_show_rank_per_subject'] ?? '1') == '1')<th>Rang</th>@endif
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
                                @if(($settings['bulletin_show_teachers'] ?? '1') == '1')<td>{{ $professeurs[$resultat->matiere_id] ?? 'M.' }}</td>@endif
                                @if(($settings['bulletin_show_appreciations'] ?? '1') == '1')<td>{{ $resultat->appreciation ?? 'Nul' }}</td>@endif
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="7" class="center">Aucune matière d'enseignement général</td>
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
                            @if(($settings['bulletin_show_teachers'] ?? '1') == '1')<td>{{ $professeurs[$resultat->matiere_id] ?? 'M.' }}</td>@endif
                            @if(($settings['bulletin_show_appreciations'] ?? '1') == '1')<td>{{ $resultat->appreciation ?? 'Nul' }}</td>@endif
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="7" class="center">Aucune matière d'enseignement technique</td>
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

        @if(($settings['bulletin_show_results_section'] ?? '1') == '1')
        <div class="results-container">
            <div class="results-left">
                <table class="results-table">
                    <thead>
                        <tr class="section-header">
                            <td colspan="2">RÉSULTATS</td>
                        </tr>
                    </thead>
                    <tbody>
                        @if(($settings['bulletin_show_raw_average'] ?? '1') == '1')
                        <tr>
                            <td>Moyenne Brute</td>
                            <td class="center" style="width: 140px;">
                                <div style="border: 1px solid #000; padding: 4px; width: 60px; display: inline-block;">{{ number_format($moyenneGlobale, 2) }}</div>
                            </td>
                        </tr>
                        @endif
                        @if(($settings['bulletin_show_attendance_note'] ?? '1') == '1')
                        <tr>
                            <td>Note d'assiduité</td>
                            <td class="center">
                                <div style="border: 1px solid #000; padding: 4px; width: 60px; display: inline-block;">
                                    {{ $note_assiduite > 0 ? '+' . number_format($note_assiduite, 2) : number_format($note_assiduite, 2) }}
                                </div>
                            </td>
                        </tr>
                        @endif
                        @if(($settings['bulletin_show_semester_average'] ?? '1') == '1')
                        <tr>
                            <td>Moyenne {{ $periode == 'semestre1' ? '1er' : '2e' }} Semestre</td>
                            <td class="center">
                                <div style="border: 1px solid #000; padding: 4px; width: 60px; display: inline-block;">{{ number_format($moyenneAvecAssiduite, 2) }}</div>
                            </td>
                        </tr>
                        @endif
                        @if(($settings['bulletin_show_student_rank'] ?? '1') == '1')
                        <tr>
                            <td>Rang</td>
                            <td class="center">
                                <div style="border: 1px solid #000; padding: 4px; width: 60px; display: inline-block;">{{ $rang }}</div>
                            </td>
                        </tr>
                        @endif
                    </tbody>
                </table>

                @if(($settings['bulletin_show_mentions'] ?? '1') == '1')
                <div style="margin-top: 15px;">
                    @if(($settings['bulletin_show_felicitation'] ?? '1') == '1')
                    <div class="mention-box">
                        <div class="mention-label">Félicitation</div>
                        <div class="mention-value">
                            @php
                                $felicitationThreshold = floatval($settings['bulletin_felicitation_threshold'] ?? 16);
                                $isChecked = ($settings['bulletin_auto_calculate_mention'] ?? '1') == '1' ? ($moyenneGlobale >= $felicitationThreshold) : false;
                            @endphp
                            <input type="checkbox" {{ $isChecked ? 'checked' : '' }}>
                        </div>
                    </div>
                    @endif
                    @if(($settings['bulletin_show_encouragement'] ?? '1') == '1')
                    <div class="mention-box">
                        <div class="mention-label">Encouragement</div>
                        <div class="mention-value">
                            @php
                                $encouragementThreshold = floatval($settings['bulletin_encouragement_threshold'] ?? 14);
                                $felicitationThreshold = floatval($settings['bulletin_felicitation_threshold'] ?? 16);
                                $isChecked = ($settings['bulletin_auto_calculate_mention'] ?? '1') == '1' ? ($moyenneGlobale >= $encouragementThreshold && $moyenneGlobale < $felicitationThreshold) : false;
                            @endphp
                            <input type="checkbox" {{ $isChecked ? 'checked' : '' }}>
                        </div>
                    </div>
                    @endif
                    @if(($settings['bulletin_show_honor_roll'] ?? '1') == '1')
                    <div class="mention-box">
                        <div class="mention-label">Tableau d'honneur</div>
                        <div class="mention-value">
                            @php
                                $honorRollThreshold = floatval($settings['bulletin_honor_roll_threshold'] ?? 12);
                                $encouragementThreshold = floatval($settings['bulletin_encouragement_threshold'] ?? 14);
                                $isChecked = ($settings['bulletin_auto_calculate_mention'] ?? '1') == '1' ? ($moyenneGlobale >= $honorRollThreshold && $moyenneGlobale < $encouragementThreshold) : false;
                            @endphp
                            <input type="checkbox" {{ $isChecked ? 'checked' : '' }}>
                        </div>
                    </div>
                    @endif
                    @if(($settings['bulletin_show_work_warning'] ?? '1') == '1')
                    <div class="mention-box">
                        <div class="mention-label">Avertissement (Travail)</div>
                        <div class="mention-value">
                            @php
                                $workWarningThreshold = floatval($settings['bulletin_work_warning_threshold'] ?? 8);
                                $isChecked = ($settings['bulletin_auto_calculate_mention'] ?? '1') == '1' ? ($moyenneGlobale >= $workWarningThreshold && $moyenneGlobale < 10) : false;
                            @endphp
                            <input type="checkbox" {{ $isChecked ? 'checked' : '' }}>
                        </div>
                    </div>
                    @endif
                    @if(($settings['bulletin_show_conduct_blame'] ?? '1') == '1')
                    <div class="mention-box">
                        <div class="mention-label">Blâme (Conduite)</div>
                        <div class="mention-value">
                            <input type="checkbox">
                        </div>
                    </div>
                    @endif
                </div>
                @endif
            </div>
            @if(($settings['bulletin_show_statistics'] ?? '1') == '1')
            <div class="results-right">
                <table class="stats-table">
                    <thead>
                        <tr class="section-header">
                            <td colspan="2">STATISTIQUES</td>
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
            @endif
        </div>
        @endif

        @if(($settings['bulletin_show_council_decision'] ?? '1') == '1')
        <div class="decision-container">
            <div class="decision-title">Décision du conseil de classe</div>
            <div style="min-height: 40px;">
                {{ $appreciation }}
            </div>
        </div>
        @endif

        @if(($settings['bulletin_show_signature'] ?? '1') == '1' || ($settings['bulletin_show_director_signature'] ?? '1') == '1')
        <div class="signature-container">
            @if(($settings['bulletin_show_director_signature'] ?? '1') == '1')
            <div class="signature-box">
                <div>Signature de la Directrice des Études</div>
                <div class="signature-line"></div>
            </div>
            @endif
        </div>
        @endif

        {{-- Bouton d'impression supprimé car le fichier est déjà un PDF généré --}}
    </div>
</body>
</html>

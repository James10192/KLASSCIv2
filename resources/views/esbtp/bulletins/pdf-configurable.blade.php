<!DOCTYPE html>
<html lang="fr">
<head>
    @include('pdf.partials.theme')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulletin de Notes - {{ $etudiant->nom }} {{ $etudiant->prenoms ?? $etudiant->prenom }}</title>
    <style>
        /* CSS compatible DomPDF et navigateur */
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: {{ $settings['bulletin_font_size'] ?? '11' }}px;
            margin: 0;
            padding: 0;
            background-color: white;
            line-height: 1.2;
        }
        .container {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            padding: 10px;
        }
        
        /* Reset pour éviter les conflits DomPDF */
        * {
            box-sizing: border-box;
        }
        
        /* Version table pour header (compatible DomPDF) */
        .header {
            width: 100%;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .header-table td {
            border: none;
            padding: 5px;
            vertical-align: top;
            word-wrap: break-word;
        }
        .header-left, .header-right {
            width: 25%;
            font-size: 10px;
            line-height: 1.2;
        }
        .header-center {
            width: 50%;
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
        /* Version table pour student-info (compatible DomPDF) */
        .student-info {
            width: 100%;
            margin-bottom: 20px;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
        }
        .student-info-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .student-info-table td {
            border: none;
            padding: 8px;
            vertical-align: top;
            word-wrap: break-word;
        }

        /* Styles pour la colonne photo et matricule */
        .student-info-table td:first-child {
            width: 150px;
            text-align: center;
            vertical-align: top;
            padding: 10px;
            min-width: 150px;
            display: table-cell;
        }

        .student-info-table td:first-child img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #007bff;
            display: block;
            margin: 0 auto;
        }

        .student-info-table td:first-child > div:not(.matricule-text) {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            border: 2px solid #007bff;
            display: block;
            margin: 0 auto;
            background: #f3f4f6;
            text-align: center;
            line-height: 100px;
            font-size: 32px;
            color: #6b7280;
        }

        .student-info-table td:first-child .matricule-text {
            margin-top: 8px;
            margin-left: auto;
            margin-right: auto;
            font-weight: bold;
            font-size: 9px;
            text-align: center;
            width: 100%;
            white-space: nowrap;
            overflow: visible;
            word-break: keep-all;
            word-wrap: normal;
            writing-mode: horizontal-tb;
            display: block;
            position: relative;
            left: 0;
            right: 0;
        }
        .info-group {
            width: 40%;
            vertical-align: top;
        }
        .info-row {
            margin-bottom: 5px;
        }
        .info-label {
            font-weight: bold;
            display: inline-block;
            width: 120px;
        }
        .info-value {
            display: inline;
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
        /* Version table pour results-container (compatible DomPDF) */
        .results-container {
            width: 100%;
            margin-bottom: 20px;
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
            padding-right: 5px;
        }
        .results-right {
            width: 50%;
            padding-left: 5px;
        }
        .results-table, .stats-table {
            width: 100%;
            font-size: 10px;
        }
        /* Version table pour mention-box (compatible DomPDF) */
        .mention-box {
            width: 100%;
            margin-bottom: 5px;
            border: 1px solid #ccc;
            font-size: 9px;
        }
        .mention-table {
            width: 100%;
            border-collapse: collapse;
        }
        .mention-table td {
            border: none;
            padding: 2px 5px;
        }
        .mention-label {
            text-align: left;
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
        /* Mode PDF simulation pour preview */
        .pdf-mode body {
            margin: 0;
            padding: 0;
            background-color: white;
            font-family: DejaVu Sans, Arial, sans-serif;
        }
        .pdf-mode .container {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            padding: 20mm;
            box-sizing: border-box;
            background-color: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.3);
        }

        @if($isPdfExport ?? false)
        /* Styles spécifiques pour l'export PDF - Approche simplifiée et éprouvée */
        @page {
            margin: 20mm 15mm !important;
        }
        
        html {
            margin: 0;
            padding: 0;
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
            background-color: white;
        }
        
        /* Reset spécifique pour PDF */
        body.pdf-export .student-info-table {
            table-layout: fixed;
            width: 100%;
            border-collapse: collapse;
        }

        body.pdf-export .student-info-table td {
            border: none;
            padding: 8px;
            vertical-align: top;
            word-wrap: break-word;
        }

        /* Styles spécifiques pour la photo et matricule en PDF */
        body.pdf-export .student-info-table td:first-child {
            width: 150px !important;
            min-width: 150px !important;
            text-align: center !important;
            vertical-align: top !important;
            padding: 10px !important;
        }

        body.pdf-export .info-group {
            width: 40% !important;
            vertical-align: top !important;
        }

        body.pdf-export .student-info-table td:first-child img {
            width: 100px !important;
            height: 100px !important;
            border-radius: 50% !important;
            object-fit: cover !important;
            border: 2px solid #007bff !important;
            display: block !important;
            margin: 0 auto !important;
            margin-left: auto !important;
            margin-right: auto !important;
        }

        /* Centrage spécifique pour DomPDF avec transform en fallback */
        body.pdf-export .student-info-table td:first-child img,
        body.pdf-export .student-info-table td:first-child > div:not(.matricule-text) {
            position: relative !important;
            left: 50% !important;
            transform: translateX(-50%) !important;
        }

        body.pdf-export .student-info-table td:first-child .matricule-text {
            margin-top: 8px !important;
            margin-left: auto !important;
            margin-right: auto !important;
            font-weight: bold !important;
            font-size: 9px !important;
            text-align: center !important;
            width: 100% !important;
            white-space: nowrap !important;
            overflow: visible !important;
            word-break: keep-all !important;
            word-wrap: normal !important;
            writing-mode: horizontal-tb !important;
            display: block !important;
            position: relative !important;
            left: 0 !important;
            right: 0 !important;
        }
        body.pdf-export th, 
        body.pdf-export td, 
        body.pdf-export p, 
        body.pdf-export div, 
        body.pdf-export h1, 
        body.pdf-export h2, 
        body.pdf-export h3 {
            margin: 0;
            padding: 2px;
        }
        @endif
        
        @media print {
            body {
                margin: 0;
                padding: 0;
                background-color: white;
            }
            .container {
                box-shadow: none;
                width: 100%;
                max-width: none;
            }
            .print-button, .pdf-toggle {
                display: none !important;
            }
        }
        
        /* Bouton de simulation PDF */
        .pdf-toggle {
            position: fixed;
            top: 10px;
            right: 10px;
            background-color: #28a745;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            z-index: 1000;
            font-size: 12px;
        }
        .pdf-toggle:hover {
            background-color: #218838;
        }
    </style>
</head>
<body @if($isPdfExport ?? false)class="pdf-export"@endif>
    <!-- Bouton pour basculer en mode PDF preview (seulement pour preview web) -->
    @unless($isPdfExport ?? false)
    <button class="pdf-toggle" onclick="togglePDFMode()" id="pdfToggle">Mode PDF</button>
    @endunless
    
    <div class="container">
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
                        <div>{{ $settings['bulletin_ministry_text'] ?? 'Ministère de l\'Enseignement Supérieur' }}</div>
                        <div>et de la Recherche Scientifique</div>
                        @endif
                    </td>
                    <td class="header-center">
                        @if(($settings['bulletin_show_logo'] ?? '1') == '1' && isset($logoBase64) && $logoBase64)
                        <img src="{{ $logoBase64 }}" alt="Logo ESBTP" class="logo">
                        @endif
                        @if(($settings['bulletin_show_school_info'] ?? '1') == '1')
                        <div class="school-name">
                            {{ $settings['bulletin_school_name_custom'] ?: $settings['school_name'] }}
                        </div>
                        <div class="school-address">{{ $settings['school_address'] }} • Tel: {{ $settings['school_phone'] }} • {{ $settings['school_email'] }}</div>
                        @endif
                    </td>
                    <td class="header-right">
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
                        <div>Année Scolaire: {{ $anneeUniversitaire->annee_debut }}-{{ $anneeUniversitaire->annee_fin }}</div>
                    </td>
                </tr>
            </table>
        </div>
        @endif

        {{-- Debug temporaire --}}
        <!-- DEBUG: photoEtudiantBase64 = {{ $photoEtudiantBase64 ? 'PRÉSENT' : 'ABSENT' }} -->
        <!-- DEBUG: etudiant nom = {{ $etudiant->nom ?? 'NON DÉFINI' }} -->

        @if(true) {{-- Force l'affichage des informations étudiant --}}
        <!-- SECTION INFORMATIONS ÉTUDIANT -->
        <div style="background: #e3f2fd; padding: 10px; margin: 15px 0; border: 2px solid #2196f3; text-align: center; font-weight: bold; color: #1976d2;">
            INFORMATIONS DE L'ÉTUDIANT
        </div>
        <div class="student-info">
            <table class="student-info-table">
                <tr>
                    <!-- Photo de l'étudiant -->
                    <td style="width: 150px; min-width: 150px; text-align: center; vertical-align: top; padding: 10px; position: relative;">
                        @if(isset($photoEtudiantBase64) && $photoEtudiantBase64)
                            <img src="{{ $photoEtudiantBase64 }}" alt="Photo" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 2px solid #007bff; display: block; margin: 0 auto;">
                        @else
                            <div style="width: 100px; height: 100px; border-radius: 50%; background: #f3f4f6; border: 2px solid #007bff; text-align: center; line-height: 100px; font-size: 32px; color: #6b7280; margin: 0 auto;">
                                👤
                            </div>
                        @endif
                        @if(($settings['bulletin_show_matricule'] ?? '1') == '1')
                        <div class="matricule-text" style="white-space: nowrap; overflow: visible; word-break: keep-all; word-wrap: normal; display: block; text-align: center; margin: 8px auto 0 auto; width: 100%; position: relative; left: 0; right: 0;">{{ $etudiant->matricule }}</div>
                        @endif
                    </td>
                    <!-- Informations personnelles -->
                    <td class="info-group" style="width: 40%; vertical-align: top;">
                        <div class="info-row">
                            <span class="info-label">Nom et Prénoms :</span>
                            <span class="info-value">{{ $etudiant->nom }} {{ $etudiant->prenoms ?? $etudiant->prenom }}</span>
                        </div>
                        @if(($settings['bulletin_show_birth_date'] ?? '1') == '1')
                        <div class="info-row">
                            <span class="info-label">Date de Naissance :</span>
                            <span class="info-value">{{ $etudiant->date_naissance ? \Carbon\Carbon::parse($etudiant->date_naissance)->format('d/m/Y') : 'Non renseigné' }}</span>
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
                    <!-- Informations académiques -->
                    <td class="info-group" style="width: 40%; vertical-align: top;">
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
            <table class="results-container-table">
                <tr>
                    <td class="results-left">
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
                        @if($periode == 'semestre2')
                        <tr>
                            <td>Moyenne Semestre 1</td>
                            <td class="center">
                                <div style="border: 1px solid #000; padding: 4px; width: 60px; display: inline-block;">{{ $moyenneSemestre1 !== null ? number_format($moyenneSemestre1, 2) : '-' }}</div>
                            </td>
                        </tr>
                        <tr>
                            <td>Moyenne Annuelle</td>
                            <td class="center">
                                <div style="border: 1px solid #000; padding: 4px; width: 60px; display: inline-block;">{{ $moyenneAnnuelle !== null ? number_format($moyenneAnnuelle, 2) : '-' }}</div>
                            </td>
                        </tr>
                        @endif
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
                        <table class="mention-table"><tr>
                            <td class="mention-label">Félicitation</td>
                            <td class="mention-value">
                            @php
                                $felicitationThreshold = floatval($settings['bulletin_felicitation_threshold'] ?? 16);
                                $isChecked = ($settings['bulletin_auto_calculate_mention'] ?? '1') == '1' ? ($moyenneGlobale >= $felicitationThreshold) : false;
                            @endphp
                            <input type="checkbox" {{ $isChecked ? 'checked' : '' }}>
                        </td>
                        </tr></table>
                    </div>
                    @endif
                    @if(($settings['bulletin_show_encouragement'] ?? '1') == '1')
                    <div class="mention-box">
                        <table class="mention-table"><tr>
                            <td class="mention-label">Encouragement</td>
                            <td class="mention-value">
                            @php
                                $encouragementThreshold = floatval($settings['bulletin_encouragement_threshold'] ?? 14);
                                $felicitationThreshold = floatval($settings['bulletin_felicitation_threshold'] ?? 16);
                                $isChecked = ($settings['bulletin_auto_calculate_mention'] ?? '1') == '1' ? ($moyenneGlobale >= $encouragementThreshold && $moyenneGlobale < $felicitationThreshold) : false;
                            @endphp
                            <input type="checkbox" {{ $isChecked ? 'checked' : '' }}>
                        </td>
                        </tr></table>
                    </div>
                    @endif
                    @if(($settings['bulletin_show_honor_roll'] ?? '1') == '1')
                    <div class="mention-box">
                        <table class="mention-table"><tr>
                            <td class="mention-label">Tableau d'honneur</td>
                            <td class="mention-value">
                            @php
                                $honorRollThreshold = floatval($settings['bulletin_honor_roll_threshold'] ?? 12);
                                $encouragementThreshold = floatval($settings['bulletin_encouragement_threshold'] ?? 14);
                                $isChecked = ($settings['bulletin_auto_calculate_mention'] ?? '1') == '1' ? ($moyenneGlobale >= $honorRollThreshold && $moyenneGlobale < $encouragementThreshold) : false;
                            @endphp
                            <input type="checkbox" {{ $isChecked ? 'checked' : '' }}>
                        </td>
                        </tr></table>
                    </div>
                    @endif
                    @if(($settings['bulletin_show_work_warning'] ?? '1') == '1')
                    <div class="mention-box">
                        <table class="mention-table"><tr>
                            <td class="mention-label">Avertissement (Travail)</td>
                            <td class="mention-value">
                            @php
                                $workWarningThreshold = floatval($settings['bulletin_work_warning_threshold'] ?? 8);
                                $isChecked = ($settings['bulletin_auto_calculate_mention'] ?? '1') == '1' ? ($moyenneGlobale >= $workWarningThreshold && $moyenneGlobale < 10) : false;
                            @endphp
                            <input type="checkbox" {{ $isChecked ? 'checked' : '' }}>
                        </td>
                        </tr></table>
                    </div>
                    @endif
                    @if(($settings['bulletin_show_conduct_blame'] ?? '1') == '1')
                    <div class="mention-box">
                        <table class="mention-table"><tr>
                            <td class="mention-label">Blâme (Conduite)</td>
                            <td class="mention-value">
                            <input type="checkbox">
                        </td>
                        </tr></table>
                    </div>
                    @endif
                </div>
                @endif
                    </td>
                    @if(($settings['bulletin_show_statistics'] ?? '1') == '1')
                    <td class="results-right">
                <table class="stats-table">
                            <thead>
                                <tr class="section-header">
                                    <td colspan="2">STATISTIQUES - {{ $periode == 'semestre2' ? 'SEMESTRE 2' : 'SEMESTRE 1' }}</td>
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
                    </td>
                    @endif
                </tr>
            </table>
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
        @php
            $directorTitle = $settings['director_title'] ?? \App\Helpers\SettingsHelper::get('director_title', 'Directeur');
            $directorName = $settings['director_name'] ?? \App\Helpers\SettingsHelper::get('director_name', '');
        @endphp
        <div class="signature-container">
            @if(($settings['bulletin_show_director_signature'] ?? '1') == '1')
            <div class="signature-box">
                <div>{{ $directorTitle }}</div>
                <div class="signature-line"></div>
                @if($directorName)
                    <div style="margin-top: 6px; font-weight: bold;">{{ $directorName }}</div>
                @endif
            </div>
            @endif
        </div>
        @endif

        {{-- Bouton d'impression supprimé car le fichier est déjà un PDF généré --}}
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

        // Auto-basculer en mode PDF si le paramètre est présent dans l'URL
        if (window.location.search.includes('preview=pdf')) {
            togglePDFMode();
        }
    </script>
    @endunless
</body>
</html>

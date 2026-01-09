<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feuille de saisie des notes - {{ $evaluation->titre }}</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            font-size: 10px;
            margin: 0;
            padding: 0.5px;
            color: #333;
            line-height: 1.2;
            background: white;
        }

        .container {
            max-width: 100%;
            background: white;
            padding: 1.5px;
        }

        /* Header principal */
        .header-section {
            background: #007bff;
            color: white;
            padding: 1.5px;
            border-radius: 4px;
            text-align: center;
            margin-bottom: 2px;
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }

        .header-logo {
            max-height: 24px;
            max-width: 60px;
            margin-bottom: 2px;
            filter: brightness(0) invert(1);
        }

        .school-name {
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 2px;
        }

        .school-info {
            font-size: 8px;
            margin-bottom: 2px;
            opacity: 0.9;
        }

        .document-title-section {
            background: rgba(255,255,255,0.2);
            padding: 2px 3px;
            border-radius: 4px;
            margin-top: 2px;
        }

        .document-title {
            font-size: 10px;
            font-weight: 600;
            margin-bottom: 2px;
        }

        .evaluation-info-grid {
            display: table;
            width: 100%;
            font-size: 8.5px;
        }

        .evaluation-info-row {
            display: table-row;
        }

        .evaluation-info-cell {
            display: table-cell;
            width: 25%;
            text-align: center;
            padding: 0.5px;
        }

        .info-badge {
            background: rgba(255,255,255,0.3);
            padding: 0.5px 2px;
            border-radius: 4px;
            display: inline-block;
            margin-top: 0.5px;
        }

        .date-line, .teacher-line {
            border-bottom: 1px solid rgba(255,255,255,0.7);
            padding: 1px 4px;
            display: inline-block;
            min-width: 45px;
        }

        /* KPI Section */
        .kpi-section {
            display: table;
            width: 100%;
            margin-bottom: 2px;
        }

        .kpi-row {
            display: table-row;
        }

        .kpi-card {
            display: table-cell;
            width: 25%;
            padding: 0.5px;
            text-align: center;
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            vertical-align: top;
            font-size: 8px;
        }

        .kpi-card:first-child {
            border-radius: 4px 0 0 4px;
        }

        .kpi-card:last-child {
            border-radius: 0 4px 4px 0;
        }

        .kpi-title {
            font-size: 7.5px;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-bottom: 1px;
        }

        .kpi-value {
            font-size: 10px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 1px;
        }

        .kpi-desc {
            font-size: 7px;
            color: #9ca3af;
        }

        /* Table moderne */
        .notes-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
            background: white;
            border-radius: 4px;
            overflow: hidden;
            font-size: 9px;
        }

        .notes-table th {
            background: #007bff;
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            font-size: 8.5px;
            padding: 1px 1px;
            text-align: center;
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }

        .notes-table td {
            padding: 1px 0.5px;
            border-bottom: 1px solid #e5e7eb;
            text-align: center;
            vertical-align: middle;
            font-size: 8.5px;
        }

        .notes-table tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        .student-number {
            background: #007bff;
            color: white;
            padding: 0.5px 1.5px;
            border-radius: 50%;
            font-weight: bold;
            font-size: 8.5px;
            min-width: 10px;
            display: inline-block;
        }

        .student-matricule {
            font-family: 'Courier New', monospace;
            background: #f3f4f6;
            padding: 0.5px 1.5px;
            border-radius: 3px;
            font-size: 8px;
            color: #374151;
        }

        .student-info-cell {
            text-align: left !important;
            padding-left: 2px !important;
        }

        .student-name {
            font-weight: 600;
            font-size: 9px;
            color: #1f2937;
            line-height: 1.2;
        }

        .student-gender {
            font-size: 7px;
            color: #6b7280;
            margin-top: 0.5px;
        }

        .note-box {
            width: 26px;
            height: 10px;
            border: 2px solid #007bff;
            border-radius: 4px;
            display: inline-block;
            text-align: center;
            font-weight: 700;
            font-size: 8px;
            line-height: 8px;
            background: white;
            margin: 0 1px;
        }

        .note-type-column {
            width: 12%;
        }

        .observations-column {
            width: 22%;
            min-height: 10px;
            border-bottom: 1px solid #d1d5db;
        }

        /* Footer section */
        .footer-section {
            margin-top: 4px;
            display: table;
            width: 100%;
        }

        .footer-left, .footer-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding: 1.5px;
        }

        .summary-card {
            background: #f8f9fa;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            padding: 1.5px;
        }

        .summary-title {
            font-size: 9px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 2px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .summary-grid {
            display: table;
            width: 100%;
        }

        .summary-row {
            display: table-row;
        }

        .summary-cell {
            display: table-cell;
            width: 33.33%;
            text-align: center;
            padding: 0.5px;
        }

        .summary-value {
            font-size: 9px;
            font-weight: bold;
            color: #007bff;
        }

        .summary-label {
            font-size: 8px;
            color: #6b7280;
            margin-top: 0.5px;
        }

        .info-card {
            background: #f8f9fa;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            padding: 1.5px;
            margin-left: 2px;
        }

        .info-field {
            margin-bottom: 2px;
        }

        .info-label {
            font-size: 8px;
            color: #6b7280;
            margin-bottom: 1px;
        }

        .info-value {
            font-size: 9px;
            font-weight: 600;
            color: #374151;
        }

        /* Informations de génération */
        .generation-info {
            text-align: center;
            font-size: 8px;
            color: #6b7280;
            margin-top: 4px;
            padding-top: 3px;
            border-top: 1px solid #e5e7eb;
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 15px 10px;
            color: #6b7280;
        }

        .empty-icon {
            font-size: 18px;
            margin-bottom: 10px;
            color: #d1d5db;
        }

        /* Print optimizations */
        @media print {
            body {
                background: white;
                padding: 1.5px;
            }

            .container {
                padding: 1.5px;
            }

            .header-section {
                margin-bottom: 6px;
            }

            .kpi-section {
                margin-bottom: 6px;
            }

            .footer-section {
                margin-top: 6px;
            }
        }

        @page {
            margin: 0.5cm;
            size: A4 portrait;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header Section -->
        <div class="header-section">
            @if($etablissement['logo'] && file_exists(storage_path('app/public/' . $etablissement['logo'])))
                <img src="data:image/{{ pathinfo($etablissement['logo'], PATHINFO_EXTENSION) }};base64,{{ base64_encode(file_get_contents(storage_path('app/public/' . $etablissement['logo']))) }}" class="header-logo" alt="Logo">
            @endif

            <div class="school-name">{{ $etablissement['nom'] ?? 'KLASSCI' }}</div>

            @if($etablissement['adresse'] || $etablissement['telephone'] || $etablissement['email'])
            <div class="school-info">
                @if($etablissement['adresse']){{ $etablissement['adresse'] }}@endif
                @if($etablissement['telephone'] && $etablissement['adresse']) | @endif
                @if($etablissement['telephone'])Tel: {{ $etablissement['telephone'] }}@endif
                @if($etablissement['email'] && ($etablissement['adresse'] || $etablissement['telephone'])) | @endif
                @if($etablissement['email'])Email: {{ $etablissement['email'] }}@endif
            </div>
            @endif

            <div class="document-title-section">
                <div class="document-title">FEUILLE DE SAISIE DES NOTES</div>
                <div class="evaluation-info-grid">
                    <div class="evaluation-info-row">
                        <div class="evaluation-info-cell">
                            <strong>Évaluation:</strong><br>
                            <span class="info-badge">{{ $evaluation->titre }}</span>
                        </div>
                        <div class="evaluation-info-cell">
                            <strong>Matière:</strong><br>
                            <span class="info-badge">{{ $evaluation->matiere->name ?? 'N/A' }}</span>
                        </div>
                        <div class="evaluation-info-cell">
                            <strong>Date:</strong><br>
                            <span class="date-line">__________</span>
                        </div>
                        <div class="evaluation-info-cell">
                            <strong>Enseignant:</strong><br>
                            <span class="teacher-line">____________</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- KPI Section -->
        <div class="kpi-section">
            <div class="kpi-row">
                <div class="kpi-card">
                    <div class="kpi-title">Total</div>
                    <div class="kpi-value">{{ $etudiants->count() }}</div>
                    <div class="kpi-desc">Étudiants</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-title">Classe</div>
                    <div class="kpi-value" style="font-size: 8.5px; line-height: 1.1;">{{ $evaluation->classe->name ?? 'N/A' }}</div>
                    <div class="kpi-desc">{{ $evaluation->classe->filiere->name ?? 'Filière' }}</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-title">Type</div>
                    <div class="kpi-value" style="font-size: 8.5px; line-height: 1.1;">{{ ucfirst($evaluation->type) }}</div>
                    <div class="kpi-desc">{{ $evaluation->coefficient }}pts</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-title">Barème</div>
                    <div class="kpi-value" style="font-size: 8.5px; line-height: 1.1;">/ {{ $evaluation->bareme }}</div>
                    <div class="kpi-desc">Points</div>
                </div>
            </div>
        </div>

        <!-- Liste des étudiants -->
        @if($etudiants->count() > 0)
            <table class="notes-table">
                <thead>
                    <tr>
                        <th width="25">N°</th>
                        <th width="60">Matricule</th>
                        <th>Nom et Prénoms</th>
                        <th width="60">Note</th>
                        <th width="40">Abs.</th>
                        <th width="100">Observations</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($etudiants as $index => $etudiant)
                    @php
                        $note = $notesByEtudiant[$etudiant->id] ?? null;
                    @endphp
                    <tr>
                        <td>
                            <span class="student-number">{{ $index + 1 }}</span>
                        </td>
                        <td>
                            <span class="student-matricule">{{ $etudiant->matricule ?? 'N/A' }}</span>
                        </td>
                        <td class="student-info-cell">
                            <div class="student-name">{{ $etudiant->nom }} {{ $etudiant->prenoms }}</div>
                            <div class="student-gender">{{ $etudiant->genre == 'M' ? 'Masculin' : 'Féminin' }}</div>
                        </td>
                        <td class="note-type-column">
                            <div class="note-box">
                                @if(!empty($note) && !$note->is_absent)
                                    {{ rtrim(rtrim(number_format($note->note, 2), '0'), '.') }}
                                @endif
                            </div>
                        </td>
                        <td>
                            <div class="note-box" style="width: 10px; height: 10px; font-size: 7px; line-height: 7px;">
                                @if(!empty($note) && $note->is_absent)
                                    ABS
                                @endif
                            </div>
                        </td>
                        <td class="observations-column"></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <!-- Footer Section -->
            <div class="footer-section">
                <div class="footer-left">
                    <div class="summary-card">
                        <div class="summary-title">Résumé des notes</div>
                        <div class="summary-grid">
                            <div class="summary-row">
                                <div class="summary-cell">
                                    <div class="summary-value">{{ $etudiants->count() }}</div>
                                    <div class="summary-label">Total</div>
                                </div>
                                <div class="summary-cell">
                                    <div class="summary-value">___</div>
                                    <div class="summary-label">Saisis</div>
                                </div>
                                <div class="summary-cell">
                                    <div class="summary-value">___</div>
                                    <div class="summary-label">Absents</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="footer-right">
                    <div class="info-card">
                        <div class="summary-title">Informations évaluation</div>
                        <div class="info-field">
                            <div class="info-label">Date évaluation :</div>
                            <div class="info-value">{{ $evaluation->date_evaluation ? $evaluation->date_evaluation->format('d/m/Y') : 'Non renseignée' }}</div>
                        </div>
                        <div class="info-field">
                            <div class="info-label">Coefficient :</div>
                            <div class="info-value">{{ $evaluation->coefficient }}</div>
                        </div>
                        <div class="info-field">
                            <div class="info-label">Durée :</div>
                            <div class="info-value">{{ $evaluation->duree_minutes ? $evaluation->duree_minutes . ' min' : 'Non renseignée' }}</div>
                        </div>
                        <div class="info-field">
                            <div class="info-label">Année :</div>
                            <div class="info-value">{{ $anneeCourante->name ?? 'Courante' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="empty-state">
                <div class="empty-icon">📝</div>
                <p>Aucun étudiant inscrit dans cette classe pour l'année {{ $anneeCourante->name ?? 'courante' }}.</p>
            </div>
        @endif

        <!-- Generation Info -->
        <div class="generation-info">
            <strong>Document généré automatiquement le {{ now()->format('d/m/Y à H:i') }}</strong><br>
            {{ $etablissement['nom'] ?? 'KLASSCI' }} - Système de Gestion des Évaluations<br>
            <strong>Instructions :</strong> Renseigner la note dans la case prévue · Cocher ABS si l'étudiant était absent
        </div>
    </div>
</body>
</html>

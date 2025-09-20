<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste d'appel - {{ $classe->name }}</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            font-size: 9px;
            margin: 0;
            padding: 8px;
            color: #333;
            line-height: 1.2;
            background: white;
        }

        .container {
            max-width: 100%;
            background: white;
            padding: 12px;
        }

        /* Header principal */
        .header-section {
            background: #007bff;
            color: white;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 15px;
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }

        .header-logo {
            max-height: 40px;
            max-width: 100px;
            margin-bottom: 8px;
            filter: brightness(0) invert(1);
        }

        .school-name {
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 3px;
        }

        .school-info {
            font-size: 7px;
            margin-bottom: 8px;
            opacity: 0.9;
        }

        .document-title-section {
            background: rgba(255,255,255,0.2);
            padding: 8px;
            border-radius: 6px;
            margin-top: 8px;
        }

        .document-title {
            font-size: 10px;
            font-weight: 600;
            margin-bottom: 6px;
        }

        .class-info-grid {
            display: table;
            width: 100%;
            font-size: 8px;
        }

        .class-info-row {
            display: table-row;
        }

        .class-info-cell {
            display: table-cell;
            width: 33.33%;
            text-align: center;
            padding: 3px;
        }

        .info-badge {
            background: rgba(255,255,255,0.3);
            padding: 2px 4px;
            border-radius: 8px;
            display: inline-block;
            margin-top: 2px;
        }

        .date-line, .teacher-line {
            border-bottom: 1px solid rgba(255,255,255,0.7);
            padding: 1px 8px;
            display: inline-block;
            min-width: 60px;
        }

        /* KPI Section */
        .kpi-section {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }

        .kpi-row {
            display: table-row;
        }

        .kpi-card {
            display: table-cell;
            width: 25%;
            padding: 4px;
            text-align: center;
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            vertical-align: top;
            font-size: 7px;
        }

        .kpi-card:first-child {
            border-radius: 6px 0 0 6px;
        }

        .kpi-card:last-child {
            border-radius: 0 6px 6px 0;
        }

        .kpi-title {
            font-size: 6px;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.2px;
            margin-bottom: 2px;
        }

        .kpi-value {
            font-size: 9px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 1px;
            line-height: 1.1;
        }

        .kpi-desc {
            font-size: 5px;
            color: #9ca3af;
        }

        /* Table moderne */
        .attendance-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            background: white;
            border-radius: 6px;
            overflow: hidden;
            font-size: 9px;
        }

        .attendance-table th {
            background: #007bff;
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            font-size: 8px;
            padding: 6px 4px;
            text-align: center;
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }

        .attendance-table td {
            padding: 5px 3px;
            border-bottom: 1px solid #e5e7eb;
            text-align: center;
            vertical-align: middle;
            font-size: 8px;
        }

        .attendance-table tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        .student-number {
            background: #007bff;
            color: white;
            padding: 2px 4px;
            border-radius: 50%;
            font-weight: bold;
            font-size: 8px;
            min-width: 16px;
            display: inline-block;
        }

        .student-matricule {
            font-family: 'Courier New', monospace;
            background: #f3f4f6;
            padding: 1px 3px;
            border-radius: 3px;
            font-size: 7px;
            color: #374151;
        }

        .student-info-cell {
            text-align: left !important;
            padding-left: 6px !important;
        }

        .student-name {
            font-weight: 600;
            font-size: 8px;
            color: #1f2937;
            line-height: 1.2;
        }

        .student-gender {
            font-size: 6px;
            color: #6b7280;
            margin-top: 1px;
        }

        .checkbox-box {
            width: 14px;
            height: 14px;
            border: 2px solid #007bff;
            border-radius: 2px;
            display: inline-block;
            background: white;
        }

        .observations-column {
            width: 20%;
            min-height: 15px;
            border-bottom: 1px solid #d1d5db;
        }

        /* Footer section */
        .footer-section {
            margin-top: 15px;
            display: table;
            width: 100%;
        }

        .footer-left, .footer-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding: 5px;
        }

        .summary-card {
            background: #f8f9fa;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 10px;
        }

        .summary-title {
            font-size: 9px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
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
            padding: 3px;
        }

        .summary-value {
            font-size: 12px;
            font-weight: bold;
            color: #007bff;
        }

        .summary-label {
            font-size: 7px;
            color: #6b7280;
            margin-top: 1px;
        }

        .signature-card {
            background: #f8f9fa;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 10px;
            margin-left: 5px;
        }

        .signature-field {
            margin-bottom: 10px;
        }

        .signature-label {
            font-size: 8px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 3px;
        }

        .signature-line {
            border-bottom: 1px solid #d1d5db;
            padding: 3px 0;
            min-height: 15px;
        }

        .signature-box {
            border: 1px solid #d1d5db;
            border-radius: 4px;
            padding: 10px;
            min-height: 30px;
            background: white;
        }

        /* Informations de génération */
        .generation-info {
            text-align: center;
            font-size: 7px;
            color: #6b7280;
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px solid #e5e7eb;
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 30px 15px;
            color: #6b7280;
        }

        .empty-icon {
            font-size: 24px;
            margin-bottom: 10px;
            color: #d1d5db;
        }

        /* Print optimizations */
        @media print {
            body {
                background: white;
                padding: 5px;
            }

            .container {
                padding: 10px;
            }

            .header-section {
                margin-bottom: 10px;
            }

            .kpi-section {
                margin-bottom: 10px;
            }

            .footer-section {
                margin-top: 10px;
            }
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

            <div class="school-name">{{ $etablissement['nom'] ?? 'ESBTP-yAKRO' }}</div>

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
                <div class="document-title">FEUILLE D'APPEL</div>
                <div class="class-info-grid">
                    <div class="class-info-row">
                        <div class="class-info-cell">
                            <strong>Classe:</strong><br>
                            <span class="info-badge">{{ $classe->name }}</span>
                        </div>
                        <div class="class-info-cell">
                            <strong>Date:</strong><br>
                            <span class="date-line">__________</span>
                        </div>
                        <div class="class-info-cell">
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
                    <div class="kpi-desc">Etudiants</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-title">Filiere</div>
                    <div class="kpi-value" style="font-size: 8px; line-height: 1.1;">{{ $classe->filiere->name ?? 'N/A' }}</div>
                    <div class="kpi-desc">Specialisation</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-title">Niveau</div>
                    <div class="kpi-value" style="font-size: 8px; line-height: 1.1;">{{ $classe->niveau->name ?? 'N/A' }}</div>
                    <div class="kpi-desc">Annee d'etudes</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-title">Annee</div>
                    <div class="kpi-value" style="font-size: 8px; line-height: 1.1;">{{ $anneeCourante->name ?? 'Courante' }}</div>
                    <div class="kpi-desc">Universitaire</div>
                </div>
            </div>
        </div>

        <!-- Liste des étudiants -->
        @if($etudiants->count() > 0)
            <table class="attendance-table">
                <thead>
                    <tr>
                        <th width="30">N°</th>
                        <th width="70">Matricule</th>
                        <th>Nom et Prenoms</th>
                        <th width="50">Present</th>
                        <th width="50">Absent</th>
                        <th width="100">Observations</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($etudiants as $index => $etudiant)
                    <tr>
                        <td>
                            <span class="student-number">{{ $index + 1 }}</span>
                        </td>
                        <td>
                            <span class="student-matricule">{{ $etudiant->matricule ?? 'N/A' }}</span>
                        </td>
                        <td class="student-info-cell">
                            <div class="student-name">{{ $etudiant->nom }} {{ $etudiant->prenoms }}</div>
                            <div class="student-gender">{{ $etudiant->genre == 'M' ? 'Masculin' : 'Feminin' }}</div>
                        </td>
                        <td>
                            <div class="checkbox-box"></div>
                        </td>
                        <td>
                            <div class="checkbox-box"></div>
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
                        <div class="summary-title">Resume des presences</div>
                        <div class="summary-grid">
                            <div class="summary-row">
                                <div class="summary-cell">
                                    <div class="summary-value">{{ $etudiants->count() }}</div>
                                    <div class="summary-label">Total</div>
                                </div>
                                <div class="summary-cell">
                                    <div class="summary-value">___</div>
                                    <div class="summary-label">Presents</div>
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
                    <div class="signature-card">
                        <div class="summary-title">Validation enseignant</div>
                        <div class="signature-field">
                            <div class="signature-label">Nom de l'enseignant:</div>
                            <div class="signature-line"></div>
                        </div>
                        <div class="signature-field">
                            <div class="signature-label">Signature:</div>
                            <div class="signature-box"></div>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="empty-state">
                <div class="empty-icon">Aucun etudiant</div>
                <p>Aucun etudiant inscrit dans cette classe pour l'annee {{ $anneeCourante->name ?? 'courante' }}.</p>
            </div>
        @endif

        <!-- Generation Info -->
        <div class="generation-info">
            <strong>Document genere automatiquement le {{ now()->format('d/m/Y a H:i') }}</strong><br>
            {{ $etablissement['nom'] ?? 'ESBTP-yAKRO' }} - Systeme de Gestion des Inscriptions
        </div>
    </div>
</body>
</html>
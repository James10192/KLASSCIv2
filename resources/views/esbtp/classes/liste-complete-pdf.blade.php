<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste complète - {{ $classe->name }}</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            font-size: 8px;
            margin: 0;
            padding: 8px;
            color: #333;
            line-height: 1.2;
            background: white;
        }

        .container {
            max-width: 100%;
            background: white;
            padding: 10px;
        }

        /* Header principal */
        .header-section {
            background: #007bff;
            color: white;
            padding: 12px;
            border-radius: 6px;
            text-align: center;
            margin-bottom: 12px;
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }

        .header-logo {
            max-height: 35px;
            max-width: 80px;
            margin-bottom: 6px;
            filter: brightness(0) invert(1);
        }

        .school-name {
            font-size: 11px;
            font-weight: 700;
            margin-bottom: 3px;
        }

        .school-info {
            font-size: 6px;
            margin-bottom: 6px;
            opacity: 0.9;
        }

        .document-title-section {
            background: rgba(255,255,255,0.2);
            padding: 6px;
            border-radius: 4px;
            margin-top: 6px;
        }

        .document-title {
            font-size: 9px;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .class-info-grid {
            display: table;
            width: 100%;
            font-size: 7px;
        }

        .class-info-row {
            display: table-row;
        }

        .class-info-cell {
            display: table-cell;
            width: 33.33%;
            text-align: center;
            padding: 2px;
        }

        .info-badge {
            background: rgba(255,255,255,0.3);
            padding: 1px 3px;
            border-radius: 6px;
            display: inline-block;
            margin-top: 1px;
        }

        /* KPI Section */
        .kpi-section {
            display: table;
            width: 100%;
            margin-bottom: 12px;
        }

        .kpi-row {
            display: table-row;
        }

        .kpi-card {
            display: table-cell;
            width: 25%;
            padding: 3px;
            text-align: center;
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            vertical-align: top;
            font-size: 6px;
        }

        .kpi-card:first-child {
            border-radius: 4px 0 0 4px;
        }

        .kpi-card:last-child {
            border-radius: 0 4px 4px 0;
        }

        .kpi-title {
            font-size: 5px;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.1px;
            margin-bottom: 1px;
        }

        .kpi-value {
            font-size: 8px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 1px;
            line-height: 1.1;
        }

        .kpi-desc {
            font-size: 4px;
            color: #9ca3af;
        }

        /* Table moderne */
        .students-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            background: white;
            border-radius: 4px;
            overflow: hidden;
            font-size: 7px;
        }

        .students-table th {
            background: #007bff;
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.2px;
            font-size: 6px;
            padding: 4px 2px;
            text-align: center;
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }

        .students-table td {
            padding: 3px 2px;
            border-bottom: 1px solid #e5e7eb;
            text-align: center;
            vertical-align: middle;
            font-size: 6px;
        }

        .students-table tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        .student-number {
            background: #007bff;
            color: white;
            padding: 1px 3px;
            border-radius: 50%;
            font-weight: bold;
            font-size: 6px;
            min-width: 12px;
            display: inline-block;
        }

        .student-matricule {
            font-family: 'Courier New', monospace;
            background: #f3f4f6;
            padding: 1px 2px;
            border-radius: 2px;
            font-size: 5px;
            color: #374151;
        }

        .student-info-cell {
            text-align: left !important;
            padding-left: 4px !important;
        }

        .student-name {
            font-weight: 600;
            font-size: 6px;
            color: #1f2937;
            line-height: 1.2;
        }

        .student-gender {
            font-size: 5px;
            color: #6b7280;
            margin-top: 1px;
        }

        .genre-badge {
            background: #007bff;
            color: white;
            padding: 1px 3px;
            border-radius: 50%;
            font-weight: bold;
            font-size: 5px;
            min-width: 12px;
            display: inline-block;
        }

        .genre-badge.female {
            background: #e91e63;
        }

        /* Footer section */
        .footer-section {
            margin-top: 12px;
            display: table;
            width: 100%;
        }

        .footer-left, .footer-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding: 3px;
        }

        .summary-card {
            background: #f8f9fa;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            padding: 6px;
        }

        .summary-title {
            font-size: 7px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 0.2px;
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
            width: 25%;
            text-align: center;
            padding: 2px;
        }

        .summary-value {
            font-size: 8px;
            font-weight: bold;
            color: #007bff;
        }

        .summary-label {
            font-size: 5px;
            color: #6b7280;
            margin-top: 1px;
        }

        .info-card {
            background: #f8f9fa;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            padding: 6px;
            margin-left: 3px;
        }

        .info-field {
            margin-bottom: 4px;
        }

        .info-label {
            font-size: 5px;
            color: #6b7280;
            margin-bottom: 1px;
        }

        .info-value {
            font-size: 6px;
            font-weight: 600;
            color: #374151;
        }

        /* Informations de génération */
        .generation-info {
            text-align: center;
            font-size: 5px;
            color: #6b7280;
            margin-top: 10px;
            padding-top: 6px;
            border-top: 1px solid #e5e7eb;
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 20px 10px;
            color: #6b7280;
        }

        .empty-icon {
            font-size: 16px;
            margin-bottom: 6px;
            color: #d1d5db;
        }

        /* Print optimizations */
        @media print {
            body {
                background: white;
                padding: 4px;
                font-size: 7px;
            }

            .container {
                padding: 6px;
            }

            .header-section {
                margin-bottom: 8px;
            }

            .kpi-section {
                margin-bottom: 8px;
            }

            .footer-section {
                margin-top: 8px;
            }
        }

        @page {
            margin: 0.5cm;
            size: A4 landscape;
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
                <div class="document-title">LISTE COMPLÈTE DES ÉTUDIANTS</div>
                <div class="class-info-grid">
                    <div class="class-info-row">
                        <div class="class-info-cell">
                            <strong>Classe:</strong><br>
                            <span class="info-badge">{{ $classe->name }}</span>
                        </div>
                        <div class="class-info-cell">
                            <strong>Date:</strong><br>
                            <span class="info-badge">{{ now()->format('d/m/Y') }}</span>
                        </div>
                        <div class="class-info-cell">
                            <strong>Code:</strong><br>
                            <span class="info-badge">{{ $classe->code ?? 'N/A' }}</span>
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
                    <div class="kpi-value" style="font-size: 6px; line-height: 1.1;">{{ $classe->filiere->name ?? 'N/A' }}</div>
                    <div class="kpi-desc">Specialisation</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-title">Niveau</div>
                    <div class="kpi-value" style="font-size: 6px; line-height: 1.1;">{{ $classe->niveau->name ?? 'N/A' }}</div>
                    <div class="kpi-desc">Annee d'etudes</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-title">Repartition</div>
                    <div class="kpi-value" style="font-size: 6px; line-height: 1.1;">{{ $etudiants->where('genre', 'M')->count() }}H/{{ $etudiants->where('genre', 'F')->count() }}F</div>
                    <div class="kpi-desc">Hommes/Femmes</div>
                </div>
            </div>
        </div>

        <!-- Liste des étudiants -->
        @if($etudiants->count() > 0)
            <table class="students-table">
                <thead>
                    <tr>
                        <th width="25">N°</th>
                        <th width="60">Matricule</th>
                        <th>Nom et Prenoms</th>
                        <th width="30">Genre</th>
                        <th width="60">Date naiss.</th>
                        <th width="70">Telephone</th>
                        <th width="100">Email</th>
                        <th>Adresse</th>
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
                        </td>
                        <td>
                            <span class="genre-badge {{ $etudiant->genre == 'F' ? 'female' : '' }}">{{ $etudiant->genre ?? 'N/A' }}</span>
                        </td>
                        <td>
                            {{ $etudiant->date_naissance ? \Carbon\Carbon::parse($etudiant->date_naissance)->format('d/m/Y') : 'Non renseigne' }}
                        </td>
                        <td>
                            {{ $etudiant->telephone ?? 'Non renseigne' }}
                        </td>
                        <td style="font-size: 5px;">
                            {{ $etudiant->email ?? 'Non renseigne' }}
                        </td>
                        <td style="font-size: 5px;">
                            {{ $etudiant->adresse ?? 'Non renseigne' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <!-- Footer Section -->
            <div class="footer-section">
                <div class="footer-left">
                    <div class="summary-card">
                        <div class="summary-title">Resume statistique</div>
                        <div class="summary-grid">
                            <div class="summary-row">
                                <div class="summary-cell">
                                    <div class="summary-value">{{ $etudiants->count() }}</div>
                                    <div class="summary-label">Total</div>
                                </div>
                                <div class="summary-cell">
                                    <div class="summary-value">{{ $etudiants->where('genre', 'M')->count() }}</div>
                                    <div class="summary-label">Hommes</div>
                                </div>
                                <div class="summary-cell">
                                    <div class="summary-value">{{ $etudiants->where('genre', 'F')->count() }}</div>
                                    <div class="summary-label">Femmes</div>
                                </div>
                                <div class="summary-cell">
                                    <div class="summary-value">{{ ($classe->places_totales ?? 0) - $etudiants->count() }}</div>
                                    <div class="summary-label">Places libres</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="footer-right">
                    <div class="info-card">
                        <div class="summary-title">Informations document</div>
                        <div class="info-field">
                            <div class="info-label">Document genere le :</div>
                            <div class="info-value">{{ now()->format('d/m/Y à H:i') }}</div>
                        </div>
                        <div class="info-field">
                            <div class="info-label">Par :</div>
                            <div class="info-value">{{ auth()->user()->name ?? 'Systeme' }}</div>
                        </div>
                        <div class="info-field">
                            <div class="info-label">Etablissement :</div>
                            <div class="info-value">{{ $etablissement['nom'] ?? 'ESBTP-yAKRO' }}</div>
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
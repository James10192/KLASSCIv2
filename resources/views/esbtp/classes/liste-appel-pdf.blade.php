{{-- liste-appel-pdf : force recompile timestamp 2026-05-08 --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    @include('pdf.partials.theme')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste d'appel - {{ $classe->name }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
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
            padding: 3px;
        }

        /* Header principal */
        .header-section {
            background: #007bff;
            color: white;
            padding: 4px;
            border-radius: 6px;
            text-align: center;
            margin-bottom: 4px;
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }

        .header-logo-frame {
            display: inline-block;
            background: #fff;
            padding: 3px;
            border-radius: 4px;
            border: 1px solid rgba(255,255,255,0.35);
            margin-bottom: 2px;
        }
        .header-logo {
            max-height: 24px;
            max-width: 60px;
            display: block;
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

        .class-info-grid {
            display: table;
            width: 100%;
            font-size: 8.5px;
        }

        .class-info-row {
            display: table-row;
        }

        .class-info-cell {
            display: table-cell;
            width: 33.33%;
            text-align: center;
            padding: 0.5px;
        }

        .info-badge {
            background: rgba(255,255,255,0.3);
            padding: 0.5px 2px;
            border-radius: 6px;
            display: inline-block;
            margin-top: 0.5px;
        }

        .date-line, .teacher-line {
            border-bottom: 1px solid rgba(255,255,255,0.7);
            padding: 0.5px 3px;
            display: inline-block;
            min-width: 40px;
        }

        /* KPI Section */
        .kpi-section {
            display: table;
            width: 100%;
            margin-bottom: 4px;
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
            letter-spacing: 0.2px;
            margin-bottom: 1px;
        }

        .kpi-value {
            font-size: 10px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 0.5px;
            line-height: 1.1;
        }

        .kpi-desc {
            font-size: 7px;
            color: #9ca3af;
        }

        /* Table moderne */
        .attendance-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
            background: white;
            border-radius: 4px;
            overflow: hidden;
            font-size: 9px;
        }

        .attendance-table th {
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

        .attendance-table td {
            padding: 1px 0.5px;
            border-bottom: 1px solid #e5e7eb;
            text-align: center;
            vertical-align: middle;
            font-size: 8.5px;
        }

        .attendance-table tbody tr:nth-child(even) {
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

        .checkbox-box {
            width: 11px;
            height: 11px;
            border: 2px solid #007bff;
            border-radius: 2px;
            display: inline-block;
            background: white;
        }

        .observations-column {
            width: 20%;
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
            padding: 1px;
        }

        .summary-card {
            background: #f8f9fa;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            padding: 3px;
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
            font-size: 12px;
            font-weight: bold;
            color: #007bff;
        }

        .summary-label {
            font-size: 8px;
            color: #6b7280;
            margin-top: 0.5px;
        }

        .signature-card {
            background: #f8f9fa;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            padding: 3px;
            margin-left: 2px;
        }

        .signature-field {
            margin-bottom: 3px;
        }

        .signature-label {
            font-size: 8.5px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 1px;
        }

        .signature-line {
            border-bottom: 1px solid #d1d5db;
            padding: 1px 0;
            min-height: 8px;
        }

        .signature-box {
            border: 1px solid #d1d5db;
            border-radius: 4px;
            padding: 3px;
            min-height: 16px;
            background: white;
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
            font-size: 21px;
            margin-bottom: 6px;
            color: #d1d5db;
        }

        /* Print optimizations */
        @media print {
            body {
                background: white;
                padding: 3px;
            }

            .container {
                padding: 4px;
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
    </style>
</head>
<body>
    <div class="container">
        <!-- Header Section -->
        <div class="header-section">
            @php($_logo = \App\Helpers\SettingsHelper::resolveLogoBase64())
            @if($_logo)
                <span class="header-logo-frame"><img src="{{ $_logo['data_uri'] }}" class="header-logo" alt="Logo"></span>
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
                    <div class="kpi-value" style="font-size: 9px; line-height: 1.1;">{{ $classe->filiere->name ?? 'N/A' }}</div>
                    <div class="kpi-desc">Specialisation</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-title">Niveau</div>
                    <div class="kpi-value" style="font-size: 9px; line-height: 1.1;">{{ $classe->niveau->name ?? 'N/A' }}</div>
                    <div class="kpi-desc">Annee d'etudes</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-title">Annee</div>
                    <div class="kpi-value" style="font-size: 9px; line-height: 1.1;">{{ $anneeCourante->name ?? 'Courante' }}</div>
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
                        <th width="60">Matricule</th>
                        <th>Nom et Prenoms</th>
                        <th width="40">Present</th>
                        <th width="40">Absent</th>
                        <th width="90">Observations</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($etudiants as $index => $etudiant)
                    @php $accProfile = auth()->user()?->can('students.accessibility.export') ? $etudiant->accessibilityProfile : null; @endphp
                    <tr>
                        <td>
                            <span class="student-number">{{ $index + 1 }}</span>
                        </td>
                        <td>
                            <span class="student-matricule">{{ $etudiant->matricule ?? 'N/A' }}</span>
                        </td>
                        <td class="student-info-cell">
                            <div class="student-name">
                                {{ $etudiant->nom }} {{ $etudiant->prenoms }}
                                @if($accProfile)
                                    <span style="display:inline-block; background:#0453cb; color:#fff; padding:1px 5px; border-radius:50px; font-size:9px; margin-left:3px; font-weight:600; -webkit-print-color-adjust:exact; color-adjust:exact;" title="{{ $accProfile->summaryBadge() }}">&#9855;</span>
                                @endif
                            </div>
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

            @php
                $accStudents = auth()->user()?->can('students.accessibility.export')
                    ? $etudiants->filter(fn ($e) => $e->accessibilityProfile)
                    : collect();
            @endphp
            @if($accStudents->isNotEmpty())
                <div style="margin-top:6px; padding:6px 8px; background:#eff6ff; border:1px solid #bfdbfe; border-radius:4px; -webkit-print-color-adjust:exact; color-adjust:exact;">
                    <div style="font-size:8px; font-weight:700; color:#0453cb; text-transform:uppercase; letter-spacing:.04em; margin-bottom:3px;">
                        &#9855; Aménagements à respecter ({{ $accStudents->count() }})
                    </div>
                    <table style="width:100%; border-collapse:collapse; font-size:7.5px;">
                        @foreach($accStudents as $accE)
                            @php $p = $accE->accessibilityProfile; @endphp
                            <tr>
                                <td style="width:25%; padding:1px 4px; vertical-align:top;">
                                    <strong style="color:#1e293b;">{{ $accE->nom }} {{ $accE->prenoms }}</strong>
                                </td>
                                <td style="padding:1px 4px; vertical-align:top; color:#475569;">
                                    @if($p->requires_third_time) <span style="color:#0453cb; font-weight:700;">Tiers-temps {{ $p->third_time_percentage }}%</span> · @endif
                                    @if($p->assistant_required) <span style="color:#0453cb; font-weight:700;">Assistant requis</span> · @endif
                                    {{ implode(' · ', $p->accommodationLabels()) }}
                                    @if($p->short_description) — <em>{{ $p->short_description }}</em> @endif
                                </td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            @endif

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
        @php $pdfCfg = $pdfCfg ?? \App\Helpers\SettingsHelper::getPdfSettings(); @endphp
        <div class="generation-info">
            <strong>Document genere automatiquement le {{ now()->format('d/m/Y a H:i') }}</strong>@if(($pdfCfg['show_generator_name'] ?? true) && auth()->check()) par {{ auth()->user()->name }}@endif<br>
            {{ $etablissement['nom'] ?? 'KLASSCI' }} - Systeme de Gestion des Inscriptions
        </div>
    </div>
</body>
</html>

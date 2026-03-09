<!DOCTYPE html>
<html lang="fr">
<head>
    @include('pdf.partials.theme')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste complète - {{ $classe->name }}</title>
    <style>
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 10px;
            margin: 0;
            padding: 8px;
            color: #333;
            line-height: 1.3;
            background: white;
        }

        .container {
            max-width: 100%;
            background: white;
            padding: 10px;
        }

        /* Header principal - structure table 2 colonnes */
        .header-section {
            border-radius: 6px;
            margin-bottom: 12px;
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
            overflow: hidden;
        }

        /* Table moderne */
        .students-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            background: white;
            border-radius: 4px;
            overflow: hidden;
            font-size: 9px;
        }

        .students-table th {
            background: #007bff;
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.2px;
            font-size: 9px;
            padding: 6px 4px;
            text-align: center;
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }

        .students-table td {
            padding: 5px 4px;
            border-bottom: 1px solid #e5e7eb;
            text-align: center;
            vertical-align: middle;
            font-size: 9px;
        }

        .students-table tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        .student-number {
            background: #007bff;
            color: white;
            padding: 2px 4px;
            border-radius: 50%;
            font-weight: bold;
            font-size: 9px;
            min-width: 16px;
            display: inline-block;
        }

        .student-matricule {
            font-family: 'Courier New', monospace;
            background: #f3f4f6;
            padding: 2px 3px;
            border-radius: 2px;
            font-size: 8px;
            color: #374151;
        }

        .student-info-cell {
            text-align: left !important;
            padding-left: 4px !important;
        }

        .student-name {
            font-weight: 600;
            font-size: 9px;
            color: #1f2937;
            line-height: 1.3;
        }

        .student-gender {
            font-size: 8px;
            color: #6b7280;
            margin-top: 1px;
        }

        .genre-badge {
            background: #007bff;
            color: white;
            padding: 2px 4px;
            border-radius: 50%;
            font-weight: bold;
            font-size: 8px;
            min-width: 16px;
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
            padding: 9px;
        }

        .summary-title {
            font-size: 10px;
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
            font-size: 11px;
            font-weight: bold;
            color: #007bff;
        }

        .summary-label {
            font-size: 8px;
            color: #6b7280;
            margin-top: 1px;
        }

        .info-card {
            background: #f8f9fa;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            padding: 9px;
            margin-left: 3px;
        }

        .info-field {
            margin-bottom: 5px;
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
                font-size: 10px;
            }

            .container {
                padding: 8px;
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

        @page {
            margin: 0.5cm;
            size: A4 landscape;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header Section — 2 colonnes : Logo | Infos école + Titre document -->
        <div class="header-section">
            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                <tr>
                    <!-- Colonne gauche : Logo -->
                    <td width="18%" style="background-color: #0453cb; padding: 14px 10px; text-align: center; vertical-align: middle; border-right: 2px solid rgba(255,255,255,0.25);">
                        @if($etablissement['logo'] && file_exists(storage_path('app/public/' . $etablissement['logo'])))
                            <img src="data:image/{{ pathinfo($etablissement['logo'], PATHINFO_EXTENSION) }};base64,{{ base64_encode(file_get_contents(storage_path('app/public/' . $etablissement['logo']))) }}"
                                 style="max-height: 55px; max-width: 100px; filter: brightness(0) invert(1);" alt="Logo">
                        @else
                            <div style="font-size: 30px; font-weight: 900; color: white; opacity: 0.4; letter-spacing: -2px;">K</div>
                        @endif
                    </td>
                    <!-- Colonne droite : Nom école + contact + titre document -->
                    <td width="82%" style="background-color: #0453cb; padding: 12px 16px; vertical-align: middle;">
                        <!-- Nom établissement -->
                        <div style="font-size: 15px; font-weight: 700; color: white; margin-bottom: 2px;">{{ $etablissement['nom'] ?? 'KLASSCI' }}</div>
                        <!-- Adresse | Tél | Email -->
                        @if($etablissement['adresse'] || $etablissement['telephone'] || $etablissement['email'])
                        <div style="font-size: 8.5px; color: white; opacity: 0.85; margin-bottom: 8px;">
                            @if($etablissement['adresse']){{ $etablissement['adresse'] }}@endif
                            @if($etablissement['telephone'])
                                @if($etablissement['adresse']) &nbsp;|&nbsp; @endif
                                Tél: {{ $etablissement['telephone'] }}
                            @endif
                            @if($etablissement['email'])
                                @if($etablissement['adresse'] || $etablissement['telephone']) &nbsp;|&nbsp; @endif
                                Email: {{ $etablissement['email'] }}
                            @endif
                        </div>
                        @endif
                        <!-- Séparateur + titre document + infos classe -->
                        <div style="border-top: 1px solid rgba(255,255,255,0.35); padding-top: 7px;">
                            <div style="font-size: 12px; font-weight: 700; color: white; letter-spacing: 0.5px; margin-bottom: 5px;">LISTE COMPLÈTE DES ÉTUDIANTS</div>
                            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td width="33%" style="font-size: 9px; color: white;">
                                        <span style="color: white; opacity: 0.75;">Classe :</span>
                                        <strong style="color: white;">{{ $classe->name }}</strong>
                                    </td>
                                    <td width="33%" style="font-size: 9px; color: white; text-align: center;">
                                        <span style="color: white; opacity: 0.75;">Date :</span>
                                        <strong style="color: white;">{{ now()->format('d/m/Y') }}</strong>
                                    </td>
                                    <td width="34%" style="font-size: 9px; color: white; text-align: right;">
                                        <span style="color: white; opacity: 0.75;">Code :</span>
                                        <strong style="color: white;">{{ $classe->code ?? 'N/A' }}</strong>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- KPI Section — 4 cellules uniformes fond bleu (pas de .kpi-value/.kpi-title pour éviter override theme) -->
        <table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-bottom: 12px;">
            <tr>
                <!-- TOTAL -->
                <td width="25%" style="background-color: #0453cb; padding: 9px 8px; text-align: center; vertical-align: middle; border-right: 1px solid rgba(255,255,255,0.25);">
                    <div style="font-size: 7.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: white; opacity: 0.8; margin-bottom: 4px;">TOTAL</div>
                    <div style="font-size: 18px; font-weight: 700; color: white; line-height: 1.1; margin-bottom: 4px;">{{ $etudiants->count() }}</div>
                    <div style="font-size: 7px; color: white; opacity: 0.65;">Étudiants</div>
                </td>
                <!-- FILIÈRE -->
                <td width="25%" style="background-color: #0453cb; padding: 9px 8px; text-align: center; vertical-align: middle; border-right: 1px solid rgba(255,255,255,0.25);">
                    <div style="font-size: 7.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: white; opacity: 0.8; margin-bottom: 4px;">FILIÈRE</div>
                    <div style="font-size: 10px; font-weight: 700; color: white; line-height: 1.25; margin-bottom: 4px;">{{ $classe->filiere->name ?? 'N/A' }}</div>
                    <div style="font-size: 7px; color: white; opacity: 0.65;">Spécialisation</div>
                </td>
                <!-- NIVEAU -->
                <td width="25%" style="background-color: #0453cb; padding: 9px 8px; text-align: center; vertical-align: middle; border-right: 1px solid rgba(255,255,255,0.25);">
                    <div style="font-size: 7.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: white; opacity: 0.8; margin-bottom: 4px;">NIVEAU</div>
                    <div style="font-size: 10px; font-weight: 700; color: white; line-height: 1.25; margin-bottom: 4px;">{{ $classe->niveau->name ?? 'N/A' }}</div>
                    <div style="font-size: 7px; color: white; opacity: 0.65;">Année d'études</div>
                </td>
                <!-- RÉPARTITION -->
                <td width="25%" style="background-color: #0453cb; padding: 9px 8px; text-align: center; vertical-align: middle;">
                    <div style="font-size: 7.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: white; opacity: 0.8; margin-bottom: 4px;">RÉPARTITION</div>
                    <div style="font-size: 13px; font-weight: 700; color: white; line-height: 1.25; margin-bottom: 4px;">{{ $etudiants->where('genre', 'M')->count() }}H / {{ $etudiants->where('genre', 'F')->count() }}F</div>
                    <div style="font-size: 7px; color: white; opacity: 0.65;">Hommes / Femmes</div>
                </td>
            </tr>
        </table>

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
                        <td style="font-size: 8px;">
                            {{ $etudiant->email ?? 'Non renseigne' }}
                        </td>
                        <td style="font-size: 8px;">
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
                            <div class="info-value">{{ $etablissement['nom'] ?? 'KLASSCI' }}</div>
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
            {{ $etablissement['nom'] ?? 'KLASSCI' }} - Systeme de Gestion des Inscriptions
        </div>
    </div>
</body>
</html>

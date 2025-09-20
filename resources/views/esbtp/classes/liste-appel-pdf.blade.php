<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste d'appel - {{ $classe->name }}</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 20px;
            color: #333;
            line-height: 1.4;
        }

        .modern-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 15px;
            text-align: center;
            margin-bottom: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .header-logo {
            max-height: 70px;
            max-width: 180px;
            margin-bottom: 15px;
            filter: brightness(0) invert(1);
        }

        .school-name {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 8px;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
        }

        .school-info {
            font-size: 11px;
            margin-bottom: 20px;
            opacity: 0.9;
        }

        .document-title-box {
            background: rgba(255,255,255,0.2);
            padding: 15px;
            border-radius: 10px;
            margin-top: 15px;
        }

        .document-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .info-grid {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }

        .info-row {
            display: table-row;
        }

        .info-cell {
            display: table-cell;
            vertical-align: middle;
            padding: 5px;
        }

        .info-cell.left {
            text-align: left;
            width: 60%;
        }

        .info-cell.right {
            text-align: right;
            width: 40%;
        }

        .classe-badge {
            background: rgba(255,255,255,0.3);
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 16px;
            font-weight: 600;
        }

        .date-line {
            border-bottom: 2px solid rgba(255,255,255,0.7);
            padding: 3px 20px;
            font-size: 14px;
        }

        .details-grid {
            display: table;
            width: 100%;
            font-size: 12px;
        }

        .details-row {
            display: table-row;
        }

        .details-cell {
            display: table-cell;
            width: 33.33%;
            text-align: center;
            padding: 5px;
        }

        .detail-badge {
            background: rgba(255,255,255,0.3);
            padding: 3px 8px;
            border-radius: 12px;
            display: inline-block;
            margin-top: 3px;
        }

        .attendance-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            border-radius: 8px;
            overflow: hidden;
        }

        .attendance-table th,
        .attendance-table td {
            border: 1px solid #e0e0e0;
            padding: 10px 8px;
            text-align: left;
        }

        .attendance-table th {
            background: #667eea;
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 10px;
        }

        .attendance-table tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        .checkbox-cell {
            width: 35px;
            text-align: center;
            font-size: 14px;
        }

        .number-cell {
            width: 40px;
            text-align: center;
            font-weight: 600;
            color: #667eea;
        }

        .footer {
            margin-top: 30px;
            display: table;
            width: 100%;
        }

        .footer-section {
            display: table-cell;
            vertical-align: top;
            width: 50%;
            padding: 10px;
        }

        .footer-left {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
        }

        .footer-right {
            text-align: right;
            padding-left: 20px;
        }

        .signature-box {
            border: 2px solid #667eea;
            border-radius: 8px;
            padding: 15px;
            margin-top: 10px;
            height: 60px;
            background: #f8f9fa;
        }

        .signature-label {
            color: #667eea;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .stats-item {
            margin-bottom: 8px;
            font-weight: 600;
        }

        .stats-value {
            color: #667eea;
        }
    </style>
</head>
<body>
    <!-- En-tête moderne -->
    <div class="modern-header">
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

        <div class="document-title-box">
            <div class="document-title">FEUILLE D'APPEL</div>

            <div class="info-grid">
                <div class="info-row">
                    <div class="info-cell left">
                        <strong>Classe :</strong> <span class="classe-badge">{{ $classe->name }}</span>
                    </div>
                    <div class="info-cell right">
                        <strong>Date :</strong> <span class="date-line">_____________</span>
                    </div>
                </div>
            </div>

            <div class="details-grid">
                <div class="details-row">
                    <div class="details-cell">
                        <strong>Filiere :</strong><br>
                        <span class="detail-badge">{{ $classe->filiere->name ?? 'Non renseigne' }}</span>
                    </div>
                    <div class="details-cell">
                        <strong>Niveau :</strong><br>
                        <span class="detail-badge">{{ $classe->niveau->name ?? 'Non renseigne' }}</span>
                    </div>
                    <div class="details-cell">
                        <strong>Annee :</strong><br>
                        <span class="detail-badge">{{ $anneeCourante->name ?? 'Non renseigne' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Liste des étudiants -->
    @if($etudiants->count() > 0)
        <table class="attendance-table">
            <thead>
                <tr>
                    <th class="number-cell">N°</th>
                    <th>Matricule</th>
                    <th>Nom et Prenoms</th>
                    <th class="checkbox-cell">Present</th>
                    <th class="checkbox-cell">Absent</th>
                    <th style="width: 120px;">Observations</th>
                </tr>
            </thead>
            <tbody>
                @foreach($etudiants as $index => $etudiant)
                <tr>
                    <td class="number-cell">{{ $index + 1 }}</td>
                    <td>{{ $etudiant->matricule ?? 'Non renseigne' }}</td>
                    <td>{{ $etudiant->nom }} {{ $etudiant->prenoms }}</td>
                    <td class="checkbox-cell">☐</td>
                    <td class="checkbox-cell">☐</td>
                    <td></td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Résumé et signature -->
        <div class="footer">
            <div class="footer-section">
                <div class="footer-left">
                    <div class="stats-item">
                        <strong>Total etudiants :</strong> <span class="stats-value">{{ $etudiants->count() }}</span>
                    </div>
                    <div class="stats-item">
                        <strong>Presents :</strong> <span class="stats-value">_____ / {{ $etudiants->count() }}</span>
                    </div>
                    <div class="stats-item">
                        <strong>Absents :</strong> <span class="stats-value">_____ / {{ $etudiants->count() }}</span>
                    </div>
                </div>
            </div>
            <div class="footer-section">
                <div class="footer-right">
                    <div class="signature-label">Enseignant :</div>
                    <div style="margin-bottom: 15px;">_____________________</div>
                    <div class="signature-label">Signature :</div>
                    <div class="signature-box"></div>
                </div>
            </div>
        </div>
    @else
        <div style="text-align: center; padding: 40px; background: #f8f9fa; border-radius: 10px; margin-top: 20px;">
            <p style="font-size: 16px; color: #666;">Aucun etudiant inscrit dans cette classe pour l'annee {{ $anneeCourante->name ?? 'courante' }}.</p>
        </div>
    @endif

    <!-- Pied de page avec informations -->
    <div style="margin-top: 30px; text-align: center; font-size: 10px; color: #666; border-top: 1px solid #e0e0e0; padding-top: 15px;">
        <p><strong>Document genere automatiquement le {{ now()->format('d/m/Y a H:i') }}</strong></p>
        <p>{{ $etablissement['nom'] ?? 'ESBTP-yAKRO' }} - Systeme de Gestion des Inscriptions</p>
    </div>
</body>
</html>
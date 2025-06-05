@php
use App\Helpers\SettingsHelper;

// Récupérer les paramètres de l'établissement et PDF
$schoolInfo = SettingsHelper::getSchoolInfo();
$pdfSettings = SettingsHelper::getPdfSettings();
@endphp

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulletin de Notes - {{ $etudiant->nom }} {{ $etudiant->prenom }}</title>
    <style>
        @page {
            margin: 15mm;
            size: A4;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            font-size: 11px;
            line-height: 1.2;
            color: #333;
            background: white;
        }

        .bulletin-container {
            width: 100%;
            max-width: 210mm;
            margin: 0 auto;
            padding: 0;
        }

        /* En-tête */
        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #2c5aa0;
        }

        .school-info {
            flex: 1;
        }

        .school-name {
            font-size: 16px;
            font-weight: bold;
            color: #2c5aa0;
            margin-bottom: 3px;
        }

        .school-address {
            font-size: 10px;
            color: #666;
            line-height: 1.3;
        }

        .logo {
            width: 60px;
            height: 60px;
            object-fit: contain;
        }

        .bulletin-title {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            color: #2c5aa0;
            margin: 10px 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Informations étudiant */
        .student-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            background: #f8f9fa;
            padding: 8px;
            border-radius: 5px;
        }

        .student-left, .student-right {
            flex: 1;
        }

        .student-right {
            text-align: right;
        }

        .info-row {
            margin-bottom: 3px;
        }

        .info-label {
            font-weight: bold;
            display: inline-block;
            width: 80px;
        }

        /* Tableaux */
        .grades-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            font-size: 10px;
        }

        .grades-table th,
        .grades-table td {
            border: 1px solid #ddd;
            padding: 4px 6px;
            text-align: center;
        }

        .grades-table th {
            background: #2c5aa0;
            color: white;
            font-weight: bold;
            font-size: 9px;
        }

        .subject-name {
            text-align: left !important;
            font-weight: 500;
        }

        .section-header {
            background: #e3f2fd !important;
            color: #1976d2 !important;
            font-weight: bold;
        }

        .summary-row {
            background: #f5f5f5;
            font-weight: bold;
        }

        /* Section résultats et statistiques */
        .results-section {
            display: flex;
            gap: 15px;
            margin: 15px 0;
        }

        .results-box, .stats-box {
            flex: 1;
            border: 1px solid #ddd;
            border-radius: 5px;
            overflow: hidden;
        }

        .box-header {
            background: #2c5aa0;
            color: white;
            padding: 6px;
            font-weight: bold;
            text-align: center;
            font-size: 11px;
        }

        .box-content {
            padding: 8px;
        }

        .result-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 6px;
            padding: 3px 0;
        }

        .result-label {
            font-weight: 500;
        }

        .result-value {
            background: white;
            border: 1px solid #333;
            padding: 3px 8px;
            min-width: 50px;
            text-align: center;
            font-weight: bold;
            border-radius: 3px;
        }

        /* Absences */
        .absences-section {
            margin: 10px 0;
        }

        .absences-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }

        .absences-table th,
        .absences-table td {
            border: 1px solid #ddd;
            padding: 5px;
            text-align: center;
        }

        .absences-table th {
            background: #ff9800;
            color: white;
            font-weight: bold;
        }

        /* Mentions et décisions */
        .mentions-section {
            display: flex;
            gap: 15px;
            margin: 15px 0;
        }

        .mentions-box, .decision-box {
            flex: 1;
            border: 1px solid #ddd;
            border-radius: 5px;
            overflow: hidden;
        }

        .mentions-grid {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 5px;
            padding: 8px;
        }

        .mention-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 2px 0;
        }

        .mention-checkbox {
            width: 15px;
            height: 15px;
            border: 2px solid #333;
            display: inline-block;
            position: relative;
        }

        .mention-checkbox.checked::after {
            content: '✓';
            position: absolute;
            top: -2px;
            left: 2px;
            font-size: 12px;
            font-weight: bold;
        }

        .decision-content {
            padding: 8px;
            min-height: 60px;
            border-bottom: 1px dotted #ccc;
            margin-bottom: 5px;
        }

        .decision-label {
            font-weight: bold;
            margin-bottom: 5px;
            color: #2c5aa0;
        }

        /* Signatures */
        .signatures-section {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
            gap: 20px;
        }

        .signature-box {
            flex: 1;
            text-align: center;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 10px;
            min-height: 80px;
        }

        .signature-title {
            font-weight: bold;
            margin-bottom: 8px;
            color: #2c5aa0;
        }

        .signature-line {
            border-bottom: 1px solid #333;
            margin: 20px 0 5px 0;
            height: 1px;
        }

        .signature-name {
            font-size: 10px;
            font-style: italic;
        }

        /* Utilitaires */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .text-primary { color: #2c5aa0; }

        /* Impression */
        @media print {
            .print-button { display: none; }
            body { font-size: 10px; }
            .bulletin-container { margin: 0; padding: 0; }
        }

        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #2c5aa0;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            z-index: 1000;
        }

        .print-button:hover {
            background: #1e3d72;
        }
    </style>
</head>
<body>
    <div class="bulletin-container">
        <!-- En-tête -->
        <div class="header">
            <div class="school-info">
                <div class="school-name">{{ $schoolInfo['name'] ?? 'ÉCOLE SUPÉRIEURE' }}</div>
                <div class="school-address">
                    {{ $schoolInfo['address'] ?? '' }}<br>
                    {{ $schoolInfo['phone'] ?? '' }} | {{ $schoolInfo['email'] ?? '' }}
                </div>
            </div>
            @if(isset($schoolInfo['logo']) && $schoolInfo['logo'])
                <img src="{{ asset('storage/' . $schoolInfo['logo']) }}" alt="Logo" class="logo">
            @endif
        </div>

        <div class="bulletin-title">Bulletin de Notes</div>

        <!-- Informations étudiant -->
        <div class="student-info">
            <div class="student-left">
                <div class="info-row">
                    <span class="info-label">Nom :</span>
                    <span class="font-bold">{{ $etudiant->nom }} {{ $etudiant->prenom }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Matricule :</span>
                    <span>{{ $etudiant->matricule }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Classe :</span>
                    <span>{{ $classe->nom }}</span>
                </div>
            </div>
            <div class="student-right">
                <div class="info-row">
                    <span class="info-label">Période :</span>
                    <span class="font-bold">{{ $periode == 'semestre1' ? '1er Semestre' : '2e Semestre' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Année :</span>
                    <span>{{ $anneeUniversitaire->libelle }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Date :</span>
                    <span>{{ $date_edition }}</span>
                </div>
            </div>
        </div>

        <!-- Tableau des notes -->
        <table class="grades-table">
            <thead>
                <tr>
                    <th style="width: 25%;">Matières</th>
                    <th style="width: 12%;">Moyenne</th>
                    <th style="width: 10%;">Coeff.</th>
                    <th style="width: 12%;">Total</th>
                    <th style="width: 8%;">Rang</th>
                    <th style="width: 15%;">Professeur</th>
                    <th style="width: 18%;">Appréciation</th>
                </tr>
            </thead>
            <tbody>
                <!-- Enseignement Général -->
                <tr class="section-header">
                    <td colspan="7">ENSEIGNEMENT GÉNÉRAL</td>
                </tr>
                @if(isset($resultatsGeneraux) && $resultatsGeneraux->count() > 0)
                    @foreach($resultatsGeneraux as $resultat)
                        <tr>
                            <td class="subject-name">{{ $resultat->matiere->nom ?? 'N/A' }}</td>
                            <td>{{ number_format($resultat->moyenne, 2) }}</td>
                            <td>{{ $resultat->coefficient }}</td>
                            <td>{{ number_format($resultat->moyenne * $resultat->coefficient, 2) }}</td>
                            <td>{{ $resultat->rang ?: '-' }}</td>
                            <td>{{ $professeurs[$resultat->matiere_id] ?? 'M.' }}</td>
                            <td>{{ $resultat->appreciation ?? '-' }}</td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="7" class="text-center">Aucune matière d'enseignement général</td>
                    </tr>
                @endif
                <tr class="summary-row">
                    <td class="subject-name">Moyenne Enseignement Général</td>
                    <td class="font-bold">{{ number_format($moyenneGeneraux, 2) }}</td>
                    <td colspan="5"></td>
                </tr>

                <!-- Enseignement Technique -->
                <tr class="section-header">
                    <td colspan="7">ENSEIGNEMENT TECHNIQUE</td>
                </tr>
                @if(isset($resultatsTechniques) && $resultatsTechniques->count() > 0)
                    @foreach($resultatsTechniques as $resultat)
                        <tr>
                            <td class="subject-name">{{ $resultat->matiere->nom ?? 'N/A' }}</td>
                            <td>{{ number_format($resultat->moyenne, 2) }}</td>
                            <td>{{ $resultat->coefficient }}</td>
                            <td>{{ number_format($resultat->moyenne * $resultat->coefficient, 2) }}</td>
                            <td>{{ $resultat->rang ?: '-' }}</td>
                            <td>{{ $professeurs[$resultat->matiere_id] ?? 'M.' }}</td>
                            <td>{{ $resultat->appreciation ?? '-' }}</td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="7" class="text-center">Aucune matière d'enseignement technique</td>
                    </tr>
                @endif
                <tr class="summary-row">
                    <td class="subject-name">Moyenne Enseignement Technique</td>
                    <td class="font-bold">{{ number_format($moyenneTechnique, 2) }}</td>
                    <td colspan="5"></td>
                </tr>
            </tbody>
        </table>

        <!-- Absences -->
        <div class="absences-section">
            <table class="absences-table">
                <thead>
                    <tr>
                        <th colspan="2">ABSENCES</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="width: 70%;">Absences justifiées</td>
                        <td class="font-bold">{{ $absencesJustifiees ?? '0' }} heure(s)</td>
                    </tr>
                    <tr>
                        <td>Absences non justifiées</td>
                        <td class="font-bold">{{ $absencesNonJustifiees ?? '0' }} heure(s)</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Résultats et Statistiques -->
        <div class="results-section">
            <div class="results-box">
                <div class="box-header">RÉSULTATS</div>
                <div class="box-content">
                    <div class="result-row">
                        <span class="result-label">Moyenne Brute :</span>
                        <span class="result-value">{{ number_format($moyenneGlobale, 2) }}</span>
                    </div>
                    <div class="result-row">
                        <span class="result-label">Note d'assiduité :</span>
                        <span class="result-value">{{ number_format($note_assiduite, 2) }}</span>
                    </div>
                    <div class="result-row">
                        <span class="result-label">Moyenne {{ $periode == 'semestre1' ? '1er' : '2e' }} Semestre :</span>
                        <span class="result-value">{{ number_format($moyenneAvecAssiduite, 2) }}</span>
                    </div>
                    <div class="result-row">
                        <span class="result-label">Rang :</span>
                        <span class="result-value">{{ $rang }}/{{ $effectif }}</span>
                    </div>
                </div>
            </div>

            <div class="stats-box">
                <div class="box-header">STATISTIQUES DE CLASSE</div>
                <div class="box-content">
                    <div class="result-row">
                        <span class="result-label">Plus forte moyenne :</span>
                        <span class="result-value">{{ number_format($meilleure_moyenne, 2) }}</span>
                    </div>
                    <div class="result-row">
                        <span class="result-label">Plus faible moyenne :</span>
                        <span class="result-value">{{ number_format($plus_faible_moyenne, 2) }}</span>
                    </div>
                    <div class="result-row">
                        <span class="result-label">Moyenne de classe :</span>
                        <span class="result-value">{{ number_format($moyenne_classe, 2) }}</span>
                    </div>
                    <div class="result-row">
                        <span class="result-label">Effectif :</span>
                        <span class="result-value">{{ $effectif }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mentions et Décision -->
        <div class="mentions-section">
            <div class="mentions-box">
                <div class="box-header">MENTIONS</div>
                <div class="mentions-grid">
                    <div class="mention-item">
                        <span class="mention-checkbox {{ $moyenneGlobale >= 16 ? 'checked' : '' }}"></span>
                        <span>Félicitations</span>
                    </div>
                    <div class="mention-item">
                        <span class="mention-checkbox {{ $moyenneGlobale >= 14 && $moyenneGlobale < 16 ? 'checked' : '' }}"></span>
                        <span>Tableau d'honneur</span>
                    </div>
                    <div class="mention-item">
                        <span class="mention-checkbox {{ $moyenneGlobale >= 12 && $moyenneGlobale < 14 ? 'checked' : '' }}"></span>
                        <span>Encouragements</span>
                    </div>
                    <div class="mention-item">
                        <span class="mention-checkbox {{ $moyenneGlobale >= 8 && $moyenneGlobale < 10 ? 'checked' : '' }}"></span>
                        <span>Avertissement travail</span>
                    </div>
                    <div class="mention-item">
                        <span class="mention-checkbox"></span>
                        <span>Blâme conduite</span>
                    </div>
                    <div class="mention-item">
                        <span class="mention-checkbox"></span>
                        <span>Exclusion temporaire</span>
                    </div>
                </div>
            </div>

            <div class="decision-box">
                <div class="box-header">DÉCISION DU CONSEIL DE CLASSE</div>
                <div class="decision-content">
                    <div class="decision-label">Appréciation générale :</div>
                    <div style="min-height: 25px; border-bottom: 1px dotted #ccc; margin: 5px 0;"></div>
                    <div style="min-height: 25px; border-bottom: 1px dotted #ccc; margin: 5px 0;"></div>
                </div>
            </div>
        </div>

        <!-- Signatures -->
        <div class="signatures-section">
            <div class="signature-box">
                <div class="signature-title">Professeur Principal</div>
                <div class="signature-line"></div>
                <div class="signature-name">Nom et signature</div>
            </div>

            <div class="signature-box">
                <div class="signature-title">{{ $schoolInfo['director_title'] ?? 'Directeur Général' }}</div>
                <div class="signature-line"></div>
                <div class="signature-name">{{ $schoolInfo['director_name'] ?? 'Nom et signature' }}</div>
            </div>

            <div class="signature-box">
                <div class="signature-title">Parent/Tuteur</div>
                <div class="signature-line"></div>
                <div class="signature-name">Nom et signature</div>
            </div>
        </div>

        @if($pdfSettings['footer_text'])
            <div style="margin-top: 10px; text-align: center; font-size: 9px; color: #666; border-top: 1px solid #eee; padding-top: 5px;">
                {{ $pdfSettings['footer_text'] }}
            </div>
        @endif
    </div>

    <button onclick="window.print()" class="print-button">📄 Imprimer</button>
</body>
</html>

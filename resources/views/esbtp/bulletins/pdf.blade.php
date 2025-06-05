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
    <title>Bulletin de Notes</title>
    <style>
        @page {
            margin: 10mm;
            size: A4 portrait;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            line-height: 1.3;
            color: #2c3e50;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 8px;
            border-radius: 5px;
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-left {
            flex: 1;
        }

        .header-center {
            flex: 2;
            text-align: center;
        }

        .header-right {
            flex: 1;
            text-align: right;
        }

        .school-name {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .school-info {
            font-size: 9px;
            opacity: 0.9;
        }

        .bulletin-title {
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .logo {
            max-height: 40px;
            max-width: 60px;
        }

        .student-info {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 8px;
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
        }

        .student-left, .student-right {
            flex: 1;
        }

        .info-row {
            margin-bottom: 3px;
            font-size: 10px;
        }

        .info-label {
            font-weight: bold;
            display: inline-block;
            width: 80px;
        }

        .grades-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            font-size: 10px;
        }

        .grades-table th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 6px 4px;
            text-align: center;
            font-size: 9px;
            font-weight: bold;
        }

        .grades-table td {
            border: 1px solid #dee2e6;
            padding: 4px;
            text-align: center;
        }

        .grades-table .matiere-name {
            text-align: left;
            font-weight: bold;
            max-width: 120px;
        }

        .section-title {
            background: #2c3e50;
            color: white;
            padding: 4px 8px;
            font-weight: bold;
            font-size: 10px;
            margin-bottom: 4px;
            border-radius: 3px;
        }

        .absence-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            font-size: 10px;
        }

        .absence-table th, .absence-table td {
            border: 1px solid #dee2e6;
            padding: 4px;
            text-align: center;
        }

        .absence-table th {
            background: #6c757d;
            color: white;
            font-size: 9px;
        }

        .results-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .results-left, .results-right {
            width: 48%;
        }

        .result-box {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 6px;
            margin-bottom: 6px;
        }

        .result-title {
            font-weight: bold;
            font-size: 10px;
            color: #2c3e50;
            margin-bottom: 4px;
        }

        .result-value {
            font-size: 12px;
            font-weight: bold;
            color: #667eea;
        }

        .mentions-section {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 8px;
            margin-bottom: 8px;
        }

        .mentions-title {
            font-weight: bold;
            font-size: 10px;
            margin-bottom: 6px;
            color: #2c3e50;
        }

        .mentions-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .mention-item {
            display: flex;
            align-items: center;
            font-size: 9px;
        }

        .checkbox {
            width: 12px;
            height: 12px;
            border: 1px solid #2c3e50;
            margin-right: 4px;
            display: inline-block;
            text-align: center;
            line-height: 10px;
            font-size: 8px;
        }

        .checkbox.checked {
            background: #28a745;
            color: white;
        }

        .decision-section {
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 8px;
            margin-bottom: 8px;
        }

        .decision-title {
            font-weight: bold;
            font-size: 10px;
            margin-bottom: 6px;
            color: #2c3e50;
        }

        .decision-lines {
            border-bottom: 1px solid #ccc;
            height: 20px;
            margin-bottom: 4px;
        }

        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 12px;
        }

        .signature-box {
            width: 45%;
            text-align: center;
            border-top: 1px dashed #666;
            padding-top: 4px;
            font-size: 9px;
        }

        .page-break {
            page-break-before: always;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .text-sm { font-size: 9px; }
    </style>
</head>
<body>
    <!-- En-tête du bulletin -->
    <div class="header">
        <div class="header-left">
            @if(!empty($logo))
                <img src="{{ $logo }}" alt="Logo" class="logo">
            @endif
        </div>
        <div class="header-center">
            <div class="school-name">{{ $school_name ?? 'ESBTP-yAKRO' }}</div>
            <div class="school-info">{{ $school_address ?? '' }}</div>
            <div class="school-info">{{ $school_phone ?? '' }}</div>
            <div class="bulletin-title">Bulletin de Notes</div>
        </div>
        <div class="header-right">
            <div class="school-info">{{ $school_email ?? '' }}</div>
            <div class="school-info">Année: {{ $anneeUniversitaire->libelle ?? '' }}</div>
            <div class="school-info">{{ ucfirst($periode ?? '') }}</div>
        </div>
    </div>

    <!-- Informations de l'étudiant -->
    <div class="student-info">
        <div class="student-left">
            <div class="info-row">
                <span class="info-label">Nom :</span>
                <span>{{ $etudiant->nom ?? '' }} {{ $etudiant->prenoms ?? '' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Matricule :</span>
                <span>{{ $etudiant->matricule ?? '' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Classe :</span>
                <span>{{ $classe->name ?? $classe->nom ?? '' }}</span>
            </div>
        </div>
        <div class="student-right">
            <div class="info-row">
                <span class="info-label">Date :</span>
                <span>{{ $date_edition ?? date('d/m/Y') }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Effectif :</span>
                <span>{{ $effectif ?? $effectifClasse ?? 0 }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Rang :</span>
                <span>{{ $rang ?? 'N/A' }}</span>
            </div>
        </div>
    </div>

    <!-- Matières Générales -->
    @if(isset($resultatsGeneraux) && $resultatsGeneraux->count() > 0)
    <div class="section-title">MATIÈRES GÉNÉRALES</div>
    <table class="grades-table">
        <thead>
            <tr>
                <th style="width: 25%;">Matières</th>
                <th style="width: 8%;">Coef</th>
                <th style="width: 10%;">Note 1</th>
                <th style="width: 10%;">Note 2</th>
                <th style="width: 10%;">Note 3</th>
                <th style="width: 12%;">Moyenne</th>
                <th style="width: 10%;">Rang</th>
                <th style="width: 15%;">Professeur</th>
            </tr>
        </thead>
        <tbody>
            @foreach($resultatsGeneraux as $resultat)
            <tr>
                <td class="matiere-name">{{ $resultat->matiere->nom ?? $resultat->matiere->name ?? 'N/A' }}</td>
                <td>{{ $resultat->coefficient ?? 1 }}</td>
                <td>{{ $resultat->note1 ?? '-' }}</td>
                <td>{{ $resultat->note2 ?? '-' }}</td>
                <td>{{ $resultat->note3 ?? '-' }}</td>
                <td class="font-bold">{{ number_format($resultat->moyenne ?? 0, 2) }}</td>
                <td>{{ $resultat->rang ?? '-' }}</td>
                <td class="text-sm">{{ $resultat->professeur ?? '-' }}</td>
            </tr>
            @endforeach
            <tr style="background: #e9ecef;">
                <td class="font-bold">MOYENNE GÉNÉRALE</td>
                <td>-</td>
                <td>-</td>
                <td>-</td>
                <td>-</td>
                <td class="font-bold">{{ number_format($moyenneGeneraux ?? 0, 2) }}</td>
                <td>-</td>
                <td>-</td>
            </tr>
        </tbody>
    </table>
    @endif

    <!-- Matières Techniques -->
    @if(isset($resultatsTechniques) && $resultatsTechniques->count() > 0)
    <div class="section-title">MATIÈRES TECHNIQUES</div>
    <table class="grades-table">
        <thead>
            <tr>
                <th style="width: 25%;">Matières</th>
                <th style="width: 8%;">Coef</th>
                <th style="width: 10%;">Note 1</th>
                <th style="width: 10%;">Note 2</th>
                <th style="width: 10%;">Note 3</th>
                <th style="width: 12%;">Moyenne</th>
                <th style="width: 10%;">Rang</th>
                <th style="width: 15%;">Professeur</th>
            </tr>
        </thead>
        <tbody>
            @foreach($resultatsTechniques as $resultat)
            <tr>
                <td class="matiere-name">{{ $resultat->matiere->nom ?? $resultat->matiere->name ?? 'N/A' }}</td>
                <td>{{ $resultat->coefficient ?? 1 }}</td>
                <td>{{ $resultat->note1 ?? '-' }}</td>
                <td>{{ $resultat->note2 ?? '-' }}</td>
                <td>{{ $resultat->note3 ?? '-' }}</td>
                <td class="font-bold">{{ number_format($resultat->moyenne ?? 0, 2) }}</td>
                <td>{{ $resultat->rang ?? '-' }}</td>
                <td class="text-sm">{{ $resultat->professeur ?? '-' }}</td>
            </tr>
            @endforeach
            <tr style="background: #e9ecef;">
                <td class="font-bold">MOYENNE TECHNIQUE</td>
                <td>-</td>
                <td>-</td>
                <td>-</td>
                <td>-</td>
                <td class="font-bold">{{ number_format($moyenneTechnique ?? 0, 2) }}</td>
                <td>-</td>
                <td>-</td>
            </tr>
        </tbody>
    </table>
    @endif

    <!-- Tableau des absences -->
    <div class="section-title">ABSENCES</div>
    <table class="absence-table">
        <thead>
            <tr>
                <th>Absences Justifiées</th>
                <th>Absences Non Justifiées</th>
                <th>Total Absences</th>
                <th>Note d'Assiduité</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $absencesJustifiees ?? $absences_justifiees ?? 0 }}h</td>
                <td>{{ $absencesNonJustifiees ?? $absences_non_justifiees ?? 0 }}h</td>
                <td>{{ ($absencesJustifiees ?? 0) + ($absencesNonJustifiees ?? 0) }}h</td>
                <td class="font-bold">{{ number_format($note_assiduite ?? 0, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <!-- Résultats et statistiques -->
    <div class="results-section">
        <div class="results-left">
            <div class="result-box">
                <div class="result-title">Moyenne Brute</div>
                <div class="result-value">{{ number_format($moyenneBrute ?? $moyenneGenerale ?? 0, 2) }}/20</div>
            </div>
            <div class="result-box">
                <div class="result-title">Moyenne avec Assiduité</div>
                <div class="result-value">{{ number_format($moyenneAvecAssiduite ?? $moyenneSemestre ?? 0, 2) }}/20</div>
            </div>
            <div class="result-box">
                <div class="result-title">Rang</div>
                <div class="result-value">{{ $rang ?? 'N/A' }}/{{ $effectif ?? $effectifClasse ?? 0 }}</div>
            </div>
        </div>
        <div class="results-right">
            <div class="result-box">
                <div class="result-title">Meilleure Moyenne</div>
                <div class="result-value">{{ number_format($meilleure_moyenne ?? $plusForteMoyenne ?? 0, 2) }}/20</div>
            </div>
            <div class="result-box">
                <div class="result-title">Plus Faible Moyenne</div>
                <div class="result-value">{{ number_format($plus_faible_moyenne ?? $plusFaibleMoyenne ?? 0, 2) }}/20</div>
            </div>
            <div class="result-box">
                <div class="result-title">Moyenne de Classe</div>
                <div class="result-value">{{ number_format($moyenne_classe ?? $moyenneClasse ?? 0, 2) }}/20</div>
            </div>
        </div>
    </div>

    <!-- Mentions et appréciations -->
    <div class="mentions-section">
        <div class="mentions-title">MENTIONS ET APPRÉCIATIONS</div>
        <div class="mentions-grid">
            @php
                $moyenne = $moyenneAvecAssiduite ?? $moyenneSemestre ?? $moyenneGenerale ?? 0;
            @endphp
            <div class="mention-item">
                <span class="checkbox {{ $moyenne >= 16 ? 'checked' : '' }}">{{ $moyenne >= 16 ? '✓' : '' }}</span>
                Félicitations (≥ 16)
            </div>
            <div class="mention-item">
                <span class="checkbox {{ $moyenne >= 14 && $moyenne < 16 ? 'checked' : '' }}">{{ $moyenne >= 14 && $moyenne < 16 ? '✓' : '' }}</span>
                Tableau d'honneur (≥ 14)
            </div>
            <div class="mention-item">
                <span class="checkbox {{ $moyenne >= 12 && $moyenne < 14 ? 'checked' : '' }}">{{ $moyenne >= 12 && $moyenne < 14 ? '✓' : '' }}</span>
                Encouragements (≥ 12)
            </div>
            <div class="mention-item">
                <span class="checkbox {{ $moyenne >= 10 && $moyenne < 12 ? 'checked' : '' }}">{{ $moyenne >= 10 && $moyenne < 12 ? '✓' : '' }}</span>
                Passable (≥ 10)
            </div>
            <div class="mention-item">
                <span class="checkbox {{ $moyenne >= 8 && $moyenne < 10 ? 'checked' : '' }}">{{ $moyenne >= 8 && $moyenne < 10 ? '✓' : '' }}</span>
                Avertissement (≥ 8)
            </div>
            <div class="mention-item">
                <span class="checkbox {{ $moyenne < 8 ? 'checked' : '' }}">{{ $moyenne < 8 ? '✓' : '' }}</span>
                Blâme (< 8)
            </div>
        </div>
    </div>

    <!-- Décision du conseil de classe -->
    <div class="decision-section">
        <div class="decision-title">DÉCISION DU CONSEIL DE CLASSE</div>
        <div style="display: flex; gap: 20px; margin-bottom: 8px;">
            <div class="mention-item">
                <span class="checkbox"></span>
                Passage en classe supérieure
            </div>
            <div class="mention-item">
                <span class="checkbox"></span>
                Redoublement
            </div>
            <div class="mention-item">
                <span class="checkbox"></span>
                Exclusion définitive
            </div>
            <div class="mention-item">
                <span class="checkbox"></span>
                Exclusion temporaire
            </div>
        </div>
        <div class="decision-lines"></div>
        <div class="decision-lines"></div>
        <div class="decision-lines"></div>
    </div>

    <!-- Signatures -->
    <div class="signatures">
        <div class="signature-box">
            <div class="font-bold">{{ $director_title ?? 'Directeur' }}</div>
            <div style="margin-top: 20px;">{{ $director_name ?? '' }}</div>
        </div>
        <div class="signature-box">
            <div class="font-bold">Professeur Principal</div>
            <div style="margin-top: 20px;"></div>
        </div>
    </div>

    <!-- Pied de page -->
    @if(!empty($pdf_footer_text))
    <div style="text-align: center; margin-top: 12px; font-size: 8px; color: #666;">
        {{ $pdf_footer_text }}
    </div>
    @endif
</body>
</html>



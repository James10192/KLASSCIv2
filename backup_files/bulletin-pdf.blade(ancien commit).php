<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulletin de Notes - {{ $etudiant->nom }} {{ $etudiant->prenoms ?? $etudiant->prenom }}</title>
    <style>
        @page {
            size: A4;
            margin: 0;
        }
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 15px;
            background-color: white;
            color: #000;
            font-size: 11px;
        }
        .container {
            width: 100%;
            max-width: 210mm;
            margin: 0 auto;
            background: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 15px 20px;
        }
        .header-left {
            flex: 1;
        }
        .header-center {
            flex: 1;
            text-align: center;
        }
        .header-right {
            flex: 1;
            text-align: right;
        }
        .logo {
            width: 100px;
            margin: 0 auto;
            display: block;
        }
        .school-name {
            font-size: 14px;
            font-weight: bold;
            margin: 10px 0 5px;
            color: #000;
        }
        .school-address {
            font-size: 9px;
            margin: 2px 0;
        }
        .title {
            font-size: 16px;
            font-weight: bold;
            margin: 10px 0;
            color: #000;
        }
        .student-info {
            display: flex;
            justify-content: space-between;
            border: 1px solid #000;
            margin: 15px 20px;
            padding: 10px;
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
            width: calc(100% - 40px);
            margin: 15px 20px;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #000;
            padding: 6px 8px;
            text-align: left;
        }
        th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        .section-header {
            background-color: #f0f0f0;
            text-align: center;
            font-weight: bold;
            font-size: 12px;
        }
        .subject-row td {
            font-size: 11px;
            height: 25px;
        }
        .summary-row {
            font-weight: bold;
            background-color: #f0f0f0;
        }
        .center {
            text-align: center;
        }
        .right {
            text-align: right;
        }
        .results-container {
            display: flex;
            justify-content: space-between;
            margin: 15px 20px;
        }
        .results-left {
            flex: 2;
        }
        .results-right {
            flex: 1;
            margin-left: 15px;
        }
        .results-table {
            width: 100%;
            border-collapse: collapse;
        }
        .results-table th, .results-table td {
            border: 1px solid #000;
            padding: 5px;
        }
        .absences-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .decision-container {
            margin: 15px 20px;
            border: 1px solid #000;
            padding: 10px;
        }
        .decision-title {
            font-weight: bold;
            margin-bottom: 10px;
        }
        .signature-container {
            display: flex;
            justify-content: flex-end;
            margin: 15px 20px;
            padding-top: 20px;
        }
        .signature-box {
            text-align: center;
            width: 200px;
        }
        .signature-line {
            width: 180px;
            border-top: 1px solid #000;
            margin: 40px auto 5px;
        }
        .stats-table {
            width: 100%;
            border-collapse: collapse;
        }
        .stats-table th, .stats-table td {
            border: 1px solid #000;
            padding: 4px;
            font-size: 10px;
        }
        .mention-box {
            width: 100%;
            display: flex;
            border: 1px solid #000;
            margin-bottom: 10px;
        }
        .mention-label {
            padding: 5px;
            font-weight: bold;
            width: 80px;
            border-right: 1px solid #000;
        }
        .mention-value {
            padding: 5px;
            flex: 1;
        }
        .print-button {
            margin: 20px;
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
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
        <div class="header">
            <div class="header-left">
                <div>République de Côte d'Ivoire</div>
                <div>Union-Discipline-Travail</div>
                <div>Ministère de l'Enseignement Supérieur</div>
                <div>et de la Recherche Scientifique</div>
            </div>
            <div class="header-center">
                <img src="{{ asset('images/esbtp_logo.png') }}" alt="Logo ESBTP" class="logo">
                <div class="school-name">École Spéciale du Bâtiment et des Travaux Publics</div>
                <div class="school-address">BP 04 BP 1234 Abidjan 04 • Tel: 00 00 00 00 • Fax: 00 00 00 00</div>
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
                <div>Édition du: {{ $date_edition }}</div>
                <div>Cycle: Brevet de Technicien Supérieur</div>
                <div>BTS</div>
                <div>Année Scolaire: {{ $anneeUniversitaire->libelle ?? $anneeUniversitaire->name ?? '2022-2023' }}</div>
            </div>
        </div>

        <div class="student-info">
            <div class="info-group">
                <div class="info-row">
                    <div class="info-label">Matricule :</div>
                    <div class="info-value">{{ $etudiant->matricule }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Nom et Prénoms :</div>
                    <div class="info-value">{{ $etudiant->nom }} {{ $etudiant->prenoms ?? $etudiant->prenom }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Date de Naissance :</div>
                    <div class="info-value">{{ \Carbon\Carbon::parse($etudiant->date_naissance)->format('d/m/Y') }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Redoublant :</div>
                    <div class="info-value">{{ $etudiant->inscriptions->first()->is_redoublant ? 'Oui' : 'Non' }}</div>
                </div>
            </div>
            <div class="info-group">
                <div class="info-row">
                    <div class="info-label">Classe :</div>
                    <div class="info-value">{{ $classe->libelle ?? $classe->name }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Année d'étude :</div>
                    <div class="info-value">{{ $classe->niveau->libelle ?? $classe->niveau->name ?? ($classe->annee ?? 'N/A') }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Effectif :</div>
                    <div class="info-value">{{ $effectif }}</div>
                </div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Matière</th>
                    <th>Moyenne M</th>
                    <th>Coef C</th>
                    <th>Moy Pondérée M*C</th>
                    <th>Rang</th>
                    <th>Professeurs</th>
                    <th>Appréciations</th>
                </tr>
                <tr class="section-header">
                    <td colspan="7">Enseignement Général</td>
                </tr>
            </thead>
            <tbody>
                @if(isset($resultatsGeneraux) && $resultatsGeneraux->count() > 0)
                    @foreach($resultatsGeneraux as $resultat)
                        <tr class="subject-row">
                            <td>{{ $resultat->matiere->name ?? $resultat->matiere->nom ?? 'N/A' }}</td>
                            <td class="center">{{ number_format($resultat->moyenne, 2) }}</td>
                            <td class="center">{{ $resultat->coefficient }}</td>
                            <td class="center">{{ number_format($resultat->moyenne * $resultat->coefficient, 2) }}</td>
                            <td class="center">{{ $resultat->rang ?: '-' }}</td>
                            <td>{{ $professeurs[$resultat->matiere_id] ?? 'M.' }}</td>
                            <td>{{ $resultat->appreciation ?? 'Nul' }}</td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="7" class="center">Aucune matière d'enseignement général</td>
                    </tr>
                @endif
                <tr class="summary-row">
                    <td>Moyenne enseignement général</td>
                    <td colspan="2" class="center">{{ number_format($moyenneGenerale, 2) }}</td>
                    <td colspan="4"></td>
                </tr>
                <tr class="section-header">
                    <td colspan="7">Enseignement Technique</td>
                </tr>
                @if(isset($resultatsTechniques) && $resultatsTechniques->count() > 0)
                    @foreach($resultatsTechniques as $resultat)
                        <tr class="subject-row">
                            <td>{{ $resultat->matiere->name ?? $resultat->matiere->nom ?? 'N/A' }}</td>
                            <td class="center">{{ number_format($resultat->moyenne, 2) }}</td>
                            <td class="center">{{ $resultat->coefficient }}</td>
                            <td class="center">{{ number_format($resultat->moyenne * $resultat->coefficient, 2) }}</td>
                            <td class="center">{{ $resultat->rang ?: '-' }}</td>
                            <td>{{ $professeurs[$resultat->matiere_id] ?? 'M.' }}</td>
                            <td>{{ $resultat->appreciation ?? 'Nul' }}</td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="7" class="center">Aucune matière d'enseignement technique</td>
                    </tr>
                @endif
                <tr class="summary-row">
                    <td>Moyenne enseignement technique</td>
                    <td colspan="2" class="center">{{ number_format($moyenneTechnique, 2) }}</td>
                    <td colspan="4"></td>
                </tr>
            </tbody>
        </table>

        <table class="absences-table">
            <thead>
                <tr class="section-header">
                    <td colspan="2">Nombre d'heures d'absence</td>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Absences justifiées</td>
                    <td class="center" style="width: 50%;">{{ isset($absencesJustifiees) ? $absencesJustifiees : (isset($absences_justifiees) ? $absences_justifiees : (isset($bulletin->absences_justifiees) ? $bulletin->absences_justifiees : '00')) }} Heure(s)</td>
                </tr>
                <tr>
                    <td>Absences non justifiées</td>
                    <td class="center">{{ isset($absencesNonJustifiees) ? $absencesNonJustifiees : (isset($absences_non_justifiees) ? $absences_non_justifiees : (isset($bulletin->absences_non_justifiees) ? $bulletin->absences_non_justifiees : '00')) }} Heure(s)</td>
                </tr>
            </tbody>
        </table>

        <div class="results-container">
            <div class="results-left">
                <table class="results-table">
                    <thead>
                        <tr class="section-header">
                            <td colspan="2">RÉSULTATS</td>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Moyenne Brute</td>
                            <td class="center" style="width: 140px;">
                                <div style="border: 1px solid #000; padding: 4px; width: 60px; display: inline-block;">{{ number_format($moyenneGlobale, 2) }}</div>
                            </td>
                        </tr>
                        <tr>
                            <td>Note d'assiduité</td>
                            <td class="center">
                                <div style="border: 1px solid #000; padding: 4px; width: 60px; display: inline-block;">{{ number_format($note_assiduite, 2) }}</div>
                            </td>
                        </tr>
                        <tr>
                            <td>Moyenne {{ $periode == 'semestre1' ? '1er' : '2e' }} Semestre</td>
                            <td class="center">
                                <div style="border: 1px solid #000; padding: 4px; width: 60px; display: inline-block;">{{ number_format($moyenneAvecAssiduite, 2) }}</div>
                            </td>
                        </tr>
                        <tr>
                            <td>Rang</td>
                            <td class="center">
                                <div style="border: 1px solid #000; padding: 4px; width: 60px; display: inline-block;">{{ $rang }}</div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div style="margin-top: 15px;">
                    <div class="mention-box">
                        <div class="mention-label">Félicitation</div>
                        <div class="mention-value">
                            <input type="checkbox" {{ $moyenneGlobale >= 16 ? 'checked' : '' }}>
                        </div>
                    </div>
                    <div class="mention-box">
                        <div class="mention-label">Encouragement</div>
                        <div class="mention-value">
                            <input type="checkbox" {{ $moyenneGlobale >= 14 && $moyenneGlobale < 16 ? 'checked' : '' }}>
                        </div>
                    </div>
                    <div class="mention-box">
                        <div class="mention-label">Tableau d'honneur</div>
                        <div class="mention-value">
                            <input type="checkbox" {{ $moyenneGlobale >= 12 && $moyenneGlobale < 14 ? 'checked' : '' }}>
                        </div>
                    </div>
                    <div class="mention-box">
                        <div class="mention-label">Avertissement (Travail)</div>
                        <div class="mention-value">
                            <input type="checkbox" {{ $moyenneGlobale >= 8 && $moyenneGlobale < 10 ? 'checked' : '' }}>
                        </div>
                    </div>
                    <div class="mention-box">
                        <div class="mention-label">Blâme (Conduite)</div>
                        <div class="mention-value">
                            <input type="checkbox">
                        </div>
                    </div>
                </div>
            </div>
            <div class="results-right">
                <table class="stats-table">
                    <thead>
                        <tr class="section-header">
                            <td colspan="2">STATISTIQUES</td>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Plus forte moyenne</td>
                            <td class="center" style="width: 60px;">{{ number_format($meilleure_moyenne, 2) }}</td>
                        </tr>
                        <tr>
                            <td>Plus faible moyenne</td>
                            <td class="center">{{ number_format($plus_faible_moyenne, 2) }}</td>
                        </tr>
                        <tr>
                            <td>Moyenne de la classe</td>
                            <td class="center">{{ number_format($moyenne_classe, 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="decision-container">
            <div class="decision-title">Décision du conseil de classe</div>
            <div style="min-height: 40px;">
                {{ $appreciation }}
            </div>
        </div>

        <div class="signature-container">
            <div class="signature-box">
                <div>Signature de la Directrice des Études</div>
                <div class="signature-line"></div>
            </div>
        </div>

        <button onclick="window.print()" class="print-button">Imprimer le bulletin</button>
    </div>
</body>
</html>

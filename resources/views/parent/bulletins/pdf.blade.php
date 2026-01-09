<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulletin de notes - {{ $etudiant->nom }} {{ $etudiant->prenoms }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            color: #333;
            font-size: 12px;
        }
        
        .container {
            width: 100%;
            margin: 0 auto;
        }
        
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #555;
            padding-bottom: 10px;
        }
        
        .logo {
            width: 80px;
            height: auto;
        }
        
        .school-name {
            font-size: 20px;
            font-weight: bold;
            margin: 5px 0;
        }
        
        .school-address {
            font-size: 12px;
            margin: 5px 0;
        }
        
        .bulletin-title {
            font-size: 16px;
            font-weight: bold;
            margin: 15px 0;
            text-align: center;
            text-transform: uppercase;
        }
        
        .student-info {
            width: 100%;
            margin-bottom: 20px;
        }
        
        .student-info table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .student-info table td {
            padding: 5px;
        }
        
        .results-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .results-table th, .results-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
        }
        
        .results-table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        
        .results-table tr.total {
            font-weight: bold;
            background-color: #f2f2f2;
        }
        
        .subject-col {
            text-align: left !important;
        }
        
        .stats-box {
            width: 100%;
            margin-bottom: 20px;
        }
        
        .stats-box table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .stats-box table td {
            padding: 5px;
            text-align: center;
            border: 1px solid #ddd;
        }
        
        .stats-box table th {
            background-color: #f2f2f2;
            border: 1px solid #ddd;
            padding: 5px;
        }
        
        .observations {
            width: 100%;
            margin-bottom: 20px;
        }
        
        .observations table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .observations table td {
            padding: 8px;
            border: 1px solid #ddd;
        }
        
        .observations table th {
            padding: 8px;
            border: 1px solid #ddd;
            background-color: #f2f2f2;
            text-align: left;
        }
        
        .signatures {
            width: 100%;
            margin-top: 40px;
        }
        
        .signatures table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .signatures table td {
            width: 33%;
            text-align: center;
            padding-top: 60px;
        }
        
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }

        .good {
            color: green;
        }
        
        .bad {
            color: red;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- En-tête -->
        <div class="header">
            <div class="school-name">ÉCOLE SUPÉRIEURE DU BÂTIMENT ET DES TRAVAUX PUBLICS</div>
            <div class="school-address">Cocody Danga, Abidjan, Côte d'Ivoire</div>
            <div class="school-address">Tél: (+225) xx xx xx xx xx | Email: contact@esbtp-yakro.ci</div>
        </div>
        
        <!-- Titre du bulletin -->
        <div class="bulletin-title">
            Bulletin de notes - {{ $bulletin->periode }} - {{ $bulletin->anneeUniversitaire->annee_debut }}/{{ $bulletin->anneeUniversitaire->annee_fin }}
        </div>
        
        <!-- Informations de l'étudiant -->
        <div class="student-info">
            <table>
                <tr>
                    <td width="50%">
                        <strong>Nom & prénom:</strong> {{ $etudiant->nom }} {{ $etudiant->prenoms }}
                    </td>
                    <td width="50%">
                        <strong>Matricule:</strong> {{ $etudiant->matricule }}
                    </td>
                </tr>
                <tr>
                    <td width="50%">
                        <strong>Classe:</strong> {{ $bulletin->classe->nom }}
                    </td>
                    <td width="50%">
                        <strong>Filière:</strong> {{ $bulletin->classe->filiere->nom ?? 'Non définie' }}
                    </td>
                </tr>
                <tr>
                    <td width="50%">
                        <strong>Effectif:</strong> {{ $bulletin->effectif }} étudiants
                    </td>
                    <td width="50%">
                        <strong>Date d'émission:</strong> {{ $bulletin->created_at->format('d/m/Y') }}
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Tableau des résultats -->
        <div class="results-table">
            <table class="results-table">
                <thead>
                    <tr>
                        <th class="subject-col">Matière</th>
                        <th>Coefficient</th>
                        <th>Note/20</th>
                        <th>Moyenne classe</th>
                        <th>Note min</th>
                        <th>Note max</th>
                        <th>Rang</th>
                        <th>Appréciation</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($resultats as $resultat)
                    <tr>
                        <td class="subject-col">{{ $resultat->matiere->nom }}</td>
                        <td>{{ $resultat->coefficient }}</td>
                        <td class="{{ $resultat->note < 10 ? 'bad' : 'good' }}">{{ number_format($resultat->note, 2) }}</td>
                        <td>{{ number_format($resultat->moyenne_classe, 2) }}</td>
                        <td>{{ number_format($resultat->note_min, 2) }}</td>
                        <td>{{ number_format($resultat->note_max, 2) }}</td>
                        <td>{{ $resultat->rang }}/{{ $bulletin->effectif }}</td>
                        <td>
                            @php
                                if($resultat->note < 5) { 
                                    echo 'Très insuffisant';
                                } elseif($resultat->note < 10) { 
                                    echo 'Insuffisant'; 
                                } elseif($resultat->note < 12) { 
                                    echo 'Passable'; 
                                } elseif($resultat->note < 14) { 
                                    echo 'Assez bien'; 
                                } elseif($resultat->note < 16) { 
                                    echo 'Bien'; 
                                } else { 
                                    echo 'Très bien'; 
                                }
                            @endphp
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="total">
                        <td class="subject-col">MOYENNES GÉNÉRALES</td>
                        <td>{{ $resultats->sum('coefficient') }}</td>
                        <td class="{{ $bulletin->moyenne_generale < 10 ? 'bad' : 'good' }}">{{ number_format($bulletin->moyenne_generale, 2) }}</td>
                        <td>{{ number_format($bulletin->moyenne_classe, 2) }}</td>
                        <td>{{ number_format($bulletin->moyenne_min, 2) }}</td>
                        <td>{{ number_format($bulletin->moyenne_max, 2) }}</td>
                        <td>{{ $bulletin->rang }}/{{ $bulletin->effectif }}</td>
                        <td>
                            @php
                                if($bulletin->moyenne_generale < 5) { 
                                    echo 'Très insuffisant';
                                } elseif($bulletin->moyenne_generale < 10) { 
                                    echo 'Insuffisant'; 
                                } elseif($bulletin->moyenne_generale < 12) { 
                                    echo 'Passable'; 
                                } elseif($bulletin->moyenne_generale < 14) { 
                                    echo 'Assez bien'; 
                                } elseif($bulletin->moyenne_generale < 16) { 
                                    echo 'Bien'; 
                                } else { 
                                    echo 'Très bien'; 
                                }
                            @endphp
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        <!-- Statistiques de l'étudiant -->
        <div class="stats-box">
            <table>
                <tr>
                    <th colspan="4">Résumé des performances</th>
                </tr>
                <tr>
                    <td><strong>Moyenne générale:</strong> {{ number_format($bulletin->moyenne_generale, 2) }}/20</td>
                    <td><strong>Rang:</strong> {{ $bulletin->rang }}/{{ $bulletin->effectif }}</td>
                    <td><strong>Notes ≥ 10:</strong> {{ $resultats->where('note', '>=', 10)->count() }}/{{ $resultats->count() }}</td>
                    <td><strong>Notes < 10:</strong> {{ $resultats->where('note', '<', 10)->count() }}/{{ $resultats->count() }}</td>
                </tr>
            </table>
        </div>
        
        <!-- Observations -->
        <div class="observations">
            <table>
                <tr>
                    <th width="30%">Décision du conseil:</th>
                    <td width="70%">{{ $bulletin->decision ?? 'Non définie' }}</td>
                </tr>
                <tr>
                    <th width="30%">Observations:</th>
                    <td width="70%">{{ $bulletin->observations ?? 'Aucune observation particulière' }}</td>
                </tr>
            </table>
        </div>
        
        <!-- Signatures -->
        <div class="signatures">
            <table>
                <tr>
                    <td>Le Directeur des Études</td>
                    <td>Le Chef d'Établissement</td>
                    <td>Le Parent</td>
                </tr>
            </table>
        </div>
        
        <!-- Pied de page -->
        <div class="footer">
            <p>KLASSCI - Bulletin généré le {{ \Carbon\Carbon::now()->format('d/m/Y à H:i') }} - Page 1/1</p>
        </div>
    </div>
</body>
</html> 
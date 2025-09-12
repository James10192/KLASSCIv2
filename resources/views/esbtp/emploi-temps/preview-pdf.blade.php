<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prévisualisation PDF - Emploi du temps {{ $emploiTemps->classe->name ?? 'Non défini' }}</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 20px;
            background-color: white;
            color: #333;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: white;
        }

        /* En-tête PDF configurable (comme bulletins) */
        .pdf-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
            margin-bottom: 30px;
            border-radius: 10px;
        }

        .logo-section {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }

        .logo {
            max-width: 80px;
            max-height: 80px;
            border-radius: 10px;
            margin-right: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .school-info h1 {
            margin: 0 0 10px 0;
            font-size: 28px;
            font-weight: bold;
        }

        .school-info p {
            margin: 5px 0;
            font-size: 14px;
            opacity: 0.9;
        }

        .timetable-info {
            background: rgba(255, 255, 255, 0.1);
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
        }

        .timetable-info h2 {
            margin: 0 0 15px 0;
            font-size: 24px;
            text-align: center;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            text-align: left;
        }

        .info-item {
            display: flex;
            align-items: center;
            background: rgba(255, 255, 255, 0.1);
            padding: 10px;
            border-radius: 5px;
        }

        .info-label {
            font-weight: bold;
            margin-right: 10px;
            min-width: 80px;
        }

        /* Notice de prévisualisation */
        .preview-notice {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 15px;
            text-align: center;
            font-weight: bold;
            font-size: 16px;
            margin-bottom: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        /* Table emploi du temps - MÊMES STYLES que show */
        .timetable-container {
            overflow-x: auto;
            margin-bottom: 30px;
        }

        .timetable {
            width: 100%;
            min-width: 900px;
            border-collapse: collapse;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .timetable th, .timetable td {
            min-width: 150px;
            height: 60px;
            position: relative;
            border: 1px solid #dee2e6;
            padding: 8px;
            text-align: center;
            vertical-align: middle;
        }

        .timetable th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-weight: bold;
            font-size: 14px;
        }

        .time-column {
            width: 80px;
            font-weight: bold;
            background-color: #f8f9fa;
            font-size: 12px;
        }

        /* Session cells - EXACTEMENT comme show */
        .session-cell {
            padding: 5px;
            border-radius: 4px;
            font-size: 0.85rem;
            color: #fff;
            height: calc(100% - 10px);
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .session-matiere {
            font-weight: bold;
            font-size: 0.9rem;
            margin-bottom: 2px;
        }

        .session-enseignant {
            font-size: 0.8rem;
            opacity: 0.9;
            margin-bottom: 2px;
        }

        .session-details {
            font-size: 0.75rem;
            opacity: 0.8;
        }

        /* MÊMES COULEURS que show */
        .session-cours {
            background-color: #667eea;
        }

        .session-td {
            background-color: #10b981;
        }

        .session-tp {
            background-color: #6b7280;
        }

        .session-examen {
            background-color: #ef4444;
        }

        .session-autre {
            background-color: #f59e0b;
            color: #1f2937 !important;
        }

        .session-pause {
            background-color: #9ca3af;
        }

        .session-dejeuner {
            background-color: #f97316;
            color: #1f2937 !important;
        }

        /* Statistiques */
        .stats-section {
            margin: 30px 0;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 10px;
        }

        .stats-title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 20px;
            color: #495057;
            text-align: center;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            border-left: 4px solid #667eea;
        }

        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 14px;
            color: #6c757d;
        }

        /* Légende */
        .legend {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 15px;
            margin: 20px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
        }

        .legend-item {
            display: flex;
            align-items: center;
            font-size: 14px;
            font-weight: 500;
        }

        .legend-color {
            width: 20px;
            height: 15px;
            border-radius: 3px;
            margin-right: 8px;
        }

        /* Actions de navigation */
        .actions-section {
            padding: 30px 0;
            text-align: center;
            border-top: 1px solid #dee2e6;
            margin-top: 30px;
        }

        .btn {
            display: inline-block;
            padding: 12px 24px;
            margin: 0 10px 10px 0;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }

        .btn-secondary {
            background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            
            .timetable-container {
                margin-bottom: 20px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .legend {
                flex-direction: column;
                align-items: flex-start;
            }
        }

        @media print {
            .actions-section {
                display: none;
            }
            
            .preview-notice {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- En-tête PDF configurable -->
        @if($settings['timetable_show_header'] ?? true)
        <div class="pdf-header">
            <div class="logo-section">
                @if($logoBase64 && ($settings['timetable_show_logo'] ?? true))
                    <img src="{{ $logoBase64 }}" alt="Logo" class="logo">
                @endif
                <div class="school-info">
                    <h1>{{ $settings['school_name'] ?? 'École Spéciale du Bâtiment et des Travaux Publics' }}</h1>
                    <p>{{ $settings['school_type'] ?? 'Enseignement Supérieur Technique' }}</p>
                    @if($settings['school_address'])
                        <p>{{ $settings['school_address'] }}</p>
                    @endif
                    @if($settings['school_city'])
                        <p>{{ $settings['school_city'] }} - {{ $settings['school_country'] ?? 'Côte d\'Ivoire' }}</p>
                    @endif
                </div>
            </div>
            
            <div class="timetable-info">
                <h2>EMPLOI DU TEMPS</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Classe :</span>
                        <span>{{ $emploiTemps->classe->name ?? 'Non définie' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Filière :</span>
                        <span>{{ $emploiTemps->classe->filiere->name ?? 'Non définie' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Niveau :</span>
                        <span>{{ $emploiTemps->classe->niveau->name ?? 'Non défini' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Année :</span>
                        <span>{{ $emploiTemps->annee->name ?? 'Non définie' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Effectif :</span>
                        <span>{{ $emploiTemps->classe->etudiants_count ?? 0 }} étudiants</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Édité le :</span>
                        <span>{{ now()->format('d/m/Y à H:i') }}</span>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Notice de prévisualisation -->
        <div class="preview-notice">
            📋 PRÉVISUALISATION PDF - Aperçu de l'emploi du temps avant génération du fichier final
        </div>

        <!-- Légende des couleurs -->
        <div class="legend">
            <div class="legend-item">
                <div class="legend-color session-cours"></div>
                <span>Cours magistraux</span>
            </div>
            <div class="legend-item">
                <div class="legend-color session-td"></div>
                <span>Travaux dirigés</span>
            </div>
            <div class="legend-item">
                <div class="legend-color session-tp"></div>
                <span>Travaux pratiques</span>
            </div>
            <div class="legend-item">
                <div class="legend-color session-examen"></div>
                <span>Examens</span>
            </div>
            <div class="legend-item">
                <div class="legend-color session-pause"></div>
                <span>Pauses</span>
            </div>
            <div class="legend-item">
                <div class="legend-color session-dejeuner"></div>
                <span>Déjeuner</span>
            </div>
        </div>

        <!-- Tableau emploi du temps -->
        <div class="timetable-container">
            <table class="timetable">
                <thead>
                    <tr>
                        <th class="time-column">Heure</th>
                        <th>Lundi</th>
                        <th>Mardi</th>
                        <th>Mercredi</th>
                        <th>Jeudi</th>
                        <th>Vendredi</th>
                        <th>Samedi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($timeSlots as $timeIndex => $time)
                        <tr>
                            <td class="time-column">{{ $time }}</td>
                            @foreach($days as $dayIndex => $day)
                                <td>
                                    @if(isset($seancesParJour[$dayIndex + 1][$timeIndex]))
                                        @php
                                            $seance = $seancesParJour[$dayIndex + 1][$timeIndex];
                                            $typeClass = 'session-' . strtolower($seance->type ?? 'autre');
                                        @endphp
                                        <div class="session-cell {{ $typeClass }}">
                                            <div class="session-matiere">
                                                {{ $seance->matiere->name ?? 'Matière' }}
                                            </div>
                                            <div class="session-enseignant">
                                                {{ $seance->enseignant_nom ?? 'Enseignant' }}
                                            </div>
                                            <div class="session-details">
                                                {{ ucfirst($seance->type ?? 'Cours') }}
                                                @if($seance->salle)
                                                    - {{ $seance->salle }}
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Statistiques -->
        @if($settings['timetable_show_stats'] ?? true)
        <div class="stats-section">
            <h3 class="stats-title">📊 Statistiques de l'emploi du temps</h3>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number">{{ count($seances) }}</div>
                    <div class="stat-label">Total séances</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">{{ count($matiereStats) }}</div>
                    <div class="stat-label">Matières différentes</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">{{ count($timeSlots) }}</div>
                    <div class="stat-label">Créneaux horaires</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">6</div>
                    <div class="stat-label">Jours de cours</div>
                </div>
            </div>
            
            @if(!empty($matiereStats))
                <h4 style="margin-top: 30px; margin-bottom: 15px; color: #495057; text-align: center;">Répartition par matière</h4>
                <div class="stats-grid">
                    @foreach($matiereStats as $matiere => $count)
                        <div class="stat-card">
                            <div class="stat-number">{{ $count }}</div>
                            <div class="stat-label">{{ $matiere }}</div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
        @endif

        <!-- Actions -->
        <div class="actions-section">
            <h3 style="margin-bottom: 20px; color: #495057;">Actions disponibles</h3>
            <a href="{{ route('esbtp.emploi-temps.show', $emploiTemps->id) }}" class="btn btn-secondary">
                ⬅️ Retour à l'emploi du temps
            </a>
            <a href="{{ route('esbtp.emploi-temps.export-pdf', $emploiTemps->id) }}" class="btn btn-success">
                📥 Télécharger PDF
            </a>
            <a href="{{ route('esbtp.emploi-temps.edit', $emploiTemps->id) }}" class="btn btn-primary">
                ✏️ Modifier l'emploi du temps
            </a>
        </div>
    </div>
</body>
</html>
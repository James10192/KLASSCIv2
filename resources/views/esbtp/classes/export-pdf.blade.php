<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Export classes - {{ $settings['nom'] ?? 'Établissement' }}</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            font-size: 10px;
            margin: 0;
            padding: 0;
            background: #f3f4f6;
            color: #1f2937;
            line-height: 1.4;
        }

        .page {
            padding: 12px;
        }

        .container {
            background: #ffffff;
            border-radius: 12px;
            padding: 10px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.04);
        }

        .header-section {
            background: #0453cb;
            color: #ffffff;
            padding: 14px;
            border-radius: 12px;
            text-align: center;
            margin-bottom: 14px;
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }

        .header-logo {
            max-height: 38px;
            margin-bottom: 8px;
        }

        .school-name {
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 0.4px;
            margin-bottom: 4px;
        }

        .school-meta {
            font-size: 9px;
            opacity: 0.9;
        }

        .document-title {
            display: inline-block;
            margin-top: 8px;
            background: rgba(255, 255, 255, 0.15);
            padding: 5px 12px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 0.6px;
        }

        .info-section {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 10px;
            margin-bottom: 12px;
            text-align: center;
        }

        .info-label {
            font-size: 9px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            margin-bottom: 4px;
        }

        .info-value {
            font-size: 12px;
            font-weight: 700;
            color: #0453cb;
        }

        .kpi-grid {
            text-align: center;
            font-size: 0;
            margin-bottom: 12px;
        }

        .kpi-card {
            display: inline-block;
            vertical-align: top;
            width: 23%;
            margin: 0 1% 6px;
            font-size: 10px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 8px 10px;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.06);
        }

        .kpi-label {
            font-size: 8px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            margin-bottom: 6px;
        }

        .kpi-value {
            font-size: 15px;
            font-weight: 700;
            color: #0453cb;
            margin-bottom: 4px;
        }

        .kpi-sub {
            font-size: 9px;
            color: #6b7280;
        }

        .section-title {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.4px;
            text-transform: uppercase;
            color: #0f172a;
            margin: 16px 0 8px;
        }

        .classes-table {
            width: 100%;
            border-collapse: collapse;
            background: #ffffff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 6px 14px rgba(15, 23, 42, 0.05);
            table-layout: fixed;
        }

        .classes-table thead th {
            background: #0453cb;
            color: #ffffff;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.2px;
            font-size: 7.5px;
            padding: 4px 3px;
            text-align: center;
            border-right: 1px solid rgba(255, 255, 255, 0.2);
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }

        .classes-table thead th:last-child {
            border-right: none;
        }

        .classes-table tbody td {
            padding: 4px 3px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: middle;
            font-size: 8px;
            word-break: break-word;
        }

        .classes-table tbody tr:nth-child(even) {
            background: #f8fafc;
        }

        .classes-table tbody tr:last-child td {
            border-bottom: none;
        }

        /* Colonne N° */
        .classes-table td:nth-child(1),
        .classes-table th:nth-child(1) {
            width: 30px;
            text-align: center;
        }

        /* Colonne Nom classe */
        .classes-table td:nth-child(2),
        .classes-table th:nth-child(2) {
            width: 120px;
            font-weight: 600;
        }

        /* Colonne Code */
        .classes-table td:nth-child(3),
        .classes-table th:nth-child(3) {
            width: 70px;
            text-align: center;
            font-family: 'Courier New', monospace;
        }

        /* Colonne Filière */
        .classes-table td:nth-child(4),
        .classes-table th:nth-child(4) {
            width: 120px;
        }

        /* Colonne Niveau */
        .classes-table td:nth-child(5),
        .classes-table th:nth-child(5) {
            width: 90px;
        }

        /* Colonne Effectif/Capacité */
        .classes-table td:nth-child(6),
        .classes-table th:nth-child(6) {
            width: 65px;
            text-align: center;
            font-weight: 600;
        }

        /* Colonne Taux */
        .classes-table td:nth-child(7),
        .classes-table th:nth-child(7) {
            width: 55px;
            text-align: center;
        }

        /* Colonne Statut */
        .classes-table td:nth-child(8),
        .classes-table th:nth-child(8) {
            width: 50px;
            text-align: center;
        }

        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 999px;
            font-size: 7px;
            font-weight: 600;
            letter-spacing: 0.3px;
            text-transform: uppercase;
        }

        .badge-success {
            background: #dcfce7;
            color: #166534;
        }

        .badge-danger {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge-warning {
            background: #fef3c7;
            color: #92400e;
        }

        .filters-section {
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 8px;
            padding: 8px 10px;
            margin-top: 12px;
        }

        .filters-title {
            font-size: 9px;
            font-weight: 700;
            color: #78350f;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            margin-bottom: 4px;
        }

        .filters-list {
            font-size: 8px;
            color: #92400e;
            line-height: 1.6;
        }

        .footer {
            margin-top: 14px;
            text-align: center;
            font-size: 8px;
            color: #9ca3af;
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="container">
            {{-- En-tête --}}
            <div class="header-section">
                @if(!empty($settings['logo']))
                    <img src="{{ public_path('storage/' . $settings['logo']) }}" alt="Logo" class="header-logo">
                @endif

                <div class="school-name">{{ $settings['nom'] ?? 'ESBTP-yAKRO' }}</div>

                @if(!empty($settings['adresse']) || !empty($settings['telephone']))
                    <div class="school-meta">
                        @if(!empty($settings['adresse']))
                            {{ $settings['adresse'] }}
                        @endif
                        @if(!empty($settings['adresse']) && !empty($settings['telephone']))
                            •
                        @endif
                        @if(!empty($settings['telephone']))
                            Tél: {{ $settings['telephone'] }}
                        @endif
                    </div>
                @endif

                <div class="document-title">LISTE DES CLASSES</div>
            </div>

            {{-- Année universitaire --}}
            <div class="info-section">
                <div class="info-label">Année Universitaire</div>
                <div class="info-value">{{ $anneeCourante ? $anneeCourante->name : 'Année en cours' }}</div>
            </div>

            {{-- KPI Cards --}}
            @php
                $totalClasses = $classes->count();
                $classesActives = $classes->where('is_active', true)->count();
                $totalEffectif = 0;
                $totalCapacite = 0;

                foreach ($classes as $classe) {
                    $effectif = $classe->inscriptions()->where('status', '!=', 'annulée')->count();
                    $totalEffectif += $effectif;
                    $totalCapacite += $classe->places_totales ?? 0;
                }

                $tauxMoyenRemplissage = $totalCapacite > 0 ? round(($totalEffectif / $totalCapacite) * 100, 1) : 0;
            @endphp

            <div class="kpi-grid">
                <div class="kpi-card">
                    <div class="kpi-label">Total Classes</div>
                    <div class="kpi-value">{{ $totalClasses }}</div>
                    <div class="kpi-sub">{{ $classesActives }} actives</div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-label">Effectif Total</div>
                    <div class="kpi-value">{{ $totalEffectif }}</div>
                    <div class="kpi-sub">étudiants</div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-label">Capacité Totale</div>
                    <div class="kpi-value">{{ $totalCapacite }}</div>
                    <div class="kpi-sub">places</div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-label">Taux Remplissage</div>
                    <div class="kpi-value">{{ $tauxMoyenRemplissage }}%</div>
                    <div class="kpi-sub">moyen</div>
                </div>
            </div>

            {{-- Tableau des classes --}}
            <div class="section-title">DÉTAIL DES CLASSES</div>

            <table class="classes-table">
                <thead>
                    <tr>
                        <th>N°</th>
                        <th>Nom Classe</th>
                        <th>Code</th>
                        <th>Filière</th>
                        <th>Niveau</th>
                        <th>Effectif</th>
                        <th>Taux</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($classes as $index => $classe)
                        @php
                            $effectifActuel = $classe->inscriptions()->where('status', '!=', 'annulée')->count();
                            $capaciteMax = $classe->places_totales ?? 0;
                            $placesRestantes = max(0, $capaciteMax - $effectifActuel);
                            $tauxRemplissage = $capaciteMax > 0 ? round(($effectifActuel / $capaciteMax) * 100, 1) : 0;

                            $badgeClass = 'badge-success';
                            if ($tauxRemplissage >= 100) {
                                $badgeClass = 'badge-danger';
                            } elseif ($tauxRemplissage >= 80) {
                                $badgeClass = 'badge-warning';
                            }
                        @endphp
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $classe->name ?? 'N/A' }}</td>
                            <td>{{ $classe->code ?? 'N/A' }}</td>
                            <td>{{ $classe->filiere ? $classe->filiere->name : 'N/A' }}</td>
                            <td>{{ $classe->niveau ? $classe->niveau->name : 'N/A' }}</td>
                            <td>{{ $effectifActuel }}/{{ $capaciteMax }}</td>
                            <td>
                                <span class="badge {{ $badgeClass }}">
                                    {{ $tauxRemplissage }}%
                                </span>
                            </td>
                            <td>
                                @if($classe->is_active)
                                    <span class="badge badge-success">Active</span>
                                @else
                                    <span class="badge badge-danger">Inactive</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            {{-- Filtres appliqués --}}
            @if(!empty(array_filter($filters)))
                <div class="filters-section">
                    <div class="filters-title">🔍 Filtres appliqués</div>
                    <div class="filters-list">
                        @if(!empty($filters['search']))
                            • Recherche : <strong>{{ $filters['search'] }}</strong><br>
                        @endif

                        @if(!empty($filters['filiere_id']))
                            @php
                                $filiere = \App\Models\ESBTPFiliere::find($filters['filiere_id']);
                            @endphp
                            @if($filiere)
                                • Filière : <strong>{{ $filiere->name }}</strong><br>
                            @endif
                        @endif

                        @if(!empty($filters['niveau_id']))
                            @php
                                $niveau = \App\Models\ESBTPNiveauEtude::find($filters['niveau_id']);
                            @endphp
                            @if($niveau)
                                • Niveau : <strong>{{ $niveau->name }}</strong><br>
                            @endif
                        @endif

                        @if(!empty($filters['statut']))
                            • Statut : <strong>{{ $filters['statut'] === 'active' ? 'Actives' : 'Inactives' }}</strong><br>
                        @endif

                        @if(!empty($filters['capacite']))
                            @php
                                $capaciteLabel = [
                                    'disponible' => 'Classes avec places disponibles',
                                    'pleine' => 'Classes pleines'
                                ][$filters['capacite']] ?? $filters['capacite'];
                            @endphp
                            • Capacité : <strong>{{ $capaciteLabel }}</strong><br>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Footer --}}
            <div class="footer">
                Document généré le {{ $dateExport->format('d/m/Y à H:i') }}
            </div>
        </div>
    </div>
</body>
</html>

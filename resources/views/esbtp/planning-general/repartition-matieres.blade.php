@extends('layouts.app')

@section('title', 'Charge Pédagogique par Classe - KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
    .repartition-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: var(--space-xl);
        border-radius: var(--radius-large);
        margin-bottom: var(--space-xl);
    }
    
    .planning-nav {
        background: var(--surface);
        border-radius: var(--radius-medium);
        padding: var(--space-md);
        margin-bottom: var(--space-lg);
        box-shadow: var(--shadow-card);
    }
    
    .planning-nav .nav-tabs {
        border: none;
        background: rgba(var(--primary-rgb), 0.05);
        border-radius: var(--radius-small);
        padding: var(--space-xs);
    }
    
    .planning-nav .nav-link {
        border: none;
        color: var(--text-secondary);
        background: transparent;
        border-radius: var(--radius-small);
        padding: var(--space-sm) var(--space-md);
        margin: 0 var(--space-xs);
        transition: all 0.3s ease;
    }
    
    .planning-nav .nav-link.active {
        background: var(--primary);
        color: white;
        box-shadow: 0 2px 8px rgba(var(--primary-rgb), 0.3);
    }
    
    .chart-container {
        background: var(--surface);
        border-radius: var(--radius-medium);
        padding: var(--space-xl);
        margin-bottom: var(--space-lg);
        box-shadow: var(--shadow-card);
        position: relative;
        height: 450px;
        overflow: hidden; /* Empêcher les scrollbars */
    }
    
    .chart-container canvas {
        max-height: 100%;
        max-width: 100%;
        width: 100% !important;
        height: auto !important;
    }
    
    .matiere-card {
        background: var(--surface);
        border-radius: var(--radius-medium);
        padding: var(--space-lg);
        margin-bottom: var(--space-sm);
        border: 1px solid var(--border);
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    
    .matiere-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 4px;
        height: 100%;
        background: var(--primary);
        transition: width 0.3s ease;
    }
    
    .matiere-card:hover::before {
        width: 8px;
    }
    
    .matiere-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-hover);
    }
    
    .matiere-nom {
        font-weight: 600;
        color: var(--primary);
        margin-bottom: var(--space-xs);
    }
    
    .matiere-stats {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: var(--space-lg);
    }
    
    .stat-item {
        text-align: center;
        flex: 1;
    }
    
    .stat-value {
        font-size: 1.5rem;
        font-weight: bold;
        color: var(--primary);
    }
    
    .stat-label {
        font-size: 0.875rem;
        color: var(--text-secondary);
    }
    
    .progress-bar-matiere {
        height: 6px;
        background: var(--border);
        border-radius: var(--radius-full);
        overflow: hidden;
        margin-top: var(--space-sm);
    }
    
    .progress-fill-matiere {
        height: 100%;
        background: linear-gradient(90deg, var(--primary), var(--primary-light));
        transition: width 0.8s ease;
    }
    
    .progress-bars-container {
        margin-top: var(--space-md);
    }
    
    .progress-bar-volume {
        height: 8px;
        background: #f1f3f4;
        border-radius: var(--radius-full);
        overflow: hidden;
        margin-bottom: var(--space-xs);
        position: relative;
    }
    
    .progress-bar-volume.realise {
        background: #e8f5e8;
    }
    
    .progress-bar-volume.planifie {
        background: #e3f2fd;
    }
    
    .progress-fill-volume {
        height: 100%;
        transition: width 0.8s ease;
        border-radius: var(--radius-full);
    }
    
    .progress-fill-volume.realise {
        background: linear-gradient(90deg, #4caf50, #66bb6a);
    }
    
    .progress-fill-volume.planifie {
        background: linear-gradient(90deg, #2196f3, #42a5f5);
    }
    
    .progress-fill-volume.restant {
        background: linear-gradient(90deg, #ff9800, #ffb74d);
    }
    
    .volume-legend {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 0.75rem;
        color: var(--text-secondary);
        margin-bottom: var(--space-xs);
    }
    
    .non-configure-card {
        background: #fff3cd;
        border: 1px solid #ffeaa7;
        border-radius: var(--radius-medium);
        padding: var(--space-md);
        margin-top: var(--space-sm);
        text-align: center;
    }
    
    .non-configure-card .icon {
        color: #856404;
        font-size: 1.5rem;
        margin-bottom: var(--space-sm);
    }
    
    .configure-btn {
        margin-top: var(--space-sm);
    }
    
    .filters-section {
        background: var(--surface);
        border-radius: var(--radius-medium);
        padding: var(--space-lg);
        margin-bottom: var(--space-xl);
        box-shadow: var(--shadow-card);
    }
    
    .summary-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: var(--space-lg);
        margin-bottom: var(--space-xl);
    }
    
    .summary-card {
        background: var(--surface);
        border-radius: var(--radius-medium);
        padding: var(--space-lg);
        text-align: center;
        box-shadow: var(--shadow-card);
        border: 1px solid var(--border);
    }
    
    .summary-card .icon {
        width: 50px;
        height: 50px;
        border-radius: var(--radius-circle);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto var(--space-md);
        font-size: 1.25rem;
        color: white;
    }
    
    .summary-card.primary .icon { background: var(--primary); }
    .summary-card.success .icon { background: var(--success); }
    .summary-card.info .icon { background: var(--info); }
    .summary-card.warning .icon { background: var(--warning); }
    
    .legend-item {
        display: flex;
        align-items: center;
        margin-bottom: var(--space-xs);
    }
    
    .legend-color {
        width: 16px;
        height: 16px;
        border-radius: var(--radius-small);
        margin-right: var(--space-sm);
    }
    
    .objectif-badge {
        position: absolute;
        top: var(--space-md);
        right: var(--space-md);
        padding: var(--space-xs) var(--space-sm);
        border-radius: var(--radius-small);
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .objectif-badge.atteint {
        background: var(--success);
        color: white;
    }
    
    .objectif-badge.proche {
        background: var(--warning);
        color: white;
    }
    
    .objectif-badge.loin {
        background: var(--danger);
        color: white;
    }

    .class-card {
        background: var(--surface);
        border-radius: var(--radius-medium);
        border: 1px solid var(--border);
        margin-bottom: var(--space-lg);
        box-shadow: var(--shadow-card);
        overflow: hidden;
    }

    .class-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--space-lg);
        padding: var(--space-md) var(--space-lg);
        border-bottom: 1px solid var(--border);
        background: #f8fafc;
    }

    .class-card-body {
        padding: var(--space-lg);
    }

    .class-title a {
        color: var(--primary);
        font-weight: 700;
    }

    .class-meta {
        margin-top: var(--space-xs);
    }

    .class-kpis {
        display: flex;
        align-items: center;
        gap: var(--space-md);
        flex-wrap: wrap;
    }

    .class-kpi {
        text-align: center;
        min-width: 90px;
    }

    .class-kpi .value {
        font-weight: 700;
        color: var(--primary);
    }

    .class-kpi .label {
        font-size: 0.75rem;
        color: var(--text-secondary);
    }

    .teacher-list {
        margin-top: var(--space-md);
    }

    .teacher-label {
        font-size: 0.85rem;
        color: var(--text-secondary);
        margin-bottom: var(--space-xs);
    }

    .teacher-chips {
        display: flex;
        flex-wrap: wrap;
        gap: var(--space-xs);
    }

    .teacher-chip {
        display: flex;
        flex-direction: column;
        gap: 2px;
        padding: var(--space-xs) var(--space-sm);
        border-radius: var(--radius-small);
        border: 1px solid var(--border);
        background: #f8f9fb;
        text-decoration: none;
        color: var(--text-primary);
        transition: all 0.2s ease;
    }

    .teacher-chip:hover {
        box-shadow: var(--shadow-hover);
        transform: translateY(-1px);
    }

    .teacher-name {
        font-weight: 600;
        font-size: 0.85rem;
    }

    .teacher-hours {
        font-size: 0.75rem;
        color: var(--text-secondary);
    }

    .teacher-empty {
        font-size: 0.85rem;
        color: var(--text-secondary);
    }
    
    @media (max-width: 768px) {
        .class-card-header {
            flex-direction: column;
            align-items: flex-start;
        }

        .class-kpis {
            width: 100%;
            justify-content: space-between;
        }

        .chart-container {
            height: 350px;
            padding: var(--space-md);
        }
        
        .chart-container canvas {
            max-height: 300px !important;
        }
        
        .matiere-stats {
            flex-direction: column;
            gap: var(--space-md);
        }
        
        .summary-stats {
            grid-template-columns: 1fr;
        }
    }
    
    @media (max-width: 576px) {
        .chart-container {
            height: 300px;
            padding: var(--space-sm);
        }
        
        .chart-container h5 {
            font-size: 1rem;
        }
    }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header et navigation du planning -->
        <x-planning-header 
            title="Charge pédagogique par classe" 
            subtitle="Suivi des heures planifiées et réalisées par matière, avec les enseignants affectés"
            active-tab="repartition"
            :annee-selectionnee="$anneeSelectionnee"
            :annees="$annees"
        />


        <!-- Filtres -->
        <div class="filters-section">
            <form method="GET" class="row align-items-end">
                <div class="col-md-3">
                    <label for="annee_id" class="form-label">Année Universitaire</label>
                    <select name="annee_id" id="annee_id" class="form-select" onchange="this.form.submit()">
                        <option value="all" {{ request('annee_id') == 'all' ? 'selected' : '' }}>Toutes les années</option>
                        @foreach($annees as $annee)
                            <option value="{{ $annee->id }}" {{ request('annee_id') == $annee->id ? 'selected' : '' }}>
                                {{ $annee->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="classe_id" class="form-label">Classe</label>
                    <select name="classe_id" id="classe_id" class="form-select" onchange="this.form.submit()">
                        <option value="">Toutes les classes</option>
                        @foreach($classes as $classe)
                            <option value="{{ $classe->id }}" 
                                    data-filiere-id="{{ $classe->filiere_id }}"
                                    data-niveau-id="{{ $classe->niveau_etude_id }}"
                                    {{ request('classe_id') == $classe->id ? 'selected' : '' }}>
                                {{ $classe->name }} ({{ $classe->filiere->name ?? 'N/A' }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Période</label>
                    <div class="d-flex gap-2">
                        <button type="submit" name="periode" value="semestre1" class="btn btn-sm btn-outline-primary {{ request('periode') == 'semestre1' ? 'active' : '' }}">
                            Semestre 1
                        </button>
                        <button type="submit" name="periode" value="semestre2" class="btn btn-sm btn-outline-primary {{ request('periode') == 'semestre2' ? 'active' : '' }}">
                            Semestre 2
                        </button>
                        <button type="submit" name="periode" value="annee" class="btn btn-sm btn-outline-primary {{ request('periode') == 'annee' || !request('periode') ? 'active' : '' }}">
                            Année
                        </button>
                    </div>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn-acasi primary">
                        <i class="fas fa-search me-1"></i>Filtrer
                    </button>
                </div>
            </form>
        </div>

        <!-- Statistiques résumées -->
        <div class="summary-stats">
            <div class="summary-card primary">
                <div class="icon">
                    <i class="fas fa-school"></i>
                </div>
                <div class="stat-value">{{ $statsRepartition['classes'] ?? 0 }}</div>
                <div class="stat-label">Classes couvertes</div>
            </div>
            <div class="summary-card info">
                <div class="icon">
                    <i class="fas fa-book"></i>
                </div>
                <div class="stat-value">{{ $statsRepartition['matieres'] ?? 0 }}</div>
                <div class="stat-label">Matières suivies</div>
            </div>
            <div class="summary-card success">
                <div class="icon">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="stat-value">{{ number_format($statsRepartition['heures_planifiees'] ?? 0, 1) }}h</div>
                <div class="stat-label">Heures planifiées</div>
            </div>
            <div class="summary-card warning">
                <div class="icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-value">{{ $statsRepartition['taux_realisation'] ?? 0 }}%</div>
                <div class="stat-label">Taux de réalisation</div>
            </div>
        </div>

        <div class="row">
            <!-- Graphique en secteurs -->
            <div class="col-md-6">
                <div class="chart-container">
                    <h5 class="mb-3"><i class="fas fa-chart-pie me-2"></i>Répartition des heures réalisées</h5>
                    <canvas id="pieChart"></canvas>
                </div>
            </div>
            
            <!-- Graphique en barres -->
            <div class="col-md-6">
                <div class="chart-container">
                    <h5 class="mb-3"><i class="fas fa-chart-bar me-2"></i>Planifié vs Réalisé par classe</h5>
                    <canvas id="barChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Détail par classe et matière -->
        <div class="card-moderne">
            <div class="card-header">
                <h5><i class="fas fa-layer-group me-2"></i>Détail par classe et matière</h5>
                <p class="text-muted mb-0">
                    @if(request('classe_id'))
                        @php
                            $classeHeader = $classes->find(request('classe_id'));
                            $filiereHeader = $classeHeader?->filiere?->name ?? 'N/A';
                            $niveauHeader = $classeHeader?->niveau?->name ?? 'N/A';
                        @endphp
                        Classe : {{ $classeHeader?->name ?? 'N/A' }}
                        <span class="badge bg-info ms-2">{{ $filiereHeader }} - {{ $niveauHeader }}</span>
                    @else
                        Toutes les classes
                    @endif
                    @if(request('annee_id'))
                        - Année : {{ $annees->find(request('annee_id'))->name ?? 'N/A' }}
                    @endif
                </p>
            </div>
            <div class="card-body">
                @if($repartition->count() > 0)
                    @foreach($repartition as $classeData)
                        @php
                            $classe = $classeData['classe'];
                            $collapseId = 'classe-repartition-' . $classe->id;
                            $filiereName = $classe->filiere->name ?? 'N/A';
                            $niveauName = $classe->niveau->name ?? 'N/A';
                        @endphp
                        <div class="class-card">
                            <div class="class-card-header">
                                <div>
                                    <h6 class="class-title">
                                        <a href="{{ route('esbtp.classes.show', ['classe' => $classe->id]) }}" class="text-decoration-none">
                                            {{ $classe->name }}
                                        </a>
                                    </h6>
                                    <div class="class-meta">
                                        <span class="badge bg-info">{{ $filiereName }} - {{ $niveauName }}</span>
                                    </div>
                                </div>
                            <div class="class-kpis">
                                    <div class="class-kpi">
                                        <span class="value">{{ number_format($classeData['stats']['heures_planifiees_total'], 1) }}h</span>
                                        <span class="label">Planifiées</span>
                                    </div>
                                    <div class="class-kpi">
                                        <span class="value">{{ number_format($classeData['stats']['heures_realisees_total'], 1) }}h</span>
                                        <span class="label">Réalisées</span>
                                    </div>
                                    <div class="class-kpi">
                                        <span class="value">{{ $classeData['stats']['taux_realisation'] }}%</span>
                                        <span class="label">Réalisation</span>
                                </div>
                            </div>
                            <a href="{{ route('esbtp.classes.show', ['classe' => $classe->id]) }}" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-eye me-1"></i>Voir la classe
                            </a>
                            <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $collapseId }}" aria-expanded="true" aria-controls="{{ $collapseId }}">
                                <i class="fas fa-chevron-up"></i>
                            </button>
                            </div>
                            <div id="{{ $collapseId }}" class="collapse show">
                                <div class="class-card-body">
                                    @if($classeData['matieres']->isNotEmpty())
                                        @foreach($classeData['matieres'] as $item)
                                            <div class="matiere-card"
                                                 data-matiere-id="{{ $item['matiere']->id ?? '' }}"
                                                 data-filiere-id="{{ $classe->filiere_id ?? '' }}"
                                                 data-niveau-id="{{ $classe->niveau_etude_id ?? '' }}"
                                                 data-matiere-name="{{ $item['matiere']->name ?? '' }}"
                                                 data-filiere-name="{{ $filiereName }}"
                                                 data-niveau-name="{{ $niveauName }}"
                                                 data-classe-id="{{ $classe->id }}"
                                                 data-classe-name="{{ $classe->name }}">
                                                <div class="d-flex justify-content-between align-items-start mb-3">
                                                    <div>
                                                        <h6 class="matiere-nom">{{ $item['matiere']->name ?? 'Matière inconnue' }}</h6>
                                                        <small class="text-muted">{{ $item['matiere']->code ?? 'N/A' }}</small>
                                                    </div>
                                                    <div class="objectif-badge {{ $item['est_configure'] ? ($item['pourcentage_realise'] >= 80 ? 'atteint' : ($item['pourcentage_realise'] >= 50 ? 'proche' : 'loin')) : 'loin' }}">
                                                        @if($item['est_configure'])
                                                            {{ $item['pourcentage_realise'] }}%
                                                        @else
                                                            Non configuré
                                                        @endif
                                                    </div>
                                                </div>

                                                <div class="matiere-stats">
                                                    <div class="stat-item">
                                                        <div class="stat-value">{{ number_format($item['heures_realisees'], 1) }}h</div>
                                                        <div class="stat-label">Heures réalisées</div>
                                                    </div>
                                                    @if($item['est_configure'])
                                                        <div class="stat-item">
                                                            <div class="stat-value">{{ number_format($item['heures_planifiees'], 1) }}h</div>
                                                            <div class="stat-label">Heures planifiées</div>
                                                        </div>
                                                        <div class="stat-item">
                                                            <div class="stat-value">{{ number_format($item['heures_restantes'], 1) }}h</div>
                                                            <div class="stat-label">Heures restantes</div>
                                                        </div>
                                                    @else
                                                        <div class="stat-item">
                                                            <div class="stat-value">{{ $item['nb_seances'] }}</div>
                                                            <div class="stat-label">Séances</div>
                                                        </div>
                                                        <div class="stat-item">
                                                            <div class="stat-value">{{ number_format($item['heures_realisees'] / max($item['nb_seances'], 1), 1) }}h</div>
                                                            <div class="stat-label">Moy. par séance</div>
                                                        </div>
                                                    @endif
                                                </div>

                                                @if($item['est_configure'])
                                                    <div class="progress-bars-container">
                                                        <div class="volume-legend">
                                                            <span>Réalisé vs Planifié ({{ ucfirst($item['periode']) }})</span>
                                                            <span class="fw-bold">{{ $item['pourcentage_realise'] }}% complété</span>
                                                        </div>
                                                        <div class="progress-bar-volume realise">
                                                            <div class="progress-fill-volume realise" style="width: {{ min($item['pourcentage_realise'], 100) }}%"></div>
                                                        </div>
                                                        <div class="d-flex justify-content-between mt-1">
                                                            <small class="text-success">✓ {{ number_format($item['heures_realisees'], 1) }}h réalisées</small>
                                                            @if($item['heures_restantes'] > 0)
                                                                <small class="text-warning">⏱ {{ number_format($item['heures_restantes'], 1) }}h restantes</small>
                                                            @else
                                                                <small class="text-success">✅ Objectif atteint</small>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @else
                                                    <div class="non-configure-card">
                                                        <div class="icon">
                                                            <i class="fas fa-exclamation-triangle"></i>
                                                        </div>
                                                        <div class="fw-bold mb-1">Planning non configuré</div>
                                                        @if($item['matiere'])
                                                            <div class="text-muted mb-2">
                                                                Aucune planification horaire n'a été définie pour {{ $filiereName }} - {{ $niveauName }}.
                                                            </div>
                                                            <button type="button" class="btn btn-sm btn-warning configure-btn"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#configureModal"
                                                                    data-matiere-id="{{ $item['matiere']->id }}"
                                                                    data-matiere-name="{{ $item['matiere']->name }}"
                                                                    data-matiere-code="{{ $item['matiere']->code }}"
                                                                    data-filiere-id="{{ $classe->filiere_id }}"
                                                                    data-niveau-id="{{ $classe->niveau_etude_id }}"
                                                                    data-filiere-name="{{ $filiereName }}"
                                                                    data-niveau-name="{{ $niveauName }}"
                                                                    data-classe-id="{{ $classe->id }}"
                                                                    data-classe-name="{{ $classe->name }}">
                                                                <i class="fas fa-cog me-1"></i>Configurer pour la classe
                                                            </button>
                                                        @else
                                                            <span class="text-muted">
                                                                <i class="fas fa-exclamation-triangle me-1"></i>Matière non définie
                                                            </span>
                                                        @endif
                                                    </div>
                                                @endif

                                                <div class="teacher-list">
                                                    @if($item['enseignants']->isNotEmpty())
                                                        <div class="teacher-label">Enseignants</div>
                                                        <div class="teacher-chips">
                                                            @foreach($item['enseignants'] as $enseignant)
                                                                <a href="{{ route('esbtp.enseignants.show', ['enseignant' => $enseignant['id']]) }}" class="teacher-chip">
                                                                    <span class="teacher-name">{{ $enseignant['name'] }}</span>
                                                                    <span class="teacher-hours">{{ number_format($enseignant['heures_realisees'], 1) }}h • {{ $enseignant['nb_seances'] }} séances</span>
                                                                </a>
                                                            @endforeach
                                                        </div>
                                                    @else
                                                        <div class="teacher-empty">Aucun enseignant trouvé sur l'emploi du temps.</div>
                                                    @endif
                                                </div>

                                                <div class="progress-bar-matiere" style="margin-top: {{ $item['est_configure'] ? 'var(--space-md)' : 'var(--space-sm)' }}">
                                                    <div class="progress-fill-matiere" style="width: {{ min($item['pourcentage'], 100) }}%"></div>
                                                </div>
                                                <div class="text-center mt-1">
                                                    <small class="text-muted">{{ $item['pourcentage'] }}% du total des heures réalisées de la classe</small>
                                                </div>
                                            </div>
                                        @endforeach
                                    @else
                                        <div class="text-center py-4">
                                            <i class="fas fa-calendar-times fa-2x text-muted mb-2"></i>
                                            <p class="text-muted mb-0">Aucune matière planifiée ou réalisée pour cette classe.</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-chart-pie fa-3x text-muted mb-3"></i>
                        <h5>Aucune donnée disponible</h5>
                        <p class="text-muted">Modifiez les filtres pour afficher des données de répartition.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Modal de configuration rapide du planning -->
<div class="modal fade" id="configureModal" tabindex="-1" aria-labelledby="configureModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="configureModalLabel">
                    <i class="fas fa-cog me-2"></i>Configuration du planning
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="configureForm">
                    @csrf
                    <input type="hidden" id="matiere_id" name="matiere_id">
                    <input type="hidden" name="annee_id" value="{{ $anneeSelectionnee?->id }}">
                    <input type="hidden" id="classe_id_modal" name="classe_id" value="">
                    <input type="hidden" id="filiere_id" name="filiere_id">
                    <input type="hidden" id="niveau_id" name="niveau_id">
                    
                    <!-- Informations contextuelles -->
                    <div class="alert alert-info">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-info-circle me-2"></i>
                            <div>
                                <strong>Configuration pour :</strong>
                                <span id="modal-context">Année {{ $anneeSelectionnee?->name }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Configuration de base -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="volume_horaire" class="form-label">
                                    <i class="fas fa-clock me-1"></i>Volume horaire prévu (heures)
                                </label>
                                <input type="number" class="form-control" id="volume_horaire" name="volume_horaire" 
                                       min="1" max="200" step="0.5" required>
                                <div class="form-text">Nombre total d'heures à enseigner</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="periode" class="form-label">
                                    <i class="fas fa-calendar-alt me-1"></i>Période
                                </label>
                                <select class="form-select" id="periode" name="periode" required>
                                    <option value="">Choisir une période</option>
                                    <option value="semestre1">Semestre 1</option>
                                    <option value="semestre2">Semestre 2</option>
                                    <option value="annee">Année complète</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="nb_seances" class="form-label">
                                    <i class="fas fa-list-ol me-1"></i>Nombre de séances
                                </label>
                                <input type="number" class="form-control" id="nb_seances" name="nb_seances" 
                                       min="1" max="100" required>
                                <div class="form-text">Nombre total de séances prévues</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="duree_seance" class="form-label">
                                    <i class="fas fa-hourglass-half me-1"></i>Durée par séance (heures)
                                </label>
                                <input type="number" class="form-control" id="duree_seance" name="duree_seance" 
                                       min="0.5" max="8" step="0.5" required>
                                <div class="form-text">Durée moyenne de chaque séance</div>
                            </div>
                        </div>
                    </div>

                    <!-- Calcul automatique -->
                    <div class="alert alert-secondary" id="calcul-automatique" style="display: none;">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-calculator me-2"></i>
                            <span id="calcul-text"></span>
                        </div>
                    </div>

                    <!-- Notes optionnelles -->
                    <div class="mb-3">
                        <label for="notes" class="form-label">
                            <i class="fas fa-sticky-note me-1"></i>Notes (optionnel)
                        </label>
                        <textarea class="form-control" id="notes" name="notes" rows="2" 
                                  placeholder="Remarques ou objectifs spécifiques..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Annuler
                </button>
                <a href="{{ route('esbtp.planning-general.index', array_filter(['annee_id' => $anneeSelectionnee?->id, 'classe_id' => request('classe_id')])) }}" 
                   class="btn btn-info">
                    <i class="fas fa-cogs me-1"></i>Configuration avancée
                </a>
                <button type="submit" form="configureForm" class="btn btn-primary" id="saveBtn">
                    <i class="fas fa-save me-1"></i>Enregistrer
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const debugLog = () => {};
const debugWarn = () => {};
const debugError = () => {};

$(document).ready(function() {
    const chartData = @json($chartData);
    const chartLabels = chartData.labels || [];
    const heuresPlanifiees = chartData.planifiees || [];
    const heuresRealisees = chartData.realisees || [];
    const totalHeures = heuresRealisees.reduce((sum, value) => sum + parseFloat(value || 0), 0);

    // Couleurs pour les graphiques
    const colors = [
        '#3B82F6', '#EF4444', '#10B981', '#F59E0B', '#8B5CF6',
        '#EC4899', '#14B8A6', '#F97316', '#6366F1', '#84CC16'
    ];

    if (chartLabels.length > 0) {
        const pieElement = document.getElementById('pieChart');
        const barElement = document.getElementById('barChart');

        if (pieElement) {
            const pieCtx = pieElement.getContext('2d');
            new Chart(pieCtx, {
                type: 'doughnut',
                data: {
                    labels: chartLabels,
                    datasets: [{
                        data: heuresRealisees,
                        backgroundColor: colors.slice(0, chartLabels.length),
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: window.innerWidth < 768 ? 'bottom' : 'right',
                            labels: {
                                usePointStyle: true,
                                padding: window.innerWidth < 768 ? 10 : 15,
                                font: {
                                    size: window.innerWidth < 768 ? 10 : 11
                                },
                                boxWidth: window.innerWidth < 768 ? 10 : 12
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed;
                                    const percentage = totalHeures > 0 ? ((value / totalHeures) * 100).toFixed(1) : 0;
                                    return `${label}: ${value}h (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
        }

        if (barElement) {
            const barCtx = barElement.getContext('2d');
            new Chart(barCtx, {
                type: 'bar',
                data: {
                    labels: chartLabels,
                    datasets: [
                        {
                            label: 'Heures planifiées',
                            data: heuresPlanifiees,
                            backgroundColor: 'rgba(59, 130, 246, 0.6)',
                            borderColor: '#3B82F6',
                            borderWidth: 1
                        },
                        {
                            label: 'Heures réalisées',
                            data: heuresRealisees,
                            backgroundColor: 'rgba(16, 185, 129, 0.6)',
                            borderColor: '#10B981',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return value + 'h';
                                }
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'bottom'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const value = context.parsed.y;
                                    const percentage = totalHeures > 0 ? ((value / totalHeures) * 100).toFixed(1) : 0;
                                    return `${context.dataset.label}: ${value}h (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
        }
    }
    
    // Animation des barres de progression
    setTimeout(() => {
        $('.progress-fill-matiere').each(function(index) {
            const width = $(this).css('width');
            $(this).css('width', '0');
            
            setTimeout(() => {
                $(this).animate({
                    'width': width
                }, 800);
            }, index * 200);
        });
    }, 500);
    
    // Animation d'entrée des cartes
    $('.matiere-card').each(function(index) {
        $(this).css({
            'opacity': '0',
            'transform': 'translateY(20px)'
        });
        
        setTimeout(() => {
            $(this).animate({
                'opacity': '1',
                'transform': 'translateY(0)'
            }, 300);
        }, index * 100);
    });

    // Gestion du modal de configuration
    document.getElementById('configureModal').addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const matiereId = button.getAttribute('data-matiere-id');
        const matiereName = button.getAttribute('data-matiere-name');
        const matiereCode = button.getAttribute('data-matiere-code');
        const filiereId = button.getAttribute('data-filiere-id');
        const niveauId = button.getAttribute('data-niveau-id');
        const filiereName = button.getAttribute('data-filiere-name');
        const niveauName = button.getAttribute('data-niveau-name');
        const classeId = button.getAttribute('data-classe-id');
        const classeName = button.getAttribute('data-classe-name');
        
        // Mettre à jour le titre du modal
        document.getElementById('configureModalLabel').innerHTML = '<i class="fas fa-cog me-2"></i>Configuration du planning - ' + matiereName;
        
        // Remplir le formulaire
        document.getElementById('matiere_id').value = matiereId;
        document.getElementById('filiere_id').value = filiereId || '';
        document.getElementById('niveau_id').value = niveauId || '';
        document.getElementById('classe_id_modal').value = classeId || '';
        
        // Mettre à jour le contexte affiché
        const contextElement = document.getElementById('modal-context');
        if (contextElement) {
            let contextHTML = 'Année {{ $anneeSelectionnee?->name }}';
            if (classeName) {
                contextHTML += ` - <strong>${classeName}</strong> <small class="text-muted">(${filiereName} - ${niveauName})</small>`;
            } else if (filiereId && niveauId) {
                contextHTML += ` - <strong>${filiereName} - ${niveauName}</strong>`;
            } else {
                contextHTML += ' - <em>Toutes les filières/niveaux</em>';
            }
            contextElement.innerHTML = contextHTML;
        }
        
        // Réinitialiser le formulaire
        document.getElementById('configureForm').reset();
        document.getElementById('matiere_id').value = matiereId; // Remettre l'ID après reset
        document.getElementById('filiere_id').value = filiereId || '';
        document.getElementById('niveau_id').value = niveauId || '';
        document.getElementById('calcul-automatique').style.display = 'none';
    });

    // Calculs automatiques en temps réel
    function updateCalculations() {
        const volumeHoraire = parseFloat(document.getElementById('volume_horaire').value) || 0;
        const nbSeances = parseInt(document.getElementById('nb_seances').value) || 0;
        const dureeSeance = parseFloat(document.getElementById('duree_seance').value) || 0;
        
        if (volumeHoraire > 0 && nbSeances > 0 && dureeSeance > 0) {
            const totalCalcule = nbSeances * dureeSeance;
            const difference = Math.abs(totalCalcule - volumeHoraire);
            
            let message = '';
            let alertClass = 'alert-secondary';
            
            if (Math.abs(totalCalcule - volumeHoraire) < 0.1) {
                message = `Parfait ! ${nbSeances} séances de ${dureeSeance}h = ${totalCalcule}h`;
                alertClass = 'alert-success';
            } else if (totalCalcule > volumeHoraire) {
                message = `Attention : ${nbSeances} séances de ${dureeSeance}h = ${totalCalcule}h (${difference}h de plus que prévu)`;
                alertClass = 'alert-warning';
            } else {
                message = `Attention : ${nbSeances} séances de ${dureeSeance}h = ${totalCalcule}h (${difference}h de moins que prévu)`;
                alertClass = 'alert-warning';
            }
            
            const calculElement = document.getElementById('calcul-automatique');
            calculElement.className = 'alert ' + alertClass;
            document.getElementById('calcul-text').textContent = message;
            calculElement.style.display = 'block';
        } else {
            document.getElementById('calcul-automatique').style.display = 'none';
        }
    }

    // Auto-calcul des valeurs manquantes
    function setupInputListeners() {
        const inputs = ['volume_horaire', 'nb_seances', 'duree_seance'];
        inputs.forEach(inputId => {
            document.getElementById(inputId).addEventListener('input', function() {
                updateCalculations();
                
                // Auto-calcul intelligent
                const volumeHoraire = parseFloat(document.getElementById('volume_horaire').value) || 0;
                const nbSeances = parseInt(document.getElementById('nb_seances').value) || 0;
                const dureeSeance = parseFloat(document.getElementById('duree_seance').value) || 0;
                
                // Si on a volume et nb séances, calculer durée
                if (volumeHoraire > 0 && nbSeances > 0 && dureeSeance === 0) {
                    const dureeCalculee = Math.round((volumeHoraire / nbSeances) * 2) / 2; // Arrondir au 0.5 près
                    document.getElementById('duree_seance').value = dureeCalculee;
                }
                
                // Si on a volume et durée, calculer nb séances
                if (volumeHoraire > 0 && dureeSeance > 0 && nbSeances === 0) {
                    const seancesCalculees = Math.round(volumeHoraire / dureeSeance);
                    document.getElementById('nb_seances').value = seancesCalculees;
                }
                
                updateCalculations();
            });
        });
    }
    
    // Initialiser les écouteurs d'événements
    setupInputListeners();

    // Soumission du formulaire
    document.getElementById('configureForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const submitBtn = document.getElementById('saveBtn');
        
        // Désactiver le bouton
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Enregistrement...';
        
        fetch('{{ route("esbtp.planning-general.configure-rapide") }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(response => {
            if (response.success) {
                // Fermer le modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('configureModal'));
                modal.hide();
                
                // Afficher un message de succès
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-success alert-dismissible fade show position-fixed';
                alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
                alertDiv.innerHTML = `
                    <i class="fas fa-check-circle me-2"></i>${response.message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                document.body.appendChild(alertDiv);
                
                // Recharger la page après un délai
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            } else {
                alert('Erreur : ' + response.message);
            }
        })
        .catch(error => {
            debugError('Erreur:', error);
            alert('Erreur lors de la sauvegarde');
        })
        .finally(() => {
            // Réactiver le bouton
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-save me-1"></i>Enregistrer';
        });
    });

    // ===== DEBUG MODAL =====
    debugLog('Bootstrap version:', typeof bootstrap !== 'undefined' ? 'loaded' : 'NOT LOADED');
    
    // Test de clic sur les boutons
    document.addEventListener('click', function(e) {
        if (e.target.closest('button[data-bs-target="#configureModal"]')) {
            debugLog('Bouton modal cliqué!');
            debugLog('Button:', e.target.closest('button'));
            debugLog('Modal element:', document.getElementById('configureModal'));
            
            // Test manuel d'ouverture du modal
            setTimeout(() => {
                const modalEl = document.getElementById('configureModal');
                if (modalEl && typeof bootstrap !== 'undefined') {
                    const modalInstance = new bootstrap.Modal(modalEl);
                    modalInstance.show();
                    debugLog('Modal ouvert manuellement');
                } else {
                    debugError('Bootstrap ou modal element introuvable');
                }
            }, 100);
        }
    });
    
    // Vérifier l'état du modal
    const modalElement = document.getElementById('configureModal');
    if (modalElement) {
        debugLog('Modal element found:', modalElement);
        modalElement.addEventListener('shown.bs.modal', function () {
            debugLog('Modal shown event triggered');
        });
        modalElement.addEventListener('hidden.bs.modal', function () {
            debugLog('Modal hidden event triggered');
        });
        modalElement.addEventListener('show.bs.modal', function () {
            debugLog('Modal show event triggered');
        });
    } else {
        debugError('Modal element NOT FOUND');
    }

});
</script>
@endpush

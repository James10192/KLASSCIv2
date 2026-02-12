@extends('layouts.app')

@section('title', 'Charge Pédagogique par Classe - KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<script src="https://cdn.jsdelivr.net/npm/echarts@5/dist/echarts.min.js"></script>
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
        padding: var(--space-lg);
        margin-bottom: var(--space-lg);
        box-shadow: var(--shadow-card);
        position: relative;
    }

    .chart-container .chart-area {
        width: 100%;
        height: 400px;
    }
    
    /* =============================================
       MATIÈRE CARDS — Modern 2-column layout
       ============================================= */
    .matiere-card {
        background: #fff;
        border-radius: 14px;
        border: 1px solid #e2e8f0;
        padding: 20px 24px;
        margin-bottom: 10px;
        box-shadow: 0 1px 4px rgba(0,0,0,.05);
        display: grid;
        grid-template-columns: 1fr 260px;
        gap: 24px;
        align-items: start;
        transition: box-shadow .2s;
        position: relative;
        overflow: visible;
    }

    .matiere-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,.09);
    }

    .matiere-card-left {
        min-width: 0;
    }

    .matiere-card-right {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .matiere-nom {
        font-size: 1rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 4px;
    }

    .matiere-code-pill {
        display: inline-flex;
        font-size: 0.72rem;
        font-weight: 600;
        padding: 2px 8px;
        border-radius: 99px;
        background: #f1f5f9;
        color: #64748b;
        margin-bottom: 8px;
    }

    .matiere-stats {
        display: flex;
        gap: 16px;
        flex-wrap: wrap;
    }

    .stat-item {
        text-align: center;
        min-width: 80px;
    }
    
    .stat-value {
        font-size: 1.75rem;
        font-weight: 800;
        color: #1e293b;
        line-height: 1;
    }

    .stat-label {
        font-size: 0.72rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: #94a3b8;
    }
    
    /* Progress bars — modern KLASSCI */
    .progress-bar-matiere {
        height: 10px;
        background: #f1f5f9;
        border-radius: 99px;
        overflow: hidden;
        margin: 10px 0 6px;
    }

    .progress-fill-matiere {
        height: 100%;
        border-radius: 99px;
        transition: width .7s cubic-bezier(.4,0,.2,1);
        background: linear-gradient(90deg, #93c5fd, #0453cb);
    }

    .progress-bars-container {
        margin-top: 8px;
    }

    .progress-bar-volume {
        height: 10px;
        background: #f1f5f9;
        border-radius: 99px;
        overflow: hidden;
        margin-bottom: 6px;
        position: relative;
    }

    .progress-fill-volume {
        height: 100%;
        transition: width .7s cubic-bezier(.4,0,.2,1);
        border-radius: 99px;
    }

    /* Level-based colors matching classes/show.blade.php */
    .progress-fill-volume.level-low  { background: linear-gradient(90deg, #fca5a5, #ef4444); }
    .progress-fill-volume.level-mid  { background: linear-gradient(90deg, #fcd34d, #f59e0b); }
    .progress-fill-volume.level-good { background: linear-gradient(90deg, #6ee7b7, #10b981); }
    .progress-fill-volume.level-done { background: linear-gradient(90deg, #93c5fd, #0453cb); }
    .progress-fill-volume.overage    { background: linear-gradient(90deg, #ef4444, #f97316); }

    /* Keep backward compat aliases */
    .progress-fill-volume.realise  { background: linear-gradient(90deg, #6ee7b7, #10b981); }
    .progress-fill-volume.planifie { background: linear-gradient(90deg, #93c5fd, #0453cb); }
    .progress-fill-volume.restant  { background: linear-gradient(90deg, #fcd34d, #f59e0b); }

    .volume-legend {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 0.75rem;
        color: #64748b;
        margin-bottom: 6px;
    }

    /* Percent badge pill */
    .planning-percent-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 52px;
        padding: 3px 10px;
        border-radius: 99px;
        font-size: .82rem;
        font-weight: 700;
        white-space: nowrap;
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

    .filters-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: var(--space-md);
        margin-bottom: var(--space-md);
    }

    .filters-grid label {
        display: block;
        margin-bottom: var(--space-sm);
        font-weight: 600;
        font-size: var(--text-small);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--text-secondary);
    }

    .filters-actions {
        display: flex;
        gap: var(--space-md);
        align-items: center;
        flex-wrap: wrap;
    }

    .filters-count {
        margin-left: auto;
        font-size: var(--text-small);
        color: var(--text-muted);
    }

    .filter-loading {
        display: none;
        align-items: center;
        gap: var(--space-sm);
        font-size: var(--text-small);
        color: var(--text-secondary);
    }

    .filter-loading.active {
        display: flex;
    }
    
    /* =============================================
       KPI SUMMARY CARDS — Modern KLASSCI Design
       ============================================= */
    .summary-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        margin-bottom: 28px;
    }

    .summary-card {
        background: #fff;
        border-radius: 14px;
        padding: 18px 20px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 1px 4px rgba(0,0,0,.06);
        display: flex;
        flex-direction: column;
        gap: 4px;
        position: relative;
        overflow: hidden;
    }

    .summary-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 3px;
        background: var(--kpi-color, #0453cb);
        border-radius: 14px 14px 0 0;
    }

    .summary-card .icon {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        margin-bottom: 4px;
        background: color-mix(in srgb, var(--kpi-color, #0453cb) 12%, transparent);
        color: var(--kpi-color, #0453cb);
    }

    .summary-card.primary { --kpi-color: #0453cb; }
    .summary-card.success { --kpi-color: #10b981; }
    .summary-card.info    { --kpi-color: #5e91de; }
    .summary-card.warning { --kpi-color: #f59e0b; }
    
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

    .overage-badge {
        background: rgba(220, 38, 38, 0.1);
        color: #b91c1c;
        border: 1px solid rgba(220, 38, 38, 0.3);
        padding: 4px 10px;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .progress-fill-volume.overage {
        background: linear-gradient(90deg, #ef4444, #f97316);
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

    /* Teacher avatar chips — modern pill design */
    .teacher-chip {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 4px 12px 4px 4px;
        border-radius: 99px;
        border: 1px solid #e2e8f0;
        background: #fff;
        text-decoration: none;
        color: #1e293b;
        font-size: .8rem;
        font-weight: 500;
        transition: box-shadow .15s, transform .15s, border-color .15s;
    }

    .teacher-chip:hover {
        box-shadow: 0 2px 8px rgba(0,0,0,.1);
        transform: translateY(-1px);
        border-color: #cbd5e1;
        color: #1e293b;
    }

    .teacher-avatar {
        width: 26px;
        height: 26px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: .72rem;
        font-weight: 700;
        color: #fff;
        flex-shrink: 0;
        background: linear-gradient(135deg, #0453cb, #5e91de);
    }

    .teacher-name {
        font-weight: 600;
        font-size: 0.8rem;
    }

    .teacher-hours {
        font-size: 0.72rem;
        color: #64748b;
        font-weight: 400;
    }

    .teacher-empty {
        font-size: 0.85rem;
        color: #94a3b8;
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

        .chart-container .chart-area {
            height: 350px;
        }

        .matiere-card {
            grid-template-columns: 1fr;
        }

        .matiere-card-right {
            flex-direction: row;
            flex-wrap: wrap;
            gap: 12px;
        }

        .matiere-stats {
            flex-wrap: wrap;
            justify-content: flex-start;
        }

        .summary-stats {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    
    @media (max-width: 576px) {
        .chart-container .chart-area {
            height: 280px;
        }

        .chart-container h5 {
            font-size: 1rem;
        }
    }
</style>
@endsection

@php
    // Helper : convertir heures décimales en format HHhMM (ex: 10.5 → "10H30")
    $formatHM = function($decimal) {
        if ($decimal == 0) return '0H00';
        $hours = floor(abs($decimal));
        $minutes = round((abs($decimal) - $hours) * 60);
        if ($minutes >= 60) { $hours++; $minutes = 0; }
        $sign = $decimal < 0 ? '-' : '';
        return $sign . $hours . 'H' . str_pad($minutes, 2, '0', STR_PAD_LEFT);
    };
@endphp

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

        <!-- Filtres de recherche -->
        <div class="filters-section">
            <div class="section-title mb-md">
                <i class="fas fa-filter me-2"></i>Filtres de recherche
            </div>
            <form method="GET" id="repartitionFiltersForm">
                <div class="filters-grid">
                    <!-- Recherche texte -->
                    <div>
                        <label for="search">Recherche</label>
                        <input type="text" name="search" id="search" value="{{ $search ?? '' }}" placeholder="Nom de classe..." class="form-control" style="width: 100%;">
                    </div>

                    <!-- Année Universitaire -->
                    <div>
                        <label for="annee_id">Année</label>
                        <select name="annee_id" id="annee_id" class="form-control" style="width: 100%;">
                            <option value="all" {{ request('annee_id') == 'all' ? 'selected' : '' }}>Toutes les années</option>
                            @foreach($annees as $annee)
                                <option value="{{ $annee->id }}" {{ request('annee_id') == $annee->id ? 'selected' : '' }}>
                                    {{ $annee->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Filière -->
                    <div>
                        <label for="filiere_id">Filière</label>
                        <select name="filiere_id" id="filiere_id" class="form-control" style="width: 100%;">
                            <option value="">Toutes les filières</option>
                            @foreach($filieres as $filiere)
                                <option value="{{ $filiere->id }}" {{ ($filiereId ?? '') == $filiere->id ? 'selected' : '' }}>
                                    {{ $filiere->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Niveau -->
                    <div>
                        <label for="niveau_id">Niveau</label>
                        <select name="niveau_id" id="niveau_id" class="form-control" style="width: 100%;">
                            <option value="">Tous les niveaux</option>
                            @foreach($niveaux as $niveau)
                                <option value="{{ $niveau->id }}" {{ ($niveauId ?? '') == $niveau->id ? 'selected' : '' }}>
                                    {{ $niveau->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Classe spécifique -->
                    <div>
                        <label for="classe_id">Classe</label>
                        <select name="classe_id" id="classe_id" class="form-control" style="width: 100%;">
                            <option value="">Toutes les classes</option>
                            @foreach($classes as $classe)
                                <option value="{{ $classe->id }}" {{ request('classe_id') == $classe->id ? 'selected' : '' }}>
                                    {{ $classe->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Période -->
                    <div>
                        <label>Période</label>
                        <div class="d-flex gap-2 mt-1">
                            <button type="button" name="periode" value="semestre1" class="btn btn-sm btn-outline-primary periode-btn {{ request('periode') == 'semestre1' ? 'active' : '' }}">S1</button>
                            <button type="button" name="periode" value="semestre2" class="btn btn-sm btn-outline-primary periode-btn {{ request('periode') == 'semestre2' ? 'active' : '' }}">S2</button>
                            <button type="button" name="periode" value="annee" class="btn btn-sm btn-outline-primary periode-btn {{ request('periode') == 'annee' || !request('periode') ? 'active' : '' }}">Année</button>
                        </div>
                        <input type="hidden" name="periode" id="periode_value" value="{{ request('periode', 'annee') }}">
                    </div>
                </div>

                <div class="filters-actions">
                    <button type="submit" class="btn-acasi primary" id="btn-filtrer">
                        <i class="fas fa-search me-1"></i>Filtrer
                    </button>
                    <button type="button" id="reset-filters-btn" class="btn-acasi secondary">
                        <i class="fas fa-times me-1"></i>Réinitialiser
                    </button>
                    <div class="filter-loading" id="filter-loading">
                        <i class="fas fa-spinner fa-spin"></i>
                        <span>Chargement...</span>
                    </div>
                    <div class="filters-count">
                        <i class="fas fa-layer-group me-1"></i><span id="classes-count">{{ $repartition->count() }}</span> classe(s)
                    </div>
                </div>
            </form>
        </div>

        <div id="repartition-content">
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
                <div class="stat-value">{{ $formatHM($statsRepartition['heures_planifiees'] ?? 0) }}</div>
                <div class="stat-label">Heures planifiées</div>
            </div>
            <div class="summary-card warning">
                <div class="icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                @php
                    $tauxRealisation = $statsRepartition['taux_realisation'] ?? 0;
                    $heuresPlanifiees = $statsRepartition['heures_planifiees'] ?? 0;
                    $heuresRealisees = $statsRepartition['heures_realisees'] ?? 0;
                    $depassement = max(0, $heuresRealisees - $heuresPlanifiees);
                @endphp
                <div class="stat-value">{{ $tauxRealisation }}%</div>
                <div class="stat-label">Taux de réalisation</div>
                @if($tauxRealisation > 100)
                    <div class="mt-2">
                        <span class="overage-badge">Dépassement +{{ $formatHM($depassement) }}</span>
                    </div>
                @endif
            </div>
        </div>

        <div class="row">
            <!-- Graphique en secteurs -->
            <div class="col-md-6">
                <div class="chart-container">
                    <h5 class="mb-3"><i class="fas fa-chart-pie me-2"></i>Répartition des heures réalisées</h5>
                    <div id="pieChart" class="chart-area"></div>
                </div>
            </div>

            <!-- Graphique en barres (scrollable) -->
            <div class="col-md-6">
                <div class="chart-container">
                    <h5 class="mb-3"><i class="fas fa-chart-bar me-2"></i>Planifié vs Réalisé par classe</h5>
                    <div id="barChart" class="chart-area"></div>
                </div>
            </div>
        </div>
        <script type="application/json" id="repartition-chart-data">@json($chartData)</script>

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
                                @php
                                    $heuresPlanifieesClasse = $classeData['stats']['heures_planifiees_total'];
                                    $heuresRealiseesClasse = $classeData['stats']['heures_realisees_total'];
                                    $heuresRestantesClasse = max(0, $heuresPlanifieesClasse - $heuresRealiseesClasse);
                                    $depassementClasse = max(0, $heuresRealiseesClasse - $heuresPlanifieesClasse);
                                @endphp
                                <div class="class-kpis">
                                    <div class="class-kpi">
                                        <span class="value">{{ $formatHM($heuresPlanifieesClasse) }}</span>
                                        <span class="label">Planifiées</span>
                                    </div>
                                    <div class="class-kpi">
                                        <span class="value">{{ $formatHM($heuresRealiseesClasse) }}</span>
                                        <span class="label">Réalisées</span>
                                    </div>
                                    @if($heuresRestantesClasse > 0)
                                    <div class="class-kpi">
                                        <span class="value text-warning">{{ $formatHM($heuresRestantesClasse) }}</span>
                                        <span class="label">Restantes</span>
                                    </div>
                                    @endif
                                    <div class="class-kpi">
                                        <span class="value">{{ $classeData['stats']['taux_realisation'] }}%</span>
                                        <span class="label">Réalisation</span>
                                    </div>
                                    @if($classeData['stats']['taux_realisation'] > 100)
                                        <div class="class-kpi">
                                            <span class="overage-badge">+{{ $formatHM($depassementClasse) }}</span>
                                            <span class="label">Dépassement</span>
                                        </div>
                                    @endif
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
                                            @php
                                                $pct = $item['pourcentage_realise'] ?? 0;
                                                $levelClass = $pct >= 100 ? 'level-done' : ($pct >= 75 ? 'level-good' : ($pct >= 40 ? 'level-mid' : 'level-low'));
                                                $badgeBg = $pct >= 100 ? '#dbeafe' : ($pct >= 75 ? '#d1fae5' : ($pct >= 40 ? '#fef3c7' : '#fee2e2'));
                                                $badgeTxt = $pct >= 100 ? '#1d4ed8' : ($pct >= 75 ? '#065f46' : ($pct >= 40 ? '#92400e' : '#991b1b'));
                                            @endphp
                                            <div class="matiere-card"
                                                 data-matiere-id="{{ $item['matiere']->id ?? '' }}"
                                                 data-filiere-id="{{ $classe->filiere_id ?? '' }}"
                                                 data-niveau-id="{{ $classe->niveau_etude_id ?? '' }}"
                                                 data-matiere-name="{{ $item['matiere']->name ?? '' }}"
                                                 data-filiere-name="{{ $filiereName }}"
                                                 data-niveau-name="{{ $niveauName }}"
                                                 data-classe-id="{{ $classe->id }}"
                                                 data-classe-name="{{ $classe->name }}">

                                                <!-- Colonne gauche : nom + infos -->
                                                <div class="matiere-card-left">
                                                    <div class="d-flex align-items-start gap-2 mb-2">
                                                        <div>
                                                            <div class="matiere-nom">{{ $item['matiere']->name ?? 'Matière inconnue' }}</div>
                                                            <span class="matiere-code-pill">{{ $item['matiere']->code ?? 'N/A' }}</span>
                                                        </div>
                                                    </div>

                                                    @if($item['est_configure'])
                                                        <div class="volume-legend mt-1">
                                                            <span class="text-muted" style="font-size:.8rem;">Réalisé vs Planifié ({{ ucfirst($item['periode']) }})</span>
                                                        </div>
                                                        <div class="progress-bar-volume">
                                                            <div class="progress-fill-volume {{ $item['pourcentage_realise'] > 100 ? 'overage' : $levelClass }}" style="width: {{ min($pct, 100) }}%"></div>
                                                        </div>
                                                        <div class="d-flex justify-content-between">
                                                            <small class="text-success">✓ {{ $formatHM($item['heures_realisees']) }} réalisées</small>
                                                            @if($item['heures_restantes'] > 0)
                                                                <small class="text-warning">⏱ {{ $formatHM($item['heures_restantes']) }} restantes</small>
                                                            @else
                                                                <small class="text-success">✅ Objectif atteint</small>
                                                            @endif
                                                        </div>
                                                    @else
                                                        <div class="non-configure-card mt-2">
                                                            <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
                                                            <div class="fw-bold mb-1">Planning non configuré</div>
                                                            @if($item['matiere'])
                                                                <div class="text-muted mb-2" style="font-size:.8rem;">
                                                                    Aucune planification horaire définie pour {{ $filiereName }} - {{ $niveauName }}.
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
                                                                    <i class="fas fa-cog me-1"></i>Configurer
                                                                </button>
                                                            @endif
                                                        </div>
                                                    @endif

                                                    <div class="teacher-list mt-3">
                                                        @if($item['enseignants']->isNotEmpty())
                                                            <div class="teacher-label mb-2" style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#94a3b8;">Enseignants</div>
                                                            <div class="teacher-chips d-flex flex-wrap gap-2">
                                                                @foreach($item['enseignants'] as $enseignant)
                                                                    <a href="{{ route('esbtp.enseignants.show', ['enseignant' => $enseignant['id']]) }}" class="teacher-chip">
                                                                        <span class="teacher-avatar">{{ substr($enseignant['name'], 0, 2) }}</span>
                                                                        <span class="teacher-name">{{ $enseignant['name'] }}</span>
                                                                        <span class="teacher-hours">{{ $formatHM($enseignant['heures_realisees']) }}</span>
                                                                    </a>
                                                                @endforeach
                                                            </div>
                                                        @else
                                                            <div class="teacher-empty">Aucun enseignant trouvé.</div>
                                                        @endif
                                                    </div>
                                                </div>

                                                <!-- Colonne droite : KPIs -->
                                                <div class="matiere-card-right">
                                                    @if($item['est_configure'])
                                                        <div class="d-flex align-items-center justify-content-end mb-1">
                                                            <span class="planning-percent-badge" style="background:{{ $badgeBg }};color:{{ $badgeTxt }};">{{ $pct }}%</span>
                                                        </div>
                                                    @endif
                                                    <div class="matiere-stats justify-content-end">
                                                        <div class="stat-item">
                                                            <div class="stat-value">{{ $formatHM($item['heures_realisees']) }}</div>
                                                            <div class="stat-label">Réalisées</div>
                                                        </div>
                                                        @if($item['est_configure'])
                                                            <div class="stat-item">
                                                                <div class="stat-value">{{ $formatHM($item['heures_planifiees']) }}</div>
                                                                <div class="stat-label">Planifiées</div>
                                                            </div>
                                                            <div class="stat-item">
                                                                <div class="stat-value" style="color:{{ $item['heures_restantes'] > 0 ? '#f59e0b' : '#10b981' }}">{{ $formatHM($item['heures_restantes']) }}</div>
                                                                <div class="stat-label">Restantes</div>
                                                            </div>
                                                        @else
                                                            <div class="stat-item">
                                                                <div class="stat-value">{{ $item['nb_seances'] }}</div>
                                                                <div class="stat-label">Séances</div>
                                                            </div>
                                                        @endif
                                                    </div>
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
                    <input type="hidden" id="filiere_id_modal" name="filiere_id">
                    <input type="hidden" id="niveau_id_modal" name="niveau_id">
                    
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
$(document).ready(function() {

    // ==========================================
    // Helper : format décimal → HHhMM
    // ==========================================
    function formatHM(decimal) {
        if (!decimal || decimal === 0) return '0H00';
        const h = Math.floor(Math.abs(decimal));
        let m = Math.round((Math.abs(decimal) - h) * 60);
        if (m >= 60) { return (h + 1) + 'H00'; }
        const sign = decimal < 0 ? '-' : '';
        return sign + h + 'H' + String(m).padStart(2, '0');
    }

    // ==========================================
    // ECharts : Initialisation et rendu
    // ==========================================
    const COLORS = [
        '#3B82F6', '#EF4444', '#10B981', '#F59E0B', '#8B5CF6',
        '#EC4899', '#14B8A6', '#F97316', '#6366F1', '#84CC16',
        '#0EA5E9', '#F43F5E', '#22C55E', '#A855F7', '#FB923C',
        '#06B6D4', '#E11D48', '#4ADE80', '#7C3AED', '#FBBF24'
    ];
    const MAX_PIE_ITEMS = 15;

    window.repartitionCharts = window.repartitionCharts || { pie: null, bar: null };

    function readChartData() {
        const raw = document.getElementById('repartition-chart-data');
        if (!raw) return { labels: [], planifiees: [], realisees: [] };
        try { return JSON.parse(raw.textContent || '{}'); }
        catch (e) { return { labels: [], planifiees: [], realisees: [] }; }
    }

    function renderCharts() {
        const chartData = readChartData();
        const labels = chartData.labels || [];
        const planifiees = chartData.planifiees || [];
        const realisees = chartData.realisees || [];

        // Dispose anciennes instances
        if (window.repartitionCharts.pie) { window.repartitionCharts.pie.dispose(); }
        if (window.repartitionCharts.bar) { window.repartitionCharts.bar.dispose(); }

        if (labels.length === 0) return;

        // ---- PIE CHART (Doughnut) ----
        const pieEl = document.getElementById('pieChart');
        if (pieEl) {
            const pieInstance = echarts.init(pieEl);
            window.repartitionCharts.pie = pieInstance;

            // Grouper les petites classes dans "Autres" si > MAX_PIE_ITEMS
            let pieData = labels.map((label, i) => ({ name: label, value: parseFloat(realisees[i]) || 0 }));
            pieData.sort((a, b) => b.value - a.value);

            if (pieData.length > MAX_PIE_ITEMS) {
                const top = pieData.slice(0, MAX_PIE_ITEMS);
                const rest = pieData.slice(MAX_PIE_ITEMS);
                const restTotal = rest.reduce((sum, item) => sum + item.value, 0);
                top.push({ name: 'Autres (' + rest.length + ' classes)', value: restTotal });
                pieData = top;
            }

            pieInstance.setOption({
                tooltip: {
                    trigger: 'item',
                    formatter: function(params) {
                        return '<strong>' + params.name + '</strong><br/>' +
                               formatHM(params.value) + ' (' + params.percent + '%)';
                    }
                },
                legend: {
                    type: 'scroll',
                    orient: window.innerWidth < 768 ? 'horizontal' : 'vertical',
                    right: window.innerWidth < 768 ? 'center' : '3%',
                    top: window.innerWidth < 768 ? 'bottom' : '15%',
                    bottom: window.innerWidth < 768 ? '0%' : '15%',
                    textStyle: { fontSize: 11 },
                    pageIconSize: 12
                },
                color: COLORS,
                series: [{
                    name: 'Heures réalisées',
                    type: 'pie',
                    radius: ['35%', '65%'],
                    center: window.innerWidth < 768 ? ['50%', '40%'] : ['35%', '50%'],
                    avoidLabelOverlap: true,
                    label: { show: false },
                    emphasis: {
                        label: { show: true, fontWeight: 'bold', fontSize: 13 }
                    },
                    data: pieData
                }]
            });
        }

        // ---- BAR CHART (Scrollable avec dataZoom) ----
        const barEl = document.getElementById('barChart');
        if (barEl) {
            const barInstance = echarts.init(barEl);
            window.repartitionCharts.bar = barInstance;

            // Calculer l'affichage initial (max ~20 classes visibles)
            const visiblePercent = labels.length > 20 ? Math.round((20 / labels.length) * 100) : 100;

            barInstance.setOption({
                tooltip: {
                    trigger: 'axis',
                    axisPointer: { type: 'shadow' },
                    formatter: function(params) {
                        let html = '<strong>' + params[0].axisValue + '</strong>';
                        params.forEach(function(p) {
                            html += '<br/>' + p.marker + ' ' + p.seriesName + ': ' + formatHM(p.value);
                        });
                        return html;
                    }
                },
                legend: {
                    data: ['Heures planifiées', 'Heures réalisées'],
                    bottom: labels.length > 20 ? 35 : 0
                },
                grid: {
                    left: '3%',
                    right: '4%',
                    bottom: labels.length > 20 ? 80 : 40,
                    top: 15,
                    containLabel: true
                },
                dataZoom: labels.length > 20 ? [
                    {
                        type: 'slider',
                        show: true,
                        xAxisIndex: [0],
                        start: 0,
                        end: visiblePercent,
                        bottom: 5,
                        height: 25,
                        borderColor: '#ddd',
                        fillerColor: 'rgba(59, 130, 246, 0.1)',
                        handleStyle: { color: '#3B82F6' }
                    },
                    {
                        type: 'inside',
                        xAxisIndex: [0],
                        start: 0,
                        end: visiblePercent
                    }
                ] : [],
                xAxis: {
                    type: 'category',
                    data: labels,
                    axisLabel: {
                        rotate: labels.length > 10 ? 45 : 0,
                        fontSize: 10,
                        interval: 0
                    }
                },
                yAxis: {
                    type: 'value',
                    axisLabel: {
                        formatter: function(value) { return formatHM(value); },
                        fontSize: 10
                    }
                },
                series: [
                    {
                        name: 'Heures planifiées',
                        type: 'bar',
                        data: planifiees,
                        itemStyle: { color: 'rgba(59, 130, 246, 0.7)', borderColor: '#3B82F6', borderWidth: 1 },
                        barMaxWidth: 40
                    },
                    {
                        name: 'Heures réalisées',
                        type: 'bar',
                        data: realisees,
                        itemStyle: { color: 'rgba(16, 185, 129, 0.7)', borderColor: '#10B981', borderWidth: 1 },
                        barMaxWidth: 40
                    }
                ]
            });
        }

        // Resize auto
        window.addEventListener('resize', function() {
            if (window.repartitionCharts.pie) window.repartitionCharts.pie.resize();
            if (window.repartitionCharts.bar) window.repartitionCharts.bar.resize();
        });
    }

    // ==========================================
    // AJAX Filtering (sans refresh page)
    // ==========================================
    const filterForm = document.getElementById('repartitionFiltersForm');
    const loadingIndicator = document.getElementById('filter-loading');
    const classesCountEl = document.getElementById('classes-count');
    let searchTimeout = null;

    function getFilterParams() {
        const formData = new FormData(filterForm);
        // S'assurer que la période est incluse
        const periodeHidden = document.getElementById('periode_value');
        if (periodeHidden) {
            formData.set('periode', periodeHidden.value);
        }
        return new URLSearchParams(formData);
    }

    function fetchRepartitionContent(params) {
        const url = window.location.pathname + '?' + params.toString();
        if (loadingIndicator) loadingIndicator.classList.add('active');

        return fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(response) { return response.text(); })
            .then(function(html) {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const nextContent = doc.querySelector('#repartition-content');
                const currentContent = document.querySelector('#repartition-content');
                if (nextContent && currentContent) {
                    currentContent.innerHTML = nextContent.innerHTML;
                    renderCharts();
                    // Mettre à jour le compteur de classes
                    const newCount = doc.querySelector('#classes-count');
                    if (newCount && classesCountEl) {
                        classesCountEl.textContent = newCount.textContent;
                    }
                }
                window.history.replaceState({}, '', url);
            })
            .catch(function() {
                window.location.href = url;
            })
            .finally(function() {
                if (loadingIndicator) loadingIndicator.classList.remove('active');
            });
    }

    function triggerAjaxFilter() {
        fetchRepartitionContent(getFilterParams());
    }

    if (filterForm) {
        // Soumission du formulaire (bouton Filtrer)
        filterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            triggerAjaxFilter();
        });

        // Changement de select → filtre AJAX immédiat
        filterForm.addEventListener('change', function(e) {
            if (e.target.matches('#annee_id, #classe_id, #filiere_id, #niveau_id')) {
                triggerAjaxFilter();
            }
        });

        // Recherche texte avec debounce (300ms)
        var searchInput = document.getElementById('search');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function() {
                    triggerAjaxFilter();
                }, 300);
            });
        }

        // Boutons période
        filterForm.addEventListener('click', function(e) {
            var btn = e.target.closest('.periode-btn');
            if (!btn) return;
            e.preventDefault();
            filterForm.querySelectorAll('.periode-btn').forEach(function(b) { b.classList.remove('active'); });
            btn.classList.add('active');
            document.getElementById('periode_value').value = btn.value;
            triggerAjaxFilter();
        });

        // Bouton réinitialiser
        document.getElementById('reset-filters-btn').addEventListener('click', function() {
            document.getElementById('search').value = '';
            document.getElementById('annee_id').value = 'all';
            document.getElementById('filiere_id').value = '';
            document.getElementById('niveau_id').value = '';
            document.getElementById('classe_id').value = '';
            document.getElementById('periode_value').value = 'annee';
            filterForm.querySelectorAll('.periode-btn').forEach(function(b) { b.classList.remove('active'); });
            filterForm.querySelector('.periode-btn[value="annee"]').classList.add('active');
            triggerAjaxFilter();
        });
    }

    // ==========================================
    // Initialisation
    // ==========================================
    renderCharts();

    // ==========================================
    // Modal de configuration rapide
    // ==========================================
    var configureModalEl = document.getElementById('configureModal');
    if (configureModalEl) {
        configureModalEl.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            if (!button) return;
            var matiereId = button.getAttribute('data-matiere-id');
            var matiereName = button.getAttribute('data-matiere-name');
            var filiereIdModal = button.getAttribute('data-filiere-id');
            var niveauIdModal = button.getAttribute('data-niveau-id');
            var filiereName = button.getAttribute('data-filiere-name');
            var niveauName = button.getAttribute('data-niveau-name');
            var classeIdModal = button.getAttribute('data-classe-id');
            var classeName = button.getAttribute('data-classe-name');

            document.getElementById('configureModalLabel').innerHTML = '<i class="fas fa-cog me-2"></i>Configuration du planning - ' + matiereName;

            document.getElementById('matiere_id').value = matiereId;
            document.getElementById('filiere_id_modal').value = filiereIdModal || '';
            document.getElementById('niveau_id_modal').value = niveauIdModal || '';
            document.getElementById('classe_id_modal').value = classeIdModal || '';

            var contextElement = document.getElementById('modal-context');
            if (contextElement) {
                var contextHTML = 'Année {{ $anneeSelectionnee?->name }}';
                if (classeName) {
                    contextHTML += ' - <strong>' + classeName + '</strong> <small class="text-muted">(' + filiereName + ' - ' + niveauName + ')</small>';
                } else if (filiereIdModal && niveauIdModal) {
                    contextHTML += ' - <strong>' + filiereName + ' - ' + niveauName + '</strong>';
                } else {
                    contextHTML += ' - <em>Toutes les filières/niveaux</em>';
                }
                contextElement.innerHTML = contextHTML;
            }

            document.getElementById('configureForm').reset();
            document.getElementById('matiere_id').value = matiereId;
            document.getElementById('filiere_id_modal').value = filiereIdModal || '';
            document.getElementById('niveau_id_modal').value = niveauIdModal || '';
            document.getElementById('calcul-automatique').style.display = 'none';
        });

        // Test manuel d'ouverture du modal
        document.addEventListener('click', function(e) {
            if (e.target.closest('button[data-bs-target="#configureModal"]')) {
                setTimeout(function() {
                    var modalEl = document.getElementById('configureModal');
                    if (modalEl && typeof bootstrap !== 'undefined') {
                        var modalInstance = new bootstrap.Modal(modalEl);
                        modalInstance.show();
                    }
                }, 100);
            }
        });
    }

    // Calculs automatiques en temps réel
    function updateCalculations() {
        var volumeHoraire = parseFloat(document.getElementById('volume_horaire').value) || 0;
        var nbSeances = parseInt(document.getElementById('nb_seances').value) || 0;
        var dureeSeance = parseFloat(document.getElementById('duree_seance').value) || 0;

        if (volumeHoraire > 0 && nbSeances > 0 && dureeSeance > 0) {
            var totalCalcule = nbSeances * dureeSeance;
            var difference = Math.abs(totalCalcule - volumeHoraire);
            var message = '';
            var alertClass = 'alert-secondary';

            if (Math.abs(totalCalcule - volumeHoraire) < 0.1) {
                message = 'Parfait ! ' + nbSeances + ' séances de ' + formatHM(dureeSeance) + ' = ' + formatHM(totalCalcule);
                alertClass = 'alert-success';
            } else if (totalCalcule > volumeHoraire) {
                message = 'Attention : ' + nbSeances + ' séances de ' + formatHM(dureeSeance) + ' = ' + formatHM(totalCalcule) + ' (' + formatHM(difference) + ' de plus que prévu)';
                alertClass = 'alert-warning';
            } else {
                message = 'Attention : ' + nbSeances + ' séances de ' + formatHM(dureeSeance) + ' = ' + formatHM(totalCalcule) + ' (' + formatHM(difference) + ' de moins que prévu)';
                alertClass = 'alert-warning';
            }

            var calculElement = document.getElementById('calcul-automatique');
            calculElement.className = 'alert ' + alertClass;
            document.getElementById('calcul-text').textContent = message;
            calculElement.style.display = 'block';
        } else {
            document.getElementById('calcul-automatique').style.display = 'none';
        }
    }

    function setupInputListeners() {
        ['volume_horaire', 'nb_seances', 'duree_seance'].forEach(function(inputId) {
            var el = document.getElementById(inputId);
            if (!el) return;
            el.addEventListener('input', function() {
                updateCalculations();
                var volumeHoraire = parseFloat(document.getElementById('volume_horaire').value) || 0;
                var nbSeances = parseInt(document.getElementById('nb_seances').value) || 0;
                var dureeSeance = parseFloat(document.getElementById('duree_seance').value) || 0;

                if (volumeHoraire > 0 && nbSeances > 0 && dureeSeance === 0) {
                    document.getElementById('duree_seance').value = Math.round((volumeHoraire / nbSeances) * 2) / 2;
                }
                if (volumeHoraire > 0 && dureeSeance > 0 && nbSeances === 0) {
                    document.getElementById('nb_seances').value = Math.round(volumeHoraire / dureeSeance);
                }
                updateCalculations();
            });
        });
    }
    setupInputListeners();

    // Soumission du formulaire de configuration
    var configForm = document.getElementById('configureForm');
    if (configForm) {
        configForm.addEventListener('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(this);
            var submitBtn = document.getElementById('saveBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Enregistrement...';

            fetch('{{ route("esbtp.planning-general.configure-rapide") }}', {
                method: 'POST',
                body: formData,
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') }
            })
            .then(function(response) { return response.json(); })
            .then(function(response) {
                if (response.success) {
                    var modal = bootstrap.Modal.getInstance(document.getElementById('configureModal'));
                    modal.hide();
                    var alertDiv = document.createElement('div');
                    alertDiv.className = 'alert alert-success alert-dismissible fade show position-fixed';
                    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
                    alertDiv.innerHTML = '<i class="fas fa-check-circle me-2"></i>' + response.message + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                    document.body.appendChild(alertDiv);
                    setTimeout(function() { triggerAjaxFilter(); }, 1500);
                } else {
                    alert('Erreur : ' + response.message);
                }
            })
            .catch(function() { alert('Erreur lors de la sauvegarde'); })
            .finally(function() {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-save me-1"></i>Enregistrer';
            });
        });
    }
});
</script>
@endpush

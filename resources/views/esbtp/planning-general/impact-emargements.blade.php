@extends('layouts.app')

@section('title', 'Impact des Émargements - Planification Académique')

@push('styles')
<style>
    .stats-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 15px;
        padding: 1.5rem;
        color: white;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        transition: transform 0.3s ease;
    }
    
    .stats-card:hover {
        transform: translateY(-5px);
    }
    
    .stats-card .stats-icon {
        font-size: 2.5rem;
        opacity: 0.8;
    }
    
    .sync-status {
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 500;
    }
    
    .sync-status.synchronise {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    
    .sync-status.leger_ecart {
        background: #fff3cd;
        color: #856404;
        border: 1px solid #ffeaa7;
    }
    
    .sync-status.emargement_superieur {
        background: #cce5ff;
        color: #004085;
        border: 1px solid #b3d7ff;
    }
    
    .sync-status.planification_superieure {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    
    .progress-bar-custom {
        height: 8px;
        border-radius: 4px;
        background: #e9ecef;
        overflow: hidden;
        margin: 0.5rem 0;
    }
    
    .progress-fill {
        height: 100%;
        transition: width 0.6s ease;
        border-radius: 4px;
    }
    
    .progress-fill.base {
        background: linear-gradient(90deg, #28a745, #20c997);
    }
    
    .progress-fill.emargement {
        background: linear-gradient(90deg, #007bff, #6f42c1);
    }
    
    .filter-section {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 1.5rem;
        margin-bottom: 2rem;
        border: 1px solid #dee2e6;
    }
    
    .impact-card {
        background: white;
        border-radius: 10px;
        padding: 1.5rem;
        margin-bottom: 1rem;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        border-left: 4px solid #007bff;
        transition: all 0.3s ease;
    }
    
    .impact-card:hover {
        transform: translateX(5px);
        box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    }
    
    .impact-card.high-activity {
        border-left-color: #28a745;
    }
    
    .impact-card.medium-activity {
        border-left-color: #ffc107;
    }
    
    .impact-card.low-activity {
        border-left-color: #dc3545;
    }
    
    .metric-badge {
        display: inline-block;
        padding: 0.4rem 0.8rem;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 500;
        margin-right: 0.5rem;
        margin-bottom: 0.5rem;
    }
    
    .metric-badge.success {
        background: #d4edda;
        color: #155724;
    }
    
    .metric-badge.warning {
        background: #fff3cd;
        color: #856404;
    }
    
    .metric-badge.info {
        background: #d1ecf1;
        color: #0c5460;
    }
    
    .teaching-stats {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 1rem;
        margin-top: 1rem;
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="h3 mb-1">
                        <i class="fas fa-chart-line text-primary me-2"></i>
                        Impact des Émargements
                    </h2>
                    <p class="text-muted mb-0">
                        Analyse de l'impact des émargements sur la progression des planifications académiques
                        @if($anneeSelectionnee)
                            - {{ $anneeSelectionnee->nom }}
                        @endif
                    </p>
                </div>
                <div>
                    <a href="{{ route('esbtp.planning-general.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>
                        Retour au Planning
                    </a>
                </div>
            </div>

            <!-- Filtres -->
            <div class="filter-section">
                <form method="GET" action="{{ route('esbtp.planning-general.impact-emargements') }}" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Année Universitaire</label>
                        <select name="annee_id" class="form-select">
                            <option value="">Toutes les années</option>
                            @foreach($annees as $annee)
                                <option value="{{ $annee->id }}" {{ $anneeId == $annee->id ? 'selected' : '' }}>
                                    {{ $annee->nom }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Filière</label>
                        <select name="filiere_id" class="form-select">
                            <option value="">Toutes les filières</option>
                            @foreach($filieres as $filiere)
                                <option value="{{ $filiere->id }}" {{ $filiereId == $filiere->id ? 'selected' : '' }}>
                                    {{ $filiere->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Niveau</label>
                        <select name="niveau_id" class="form-select">
                            <option value="">Tous les niveaux</option>
                            @foreach($niveaux as $niveau)
                                <option value="{{ $niveau->id }}" {{ $niveauId == $niveau->id ? 'selected' : '' }}>
                                    {{ $niveau->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Période</label>
                        <div class="input-group">
                            <input type="date" name="periode_debut" class="form-control" value="{{ $periodeDebut }}">
                            <span class="input-group-text">à</span>
                            <input type="date" name="periode_fin" class="form-control" value="{{ $periodeFin }}">
                        </div>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter me-1"></i>
                            Appliquer les filtres
                        </button>
                        <a href="{{ route('esbtp.planning-general.impact-emargements') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-1"></i>
                            Réinitialiser
                        </a>
                    </div>
                </form>
            </div>

            <!-- Statistiques générales -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="d-flex align-items-center">
                            <div class="stats-icon me-3">
                                <i class="fas fa-clipboard-check"></i>
                            </div>
                            <div>
                                <h3 class="mb-0">{{ $statistiquesEmargement['total_emargements'] }}</h3>
                                <p class="mb-0 opacity-75">Émargements Total</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="d-flex align-items-center">
                            <div class="stats-icon me-3">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div>
                                <h3 class="mb-0">{{ $statistiquesEmargement['emargements_valides'] }}</h3>
                                <p class="mb-0 opacity-75">Émargements Validés</p>
                                <small class="opacity-75">{{ $statistiquesEmargement['taux_validation'] }}% de validation</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="d-flex align-items-center">
                            <div class="stats-icon me-3">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div>
                                <h3 class="mb-0">{{ round($statistiquesEmargement['heures_totales_emargees'], 1) }}h</h3>
                                <p class="mb-0 opacity-75">Heures Émargées</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="d-flex align-items-center">
                            <div class="stats-icon me-3">
                                <i class="fas fa-hourglass-half"></i>
                            </div>
                            <div>
                                <h3 class="mb-0">{{ $statistiquesEmargement['emargements_pending'] }}</h3>
                                <p class="mb-0 opacity-75">En Attente</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Impact détaillé par planification -->
            <div class="row">
                <div class="col-lg-8">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-chart-bar text-primary me-2"></i>
                                Impact par Planification
                            </h5>
                        </div>
                        <div class="card-body">
                            @if($impactData->count() > 0)
                                @foreach($impactData as $impact)
                                    <div class="impact-card {{ $impact['nb_emargements_valides'] > 10 ? 'high-activity' : ($impact['nb_emargements_valides'] > 5 ? 'medium-activity' : 'low-activity') }}">
                                        <div class="row align-items-center">
                                            <div class="col-md-4">
                                                <h6 class="mb-1">
                                                    {{ $impact['planification']->matiere->name ?? 'Matière inconnue' }}
                                                </h6>
                                                <small class="text-muted">
                                                    {{ $impact['planification']->filiere->name ?? 'N/A' }} - 
                                                    {{ $impact['planification']->niveauEtude->name ?? 'N/A' }}
                                                </small>
                                                @if($impact['planification']->enseignantPrincipal)
                                                    <br><small class="text-info">
                                                        <i class="fas fa-user me-1"></i>
                                                        {{ $impact['planification']->enseignantPrincipal->name }}
                                                    </small>
                                                @endif
                                            </div>
                                            <div class="col-md-4">
                                                <div class="mb-2">
                                                    <span class="metric-badge success">
                                                        {{ $impact['nb_emargements_valides'] }} émargements
                                                    </span>
                                                    <span class="sync-status {{ $impact['statut_synchronisation']['statut'] }}">
                                                        {{ $impact['statut_synchronisation']['message'] }}
                                                    </span>
                                                </div>
                                                
                                                <div class="progress-bar-custom">
                                                    <div class="progress-fill base" style="width: {{ $impact['taux_progression_base'] }}%"></div>
                                                </div>
                                                <small class="text-muted">Progression base: {{ $impact['taux_progression_base'] }}%</small>
                                                
                                                <div class="progress-bar-custom">
                                                    <div class="progress-fill emargement" style="width: {{ $impact['taux_progression_emargement'] }}%"></div>
                                                </div>
                                                <small class="text-muted">Progression émargement: {{ $impact['taux_progression_emargement'] }}%</small>
                                            </div>
                                            <div class="col-md-4 text-end">
                                                <div class="metric-badge info">
                                                    <strong>{{ $impact['heures_planifiees'] }}h</strong> planifiées
                                                </div>
                                                <br>
                                                <div class="metric-badge {{ $impact['ecart_heures'] == 0 ? 'success' : 'warning' }}">
                                                    {{ $impact['heures_emargement'] }}h émargées
                                                    @if($impact['ecart_heures'] != 0)
                                                        <span class="badge bg-secondary">
                                                            {{ $impact['ecart_heures'] > 0 ? '+' : '' }}{{ $impact['ecart_heures'] }}h
                                                        </span>
                                                    @endif
                                                </div>
                                                
                                                @if($impact['derniere_maj_heures'])
                                                    <br><small class="text-muted">
                                                        MAJ: {{ \Carbon\Carbon::parse($impact['derniere_maj_heures'])->format('d/m/Y H:i') }}
                                                    </small>
                                                @endif
                                            </div>
                                        </div>
                                        
                                        @if($impact['emargements_recents']->count() > 0)
                                            <div class="teaching-stats">
                                                <strong>Derniers émargements:</strong>
                                                @foreach($impact['emargements_recents'] as $emargement)
                                                    <span class="badge bg-light text-dark me-1">
                                                        {{ \Carbon\Carbon::parse($emargement->date)->format('d/m') }}
                                                        @if($emargement->seance)
                                                            ({{ \Carbon\Carbon::parse($emargement->seance->heure_debut)->format('H:i') }}-{{ \Carbon\Carbon::parse($emargement->seance->heure_fin)->format('H:i') }})
                                                        @endif
                                                    </span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            @else
                                <div class="text-center py-4">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">Aucune donnée d'impact trouvée</h5>
                                    <p class="text-muted">Aucune planification avec émargements pour les critères sélectionnés.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <!-- Progression par matière -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-book text-success me-2"></i>
                                Top Matières (Émargements)
                            </h6>
                        </div>
                        <div class="card-body">
                            @if($progressionMatieres->count() > 0)
                                @foreach($progressionMatieres->take(5) as $progression)
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div>
                                            <strong>{{ $progression['matiere']->name }}</strong>
                                            <br><small class="text-muted">{{ $progression['heures_emargement'] }}h / {{ $progression['heures_planifiees'] }}h</small>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge bg-primary">{{ $progression['taux_progression_emargement'] }}%</span>
                                        </div>
                                    </div>
                                    <div class="progress-bar-custom">
                                        <div class="progress-fill emargement" style="width: {{ min(100, $progression['taux_progression_emargement']) }}%"></div>
                                    </div>
                                @endforeach
                            @else
                                <p class="text-muted text-center">Aucune donnée disponible</p>
                            @endif
                        </div>
                    </div>

                    <!-- Top enseignants émargement -->
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-user-check text-info me-2"></i>
                                Top Enseignants (Taux Émargement)
                            </h6>
                        </div>
                        <div class="card-body">
                            @if($enseignantsEmargement->count() > 0)
                                @foreach($enseignantsEmargement->take(5) as $enseignant)
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div>
                                            <strong>{{ $enseignant['enseignant']->name }}</strong>
                                            <br><small class="text-muted">{{ $enseignant['emargements_valides'] }}/{{ $enseignant['seances_totales'] }} séances</small>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge bg-{{ $enseignant['taux_emargement'] >= 80 ? 'success' : ($enseignant['taux_emargement'] >= 60 ? 'warning' : 'danger') }}">
                                                {{ $enseignant['taux_emargement'] }}%
                                            </span>
                                        </div>
                                    </div>
                                    @if($enseignant['dernier_emargement'])
                                        <small class="text-muted">
                                            Dernier: {{ \Carbon\Carbon::parse($enseignant['dernier_emargement']->validated_at)->format('d/m/Y H:i') }}
                                        </small>
                                    @endif
                                @endforeach
                            @else
                                <p class="text-muted text-center">Aucun enseignant trouvé</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-refresh des données toutes les 5 minutes
    setTimeout(function() {
        location.reload();
    }, 300000);
    
    // Tooltip pour les éléments avec titre
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>
@endpush
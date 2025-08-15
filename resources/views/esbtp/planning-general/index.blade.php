@extends('layouts.app')

@section('title', 'Planning Général - ESBTP-yAKRO')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
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
    
    .stats-planning {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: var(--space-lg);
        margin-bottom: var(--space-xl);
    }
    
    .stat-planning {
        text-align: center;
        position: relative;
        overflow: hidden;
    }
    
    .stat-planning::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        border-radius: var(--radius-medium) var(--radius-medium) 0 0;
    }
    
    .stat-planning.primary::before { background: linear-gradient(90deg, var(--primary), #60a5fa); }
    .stat-planning.success::before { background: linear-gradient(90deg, var(--success), #34d399); }
    .stat-planning.warning::before { background: linear-gradient(90deg, var(--warning), #fbbf24); }
    .stat-planning.info::before { background: linear-gradient(90deg, var(--info), #38bdf8); }
    
    .stat-icon-planning {
        width: 50px;
        height: 50px;
        border-radius: var(--radius-circle);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto var(--space-sm);
        font-size: 20px;
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(255, 255, 255, 0.7));
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.3);
    }
    
    .stat-planning.primary .stat-icon-planning { color: var(--primary); }
    .stat-planning.success .stat-icon-planning { color: var(--success); }
    .stat-planning.warning .stat-icon-planning { color: var(--warning); }
    .stat-planning.info .stat-icon-planning { color: var(--info); }
    
    .quick-actions {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: var(--space-lg);
        margin-top: var(--space-xl);
    }
    
    .action-card {
        position: relative;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        cursor: pointer;
        overflow: hidden;
    }
    
    .action-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
    }
    
    .action-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(135deg, transparent 0%, rgba(99, 102, 241, 0.02) 100%);
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    
    .action-card:hover::before {
        opacity: 1;
    }
    
    .action-icon {
        width: 40px;
        height: 40px;
        border-radius: var(--radius-medium);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 18px;
        margin-bottom: var(--space-md);
    }
    
    .action-icon.calendar { background: linear-gradient(135deg, #8b5cf6, #a78bfa); }
    .action-icon.chart { background: linear-gradient(135deg, #06b6d4, #67e8f9); }
    .action-icon.users { background: linear-gradient(135deg, #f59e0b, #fbbf24); }
    .action-icon.settings { background: linear-gradient(135deg, #ef4444, #f87171); }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header et navigation du planning -->
        <x-planning-header 
            title="Planning Général" 
            subtitle="Vue d'ensemble du planning académique et organisation des cours"
            active-tab="overview"
            :annee-selectionnee="$anneeSelectionnee"
            :annees="$annees"
        />

        @if(!$anneeSelectionnee)
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                Aucune année universitaire sélectionnée. Veuillez en choisir une pour afficher le planning.
            </div>
        @else
            <!-- Statistiques du planning -->
            <div class="stats-planning">
                <div class="card-moderne stat-planning primary">
                    <div class="p-lg">
                        <div class="stat-icon-planning">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-value">{{ number_format($stats['total_seances']) }}</div>
                        <div class="stat-label">Séances programmées</div>
                    </div>
                </div>
                
                <div class="card-moderne stat-planning success">
                    <div class="p-lg">
                        <div class="stat-icon-planning">
                            <i class="fas fa-hourglass-half"></i>
                        </div>
                        <div class="stat-value">{{ number_format($stats['total_heures'], 0) }}h</div>
                        <div class="stat-label">Heures de cours</div>
                    </div>
                </div>
                
                <div class="card-moderne stat-planning warning">
                    <div class="p-lg">
                        <div class="stat-icon-planning">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-value">{{ $stats['total_classes'] }}</div>
                        <div class="stat-label">Classes actives</div>
                    </div>
                </div>
                
                <div class="card-moderne stat-planning info">
                    <div class="p-lg">
                        <div class="stat-icon-planning">
                            <i class="fas fa-book"></i>
                        </div>
                        <div class="stat-value">{{ $stats['total_matieres'] }}</div>
                        <div class="stat-label">Matières enseignées</div>
                    </div>
                </div>
            </div>

            <!-- Informations de l'année sélectionnée -->
            <div class="card-moderne mb-lg">
                <div class="p-lg">
                    <div class="section-title mb-md">
                        <i class="fas fa-info-circle me-2"></i>
                        Informations Année {{ $anneeSelectionnee->name }}
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-item">
                                <strong>Période :</strong>
                                {{ \Carbon\Carbon::parse($anneeSelectionnee->start_date)->format('d/m/Y') }} - 
                                {{ \Carbon\Carbon::parse($anneeSelectionnee->end_date)->format('d/m/Y') }}
                            </div>
                            <div class="info-item mt-2">
                                <strong>Statut :</strong>
                                @if(optional($anneeSelectionnee)->is_current)
                                    <span class="badge bg-success">Année en cours</span>
                                @else
                                    <span class="badge bg-secondary">Année archivée</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <strong>Total enseignants :</strong>
                                {{ $stats['total_enseignants'] }} enseignants actifs
                            </div>
                            <div class="info-item mt-2">
                                <strong>Charge globale :</strong>
                                {{ number_format($stats['total_heures'], 0) }} heures planifiées
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Interface de Planification Académique -->
            <div class="card-moderne mb-lg">
                <div class="card-header">
                    <h5><i class="fas fa-calendar-plus me-2"></i>Planification Académique</h5>
                    <p class="text-muted mb-0">Créer et gérer les planifications par filière/niveau/semestre</p>
                </div>
                <div class="card-body">
                    <!-- Formulaire de sélection -->
                    <form method="GET" class="mb-4">
                        <div class="row">
                            <div class="col-md-3">
                                <label for="annee_id" class="form-label">Année Universitaire</label>
                                <select name="annee_id" id="annee_id" class="form-select" onchange="this.form.submit()">
                                    <option value="">Sélectionner une année</option>
                                    @foreach($annees as $annee)
                                        <option value="{{ $annee->id }}" {{ request('annee_id') == $annee->id ? 'selected' : '' }}>
                                            {{ $annee->name }}
                                            @if(optional($annee)->is_current) (En cours) @endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div class="col-md-3">
                                <label for="filiere_id" class="form-label">Filière</label>
                                <select name="filiere_id" id="filiere_id" class="form-select" onchange="this.form.submit()">
                                    <option value="">Sélectionner une filière</option>
                                    @foreach($filieres as $filiere)
                                        <option value="{{ $filiere->id }}" {{ request('filiere_id') == $filiere->id ? 'selected' : '' }}>
                                            {{ $filiere->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div class="col-md-3">
                                <label for="niveau_id" class="form-label">Niveau</label>
                                <select name="niveau_id" id="niveau_id" class="form-select" onchange="this.form.submit()">
                                    <option value="">Sélectionner un niveau</option>
                                    @foreach($niveaux as $niveau)
                                        <option value="{{ $niveau->id }}" {{ request('niveau_id') == $niveau->id ? 'selected' : '' }}>
                                            {{ $niveau->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div class="col-md-3">
                                <label for="semestre" class="form-label">Semestre</label>
                                <select name="semestre" id="semestre" class="form-select" onchange="this.form.submit()">
                                    <option value="1" {{ request('semestre', 1) == 1 ? 'selected' : '' }}>Semestre 1</option>
                                    <option value="2" {{ request('semestre', 1) == 2 ? 'selected' : '' }}>Semestre 2</option>
                                </select>
                            </div>
                        </div>
                    </form>

                    @if(request('annee_id') && request('filiere_id') && request('niveau_id'))
                        <!-- Affichage de la sélection -->
                        <div class="alert alert-info">
                            <h6>📋 Planification pour :</h6>
                            <ul class="mb-0">
                                <li><strong>Année :</strong> {{ $anneeSelectionnee->name }}</li>
                                <li><strong>Filière :</strong> {{ $filiereSelectionnee->name }}</li>
                                <li><strong>Niveau :</strong> {{ $niveauSelectionne->name }}</li>
                                <li><strong>Semestre :</strong> {{ request('semestre', 1) }}</li>
                            </ul>
                        </div>

                        <!-- Statistiques -->
                        @if($statistiques)
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card bg-primary text-white">
                                    <div class="card-body text-center">
                                        <h4>{{ $statistiques['total_matieres_planifiees'] }}</h4>
                                        <p class="mb-0">Matières Planifiées</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-success text-white">
                                    <div class="card-body text-center">
                                        <h4>{{ $statistiques['total_heures_planifiees'] }}h</h4>
                                        <p class="mb-0">Heures Planifiées</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-info text-white">
                                    <div class="card-body text-center">
                                        <h4>{{ $statistiques['total_enseignants_assignes'] }}</h4>
                                        <p class="mb-0">Enseignants Assignés</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-warning text-white">
                                    <div class="card-body text-center">
                                        <h4>{{ $statistiques['taux_completion'] }}%</h4>
                                        <p class="mb-0">Taux de Completion</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Formulaire d'ajout de planification -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h6>➕ Ajouter une Planification</h6>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="{{ route('esbtp.planning-general.store-planification') }}">
                                    @csrf
                                    <input type="hidden" name="annee_universitaire_id" value="{{ request('annee_id') }}">
                                    <input type="hidden" name="filiere_id" value="{{ request('filiere_id') }}">
                                    <input type="hidden" name="niveau_etude_id" value="{{ request('niveau_id') }}">
                                    <input type="hidden" name="semestre" value="{{ request('semestre', 1) }}">
                                    
                                    <div class="row">
                                        <div class="col-md-4">
                                            <label for="matiere_id" class="form-label">Matière *</label>
                                            <select name="matiere_id" id="matiere_id" class="form-select" required>
                                                <option value="">Choisir une matière</option>
                                                @foreach($matieres as $matiere)
                                                    <option value="{{ $matiere->id }}">{{ $matiere->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        
                                        <div class="col-md-2">
                                            <label for="volume_horaire_total" class="form-label">Vol. Total (h) *</label>
                                            <input type="number" name="volume_horaire_total" id="volume_horaire_total" 
                                                   class="form-control" min="1" max="200" required>
                                        </div>
                                        
                                        <div class="col-md-2">
                                            <label for="volume_horaire_cm" class="form-label">CM (h)</label>
                                            <input type="number" name="volume_horaire_cm" id="volume_horaire_cm" 
                                                   class="form-control" min="0">
                                        </div>
                                        
                                        <div class="col-md-2">
                                            <label for="volume_horaire_td" class="form-label">TD (h)</label>
                                            <input type="number" name="volume_horaire_td" id="volume_horaire_td" 
                                                   class="form-control" min="0">
                                        </div>
                                        
                                        <div class="col-md-2">
                                            <label for="volume_horaire_tp" class="form-label">TP (h)</label>
                                            <input type="number" name="volume_horaire_tp" id="volume_horaire_tp" 
                                                   class="form-control" min="0">
                                        </div>
                                    </div>
                                    
                                    <div class="row mt-3">
                                        <div class="col-md-4">
                                            <label for="enseignant_principal_id" class="form-label">Enseignant Principal</label>
                                            <select name="enseignant_principal_id" id="enseignant_principal_id" class="form-select">
                                                <option value="">Assigner plus tard</option>
                                                @foreach($enseignants as $enseignant)
                                                    <option value="{{ $enseignant->id }}">{{ $enseignant->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        
                                        <div class="col-md-2">
                                            <label for="coefficient" class="form-label">Coefficient</label>
                                            <input type="number" name="coefficient" id="coefficient" 
                                                   class="form-control" min="0.5" max="10" step="0.5" value="1">
                                        </div>
                                        
                                        <div class="col-md-2">
                                            <label for="credits_ects" class="form-label">Crédits ECTS</label>
                                            <input type="number" name="credits_ects" id="credits_ects" 
                                                   class="form-control" min="1" max="30">
                                        </div>
                                        
                                        <div class="col-md-4 d-flex align-items-end">
                                            <button type="submit" class="btn-acasi primary">
                                                <i class="fas fa-plus"></i> Ajouter la Planification
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Liste des planifications existantes -->
                        <div class="card">
                            <div class="card-header">
                                <h6>📚 Planifications Existantes ({{ $planifications->count() }})</h6>
                            </div>
                            <div class="card-body">
                                @if($planifications->count() > 0)
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Matière</th>
                                                    <th>Vol. Total</th>
                                                    <th>CM/TD/TP</th>
                                                    <th>Enseignant</th>
                                                    <th>Coeff.</th>
                                                    <th>ECTS</th>
                                                    <th>Statut</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($planifications as $planification)
                                                <tr>
                                                    <td><strong>{{ $planification->matiere->name ?? 'N/A' }}</strong></td>
                                                    <td>{{ $planification->volume_horaire_total }}h</td>
                                                    <td>
                                                        <small>
                                                            CM: {{ $planification->volume_horaire_cm }}h<br>
                                                            TD: {{ $planification->volume_horaire_td }}h<br>
                                                            TP: {{ $planification->volume_horaire_tp }}h
                                                        </small>
                                                    </td>
                                                    <td>{{ $planification->enseignantPrincipal->name ?? 'Non assigné' }}</td>
                                                    <td>{{ $planification->coefficient }}</td>
                                                    <td>{{ $planification->credits_ects }}</td>
                                                    <td>
                                                        <span class="badge bg-{{ $planification->statut == 'valide' ? 'success' : 'warning' }}">
                                                            {{ ucfirst($planification->statut) }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        @if($planification->isModifiable())
                                                            <form method="POST" action="{{ route('esbtp.planning-general.destroy-planification', $planification->id) }}" 
                                                                  style="display: inline;" onsubmit="return confirm('Supprimer cette planification ?')">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </form>
                                                        @endif
                                                        
                                                        @if($planification->statut == 'planifie')
                                                            <form method="POST" action="{{ route('esbtp.planning-general.valider-planification', $planification->id) }}" 
                                                                  style="display: inline;">
                                                                @csrf
                                                                <button type="submit" class="btn btn-sm btn-outline-success">
                                                                    <i class="fas fa-check"></i> Valider
                                                                </button>
                                                            </form>
                                                        @endif
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="text-center text-muted py-4">
                                        <i class="fas fa-clipboard-list fa-3x mb-3"></i>
                                        <p>Aucune planification pour cette sélection.</p>
                                        <p>Utilisez le formulaire ci-dessus pour commencer la planification.</p>
                                    </div>
                                @endif
                            </div>
                        </div>

                    @else
                        <!-- Message d'invite -->
                        <div class="text-center py-5">
                            <i class="fas fa-arrow-up fa-3x text-muted mb-3"></i>
                            <h5>Sélectionnez d'abord une Année, Filière et Niveau</h5>
                            <p class="text-muted">pour commencer la planification académique</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Actions rapides -->
            <div class="card-moderne">
                <div class="p-lg">
                    <div class="section-title mb-lg">
                        <i class="fas fa-bolt me-2"></i>
                        Actions Rapides
                    </div>
                    
                    <div class="quick-actions">
                        <a href="{{ route('esbtp.planning-general.annuel', ['annee_id' => $anneeSelectionnee->id]) }}" class="action-card card-moderne text-decoration-none">
                            <div class="p-lg">
                                <div class="action-icon calendar">
                                    <i class="fas fa-calendar-alt"></i>
                                </div>
                                <h6 class="font-semibold">Planning Annuel</h6>
                                <p class="text-muted mb-0">Visualisez le calendrier complet de l'année académique</p>
                            </div>
                        </a>
                        
                        <a href="{{ route('esbtp.planning-general.repartition-matieres', ['annee_id' => $anneeSelectionnee->id]) }}" class="action-card card-moderne text-decoration-none">
                            <div class="p-lg">
                                <div class="action-icon chart">
                                    <i class="fas fa-chart-pie"></i>
                                </div>
                                <h6 class="font-semibold">Répartition Matières</h6>
                                <p class="text-muted mb-0">Analysez la distribution des heures par matière</p>
                            </div>
                        </a>
                        
                        <a href="{{ route('esbtp.emploi-temps.index') }}" class="action-card card-moderne text-decoration-none">
                            <div class="p-lg">
                                <div class="action-icon users">
                                    <i class="fas fa-table"></i>
                                </div>
                                <h6 class="font-semibold">Emplois du Temps</h6>
                                <p class="text-muted mb-0">Gérez les emplois du temps par classe</p>
                            </div>
                        </a>

                        <a href="{{ route('esbtp.planning-general.impact-emargements', ['annee_id' => $anneeSelectionnee->id]) }}" class="action-card card-moderne text-decoration-none">
                            <div class="p-lg">
                                <div class="action-icon settings" style="background: linear-gradient(135deg, #10b981, #34d399);">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                                <h6 class="font-semibold">Impact Émargements</h6>
                                <p class="text-muted mb-0">Visualisez l'impact des émargements sur la progression</p>
                            </div>
                        </a>
                        
                        @canany(['manage-planning', 'view-all-timetables'])
                        <a href="{{ route('esbtp.planning-general.coordinateur', ['annee_id' => $anneeSelectionnee->id]) }}" class="action-card card-moderne text-decoration-none">
                            <div class="p-lg">
                                <div class="action-icon settings">
                                    <i class="fas fa-cogs"></i>
                                </div>
                                <h6 class="font-semibold">Gestion Avancée</h6>
                                <p class="text-muted mb-0">Outils de coordination et d'administration</p>
                            </div>
                        </a>
                        @endcanany
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function() {
    // Animation des cartes au scroll
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach((entry, index) => {
            if (entry.isIntersecting) {
                setTimeout(() => {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }, index * 100);
            }
        });
    }, observerOptions);
    
    // Observer toutes les cartes
    $('.card-moderne').each(function() {
        $(this).css({
            'opacity': '0',
            'transform': 'translateY(20px)',
            'transition': 'all 0.6s ease-out'
        });
        observer.observe(this);
    });
});
</script>
@endpush
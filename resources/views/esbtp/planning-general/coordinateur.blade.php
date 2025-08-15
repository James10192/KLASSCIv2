@extends('layouts.app')

@section('title', 'Dashboard Pédagogie - ESBTP-yAKRO')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    
    .dashboard-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: var(--space-lg);
        margin-bottom: var(--space-xl);
    }
    
    .stat-card {
        background: var(--surface);
        border-radius: var(--radius-medium);
        padding: var(--space-lg);
        border: 1px solid var(--border);
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .stat-card:hover {
        box-shadow: var(--shadow-hover);
        transform: translateY(-2px);
    }
    
    .stat-card .stat-icon {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        margin-bottom: var(--space-md);
    }
    
    .stat-card .stat-number {
        font-size: 2rem;
        font-weight: bold;
        color: var(--primary);
    }
    
    .stat-card .stat-label {
        color: var(--text-secondary);
        font-size: 0.9rem;
    }
    
    .slider-container {
        position: relative;
        overflow: hidden;
        border-radius: var(--radius-medium);
        background: var(--surface);
        box-shadow: var(--shadow-card);
    }
    
    .slider-content {
        display: flex;
        transition: transform 0.3s ease;
    }
    
    .slider-panel {
        min-width: 100%;
        padding: var(--space-lg);
    }
    
    .slider-controls {
        display: flex;
        justify-content: center;
        gap: var(--space-sm);
        margin-bottom: var(--space-md);
    }
    
    .slider-btn {
        padding: var(--space-sm) var(--space-md);
        border: none;
        background: rgba(var(--primary-rgb), 0.1);
        color: var(--primary);
        border-radius: var(--radius-small);
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .slider-btn.active {
        background: var(--primary);
        color: white;
    }
    
    .quick-actions {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: var(--space-md);
        margin-bottom: var(--space-xl);
    }
    
    .quick-action {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-medium);
        padding: var(--space-md);
        text-align: center;
        text-decoration: none;
        color: var(--text-primary);
        transition: all 0.3s ease;
    }
    
    .quick-action:hover {
        background: var(--primary);
        color: white;
        transform: translateY(-2px);
        box-shadow: var(--shadow-hover);
    }
    
    .quick-action .action-icon {
        font-size: 2rem;
        margin-bottom: var(--space-sm);
    }
    
    .content-panel {
        background: var(--surface);
        border-radius: var(--radius-medium);
        padding: var(--space-lg);
        margin-bottom: var(--space-lg);
        box-shadow: var(--shadow-card);
    }
    
    .allocation-card {
        background: var(--surface);
        border-radius: var(--radius-medium);
        padding: var(--space-lg);
        margin-bottom: var(--space-md);
        border: 1px solid var(--border);
        transition: all 0.3s ease;
    }
    
    .allocation-card:hover {
        box-shadow: var(--shadow-hover);
        transform: translateY(-2px);
    }
    
    .programmation-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: var(--space-lg);
        margin-bottom: var(--space-xl);
    }
    
    .jour-programmation {
        background: var(--surface);
        border-radius: var(--radius-medium);
        padding: var(--space-md);
        border: 1px solid var(--border);
    }
    
    .jour-programmation h6 {
        color: var(--primary);
        margin-bottom: var(--space-md);
        font-weight: 600;
    }
    
    .seance-item {
        background: var(--background);
        border-radius: var(--radius-small);
        padding: var(--space-sm);
        margin-bottom: var(--space-xs);
        border-left: 4px solid var(--primary);
    }
    
    .seance-item:last-child {
        margin-bottom: 0;
    }
    
    .code-emargement {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-medium);
        padding: var(--space-md);
        margin-bottom: var(--space-sm);
        position: relative;
    }
    
    .code-emargement.expire {
        background: #fff5f5;
        border-color: #fed7d7;
    }
    
    .code-emargement.active {
        background: #f0fff4;
        border-color: #9ae6b4;
    }
    
    .code-badge {
        font-family: 'Courier New', monospace;
        font-size: 1.2rem;
        font-weight: bold;
        color: var(--primary);
        background: var(--background);
        padding: var(--space-xs) var(--space-sm);
        border-radius: var(--radius-small);
        border: 1px solid var(--border);
    }
    
    .progress-bar-custom {
        height: 8px;
        background: var(--border);
        border-radius: var(--radius-full);
        overflow: hidden;
        margin-top: var(--space-xs);
    }
    
    .progress-fill {
        height: 100%;
        background: linear-gradient(90deg, var(--success), #48bb78);
        transition: width 0.3s ease;
    }
    
    .presence-card {
        background: var(--surface);
        border-radius: var(--radius-medium);
        padding: var(--space-md);
        margin-bottom: var(--space-sm);
        border: 1px solid var(--border);
    }
    
    .presence-card .taux {
        font-size: 1.5rem;
        font-weight: bold;
        color: var(--primary);
    }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header et navigation du planning -->
        <x-planning-header 
            title="Interface Coordinateur" 
            subtitle="Gestion avancée du planning et supervision académique"
            active-tab="coordinateur"
            :annee-selectionnee="$anneeSelectionnee"
            :annees="$annees"
        />

        <!-- Sélection du mois -->
        <div class="card-moderne mb-lg">
            <div class="p-md">
                <form method="GET" class="row align-items-center">
                    <div class="col-md-3">
                        <label for="annee_id" class="form-label">Année Universitaire</label>
                        <select name="annee_id" id="annee_id" class="form-select" onchange="this.form.submit()">
                            @foreach($annees as $annee)
                                <option value="{{ $annee->id }}" {{ request('annee_id') == $annee->id ? 'selected' : '' }}>
                                    {{ $annee->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="mois" class="form-label">Mois</label>
                        <select name="mois" id="mois" class="form-select" onchange="this.form.submit()">
                            @for($i = 1; $i <= 12; $i++)
                                <option value="{{ $i }}" {{ $mois == $i ? 'selected' : '' }}>
                                    {{ \Carbon\Carbon::create(null, $i)->translatedFormat('F') }}
                                </option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-md-6 text-end">
                        <span class="text-muted">
                            Période : {{ $anneeSelectionnee ? $anneeSelectionnee->name : 'Aucune année sélectionnée' }}
                        </span>
                    </div>
                </form>
            </div>
        </div>

        <!-- Actions rapides -->
        <div class="card-moderne mb-lg">
            <div class="p-md">
                <h5 class="mb-md"><i class="fas fa-bolt me-2"></i>Actions Rapides</h5>
                <div class="row">
                    <div class="col-md-3">
                        <a href="{{ route('esbtp.admin.attendance.index') }}" class="btn-acasi primary w-100 mb-sm">
                            <i class="fas fa-qrcode me-2"></i>
                            <div>
                                <strong>Codes d'Émargement</strong>
                                <small class="d-block">Générer et gérer les codes</small>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="{{ route('esbtp.admin.attendance.report') }}" class="btn-acasi secondary w-100 mb-sm">
                            <i class="fas fa-chalkboard-teacher me-2"></i>
                            <div>
                                <strong>Suivi Enseignants</strong>
                                <small class="d-block">Voir les émargements</small>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="{{ route('esbtp.attendances.index') }}" class="btn-acasi info w-100 mb-sm">
                            <i class="fas fa-clipboard-list me-2"></i>
                            <div>
                                <strong>Appels Étudiants</strong>
                                <small class="d-block">Consulter les présences</small>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Allocation Horaire par Module -->
            <div class="col-md-4">
                <div class="card-moderne">
                    <div class="card-header">
                        <h5><i class="fas fa-clock me-2"></i>Allocation Horaire par Module</h5>
                    </div>
                    <div class="card-body">
                        @foreach($allocationHoraire as $allocation)
                        <div class="allocation-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1">{{ $allocation['module'] }}</h6>
                                    <p class="text-muted small mb-2">{{ $allocation['description'] }}</p>
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-clock text-primary me-2"></i>
                                        <span class="fw-bold">{{ $allocation['heures'] }}h</span>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <button class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        @endforeach
                        
                        <div class="text-center mt-3">
                            <button class="btn-acasi primary btn-sm">
                                <i class="fas fa-plus me-1"></i>Ajouter Module
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Programmation Hebdomadaire -->
            <div class="col-md-8">
                <div class="card-moderne">
                    <div class="card-header">
                        <h5><i class="fas fa-calendar-week me-2"></i>Programmation Hebdomadaire</h5>
                        <p class="text-muted mb-0">Mois de {{ \Carbon\Carbon::create(null, $mois)->translatedFormat('F') }}</p>
                    </div>
                    <div class="card-body">
                        <div class="programmation-grid">
                            @foreach($programmationHebdomadaire as $jour => $seances)
                            <div class="jour-programmation">
                                <h6>{{ ucfirst($jour) }}</h6>
                                @if(count($seances) > 0)
                                    @foreach($seances as $seance)
                                    <div class="seance-item">
                                        <div class="d-flex justify-content-between">
                                            <span class="fw-bold">{{ $seance['matiere'] }}</span>
                                            <span class="text-muted">{{ $seance['horaire'] }}</span>
                                        </div>
                                        <small class="text-muted">{{ $seance['classe'] }}</small>
                                    </div>
                                    @endforeach
                                @else
                                    <p class="text-muted text-center py-3">
                                        <i class="fas fa-calendar-times"></i><br>
                                        Aucune séance programmée
                                    </p>
                                @endif
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <!-- Codes d'Émargement Actifs -->
            <div class="col-md-6">
                <div class="card-moderne">
                    <div class="card-header">
                        <h5><i class="fas fa-qrcode me-2"></i>Codes d'Émargement Actifs</h5>
                        <p class="text-muted mb-0">Supervision des présences enseignants</p>
                    </div>
                    <div class="card-body">
                        @foreach($codesEmargement as $code)
                        <div class="code-emargement {{ $code['expire'] ? 'expire' : 'active' }}">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="code-badge">{{ $code['code'] }}</div>
                                    <div class="mt-2">
                                        <small class="text-muted">{{ $code['cours'] }}</small>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-{{ $code['expire'] ? 'danger' : 'success' }}">
                                        {{ $code['expire_dans'] }}
                                    </span>
                                    <div class="mt-1">
                                        <button class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-sync"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                        
                        <div class="text-center mt-3">
                            <button class="btn-acasi success btn-sm">
                                <i class="fas fa-plus me-1"></i>Nouveau Code
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Taux de Présence par Classe -->
            <div class="col-md-6">
                <div class="card-moderne">
                    <div class="card-header">
                        <h5><i class="fas fa-chart-line me-2"></i>Taux de Présence par Classe</h5>
                        <p class="text-muted mb-0">Suivi de l'assiduité des étudiants</p>
                    </div>
                    <div class="card-body">
                        @foreach($tauxPresenceClasses as $classe)
                        <div class="presence-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">{{ $classe['nom'] }}</h6>
                                    <small class="text-muted">{{ $classe['effectif'] }} étudiants</small>
                                </div>
                                <div class="text-end">
                                    <div class="taux">{{ $classe['taux'] }}%</div>
                                </div>
                            </div>
                            <div class="progress-bar-custom">
                                <div class="progress-fill" style="width: {{ $classe['taux'] }}%"></div>
                            </div>
                        </div>
                        @endforeach
                        
                        <div class="text-center mt-3">
                            <button class="btn-acasi info btn-sm">
                                <i class="fas fa-chart-bar me-1"></i>Rapport Détaillé
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions Rapides -->
        <div class="card-moderne mt-4">
            <div class="card-header">
                <h5><i class="fas fa-bolt me-2"></i>Actions Rapides</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-2">
                        <a href="{{ route('esbtp.planning-general.annuel', ['annee_id' => request('annee_id')]) }}" class="btn-acasi primary w-100">
                            <i class="fas fa-calendar-alt me-2"></i>Planning Annuel
                        </a>
                    </div>
                    <div class="col-md-3 mb-2">
                        <a href="{{ route('esbtp.planning-general.repartition-matieres', ['annee_id' => request('annee_id')]) }}" class="btn-acasi info w-100">
                            <i class="fas fa-chart-pie me-2"></i>Répartition Matières
                        </a>
                    </div>
                    <div class="col-md-3 mb-2">
                        <a href="{{ route('esbtp.evenements-academiques.index') }}" class="btn-acasi success w-100">
                            <i class="fas fa-calendar-check me-2"></i>Événements
                        </a>
                    </div>
                    <div class="col-md-3 mb-2">
                        <a href="{{ route('esbtp.emploi-temps.index') }}" class="btn-acasi warning w-100">
                            <i class="fas fa-table me-2"></i>Emplois du Temps
                        </a>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-3 mb-2">
                        <a href="{{ route('esbtp.etudiants.index') }}" class="btn-acasi secondary w-100">
                            <i class="fas fa-user-graduate me-2"></i>Gestion Étudiants
                        </a>
                    </div>
                    <div class="col-md-3 mb-2">
                        <a href="{{ route('esbtp.personnel.unified.index') }}" class="btn-acasi secondary w-100">
                            <i class="fas fa-chalkboard-teacher me-2"></i>Gestion Personnel
                        </a>
                    </div>
                    <div class="col-md-3 mb-2">
                        <a href="{{ route('esbtp.attendances.index') }}" class="btn-acasi secondary w-100">
                            <i class="fas fa-user-check me-2"></i>Présences/Absences
                        </a>
                    </div>
                    <div class="col-md-3 mb-2">
                        <a href="{{ route('esbtp.annonces.index') }}" class="btn-acasi secondary w-100">
                            <i class="fas fa-bullhorn me-2"></i>Annonces
                        </a>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-4 mb-2">
                        <a href="{{ route('esbtp.evaluations.index') }}" class="btn-acasi secondary w-100">
                            <i class="fas fa-clipboard-list me-2"></i>Évaluations
                        </a>
                    </div>
                    <div class="col-md-4 mb-2">
                        <a href="{{ route('esbtp.notes.index') }}" class="btn-acasi secondary w-100">
                            <i class="fas fa-edit me-2"></i>Notes
                        </a>
                    </div>
                    <div class="col-md-4 mb-2">
                        <a href="{{ route('esbtp.bulletins.index') }}" class="btn-acasi secondary w-100">
                            <i class="fas fa-trophy me-2"></i>Bulletins/Résultats
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Fonction pour changer d'année
function changeAnnee(anneeId) {
    const url = new URL(window.location);
    url.searchParams.set('annee_id', anneeId);
    window.location.href = url.toString();
}

$(document).ready(function() {
    // Animation des cartes d'allocation
    $('.allocation-card').each(function(index) {
        $(this).css('transform', 'translateY(20px)');
        $(this).css('opacity', '0');
        
        setTimeout(() => {
            $(this).animate({
                'transform': 'translateY(0)',
                'opacity': '1'
            }, 300);
        }, index * 100);
    });
    
    // Animation des barres de progression
    $('.progress-fill').each(function() {
        const width = $(this).css('width');
        $(this).css('width', '0');
        
        setTimeout(() => {
            $(this).animate({
                'width': width
            }, 800);
        }, 500);
    });
});
</script>
@endpush
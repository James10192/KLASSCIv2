@extends('layouts.app')

@section('title', 'Gestion des Enseignants')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    .stats-enseignants {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: var(--space-lg);
        margin-bottom: var(--space-xl);
    }
    
    .stat-enseignant {
        text-align: center;
        position: relative;
        overflow: hidden;
    }
    
    .stat-enseignant::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        border-radius: var(--radius-medium) var(--radius-medium) 0 0;
    }
    
    .stat-enseignant.total::before { background: linear-gradient(90deg, var(--primary), #60a5fa); }
    .stat-enseignant.actifs::before { background: linear-gradient(90deg, var(--success), #34d399); }
    .stat-enseignant.valides::before { background: linear-gradient(90deg, var(--info), #38bdf8); }
    .stat-enseignant.charge::before { background: linear-gradient(90deg, var(--warning), #fbbf24); }
    
    .filter-section {
        background: var(--surface);
        border-radius: var(--radius-medium);
        padding: var(--space-lg);
        margin-bottom: var(--space-xl);
        box-shadow: var(--shadow-card);
    }
    
    .enseignant-card {
        transition: all 0.3s ease;
        border-left: 4px solid transparent;
    }
    
    .enseignant-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-card-hover);
        border-left-color: var(--primary);
    }
    
    .status-badge {
        font-size: 0.8rem;
        padding: 0.25rem 0.5rem;
        border-radius: var(--radius-small);
    }
    
    .charge-progress {
        height: 6px;
        background: var(--surface-secondary);
        border-radius: 3px;
        overflow: hidden;
    }
    
    .charge-progress-bar {
        height: 100%;
        transition: width 0.3s ease;
        border-radius: 3px;
    }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1>Gestion des Enseignants</h1>
                <p class="header-subtitle">Système avancé de gestion des profils enseignants</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('esbtp.enseignants.create') }}" class="btn-acasi primary">
                    <i class="fas fa-plus"></i>Nouveau Profil
                </a>
                <a href="{{ route('esbtp.enseignants.dashboard') }}" class="btn-acasi secondary">
                    <i class="fas fa-chart-bar"></i>Tableau de Bord
                </a>
            </div>
        </div>

        <!-- Message de transition -->
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Nouveau système enseignant :</strong> Cette interface utilise le nouveau système de profils enseignants amélioré.
            Les anciens enseignants peuvent être migrés vers le nouveau système.
        </div>

        <!-- Interface simplifiée pour le moment -->
        <div class="card-moderne">
            <div class="card-header">
                <h5><i class="fas fa-construction me-2"></i>Interface en Construction</h5>
            </div>
            <div class="card-body text-center py-5">
                <i class="fas fa-tools fa-3x text-muted mb-3"></i>
                <h4>Nouveau Système Enseignant</h4>
                <p class="text-muted mb-4">
                    Le nouveau système de gestion des enseignants avec profils avancés est en cours de déploiement.
                    Les fonctionnalités incluent :
                </p>
                
                <div class="row justify-content-center">
                    <div class="col-md-8">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="feature-item p-3 border rounded">
                                    <i class="fas fa-user-graduate text-primary mb-2"></i>
                                    <h6>Profils Détaillés</h6>
                                    <small class="text-muted">Diplômes, spécialisations, expérience</small>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="feature-item p-3 border rounded">
                                    <i class="fas fa-calendar-check text-success mb-2"></i>
                                    <h6>Disponibilités</h6>
                                    <small class="text-muted">Gestion des créneaux horaires</small>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="feature-item p-3 border rounded">
                                    <i class="fas fa-chart-line text-info mb-2"></i>
                                    <h6>Évaluations</h6>
                                    <small class="text-muted">Suivi des performances</small>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="feature-item p-3 border rounded">
                                    <i class="fas fa-clock text-warning mb-2"></i>
                                    <h6>Charge Horaire</h6>
                                    <small class="text-muted">Gestion automatique des heures</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <a href="{{ route('esbtp.planning-general.test') }}" class="btn-acasi primary me-2">
                        <i class="fas fa-calendar-alt"></i>Voir Planning Général
                    </a>
                    <a href="{{ route('esbtp.planning-general.index') }}" class="btn-acasi secondary">
                        <i class="fas fa-home"></i>Retour Planning
                    </a>
                </div>
            </div>
        </div>

        <!-- Ancienne interface (temporaire) -->
        @if(isset($enseignants) && $enseignants->count() > 0)
        <div class="card-moderne mt-4">
            <div class="card-header">
                <h5><i class="fas fa-list me-2"></i>Enseignants Existants (Ancien Système)</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Email</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($enseignants as $enseignant)
                            <tr>
                                <td>{{ $enseignant->name }}</td>
                                <td>{{ $enseignant->email }}</td>
                                <td>
                                    <span class="badge bg-{{ $enseignant->is_active ? 'success' : 'secondary' }}">
                                        {{ $enseignant->is_active ? 'Actif' : 'Inactif' }}
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" onclick="alert('Migration vers nouveau système bientôt disponible')">
                                        <i class="fas fa-arrow-right"></i> Migrer
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Animation simple
    $('.feature-item').hover(
        function() {
            $(this).addClass('shadow-sm');
        },
        function() {
            $(this).removeClass('shadow-sm');
        }
    );
});
</script>
@endpush
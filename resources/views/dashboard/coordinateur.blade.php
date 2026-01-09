@extends('layouts.app')

@section('title', 'Dashboard Coordinateur - KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    .coordinateur-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: var(--space-xl);
        border-radius: var(--radius-large);
        margin-bottom: var(--space-xl);
        position: relative;
        overflow: visible;
    }
    
    .coordinateur-header::before {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 100px;
        height: 100%;
        background: rgba(255,255,255,0.1);
        transform: skewX(-15deg);
        transform-origin: top;
    }

    /* Styles pour les dropdowns dans le header */
    .coordinateur-header .dropdown {
        position: relative;
        z-index: 1050;
    }

    .coordinateur-header .dropdown-menu {
        z-index: 1051 !important;
        position: absolute !important;
        background: white !important;
        border: 1px solid var(--border) !important;
        border-radius: var(--radius-medium) !important;
        box-shadow: 0 4px 20px rgba(0,0,0,0.15) !important;
        margin-top: 2px !important;
    }

    .coordinateur-header .dropdown-item {
        padding: var(--space-sm) var(--space-md) !important;
        color: var(--text-primary) !important;
        text-decoration: none !important;
        display: flex !important;
        align-items: center !important;
        transition: all 0.2s ease !important;
    }

    .coordinateur-header .dropdown-item:hover {
        background: rgba(var(--primary-rgb), 0.1) !important;
        color: var(--primary) !important;
    }

    .coordinateur-header .dropdown-item i {
        margin-right: var(--space-sm) !important;
    }
    
    .dashboard-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
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
        min-height: 140px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
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
        margin-bottom: var(--space-xs);
        line-height: 1.2;
    }

    .stat-card .stat-label {
        color: var(--text-secondary);
        font-size: 0.9rem;
        margin-top: var(--space-xs);
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
    
    .attendance-rate {
        background: var(--surface);
        border-radius: var(--radius-medium);
        padding: var(--space-md);
        margin-bottom: var(--space-sm);
        border: 1px solid var(--border);
    }
    
    .attendance-rate .rate-number {
        font-size: 1.5rem;
        font-weight: bold;
        color: var(--primary);
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
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header Coordinateur -->
        <div class="coordinateur-header">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <div style="display: flex; align-items: center; gap: var(--space-lg);">
                        <div style="width: 80px; height: 80px; border-radius: var(--radius-circle); background-color: var(--accent-blue); color: white; display: flex; align-items: center; justify-content: center; font-size: 2rem; font-weight: 700; box-shadow: var(--shadow-elevated);">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <div>
                            <h1 style="color: white; margin: 0; font-size: var(--title-main); font-weight: 700;">Dashboard Coordinateur</h1>
                            <p style="color: rgba(255,255,255,0.8); margin: var(--space-xs) 0 0 0;">Supervision académique et gestion pédagogique</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 text-end">
                    <div class="header-actions">
                        <span class="badge rounded-pill bg-light text-dark me-2">
                            <i class="fas fa-calendar me-1"></i>
                            {{ $anneeEnCours->name ?? 'Année non définie' }}
                        </span>
                        <button class="btn-acasi secondary" style="margin-right: var(--space-md);" onclick="location.reload()" title="Actualiser les données">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                        <div class="dropdown d-inline-block">
                            <button class="btn-acasi" style="background-color: var(--warning); color: white;" type="button" id="quickActionsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-bolt"></i> Actions rapides
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="{{ route('esbtp.emploi-temps.index') }}"><i class="fas fa-table" style="color: var(--primary);"></i> Emplois du temps</a></li>
                                <li><a class="dropdown-item" href="{{ route('esbtp.evaluations.index') }}"><i class="fas fa-clipboard-list" style="color: var(--success);"></i> Évaluations</a></li>
                                <li><a class="dropdown-item" href="{{ route('esbtp.annonces.create') }}"><i class="fas fa-bullhorn" style="color: var(--warning);"></i> Publier annonce</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="{{ route('esbtp.planning-general.coordinateur') }}"><i class="fas fa-calendar-alt" style="color: var(--accent-blue);"></i> Planning général</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistiques principales -->
        <div class="dashboard-cards">
            <div class="stat-card" onclick="window.location.href='{{ route('esbtp.etudiants.index') }}'">
                <div class="stat-icon" style="background-color: rgba(59, 130, 246, 0.1); color: var(--primary);">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div class="stat-number">{{ $totalStudents ?? 0 }}</div>
                <div class="stat-label">Étudiants inscrits</div>
            </div>

            <div class="stat-card" onclick="window.location.href='{{ route('esbtp.classes.index') }}'">
                <div class="stat-icon" style="background-color: rgba(34, 197, 94, 0.1); color: var(--success);">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-number">{{ $totalClasses ?? 0 }}</div>
                <div class="stat-label">Classes actives</div>
            </div>

            <div class="stat-card" onclick="window.location.href='{{ route('esbtp.teachers.index') }}'">
                <div class="stat-icon" style="background-color: rgba(245, 158, 11, 0.1); color: var(--warning);">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <div class="stat-number">{{ $totalTeachers ?? 0 }}</div>
                <div class="stat-label">Enseignants</div>
            </div>

            <div class="stat-card" onclick="window.location.href='{{ route('esbtp.evaluations.index') }}'">
                <div class="stat-icon" style="background-color: rgba(6, 182, 212, 0.1); color: var(--accent-blue);">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <div class="stat-number">{{ $totalExamens ?? 0 }}</div>
                <div class="stat-label">Évaluations</div>
            </div>

            <div class="stat-card" onclick="window.location.href='{{ route('esbtp.emploi-temps.index') }}'">
                <div class="stat-icon" style="background-color: rgba(4, 83, 203, 0.1); color: #0453cb;">
                    <i class="fas fa-table"></i>
                </div>
                <div class="stat-number">{{ $totalEmploiTemps ?? 0 }}</div>
                <div class="stat-label">Emplois du temps</div>
            </div>

            <div class="stat-card" onclick="window.location.href='{{ route('esbtp.attendances.index') }}'">
                <div class="stat-icon" style="background-color: rgba(239, 68, 68, 0.1); color: var(--danger);">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="stat-number">{{ $attendanceStats['attendance_rate'] ?? 0 }}%</div>
                <div class="stat-label">Taux de présence</div>
            </div>
        </div>

        <div class="row">
            <!-- Inscriptions récentes -->
            <div class="col-xl-6 mb-xl">
                <div class="card-moderne" style="min-height: 400px;">
                    <div class="card-header">
                        <h5><i class="fas fa-user-plus me-2" style="color: var(--primary);"></i>Inscriptions récentes</h5>
                        @if($pendingInscriptionsCount ?? 0 > 0)
                            <span class="badge bg-warning">{{ $pendingInscriptionsCount }} en attente</span>
                        @endif
                    </div>
                    <div class="card-body">
                        @if(isset($recentInscriptions) && $recentInscriptions->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Étudiant</th>
                                            <th>Filière</th>
                                            <th>Date</th>
                                            <th>Statut</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($recentInscriptions as $inscription)
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        @if($inscription->etudiant && $inscription->etudiant->photo_url)
                                                            <img src="{{ $inscription->etudiant->photo_url }}"
                                                                 alt="{{ $inscription->etudiant->nom }} {{ $inscription->etudiant->prenom }}"
                                                                 class="me-2"
                                                                 style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                                                        @else
                                                            <div class="avatar-sm me-2">
                                                                {{ $inscription->etudiant ? substr($inscription->etudiant->prenom ?? '', 0, 2) : 'NN' }}
                                                            </div>
                                                        @endif
                                                        <div>
                                                            @if($inscription->etudiant)
                                                                <a href="{{ route('esbtp.etudiants.show', $inscription->etudiant->id) }}"
                                                                   class="text-decoration-none">
                                                                    <div class="fw-medium text-primary" style="cursor: pointer;">
                                                                        {{ $inscription->etudiant->nom ?? 'N/A' }} {{ $inscription->etudiant->prenom ?? '' }}
                                                                    </div>
                                                                </a>
                                                            @else
                                                                <div class="fw-medium">N/A</div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>{{ $inscription->classe->filiere->name ?? $inscription->classe->filiere->nom ?? 'N/A' }}</td>
                                                <td>{{ $inscription->created_at->format('d/m/Y') }}</td>
                                                <td>
                                                    <span class="badge bg-{{ $inscription->status == 'pending' ? 'warning' : 'success' }}">
                                                        {{ ucfirst($inscription->status) }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-4">
                                <i class="fas fa-user-plus fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Aucune inscription récente</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Évaluations récentes -->
            <div class="col-xl-6 mb-xl">
                <div class="card-moderne" style="min-height: 400px;">
                    <div class="card-header">
                        <h5><i class="fas fa-clipboard-list me-2" style="color: var(--success);"></i>Évaluations récentes</h5>
                    </div>
                    <div class="card-body">
                        @if(isset($recentExamens) && $recentExamens->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Matière</th>
                                            <th>Classe</th>
                                            <th>Type</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($recentExamens as $examen)
                                            <tr>
                                                <td class="fw-medium">{{ $examen->matiere->nom ?? 'N/A' }}</td>
                                                <td>{{ $examen->classe->nom ?? 'N/A' }}</td>
                                                <td>
                                                    <span class="badge bg-info">{{ $examen->type ?? 'Examen' }}</span>
                                                </td>
                                                <td>{{ \Carbon\Carbon::parse($examen->date_evaluation)->format('d/m/Y') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-4">
                                <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Aucune évaluation récente</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Présences et Messages -->
        <div class="row">
            <!-- Statistiques de présence -->
            <div class="col-md-6 mb-lg">
                <div class="card-moderne" style="min-height: 300px;">
                    <div class="card-header">
                        <h5><i class="fas fa-chart-line me-2" style="color: var(--accent-blue);"></i>Taux de présence aujourd'hui</h5>
                    </div>
                    <div class="card-body">
                        <div class="attendance-rate">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="rate-number">{{ $attendanceStats['attendance_rate'] ?? 0 }}%</div>
                                    <small class="text-muted">Présence générale</small>
                                </div>
                                <div class="text-end">
                                    <div class="text-success">{{ $attendanceStats['total_present'] ?? 0 }} présents</div>
                                    <div class="text-danger">{{ $attendanceStats['total_absent'] ?? 0 }} absents</div>
                                </div>
                            </div>
                            <div class="progress-bar-custom">
                                <div class="progress-fill" style="width: {{ $attendanceStats['attendance_rate'] ?? 0 }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Messages récents -->
            <div class="col-md-6 mb-lg">
                <div class="card-moderne" style="min-height: 300px;">
                    <div class="card-header">
                        <h5><i class="fas fa-comments me-2" style="color: var(--warning);"></i>Messages récents</h5>
                    </div>
                    <div class="card-body">
                        @if(isset($recentMessages) && $recentMessages->count() > 0)
                            @foreach($recentMessages as $message)
                                <div class="d-flex mb-3 pb-3 border-bottom">
                                    <div class="avatar-sm me-3">
                                        {{ substr($message->sender_name ?? 'U', 0, 2) }}
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-medium">{{ $message->sender_name ?? 'Utilisateur' }}</div>
                                        <div class="text-muted small">{{ Str::limit($message->content ?? '', 50) }}</div>
                                        <div class="text-muted small">{{ $message->created_at->diffForHumans() }}</div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="text-center py-4">
                                <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Aucun message récent</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions rapides -->
        <div class="card-moderne">
            <div class="card-header">
                <h5><i class="fas fa-bolt me-2"></i>Actions rapides</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-2">
                        <a href="{{ route('esbtp.planning-general.coordinateur') }}" class="btn-acasi primary w-100">
                            <i class="fas fa-calendar-alt me-2"></i>Planning Général
                        </a>
                    </div>
                    <div class="col-md-3 mb-2">
                        <a href="{{ route('esbtp.emploi-temps.index') }}" class="btn-acasi info w-100">
                            <i class="fas fa-table me-2"></i>Emplois du Temps
                        </a>
                    </div>
                    <div class="col-md-3 mb-2">
                        <a href="{{ route('esbtp.evaluations.index') }}" class="btn-acasi success w-100">
                            <i class="fas fa-clipboard-list me-2"></i>Évaluations
                        </a>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-3 mb-2">
                        <a href="{{ route('esbtp.etudiants.index') }}" class="btn-acasi secondary w-100">
                            <i class="fas fa-user-graduate me-2"></i>Étudiants
                        </a>
                    </div>
                    <div class="col-md-3 mb-2">
                        <a href="{{ route('esbtp.teachers.index') }}" class="btn-acasi secondary w-100">
                            <i class="fas fa-chalkboard-teacher me-2"></i>Enseignants
                        </a>
                    </div>
                    <div class="col-md-3 mb-2">
                        <a href="{{ route('esbtp.annonces.index') }}" class="btn-acasi secondary w-100">
                            <i class="fas fa-bullhorn me-2"></i>Annonces
                        </a>
                    </div>
                    <div class="col-md-3 mb-2">
                        <a href="{{ route('esbtp.notes.index') }}" class="btn-acasi secondary w-100">
                            <i class="fas fa-edit me-2"></i>Notes
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
$(document).ready(function() {
    // Animation des cartes statistiques
    $('.stat-card').each(function(index) {
        $(this).css('transform', 'translateY(20px)');
        $(this).css('opacity', '0');
        
        setTimeout(() => {
            $(this).animate({
                'transform': 'translateY(0)',
                'opacity': '1'
            }, 300);
        }, index * 100);
    });
    
    // Animation de la barre de progression
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

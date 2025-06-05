@extends('layouts.app')

@section('title', 'Tableau de bord Super Admin')

@section('content')
<div class="nextadmin-content">
    <!-- Page Header with Modern Design -->
    <div class="page-header d-flex justify-content-between align-items-center animate-fade-in-up">
        <div>
            <h1 class="page-title">
                <i class="fas fa-chart-line me-3"></i>
                Tableau de bord
            </h1>
            <p class="page-subtitle">
                <i class="fas fa-building me-2"></i>
                Gestion administrative KLASSCI
            </p>
        </div>
        <div class="d-flex gap-3">
            <button class="btn btn-light hover-lift" data-bs-toggle="tooltip" data-bs-placement="top" title="Actualiser les données" onclick="location.reload()">
                <i class="fas fa-sync-alt"></i>
            </button>
            <div class="dropdown">
                <button class="btn btn-primary dropdown-toggle hover-lift" type="button" id="quickActionsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-bolt me-2"></i> Actions rapides
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="quickActionsDropdown">
                    <li><a class="dropdown-item" href="{{ route('esbtp.etudiants.create') }}">
                        <i class="fas fa-user-plus text-primary me-2"></i> Nouvel étudiant
                    </a></li>
                    <li><a class="dropdown-item" href="{{ route('esbtp.evaluations.create') }}">
                        <i class="fas fa-file-alt text-success me-2"></i> Créer examen
                    </a></li>
                    <li><a class="dropdown-item" href="{{ route('esbtp.annonces.create') }}">
                        <i class="fas fa-bullhorn text-warning me-2"></i> Publier annonce
                    </a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="{{ route('esbtp.bulletins.generate') }}">
                        <i class="fas fa-print text-info me-2"></i> Générer bulletins
                    </a></li>
                </ul>
            </div>
        </div>
    </div>

    @php
        $pendingInscriptionsCount = \App\Models\ESBTPInscription::where('status', 'pending')->count();
    @endphp

    <!-- Alert with Modern Glassmorphism -->
    @if($pendingInscriptionsCount > 0)
    <div class="alert alert-warning d-flex align-items-center animate-slide-in-right" role="alert">
        <div class="d-flex align-items-center">
            <div class="me-4">
                <div class="d-flex align-items-center justify-content-center" style="width: 60px; height: 60px; background: linear-gradient(135deg, #f59e0b, #fbbf24); border-radius: 20px;">
                    <i class="fas fa-exclamation-triangle fa-2x text-white"></i>
                </div>
            </div>
            <div class="flex-grow-1">
                <h5 class="alert-heading mb-2">
                    <i class="fas fa-bell me-2"></i>
                    Attention! Inscriptions en attente
                </h5>
                <p class="mb-3">
                    Il y a <strong>{{ $pendingInscriptionsCount }}</strong> inscription(s) en attente de validation.
                    Ces inscriptions nécessitent votre vérification pour finaliser le processus d'admission des étudiants.
                </p>
                <a href="{{ route('esbtp.inscriptions.index', ['status' => 'pending']) }}" class="btn btn-warning btn-sm hover-lift">
                    <i class="fas fa-check-circle me-1"></i> Consulter et valider
                </a>
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <!-- Modern Stats Cards with Real Data -->
    <div class="row g-4 mb-5">
        <div class="col-xl-3 col-md-6">
            <div class="stat-card primary animate-fade-in-up" style="animation-delay: 0.1s;">
                <div class="stat-card-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-card-content">
                    <div class="stat-card-value">{{ $totalStudents }}</div>
                    <div class="stat-card-label">Étudiants inscrits</div>
                    <div class="stat-card-change positive">
                        <i class="fas fa-arrow-up"></i> Actifs
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="stat-card success animate-fade-in-up" style="animation-delay: 0.2s;">
                <div class="stat-card-icon">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <div class="stat-card-content">
                    <div class="stat-card-value">{{ $totalFilieres }}</div>
                    <div class="stat-card-label">Filières actives</div>
                    <div class="stat-card-change positive">
                        <i class="fas fa-check"></i> Disponibles
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="stat-card warning animate-fade-in-up" style="animation-delay: 0.3s;">
                <div class="stat-card-icon">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <div class="stat-card-content">
                    <div class="stat-card-value">{{ $totalClasses }}</div>
                    <div class="stat-card-label">Classes ouvertes</div>
                    <div class="stat-card-change positive">
                        <i class="fas fa-door-open"></i> En cours
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="stat-card info animate-fade-in-up" style="animation-delay: 0.4s;">
                <div class="stat-card-icon">
                    <i class="fas fa-book-open"></i>
                </div>
                <div class="stat-card-content">
                    <div class="stat-card-value">{{ $totalMatieres }}</div>
                    <div class="stat-card-label">Matières enseignées</div>
                    <div class="stat-card-change positive">
                        <i class="fas fa-book"></i> Actives
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section with Real Data -->
    <div class="row g-4 mb-5">
        <!-- Main Chart -->
        <div class="col-xl-8">
            <div class="card hover-lift animate-fade-in-up" style="animation-delay: 0.5s;">
                <div class="card-header">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h5 class="card-title mb-1">
                                <i class="fas fa-chart-area text-primary me-2"></i>
                                Évolution des inscriptions
                            </h5>
                            <p class="text-muted mb-0">Nombre d'étudiants inscrits par mois</p>
                        </div>
                    <div class="dropdown">
                            <button class="btn btn-light btn-sm dropdown-toggle" type="button" id="chartPeriod" data-bs-toggle="dropdown">
                                <i class="fas fa-calendar me-1"></i> 12 derniers mois
                        </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item active" href="#"><i class="fas fa-calendar-day me-2"></i>12 mois</a></li>
                                <li><a class="dropdown-item" href="#"><i class="fas fa-calendar-week me-2"></i>6 mois</a></li>
                                <li><a class="dropdown-item" href="#"><i class="fas fa-calendar-alt me-2"></i>3 mois</a></li>
                        </ul>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div style="position: relative; height: 350px;">
                        <canvas id="inscriptionsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filières Distribution -->
        <div class="col-xl-4">
            <div class="card hover-lift animate-fade-in-up" style="animation-delay: 0.6s;">
                <div class="card-header">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h5 class="card-title mb-1">
                                <i class="fas fa-chart-pie text-success me-2"></i>
                                Répartition par filière
                            </h5>
                            <p class="text-muted mb-0">Distribution des étudiants</p>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    @if($filiereStats->count() > 0)
                        <div style="position: relative; height: 250px;">
                            <canvas id="filieresChart"></canvas>
                        </div>
                        <div class="mt-3">
                            @foreach($filiereStats as $filiere)
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div class="d-flex align-items-center">
                                    <div class="me-2" style="width: 12px; height: 12px; background: {{ $filiere['color'] }}; border-radius: 50%;"></div>
                                    <span class="fw-medium">{{ $filiere['name'] }}</span>
                                </div>
                                <span class="badge bg-light text-dark">{{ $filiere['students'] }}</span>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-chart-pie fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Aucune donnée disponible</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Data Tables Section with Real Data -->
    <div class="row g-4">
        <!-- Recent Inscriptions -->
        <div class="col-xl-12">
            <div class="card hover-lift animate-fade-in-up" style="animation-delay: 0.7s;">
                <div class="card-header">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h5 class="card-title mb-1">
                                <i class="fas fa-user-plus text-primary me-2"></i>
                                Inscriptions récentes
                            </h5>
                            <p class="text-muted mb-0">Dernières demandes d'inscription</p>
                        </div>
                        <a href="{{ route('esbtp.inscriptions.index') }}" class="btn btn-primary btn-sm hover-lift">
                            <i class="fas fa-eye me-1"></i> Voir tout
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    @if($recentInscriptions->count() > 0)
                    <div class="table-responsive">
                            <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                        <th><i class="fas fa-user me-1"></i> Étudiant</th>
                                        <th><i class="fas fa-graduation-cap me-1"></i> Filière</th>
                                        <th><i class="fas fa-school me-1"></i> Classe</th>
                                        <th><i class="fas fa-calendar me-1"></i> Date d'inscription</th>
                                        <th><i class="fas fa-flag me-1"></i> Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                    @foreach($recentInscriptions as $inscription)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                                <div class="navbar-avatar me-3" style="width: 40px; height: 40px; font-size: 16px; background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                                                    {{ strtoupper(substr($inscription->etudiant->prenoms ?? 'N', 0, 1) . substr($inscription->etudiant->nom ?? 'A', 0, 1)) }}
                                                </div>
                                                <div>
                                                    <div class="fw-semibold">{{ $inscription->etudiant->prenoms ?? 'N/A' }} {{ $inscription->etudiant->nom ?? 'N/A' }}</div>
                                                    <small class="text-muted">{{ $inscription->etudiant->email ?? 'Email non disponible' }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            @if($inscription->classe && $inscription->classe->filiere)
                                                <span class="badge" style="background: linear-gradient(135deg, #22c55e, #16a34a); color: white; padding: 8px 12px; border-radius: 8px;">
                                                    <i class="fas fa-graduation-cap me-1"></i>
                                                    {{ $inscription->classe->filiere->name }}
                                                </span>
                                            @else
                                                <span class="badge bg-secondary">
                                                    <i class="fas fa-question me-1"></i> Non définie
                                                </span>
                                            @endif
                                    </td>
                                        <td>
                                            @if($inscription->classe)
                                                <span class="badge bg-info text-white">
                                                    <i class="fas fa-users me-1"></i>
                                                    {{ $inscription->classe->name }}
                                                </span>
                                            @else
                                                <span class="badge bg-secondary">
                                                    <i class="fas fa-question me-1"></i> Non assignée
                                                </span>
                                            @endif
                                    </td>
                                    <td>
                                            <div class="fw-semibold">{{ $inscription->created_at->format('d/m/Y') }}</div>
                                            <small class="text-muted">{{ $inscription->created_at->diffForHumans() }}</small>
                                    </td>
                                        <td>
                                            @if($inscription->status === 'active' || $inscription->status === 'actif')
                                                <span class="badge bg-success" style="padding: 8px 12px; border-radius: 8px;">
                                                    <i class="fas fa-check-circle me-1"></i> Actif
                                                </span>
                                            @elseif($inscription->status === 'validated' || $inscription->status === 'validé' || $inscription->status === 'approved')
                                                <span class="badge bg-success" style="padding: 8px 12px; border-radius: 8px;">
                                                    <i class="fas fa-check-circle me-1"></i> Validé
                                                </span>
                                            @elseif($inscription->status === 'pending' || $inscription->status === 'en_attente' || $inscription->status === 'waiting')
                                                <span class="badge bg-warning" style="padding: 8px 12px; border-radius: 8px;">
                                                    <i class="fas fa-clock me-1"></i> En attente
                                                </span>
                                            @elseif($inscription->status === 'rejected' || $inscription->status === 'refusé' || $inscription->status === 'refused')
                                                <span class="badge bg-danger" style="padding: 8px 12px; border-radius: 8px;">
                                                    <i class="fas fa-times-circle me-1"></i> Refusé
                                                </span>
                                            @elseif($inscription->status === 'inactive' || $inscription->status === 'inactif' || $inscription->status === 'disabled')
                                                <span class="badge bg-secondary" style="padding: 8px 12px; border-radius: 8px;">
                                                    <i class="fas fa-pause-circle me-1"></i> Inactif
                                                </span>
                                            @else
                                                <span class="badge bg-light text-dark" style="padding: 8px 12px; border-radius: 8px; border: 1px solid #dee2e6;">
                                                    <i class="fas fa-info-circle me-1"></i> {{ ucfirst($inscription->status ?? 'Inconnu') }}
                                                </span>
                                            @endif
                                    </td>
                                </tr>
                                    @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-user-plus fa-4x text-muted mb-3"></i>
                            <h5 class="text-muted">Aucune inscription récente</h5>
                            <p class="text-muted">Les nouvelles inscriptions apparaîtront ici</p>
                            <a href="{{ route('esbtp.inscriptions.create') }}" class="btn btn-primary mt-3">
                                <i class="fas fa-plus me-1"></i> Nouvelle inscription
                            </a>
                        </div>
                    @endif
                </div>
                <div class="card-footer text-center">
                    <a href="{{ route('esbtp.inscriptions.index') }}" class="btn btn-light hover-lift">
                        <i class="fas fa-arrow-right me-1"></i> Voir toutes les inscriptions
                    </a>
                </div>
                </div>
            </div>
        </div>

    <!-- Quick Stats Row -->
    <div class="row g-4 mt-4">
        <div class="col-xl-3 col-md-6">
            <div class="card text-center hover-lift">
                <div class="card-body">
                    <div class="mb-3">
                        <i class="fas fa-user-tie fa-2x text-primary"></i>
                    </div>
                    <h4 class="fw-bold">{{ $totalTeachers ?? 0 }}</h4>
                    <p class="text-muted mb-0">Enseignants</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card text-center hover-lift">
                <div class="card-body">
                    <div class="mb-3">
                        <i class="fas fa-users fa-2x text-success"></i>
                    </div>
                    <h4 class="fw-bold">{{ $totalUsers ?? 0 }}</h4>
                    <p class="text-muted mb-0">Utilisateurs</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card text-center hover-lift">
                <div class="card-body">
                    <div class="mb-3">
                        <i class="fas fa-bullhorn fa-2x text-warning"></i>
                    </div>
                    <h4 class="fw-bold">{{ $recentAnnouncements->count() }}</h4>
                    <p class="text-muted mb-0">Annonces récentes</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card text-center hover-lift">
                <div class="card-body">
                    <div class="mb-3">
                        <i class="fas fa-chart-line fa-2x text-info"></i>
                    </div>
                    <h4 class="fw-bold">{{ $attendanceStats['attendance_rate'] }}%</h4>
                    <p class="text-muted mb-0">Taux de présence</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js Scripts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
    // Données pour le graphique des inscriptions
    const monthlyData = @json($monthlyStats);

    // Graphique des inscriptions
    const inscriptionsCtx = document.getElementById('inscriptionsChart');
    if (inscriptionsCtx) {
        new Chart(inscriptionsCtx, {
            type: 'line',
            data: {
                labels: monthlyData.map(item => item.month + ' ' + item.year),
                datasets: [{
                        label: 'Inscriptions',
                    data: monthlyData.map(item => item.inscriptions),
                        borderColor: '#6366f1',
                        backgroundColor: 'rgba(99, 102, 241, 0.1)',
                        tension: 0.4,
                        fill: true
                }, {
                    label: 'Étudiants',
                    data: monthlyData.map(item => item.students),
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    // Graphique des filières
    const filieresCtx = document.getElementById('filieresChart');
    if (filieresCtx) {
        const filieresData = @json($filiereStats);

        new Chart(filieresCtx, {
            type: 'doughnut',
            data: {
                labels: filieresData.map(item => item.name),
                datasets: [{
                    data: filieresData.map(item => item.students),
                    backgroundColor: filieresData.map(item => item.color),
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                            display: false
                    }
                }
            }
        });
    }
    });
</script>
@endsection

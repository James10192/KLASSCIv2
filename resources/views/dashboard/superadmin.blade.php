@extends('layouts.app')

@section('title', 'Tableau de bord Super Admin')

@section('content')
<div class="nextadmin-content">
    <!-- HEADER PREMIUM -->
    <div class="bg-gradient-primary rounded-4 p-5 mb-4 d-flex align-items-center justify-content-between" style="background: linear-gradient(135deg, #0453cb 0%, #5e91de 100%); min-height: 160px;">
        <div class="d-flex align-items-center gap-4">
            <div class="bg-white bg-opacity-25 rounded-circle d-flex align-items-center justify-content-center" style="width:72px;height:72px;">
                <i class="fas fa-crown fa-2x text-white"></i>
            </div>
            <div>
                <h1 class="text-white fw-bold mb-1" style="font-size:2.2rem;">Tableau de bord Super Admin</h1>
                <p class="text-white-50 mb-0">Gestion administrative KLASSCI</p>
            </div>
        </div>
        <div class="d-flex gap-3">
            <button class="btn btn-light btn-lg shadow-sm" data-bs-toggle="tooltip" title="Actualiser les données" onclick="location.reload()">
                <i class="fas fa-sync-alt"></i>
            </button>
            <div class="dropdown">
                <button class="btn btn-warning btn-lg dropdown-toggle text-white fw-bold" type="button" id="quickActionsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-bolt me-2"></i> Actions rapides
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="{{ route('esbtp.inscriptions.create') }}"><i class="fas fa-user-plus text-primary me-2"></i> Nouvel étudiant</a></li>
                    <li><a class="dropdown-item" href="{{ route('esbtp.evaluations.create') }}"><i class="fas fa-file-alt text-success me-2"></i> Créer examen</a></li>
                    <li><a class="dropdown-item" href="{{ route('esbtp.annonces.create') }}"><i class="fas fa-bullhorn text-warning me-2"></i> Publier annonce</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <!-- <li><a class="dropdown-item" href="{{ route('esbtp.resultats.index') }}"><i class="fas fa-print text-info me-2"></i> Générer bulletins</a></li> -->
                </ul>
            </div>
        </div>
    </div>

    @php
        $pendingInscriptionsCount = \App\Models\ESBTPInscription::where('status', 'pending')->count();
    @endphp

    <!-- ALERT GLASSMORPHISM -->
    @if($pendingInscriptionsCount > 0)
    <div class="alert alert-warning d-flex align-items-center shadow-lg rounded-4 p-4 mb-5 animate-fade-in-up" style="backdrop-filter: blur(8px) saturate(1.2); background: rgba(255, 193, 7, 0.18); border: none;">
        <div class="me-4 d-flex align-items-center justify-content-center" style="width: 64px; height: 64px; background: linear-gradient(135deg, #f59e0b, #fbbf24); border-radius: 18px;">
            <i class="fas fa-exclamation-triangle fa-2x text-white"></i>
        </div>
        <div class="flex-grow-1">
            <h5 class="alert-heading mb-2 fw-bold text-dark">
                <i class="fas fa-bell me-2 text-warning"></i>
                Attention! Inscriptions en attente
            </h5>
            <p class="mb-3 text-dark">
                Il y a <strong>{{ $pendingInscriptionsCount }}</strong> inscription(s) en attente de validation.<br>
                Ces inscriptions nécessitent votre vérification pour finaliser le processus d'admission des étudiants.
            </p>
            <a href="{{ route('esbtp.inscriptions.index', ['status' => 'pending']) }}" class="btn btn-warning btn-lg rounded-pill px-4 shadow-sm fw-bold">
                <i class="fas fa-check-circle me-1"></i> Consulter et valider
            </a>
        </div>
        <button type="button" class="btn-close ms-3" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <!-- CARDS STATISTIQUES PREMIUM -->
    <div class="row g-4 mb-5 animate-fade-in-up">
        <div class="col-xl-3 col-md-6">
            <div class="card shadow-lg border-0 rounded-4 text-center p-4 h-100 hover-lift">
                <div class="d-flex justify-content-center mb-3">
                    <span class="d-inline-flex align-items-center justify-content-center bg-primary bg-gradient text-white rounded-circle" style="width:60px;height:60px;font-size:2rem;">
                        <i class="fas fa-users"></i>
                    </span>
                </div>
                <div class="display-5 fw-bold mb-1 text-primary">{{ $totalStudents }}</div>
                <div class="text-muted mb-2">Étudiants inscrits</div>
                <span class="badge bg-success bg-gradient rounded-pill px-3 py-2">Actifs <i class="fas fa-arrow-up ms-1"></i></span>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card shadow-lg border-0 rounded-4 text-center p-4 h-100 hover-lift">
                <div class="d-flex justify-content-center mb-3">
                    <span class="d-inline-flex align-items-center justify-content-center bg-success bg-gradient text-white rounded-circle" style="width:60px;height:60px;font-size:2rem;">
                        <i class="fas fa-graduation-cap"></i>
                    </span>
                </div>
                <div class="display-5 fw-bold mb-1 text-success">{{ $totalFilieres }}</div>
                <div class="text-muted mb-2">Filières actives</div>
                <span class="badge bg-primary bg-gradient rounded-pill px-3 py-2">Disponibles <i class="fas fa-check ms-1"></i></span>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card shadow-lg border-0 rounded-4 text-center p-4 h-100 hover-lift">
                <div class="d-flex justify-content-center mb-3">
                    <span class="d-inline-flex align-items-center justify-content-center bg-warning bg-gradient text-white rounded-circle" style="width:60px;height:60px;font-size:2rem;">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </span>
                </div>
                <div class="display-5 fw-bold mb-1 text-warning">{{ $totalClasses }}</div>
                <div class="text-muted mb-2">Classes ouvertes</div>
                <span class="badge bg-info bg-gradient rounded-pill px-3 py-2">En cours <i class="fas fa-door-open ms-1"></i></span>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card shadow-lg border-0 rounded-4 text-center p-4 h-100 hover-lift">
                <div class="d-flex justify-content-center mb-3">
                    <span class="d-inline-flex align-items-center justify-content-center bg-info bg-gradient text-white rounded-circle" style="width:60px;height:60px;font-size:2rem;">
                        <i class="fas fa-book-open"></i>
                    </span>
                </div>
                <div class="display-5 fw-bold mb-1 text-info">{{ $totalMatieres }}</div>
                <div class="text-muted mb-2">Matières enseignées</div>
                <span class="badge bg-warning bg-gradient rounded-pill px-3 py-2">Actives <i class="fas fa-book ms-1"></i></span>
            </div>
        </div>
    </div>

    <!-- SECTION GRAPHIQUES PREMIUM -->
    <div class="row g-4 mb-5 animate-fade-in-up">
        <div class="col-xl-8">
            <div class="card shadow-lg border-0 rounded-4 p-4 h-100">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <h5 class="fw-bold mb-1 text-primary"><i class="fas fa-chart-area me-2"></i>Évolution des inscriptions</h5>
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
                <div style="position: relative; height: 350px;">
                    <canvas id="inscriptionsChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card shadow-lg border-0 rounded-4 p-4 h-100">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <h5 class="fw-bold mb-1 text-success"><i class="fas fa-chart-pie me-2"></i>Répartition par filière</h5>
                        <p class="text-muted mb-0">Distribution des étudiants</p>
                    </div>
                </div>
                <div style="position: relative; height: 250px;">
                    <canvas id="filieresChart"></canvas>
                </div>
                <div class="mt-3">
                    @foreach($filiereStats as $filiere)
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="d-flex align-items-center">
                            <div class="me-2" style="width: 14px; height: 14px; background: {{ $filiere['color'] }}; border-radius: 50%;"></div>
                            <span class="fw-medium">{{ $filiere['name'] }}</span>
                        </div>
                        <span class="badge bg-light text-dark px-3 py-2 rounded-pill">{{ $filiere['students'] }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- TABLEAU INSCRIPTIONS RÉCENTES PREMIUM -->
    <div class="card shadow-lg border-0 rounded-4 p-4 mb-5 animate-fade-in-up">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <h5 class="fw-bold mb-1 text-primary"><i class="fas fa-user-plus me-2"></i>Inscriptions récentes</h5>
                <p class="text-muted mb-0">Dernières demandes d'inscription</p>
            </div>
            <a href="{{ route('esbtp.inscriptions.index') }}" class="btn btn-primary btn-sm rounded-pill px-4 fw-bold">
                <i class="fas fa-eye me-1"></i> Voir tout
            </a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light sticky-top">
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
                                <div class="rounded-circle bg-primary bg-gradient text-white d-flex align-items-center justify-content-center me-3" style="width: 44px; height: 44px; font-size: 1.1rem;">
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
                                <span class="badge bg-success bg-gradient text-white px-3 py-2 rounded-pill">
                                    <i class="fas fa-graduation-cap me-1"></i>
                                    {{ $inscription->classe->filiere->name }}
                                </span>
                            @else
                                <span class="badge bg-secondary px-3 py-2 rounded-pill">
                                    <i class="fas fa-question me-1"></i> Non définie
                                </span>
                            @endif
                        </td>
                        <td>
                            @if($inscription->classe)
                                <span class="badge bg-info bg-gradient text-white px-3 py-2 rounded-pill">
                                    <i class="fas fa-users me-1"></i>
                                    {{ $inscription->classe->name }}
                                </span>
                            @else
                                <span class="badge bg-secondary px-3 py-2 rounded-pill">
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
                                <span class="badge bg-success bg-gradient text-white px-3 py-2 rounded-pill">
                                    <i class="fas fa-check-circle me-1"></i> Actif
                                </span>
                            @elseif($inscription->status === 'validated' || $inscription->status === 'validé' || $inscription->status === 'approved')
                                <span class="badge bg-success bg-gradient text-white px-3 py-2 rounded-pill">
                                    <i class="fas fa-check-circle me-1"></i> Validé
                                </span>
                            @elseif($inscription->status === 'pending' || $inscription->status === 'en_attente' || $inscription->status === 'waiting')
                                <span class="badge bg-warning bg-gradient text-white px-3 py-2 rounded-pill">
                                    <i class="fas fa-clock me-1"></i> En attente
                                </span>
                            @elseif($inscription->status === 'rejected' || $inscription->status === 'refusé' || $inscription->status === 'refused')
                                <span class="badge bg-danger bg-gradient text-white px-3 py-2 rounded-pill">
                                    <i class="fas fa-times-circle me-1"></i> Refusé
                                </span>
                            @elseif($inscription->status === 'inactive' || $inscription->status === 'inactif' || $inscription->status === 'disabled')
                                <span class="badge bg-secondary px-3 py-2 rounded-pill">
                                    <i class="fas fa-pause-circle me-1"></i> Inactif
                                </span>
                            @else
                                <span class="badge bg-light text-dark px-3 py-2 rounded-pill border">
                                    <i class="fas fa-info-circle me-1"></i> {{ ucfirst($inscription->status ?? 'Inconnu') }}
                                </span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-transparent border-0 text-center mt-3">
            <a href="{{ route('esbtp.inscriptions.index') }}" class="btn btn-light btn-lg rounded-pill px-4 fw-bold hover-lift">
                <i class="fas fa-arrow-right me-1"></i> Voir toutes les inscriptions
            </a>
        </div>
    </div>

    <!-- ACTIONS RAPIDES PREMIUM -->
    <div class="row g-4 mb-5 animate-fade-in-up">
        <div class="col-xl-3 col-md-6">
            <a href="{{ route('esbtp.inscriptions.create') }}" class="btn btn-primary btn-lg w-100 d-flex flex-column align-items-center justify-content-center gap-2 py-4 rounded-4 shadow-sm hover-lift">
                <i class="fas fa-user-plus fa-2x"></i>
                <span class="fw-bold">Nouvel étudiant</span>
            </a>
        </div>
        <div class="col-xl-3 col-md-6">
            <a href="{{ route('esbtp.evaluations.create') }}" class="btn btn-success btn-lg w-100 d-flex flex-column align-items-center justify-content-center gap-2 py-4 rounded-4 shadow-sm hover-lift">
                <i class="fas fa-file-alt fa-2x"></i>
                <span class="fw-bold">Créer examen</span>
            </a>
        </div>
        <div class="col-xl-3 col-md-6">
            <a href="{{ route('esbtp.annonces.create') }}" class="btn btn-warning btn-lg w-100 d-flex flex-column align-items-center justify-content-center gap-2 py-4 rounded-4 shadow-sm hover-lift text-white">
                <i class="fas fa-bullhorn fa-2x"></i>
                <span class="fw-bold">Publier annonce</span>
            </a>
        </div>
        <div class="col-xl-3 col-md-6">
            <a href="{{ route('esbtp.resultats.index') }}" class="btn btn-info btn-lg w-100 d-flex flex-column align-items-center justify-content-center gap-2 py-4 rounded-4 shadow-sm hover-lift text-white">
                <i class="fas fa-print fa-2x"></i>
                <span class="fw-bold">Générer bulletins</span>
            </a>
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

@extends('layouts.app')

@section('title', 'Tableau de bord Super Admin')

@section('content')
<div class="main-content">
    <!-- Header -->
    <div class="dashboard-header mb-xl" style="background-color: var(--primary); color: white; border-radius: var(--radius-medium);">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <div style="display: flex; align-items: center; gap: var(--space-lg);">
                    <div style="width: 80px; height: 80px; border-radius: var(--radius-circle); background-color: var(--accent-blue); color: white; display: flex; align-items: center; justify-content: center; font-size: 2rem; font-weight: 700; box-shadow: var(--shadow-elevated);">
                        <i class="fas fa-crown"></i>
                    </div>
                    <div>
                        <h1 style="color: white; margin: 0; font-size: var(--title-main); font-weight: 700;">Tableau de bord Super Admin</h1>
                        <p style="color: rgba(255,255,255,0.8); margin: var(--space-xs) 0 0 0;">Gestion administrative ESBTP-yAKRO</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 text-end">
                <div class="header-actions">
                    <button class="btn-acasi secondary" style="margin-right: var(--space-md);" onclick="location.reload()" title="Actualiser les données">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                    <div class="dropdown d-inline-block">
                        <button class="btn-acasi" style="background-color: var(--warning); color: white;" type="button" id="quickActionsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-bolt"></i> Actions rapides
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="{{ route('esbtp.inscriptions.create') }}"><i class="fas fa-user-plus" style="color: var(--primary);"></i> Nouvel étudiant</a></li>
                            <li><a class="dropdown-item" href="{{ route('esbtp.evaluations.create') }}"><i class="fas fa-file-alt" style="color: var(--success);"></i> Créer examen</a></li>
                            <li><a class="dropdown-item" href="{{ route('esbtp.annonces.create') }}"><i class="fas fa-bullhorn" style="color: var(--warning);"></i> Publier annonce</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="{{ route('esbtp.resultats.index') }}"><i class="fas fa-print" style="color: var(--accent-blue);"></i> Générer bulletins</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @php
        $pendingInscriptionsCount = \App\Models\ESBTPInscription::where('status', 'pending')->count();
    @endphp

    <!-- Alert for pending inscriptions -->
    @if($pendingInscriptionsCount > 0)
    <div class="alert alert-warning alert-dismissible fade show mb-lg" style="background-color: rgba(245, 158, 11, 0.1); border: 1px solid var(--warning); border-radius: var(--radius-medium); padding: var(--space-lg);">
        <div style="display: flex; align-items: center; gap: var(--space-md);">
            <div style="width: 64px; height: 64px; background-color: var(--warning); color: white; border-radius: var(--radius-medium); display: flex; align-items: center; justify-content: center; box-shadow: var(--shadow-card);">
                <i class="fas fa-exclamation-triangle fa-2x"></i>
            </div>
            <div style="flex: 1;">
                <h5 style="color: var(--warning); font-weight: 600; margin: 0 0 var(--space-sm) 0;">
                    <i class="fas fa-bell"></i>
                    Attention! Inscriptions en attente
                </h5>
                <p style="margin: 0 0 var(--space-md) 0; color: var(--text-primary);">
                    Il y a <strong>{{ $pendingInscriptionsCount }}</strong> inscription(s) en attente de validation.<br>
                    Ces inscriptions nécessitent votre vérification pour finaliser le processus d'admission des étudiants.
                </p>
                <a href="{{ route('esbtp.inscriptions.index', ['status' => 'pending']) }}" class="btn-acasi" style="background-color: var(--warning); color: white;">
                    <i class="fas fa-check-circle"></i> Consulter et valider
                </a>
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <!-- KPI Cards -->
    <div class="kpi-grid mb-xl">
        <div class="kpi-card card-moderne" style="background-color: var(--primary); color: white; text-align: center;">
            <i class="fas fa-users fa-2x mb-md"></i>
            <div class="kpi-title" style="color: white;">Étudiants</div>
            <div class="kpi-value" style="color: white;">{{ $totalStudents }}</div>
            <div style="color: rgba(255,255,255,0.8); font-size: var(--text-small);">Inscrits actifs</div>
        </div>

        <div class="kpi-card card-moderne" style="background-color: var(--success); color: white; text-align: center;">
            <i class="fas fa-graduation-cap fa-2x mb-md"></i>
            <div class="kpi-title" style="color: white;">Filières</div>
            <div class="kpi-value" style="color: white;">{{ $totalFilieres }}</div>
            <div style="color: rgba(255,255,255,0.8); font-size: var(--text-small);">Disponibles</div>
        </div>

        <div class="kpi-card card-moderne" style="background-color: var(--warning); color: white; text-align: center;">
            <i class="fas fa-chalkboard-teacher fa-2x mb-md"></i>
            <div class="kpi-title" style="color: white;">Classes</div>
            <div class="kpi-value" style="color: white;">{{ $totalClasses }}</div>
            <div style="color: rgba(255,255,255,0.8); font-size: var(--text-small);">Ouvertes</div>
        </div>

        <div class="kpi-card card-moderne" style="background-color: var(--accent-blue); color: white; text-align: center;">
            <i class="fas fa-book-open fa-2x mb-md"></i>
            <div class="kpi-title" style="color: white;">Matières</div>
            <div class="kpi-value" style="color: white;">{{ $totalMatieres }}</div>
            <div style="color: rgba(255,255,255,0.8); font-size: var(--text-small);">Enseignées</div>
        </div>

        <div class="kpi-card card-moderne" style="background-color: var(--secondary); color: white; text-align: center;">
            <i class="fas fa-user-tie fa-2x mb-md"></i>
            <div class="kpi-title" style="color: white;">Enseignants</div>
            <div class="kpi-value" style="color: white;">{{ $totalTeachers ?? 0 }}</div>
            <div style="color: rgba(255,255,255,0.8); font-size: var(--text-small);">Actifs</div>
        </div>

        <div class="kpi-card card-moderne" style="background-color: var(--neutral); color: white; text-align: center;">
            <i class="fas fa-chart-line fa-2x mb-md"></i>
            <div class="kpi-title" style="color: white;">Présence</div>
            <div class="kpi-value" style="color: white;">{{ $attendanceStats['attendance_rate'] }}%</div>
            <div style="color: rgba(255,255,255,0.8); font-size: var(--text-small);">Taux moyen</div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="row mb-xl">
        <div class="col-xl-8 mb-lg">
            <div class="card-moderne p-lg">
                <div class="section-title mb-lg" style="color: var(--primary); border-bottom: 2px solid var(--primary); padding-bottom: var(--space-sm);">
                    <i class="fas fa-chart-area"></i>
                    Évolution des inscriptions
                </div>
                <p style="color: var(--text-secondary); margin-bottom: var(--space-lg);">Nombre d'étudiants inscrits par mois</p>
                
                <div class="chart-container">
                    <canvas id="inscriptionsChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-xl-4 mb-lg">
            <div class="card-moderne p-lg">
                <div class="section-title mb-lg" style="color: var(--success); border-bottom: 2px solid var(--success); padding-bottom: var(--space-sm);">
                    <i class="fas fa-chart-pie"></i>
                    Répartition par filière
                </div>
                <p style="color: var(--text-secondary); margin-bottom: var(--space-lg);">Distribution des étudiants</p>
                
                <div style="position: relative; height: 250px; margin-bottom: var(--space-lg);">
                    <canvas id="filieresChart"></canvas>
                </div>
                
                <div>
                    @foreach($filiereStats as $filiere)
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: var(--space-sm); margin-bottom: var(--space-sm); background: var(--background); border-radius: var(--radius-small); border-left: 4px solid {{ $filiere['color'] }};">
                        <div style="display: flex; align-items: center;">
                            <div style="width: 14px; height: 14px; background: {{ $filiere['color'] }}; border-radius: 50%; margin-right: var(--space-sm);"></div>
                            <span style="font-weight: 500; color: var(--text-primary);">{{ $filiere['name'] }}</span>
                        </div>
                        <span class="badge primary">{{ $filiere['students'] }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Inscriptions Table -->
    <div class="card-moderne mb-xl">
        <div class="p-lg">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-lg);">
                <div>
                    <div class="section-title" style="color: var(--primary);">
                        <i class="fas fa-user-plus"></i>
                        Inscriptions récentes
                    </div>
                    <p style="color: var(--text-secondary); margin: 0;">Dernières demandes d'inscription</p>
                </div>
                <a href="{{ route('esbtp.inscriptions.index') }}" class="btn-acasi primary">
                    <i class="fas fa-eye"></i> Voir tout
                </a>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover" style="border-collapse: separate; border-spacing: 0; border-radius: var(--radius-medium); overflow: hidden; box-shadow: var(--shadow-card);">
                    <thead style="background-color: var(--primary); color: white;">
                        <tr>
                            <th style="padding: var(--space-md); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; border: none;">
                                <i class="fas fa-user"></i> Étudiant
                            </th>
                            <th style="padding: var(--space-md); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; border: none;">
                                <i class="fas fa-graduation-cap"></i> Filière
                            </th>
                            <th style="padding: var(--space-md); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; border: none;">
                                <i class="fas fa-school"></i> Classe
                            </th>
                            <th style="padding: var(--space-md); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; border: none;">
                                <i class="fas fa-calendar"></i> Date
                            </th>
                            <th style="padding: var(--space-md); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; border: none;">
                                <i class="fas fa-flag"></i> Statut
                            </th>
                        </tr>
                    </thead>
                    <tbody style="background-color: var(--surface);">
                        @foreach($recentInscriptions as $inscription)
                        <tr style="border-bottom: 1px solid #f3f4f6;">
                            <td style="padding: var(--space-md);">
                                <div style="display: flex; align-items: center;">
                                    <div style="width: 44px; height: 44px; border-radius: var(--radius-circle); background-color: var(--primary); color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; margin-right: var(--space-md);">
                                        {{ strtoupper(substr($inscription->etudiant->prenoms ?? 'N', 0, 1) . substr($inscription->etudiant->nom ?? 'A', 0, 1)) }}
                                    </div>
                                    <div>
                                        <div style="font-weight: 600; color: var(--text-primary);">{{ $inscription->etudiant->prenoms ?? 'N/A' }} {{ $inscription->etudiant->nom ?? 'N/A' }}</div>
                                        <small style="color: var(--text-secondary);">{{ $inscription->etudiant->email ?? 'Email non disponible' }}</small>
                                    </div>
                                </div>
                            </td>
                            <td style="padding: var(--space-md);">
                                @if($inscription->classe && $inscription->classe->filiere)
                                    <span class="badge success">
                                        <i class="fas fa-graduation-cap"></i>
                                        {{ $inscription->classe->filiere->name }}
                                    </span>
                                @else
                                    <span class="badge" style="background-color: rgba(107, 114, 128, 0.1); color: var(--neutral);">
                                        <i class="fas fa-question"></i> Non définie
                                    </span>
                                @endif
                            </td>
                            <td style="padding: var(--space-md);">
                                @if($inscription->classe)
                                    <span class="badge" style="background-color: rgba(6, 182, 212, 0.1); color: var(--accent-blue);">
                                        <i class="fas fa-users"></i>
                                        {{ $inscription->classe->name }}
                                    </span>
                                @else
                                    <span class="badge" style="background-color: rgba(107, 114, 128, 0.1); color: var(--neutral);">
                                        <i class="fas fa-question"></i> Non assignée
                                    </span>
                                @endif
                            </td>
                            <td style="padding: var(--space-md);">
                                <div style="font-weight: 600; color: var(--text-primary);">{{ $inscription->created_at->format('d/m/Y') }}</div>
                                <small style="color: var(--text-secondary);">{{ $inscription->created_at->diffForHumans() }}</small>
                            </td>
                            <td style="padding: var(--space-md);">
                                @if($inscription->status === 'active' || $inscription->status === 'actif')
                                    <span class="badge success">
                                        <i class="fas fa-check-circle"></i> Actif
                                    </span>
                                @elseif($inscription->status === 'validated' || $inscription->status === 'validé' || $inscription->status === 'approved')
                                    <span class="badge success">
                                        <i class="fas fa-check-circle"></i> Validé
                                    </span>
                                @elseif($inscription->status === 'pending' || $inscription->status === 'en_attente' || $inscription->status === 'waiting')
                                    <span class="badge warning">
                                        <i class="fas fa-clock"></i> En attente
                                    </span>
                                @elseif($inscription->status === 'rejected' || $inscription->status === 'refusé' || $inscription->status === 'refused')
                                    <span class="badge danger">
                                        <i class="fas fa-times-circle"></i> Refusé
                                    </span>
                                @elseif($inscription->status === 'inactive' || $inscription->status === 'inactif' || $inscription->status === 'disabled')
                                    <span class="badge" style="background-color: rgba(107, 114, 128, 0.1); color: var(--neutral);">
                                        <i class="fas fa-pause-circle"></i> Inactif
                                    </span>
                                @else
                                    <span class="badge" style="background-color: rgba(107, 114, 128, 0.1); color: var(--neutral);">
                                        <i class="fas fa-info-circle"></i> {{ ucfirst($inscription->status ?? 'Inconnu') }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <div class="text-center mt-lg">
                <a href="{{ route('esbtp.inscriptions.index') }}" class="btn-acasi secondary">
                    <i class="fas fa-arrow-right"></i> Voir toutes les inscriptions
                </a>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-xl">
        <div class="col-xl-3 col-md-6 mb-lg">
            <a href="{{ route('esbtp.inscriptions.create') }}" class="card-moderne" style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: var(--space-xl); text-decoration: none; color: inherit; transition: all 0.3s ease;">
                <div style="width: 80px; height: 80px; border-radius: var(--radius-circle); background-color: var(--primary); color: white; display: flex; align-items: center; justify-content: center; margin-bottom: var(--space-md); box-shadow: var(--shadow-card);">
                    <i class="fas fa-user-plus fa-2x"></i>
                </div>
                <span style="font-weight: 600; color: var(--text-primary);">Nouvel étudiant</span>
            </a>
        </div>
        <div class="col-xl-3 col-md-6 mb-lg">
            <a href="{{ route('esbtp.evaluations.create') }}" class="card-moderne" style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: var(--space-xl); text-decoration: none; color: inherit; transition: all 0.3s ease;">
                <div style="width: 80px; height: 80px; border-radius: var(--radius-circle); background-color: var(--success); color: white; display: flex; align-items: center; justify-content: center; margin-bottom: var(--space-md); box-shadow: var(--shadow-card);">
                    <i class="fas fa-file-alt fa-2x"></i>
                </div>
                <span style="font-weight: 600; color: var(--text-primary);">Créer examen</span>
            </a>
        </div>
        <div class="col-xl-3 col-md-6 mb-lg">
            <a href="{{ route('esbtp.annonces.create') }}" class="card-moderne" style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: var(--space-xl); text-decoration: none; color: inherit; transition: all 0.3s ease;">
                <div style="width: 80px; height: 80px; border-radius: var(--radius-circle); background-color: var(--warning); color: white; display: flex; align-items: center; justify-content: center; margin-bottom: var(--space-md); box-shadow: var(--shadow-card);">
                    <i class="fas fa-bullhorn fa-2x"></i>
                </div>
                <span style="font-weight: 600; color: var(--text-primary);">Publier annonce</span>
            </a>
        </div>
        <div class="col-xl-3 col-md-6 mb-lg">
            <a href="{{ route('esbtp.resultats.index') }}" class="card-moderne" style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: var(--space-xl); text-decoration: none; color: inherit; transition: all 0.3s ease;">
                <div style="width: 80px; height: 80px; border-radius: var(--radius-circle); background-color: var(--accent-blue); color: white; display: flex; align-items: center; justify-content: center; margin-bottom: var(--space-md); box-shadow: var(--shadow-card);">
                    <i class="fas fa-print fa-2x"></i>
                </div>
                <span style="font-weight: 600; color: var(--text-primary);">Générer bulletins</span>
            </a>
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

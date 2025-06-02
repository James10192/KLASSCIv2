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
                Gestion administrative ESBTP-yAKRO
            </p>
        </div>
        <div class="d-flex gap-3">
            <button class="btn btn-light hover-lift" data-bs-toggle="tooltip" data-bs-placement="top" title="Actualiser les données">
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

    <!-- Modern Stats Cards with Glassmorphism -->
    <div class="row g-4 mb-5">
        <div class="col-xl-3 col-md-6">
            <div class="stat-card primary animate-fade-in-up" style="animation-delay: 0.1s;">
                <div class="stat-card-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-card-content">
                    <div class="stat-card-value">{{ $totalStudents ?? 0 }}</div>
                    <div class="stat-card-label">Étudiants inscrits</div>
                    <div class="stat-card-change positive">
                        <i class="fas fa-arrow-up"></i> +4.25%
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
                    <div class="stat-card-value">{{ $totalFilieres ?? 0 }}</div>
                    <div class="stat-card-label">Filières actives</div>
                    <div class="stat-card-change positive">
                        <i class="fas fa-arrow-up"></i> +2.1%
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="stat-card warning animate-fade-in-up" style="animation-delay: 0.3s;">
                <div class="stat-card-icon">
                    <i class="fas fa-book-open"></i>
                </div>
                <div class="stat-card-content">
                    <div class="stat-card-value">{{ $totalMatieres ?? 0 }}</div>
                    <div class="stat-card-label">Matières enseignées</div>
                    <div class="stat-card-change positive">
                        <i class="fas fa-arrow-up"></i> +1.8%
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="stat-card info animate-fade-in-up" style="animation-delay: 0.4s;">
                <div class="stat-card-icon">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <div class="stat-card-content">
                    <div class="stat-card-value">{{ $totalClasses ?? 0 }}</div>
                    <div class="stat-card-label">Classes ouvertes</div>
                    <div class="stat-card-change positive">
                        <i class="fas fa-arrow-up"></i> +2.8%
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section with Modern Cards -->
    <div class="row g-4 mb-5">
        <!-- Main Chart -->
        <div class="col-xl-8">
            <div class="card hover-lift animate-fade-in-up" style="animation-delay: 0.5s;">
                <div class="card-header">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h5 class="card-title mb-1">
                                <i class="fas fa-chart-area text-primary me-2"></i>
                                Statistiques des inscriptions
                            </h5>
                            <p class="text-muted mb-0">Évolution mensuelle des inscriptions et validations</p>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-light btn-sm dropdown-toggle" type="button" id="chartPeriod" data-bs-toggle="dropdown">
                                <i class="fas fa-calendar me-1"></i> Mensuel
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item active" href="#"><i class="fas fa-calendar-day me-2"></i>Mensuel</a></li>
                                <li><a class="dropdown-item" href="#"><i class="fas fa-calendar-week me-2"></i>Trimestriel</a></li>
                                <li><a class="dropdown-item" href="#"><i class="fas fa-calendar-alt me-2"></i>Annuel</a></li>
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

        <!-- Financial Overview -->
        <div class="col-xl-4">
            <div class="card hover-lift animate-fade-in-up" style="animation-delay: 0.6s;">
                <div class="card-header">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h5 class="card-title mb-1">
                                <i class="fas fa-coins text-success me-2"></i>
                                Aperçu financier
                            </h5>
                            <p class="text-muted mb-0">Situation des paiements</p>
                        </div>
                        <a href="{{ route('esbtp.comptabilite.index') }}" class="btn btn-light btn-sm hover-lift">
                            <i class="fas fa-external-link-alt"></i>
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3 mb-4">
                        <div class="col-6">
                            <div class="text-center p-3" style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(52, 211, 153, 0.1)); border-radius: 16px;">
                                <h6 class="text-success mb-1">Total payé</h6>
                                <h4 class="mb-0 text-success fw-bold">{{ number_format(45070000, 0, ',', ' ') }}</h4>
                                <small class="text-muted">FCFA</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center p-3" style="background: linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(248, 113, 113, 0.1)); border-radius: 16px;">
                                <h6 class="text-danger mb-1">Montant dû</h6>
                                <h4 class="mb-0 text-danger fw-bold">{{ number_format(32400000, 0, ',', ' ') }}</h4>
                                <small class="text-muted">FCFA</small>
                            </div>
                        </div>
                    </div>
                    <div style="position: relative; height: 200px;">
                        <canvas id="paymentsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Tables Section -->
    <div class="row g-4">
        <!-- Recent Inscriptions -->
        <div class="col-xl-6">
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
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th><i class="fas fa-user me-1"></i> Étudiant</th>
                                    <th><i class="fas fa-graduation-cap me-1"></i> Filière</th>
                                    <th><i class="fas fa-calendar me-1"></i> Date</th>
                                    <th><i class="fas fa-flag me-1"></i> Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="navbar-avatar me-3" style="width: 32px; height: 32px; font-size: 14px;">
                                                KY
                                            </div>
                                            <div>
                                                <div class="fw-semibold">Konan Yves</div>
                                                <small class="text-muted">konan.yves@email.com</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge" style="background: linear-gradient(135deg, #6366f1, #818cf8); color: white;">
                                            Informatique
                                        </span>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">06/11/2023</div>
                                        <small class="text-muted">Il y a 2 jours</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-success">
                                            <i class="fas fa-check me-1"></i> Validé
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="navbar-avatar me-3" style="width: 32px; height: 32px; font-size: 14px;">
                                                TF
                                            </div>
                                            <div>
                                                <div class="fw-semibold">Touré Fatima</div>
                                                <small class="text-muted">toure.fatima@email.com</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge" style="background: linear-gradient(135deg, #f59e0b, #fbbf24); color: white;">
                                            Comptabilité
                                        </span>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">04/11/2023</div>
                                        <small class="text-muted">Il y a 4 jours</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-warning">
                                            <i class="fas fa-clock me-1"></i> En attente
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="navbar-avatar me-3" style="width: 32px; height: 32px; font-size: 14px;">
                                                DM
                                            </div>
                                            <div>
                                                <div class="fw-semibold">Diallo Mohamed</div>
                                                <small class="text-muted">diallo.mohamed@email.com</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge" style="background: linear-gradient(135deg, #10b981, #34d399); color: white;">
                                            Électronique
                                        </span>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">03/11/2023</div>
                                        <small class="text-muted">Il y a 5 jours</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-success">
                                            <i class="fas fa-check me-1"></i> Validé
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="navbar-avatar me-3" style="width: 32px; height: 32px; font-size: 14px;">
                                                KA
                                            </div>
                                            <div>
                                                <div class="fw-semibold">Koffi Anne</div>
                                                <small class="text-muted">koffi.anne@email.com</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge" style="background: linear-gradient(135deg, #06b6d4, #22d3ee); color: white;">
                                            Génie Civil
                                        </span>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">01/11/2023</div>
                                        <small class="text-muted">Il y a 1 semaine</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-success">
                                            <i class="fas fa-check me-1"></i> Validé
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer text-center">
                    <a href="{{ route('esbtp.inscriptions.index') }}" class="btn btn-light hover-lift">
                        <i class="fas fa-arrow-right me-1"></i> Voir toutes les inscriptions
                    </a>
                </div>
            </div>
        </div>

        <!-- Upcoming Exams -->
        <div class="col-xl-6">
            <div class="card hover-lift animate-fade-in-up" style="animation-delay: 0.8s;">
                <div class="card-header">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h5 class="card-title mb-1">
                                <i class="fas fa-calendar-check text-info me-2"></i>
                                Examens à venir
                            </h5>
                            <p class="text-muted mb-0">Prochaines évaluations programmées</p>
                        </div>
                        <a href="{{ route('esbtp.evaluations.index') }}" class="btn btn-info btn-sm hover-lift">
                            <i class="fas fa-eye me-1"></i> Voir tout
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th><i class="fas fa-book me-1"></i> Matière</th>
                                    <th><i class="fas fa-users me-1"></i> Classe</th>
                                    <th><i class="fas fa-calendar me-1"></i> Date</th>
                                    <th><i class="fas fa-cog me-1"></i> Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="me-3" style="width: 32px; height: 32px; background: linear-gradient(135deg, #6366f1, #818cf8); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-calculator text-white"></i>
                                            </div>
                                            <div>
                                                <div class="fw-semibold">Mathématiques</div>
                                                <small class="text-muted">Algèbre linéaire</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">1ère année Informatique</div>
                                        <small class="text-muted">25 étudiants</small>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">15/11/2023</div>
                                        <small class="text-muted">Dans 3 jours</small>
                                    </td>
                                    <td>
                                        <button class="btn btn-light btn-sm hover-lift" data-bs-toggle="tooltip" title="Voir les détails">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="me-3" style="width: 32px; height: 32px; background: linear-gradient(135deg, #10b981, #34d399); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-code text-white"></i>
                                            </div>
                                            <div>
                                                <div class="fw-semibold">Programmation Java</div>
                                                <small class="text-muted">POO avancée</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">2ème année Informatique</div>
                                        <small class="text-muted">18 étudiants</small>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">17/11/2023</div>
                                        <small class="text-muted">Dans 5 jours</small>
                                    </td>
                                    <td>
                                        <button class="btn btn-light btn-sm hover-lift" data-bs-toggle="tooltip" title="Voir les détails">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="me-3" style="width: 32px; height: 32px; background: linear-gradient(135deg, #f59e0b, #fbbf24); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-chart-line text-white"></i>
                                            </div>
                                            <div>
                                                <div class="fw-semibold">Analyse financière</div>
                                                <small class="text-muted">Ratios financiers</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">3ème année Comptabilité</div>
                                        <small class="text-muted">22 étudiants</small>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">18/11/2023</div>
                                        <small class="text-muted">Dans 6 jours</small>
                                    </td>
                                    <td>
                                        <button class="btn btn-light btn-sm hover-lift" data-bs-toggle="tooltip" title="Voir les détails">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="me-3" style="width: 32px; height: 32px; background: linear-gradient(135deg, #06b6d4, #22d3ee); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-hammer text-white"></i>
                                            </div>
                                            <div>
                                                <div class="fw-semibold">Résistance des matériaux</div>
                                                <small class="text-muted">Structures</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">2ème année Génie Civil</div>
                                        <small class="text-muted">20 étudiants</small>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">20/11/2023</div>
                                        <small class="text-muted">Dans 8 jours</small>
                                    </td>
                                    <td>
                                        <button class="btn btn-light btn-sm hover-lift" data-bs-toggle="tooltip" title="Voir les détails">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer text-center">
                    <a href="{{ route('esbtp.evaluations.index') }}" class="btn btn-light hover-lift">
                        <i class="fas fa-arrow-right me-1"></i> Voir tous les examens
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions Floating Panel -->
    <div class="position-fixed bottom-0 end-0 p-4" style="z-index: 1000;">
        <div class="d-flex flex-column gap-3">
            <button class="btn btn-primary btn-lg rounded-circle hover-lift animate-float" style="width: 60px; height: 60px;" data-bs-toggle="tooltip" data-bs-placement="left" title="Nouvelle inscription">
                <i class="fas fa-plus fa-lg"></i>
            </button>
            <button class="btn btn-success btn-lg rounded-circle hover-lift animate-float" style="width: 60px; height: 60px; animation-delay: 0.5s;" data-bs-toggle="tooltip" data-bs-placement="left" title="Messages">
                <i class="fas fa-envelope fa-lg"></i>
            </button>
            <button class="btn btn-info btn-lg rounded-circle hover-lift animate-float" style="width: 60px; height: 60px; animation-delay: 1s;" data-bs-toggle="tooltip" data-bs-placement="left" title="Notifications">
                <i class="fas fa-bell fa-lg"></i>
            </button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Modern Chart Configuration
    const chartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'top',
                labels: {
                    usePointStyle: true,
                    padding: 20,
                    font: {
                        family: 'Inter',
                        size: 12,
                        weight: '500'
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    color: 'rgba(255, 255, 255, 0.1)',
                    drawBorder: false
                },
                ticks: {
                    font: {
                        family: 'Inter',
                        size: 11
                    },
                    color: '#64748b'
                }
            },
            x: {
                grid: {
                    display: false
                },
                ticks: {
                    font: {
                        family: 'Inter',
                        size: 11
                    },
                    color: '#64748b'
                }
            }
        }
    };

    // Inscriptions Chart with Modern Gradient
    const inscriptionsCtx = document.getElementById('inscriptionsChart').getContext('2d');
    const inscriptionsChart = new Chart(inscriptionsCtx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc'],
            datasets: [
                {
                    label: 'Inscriptions',
                    data: [30, 40, 35, 50, 49, 60, 70, 91, 125, 85, 60, 45],
                    borderColor: '#6366f1',
                    backgroundColor: function(context) {
                        const chart = context.chart;
                        const {ctx, chartArea} = chart;
                        if (!chartArea) return null;

                        const gradient = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
                        gradient.addColorStop(0, 'rgba(99, 102, 241, 0.3)');
                        gradient.addColorStop(1, 'rgba(99, 102, 241, 0.05)');
                        return gradient;
                    },
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#6366f1',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 3,
                    pointRadius: 6,
                    pointHoverRadius: 8
                },
                {
                    label: 'Validations',
                    data: [20, 35, 30, 45, 40, 55, 65, 85, 115, 80, 50, 40],
                    borderColor: '#10b981',
                    backgroundColor: function(context) {
                        const chart = context.chart;
                        const {ctx, chartArea} = chart;
                        if (!chartArea) return null;

                        const gradient = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
                        gradient.addColorStop(0, 'rgba(16, 185, 129, 0.3)');
                        gradient.addColorStop(1, 'rgba(16, 185, 129, 0.05)');
                        return gradient;
                    },
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#10b981',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 3,
                    pointRadius: 6,
                    pointHoverRadius: 8
                }
            ]
        },
        options: chartOptions
    });

    // Payments Doughnut Chart with Modern Colors
    const paymentsCtx = document.getElementById('paymentsChart').getContext('2d');
    const paymentsChart = new Chart(paymentsCtx, {
        type: 'doughnut',
        data: {
            labels: ['Payé', 'En attente', 'En retard'],
            datasets: [{
                data: [45070000, 25400000, 7000000],
                backgroundColor: [
                    '#10b981',
                    '#f59e0b',
                    '#ef4444'
                ],
                borderWidth: 0,
                cutout: '70%'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        padding: 15,
                        font: {
                            family: 'Inter',
                            size: 11,
                            weight: '500'
                        }
                    }
                }
            }
        }
    });

    // Add smooth scroll behavior for floating buttons
    document.querySelectorAll('.animate-float').forEach((btn, index) => {
        btn.addEventListener('mouseenter', function() {
            this.style.animationPlayState = 'paused';
            this.style.transform = 'translateY(-5px) scale(1.1)';
        });

        btn.addEventListener('mouseleave', function() {
            this.style.animationPlayState = 'running';
            this.style.transform = '';
        });
    });

    // Add loading states for stat cards
    document.querySelectorAll('.stat-card').forEach(card => {
        card.addEventListener('click', function() {
            const icon = this.querySelector('.stat-card-icon i');
            const originalClass = icon.className;
            icon.className = 'fas fa-spinner fa-spin';

            setTimeout(() => {
                icon.className = originalClass;
            }, 1000);
        });
    });
});
</script>
@endsection

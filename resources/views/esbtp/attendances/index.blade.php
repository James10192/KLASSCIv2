@extends('layouts.app')

@section('title', 'Gestion des présences')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    .attendance-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: var(--space-xl);
        border-radius: var(--radius-large);
        margin-bottom: var(--space-xl);
        position: relative;
        overflow: hidden;
        box-shadow: var(--shadow-elevated);
    }
    
    .attendance-header::before {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 120px;
        height: 100%;
        background: rgba(255,255,255,0.15);
        transform: skewX(-15deg);
        transform-origin: top;
    }

    .page-title {
        font-size: 1.8rem;
        font-weight: 700;
        margin: 0;
        position: relative;
        z-index: 1;
        color: white;
        text-shadow: 0 2px 4px rgba(0,0,0,0.3);
    }

    .page-subtitle {
        opacity: 0.95;
        margin: var(--space-sm) 0 0;
        position: relative;
        z-index: 1;
        color: rgba(255,255,255,0.9);
        font-size: 1rem;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: var(--space-lg);
        margin-bottom: var(--space-xl);
    }

    .stat-card {
        background: var(--surface);
        border-radius: var(--radius-medium);
        padding: var(--space-lg);
        border: 1px solid rgba(0,0,0,0.05);
        transition: all 0.3s ease;
        cursor: pointer;
        box-shadow: var(--shadow-card);
        position: relative;
        overflow: hidden;
    }

    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 4px;
        height: 100%;
        transition: width 0.3s ease;
    }

    .stat-card:hover {
        box-shadow: var(--shadow-hover);
        transform: translateY(-2px);
    }

    .stat-card:hover::before {
        width: 8px;
    }

    .stat-card.present::before { background: var(--success); }
    .stat-card.absent::before { background: var(--danger); }
    .stat-card.late::before { background: var(--warning); }
    .stat-card.excused::before { background: var(--accent-blue); }

    .stat-card .stat-icon {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        margin-bottom: var(--space-md);
        color: white;
    }

    .stat-card .stat-number {
        font-size: 2.2rem;
        font-weight: bold;
        margin-bottom: var(--space-sm);
    }

    .stat-card .stat-label {
        color: var(--text-secondary);
        font-size: 0.9rem;
        margin: 0;
        font-weight: 500;
    }

    .stat-card .stat-percentage {
        font-size: 0.8rem;
        color: var(--text-muted);
        margin-top: var(--space-xs);
    }

    .icon-success { background: var(--success); }
    .icon-danger { background: var(--danger); }
    .icon-warning { background: var(--warning); }
    .icon-info { background: var(--accent-blue); }

    .filters-card, .data-card, .chart-card {
        background: var(--surface);
        border-radius: var(--radius-medium);
        padding: var(--space-lg);
        border: 1px solid rgba(0,0,0,0.05);
        box-shadow: var(--shadow-card);
        margin-bottom: var(--space-lg);
    }

    .chart-container {
        position: relative;
        height: 350px;
        width: 100%;
        overflow: visible;
    }

    #attendanceChart {
        max-height: 320px !important;
        height: 320px !important;
        width: 100% !important;
    }

    .table-modern {
        border-collapse: separate;
        border-spacing: 0;
        border-radius: var(--radius-medium);
        overflow: hidden;
        box-shadow: var(--shadow-card);
    }

    .table-modern thead {
        background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
        color: white;
    }

    .table-modern thead th {
        border: none;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.8rem;
        letter-spacing: 0.5px;
        padding: var(--space-md);
    }

    .table-modern tbody tr {
        transition: all 0.2s ease;
    }

    .table-modern tbody tr:hover {
        background: rgba(102, 126, 234, 0.05);
    }

    .table-modern tbody td {
        border: none;
        border-bottom: 1px solid rgba(0,0,0,0.05);
        padding: var(--space-md);
        vertical-align: middle;
    }

    .status-badge {
        display: inline-block;
        padding: 0.4rem 0.8rem;
        font-size: 0.8rem;
        font-weight: 600;
        line-height: 1;
        text-align: center;
        white-space: nowrap;
        border-radius: var(--radius-small);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .status-badge.present {
        background: var(--success);
        color: white;
    }

    .status-badge.absent {
        background: var(--danger);
        color: white;
    }

    .status-badge.late {
        background: var(--warning);
        color: var(--text-primary);
    }

    .status-badge.excused {
        background: var(--accent-blue);
        color: white;
    }

    .action-buttons {
        display: flex;
        gap: var(--space-sm);
    }

    .btn-modern {
        padding: 0.5rem 1rem;
        border-radius: var(--radius-small);
        border: none;
        font-weight: 500;
        transition: all 0.2s ease;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .btn-primary-modern {
        background: var(--primary);
        color: white;
    }

    .btn-primary-modern:hover {
        background: var(--secondary);
        transform: translateY(-1px);
        box-shadow: var(--shadow-hover);
    }

    .btn-info-modern {
        background: var(--accent-blue);
        color: white;
    }

    .btn-info-modern:hover {
        background: var(--accent-orange);
        transform: translateY(-1px);
    }

    .quick-actions {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: var(--space-md);
        margin-bottom: var(--space-xl);
    }

    .quick-action {
        background: var(--surface);
        border: 1px solid rgba(102, 126, 234, 0.2);
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
        border-color: var(--primary);
    }

    .quick-action .action-icon {
        font-size: 2rem;
        margin-bottom: var(--space-sm);
        color: var(--primary);
        transition: color 0.3s ease;
    }

    .quick-action:hover .action-icon {
        color: white;
    }

    /* Styles pour les statistiques coordinateur */
    .bg-gradient-primary { background: linear-gradient(45deg, #4e73df, #224abe); }
    .bg-gradient-success { background: linear-gradient(45deg, #1cc88a, #13855c); }
    .bg-gradient-warning { background: linear-gradient(45deg, #f6c23e, #dda20a); }
    .bg-gradient-info { background: linear-gradient(45deg, #36b9cc, #258391); }
    
    .text-white-50 { color: rgba(255, 255, 255, 0.7); }
    .text-white-75 { color: rgba(255, 255, 255, 0.85); }

    .card:hover {
        transform: translateY(-2px);
        transition: transform 0.2s ease-in-out;
    }

    /* Styles pour activités récentes et timeline */
    .timeline {
        position: relative;
        max-height: 400px;
        overflow-y: auto;
    }

    .timeline-item {
        position: relative;
        padding-left: 30px;
        margin-bottom: 20px;
    }

    .timeline-item::before {
        content: '';
        position: absolute;
        left: 8px;
        top: 0;
        bottom: -20px;
        width: 2px;
        background-color: #e3e6f0;
    }

    .timeline-icon {
        position: absolute;
        left: 0;
        top: 0;
        width: 16px;
        height: 16px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 8px;
        color: white;
    }

    .timeline-icon.success { background-color: #28a745; }
    .timeline-icon.warning { background-color: #ffc107; }

    /* Styles pour la section Présences par Classe optimisée */
    .classe-stats-container {
        max-height: 500px;
        overflow-y: auto;
        padding-right: 8px;
        margin-right: -8px;
    }

    .classe-stats-container::-webkit-scrollbar {
        width: 6px;
    }

    .classe-stats-container::-webkit-scrollbar-track {
        background: #f8f9fa;
        border-radius: 3px;
    }

    .classe-stats-container::-webkit-scrollbar-thumb {
        background: var(--primary);
        border-radius: 3px;
        transition: background 0.3s ease;
    }

    .classe-stats-container::-webkit-scrollbar-thumb:hover {
        background: var(--secondary);
    }

    .classe-item {
        border-radius: var(--radius-small);
        transition: all 0.3s ease;
        border: 1px solid transparent;
        padding: 12px;
        margin-bottom: 12px;
        background: rgba(255, 255, 255, 0.7);
    }

    .classe-item:hover {
        background: rgba(4, 83, 203, 0.05);
        border-color: rgba(4, 83, 203, 0.2);
        transform: translateX(4px);
        box-shadow: 0 2px 8px rgba(4, 83, 203, 0.1);
    }

    .classe-item.hidden {
        display: none !important;
    }

    .classe-name {
        color: var(--primary);
        font-size: 0.95rem;
    }

    .classe-students {
        font-size: 0.8rem;
    }

    .classe-rate {
        font-size: 0.8rem;
        font-weight: 600;
    }

    /* Vue compacte */
    .view-compact .classe-stats-details {
        display: none !important;
    }

    .view-compact .classe-stats-compact {
        display: block !important;
    }

    .view-compact .classe-item {
        margin-bottom: 8px;
        padding: 8px 12px;
    }

    .view-compact .classe-name {
        font-size: 0.9rem;
        margin-bottom: 0;
    }

    .view-compact .classe-students {
        font-size: 0.75rem;
    }

    /* Boutons de contrôle */
    .btn-group-sm .btn {
        padding: 4px 8px;
        font-size: 0.8rem;
        border-radius: 4px;
    }

    .btn-group-sm .btn.active {
        background-color: var(--primary);
        border-color: var(--primary);
        color: white;
    }

    /* Animation pour les éléments qui apparaissent */
    .classe-item {
        animation: slideInUp 0.3s ease-out;
    }

    @keyframes slideInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Badges compacts */
    .classe-stats-compact .badge {
        font-size: 0.7rem;
        padding: 2px 6px;
    }

    /* Message de recherche */
    .no-results {
        padding: 2rem 1rem;
    }

    /* Pagination */
    .classe-pagination {
        border-top: 1px solid rgba(0, 0, 0, 0.1);
        padding-top: 12px;
        margin-top: 16px;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .classe-stats-container {
            max-height: 400px;
        }
        
        .classe-item {
            padding: 8px;
        }
        
        .classe-stats-details .col-3 {
            flex: 0 0 50%;
            max-width: 50%;
            margin-bottom: 8px;
        }
        
        .classe-stats-details .row {
            margin-bottom: 8px;
        }
    }

    @media (max-width: 576px) {
        .classe-stats-container {
            max-height: 350px;
        }
        
        .classe-stats-details .col-3 {
            flex: 0 0 100%;
            max-width: 100%;
            margin-bottom: 4px;
        }
        
        .classe-stats-details .text-center {
            text-align: left !important;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
    }
    .timeline-icon.info { background-color: #17a2b8; }
    .timeline-icon.danger { background-color: #dc3545; }

    /* Styles pour section coordinateur */
    .coordinator-section {
        margin-bottom: var(--space-xl);
        border-radius: var(--radius-large);
        overflow: hidden;
    }

    .coordinator-section .card-header {
        background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
        border: none;
        padding: var(--space-lg);
    }

    .coordinator-actions .btn {
        transition: all 0.3s ease;
        border-radius: var(--radius-medium);
        box-shadow: var(--shadow-card);
    }

    .coordinator-actions .btn:hover {
        transform: translateY(-3px);
        box-shadow: var(--shadow-hover);
    }

    .alert-sm {
        padding: 0.5rem;
        font-size: 0.875rem;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .coordinator-actions .col-md-3 {
            margin-bottom: var(--space-md);
        }
        
        .timeline {
            max-height: 300px;
        }
        
        .stats-grid {
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        }
    }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header Section -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1><i class="fas fa-users-class me-2"></i>Gestion des Présences</h1>
                <p class="header-subtitle">Suivi et analyse des présences étudiantes en temps réel</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('esbtp.attendances.create') }}" class="btn-acasi primary me-2">
                    <i class="fas fa-plus-circle"></i>Marquer Présences
                </a>
                <a href="{{ route('esbtp.attendances.rapport-form') }}" class="btn-acasi secondary">
                    <i class="fas fa-chart-bar"></i>Générer Rapport
                </a>
            </div>
        </div>

        <!-- Statistiques principales -->
        <div class="kpi-grid mb-4">
            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Étudiants Présents</div>
                <div class="kpi-value" style="color: var(--primary); font-size: 2.5rem; font-weight: bold;">{{ $stats['present'] ?? 0 }}</div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-user-check"></i>
                    {{ ($stats['total'] ?? 0) > 0 ? round(($stats['present'] ?? 0) / $stats['total'] * 100, 1) : 0 }}% du total
                </div>
            </div>

            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Étudiants Absents</div>
                <div class="kpi-value" style="color: var(--primary); font-size: 2.5rem; font-weight: bold;">{{ $stats['absent'] ?? 0 }}</div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-user-times"></i>
                    {{ ($stats['total'] ?? 0) > 0 ? round(($stats['absent'] ?? 0) / $stats['total'] * 100, 1) : 0 }}% du total
                </div>
            </div>

            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Retards</div>
                <div class="kpi-value" style="color: var(--primary); font-size: 2.5rem; font-weight: bold;">{{ $stats['retard'] ?? 0 }}</div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-clock"></i>
                    {{ ($stats['total'] ?? 0) > 0 ? round(($stats['retard'] ?? 0) / $stats['total'] * 100, 1) : 0 }}% du total
                </div>
            </div>

            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Excusés</div>
                <div class="kpi-value" style="color: var(--primary); font-size: 2.5rem; font-weight: bold;">{{ $stats['excuse'] ?? 0 }}</div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-notes-medical"></i>
                    {{ ($stats['total'] ?? 0) > 0 ? round(($stats['excuse'] ?? 0) / $stats['total'] * 100, 1) : 0 }}% du total
                </div>
            </div>
        </div>

        @if(auth()->user() && auth()->user()->hasRole('coordinateur') && $coordinatorStats)
        <!-- Section Coordinateur - Suivi Enseignants -->
        <div class="main-card mb-4">
            <div class="main-card-header" style="background: linear-gradient(135deg, rgba(30, 58, 138, 0.1), rgba(30, 64, 175, 0.05));">
                <div class="main-card-title">
                    <i class="fas fa-chalkboard-teacher"></i>
                    Suivi des Émargements Enseignants - Aujourd'hui
                </div>
                <div class="main-card-subtitle">Supervision en temps réel de l'activité pédagogique</div>
                @if($unreadNotifications > 0)
                <div class="ms-auto">
                    <a href="{{ route('notifications.index') }}" class="btn-acasi warning btn-sm">
                        <i class="fas fa-bell"></i>{{ $unreadNotifications }} notifications
                    </a>
                </div>
                @endif
            </div>
            <div class="main-card-body">
                <div class="kpi-grid">
                    <!-- Émargements du jour -->
                    <div class="kpi-card card-moderne bg-primary">
                        <div class="kpi-title">Émargements</div>
                        <div class="kpi-value color-primary">{{ $coordinatorStats['teacher_attendances_today'] ?? 0 }}</div>
                        <div class="kpi-trend">
                            <i class="fas fa-clipboard-check"></i>
                            sur {{ $coordinatorStats['scheduled_courses_today'] ?? 0 }} cours
                        </div>
                        <div class="progress mt-3" style="height: 6px;">
                            <div class="progress-bar bg-white" role="progressbar" 
                                 style="width: {{ $coordinatorStats['teacher_attendance_rate'] ?? 0 }}%">
                            </div>
                        </div>
                    </div>

                    <!-- Appels terminés -->
                    <div class="kpi-card card-moderne bg-success">
                        <div class="kpi-title">Appels Terminés</div>
                        <div class="kpi-value color-success">{{ $coordinatorStats['roll_calls_completed_today'] ?? 0 }}</div>
                        <div class="kpi-trend">
                            <i class="fas fa-users-check"></i>
                            {{ $coordinatorStats['students_present_today'] ?? 0 }} présents
                        </div>
                        <div class="progress mt-3" style="height: 6px;">
                            <div class="progress-bar bg-white" role="progressbar" 
                                 style="width: {{ $coordinatorStats['roll_call_completion_rate'] ?? 0 }}%">
                            </div>
                        </div>
                    </div>

                    <!-- Retards détectés -->
                    <div class="kpi-card card-moderne bg-warning">
                        <div class="kpi-title">Retards Détectés</div>
                        <div class="kpi-value color-warning">{{ $coordinatorStats['delays_today'] ?? 0 }}</div>
                        <div class="kpi-trend">
                            <i class="fas fa-clock"></i>
                            émargements manqués
                            @if(($coordinatorStats['delays_today'] ?? 0) > 0)
                                <div class="mt-2">
                                    <small>⚠️ Attention requise</small>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Cours clôturés -->
                    <div class="kpi-card card-moderne bg-accent">
                        <div class="kpi-title">Cours Clôturés</div>
                        <div class="kpi-value color-accent">{{ $coordinatorStats['courses_closed_today'] ?? 0 }}</div>
                        <div class="kpi-trend">
                            <i class="fas fa-check-circle"></i>
                            séances terminées
                        </div>
                    </div>
                </div>

                    <!-- Alertes coordinateur -->
                    @if(($coordinatorStats['delays_today'] ?? 0) > 0 || ($coordinatorStats['high_absence_classes'] ?? 0) > 0)
                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="alert-section">
                                <h6 class="text-muted mb-3">🔔 Alertes du jour</h6>
                                
                                @if(($coordinatorStats['delays_today'] ?? 0) > 0)
                                <div class="alert alert-warning border-0 shadow-sm mb-2">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        <div>
                                            <strong>{{ $coordinatorStats['delays_today'] }} retard(s) d'émargement</strong>
                                            <p class="mb-0 small">Des enseignants n'ont pas émargé à temps</p>
                                        </div>
                                    </div>
                                </div>
                                @endif

                                @if(($coordinatorStats['high_absence_classes'] ?? 0) > 0)
                                <div class="alert alert-danger border-0 shadow-sm mb-2">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-users-slash me-2"></i>
                                        <div>
                                            <strong>{{ $coordinatorStats['high_absence_classes'] }} classe(s) avec forte absentéisme</strong>
                                            <p class="mb-0 small">Plus de 30% d'absences détectées</p>
                                        </div>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Actions rapides coordinateur -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <h6 class="text-muted mb-3">🚀 Actions Rapides Coordinateur</h6>
                            <div class="row coordinator-actions">
                                <div class="col-md-3 mb-3">
                                    <a href="{{ route('esbtp.teacher-attendance.report') }}" class="btn btn-outline-primary w-100 h-100 d-flex flex-column justify-content-center p-3">
                                        <i class="fas fa-chalkboard-teacher fa-2x mb-2"></i>
                                        <span class="fw-bold">Émargements</span>
                                        <small class="text-muted">Voir tous les enseignants</small>
                                    </a>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <a href="{{ route('notifications.index') }}" class="btn btn-outline-info w-100 h-100 d-flex flex-column justify-content-center p-3">
                                        <i class="fas fa-bell fa-2x mb-2"></i>
                                        <span class="fw-bold">Notifications</span>
                                        <small class="text-muted">{{ $unreadNotifications ?? 0 }} non lues</small>
                                    </a>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <button class="btn btn-outline-warning w-100 h-100 d-flex flex-column justify-content-center p-3" onclick="generateDailyReport()">
                                        <i class="fas fa-chart-line fa-2x mb-2"></i>
                                        <span class="fw-bold">Rapport du Jour</span>
                                        <small class="text-muted">Générer le récap</small>
                                    </button>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <button class="btn btn-outline-success w-100 h-100 d-flex flex-column justify-content-center p-3" onclick="refreshData()">
                                        <i class="fas fa-sync-alt fa-2x mb-2"></i>
                                        <span class="fw-bold">Actualiser</span>
                                        <small class="text-muted">Données en temps réel</small>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Actions rapides -->
    <div class="quick-actions">
        <a href="{{ route('esbtp.attendances.create') }}" class="quick-action">
            <div class="action-icon">
                <i class="fas fa-plus-circle"></i>
            </div>
            <div class="action-title">Marquer Présences</div>
            <div class="action-description">Enregistrer les présences</div>
        </a>

        <a href="{{ route('esbtp.attendances.rapport-form') }}" class="quick-action">
            <div class="action-icon">
                <i class="fas fa-chart-bar"></i>
            </div>
            <div class="action-title">Générer Rapport</div>
            <div class="action-description">Analyser les données</div>
        </a>

        <a href="#" class="quick-action" onclick="exportData()">
            <div class="action-icon">
                <i class="fas fa-file-export"></i>
            </div>
            <div class="action-title">Exporter Données</div>
            <div class="action-description">CSV, Excel, PDF</div>
        </a>

        <a href="#" class="quick-action" onclick="showStatistics()">
            <div class="action-icon">
                <i class="fas fa-analytics"></i>
            </div>
            <div class="action-title">Statistiques</div>
            <div class="action-description">Analyse approfondie</div>
        </a>
    </div>

    <div class="row">
        <!-- Graphique des tendances -->
        <div class="col-lg-8">
            <div class="chart-card">
                <h5 class="mb-3">
                    <i class="fas fa-line-chart me-2 text-primary"></i>
                    Tendance des 7 Derniers Jours
                </h5>
                <div class="chart-container">
                    <canvas id="attendanceChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Statistiques par classe -->
        <div class="col-lg-4">
            <div class="data-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">
                        <i class="fas fa-graduation-cap me-2 text-primary"></i>
                        Présences par Classe
                    </h5>
                    <div class="d-flex align-items-center gap-2">
                        <small class="text-muted">{{ count($classeStats ?? []) }} classes</small>
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-outline-secondary" id="compactViewBtn" title="Vue compacte">
                                <i class="fas fa-th-list"></i>
                            </button>
                            <button type="button" class="btn btn-outline-secondary active" id="detailedViewBtn" title="Vue détaillée">
                                <i class="fas fa-th-large"></i>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Recherche/Filtre -->
                @if(isset($classeStats) && count($classeStats) > 5)
                <div class="mb-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control" id="classSearchInput" placeholder="Rechercher une classe...">
                        <button class="btn btn-outline-secondary" type="button" id="clearSearchBtn">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                @endif
                
                @if(isset($classeStats) && count($classeStats) > 0)
                    <!-- Container avec scroll -->
                    <div class="classe-stats-container" id="classeStatsContainer">
                        <div class="classe-stats-content" id="classeStatsContent">
                            @foreach($classeStats as $index => $classe)
                        <div class="classe-item mb-4" data-classe-name="{{ strtolower($classe['name']) }}" data-index="{{ $index }}">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div class="classe-info">
                                    <h6 class="fw-bold mb-1 classe-name">{{ $classe['name'] }}</h6>
                                    <small class="text-muted classe-students">{{ $classe['total_students'] }} étudiants</small>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-primary classe-rate">{{ $classe['attendance_rate'] }}%</span>
                                </div>
                            </div>
                            
                            <!-- Barres de progression pour chaque statut -->
                            <div class="row g-1 mb-2 classe-stats-details">
                                <div class="col-3">
                                    <div class="text-center">
                                        <div class="fw-bold text-success">{{ $classe['present'] }}</div>
                                        <small class="text-muted">Présents</small>
                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="text-center">
                                        <div class="fw-bold text-danger">{{ $classe['absent'] }}</div>
                                        <small class="text-muted">Absents</small>
                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="text-center">
                                        <div class="fw-bold text-warning">{{ $classe['retard'] }}</div>
                                        <small class="text-muted">Retards</small>
                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="text-center">
                                        <div class="fw-bold text-info">{{ $classe['excuse'] }}</div>
                                        <small class="text-muted">Excusés</small>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Vue compacte (cachée par défaut) -->
                            <div class="classe-stats-compact d-none">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="d-flex gap-3">
                                        <span class="badge bg-success">{{ $classe['present'] }}P</span>
                                        <span class="badge bg-danger">{{ $classe['absent'] }}A</span>
                                        <span class="badge bg-warning">{{ $classe['retard'] }}R</span>
                                        <span class="badge bg-info">{{ $classe['excuse'] }}E</span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Barre de progression globale -->
                            <div class="progress" style="height: 8px;">
                                @if($classe['total_attendance'] > 0)
                                    <div class="progress-bar bg-success" style="width: {{ ($classe['present'] / $classe['total_attendance']) * 100 }}%"></div>
                                    <div class="progress-bar bg-warning" style="width: {{ ($classe['retard'] / $classe['total_attendance']) * 100 }}%"></div>
                                    <div class="progress-bar bg-info" style="width: {{ ($classe['excuse'] / $classe['total_attendance']) * 100 }}%"></div>
                                    <div class="progress-bar bg-danger" style="width: {{ ($classe['absent'] / $classe['total_attendance']) * 100 }}%"></div>
                                @else
                                    <div class="progress-bar bg-secondary" style="width: 100%"></div>
                                @endif
                            </div>
                        </div>
                        @endforeach
                        </div>
                        
                        <!-- Pagination si trop de classes -->
                        @if(count($classeStats) > 10)
                        <div class="classe-pagination text-center mt-3" id="classePagination">
                            <button class="btn btn-sm btn-outline-primary" id="loadMoreClassesBtn">
                                <i class="fas fa-chevron-down me-1"></i>
                                Voir plus de classes
                            </button>
                        </div>
                        @endif
                        
                        <!-- Message si aucun résultat de recherche -->
                        <div class="no-results d-none text-center text-muted py-4" id="noSearchResults">
                            <i class="fas fa-search fa-2x mb-3 opacity-50"></i>
                            <p>Aucune classe trouvée</p>
                            <p class="small">Essayez un autre terme de recherche</p>
                        </div>
                    </div>
                @else
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-chart-pie fa-3x mb-3 opacity-50"></i>
                        <p>Aucune donnée de présence</p>
                        <p class="small">Les statistiques apparaîtront une fois les présences enregistrées</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if(auth()->user() && auth()->user()->hasRole('coordinateur') && $coordinatorStats)
    <!-- Activités récentes coordinateur -->
    <div class="row mb-4">
        <div class="col-xl-8 col-lg-7 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">📝 Activités Récentes</h5>
                    <small class="text-muted">Dernières 24h</small>
                </div>
                <div class="card-body">
                    <div class="timeline" id="recent-activities">
                        <!-- Les activités seront chargées via JavaScript -->
                        <div class="text-center py-4">
                            <div class="spinner-border spinner-border-sm text-primary" role="status">
                                <span class="visually-hidden">Chargement...</span>
                            </div>
                            <p class="mt-2 text-muted">Chargement des activités récentes...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Résumé quotidien -->
        <div class="col-xl-4 col-lg-5 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0">📊 Résumé du Jour</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">Cours prévus:</span>
                            <span class="fw-bold">{{ $coordinatorStats['scheduled_courses_today'] ?? 0 }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">Émargements:</span>
                            <span class="fw-bold text-primary">{{ $coordinatorStats['teacher_attendances_today'] ?? 0 }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">Taux émargement:</span>
                            <span class="fw-bold text-success">{{ $coordinatorStats['teacher_attendance_rate'] ?? 0 }}%</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">Appels terminés:</span>
                            <span class="fw-bold text-info">{{ $coordinatorStats['roll_calls_completed_today'] ?? 0 }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">Étudiants présents:</span>
                            <span class="fw-bold text-success">{{ $coordinatorStats['students_present_today'] ?? 0 }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">Cours clôturés:</span>
                            <span class="fw-bold">{{ $coordinatorStats['courses_closed_today'] ?? 0 }}</span>
                        </div>
                        @if(($coordinatorStats['delays_today'] ?? 0) > 0)
                        <div class="alert alert-warning alert-sm mb-0">
                            <small><strong>{{ $coordinatorStats['delays_today'] }} retard(s)</strong> détecté(s)</small>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Filtres -->
    <div class="filters-card">
        <h5 class="mb-3">
            <i class="fas fa-filter me-2"></i>
            Filtres de Recherche
        </h5>
        <form action="{{ route('esbtp.attendances.index') }}" method="GET">
            <div class="row g-3">
                <div class="col-md-2">
                    <label for="classe_id" class="form-label fw-bold">Classe</label>
                    <select name="classe_id" id="classe_id" class="form-select">
                        <option value="">Toutes les classes</option>
                        @foreach($classes as $classe)
                            <option value="{{ $classe->id }}" {{ request('classe_id') == $classe->id ? 'selected' : '' }}>
                                {{ $classe->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="matiere_id" class="form-label fw-bold">Matière</label>
                    <select name="matiere_id" id="matiere_id" class="form-select">
                        <option value="">Toutes les matières</option>
                        @foreach($matieres as $matiere)
                            <option value="{{ $matiere->id }}" {{ request('matiere_id') == $matiere->id ? 'selected' : '' }}>
                                {{ $matiere->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="etudiant_id" class="form-label fw-bold">Étudiant</label>
                    <select name="etudiant_id" id="etudiant_id" class="form-select">
                        <option value="">Tous les étudiants</option>
                        @foreach($etudiants as $etudiant)
                            <option value="{{ $etudiant->id }}" {{ request('etudiant_id') == $etudiant->id ? 'selected' : '' }}>
                                {{ $etudiant->nom_complet }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="date_debut" class="form-label fw-bold">Date début</label>
                    <input type="date" class="form-control" id="date_debut" name="date_debut"
                           value="{{ request('date_debut') }}">
                </div>

                <div class="col-md-2">
                    <label for="date_fin" class="form-label fw-bold">Date fin</label>
                    <input type="date" class="form-control" id="date_fin" name="date_fin"
                           value="{{ request('date_fin') }}">
                </div>

                <div class="col-md-2">
                    <label for="statut" class="form-label fw-bold">Statut</label>
                    <select name="statut" id="statut" class="form-select">
                        <option value="">Tous les statuts</option>
                        <option value="present" {{ request('statut') == 'present' ? 'selected' : '' }}>Présent</option>
                        <option value="absent" {{ request('statut') == 'absent' ? 'selected' : '' }}>Absent</option>
                        <option value="retard" {{ request('statut') == 'retard' ? 'selected' : '' }}>Retard</option>
                        <option value="excuse" {{ request('statut') == 'excuse' ? 'selected' : '' }}>Excusé</option>
                    </select>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-12">
                    <button type="submit" class="btn-modern btn-primary-modern me-2">
                        <i class="fas fa-search"></i>
                        Filtrer
                    </button>
                    <a href="{{ route('esbtp.attendances.index') }}" class="btn-modern btn-info-modern">
                        <i class="fas fa-refresh"></i>
                        Réinitialiser
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Table des données -->
    <div class="data-card">
        <h5 class="mb-3">
            <i class="fas fa-table me-2"></i>
            Liste des Présences
            <span class="badge bg-primary ms-2">{{ $attendances->count() }} enregistrements</span>
        </h5>

        <div class="table-responsive">
            <table class="table table-modern">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Étudiant</th>
                        <th>Classe</th>
                        <th>Matière</th>
                        <th>Statut</th>
                        <th>Enseignant</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($attendances as $attendance)
                        <tr>
                            <td>
                                <div class="fw-bold">{{ $attendance->date->format('d/m/Y') }}</div>
                                <div class="small text-muted">{{ $attendance->created_at->format('H:i') }}</div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-initial rounded-circle bg-primary text-white me-2">
                                        {{ substr($attendance->etudiant->nom_complet, 0, 2) }}
                                    </div>
                                    <div>
                                        <div class="fw-bold">{{ $attendance->etudiant->nom_complet }}</div>
                                        <div class="small text-muted">#{{ $attendance->etudiant->id }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark">
                                    {{ $attendance->classe->name ?? ($attendance->etudiant->classe->name ?? 'N/A') }}
                                </span>
                            </td>
                            <td>{{ $attendance->matiere->name ?? ($attendance->seanceCours->matiere->name ?? 'N/A') }}</td>
                            <td>
                                @if($attendance->statut === 'present')
                                    <span class="status-badge present">Présent</span>
                                @elseif($attendance->statut === 'absent')
                                    <span class="status-badge absent">Absent</span>
                                @elseif($attendance->statut === 'retard' || $attendance->statut === 'late')
                                    <span class="status-badge late">Retard</span>
                                @elseif($attendance->statut === 'excuse')
                                    <span class="status-badge excused">Excusé</span>
                                @else
                                    <span class="status-badge bg-secondary text-white">{{ ucfirst($attendance->statut) }}</span>
                                @endif
                            </td>
                            <td>
                                <div class="small">
                                    {{ $attendance->teacher->user->name ?? 'N/A' }}
                                </div>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#detailsModal{{ $attendance->id }}">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <a href="{{ route('esbtp.attendances.edit', $attendance) }}" 
                                       class="btn btn-sm btn-outline-warning">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="fas fa-user-times fa-3x mb-3 opacity-50"></i>
                                    <h6>Aucune présence enregistrée</h6>
                                    <p class="mb-0">Commencez par marquer les présences des étudiants</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($attendances->hasPages())
            <div class="d-flex justify-content-center mt-4">
                {{ $attendances->appends(request()->query())->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Modales de détails -->
@foreach($attendances as $attendance)
    <div class="modal fade" id="detailsModal{{ $attendance->id }}" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Détails de la Présence</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <dl class="row">
                        <dt class="col-sm-4">Étudiant</dt>
                        <dd class="col-sm-8">{{ $attendance->etudiant->nom_complet }}</dd>

                        <dt class="col-sm-4">Classe</dt>
                        <dd class="col-sm-8">{{ $attendance->classe->name ?? ($attendance->etudiant->classe->name ?? 'N/A') }}</dd>

                        <dt class="col-sm-4">Matière</dt>
                        <dd class="col-sm-8">{{ $attendance->matiere->name ?? ($attendance->seanceCours->matiere->name ?? 'N/A') }}</dd>

                        <dt class="col-sm-4">Date</dt>
                        <dd class="col-sm-8">{{ $attendance->date->format('d/m/Y') }}</dd>

                        <dt class="col-sm-4">Statut</dt>
                        <dd class="col-sm-8">
                            @if($attendance->statut === 'present')
                                <span class="status-badge present">Présent</span>
                            @elseif($attendance->statut === 'absent')
                                <span class="status-badge absent">Absent</span>
                            @elseif($attendance->statut === 'retard' || $attendance->statut === 'late')
                                <span class="status-badge late">Retard</span>
                            @elseif($attendance->statut === 'excuse')
                                <span class="status-badge excused">Excusé</span>
                            @endif
                        </dd>

                        <dt class="col-sm-4">Enseignant</dt>
                        <dd class="col-sm-8">{{ $attendance->teacher->user->name ?? 'N/A' }}</dd>

                        <dt class="col-sm-4">Créé le</dt>
                        <dd class="col-sm-8">{{ $attendance->created_at->format('d/m/Y H:i') }}</dd>
                    </dl>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>
@endforeach
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Graphique des tendances
const ctx = document.getElementById('attendanceChart').getContext('2d');
const attendanceChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: {!! json_encode(array_map(function($date) { 
            return \Carbon\Carbon::parse($date)->format('d/m'); 
        }, array_keys($statsParStatus ?? []))) !!},
        datasets: [{
            label: 'Présents',
            data: {!! json_encode(array_column($statsParStatus ?? [], 'present')) !!},
            borderColor: '#10b981',
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.4
        }, {
            label: 'Absents',
            data: {!! json_encode(array_column($statsParStatus ?? [], 'absent')) !!},
            borderColor: '#ef4444',
            backgroundColor: 'rgba(239, 68, 68, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.4
        }, {
            label: 'Retards',
            data: {!! json_encode(array_column($statsParStatus ?? [], 'retard')) !!},
            borderColor: '#f59e0b',
            backgroundColor: 'rgba(245, 158, 11, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.4
        }, {
            label: 'Excusés',
            data: {!! json_encode(array_column($statsParStatus ?? [], 'excuse')) !!},
            borderColor: '#06b6d4',
            backgroundColor: 'rgba(6, 182, 212, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.4
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
                beginAtZero: true,
                grid: {
                    color: 'rgba(0,0,0,0.1)'
                }
            },
            x: {
                grid: {
                    display: false
                }
            }
        }
    }
});

// Fonctions des actions rapides
function exportData() {
    alert('Fonction d\'export en cours de développement');
}

function showStatistics() {
    alert('Page de statistiques détaillées en cours de développement');
}

@if(auth()->user() && auth()->user()->hasRole('coordinateur') && $coordinatorStats)
// Fonctions coordinateur
function refreshData() {
    location.reload();
}

function generateDailyReport() {
    const loadingBtn = event.target;
    const originalText = loadingBtn.innerHTML;
    
    loadingBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Génération...';
    loadingBtn.disabled = true;
    
    fetch('{{ route("coordinateur.daily-report") ?? "#" }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            date: new Date().toISOString().split('T')[0]
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showDailyReportModal(data.report);
        } else {
            alert('Erreur lors de la génération du rapport: ' + (data.error || 'Erreur inconnue'));
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur de connexion lors de la génération du rapport');
    })
    .finally(() => {
        loadingBtn.innerHTML = originalText;
        loadingBtn.disabled = false;
    });
}

function showDailyReportModal(report) {
    // Simple alert pour l'instant
    alert('Rapport du ' + report.date + ':\n\n' +
          'Cours prévus: ' + report.summary.cours_prevus + '\n' +
          'Émargements: ' + report.summary.emargements_effectues + '\n' +
          'Taux: ' + report.summary.taux_emargement + '\n' +
          'Appels terminés: ' + report.summary.appels_termines);
}

// Charger les activités récentes
document.addEventListener('DOMContentLoaded', function() {
    loadRecentActivities();
    
    // Actualisation automatique toutes les 5 minutes
    setInterval(function() {
        loadRecentActivities();
    }, 300000);
});

function loadRecentActivities() {
    const activitiesContainer = document.getElementById('recent-activities');
    if (!activitiesContainer) return;
    
    fetch('{{ route("coordinateur.recent-activities") ?? "#" }}?limit=10', {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.activities) {
            let html = '';
            
            if (data.activities.length === 0) {
                html = `
                    <div class="text-center py-4">
                        <i class="fas fa-history fa-2x text-muted mb-2"></i>
                        <p class="text-muted">Aucune activité récente</p>
                    </div>
                `;
            } else {
                data.activities.forEach(activity => {
                    html += `
                        <div class="timeline-item">
                            <div class="timeline-icon ${activity.type}">
                                <i class="fas fa-${activity.icon}"></i>
                            </div>
                            <div class="timeline-content">
                                <h6 class="mb-1">${activity.title}</h6>
                                <p class="text-muted mb-1">${activity.description}</p>
                                <small class="text-muted">${activity.time}</small>
                            </div>
                        </div>
                    `;
                });
            }
            
            activitiesContainer.innerHTML = html;
        } else {
            activitiesContainer.innerHTML = `
                <div class="text-center py-4 text-danger">
                    <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                    <p>Erreur lors du chargement des activités</p>
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('Erreur chargement activités:', error);
        if (activitiesContainer) {
            activitiesContainer.innerHTML = `
                <div class="text-center py-4 text-warning">
                    <i class="fas fa-wifi fa-2x mb-2"></i>
                    <p>Impossible de charger les activités</p>
                    <button class="btn btn-sm btn-outline-primary" onclick="loadRecentActivities()">Réessayer</button>
                </div>
            `;
        }
    });
}
@endif

// Script pour la gestion optimisée des statistiques par classe
document.addEventListener('DOMContentLoaded', function() {
    // Éléments du DOM
    const compactViewBtn = document.getElementById('compactViewBtn');
    const detailedViewBtn = document.getElementById('detailedViewBtn');
    const classSearchInput = document.getElementById('classSearchInput');
    const clearSearchBtn = document.getElementById('clearSearchBtn');
    const classeStatsContainer = document.getElementById('classeStatsContainer');
    const classeStatsContent = document.getElementById('classeStatsContent');
    const loadMoreBtn = document.getElementById('loadMoreClassesBtn');
    const noSearchResults = document.getElementById('noSearchResults');

    // Variables de configuration
    let currentView = 'detailed'; // 'detailed' ou 'compact'
    let visibleItemsCount = 10; // Nombre d'éléments visibles initialement
    let allItems = [];
    let filteredItems = [];

    // Initialisation
    if (classeStatsContent) {
        allItems = Array.from(classeStatsContent.querySelectorAll('.classe-item'));
        filteredItems = [...allItems];
        initializePagination();
    }

    // Gestion des vues (compacte/détaillée)
    if (compactViewBtn && detailedViewBtn) {
        compactViewBtn.addEventListener('click', function() {
            switchToCompactView();
        });

        detailedViewBtn.addEventListener('click', function() {
            switchToDetailedView();
        });
    }

    // Gestion de la recherche
    if (classSearchInput) {
        classSearchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            filterClasses(searchTerm);
        });

        classSearchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
            }
        });
    }

    // Bouton de nettoyage de recherche
    if (clearSearchBtn) {
        clearSearchBtn.addEventListener('click', function() {
            classSearchInput.value = '';
            filterClasses('');
            classSearchInput.focus();
        });
    }

    // Bouton "Voir plus"
    if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', function() {
            showMoreClasses();
        });
    }

    // Fonctions principales
    function switchToCompactView() {
        currentView = 'compact';
        compactViewBtn.classList.add('active');
        detailedViewBtn.classList.remove('active');
        
        if (classeStatsContainer) {
            classeStatsContainer.classList.add('view-compact');
        }
        
        // Augmenter le nombre d'éléments visibles en mode compact
        visibleItemsCount = Math.min(20, filteredItems.length);
        updateVisibility();
        
        // Animation douce
        animateViewChange();
    }

    function switchToDetailedView() {
        currentView = 'detailed';
        detailedViewBtn.classList.add('active');
        compactViewBtn.classList.remove('active');
        
        if (classeStatsContainer) {
            classeStatsContainer.classList.remove('view-compact');
        }
        
        // Réduire le nombre d'éléments visibles en mode détaillé
        visibleItemsCount = Math.min(10, filteredItems.length);
        updateVisibility();
        
        // Animation douce
        animateViewChange();
    }

    function filterClasses(searchTerm) {
        if (!searchTerm) {
            // Afficher tous les éléments
            filteredItems = [...allItems];
            hideNoResults();
        } else {
            // Filtrer les éléments
            filteredItems = allItems.filter(item => {
                const className = item.dataset.classeName || '';
                const classeNameElement = item.querySelector('.classe-name');
                const classText = classeNameElement ? classeNameElement.textContent.toLowerCase() : '';
                
                return className.includes(searchTerm) || classText.includes(searchTerm);
            });
        }

        // Réinitialiser la pagination
        visibleItemsCount = currentView === 'compact' ? 20 : 10;
        updateVisibility();
        
        // Afficher le message "Aucun résultat" si nécessaire
        if (filteredItems.length === 0 && searchTerm) {
            showNoResults();
        } else {
            hideNoResults();
        }

        // Scroll vers le haut du conteneur
        if (classeStatsContainer) {
            classeStatsContainer.scrollTop = 0;
        }
    }

    function showMoreClasses() {
        const increment = currentView === 'compact' ? 20 : 10;
        visibleItemsCount = Math.min(visibleItemsCount + increment, filteredItems.length);
        updateVisibility();

        // Animation pour les nouveaux éléments
        const newlyVisibleItems = filteredItems.slice(visibleItemsCount - increment, visibleItemsCount);
        newlyVisibleItems.forEach((item, index) => {
            setTimeout(() => {
                item.style.animation = 'slideInUp 0.3s ease-out';
            }, index * 50);
        });
    }

    function updateVisibility() {
        // Masquer tous les éléments d'abord
        allItems.forEach(item => {
            item.classList.add('hidden');
        });

        // Afficher les éléments filtrés et visibles
        const itemsToShow = filteredItems.slice(0, visibleItemsCount);
        itemsToShow.forEach(item => {
            item.classList.remove('hidden');
        });

        // Gérer le bouton "Voir plus"
        if (loadMoreBtn) {
            if (visibleItemsCount < filteredItems.length) {
                loadMoreBtn.style.display = 'block';
                loadMoreBtn.innerHTML = `
                    <i class="fas fa-chevron-down me-1"></i>
                    Voir plus (${filteredItems.length - visibleItemsCount} restantes)
                `;
            } else {
                loadMoreBtn.style.display = 'none';
            }
        }
    }

    function initializePagination() {
        visibleItemsCount = Math.min(currentView === 'compact' ? 20 : 10, filteredItems.length);
        updateVisibility();
    }

    function showNoResults() {
        if (noSearchResults) {
            noSearchResults.classList.remove('d-none');
        }
        if (loadMoreBtn) {
            loadMoreBtn.style.display = 'none';
        }
    }

    function hideNoResults() {
        if (noSearchResults) {
            noSearchResults.classList.add('d-none');
        }
    }

    function animateViewChange() {
        const visibleItems = filteredItems.slice(0, visibleItemsCount);
        visibleItems.forEach((item, index) => {
            item.style.animation = 'none';
            setTimeout(() => {
                item.style.animation = 'slideInUp 0.3s ease-out';
            }, index * 20);
        });
    }

    // Gestion du redimensionnement de fenêtre
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => {
            // Ajuster le nombre d'éléments visibles selon la taille d'écran
            if (window.innerWidth < 768) {
                if (currentView === 'detailed') {
                    visibleItemsCount = Math.min(8, filteredItems.length);
                } else {
                    visibleItemsCount = Math.min(15, filteredItems.length);
                }
                updateVisibility();
            }
        }, 250);
    });

    // Amélioration UX: focus automatique sur la recherche avec Ctrl+K
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            if (classSearchInput) {
                classSearchInput.focus();
                classSearchInput.select();
            }
        }
    });

    // Précharger les statistiques au hover pour une meilleure réactivité
    if (classeStatsContent) {
        const classeItems = classeStatsContent.querySelectorAll('.classe-item');
        classeItems.forEach(item => {
            item.addEventListener('mouseenter', function() {
                // Petite animation au hover
                this.style.transition = 'all 0.2s ease';
            });
        });
    }
});
</script>
@endpush
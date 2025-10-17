@extends('layouts.app')

@section('title', 'Dashboard Coordinateur - Suivi des Présences')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header Section -->
        <x-dashboard.dashboard-header 
            title="Dashboard Coordinateur - Suivi des Présences"
            subtitle="Monitoring en temps réel des émargements et présences - {{ \Carbon\Carbon::today()->format('d/m/Y') }}"
            icon="fa-chart-pie"
        />

        <!-- Statistiques KPI -->
        <x-dashboard.kpi-grid :stats="$stats" />

        <!-- Section: Vue d'ensemble du workflow -->
        <div class="mb-4">
            <x-dashboard.main-card 
            title="État du Workflow Aujourd'hui"
            subtitle="Progression du processus: Émargement → Appel → Validation"
            icon="fa-project-diagram"
        >
            <div class="workflow-container">
                <!-- ROW 1: Séance → Émargement Début → Appel Début → Émargement Fin -->
                <div class="row text-center mb-4">
                    <!-- Étape 1: Séance -->
                    <div class="col-6 col-md-3 mb-3">
                        <div class="workflow-step-compact">
                            <div class="workflow-number">1</div>
                            <div class="workflow-value-compact text-primary">{{ $stats['scheduled_courses_today'] ?? 0 }}</div>
                            <div class="workflow-label-compact">Séance</div>
                        </div>
                    </div>

                    <!-- Étape 2: Émargement Début -->
                    <div class="col-6 col-md-3 mb-3">
                        <div class="workflow-step-compact">
                            <div class="workflow-number">2</div>
                            <div class="workflow-value-compact text-success">{{ $stats['teacher_start_attendances_today'] ?? 0 }}</div>
                            <div class="workflow-label-compact">Émargement début</div>
                        </div>
                    </div>

                    <!-- Étape 3: Appel Début -->
                    <div class="col-6 col-md-3 mb-3">
                        <div class="workflow-step-compact">
                            <div class="workflow-number">3</div>
                            <div class="workflow-value-compact text-info">{{ $stats['call_start_done_today'] ?? 0 }}</div>
                            <div class="workflow-label-compact">Appel de début</div>
                        </div>
                    </div>

                    <!-- Étape 4: Émargement Fin -->
                    <div class="col-6 col-md-3 mb-3">
                        <div class="workflow-step-compact">
                            <div class="workflow-number">4</div>
                            <div class="workflow-value-compact text-warning">{{ $stats['teacher_end_attendances_today'] ?? 0 }}</div>
                            <div class="workflow-label-compact">Émargement fin</div>
                        </div>
                    </div>
                </div>

                <!-- ROW 2: Émargement Complet → Appel Fin → Appels Terminés → Workflow Complet -->
                <div class="row text-center">
                    <!-- Étape 5: Émargement Complet (début + fin) -->
                    <div class="col-6 col-md-3 mb-3">
                        <div class="workflow-step-compact">
                            <div class="workflow-number">5</div>
                            <div class="workflow-value-compact text-success">{{ $stats['teacher_attendances_today'] ?? 0 }}</div>
                            <div class="workflow-label-compact">Émargement complet</div>
                        </div>
                    </div>

                    <!-- Étape 6: Appel Fin -->
                    <div class="col-6 col-md-3 mb-3">
                        <div class="workflow-step-compact">
                            <div class="workflow-number">6</div>
                            <div class="workflow-value-compact text-info">{{ $stats['call_end_done_today'] ?? 0 }}</div>
                            <div class="workflow-label-compact">Appel fin</div>
                        </div>
                    </div>

                    <!-- Étape 7: Appels Terminés (au moins 1 appel) -->
                    <div class="col-6 col-md-3 mb-3">
                        <div class="workflow-step-compact">
                            <div class="workflow-number">7</div>
                            <div class="workflow-value-compact text-primary">{{ $stats['roll_calls_completed_today'] ?? 0 }}</div>
                            <div class="workflow-label-compact">Appels terminés</div>
                        </div>
                    </div>

                    <!-- Étape 8: Workflow Complet -->
                    <div class="col-6 col-md-3 mb-3">
                        <div class="workflow-step-compact">
                            <div class="workflow-number">✓</div>
                            <div class="workflow-value-compact text-success">{{ $stats['courses_completed_today'] ?? 0 }}</div>
                            <div class="workflow-label-compact">Workflow complet</div>
                        </div>
                    </div>
                </div>

                @php
                    $totalCourses = $stats['scheduled_courses_today'] ?? 0;
                    $completedCourses = $stats['courses_completed_today'] ?? 0;
                    $completionRate = $totalCourses > 0 ? round(($completedCourses / $totalCourses) * 100, 1) : 0;
                @endphp

                @if($totalCourses > 0)
                <div class="workflow-summary mt-4 pt-3">
                    <div class="d-flex align-items-center justify-content-center">
                        <div class="workflow-icon primary small me-3">
                            <i class="fas fa-chart-pie"></i>
                        </div>
                        <div class="text-center">
                            <span class="text-muted me-2">Progression globale:</span>
                            <strong class="text-primary fs-5">{{ $completionRate }}%</strong>
                            <span class="text-muted ms-2">({{ $completedCourses }}/{{ $totalCourses }} cours complets)</span>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </x-dashboard.main-card>
        </div>

        <!-- Section: Statistiques par Matière -->
        <div class="mb-4">
            <x-dashboard.main-card 
            title="Statistiques par Matière"
            subtitle="Progression des cours par matière aujourd'hui"
            icon="fa-book-open"
        >
            @if(!empty($stats['subjects_stats']) && count($stats['subjects_stats']) > 0)
            <div class="row">
                @foreach($stats['subjects_stats'] as $subject)
                <div class="col-lg-6 col-xl-4 mb-4">
                    <div class="subject-card">
                        <div class="subject-header">
                            <h6 class="subject-name">{{ $subject['matiere_name'] }}</h6>
                            @php
                                $taux = $subject['taux_completion'] ?? 0;
                                $badgeClass = $taux >= 80 ? 'success' : ($taux >= 50 ? 'warning' : 'primary');
                            @endphp
                            <span class="subject-badge badge-{{ $badgeClass }}">{{ $taux }}%</span>
                        </div>
                        
                        <div class="subject-stats">
                            <div class="stat-item">
                                <div class="stat-value text-primary">{{ $subject['total_seances'] ?? 0 }}</div>
                                <small class="stat-label">Séances</small>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value text-success">{{ $subject['emargements_effectues'] ?? 0 }}</div>
                                <small class="stat-label">Émargé</small>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value text-info">{{ $subject['appels_effectues'] ?? 0 }}</div>
                                <small class="stat-label">Appels</small>
                            </div>
                        </div>
                        
                        <div class="subject-progress">
                            <div class="progress-bar-subject bg-{{ $badgeClass }}" style="width: {{ $taux }}%"></div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <div class="empty-state">
                <i class="fas fa-book-open"></i>
                <p>
                    Aucune statistique par matière aujourd'hui
                    <br><small class="text-muted">Les données apparaîtront dès qu'il y aura des cours planifiés.</small>
                </p>
            </div>
            @endif
        </x-dashboard.main-card>
        </div>

        <!-- Section: Alertes et Actions Rapides -->
        <div class="row">
            <div class="col-lg-8 mb-4">
                <x-dashboard.main-card 
                    title="Alertes et Notifications"
                    subtitle="Alertes importantes du jour"
                    icon="fa-bell"
                >
                    @if(!empty($stats['alerts']) && count($stats['alerts']) > 0)
                        @foreach($stats['alerts'] as $alert)
                        <div class="alert-item alert-{{ $alert['type'] }}">
                            <div class="alert-icon">
                                <i class="fas fa-{{ $alert['type'] === 'warning' ? 'exclamation-triangle' : ($alert['type'] === 'danger' ? 'times-circle' : 'info-circle') }}"></i>
                            </div>
                            <div class="alert-content">
                                <strong class="alert-title">{{ $alert['title'] }}</strong>
                                <p class="alert-message">{{ $alert['message'] }}</p>
                                @if(!empty($alert['details']))
                                    <ul class="alert-details">
                                        @foreach(array_slice($alert['details'], 0, 3) as $detail)
                                        <li>{{ $detail }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    @else
                    <div class="alert-item alert-success">
                        <div class="alert-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="alert-content">
                            <strong class="alert-title">Situation normale</strong>
                            <p class="alert-message">Aucune alerte aujourd'hui</p>
                        </div>
                    </div>
                    @endif
                </x-dashboard.main-card>
            </div>

            <div class="col-lg-4 mb-4">
                <x-dashboard.main-card 
                    title="Actions Rapides"
                    subtitle="Raccourcis et outils"
                    icon="fa-bolt"
                >
                    <div class="actions-grid">
                        <a href="{{ route('esbtp.teacher-attendance.report') }}" class="action-button primary">
                            <i class="fas fa-clipboard-list"></i>
                            <span>Rapport Émargements</span>
                        </a>
                        <a href="{{ route('esbtp.attendances.index') }}" class="action-button success">
                            <i class="fas fa-users"></i>
                            <span>Gérer Présences</span>
                        </a>
                        <button class="action-button warning" onclick="generateReport()">
                            <i class="fas fa-file-export"></i>
                            <span>Export Journalier</span>
                        </button>
                        <button class="action-button info" onclick="refreshData()">
                            <i class="fas fa-sync-alt"></i>
                            <span>Actualiser</span>
                        </button>
                    </div>

                    <!-- Stats supplémentaires -->
                    <div class="additional-stats">
                        <div class="stat-row">
                            <span class="stat-label">Émargements complets (début+fin):</span>
                            <span class="stat-value text-success">{{ $stats['teacher_attendances_today'] ?? 0 }}</span>
                        </div>
                        @if(isset($stats['teacher_start_attendances_today']) && $stats['teacher_start_attendances_today'] > 0)
                        <div class="stat-row">
                            <span class="stat-label">Émargements début seulement:</span>
                            <span class="stat-value text-warning">{{ $stats['teacher_start_attendances_today'] - ($stats['teacher_attendances_today'] ?? 0) }}</span>
                        </div>
                        @endif
                        <div class="stat-row">
                            <span class="stat-label">Étudiants total:</span>
                            <span class="stat-value text-primary">{{ $stats['students_total_today'] ?? 0 }}</span>
                        </div>
                        <div class="stat-row">
                            <span class="stat-label">Présents:</span>
                            <span class="stat-value text-success">{{ $stats['students_present_today'] ?? 0 }}</span>
                        </div>
                        <div class="stat-row">
                            <span class="stat-label">Enseignants actifs:</span>
                            <span class="stat-value text-info">{{ $stats['active_teachers_today'] ?? 0 }}</span>
                        </div>
                        @if(($stats['delays_today'] ?? 0) > 0)
                        <div class="stat-row">
                            <span class="stat-label">Retards:</span>
                            <span class="stat-value text-warning">{{ $stats['delays_today'] }}</span>
                        </div>
                        @endif
                    </div>
                </x-dashboard.main-card>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function refreshData() {
    window.location.reload();
}

function generateReport() {
    window.open('{{ route("coordinateur.daily-report") }}?date={{ \Carbon\Carbon::today()->format("Y-m-d") }}', '_blank');
}

// Auto-refresh every 2 minutes
setInterval(function() {
    if (document.visibilityState === 'visible') {
        refreshData();
    }
}, 120000);

// Animate cards on load
document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.main-card, .kpi-card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            card.style.transition = 'all 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
});
</script>
@endsection

@push('styles')
<style>
/* Workflow Styles - Compact Design */
.workflow-container {
    padding: 1rem 0;
}

.workflow-step-compact {
    text-align: center;
    padding: 1rem;
    background: var(--surface);
    border: 1px solid rgba(0, 0, 0, 0.08);
    border-radius: var(--radius-medium);
    transition: all 0.3s ease;
}

.workflow-step-compact:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-hover);
}

.workflow-number {
    width: 36px;
    height: 36px;
    border-radius: var(--radius-circle);
    background: linear-gradient(135deg, var(--primary), #60a5fa);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 0.75rem;
    font-weight: 700;
    font-size: 0.9rem;
}

.workflow-value-compact {
    font-size: 1.75rem;
    font-weight: 800;
    margin-bottom: 0.25rem;
}

.workflow-label-compact {
    font-size: 0.8rem;
    font-weight: 500;
    color: var(--text-secondary);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.workflow-icon {
    width: 60px;
    height: 60px;
    border-radius: var(--radius-circle);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: white;
    margin: 0 auto 1rem auto;
}

.workflow-icon.small {
    width: 32px;
    height: 32px;
    font-size: 16px;
}

.workflow-icon.primary { background: linear-gradient(135deg, var(--primary), #60a5fa); }
.workflow-icon.success { background: linear-gradient(135deg, var(--success), #34d399); }
.workflow-icon.info { background: linear-gradient(135deg, var(--accent-blue), #38bdf8); }
.workflow-icon.warning { background: linear-gradient(135deg, var(--warning), #fbbf24); }

.workflow-summary {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid rgba(0, 0, 0, 0.1);
}

/* Subject Cards */
.subject-card {
    background: var(--surface);
    border: 1px solid rgba(0, 0, 0, 0.1);
    border-radius: var(--radius-medium);
    padding: 1.25rem;
    height: 100%;
    transition: all 0.2s ease;
}

.subject-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-elevated);
}

.subject-header {
    display: flex;
    justify-content: between;
    align-items: flex-start;
    margin-bottom: 1rem;
}

.subject-name {
    font-weight: 600;
    color: var(--text-primary);
    margin: 0;
    flex: 1;
}

.subject-badge {
    padding: 0.25rem 0.75rem;
    border-radius: var(--radius-full);
    font-size: 0.75rem;
    font-weight: 600;
    color: white;
}

.badge-success { background: var(--success); }
.badge-warning { background: var(--warning); }
.badge-primary { background: var(--primary); }

.subject-stats {
    display: flex;
    justify-content: space-between;
    margin-bottom: 1rem;
}

.stat-item {
    text-align: center;
    flex: 1;
}

.stat-value {
    font-size: 1.25rem;
    font-weight: 700;
}

.stat-label {
    color: var(--text-secondary);
    font-size: 0.75rem;
}

.subject-progress {
    height: 6px;
    background: rgba(0, 0, 0, 0.1);
    border-radius: 3px;
    overflow: hidden;
}

.progress-bar-subject {
    height: 100%;
    border-radius: 3px;
    transition: width 0.8s ease;
}

/* Alert Items */
.alert-item {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 1rem;
    border-left: 4px solid;
    background: var(--surface);
    border-radius: var(--radius-small);
    margin-bottom: 1rem;
}

.alert-item.alert-success { border-left-color: var(--success); }
.alert-item.alert-warning { border-left-color: var(--warning); }
.alert-item.alert-danger { border-left-color: var(--danger); }
.alert-item.alert-info { border-left-color: var(--accent-blue); }

.alert-icon {
    width: 40px;
    height: 40px;
    border-radius: var(--radius-circle);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    color: white;
    flex-shrink: 0;
}

.alert-success .alert-icon { background: var(--success); }
.alert-warning .alert-icon { background: var(--warning); }
.alert-danger .alert-icon { background: var(--danger); }
.alert-info .alert-icon { background: var(--accent-blue); }

.alert-content {
    flex: 1;
}

.alert-title {
    font-weight: 600;
    margin-bottom: 0.25rem;
    color: var(--text-primary);
}

.alert-message {
    margin: 0 0 0.5rem 0;
    color: var(--text-secondary);
    font-size: 0.875rem;
}

.alert-details {
    margin: 0;
    padding-left: 1.25rem;
    color: var(--text-secondary);
    font-size: 0.8rem;
}

/* Action Buttons */
.actions-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.75rem;
    margin-bottom: 1.5rem;
}

.action-button {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem;
    border: none;
    border-radius: var(--radius-medium);
    color: white;
    text-decoration: none;
    transition: all 0.2s ease;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
}

.action-button.primary { background: linear-gradient(135deg, var(--primary), #60a5fa); }
.action-button.success { background: linear-gradient(135deg, var(--success), #34d399); }
.action-button.warning { background: linear-gradient(135deg, var(--warning), #fbbf24); }
.action-button.info { background: linear-gradient(135deg, var(--accent-blue), #38bdf8); }

.action-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    color: white;
}

.action-button i {
    font-size: 1.25rem;
}

/* Additional Stats */
.additional-stats {
    padding-top: 1rem;
    border-top: 1px solid rgba(0, 0, 0, 0.1);
}

.stat-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}

.stat-row:last-child {
    border-bottom: none;
}

.stat-row .stat-label {
    color: var(--text-secondary);
    font-size: 0.875rem;
}

.stat-row .stat-value {
    font-weight: 600;
    font-size: 0.9rem;
}

/* Mobile Responsiveness */
@media (max-width: 768px) {
    .workflow-step {
        margin-bottom: 1.5rem;
    }
    
    .workflow-arrow {
        display: none !important;
    }
    
    .subject-stats {
        flex-direction: column;
        gap: 0.5rem;
        text-align: left;
    }
    
    .stat-item {
        display: flex;
        justify-content: space-between;
        text-align: left;
    }
    
    .actions-grid {
        grid-template-columns: 1fr;
    }
}
</style>
@endpush
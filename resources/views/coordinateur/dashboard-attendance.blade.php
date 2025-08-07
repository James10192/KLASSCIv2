@extends('layouts.app')

@section('title', 'Tableau de Bord - Suivi des Présences')

@section('content')
<div class="container-fluid py-4">
    <!-- En-tête du tableau de bord -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="h4 mb-1">📊 Tableau de Bord - Suivi des Présences</h2>
                            <p class="text-muted mb-0">Aperçu en temps réel des émargements enseignants et présences étudiants</p>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-outline-primary btn-sm" onclick="refreshData()">
                                <i class="fas fa-sync-alt me-1"></i> Actualiser
                            </button>
                            <div class="btn-group">
                                <button class="btn btn-info btn-sm" onclick="showTeacherAttendance()">
                                    <i class="fas fa-chalkboard-teacher me-1"></i> Enseignants
                                </button>
                                <button class="btn btn-success btn-sm" onclick="showStudentAttendance()">
                                    <i class="fas fa-users me-1"></i> Étudiants
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistiques rapides -->
    <div class="row mb-4">
        <!-- Émargements du jour -->
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card bg-gradient-primary text-white border-0 shadow">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title text-white-50 mb-1">Émargements Aujourd'hui</h6>
                            <h3 class="mb-0 fw-bold">{{ $stats['teacher_attendances_today'] ?? 0 }}</h3>
                            <small class="text-white-75">sur {{ $stats['scheduled_courses_today'] ?? 0 }} cours prévus</small>
                        </div>
                        <div class="text-white-50">
                            <i class="fas fa-clipboard-check fa-2x"></i>
                        </div>
                    </div>
                    <div class="progress mt-3" style="height: 4px;">
                        <div class="progress-bar bg-white" role="progressbar" 
                             style="width: {{ $stats['teacher_attendance_rate'] ?? 0 }}%">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Appels terminés -->
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card bg-gradient-success text-white border-0 shadow">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title text-white-50 mb-1">Appels Terminés</h6>
                            <h3 class="mb-0 fw-bold">{{ $stats['roll_calls_completed_today'] ?? 0 }}</h3>
                            <small class="text-white-75">{{ $stats['students_present_today'] ?? 0 }} présents</small>
                        </div>
                        <div class="text-white-50">
                            <i class="fas fa-users-check fa-2x"></i>
                        </div>
                    </div>
                    <div class="progress mt-3" style="height: 4px;">
                        <div class="progress-bar bg-white" role="progressbar" 
                             style="width: {{ $stats['roll_call_completion_rate'] ?? 0 }}%">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Retards détectés -->
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card bg-gradient-warning text-white border-0 shadow">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title text-white-50 mb-1">Retards Détectés</h6>
                            <h3 class="mb-0 fw-bold">{{ $stats['delays_today'] ?? 0 }}</h3>
                            <small class="text-white-75">émargements en retard</small>
                        </div>
                        <div class="text-white-50">
                            <i class="fas fa-clock fa-2x"></i>
                        </div>
                    </div>
                    @if(($stats['delays_today'] ?? 0) > 0)
                        <div class="mt-3">
                            <small class="text-white-75">⚠️ Attention requise</small>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Cours clôturés -->
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card bg-gradient-info text-white border-0 shadow">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title text-white-50 mb-1">Cours Clôturés</h6>
                            <h3 class="mb-0 fw-bold">{{ $stats['courses_closed_today'] ?? 0 }}</h3>
                            <small class="text-white-75">séances terminées</small>
                        </div>
                        <div class="text-white-50">
                            <i class="fas fa-check-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Section d'actions rapides -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0">🚀 Actions Rapides</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <a href="{{ route('esbtp.teacher-attendance.report') }}" class="btn btn-outline-primary w-100 h-100 d-flex flex-column justify-content-center">
                                <i class="fas fa-chalkboard-teacher fa-2x mb-2"></i>
                                <span>Émargements Enseignants</span>
                                <small class="text-muted">Voir tous les émargements</small>
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="{{ route('esbtp.attendances.index') }}" class="btn btn-outline-success w-100 h-100 d-flex flex-column justify-content-center">
                                <i class="fas fa-users fa-2x mb-2"></i>
                                <span>Présences Étudiants</span>
                                <small class="text-muted">Gérer les présences</small>
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="{{ route('notifications.index') }}" class="btn btn-outline-info w-100 h-100 d-flex flex-column justify-content-center">
                                <i class="fas fa-bell fa-2x mb-2"></i>
                                <span>Notifications</span>
                                <small class="text-muted">{{ $unreadNotifications ?? 0 }} non lues</small>
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <button class="btn btn-outline-warning w-100 h-100 d-flex flex-column justify-content-center" onclick="generateDailyReport()">
                                <i class="fas fa-chart-line fa-2x mb-2"></i>
                                <span>Rapport du Jour</span>
                                <small class="text-muted">Générer le récap</small>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Activités récentes -->
    <div class="row">
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

        <!-- Alertes et notifications -->
        <div class="col-xl-4 col-lg-5 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0">🔔 Alertes</h5>
                </div>
                <div class="card-body">
                    <div id="alerts-container">
                        <!-- Retards d'émargement -->
                        @if(($stats['delays_today'] ?? 0) > 0)
                        <div class="alert alert-warning border-0 shadow-sm mb-3">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <div>
                                    <strong>{{ $stats['delays_today'] }} retard(s) d'émargement</strong>
                                    <p class="mb-0 small">Des enseignants n'ont pas émargé à temps</p>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Absences importantes -->
                        @if(($stats['high_absence_classes'] ?? 0) > 0)
                        <div class="alert alert-danger border-0 shadow-sm mb-3">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-users-slash me-2"></i>
                                <div>
                                    <strong>{{ $stats['high_absence_classes'] }} classe(s) avec forte absentéisme</strong>
                                    <p class="mb-0 small">Plus de 30% d'absences détectées</p>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Tout va bien -->
                        @if(($stats['delays_today'] ?? 0) == 0 && ($stats['high_absence_classes'] ?? 0) == 0)
                        <div class="alert alert-success border-0 shadow-sm mb-3">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-check-circle me-2"></i>
                                <div>
                                    <strong>Situation normale</strong>
                                    <p class="mb-0 small">Aucune alerte particulière aujourd'hui</p>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
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
.timeline-icon.info { background-color: #17a2b8; }
.timeline-icon.danger { background-color: #dc3545; }

.card {
    transition: transform 0.2s ease-in-out;
}

.card:hover {
    transform: translateY(-2px);
}

.bg-gradient-primary { background: linear-gradient(45deg, #4e73df, #224abe); }
.bg-gradient-success { background: linear-gradient(45deg, #1cc88a, #13855c); }
.bg-gradient-warning { background: linear-gradient(45deg, #f6c23e, #dda20a); }
.bg-gradient-info { background: linear-gradient(45deg, #36b9cc, #258391); }
</style>
@endpush

@push('scripts')
<script>
// Fonction de rafraîchissement des données
function refreshData() {
    location.reload();
}

// Fonction pour afficher les émargements enseignants
function showTeacherAttendance() {
    window.location.href = '{{ route("esbtp.teacher-attendance.report") }}';
}

// Fonction pour afficher les présences étudiants
function showStudentAttendance() {
    window.location.href = '{{ route("esbtp.attendances.index") }}';
}

// Fonction pour générer le rapport quotidien
function generateDailyReport() {
    const loadingBtn = event.target;
    const originalText = loadingBtn.innerHTML;
    
    loadingBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Génération...';
    loadingBtn.disabled = true;
    
    fetch('{{ route("coordinateur.daily-report") }}', {
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
            // Afficher le rapport dans une modal ou une nouvelle fenêtre
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
    // Créer et afficher une modal avec le rapport
    const modalHtml = `
        <div class="modal fade" id="dailyReportModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Rapport du ${report.date}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Résumé</h6>
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span>Cours prévus:</span>
                                        <strong>${report.summary.cours_prevus}</strong>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span>Émargements:</span>
                                        <strong>${report.summary.emargements_effectues}</strong>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span>Taux d'émargement:</span>
                                        <strong>${report.summary.taux_emargement}</strong>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span>Appels terminés:</span>
                                        <strong>${report.summary.appels_termines}</strong>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span>Étudiants présents:</span>
                                        <strong>${report.summary.etudiants_presents}</strong>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span>Cours clôturés:</span>
                                        <strong>${report.summary.cours_clotures}</strong>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span>Retards détectés:</span>
                                        <strong>${report.summary.retards_detectes}</strong>
                                    </li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6>Recommandations</h6>
                                ${report.recommendations.map(rec => `
                                    <div class="alert alert-${rec.type} alert-sm">
                                        <small><strong>${rec.message}</strong><br>
                                        Action: ${rec.action}</small>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Supprimer l'ancienne modal si elle existe
    const existingModal = document.getElementById('dailyReportModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    // Ajouter la nouvelle modal au DOM
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    // Afficher la modal
    const modal = new bootstrap.Modal(document.getElementById('dailyReportModal'));
    modal.show();
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
    
    fetch('{{ route("coordinateur.recent-activities") }}?limit=10', {
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
        activitiesContainer.innerHTML = `
            <div class="text-center py-4 text-warning">
                <i class="fas fa-wifi fa-2x mb-2"></i>
                <p>Impossible de charger les activités</p>
                <button class="btn btn-sm btn-outline-primary" onclick="loadRecentActivities()">Réessayer</button>
            </div>
        `;
    });
}
</script>
@endpush
@endsection
@extends('layouts.app')

@section('title', 'Tableau de bord enseignant')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    /* Styles spécifiques pour le dashboard enseignant */
    body {
        background-color: var(--background);
    }
    .emargement-widget {
        border-radius: var(--radius-medium);
        padding: var(--space-lg);
        margin-bottom: var(--space-lg);
    }
    
    .status-badge {
        display: inline-flex;
        align-items: center;
        padding: var(--space-xs) var(--space-md);
        border-radius: var(--radius-large);
        font-size: var(--text-small);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    
    .status-badge.present { 
        background-color: rgba(16, 185, 129, 0.1); 
        color: var(--success); 
        border: 1px solid rgba(16, 185, 129, 0.2); 
    }
    
    .status-badge.absent { 
        background-color: rgba(239, 68, 68, 0.1); 
        color: var(--danger); 
        border: 1px solid rgba(239, 68, 68, 0.2); 
    }
    
    .status-badge.late { 
        background-color: rgba(245, 158, 11, 0.1); 
        color: var(--warning); 
        border: 1px solid rgba(245, 158, 11, 0.2); 
    }
    
    .status-badge.pending { 
        background-color: rgba(107, 114, 128, 0.1); 
        color: var(--neutral); 
        border: 1px solid rgba(107, 114, 128, 0.2); 
    }

    .code-display {
        font-family: 'Courier New', monospace;
        font-size: 2.5rem;
        font-weight: 700;
        background: linear-gradient(135deg, var(--primary), var(--accent-blue));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        text-align: center;
        padding: var(--space-lg);
        border: 2px dashed var(--primary);
        border-radius: var(--radius-medium);
        margin: var(--space-md) 0;
    }

    .quick-action-btn {
        display: inline-flex;
        align-items: center;
        gap: var(--space-sm);
        padding: var(--space-sm) var(--space-md);
        background-color: transparent;
        border: 1px solid var(--primary);
        color: var(--primary);
        border-radius: var(--radius-small);
        text-decoration: none;
        font-size: var(--text-small);
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .quick-action-btn:hover {
        background-color: var(--primary);
        color: white;
        transform: translateY(-1px);
        box-shadow: var(--shadow-hover);
    }

    .notification-card {
        border-left: 4px solid;
        padding: var(--space-md);
        margin-bottom: var(--space-md);
        border-radius: var(--radius-small);
    }

    .notification-card.warning {
        border-color: var(--warning);
        background-color: rgba(245, 158, 11, 0.05);
    }

    .notification-card.info {
        border-color: var(--accent-blue);
        background-color: rgba(6, 182, 212, 0.05);
    }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header avec bienvenue -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1><i class="fas fa-chalkboard-teacher me-2"></i>Tableau de bord enseignant</h1>
                <p class="header-subtitle">Bienvenue, <strong>{{ Auth::user()->name }}</strong> ! Gérez vos cours et émargements</p>
            </div>
            <div class="header-actions">
                <span class="text-muted">{{ \Carbon\Carbon::now()->isoFormat('dddd D MMMM YYYY') }}</span>
            </div>
        </div>

        <!-- Notifications importantes -->
        @if(isset($notifications) && count($notifications) > 0)
            <div class="mb-4">
                @foreach($notifications as $notification)
                    <div class="card-moderne mb-3 p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-{{ $notification['type'] === 'warning' ? 'exclamation-triangle text-warning' : 'info-circle text-info' }} me-2"></i>
                                {{ $notification['message'] }}
                            </div>
                            <a href="{{ $notification['action'] }}" class="btn btn-sm btn-outline-primary">
                                {{ $notification['action_text'] }}
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <!-- Grille des KPIs -->
        <div class="row mb-4">
            <!-- KPI 1: Émargement du jour -->
            <div class="col-lg-3 col-md-6">
                <div class="card-moderne p-3 {{ $todayAttendance ? 'border-success' : 'border-warning' }}">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <div class="rounded-circle p-3 {{ $todayAttendance ? 'bg-success' : 'bg-warning' }} text-white">
                                <i class="fas fa-user-check fa-2x"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="fw-bold text-primary mb-1">Émargement</h6>
                            <div class="mb-2">
                                @if($todayAttendance)
                                    <span class="badge bg-success">✓ Émargé</span>
                                @else
                                    <span class="badge bg-warning">En attente</span>
                                @endif
                            </div>
                            <small class="text-muted">
                                @if($todayAttendance)
                                    {{ $todayAttendance->validated_at->format('H:i') }}
                                @else
                                    Demander le code au coordinateur
                                @endif
                            </small>
                        </div>
                    </div>
                    @if(!$todayAttendance && $dailyCode)
                        <div class="mt-3 text-center">
                            <a href="{{ route('esbtp.teacher.attendance.index') }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-edit me-1"></i> Émarger
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            <!-- KPI 2: Mes séances -->
            <div class="col-lg-3 col-md-6">
                <div class="card-moderne p-3 border-primary">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <div class="rounded-circle p-3 bg-primary text-white">
                                <i class="fas fa-calendar-day fa-2x"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="fw-bold text-primary mb-1">Mes séances</h6>
                            <div class="h4 mb-1">{{ $attendanceStats['totalCourses'] ?? 0 }}</div>
                            <small class="text-muted">Total séances</small>
                        </div>
                    </div>
                    <div class="mt-3 text-center">
                        <a href="{{ route('teacher.timetable') }}" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-eye me-1"></i> Voir
                        </a>
                    </div>
                </div>
            </div>

            <!-- KPI 3: Taux de présence -->
            <div class="col-lg-3 col-md-6">
                <div class="card-moderne p-3 {{ isset($attendanceStats['attendanceRate']) && $attendanceStats['attendanceRate'] > 90 ? 'border-success' : (isset($attendanceStats['attendanceRate']) && $attendanceStats['attendanceRate'] > 75 ? 'border-warning' : 'border-danger') }}">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <div class="rounded-circle p-3 {{ isset($attendanceStats['attendanceRate']) && $attendanceStats['attendanceRate'] > 90 ? 'bg-success' : (isset($attendanceStats['attendanceRate']) && $attendanceStats['attendanceRate'] > 75 ? 'bg-warning' : 'bg-danger') }} text-white">
                                <i class="fas fa-chart-line fa-2x"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="fw-bold text-primary mb-1">Taux présence</h6>
                            <div class="h4 mb-1">{{ number_format($attendanceStats['attendanceRate'] ?? 0, 1) }}%</div>
                            <small class="text-muted">
                                {{ $attendanceStats['attendedCourses'] ?? 0 }}/{{ $attendanceStats['totalCourses'] ?? 0 }} séances
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- KPI 4: Appels en attente -->
            <div class="col-lg-3 col-md-6">
                <div class="card-moderne p-3 {{ $pendingRollCalls->count() > 0 ? 'border-info' : 'border-secondary' }}">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <div class="rounded-circle p-3 {{ $pendingRollCalls->count() > 0 ? 'bg-info' : 'bg-secondary' }} text-white">
                                <i class="fas fa-list-check fa-2x"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="fw-bold text-primary mb-1">Appels</h6>
                            <div class="h4 mb-1">{{ $pendingRollCalls->count() }}</div>
                            <small class="text-muted">
                                @if($pendingRollCalls->count() > 0)
                                    En attente
                                @else
                                    À jour
                                @endif
                            </small>
                        </div>
                    </div>
                    @if($pendingRollCalls->count() > 0)
                        <div class="mt-3 text-center">
                            <a href="#pending-roll-calls" class="btn btn-info btn-sm">
                                <i class="fas fa-arrow-down me-1"></i> Voir
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

    <!-- Section principale: Cours et Actions -->
    <div class="dashboard-main-grid">
        <!-- Séances du jour -->
        <div class="main-card">
            <div class="main-card-header">
                <div class="main-card-title">
                    <i class="fas fa-calendar-day"></i>
                    Mes cours aujourd'hui
                </div>
                <div class="main-card-subtitle">{{ $todayClasses->count() }} séance(s) programmée(s)</div>
            </div>
            <div class="main-card-body">
                @if($todayClasses->count() > 0)
                    <div class="course-list">
                        @foreach($todayClasses as $cours)
                            <div class="course-item">
                                <div class="course-time">
                                    <div class="time-display">
                                        {{ $cours->heure_debut ? \Carbon\Carbon::parse($cours->heure_debut)->format('H:i') : 'N/A' }} - 
                                        {{ $cours->heure_fin ? \Carbon\Carbon::parse($cours->heure_fin)->format('H:i') : 'N/A' }}
                                    </div>
                                    <div class="course-day">{{ $joursSemaine[$cours->jour] ?? 'Jour '.$cours->jour }}</div>
                                </div>
                                <div class="course-info">
                                    <div class="course-subject">{{ $cours->matiere->name ?? 'Matière non définie' }}</div>
                                    <div class="course-class">{{ $cours->classe->name ?? 'Classe non définie' }}</div>
                                    <div class="course-type">{{ ucfirst($cours->type ?? 'cours') }}</div>
                                </div>
                                <div class="course-status">
                                    @php
                                        $hasAttendance = $cours->teacherAttendance()->whereDate('validated_at', \Carbon\Carbon::today())->exists();
                                        $hasStudentCall = \App\Models\ESBTPAttendance::where('seance_cours_id', $cours->id)->exists();
                                        $isCompleted = $cours->status === 'completed';
                                    @endphp
                                    
                                    @if($isCompleted)
                                        <span class="status-badge present">✓ Terminé</span>
                                    @elseif($hasStudentCall)
                                        <span class="status-badge present">✓ Appel fait</span>
                                    @elseif($hasAttendance && \Carbon\Carbon::parse($cours->heure_debut)->isPast())
                                        <span class="status-badge late">En cours</span>
                                    @else
                                        <span class="status-badge pending">Programmé</span>
                                    @endif
                                </div>
                                <div class="course-actions">
                                    @if($hasAttendance && !$isCompleted)
                                        @if(!$hasStudentCall && \Carbon\Carbon::parse($cours->heure_debut)->subMinutes(15)->isPast())
                                            <a href="{{ route('teacher.roll-call', $cours->id) }}" class="quick-action-btn">
                                                <i class="fas fa-list-check"></i> Faire l'appel
                                            </a>
                                        @elseif($hasStudentCall)
                                            <form method="POST" action="{{ route('teacher.close-course', $cours->id) }}" style="display:inline;">
                                                @csrf
                                                <button type="submit" class="quick-action-btn" 
                                                        onclick="return confirm('Êtes-vous sûr de vouloir clôturer ce cours ?')">
                                                    <i class="fas fa-check-circle"></i> Clôturer
                                                </button>
                                            </form>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state">
                        <i class="fas fa-calendar-times"></i>
                        <p>Aucun cours programmé aujourd'hui</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Appels en attente -->
        @if($pendingRollCalls->count() > 0)
            <div id="pending-roll-calls" class="main-card urgent">
                <div class="main-card-header">
                    <div class="main-card-title">
                        <i class="fas fa-exclamation-circle"></i>
                        Appels en attente
                    </div>
                    <div class="main-card-subtitle">{{ $pendingRollCalls->count() }} cours nécessitent un appel</div>
                </div>
                <div class="main-card-body">
                    <div class="urgent-list">
                        @foreach($pendingRollCalls as $cours)
                            <div class="urgent-item">
                                <div class="urgent-info">
                                    <div class="urgent-title">
                                        {{ $cours->matiere->name ?? 'Matière inconnue' }} - {{ $cours->classe->name ?? 'Classe inconnue' }}
                                    </div>
                                    <div class="urgent-time">
                                        Débuté à {{ \Carbon\Carbon::parse($cours->heure_debut)->format('H:i') }}
                                    </div>
                                </div>
                                <a href="{{ route('teacher.roll-call', $cours->id) }}" class="btn-urgent-action">
                                    <i class="fas fa-list-check"></i>
                                    Faire l'appel
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    </div>
    
    <!-- Actions rapides -->
    <div class="quick-actions-section">
        <div class="section-header">
            <h2 class="section-title">Actions rapides</h2>
        </div>
        <div class="quick-actions-grid">
            <a href="{{ route('teacher.timetable') }}" class="quick-action-card">
                <i class="fas fa-calendar-alt"></i>
                <span>Mon emploi du temps</span>
            </a>
            <a href="{{ route('esbtp.teacher.attendance.index') }}" class="quick-action-card">
                <i class="fas fa-user-check"></i>
                <span>Émargement</span>
            </a>
            <a href="{{ route('esbtp.notes.index') }}" class="quick-action-card">
                <i class="fas fa-edit"></i>
                <span>Saisir des notes</span>
            </a>
            <a href="{{ route('teacher.grades') }}" class="quick-action-card">
                <i class="fas fa-chart-line"></i>
                <span>Mes statistiques</span>
            </a>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show position-fixed top-0 end-0 m-3" style="z-index: 9999;">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show position-fixed top-0 end-0 m-3" style="z-index: 9999;">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert').alert('close');
    }, 5000);
    
    // Smooth scroll to pending roll calls
    $('a[href="#pending-roll-calls"]').click(function(e) {
        e.preventDefault();
        $('html, body').animate({
            scrollTop: $($(this).attr('href')).offset().top - 20
        }, 500);
    });
});
</script>
@endsection

@extends('layouts.app')

@section('title', 'Tableau de bord étudiant')

@section('content')
<div class="container-fluid">
    <!-- Hero Section -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card border-0 bg-gradient-primary text-white overflow-hidden animate__animated animate__fadeIn"
                 style="background: linear-gradient(135deg, var(--esbtp-green), var(--esbtp-green-dark)); border-radius: 15px;">
                <div class="card-body p-4">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h2 class="display-5 fw-bold mb-3 animate__animated animate__fadeInUp">Bienvenue, {{ Auth::user()->name }}</h2>
                            <p class="lead mb-4 animate__animated animate__fadeInUp animate__delay-1">Suivez vos cours, notes et activités depuis votre tableau de bord étudiant.</p>
                            <div class="d-flex gap-2 animate__animated animate__fadeInUp animate__delay-2">
                                <a href="{{ route('grades.student') }}" class="btn btn-light px-4 py-2">
                                    <i class="fas fa-graduation-cap me-2"></i> Voir mes notes
                                </a>
                                <a href="{{ route('timetables.student') }}" class="btn btn-outline-light px-4 py-2">
                                    <i class="fas fa-calendar-alt me-2"></i> Mon emploi du temps
                                </a>
                            </div>
                        </div>
                        <div class="col-md-6 d-none d-md-block position-relative">
                            <div class="position-absolute top-0 end-0 mt-3 me-4 animate__animated animate__fadeInRight">
                                <div class="d-flex align-items-center">
                                    <div class="bg-white rounded-circle p-3 me-3 shadow-sm">
                                        <i class="fas fa-calendar-alt text-success fa-2x"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0 text-white">{{ now()->format('d M Y') }}</h6>
                                        <small class="text-white-50">{{ now()->format('l') }}</small>
                                    </div>
                                </div>
                            </div>
                            <img src="https://img.freepik.com/free-vector/students-concept-illustration_114360-8737.jpg"
                                 alt="Student" class="img-fluid rounded-3 mt-3 animate__animated animate__fadeInUp" style="max-height: 200px; opacity: 0.9;">
                        </div>
                    </div>
                </div>
                <div class="position-absolute bottom-0 end-0 mb-n3 me-n3 d-none d-lg-block">
                    <svg width="200" height="200" viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="100" cy="100" r="100" fill="rgba(255,255,255,0.1)"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistiques -->
    <div class="row mb-4">
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100 overflow-hidden stat-card animate__animated animate__fadeInUp">
                <div class="card-body position-relative">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h6 class="text-uppercase fw-semibold text-muted mb-1">Présence</h6>
                            <h2 class="mb-0 display-5 fw-bold">{{ $attendancePercentage ?? 0 }}%</h2>
                        </div>
                        <div class="stat-icon bg-primary-light rounded-circle p-3">
                            <i class="fas fa-clipboard-check text-primary fa-2x"></i>
                        </div>
                    </div>
                    <div class="progress" style="height: 5px;">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $attendancePercentage ?? 0 }}%"></div>
                    </div>
                    <a href="{{ route('attendances.student') }}" class="stretched-link text-decoration-none">
                        <div class="d-flex align-items-center mt-3">
                            <small class="text-primary fw-semibold">Voir détails</small>
                            <i class="fas fa-arrow-right ms-auto text-primary"></i>
                        </div>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100 overflow-hidden stat-card animate__animated animate__fadeInUp animate__delay-1">
                <div class="card-body position-relative">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h6 class="text-uppercase fw-semibold text-muted mb-1">Moyenne générale</h6>
                            <h2 class="mb-0 display-5 fw-bold">{{ $averageGrade ?? 'N/A' }}</h2>
                        </div>
                        <div class="stat-icon bg-warning-light rounded-circle p-3">
                            <i class="fas fa-graduation-cap text-warning fa-2x"></i>
                        </div>
                    </div>
                    <div class="progress" style="height: 5px;">
                        <div class="progress-bar bg-warning" role="progressbar" style="width: {{ min(($averageGrade ?? 0) * 10, 100) }}%"></div>
                    </div>
                    <a href="{{ route('grades.student') }}" class="stretched-link text-decoration-none">
                        <div class="d-flex align-items-center mt-3">
                            <small class="text-warning fw-semibold">Voir notes</small>
                            <i class="fas fa-arrow-right ms-auto text-warning"></i>
                        </div>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100 overflow-hidden stat-card animate__animated animate__fadeInUp animate__delay-2">
                <div class="card-body position-relative">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h6 class="text-uppercase fw-semibold text-muted mb-1">Notifications</h6>
                            <h2 class="mb-0 display-5 fw-bold">{{ $unreadNotifications ?? 0 }}</h2>
                        </div>
                        <div class="stat-icon bg-success-light rounded-circle p-3">
                            <i class="fas fa-bell text-success fa-2x"></i>
                        </div>
                    </div>
                    <div class="progress" style="height: 5px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: 100%"></div>
                    </div>
                    <a href="{{ route('notifications.index') }}" class="stretched-link text-decoration-none">
                        <div class="d-flex align-items-center mt-3">
                            <small class="text-success fw-semibold">Voir toutes</small>
                            <i class="fas fa-arrow-right ms-auto text-success"></i>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Emploi du temps et Devoirs -->
    <div class="row mb-4">
        <div class="col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100 animate__animated animate__fadeInUp">
                <div class="card-header bg-white py-3 d-flex align-items-center">
                    <i class="fas fa-calendar-day text-primary me-2"></i>
                    <h5 class="card-title mb-0 fw-bold">Emploi du temps d'aujourd'hui</h5>
                </div>
                <div class="card-body p-0">
                    @if(isset($seancesDuJour) && $seancesDuJour->count() > 0)
                        <div class="timetable-list">
                            @foreach($seancesDuJour as $seance)
                                <div class="timetable-item p-3 border-bottom">
                                    <div class="row align-items-center">
                                        <div class="col-md-2 text-center">
                                            <div class="time-badge bg-light rounded-pill px-3 py-2 d-inline-block">
                                                {{ \Carbon\Carbon::parse($seance->heure_debut)->format('H:i') }}
                                            </div>
                                        </div>
                                        <div class="col-md-8">
                                            <h6 class="mb-1 fw-semibold">{{ $seance->elementConstitutif->matiere->name ?? 'Matière non définie' }}</h6>
                                            <p class="mb-0 text-muted small">
                                                <i class="fas fa-chalkboard-teacher me-1"></i>
                                                {{ $seance->enseignantName }}
                                                <span class="ms-2">
                                                    <i class="fas fa-map-marker-alt me-1"></i>
                                                    {{ $seance->salle ?? 'Salle non définie' }}
                                                </span>
                                                @if($seance->type_seance == 'tp')
                                                    <span class="badge bg-info ms-2">TP</span>
                                                @endif
                                            </p>
                                        </div>
                                        <div class="col-md-2 text-end">
                                            <span class="badge bg-primary-light text-primary">
                                                {{ \Carbon\Carbon::parse($seance->heure_debut)->format('H:i') }} -
                                                {{ \Carbon\Carbon::parse($seance->heure_fin)->format('H:i') }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-5">
                            <div class="mb-3">
                                <i class="fas fa-calendar-check fa-3x text-muted"></i>
                            </div>
                            <h6 class="text-muted">Aucun cours programmé aujourd'hui</h6>
                            <p class="text-muted small mb-0">Profitez de votre journée libre</p>
                        </div>
                    @endif
                </div>
                <div class="card-footer bg-white py-3">
                    <a href="{{ route('timetables.student') }}" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-calendar-alt me-2"></i> Voir l'emploi du temps complet
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100 animate__animated animate__fadeInUp animate__delay-1">
                <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-tasks text-warning me-2"></i>
                        <h5 class="card-title mb-0 fw-bold">Devoirs à rendre</h5>
                    </div>
                </div>
                <div class="card-body p-0">
                    @if(isset($upcomingAssignments) && $upcomingAssignments->count() > 0)
                        <div class="assignment-list">
                            @foreach($upcomingAssignments as $assignment)
                                <div class="assignment-item p-3 border-bottom">
                                    <div class="d-flex">
                                        <div class="flex-shrink-0">
                                            <div class="assignment-icon bg-light rounded-circle p-2">
                                                <i class="fas fa-book text-warning"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h6 class="mb-1 fw-semibold">{{ $assignment->title }}</h6>
                                            <p class="mb-1 text-muted small">{{ $assignment->subject->name }}</p>
                                            <div class="d-flex align-items-center">
                                                <span class="badge {{ \Carbon\Carbon::parse($assignment->due_date)->isPast() ? 'bg-danger' : 'bg-warning' }} rounded-pill">
                                                    <i class="fas fa-clock me-1"></i>
                                                    {{ \Carbon\Carbon::parse($assignment->due_date)->format('d/m/Y') }}
                                                </span>
                                                <span class="ms-auto">
                                                    <a href="#" class="btn btn-sm btn-link text-decoration-none p-0">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-5">
                            <div class="mb-3">
                                <i class="fas fa-check-circle fa-3x text-muted"></i>
                            </div>
                            <h6 class="text-muted">Aucun devoir à rendre</h6>
                            <p class="text-muted small mb-0">Vous êtes à jour dans vos travaux</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Styles pour les cartes statistiques */
    .stat-card {
        transition: all 0.3s ease;
        border-radius: 15px;
    }

    .stat-card:hover {
        transform: translateY(-5px);
    }

    .stat-icon {
        width: 60px;
        height: 60px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
    }

    .bg-primary-light {
        background-color: rgba(13, 110, 253, 0.1);
    }

    .bg-success-light {
        background-color: rgba(25, 135, 84, 0.1);
    }

    .bg-warning-light {
        background-color: rgba(255, 193, 7, 0.1);
    }

    .bg-danger-light {
        background-color: rgba(220, 53, 69, 0.1);
    }

    .bg-info-light {
        background-color: rgba(13, 202, 240, 0.1);
    }

    /* Styles pour les notifications */
    .timetable-list, .assignment-list {
        max-height: 400px;
        overflow-y: auto;
    }

    .timetable-item, .assignment-item {
        transition: all 0.2s ease;
    }

    .timetable-item:hover, .assignment-item:hover {
        background-color: rgba(0, 0, 0, 0.02);
    }

    .time-badge {
        font-weight: 600;
        color: var(--esbtp-green);
    }

    .assignment-icon {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* Animation */
    .animate__delay-1 {
        animation-delay: 0.1s;
    }

    .animate__delay-2 {
        animation-delay: 0.2s;
    }
</style>
@endsection

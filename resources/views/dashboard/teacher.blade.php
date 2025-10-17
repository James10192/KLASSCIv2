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
                @php
                    $now = \Carbon\Carbon::now();
                    $hasCoursesToday = $todayClasses->count() > 0;

                    // Récupérer le teacher_id correct
                    $teacherModel = \App\Models\ESBTPTeacher::where('user_id', Auth::id())->first();
                    $teacherId = $teacherModel ? $teacherModel->id : null;

                    // Compter les émargements DÉBUT et FIN séparément
                    $emargementDebutCount = 0;
                    $emargementFinCount = 0;
                    $lastEmargementTime = null;

                    if ($teacherId && $hasCoursesToday) {
                        foreach ($todayClasses as $cours) {
                            $hasDebut = \App\Models\ESBTPTeacherAttendance::where('teacher_id', $teacherId)
                                ->where('course_id', $cours->id)
                                ->whereDate('date', today())
                                ->where('type', 'start')
                                ->exists();

                            $hasFin = \App\Models\ESBTPTeacherAttendance::where('teacher_id', $teacherId)
                                ->where('course_id', $cours->id)
                                ->whereDate('date', today())
                                ->where('type', 'end')
                                ->exists();

                            if ($hasDebut) $emargementDebutCount++;
                            if ($hasFin) $emargementFinCount++;
                        }

                        // Récupérer le dernier émargement pour afficher l'heure
                        $lastEmargement = \App\Models\ESBTPTeacherAttendance::where('teacher_id', $teacherId)
                            ->whereDate('date', today())
                            ->orderBy('validated_at', 'desc')
                            ->first();

                        if ($lastEmargement) {
                            $lastEmargementTime = $lastEmargement->validated_at;
                        }
                    }

                    // États pour le KPI - comparer avec le nombre TOTAL de cours
                    $totalSeancesJour = $todayClasses->count();
                    $hasAllEmargements = ($totalSeancesJour > 0 && $emargementDebutCount === $totalSeancesJour && $emargementFinCount === $totalSeancesJour);
                    $hasOnlyDebut = ($emargementDebutCount > 0 && $emargementFinCount === 0);
                    $hasPartialFin = ($emargementDebutCount > 0 && $emargementFinCount > 0 && $emargementDebutCount > $emargementFinCount);

                    // Cas où certains cours n'ont pas encore été émargés (début = fin mais < total)
                    $hasPartialComplete = ($emargementDebutCount > 0 && $emargementFinCount > 0 &&
                                          $emargementDebutCount === $emargementFinCount &&
                                          $emargementDebutCount < $totalSeancesJour);

                    // **NOUVELLE LOGIQUE**: Vérifier si des cours sont ABSENTS (45min+ après début)
                    $expiredCourses = $todayClasses->filter(function($cours) use ($now) {
                        $courseStart = \Carbon\Carbon::parse($cours->heure_debut);
                        $limite45min = $courseStart->copy()->addMinutes(45);
                        $hasAttendance = $cours->teacherAttendance()->whereDate('validated_at', \Carbon\Carbon::today())->exists();
                        return $now->gt($limite45min) && !$hasAttendance;
                    });

                    // **NOUVELLE LOGIQUE**: Vérifier si des cours sont DISPONIBLES (0-45min après début)
                    $availableCourses = $todayClasses->filter(function($cours) use ($now) {
                        $courseStart = \Carbon\Carbon::parse($cours->heure_debut);
                        $limite45min = $courseStart->copy()->addMinutes(45);
                        $hasAttendance = $cours->teacherAttendance()->whereDate('validated_at', \Carbon\Carbon::today())->exists();
                        return $now->gte($courseStart) && $now->lte($limite45min) && !$hasAttendance;
                    });

                    $cardClass = $hasAllEmargements ? 'border-success' : (($hasOnlyDebut || $hasPartialFin || $hasPartialComplete) ? 'border-warning' : ($expiredCourses->count() > 0 ? 'border-danger' : ($availableCourses->count() > 0 ? 'border-success' : 'border-warning')));
                    $iconClass = $hasAllEmargements ? 'bg-success' : (($hasOnlyDebut || $hasPartialFin || $hasPartialComplete) ? 'bg-warning' : ($expiredCourses->count() > 0 ? 'bg-danger' : ($availableCourses->count() > 0 ? 'bg-success' : 'bg-warning')));
                @endphp
                
                <div class="card-moderne p-3 {{ $cardClass }}">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <div class="rounded-circle p-3 {{ $iconClass }} text-white">
                                <i class="fas fa-user-check fa-2x"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="fw-bold text-primary mb-1">Émargement</h6>
                            <div class="mb-2">
                                @if($hasAllEmargements)
                                    <span class="badge bg-success">✓ Tous complets</span>
                                @elseif($hasPartialComplete)
                                    <span class="badge bg-warning">✓ Complet - Cours en attente</span>
                                @elseif($hasOnlyDebut)
                                    <span class="badge bg-warning">✓ Début - Fin à faire</span>
                                @elseif($hasPartialFin)
                                    <span class="badge bg-warning">Partiellement complet</span>
                                @elseif($expiredCourses->count() > 0)
                                    <span class="badge bg-danger">Expiré</span>
                                @elseif($availableCourses->count() > 0)
                                    <span class="badge bg-success">Ouvert maintenant</span>
                                @elseif(!$hasCoursesToday)
                                    <span class="badge bg-secondary">Pas de cours</span>
                                @else
                                    <span class="badge bg-warning">En attente</span>
                                @endif
                            </div>
                            <small class="text-muted">
                                @if($hasAllEmargements)
                                    {{ $emargementDebutCount }}/{{ $totalSeancesJour }} séances - Tout émargé
                                @elseif($hasPartialComplete)
                                    {{ $emargementDebutCount }}/{{ $totalSeancesJour }} émargés - {{ $totalSeancesJour - $emargementDebutCount }} en attente
                                @elseif($hasOnlyDebut)
                                    Début émargé à {{ $lastEmargementTime->format('H:i') }}
                                @elseif($hasPartialFin)
                                    {{ $emargementFinCount }}/{{ $emargementDebutCount }} fin émargées
                                @elseif($expiredCourses->count() > 0)
                                    {{ $expiredCourses->count() }} cours manqué(s)
                                @elseif($availableCourses->count() > 0)
                                    {{ $availableCourses->count() }} cours disponible(s)
                                @else
                                    Demander le code au coordinateur
                                @endif
                            </small>
                        </div>
                    </div>
                    @if($availableCourses->count() > 0)
                        <div class="mt-3 text-center">
                            <a href="{{ route('esbtp.attendance.mark') }}" class="btn btn-success btn-sm">
                                <i class="fas fa-signature me-1"></i> Émarger maintenant
                            </a>
                        </div>
                    @elseif($hasOnlyDebut || $hasPartialFin)
                        <div class="mt-3 text-center">
                            <a href="{{ route('esbtp.attendance.mark') }}" class="btn btn-warning btn-sm">
                                <i class="fas fa-signature me-1"></i> Émarger FIN
                            </a>
                        </div>
                    @elseif(!$hasAllEmargements && !$hasOnlyDebut && isset($dailyCode) && $dailyCode && !$expiredCourses->count())
                        <div class="mt-3 text-center">
                            <a href="{{ route('esbtp.attendance.mark') }}" class="btn btn-primary btn-sm">
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
                @php
                    // Filtrer les appels en attente pour exclure les cours expirés
                    $now = \Carbon\Carbon::now();
                    $validPendingRollCalls = $pendingRollCalls->filter(function($cours) use ($now) {
                        $courseEnd = \Carbon\Carbon::parse($cours->heure_fin);
                        $expiredWindow = $courseEnd->copy()->addMinutes(30); // 30 min après la fin pour faire l'appel
                        return $now->lte($expiredWindow);
                    });
                    
                    $expiredRollCalls = $pendingRollCalls->filter(function($cours) use ($now) {
                        $courseEnd = \Carbon\Carbon::parse($cours->heure_fin);
                        $expiredWindow = $courseEnd->copy()->addMinutes(30);
                        return $now->gt($expiredWindow);
                    });
                @endphp
                
                <div class="card-moderne p-3 {{ $validPendingRollCalls->count() > 0 ? 'border-info' : ($expiredRollCalls->count() > 0 ? 'border-danger' : 'border-secondary') }}">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <div class="rounded-circle p-3 {{ $validPendingRollCalls->count() > 0 ? 'bg-info' : ($expiredRollCalls->count() > 0 ? 'bg-danger' : 'bg-secondary') }} text-white">
                                <i class="fas fa-list-check fa-2x"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="fw-bold text-primary mb-1">Appels</h6>
                            <div class="h4 mb-1">{{ $validPendingRollCalls->count() }}</div>
                            <small class="text-muted">
                                @if($validPendingRollCalls->count() > 0)
                                    En attente
                                @elseif($expiredRollCalls->count() > 0)
                                    {{ $expiredRollCalls->count() }} expiré(s)
                                @else
                                    À jour
                                @endif
                            </small>
                        </div>
                    </div>
                    @if($validPendingRollCalls->count() > 0)
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
                                        $now = \Carbon\Carbon::now();
                                        $courseStart = \Carbon\Carbon::parse($cours->heure_debut);
                                        $courseEnd = \Carbon\Carbon::parse($cours->heure_fin);

                                        // Récupérer le daily code actif
                                        $dailyCode = \App\Models\ESBTPDailyCode::where('status', 'active')
                                            ->where('is_active', true)
                                            ->whereDate('created_at', now()->toDateString())
                                            ->first();

                                        // Récupérer le teacher_id correct (ESBTPTeacher.id pas User.id)
                                        $teacherModel = \App\Models\ESBTPTeacher::where('user_id', Auth::id())->first();
                                        $teacherId = $teacherModel ? $teacherModel->id : null;

                                        // Vérifier émargements DÉBUT et FIN
                                        $emargementDebut = null;
                                        $emargementFin = null;

                                        // Chercher les émargements du jour (peu importe le daily_code_id)
                                        if ($teacherId) {
                                            $emargementDebut = \App\Models\ESBTPTeacherAttendance::where('teacher_id', $teacherId)
                                                ->where('course_id', $cours->id)
                                                ->whereDate('date', today())
                                                ->where('type', 'start')
                                                ->first();

                                            $emargementFin = \App\Models\ESBTPTeacherAttendance::where('teacher_id', $teacherId)
                                                ->where('course_id', $cours->id)
                                                ->whereDate('date', today())
                                                ->where('type', 'end')
                                                ->first();
                                        }

                                        // FENÊTRES D'ÉMARGEMENT
                                        $limite20min = $courseStart->copy()->addMinutes(20);
                                        $limite45min = $courseStart->copy()->addMinutes(45);
                                        $fenetreClotureDebut = $courseEnd->copy()->subMinutes(20);
                                        $fenetreClotureFin = $courseEnd->copy()->addMinutes(30);

                                        // Vérifier appels étudiants
                                        $hasStudentCall = \App\Models\ESBTPAttendance::where('seance_cours_id', $cours->id)->exists();
                                        $isCompleted = $cours->status === 'completed';

                                        // États émargement DÉBUT
                                        $isTooEarly = $now->lt($courseStart) && !$emargementDebut;
                                        $canMarkStart = !$emargementDebut && $now->gte($courseStart) && $now->lte($limite45min);
                                        $isStartPresent = $now->gte($courseStart) && $now->lte($limite20min);
                                        $isStartLate = $now->gt($limite20min) && $now->lte($limite45min);
                                        $isStartExpired = !$emargementDebut && $now->gt($limite45min);

                                        // États émargement FIN
                                        $canMarkEnd = $emargementDebut && !$emargementFin && $now->gte($fenetreClotureDebut) && $now->lte($fenetreClotureFin);
                                        $isEndNotYet = $emargementDebut && !$emargementFin && $now->lt($fenetreClotureDebut);
                                        $isEndExpired = $emargementDebut && !$emargementFin && $now->gt($fenetreClotureFin);

                                        // État global
                                        $bothEmargementsDone = $emargementDebut && $emargementFin;
                                        $hasTeacherAttendance = $emargementDebut !== null; // Pour compatibilité

                                        $isAppelExpired = $now->gt($courseEnd->copy()->addMinutes(30)) && !$hasStudentCall;
                                        $isCourseActive = $now->between($courseStart, $courseEnd);
                                    @endphp
                                    
                                    @if($isCompleted)
                                        <span class="status-badge present">✓ Terminé</span>
                                    @elseif($bothEmargementsDone)
                                        <span class="status-badge present">
                                            <i class="fas fa-check-double"></i> Émargé (complet)
                                        </span>
                                    @elseif($emargementDebut && !$emargementFin)
                                        @if($canMarkEnd)
                                            <span class="status-badge" style="background-color: rgba(34, 197, 94, 0.1); color: #16a34a; border: 1px solid rgba(34, 197, 94, 0.2);">
                                                <i class="fas fa-check"></i> Émarger FIN
                                            </span>
                                        @elseif($isEndNotYet)
                                            <span class="status-badge warning">
                                                <i class="fas fa-clock"></i> Début émargé
                                            </span>
                                        @elseif($isEndExpired)
                                            <span class="status-badge danger">
                                                <i class="fas fa-ban"></i> Fin manquée
                                            </span>
                                        @endif
                                    @elseif($canMarkStart)
                                        @if($isStartPresent)
                                            <span class="status-badge" style="background-color: rgba(34, 197, 94, 0.1); color: #16a34a; border: 1px solid rgba(34, 197, 94, 0.2);">
                                                <i class="fas fa-check"></i> Disponible - PRÉSENT
                                            </span>
                                        @elseif($isStartLate)
                                            <span class="status-badge late">
                                                <i class="fas fa-exclamation-triangle"></i> Disponible - RETARD
                                            </span>
                                        @endif
                                    @elseif($isStartExpired)
                                        <span class="status-badge absent"><i class="fas fa-ban"></i> ABSENT - Délai dépassé</span>
                                    @elseif($isTooEarly)
                                        <span class="status-badge pending"><i class="fas fa-clock"></i> Trop tôt</span>
                                    @elseif($isCourseActive)
                                        <span class="status-badge late">Cours en cours</span>
                                    @else
                                        <span class="status-badge pending">Programmé</span>
                                    @endif
                                </div>
                                <div class="course-actions">
                                    @if($isCompleted)
                                        <span class="text-success small">
                                            <i class="fas fa-check-double"></i> Cours terminé
                                        </span>
                                    @elseif($bothEmargementsDone && $hasStudentCall)
                                        <span class="text-success small">
                                            <i class="fas fa-check-double"></i> Séance complète
                                        </span>
                                    @elseif($canMarkEnd)
                                        <a href="{{ route('esbtp.attendance.mark') }}" class="quick-action-btn" style="background-color: var(--primary); color: white;">
                                            <i class="fas fa-signature"></i> Émarger FIN
                                        </a>
                                    @elseif($isEndNotYet)
                                        <span class="text-warning small">
                                            <i class="fas fa-clock"></i> Fin à {{ $fenetreClotureDebut->format('H:i') }}
                                        </span>
                                    @elseif($isEndExpired)
                                        <span class="text-danger small">
                                            <i class="fas fa-ban"></i> Fin manquée
                                        </span>
                                    @elseif($emargementDebut && !$hasStudentCall)
                                        <a href="{{ route('teacher.select-call-type', $cours->id) }}" class="quick-action-btn">
                                            <i class="fas fa-list-check"></i> Faire l'appel
                                        </a>
                                    @elseif($canMarkStart && $isStartPresent)
                                        <a href="{{ route('esbtp.attendance.mark') }}" class="quick-action-btn" style="background-color: var(--success); color: white;">
                                            <i class="fas fa-signature"></i> Émarger DÉBUT
                                        </a>
                                    @elseif($canMarkStart && $isStartLate)
                                        <a href="{{ route('esbtp.attendance.mark') }}" class="quick-action-btn" style="background-color: var(--warning); color: white;">
                                            <i class="fas fa-signature"></i> Émarger (Retard)
                                        </a>
                                    @elseif($isStartExpired)
                                        <span class="text-danger small">
                                            <i class="fas fa-ban"></i> ABSENT - Trop tard
                                        </span>
                                    @elseif($isTooEarly)
                                        <span class="text-muted small">
                                            <i class="fas fa-hourglass-half"></i> Pas encore ouvert
                                        </span>
                                    @else
                                        <span class="text-muted small">
                                            <i class="fas fa-clock"></i> En attente
                                        </span>
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
        @if(isset($validPendingRollCalls) && $validPendingRollCalls->count() > 0)
            <div id="pending-roll-calls" class="main-card urgent">
                <div class="main-card-header">
                    <div class="main-card-title">
                        <i class="fas fa-exclamation-circle"></i>
                        Appels en attente
                    </div>
                    <div class="main-card-subtitle">{{ $validPendingRollCalls->count() }} cours nécessitent un appel</div>
                </div>
                <div class="main-card-body">
                    <div class="urgent-list">
                        @foreach($validPendingRollCalls as $cours)
                            @php
                                $now = \Carbon\Carbon::now();
                                $courseEnd = \Carbon\Carbon::parse($cours->heure_fin);
                                $expiredWindow = $courseEnd->copy()->addMinutes(30);
                                $isStillValid = $now->lte($expiredWindow);
                            @endphp
                            
                            @if($isStillValid)
                                <div class="urgent-item">
                                    <div class="urgent-info">
                                        <div class="urgent-title">
                                            {{ $cours->matiere->name ?? 'Matière inconnue' }} - {{ $cours->classe->name ?? 'Classe inconnue' }}
                                        </div>
                                        <div class="urgent-time">
                                            Débuté à {{ \Carbon\Carbon::parse($cours->heure_debut)->format('H:i') }}
                                            @if($now->gt($courseEnd))
                                                <span class="text-warning ms-2">
                                                    <i class="fas fa-clock"></i> 
                                                    Expire dans {{ $expiredWindow->diffForHumans($now, true) }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    <a href="{{ route('teacher.roll-call', $cours->id) }}" class="btn-urgent-action">
                                        <i class="fas fa-list-check"></i>
                                        Faire l'appel
                                    </a>
                                </div>
                            @endif
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
            <a href="{{ route('teacher.profile') }}" class="quick-action-card">
                <i class="fas fa-user-circle"></i>
                <span>Mon Profil</span>
            </a>
            <a href="{{ route('teacher.timetable') }}" class="quick-action-card">
                <i class="fas fa-calendar-alt"></i>
                <span>Mon emploi du temps</span>
            </a>
            <a href="{{ route('esbtp.attendance.mark') }}" class="quick-action-card">
                <i class="fas fa-user-check"></i>
                <span>Émargement</span>
            </a>
            <a href="{{ route('teacher.grades') }}" class="quick-action-card">
                <i class="fas fa-edit"></i>
                <span>Saisir des notes</span>
            </a>
            <a href="{{ route('teacher.availability') }}" class="quick-action-card">
                <i class="fas fa-calendar-check"></i>
                <span>Mes disponibilités</span>
            </a>
            <a href="{{ route('esbtp.annonces.index') }}" class="quick-action-card">
                <i class="fas fa-bullhorn"></i>
                <span>Annonces</span>
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

    // Auto-refresh dashboard every 2 minutes to update course statuses
    setInterval(function() {
        // Only refresh if no modals are open and no forms are being filled
        const modals = document.querySelectorAll('.modal.show');
        const activeInputs = document.querySelectorAll('input:focus, textarea:focus, select:focus');
        
        if (modals.length === 0 && activeInputs.length === 0) {
            window.location.reload();
        }
    }, 120000); // 2 minutes

    // Add visual countdown for expiring courses/attendance windows
    function updateTimeBasedElements() {
        const now = new Date();
        
        // Update status badges based on current time
        $('.course-item').each(function() {
            const timeDisplay = $(this).find('.time-display').text();
            if (timeDisplay && timeDisplay.includes(' - ')) {
                const [startTime, endTime] = timeDisplay.split(' - ');
                
                // Parse times
                const today = new Date();
                const [startHour, startMin] = startTime.split(':');
                const [endHour, endMin] = endTime.split(':');
                
                const courseStart = new Date(today);
                courseStart.setHours(parseInt(startHour), parseInt(startMin), 0, 0);
                
                const courseEnd = new Date(today);
                courseEnd.setHours(parseInt(endHour), parseInt(endMin), 0, 0);
                
                const earlyWindow = new Date(courseStart);
                earlyWindow.setMinutes(courseStart.getMinutes() - 30);
                
                const lateWindow = new Date(courseEnd);
                lateWindow.setMinutes(courseEnd.getMinutes() + 15);
                
                const statusBadge = $(this).find('.status-badge');
                
                // Add time-based visual indicators
                if (now > lateWindow && !statusBadge.hasClass('present')) {
                    statusBadge.removeClass('pending late').addClass('absent');
                    statusBadge.text('Expiré');
                } else if (now >= earlyWindow && now <= lateWindow && !statusBadge.hasClass('present')) {
                    if (statusBadge.hasClass('pending')) {
                        statusBadge.addClass('available-pulse');
                    }
                }
            }
        });
    }

    // Update every minute
    setInterval(updateTimeBasedElements, 60000);
    
    // Run once on load
    updateTimeBasedElements();
});
</script>

<style>
.available-pulse {
    animation: pulse-green 2s infinite;
}

@keyframes pulse-green {
    0% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7); }
    70% { box-shadow: 0 0 0 10px rgba(34, 197, 94, 0); }
    100% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
}
</style>
@endsection

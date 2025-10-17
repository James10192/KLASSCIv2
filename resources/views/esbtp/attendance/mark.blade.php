@extends('layouts.app')

@section('title', 'Émargement des Cours')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    .dashboard-container {
        background-color: var(--background);
        min-height: 100vh;
        padding: var(--space-lg);
    }

    .attendance-page-header {
        background-color: var(--surface);
        border-radius: var(--radius-medium);
        padding: var(--space-lg);
        margin-bottom: var(--space-lg);
        box-shadow: var(--shadow-card);
        text-align: center;
    }

    .page-title {
        font-size: var(--title-main);
        font-weight: 700;
        color: var(--primary);
        margin: 0 0 var(--space-sm) 0;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: var(--space-md);
    }

    .page-subtitle {
        color: var(--text-secondary);
        font-size: var(--text-normal);
        margin: 0;
    }

    .courses-section {
        background-color: var(--surface);
        border-radius: var(--radius-medium);
        box-shadow: var(--shadow-card);
        overflow: hidden;
    }

    .section-header {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        padding: var(--space-lg);
        display: flex;
        align-items: center;
        gap: var(--space-sm);
    }

    .section-title {
        font-size: var(--text-normal);
        font-weight: 700;
        margin: 0;
        flex: 1;
    }

    .date-badge {
        background-color: rgba(255, 255, 255, 0.2);
        padding: var(--space-xs) var(--space-sm);
        border-radius: var(--radius-small);
        font-size: var(--text-small);
        font-weight: 600;
    }

    .courses-grid {
        padding: var(--space-lg);
        display: grid;
        gap: var(--space-md);
    }

    .course-card {
        display: grid;
        grid-template-columns: 140px 1fr auto auto;
        gap: var(--space-md);
        align-items: center;
        padding: var(--space-md) var(--space-lg);
        background: linear-gradient(135deg, rgba(248, 250, 252, 0.8), rgba(241, 245, 249, 0.4));
        border: 1px solid rgba(0, 0, 0, 0.05);
        border-radius: var(--radius-medium);
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .course-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-hover);
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(248, 250, 252, 0.6));
    }

    .course-time {
        text-align: center;
        padding: var(--space-sm);
        background-color: var(--primary);
        color: white;
        border-radius: var(--radius-small);
        font-family: 'Courier New', monospace;
        font-weight: 700;
        font-size: var(--text-small);
    }

    .course-info {
        min-width: 0;
    }

    .course-subject {
        font-size: var(--text-normal);
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: var(--space-xs);
    }

    .course-details {
        display: flex;
        flex-wrap: wrap;
        gap: var(--space-sm);
        font-size: var(--text-small);
        color: var(--text-secondary);
    }

    .detail-item {
        display: flex;
        align-items: center;
        gap: var(--space-xs);
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        padding: var(--space-xs) var(--space-md);
        border-radius: var(--radius-large);
        font-size: var(--text-small);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        gap: var(--space-xs);
    }

    .status-badge.success {
        background-color: rgba(16, 185, 129, 0.1);
        color: var(--success);
        border: 1px solid rgba(16, 185, 129, 0.2);
    }

    .status-badge.warning {
        background-color: rgba(245, 158, 11, 0.1);
        color: var(--warning);
        border: 1px solid rgba(245, 158, 11, 0.2);
    }

    .status-badge.danger {
        background-color: rgba(239, 68, 68, 0.1);
        color: var(--danger);
        border: 1px solid rgba(239, 68, 68, 0.2);
    }

    .action-btn {
        display: inline-flex;
        align-items: center;
        gap: var(--space-xs);
        padding: var(--space-sm) var(--space-md);
        border: none;
        border-radius: var(--radius-small);
        font-size: var(--text-small);
        font-weight: 600;
        text-decoration: none;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .action-btn.primary {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
    }

    .action-btn.primary:hover:not(:disabled) {
        transform: translateY(-1px);
        box-shadow: var(--shadow-elevated);
        color: white;
    }

    .action-btn.success {
        background-color: var(--success);
        color: white;
    }

    .action-btn.danger {
        background-color: var(--danger);
        color: white;
    }

    .action-btn.secondary {
        background-color: var(--neutral);
        color: white;
    }

    .action-btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none !important;
        box-shadow: none !important;
    }

    .btn-wide {
        min-width: 160px;
        padding: var(--space-md) var(--space-lg);
        font-weight: 600;
    }

    .empty-state {
        text-align: center;
        padding: var(--space-xl);
        color: var(--text-muted);
    }

    .empty-state i {
        font-size: 4rem;
        margin-bottom: var(--space-md);
        opacity: 0.5;
        color: var(--accent-blue);
    }

    .empty-state h5 {
        color: var(--text-primary);
        margin-bottom: var(--space-sm);
    }

    /* Modal moderne */
    .modal-moderne .modal-content {
        border-radius: var(--radius-medium);
        border: none;
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
    }

    .modal-moderne .modal-header {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        border-bottom: none;
        padding: var(--space-lg);
        border-radius: var(--radius-medium) var(--radius-medium) 0 0;
    }

    .modal-moderne .modal-body {
        padding: var(--space-xl);
    }

    .code-input {
        font-family: 'Courier New', monospace;
        font-size: 1.5rem;
        font-weight: 700;
        text-align: center;
        padding: var(--space-lg);
        border: 2px solid var(--primary);
        border-radius: var(--radius-medium);
        background: var(--background);
        transition: all 0.3s ease;
        text-transform: uppercase;
        letter-spacing: 0.3em;
    }

    .code-input:focus {
        outline: none;
        box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.1);
        background: white;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .course-card {
            grid-template-columns: 1fr;
            text-align: center;
            gap: var(--space-sm);
        }
        
        .course-details {
            justify-content: center;
        }
    }
</style>
@endsection

@section('content')
<div class="dashboard-container">
    <!-- Header de la page -->
    <div class="attendance-page-header">
        <h1 class="page-title">
            <i class="fas fa-clipboard-check"></i>
            Émargement des Cours
        </h1>
        <p class="page-subtitle">Gérez votre présence pour les cours d'aujourd'hui</p>
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

    <!-- Section des cours -->
    <div class="courses-section">
        <div class="section-header">
            <i class="fas fa-calendar-day"></i>
            <h2 class="section-title">Mes Cours du Jour</h2>
            <div class="date-badge">{{ now()->format('d/m/Y') }}</div>
        </div>
        
        <div class="courses-grid">
            Debug: {{ $todayCourses->count() }} cours trouvés - User: {{ Auth::id() }}
            
            @php
                // Debug plus détaillé
                $user = Auth::user();
                $teacher = \App\Models\ESBTPTeacher::where('user_id', $user->id)->first();
                echo "<!-- Debug: User ID = {$user->id}, Teacher = " . ($teacher ? $teacher->id : 'NULL') . " -->";
            @endphp
            
            @if($todayCourses->isEmpty())
                <div class="empty-state">
                    <i class="fas fa-calendar-times"></i>
                    <h5>Aucun cours programmé aujourd'hui</h5>
                    <p>Profitez de votre journée libre ! 🎉</p>
                </div>
            @else
                @foreach($todayCourses as $course)
                    <div class="course-card">
                        <div class="course-time">
                            {{ $course->heure_debut ? \Carbon\Carbon::parse($course->heure_debut)->format('H:i') : '--:--' }}<br>
                            {{ $course->heure_fin ? \Carbon\Carbon::parse($course->heure_fin)->format('H:i') : '--:--' }}
                        </div>
                        
                        <div class="course-info">
                            <div class="course-subject">{{ $course->matiere->name ?? 'Matière non définie' }}</div>
                            <div class="course-details">
                                <div class="detail-item">
                                    <i class="fas fa-users"></i>
                                    <span>{{ $course->classe->name ?? 'Classe non définie' }}</span>
                                </div>
                                @if($course->salle)
                                    <div class="detail-item">
                                        <i class="fas fa-door-open"></i>
                                        <span>{{ $course->salle }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="course-status">
                            @php
                                $now = \Carbon\Carbon::now();
                                $courseStart = \Carbon\Carbon::parse($course->heure_debut);
                                $courseEnd = \Carbon\Carbon::parse($course->heure_fin);

                                // Récupérer l'ID enseignant (ESBTPTeacher, PAS User!)
                                $user = Auth::user();
                                $teacher = \App\Models\ESBTPTeacher::where('user_id', $user->id)->first();
                                $teacherId = $teacher ? $teacher->id : null;

                                $dailyCode = \App\Models\ESBTPDailyCode::where('status', 'active')
                                    ->where('is_active', true)
                                    ->whereDate('created_at', now()->toDateString())
                                    ->first();

                                $emargementDebut = null;
                                $emargementFin = null;

                                if ($teacherId) {
                                    $emargementDebut = \App\Models\ESBTPTeacherAttendance::where('teacher_id', $teacherId)
                                        ->where('course_id', $course->id)
                                        ->whereDate('date', today())
                                        ->where('type', 'start')
                                        ->first();

                                    $emargementFin = \App\Models\ESBTPTeacherAttendance::where('teacher_id', $teacherId)
                                        ->where('course_id', $course->id)
                                        ->whereDate('date', today())
                                        ->where('type', 'end')
                                        ->first();
                                }

                                // FENÊTRES D'ÉMARGEMENT DÉBUT
                                $limite20min = $courseStart->copy()->addMinutes(20);
                                $limite45min = $courseStart->copy()->addMinutes(45);

                                // FENÊTRE D'ÉMARGEMENT FIN
                                $fenetreClotureDebut = $courseEnd->copy()->subMinutes(20);
                                $fenetreClotureFin = $courseEnd->copy()->addMinutes(30);

                                // États pour émargement DÉBUT
                                $isTooEarly = $now->lt($courseStart);
                                $canMarkStart = !$emargementDebut && $now->gte($courseStart) && $now->lte($limite45min);
                                $isStartPresent = $now->gte($courseStart) && $now->lte($limite20min);
                                $isStartLate = $now->gt($limite20min) && $now->lte($limite45min);
                                $isStartExpired = !$emargementDebut && $now->gt($limite45min);

                                // États pour émargement FIN
                                $canMarkEnd = $emargementDebut && !$emargementFin && $now->gte($fenetreClotureDebut) && $now->lte($fenetreClotureFin);
                                $isEndNotYet = $emargementDebut && !$emargementFin && $now->lt($fenetreClotureDebut);
                                $isEndExpired = $emargementDebut && !$emargementFin && $now->gt($fenetreClotureFin);

                                // État global
                                $bothDone = $emargementDebut && $emargementFin;
                            @endphp

                            @if($bothDone)
                                <span class="status-badge success">
                                    <i class="fas fa-check-double"></i>
                                    Émargé (complet)
                                </span>
                            @elseif($emargementDebut && !$emargementFin)
                                @if($isEndNotYet)
                                    <span class="status-badge warning">
                                        <i class="fas fa-clock"></i>
                                        Début émargé
                                    </span>
                                @elseif($canMarkEnd)
                                    <span class="status-badge success" style="background-color: rgba(34, 197, 94, 0.1); color: #16a34a; border: 1px solid rgba(34, 197, 94, 0.2);">
                                        <i class="fas fa-check"></i>
                                        Émarger FIN
                                    </span>
                                @elseif($isEndExpired)
                                    <span class="status-badge danger">
                                        <i class="fas fa-ban"></i>
                                        Fin manquée
                                    </span>
                                @endif
                            @elseif($canMarkStart)
                                @if($isStartPresent)
                                    <span class="status-badge success" style="background-color: rgba(34, 197, 94, 0.1); color: #16a34a; border: 1px solid rgba(34, 197, 94, 0.2);">
                                        <i class="fas fa-check"></i>
                                        Disponible - PRÉSENT
                                    </span>
                                @elseif($isStartLate)
                                    <span class="status-badge warning">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        Disponible - RETARD
                                    </span>
                                @endif
                            @elseif($isStartExpired)
                                <span class="status-badge danger">
                                    <i class="fas fa-ban"></i>
                                    ABSENT - Délai dépassé
                                </span>
                            @elseif($isTooEarly)
                                <span class="status-badge" style="background-color: rgba(100, 116, 139, 0.1); color: #64748b; border: 1px solid rgba(100, 116, 139, 0.2);">
                                    <i class="fas fa-clock"></i>
                                    Trop tôt
                                </span>
                            @endif
                        </div>

                        <div class="course-actions">
                            @if($bothDone)
                                <button type="button" class="action-btn success btn-wide" disabled>
                                    <i class="fas fa-check-double"></i>
                                    Émargement complet
                                </button>
                            @elseif($canMarkEnd)
                                <button type="button" class="action-btn primary btn-wide"
                                        data-bs-toggle="modal"
                                        data-bs-target="#markAttendanceModal"
                                        data-course-id="{{ $course->id }}">
                                    <i class="fas fa-signature"></i>
                                    Émarger FIN
                                </button>
                            @elseif($isEndNotYet)
                                <button type="button" class="action-btn secondary btn-wide" disabled>
                                    <i class="fas fa-clock"></i>
                                    Fin à {{ $fenetreClotureDebut->format('H:i') }}
                                </button>
                            @elseif($isEndExpired)
                                <button type="button" class="action-btn danger btn-wide" disabled>
                                    <i class="fas fa-ban"></i>
                                    Fin manquée
                                </button>
                            @elseif($canMarkStart)
                                <button type="button" class="action-btn {{ $isStartPresent ? 'primary' : 'warning' }} btn-wide"
                                        data-bs-toggle="modal"
                                        data-bs-target="#markAttendanceModal"
                                        data-course-id="{{ $course->id }}">
                                    <i class="fas fa-signature"></i>
                                    {{ $isStartPresent ? 'Émarger DÉBUT' : 'Émarger (Retard)' }}
                                </button>
                            @elseif($isStartExpired)
                                <button type="button" class="action-btn danger btn-wide" disabled>
                                    <i class="fas fa-ban"></i>
                                    ABSENT - Trop tard
                                </button>
                            @elseif($isTooEarly)
                                <button type="button" class="action-btn secondary btn-wide" disabled>
                                    <i class="fas fa-hourglass-half"></i>
                                    Pas encore ouvert
                                </button>
                            @else
                                <button type="button" class="action-btn secondary btn-wide" disabled>
                                    <i class="fas fa-clock"></i>
                                    En attente
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
</div>

@push('modals')
<!-- Modal d'Émargement Moderne -->
<div class="modal fade modal-moderne" id="markAttendanceModal" tabindex="-1" aria-labelledby="markAttendanceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-signature me-2"></i>
                    Émarger le Cours
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('esbtp.attendance.mark') }}" id="attendanceForm">
                @csrf
                <input type="hidden" name="course_id" id="courseId">
                <div class="modal-body">
                    <div class="text-center mb-4">
                        <i class="fas fa-user-check" style="font-size: 3rem; color: var(--primary); margin-bottom: var(--space-md);"></i>
                        <h6 style="color: var(--text-primary); margin-bottom: var(--space-sm);">Confirmez votre présence</h6>
                        <p style="color: var(--text-secondary); margin: 0;">Saisissez le code fourni par l'administration</p>
                    </div>
                    
                    <div class="mb-4">
                        <label for="attendanceCode" class="form-label fw-bold text-center d-block" style="color: var(--text-primary);">
                            <i class="fas fa-key me-2"></i>Code d'Émargement
                        </label>
                        <input type="text" class="code-input form-control" id="attendanceCode"
                               name="code" required maxlength="6"
                               placeholder="XXXXXX" autocomplete="off">
                        <div class="form-text text-center mt-3" style="color: var(--text-secondary);">
                            <i class="fas fa-info-circle me-1"></i>
                            Code à 6 caractères fourni par l'administration
                        </div>
                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-center gap-3">
                    <button type="button" class="btn-acasi secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Annuler
                    </button>
                    <button type="submit" class="btn-acasi primary" id="submitAttendance">
                        <i class="fas fa-check me-2"></i>
                        Confirmer ma présence
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            if (alert.classList.contains('show')) {
                alert.classList.remove('show');
                setTimeout(() => alert.remove(), 150);
            }
        });
    }, 5000);

    // Sélection du modal et du champ caché
    const modal = document.getElementById('markAttendanceModal');
    const courseIdInput = document.getElementById('courseId');
    const attendanceForm = document.getElementById('attendanceForm');
    const submitButton = document.getElementById('submitAttendance');

    if (modal) {
        modal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            if (button && courseIdInput) {
                const courseId = button.getAttribute('data-course-id');
                courseIdInput.value = courseId;
            }
            
            // Focus automatique sur le champ code après l'ouverture
            setTimeout(function() {
                const codeInput = document.getElementById('attendanceCode');
                if (codeInput) {
                    codeInput.focus();
                    codeInput.value = ''; // Reset le champ
                }
            }, 300);
        });

        // Format code input automatically (uppercase, max 6 chars)
        const codeInput = document.getElementById('attendanceCode');
        if (codeInput) {
            codeInput.addEventListener('input', function(e) {
                let value = e.target.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
                if (value.length > 6) value = value.substring(0, 6);
                e.target.value = value;
                
                // Auto-submit when 6 characters are entered
                if (value.length === 6) {
                    setTimeout(() => {
                        if (attendanceForm) attendanceForm.submit();
                    }, 500);
                }
            });

            // Handle form submission
            attendanceForm.addEventListener('submit', function(e) {
                if (codeInput.value.length !== 6) {
                    e.preventDefault();
                    codeInput.focus();
                    codeInput.style.borderColor = 'var(--danger)';
                    setTimeout(() => {
                        codeInput.style.borderColor = '';
                    }, 3000);
                    return;
                }
                
                // Disable button and show loading state
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Vérification...';
            });
        }
    }

    // Add hover effects to course cards
    const courseCards = document.querySelectorAll('.course-card');
    courseCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-3px)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });

    // Auto-refresh page every 2 minutes to update course statuses
    setInterval(function() {
        // Only refresh if no modal is open
        const modals = document.querySelectorAll('.modal.show');
        if (modals.length === 0) {
            window.location.reload();
        }
    }, 120000); // 2 minutes

    // Update time-based statuses every minute
    setInterval(function() {
        const now = new Date();
        const courseCards = document.querySelectorAll('.course-card');
        
        courseCards.forEach(card => {
            const timeElement = card.querySelector('.course-time');
            if (timeElement) {
                const timeText = timeElement.textContent.trim().split('\n');
                const startTime = timeText[0];
                const endTime = timeText[1];
                
                if (startTime && endTime) {
                    const statusElement = card.querySelector('.status-badge');
                    const actionButton = card.querySelector('.action-btn');
                    
                    // Parse course times (format: HH:MM)
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
                    
                    // Update status based on current time
                    const isInWindow = now >= earlyWindow && now <= lateWindow;
                    const isExpired = now > lateWindow;
                    const isTooEarly = now < earlyWindow;
                    const isAttended = statusElement && statusElement.textContent.includes('Émargé');
                    
                    if (actionButton && !isAttended) {
                        if (isExpired) {
                            // Course has expired
                            statusElement.className = 'status-badge danger';
                            statusElement.innerHTML = '<i class="fas fa-times-circle"></i> Manqué';
                            actionButton.className = 'action-btn danger btn-wide';
                            actionButton.innerHTML = '<i class="fas fa-times-circle"></i> Cours manqué';
                            actionButton.disabled = true;
                        } else if (isInWindow && !actionButton.disabled) {
                            // Course is available for attendance
                            statusElement.className = 'status-badge success';
                            statusElement.style.cssText = 'background-color: rgba(34, 197, 94, 0.1); color: #16a34a; border: 1px solid rgba(34, 197, 94, 0.2);';
                            statusElement.innerHTML = '<i class="fas fa-play-circle"></i> Disponible';
                            actionButton.className = 'action-btn primary btn-wide';
                            actionButton.innerHTML = '<i class="fas fa-signature"></i> Émarger maintenant';
                        }
                    }
                }
            }
        });
    }, 60000); // 1 minute
});
</script>
@endpush
@endsection

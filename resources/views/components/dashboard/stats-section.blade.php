@props(['stats'])

<div class="row mb-4">
    <div class="col-12">
        <h5 class="mb-3">
            <i class="fas fa-chart-bar me-2 text-primary"></i>
            Statistiques Principales
        </h5>
    </div>
</div>

<div class="row mb-4">
    <!-- Cours Programmés -->
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="config-matiere-card configured" style="background: var(--surface); border: 1px solid #e5e7eb;">
            <div class="d-flex align-items-center">
                <div class="flex-shrink-0 me-3">
                    <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="fas fa-calendar-alt text-white"></i>
                    </div>
                </div>
                <div class="flex-grow-1">
                    <h4 class="mb-1 text-primary fw-bold">{{ $stats['scheduled_courses_today'] ?? 0 }}</h4>
                    <p class="mb-0 text-muted small">Cours programmés</p>
                    <small class="text-primary">Séances du {{ \Carbon\Carbon::today()->format('d/m/Y') }}</small>
                </div>
            </div>
            <div class="mt-2 pt-2 border-top">
                <small class="text-muted">
                    <i class="fas fa-clock me-1"></i>
                    Dernière MAJ: {{ \Carbon\Carbon::now()->format('H:i') }}
                </small>
            </div>
        </div>
    </div>

    <!-- Émargements Effectués -->
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="config-matiere-card {{ ($stats['teacher_attendance_rate'] ?? 0) >= 80 ? 'configured' : '' }}" style="background: var(--surface); border: 1px solid #e5e7eb;">
            <div class="d-flex align-items-center">
                <div class="flex-shrink-0 me-3">
                    <div class="rounded-circle bg-success d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="fas fa-user-check text-white"></i>
                    </div>
                </div>
                <div class="flex-grow-1">
                    <h4 class="mb-1 text-success fw-bold">{{ $stats['teacher_attendances_today'] ?? 0 }}</h4>
                    <p class="mb-0 text-muted small">Émargements enseignants</p>
                    <div class="progress mt-2" style="height: 4px;">
                        <div class="progress-bar bg-success" style="width: {{ $stats['teacher_attendance_rate'] ?? 0 }}%"></div>
                    </div>
                </div>
            </div>
            <div class="mt-2 pt-2 border-top">
                <small class="text-success fw-bold">{{ $stats['teacher_attendance_rate'] ?? 0 }}% complétés</small>
            </div>
        </div>
    </div>

    <!-- Appels Terminés -->
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="config-matiere-card" style="background: var(--surface); border: 1px solid #e5e7eb;">
            <div class="d-flex align-items-center">
                <div class="flex-shrink-0 me-3">
                    <div class="rounded-circle bg-info d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="fas fa-clipboard-check text-white"></i>
                    </div>
                </div>
                <div class="flex-grow-1">
                    <h4 class="mb-1 text-info fw-bold">{{ $stats['roll_calls_completed_today'] ?? 0 }}</h4>
                    <p class="mb-0 text-muted small">Appels d'étudiants</p>
                    <small class="text-info">{{ $stats['students_present_today'] ?? 0 }} étudiants présents</small>
                </div>
            </div>
            <div class="mt-2 pt-2 border-top">
                <small class="text-info fw-bold">{{ $stats['student_attendance_rate'] ?? 0 }}% présence</small>
            </div>
        </div>
    </div>

    <!-- Enseignants Actifs -->
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="config-matiere-card {{ ($stats['delays_today'] ?? 0) == 0 ? 'configured' : '' }}" style="background: var(--surface); border: 1px solid #e5e7eb;">
            <div class="d-flex align-items-center">
                <div class="flex-shrink-0 me-3">
                    <div class="rounded-circle bg-warning d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="fas fa-chalkboard-teacher text-white"></i>
                    </div>
                </div>
                <div class="flex-grow-1">
                    <h4 class="mb-1 text-warning fw-bold">{{ $stats['active_teachers_today'] ?? 0 }}</h4>
                    <p class="mb-0 text-muted small">Enseignants actifs</p>
                    @if(($stats['delays_today'] ?? 0) > 0)
                        <small class="text-warning">{{ $stats['delays_today'] }} en retard</small>
                    @else
                        <small class="text-success">Tous à jour</small>
                    @endif
                </div>
            </div>
            <div class="mt-2 pt-2 border-top">
                @if(($stats['delays_today'] ?? 0) > 0)
                    <small class="text-warning">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        Attention requise
                    </small>
                @else
                    <small class="text-success">
                        <i class="fas fa-check-circle me-1"></i>
                        Situation normale
                    </small>
                @endif
            </div>
        </div>
    </div>
</div>
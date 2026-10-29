@props(['stats'])

<!-- Titre Section -->
<div class="row mb-4">
    <div class="col-12">
        <div class="section-header">
            <h5 class="section-title">
                <i class="fas fa-chart-bar me-2"></i>
                Statistiques Principales
            </h5>
            <p class="section-subtitle">Vue d'ensemble des activités du {{ \Carbon\Carbon::today()->format('d/m/Y') }}</p>
        </div>
    </div>
</div>

<!-- Grid de Statistiques -->
<div class="stats-planning">
    <!-- Cours Programmés -->
    <x-dashboard.modern-stat-card
        title="Cours Programmés"
        :value="$stats['scheduled_courses_today'] ?? 0"
        subtitle="Séances planifiées pour aujourd'hui"
        icon="fa-calendar-alt"
        type="primary"
        :footer="'<div class=\"d-flex align-items-center justify-content-between\">
            <small class=\"text-muted\">
                <i class=\"fas fa-clock me-1\"></i>
                Mis à jour: ' . \Carbon\Carbon::now()->format('H:i') . '
            </small>
            <span class=\"badge badge-outline primary\">Actuel</span>
        </div>'"
    />

    <!-- Émargements Effectués -->
    <x-dashboard.modern-stat-card
        title="Émargements Enseignants"
        :value="$stats['teacher_attendances_today'] ?? 0"
        subtitle="Enseignants ayant émargé"
        icon="fa-user-check"
        type="success"
        :progress="[
            'value' => $stats['teacher_attendance_rate'] ?? 0,
            'label' => 'Taux de réalisation',
            'percentage' => $stats['teacher_attendance_rate'] ?? 0
        ]"
        :badge="($stats['teacher_attendance_rate'] ?? 0) >= 80 ? '<i class=\"fas fa-check\"></i>' : '<i class=\"fas fa-exclamation\"></i>'"
    />

    <!-- Appels Terminés -->
    <x-dashboard.modern-stat-card
        title="Appels d'Étudiants"
        :value="$stats['roll_calls_completed_today'] ?? 0"
        subtitle="Séances avec présences saisies"
        icon="fa-clipboard-check"
        type="info"
        :footer="'<div class=\"d-flex align-items-center justify-content-between\">
            <small class=\"text-info\">
                <i class=\"fas fa-users me-1\"></i>
                ' . ($stats['students_present_today'] ?? 0) . ' étudiants présents
            </small>
            <span class=\"badge badge-outline info\">' . ($stats['student_attendance_rate'] ?? 0) . '% présence</span>
        </div>'"
    />

    <!-- Enseignants Actifs -->
    <x-dashboard.modern-stat-card
        title="Enseignants Actifs"
        :value="$stats['active_teachers_today'] ?? 0"
        subtitle="Enseignants ayant émargé aujourd'hui"
        icon="fa-chalkboard-teacher"
        type="warning"
        :badge="($stats['delays_today'] ?? 0) > 0 ? '<span class=\"text-danger\">!' . $stats['delays_today'] . '</span>' : '<i class=\"fas fa-check text-success\"></i>'"
        :footer="($stats['delays_today'] ?? 0) > 0 ? 
            '<div class=\"alert-footer warning\">
                <i class=\"fas fa-exclamation-triangle me-2\"></i>
                <span>' . $stats['delays_today'] . ' enseignant(s) en retard</span>
            </div>' : 
            '<div class=\"alert-footer success\">
                <i class=\"fas fa-check-circle me-2\"></i>
                <span>Tous les enseignants sont à jour</span>
            </div>'"
    />
</div>
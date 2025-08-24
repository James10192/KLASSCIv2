@props(['stats'])

<!-- Statistiques KPI -->
<div class="kpi-grid">
    <!-- Cours Programmés -->
    <x-dashboard.kpi-card 
        title="Cours Programmés"
        :value="$stats['scheduled_courses_today'] ?? 0"
        trend="Séances planifiées aujourd'hui"
        icon="fa-calendar-alt"
        color="var(--primary)"
    />
    
    <!-- Émargements Effectués -->
    <x-dashboard.kpi-card 
        title="Émargements"
        :value="$stats['teacher_attendances_today'] ?? 0"
        trend="Enseignants ayant émargé ({{ $stats['teacher_attendance_rate'] ?? 0 }}%)"
        icon="fa-user-check"
        color="var(--success)"
    />
    
    <!-- Appels Terminés -->
    <x-dashboard.kpi-card 
        title="Appels Étudiants"
        :value="$stats['roll_calls_completed_today'] ?? 0"
        trend="Présences saisies ({{ $stats['student_attendance_rate'] ?? 0 }}% présents)"
        icon="fa-clipboard-check"
        color="var(--accent-blue)"
    />
    
    <!-- Workflow Complet -->
    <x-dashboard.kpi-card 
        title="Workflow Complet"
        :value="$stats['courses_completed_today'] ?? 0"
        trend="Émargement + Appel terminés"
        icon="fa-check-double"
        color="var(--warning)"
    />
</div>
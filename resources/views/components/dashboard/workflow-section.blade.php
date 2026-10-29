@props(['stats'])

<!-- Titre Section -->
<div class="row mb-4">
    <div class="col-12">
        <div class="section-header">
            <h5 class="section-title">
                <i class="fas fa-project-diagram me-2"></i>
                État du Workflow Aujourd'hui
            </h5>
            <p class="section-subtitle">Progression du processus: Émargement → Appel → Validation</p>
        </div>
    </div>
</div>

<!-- Workflow Cards -->
<div class="row mb-4">
    <div class="col-12">
        <div class="combinaison-card" data-aos="fade-up">
            
            <!-- Card Header Section -->
            <div class="card-header-section">
                <div class="card-logo-info">
                    <div class="stat-icon-planning primary">
                        <i class="fas fa-project-diagram"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h3 class="stat-value-planning">Workflow</h3>
                        <h6 class="stat-label-planning">État du processus</h6>
                    </div>
                </div>
            </div>

            <!-- Card Body Section -->
            <div class="card-body-section">
                <div class="row text-center">
                    <!-- Étape 1: Cours Programmés -->
                    <div class="col-md-3 mb-3">
                        <div class="d-flex flex-column align-items-center">
                            <div class="stat-icon-planning primary" style="width: 52px; height: 52px; font-size: 20px;">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <h4 class="mb-1 text-primary mt-2">{{ $stats['scheduled_courses_today'] ?? 0 }}</h4>
                            <h6 class="mb-1">Cours Programmés</h6>
                            <small class="text-muted">Séances planifiées</small>
                        </div>
                    </div>

                    <!-- Flèche 1 -->
                    <div class="col-md-1 d-none d-md-flex align-items-center justify-content-center">
                        <i class="fas fa-arrow-right text-muted fa-lg"></i>
                    </div>

                    <!-- Étape 2: Émargements -->
                    <div class="col-md-3 mb-3">
                        <div class="d-flex flex-column align-items-center">
                            <div class="stat-icon-planning success" style="width: 52px; height: 52px; font-size: 20px;">
                                <i class="fas fa-user-check"></i>
                            </div>
                            <h4 class="mb-1 text-success mt-2">{{ $stats['teacher_attendances_today'] ?? 0 }}</h4>
                            <h6 class="mb-1">Émargements</h6>
                            <small class="text-muted">Enseignants émargés</small>
                            <div class="progress-container mt-2" style="width: 80%;">
                                <div class="progress-bar-modern">
                                    <div class="progress-fill success" style="width: {{ $stats['teacher_attendance_rate'] ?? 0 }}%"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Flèche 2 -->
                    <div class="col-md-1 d-none d-md-flex align-items-center justify-content-center">
                        <i class="fas fa-arrow-right text-muted fa-lg"></i>
                    </div>

                    <!-- Étape 3: Appels -->
                    <div class="col-md-3 mb-3">
                        <div class="d-flex flex-column align-items-center">
                            <div class="stat-icon-planning info" style="width: 52px; height: 52px; font-size: 20px;">
                                <i class="fas fa-clipboard-check"></i>
                            </div>
                            <h4 class="mb-1 text-info mt-2">{{ $stats['roll_calls_completed_today'] ?? 0 }}</h4>
                            <h6 class="mb-1">Appels Terminés</h6>
                            <small class="text-muted">Présences saisies</small>
                            @if(($stats['students_present_today'] ?? 0) > 0)
                                <small class="text-info fw-bold mt-1">{{ $stats['student_attendance_rate'] ?? 0 }}% présents</small>
                            @endif
                        </div>
                    </div>

                    <!-- Flèche 3 -->
                    <div class="col-md-1 d-none d-md-flex align-items-center justify-content-center">
                        <i class="fas fa-arrow-right text-muted fa-lg"></i>
                    </div>

                    <!-- Étape 4: Workflow Complet -->
                    <div class="col-md-3 mb-3">
                        <div class="d-flex flex-column align-items-center">
                            <div class="stat-icon-planning warning" style="width: 52px; height: 52px; font-size: 20px;">
                                <i class="fas fa-check-double"></i>
                            </div>
                            <h4 class="mb-1 text-warning mt-2">{{ $stats['courses_completed_today'] ?? 0 }}</h4>
                            <h6 class="mb-1">Workflow Complet</h6>
                            <small class="text-muted">Émargement + Appel</small>
                            @php
                                $totalCourses = $stats['scheduled_courses_today'] ?? 0;
                                $completedCourses = $stats['courses_completed_today'] ?? 0;
                                $completionRate = $totalCourses > 0 ? round(($completedCourses / $totalCourses) * 100, 1) : 0;
                            @endphp
                            <div class="progress-container mt-2" style="width: 80%;">
                                <div class="progress-bar-modern">
                                    <div class="progress-fill warning" style="width: {{ $completionRate }}%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Card Footer Section -->
            @if($totalCourses > 0)
            <div class="card-footer-section">
                <div class="d-flex align-items-center justify-content-center">
                    <div class="stat-icon-planning primary" style="width: 32px; height: 32px; font-size: 14px; margin-right: 0.75rem;">
                        <i class="fas fa-chart-pie"></i>
                    </div>
                    <div class="flex-grow-1 text-center">
                        <span class="text-muted me-2">Progression globale:</span>
                        <strong class="text-primary fs-5">{{ $completionRate }}%</strong>
                        <span class="text-muted ms-2">({{ $completedCourses }}/{{ $totalCourses }})</span>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
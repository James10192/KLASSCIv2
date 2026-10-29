@props(['stats'])

<div class="row">
    <!-- Statistiques par Matière -->
    <div class="col-lg-8 mb-4">
        <div class="config-matiere-card" style="background: var(--surface); border: 1px solid #e5e7eb;">
            <div class="row">
                <div class="col-12 mb-3">
                    <h5 class="mb-2">
                        <i class="fas fa-book-open me-2 text-primary"></i>
                        Statistiques par Matière
                    </h5>
                    <p class="text-muted mb-0">Progression des cours par matière aujourd'hui</p>
                </div>
            </div>
            
            <div class="row">
                @forelse($stats['subjects_stats'] ?? [] as $subject)
                <div class="col-lg-6 mb-3">
                    <div class="border rounded p-3 h-100">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h6 class="mb-1 text-primary">{{ $subject['matiere_name'] }}</h6>
                            @php
                                $taux = $subject['taux_completion'] ?? 0;
                                $badgeClass = $taux >= 80 ? 'success' : ($taux >= 50 ? 'warning' : 'danger');
                            @endphp
                            <span class="badge bg-{{ $badgeClass }}">{{ $taux }}%</span>
                        </div>
                        
                        <div class="row text-center small">
                            <div class="col-4">
                                <div class="fw-bold text-primary">{{ $subject['total_seances'] ?? 0 }}</div>
                                <small class="text-muted">Séances</small>
                            </div>
                            <div class="col-4">
                                <div class="fw-bold text-success">{{ $subject['emargements_effectues'] ?? 0 }}</div>
                                <small class="text-muted">Émargé</small>
                            </div>
                            <div class="col-4">
                                <div class="fw-bold text-info">{{ $subject['appels_effectues'] ?? 0 }}</div>
                                <small class="text-muted">Appels</small>
                            </div>
                        </div>
                        
                        <div class="progress mt-2" style="height: 4px;">
                            <div class="progress-bar bg-{{ $badgeClass }}" style="width: {{ $taux }}%"></div>
                        </div>
                    </div>
                </div>
                @empty
                <div class="col-12 text-center py-4">
                    <i class="fas fa-book-open fa-3x text-muted mb-2"></i>
                    <p class="text-muted">Aucune statistique par matière aujourd'hui</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Alertes et Actions -->
    <div class="col-lg-4 mb-4">
        <div class="config-matiere-card h-100" style="background: var(--surface); border: 1px solid #e5e7eb;">
            <div class="row">
                <div class="col-12 mb-3">
                    <h5 class="mb-2">
                        <i class="fas fa-bell me-2 text-primary"></i>
                        Alertes & Actions
                    </h5>
                </div>
            </div>
            
            <!-- Alertes -->
            <div class="alerts-section mb-4">
                @forelse($stats['alerts'] ?? [] as $alert)
                <div class="alert alert-{{ $alert['type'] }} border-0 shadow-sm mb-2">
                    <div class="d-flex">
                        <i class="fas fa-{{ $alert['type'] === 'warning' ? 'exclamation-triangle' : ($alert['type'] === 'danger' ? 'times-circle' : 'info-circle') }} me-2 mt-1"></i>
                        <div class="flex-grow-1">
                            <strong>{{ $alert['title'] }}</strong>
                            <p class="mb-1 small">{{ $alert['message'] }}</p>
                            @if(!empty($alert['details']))
                                <ul class="mb-0 small text-muted">
                                    @foreach(array_slice($alert['details'], 0, 3) as $detail)
                                    <li>{{ $detail }}</li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </div>
                </div>
                @empty
                <div class="alert alert-success border-0 shadow-sm">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-check-circle me-2"></i>
                        <div>
                            <strong>Situation normale</strong>
                            <p class="mb-0 small">Aucune alerte aujourd'hui</p>
                        </div>
                    </div>
                </div>
                @endforelse
            </div>

            <!-- Actions Rapides -->
            <div class="actions-section">
                <h6 class="mb-3">
                    <i class="fas fa-bolt me-2"></i>
                    Actions Rapides
                </h6>
                
                <div class="d-grid gap-2">
                    <a href="{{ route('esbtp.teacher-attendance.report') }}" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-clipboard-list me-2"></i>Rapport Émargements
                    </a>
                    <a href="{{ route('esbtp.attendances.index') }}" class="btn btn-outline-success btn-sm">
                        <i class="fas fa-users me-2"></i>Gérer Présences Étudiants
                    </a>
                    <button class="btn btn-outline-warning btn-sm" onclick="generateReport()">
                        <i class="fas fa-file-export me-2"></i>Export Journalier
                    </button>
                    <button class="btn btn-outline-info btn-sm" onclick="refreshData()">
                        <i class="fas fa-sync-alt me-2"></i>Actualiser Données
                    </button>
                </div>
                
                <!-- Stats supplémentaires -->
                <div class="mt-3 pt-3 border-top">
                    <div class="row text-center small">
                        <div class="col-6">
                            <div class="fw-bold text-primary">{{ $stats['students_total_today'] ?? 0 }}</div>
                            <small class="text-muted">Étudiants total</small>
                        </div>
                        <div class="col-6">
                            <div class="fw-bold text-success">{{ $stats['students_present_today'] ?? 0 }}</div>
                            <small class="text-muted">Présents</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
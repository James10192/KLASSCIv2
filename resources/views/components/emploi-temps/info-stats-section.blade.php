@props(['emploiTemps', 'matiereStats' => []])

<div class="row mb-4">
    <!-- Section: Informations -->
    <div class="col-lg-6">
        <div class="main-card h-100">
            <div class="main-card-header">
                <div class="main-card-title">
                    <i class="fas fa-info-circle"></i>
                    Informations
                </div>
                <div class="main-card-subtitle">Détails de l'emploi du temps</div>
            </div>
            <div class="main-card-body">
                <div class="info-list">
                    <p class="mb-2"><strong>Classe :</strong> {{ is_object($emploiTemps) && is_object($emploiTemps->classe) ? $emploiTemps->classe->name : 'Non définie' }}</p>
                    <p class="mb-2"><strong>Filière :</strong> {{ is_object($emploiTemps) && is_object($emploiTemps->classe) && is_object($emploiTemps->classe->filiere) ? $emploiTemps->classe->filiere->name : 'Non définie' }}</p>
                    <p class="mb-2"><strong>Niveau :</strong> {{ is_object($emploiTemps) && is_object($emploiTemps->classe) && is_object($emploiTemps->classe->niveau) ? $emploiTemps->classe->niveau->name : 'Non défini' }}</p>
                    <p class="mb-2"><strong>Année universitaire :</strong> {{ is_object($emploiTemps) && is_object($emploiTemps->annee) ? $emploiTemps->annee->name : 'Non définie' }}</p>
                    <p class="mb-2">
                        <strong>Période :</strong>
                        @if(isset($emploiTemps->semestre) && $emploiTemps->semestre == 'Semestre 1')
                            Semestre 1
                        @elseif(isset($emploiTemps->semestre) && $emploiTemps->semestre == 'Semestre 2')
                            Semestre 2
                        @else
                            Année complète
                        @endif
                    </p>
                    <p class="mb-2">
                        <strong>Statut :</strong>
                        @if(isset($emploiTemps->is_active) && $emploiTemps->is_active)
                            <span class="badge bg-success">Actif</span>
                        @else
                            <span class="badge bg-secondary">Inactif</span>
                        @endif
                        @if(isset($emploiTemps->is_current) && $emploiTemps->is_current)
                            <span class="badge bg-info ms-1">Courant</span>
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Section: Statistiques -->
    <div class="col-lg-6">
        <div class="main-card h-100">
            <div class="main-card-header">
                <div class="main-card-title">
                    <i class="fas fa-chart-pie"></i>
                    Statistiques
                </div>
                <div class="main-card-subtitle">Répartition des séances</div>
            </div>
            <div class="main-card-body">
                <div class="stats-grid">
                    <div class="stat-item mb-3">
                        <h6>Types de séances</h6>
                        <div class="d-flex flex-wrap gap-2">
                            <span class="badge bg-primary">{{ is_object($emploiTemps) && is_object($emploiTemps->seances) ? $emploiTemps->seances->where('type', 'course')->count() : '0' }} cours</span>
                            <span class="badge bg-success">{{ is_object($emploiTemps) && is_object($emploiTemps->seances) ? $emploiTemps->seances->where('type', 'homework')->count() : '0' }} devoirs</span>
                            <span class="badge bg-warning">{{ is_object($emploiTemps) && is_object($emploiTemps->seances) ? $emploiTemps->seances->where('type', 'break')->count() : '0' }} récréations</span>
                            <span class="badge bg-info">{{ is_object($emploiTemps) && is_object($emploiTemps->seances) ? $emploiTemps->seances->where('type', 'lunch')->count() : '0' }} pauses</span>
                        </div>
                    </div>
                    <div class="stat-item mb-3">
                        <h6>Séances par matière</h6>
                        <div style="max-height: 120px; overflow-y: auto;">
                            @if(isset($matiereStats) && is_array($matiereStats))
                                @foreach($matiereStats as $matiere => $count)
                                    <div class="d-flex justify-content-between mb-1">
                                        <span>{{ $matiere }}</span>
                                        <span class="badge bg-light text-dark">{{ $count }}</span>
                                    </div>
                                @endforeach
                            @else
                                <small class="text-muted">Aucune donnée disponible</small>
                            @endif
                        </div>
                    </div>
                    <div class="stat-item">
                        <div class="d-flex justify-content-between">
                            <span>Total séances :</span>
                            <strong>{{ is_object($emploiTemps) && is_object($emploiTemps->seances) ? $emploiTemps->seances->count() : '0' }}</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Séances actives :</span>
                            <strong>{{ is_object($emploiTemps) && is_object($emploiTemps->seances) ? $emploiTemps->seances->where('is_active', 1)->count() : '0' }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
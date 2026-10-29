@props([
    'subject' => []
])

<div class="subject-card">
    <div class="d-flex justify-content-between align-items-start">
        <div class="flex-grow-1">
            <h6 class="mb-1">{{ $subject['matiere_name'] ?? 'Non défini' }}</h6>
            <div class="row text-center mt-2">
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
        </div>
        <div class="ms-2">
            @php
                $taux = $subject['taux_completion'] ?? 0;
                $badgeClass = $taux >= 80 ? 'success' : ($taux >= 50 ? 'warning' : 'danger');
            @endphp
            <div class="badge bg-{{ $badgeClass }}">
                {{ $taux }}%
            </div>
        </div>
    </div>
</div>
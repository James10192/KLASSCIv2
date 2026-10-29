@php
    $niveauClasses = [
        'critique' => 'bg-danger text-white',
        'warning' => 'bg-warning text-dark',
        'info' => 'bg-info text-white'
    ];
    $iconesNiveau = [
        'critique' => 'fas fa-exclamation-triangle',
        'warning' => 'fas fa-exclamation-circle',
        'info' => 'fas fa-info-circle'
    ];
@endphp

<div class="col-md-6 mb-3">
    <div class="card border-0 h-100 shadow-sm {{ $niveauClasses[$alerte['niveau']] ?? 'bg-secondary text-white' }}">
        <div class="card-body p-3">
            <div class="d-flex align-items-start">
        <div class="me-3">
                    <i class="{{ $iconesNiveau[$alerte['niveau']] ?? 'fas fa-bell' }} fa-lg"></i>
        </div>
        <div class="flex-grow-1">
                    <h6 class="card-title mb-1 fw-bold">{{ $alerte['titre'] ?? 'Alerte Financière' }}</h6>
                    <p class="card-text small mb-2">{{ $alerte['message'] ?? 'Aucun message' }}</p>
                    @if(isset($alerte['valeur']))
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="small">Valeur:</span>
                            <span class="fw-bold">{{ number_format($alerte['valeur'], 0, ',', ' ') }} FCFA</span>
            </div>
                    @endif
                    @if(isset($alerte['pourcentage']))
                        <div class="progress mt-2" style="height: 4px;">
                            <div class="progress-bar bg-white" role="progressbar"
                                 style="width: {{ $alerte['pourcentage'] }}%"
                                 aria-valuenow="{{ $alerte['pourcentage'] }}"
                                 aria-valuemin="0" aria-valuemax="100">
            </div>
            </div>
            @endif
        </div>
            </div>
        </div>
    </div>
</div>

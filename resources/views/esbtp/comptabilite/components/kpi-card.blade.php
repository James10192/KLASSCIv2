@php
    $couleurs = [
        'primary' => 'bg-primary',
        'success' => 'bg-success',
        'warning' => 'bg-warning',
        'danger' => 'bg-danger',
        'info' => 'bg-info',
        'secondary' => 'bg-secondary'
    ];
    $couleurClass = $couleurs[$couleur ?? 'primary'] ?? 'bg-primary';
@endphp

<div class="col-md-3 mb-4">
    <div class="card border-0 shadow-lg hover-lift h-100 premium-glass">
        <div class="card-body p-4">
            <!-- Header avec icône et couleur -->
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="icon-container {{ $couleurClass }} rounded-circle d-flex align-items-center justify-content-center"
                     style="width: 50px; height: 50px;">
                    <i class="{{ $icone ?? 'fas fa-chart-line' }} fa-lg text-white"></i>
                </div>
                @if(isset($tendance))
                    <div class="tendance">
                        @if($tendance > 0)
                            <i class="fas fa-arrow-up text-success"></i>
                            <span class="text-success small">+{{ number_format($tendance, 1) }}%</span>
                        @elseif($tendance < 0)
                            <i class="fas fa-arrow-down text-danger"></i>
                            <span class="text-danger small">{{ number_format($tendance, 1) }}%</span>
                        @else
                            <i class="fas fa-minus text-muted"></i>
                            <span class="text-muted small">0%</span>
                        @endif
                    </div>
                @endif
            </div>

            <!-- Titre -->
            <h6 class="card-title text-muted mb-2 fw-bold text-uppercase small">{{ $titre ?? 'KPI' }}</h6>

            <!-- Valeur principale -->
            <div class="d-flex align-items-end mb-3">
                <h2 class="card-text fw-bold mb-0 {{ $couleurClass }} bg-gradient text-transparent bg-clip-text">
                    {{ $valeur ?? '0' }}
                </h2>
                @if(isset($unite))
                    <span class="text-muted ms-2 small">{{ $unite }}</span>
                @endif
            </div>

            <!-- Sous-texte descriptif -->
            @if(isset($description))
                <p class="text-muted small mb-3">{{ $description }}</p>
            @endif

            <!-- Barre de progression si objectif défini -->
            @if(isset($objectif) && isset($pourcentage))
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="small text-muted">Objectif</span>
                        <span class="small fw-bold">{{ number_format($pourcentage, 1) }}%</span>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar {{ $couleurClass }}"
                             role="progressbar"
                             style="width: {{ min(100, $pourcentage) }}%"
                             aria-valuenow="{{ $pourcentage }}"
                             aria-valuemin="0"
                             aria-valuemax="100">
                        </div>
                    </div>
                    <div class="d-flex justify-content-between mt-1">
                        <span class="small text-muted">{{ $valeur ?? '0' }}</span>
                        <span class="small text-muted">{{ $objectif ?? '0' }}</span>
                    </div>
                </div>
            @endif

            <!-- Actions rapides -->
            @if(isset($actions) && is_array($actions))
                <div class="d-flex gap-2 mt-3">
                    @foreach($actions as $action)
                        <a href="{{ $action['url'] ?? '#' }}"
                           class="btn btn-sm btn-outline-{{ $couleur ?? 'primary' }} flex-fill"
                           title="{{ $action['titre'] ?? '' }}">
                            <i class="{{ $action['icone'] ?? 'fas fa-eye' }}"></i>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>

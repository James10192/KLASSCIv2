{{-- Chart Container Component --}}
<div class="card border-0 shadow-lg rounded-4 p-4 premium-glass">
    <div class="card-body p-0">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="fw-bold mb-0">
                <i class="fas fa-chart-line text-primary me-2"></i>
                {{ $title ?? 'Graphique' }}
            </h5>

            @if(isset($actions) && $actions)
            <div class="dropdown">
                <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="fas fa-cog"></i>
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="#" onclick="refreshChart('{{ $chartId }}')">
                        <i class="fas fa-sync me-2"></i>Actualiser
                    </a></li>
                    <li><a class="dropdown-item" href="#" onclick="downloadChart('{{ $chartId }}')">
                        <i class="fas fa-download me-2"></i>Télécharger
                    </a></li>
                </ul>
            </div>
            @endif
        </div>

        <div class="position-relative" style="height: {{ $height ?? '300px' }};">
            <canvas id="{{ $chartId ?? 'chart' }}" class="w-100"></canvas>

            {{-- Loader pour le graphique --}}
            <div id="{{ $chartId ?? 'chart' }}-loader" class="position-absolute top-50 start-50 translate-middle d-none">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Chargement...</span>
                </div>
            </div>
        </div>

        @if(isset($description))
        <div class="mt-3 text-muted small">
            {{ $description }}
        </div>
        @endif
    </div>
</div>

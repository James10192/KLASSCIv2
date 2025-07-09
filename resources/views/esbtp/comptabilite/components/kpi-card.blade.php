{{-- KPI Card Component --}}
<div class="col-md-3">
    <div class="card border-0 shadow-lg rounded-4 p-4 premium-glass text-center hover-lift" id="{{ $id ?? 'kpi-card' }}">
        <div class="d-flex justify-content-center mb-3">
            <span class="d-inline-flex align-items-center justify-content-center bg-{{ $color ?? 'primary' }} bg-gradient text-white rounded-circle" style="width:56px;height:56px;font-size:1.5rem;">
                <i class="{{ $icon ?? 'fas fa-chart-bar' }}"></i>
            </span>
        </div>

        <div class="display-6 fw-bold mb-2 text-{{ $color ?? 'primary' }}" data-kpi-value>
            {{ $value ?? '0' }}
        </div>

        <div class="text-muted mb-2 fw-semibold">
            {{ $title ?? 'KPI' }}
        </div>

        @if(isset($subtitle))
        <div class="small text-secondary mb-2">
            {{ $subtitle }}
        </div>
        @endif

        @if(isset($trend))
        <div class="d-flex align-items-center justify-content-center gap-1">
            @if($trend === 'up')
                <i class="fas fa-arrow-up text-success small"></i>
                <span class="badge bg-success bg-gradient rounded-pill px-3 py-2">
                    <i class="fas fa-trending-up me-1"></i>Positif
                </span>
            @elseif($trend === 'down')
                <i class="fas fa-arrow-down text-danger small"></i>
                <span class="badge bg-danger bg-gradient rounded-pill px-3 py-2">
                    <i class="fas fa-trending-down me-1"></i>Négatif
                </span>
            @else
                <i class="fas fa-minus text-warning small"></i>
                <span class="badge bg-warning bg-gradient rounded-pill px-3 py-2">
                    <i class="fas fa-equals me-1"></i>Stable
                </span>
            @endif
        </div>
        @endif
    </div>
</div>

{{-- Alerte Financière Component --}}
<div class="col-md-6 mb-2">
    <div class="d-flex align-items-center p-3 rounded-3
        @if(($alerte['niveau'] ?? 'info') === 'critique') bg-danger bg-opacity-10 border border-danger
        @elseif(($alerte['niveau'] ?? 'info') === 'warning') bg-warning bg-opacity-10 border border-warning
        @else bg-info bg-opacity-10 border border-info
        @endif">

        <div class="me-3">
            @if(($alerte['niveau'] ?? 'info') === 'critique')
                <i class="fas fa-exclamation-circle fa-lg text-danger"></i>
            @elseif(($alerte['niveau'] ?? 'info') === 'warning')
                <i class="fas fa-exclamation-triangle fa-lg text-warning"></i>
            @else
                <i class="fas fa-info-circle fa-lg text-info"></i>
            @endif
        </div>

        <div class="flex-grow-1">
            <div class="fw-semibold mb-1
                @if(($alerte['niveau'] ?? 'info') === 'critique') text-danger
                @elseif(($alerte['niveau'] ?? 'info') === 'warning') text-warning
                @else text-info
                @endif">
                {{ $alerte['titre'] ?? 'Alerte' }}
            </div>

            <div class="small text-muted">
                {{ $alerte['message'] ?? 'Message d\'alerte' }}
            </div>

            @if(isset($alerte['valeur']))
            <div class="small mt-1">
                <strong>Valeur:</strong> {{ $alerte['valeur'] }}
                @if(isset($alerte['seuil']))
                    | <strong>Seuil:</strong> {{ $alerte['seuil'] }}
                @endif
            </div>
            @endif
        </div>

        @if(isset($alerte['action']))
        <div class="ms-2">
            <button type="button" class="btn btn-sm
                @if(($alerte['niveau'] ?? 'info') === 'critique') btn-outline-danger
                @elseif(($alerte['niveau'] ?? 'info') === 'warning') btn-outline-warning
                @else btn-outline-info
                @endif"
                onclick="handleAlerte('{{ $alerte['id'] ?? '' }}')">
                <i class="fas fa-arrow-right"></i>
            </button>
        </div>
        @endif
    </div>
</div>

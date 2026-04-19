{{-- Répartition par type de frais (AJAX-refreshable via #paiements-metrics-details) --}}
@if(isset($stats['academic_total']) && ($stats['academic_total'] > 0 || $stats['service_total'] > 0 || $stats['administrative_total'] > 0))
<div class="card-moderne mb-lg">
    <div class="p-lg">
        <div class="d-flex justify-content-between align-items-center mb-md">
            <div class="section-title mb-0">
                <i class="fas fa-chart-pie me-2"></i>
                Répartition par Type de Frais (paiements filtrés)
            </div>
        </div>

        <div class="row">
            @if($stats['academic_total'] > 0)
            <div class="col-md-4">
                <div class="resultat-card border-start border-success border-3">
                    <div class="resultat-title">Frais Académiques</div>
                    <div class="resultat-montant color-success">{{ number_format($stats['academic_total'], 0, ',', ' ') }} FCFA</div>
                    <div class="progress mb-2" style="height: 8px;">
                        <div class="progress-bar bg-success" style="width: {{ $stats['academic_total'] > 0 ? ($stats['academic_paid'] / $stats['academic_total']) * 100 : 0 }}%"></div>
                    </div>
                    <small class="text-muted">
                        Validés: {{ number_format($stats['academic_paid'], 0, ',', ' ') }} FCFA
                    </small>
                </div>
            </div>
            @endif

            @if($stats['service_total'] > 0)
            <div class="col-md-4">
                <div class="resultat-card border-start border-warning border-3">
                    <div class="resultat-title">Services Optionnels</div>
                    <div class="resultat-montant color-warning">{{ number_format($stats['service_total'], 0, ',', ' ') }} FCFA</div>
                    <div class="progress mb-2" style="height: 8px;">
                        <div class="progress-bar bg-warning" style="width: {{ $stats['service_total'] > 0 ? ($stats['service_paid'] / $stats['service_total']) * 100 : 0 }}%"></div>
                    </div>
                    <small class="text-muted">
                        Validés: {{ number_format($stats['service_paid'], 0, ',', ' ') }} FCFA
                    </small>
                </div>
            </div>
            @endif

            @if($stats['administrative_total'] > 0)
            <div class="col-md-4">
                <div class="resultat-card border-start border-info border-3">
                    <div class="resultat-title">Frais Administratifs</div>
                    <div class="resultat-montant color-info">{{ number_format($stats['administrative_total'], 0, ',', ' ') }} FCFA</div>
                    <div class="progress mb-2" style="height: 8px;">
                        <div class="progress-bar bg-info" style="width: {{ $stats['administrative_total'] > 0 ? ($stats['administrative_paid'] / $stats['administrative_total']) * 100 : 0 }}%"></div>
                    </div>
                    <small class="text-muted">
                        Validés: {{ number_format($stats['administrative_paid'], 0, ',', ' ') }} FCFA
                    </small>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endif

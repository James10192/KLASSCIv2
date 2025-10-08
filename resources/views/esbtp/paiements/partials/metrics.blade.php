<div class="kpi-grid">
    <div class="card-moderne kpi-card">
        <div class="kpi-title">Frais Académiques Payés</div>
        <div class="kpi-value color-success">{{ number_format($stats['academic_paid'], 0, ',', ' ') }} FCFA</div>
        <div class="kpi-trend positive">
            <i class="fas fa-graduation-cap"></i>
            @if($stats['academic_total'] > 0)
                {{ number_format(($stats['academic_paid'] / $stats['academic_total']) * 100, 1) }}% payé
            @else
                Aucun frais
            @endif
        </div>
    </div>
    
    <div class="card-moderne kpi-card">
        <div class="kpi-title">Services Optionnels</div>
        <div class="kpi-value color-warning">{{ number_format($stats['service_paid'], 0, ',', ' ') }} FCFA</div>
        <div class="kpi-trend">
            <i class="fas fa-cogs"></i>
            @if($stats['service_total'] > 0)
                {{ number_format(($stats['service_paid'] / $stats['service_total']) * 100, 1) }}% payé
            @else
                Aucun service
            @endif
        </div>
    </div>
    
    <div class="card-moderne kpi-card">
        <div class="kpi-title">Frais Administratifs</div>
        <div class="kpi-value color-info">{{ number_format($stats['administrative_paid'], 0, ',', ' ') }} FCFA</div>
        <div class="kpi-trend">
            <i class="fas fa-file-alt"></i>
            @if($stats['administrative_total'] > 0)
                {{ number_format(($stats['administrative_paid'] / $stats['administrative_total']) * 100, 1) }}% payé
            @else
                Aucun frais
            @endif
        </div>
    </div>

    <div class="card-moderne kpi-card">
        <div class="kpi-title">Taux de Recouvrement Global</div>
        <div class="kpi-value color-primary">{{ $stats['recovery_rate'] }}%</div>
        <div class="kpi-trend {{ $stats['recovery_rate'] >= 75 ? 'positive' : ($stats['recovery_rate'] >= 50 ? '' : 'negative') }}">
            <i class="fas fa-chart-line"></i>
            {{ number_format($stats['montant_valide'], 0, ',', ' ') }} / {{ number_format($stats['montant_total'], 0, ',', ' ') }} FCFA
        </div>
    </div>

    @if($stats['reliquats_total'] > 0)
    <div class="card-moderne kpi-card">
        <div class="kpi-title">Reliquats à Recouvrer</div>
        <div class="kpi-value color-danger">{{ number_format($stats['reliquats_total'], 0, ',', ' ') }} FCFA</div>
        <div class="kpi-trend">
            <i class="fas fa-history"></i>
            Années précédentes
        </div>
    </div>
    @endif
</div>

<div class="card-moderne mb-lg">
    <div class="p-lg">
        <div class="d-flex justify-content-between align-items-center mb-md">
            <div class="section-title mb-0">
                <i class="fas fa-chart-pie me-2"></i>
                Répartition des Paiements par Catégorie
            </div>
            <a href="{{ route('esbtp.paiements.suivi-categories') }}" class="btn-acasi secondary small">
                <i class="fas fa-chart-bar me-1"></i>Vue détaillée
            </a>
        </div>
        
        <div class="row">
            <div class="col-md-4">
                <div class="resultat-card border-start border-success border-3">
                    <div class="resultat-title">Frais Académiques</div>
                    <div class="resultat-montant color-success">{{ number_format($stats['academic_paid'], 0, ',', ' ') }} FCFA</div>
                    <div class="progress mb-2" style="height: 8px;">
                        <div class="progress-bar bg-success" style="width: {{ $stats['academic_total'] > 0 ? ($stats['academic_paid'] / $stats['academic_total']) * 100 : 0 }}%"></div>
                    </div>
                    <small class="text-muted">
                        En attente: {{ number_format($stats['academic_pending'], 0, ',', ' ') }} FCFA
                    </small>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="resultat-card border-start border-warning border-3">
                    <div class="resultat-title">Services Optionnels</div>
                    <div class="resultat-montant color-warning">{{ number_format($stats['service_paid'], 0, ',', ' ') }} FCFA</div>
                    <div class="progress mb-2" style="height: 8px;">
                        <div class="progress-bar bg-warning" style="width: {{ $stats['service_total'] > 0 ? ($stats['service_paid'] / $stats['service_total']) * 100 : 0 }}%"></div>
                    </div>
                    <small class="text-muted">
                        En attente: {{ number_format($stats['service_pending'], 0, ',', ' ') }} FCFA
                    </small>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="resultat-card border-start border-info border-3">
                    <div class="resultat-title">Frais Administratifs</div>
                    <div class="resultat-montant color-info">{{ number_format($stats['administrative_paid'], 0, ',', ' ') }} FCFA</div>
                    <div class="progress mb-2" style="height: 8px;">
                        <div class="progress-bar bg-info" style="width: {{ $stats['administrative_total'] > 0 ? ($stats['administrative_paid'] / $stats['administrative_total']) * 100 : 0 }}%"></div>
                    </div>
                    <small class="text-muted">
                        En attente: {{ number_format($stats['administrative_pending'], 0, ',', ' ') }} FCFA
                    </small>
                </div>
            </div>
        </div>

        @if($stats['reliquats_total'] > 0)
        <div class="mt-3">
            <div class="alert alert-info alert-sm">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Note :</strong> Les montants « En attente » incluent les reliquats des années précédentes ({{ number_format($stats['reliquats_total'], 0, ',', ' ') }} FCFA).
            </div>
        </div>
        @endif
    </div>
</div>

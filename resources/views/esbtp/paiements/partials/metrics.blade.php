<div class="kpi-grid">
    {{-- KPI 1: Paiements VALIDÉS (statut validé) --}}
    <div class="card-moderne kpi-card">
        <div class="kpi-title">Paiements Validés</div>
        <div class="kpi-value color-success">{{ number_format($stats['montant_valide'], 0, ',', ' ') }} FCFA</div>
        <div class="kpi-trend positive">
            <i class="fas fa-check-circle"></i>
            {{ $stats['valides'] ?? 0 }} paiement(s)
        </div>
    </div>

    {{-- KPI 2: Paiements EN ATTENTE de validation (statut en_attente) --}}
    <div class="card-moderne kpi-card">
        <div class="kpi-title">En Attente de Validation</div>
        <div class="kpi-value color-warning">{{ number_format($stats['montant_en_attente'], 0, ',', ' ') }} FCFA</div>
        <div class="kpi-trend">
            <i class="fas fa-clock"></i>
            {{ $stats['en_attente'] ?? 0 }} paiement(s)
        </div>
    </div>

    {{-- KPI 3: Paiements REJETÉS (statut rejeté) --}}
    <div class="card-moderne kpi-card">
        <div class="kpi-title">Paiements Rejetés</div>
        <div class="kpi-value color-danger">{{ number_format($stats['montant_rejete'] ?? 0, 0, ',', ' ') }} FCFA</div>
        <div class="kpi-trend negative">
            <i class="fas fa-times-circle"></i>
            {{ $stats['rejetes'] ?? 0 }} paiement(s)
        </div>
    </div>

    {{-- KPI 4: TOTAL des paiements (tous statuts confondus) --}}
    <div class="card-moderne kpi-card">
        <div class="kpi-title">Total Paiements</div>
        <div class="kpi-value color-primary">{{ number_format($stats['montant_total'], 0, ',', ' ') }} FCFA</div>
        <div class="kpi-trend">
            <i class="fas fa-wallet"></i>
            {{ $stats['total'] ?? 0 }} paiement(s)
        </div>
    </div>
</div>

{{-- Section explicative --}}
<div class="alert alert-info alert-sm mb-lg">
    <i class="fas fa-info-circle me-2"></i>
    <strong>Note :</strong> Ces chiffres représentent les paiements <u>enregistrés dans le système</u> selon leur statut de validation.
    Pour voir les montants attendus vs payés par catégorie de frais, consultez le
    <a href="{{ route('esbtp.paiements.suivi-categories') }}" class="alert-link">Suivi par Catégorie</a>.
</div>

{{-- Répartition par catégorie (sur les paiements filtrés) --}}
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

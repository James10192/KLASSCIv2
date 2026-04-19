@php
    $currentStatus = request('status');
    $buildStatusUrl = function ($status) {
        $params = request()->query();
        if ($status === null) {
            unset($params['status']);
        } else {
            $params['status'] = $status;
        }
        return route('esbtp.paiements.index') . (!empty($params) ? '?' . http_build_query($params) : '');
    };
@endphp
<div class="kpi-grid">
    {{-- KPI 1: Paiements VALIDÉS (drill-down ?status=validé) --}}
    <a href="{{ $buildStatusUrl('validé') }}" class="pi-kpi-link {{ $currentStatus === 'validé' ? 'is-active' : '' }}" title="Filtrer sur les paiements validés" aria-label="Filtrer les paiements validés">
        <div class="card-moderne kpi-card">
            <div class="kpi-title">Paiements Validés</div>
            <div class="kpi-value color-success">{{ number_format($stats['montant_valide'], 0, ',', ' ') }} FCFA</div>
            <div class="kpi-trend positive">
                <i class="fas fa-check-circle"></i>
                {{ $stats['valides'] ?? 0 }} paiement(s)
            </div>
        </div>
    </a>

    {{-- KPI 2: Paiements EN ATTENTE (drill-down ?status=en_attente) --}}
    <a href="{{ $buildStatusUrl('en_attente') }}" class="pi-kpi-link {{ $currentStatus === 'en_attente' ? 'is-active' : '' }}" title="Filtrer sur les paiements en attente" aria-label="Filtrer les paiements en attente">
        <div class="card-moderne kpi-card">
            <div class="kpi-title">En Attente de Validation</div>
            <div class="kpi-value color-warning">{{ number_format($stats['montant_en_attente'], 0, ',', ' ') }} FCFA</div>
            <div class="kpi-trend">
                <i class="fas fa-clock"></i>
                {{ $stats['en_attente'] ?? 0 }} paiement(s)
            </div>
        </div>
    </a>

    {{-- KPI 3: Paiements REJETÉS (drill-down ?status=rejeté) --}}
    <a href="{{ $buildStatusUrl('rejeté') }}" class="pi-kpi-link {{ $currentStatus === 'rejeté' ? 'is-active' : '' }}" title="Filtrer sur les paiements rejetés" aria-label="Filtrer les paiements rejetés">
        <div class="card-moderne kpi-card">
            <div class="kpi-title">Paiements Rejetés</div>
            <div class="kpi-value color-danger">{{ number_format($stats['montant_rejete'] ?? 0, 0, ',', ' ') }} FCFA</div>
            <div class="kpi-trend negative">
                <i class="fas fa-times-circle"></i>
                {{ $stats['rejetes'] ?? 0 }} paiement(s)
            </div>
        </div>
    </a>

    {{-- KPI 4: TOTAL (retire le filtre status) --}}
    <a href="{{ $buildStatusUrl(null) }}" class="pi-kpi-link {{ empty($currentStatus) ? 'is-active' : '' }}" title="Afficher tous les statuts" aria-label="Afficher tous les paiements">
        <div class="card-moderne kpi-card">
            <div class="kpi-title">Total Paiements</div>
            <div class="kpi-value color-primary">{{ number_format($stats['montant_total'], 0, ',', ' ') }} FCFA</div>
            <div class="kpi-trend">
                <i class="fas fa-wallet"></i>
                {{ $stats['total'] ?? 0 }} paiement(s)
            </div>
        </div>
    </a>
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

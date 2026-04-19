{{-- 4 KPI glass cards rendus dans la row 2 du pi-hero (fond transparent) --}}
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
<div class="pi-hero-kpis">
    <a href="{{ $buildStatusUrl('validé') }}" class="pi-hero-kpi {{ $currentStatus === 'validé' ? 'is-active' : '' }}" title="Filtrer sur les paiements validés" aria-label="Filtrer les paiements validés">
        <div class="pi-hero-kpi-head">
            <i class="fas fa-check-circle"></i>
            <span class="pi-hero-kpi-label">Validés</span>
        </div>
        <div class="pi-hero-kpi-value">{{ number_format($stats['montant_valide'], 0, ',', ' ') }}<span class="pi-hero-kpi-unit">FCFA</span></div>
        <div class="pi-hero-kpi-meta">{{ $stats['valides'] ?? 0 }} paiement(s)</div>
    </a>
    <a href="{{ $buildStatusUrl('en_attente') }}" class="pi-hero-kpi {{ $currentStatus === 'en_attente' ? 'is-active' : '' }}" title="Filtrer sur les paiements en attente" aria-label="Filtrer les paiements en attente">
        <div class="pi-hero-kpi-head">
            <i class="fas fa-clock"></i>
            <span class="pi-hero-kpi-label">En attente</span>
        </div>
        <div class="pi-hero-kpi-value">{{ number_format($stats['montant_en_attente'], 0, ',', ' ') }}<span class="pi-hero-kpi-unit">FCFA</span></div>
        <div class="pi-hero-kpi-meta">{{ $stats['en_attente'] ?? 0 }} paiement(s)</div>
    </a>
    <a href="{{ $buildStatusUrl('rejeté') }}" class="pi-hero-kpi {{ $currentStatus === 'rejeté' ? 'is-active' : '' }}" title="Filtrer sur les paiements rejetés" aria-label="Filtrer les paiements rejetés">
        <div class="pi-hero-kpi-head">
            <i class="fas fa-times-circle"></i>
            <span class="pi-hero-kpi-label">Rejetés</span>
        </div>
        <div class="pi-hero-kpi-value">{{ number_format($stats['montant_rejete'] ?? 0, 0, ',', ' ') }}<span class="pi-hero-kpi-unit">FCFA</span></div>
        <div class="pi-hero-kpi-meta">{{ $stats['rejetes'] ?? 0 }} paiement(s)</div>
    </a>
    <a href="{{ $buildStatusUrl(null) }}" class="pi-hero-kpi {{ empty($currentStatus) ? 'is-active' : '' }}" title="Afficher tous les paiements" aria-label="Afficher tous les paiements">
        <div class="pi-hero-kpi-head">
            <i class="fas fa-wallet"></i>
            <span class="pi-hero-kpi-label">Total</span>
        </div>
        <div class="pi-hero-kpi-value">{{ number_format($stats['montant_total'], 0, ',', ' ') }}<span class="pi-hero-kpi-unit">FCFA</span></div>
        <div class="pi-hero-kpi-meta">{{ $stats['total'] ?? 0 }} paiement(s)</div>
    </a>
</div>

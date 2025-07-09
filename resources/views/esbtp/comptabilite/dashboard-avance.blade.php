@extends('layouts.app')

@section('title', 'Dashboard Financier Avancé')

@section('content')
<div class="container-fluid py-4">
    <!-- HEADER PREMIUM -->
    <div class="bg-gradient-primary rounded-4 p-5 mb-4 d-flex align-items-center justify-content-between animate-fade-in-up" style="background: linear-gradient(135deg, #0453cb 0%, #5e91de 100%); min-height: 120px;">
        <div class="d-flex align-items-center gap-4">
            <div class="bg-white bg-opacity-25 rounded-circle d-flex align-items-center justify-content-center" style="width:64px;height:64px;">
                <i class="fas fa-chart-line fa-2x text-white"></i>
            </div>
            <div>
                <h1 class="text-white fw-bold mb-1" style="font-size:1.8rem;">Dashboard Financier Avancé</h1>
                <p class="text-white-50 mb-0">Suivi en temps réel des performances financières</p>
            </div>
        </div>
        <div class="text-end">
            <div class="text-white-50 small">Dernière mise à jour</div>
            <div class="text-white fw-bold" id="lastUpdate">{{ now()->format('H:i:s') }}</div>
        </div>
    </div>

    <!-- ALERTES FINANCIÈRES -->
    @if(isset($alertes) && count($alertes) > 0)
    <div class="row mb-4 animate-fade-in-up">
        <div class="col-12">
            <div class="alert alert-warning border-0 rounded-4 shadow-lg p-4">
                <div class="d-flex align-items-center mb-3">
                    <i class="fas fa-exclamation-triangle fa-2x text-warning me-3"></i>
                    <h5 class="mb-0 fw-bold">Alertes Financières</h5>
                </div>
                <div class="row" id="alertesContainer">
                    @foreach($alertes as $alerte)
                        @include('esbtp.comptabilite.components.alerte-financiere', ['alerte' => $alerte])
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- KPIs PRINCIPAUX -->
    <div class="row g-4 mb-4 animate-fade-in-up" id="kpisContainer">
        @if(isset($kpis))
            <!-- Recettes -->
            @include('esbtp.comptabilite.components.kpi-card', [
                'title' => 'Recettes Totales',
                'value' => number_format($kpis['recettes']['total'] ?? 0, 0, ',', ' ') . ' FCFA',
                'icon' => 'fas fa-coins',
                'color' => 'success',
                'trend' => $kpis['recettes']['objectif_atteint'] ?? false ? 'up' : 'down',
                'subtitle' => 'Taux: ' . ($kpis['recettes']['taux_recouvrement'] ?? 0) . '%',
                'id' => 'kpi-recettes'
            ])

            <!-- Dépenses -->
            @include('esbtp.comptabilite.components.kpi-card', [
                'title' => 'Dépenses Totales',
                'value' => number_format($kpis['depenses']['total'] ?? 0, 0, ',', ' ') . ' FCFA',
                'icon' => 'fas fa-money-bill-wave',
                'color' => 'danger',
                'trend' => 'stable',
                'subtitle' => 'Budget restant: ' . number_format($kpis['depenses']['budget_restant'] ?? 0, 0, ',', ' ') . ' FCFA',
                'id' => 'kpi-depenses'
            ])

            <!-- Résultat Net -->
            @include('esbtp.comptabilite.components.kpi-card', [
                'title' => 'Résultat Net',
                'value' => number_format($kpis['performance']['resultat_net'] ?? 0, 0, ',', ' ') . ' FCFA',
                'icon' => 'fas fa-chart-pie',
                'color' => ($kpis['performance']['resultat_net'] ?? 0) > 0 ? 'success' : 'danger',
                'trend' => ($kpis['performance']['resultat_net'] ?? 0) > 0 ? 'up' : 'down',
                'subtitle' => 'Marge: ' . ($kpis['performance']['marge_nette'] ?? 0) . '%',
                'id' => 'kpi-resultat'
            ])

            <!-- Taux de Recouvrement -->
            @include('esbtp.comptabilite.components.kpi-card', [
                'title' => 'Taux de Recouvrement',
                'value' => ($kpis['paiements']['taux_recouvrement'] ?? 0) . '%',
                'icon' => 'fas fa-percentage',
                'color' => ($kpis['paiements']['taux_recouvrement'] ?? 0) >= 85 ? 'success' : 'warning',
                'trend' => ($kpis['paiements']['taux_recouvrement'] ?? 0) >= 85 ? 'up' : 'down',
                'subtitle' => ($kpis['paiements']['complets'] ?? 0) . ' étudiants à jour',
                'id' => 'kpi-recouvrement'
            ])
        @endif
    </div>

    <!-- GRAPHIQUES PRINCIPAUX -->
    <div class="row g-4 mb-4 animate-fade-in-up">
        <!-- Évolution Recettes/Dépenses -->
        <div class="col-lg-8">
            @include('esbtp.comptabilite.components.chart-container', [
                'title' => 'Évolution Financière (12 derniers mois)',
                'chartId' => 'evolutionChart',
                'height' => '400px'
            ])
        </div>

        <!-- Répartition par Filière -->
        <div class="col-lg-4">
            @include('esbtp.comptabilite.components.chart-container', [
                'title' => 'Répartition par Filière',
                'chartId' => 'repartitionChart',
                'height' => '400px'
            ])
        </div>
    </div>

    <!-- GRAPHIQUES SECONDAIRES -->
    <div class="row g-4 mb-4 animate-fade-in-up">
        <!-- Prévisions -->
        <div class="col-lg-6">
            @include('esbtp.comptabilite.components.chart-container', [
                'title' => 'Prévisions Financières (3 mois)',
                'chartId' => 'previsionsChart',
                'height' => '300px'
            ])
        </div>

        <!-- Performance Mensuelle -->
        <div class="col-lg-6">
            @include('esbtp.comptabilite.components.chart-container', [
                'title' => 'Performance Mensuelle',
                'chartId' => 'performanceChart',
                'height' => '300px'
            ])
        </div>
    </div>

    <!-- INDICATEURS DÉTAILLÉS -->
    <div class="row g-4 animate-fade-in-up">
        <div class="col-lg-8">
            <div class="card border-0 shadow-lg rounded-4 p-4 premium-glass">
                <h5 class="fw-bold mb-4"><i class="fas fa-list-alt text-primary me-2"></i>Indicateurs Détaillés</h5>
                <div class="row g-3" id="indicateursDetailles">
                    @if(isset($kpis))
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-center p-3 bg-light rounded-3">
                                <span class="fw-semibold">Étudiants payés complets</span>
                                <span class="badge bg-success">{{ $kpis['paiements']['complets'] ?? 0 }}</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-center p-3 bg-light rounded-3">
                                <span class="fw-semibold">Étudiants payés partiels</span>
                                <span class="badge bg-warning">{{ $kpis['paiements']['partiels'] ?? 0 }}</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-center p-3 bg-light rounded-3">
                                <span class="fw-semibold">Étudiants impayés</span>
                                <span class="badge bg-danger">{{ $kpis['paiements']['impayés'] ?? 0 }}</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-center p-3 bg-light rounded-3">
                                <span class="fw-semibold">Recettes mensuelles</span>
                                <span class="badge bg-info">{{ number_format($kpis['recettes']['mensuel'] ?? 0, 0, ',', ' ') }} FCFA</span>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-lg rounded-4 p-4 premium-glass">
                <h5 class="fw-bold mb-4"><i class="fas fa-bullseye text-primary me-2"></i>Objectifs</h5>
                <div class="space-y-3" id="objectifs">
                    @if(isset($kpis))
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="small fw-semibold">Taux de recouvrement</span>
                                <span class="small">{{ $kpis['paiements']['taux_recouvrement'] ?? 0 }}% / 85%</span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-success" style="width: {{ min(($kpis['paiements']['taux_recouvrement'] ?? 0), 100) }}%"></div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="small fw-semibold">Objectif recettes</span>
                                <span class="small">{{ round((($kpis['recettes']['total'] ?? 0) / max($kpis['recettes']['previsionnel'] ?? 1, 1)) * 100, 1) }}%</span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-primary" style="width: {{ min(round((($kpis['recettes']['total'] ?? 0) / max($kpis['recettes']['previsionnel'] ?? 1, 1)) * 100, 1), 100) }}%"></div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Loader pour les mises à jour -->
<div id="loadingOverlay" class="position-fixed top-0 start-0 w-100 h-100 d-none" style="background: rgba(0,0,0,0.1); z-index: 9999;">
    <div class="d-flex align-items-center justify-content-center h-100">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Mise à jour...</span>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.premium-glass {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.animate-fade-in-up {
    animation: fadeInUp 0.6s ease-out;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.hover-lift {
    transition: transform 0.3s ease;
}

.hover-lift:hover {
    transform: translateY(-5px);
}

.kpi-updating {
    animation: pulse 1s ease-in-out;
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.7; }
    100% { opacity: 1; }
}
</style>
@endpush

@push('scripts')
<script src="{{ asset('js/comptabilite-dashboard.js') }}"></script>
<script>
// Initialisation du dashboard
document.addEventListener('DOMContentLoaded', function() {
    // Données initiales pour les graphiques
    const evolutionData = @json($evolutionRecettes ?? []);
    const evolutionDepensesData = @json($evolutionDepenses ?? []);
    const kpisData = @json($kpis ?? []);

    // Initialiser le gestionnaire de dashboard
    if (typeof ComptabiliteManager !== 'undefined') {
        const dashboard = new ComptabiliteManager();
        dashboard.init({
            evolutionData: evolutionData,
            evolutionDepensesData: evolutionDepensesData,
            kpisData: kpisData,
            autoRefresh: true,
            refreshInterval: 30000 // 30 secondes
        });
    }
});
</script>
@endpush

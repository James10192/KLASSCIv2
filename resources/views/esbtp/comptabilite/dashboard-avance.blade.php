@extends('layouts.app')

@section('title', 'Dashboard Financier ESBTP KLASSCI')

@section('content')
<div class="dashboard-acasi">
    {{-- Sidebar Gauche --}}
    <aside class="sidebar-left">
        <div class="logo">ESBTP</div>

        <nav class="navigation">
            <ul class="navigation-menu">
                <li class="navigation-item">
                    <a href="{{ route('esbtp.comptabilite.dashboard-avance') }}" class="navigation-link active">
                        <i class="fas fa-home"></i>
                        Accueil
                    </a>
                </li>
                <li class="navigation-item">
                    <a href="{{ route('esbtp.comptabilite.paiements.index') }}" class="navigation-link">
                        <i class="fas fa-credit-card"></i>
                        Paiements
                    </a>
                </li>
                <li class="navigation-item">
                    <a href="{{ route('esbtp.comptabilite.depenses.index') }}" class="navigation-link">
                        <i class="fas fa-shopping-cart"></i>
                        Dépenses
                    </a>
                </li>
                <li class="navigation-item">
                    <a href="{{ route('esbtp.comptabilite.bons-sortie.index') }}" class="navigation-link">
                        <i class="fas fa-file-export"></i>
                        Bons de Sortie
                    </a>
                </li>
                <li class="navigation-item">
                    <a href="{{ route('esbtp.comptabilite.factures.index') }}" class="navigation-link">
                        <i class="fas fa-file-invoice"></i>
                        Factures
                    </a>
                </li>
                <li class="navigation-item">
                    <a href="{{ route('esbtp.comptabilite.rapports') }}" class="navigation-link">
                        <i class="fas fa-chart-bar"></i>
                        Rapports
                    </a>
                </li>
            </ul>
        </nav>
    </aside>

    {{-- Contenu Principal --}}
    <main class="main-content">
        {{-- Header --}}
        <header class="dashboard-header">
            <div class="header-left">
                <h1><i class="fas fa-chart-line color-primary"></i> Dashboard Financier</h1>
                <p class="header-subtitle">
                    Analytics comptables en temps réel •
                    <span class="color-success">{{ now()->format('d/m/Y H:i') }}</span>
                </p>
            </div>
            <div class="header-actions">
                <input type="text" class="search-bar" placeholder="Rechercher...">
                <select class="year-selector" id="yearSelector">
                    <option value="{{ now()->year }}" selected>{{ now()->year }}</option>
                    <option value="{{ now()->year - 1 }}">{{ now()->year - 1 }}</option>
                    <option value="{{ now()->year - 2 }}">{{ now()->year - 2 }}</option>
                </select>
                <button class="btn-acasi primary" onclick="refreshDashboard()">
                    <i class="fas fa-sync-alt"></i>
                    Actualiser
                </button>
            </div>
        </header>

        {{-- Section Soldes Principaux --}}
        <section class="soldes-section">
            <h2 class="section-title">Soldes Principaux</h2>
            <div class="soldes-grid">
                <div class="card-moderne solde-card animate-slide-up">
                    <h3 class="solde-title">Trésorerie Totale</h3>
                    <div class="solde-montant color-primary">
                        {{ number_format(($kpis['total_recettes'] ?? 0) - ($kpis['total_depenses'] ?? 0), 0, ',', ' ') }} FCFA
                    </div>
                    <div class="solde-chart">
                        <canvas id="tresorerieChart" width="100" height="40"></canvas>
                    </div>
                </div>

                <div class="card-moderne solde-card animate-slide-up" style="animation-delay: 0.1s">
                    <h3 class="solde-title">Recettes du Mois</h3>
                    <div class="solde-montant color-accent">
                        {{ number_format($kpis['recettes_mois'] ?? 0, 0, ',', ' ') }} FCFA
                    </div>
                    <div class="solde-chart">
                        <canvas id="recettesChart" width="100" height="40"></canvas>
                    </div>
                </div>

                <div class="card-moderne solde-card animate-slide-up" style="animation-delay: 0.2s">
                    <h3 class="solde-title">Dépenses du Mois</h3>
                    <div class="solde-montant color-warning">
                        {{ number_format($kpis['depenses_mois'] ?? 0, 0, ',', ' ') }} FCFA
                    </div>
                    <div class="solde-chart">
                        <canvas id="depensesChart" width="100" height="40"></canvas>
                    </div>
                </div>
            </div>
        </section>

        {{-- Section KPIs --}}
        <section class="kpis-section">
            <h2 class="section-title">Indicateurs de Performance</h2>
            <div class="kpi-grid">
                <div class="card-moderne kpi-card animate-slide-up">
                    <h3 class="kpi-title">Taux de Recouvrement</h3>
                    <div class="kpi-value color-success">{{ number_format($kpis['taux_recouvrement'] ?? 0, 1) }}%</div>
                    @if(isset($kpis['evolution_recouvrement']))
                        <div class="kpi-trend {{ $kpis['evolution_recouvrement'] >= 0 ? 'positive' : 'negative' }}">
                            <i class="fas fa-arrow-{{ $kpis['evolution_recouvrement'] >= 0 ? 'up' : 'down' }}"></i>
                            {{ abs($kpis['evolution_recouvrement']) }}%
                        </div>
                    @endif
                </div>

                <div class="card-moderne kpi-card animate-slide-up" style="animation-delay: 0.1s">
                    <h3 class="kpi-title">Marge Nette</h3>
                    <div class="kpi-value color-primary">{{ number_format($kpis['marge_nette'] ?? 0, 1) }}%</div>
                    @if(isset($kpis['evolution_marge']))
                        <div class="kpi-trend {{ $kpis['evolution_marge'] >= 0 ? 'positive' : 'negative' }}">
                            <i class="fas fa-arrow-{{ $kpis['evolution_marge'] >= 0 ? 'up' : 'down' }}"></i>
                            {{ abs($kpis['evolution_marge']) }}%
                        </div>
                    @endif
                </div>

                <div class="card-moderne kpi-card animate-slide-up" style="animation-delay: 0.2s">
                    <h3 class="kpi-title">Étudiants Solvents</h3>
                    <div class="kpi-value color-accent">{{ $kpis['etudiants_solvents'] ?? 0 }}</div>
                    <div class="kpi-trend positive">
                        <i class="fas fa-users"></i>
                        sur {{ $kpis['total_etudiants'] ?? 0 }}
                    </div>
                </div>

                <div class="card-moderne kpi-card animate-slide-up" style="animation-delay: 0.3s">
                    <h3 class="kpi-title">Objectif Atteint</h3>
                    <div class="kpi-value color-success">{{ number_format($kpis['objectif_atteint'] ?? 0, 1) }}%</div>
                    <div class="kpi-trend positive">
                        <i class="fas fa-target"></i>
                        En cours
                    </div>
                </div>
            </div>
        </section>

        {{-- Section Résultats --}}
        <section class="resultats-section">
            <h2 class="section-title">Résultats {{ now()->year }}</h2>
            <div class="resultats-grid">
                <div class="card-moderne resultat-card animate-slide-up">
                    <h3 class="resultat-title">Chiffre d'Affaires HT {{ now()->year }}</h3>
                    <div class="resultat-montant color-accent">
                        {{ number_format($kpis['total_recettes'] ?? 0, 0, ',', ' ') }} FCFA
                    </div>
                    <ul class="resultat-details">
                        @if(isset($donneesFinancieres['top_filieres']))
                            @foreach(array_slice($donneesFinancieres['top_filieres'], 0, 3) as $filiere)
                            <li class="resultat-detail">
                                <span>{{ $filiere['nom'] ?? 'Filière' }}</span>
                                <span class="font-semibold">{{ number_format($filiere['recettes'] ?? 0, 0, ',', ' ') }} FCFA</span>
                            </li>
                            @endforeach
                        @else
                            <li class="resultat-detail">
                                <span>Informatique</span>
                                <span class="font-semibold">{{ number_format(($kpis['total_recettes'] ?? 0) * 0.4, 0, ',', ' ') }} FCFA</span>
                            </li>
                            <li class="resultat-detail">
                                <span>BTP</span>
                                <span class="font-semibold">{{ number_format(($kpis['total_recettes'] ?? 0) * 0.35, 0, ',', ' ') }} FCFA</span>
                            </li>
                            <li class="resultat-detail">
                                <span>Commerce</span>
                                <span class="font-semibold">{{ number_format(($kpis['total_recettes'] ?? 0) * 0.25, 0, ',', ' ') }} FCFA</span>
                            </li>
                        @endif
                    </ul>
                </div>

                <div class="card-moderne resultat-card animate-slide-up" style="animation-delay: 0.1s">
                    <h3 class="resultat-title">Résultat Net {{ now()->year }}</h3>
                    <div class="resultat-montant {{ ($kpis['resultat_net'] ?? 0) >= 0 ? 'color-success' : 'color-danger' }}">
                        {{ number_format($kpis['resultat_net'] ?? 0, 0, ',', ' ') }} FCFA
                    </div>
                    <ul class="resultat-details">
                        <li class="resultat-detail">
                            <span>Chiffre d'affaires</span>
                            <span class="font-semibold color-success">{{ number_format($kpis['total_recettes'] ?? 0, 0, ',', ' ') }} FCFA</span>
                        </li>
                        <li class="resultat-detail">
                            <span>Dépenses totales</span>
                            <span class="font-semibold color-danger">{{ number_format($kpis['total_depenses'] ?? 0, 0, ',', ' ') }} FCFA</span>
                        </li>
                        <li class="resultat-detail">
                            <span>Marge nette</span>
                            <span class="font-semibold">{{ number_format($kpis['marge_nette'] ?? 0, 1) }}%</span>
                        </li>
                    </ul>
                </div>

                <div class="card-moderne resultat-card animate-slide-up" style="animation-delay: 0.2s">
                    <h3 class="resultat-title">Charges {{ now()->year }}</h3>
                    <div class="resultat-montant color-warning">
                        {{ number_format($kpis['total_depenses'] ?? 0, 0, ',', ' ') }} FCFA
                    </div>
                    <ul class="resultat-details">
                        @if(isset($donneesFinancieres['categories_depenses']))
                            @foreach(array_slice($donneesFinancieres['categories_depenses'], 0, 4) as $categorie)
                            <li class="resultat-detail">
                                <span>{{ $categorie['nom'] ?? 'Catégorie' }}</span>
                                <span class="font-semibold">{{ number_format($categorie['total'] ?? 0, 0, ',', ' ') }} FCFA</span>
                            </li>
                            @endforeach
                        @else
                            <li class="resultat-detail">
                                <span><i class="fas fa-users"></i> Personnel</span>
                                <span class="font-semibold">{{ number_format(($kpis['total_depenses'] ?? 0) * 0.6, 0, ',', ' ') }} FCFA</span>
                            </li>
                            <li class="resultat-detail">
                                <span><i class="fas fa-building"></i> Infrastructure</span>
                                <span class="font-semibold">{{ number_format(($kpis['total_depenses'] ?? 0) * 0.25, 0, ',', ' ') }} FCFA</span>
                            </li>
                            <li class="resultat-detail">
                                <span><i class="fas fa-laptop"></i> Équipements</span>
                                <span class="font-semibold">{{ number_format(($kpis['total_depenses'] ?? 0) * 0.1, 0, ',', ' ') }} FCFA</span>
                            </li>
                            <li class="resultat-detail">
                                <span><i class="fas fa-cog"></i> Autres</span>
                                <span class="font-semibold">{{ number_format(($kpis['total_depenses'] ?? 0) * 0.05, 0, ',', ' ') }} FCFA</span>
                            </li>
                        @endif
                    </ul>
                </div>
            </div>
        </section>

        {{-- Section Graphiques --}}
        <section class="charts-section">
            <div class="chart-container animate-slide-up">
                <h3 class="chart-title">Évolution Financière Mensuelle</h3>
                <canvas id="evolutionChart" class="chart-canvas"></canvas>
            </div>
        </section>
    </main>

    {{-- Sidebar Droite --}}
    <aside class="sidebar-right">
        <h2 class="sidebar-title">Paiements Étudiants</h2>
        <p class="sidebar-subtitle">
            {{ number_format(($kpis['montant_en_attente'] ?? 0), 0, ',', ' ') }} FCFA en attente
        </p>

        <ul class="client-list">
            @if(isset($donneesFinancieres['etudiants_en_attente']) && !empty($donneesFinancieres['etudiants_en_attente']))
                @foreach(array_slice($donneesFinancieres['etudiants_en_attente'], 0, 8) as $etudiant)
                <li class="client-item {{ ($etudiant['montant_du'] ?? 0) > 500000 ? 'danger' : 'success' }}">
                    <div class="client-nom">{{ $etudiant['nom'] ?? 'Étudiant' }}</div>
                    <div class="client-montant {{ ($etudiant['montant_du'] ?? 0) > 500000 ? 'color-danger' : 'color-success' }}">
                        {{ number_format($etudiant['montant_du'] ?? 0, 0, ',', ' ') }} FCFA
                    </div>
                    <div class="client-statut">
                        {{ ($etudiant['montant_du'] ?? 0) > 500000 ? 'Retard de paiement' : 'En attente' }}
                    </div>
                </li>
                @endforeach
            @else
                <li class="client-item success">
                    <div class="client-nom">ADJOUMANI Kouadio</div>
                    <div class="client-montant color-danger">850 000 FCFA</div>
                    <div class="client-statut">Retard de paiement depuis 30 jours</div>
                </li>
                <li class="client-item success">
                    <div class="client-nom">TRAORE Fatou</div>
                    <div class="client-montant color-warning">450 000 FCFA</div>
                    <div class="client-statut">2ème échéance en attente</div>
                </li>
                <li class="client-item success">
                    <div class="client-nom">KONE Ibrahim</div>
                    <div class="client-montant color-success">200 000 FCFA</div>
                    <div class="client-statut">1ère échéance en attente</div>
                </li>
                <li class="client-item success">
                    <div class="client-nom">YAO Marie</div>
                    <div class="client-montant color-success">300 000 FCFA</div>
                    <div class="client-statut">Solde restant</div>
                </li>
            @endif
        </ul>

        {{-- Actions Rapides --}}
        <div class="mt-lg">
            <h3 class="sidebar-title">Actions Rapides</h3>
            <div style="display: flex; flex-direction: column; gap: var(--space-sm);">
                <a href="{{ route('esbtp.comptabilite.relances.index') }}" class="btn-acasi secondary">
                    <i class="fas fa-bell"></i>
                    Envoyer Relances
                </a>
                <a href="{{ route('esbtp.comptabilite.rapports') }}" class="btn-acasi secondary">
                    <i class="fas fa-file-export"></i>
                    Export Rapport
                </a>
                <button class="btn-acasi primary" onclick="genererPrevisions()">
                    <i class="fas fa-crystal-ball"></i>
                    Prévisions IA
                </button>
            </div>
        </div>
    </aside>
</div>
@endsection

@push('styles')
<link href="{{ asset('css/dashboard-moderne.css') }}" rel="stylesheet">
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // === GRAPHIQUES ACASI STYLE ===

    // Configuration des couleurs ACASI
    const colors = {
        primary: '#1e3a8a',
        secondary: '#1e40af',
        accent: '#06b6d4',
        success: '#10b981',
        warning: '#f59e0b',
        danger: '#ef4444'
    };

    // Graphique d'évolution principale
    const evolutionCtx = document.getElementById('evolutionChart');
    if (evolutionCtx) {
        new Chart(evolutionCtx, {
            type: 'line',
            data: {
                labels: @json(isset($donneesFinancieres['recettes_mensuelles']) ?
                    collect($donneesFinancieres['recettes_mensuelles'])->map(function($item) {
                        return \Carbon\Carbon::createFromDate($item['annee'], $item['mois'], 1)->format('M Y');
                    })->toArray() :
                    ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun', 'Jul', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc']),
                datasets: [{
                    label: 'Recettes',
                    data: @json(isset($donneesFinancieres['recettes_mensuelles']) ?
                        collect($donneesFinancieres['recettes_mensuelles'])->pluck('total')->toArray() :
                        [2800000, 3200000, 2900000, 3500000, 3100000, 3400000, 3300000, 3600000, 3200000, 3800000, 3500000, 4000000]),
                    borderColor: colors.accent,
                    backgroundColor: colors.accent + '20',
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: colors.accent,
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 4
                }, {
                    label: 'Dépenses',
                    data: @json(isset($donneesFinancieres['depenses_mensuelles']) ?
                        collect($donneesFinancieres['depenses_mensuelles'])->pluck('total')->toArray() :
                        [2200000, 2400000, 2300000, 2600000, 2500000, 2700000, 2600000, 2800000, 2500000, 2900000, 2700000, 3000000]),
                    borderColor: colors.warning,
                    backgroundColor: colors.warning + '20',
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: colors.warning,
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            padding: 20,
                            font: {
                                family: 'system-ui',
                                size: 12,
                                weight: '500'
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(255, 255, 255, 0.95)',
                        titleColor: '#111827',
                        bodyColor: '#6b7280',
                        borderColor: '#e5e7eb',
                        borderWidth: 1,
                        cornerRadius: 8,
                        padding: 12,
                        displayColors: true,
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' +
                                    new Intl.NumberFormat('fr-FR').format(context.parsed.y) + ' FCFA';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            color: '#f3f4f6',
                            drawBorder: false
                        },
                        ticks: {
                            color: '#6b7280',
                            font: {
                                family: 'system-ui',
                                size: 11
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#f3f4f6',
                            drawBorder: false
                        },
                        ticks: {
                            color: '#6b7280',
                            font: {
                                family: 'system-ui',
                                size: 11
                            },
                            callback: function(value) {
                                return new Intl.NumberFormat('fr-FR', {
                                    notation: 'compact',
                                    compactDisplay: 'short'
                                }).format(value) + ' FCFA';
                            }
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                }
            }
        });
    }

    // Mini graphiques de trésorerie
    const miniChartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: { enabled: false }
        },
        scales: {
            x: { display: false },
            y: { display: false }
        },
        elements: {
            point: { radius: 0 }
        }
    };

    // Graphique trésorerie
    const tresorerieCtx = document.getElementById('tresorerieChart');
    if (tresorerieCtx) {
        new Chart(tresorerieCtx, {
            type: 'line',
            data: {
                labels: ['J-6', 'J-5', 'J-4', 'J-3', 'J-2', 'J-1', 'Aujourd\'hui'],
                datasets: [{
                    data: [580000, 620000, 590000, 640000, 680000, 700000, 750000],
                    borderColor: colors.primary,
                    backgroundColor: colors.primary + '30',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: miniChartOptions
        });
    }

    // Graphique recettes
    const recettesCtx = document.getElementById('recettesChart');
    if (recettesCtx) {
        new Chart(recettesCtx, {
            type: 'line',
            data: {
                labels: ['J-6', 'J-5', 'J-4', 'J-3', 'J-2', 'J-1', 'Aujourd\'hui'],
                datasets: [{
                    data: [45000, 52000, 48000, 65000, 58000, 70000, 85000],
                    borderColor: colors.accent,
                    backgroundColor: colors.accent + '30',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: miniChartOptions
        });
    }

    // Graphique dépenses
    const depensesCtx = document.getElementById('depensesChart');
    if (depensesCtx) {
        new Chart(depensesCtx, {
            type: 'line',
            data: {
                labels: ['J-6', 'J-5', 'J-4', 'J-3', 'J-2', 'J-1', 'Aujourd\'hui'],
                datasets: [{
                    data: [35000, 42000, 38000, 45000, 48000, 50000, 55000],
                    borderColor: colors.warning,
                    backgroundColor: colors.warning + '30',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: miniChartOptions
        });
    }

    // === FONCTIONS INTERACTIVES ===

    window.refreshDashboard = function() {
        window.location.reload();
    };

    window.genererPrevisions = function() {
        alert('Fonctionnalité IA en développement');
    };

    // Sélecteur d'année
    document.getElementById('yearSelector').addEventListener('change', function() {
        const selectedYear = this.value;
        window.location.href = window.location.pathname + '?year=' + selectedYear;
    });

    // Auto-refresh toutes les 5 minutes
    setInterval(function() {
        fetch('{{ route("esbtp.comptabilite.kpis-temps-reel") }}')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateKPIs(data.kpis);
                }
            })
            .catch(console.error);
    }, 300000);

    function updateKPIs(kpis) {
        // Mettre à jour les valeurs des KPIs sans rechargement
        const kpiValues = document.querySelectorAll('.kpi-value');
        const soldeValues = document.querySelectorAll('.solde-montant');

        if (kpiValues.length >= 4) {
            kpiValues[0].textContent = parseFloat(kpis.taux_recouvrement || 0).toFixed(1) + '%';
            kpiValues[1].textContent = parseFloat(kpis.marge_nette || 0).toFixed(1) + '%';
            kpiValues[2].textContent = kpis.etudiants_solvents || 0;
            kpiValues[3].textContent = parseFloat(kpis.objectif_atteint || 0).toFixed(1) + '%';
        }
    }

    // Animation au scroll
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-slide-up');
            }
        });
    }, observerOptions);

    document.querySelectorAll('.card-moderne, .chart-container').forEach(element => {
        observer.observe(element);
    });
});
</script>
@endpush

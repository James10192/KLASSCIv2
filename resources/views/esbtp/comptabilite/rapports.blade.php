@extends('esbtp.comptabilite.components.dashboard-layout')

@section('title', 'Rapports financiers')

@section('sidebar')
    <li class="navigation-item">
        <a href="{{ route('esbtp.comptabilite.dashboard') }}" class="navigation-link">
            <i class="fas fa-home"></i> Accueil
        </a>
    </li>
    <li class="navigation-item">
        <a href="{{ route('esbtp.comptabilite.paiements') }}" class="navigation-link">
            <i class="fas fa-credit-card"></i> Paiements
        </a>
    </li>
    @if(Route::has('esbtp.comptabilite.depenses'))
<li class="navigation-item">
        <a href="{{ route('esbtp.comptabilite.depenses') }}" class="navigation-link">
            <i class="fas fa-shopping-cart"></i> Dépenses
        </a>
    </li>
@endif
@if(Route::has('esbtp.comptabilite.bons-sortie.index'))
<li class="navigation-item">
        <a href="{{ route('esbtp.comptabilite.bons-sortie.index') }}" class="navigation-link">
            <i class="fas fa-file-export"></i> Bons de Sortie
        </a>
    </li>
@endif
@if(Route::has('esbtp.comptabilite.factures'))
<li class="navigation-item">
        <a href="{{ route('esbtp.comptabilite.factures') }}" class="navigation-link">
            <i class="fas fa-file-invoice"></i> Factures
        </a>
    </li>
@endif
<li class="navigation-item">
        <a href="{{ route('esbtp.comptabilite.rapports') }}" class="navigation-link active">
            <i class="fas fa-chart-bar"></i> Rapports
        </a>
    </li>
@endsection



@section('sidebarRight')
    <div class="d-flex flex-column gap-2">
        <a href="{{ route('esbtp.comptabilite.dashboard') }}" class="btn btn-outline-primary d-flex align-items-center gap-2">
            <i class="fas fa-tachometer-alt"></i> Dashboard avancé
        </a>
        <a href="#" class="btn btn-outline-secondary d-flex align-items-center gap-2">
            <i class="fas fa-file-export"></i> Exporter rapport
        </a>
        <a href="#" class="btn btn-outline-info d-flex align-items-center gap-2">
            <i class="fas fa-layer-group"></i> Modèles de rapport
        </a>
    </div>
@endsection

@section('content-block')
    @push('styles')
<style>
    .financial-card {
        border: none;
        border-radius: 10px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
        overflow: hidden;
        height: 100%;
        position: relative;
    }
    .financial-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
    }
        .recettes-card { background: linear-gradient(135deg, #4ade80 0%, #22c55e 100%); }
        .depenses-card { background: linear-gradient(135deg, #f87171 0%, #ef4444 100%); }
        .balance-card { background: linear-gradient(135deg, #60a5fa 0%, #3b82f6 100%); }
        .balance-negative { background: linear-gradient(135deg, #facc15 0%, #eab308 100%); }
        .card-amount { font-size: 1.8rem; font-weight: 700; margin-bottom: 0.5rem; color: white; }
        .card-label { font-size: 1rem; font-weight: 500; color: rgba(255, 255, 255, 0.9); margin-bottom: 0.75rem; }
        .card-desc { font-size: 0.85rem; color: rgba(255, 255, 255, 0.7); }
        .financial-card .float-icon { position: absolute; right: 15px; font-size: 4rem; bottom: 0; opacity: 0.2; transform: translateY(10px); transition: all 0.3s ease; }
        .financial-card:hover .float-icon { opacity: 0.4; transform: translateY(0); }
</style>
    @endpush

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0 fw-bold">
                        <i class="fas fa-chart-pie text-primary me-2"></i>Rapports financiers
                    </h5>
                    <div>
                        <button class="btn btn-sm btn-outline-secondary me-2" onclick="window.print()">
                            <i class="fas fa-print me-1"></i>Imprimer
                        </button>
                        <button class="btn btn-sm btn-primary">
                            <i class="fas fa-file-pdf me-1"></i>Exporter PDF
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Sélection de période -->
                    <form method="GET" action="{{ route('esbtp.comptabilite.rapports') }}" class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label for="annee" class="form-label">Année</label>
                            <select class="form-select" id="annee" name="annee" onchange="this.form.submit()">
                                @for($i = date('Y'); $i >= date('Y') - 5; $i--)
                                    <option value="{{ $i }}" {{ ($annee ?? date('Y')) == $i ? 'selected' : '' }}>{{ $i }}</option>
                                @endfor
                            </select>
                        </div>
                    </form>
                    <!-- Résumé annuel -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Résumé financier pour l'année {{ $annee ?? date('Y') }}</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <div class="financial-card recettes-card">
                                                <div class="card-body p-4">
                                                    <div class="card-label">
                                                        <i class="fas fa-money-bill-wave me-2"></i>TOTAL DES RECETTES
                                                    </div>
                                                    <div class="card-amount">
                                                        {{ number_format(array_sum($recettes ?? [0]), 0, ',', ' ') }} FCFA
                                                    </div>
                                                    <div class="card-desc">Tous les paiements reçus</div>
                                                    <div class="float-icon">
                                                        <i class="fas fa-coins"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <div class="financial-card depenses-card">
                                                <div class="card-body p-4">
                                                    <div class="card-label">
                                                        <i class="fas fa-file-invoice-dollar me-2"></i>TOTAL DES DÉPENSES
                                                    </div>
                                                    <div class="card-amount">
                                                        {{ number_format(array_sum($depenses ?? [0]), 0, ',', ' ') }} FCFA
                                                    </div>
                                                    <div class="card-desc">Toutes les dépenses effectuées</div>
                                                    <div class="float-icon">
                                                        <i class="fas fa-shopping-cart"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            @php
                                                $balance = array_sum($recettes ?? [0]) - array_sum($depenses ?? [0]);
                                                $isPositive = $balance >= 0;
                                            @endphp
                                            <div class="financial-card {{ $isPositive ? 'balance-card' : 'balance-negative' }}">
                                                <div class="card-body p-4">
                                                    <div class="card-label">
                                                        <i class="fas fa-balance-scale me-2"></i>BALANCE
                                                    </div>
                                                    <div class="card-amount">
                                                        {{ number_format($balance, 0, ',', ' ') }} FCFA
                                                    </div>
                                                    <div class="card-desc">{{ $isPositive ? 'Excédent budgétaire' : 'Déficit budgétaire' }}</div>
                                                    <div class="float-icon">
                                                        <i class="fas fa-{{ $isPositive ? 'chart-line' : 'chart-area' }}"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Graphique recettes/dépenses par mois -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Évolution mensuelle des recettes et dépenses</h5>
                                </div>
                                <div class="card-body">
                                    <div style="height: 400px; background-color: #f8f9fa; border-radius: 8px; display: flex; justify-content: center; align-items: center;" id="monthly-chart">
                                        <div class="text-center">
                                            <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
                                            <p>Le graphique d'évolution mensuelle sera affiché ici</p>
                                        </div>
                                    </div>
                                    <!-- Tableau des données -->
                                    <div class="table-responsive mt-4">
                                        <table class="table table-sm table-bordered">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Mois</th>
                                                    @foreach($mois ?? [] as $m)
                                                        <th>{{ $m }}</th>
                                                    @endforeach
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <th>Recettes</th>
                                                    @foreach($recettes ?? [] as $recette)
                                                        <td>{{ number_format($recette, 0, ',', ' ') }} FCFA</td>
                                                    @endforeach
                                                </tr>
                                                <tr>
                                                    <th>Dépenses</th>
                                                    @foreach($depenses ?? [] as $depense)
                                                        <td>{{ number_format($depense, 0, ',', ' ') }} FCFA</td>
                                                    @endforeach
                                                </tr>
                                                <tr>
                                                    <th>Balance</th>
                                                    @if(isset($recettes) && isset($depenses))
                                                        @foreach(array_map(function($a, $b) { return $a - $b; }, $recettes, $depenses) as $balance)
                                                            <td class="{{ $balance >= 0 ? 'text-success' : 'text-danger' }}">
                                                                {{ number_format($balance, 0, ',', ' ') }} FCFA
                                                            </td>
                                                        @endforeach
                                                    @endif
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Répartition des paiements et dépenses -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Répartition des paiements par type</h5>
                                </div>
                                <div class="card-body">
                                    <div style="height: 300px; background-color: #f8f9fa; border-radius: 8px; display: flex; justify-content: center; align-items: center;" id="payments-chart">
                                        <div class="text-center">
                                            <i class="fas fa-chart-pie fa-3x text-muted mb-3"></i>
                                            <p>Le graphique de répartition sera affiché ici</p>
                                        </div>
                                    </div>
                                    <!-- Tableau des données -->
                                    <div class="table-responsive mt-3">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Type</th>
                                                    <th>Montant</th>
                                                    <th>Pourcentage</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @if(isset($paiementsParType))
                                                        @php $totalPaiements = $paiementsParType->sum('total'); @endphp
                                                    @foreach($paiementsParType as $paiement)
                                                        <tr>
                                                            <td>{{ $paiement->type_paiement }}</td>
                                                            <td>{{ number_format($paiement->total, 0, ',', ' ') }} FCFA</td>
                                                                <td>{{ number_format($totalPaiements > 0 ? ($paiement->total / $totalPaiements * 100) : 0, 1) }}%</td>
                                                        </tr>
                                                    @endforeach
                                                @else
                                                    <tr>
                                                        <td colspan="3" class="text-center">Aucune donnée disponible</td>
                                                    </tr>
                                                @endif
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Répartition des dépenses par catégorie</h5>
                                </div>
                                <div class="card-body">
                                    <div style="height: 300px; background-color: #f8f9fa; border-radius: 8px; display: flex; justify-content: center; align-items: center;" id="expenses-chart">
                                        <div class="text-center">
                                            <i class="fas fa-chart-pie fa-3x text-muted mb-3"></i>
                                            <p>Le graphique de répartition sera affiché ici</p>
                                        </div>
                                    </div>
                                    <!-- Tableau des données -->
                                    <div class="table-responsive mt-3">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Catégorie</th>
                                                    <th>Montant</th>
                                                    <th>Pourcentage</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @if(isset($depensesParCategorie))
                                                        @php $totalDepenses = $depensesParCategorie->sum('total'); @endphp
                                                    @foreach($depensesParCategorie as $depense)
                                                        <tr>
                                                            <td>{{ $depense->categorie }}</td>
                                                            <td>{{ number_format($depense->total, 0, ',', ' ') }} FCFA</td>
                                                                <td>{{ number_format($totalDepenses > 0 ? ($depense->total / $totalDepenses * 100) : 0, 1) }}%</td>
                                                        </tr>
                                                    @endforeach
                                                @else
                                                    <tr>
                                                        <td colspan="3" class="text-center">Aucune donnée disponible</td>
                                                    </tr>
                                                @endif
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Paiements par classe -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Paiements par classe</h5>
                                </div>
                                <div class="card-body">
                                    @if(isset($paiementsParClasse) && $paiementsParClasse->count() > 0)
                                        <div class="table-responsive">
                                            <table class="table table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Classe</th>
                                                        <th>Montant total payé</th>
                                                        <th>Pourcentage du total</th>
                                                        <th>Représentation</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                        @php $totalClasses = $paiementsParClasse->sum(); @endphp
                                                    @foreach($paiementsParClasse as $classe => $montant)
                                                        <tr>
                                                            <td>{{ $classe }}</td>
                                                            <td>{{ number_format($montant, 0, ',', ' ') }} FCFA</td>
                                                            <td>{{ number_format($totalClasses > 0 ? ($montant / $totalClasses * 100) : 0, 1) }}%</td>
                                                            <td>
                                                                <div class="progress">
                                                                    <div class="progress-bar bg-success" role="progressbar" 
                                                                         style="width: {{ $totalClasses > 0 ? ($montant / $totalClasses * 100) : 0 }}%" 
                                                                         aria-valuenow="{{ $totalClasses > 0 ? ($montant / $totalClasses * 100) : 0 }}" 
                                                                         aria-valuemin="0" 
                                                                         aria-valuemax="100"></div>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                                <tfoot>
                                                    <tr class="table-light">
                                                        <th>Total</th>
                                                        <th>{{ number_format($totalClasses, 0, ',', ' ') }} FCFA</th>
                                                        <th>100%</th>
                                                        <th></th>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    @else
                                        <div class="alert alert-info">
                                            Aucune donnée disponible pour les paiements par classe.
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Scripts pour les graphiques pourront être ajoutés ici ultérieurement
    // Utilisation de Chart.js recommandée
</script>
@endpush 

@extends('layouts.app')

@section('title', 'Tableau de bord financier')

@section('styles')
<style>
    .finance-card {
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        overflow: hidden;
        border: none;
        margin-bottom: 20px;
    }
    
    .finance-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15);
    }
    
    .finance-card .card-header {
        padding: 15px 20px;
        border-bottom: none;
    }
    
    .finance-card .card-body {
        padding: 20px;
    }
    
    .finance-amount {
        font-size: 2.5rem;
        font-weight: 700;
        margin: 10px 0;
        line-height: 1.2;
    }
    
    .finance-label {
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 0;
        opacity: 0.8;
    }
    
    .finance-icon {
        font-size: 2.5rem;
        opacity: 0.3;
        position: absolute;
        right: 20px;
        top: 20px;
    }
    
    .recettes-card {
        background: linear-gradient(135deg, #1e8e3e 0%, #34a853 100%);
        color: white;
    }
    
    .depenses-card {
        background: linear-gradient(135deg, #d93025 0%, #ea4335 100%);
        color: white;
    }
    
    .balance-card {
        background: linear-gradient(135deg, #1a73e8 0%, #4285f4 100%);
        color: white;
    }
    
    .date-filter-container {
        background-color: #f8f9fa;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 25px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.05);
    }
    
    .action-button {
        border-radius: 50px;
        padding: 10px 20px;
        font-weight: 500;
        transition: all 0.3s ease;
    }
    
    .action-button i {
        margin-right: 8px;
    }
    
    .action-button.btn-primary {
        background-color: #1a73e8;
        border-color: #1a73e8;
    }
    
    .action-button.btn-primary:hover {
        background-color: #1765cc;
        border-color: #1765cc;
    }
    
    .action-button.btn-success {
        background-color: #1e8e3e;
        border-color: #1e8e3e;
    }
    
    .action-button.btn-success:hover {
        background-color: #187733;
        border-color: #187733;
    }
    
    .action-button.btn-danger {
        background-color: #d93025;
        border-color: #d93025;
    }
    
    .action-button.btn-danger:hover {
        background-color: #c62828;
        border-color: #c62828;
    }
    
    .table-container {
        background-color: white;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        padding: 0;
        margin-bottom: 30px;
        overflow: hidden;
    }
    
    .table-container .table-header {
        background-color: #f8f9fa;
        padding: 15px 20px;
        border-bottom: 1px solid #e9ecef;
    }
    
    .table-container .table-header h5 {
        margin-bottom: 0;
        font-weight: 600;
    }
    
    .custom-filter-btn {
        padding: 10px 20px;
        background-color: #1a73e8;
        color: white;
        border: none;
        border-radius: 5px;
        font-weight: 500;
    }
    
    .custom-filter-btn:hover {
        background-color: #1765cc;
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0 fw-bold">
                        <i class="fas fa-chart-line text-primary me-2"></i>Tableau de bord financier
                    </h5>
                    <div>
                        <a href="{{ route('esbtp.comptabilite.paiements.create') }}" class="btn action-button btn-success me-2">
                            <i class="fas fa-plus-circle"></i> Nouveau paiement
                        </a>
                        <a href="{{ route('esbtp.comptabilite.depenses.create') }}" class="btn action-button btn-danger">
                            <i class="fas fa-plus-circle"></i> Nouvelle dépense
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Filtres de période -->
                    <div class="date-filter-container">
                        <form method="GET" action="{{ route('esbtp.comptabilite.index') }}" class="row g-3">
                            <div class="col-md-4">
                                <label for="date_debut" class="form-label fw-semibold">Période du</label>
                                <input type="date" class="form-control" id="date_debut" name="date_debut" 
                                       value="{{ $debut ?? now()->startOfMonth()->format('Y-m-d') }}">
                            </div>
                            <div class="col-md-4">
                                <label for="date_fin" class="form-label fw-semibold">au</label>
                                <input type="date" class="form-control" id="date_fin" name="date_fin" 
                                       value="{{ $fin ?? now()->endOfMonth()->format('Y-m-d') }}">
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="submit" class="btn custom-filter-btn">
                                    <i class="fas fa-filter me-1"></i> Filtrer
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Résumé financier -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card finance-card recettes-card">
                                <div class="card-body">
                                    <i class="fas fa-money-bill-wave finance-icon"></i>
                                    <p class="finance-label">Recettes</p>
                                    <h2 class="finance-amount">{{ number_format($totalRecettes ?? 0, 0, ',', ' ') }} FCFA</h2>
                                    <p class="mb-0">Total des paiements reçus</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card finance-card depenses-card">
                                <div class="card-body">
                                    <i class="fas fa-file-invoice finance-icon"></i>
                                    <p class="finance-label">Dépenses</p>
                                    <h2 class="finance-amount">{{ number_format($totalDepenses ?? 0, 0, ',', ' ') }} FCFA</h2>
                                    <p class="mb-0">Total des dépenses effectuées</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card finance-card balance-card">
                                <div class="card-body">
                                    <i class="fas fa-balance-scale finance-icon"></i>
                                    <p class="finance-label">Balance</p>
                                    <h2 class="finance-amount">{{ number_format($balance ?? 0, 0, ',', ' ') }} FCFA</h2>
                                    <p class="mb-0">Différence recettes - dépenses</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Transactions récentes -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="table-container">
                                <div class="table-header d-flex justify-content-between align-items-center">
                                    <h5>Paiements récents</h5>
                                    <a href="{{ route('esbtp.comptabilite.paiements') }}" class="btn btn-sm btn-outline-primary">Voir tout</a>
                                </div>
                                <div class="p-3">
                                    @if(isset($recentesPaiements) && $recentesPaiements->count() > 0)
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Date</th>
                                                        <th>Étudiant</th>
                                                        <th>Montant</th>
                                                        <th>Type</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($recentesPaiements as $paiement)
                                                        <tr>
                                                            <td>{{ $paiement->date_paiement->format('d/m/Y') }}</td>
                                                            <td>{{ $paiement->etudiant->user->name ?? 'N/A' }}</td>
                                                            <td>{{ number_format($paiement->montant, 0, ',', ' ') }} FCFA</td>
                                                            <td>{{ $paiement->type_paiement }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <div class="alert alert-info">Aucun paiement récent</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="table-container">
                                <div class="table-header d-flex justify-content-between align-items-center">
                                    <h5>Dépenses récentes</h5>
                                    <a href="{{ route('esbtp.comptabilite.depenses') }}" class="btn btn-sm btn-outline-primary">Voir tout</a>
                                </div>
                                <div class="p-3">
                                    @if(isset($recentesDepenses) && $recentesDepenses->count() > 0)
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Date</th>
                                                        <th>Description</th>
                                                        <th>Catégorie</th>
                                                        <th>Montant</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($recentesDepenses as $depense)
                                                        <tr>
                                                            <td>{{ $depense->date_depense->format('d/m/Y') }}</td>
                                                            <td>{{ $depense->description ?? $depense->libelle }}</td>
                                                            <td>{{ $depense->categorie->nom ?? 'N/A' }}</td>
                                                            <td>{{ number_format($depense->montant, 0, ',', ' ') }} FCFA</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <div class="alert alert-info">Aucune dépense récente</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Étudiants avec paiements en retard -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="table-container">
                                <div class="table-header">
                                    <h5>Étudiants avec paiements en retard</h5>
                                </div>
                                <div class="p-3">
                                    @if(isset($etudiantsEnRetard) && $etudiantsEnRetard->count() > 0)
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Étudiant</th>
                                                        <th>Classe</th>
                                                        <th>Montant dû</th>
                                                        <th>Date limite</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($etudiantsEnRetard as $etudiant)
                                                        <tr>
                                                            <td>{{ $etudiant->user->name }}</td>
                                                            <td>{{ $etudiant->classe->nom ?? 'Non assignée' }}</td>
                                                            <td>{{ number_format($etudiant->montant_du, 0, ',', ' ') }} FCFA</td>
                                                            <td>{{ $etudiant->date_echeance->format('d/m/Y') }}</td>
                                                            <td>
                                                                <a href="{{ route('esbtp.comptabilite.paiements.create', ['etudiant_id' => $etudiant->id]) }}" class="btn btn-sm btn-primary">
                                                                    <i class="fas fa-plus"></i> Paiement
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <div class="alert alert-success">Aucun étudiant en retard de paiement</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Rapports et statistiques -->
                    <div class="row">
                        <div class="col-12 text-center mb-4">
                            <h4 class="fw-bold">Rapports et statistiques</h4>
                            <p class="text-muted">Visualisez les tendances financières de l'école</p>
                        </div>
                        <div class="col-md-6 mb-4">
                            <div class="table-container">
                                <div class="table-header">
                                    <h5>Évolution mensuelle des finances</h5>
                                </div>
                                <div class="p-4">
                                    <div style="height: 300px; background-color: #f8f9fa; border-radius: 8px; display: flex; justify-content: center; align-items: center;">
                                        <div class="text-center">
                                            <i class="fas fa-chart-line fa-3x text-primary mb-3"></i>
                                            <p>Graphique d'évolution mensuelle</p>
                                            <a href="{{ route('esbtp.comptabilite.rapports') }}" class="btn btn-primary">
                                                Voir les rapports détaillés
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <div class="table-container">
                                <div class="table-header">
                                    <h5>Répartition des dépenses</h5>
                                </div>
                                <div class="p-4">
                                    <div style="height: 300px; background-color: #f8f9fa; border-radius: 8px; display: flex; justify-content: center; align-items: center;">
                                        <div class="text-center">
                                            <i class="fas fa-chart-pie fa-3x text-primary mb-3"></i>
                                            <p>Graphique de répartition</p>
                                            <a href="{{ route('esbtp.comptabilite.rapports') }}" class="btn btn-primary">
                                                Voir les rapports détaillés
                                            </a>
                                        </div>
                                    </div>
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
    document.addEventListener('DOMContentLoaded', function() {
        // Initialisation des graphiques si nécessaire
        
        // Vérification des liens des boutons d'action
        const nouveauPaiementBtn = document.querySelector('a[href="{{ route("esbtp.comptabilite.paiements.create") }}"]');
        const nouvelleDepenseBtn = document.querySelector('a[href="{{ route("esbtp.comptabilite.depenses.create") }}"]');
        
        if (nouveauPaiementBtn) {
            nouveauPaiementBtn.addEventListener('click', function(e) {
                debugLog('Navigation vers création de paiement');
            });
        }
        
        if (nouvelleDepenseBtn) {
            nouvelleDepenseBtn.addEventListener('click', function(e) {
                debugLog('Navigation vers création de dépense');
            });
        }
    });
</script>
@endpush 
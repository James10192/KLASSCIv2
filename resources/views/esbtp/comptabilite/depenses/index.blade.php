@extends('layouts.app')

@section('title', 'Gestion des dépenses')

@section('styles')
<style>
    /* Styles généraux */
    .finance-card {
        border-radius: 12px;
        box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
        margin-bottom: 1.75rem;
        overflow: hidden;
        border: none;
    }
    
    .finance-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    }
    
    .card-header-gradient {
        background: linear-gradient(135deg, #0056b3, #004494);
        color: white;
        padding: 20px 25px;
        position: relative;
    }
    
    .card-header-gradient::after {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        bottom: 0;
        left: 0;
        background: linear-gradient(45deg, rgba(0, 0, 0, 0.1), transparent);
        z-index: 1;
    }
    
    .card-header-gradient > * {
        position: relative;
        z-index: 2;
    }
    
    /* Styles pour les statistiques */
    .stat-card {
        text-align: center;
        padding: 20px;
        border-radius: 12px;
        background: white;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        margin-bottom: 20px;
        position: relative;
        overflow: hidden;
        border: none;
        transition: all 0.3s ease;
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    }
    
    .stat-card.total-card {
        background: linear-gradient(135deg, #e74c3c, #c0392b);
        color: white;
    }
    
    .stat-card.avg-card {
        background: linear-gradient(135deg, #0056b3, #004494);
        color: white;
    }
    
    .stat-card.count-card {
        background: linear-gradient(135deg, #2ecc71, #27ae60);
        color: white;
    }
    
    .stat-icon {
        font-size: 2.5rem;
        margin-bottom: 15px;
        opacity: 0.8;
    }
    
    .stat-value {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 5px;
    }
    
    .stat-label {
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        opacity: 0.8;
    }
    
    /* Styles pour les filtres */
    .filter-panel {
        background-color: white;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        margin-bottom: 25px;
        overflow: hidden;
        border: none;
    }
    
    .filter-header {
        padding: 15px 20px;
        background: #f8f9fa;
        border-bottom: 1px solid #f0f0f0;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .filter-header:hover {
        background: #f1f3f5;
    }
    
    .filter-header i {
        transition: transform 0.3s ease;
    }
    
    .filter-header[aria-expanded="true"] i {
        transform: rotate(180deg);
    }
    
    .filter-body {
        padding: 20px;
    }
    
    /* Styles pour la table */
    .custom-table {
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    }
    
    .custom-table thead {
        background: linear-gradient(135deg, #0056b3, #004494);
        color: white;
    }
    
    .custom-table th {
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.85rem;
        letter-spacing: 0.5px;
        padding: 15px;
        border: none;
    }
    
    .custom-table td {
        padding: 12px 15px;
        vertical-align: middle;
        border-color: #f0f0f0;
    }
    
    .custom-table tbody tr {
        transition: all 0.2s ease;
    }
    
    .custom-table tbody tr:hover {
        background-color: rgba(0, 86, 179, 0.05);
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
    }
    
    /* Styles pour les badges et boutons */
    .action-btn {
        width: 36px;
        height: 36px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        transition: all 0.3s ease;
        margin: 0 3px;
        border: none;
    }
    
    .action-btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
    }
    
    .action-btn.btn-info {
        background: #0056b3;
        color: white;
    }
    
    .action-btn.btn-primary {
        background: #2ecc71;
        color: white;
    }
    
    .action-btn.btn-danger {
        background: #e74c3c;
        color: white;
    }
    
    .btn-main {
        padding: 10px 20px;
        border-radius: 50px;
        font-weight: 600;
        letter-spacing: 0.5px;
        transition: all 0.3s ease;
        border: none;
    }
    
    .btn-main:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }
    
    /* Pagination personnalisée */
    .custom-pagination .page-item .page-link {
        border-radius: 50px;
        margin: 0 3px;
        color: #0056b3;
        border: none;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
    }
    
    .custom-pagination .page-item.active .page-link {
        background: linear-gradient(135deg, #0056b3, #004494);
        border: none;
    }
    
    /* Adaptations responsives */
    @media (max-width: 768px) {
        .stat-card {
            margin-bottom: 15px;
        }
        
        .stat-value {
            font-size: 1.5rem;
        }
        
        .custom-table {
            font-size: 0.9rem;
        }
    }
</style>
@endsection

@section('content')
<div class="container-fluid py-4">
    <!-- HEADER PREMIUM -->
    <div class="bg-gradient-primary rounded-4 p-5 mb-4 d-flex align-items-center justify-content-between gap-4 animate-fade-in-up" style="background: linear-gradient(135deg, #0453cb 0%, #5e91de 100%); min-height: 120px;">
        <div class="d-flex align-items-center gap-3">
            <div class="bg-white bg-opacity-25 rounded-circle d-flex align-items-center justify-content-center" style="width:56px;height:56px;">
                <i class="fas fa-money-bill-wave fa-2x text-white"></i>
            </div>
            <div>
                <h1 class="h3 fw-bold text-white mb-1">Gestion des dépenses</h1>
                <div class="text-white-50">Suivi et gestion des dépenses de l'établissement</div>
            </div>
        </div>
        <a href="{{ route('esbtp.comptabilite.depenses.create') }}" class="btn btn-lg btn-warning fw-bold shadow rounded-3 px-4 py-2 d-flex align-items-center gap-2 animate-fade-in-up">
            <i class="fas fa-plus"></i> Nouvelle dépense
        </a>
    </div>
    
    <!-- Statistiques des dépenses -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="stat-card total-card">
                <i class="fas fa-money-bill-wave stat-icon"></i>
                <div class="stat-value">{{ number_format($depenses->sum('montant') ?? 0, 0, ',', ' ') }}</div>
                <div class="stat-label">Total FCFA</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card avg-card">
                <i class="fas fa-calculator stat-icon"></i>
                <div class="stat-value">{{ number_format($depenses->avg('montant') ?? 0, 0, ',', ' ') }}</div>
                <div class="stat-label">Moyenne FCFA</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card count-card">
                <i class="fas fa-receipt stat-icon"></i>
                <div class="stat-value">{{ $depenses->count() ?? 0 }}</div>
                <div class="stat-label">Dépenses</div>
            </div>
        </div>
    </div>
    
    <!-- Panel de filtres -->
    <div class="filter-panel">
        <div class="filter-header d-flex justify-content-between align-items-center" data-bs-toggle="collapse" data-bs-target="#filterOptions" aria-expanded="false" aria-controls="filterOptions">
            <div>
                <i class="fas fa-filter me-2"></i>
                <span class="fw-bold">Filtres de recherche</span>
            </div>
            <i class="fas fa-chevron-down"></i>
        </div>
        <div class="collapse" id="filterOptions">
            <div class="filter-body">
                <form method="GET" action="{{ route('esbtp.comptabilite.depenses') }}" class="row g-3">
                    <div class="col-md-4">
                        <label for="categorie" class="form-label fw-bold">Catégorie</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-tag"></i></span>
                            <select class="form-select" id="categorie" name="categorie">
                                <option value="">Toutes les catégories</option>
                                @foreach($categories ?? [] as $categorie)
                                    <option value="{{ $categorie }}" {{ request('categorie') == $categorie ? 'selected' : '' }}>
                                        {{ $categorie }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label for="date_debut" class="form-label fw-bold">Date début</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                            <input type="date" class="form-control" id="date_debut" name="date_debut" value="{{ request('date_debut') }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label for="date_fin" class="form-label fw-bold">Date fin</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                            <input type="date" class="form-control" id="date_fin" name="date_fin" value="{{ request('date_fin') }}">
                        </div>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <div class="d-grid gap-2 w-100">
                            <button type="submit" class="btn btn-primary btn-main">
                                <i class="fas fa-search me-2"></i>Filtrer
                            </button>
                            <a href="{{ route('esbtp.comptabilite.depenses') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-redo me-2"></i>Réinitialiser
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Tableau des dépenses -->
    <div class="finance-card">
        <div class="card-header-gradient d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold">
                <i class="fas fa-list me-2"></i>Liste des dépenses
            </h5>
            <span class="badge bg-light text-dark rounded-pill px-3 py-2">
                {{ $depenses->count() }} dépense(s) trouvée(s)
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-borderless custom-table mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Date</th>
                            <th>Description</th>
                            <th>Catégorie</th>
                            <th class="text-end">Montant</th>
                            <th>Bénéficiaire</th>
                            <th>Référence</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($depenses ?? [] as $depense)
                            <tr>
                                <td>
                                    <span class="fw-bold">#{{ $depense->id }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark rounded-pill px-3 py-2">
                                        <i class="far fa-calendar-alt me-1"></i>
                                        {{ $depense->date_depense ? $depense->date_depense->format('d/m/Y') : 'N/A' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="text-truncate" style="max-width: 150px;" title="{{ $depense->description ?? $depense->libelle }}">
                                        {{ $depense->description ?? $depense->libelle }}
                                    </div>
                                </td>
                                <td>
                                    @if($depense->categorie)
                                        <span class="badge rounded-pill px-3 py-2" style="background-color: rgba(0, 86, 179, 0.2); color: #0056b3;">
                                            {{ $depense->categorie->nom }}
                                        </span>
                                    @else
                                        <span class="badge rounded-pill px-3 py-2 bg-light text-secondary">
                                            Non catégorisé
                                        </span>
                                    @endif
                                </td>
                                <td class="text-end fw-bold text-danger">
                                    {{ number_format($depense->montant, 0, ',', ' ') }} FCFA
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar avatar-sm bg-light rounded-circle me-2 d-flex align-items-center justify-content-center">
                                            <i class="fas fa-user text-primary"></i>
                                        </div>
                                        <span>{{ $depense->beneficiaire ?? 'N/A' }}</span>
                                    </div>
                                </td>
                                <td>
                                    @if($depense->reference)
                                        <span class="badge bg-light text-dark rounded-pill px-3 py-2">
                                            <i class="fas fa-hashtag me-1 opacity-50"></i>{{ $depense->reference }}
                                        </span>
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('esbtp.comptabilite.depenses.show', $depense->id) }}" class="btn action-btn btn-info" title="Voir les détails">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="#" class="btn action-btn btn-primary" onclick="window.print()" title="Imprimer">
                                        <i class="fas fa-print"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <div class="py-5">
                                        <div class="mb-3">
                                            <i class="fas fa-search fa-3x text-muted"></i>
                                        </div>
                                        <h5 class="mb-2">Aucune dépense trouvée</h5>
                                        <p class="text-muted mb-4">Essayez de modifier vos filtres ou d'ajouter une nouvelle dépense</p>
                                        <a href="{{ route('esbtp.comptabilite.depenses.create') }}" class="btn btn-primary btn-main px-4">
                                            <i class="fas fa-plus me-2"></i>Nouvelle dépense
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Pagination -->
    @if(isset($depenses) && $depenses->hasPages())
        <div class="d-flex justify-content-center mt-4">
            <div class="custom-pagination">
                {{ $depenses->appends(request()->query())->links() }}
            </div>
        </div>
    @endif

    <!-- Liens rapides -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="finance-card">
                <div class="card-body">
                    <h5 class="card-title mb-4"><i class="fas fa-link me-2"></i>Accès rapides</h5>
                    <div class="d-flex flex-wrap gap-3">
                        <a href="{{ route('esbtp.comptabilite.rapports') }}" class="btn btn-outline-primary btn-main">
                            <i class="fas fa-chart-pie me-2"></i>Rapports financiers
                        </a>
                        <a href="{{ route('esbtp.comptabilite.index') }}" class="btn btn-outline-secondary btn-main">
                            <i class="fas fa-home me-2"></i>Tableau de bord
                        </a>
                        <a href="{{ route('esbtp.comptabilite.paiements') }}" class="btn btn-outline-success btn-main">
                            <i class="fas fa-money-bill-wave me-2"></i>Paiements reçus
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scripts spécifiques à la page -->
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Animation pour l'ouverture/fermeture du panneau de filtres
        const filterHeader = document.querySelector('.filter-header');
        const chevronIcon = filterHeader.querySelector('.fa-chevron-down');
        
        filterHeader.addEventListener('click', function() {
            chevronIcon.classList.toggle('rotate-180');
        });
        
        // Si des filtres sont actifs, ouvrir automatiquement le panneau
        @if(request()->has('categorie') || request()->has('date_debut') || request()->has('date_fin'))
            new bootstrap.Collapse(document.getElementById('filterOptions'), {
                show: true
            });
        @endif
    });
</script>
@endpush
@endsection 
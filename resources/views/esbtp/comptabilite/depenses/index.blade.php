@extends('esbtp.comptabilite.components.dashboard-layout')

@section('title', 'Gestion des dépenses')

@section('sidebar')
    <li class="navigation-item">
        <a href="{{ route('esbtp.comptabilite.dashboard-avance') }}" class="navigation-link">
            <i class="fas fa-home"></i> Accueil
        </a>
    </li>
    <li class="navigation-item">
        <a href="{{ route('esbtp.comptabilite.paiements') }}" class="navigation-link">
            <i class="fas fa-credit-card"></i> Paiements
        </a>
    </li>
    <li class="navigation-item">
        <a href="{{ route('esbtp.comptabilite.depenses') }}" class="navigation-link active">
            <i class="fas fa-shopping-cart"></i> Dépenses
        </a>
    </li>
    <li class="navigation-item">
        <a href="{{ route('esbtp.comptabilite.bons-sortie.index') }}" class="navigation-link">
            <i class="fas fa-file-export"></i> Bons de Sortie
        </a>
    </li>
    <li class="navigation-item">
        <a href="{{ route('esbtp.comptabilite.factures') }}" class="navigation-link">
            <i class="fas fa-file-invoice"></i> Factures
        </a>
    </li>
    <li class="navigation-item">
        <a href="{{ route('esbtp.comptabilite.rapports') }}" class="navigation-link">
            <i class="fas fa-chart-bar"></i> Rapports
        </a>
    </li>
@endsection

@section('header')
    <div class="header-left">
        <h1><i class="fas fa-shopping-cart color-primary"></i> Gestion des dépenses</h1>
        <p class="header-subtitle">Suivi et gestion des dépenses de l'établissement</p>
            </div>
    <div class="header-actions">
        <a href="{{ route('esbtp.comptabilite.depenses.create') }}" class="btn-acasi primary">
            <i class="fas fa-plus-circle"></i> Nouvelle dépense
        </a>
    </div>
@endsection

@section('sidebarRight')
    <h3 class="sidebar-title">Actions Rapides</h3>
    <div style="display: flex; flex-direction: column; gap: var(--space-sm);">
        <a href="{{ route('esbtp.comptabilite.rapports') }}" class="btn-acasi secondary">
            <i class="fas fa-file-export"></i> Export Rapport
        </a>
        <a href="{{ route('esbtp.comptabilite.dashboard-avance') }}" class="btn-acasi secondary">
            <i class="fas fa-chart-line"></i> Voir Dashboard Avancé
        </a>
    </div>
@endsection

@section('content-block')
    {{-- Filtres de recherche --}}
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-filter me-1"></i> Filtres
        </div>
        <div class="card-body">
            <form action="{{ route('esbtp.comptabilite.depenses') }}" method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="categorie" class="form-label">Catégorie</label>
                            <select class="form-select" id="categorie" name="categorie">
                                <option value="">Toutes les catégories</option>
                                @foreach($categories ?? [] as $categorie)
                                    <option value="{{ $categorie }}" {{ request('categorie') == $categorie ? 'selected' : '' }}>
                                        {{ $categorie }}
                                    </option>
                                @endforeach
                            </select>
                    </div>
                    <div class="col-md-3">
                    <label for="date_debut" class="form-label">Date début</label>
                            <input type="date" class="form-control" id="date_debut" name="date_debut" value="{{ request('date_debut') }}">
                    </div>
                    <div class="col-md-3">
                    <label for="date_fin" class="form-label">Date fin</label>
                            <input type="date" class="form-control" id="date_fin" name="date_fin" value="{{ request('date_fin') }}">
                        </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search me-1"></i> Rechercher
                            </button>
                    <a href="{{ route('esbtp.comptabilite.depenses') }}" class="btn btn-secondary w-100 ms-2">
                        <i class="fas fa-redo me-1"></i> Réinitialiser
                            </a>
                    </div>
                </form>
        </div>
    </div>
    {{-- Tableau des dépenses --}}
            <div class="table-responsive">
        <table class="table table-striped table-hover">
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
                        <td><span class="fw-bold">#{{ $depense->id }}</span></td>
                        <td>{{ $depense->date_depense ? $depense->date_depense->format('d/m/Y') : 'N/A' }}</td>
                        <td><div class="text-truncate" style="max-width: 150px;" title="{{ $depense->description ?? $depense->libelle }}">{{ $depense->description ?? $depense->libelle }}</div></td>
                        <td>@if($depense->categorie)<span class="badge rounded-pill px-3 py-2 bg-info text-dark">{{ $depense->categorie->nom }}</span>@else<span class="badge rounded-pill px-3 py-2 bg-light text-secondary">Non catégorisé</span>@endif</td>
                        <td class="text-end fw-bold text-danger">{{ number_format($depense->montant, 0, ',', ' ') }} FCFA</td>
                        <td>{{ $depense->beneficiaire ?? '-' }}</td>
                        <td>{{ $depense->reference ?? '-' }}</td>
                                <td class="text-center">
                            <a href="{{ route('esbtp.comptabilite.depenses.show', $depense->id) }}" class="btn btn-sm btn-info" title="Voir"><i class="fas fa-eye"></i></a>
                            <a href="{{ route('esbtp.comptabilite.depenses.edit', $depense->id) }}" class="btn btn-sm btn-primary" title="Éditer"><i class="fas fa-edit"></i></a>
                            <form action="{{ route('esbtp.comptabilite.depenses.destroy', $depense->id) }}" method="POST" class="d-inline-block" onsubmit="return confirm('Supprimer cette dépense ?');">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-danger" title="Supprimer"><i class="fas fa-trash"></i></button>
                            </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                        <td colspan="8" class="text-center text-muted py-4">Aucune dépense trouvée.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
    {{-- Pagination --}}
    <div class="mt-3">
        {{ $depenses->links() }}
    </div>
@endsection

@push('styles')
<link href="{{ asset('css/dashboard-moderne.css') }}" rel="stylesheet">
@endpush

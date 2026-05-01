@extends('esbtp.comptabilite.components.dashboard-layout')

@section('title', 'Gestion des paiements')

@section('sidebar')
    <li class="navigation-item">
        <a href="{{ route('esbtp.comptabilite.dashboard') }}" class="navigation-link">
            <i class="fas fa-home"></i> Accueil
        </a>
    </li>
    <li class="navigation-item">
        <a href="{{ route('esbtp.comptabilite.paiements') }}" class="navigation-link active">
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
        <a href="{{ route('esbtp.comptabilite.rapports') }}" class="navigation-link">
            <i class="fas fa-chart-bar"></i> Rapports
        </a>
    </li>
@endsection

@section('header')
    <div class="header-left">
        <h1><i class="fas fa-credit-card color-primary"></i> Gestion des paiements</h1>
        <p class="header-subtitle">Suivi et gestion des paiements étudiants</p>
    </div>
    <div class="header-actions">
        @can('paiements.create')
        <a href="{{ route('esbtp.comptabilite.paiements.create') }}" class="btn-acasi primary">
            <i class="fas fa-plus-circle"></i> Nouveau paiement
        </a>
        @endcan
    </div>
@endsection

@section('sidebarRight')
    <h3 class="sidebar-title">Actions Rapides</h3>
    <div style="display: flex; flex-direction: column; gap: var(--space-sm);">
        @can('comptabilite.reports.export')
        <a href="{{ route('esbtp.comptabilite.rapports') }}" class="btn-acasi secondary">
            <i class="fas fa-file-export"></i> Export Rapport
            </a>
        @endcan
        </div>
@endsection

@section('content-block')
    {{-- Filtres de recherche --}}
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-filter me-1"></i> Filtres
                </div>
                <div class="card-body">
                    <form action="{{ route('esbtp.comptabilite.paiements') }}" method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label for="etudiant" class="form-label">Étudiant</label>
                            <input type="text" class="form-control" id="etudiant" name="etudiant" placeholder="Nom ou matricule" value="{{ request('etudiant') }}">
                        </div>
                        <div class="col-md-3">
                            <label for="date_debut" class="form-label">Date début</label>
                            <input type="date" class="form-control" id="date_debut" name="date_debut" value="{{ request('date_debut') }}">
                        </div>
                        <div class="col-md-3">
                            <label for="date_fin" class="form-label">Date fin</label>
                            <input type="date" class="form-control" id="date_fin" name="date_fin" value="{{ request('date_fin') }}">
                        </div>
                        <div class="col-md-3">
                            <label for="statut" class="form-label">Statut</label>
                            <select class="form-select" id="statut" name="statut">
                                <option value="">Tous</option>
                        <option value="complété" {{ request('statut') == 'complété' ? 'selected' : '' }}>Complété</option>
                                <option value="en attente" {{ request('statut') == 'en attente' ? 'selected' : '' }}>En attente</option>
                                <option value="annulé" {{ request('statut') == 'annulé' ? 'selected' : '' }}>Annulé</option>
                            </select>
                        </div>
                        <div class="col-12 text-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-1"></i> Rechercher
                            </button>
                            <a href="{{ route('esbtp.comptabilite.paiements') }}" class="btn btn-secondary">
                                <i class="fas fa-redo me-1"></i> Réinitialiser
                            </a>
                        </div>
                    </form>
                </div>
            </div>
    {{-- Tableau des paiements --}}
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Référence</th>
                            <th>Étudiant</th>
                            <th>Type</th>
                            <th>Montant</th>
                            <th>Mode de paiement</th>
                            <th>Date</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($paiements as $paiement)
                        <tr>
                            <td>{{ $paiement->reference_paiement }}</td>
                            <td>{{ $paiement->etudiant->nom }} {{ $paiement->etudiant->prenom }}</td>
                            <td>{{ $paiement->type_paiement }}</td>
                            <td class="text-end">{{ number_format($paiement->montant, 0, ',', ' ') }} FCFA</td>
                            <td>{{ $paiement->mode_paiement }}</td>
                            <td>{{ \Carbon\Carbon::parse($paiement->date_paiement)->format('d/m/Y') }}</td>
                            <td>
                        @if($paiement->statut == 'complété')
                                <span class="badge bg-success">Complété</span>
                                @elseif($paiement->statut == 'en attente')
                                <span class="badge bg-warning">En attente</span>
                                @elseif($paiement->statut == 'annulé')
                                <span class="badge bg-danger">Annulé</span>
                                @else
                                <span class="badge bg-secondary">{{ $paiement->statut }}</span>
                                @endif
                            </td>
                            <td class="text-nowrap">
                                <div class="btn-group" role="group">
                                    <a href="{{ route('esbtp.comptabilite.paiements.show', $paiement->id) }}" class="btn btn-sm btn-info" title="Détails">
                                        <i class="fas fa-eye"></i>
                                    </a>
                            @if($paiement->statut != 'complété')
                                    @can('paiements.edit')
                                    <a href="{{ route('esbtp.comptabilite.paiements.edit', $paiement->id) }}" class="btn btn-sm btn-primary" title="Modifier">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    @endcan
                                    @endif
                                    <a href="{{ route('esbtp.comptabilite.paiements.recu', $paiement->id) }}" class="btn btn-sm btn-success" title="Générer reçu" target="_blank">
                                        <i class="fas fa-file-invoice"></i>
                                    </a>
                                    @if($paiement->statut == 'en attente')
                                    @can('paiements.validate')
                                    <form action="{{ route('esbtp.comptabilite.paiements.valider', $paiement->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success" title="Valider">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </form>
                                    <form action="{{ route('esbtp.comptabilite.paiements.rejeter', $paiement->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir rejeter ce paiement?')">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-danger" title="Rejeter">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </form>
                                    @endcan
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center">Aucun paiement trouvé</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
    {{-- Pagination --}}
    <div class="mt-3">
        {{ $paiements->links() }}
</div>
@endsection 

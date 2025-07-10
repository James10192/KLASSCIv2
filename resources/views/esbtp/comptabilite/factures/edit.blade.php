@extends('esbtp.comptabilite.components.dashboard-layout')

@section('title', 'Édition facture ' . $facture->numero)

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
        <a href="{{ route('esbtp.comptabilite.depenses') }}" class="navigation-link">
            <i class="fas fa-shopping-cart"></i> Dépenses
        </a>
    </li>
    <li class="navigation-item">
        <a href="{{ route('esbtp.comptabilite.bons-sortie.index') }}" class="navigation-link">
            <i class="fas fa-file-export"></i> Bons de Sortie
        </a>
    </li>
    <li class="navigation-item">
        <a href="{{ route('esbtp.comptabilite.factures') }}" class="navigation-link active">
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
        <h1><i class="fas fa-file-invoice color-primary"></i> Édition de la facture <span class="text-primary">#{{ $facture->numero }}</span></h1>
        <p class="header-subtitle">Modifier les informations de la facture</p>
    </div>
    <div class="header-actions">
        <a href="{{ route('esbtp.comptabilite.factures') }}" class="btn-acasi secondary">
            <i class="fas fa-arrow-left"></i> Retour
        </a>
    </div>
@endsection

@section('sidebarRight')
    <h3 class="sidebar-title">Actions Rapides</h3>
    <div style="display: flex; flex-direction: column; gap: var(--space-sm);">
        <a href="{{ route('esbtp.comptabilite.factures.pdf', $facture->id) }}" class="btn-acasi secondary" target="_blank">
            <i class="fas fa-file-pdf"></i> PDF
        </a>
    </div>
@endsection

@section('content-block')
<div class="container-fluid py-4">
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <form action="{{ route('esbtp.comptabilite.factures.update', $facture->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Informations principales</h5>
                        <div class="mb-3">
                            <label class="form-label">Statut</label>
                            <select name="statut" class="form-select">
                                <option value="émise" {{ $facture->statut === 'émise' ? 'selected' : '' }}>Émise</option>
                                <option value="payée" {{ $facture->statut === 'payée' ? 'selected' : '' }}>Payée</option>
                                <option value="partiellement payée" {{ $facture->statut === 'partiellement payée' ? 'selected' : '' }}>Partiellement payée</option>
                                <option value="annulée" {{ $facture->statut === 'annulée' ? 'selected' : '' }}>Annulée</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Date émission</label>
                            <input type="date" name="date_emission" class="form-control" value="{{ $facture->date_emission ? $facture->date_emission->format('Y-m-d') : '' }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Date échéance</label>
                            <input type="date" name="date_echeance" class="form-control" value="{{ $facture->date_echeance ? $facture->date_echeance->format('Y-m-d') : '' }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Fournisseur</label>
                            <input type="text" name="fournisseur" class="form-control" value="{{ $facture->fournisseur->nom ?? '' }}" placeholder="Nom du fournisseur">
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Montants</h5>
                        <div class="mb-3">
                            <label class="form-label">Montant total</label>
                            <input type="number" name="montant_total" class="form-control" value="{{ $facture->montant_total }}" min="0" step="1">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Montant payé</label>
                            <input type="number" name="montant_paye" class="form-control" value="{{ $facture->montant_paye }}" min="0" step="1">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card shadow-sm mb-4">
            <div class="card-body p-0">
                <h5 class="card-title px-4 pt-4">Détails de la facture</h5>
                <div class="table-responsive">
                    <table class="table table-striped align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Description</th>
                                <th>Quantité</th>
                                <th>Prix unitaire</th>
                                <th>Montant</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($facture->details as $i => $detail)
                                <tr>
                                    <td>{{ $i+1 }}</td>
                                    <td><input type="text" name="details[{{ $i }}][description]" class="form-control" value="{{ $detail->description }}" required></td>
                                    <td><input type="number" name="details[{{ $i }}][quantite]" class="form-control" value="{{ $detail->quantite }}" min="1" required></td>
                                    <td><input type="number" name="details[{{ $i }}][prix_unitaire]" class="form-control" value="{{ $detail->prix_unitaire }}" min="0" required></td>
                                    <td><input type="number" name="details[{{ $i }}][montant]" class="form-control" value="{{ $detail->montant }}" min="0" required></td>
                                    <td><!-- Actions JS à ajouter pour supprimer une ligne --></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="d-flex justify-content-end gap-2">
            <button type="submit" class="btn-acasi primary">
                <i class="fas fa-save"></i> Enregistrer
            </button>
            <a href="{{ route('esbtp.comptabilite.factures') }}" class="btn-acasi secondary">
                <i class="fas fa-times"></i> Annuler
            </a>
        </div>
    </form>
</div>
@endsection

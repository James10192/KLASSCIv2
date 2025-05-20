@extends('layouts.app')

@section('title', 'Édition facture ' . $facture->numero)

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <h2 class="fw-bold mb-0">Éditer la facture <span class="text-primary">#{{ $facture->numero }}</span></h2>
                <span class="badge bg-{{ $facture->statut === 'émise' ? 'info' : ($facture->statut === 'payée' ? 'success' : ($facture->statut === 'partiellement payée' ? 'warning' : 'secondary')) }}">
                    {{ ucfirst($facture->statut) }}
                </span>
            </div>
            <div>
                <a href="{{ route('esbtp.comptabilite.factures', $facture->id) }}" class="btn btn-outline-dark">
                    <i class="fas fa-arrow-left"></i> Retour
                </a>
            </div>
        </div>
    </div>
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
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Enregistrer
            </button>
            <a href="{{ route('esbtp.comptabilite.factures', $facture->id) }}" class="btn btn-secondary">
                <i class="fas fa-times"></i> Annuler
            </a>
        </div>
    </form>
</div>
@endsection

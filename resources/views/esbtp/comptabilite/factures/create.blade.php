@extends('esbtp.comptabilite.components.dashboard-layout')

@section('title', 'Nouvelle Facture')

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
            <i class="fas fa-money-bill-wave"></i> Dépenses
        </a>
    </li>
    <li class="navigation-item active">
        <a href="{{ route('esbtp.comptabilite.factures') }}" class="navigation-link">
            <i class="fas fa-file-invoice"></i> Factures
        </a>
    </li>
    <li class="navigation-item">
        <a href="{{ route('esbtp.comptabilite.rapports') }}" class="navigation-link">
            <i class="fas fa-chart-line"></i> Rapports
        </a>
    </li>
@endsection

@section('content-block')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="fw-bold mb-0">Nouvelle Facture</h2>
        </div>
    </div>
    <form method="POST" action="{{ route('esbtp.comptabilite.factures.store') }}" class="card p-4 shadow-sm">
        @csrf
        <div class="row g-3">
            <div class="col-md-6">
                <label for="numero" class="form-label fw-medium">Numéro <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('numero') is-invalid @enderror" id="numero" name="numero" value="{{ old('numero') }}" required>
                @error('numero')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6">
                <label for="date" class="form-label fw-medium">Date <span class="text-danger">*</span></label>
                <input type="date" class="form-control @error('date') is-invalid @enderror" id="date" name="date" value="{{ old('date', date('Y-m-d')) }}" required>
                @error('date')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6">
                <label for="fournisseur_id" class="form-label fw-medium">Fournisseur <span class="text-danger">*</span></label>
                <select class="form-select @error('fournisseur_id') is-invalid @enderror" id="fournisseur_id" name="fournisseur_id" required>
                    <option value="">Sélectionner...</option>
                    @foreach($fournisseurs as $fournisseur)
                        <option value="{{ $fournisseur->id }}" {{ old('fournisseur_id') == $fournisseur->id ? 'selected' : '' }}>{{ $fournisseur->nom }}</option>
                    @endforeach
                </select>
                @error('fournisseur_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6">
                <label for="montant" class="form-label fw-medium">Montant <span class="text-danger">*</span></label>
                <input type="number" step="0.01" class="form-control @error('montant') is-invalid @enderror" id="montant" name="montant" value="{{ old('montant') }}" required>
                @error('montant')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-12">
                <label for="description" class="form-label fw-medium">Description</label>
                <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description') }}</textarea>
                @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
        <div class="d-flex justify-content-end gap-3 mt-4">
            <button type="submit" class="btn btn-primary px-4 py-2">
                <i class="fas fa-save me-1"></i> Enregistrer
            </button>
            <a href="{{ route('esbtp.comptabilite.factures') }}" class="btn btn-outline-secondary px-4 py-2">
                <i class="fas fa-times me-1"></i> Annuler
            </a>
        </div>
    </form>
</div>
@endsection 
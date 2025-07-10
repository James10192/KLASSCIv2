@extends('esbtp.comptabilite.components.dashboard-layout')

@section('title', 'Nouvelle dépense')

@section('sidebar')
    <li class="navigation-item">
        <a href="{{ route('esbtp.comptabilite.dashboard-avance') }}" class="navigation-link">
            <i class="fas fa-home"></i> Accueil
        </a>
    </li>
    <li class="navigation-item">
        <a href="{{ route('esbtp.comptabilite.paiements') }}" class="navigation-link">
            <i class="fas fa-money-check-alt"></i> Paiements
        </a>
    </li>
    <li class="navigation-item active">
        <a href="{{ route('esbtp.comptabilite.depenses') }}" class="navigation-link">
            <i class="fas fa-receipt"></i> Dépenses
        </a>
    </li>
    <li class="navigation-item">
        <a href="{{ route('esbtp.comptabilite.factures') }}" class="navigation-link">
            <i class="fas fa-file-invoice"></i> Factures
        </a>
    </li>
    <li class="navigation-item">
        <a href="{{ route('esbtp.comptabilite.bons-sortie.index') }}" class="navigation-link">
            <i class="fas fa-truck-loading"></i> Bons de sortie
        </a>
    </li>
    <li class="navigation-item">
        <a href="{{ route('esbtp.comptabilite.rapports') }}" class="navigation-link">
            <i class="fas fa-chart-bar"></i> Rapports
        </a>
    </li>
@endsection

@section('sidebarRight')
    <div class="acasi-sidebar-right-block mb-4">
        <a href="{{ route('esbtp.comptabilite.depenses') }}" class="btn btn-outline-primary w-100 mb-2">
            <i class="fas fa-list"></i> Liste des dépenses
        </a>
        <a href="{{ route('esbtp.comptabilite.depenses.create') }}" class="btn btn-primary w-100">
            <i class="fas fa-plus"></i> Nouvelle dépense
        </a>
    </div>
@endsection

@section('content-block')
<div class="acasi-card p-5 rounded-3 shadow mb-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0 fw-bold"><i class="fas fa-plus-circle me-2"></i>Créer une nouvelle dépense</h4>
        <a href="{{ route('esbtp.comptabilite.depenses') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Retour à la liste
        </a>
    </div>
    @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif
    <form action="{{ route('esbtp.comptabilite.depenses.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <label for="libelle" class="form-label fw-medium">Libellé <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('libelle') is-invalid @enderror" id="libelle" name="libelle" value="{{ old('libelle') }}" required>
                @error('libelle')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6">
                <label for="categorie_id" class="form-label fw-medium">Catégorie <span class="text-danger">*</span></label>
                <select class="form-select @error('categorie_id') is-invalid @enderror" id="categorie_id" name="categorie_id" required>
                    <option value="">-- Sélectionnez une catégorie --</option>
                    @foreach($categories ?? [] as $categorie)
                    <option value="{{ $categorie->id }}" {{ old('categorie_id') == $categorie->id ? 'selected' : '' }}>
                        {{ $categorie->nom }} {{ !empty($categorie->code) ? '('.$categorie->code.')' : '' }}
                    </option>
                    @endforeach
                </select>
                @error('categorie_id')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <label for="montant" class="form-label fw-medium">Montant (FCFA) <span class="text-danger">*</span></label>
                <div class="input-group">
                    <input type="number" class="form-control @error('montant') is-invalid @enderror" id="montant" name="montant" value="{{ old('montant') }}" min="0" step="0.01" required>
                    <span class="input-group-text">FCFA</span>
                </div>
                @error('montant')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6">
                <label for="date_depense" class="form-label fw-medium">Date de dépense <span class="text-danger">*</span></label>
                <input type="date" class="form-control @error('date_depense') is-invalid @enderror" id="date_depense" name="date_depense" value="{{ old('date_depense', date('Y-m-d')) }}" required>
                @error('date_depense')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <label for="mode_paiement" class="form-label fw-medium">Mode de paiement <span class="text-danger">*</span></label>
                <select class="form-select @error('mode_paiement') is-invalid @enderror" id="mode_paiement" name="mode_paiement" required>
                    <option value="">-- Sélectionnez un mode --</option>
                    <option value="espèces" {{ old('mode_paiement') == 'espèces' ? 'selected' : '' }}>Espèces</option>
                    <option value="chèque" {{ old('mode_paiement') == 'chèque' ? 'selected' : '' }}>Chèque</option>
                    <option value="virement" {{ old('mode_paiement') == 'virement' ? 'selected' : '' }}>Virement</option>
                    <option value="carte bancaire" {{ old('mode_paiement') == 'carte bancaire' ? 'selected' : '' }}>Carte bancaire</option>
                    <option value="mobile money" {{ old('mode_paiement') == 'mobile money' ? 'selected' : '' }}>Mobile Money</option>
                </select>
                @error('mode_paiement')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6">
                <label for="reference" class="form-label fw-medium">Référence</label>
                <input type="text" class="form-control @error('reference') is-invalid @enderror" id="reference" name="reference" value="{{ old('reference') }}">
                @error('reference')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <small class="form-text text-muted">Numéro de facture, référence de transaction, etc.</small>
            </div>
        </div>
        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <label for="numero_transaction" class="form-label fw-medium">Numéro de transaction</label>
                <input type="text" class="form-control @error('numero_transaction') is-invalid @enderror" id="numero_transaction" name="numero_transaction" value="{{ old('numero_transaction') }}">
                @error('numero_transaction')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <small class="form-text text-muted">Numéro de chèque, référence de virement, etc.</small>
            </div>
            <div class="col-md-6">
                <label for="fournisseur_id" class="form-label fw-medium">Fournisseur</label>
                <select class="form-select select2 @error('fournisseur_id') is-invalid @enderror" id="fournisseur_id" name="fournisseur_id">
                    <option value="">-- Sélectionnez un fournisseur (facultatif) --</option>
                    @foreach($fournisseurs ?? [] as $fournisseur)
                    <option value="{{ $fournisseur->id }}" {{ old('fournisseur_id') == $fournisseur->id ? 'selected' : '' }}>
                        {{ $fournisseur->nom }}
                    </option>
                    @endforeach
                </select>
                @error('fournisseur_id')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
        <div class="row g-4 mb-4">
            <div class="col-md-12">
                <label for="description" class="form-label fw-medium">Description</label>
                <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description') }}</textarea>
                @error('description')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
        <div class="row g-4 mb-4">
            <div class="col-md-12">
                <label for="path_justificatif" class="form-label fw-medium">Justificatif (image ou PDF)</label>
                <input type="file" class="form-control @error('path_justificatif') is-invalid @enderror" id="path_justificatif" name="path_justificatif" accept=".jpg,.jpeg,.png,.pdf">
                @error('path_justificatif')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <small class="form-text text-muted">Formats acceptés: JPG, PNG, PDF (max 5 Mo)</small>
            </div>
        </div>
        <div class="row g-4 mb-4">
            <div class="col-md-12">
                <label for="notes_internes" class="form-label fw-medium">Notes internes</label>
                <textarea class="form-control @error('notes_internes') is-invalid @enderror" id="notes_internes" name="notes_internes" rows="2">{{ old('notes_internes') }}</textarea>
                @error('notes_internes')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <small class="form-text text-muted">Notes visibles uniquement par les administrateurs</small>
            </div>
        </div>
        <div class="d-flex justify-content-end gap-3">
            <button type="submit" class="btn btn-primary px-4 py-2">
                <i class="fas fa-save me-1"></i> Enregistrer
            </button>
            <a href="{{ route('esbtp.comptabilite.depenses') }}" class="btn btn-outline-secondary px-4 py-2">
                <i class="fas fa-times me-1"></i> Annuler
            </a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialiser Select2 si disponible
        if (typeof $.fn.select2 !== 'undefined') {
            $('.select2').select2({
                placeholder: 'Sélectionnez une option',
                allowClear: true
            });
        }
        // Gérer l'affichage du champ numéro de transaction en fonction du mode de paiement
        const modePaiementSelect = document.getElementById('mode_paiement');
        const numeroTransactionDiv = document.getElementById('numero_transaction').closest('.col-md-6');
        function toggleNumeroTransaction() {
            const selectedMode = modePaiementSelect.value;
            if (selectedMode === 'espèces') {
                numeroTransactionDiv.style.display = 'none';
            } else {
                numeroTransactionDiv.style.display = 'block';
            }
        }
        if (modePaiementSelect) {
            modePaiementSelect.addEventListener('change', toggleNumeroTransaction);
            // Exécuter au chargement de la page
            toggleNumeroTransaction();
        }
    });
</script>
@endpush 
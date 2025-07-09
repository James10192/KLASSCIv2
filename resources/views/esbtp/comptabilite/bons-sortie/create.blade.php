@extends('layouts.app')

@section('title', 'Nouveau Bon de Sortie')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h3 mb-0">
                    <i class="fas fa-plus-circle text-primary"></i>
                    Nouveau Bon de Sortie
                </h1>
                <a href="{{ route('esbtp.comptabilite.bons-sortie.index') }}"
                   class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Retour
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Formulaire -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-edit"></i> Informations du Bon
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('esbtp.comptabilite.bons-sortie.store') }}"
                          id="bonSortieForm">
                        @csrf

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label class="form-label">Libellé <span class="text-danger">*</span></label>
                                    <input type="text"
                                           name="libelle"
                                           class="form-control @error('libelle') is-invalid @enderror"
                                           value="{{ old('libelle') }}"
                                           required
                                           id="libelle"
                                           onkeyup="updatePreview()">
                                    @error('libelle')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label class="form-label">Montant (FCFA) <span class="text-danger">*</span></label>
                                    <input type="number"
                                           name="montant"
                                           class="form-control @error('montant') is-invalid @enderror"
                                           value="{{ old('montant') }}"
                                           required
                                           min="0"
                                           step="1"
                                           id="montant"
                                           onkeyup="updatePreview()">
                                    @error('montant')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label class="form-label">Date de dépense <span class="text-danger">*</span></label>
                                    <input type="date"
                                           name="date_depense"
                                           class="form-control @error('date_depense') is-invalid @enderror"
                                           value="{{ old('date_depense', date('Y-m-d')) }}"
                                           required
                                           id="date_depense"
                                           onchange="updatePreview()">
                                    @error('date_depense')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label class="form-label">Catégorie <span class="text-danger">*</span></label>
                                    <select name="categorie_id"
                                            class="form-control @error('categorie_id') is-invalid @enderror"
                                            required
                                            id="categorie_id"
                                            onchange="updatePreview()">
                                        <option value="">Sélectionner une catégorie</option>
                                        @foreach($categories as $categorie)
                                        <option value="{{ $categorie->id }}"
                                                {{ old('categorie_id') == $categorie->id ? 'selected' : '' }}>
                                            {{ $categorie->nom }}
                                        </option>
                                        @endforeach
                                    </select>
                                    @error('categorie_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label class="form-label">Fournisseur</label>
                                    <select name="fournisseur_id"
                                            class="form-control @error('fournisseur_id') is-invalid @enderror"
                                            id="fournisseur_id"
                                            onchange="updatePreview()">
                                        <option value="">Sélectionner un fournisseur</option>
                                        @foreach($fournisseurs as $fournisseur)
                                        <option value="{{ $fournisseur->id }}"
                                                {{ old('fournisseur_id') == $fournisseur->id ? 'selected' : '' }}>
                                            {{ $fournisseur->nom }}
                                        </option>
                                        @endforeach
                                    </select>
                                    @error('fournisseur_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label class="form-label">Mode de paiement <span class="text-danger">*</span></label>
                                    <select name="mode_paiement"
                                            class="form-control @error('mode_paiement') is-invalid @enderror"
                                            required
                                            id="mode_paiement"
                                            onchange="updatePreview()">
                                        <option value="">Sélectionner un mode</option>
                                        <option value="especes" {{ old('mode_paiement') == 'especes' ? 'selected' : '' }}>Espèces</option>
                                        <option value="cheque" {{ old('mode_paiement') == 'cheque' ? 'selected' : '' }}>Chèque</option>
                                        <option value="virement" {{ old('mode_paiement') == 'virement' ? 'selected' : '' }}>Virement</option>
                                        <option value="carte" {{ old('mode_paiement') == 'carte' ? 'selected' : '' }}>Carte bancaire</option>
                                    </select>
                                    @error('mode_paiement')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description"
                                      class="form-control @error('description') is-invalid @enderror"
                                      rows="3"
                                      id="description"
                                      onkeyup="updatePreview()"
                                      placeholder="Description détaillée de la dépense...">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group mb-3">
                            <div class="form-check">
                                <input type="checkbox"
                                       class="form-check-input"
                                       id="soumettre_approbation"
                                       name="soumettre_approbation"
                                       value="1"
                                       {{ old('soumettre_approbation') ? 'checked' : '' }}>
                                <label class="form-check-label" for="soumettre_approbation">
                                    Soumettre immédiatement pour approbation
                                </label>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('esbtp.comptabilite.bons-sortie.index') }}"
                               class="btn btn-secondary">
                                <i class="fas fa-times"></i> Annuler
                            </a>
                            <div>
                                <button type="button"
                                        class="btn btn-info me-2"
                                        onclick="previewPDF()">
                                    <i class="fas fa-eye"></i> Prévisualiser
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Enregistrer
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Prévisualisation -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-eye"></i> Prévisualisation
                    </h5>
                </div>
                <div class="card-body">
                    <div id="preview-container">
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-file-export fa-3x mb-3"></i>
                            <p>La prévisualisation apparaîtra ici</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Aide -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-info-circle"></i> Aide
                    </h6>
                </div>
                <div class="card-body">
                    <small class="text-muted">
                        <strong>Workflow :</strong><br>
                        1. Brouillon → En attente<br>
                        2. En attente → Approuvé/Rejeté<br>
                        3. Approuvé → Payé<br><br>

                        <strong>Permissions requises :</strong><br>
                        • Approbation : comptabilite.bons.approve<br>
                        • Modification : comptabilite.bons.edit
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal prévisualisation PDF -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Prévisualisation PDF</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="pdf-preview-container" class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let previewTimeout;

function updatePreview() {
    clearTimeout(previewTimeout);
    previewTimeout = setTimeout(function() {
        const formData = {
            libelle: document.getElementById('libelle').value,
            montant: document.getElementById('montant').value,
            date_depense: document.getElementById('date_depense').value,
            categorie_id: document.getElementById('categorie_id').value,
            fournisseur_id: document.getElementById('fournisseur_id').value,
            mode_paiement: document.getElementById('mode_paiement').value,
            description: document.getElementById('description').value
        };

        // Mise à jour de la prévisualisation
        const container = document.getElementById('preview-container');

        if (formData.libelle && formData.montant && formData.date_depense) {
            const categorieText = document.getElementById('categorie_id').selectedOptions[0]?.text || 'N/A';
            const fournisseurText = document.getElementById('fournisseur_id').selectedOptions[0]?.text || 'N/A';
            const modeText = document.getElementById('mode_paiement').selectedOptions[0]?.text || 'N/A';

            container.innerHTML = `
                <div class="border rounded p-3">
                    <div class="text-center mb-3">
                        <h6 class="text-primary">BON DE SORTIE</h6>
                        <small class="text-muted">N° BON-${new Date().toISOString().slice(0,10).replace(/-/g,'')}-XXXX</small>
                    </div>

                    <table class="table table-sm">
                        <tr>
                            <td><strong>Libellé:</strong></td>
                            <td>${formData.libelle}</td>
                        </tr>
                        <tr>
                            <td><strong>Montant:</strong></td>
                            <td class="text-success">${new Intl.NumberFormat('fr-FR').format(formData.montant)} FCFA</td>
                        </tr>
                        <tr>
                            <td><strong>Date:</strong></td>
                            <td>${new Date(formData.date_depense).toLocaleDateString('fr-FR')}</td>
                        </tr>
                        <tr>
                            <td><strong>Catégorie:</strong></td>
                            <td>${categorieText}</td>
                        </tr>
                        <tr>
                            <td><strong>Fournisseur:</strong></td>
                            <td>${fournisseurText}</td>
                        </tr>
                        <tr>
                            <td><strong>Mode:</strong></td>
                            <td>${modeText}</td>
                        </tr>
                    </table>

                    ${formData.description ? `
                        <div class="mt-2">
                            <strong>Description:</strong><br>
                            <small>${formData.description}</small>
                        </div>
                    ` : ''}

                    <div class="text-center mt-3">
                        <span class="badge bg-secondary">Brouillon</span>
                    </div>
                </div>
            `;
        } else {
            container.innerHTML = `
                <div class="text-center text-muted py-5">
                    <i class="fas fa-file-export fa-3x mb-3"></i>
                    <p>Remplissez les champs requis pour voir la prévisualisation</p>
                </div>
            `;
        }
    }, 500);
}

function previewPDF() {
    const form = document.getElementById('bonSortieForm');
    const formData = new FormData(form);

    // Validation basique
    if (!formData.get('libelle') || !formData.get('montant') || !formData.get('date_depense')) {
        alert('Veuillez remplir tous les champs obligatoires avant la prévisualisation');
        return;
    }

    const modal = new bootstrap.Modal(document.getElementById('previewModal'));
    modal.show();

    // Simuler le chargement du PDF
    setTimeout(function() {
        document.getElementById('pdf-preview-container').innerHTML = `
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                Prévisualisation PDF sera disponible après enregistrement
            </div>
        `;
    }, 1000);
}

// Validation en temps réel
document.getElementById('bonSortieForm').addEventListener('submit', function(e) {
    const libelle = document.getElementById('libelle').value;
    const montant = document.getElementById('montant').value;
    const date = document.getElementById('date_depense').value;
    const categorie = document.getElementById('categorie_id').value;
    const mode = document.getElementById('mode_paiement').value;

    if (!libelle || !montant || !date || !categorie || !mode) {
        e.preventDefault();
        alert('Veuillez remplir tous les champs obligatoires');
        return false;
    }

    if (parseFloat(montant) <= 0) {
        e.preventDefault();
        alert('Le montant doit être supérieur à 0');
        return false;
    }
});

// Initialiser la prévisualisation
document.addEventListener('DOMContentLoaded', function() {
    updatePreview();
});
</script>
@endpush

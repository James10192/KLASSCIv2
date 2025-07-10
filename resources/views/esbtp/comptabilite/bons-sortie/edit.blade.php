@extends('layouts.app')

@section('title', 'Modifier Bon de Sortie')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h3 mb-0">
                    <i class="fas fa-edit text-warning"></i>
                    Modifier Bon de Sortie {{ $bon->numero_bon }}
                </h1>
                <div>
                    <a href="{{ route('esbtp.comptabilite.bons-sortie.show', $bon->id) }}"
                       class="btn btn-secondary me-2">
                        <i class="fas fa-eye"></i> Voir
                    </a>
                    <a href="{{ route('esbtp.comptabilite.bons-sortie.index') }}"
                       class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Retour
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if($bon->statut_workflow !== 'brouillon')
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle"></i>
        <strong>Attention :</strong> Ce bon n'est plus en brouillon et ne peut normalement pas être modifié.
    </div>
    @endif

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
                    <form method="POST" action="{{ route('esbtp.comptabilite.bons-sortie.update', $bon->id) }}"
                          id="bonSortieForm">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label class="form-label">Numéro de bon</label>
                                    <input type="text"
                                           class="form-control"
                                           value="{{ $bon->numero_bon }}"
                                           readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label class="form-label">Statut actuel</label>
                                    @php
                                        $statutClass = match($bon->statut_workflow) {
                                            'brouillon' => 'bg-secondary',
                                            'en_attente' => 'bg-warning',
                                            'approuve' => 'bg-success',
                                            'paye' => 'bg-primary',
                                            'rejete' => 'bg-danger',
                                            default => 'bg-secondary'
                                        };
                                        $statutText = match($bon->statut_workflow) {
                                            'brouillon' => 'Brouillon',
                                            'en_attente' => 'En Attente',
                                            'approuve' => 'Approuvé',
                                            'paye' => 'Payé',
                                            'rejete' => 'Rejeté',
                                            default => 'Inconnu'
                                        };
                                    @endphp
                                    <div>
                                        <span class="badge {{ $statutClass }} fs-6">{{ $statutText }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label class="form-label">Libellé <span class="text-danger">*</span></label>
                                    <input type="text"
                                           name="libelle"
                                           class="form-control @error('libelle') is-invalid @enderror"
                                           value="{{ old('libelle', $bon->libelle) }}"
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
                                           value="{{ old('montant', $bon->montant) }}"
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
                                           value="{{ old('date_depense', $bon->date_depense) }}"
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
                                                {{ old('categorie_id', $bon->categorie_id) == $categorie->id ? 'selected' : '' }}>
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
                                                {{ old('fournisseur_id', $bon->fournisseur_id) == $fournisseur->id ? 'selected' : '' }}>
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
                                        <option value="especes" {{ old('mode_paiement', $bon->mode_paiement) == 'especes' ? 'selected' : '' }}>Espèces</option>
                                        <option value="cheque" {{ old('mode_paiement', $bon->mode_paiement) == 'cheque' ? 'selected' : '' }}>Chèque</option>
                                        <option value="virement" {{ old('mode_paiement', $bon->mode_paiement) == 'virement' ? 'selected' : '' }}>Virement</option>
                                        <option value="carte" {{ old('mode_paiement', $bon->mode_paiement) == 'carte' ? 'selected' : '' }}>Carte bancaire</option>
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
                                      placeholder="Description détaillée de la dépense...">{{ old('description', $bon->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('esbtp.comptabilite.bons-sortie.show', $bon->id) }}"
                               class="btn btn-secondary">
                                <i class="fas fa-times"></i> Annuler
                            </a>
                            <div>
                                <button type="button"
                                        class="btn btn-info me-2"
                                        onclick="previewPDF()">
                                    <i class="fas fa-eye"></i> Prévisualiser
                                </button>
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-save"></i> Enregistrer les modifications
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
                        <!-- La prévisualisation sera générée par JavaScript -->
                    </div>
                </div>
            </div>

            <!-- Historique des modifications -->
            @if($bon->workflow_data)
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-history"></i> Historique
                    </h6>
                </div>
                <div class="card-body">
                    @php $workflow = json_decode($bon->workflow_data, true) ?? []; @endphp
                    @if(is_array($workflow) && count($workflow) > 0)
                    <div class="timeline">
                        @foreach(array_slice($workflow, -3) as $etape)
                        <div class="timeline-item mb-2">
                            <small class="text-muted">
                                <strong>{{ ucfirst($etape['action'] ?? 'Action') }}</strong><br>
                                {{ $etape['utilisateur_nom'] ?? 'Utilisateur' }} -
                                {{ \Carbon\Carbon::parse($etape['date'] ?? now())->format('d/m/Y H:i') }}
                            </small>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <p class="text-muted small">Aucun historique disponible</p>
                    @endif
                </div>
            </div>
            @endif

            <!-- Aide -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-info-circle"></i> Aide
                    </h6>
                </div>
                <div class="card-body">
                    <small class="text-muted">
                        <strong>Modification :</strong><br>
                        Seuls les bons en brouillon peuvent être modifiés librement.<br><br>

                        <strong>Après modification :</strong><br>
                        Le bon conserve son numéro et son historique.
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
                        <small class="text-muted">N° {{ $bon->numero_bon }}</small>
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
                        <span class="badge bg-warning">Modifié</span>
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


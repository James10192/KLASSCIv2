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
                <label for="fournisseur_selection" class="form-label fw-medium">Fournisseur</label>
                <div class="d-flex gap-2">
                    <select class="form-select @error('fournisseur_id') is-invalid @enderror" id="fournisseur_selection" name="fournisseur_id" style="flex: 1;">
                        <option value="">-- Sélectionnez un fournisseur --</option>
                        @foreach($fournisseurs ?? [] as $fournisseur)
                        <option value="{{ $fournisseur->id }}" {{ old('fournisseur_id') == $fournisseur->id ? 'selected' : '' }}>
                            {{ $fournisseur->nom }}
                        </option>
                        @endforeach
                        <option value="nouveau">➕ Nouveau fournisseur</option>
                    </select>
                    <button type="button" class="btn btn-outline-primary" id="btn-nouveau-fournisseur" data-bs-toggle="modal" data-bs-target="#modalNouveauFournisseur">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
                @error('fournisseur_id')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                
                <!-- Champ pour nouveau fournisseur (masqué par défaut) -->
                <div id="nouveau-fournisseur-div" style="display: none;" class="mt-2">
                    <input type="text" class="form-control @error('nouveau_fournisseur') is-invalid @enderror" id="nouveau_fournisseur" name="nouveau_fournisseur" value="{{ old('nouveau_fournisseur') }}" placeholder="Nom du nouveau fournisseur">
                    @error('nouveau_fournisseur')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="form-text text-muted">Saisissez le nom du fournisseur. Il sera créé automatiquement.</small>
                </div>
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

<!-- Modal pour créer un nouveau fournisseur -->
<div class="modal fade" id="modalNouveauFournisseur" tabindex="-1" aria-labelledby="modalNouveauFournisseurLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalNouveauFournisseurLabel">
                    <i class="fas fa-plus-circle me-2"></i>Nouveau fournisseur
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formNouveauFournisseur">
                @csrf
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="modal_nom" class="form-label fw-medium">Nom <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="modal_nom" name="nom" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="modal_email" class="form-label fw-medium">Email</label>
                            <input type="email" class="form-control" id="modal_email" name="email">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="modal_telephone" class="form-label fw-medium">Téléphone</label>
                            <input type="text" class="form-control" id="modal_telephone" name="telephone">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="modal_personne_contact" class="form-label fw-medium">Personne de contact</label>
                            <input type="text" class="form-control" id="modal_personne_contact" name="personne_contact">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="modal_telephone_contact" class="form-label fw-medium">Téléphone contact</label>
                            <input type="text" class="form-control" id="modal_telephone_contact" name="telephone_contact">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="modal_email_contact" class="form-label fw-medium">Email contact</label>
                            <input type="email" class="form-control" id="modal_email_contact" name="email_contact">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-12">
                            <label for="modal_adresse" class="form-label fw-medium">Adresse</label>
                            <textarea class="form-control" id="modal_adresse" name="adresse" rows="3"></textarea>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Annuler
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Créer le fournisseur
                    </button>
                </div>
            </form>
        </div>
    </div>
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

        // Gérer le select fournisseur et l'affichage du champ nouveau fournisseur
        const fournisseurSelect = document.getElementById('fournisseur_selection');
        const nouveauFournisseurDiv = document.getElementById('nouveau-fournisseur-div');
        const nouveauFournisseurInput = document.getElementById('nouveau_fournisseur');

        function toggleNouveauFournisseur() {
            if (fournisseurSelect.value === 'nouveau') {
                nouveauFournisseurDiv.style.display = 'block';
                nouveauFournisseurInput.setAttribute('required', 'required');
                // Réinitialiser la valeur du select pour ne pas envoyer "nouveau"
                fournisseurSelect.name = '';
            } else {
                nouveauFournisseurDiv.style.display = 'none';
                nouveauFournisseurInput.removeAttribute('required');
                nouveauFournisseurInput.value = '';
                fournisseurSelect.name = 'fournisseur_id';
            }
        }

        if (fournisseurSelect) {
            fournisseurSelect.addEventListener('change', toggleNouveauFournisseur);
            // Exécuter au chargement de la page
            toggleNouveauFournisseur();
        }

        // Gérer le formulaire modal de création de fournisseur
        const formModal = document.getElementById('formNouveauFournisseur');
        const modal = document.getElementById('modalNouveauFournisseur');

        if (formModal) {
            formModal.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(formModal);
                const submitBtn = formModal.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                
                // Désactiver le bouton et afficher le loading
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Création...';
                
                // Nettoyer les erreurs précédentes
                const errorElements = formModal.querySelectorAll('.invalid-feedback');
                errorElements.forEach(el => el.textContent = '');
                const invalidInputs = formModal.querySelectorAll('.is-invalid');
                invalidInputs.forEach(el => el.classList.remove('is-invalid'));

                fetch('{{ route("esbtp.comptabilite.fournisseurs.ajax.store") }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Ajouter le nouveau fournisseur au select
                        const newOption = new Option(data.fournisseur.nom, data.fournisseur.id, true, true);
                        fournisseurSelect.appendChild(newOption);
                        
                        // Réinitialiser le nom du select et cacher le champ nouveau fournisseur
                        fournisseurSelect.name = 'fournisseur_id';
                        nouveauFournisseurDiv.style.display = 'none';
                        nouveauFournisseurInput.removeAttribute('required');
                        nouveauFournisseurInput.value = '';
                        
                        // Fermer le modal
                        const bsModal = bootstrap.Modal.getInstance(modal);
                        bsModal.hide();
                        
                        // Réinitialiser le formulaire
                        formModal.reset();
                        
                        // Afficher un message de succès
                        showAlert('success', data.message);
                    } else {
                        // Afficher les erreurs de validation
                        if (data.errors) {
                            Object.keys(data.errors).forEach(field => {
                                const input = formModal.querySelector(`[name="${field}"]`);
                                const feedback = input.nextElementSibling;
                                if (input && feedback) {
                                    input.classList.add('is-invalid');
                                    feedback.textContent = data.errors[field][0];
                                }
                            });
                        }
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    showAlert('danger', 'Une erreur est survenue lors de la création du fournisseur.');
                })
                .finally(() => {
                    // Réactiver le bouton
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                });
            });
        }

        // Fonction pour afficher les alertes
        function showAlert(type, message) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show mt-3`;
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;
            
            const form = document.querySelector('form[action*="depenses.store"]');
            form.parentNode.insertBefore(alertDiv, form);
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                alertDiv.remove();
            }, 5000);
        }
    });
</script>
@endpush 
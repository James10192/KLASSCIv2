@extends('esbtp.comptabilite.components.dashboard-layout')

@section('title', 'Nouveau paiement')

@section('sidebar')
    <li class="navigation-item">
        <a href="{{ route('esbtp.comptabilite.dashboard-avance') }}" class="navigation-link">
            <i class="fas fa-home"></i> Accueil
        </a>
    </li>
    <li class="navigation-item active">
        <a href="{{ route('esbtp.comptabilite.paiements') }}" class="navigation-link active">
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
        <h1><i class="fas fa-credit-card color-primary"></i> Nouveau paiement</h1>
        <p class="header-subtitle">Enregistrer un nouveau paiement d'étudiant</p>
    </div>
    <div class="header-actions">
        <a href="{{ route('esbtp.comptabilite.paiements') }}" class="btn-acasi secondary">
            <i class="fas fa-arrow-left"></i> Retour à la liste
        </a>
    </div>
@endsection

@section('sidebarRight')
    <h3 class="sidebar-title">Actions Rapides</h3>
    <div style="display: flex; flex-direction: column; gap: var(--space-sm);">
        <a href="{{ route('esbtp.comptabilite.paiements') }}" class="btn-acasi secondary">
            <i class="fas fa-list"></i> Liste des paiements
        </a>
    </div>
@endsection

@section('content-block')
    <div class="card-moderne animate-slide-up p-5 rounded-3 shadow" style="max-width: 900px; margin: 0 auto; background: #fff;">
        <h3 class="section-title mb-4 fw-bold"><i class="fas fa-credit-card"></i> Créer un nouveau paiement</h3>
        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        <form action="{{ route('esbtp.comptabilite.paiements.store') }}" method="POST">
            @csrf
            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <label for="etudiant_id" class="form-label fw-medium" style="font-size: 1.08rem;">Étudiant <span class="text-danger">*</span></label>
                    <select class="form-select select2 @error('etudiant_id') is-invalid @enderror" id="etudiant_id" name="etudiant_id" required>
                        <option value="">-- Sélectionnez un étudiant --</option>
                        @foreach($etudiants ?? [] as $etudiant)
                            <option value="{{ $etudiant->id }}" {{ old('etudiant_id', request('etudiant_id')) == $etudiant->id ? 'selected' : '' }}>
                                {{ $etudiant->nom ?? '' }} {{ $etudiant->prenom ?? '' }} {{ !empty($etudiant->matricule) ? '('.$etudiant->matricule.')' : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('etudiant_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label for="annee_universitaire_id" class="form-label fw-medium" style="font-size: 1.08rem;">Année universitaire <span class="text-danger">*</span></label>
                    <select class="form-select @error('annee_universitaire_id') is-invalid @enderror" id="annee_universitaire_id" name="annee_universitaire_id" required>
                        <option value="">-- Sélectionnez une année --</option>
                        @foreach($anneesUniversitaires ?? [] as $annee)
                            <option value="{{ $annee->id }}" {{ old('annee_universitaire_id') == $annee->id ? 'selected' : '' }}>
                                {{ $annee->name ?? $annee->nom }}
                            </option>
                        @endforeach
                    </select>
                    @error('annee_universitaire_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <label for="type_paiement" class="form-label fw-medium" style="font-size: 1.08rem;">Type de paiement <span class="text-danger">*</span></label>
                    <select class="form-select @error('type_paiement') is-invalid @enderror" id="type_paiement" name="type_paiement" required>
                        <option value="">-- Sélectionnez un type --</option>
                        <option value="Frais d'inscription" {{ old('type_paiement') == "Frais d'inscription" ? 'selected' : '' }}>Frais d'inscription</option>
                        <option value="Frais de scolarité" {{ old('type_paiement') == "Frais de scolarité" ? 'selected' : '' }}>Frais de scolarité</option>
                        <option value="Mensualité" {{ old('type_paiement') == "Mensualité" ? 'selected' : '' }}>Mensualité</option>
                        <option value="Trimestriel" {{ old('type_paiement') == "Trimestriel" ? 'selected' : '' }}>Trimestriel</option>
                        <option value="Semestriel" {{ old('type_paiement') == "Semestriel" ? 'selected' : '' }}>Semestriel</option>
                        <option value="Frais d'examen" {{ old('type_paiement') == "Frais d'examen" ? 'selected' : '' }}>Frais d'examen</option>
                        <option value="Frais de diplôme" {{ old('type_paiement') == "Frais de diplôme" ? 'selected' : '' }}>Frais de diplôme</option>
                        <option value="Autre" {{ old('type_paiement') == "Autre" ? 'selected' : '' }}>Autre</option>
                    </select>
                    @error('type_paiement')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label for="montant" class="form-label fw-medium" style="font-size: 1.08rem;">Montant (FCFA) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="number" class="form-control @error('montant') is-invalid @enderror" id="montant" name="montant" value="{{ old('montant') }}" min="0" required placeholder="Montant à payer">
                        <span class="input-group-text">FCFA</span>
                    </div>
                    @error('montant')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <label for="mode_paiement" class="form-label fw-medium" style="font-size: 1.08rem;">Mode de paiement <span class="text-danger">*</span></label>
                    <select class="form-select @error('mode_paiement') is-invalid @enderror" id="mode_paiement" name="mode_paiement" required>
                        <option value="">-- Sélectionnez un mode --</option>
                        @foreach($modesPaiement ?? ['espèces', 'chèque', 'virement', 'mobile money', 'carte bancaire'] as $mode)
                            <option value="{{ $mode }}" {{ old('mode_paiement') == $mode ? 'selected' : '' }}>
                                {{ ucfirst($mode) }}
                            </option>
                        @endforeach
                    </select>
                    @error('mode_paiement')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label for="numero_transaction" class="form-label fw-medium" style="font-size: 1.08rem;">Numéro de transaction</label>
                    <input type="text" class="form-control @error('numero_transaction') is-invalid @enderror" id="numero_transaction" name="numero_transaction" value="{{ old('numero_transaction') }}" placeholder="Numéro de chèque, référence de virement, etc.">
                    @error('numero_transaction')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="form-text text-muted">Numéro de chèque, référence de virement, etc.</small>
                </div>
            </div>
            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <label for="date_paiement" class="form-label fw-medium" style="font-size: 1.08rem;">Date de paiement <span class="text-danger">*</span></label>
                    <input type="date" class="form-control @error('date_paiement') is-invalid @enderror" id="date_paiement" name="date_paiement" value="{{ old('date_paiement', date('Y-m-d')) }}" required>
                    @error('date_paiement')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label for="date_echeance" class="form-label fw-medium" style="font-size: 1.08rem;">Date d'échéance</label>
                    <input type="date" class="form-control @error('date_echeance') is-invalid @enderror" id="date_echeance" name="date_echeance" value="{{ old('date_echeance') }}" placeholder="Date limite de paiement (si applicable)">
                    @error('date_echeance')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="form-text text-muted">Date limite de paiement (si applicable)</small>
                </div>
            </div>
            <div class="row g-4 mb-4">
                <div class="col-md-12">
                    <label for="description" class="form-label fw-medium" style="font-size: 1.08rem;">Description</label>
                    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3" placeholder="Ajouter une note ou un commentaire">{{ old('description') }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="d-flex justify-content-end gap-3 mt-4">
                <button type="submit" class="btn-acasi primary px-4 py-2 fw-bold" style="font-size: 1.08rem;">
                    <i class="fas fa-save me-1"></i> Enregistrer
                </button>
                <a href="{{ route('esbtp.comptabilite.paiements') }}" class="btn-acasi secondary px-4 py-2 fw-bold" style="font-size: 1.08rem;">
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
        // Charger les informations de frais de scolarité lors du changement d'étudiant et d'année
        const etudiantSelect = document.getElementById('etudiant_id');
        const anneeSelect = document.getElementById('annee_universitaire_id');
        const typeSelect = document.getElementById('type_paiement');
        const montantInput = document.getElementById('montant');
        function chargerInformationsFrais() {
            const etudiantId = etudiantSelect.value;
            const anneeId = anneeSelect.value;
            const typePaiement = typeSelect.value;
            if (etudiantId && anneeId && typePaiement) {
                // Ici, vous pourriez ajouter un appel AJAX pour récupérer les informations sur les frais
                // et préremplir automatiquement le montant en fonction du type de paiement
                // Exemple (à adapter selon votre API):
                /*
                fetch(`/api/esbtp/frais-scolarite?etudiant=${etudiantId}&annee=${anneeId}&type=${typePaiement}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.montant) {
                            montantInput.value = data.montant;
                        }
                    })
                    .catch(error => debugError('Erreur lors du chargement des frais:', error));
                */
            }
        }
        if (etudiantSelect && anneeSelect && typeSelect) {
            etudiantSelect.addEventListener('change', chargerInformationsFrais);
            anneeSelect.addEventListener('change', chargerInformationsFrais);
            typeSelect.addEventListener('change', chargerInformationsFrais);
        }
    });
</script>
@endpush

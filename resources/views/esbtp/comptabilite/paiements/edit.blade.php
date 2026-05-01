@extends('esbtp.comptabilite.components.dashboard-layout')

@section('title', 'Modifier le paiement')

@section('sidebar')
    <li class="navigation-item">
        <a href="{{ route('esbtp.comptabilite.dashboard') }}" class="navigation-link">
            <i class="fas fa-home"></i> Accueil
        </a>
    </li>
    <li class="navigation-item active">
        <a href="{{ route('esbtp.comptabilite.paiements') }}" class="navigation-link">
            <i class="fas fa-money-check-alt"></i> Paiements
        </a>
    </li>
    @if(Route::has('esbtp.comptabilite.depenses'))
    <li class="navigation-item">
        <a href="{{ route('esbtp.comptabilite.depenses') }}" class="navigation-link">
            <i class="fas fa-receipt"></i> Dépenses
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
@if(Route::has('esbtp.comptabilite.bons-sortie'))
<li class="navigation-item">
        <a href="{{ route('esbtp.comptabilite.bons-sortie') }}" class="navigation-link">
            <i class="fas fa-truck-loading"></i> Bons de sortie
        </a>
    </li>
@endif
<li class="navigation-item">
        <a href="{{ route('esbtp.comptabilite.rapports') }}" class="navigation-link">
            <i class="fas fa-chart-bar"></i> Rapports
        </a>
    </li>
@endsection

@section('sidebarRight')
    <div class="acasi-sidebar-right-block mb-4">
        <a href="{{ route('esbtp.comptabilite.paiements') }}" class="btn btn-outline-primary w-100 mb-2">
            <i class="fas fa-list"></i> Liste des paiements
        </a>
        <a href="{{ route('esbtp.comptabilite.paiements.create') }}" class="btn btn-primary w-100">
            <i class="fas fa-plus"></i> Nouveau paiement
        </a>
    </div>
@endsection

@section('content-block')
<div class="acasi-card p-5 rounded-3 shadow mb-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0 fw-bold"><i class="fas fa-edit me-2"></i>Modifier le paiement : <span class="text-primary">{{ $paiement->reference_paiement }}</span></h4>
        <a href="{{ route('esbtp.comptabilite.paiements.show', $paiement->id) }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Retour aux détails
            </a>
        </div>
            @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            @endif
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
            <form action="{{ route('esbtp.comptabilite.paiements.update', $paiement->id) }}" method="POST">
                @csrf
                @method('PUT')
        <div class="row g-4 mb-4">
                    <div class="col-md-6">
                <label for="etudiant_id" class="form-label fw-medium">Étudiant <span class="text-danger">*</span></label>
                        <select class="form-select select2 @error('etudiant_id') is-invalid @enderror" id="etudiant_id" name="etudiant_id" required>
                            <option value="">-- Sélectionnez un étudiant --</option>
                            @foreach($etudiants ?? [] as $etudiant)
                            <option value="{{ $etudiant->id }}" {{ old('etudiant_id', $paiement->etudiant_id) == $etudiant->id ? 'selected' : '' }}>
                                {{ $etudiant->nom ?? '' }} {{ $etudiant->prenom ?? '' }} {{ !empty($etudiant->matricule) ? '('.$etudiant->matricule.')' : '' }}
                            </option>
                            @endforeach
                        </select>
                        @error('etudiant_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                <label for="annee_universitaire_id" class="form-label fw-medium">Année universitaire <span class="text-danger">*</span></label>
                        <select class="form-select @error('annee_universitaire_id') is-invalid @enderror" id="annee_universitaire_id" name="annee_universitaire_id" required>
                            <option value="">-- Sélectionnez une année --</option>
                            @foreach($anneesUniversitaires ?? [] as $annee)
                            <option value="{{ $annee->id }}" {{ old('annee_universitaire_id', $paiement->annee_universitaire_id) == $annee->id ? 'selected' : '' }}>
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
                <label for="type_paiement" class="form-label fw-medium">Type de paiement <span class="text-danger">*</span></label>
                        <select class="form-select @error('type_paiement') is-invalid @enderror" id="type_paiement" name="type_paiement" required>
                            <option value="">-- Sélectionnez un type --</option>
                            <option value="Frais d'inscription" {{ old('type_paiement', $paiement->type_paiement) == "Frais d'inscription" ? 'selected' : '' }}>Frais d'inscription</option>
                            <option value="Frais de scolarité" {{ old('type_paiement', $paiement->type_paiement) == "Frais de scolarité" ? 'selected' : '' }}>Frais de scolarité</option>
                            <option value="Mensualité" {{ old('type_paiement', $paiement->type_paiement) == "Mensualité" ? 'selected' : '' }}>Mensualité</option>
                            <option value="Trimestriel" {{ old('type_paiement', $paiement->type_paiement) == "Trimestriel" ? 'selected' : '' }}>Trimestriel</option>
                            <option value="Semestriel" {{ old('type_paiement', $paiement->type_paiement) == "Semestriel" ? 'selected' : '' }}>Semestriel</option>
                            <option value="Frais d'examen" {{ old('type_paiement', $paiement->type_paiement) == "Frais d'examen" ? 'selected' : '' }}>Frais d'examen</option>
                            <option value="Frais de diplôme" {{ old('type_paiement', $paiement->type_paiement) == "Frais de diplôme" ? 'selected' : '' }}>Frais de diplôme</option>
                            <option value="Autre" {{ old('type_paiement', $paiement->type_paiement) == "Autre" ? 'selected' : '' }}>Autre</option>
                        </select>
                        @error('type_paiement')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                <label for="montant" class="form-label fw-medium">Montant (FCFA) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" class="form-control @error('montant') is-invalid @enderror" id="montant" name="montant" value="{{ old('montant', $paiement->montant) }}" min="0" required>
                            <span class="input-group-text">FCFA</span>
                        </div>
                        @error('montant')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
        <div class="row g-4 mb-4">
                    <div class="col-md-6">
                <label for="mode_paiement" class="form-label fw-medium">Mode de paiement <span class="text-danger">*</span></label>
                        <select class="form-select @error('mode_paiement') is-invalid @enderror" id="mode_paiement" name="mode_paiement" required>
                            <option value="">-- Sélectionnez un mode --</option>
                            @foreach($modesPaiement ?? ['espèces', 'chèque', 'virement', 'mobile money', 'carte bancaire'] as $mode)
                            <option value="{{ $mode }}" {{ old('mode_paiement', $paiement->mode_paiement) == $mode ? 'selected' : '' }}>
                                {{ ucfirst($mode) }}
                            </option>
                            @endforeach
                        </select>
                        @error('mode_paiement')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                <label for="numero_transaction" class="form-label fw-medium">Numéro de transaction</label>
                        <input type="text" class="form-control @error('numero_transaction') is-invalid @enderror" id="numero_transaction" name="numero_transaction" value="{{ old('numero_transaction', $paiement->numero_transaction) }}">
                        @error('numero_transaction')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">Numéro de chèque, référence de virement, etc.</small>
                    </div>
                </div>
        <div class="row g-4 mb-4">
                    <div class="col-md-6">
                <label for="date_paiement" class="form-label fw-medium">Date de paiement <span class="text-danger">*</span></label>
                        <input type="date" class="form-control @error('date_paiement') is-invalid @enderror" id="date_paiement" name="date_paiement" value="{{ old('date_paiement', $paiement->date_paiement ? $paiement->date_paiement->format('Y-m-d') : '') }}" required>
                        @error('date_paiement')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                <label for="date_echeance" class="form-label fw-medium">Date d'échéance</label>
                        <input type="date" class="form-control @error('date_echeance') is-invalid @enderror" id="date_echeance" name="date_echeance" value="{{ old('date_echeance', $paiement->date_echeance ? $paiement->date_echeance->format('Y-m-d') : '') }}">
                        @error('date_echeance')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">Date limite de paiement (si applicable)</small>
                    </div>
                </div>
        <div class="row g-4 mb-4">
                    <div class="col-md-12">
                <label for="Commentaire" class="form-label fw-medium">Commentaire</label>
                        <textarea class="form-control @error('commentaire') is-invalid @enderror" id="commentaire" name="commentaire" rows="3">{{ old('commentaire', $paiement->commentaire) }}</textarea>
                        @error('commentaire')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
        <div class="alert alert-info mb-4">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-info-circle fa-2x me-3"></i>
                        <div>
                            <h5 class="alert-heading">Information importante</h5>
                    <p class="mb-0">La référence du paiement est générée automatiquement et ne peut pas être modifiée : <strong>{{ $paiement->reference_paiement }}</strong></p>
                        </div>
                    </div>
                </div>
        <div class="d-flex justify-content-end gap-3">
            <button type="submit" class="btn btn-primary px-4 py-2">
                        <i class="fas fa-save me-1"></i> Enregistrer les modifications
                    </button>
            <a href="{{ route('esbtp.comptabilite.paiements.show', $paiement->id) }}" class="btn btn-outline-secondary px-4 py-2">
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
        // Fonction pour charger les informations de frais
        function chargerInformationsFrais() {
            const etudiantId = etudiantSelect.value;
            const anneeId = anneeSelect.value;
            const typePaiement = typeSelect.value;
            if (etudiantId && anneeId && typePaiement) {
                // Ici, vous pourriez ajouter un appel AJAX pour récupérer les informations sur les frais
                // et préremplir automatiquement le montant en fonction du type de paiement si nécessaire
            }
        }
        // Ajouter des écouteurs d'événements
        if (etudiantSelect && anneeSelect && typeSelect) {
            etudiantSelect.addEventListener('change', chargerInformationsFrais);
            anneeSelect.addEventListener('change', chargerInformationsFrais);
            typeSelect.addEventListener('change', chargerInformationsFrais);
        }
    });
</script>
@endpush

@extends('layouts.app')

@section('title', 'Modifier un Paiement - ESBTP-yAKRO')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header Section -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1><i class="fas fa-edit me-2"></i>Modifier le Paiement</h1>
                <p class="header-subtitle">Modification du paiement #{{ $paiement->numero_recu }}</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('esbtp.paiements.show', $paiement->id) }}" class="btn-acasi secondary">
                    <i class="fas fa-arrow-left"></i>Retour aux détails
                </a>
            </div>
        </div>

        <!-- Contenu principal -->
        <div class="card-moderne" style="background: white; border: 1px solid #e5e7eb;">
            <div class="p-4">
                    @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif

                    <form action="{{ route('esbtp.paiements.update', $paiement->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <!-- Informations de l'étudiant (non modifiables) -->
                        <div class="card mb-4">
                            <div class="card-header bg-primary">
                                <h3 class="card-title">Informations de l'Étudiant</h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Étudiant</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                                </div>
                                                <input type="text" class="form-control" value="{{ $paiement->etudiant->matricule ?? 'N/A' }} - {{ $paiement->etudiant->user->name ?? $paiement->etudiant->nom_complet ?? 'N/A' }}" readonly>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Inscription</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fas fa-graduation-cap"></i></span>
                                                </div>
                                                <input type="text" class="form-control" value="{{ $paiement->inscription->filiere->name }} - {{ $paiement->inscription->niveauEtude->name }} ({{ $paiement->inscription->anneeUniversitaire->libelle }})" readonly>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Informations du paiement (modifiables) -->
                        <div class="card mb-4">
                            <div class="card-header bg-success">
                                <h3 class="card-title">Informations du Paiement</h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="montant">Montant <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fas fa-money-bill"></i></span>
                                                </div>
                                                <input type="number" name="montant" id="montant" class="form-control" min="0" step="1" value="{{ old('montant', $paiement->montant) }}" required>
                                                <div class="input-group-append">
                                                    <span class="input-group-text">FCFA</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="date_paiement">Date de paiement <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                                                </div>
                                                <input type="date" name="date_paiement" id="date_paiement" class="form-control" value="{{ old('date_paiement', $paiement->date_paiement->format('Y-m-d')) }}" required>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="mode_paiement">Mode de paiement <span class="text-danger">*</span></label>
                                            <select name="mode_paiement" id="mode_paiement" class="form-control" required>
                                                <option value="">-- Sélectionner --</option>
                                                <option value="Espèces" {{ old('mode_paiement', $paiement->mode_paiement) == 'Espèces' ? 'selected' : '' }}>Espèces</option>
                                                <option value="Chèque" {{ old('mode_paiement', $paiement->mode_paiement) == 'Chèque' ? 'selected' : '' }}>Chèque</option>
                                                <option value="Virement" {{ old('mode_paiement', $paiement->mode_paiement) == 'Virement' ? 'selected' : '' }}>Virement bancaire</option>
                                                <option value="Mobile Money" {{ old('mode_paiement', $paiement->mode_paiement) == 'Mobile Money' ? 'selected' : '' }}>Mobile Money</option>
                                                <option value="Carte bancaire" {{ old('mode_paiement', $paiement->mode_paiement) == 'Carte bancaire' ? 'selected' : '' }}>Carte bancaire</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="reference_paiement">Référence du paiement</label>
                                            <input type="text" name="reference_paiement" id="reference_paiement" class="form-control" value="{{ old('reference_paiement', $paiement->reference_paiement) }}" placeholder="N° de chèque, transaction, etc.">
                                            <small class="form-text text-muted">Numéro de chèque, référence de transaction, etc.</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="motif">Motif du paiement <span class="text-danger">*</span></label>
                                            <select name="motif" id="motif" class="form-control" required>
                                                <option value="">-- Sélectionner --</option>
                                                <option value="Frais d'inscription" {{ old('motif', $paiement->motif) == "Frais d'inscription" ? 'selected' : '' }}>Frais d'inscription</option>
                                                <option value="Scolarité" {{ old('motif', $paiement->motif) == 'Scolarité' ? 'selected' : '' }}>Scolarité</option>
                                                <option value="Frais d'examen" {{ old('motif', $paiement->motif) == "Frais d'examen" ? 'selected' : '' }}>Frais d'examen</option>
                                                <option value="Frais de diplôme" {{ old('motif', $paiement->motif) == 'Frais de diplôme' ? 'selected' : '' }}>Frais de diplôme</option>
                                                <option value="Frais divers" {{ old('motif', $paiement->motif) == 'Frais divers' ? 'selected' : '' }}>Frais divers</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="tranche">Tranche</label>
                                            <select name="tranche" id="tranche" class="form-control">
                                                <option value="">-- Sélectionner --</option>
                                                <option value="Première tranche" {{ old('tranche', $paiement->tranche) == 'Première tranche' ? 'selected' : '' }}>Première tranche</option>
                                                <option value="Deuxième tranche" {{ old('tranche', $paiement->tranche) == 'Deuxième tranche' ? 'selected' : '' }}>Deuxième tranche</option>
                                                <option value="Troisième tranche" {{ old('tranche', $paiement->tranche) == 'Troisième tranche' ? 'selected' : '' }}>Troisième tranche</option>
                                                <option value="Paiement intégral" {{ old('tranche', $paiement->tranche) == 'Paiement intégral' ? 'selected' : '' }}>Paiement intégral</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="commentaire">Commentaire</label>
                                    <textarea name="commentaire" id="commentaire" class="form-control" rows="3">{{ old('commentaire', $paiement->commentaire) }}</textarea>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group text-center">
                            <button type="submit" class="btn-acasi primary">
                                <i class="fas fa-save"></i>Enregistrer les modifications
                            </button>
                            <a href="{{ route('esbtp.paiements.show', $paiement->id) }}" class="btn-acasi secondary ml-2">
                                <i class="fas fa-times"></i>Annuler
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 
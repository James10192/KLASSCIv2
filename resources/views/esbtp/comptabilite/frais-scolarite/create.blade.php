@extends('layouts.app')

@section('title', 'Nouvelle configuration de frais de scolarité')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Nouvelle configuration de frais de scolarité</h5>
            <a href="{{ route('esbtp.comptabilite.frais-scolarite') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> Retour à la liste
            </a>
        </div>
        <div class="card-body">
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

            <form action="{{ route('esbtp.comptabilite.frais-scolarite.store') }}" method="POST">
                @csrf
                
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="filiere_id" class="form-label">Filière <span class="text-danger">*</span></label>
                        <select class="form-select @error('filiere_id') is-invalid @enderror" id="filiere_id" name="filiere_id" required>
                            <option value="">-- Sélectionnez une filière --</option>
                            @foreach($filieres as $filiere)
                            <option value="{{ $filiere->id }}" {{ old('filiere_id') == $filiere->id ? 'selected' : '' }}>
                                {{ $filiere->name }}
                            </option>
                            @endforeach
                        </select>
                        @error('filiere_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-4">
                        <label for="niveau_id" class="form-label">Niveau d'études <span class="text-danger">*</span></label>
                        <select class="form-select @error('niveau_id') is-invalid @enderror" id="niveau_id" name="niveau_id" required>
                            <option value="">-- Sélectionnez un niveau --</option>
                            @foreach($niveaux as $niveau)
                            <option value="{{ $niveau->id }}" {{ old('niveau_id') == $niveau->id ? 'selected' : '' }}>
                                {{ $niveau->name }}
                            </option>
                            @endforeach
                        </select>
                        @error('niveau_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-4">
                        <label for="annee_universitaire_id" class="form-label">Année universitaire <span class="text-danger">*</span></label>
                        <select class="form-select @error('annee_universitaire_id') is-invalid @enderror" id="annee_universitaire_id" name="annee_universitaire_id" required>
                            <option value="">-- Sélectionnez une année --</option>
                            @foreach($annees as $annee)
                            <option value="{{ $annee->id }}" {{ old('annee_universitaire_id') == $annee->id ? 'selected' : '' }}>
                                {{ $annee->name }}
                            </option>
                            @endforeach
                        </select>
                        @error('annee_universitaire_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="montant_total" class="form-label">Montant total (FCFA) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" class="form-control @error('montant_total') is-invalid @enderror" id="montant_total" name="montant_total" value="{{ old('montant_total') }}" required min="0">
                            <span class="input-group-text">FCFA</span>
                        </div>
                        @error('montant_total')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">Montant total des frais de scolarité pour l'année universitaire complète</small>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="frais_inscription" class="form-label">Frais d'inscription (FCFA) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" class="form-control @error('frais_inscription') is-invalid @enderror" id="frais_inscription" name="frais_inscription" value="{{ old('frais_inscription') }}" required min="0">
                            <span class="input-group-text">FCFA</span>
                        </div>
                        @error('frais_inscription')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">Montant des frais d'inscription (inclus dans le montant total)</small>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label for="frais_mensuel" class="form-label">Frais mensuel (FCFA)</label>
                        <div class="input-group">
                            <input type="number" class="form-control @error('frais_mensuel') is-invalid @enderror" id="frais_mensuel" name="frais_mensuel" value="{{ old('frais_mensuel') }}" min="0">
                            <span class="input-group-text">FCFA</span>
                        </div>
                        @error('frais_mensuel')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-3">
                        <label for="frais_trimestriel" class="form-label">Frais trimestriel (FCFA)</label>
                        <div class="input-group">
                            <input type="number" class="form-control @error('frais_trimestriel') is-invalid @enderror" id="frais_trimestriel" name="frais_trimestriel" value="{{ old('frais_trimestriel') }}" min="0">
                            <span class="input-group-text">FCFA</span>
                        </div>
                        @error('frais_trimestriel')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-3">
                        <label for="frais_semestriel" class="form-label">Frais semestriel (FCFA)</label>
                        <div class="input-group">
                            <input type="number" class="form-control @error('frais_semestriel') is-invalid @enderror" id="frais_semestriel" name="frais_semestriel" value="{{ old('frais_semestriel') }}" min="0">
                            <span class="input-group-text">FCFA</span>
                        </div>
                        @error('frais_semestriel')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-3">
                        <label for="frais_annuel" class="form-label">Frais annuel (FCFA)</label>
                        <div class="input-group">
                            <input type="number" class="form-control @error('frais_annuel') is-invalid @enderror" id="frais_annuel" name="frais_annuel" value="{{ old('frais_annuel') }}" min="0">
                            <span class="input-group-text">FCFA</span>
                        </div>
                        @error('frais_annuel')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="nombre_echeances" class="form-label">Nombre d'échéances <span class="text-danger">*</span></label>
                        <input type="number" class="form-control @error('nombre_echeances') is-invalid @enderror" id="nombre_echeances" name="nombre_echeances" value="{{ old('nombre_echeances', 1) }}" required min="1" max="12">
                        @error('nombre_echeances')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">Nombre de versements pour le paiement du montant total (après les frais d'inscription)</small>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label for="details" class="form-label">Détails supplémentaires</label>
                        <textarea class="form-control @error('details') is-invalid @enderror" id="details" name="details" rows="3">{{ old('details') }}</textarea>
                        @error('details')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">Informations supplémentaires sur cette configuration de frais (facultatif)</small>
                    </div>
                </div>
                
                <div class="text-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Enregistrer
                    </button>
                    <a href="{{ route('esbtp.comptabilite.frais-scolarite') }}" class="btn btn-secondary">
                        <i class="fas fa-times me-1"></i> Annuler
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Calcul automatique des frais par échéance
        const montantTotalInput = document.getElementById('montant_total');
        const fraisInscriptionInput = document.getElementById('frais_inscription');
        const nombreEcheancesInput = document.getElementById('nombre_echeances');
        const fraisMensuelInput = document.getElementById('frais_mensuel');
        
        function calculerFraisMensuel() {
            const montantTotal = parseFloat(montantTotalInput.value) || 0;
            const fraisInscription = parseFloat(fraisInscriptionInput.value) || 0;
            const nombreEcheances = parseInt(nombreEcheancesInput.value) || 1;
            
            if (montantTotal > 0 && fraisInscription >= 0 && nombreEcheances > 0) {
                const fraisRestants = montantTotal - fraisInscription;
                const fraisMensuel = fraisRestants / nombreEcheances;
                
                // Arrondir à l'entier le plus proche
                fraisMensuelInput.value = Math.round(fraisMensuel);
            }
        }
        
        montantTotalInput.addEventListener('input', calculerFraisMensuel);
        fraisInscriptionInput.addEventListener('input', calculerFraisMensuel);
        nombreEcheancesInput.addEventListener('input', calculerFraisMensuel);
        
        // Calculer au chargement de la page si les valeurs sont déjà définies
        if (montantTotalInput.value && fraisInscriptionInput.value && nombreEcheancesInput.value) {
            calculerFraisMensuel();
        }
    });
</script>
@endpush 
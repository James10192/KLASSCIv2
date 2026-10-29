@extends('layouts.app')

@section('title', 'Modifier la bourse')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Modifier la bourse</h5>
            <a href="{{ route('esbtp.comptabilite.bourses') }}" class="btn btn-secondary">
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

            <form action="{{ route('esbtp.comptabilite.bourses.update', $bourse->id) }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="etudiant_id" class="form-label">Étudiant <span class="text-danger">*</span></label>
                        <select class="form-select select2 @error('etudiant_id') is-invalid @enderror" id="etudiant_id" name="etudiant_id" required>
                            <option value="">-- Sélectionnez un étudiant --</option>
                            @foreach($etudiants ?? [] as $etudiant)
                            <option value="{{ $etudiant->id }}" {{ old('etudiant_id', $bourse->etudiant_id) == $etudiant->id ? 'selected' : '' }}>
                                {{ $etudiant->nom_complet ?? $etudiant->user->name }} ({{ $etudiant->matricule ?? 'Sans matricule' }})
                            </option>
                            @endforeach
                        </select>
                        @error('etudiant_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-6">
                        <label for="annee_universitaire_id" class="form-label">Année universitaire <span class="text-danger">*</span></label>
                        <select class="form-select @error('annee_universitaire_id') is-invalid @enderror" id="annee_universitaire_id" name="annee_universitaire_id" required>
                            <option value="">-- Sélectionnez une année --</option>
                            @foreach($annees ?? [] as $annee)
                            <option value="{{ $annee->id }}" {{ old('annee_universitaire_id', $bourse->annee_universitaire_id) == $annee->id ? 'selected' : '' }}>
                                {{ $annee->nom }}
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
                        <label for="type_bourse" class="form-label">Type de bourse <span class="text-danger">*</span></label>
                        <select class="form-select @error('type_bourse') is-invalid @enderror" id="type_bourse" name="type_bourse" required>
                            <option value="">-- Sélectionnez un type --</option>
                            <option value="mérite" {{ old('type_bourse', $bourse->type_bourse) == 'mérite' ? 'selected' : '' }}>Bourse au mérite</option>
                            <option value="sociale" {{ old('type_bourse', $bourse->type_bourse) == 'sociale' ? 'selected' : '' }}>Bourse sociale</option>
                            <option value="excellence" {{ old('type_bourse', $bourse->type_bourse) == 'excellence' ? 'selected' : '' }}>Bourse d'excellence</option>
                            <option value="partielle" {{ old('type_bourse', $bourse->type_bourse) == 'partielle' ? 'selected' : '' }}>Bourse partielle</option>
                            <option value="complète" {{ old('type_bourse', $bourse->type_bourse) == 'complète' ? 'selected' : '' }}>Bourse complète</option>
                            <option value="autre" {{ old('type_bourse', $bourse->type_bourse) == 'autre' ? 'selected' : '' }}>Autre</option>
                        </select>
                        @error('type_bourse')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-6">
                        <label for="organisme_financeur" class="form-label">Organisme financeur</label>
                        <input type="text" class="form-control @error('organisme_financeur') is-invalid @enderror" id="organisme_financeur" name="organisme_financeur" value="{{ old('organisme_financeur', $bourse->organisme_financeur) }}">
                        @error('organisme_financeur')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">L'entité qui finance la bourse (école, gouvernement, entreprise, etc.)</small>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div id="montant_group">
                            <label for="montant" class="form-label">Montant (FCFA)</label>
                            <div class="input-group">
                                <input type="number" class="form-control @error('montant') is-invalid @enderror" id="montant" name="montant" value="{{ old('montant', $bourse->montant) }}" min="0">
                                <span class="input-group-text">FCFA</span>
                            </div>
                            @error('montant')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Montant fixe de la bourse (laissez vide si vous utilisez un pourcentage)</small>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div id="pourcentage_group">
                            <label for="pourcentage" class="form-label">Pourcentage (%)</label>
                            <div class="input-group">
                                <input type="number" class="form-control @error('pourcentage') is-invalid @enderror" id="pourcentage" name="pourcentage" value="{{ old('pourcentage', $bourse->pourcentage) }}" min="0" max="100">
                                <span class="input-group-text">%</span>
                            </div>
                            @error('pourcentage')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Pourcentage des frais de scolarité (laissez vide si vous utilisez un montant fixe)</small>
                        </div>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="date_debut" class="form-label">Date de début <span class="text-danger">*</span></label>
                        <input type="date" class="form-control @error('date_debut') is-invalid @enderror" id="date_debut" name="date_debut" value="{{ old('date_debut', $bourse->date_debut ? $bourse->date_debut->format('Y-m-d') : '') }}" required>
                        @error('date_debut')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-6">
                        <label for="date_fin" class="form-label">Date de fin</label>
                        <input type="date" class="form-control @error('date_fin') is-invalid @enderror" id="date_fin" name="date_fin" value="{{ old('date_fin', $bourse->date_fin ? $bourse->date_fin->format('Y-m-d') : '') }}">
                        @error('date_fin')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">Laissez vide pour une bourse sans date de fin définie</small>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="statut" class="form-label">Statut <span class="text-danger">*</span></label>
                        <select class="form-select @error('statut') is-invalid @enderror" id="statut" name="statut" required>
                            <option value="active" {{ old('statut', $bourse->statut) == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="suspendue" {{ old('statut', $bourse->statut) == 'suspendue' ? 'selected' : '' }}>Suspendue</option>
                            <option value="terminée" {{ old('statut', $bourse->statut) == 'terminée' ? 'selected' : '' }}>Terminée</option>
                        </select>
                        @error('statut')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label for="conditions" class="form-label">Conditions d'attribution</label>
                        <textarea class="form-control @error('conditions') is-invalid @enderror" id="conditions" name="conditions" rows="3">{{ old('conditions', $bourse->conditions) }}</textarea>
                        @error('conditions')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">Conditions que l'étudiant doit remplir pour maintenir sa bourse</small>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label for="commentaires" class="form-label">Commentaires</label>
                        <textarea class="form-control @error('commentaires') is-invalid @enderror" id="commentaires" name="commentaires" rows="3">{{ old('commentaires', $bourse->commentaires) }}</textarea>
                        @error('commentaires')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="text-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Enregistrer les modifications
                    </button>
                    <a href="{{ route('esbtp.comptabilite.bourses') }}" class="btn btn-secondary">
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
        // Initialisation de Select2 si disponible
        if (typeof $.fn.select2 !== 'undefined') {
            $('.select2').select2({
                placeholder: 'Sélectionnez une option',
                allowClear: true
            });
        }
        
        // Gestion de l'affichage des champs montant et pourcentage
        const montantInput = document.getElementById('montant');
        const pourcentageInput = document.getElementById('pourcentage');
        
        montantInput.addEventListener('input', function() {
            if (this.value) {
                pourcentageInput.value = '';
            }
        });
        
        pourcentageInput.addEventListener('input', function() {
            if (this.value) {
                montantInput.value = '';
            }
        });
    });
</script>
@endpush 
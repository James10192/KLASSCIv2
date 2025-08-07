@extends('layouts.app')

@section('title', 'Créer un emploi du temps - ESBTP-yAKRO')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

<style>
    /* Animation pour la flèche */
    @keyframes bounce {
        0%, 20%, 50%, 80%, 100% {
            transform: translateY(0);
        }
        40% {
            transform: translateY(-10px);
        }
        60% {
            transform: translateY(-5px);
        }
    }
    
    /* Amélioration des gradients */
    .bg-gradient-primary {
        background: linear-gradient(45deg, #007bff, #0056b3) !important;
    }
    
    .bg-gradient-success {
        background: linear-gradient(45deg, #28a745, #1e7e34) !important;
    }
    
    /* Amélioration des cartes */
    .card.border-primary {
        box-shadow: 0 0 20px rgba(0, 123, 255, 0.15) !important;
    }
    
    /* Amélioration des badges */
    .badge.fs-6 {
        font-size: 0.875rem !important;
        padding: 0.5rem 1rem !important;
    }
    
    /* Style des progress bars */
    .progress {
        border-radius: 10px;
        background-color: #e9ecef;
    }
    
    .progress-bar {
        border-radius: 10px;
        transition: width 0.6s ease;
    }
    
    /* Amélioration des alertes */
    .alert {
        border-radius: 15px;
    }
    
    /* Style pour les icônes circulaires */
    .rounded-circle {
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }
    
    /* Amélioration des tableaux */
    .table-hover tbody tr:hover {
        background-color: rgba(0, 123, 255, 0.05);
        transform: translateX(2px);
        transition: all 0.2s ease;
    }
    
    /* Style pour les cards avec shadow */
    .shadow-lg {
        box-shadow: 0 1rem 3rem rgba(0, 0, 0, 0.175) !important;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Créer un nouvel emploi du temps</h5>
                    <a href="{{ route('esbtp.emploi-temps.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Retour à la liste
                    </a>
                </div>
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('esbtp.emploi-temps.store') }}" method="POST">
                        @csrf

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="titre" class="form-label">Titre de l'emploi du temps *</label>
                                    <input type="text" class="form-control @error('titre') is-invalid @enderror" id="titre" name="titre" value="{{ old('titre') }}" required>
                                    @error('titre')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">Ex: Emploi du temps BTS 1ère année Génie Civil - Semestre 1</small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="classe_id" class="form-label">Classe *</label>
                                    <select class="form-select @error('classe_id') is-invalid @enderror" id="classe_id" name="classe_id" required>
                                        <option value="">Sélectionner une classe</option>
                                        @foreach($classes as $classe)
                                            <option value="{{ $classe->id }}" {{ old('classe_id') == $classe->id ? 'selected' : '' }}>
                                                {{ $classe->name }} ({{ $classe->filiere->name }} - {{ $classe->niveau->name }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('classe_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="annee_universitaire_id" class="form-label">Année universitaire *</label>
                                    <select class="form-select @error('annee_universitaire_id') is-invalid @enderror" id="annee_universitaire_id" name="annee_universitaire_id" required>
                                        <option value="">Sélectionner une année universitaire</option>
                                        @foreach($annees as $annee)
                                            <option value="{{ $annee->id }}" {{ 
                                                old('annee_universitaire_id') == $annee->id || 
                                                (empty(old('annee_universitaire_id')) && isset($anneeActive) && $anneeActive->id == $annee->id) ||
                                                request('annee_universitaire_id') == $annee->id
                                                ? 'selected' : '' }}>
                                                {{ $annee->name }}
                                                @if($anneeActive && $anneeActive->id == $annee->id)
                                                    (Année en cours)
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('annee_universitaire_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="semestre" class="form-label">Période *</label>
                                    <select class="form-select @error('semestre') is-invalid @enderror" id="semestre" name="semestre" required>
                                        <option value="">Sélectionner une période</option>
                                        <option value="Semestre 1">Semestre 1</option>
                                        <option value="Semestre 2">Semestre 2</option>
                                        <option value="Année complète">Année complète</option>
                                    </select>
                                    @error('semestre')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info">
                            <h6 class="alert-heading"><i class="fas fa-info-circle me-2"></i>Information</h6>
                            <p class="mb-0">Après avoir créé l'emploi du temps, vous pourrez consulter la planification académique et ajouter des séances de cours basées sur cette planification.</p>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="date_debut" class="form-label">Date de début *</label>
                                    <div class="input-group">
                                        <input type="date" class="form-control @error('date_debut') is-invalid @enderror" id="date_debut" name="date_debut" value="{{ old('date_debut', $semaineCourante['date_debut'] ?? '') }}" required>
                                        <button type="button" class="btn btn-outline-secondary" id="btn-semaine-courante">
                                            <i class="fas fa-calendar-week"></i> Semaine courante
                                        </button>
                                    </div>
                                    @error('date_debut')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="date_fin" class="form-label">Date de fin *</label>
                                    <input type="date" class="form-control @error('date_fin') is-invalid @enderror" id="date_fin" name="date_fin" value="{{ old('date_fin', $semaineCourante['date_fin'] ?? '') }}" required>
                                    @error('date_fin')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">
                                        <i class="fas fa-info-circle"></i> La période doit être de 6 jours maximum (du lundi au samedi).
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', '1') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">
                                Emploi du temps actif
                            </label>
                            <div class="form-text">
                                <span class="text-info"><i class="fas fa-info-circle me-1"></i>Info :</span>
                                Un seul emploi du temps peut être actif par classe à la fois. Si vous activez cet emploi du temps, les autres emplois du temps pour la même classe seront automatiquement désactivés et celui-ci sera défini comme l'emploi du temps courant.
                            </div>
                        </div>

                        <div class="alert alert-warning">
                            <h6 class="alert-heading"><i class="fas fa-exclamation-triangle me-2"></i>Remarque importante</h6>
                            <p class="mb-0">Après avoir créé l'emploi du temps, vous pourrez y ajouter des séances de cours. Assurez-vous que la classe sélectionnée a des matières et des enseignants assignés avant de créer l'emploi du temps.</p>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="reset" class="btn btn-secondary me-2">Annuler</button>
                            <button type="submit" class="btn btn-primary">Enregistrer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Amélioration des listes déroulantes avec Select2
    $('#classe_id, #annee_universitaire_id, #semestre').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'Sélectionnez un élément'
    });

    // Bouton semaine courante
    document.getElementById('btn-semaine-courante').addEventListener('click', function() {
        document.getElementById('date_debut').value = '{{ $semaineCourante['date_debut'] }}';
        document.getElementById('date_fin').value = '{{ $semaineCourante['date_fin'] }}';
    });

    // Validation période maximum 6 jours (lundi-samedi)
    document.getElementById('date_fin').addEventListener('change', function() {
        const dateDebut = new Date(document.getElementById('date_debut').value);
        const dateFin = new Date(this.value);

        if (dateDebut && dateFin) {
            const diffTime = Math.abs(dateFin - dateDebut);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

            if (diffDays > 5) {
                alert('La période de l\'emploi du temps ne doit pas dépasser 6 jours (du lundi au samedi).');
                this.value = '';
            }
        }
    });
});
</script>
@endsection

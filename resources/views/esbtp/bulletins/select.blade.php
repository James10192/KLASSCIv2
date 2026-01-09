@extends('layouts.app')

@section('title', 'Sélection des bulletins - KLASSCI')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Sélection des bulletins</h5>
                    <a href="{{ route('esbtp.bulletins.index') }}" class="btn btn-secondary">
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

                    <div class="row">
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="card-title mb-0">Consulter des bulletins</h5>
                                </div>
                                <div class="card-body">
                                    <form action="{{ route('esbtp.resultats.index') }}" method="GET">
                                        <div class="mb-3">
                                            <label for="annee_universitaire_id" class="form-label">Année Universitaire</label>
                                            <select class="form-select" id="annee_universitaire_id" name="annee_universitaire_id" required>
                                                <option value="">Sélectionnez une année universitaire</option>
                                                @foreach($anneesUniversitaires as $annee)
                                                    <option value="{{ $annee->id }}" {{ $anneeActuelle && $annee->id == $anneeActuelle->id ? 'selected' : '' }}>
                                                        {{ $annee->annee_debut }} - {{ $annee->annee_fin }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="classe_id" class="form-label">Classe</label>
                                            <select class="form-select" id="classe_id" name="classe_id" required>
                                                <option value="">Sélectionnez une classe</option>
                                                @foreach($classes as $classe)
                                                    <option value="{{ $classe->id }}">
                                                        {{ $classe->name }} ({{ $classe->filiere->name ?? 'N/A' }} - {{ $classe->niveau->name ?? 'N/A' }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="periode" class="form-label">Période</label>
                                            <select class="form-select" id="periode" name="periode" required>
                                                <option value="">Sélectionnez une période</option>
                                                <option value="semestre1">Semestre 1</option>
                                                <option value="semestre2">Semestre 2</option>
                                                <option value="annuel">Annuel</option>
                                            </select>
                                        </div>
                                        <div class="d-grid">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-search me-1"></i>Consulter les bulletins
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header bg-info text-white">
                                    <h5 class="card-title mb-0">Prévisualiser un bulletin</h5>
                                </div>
                                <div class="card-body">
                                    <form action="{{ route('esbtp.bulletins.preview') }}" method="GET">
                                        <div class="mb-3">
                                            <label for="preview_annee" class="form-label">Année Universitaire</label>
                                            <select class="form-select" id="preview_annee" name="annee" required>
                                                <option value="">Sélectionnez une année universitaire</option>
                                                @foreach($anneesUniversitaires as $annee)
                                                    <option value="{{ $annee->id }}" {{ $anneeActuelle && $annee->id == $anneeActuelle->id ? 'selected' : '' }}>
                                                        {{ $annee->annee_debut }} - {{ $annee->annee_fin }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="preview_classe" class="form-label">Classe</label>
                                            <select class="form-select" id="preview_classe" name="classe" required>
                                                <option value="">Sélectionnez une classe</option>
                                                @foreach($classes as $classe)
                                                    <option value="{{ $classe->id }}">
                                                        {{ $classe->name }} ({{ $classe->filiere->name ?? 'N/A' }} - {{ $classe->niveau->name ?? 'N/A' }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="preview_etudiant" class="form-label">Étudiant</label>
                                            <select class="form-select" id="preview_etudiant" name="etudiant" required>
                                                <option value="">Sélectionnez d'abord une classe</option>
                                            </select>
                                        </div>
                                        <div class="d-grid">
                                            <button type="submit" class="btn btn-info">
                                                <i class="fas fa-eye me-1"></i>Prévisualiser le bulletin
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header bg-success text-white">
                                    <h5 class="card-title mb-0">Générer des bulletins</h5>
                                </div>
                                <div class="card-body">
                                    <form action="{{ route('esbtp.bulletins.generer-classe') }}" method="POST">
                                        @csrf
                                        <div class="mb-3">
                                            <label for="gen_annee_universitaire_id" class="form-label">Année Universitaire</label>
                                            <select class="form-select" id="gen_annee_universitaire_id" name="annee_universitaire_id" required>
                                                <option value="">Sélectionnez une année universitaire</option>
                                                @foreach($anneesUniversitaires as $annee)
                                                    <option value="{{ $annee->id }}" {{ $anneeActuelle && $annee->id == $anneeActuelle->id ? 'selected' : '' }}>
                                                        {{ $annee->annee_debut }} - {{ $annee->annee_fin }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="gen_classe_id" class="form-label">Classe</label>
                                            <select class="form-select" id="gen_classe_id" name="classe_id" required>
                                                <option value="">Sélectionnez une classe</option>
                                                @foreach($classes as $classe)
                                                    <option value="{{ $classe->id }}">
                                                        {{ $classe->name }} ({{ $classe->filiere->name ?? 'N/A' }} - {{ $classe->niveau->name ?? 'N/A' }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="gen_periode" class="form-label">Période</label>
                                            <select class="form-select" id="gen_periode" name="periode" required>
                                                <option value="">Sélectionnez une période</option>
                                                <option value="semestre1">Semestre 1</option>
                                                <option value="semestre2">Semestre 2</option>
                                                <option value="annuel">Annuel</option>
                                            </select>
                                        </div>
                                        <div class="mb-3 form-check">
                                            <input type="checkbox" class="form-check-input" id="recalculer" name="recalculer" value="1">
                                            <label class="form-check-label" for="recalculer">Recalculer les moyennes</label>
                                            <small class="form-text text-muted d-block">Cochez cette case pour recalculer les bulletins déjà générés.</small>
                                        </div>
                                        <div class="d-grid">
                                            <button type="submit" class="btn btn-success">
                                                <i class="fas fa-file-pdf me-1"></i>Générer les bulletins
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
    $(document).ready(function() {
        $('.form-select').select2({
            theme: 'bootstrap-5'
        });

        // Charger les étudiants quand une classe est sélectionnée pour la prévisualisation
        $('#preview_classe').change(function() {
            const classeId = $(this).val();
            const etudiantSelect = $('#preview_etudiant');
            
            etudiantSelect.empty().append('<option value="">Chargement...</option>');
            
            if (classeId) {
                $.ajax({
                    url: '{{ route("esbtp.classes.etudiants", ":id") }}'.replace(':id', classeId),
                    type: 'GET',
                    success: function(response) {
                        etudiantSelect.empty().append('<option value="">Sélectionnez un étudiant</option>');
                        
                        if (response.etudiants && response.etudiants.length > 0) {
                            response.etudiants.forEach(function(etudiant) {
                                etudiantSelect.append(`<option value="${etudiant.id}">${etudiant.nom} ${etudiant.prenom} (${etudiant.matricule})</option>`);
                            });
                        } else {
                            etudiantSelect.append('<option value="">Aucun étudiant trouvé</option>');
                        }
                    },
                    error: function() {
                        etudiantSelect.empty().append('<option value="">Erreur lors du chargement</option>');
                    }
                });
            } else {
                etudiantSelect.empty().append('<option value="">Sélectionnez d\'abord une classe</option>');
            }
        });
    });
</script>
@endsection

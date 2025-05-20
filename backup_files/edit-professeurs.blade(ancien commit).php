@extends('layouts.app')

@section('title', 'Édition des professeurs pour le bulletin')

@section('styles')
<style>
    .container-edit {
        padding: 20px;
    }
    .matiere-box {
        border: 1px solid #ccc;
        border-radius: 5px;
        padding: 15px;
        margin-bottom: 15px;
    }
    .info-header {
        background-color: #f8f9fa;
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 20px;
        border-left: 4px solid #0d6efd;
    }
    .section-title {
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 1px solid #eee;
        font-weight: bold;
    }
    .action-buttons {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .btn-group-options {
        display: flex;
        gap: 10px;
    }
    .alert-guide {
        background-color: #f8f9fa;
        border-left: 4px solid #ffc107;
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 4px;
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <!-- Section de débogage -->
    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0">Informations de débogage</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6>Paramètres de la requête:</h6>
                    <ul class="list-unstyled">
                        <li><strong>Étudiant ID:</strong> {{ $etudiant->id ?? 'Non défini' }}</li>
                        <li><strong>Classe ID:</strong> {{ $classe->id ?? 'Non défini' }}</li>
                        <li><strong>Période:</strong> {{ $periode ?? 'Non défini' }}</li>
                        <li><strong>Année Universitaire ID:</strong> {{ $anneeUniversitaire->id ?? 'Non défini' }}</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h6>Statistiques:</h6>
                    <ul class="list-unstyled">
                        <li><strong>Nombre de résultats généraux:</strong> {{ $resultatsGeneraux->count() ?? 0 }}</li>
                        <li><strong>Nombre de résultats techniques:</strong> {{ $resultatsTechniques->count() ?? 0 }}</li>
                        <li><strong>Professeurs configurés:</strong> {{ !empty($professeurs) ? count($professeurs) : 0 }}</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="container-edit">
        <div class="row mb-4">
            <div class="col-md-12">
                <h2>Édition des professeurs pour le bulletin</h2>

                <div class="info-header">
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Étudiant:</strong> {{ $etudiant->nom }} {{ $etudiant->prenom }}</p>
                            <p class="mb-1"><strong>Classe:</strong> {{ $classe->libelle ?? $classe->name ?? 'N/A' }}</p>
                            <p class="mb-1"><strong>Filière:</strong> {{ $classe->filiere->nom ?? $classe->filiere->name ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Période:</strong>
                                @if($periode == 'semestre1')
                                    Premier Semestre
                                @elseif($periode == 'semestre2')
                                    Deuxième Semestre
                                @else
                                    Annuel
                                @endif
                            </p>
                            <p class="mb-1"><strong>Année:</strong> {{ $anneeUniversitaire->libelle ?? $anneeUniversitaire->name ?? 'N/A' }}</p>
                        </div>
                    </div>
                </div>

                <div class="alert-guide">
                    <h5><i class="fas fa-lightbulb me-2 text-warning"></i>Guide d'utilisation</h5>
                    <p>Sur cette page, vous pouvez saisir le nom des enseignants pour chaque matière qui apparaîtront sur le bulletin.</p>
                    <ul>
                        <li>Remplissez les champs pour les matières dont vous connaissez l'enseignant</li>
                        <li>Les champs vides n'afficheront pas de nom d'enseignant sur le bulletin</li>
                        <li>Utilisez les boutons ci-dessous pour enregistrer ou générer le bulletin</li>
                    </ul>
                </div>
            </div>
        </div>

        <form id="professeursForm" action="{{ route('esbtp.bulletins.save-professeurs') }}" method="POST">
            @csrf
            <input type="hidden" name="etudiant_id" value="{{ $etudiant->id }}">
            <input type="hidden" name="classe_id" value="{{ $classe->id }}">
            <input type="hidden" name="periode" value="{{ $periode }}">
            <input type="hidden" name="annee_universitaire_id" value="{{ $anneeUniversitaire->id }}">

            @if(isset($resultatsGeneraux) && $resultatsGeneraux->count() > 0)
            <div class="matiere-box">
                <h4 class="section-title">Matières d'enseignement général</h4>

                <div class="row">
                    @foreach($resultatsGeneraux as $resultat)
                    <div class="col-md-6 mb-3">
                        <div class="form-group">
                            <label for="professeur_{{ $resultat->matiere_id }}">
                                <strong>{{ $resultat->matiere->name ?? $resultat->matiere->nom ?? 'Matière #'.$resultat->matiere_id }}</strong>
                            </label>
                            <input type="text"
                                   class="form-control"
                                   id="professeur_{{ $resultat->matiere_id }}"
                                   name="professeurs[{{ $resultat->matiere_id }}]"
                                   value="{{ $professeurs[$resultat->matiere_id] ?? '' }}"
                                   placeholder="Nom du professeur">
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            @if(isset($resultatsTechniques) && $resultatsTechniques->count() > 0)
            <div class="matiere-box">
                <h4 class="section-title">Matières d'enseignement technique</h4>

                <div class="row">
                    @foreach($resultatsTechniques as $resultat)
                    <div class="col-md-6 mb-3">
                        <div class="form-group">
                            <label for="professeur_{{ $resultat->matiere_id }}">
                                <strong>{{ $resultat->matiere->name ?? $resultat->matiere->nom ?? 'Matière #'.$resultat->matiere_id }}</strong>
                            </label>
                            <input type="text"
                                   class="form-control"
                                   id="professeur_{{ $resultat->matiere_id }}"
                                   name="professeurs[{{ $resultat->matiere_id }}]"
                                   value="{{ $professeurs[$resultat->matiere_id] ?? '' }}"
                                   placeholder="Nom du professeur">
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            @if((!isset($resultatsGeneraux) || $resultatsGeneraux->isEmpty()) && (!isset($resultatsTechniques) || $resultatsTechniques->isEmpty()))
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                Aucune matière n'a été configurée pour cet étudiant. Veuillez d'abord configurer les matières.
            </div>
            <div class="row mb-3">
                <div class="col-md-12">
                    <a href="{{ route('esbtp.bulletins.config-matieres') }}?classe_id={{ $classe->id }}&periode={{ $periode }}&annee_universitaire_id={{ $anneeUniversitaire->id }}&bulletin={{ $etudiant->id }}" class="btn btn-info">
                        <i class="fas fa-cogs me-1"></i> Configurer les matières
                    </a>
                </div>
            </div>
            @endif

            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="action-buttons">
                        <a href="{{ route('esbtp.resultats.etudiant', [
                            'etudiant' => $etudiant->id,
                            'classe_id' => $classe->id,
                            'periode' => $periode,
                            'annee_universitaire_id' => $anneeUniversitaire->id
                        ]) }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Retour aux résultats
                        </a>

                        <div class="btn-group-options">
                            <button type="submit" class="btn btn-success" name="action" value="edit">
                                <i class="fas fa-save me-1"></i> Enregistrer et continuer
                            </button>

                            <button type="submit" class="btn btn-primary" name="action" value="generate">
                                <i class="fas fa-file-pdf me-1"></i> Générer le bulletin
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<!-- JavaScript removed to prevent any potential interference with form submission -->
@endsection

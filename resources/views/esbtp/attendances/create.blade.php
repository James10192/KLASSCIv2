@extends('layouts.app')

@section('title', 'Marquer les présences')

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header Section -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1><i class="fas fa-calendar-check me-2"></i>Marquer les présences</h1>
                <p class="header-subtitle">Enregistrement des présences étudiantes</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('esbtp.attendances.index') }}" class="btn-acasi secondary">
                    <i class="fas fa-arrow-left"></i>Retour à la liste
                </a>
            </div>
        </div>

        @if(session('warning'))
            <div class="alert alert-warning alert-dismissible fade show mb-4" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>{{ session('warning') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(isset($messageErreur))
            <div class="alert alert-warning alert-dismissible fade show mb-4" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>{{ $messageErreur }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="main-card">
            <div class="main-card-header">
                <div class="main-card-title">
                    <i class="fas fa-calendar-check"></i>
                    Marquer les présences
                </div>
                <div class="main-card-subtitle">Enregistrement des présences étudiantes pour un cours</div>
            </div>
            <div class="main-card-body">

                    <!-- Informations de débogage (visible uniquement en développement) -->
                    @if(config('app.debug') && isset($debug))
                        <div class="alert alert-secondary mb-4">
                            <h5><i class="fas fa-bug me-2"></i>Informations de débogage :</h5>
                            <pre>{{ json_encode($debug, JSON_PRETTY_PRINT) }}</pre>
                        </div>
                    @endif

                    <!-- Guide d'utilisation -->
                    <div class="alert alert-info mb-4">
                        <h5><i class="fas fa-info-circle me-2"></i>Comment marquer les présences :</h5>
                        <ol class="mb-0">
                            <li>Sélectionnez une classe dans la liste déroulante</li>
                            <li>Choisissez une séance de cours parmi celles disponibles pour cette classe</li>
                            <li>La date sera automatiquement calculée en fonction de la séance choisie</li>
                            <li>Marquez les présences pour chaque étudiant et enregistrez</li>
                        </ol>
                    </div>

                    <!-- Sélection de la classe et de la séance -->
                    <div class="mb-4">
                        <form id="selectionForm" method="GET" action="{{ route('esbtp.attendances.create') }}" class="row g-3">
                            <div class="col-md-4">
                                <label for="classe_id" class="form-label">Classe</label>
                                <select name="classe_id" id="classe_id" class="form-control" required onchange="this.form.submit()">
                                    <option value="">Sélectionner une classe</option>
                                    @foreach($classes as $classe)
                                        <option value="{{ $classe->id }}" {{ request('classe_id') == $classe->id ? 'selected' : '' }}>
                                            {{ $classe->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="form-text text-muted">
                                    <i class="fas fa-info-circle"></i> Sélectionnez d'abord une classe pour voir les séances disponibles.
                                </small>
                            </div>

                            @if(isset($classeSelectionnee) && $classeSelectionnee)
                                <div class="col-md-4">
                                    <label for="seance_id" class="form-label">Séance de cours</label>
                                    <select name="seance_id" id="seance_id" class="form-control" required onchange="this.form.submit()">
                                        <option value="">Sélectionner une séance</option>
                                        @foreach($seances as $seance)
                                            <option value="{{ $seance->id }}" {{ request('seance_id') == $seance->id ? 'selected' : '' }}
                                                data-date="{{ $seance->date_calculee }}"
                                                data-jour="{{ $seance->jour_nom }}">
                                                {{ $seance->matiere->name ?? 'Matière inconnue' }} - {{ $seance->heure_debut->format('H:i') }} à {{ $seance->heure_fin->format('H:i') }} ({{ $seance->jour_nom }})
                                                @if($seance->date_calculee)
                                                    - {{ \Carbon\Carbon::parse($seance->date_calculee)->format('d/m/Y') }}
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                    @if($seances->isEmpty())
                                        <small class="form-text text-danger">
                                            <i class="fas fa-exclamation-circle"></i> Aucune séance disponible pour cette classe. Vérifiez que l'emploi du temps est actif.
                                        </small>
                                    @else
                                        <small class="form-text text-muted">
                                            <i class="fas fa-info-circle"></i> Sélectionnez une séance pour voir les étudiants.
                                        </small>
                                    @endif
                                </div>
                            @endif

                            @if(request()->filled('seance_id') && isset($classeSelectionnee) && $classeSelectionnee)
                                <div class="col-md-4">
                                    <label for="date" class="form-label">Date</label>
                                    <input type="date" name="date" id="date" class="form-control" required value="{{ $dateSeance ?? request('date', date('Y-m-d')) }}" {{ $dateSeance ? 'readonly' : '' }}>
                                    @if($dateSeance)
                                        <small class="form-text text-info">
                                            <i class="fas fa-info-circle"></i> Cette date est automatiquement calculée en fonction du jour de la séance et de la période de l'emploi du temps.
                                        </small>
                                    @endif
                                </div>
                            @endif
                        </form>
                    </div>

                    <!-- Formulaire de saisie des présences -->
                    @if(request()->filled('seance_id') && isset($classeSelectionnee) && $classeSelectionnee && isset($etudiants) && $etudiants->count() > 0)
                        <form action="{{ route('esbtp.attendances.store') }}" method="POST">
                            @csrf
                            <input type="hidden" name="seance_cours_id" value="{{ request('seance_id') }}">
                            <input type="hidden" name="date" value="{{ $dateSeance ?? request('date') }}">

                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Étudiant</th>
                                            <th>Statut</th>
                                            <th>Commentaire</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($etudiants as $etudiant)
                                            <tr>
                                                <td>{{ $etudiant->nom_complet }}</td>
                                                <td>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input" type="radio" name="statuts[{{ $etudiant->id }}]" id="present_{{ $etudiant->id }}" value="present" checked>
                                                        <label class="form-check-label text-success" for="present_{{ $etudiant->id }}">Présent</label>
                                                    </div>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input" type="radio" name="statuts[{{ $etudiant->id }}]" id="absent_{{ $etudiant->id }}" value="absent">
                                                        <label class="form-check-label text-danger" for="absent_{{ $etudiant->id }}">Absent</label>
                                                    </div>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input" type="radio" name="statuts[{{ $etudiant->id }}]" id="retard_{{ $etudiant->id }}" value="retard">
                                                        <label class="form-check-label text-warning" for="retard_{{ $etudiant->id }}">Retard</label>
                                                    </div>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input" type="radio" name="statuts[{{ $etudiant->id }}]" id="excuse_{{ $etudiant->id }}" value="excuse">
                                                        <label class="form-check-label text-info" for="excuse_{{ $etudiant->id }}">Excusé</label>
                                                    </div>
                                                </td>
                                                <td>
                                                    <input type="text" name="commentaires[{{ $etudiant->id }}]" class="form-control" placeholder="Commentaire (optionnel)">
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-4">
                                <button type="submit" class="btn btn-gradient-primary">
                                    <i class="mdi mdi-content-save"></i> Enregistrer les présences
                                </button>
                                <a href="{{ route('esbtp.attendances.index') }}" class="btn btn-light">Annuler</a>
                            </div>
                        </form>

                        <!-- Boutons pour marquer tous les étudiants avec onclick direct -->
                        <div class="mt-4">
                            <button type="button" class="btn btn-success btn-sm" onclick="marquerTous('present')">
                                <i class="mdi mdi-check-all"></i> Tous présents
                            </button>
                            <button type="button" class="btn btn-danger btn-sm" onclick="marquerTous('absent')">
                                <i class="mdi mdi-close-all"></i> Tous absents
                            </button>
                        </div>
                    @elseif(request()->filled('seance_id') && isset($classeSelectionnee) && $classeSelectionnee && isset($etudiants) && $etudiants->count() == 0)
                        <div class="alert alert-warning">
                            <i class="mdi mdi-alert-circle"></i> Aucun étudiant n'est inscrit dans cette classe.
                        </div>
                    @elseif(request()->filled('seance_id') && isset($classeSelectionnee) && $classeSelectionnee && !isset($messageErreur))
                        <div class="alert alert-info">
                            <i class="mdi mdi-information-outline"></i> Veuillez vérifier que la classe sélectionnée a des étudiants inscrits et que l'emploi du temps est correctement configuré.
                        </div>
                    @elseif(isset($classeSelectionnee) && $classeSelectionnee && !request()->filled('seance_id') && isset($seances) && $seances->isNotEmpty())
                        <div class="alert alert-info">
                            <i class="mdi mdi-information-outline"></i> Veuillez sélectionner une séance pour voir les étudiants et marquer les présences.
                        </div>
                    @elseif(!isset($classeSelectionnee) || !$classeSelectionnee)
                        <div class="alert alert-info">
                            <i class="mdi mdi-information-outline"></i> Veuillez d'abord sélectionner une classe pour commencer.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    // Fonction simple pour marquer tous les étudiants avec un statut spécifique
    function marquerTous(statut) {
        console.log('Marquer tous comme ' + statut);

        // Sélectionner tous les boutons radio pour chaque étudiant avec la valeur spécifiée
        var radios = document.querySelectorAll('input[type="radio"][value="' + statut + '"]');
        console.log('Nombre de boutons radio trouvés: ' + radios.length);

        // Marquer chaque bouton radio comme coché
        for (var i = 0; i < radios.length; i++) {
            radios[i].checked = true;
        }
    }

    // Soumettre automatiquement le formulaire quand la date change
    document.addEventListener('DOMContentLoaded', function() {
        var dateInput = document.getElementById('date');
        if (dateInput) {
            dateInput.addEventListener('change', function() {
                document.getElementById('selectionForm').submit();
            });
        }

        // Mettre à jour la date quand la séance change
        var seanceSelect = document.getElementById('seance_id');
        if (seanceSelect) {
            seanceSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const dateCalculee = selectedOption.getAttribute('data-date');

                if (dateCalculee) {
                    const dateInput = document.getElementById('date');
                    if (dateInput) {
                        dateInput.value = dateCalculee;
                        dateInput.setAttribute('readonly', 'readonly');
                    }
                }
            });
        }
    });
</script>
@endsection

@extends('layouts.app')

@section('title', 'Modifier l\'inscription - ' . $inscription->etudiant->nom . ' ' . $inscription->etudiant->prenoms)

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    .select2-container {
        width: 100% !important;
    }
    .select2-selection {
        height: 38px !important;
        border: 1px solid #ced4da !important;
    }
    .select2-selection__rendered {
        line-height: 36px !important;
    }
    .select2-selection__arrow {
        height: 36px !important;
    }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header moderne -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1>{{ $inscription->etudiant->nom }} {{ $inscription->etudiant->prenoms }}</h1>
                <p class="header-subtitle">Modification de l'inscription - Matricule: {{ $inscription->etudiant->matricule }}</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('esbtp.inscriptions.show', $inscription->id) }}" class="btn-acasi info me-2">
                    <i class="fas fa-eye"></i>Voir les détails
                </a>
                <a href="{{ route('esbtp.inscriptions.index') }}" class="btn-acasi secondary">
                    <i class="fas fa-arrow-left"></i>Retour à la liste
                </a>
            </div>
        </div>

        <!-- Formulaire de modification d'inscription -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Modifier l'inscription</h6>
            </div>
            <div class="card-body">
            <form method="POST" action="{{ route('esbtp.inscriptions.update', $inscription->id) }}">
                @csrf
                @method('PUT')

                @if(session('info'))
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        {{ session('info') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Informations de l'étudiant (lecture seule) -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <h5 class="font-weight-bold">Informations de l'étudiant</h5>
                        <hr>

                        <div class="alert alert-info">
                            <p class="mb-0"><strong>Étudiant :</strong> {{ $inscription->etudiant->nom }} {{ $inscription->etudiant->prenoms }}</p>
                            <p class="mb-0"><strong>Matricule :</strong> {{ $inscription->etudiant->matricule }}</p>
                            <p class="mb-0"><strong>Date de naissance :</strong> {{ $inscription->etudiant->date_naissance?->format('d/m/Y') ?? 'Non renseignée' }}</p>
                            <p class="mb-0"><strong>Pour modifier les informations de l'étudiant, veuillez utiliser la page de profil de l'étudiant.</strong></p>
                        </div>
                    </div>
                </div>

                <!-- Informations de l'inscription -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <h5 class="font-weight-bold">Informations de l'inscription</h5>
                        <hr>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="date_inscription">Date d'inscription <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('date_inscription') is-invalid @enderror" id="date_inscription" name="date_inscription" value="{{ old('date_inscription', $inscription->date_inscription->format('Y-m-d')) }}" required>
                            @error('date_inscription')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="type_inscription">Type d'inscription <span class="text-danger">*</span></label>
                            <select class="form-control @error('type_inscription') is-invalid @enderror" id="type_inscription" name="type_inscription" required>
                                <option value="première_inscription" {{ old('type_inscription', $inscription->type_inscription) == 'première_inscription' ? 'selected' : '' }}>Première inscription</option>
                                <option value="réinscription" {{ old('type_inscription', $inscription->type_inscription) == 'réinscription' ? 'selected' : '' }}>Réinscription</option>
                                <option value="transfert" {{ old('type_inscription', $inscription->type_inscription) == 'transfert' ? 'selected' : '' }}>Transfert</option>
                            </select>
                            @error('type_inscription')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="status">Statut <span class="text-danger">*</span></label>
                            <select class="form-control @error('status') is-invalid @enderror" id="status" name="status" required>
                                <option value="en_attente" {{ old('status', $inscription->status) == 'en_attente' ? 'selected' : '' }}>En attente</option>
                                <option value="active" {{ old('status', $inscription->status) == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="annulée" {{ old('status', $inscription->status) == 'annulée' ? 'selected' : '' }}>Annulée</option>
                                <option value="terminée" {{ old('status', $inscription->status) == 'terminée' ? 'selected' : '' }}>Terminée</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Section Transfert (visible seulement si type_inscription = 'première_inscription') -->
                <div class="row mb-3" id="transfert-section" style="display: {{ old('type_inscription', $inscription->type_inscription) == 'première_inscription' ? 'flex' : 'none' }}">
                    <div class="col-md-12">
                        <h6 class="font-weight-bold text-primary mb-3">
                            <i class="fas fa-exchange-alt me-2"></i>Informations de transfert
                        </h6>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="est_transfert">L'étudiant vient-il d'un autre établissement ?</label>
                            <select class="form-control @error('est_transfert') is-invalid @enderror" id="est_transfert" name="est_transfert">
                                <option value="0" {{ old('est_transfert', $inscription->est_transfert ? '1' : '0') == '0' ? 'selected' : '' }}>Non</option>
                                <option value="1" {{ old('est_transfert', $inscription->est_transfert ? '1' : '0') == '1' ? 'selected' : '' }}>Oui</option>
                            </select>
                            @error('est_transfert')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6" id="etablissement-origine-field" style="display: {{ old('est_transfert', $inscription->est_transfert ? '1' : '0') == '1' ? 'block' : 'none' }}">
                        <div class="form-group">
                            <label for="etablissement_origine">Nom de l'établissement d'origine</label>
                            <input type="text" class="form-control @error('etablissement_origine') is-invalid @enderror" id="etablissement_origine" name="etablissement_origine" value="{{ old('etablissement_origine', $inscription->etablissement_origine) }}" placeholder="Ex: Lycée Technique d'Abidjan">
                            <small class="form-text text-muted">Optionnel - Indiquez l'établissement d'où vient l'étudiant</small>
                            @error('etablissement_origine')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    @if($inscription->status !== 'active' || auth()->user()->hasRole('superAdmin'))
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="filiere_id">Filière <span class="text-danger">*</span></label>
                                <select class="form-control @error('filiere_id') is-invalid @enderror" id="filiere_id" name="filiere_id" required>
                                    <option value="">Sélectionner une filière</option>
                                    @foreach($filieres as $filiere)
                                        <option value="{{ $filiere->id }}" {{ old('filiere_id', $inscription->filiere_id) == $filiere->id ? 'selected' : '' }}>
                                            {{ $filiere->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('filiere_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="niveau_id">Niveau d'études <span class="text-danger">*</span></label>
                                <select class="form-control @error('niveau_id') is-invalid @enderror" id="niveau_id" name="niveau_id" required>
                                    <option value="">Sélectionner un niveau</option>
                                    @foreach($niveaux as $niveau)
                                        <option value="{{ $niveau->id }}" {{ old('niveau_id', $inscription->niveau_id) == $niveau->id ? 'selected' : '' }}>
                                            {{ $niveau->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('niveau_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    @elseif($inscription->status === 'active' && !auth()->user()->hasRole('superAdmin'))
                        <!-- Champs hidden pour conserver les valeurs non modifiables (sauf pour superAdmin) -->
                        <input type="hidden" name="filiere_id" value="{{ $inscription->filiere_id }}">
                        <input type="hidden" name="niveau_id" value="{{ $inscription->niveau_id }}">

                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">Filière</label>
                                <input type="text" class="form-control" value="{{ $inscription->filiere->name }}" disabled>
                                <div class="alert alert-warning mt-2">La filière ne peut plus être modifiée après activation de l'inscription.</div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">Niveau d'études</label>
                                <input type="text" class="form-control" value="{{ $inscription->niveau->name }}" disabled>
                                <div class="alert alert-warning mt-2">Le niveau ne peut plus être modifié après activation de l'inscription.</div>
                            </div>
                        </div>
                    @endif

                    @if($inscription->status !== 'active' || auth()->user()->hasRole('superAdmin'))
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="classe_id" class="form-label">Classe</label>
                                <select name="classe_id" id="classe_id" class="form-select" required>
                                    <option value="">Sélectionner une classe</option>
                                    @foreach($classes as $classe)
                                        <option value="{{ $classe->id }}"
                                                data-filiere-id="{{ $classe->filiere_id }}"
                                                data-niveau-id="{{ $classe->niveau_etude_id }}"
                                                @if($inscription->classe_id == $classe->id) selected @endif>
                                            {{ $classe->name }}
                                            @if($classe->filiere && $classe->niveauEtude)
                                                ({{ $classe->filiere->name }} - {{ $classe->niveauEtude->name }})
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text text-muted">Vous pouvez changer la classe tant que l'inscription n'est pas activée.</div>
                            </div>
                        </div>
                    @elseif($inscription->status === 'active' && !auth()->user()->hasRole('superAdmin'))
                        <!-- Champ hidden pour conserver la valeur de la classe (sauf pour superAdmin) -->
                        <input type="hidden" name="classe_id" value="{{ $inscription->classe_id }}">

                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">Classe</label>
                                <input type="text" class="form-control" value="{{ $inscription->classe->name }}" disabled>
                                <div class="alert alert-warning mt-2">La classe ne peut plus être modifiée après activation de l'inscription.</div>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="affectation_status">Statut d'affectation</label>
                            <select class="form-control @error('affectation_status') is-invalid @enderror" id="affectation_status" name="affectation_status">
                                <option value="">-- Sélectionner le statut --</option>
                                <option value="affecté" {{ old('affectation_status', $inscription->affectation_status) == 'affecté' ? 'selected' : '' }}>Affecté</option>
                                <option value="réaffecté" {{ old('affectation_status', $inscription->affectation_status) == 'réaffecté' ? 'selected' : '' }}>Réaffecté</option>
                                <option value="non_affecté" {{ old('affectation_status', $inscription->affectation_status) == 'non_affecté' ? 'selected' : '' }}>Non affecté</option>
                            </select>
                            <div class="form-text text-muted">
                                Le statut d'affectation détermine les frais de scolarité selon les subventions gouvernementales ivoiriennes.
                            </div>
                            @error('affectation_status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="montant_scolarite">Montant scolarité <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('montant_scolarite') is-invalid @enderror" id="montant_scolarite" name="montant_scolarite" value="{{ old('montant_scolarite', $inscription->montant_scolarite) }}" min="0" required>
                            @error('montant_scolarite')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="observations">Observations</label>
                            <textarea class="form-control @error('observations') is-invalid @enderror" id="observations" name="observations" rows="3">{{ old('observations', $inscription->observations) }}</textarea>
                            @error('observations')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>


                <div class="row">
                    <div class="col-md-12 text-end">
                        <button type="button" class="btn btn-secondary me-2" onclick="history.back()">
                            <i class="fas fa-times me-1"></i>Annuler
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Enregistrer les modifications
                        </button>
                    </div>
                </div>
            </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Stocker toutes les options de classes pour le filtrage
        var allClassesOptions = $('#classe_id option').clone();

        // Filtrer les classes selon la combinaison filière + niveau
        function filterClasses() {
            var filiereId = $('#filiere_id').val();
            var niveauId = $('#niveau_id').val();
            var currentClasseId = $('#classe_id').val();

            // Vider la liste des classes
            $('#classe_id').empty();
            $('#classe_id').append('<option value="">Sélectionner une classe</option>');

            if (filiereId && niveauId) {
                // Filtrer les classes qui correspondent à la combinaison filière + niveau
                allClassesOptions.each(function() {
                    var $option = $(this);
                    if ($option.val() === '') {
                        return; // Skip l'option par défaut
                    }

                    var optionFiliereId = $option.data('filiere-id');
                    var optionNiveauId = $option.data('niveau-id');

                    if (optionFiliereId == filiereId && optionNiveauId == niveauId) {
                        $('#classe_id').append($option.clone());
                    }
                });

                // Réselectionner la classe actuelle si elle est toujours disponible
                if (currentClasseId && $('#classe_id option[value="' + currentClasseId + '"]').length > 0) {
                    $('#classe_id').val(currentClasseId);
                }
            } else if (!filiereId && !niveauId) {
                // Si aucune sélection, afficher toutes les classes
                allClassesOptions.each(function() {
                    var $option = $(this);
                    $('#classe_id').append($option.clone());
                });
                if (currentClasseId) {
                    $('#classe_id').val(currentClasseId);
                }
            }
        }

        // Événements : Filière OU Niveau → filtrer Classes
        $('#filiere_id, #niveau_id').change(function() {
            filterClasses();
        });

        // Avertissement si le statut est modifié à "terminée"
        $('#status').change(function() {
            if ($(this).val() === 'terminée') {
                alert("Attention : Changer le statut à 'terminée' modifiera également le statut de l'étudiant à 'diplômé' s'il n'a pas d'autres inscriptions actives.");
            } else if ($(this).val() === 'annulée') {
                alert("Attention : Changer le statut à 'annulée' peut modifier le statut de l'étudiant à 'inactif' s'il n'a pas d'autres inscriptions actives.");
            }
        });

        // Gestion de la visibilité de la section transfert
        function toggleTransfertSection() {
            const typeInscription = $('#type_inscription').val();
            if (typeInscription === 'première_inscription') {
                $('#transfert-section').show();
            } else {
                $('#transfert-section').hide();
                // Réinitialiser les valeurs si on cache la section
                $('#est_transfert').val('0');
                $('#etablissement_origine').val('');
                $('#etablissement-origine-field').hide();
            }
        }

        // Gestion de la visibilité du champ établissement d'origine
        function toggleEtablissementOrigine() {
            const estTransfert = $('#est_transfert').val();
            if (estTransfert === '1') {
                $('#etablissement-origine-field').show();
            } else {
                $('#etablissement-origine-field').hide();
                $('#etablissement_origine').val('');
            }
        }

        // Event listeners
        $('#type_inscription').change(toggleTransfertSection);
        $('#est_transfert').change(toggleEtablissementOrigine);

        // Initialiser l'état au chargement
        toggleTransfertSection();
        toggleEtablissementOrigine();
    });
</script>
@endpush

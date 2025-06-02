@extends('layouts.app')

@section('title', 'Nouvelle Inscription')

@push('styles')
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
@endpush

@section('content')
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Nouvelle Inscription</h1>
        <a href="{{ route('esbtp.inscriptions.index') }}" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm">
            <i class="fas fa-arrow-left fa-sm text-white-50"></i> Retour à la liste
        </a>
    </div>

    <!-- Formulaire d'inscription -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Formulaire d'inscription</h6>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('esbtp.inscriptions.store') }}" enctype="multipart/form-data">
                @csrf

                @if(session('info'))
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        {{ session('info') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
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

                <!-- Informations générales -->
                <div class="row">
                    <div class="col-md-12 mb-4">
                        <h5 class="font-weight-bold">Informations générales</h5>
                        <hr>
                    </div>
                </div>

                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i> Un compte étudiant sera automatiquement créé lors de l'inscription. Le nom d'utilisateur et le mot de passe seront affichés après la création.
                    </div>

                <div class="row mb-4">
                    <div class="col-md-12">
                        <h5 class="card-title">Informations de classe</h5>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle mr-1"></i> La classe sélectionnée détermine automatiquement la filière, le niveau d'études, la formation et l'année universitaire.
                    </div>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="classe_display">Classe <span class="text-danger">*</span></label>
                            <div style="display: flex; gap: 10px;">
                                <input type="hidden" id="classe_id" name="classe_id" value="{{ old('classe_id') }}">
                                <input type="text" id="classe_display" class="form-control @error('classe_id') is-invalid @enderror" value="{{ old('classe_display') }}" readonly>
                                <button class="btn btn-primary" type="button" onclick="ouvrirSelecteurClasse()" style="min-width: 120px;">
                                    <i class="fas fa-search"></i> Sélectionner
                                </button>
                            </div>
                            <small class="text-muted mt-1 d-block">Cliquez sur le bouton pour ouvrir le sélecteur de classe</small>
                        @error('classe_id')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        </div>
                    </div>
                </div>

                <!-- Informations de l'étudiant -->
                <div class="row mt-4">
                    <div class="col-md-12 mb-4">
                        <h5 class="font-weight-bold">Informations de l'étudiant</h5>
                        <hr>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="nom">Nom</label>
                        <input type="text" class="form-control @error('nom') is-invalid @enderror"
                               id="nom" name="nom" value="{{ old('nom') }}" required>
                        @error('nom')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="prenoms">Prénom(s) <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('prenoms') is-invalid @enderror"
                               id="prenoms" name="prenoms" value="{{ old('prenoms') }}" required>
                        @error('prenoms')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="matricule">Matricule <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('matricule') is-invalid @enderror"
                               id="matricule" name="matricule" value="{{ old('matricule') }}" required>
                        <small class="text-muted">Entrez manuellement le matricule de l'étudiant</small>
                        @error('matricule')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="date_naissance">Date de naissance</label>
                        <input type="date" class="form-control @error('date_naissance') is-invalid @enderror"
                               id="date_naissance" name="date_naissance" value="{{ old('date_naissance') }}" required>
                        @error('date_naissance')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="lieu_naissance">Lieu de naissance</label>
                        <input type="text" class="form-control @error('lieu_naissance') is-invalid @enderror"
                               id="lieu_naissance" name="lieu_naissance" value="{{ old('lieu_naissance') }}">
                        @error('lieu_naissance')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="sexe">Genre</label>
                        <select class="form-control @error('sexe') is-invalid @enderror" id="sexe" name="sexe" required>
                            <option value="">Sélectionner...</option>
                            <option value="M" {{ old('sexe') == 'M' ? 'selected' : '' }}>Homme</option>
                            <option value="F" {{ old('sexe') == 'F' ? 'selected' : '' }}>Femme</option>
                        </select>
                        @error('sexe')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="email_personnel">Email</label>
                        <input type="email" class="form-control @error('email_personnel') is-invalid @enderror"
                               id="email_personnel" name="email_personnel" value="{{ old('email_personnel') }}">
                        @error('email_personnel')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="telephone">Téléphone</label>
                        <input type="text" class="form-control @error('telephone') is-invalid @enderror"
                               id="telephone" name="telephone" value="{{ old('telephone') }}"
                               placeholder="+225 XX XX XXX XXX">
                        @error('telephone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="ville">Ville de résidence</label>
                        <input type="text" class="form-control @error('ville') is-invalid @enderror"
                               id="ville" name="ville" value="{{ old('ville') }}">
                        @error('ville')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="commune">Commune de résidence</label>
                        <input type="text" class="form-control @error('commune') is-invalid @enderror"
                               id="commune" name="commune" value="{{ old('commune') }}">
                        @error('commune')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-8 mb-3">
                        <label for="photo">Photo de profil</label>
                        <input type="file" class="form-control-file @error('photo') is-invalid @enderror"
                               id="photo" name="photo">
                        <small class="form-text text-muted">Formats acceptés: jpeg, png, jpg. Taille max: 2Mo</small>
                        @error('photo')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Informations du parent -->
                <div class="row mt-4">
                    <div class="col-md-12 mb-4 d-flex justify-content-between align-items-center">
                        <h5 class="font-weight-bold">Informations du/des parent(s)/tuteur(s)</h5>
                        <button type="button" class="btn btn-sm btn-primary" id="add-parent-btn">
                            <i class="fas fa-plus me-1"></i> Ajouter un parent
                        </button>
                    </div>
                    <hr>
                </div>

                <!-- Container pour les parents -->
                <div id="parents-container">
                    <!-- Premier parent (toujours présent) -->
                    <div class="parent-item card mb-3">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Parent/Tuteur 1</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-check mb-3">
                                <input class="form-check-input parent-existant-checkbox" type="checkbox" id="parent_existant_0" name="parents[0][existant]" value="1">
                                <label class="form-check-label" for="parent_existant_0">
                                    Sélectionner un parent existant
                                </label>
                            </div>
                            <!-- Champ caché pour le type de parent -->
                            <input type="hidden" name="parents[0][type]" id="parent_type_0" value="nouveau">

                            <!-- Sélection d'un parent existant -->
                            <div class="form-group mb-3 parent-existant-section" style="display: none;">
                                <label for="parent_id_0">Sélectionner un parent</label>
                                <select class="form-control parent-select" id="parent_id_0" name="parents[0][parent_id]" data-placeholder="Rechercher un parent...">
                                    <option value=""></option>
                                </select>
                            </div>

                            <!-- Nouveau parent -->
                            <div class="parent-nouveau-section">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="parent_nom_0">Nom <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="parent_nom_0" name="parents[0][nom]" value="{{ old('parents.0.nom') }}">
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label for="parent_prenoms_0">Prénom(s) <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="parent_prenoms_0" name="parents[0][prenoms]" value="{{ old('parents.0.prenoms') }}">
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label for="parent_relation_0">Relation <span class="text-danger">*</span></label>
                                        <select class="form-control" id="parent_relation_0" name="parents[0][relation]">
                                            <option value="Père" {{ old('parents.0.relation') == 'Père' ? 'selected' : '' }}>Père</option>
                                            <option value="Mère" {{ old('parents.0.relation') == 'Mère' ? 'selected' : '' }}>Mère</option>
                                            <option value="Tuteur" {{ old('parents.0.relation', 'Tuteur') == 'Tuteur' ? 'selected' : '' }}>Tuteur</option>
                                            <option value="Autre" {{ old('parents.0.relation') == 'Autre' ? 'selected' : '' }}>Autre</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="parent_telephone_0">Téléphone <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="parent_telephone_0" name="parents[0][telephone]"
                                               value="{{ old('parents.0.telephone') }}" placeholder="+225 XX XX XXX XXX">
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="parent_email_0">Email</label>
                                        <input type="email" class="form-control" id="parent_email_0" name="parents[0][email]" value="{{ old('parents.0.email') }}">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label for="parent_adresse_0">Adresse</label>
                                        <textarea class="form-control" id="parent_adresse_0" name="parents[0][adresse]" rows="2">{{ old('parents.0.adresse') }}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Template pour ajouter de nouveaux parents (caché) -->
                <template id="parent-template">
                    <div class="parent-item card mb-3">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Parent/Tuteur {number}</h5>
                            <button type="button" class="btn btn-sm btn-danger remove-parent-btn">
                                <i class="fas fa-times"></i> Supprimer
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="form-check mb-3">
                                <input class="form-check-input parent-existant-checkbox" id="parent_existant_{index}" name="parents[{index}][existant]" value="1">
                                <label class="form-check-label" for="parent_existant_{index}">
                                    Sélectionner un parent existant
                                </label>
                            </div>

                            <!-- Champ caché pour le type de parent -->
                            <input type="hidden" name="parents[{index}][type]" id="parent_type_{index}" value="nouveau">

                            <!-- Sélection d'un parent existant -->
                            <div class="form-group mb-3 parent-existant-section" style="display: none;">
                                <label for="parent_id_{index}">Sélectionner un parent</label>
                                <select class="form-control parent-select" id="parent_id_{index}" name="parents[{index}][parent_id]" data-placeholder="Rechercher un parent...">
                                    <option value=""></option>
                                </select>
                            </div>

                            <!-- Nouveau parent -->
                            <div class="parent-nouveau-section">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="parent_nom_{index}">Nom <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="parent_nom_{index}" name="parents[{index}][nom]">
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label for="parent_prenoms_{index}">Prénom(s) <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="parent_prenoms_{index}" name="parents[{index}][prenoms]">
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label for="parent_relation_{index}">Relation <span class="text-danger">*</span></label>
                                        <select class="form-control" id="parent_relation_{index}" name="parents[{index}][relation]">
                                            <option value="Père">Père</option>
                                            <option value="Mère">Mère</option>
                                            <option value="Tuteur">Tuteur</option>
                                            <option value="Autre">Autre</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="parent_telephone_{index}">Téléphone <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="parent_telephone_{index}" name="parents[{index}][telephone]"
                                               placeholder="+225 XX XX XXX XXX">
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="parent_email_{index}">Email</label>
                                        <input type="email" class="form-control" id="parent_email_{index}" name="parents[{index}][email]">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label for="parent_adresse_{index}">Adresse</label>
                                        <textarea class="form-control" id="parent_adresse_{index}" name="parents[{index}][adresse]" rows="2"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Boutons de soumission -->
                <div class="row mt-4">
                    <div class="col-md-12 text-center">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Enregistrer l'inscription
                        </button>
                        <a href="{{ route('esbtp.inscriptions.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Annuler
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Sélecteur de Classes -->
<div class="modal fade" id="modal-selecteur-classe" tabindex="-1" aria-labelledby="modal-selecteur-classe-label" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-selecteur-classe-label">Sélectionner une classe</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <select id="filter_filiere" class="form-select">
                            <option value="">Filière...</option>
                            @foreach($filieres as $filiere)
                                <option value="{{ $filiere->id }}">{{ $filiere->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select id="filter_niveau" class="form-select">
                            <option value="">Niveau...</option>
                            @foreach($niveaux as $niveau)
                                <option value="{{ $niveau->id }}">{{ $niveau->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select id="filter_annee" class="form-select">
                            <option value="">Année...</option>
                            @foreach($annees as $annee)
                                <option value="{{ $annee->id }}">{{ $annee->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-12">
                        <button id="btn-filtrer-classes" class="btn btn-primary btn-sm">
                            <i class="fas fa-filter"></i> Filtrer
                        </button>
                        <button id="btn-reset-filters" class="btn btn-secondary btn-sm">
                            <i class="fas fa-undo"></i> Réinitialiser
                        </button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Code</th>
                                <th>Filière</th>
                                <th>Niveau</th>
                                <th>Année</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="liste-classes">
                            <tr>
                                <td colspan="6" style="padding: 15px; text-align: center; color: #6c757d;">
                                    Chargement des classes...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<!-- Script pour définir la fonction ouvrirSelecteurClasse immédiatement -->
<script>
    function ouvrirSelecteurClasse() {
        console.log('Ouverture du modal de sélection de classe');
        $('#modal-selecteur-classe').modal('show');

        // Afficher une animation de chargement
        $('#liste-classes').html('<tr><td colspan="6" class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Chargement...</span></div></td></tr>');

        // Charger toutes les classes immédiatement
        $.ajax({
            url: "{{ route('esbtp.api.get-classes') }}",
            method: 'GET',
            data: {},
            success: function(response) {
                console.log('Réponse API classes:', response);
                if (response.length === 0) {
                    $('#liste-classes').html('<tr><td colspan="6" class="text-center">Aucune classe disponible</td></tr>');
                    return;
                }

                var html = '';
                $.each(response, function(index, classe) {
                    var displayName = classe.name;
                    var displayFiliere = classe.filiere_name || '';
                    var displayNiveau = classe.niveau_name || '';
                    var displayAnnee = classe.annee_name || '';

                    html += '<tr>';
                    html += '<td>' + displayName + '</td>';
                    html += '<td>' + classe.code + '</td>';
                    html += '<td>' + displayFiliere + '</td>';
                    html += '<td>' + displayNiveau + '</td>';
                    html += '<td>' + displayAnnee + '</td>';
                    html += '<td><button type="button" class="btn btn-sm btn-primary" onclick="selectionnerClasse(' + classe.id + ', \'' + displayName + '\')">Sélectionner</button></td>';
                    html += '</tr>';
                });

                $('#liste-classes').html(html);
            },
            error: function(error) {
                console.error('Erreur lors de la récupération des classes:', error);
                $('#liste-classes').html('<tr><td colspan="6" class="text-center text-danger">Erreur lors de la récupération des classes</td></tr>');
            }
        });
    }

    function fermerSelecteurClasse() {
        $('#modal-selecteur-classe').modal('hide');
    }
</script>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    $(document).ready(function() {
        console.log('DOM chargé - Initialisation du formulaire d\'inscription');

        // Variables pour gérer les parents
        let parentIndex = 0;

        // Initialiser Select2 pour le premier parent
        initializeSelect2();

        // Gestionnaire pour ajouter un parent
        $('#add-parent-btn').on('click', function() {
            console.log('Ajout d\'un nouveau parent');
            // Incrémenter l'index
            parentIndex++;

            // Récupérer le template et remplacer les index
            let template = $('#parent-template').html();
            template = template.replace(/{index}/g, parentIndex);
            template = template.replace(/{number}/g, parentIndex + 1);

            // Ajouter le template au container
            $('#parents-container').append(template);

            // Initialiser les nouveaux select2
            initializeSelect2();

            // Ajouter les gestionnaires d'événements
            addParentEventListeners();
        });

        // Initialisation des gestionnaires d'événements pour parents
        addParentEventListeners();

        // Fonction pour initialiser les gestionnaires d'événements des parents
        function addParentEventListeners() {
            console.log('Initialisation des événements parents');
            // Gestion de la suppression d'un parent
            $('.remove-parent-btn').off('click').on('click', function() {
                console.log('Suppression d\'un parent');
                $(this).closest('.parent-item').remove();
            });

            // Gestion de la checkbox "parent existant"
            $('.parent-existant-checkbox').off('change').on('change', function() {
                console.log('Toggle parent existant/nouveau');
                var isChecked = $(this).is(':checked');
                var parentItem = $(this).closest('.parent-item');
                var typeField = parentItem.find('[id^="parent_type_"]');

                if (isChecked) {
                    parentItem.find('.parent-existant-section').show();
                    parentItem.find('.parent-nouveau-section').hide();
                    typeField.val('existant');
                } else {
                    parentItem.find('.parent-existant-section').hide();
                    parentItem.find('.parent-nouveau-section').show();
                    typeField.val('nouveau');
                }
            });

            // Initialiser l'état pour les parents existants
            $('.parent-existant-checkbox').trigger('change');
        }

        // Initialiser Select2 pour les sélecteurs de parents
        function initializeSelect2() {
            console.log('Initialisation de Select2');
            $('.parent-select').select2({
                placeholder: 'Rechercher un parent...',
                minimumInputLength: 2,
                ajax: {
                    url: '{{ route("esbtp.api.search-parents") }}',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        console.log('Recherche de parents:', params.term);
                        return {
                            q: params.term
                        };
                    },
                    processResults: function(data) {
                        console.log('Résultats de la recherche:', data);
                        return {
                            results: data.items.map(function(parent) {
                                return {
                                    id: parent.id,
                                    text: parent.nom + ' ' + parent.prenoms + ' (' + parent.telephone + ')'
                                };
                            })
                        };
                    },
                    cache: true
                }
            });
        }

        // Filtrer les classes
        $('#btn-filtrer-classes').on('click', function() {
            var filiereId = $('#filter_filiere').val();
            var niveauId = $('#filter_niveau').val();
            var anneeId = $('#filter_annee').val();
            var formationId = $('#filter_formation').val();

            // Vérifier qu'au moins un filtre est sélectionné
            if (!filiereId && !niveauId && !anneeId && !formationId) {
                alert('Veuillez sélectionner au moins un filtre');
                return;
            }

            // Afficher une animation de chargement
            $('#liste-classes').html('<tr><td colspan="6" class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Chargement...</span></div></td></tr>');

            // Faire une requête AJAX pour récupérer les classes
            $.ajax({
                url: "{{ route('esbtp.api.get-classes') }}",
                method: 'GET',
                data: {
                    filiere_id: filiereId,
                    niveau_id: niveauId,
                    annee_id: anneeId,
                    formation_id: formationId
                },
                success: function(response) {
                    console.log('Réponse API classes:', response);
                    if (response.length === 0) {
                        $('#liste-classes').html('<tr><td colspan="6" class="text-center">Aucune classe trouvée avec ces critères</td></tr>');
                        return;
                    }

                    var html = '';
                    $.each(response, function(index, classe) {
                        var displayName = classe.name;
                        var displayFiliere = classe.filiere_name || '';
                        var displayNiveau = classe.niveau_name || '';
                        var displayAnnee = classe.annee_name || '';

                        html += '<tr>';
                        html += '<td>' + displayName + '</td>';
                        html += '<td>' + classe.code + '</td>';
                        html += '<td>' + displayFiliere + '</td>';
                        html += '<td>' + displayNiveau + '</td>';
                        html += '<td>' + displayAnnee + '</td>';
                        html += '<td><button type="button" class="btn btn-sm btn-primary" onclick="selectionnerClasse(' + classe.id + ', \'' + displayName + '\')">Sélectionner</button></td>';
                        html += '</tr>';
                    });

                    $('#liste-classes').html(html);
                },
                error: function(error) {
                    console.error('Erreur lors de la récupération des classes:', error);
                    $('#liste-classes').html('<tr><td colspan="6" class="text-center text-danger">Erreur lors de la récupération des classes</td></tr>');
                }
            });
        });

        // Réinitialiser les filtres
        $('#btn-reset-filters').on('click', function() {
            $('#filter_filiere').val('');
            $('#filter_niveau').val('');
            $('#filter_annee').val('');
            $('#filter_formation').val('');
            $('#liste-classes').html('<tr><td colspan="6" style="padding: 15px; text-align: center; color: #6c757d;">Veuillez sélectionner au moins un filtre</td></tr>');
        });

        window.selectionnerClasse = function(classeId, classeName) {
            console.log('Sélection de la classe:', classeId, classeName);
            document.getElementById('classe_id').value = classeId;
            document.getElementById('classe_display').value = classeName;
            document.getElementById('classe_display').classList.add('is-valid');
            fermerSelecteurClasse();
        };

        // Si une classe était déjà sélectionnée (en cas d'erreur de validation)
        var classeId = $('#classe_id').val();
        if (classeId) {
            $('#classe_display').addClass('is-valid');
        }
    });
</script>
@endpush

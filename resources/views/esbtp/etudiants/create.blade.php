@extends('layouts.app')

@section('title', 'Ajouter un étudiant - ESBTP-yAKRO')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header moderne -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1>Ajouter un étudiant</h1>
                <p class="header-subtitle">Formulaire d'inscription d'un nouvel étudiant à l'ESBTP</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('esbtp.etudiants.index') }}" class="btn-acasi secondary">
                    <i class="fas fa-arrow-left"></i>Retour à la liste
                </a>
            </div>
        </div>

        <div class="card-moderne">
            <div class="p-lg">

            @if ($errors->any())
                <div class="alert alert-danger d-flex align-items-center glass-alert mb-4">
                    <i class="fas fa-exclamation-triangle fa-2x me-3 text-danger"></i>
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <form action="{{ route('esbtp.etudiants.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card border-0 shadow-lg rounded-4 premium-glass mb-4">
                            <div class="card-header bg-white border-0 rounded-top-4">
                                <h6 class="mb-0 d-flex align-items-center">
                                    <i class="fas fa-id-card me-2"></i> Informations personnelles
                                </h6>
                            </div>
                            <div class="card-body">
                                @includeIf('esbtp.etudiants._form_personal')
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card border-0 shadow-lg rounded-4 premium-glass mb-4">
                            <div class="card-header bg-white border-0 rounded-top-4">
                                <h6 class="mb-0 d-flex align-items-center">
                                    <i class="fas fa-user-check me-2"></i> Informations d'inscription
                                </h6>
                            </div>
                            <div class="card-body">
                                @includeIf('esbtp.etudiants._form_inscription')
                            </div>
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-end gap-3 mt-4">
                    <button type="submit" class="btn btn-lg btn-primary fw-bold shadow rounded-3 px-4 py-2 d-flex align-items-center gap-2 animate-fade-in-up">
                        <i class="fas fa-save"></i> Enregistrer l'étudiant
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        debugLog('Document ready - initializing student form');

        // Afficher les informations de débogage
        debugLog('Routes API disponibles:');
        debugLog('- Search Parents: {{ route("esbtp.api.search-parents") }}');
        debugLog('- Get Classes: {{ route("esbtp.api.get-classes") }}');

        // Initialiser Select2 pour les parents existants
        $('.select-parent').select2({
            ajax: {
                url: '{{ route("esbtp.api.search-parents") }}',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    debugLog('Recherche de parents, terme:', params.term);
                    return {
                        q: params.term,
                        page: params.page
                    };
                },
                processResults: function (data, params) {
                    params.page = params.page || 1;
                    debugLog('Résultats de recherche parents:', data);

                    // Formatage des données pour Select2
                    const items = data.items.map(function(parent) {
                        return {
                            id: parent.id,
                            text: parent.nom + ' ' + parent.prenoms + ' (' + parent.telephone + ')'
                        };
                    });

                    return {
                        results: items,
                        pagination: {
                            more: data.pagination.more
                        }
                    };
                },
                cache: true
            },
            placeholder: 'Rechercher un parent...',
            minimumInputLength: 1,
            templateResult: formatParent,
            templateSelection: formatParentSelection
        });

        // Fonctions de formatage pour Select2
        function formatParent(parent) {
            if (parent.loading) {
                return parent.text;
            }
            return $('<span>' + parent.text + '</span>');
        }

        function formatParentSelection(parent) {
            return parent.text || parent.id;
        }

        // Initialiser le compteur de parents (1 parent déjà présent)
        let parentCount = 1;
        debugLog('Nombre initial de parents:', parentCount);

        // Toggle between existing and new parent inputs - avec debug
        $(document).on('change', 'input[id^="parent_existant_"]', function() {
            const index = this.id.split('_').pop();
            debugLog('Toggle parent existant pour index:', index, 'Checked:', $(this).is(':checked'));

            if ($(this).is(':checked')) {
                // Afficher le sélecteur de parent existant
                $(this).closest('.parent-item').find('.parent-existant').removeClass('d-none');
                $(this).closest('.parent-item').find('.parent-nouveau').addClass('d-none');
                debugLog('Mode parent existant activé');
            } else {
                // Afficher le formulaire de nouveau parent
                $(this).closest('.parent-item').find('.parent-existant').addClass('d-none');
                $(this).closest('.parent-item').find('.parent-nouveau').removeClass('d-none');
                debugLog('Mode nouveau parent activé');
            }
        });

        // Initialiser Select2 pour les autres champs
        $('.select2').select2();

        // AJAX pour charger les classes en fonction de la filière, du niveau et de l'année
        $('#filiere_id, #niveau_etude_id, #annee_universitaire_id').change(function() {
            var filiereId = $('#filiere_id').val();
            var niveauId = $('#niveau_etude_id').val();
            var anneeId = $('#annee_universitaire_id').val();

            debugLog('Changement détecté - Filière:', filiereId, 'Niveau:', niveauId, 'Année:', anneeId);

            if (filiereId && niveauId && anneeId) {
                debugLog('Toutes les données sont présentes, appel AJAX pour les classes');

                // Afficher un indicateur de chargement
                $('#classe_id').html('<option value="">Chargement des classes...</option>');

                $.ajax({
                    url: '{{ route("esbtp.api.get-classes") }}',
                    type: 'GET',
                    data: {
                        filiere_id: filiereId,
                        niveau_id: niveauId,
                        annee_id: anneeId
                    },
                    success: function(data) {
                        debugLog('Classes reçues:', data);

                        if (data.length === 0) {
                            $('#classe_id').html('<option value="">Aucune classe disponible</option>');
                            return;
                        }

                        var options = '<option value="">Sélectionner une classe</option>';
                        $.each(data, function(index, classe) {
                            options += '<option value="' + classe.id + '">' + classe.name + ' (' + classe.code + ')</option>';
                        });
                        $('#classe_id').html(options);

                        // Si une seule classe est disponible, la sélectionner automatiquement
                        if (data.length === 1) {
                            $('#classe_id').val(data[0].id);
                        }
                    },
                    error: function(xhr, status, error) {
                        debugError('Erreur lors de la récupération des classes:', error);
                        debugError('Réponse du serveur:', xhr.responseText);
                        $('#classe_id').html('<option value="">Erreur lors du chargement des classes</option>');
                    }
                });
            } else {
                $('#classe_id').html('<option value="">Sélectionner une classe</option>');
                debugLog('Données manquantes pour récupérer les classes');
            }
        });

        // Ajouter un parent (limité à 2 parents maximum)
        $('#add-parent').on('click', function(e) {
            debugLog('Bouton Ajouter parent cliqué');

            if (parentCount >= 2) {
                alert('Un maximum de 2 parents est autorisé.');
                return;
            }

            const index = parentCount;
            parentCount++;

            debugLog('Ajout d\'un nouveau parent avec index:', index);

            const parentHtml = `
                <div class="parent-item mb-4 p-3 border rounded">
                    <div class="d-flex justify-content-between mb-3">
                        <h6>Parent / Tuteur #${parentCount}</h6>
                        <div class="d-flex align-items-center">
                            <div class="form-check me-3">
                                <input class="form-check-input" type="checkbox" id="parent_existant_${index}" name="parent_existant[${index}]" value="1">
                                <label class="form-check-label" for="parent_existant_${index}">
                                    Choisir un parent existant
                                </label>
                            </div>
                            <button type="button" class="btn btn-sm btn-danger remove-parent">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>

                    <div class="parent-nouveau">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="parent_nom_${index}" class="form-label">Nom <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="parent_nom_${index}" name="parents[${index}][nom]">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="parent_prenoms_${index}" class="form-label">Prénom(s) <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="parent_prenoms_${index}" name="parents[${index}][prenoms]">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="parent_relation_${index}" class="form-label">Relation <span class="text-danger">*</span></label>
                                <select class="form-control" id="parent_relation_${index}" name="parents[${index}][relation]">
                                    <option value="">Sélectionner une relation</option>
                                    <option value="Père">Père</option>
                                    <option value="Mère">Mère</option>
                                    <option value="Tuteur">Tuteur</option>
                                    <option value="Autre">Autre</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="parent_telephone_${index}" class="form-label">Téléphone <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="parent_telephone_${index}" name="parents[${index}][telephone]" placeholder="+225 XX XX XXX XXX">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="parent_email_${index}" class="form-label">Email</label>
                                <input type="email" class="form-control" id="parent_email_${index}" name="parents[${index}][email]">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="parent_profession_${index}" class="form-label">Profession</label>
                                <input type="text" class="form-control" id="parent_profession_${index}" name="parents[${index}][profession]">
                            </div>
                            <div class="col-md-12 mb-3">
                                <label for="parent_adresse_${index}" class="form-label">Adresse</label>
                                <textarea class="form-control" id="parent_adresse_${index}" name="parents[${index}][adresse]" rows="2"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="parent-existant d-none">
                        <div class="row">
                            <div class="col-12 mb-3">
                                <label for="parent_id_${index}" class="form-label">Sélectionner un parent existant</label>
                                <select class="form-control select-parent" id="parent_id_${index}" name="parents[${index}][parent_id]" data-placeholder="Rechercher un parent...">
                                    <option value="">Rechercher un parent...</option>
                                </select>
                                <small class="form-text text-muted">Commencez à taper le nom, prénom ou téléphone du parent pour le rechercher</small>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            $('#parents-container').append(parentHtml);

            // Réinitialiser Select2 pour le nouveau parent
            $('#parent_id_' + index).select2({
                ajax: {
                    url: '{{ route("esbtp.api.search-parents") }}',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            q: params.term,
                            page: params.page
                        };
                    },
                    processResults: function (data, params) {
                        params.page = params.page || 1;

                        // Formatage des données pour Select2
                        const items = data.items.map(function(parent) {
                            return {
                                id: parent.id,
                                text: parent.nom + ' ' + parent.prenoms + ' (' + parent.telephone + ')'
                            };
                        });

                        return {
                            results: items,
                            pagination: {
                                more: data.pagination.more
                            }
                        };
                    },
                    cache: true
                },
                placeholder: 'Rechercher un parent...',
                minimumInputLength: 1,
                templateResult: formatParent,
                templateSelection: formatParentSelection
            });
        });

        // Supprimer un parent
        $(document).on('click', '.remove-parent', function() {
            debugLog('Bouton Supprimer parent cliqué');
            $(this).closest('.parent-item').remove();
            parentCount--;
        });

        // Déclencher le chargement initial des classes si toutes les valeurs sont définies
        if ($('#filiere_id').val() && $('#niveau_etude_id').val() && $('#annee_universitaire_id').val()) {
            $('#annee_universitaire_id').trigger('change');
        }
    });
</script>
@endsection

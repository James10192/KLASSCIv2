@extends('layouts.app')

@section('title', 'Test Bulletin Configurable')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Test du Système de Bulletin Configurable</h3>
                </div>
                <div class="card-body">
                    <!-- Test des paramètres -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Test des Paramètres</h5>
                                </div>
                                <div class="card-body">
                                    <button id="testParameters" class="btn btn-primary">
                                        <i class="fas fa-cog"></i> Tester les Paramètres
                                    </button>
                                    <div id="parametersResult" class="mt-3"></div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Statistiques des Paramètres</h5>
                                </div>
                                <div class="card-body">
                                    <div id="parametersStats">
                                        <p class="text-muted">Cliquez sur "Tester les Paramètres" pour voir les statistiques</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Formulaire de test de génération -->
                    <div class="card">
                        <div class="card-header">
                            <h5>Test de Génération de Bulletin</h5>
                        </div>
                        <div class="card-body">
                            <form id="bulletinTestForm">
                                @csrf
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="etudiant_id">ID Étudiant</label>
                                            <input type="number" class="form-control" id="etudiant_id" name="etudiant_id" value="1" required>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="classe_id">ID Classe</label>
                                            <input type="number" class="form-control" id="classe_id" name="classe_id" value="1" required>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="periode">Période</label>
                                            <select class="form-control" id="periode" name="periode" required>
                                                <option value="1er_trimestre">1er Trimestre</option>
                                                <option value="2eme_trimestre">2ème Trimestre</option>
                                                <option value="3eme_trimestre">3ème Trimestre</option>
                                                <option value="1er_semestre">1er Semestre</option>
                                                <option value="2eme_semestre">2ème Semestre</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="annee_universitaire_id">ID Année Universitaire</label>
                                            <input type="number" class="form-control" id="annee_universitaire_id" name="annee_universitaire_id" value="1" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <button type="button" id="previewBulletin" class="btn btn-info">
                                            <i class="fas fa-eye"></i> Prévisualiser
                                        </button>
                                    </div>
                                    <div class="col-md-6">
                                        <button type="button" id="generateBulletin" class="btn btn-success">
                                            <i class="fas fa-file-pdf"></i> Générer PDF
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Résultats des tests -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5>Résultats des Tests</h5>
                        </div>
                        <div class="card-body">
                            <div id="testResults">
                                <p class="text-muted">Les résultats des tests s'afficheront ici</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour la prévisualisation -->
<div class="modal fade" id="previewModal" tabindex="-1" role="dialog" aria-labelledby="previewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="previewModalLabel">Prévisualisation du Bulletin</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="previewContent">
                    <!-- Le contenu de la prévisualisation sera chargé ici -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Test des paramètres
    $('#testParameters').click(function() {
        const button = $(this);
        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Test en cours...');

        $.get('{{ route("test.bulletin.parameters") }}')
            .done(function(response) {
                if (response.success) {
                    $('#parametersResult').html(`
                        <div class="alert alert-success">
                            <h6><i class="fas fa-check"></i> ${response.message}</h6>
                            <p><strong>Nombre de paramètres:</strong> ${response.count}</p>
                        </div>
                    `);

                    // Afficher les statistiques
                    const settings = response.settings;
                    let displayCount = 0;
                    let functionalCount = 0;
                    let thresholdCount = 0;
                    let customizationCount = 0;
                    let optionCount = 0;

                    Object.keys(settings).forEach(key => {
                        if (key.includes('show_')) displayCount++;
                        else if (key.includes('auto_') || key.includes('require_') || key.includes('validate_')) functionalCount++;
                        else if (key.includes('threshold')) thresholdCount++;
                        else if (key.includes('custom') || key.includes('text')) customizationCount++;
                        else optionCount++;
                    });

                    $('#parametersStats').html(`
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Paramètres d'affichage:</strong> ${displayCount}</p>
                                <p><strong>Paramètres fonctionnels:</strong> ${functionalCount}</p>
                                <p><strong>Seuils de mention:</strong> ${thresholdCount}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Personnalisation:</strong> ${customizationCount}</p>
                                <p><strong>Options PDF:</strong> ${optionCount}</p>
                                <p><strong>Total:</strong> ${response.count}</p>
                            </div>
                        </div>
                    `);
                } else {
                    $('#parametersResult').html(`
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i> Erreur: ${response.error}
                        </div>
                    `);
                }
            })
            .fail(function(xhr) {
                const error = xhr.responseJSON ? xhr.responseJSON.error : 'Erreur de connexion';
                $('#parametersResult').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> Erreur: ${error}
                    </div>
                `);
            })
            .always(function() {
                button.prop('disabled', false).html('<i class="fas fa-cog"></i> Tester les Paramètres');
            });
    });

    // Prévisualisation du bulletin
    $('#previewBulletin').click(function() {
        const button = $(this);
        const formData = $('#bulletinTestForm').serialize();

        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Chargement...');

        $.get('{{ route("bulletin.configurable.preview") }}?' + formData)
            .done(function(response) {
                $('#previewContent').html(response);
                $('#previewModal').modal('show');

                $('#testResults').html(`
                    <div class="alert alert-success">
                        <i class="fas fa-check"></i> Prévisualisation générée avec succès
                    </div>
                `);
            })
            .fail(function(xhr) {
                const error = xhr.responseJSON ? xhr.responseJSON.error : 'Erreur de connexion';
                $('#testResults').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> Erreur de prévisualisation: ${error}
                    </div>
                `);
            })
            .always(function() {
                button.prop('disabled', false).html('<i class="fas fa-eye"></i> Prévisualiser');
            });
    });

    // Génération du PDF
    $('#generateBulletin').click(function() {
        const button = $(this);
        const formData = $('#bulletinTestForm').serialize();

        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Génération...');

        // Créer un formulaire temporaire pour le téléchargement
        const form = $('<form>', {
            method: 'POST',
            action: '{{ route("bulletin.configurable.generate") }}',
            target: '_blank'
        });

        // Ajouter le token CSRF
        form.append($('<input>', {
            type: 'hidden',
            name: '_token',
            value: $('meta[name="csrf-token"]').attr('content')
        }));

        // Ajouter les données du formulaire
        $('#bulletinTestForm').serializeArray().forEach(function(item) {
            form.append($('<input>', {
                type: 'hidden',
                name: item.name,
                value: item.value
            }));
        });

        // Soumettre le formulaire
        $('body').append(form);
        form.submit();
        form.remove();

        $('#testResults').html(`
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> Génération du PDF en cours... Le téléchargement devrait commencer automatiquement.
            </div>
        `);

        setTimeout(function() {
            button.prop('disabled', false).html('<i class="fas fa-file-pdf"></i> Générer PDF');
        }, 2000);
    });
});
</script>
@endsection

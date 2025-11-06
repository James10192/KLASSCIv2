<script>
    $(document).ready(function() {
        const form = $('form[data-places-info-target]').first();
        const placesTargetId = form.data('places-info-target');

        function updatePlacesInfo(classeId) {
            const target = placesTargetId ? $('#' + placesTargetId) : $();
            if (!target.length) {
                return;
            }

            if (!classeId) {
                target.text('Sélectionnez une classe pour voir les places disponibles.');
                target.removeClass('text-danger text-success').addClass('text-muted');
                return;
            }

            target.text('Chargement des places disponibles...');
            target.removeClass('text-danger text-success').addClass('text-muted');

            fetch(`/esbtp/classes/${classeId}/available-places`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('error');
                    }
                    return response.json();
                })
                .then(data => {
                    const available = data.available_places ?? 0;
                    const capacity = data.capacity ?? 0;
                    const isFull = Number(available) <= 0;
                    if (isFull) {
                        target.text(`Classe complète (${available}/${capacity} places disponibles)`);
                        target.removeClass('text-muted text-success').addClass('text-danger');
                    } else {
                        target.text(`Places disponibles : ${available}/${capacity}`);
                        target.removeClass('text-muted text-danger').addClass('text-success');
                    }
                })
                .catch(() => {
                    target.text('Impossible de récupérer les places disponibles.');
                    target.removeClass('text-success text-muted').addClass('text-danger');
                });
        }

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
            updatePlacesInfo($('#classe_id').val());
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

        const classeSelect = $('#classe_id');
        if (classeSelect.length) {
            classeSelect.on('change', function() {
                updatePlacesInfo($(this).val());
            });
            updatePlacesInfo(classeSelect.val());
        } else {
            const initialClasseId = $('input[name="classe_id"]').val();
            updatePlacesInfo(initialClasseId);
        }

        $('[data-embedded-toggle="form"]').on('click', function() {
            const targetId = $(this).data('target');
            const noticeId = $(this).data('notice');

            if (noticeId) {
                $('#' + noticeId).addClass('d-none');
            }

            if (targetId) {
                $('#' + targetId).removeClass('d-none');
            }
        });

        // Initialiser l'état au chargement
        toggleTransfertSection();
        toggleEtablissementOrigine();
    });
</script>

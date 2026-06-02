<script>
    $(document).ready(function() {
        const form = $('form[data-places-info-target]').first();
        const placesTargetId = form.data('places-info-target');

        // Déverrouille le select classe_id quand la case "Correction d'erreur de saisie" est cochée.
        // Avant : Alpine $watch sur document.querySelector ne fonctionne pas car DOM
        // n'est pas réactif. Pattern remplaçé par event listener natif.
        const correctionCheckbox = document.querySelector('input[name="correction_saisie"]');
        const classeSelect = document.getElementById('classe_id');
        if (correctionCheckbox && classeSelect && classeSelect.dataset.lockedByTc === '1') {
            const applyLock = () => {
                classeSelect.disabled = !correctionCheckbox.checked;
            };
            correctionCheckbox.addEventListener('change', applyLock);
            applyLock(); // état initial
        }

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

        // Section LMD-aware : récupère le mode courant + filtres LMD (mention/parcours).
        // En BTS : filtre par filiere_id + niveau_id (legacy)
        // En LMD : filtre par mention_id (optionnel) + parcours_id (optionnel) + niveau_id, scope LMD
        function getCurrentMode() {
            var section = document.getElementById('inscription-academic-section');
            return section ? (section.dataset.mode || 'unknown').toUpperCase() : 'UNKNOWN';
        }

        function getLmdFilters() {
            // Les pickers premium au-mention-picker / au-parcours-picker exposent un input
            // hidden name="lmd_filter_mention_id" / "lmd_filter_parcours_id" en interne.
            // Lecture directe du DOM via name pour découplage du composant Alpine.
            var mention = document.querySelector('input[name="lmd_filter_mention_id"]');
            var parcours = document.querySelector('input[name="lmd_filter_parcours_id"]');
            return {
                mention_id: mention ? mention.value : '',
                parcours_id: parcours ? parcours.value : '',
            };
        }

        // Filtrer les classes selon le mode + filtres
        function filterClasses() {
            var mode = getCurrentMode();
            var niveauId = $('#niveau_id').val();
            var currentClasseId = $('#classe_id').val();

            $('#classe_id').empty();
            $('#classe_id').append('<option value="">Sélectionner une classe</option>');

            if (!niveauId) {
                // Pas de niveau choisi : on remet toutes les classes (état "non filtré")
                allClassesOptions.each(function() {
                    $('#classe_id').append($(this).clone());
                });
                if (currentClasseId) $('#classe_id').val(currentClasseId);
                return;
            }

            if (mode === 'LMD') {
                var lmdFilters = getLmdFilters();
                allClassesOptions.each(function() {
                    var $option = $(this);
                    if ($option.val() === '') return;

                    var systeme = ($option.data('systeme') || 'BTS').toString().toUpperCase();
                    var optionNiveauId = $option.data('niveau-id');
                    var optionMentionId = $option.data('mention-id');
                    var optionParcoursId = $option.data('parcours-id');

                    if (systeme !== 'LMD') return;
                    if (optionNiveauId != niveauId) return;
                    if (lmdFilters.mention_id && optionMentionId != lmdFilters.mention_id) return;
                    if (lmdFilters.parcours_id && optionParcoursId != lmdFilters.parcours_id) return;

                    $('#classe_id').append($option.clone());
                });
            } else {
                // BTS classique : filtre filiere_id + niveau_id
                var filiereId = $('#filiere_id').val();
                allClassesOptions.each(function() {
                    var $option = $(this);
                    if ($option.val() === '') return;

                    var systeme = ($option.data('systeme') || 'BTS').toString().toUpperCase();
                    var optionFiliereId = $option.data('filiere-id');
                    var optionNiveauId = $option.data('niveau-id');

                    if (systeme === 'LMD') return; // exclure LMD du listing BTS
                    if (filiereId && optionFiliereId != filiereId) return;
                    if (optionNiveauId != niveauId) return;

                    $('#classe_id').append($option.clone());
                });
            }

            // Réselectionner la classe actuelle si toujours disponible
            if (currentClasseId && $('#classe_id option[value="' + currentClasseId + '"]').length > 0) {
                $('#classe_id').val(currentClasseId);
            }
        }

        // Switch BTS|LMD selon type du niveau choisi.
        function applyModeFromNiveau() {
            var niveauOption = document.querySelector('#niveau_id option:checked');
            if (!niveauOption) return;
            var niveauType = niveauOption.dataset.type || '';
            var newMode = (niveauType === 'Licence' || niveauType === 'Master' || niveauType === 'Doctorat') ? 'LMD' : (niveauType ? 'BTS' : '');

            var section = document.getElementById('inscription-academic-section');
            if (section) section.dataset.mode = newMode.toLowerCase() || 'unknown';

            var btsField = document.querySelector('.ins-bts-fieldset');
            var lmdField = document.querySelector('.ins-lmd-fieldset');
            if (btsField) {
                btsField.disabled = newMode !== 'BTS';
                btsField.style.display = newMode === 'BTS' ? '' : 'none';
            }
            if (lmdField) {
                lmdField.disabled = newMode !== 'LMD';
                lmdField.style.display = newMode === 'LMD' ? '' : 'none';
            }
            var badge = document.getElementById('ins-mode-badge');
            if (badge) {
                badge.textContent = newMode || '—';
                badge.style.background = newMode === 'LMD' ? '#0453cb' : (newMode === 'BTS' ? '#64748b' : '#cbd5e1');
            }
        }

        // Événements : Filière (BTS) OU Niveau → recalc mode + filterClasses.
        // Listener sur les pickers LMD via custom event 'mention:changed' (cf composant au-mention-picker).
        $('#filiere_id, #niveau_id').change(function() {
            applyModeFromNiveau();
            filterClasses();
            updatePlacesInfo($('#classe_id').val());
        });
        window.addEventListener('mention:changed', function() {
            filterClasses();
            updatePlacesInfo($('#classe_id').val());
        });
        // Le parcours picker n'a pas de custom event mais expose un input hidden — on watch via MutationObserver
        // léger sur l'input value (Alpine update via .value=). Fallback : input event.
        document.addEventListener('input', function(ev) {
            if (ev.target && (ev.target.name === 'lmd_filter_parcours_id' || ev.target.name === 'lmd_filter_mention_id')) {
                filterClasses();
                updatePlacesInfo($('#classe_id').val());
            }
        });

        // État initial
        applyModeFromNiveau();

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

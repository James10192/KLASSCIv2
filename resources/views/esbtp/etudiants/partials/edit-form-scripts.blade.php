<script>
    $(document).ready(function() {
        let formSubmitted = false;
        const form = $('#editEtudiantForm');
        form.on('submit', function(e) {
            if (formSubmitted) {
                e.preventDefault();
                return false;
            }
            formSubmitted = true;
            $(this).find('button[type="submit"]').prop('disabled', true);
        });

        $('input[type="file"]').on('change', function() {
            const maxSize = 2 * 1024 * 1024;
            if (this.files[0] && this.files[0].size > maxSize) {
                alert('La taille de la photo ne doit pas dépasser 2MB');
                this.value = '';
            }
        });

        if (typeof $.fn.select2 !== 'undefined') {
            $('#sexe, #statut').select2({
                theme: 'bootstrap4',
                minimumResultsForSearch: Infinity
            });

            const nationaliteSelect = $('#nationalite');
            if (nationaliteSelect.length) {
                const wasDisabled = nationaliteSelect.prop('disabled');
                nationaliteSelect.prop('disabled', false);
                nationaliteSelect.select2({
                    theme: 'bootstrap4',
                    placeholder: 'Sélectionner une nationalité',
                    allowClear: true
                });
                if (wasDisabled) {
                    nationaliteSelect.prop('disabled', true);
                }
            }
        }

        const maxParents = 2;
        const parentsContainer = $('#parents-container');
        const addParentGroup = $('#add-parent-group');

        function ensureEmptyState() {
            if (parentsContainer.find('.parent-card').length === 0) {
                if (parentsContainer.find('.alert').length === 0) {
                    parentsContainer.html(`
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Aucun parent enregistré pour le moment. Ajoutez un parent en utilisant les boutons ci-dessus.
                        </div>
                    `);
                }
            } else {
                parentsContainer.find('.alert').remove();
            }
        }

        function recalculateParentCount() {
            const count = parentsContainer.find('.parent-card').length;
            if (count >= maxParents) {
                addParentGroup.hide();
            } else {
                addParentGroup.show();
            }
        }

        ensureEmptyState();
        recalculateParentCount();

        $('#add-new-parent').on('click', function() {
            if (parentsContainer.find('.parent-card').length >= maxParents) {
                alert('Vous ne pouvez ajouter que 2 parents maximum.');
                return;
            }

            parentsContainer.find('#new-parent-card').remove();
            parentsContainer.append(createNewParentCard());
            ensureEmptyState();
            recalculateParentCount();
        });

        $(document).on('click', '.remove-parent', function() {
            const parentId = $(this).data('parent-id');
            const card = $(this).closest('.parent-card');

            if (parentId) {
                $('<input>', {
                    type: 'hidden',
                    name: 'delete_parents[]',
                    value: parentId
                }).appendTo(form);
            }

            card.remove();
            ensureEmptyState();
            recalculateParentCount();
        });

        // Ajouter un parent existant
        $(document).on('click', '.add-existing-parent-btn', function() {
            if (parentsContainer.find('.parent-card').length >= maxParents) {
                alert('Vous ne pouvez ajouter que 2 parents maximum.');
                return;
            }

            const parentData = {
                id: $(this).data('parent-id'),
                nom: $(this).data('parent-nom'),
                prenoms: $(this).data('parent-prenoms'),
                telephone: $(this).data('parent-telephone'),
                email: $(this).data('parent-email'),
                profession: $(this).data('parent-profession'),
                adresse: $(this).data('parent-adresse')
            };

            // Vérifier si le parent n'est pas déjà ajouté
            if (parentsContainer.find(`input[value="${parentData.id}"]`).length > 0) {
                alert('Ce parent est déjà ajouté à cet étudiant.');
                return;
            }

            const newIndex = parentsContainer.find('.parent-card').length;

            const existingParentCard = `
<div class="parent-card mb-4" data-parent-index="${newIndex}" style="background: #fff; border-radius: 12px; padding: 1.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border: 1px solid #e9ecef;">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h6 class="mb-1 text-primary" style="font-weight: 600;">
                <i class="fas fa-user-friends me-2"></i>Parent / Tuteur #${newIndex + 1}
            </h6>
            <small class="text-muted">${parentData.prenoms} ${parentData.nom}</small>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-success">Existant</span>
            <button type="button" class="btn btn-sm btn-outline-danger remove-parent" data-parent-id="${parentData.id}">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
    <div class="parent-card-body">
        <input type="hidden" name="existing_parents[]" value="${parentData.id}">
        <input type="hidden" name="existing_parents_relation[${parentData.id}]" value="Père" class="parent-relation-input">

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Nom</label>
                <input type="text" class="form-control" value="${parentData.nom}" readonly>
            </div>
            <div class="col-md-6">
                <label class="form-label">Prénom(s)</label>
                <input type="text" class="form-control" value="${parentData.prenoms}" readonly>
            </div>
            <div class="col-md-4">
                <label class="form-label">Relation <span class="text-danger">*</span></label>
                <select class="form-select parent-relation-select" data-parent-id="${parentData.id}" required>
                    <option value="Père">Père</option>
                    <option value="Mère">Mère</option>
                    <option value="Tuteur">Tuteur</option>
                    <option value="Autre">Autre</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Téléphone</label>
                <input type="text" class="form-control" value="${parentData.telephone}" readonly>
            </div>
            <div class="col-md-4">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" value="${parentData.email}" readonly>
            </div>
            <div class="col-md-6">
                <label class="form-label">Profession</label>
                <input type="text" class="form-control" value="${parentData.profession}" readonly>
            </div>
            <div class="col-md-6">
                <label class="form-label">Adresse</label>
                <textarea class="form-control" rows="1" readonly>${parentData.adresse}</textarea>
            </div>
        </div>
    </div>
</div>`;

            parentsContainer.append(existingParentCard);
            ensureEmptyState();
            recalculateParentCount();

            // Fermer le panel
            document.getElementById('searchParentModal').classList.remove('open');

            // Réinitialiser les champs de recherche
            document.getElementById('parent_search_query').value = '';
            document.getElementById('parent_search_filter').value = 'all';

            debugLog('Parent existant ajouté:', parentData);
        });

        // Mettre à jour la relation dans le input hidden quand le select change
        $(document).on('change', '.parent-relation-select', function() {
            const parentId = $(this).data('parent-id');
            const relation = $(this).val();
            $(this).closest('.parent-card').find('.parent-relation-input').val(relation);
        });

        // Fonction pour créer une nouvelle carte parent
        function createNewParentCard() {
            return `
<div class="parent-card mb-4" id="new-parent-card" data-parent-index="new" style="background: #fff; border-radius: 12px; padding: 1.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border: 1px solid #e9ecef;">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h6 class="mb-1 text-primary" style="font-weight: 600;">
                <i class="fas fa-user-friends me-2"></i>Nouveau parent / tuteur
            </h6>
            <small class="text-muted">Renseignez les informations du représentant.</small>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-sm btn-outline-danger remove-parent">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
    <div class="parent-card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Nom <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="new_parent[nom]" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Prénom(s) <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="new_parent[prenoms]" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Relation <span class="text-danger">*</span></label>
                <select class="form-select" name="new_parent[relation]" required>
                    <option value="">Sélectionner une relation</option>
                    <option value="Père">Père</option>
                    <option value="Mère">Mère</option>
                    <option value="Tuteur">Tuteur</option>
                    <option value="Autre">Autre</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Téléphone <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="new_parent[telephone]" placeholder="+225 XX XX XXX XXX" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" name="new_parent[email]">
            </div>
            <div class="col-md-6">
                <label class="form-label">Profession</label>
                <input type="text" class="form-control" name="new_parent[profession]">
            </div>
            <div class="col-md-6">
                <label class="form-label">Adresse</label>
                <textarea class="form-control" name="new_parent[adresse]" rows="1"></textarea>
            </div>
        </div>
    </div>
</div>`;
        }

        // === GESTION DES PARENTS EXISTANTS (Style class-selector) ===
        let allParents = [];
        let currentParentSort = { column: null, direction: 'asc' };

        // Fonction pour charger tous les parents
        function loadParents() {
            const tableBody = document.getElementById('parents-table-body');
            tableBody.innerHTML = '<tr><td colspan="5" class="text-center">Chargement...</td></tr>';

            fetch('{{ route("esbtp.parents.search") }}?q=&etudiant_id={{ $etudiant->id }}')
                .then(response => response.json())
                .then(parents => {
                    allParents = parents;
                    displayParentsTable(allParents);
                })
                .catch(error => {
                    debugError('Error loading parents:', error);
                    tableBody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Erreur lors du chargement des parents.</td></tr>';
                });
        }

        // Fonction pour afficher les parents dans le tableau
        function displayParentsTable(parents) {
            const tableBody = document.getElementById('parents-table-body');
            tableBody.innerHTML = '';

            if (parents.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Aucun parent trouvé</td></tr>';
                return;
            }

            parents.forEach(parent => {
                const enfants = parent.etudiants && parent.etudiants.length > 0
                    ? parent.etudiants.map(e => `${e.prenoms} ${e.nom}`).join(', ')
                    : '<em class="text-muted">Aucun</em>';

                tableBody.innerHTML += `<tr>
                    <td>${parent.nom || ''}</td>
                    <td>${parent.prenoms || ''}</td>
                    <td>${parent.telephone || 'Non renseigné'}</td>
                    <td>${enfants}</td>
                    <td>
                        <button type="button" class="btn btn-sm btn-primary add-existing-parent-btn"
                                data-parent-id="${parent.id}"
                                data-parent-nom="${parent.nom || ''}"
                                data-parent-prenoms="${parent.prenoms || ''}"
                                data-parent-telephone="${parent.telephone || ''}"
                                data-parent-email="${parent.email || ''}"
                                data-parent-profession="${parent.profession || ''}"
                                data-parent-adresse="${parent.adresse || ''}">
                            Sélectionner
                        </button>
                    </td>
                </tr>`;
            });
        }

        // Fonction pour filtrer les parents
        function filterParents() {
            const filterType = document.getElementById('parent_search_filter').value;
            const query = document.getElementById('parent_search_query').value.toLowerCase();

            let filteredParents = allParents;

            if (query) {
                filteredParents = filteredParents.filter(parent => {
                    switch (filterType) {
                        case 'nom':
                            return (parent.nom || '').toLowerCase().includes(query);
                        case 'prenoms':
                            return (parent.prenoms || '').toLowerCase().includes(query);
                        case 'telephone':
                            return (parent.telephone || '').toLowerCase().includes(query);
                        default: // 'all'
                            return (parent.nom || '').toLowerCase().includes(query) ||
                                   (parent.prenoms || '').toLowerCase().includes(query) ||
                                   (parent.telephone || '').toLowerCase().includes(query);
                    }
                });
            }

            if (currentParentSort.column) {
                sortParents(filteredParents, currentParentSort.column, currentParentSort.direction);
            } else {
                displayParentsTable(filteredParents);
            }
        }

        // Fonction pour trier les parents
        function sortParents(parents, column, direction) {
            const sortedParents = [...parents].sort((a, b) => {
                let aValue = a[column] || '';
                let bValue = b[column] || '';

                if (direction === 'asc') {
                    return aValue.localeCompare(bValue);
                } else {
                    return bValue.localeCompare(aValue);
                }
            });

            displayParentsTable(sortedParents);
            updateParentSortIcons(column, direction);
        }

        // Fonction pour mettre à jour les icônes de tri
        function updateParentSortIcons(activeColumn, direction) {
            document.querySelectorAll('.sortable-parent i').forEach(icon => {
                icon.className = 'fas fa-sort text-muted';
            });

            const activeHeader = document.querySelector(`[data-column="${activeColumn}"] i`);
            if (activeHeader) {
                if (direction === 'asc') {
                    activeHeader.className = 'fas fa-sort-up text-primary';
                } else {
                    activeHeader.className = 'fas fa-sort-down text-primary';
                }
            }
        }

        // Event listeners pour les filtres et la recherche
        document.getElementById('parent_search_query').addEventListener('input', filterParents);
        document.getElementById('parent_search_filter').addEventListener('change', filterParents);

        // Event listeners pour le tri
        document.querySelectorAll('.sortable-parent').forEach(header => {
            header.addEventListener('click', function() {
                const column = this.getAttribute('data-column');
                let direction = 'asc';

                if (currentParentSort.column === column) {
                    direction = currentParentSort.direction === 'asc' ? 'desc' : 'asc';
                }

                currentParentSort = { column, direction };

                const filterType = document.getElementById('parent_search_filter').value;
                const query = document.getElementById('parent_search_query').value.toLowerCase();

                let filteredParents = allParents;

                if (query) {
                    filteredParents = filteredParents.filter(parent => {
                        switch (filterType) {
                            case 'nom':
                                return (parent.nom || '').toLowerCase().includes(query);
                            case 'prenoms':
                                return (parent.prenoms || '').toLowerCase().includes(query);
                            case 'telephone':
                                return (parent.telephone || '').toLowerCase().includes(query);
                            default:
                                return (parent.nom || '').toLowerCase().includes(query) ||
                                       (parent.prenoms || '').toLowerCase().includes(query) ||
                                       (parent.telephone || '').toLowerCase().includes(query);
                        }
                    });
                }

                sortParents(filteredParents, column, direction);
            });
        });

        // Charger les parents quand le panel s'ouvre
        var existingBtn = document.getElementById('add-existing-parent');
        if (existingBtn) {
            existingBtn.addEventListener('click', function() {
                loadParents();
            });
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

        // ========================
        // GESTION DES MATRICULES
        // ========================

        const matriculeInput = document.getElementById('matriculeInput');
        const matriculeContainer = document.getElementById('matriculeContainer');
        const generateBtn = document.getElementById('generateMatriculeBtn');
        const checkBtn = document.getElementById('checkMatriculeBtn');
        const matriculeStatus = document.getElementById('matriculeStatus');
        const matriculeMode = document.getElementById('matriculeMode');
        const matriculeHelp = document.getElementById('matriculeHelp');
        const genreSelect = document.getElementById('sexe');
        const initialGenre = genreSelect ? genreSelect.value : null;

        // Charger le mode de génération des matricules
        let currentMatriculeMode = 'automatique'; // Par défaut

        // Récupérer les infos de niveau/filière depuis l'inscription la plus récente (passées par le controller)
        const niveauEtudeCodeFromInscription = @json($niveauEtudeCode ?? null);
        const filiereIdFromInscription = @json($filiereIdForMatricule ?? null);

        // DEBUG CONSOLE - À SUPPRIMER APRÈS DIAGNOSTIC
        console.log('=== DEBUG MATRICULE EDIT ===');
        console.log('niveauEtudeCodeFromInscription:', niveauEtudeCodeFromInscription);
        console.log('filiereIdFromInscription:', filiereIdFromInscription);
        console.log('Variables PHP brutes - $niveauEtudeCode:', '@php echo json_encode($niveauEtudeCode ?? "UNDEFINED"); @endphp');
        console.log('Variables PHP brutes - $filiereIdForMatricule:', '@php echo json_encode($filiereIdForMatricule ?? "UNDEFINED"); @endphp');
        console.log('Variables PHP brutes - $inscriptionRecente:', '@php echo isset($inscriptionRecente) && $inscriptionRecente ? "ID=" . $inscriptionRecente->id : "NON DEFINI"; @endphp');
        console.log('============================');

        // Initialiser niveauConfig directement si on a les infos depuis l'inscription la plus récente
        let niveauConfig = niveauEtudeCodeFromInscription ? { code: niveauEtudeCodeFromInscription } : null;
        console.log('niveauConfig initialisé:', niveauConfig);

        fetch('/esbtp/matricule-config/mode-info', {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            currentMatriculeMode = data.mode || 'automatique';
            updateMatriculeUI();
            // Vérifier le statut du niveau config après avoir mis à jour l'UI
            checkNiveauConfigStatus();
        })
        .catch(error => {
            console.error('Erreur lors du chargement du mode matricule:', error);
            updateMatriculeUI();
            checkNiveauConfigStatus();
        });

        function updateMatriculeUI() {
            if (!authUserIsSuperAdmin) {
                // Pour les non superAdmin, toujours en mode lecture seule
                if (matriculeContainer) {
                    matriculeContainer.style.display = 'block';
                }
                matriculeInput.readOnly = true;
                matriculeHelp.textContent = 'Le matricule ne peut pas être modifié';
                return;
            }

            if (currentMatriculeMode === 'automatique') {
                // MODE AUTO : Afficher avec bouton génération
                if (matriculeContainer) {
                    matriculeContainer.style.display = 'block';
                }
                matriculeMode.textContent = 'AUTO';
                matriculeMode.className = 'badge bg-success ms-1';
                matriculeHelp.textContent = 'Généré automatiquement selon genre et classe';
                generateBtn.style.display = 'inline-flex';
                checkBtn.style.display = 'none';
                matriculeInput.readOnly = true;
                matriculeInput.placeholder = 'Auto-généré';
            } else {
                // MODE MANUEL : Afficher avec vérification
                if (matriculeContainer) {
                    matriculeContainer.style.display = 'block';
                }
                matriculeMode.textContent = 'MANUEL';
                matriculeMode.className = 'badge bg-warning ms-1';
                matriculeHelp.textContent = 'Saisissez manuellement le matricule (vérification anti-doublon)';
                generateBtn.style.display = 'none';
                checkBtn.style.display = 'inline-flex';
                matriculeInput.readOnly = false;
                matriculeInput.placeholder = 'Ex: MAT25-0001';
            }
        }

        // Vérification d'accès superAdmin
        const authUserIsSuperAdmin = @json(auth()->user()->can('admin.access'));

        if (authUserIsSuperAdmin && generateBtn) {
            generateBtn.addEventListener('click', function() {
                const genre = genreSelect ? genreSelect.value : null;

                if (!genre) {
                    showMatriculeStatus('Veuillez d\'abord sélectionner le genre/sexe', 'warning');
                    return;
                }

                if (!niveauConfig) {
                    showMatriculeStatus('Niveau d\'études non configuré. Contactez l\'équipe technique.', 'danger');
                    return;
                }

                generateBtn.disabled = true;
                generateBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Génération...';

                fetch('/esbtp/matricule-config/generate', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        niveau_etude_code: niveauConfig.code,
                        genre: genre,
                        annee: new Date().getFullYear()
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        matriculeInput.value = data.matricule;
                        showMatriculeStatus('Matricule généré avec succès', 'success');
                    } else {
                        showMatriculeStatus(data.message || 'Erreur lors de la génération', 'danger');
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    showMatriculeStatus('Erreur de connexion', 'danger');
                })
                .finally(() => {
                    generateBtn.disabled = false;
                    generateBtn.innerHTML = '<i class="fas fa-magic"></i> Générer';
                });
            });
        }

        if (authUserIsSuperAdmin && checkBtn) {
            checkBtn.addEventListener('click', checkMatriculeManuel);

            // Vérification en temps réel pour le mode manuel
            if (currentMatriculeMode === 'manuel') {
                let checkTimeout;
                matriculeInput.addEventListener('input', function() {
                    clearTimeout(checkTimeout);
                    if (this.value.length >= 3) {
                        checkTimeout = setTimeout(checkMatriculeManuel, 500);
                    }
                });
            }
        }

        function checkMatriculeManuel() {
            const matricule = matriculeInput.value.trim();

            if (!matricule) {
                showMatriculeStatus('', '');
                return;
            }

            checkBtn.disabled = true;
            checkBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

            fetch('/esbtp/matricule-config/check', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ matricule: matricule })
            })
            .then(response => response.json())
            .then(data => {
                if (data.exists) {
                    showMatriculeStatus('❌ Ce matricule existe déjà', 'danger');
                } else {
                    showMatriculeStatus('✅ Matricule disponible', 'success');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showMatriculeStatus('Erreur de vérification', 'warning');
            })
            .finally(() => {
                checkBtn.disabled = false;
                checkBtn.innerHTML = '<i class="fas fa-search"></i> Vérifier';
            });
        }

        function showMatriculeStatus(message, type) {
            if (!matriculeStatus) return;
            
            if (!message) {
                matriculeStatus.innerHTML = '';
                return;
            }

            const alertClass = {
                'success': 'alert-success',
                'danger': 'alert-danger',
                'warning': 'alert-warning',
                'info': 'alert-info'
            }[type] || 'alert-info';

            matriculeStatus.innerHTML = `<small class="alert ${alertClass} p-1 m-0">${message}</small>`;
        }

        // Vérifier si le niveau est configuré pour la génération automatique
        // (niveauConfig est déjà initialisé depuis les données Blade de l'inscription récente)
        function checkNiveauConfigStatus() {
            // Si l'étudiant a déjà un matricule, pas besoin de warning
            const hasExistingMatricule = matriculeInput && matriculeInput.value && matriculeInput.value.trim() !== '';

            if (!niveauConfig && currentMatriculeMode === 'automatique' && authUserIsSuperAdmin) {
                if (hasExistingMatricule) {
                    // L'étudiant a déjà un matricule, on peut le régénérer manuellement si besoin
                    showMatriculeStatus('ℹ️ Matricule existant. Cliquez sur "Générer" pour en créer un nouveau.', 'info');
                } else {
                    // Pas de matricule et pas d'inscription trouvée
                    showMatriculeStatus('⚠️ Aucune inscription trouvée - génération automatique impossible', 'warning');
                }
                if (generateBtn) generateBtn.disabled = !hasExistingMatricule; // Permettre si matricule existe
            } else if (niveauConfig) {
                showMatriculeStatus('', '');
                if (generateBtn && authUserIsSuperAdmin) generateBtn.disabled = false;
            }
        }

        // Vérifier le statut au démarrage (après le chargement du mode)
        // Note: On appelle cette fonction après avoir récupéré le mode matricule

        function maybeAutoRegenerateMatricule(force) {
            if (!authUserIsSuperAdmin) {
                return;
            }

            if (currentMatriculeMode !== 'automatique') {
                return;
            }

            const genre = genreSelect ? genreSelect.value : null;
            if (!genre || !niveauConfig) {
                return;
            }

            if (force) {
                generateMatriculeAuto();
                return;
            }

            if (!matriculeInput.value) {
                generateMatriculeAuto();
            }
        }

        // Écouter les changements de genre pour le mode auto
        // (pas besoin d'écouter la classe car on utilise les infos de l'inscription récente)
        if (genreSelect && authUserIsSuperAdmin) {
            genreSelect.addEventListener('change', function() {
                // Vérifier si le genre a changé et régénérer si nécessaire
                if (genreSelect.value !== initialGenre) {
                    maybeAutoRegenerateMatricule(true);
                }
            });
        }

        async function generateMatriculeAuto() {
            const genre = genreSelect ? genreSelect.value : null;

            if (!genre) {
                console.log('Genre non renseigné pour la génération auto');
                return null;
            }

            if (!niveauConfig) {
                console.log('Niveau config non trouvé pour la génération auto');
                return null;
            }

            try {
                const response = await fetch('/esbtp/matricule-config/generate', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        niveau_etude_code: niveauConfig.code,
                        genre: genre,
                        annee: new Date().getFullYear()
                    })
                });

                const data = await response.json();

                if (data.success && data.matricule) {
                    console.log('Matricule généré avec succès:', data.matricule);
                    matriculeInput.value = data.matricule;
                    showMatriculeStatus('Matricule généré avec succès', 'success');
                    return data.matricule;
                } else {
                    console.error('Erreur lors de la génération:', data.message || 'Erreur inconnue');
                    showMatriculeStatus(data.message || 'Erreur lors de la génération', 'danger');
                    return null;
                }
            } catch (error) {
                console.error('Erreur réseau lors de la génération du matricule:', error);
                showMatriculeStatus('Erreur de connexion', 'danger');
                return null;
            }
        }

        // Si mode auto et genre déjà sélectionné, générer le matricule au chargement
        // Note: niveauConfig est déjà initialisé depuis les données Blade de l'inscription récente
        $(document).ready(function() {
            if (currentMatriculeMode === 'automatique' && authUserIsSuperAdmin) {
                // Vérifier le statut du niveau config
                checkNiveauConfigStatus();

                // Si on a les infos nécessaires et pas de matricule, générer automatiquement
                if (genreSelect && genreSelect.value && !matriculeInput.value && niveauConfig) {
                    setTimeout(() => {
                        generateMatriculeAuto();
                    }, 100);
                }
            }
        });

    }); // FIN du $(document).ready()
</script>

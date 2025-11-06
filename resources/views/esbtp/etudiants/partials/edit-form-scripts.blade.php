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

            // Fermer la modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('searchParentModal'));
            if (modal) {
                modal.hide();
            }

            // Réinitialiser les champs de recherche
            document.getElementById('parent_search_query').value = '';
            document.getElementById('parent_search_filter').value = 'all';

            console.log('Parent existant ajouté:', parentData);
        });

        // Mettre à jour la relation dans le input hidden quand le select change
        $(document).on('change', '.parent-relation-select', function() {
            const parentId = $(this).data('parent-id');
            const relation = $(this).val();
            $(this).closest('.parent-card').find('.parent-relation-input').val(relation);
        });
    });

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
                console.error('Error loading parents:', error);
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

});

// Charger les parents quand le modal s'ouvre
document.getElementById('searchParentModal').addEventListener('show.bs.modal', function() {
    loadParents();
});
</script>

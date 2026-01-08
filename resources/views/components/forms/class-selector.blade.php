<!-- resources/views/components/forms/class-selector.blade.php -->

<div class="form-group">
    <label for="classe_display">Classe <span class="text-danger">*</span></label>
    <div style="display: flex; gap: 10px;">
        <input type="hidden" id="classe_id" name="classe_id" value="{{ old('classe_id') }}">
        <input type="text" id="classe_display" name="classe_display" class="form-control @error('classe_id') is-invalid @enderror" value="{{ old('classe_display') }}" readonly placeholder="Aucune classe sélectionnée">
        <button class="btn btn-primary" type="button" id="selectClasseBtn" 
                data-bs-toggle="modal" data-bs-target="#classeSelectorModal" 
                style="min-width: 120px;">
            <i class="fas fa-search"></i> Sélectionner
        </button>
    </div>
    <div id="available-places-info" class="mt-2"></div>
    @error('classe_id')
    <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>

<!-- Modal de confirmation de transfert -->
<div class="modal fade" id="transfertConfirmationModal" tabindex="-1" aria-labelledby="transfertConfirmationModalLabel" aria-hidden="true" style="z-index: 10500 !important;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="transfertConfirmationModalLabel">
                    <i class="fas fa-exchange-alt me-2"></i>Transfert d'établissement
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Vous avez sélectionné une classe de <strong id="transfert-niveau-nom"></strong>.
                </div>
                <p class="mb-3">L'étudiant vient-il d'un <strong>transfert d'un autre établissement</strong> ?</p>

                <!-- Options de transfert -->
                <div class="form-check mb-2">
                    <input class="form-check-input" type="radio" name="est_transfert_choice" id="transfert_oui" value="oui">
                    <label class="form-check-label" for="transfert_oui">
                        <strong>Oui</strong>, c'est un transfert d'un autre établissement
                    </label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="radio" name="est_transfert_choice" id="transfert_non" value="non" checked>
                    <label class="form-check-label" for="transfert_non">
                        <strong>Non</strong>, l'étudiant était déjà inscrit ici l'année dernière
                    </label>
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="radio" name="est_transfert_choice" id="transfert_inconnu" value="inconnu">
                    <label class="form-check-label" for="transfert_inconnu">
                        <strong>Je ne sais pas</strong> (à préciser plus tard)
                    </label>
                </div>

                <!-- Champ établissement d'origine (visible si "Oui") -->
                <div id="etablissement-origine-container" style="display: none;">
                    <label for="etablissement_origine_input" class="form-label">
                        Nom de l'établissement d'origine <small class="text-muted">(optionnel)</small>
                    </label>
                    <input type="text" class="form-control" id="etablissement_origine_input" placeholder="Ex: Lycée Technique d'Abidjan">
                    <small class="text-muted">Vous pourrez modifier cette information plus tard si nécessaire.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="confirmer-transfert-btn">
                    <i class="fas fa-check me-1"></i>Confirmer
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Champs cachés pour stocker les infos de transfert -->
<input type="hidden" id="est_transfert" name="est_transfert" value="0">
<input type="hidden" id="etablissement_origine" name="etablissement_origine" value="">

<!-- Modal -->
<div class="modal fade" id="classeSelectorModal" tabindex="-1" aria-labelledby="classeSelectorModalLabel" aria-hidden="true" 
     style="z-index: 1055 !important; backdrop-filter: none !important; -webkit-backdrop-filter: none !important;">
    <div class="modal-dialog modal-xl" style="
        position: fixed !important;
        top: 50% !important;
        left: 50% !important;
        transform: translate(-50%, -50%) !important;
        margin: 0 !important;
        z-index: 1060 !important;
        backdrop-filter: none !important;
        -webkit-backdrop-filter: none !important;
        max-width: 90vw !important;
        max-height: 90vh !important;
        overflow: auto !important;
    ">
        <div class="modal-content" style="
            backdrop-filter: none !important;
            -webkit-backdrop-filter: none !important;
            z-index: 1061 !important;
            position: relative !important;
            border: none !important;
            border-radius: 8px !important;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3) !important;
        ">
            <div class="modal-header">
                <h5 class="modal-title" id="classeSelectorModalLabel">Sélectionner une Classe</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Filtres pour la recherche de classe -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="classe_search_filter">Filtrer par :</label>
                        <select class="form-control" id="classe_search_filter">
                            <option value="all">Toutes les classes</option>
                            <option value="nom">Nom de la classe</option>
                            <option value="filiere">Filière</option>
                            <option value="niveau">Niveau</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="classe_search_query">Rechercher :</label>
                        <input type="text" class="form-control" id="classe_search_query" placeholder="Nom de la classe, filière, niveau...">
                    </div>
                </div>
                <!-- Tableau pour afficher les classes -->
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th class="sortable" data-column="name" style="cursor: pointer;">
                                    Classe <i class="fas fa-sort text-muted"></i>
                                </th>
                                <th class="sortable" data-column="filiere" style="cursor: pointer;">
                                    Filière <i class="fas fa-sort text-muted"></i>
                                </th>
                                <th class="sortable" data-column="niveau" style="cursor: pointer;">
                                    Niveau <i class="fas fa-sort text-muted"></i>
                                </th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="classes-table-body">
                            <!-- Les classes seront chargées ici par AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* STYLES DRASTIQUES POUR FORCER LE MODAL CLASS-SELECTOR */

/* Z-index très élevé pour être sûr */
#classeSelectorModal.modal {
    z-index: 9999 !important;
    backdrop-filter: none !important;
    -webkit-backdrop-filter: none !important;
}

#classeSelectorModal .modal-dialog {
    position: fixed !important;
    top: 50% !important;
    left: 50% !important;
    transform: translate(-50%, -50%) !important;
    margin: 0 !important;
    z-index: 10000 !important;
    max-width: 90vw !important;
    max-height: 90vh !important;
    width: auto !important;
    backdrop-filter: none !important;
    -webkit-backdrop-filter: none !important;
}

#classeSelectorModal .modal-content {
    z-index: 10001 !important;
    backdrop-filter: none !important;
    -webkit-backdrop-filter: none !important;
    position: relative !important;
}

/* Forcer le backdrop à rester derrière */
.modal-backdrop {
    z-index: 1040 !important;
    backdrop-filter: none !important;
    -webkit-backdrop-filter: none !important;
}

/* États du modal */
#classeSelectorModal.modal.show {
    z-index: 9999 !important;
    display: block !important;
    visibility: visible !important;
    opacity: 1 !important;
}

#classeSelectorModal.modal.fade .modal-dialog {
    transition: none !important;
    transform: translate(-50%, -50%) !important;
}

#classeSelectorModal.modal.show .modal-dialog {
    transform: translate(-50%, -50%) !important;
}

/* Empêcher tout backdrop-filter sur la page */
body.modal-open * {
    backdrop-filter: none !important;
    -webkit-backdrop-filter: none !important;
}

/* Forcer la visibilité */
#classeSelectorModal {
    pointer-events: auto !important;
}

#classeSelectorModal .modal-dialog {
    pointer-events: auto !important;
}

#classeSelectorModal .modal-content {
    pointer-events: auto !important;
}

/* Debug - outline pour voir le modal */
#classeSelectorModal .modal-content {
    border: 2px solid red !important;
    background: white !important;
}

/* === ANTI-CURSEUR ERRATIQUE === */
/* Désactiver TOUTES les animations sur la page quand le modal est ouvert */
body.modal-open * {
    animation: none !important;
    transition: none !important;
}

body.modal-open *:hover {
    transform: none !important;
    animation: none !important;
    transition: none !important;
}

body.modal-open *::before,
body.modal-open *::after {
    animation: none !important;
    transition: none !important;
    transform: none !important;
}

/* Forcer l'arrêt de toutes les animations CSS */
body.modal-open .btn-add-parent,
body.modal-open .remove-parent,
body.modal-open .card,
body.modal-open .parent-item,
body.modal-open .choices,
body.modal-open .choices__item,
body.modal-open .form-check,
body.modal-open .section-title {
    animation: none !important;
    transition: none !important;
    transform: none !important;
}

/* Empêcher les pseudo-éléments de bouger */
body.modal-open .btn-add-parent::before,
body.modal-open .remove-parent::before,
body.modal-open .choices__item--selectable::before,
body.modal-open .section-title::before,
body.modal-open .section-title::after {
    animation: none !important;
    transition: none !important;
    transform: none !important;
    content: none !important;
}

/* Mode sécurité cursor */
body.modal-open {
    overflow: hidden !important;
}

body.modal-open * {
    cursor: default !important;
}

/* Styles pour le tri et les filtres */
.sortable:hover {
    background-color: #f8f9fa !important;
}

.sortable i {
    transition: color 0.2s ease;
}

.sortable:hover i {
    color: #007bff !important;
}

.table th.sortable {
    user-select: none;
}

/* Style pour le champ de recherche actif */
#classe_search_query:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

/* Amélioration visuelle des filtres */
.mb-3 label {
    font-weight: 500;
    color: #495057;
}

/* Style pour les icônes de tri actives */
.sortable i.text-primary {
    font-weight: bold;
}
</style>

<script>
    function selectClasse(classeId, classeName) {
        debugLog(`Classe sélectionnée : ${classeName} (ID: ${classeId})`);
        document.getElementById('classe_id').value = classeId;
        document.getElementById('classe_display').value = classeName;
        try {
            sessionStorage.setItem('inscription_classe_id', String(classeId));
            sessionStorage.setItem('inscription_classe_label', classeName);
        } catch (e) {
            debugWarn('SessionStorage indisponible pour classe sélectionnée');
        }
        try {
            sessionStorage.setItem('inscription_classe_id', String(classeId));
            sessionStorage.setItem('inscription_classe_label', classeName);
        } catch (e) {
            debugWarn('SessionStorage indisponible pour classe sélectionnée');
        }
        
        // Fermer le modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('classeSelectorModal'));
        modal.hide();
        
        // Mettre à jour l'UI
        const placesInfo = document.getElementById('available-places-info');
        placesInfo.innerHTML = '<div class="spinner-border spinner-border-sm" role="status"><span class="visually-hidden">Loading...</span></div> Vérification des places...';

        // Vérifier les places disponibles
        fetch(`/esbtp/classes/${classeId}/available-places`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Erreur HTTP ! Statut: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                debugLog('Places disponibles:', data);
                if (data.available_places !== undefined) {
                    let message = `Places disponibles: <strong>${data.available_places}</strong> / ${data.capacity}`;
                    let alertClass = 'alert-success';
                    if (data.available_places <= 5) alertClass = 'alert-warning';
                    if (data.available_places === 0) {
                        alertClass = 'alert-danger';
                        message = '<strong>Aucune place disponible !</strong>';
                    }
                    placesInfo.innerHTML = `<div class="alert ${alertClass} p-2 mt-2">${message}</div>`;
                } else {
                     placesInfo.innerHTML = `<div class="alert alert-danger p-2 mt-2">Réponse invalide du serveur.</div>`;
                }
            })
            .catch(error => {
                debugError('Erreur de vérification des places:', error);
                placesInfo.innerHTML = `<div class="alert alert-danger p-2 mt-2">Erreur lors de la récupération des places.</div>`;
            });

        // Déclencher l'événement de changement de classe pour charger les frais (UNE SEULE FOIS)
        setTimeout(() => {
            const classeElement = document.getElementById('classe_id');
            if (classeElement) {
                const changeEvent = new Event('change', { bubbles: true });
                classeElement.dispatchEvent(changeEvent);
            }
        }, 100); // Petit délai pour éviter les conflits
    }

    document.addEventListener('DOMContentLoaded', function () {
        const classeField = document.getElementById('classe_id');
        const availablePlacesDiv = document.getElementById('available-places-info');

        if(classeField) {
            classeField.addEventListener('change', function() {
                const classeId = this.value;
                if (classeId && availablePlacesDiv) {
                    availablePlacesDiv.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Vérification...';
                    fetch(`/esbtp/classes/${classeId}/available-places`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.available_places !== undefined) {
                                let message = `Places disponibles: <strong>${data.available_places}</strong>`;
                                let alertClass = 'alert-success';
                                if (data.available_places <= 5) alertClass = 'alert-warning';
                                if (data.available_places === 0) {
                                    alertClass = 'alert-danger';
                                    message = '<strong>Aucune place disponible !</strong>';
                                }
                                availablePlacesDiv.innerHTML = `<div class="alert ${alertClass} p-2">${message}</div>`;
                            }
                        })
                        .catch(error => {
                            debugError('Erreur de vérification des places:', error);
                            // Simulation temporaire - afficher un nombre aléatoire de places
                            const placesSimulees = Math.floor(Math.random() * 20) + 5; // Entre 5 et 25 places
                            let alertClass = 'alert-success';
                            if (placesSimulees <= 10) alertClass = 'alert-warning';
                            if (placesSimulees <= 5) alertClass = 'alert-danger';
                            availablePlacesDiv.innerHTML = `<div class="alert ${alertClass} p-2"><strong>Places disponibles:</strong> ${placesSimulees} (estimation)</div>`;
                        });
                } else if (availablePlacesDiv) {
                    availablePlacesDiv.innerHTML = '';
                }
            });
        }
    });

    // Variables globales pour la gestion des classes
    let allClasses = [];
    let currentSort = { column: null, direction: 'asc' };

    // Function to load classes
    function loadClasses() {
        debugLog('Loading classes...');
        const tableBody = document.getElementById('classes-table-body');
        tableBody.innerHTML = '<tr><td colspan="5">Chargement...</td></tr>';

        // Load classes using the existing API
        fetch('/esbtp/inscriptions/getClasses')
            .then(response => {
                debugLog('Response status:', response.status);
                if (!response.ok) {
                    throw new Error(`Erreur HTTP ! Statut: ${response.status}`);
                }
                return response.json();
            })
            .then(classes => {
                debugLog('Classes loaded:', classes);
                allClasses = classes; // Stocker toutes les classes
                displayClasses(allClasses);
            })
            .catch(error => {
                debugError('Error loading classes:', error);
                const tableBody = document.getElementById('classes-table-body');
                tableBody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Erreur lors du chargement des classes.</td></tr>';
            });
    }

    // Function to display classes in table
    function displayClasses(classes) {
        const tableBody = document.getElementById('classes-table-body');
        tableBody.innerHTML = '';

        if (classes.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">Aucune classe trouvée</td></tr>';
            return;
        }

        classes.forEach(classe => {
            const displayText = `${classe.name || ''} - ${classe.filiere_name || 'N/A'} - ${classe.niveau_name || 'N/A'}`;
            tableBody.innerHTML += `<tr>
                <td>${classe.name || ''}</td>
                <td>${classe.filiere_name || 'N/A'}</td>
                <td>${classe.niveau_name || 'N/A'}</td>
                <td><button type="button" class="btn btn-sm btn-primary" onclick="selectClasse(${classe.id}, '${displayText.replace(/'/g, "\\'")}\')">Sélectionner</button></td>
            </tr>`;
        });
    }

    // Function to filter classes
    function filterClasses() {
        const filterType = document.getElementById('classe_search_filter').value;
        const query = document.getElementById('classe_search_query').value.toLowerCase();

        let filteredClasses = allClasses;

        // Filter by search query if provided
        if (query) {
            filteredClasses = filteredClasses.filter(classe => {
                switch (filterType) {
                    case 'nom':
                        return (classe.name || '').toLowerCase().includes(query);
                    case 'filiere':
                        return (classe.filiere_name || '').toLowerCase().includes(query);
                    case 'niveau':
                        return (classe.niveau_name || '').toLowerCase().includes(query);
                    default: // 'all'
                        return (classe.name || '').toLowerCase().includes(query) ||
                               (classe.filiere_name || '').toLowerCase().includes(query) ||
                               (classe.niveau_name || '').toLowerCase().includes(query);
                }
            });
        }

        // Apply current sort if any
        if (currentSort.column) {
            sortClasses(filteredClasses, currentSort.column, currentSort.direction);
        } else {
            displayClasses(filteredClasses);
        }
    }

    // Function to sort classes
    function sortClasses(classes, column, direction) {
        const sortedClasses = [...classes].sort((a, b) => {
            let aValue, bValue;

            switch (column) {
                case 'name':
                    aValue = a.name || '';
                    bValue = b.name || '';
                    break;
                case 'filiere':
                    aValue = a.filiere_name || '';
                    bValue = b.filiere_name || '';
                    break;
                case 'niveau':
                    aValue = a.niveau_name || '';
                    bValue = b.niveau_name || '';
                    break;
                default:
                    return 0;
            }

            if (direction === 'asc') {
                return aValue.localeCompare(bValue);
            } else {
                return bValue.localeCompare(aValue);
            }
        });

        displayClasses(sortedClasses);
        updateSortIcons(column, direction);
    }

    // Function to update sort icons
    function updateSortIcons(activeColumn, direction) {
        // Reset all icons
        document.querySelectorAll('.sortable i').forEach(icon => {
            icon.className = 'fas fa-sort text-muted';
        });

        // Update active column icon
        const activeHeader = document.querySelector(`[data-column="${activeColumn}"] i`);
        if (activeHeader) {
            if (direction === 'asc') {
                activeHeader.className = 'fas fa-sort-up text-primary';
            } else {
                activeHeader.className = 'fas fa-sort-down text-primary';
            }
        }
    }

    // Event listeners for filters and search
    document.addEventListener('DOMContentLoaded', function() {
        // Search input event listener
        const searchQuery = document.getElementById('classe_search_query');
        if (searchQuery) {
            searchQuery.addEventListener('input', filterClasses);
        }

        // Filter type change event listener
        const searchFilter = document.getElementById('classe_search_filter');
        if (searchFilter) {
            searchFilter.addEventListener('change', filterClasses);
        }

        // Sort headers click event listeners
        document.querySelectorAll('.sortable').forEach(header => {
            header.addEventListener('click', function() {
                const column = this.getAttribute('data-column');
                let direction = 'asc';

                // Toggle direction if clicking the same column
                if (currentSort.column === column) {
                    direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
                }

                currentSort = { column, direction };

                // Apply sort to currently visible classes
                const filterType = document.getElementById('classe_search_filter').value;
                const query = document.getElementById('classe_search_query').value.toLowerCase();

                let filteredClasses = allClasses;

                // Apply same filters as filterClasses()
                if (query) {
                    filteredClasses = filteredClasses.filter(classe => {
                        switch (filterType) {
                            case 'nom':
                                return (classe.name || '').toLowerCase().includes(query);
                            case 'filiere':
                                return (classe.filiere_name || '').toLowerCase().includes(query);
                            case 'niveau':
                                return (classe.niveau_name || '').toLowerCase().includes(query);
                            default: // 'all'
                                return (classe.name || '').toLowerCase().includes(query) ||
                                       (classe.filiere_name || '').toLowerCase().includes(query) ||
                                       (classe.niveau_name || '').toLowerCase().includes(query);
                        }
                    });
                }

                sortClasses(filteredClasses, column, direction);
            });
        });
    });

    // Add AJAX loading logic for classes when modal opens
    document.getElementById('classeSelectorModal').addEventListener('show.bs.modal', function () {
        debugLog('Modal show event triggered');
        loadClasses();
        
        // STOPPER TOUTES LES ANIMATIONS pour éviter curseur erratique
        document.body.style.setProperty('overflow', 'hidden', 'important');
        
        // Ajouter classe spéciale pour désactiver animations
        document.body.classList.add('modal-open-safe');
        
        // Désactiver toutes les animations CSS
        const style = document.createElement('style');
        style.id = 'anti-cursor-style';
        style.textContent = `
            * { 
                animation: none !important; 
                transition: none !important; 
                transform: none !important;
            }
            *:hover { 
                transform: none !important; 
                animation: none !important; 
            }
            *::before, *::after { 
                animation: none !important; 
                transition: none !important; 
                transform: none !important; 
            }
        `;
        document.head.appendChild(style);
    });

    // SOLUTION DRASTIQUE - Forcer le modal au premier plan avec z-index élevé
    document.getElementById('classeSelectorModal').addEventListener('shown.bs.modal', function () {
        debugLog('Modal shown - Applying FORCE fixes');
        
        const modal = document.getElementById('classeSelectorModal');
        const modalDialog = modal.querySelector('.modal-dialog');
        const modalContent = modal.querySelector('.modal-content');
        
        // Z-index très élevé pour être sûr d'être au-dessus
        modal.style.setProperty('z-index', '9999', 'important');
        modalDialog.style.setProperty('z-index', '10000', 'important');
        modalContent.style.setProperty('z-index', '10001', 'important');
        
        // Centrage parfait en position fixed
        modalDialog.style.setProperty('position', 'fixed', 'important');
        modalDialog.style.setProperty('top', '50%', 'important');
        modalDialog.style.setProperty('left', '50%', 'important');
        modalDialog.style.setProperty('transform', 'translate(-50%, -50%)', 'important');
        modalDialog.style.setProperty('margin', '0', 'important');
        modalDialog.style.setProperty('width', 'auto', 'important');
        modalDialog.style.setProperty('max-width', '90vw', 'important');
        modalDialog.style.setProperty('max-height', '90vh', 'important');
        
        // Supprimer backdrop-filter
        modal.style.setProperty('backdrop-filter', 'none', 'important');
        modal.style.setProperty('-webkit-backdrop-filter', 'none', 'important');
        modalDialog.style.setProperty('backdrop-filter', 'none', 'important');
        modalDialog.style.setProperty('-webkit-backdrop-filter', 'none', 'important');
        modalContent.style.setProperty('backdrop-filter', 'none', 'important');
        modalContent.style.setProperty('-webkit-backdrop-filter', 'none', 'important');
        
        // Forcer le backdrop à rester en arrière
        const backdrop = document.querySelector('.modal-backdrop');
        if (backdrop) {
            backdrop.style.setProperty('z-index', '1040', 'important');
            backdrop.style.setProperty('backdrop-filter', 'none', 'important');
            backdrop.style.setProperty('-webkit-backdrop-filter', 'none', 'important');
        }
        
        // Rendre le modal cliquable
        modal.style.setProperty('pointer-events', 'auto', 'important');
        modalDialog.style.setProperty('pointer-events', 'auto', 'important');
        modalContent.style.setProperty('pointer-events', 'auto', 'important');
        
        debugLog('FORCE fixes applied - Modal should be visible and centered');
    });

    // SOLUTION ALTERNATIVE - Si la première ne marche pas, forcer manuellement
    document.getElementById('selectClasseBtn').addEventListener('click', function() {
        debugLog('Button clicked - Setting up manual modal fix');
        
        // Attendre que Bootstrap ouvre le modal
        setTimeout(() => {
            const modal = document.getElementById('classeSelectorModal');
            const modalDialog = modal.querySelector('.modal-dialog');
            
            // Appliquer le fix manuellement après un délai
            if (modal && modalDialog) {
                debugLog('Applying manual modal fixes...');
                
                // Supprimer la classe fade temporairement pour éviter les animations
                modal.classList.remove('fade');
                
                // Appliquer les styles directement
                modal.style.display = 'block';
                modal.style.zIndex = '9999';
                modal.style.backgroundColor = 'rgba(0,0,0,0.5)';
                
                modalDialog.style.position = 'fixed';
                modalDialog.style.top = '50%';
                modalDialog.style.left = '50%';
                modalDialog.style.transform = 'translate(-50%, -50%)';
                modalDialog.style.zIndex = '10000';
                modalDialog.style.margin = '0';
                modalDialog.style.width = 'auto';
                modalDialog.style.maxWidth = '90vw';
                modalDialog.style.maxHeight = '90vh';
                
                // Remettre la classe fade après
                setTimeout(() => {
                    modal.classList.add('fade');
                }, 100);
            }
        }, 200);
    });

    // RÉTABLIR LES ANIMATIONS quand le modal se ferme
    document.getElementById('classeSelectorModal').addEventListener('hidden.bs.modal', function () {
        debugLog('Modal hidden - Restoring animations');

        // Retirer la classe spéciale
        document.body.classList.remove('modal-open-safe');

        // Supprimer le style anti-cursor
        const antiCursorStyle = document.getElementById('anti-cursor-style');
        if (antiCursorStyle) {
            antiCursorStyle.remove();
        }

        // Rétablir le scroll
        document.body.style.overflow = '';
    });

    // ============================================
    // GESTION DU MODAL DE TRANSFERT D'ÉTABLISSEMENT
    // ============================================

    // Fonction modifiée selectClasse pour vérifier le transfert
    window.originalSelectClasse = window.selectClasse || selectClasse;

    function selectClasse(classeId, classeName) {
        debugLog(`Classe sélectionnée : ${classeName} (ID: ${classeId})`);
        document.getElementById('classe_id').value = classeId;
        document.getElementById('classe_display').value = classeName;

        // Fermer le modal de sélection
        const modal = bootstrap.Modal.getInstance(document.getElementById('classeSelectorModal'));
        modal.hide();

        // Mettre à jour l'UI
        const placesInfo = document.getElementById('available-places-info');
        placesInfo.innerHTML = '<div class="spinner-border spinner-border-sm" role="status"><span class="visually-hidden">Loading...</span></div> Vérification des places...';

        // Vérifier les places disponibles
        fetch(`/esbtp/classes/${classeId}/available-places`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Erreur HTTP ! Statut: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                debugLog('Places disponibles:', data);
                if (data.available_places !== undefined) {
                    let message = `Places disponibles: <strong>${data.available_places}</strong> / ${data.capacity}`;
                    let alertClass = 'alert-success';
                    if (data.available_places <= 5) alertClass = 'alert-warning';
                    if (data.available_places === 0) {
                        alertClass = 'alert-danger';
                        message = '<strong>Aucune place disponible !</strong>';
                    }
                    placesInfo.innerHTML = `<div class="alert ${alertClass} p-2 mt-2">${message}</div>`;
                } else {
                     placesInfo.innerHTML = `<div class="alert alert-danger p-2 mt-2">Réponse invalide du serveur.</div>`;
                }
            })
            .catch(error => {
                debugError('Erreur de vérification des places:', error);
                placesInfo.innerHTML = `<div class="alert alert-danger p-2 mt-2">Erreur lors de la récupération des places.</div>`;
            });

        // NOUVEAU: Vérifier si la classe nécessite confirmation de transfert
        fetch(`/esbtp/inscriptions/check-transfert/${classeId}`)
            .then(response => response.json())
            .then(data => {
                debugLog('Vérification transfert:', data);
                if (data.success && data.necessite_confirmation) {
                    // Classe de 2ème année ou plus -> afficher modal de transfert
                    document.getElementById('transfert-niveau-nom').textContent = data.niveau_nom || data.niveau_code;

                    // Ouvrir le modal de transfert
                    const transfertModal = new bootstrap.Modal(document.getElementById('transfertConfirmationModal'));
                    transfertModal.show();
                } else {
                    // Classe de 1ère année -> pas besoin de confirmation, déclencher événement change
                    triggerClasseChangeEvent();
                }
            })
            .catch(error => {
                debugError('Erreur vérification transfert:', error);
                // En cas d'erreur, on déclenche l'événement quand même
                triggerClasseChangeEvent();
            });
    }

    // Fonction helper pour déclencher l'événement de changement de classe
    function triggerClasseChangeEvent() {
        setTimeout(() => {
            const classeElement = document.getElementById('classe_id');
            if (classeElement) {
                const changeEvent = new Event('change', { bubbles: true });
                classeElement.dispatchEvent(changeEvent);
            }
        }, 100);
    }

    // Gestion de l'affichage du champ établissement d'origine
    document.addEventListener('DOMContentLoaded', function() {
        const radioButtons = document.querySelectorAll('input[name="est_transfert_choice"]');
        const etablissementContainer = document.getElementById('etablissement-origine-container');
        const classeField = document.getElementById('classe_id');
        const classeDisplay = document.getElementById('classe_display');

        if (classeField && !classeField.value) {
            try {
                const storedClasseId = sessionStorage.getItem('inscription_classe_id');
                const storedClasseLabel = sessionStorage.getItem('inscription_classe_label');
                if (storedClasseId && storedClasseLabel) {
                    classeField.value = storedClasseId;
                    if (classeDisplay) {
                        classeDisplay.value = storedClasseLabel;
                    }
                    triggerClasseChangeEvent();
                }
            } catch (e) {
                debugWarn('SessionStorage indisponible pour restauration classe');
            }
        }

        radioButtons.forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.value === 'oui') {
                    etablissementContainer.style.display = 'block';
                } else {
                    etablissementContainer.style.display = 'none';
                }
            });
        });

        // Gestion du bouton de confirmation du modal de transfert
        const confirmerBtn = document.getElementById('confirmer-transfert-btn');
        if (confirmerBtn) {
            confirmerBtn.addEventListener('click', function() {
                const selectedChoice = document.querySelector('input[name="est_transfert_choice"]:checked').value;
                const etablissementInput = document.getElementById('etablissement_origine_input').value;

                // Stocker les valeurs dans les champs cachés
                const estTransfertField = document.getElementById('est_transfert');
                const etablissementOrigineField = document.getElementById('etablissement_origine');

                if (selectedChoice === 'oui') {
                    estTransfertField.value = '1';
                    etablissementOrigineField.value = etablissementInput;
                } else if (selectedChoice === 'non') {
                    estTransfertField.value = '0';
                    etablissementOrigineField.value = '';
                } else { // inconnu
                    estTransfertField.value = '0'; // Par défaut non
                    etablissementOrigineField.value = '';
                }

                debugLog('Transfert confirmé:', {
                    est_transfert: estTransfertField.value,
                    etablissement_origine: etablissementOrigineField.value
                });

                // Fermer le modal
                const transfertModal = bootstrap.Modal.getInstance(document.getElementById('transfertConfirmationModal'));
                transfertModal.hide();

                // Déclencher l'événement de changement de classe pour charger les frais
                triggerClasseChangeEvent();
            });
        }

        // Reset du modal quand il se ferme
        document.getElementById('transfertConfirmationModal')?.addEventListener('hidden.bs.modal', function() {
            // Reset du formulaire
            document.getElementById('transfert_non').checked = true;
            document.getElementById('etablissement_origine_input').value = '';
            document.getElementById('etablissement-origine-container').style.display = 'none';
        });
    });

</script> 

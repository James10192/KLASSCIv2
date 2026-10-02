/*
 * Liste des étudiants (/esbtp/etudiants) — script propre à la page.
 *
 * Sorti de la vue en octobre 2026 : recopié en <script> dans chaque réponse, il
 * pesait environ 55 Ko par affichage. Versionné par ?v=filemtime, il est
 * désormais mis en cache un an.
 *
 * Les données qui dépendent du serveur (adresses, libellés des classes, listes
 * de l'export) arrivent par window.etuIndexConfig, posé par la vue juste avant
 * ce fichier. Script classique, chargé au même endroit que l'ancien bloc : les
 * fonctions de haut niveau (exportModal, clearAllFilters…) restent globales.
 */
    // ========================================
    // MOBILE FILTER DRAWER - JavaScript
    // ========================================
    document.addEventListener('DOMContentLoaded', function() {
        const fab = document.getElementById('mobile-filter-fab');
        const drawer = document.getElementById('filter-drawer');
        const overlay = document.getElementById('filter-drawer-overlay');
        const closeBtn = document.getElementById('filter-drawer-close');
        const resetBtn = document.getElementById('filter-drawer-reset');

        if (!fab || !drawer || !overlay) {
            debugLog('⚠️ Drawer elements not found');
            return;
        }

        // Fonction pour ouvrir le drawer
        function openDrawer() {
            debugLog('📂 Opening filter drawer');
            drawer.classList.add('active');
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden'; // Empêcher le scroll du body
        }

        // Fonction pour fermer le drawer
        function closeDrawer() {
            debugLog('📁 Closing filter drawer');
            drawer.classList.remove('active');
            overlay.classList.remove('active');
            document.body.style.overflow = ''; // Restaurer le scroll
        }

        // Event listeners
        fab.addEventListener('click', openDrawer);
        closeBtn.addEventListener('click', closeDrawer);
        overlay.addEventListener('click', closeDrawer);

        // Bouton réinitialiser dans le drawer (AJAX - pas de refresh)
        resetBtn.addEventListener('click', function() {
            debugLog('🔄 Réinitialisation des filtres (drawer mobile)');

            // Utiliser clearAllFilters qui gère tout (AJAX + reset selects)
            if (typeof clearAllFilters === 'function') {
                clearAllFilters();

                // Fermer le drawer après l'AJAX
                setTimeout(closeDrawer, 300);
            } else {
                // Fallback si clearAllFilters n'est pas disponible
                window.location.href = window.etuIndexConfig.indexUrl;
            }
        });

        // Fermer avec la touche Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && drawer.classList.contains('active')) {
                closeDrawer();
            }
        });

        // ========================================
        // AJAX SUBMISSION DU DRAWER (PAS DE REFRESH PAGE)
        // ========================================
        const mobileForm = document.getElementById('mobile-search-form');
        if (mobileForm) {
            mobileForm.addEventListener('submit', function(e) {
                e.preventDefault();  // Empêcher la soumission normale (refresh page)
                debugLog('📤 Soumission AJAX du drawer mobile');

                // Construire les paramètres depuis le formulaire mobile
                const formData = new FormData(mobileForm);
                const params = new URLSearchParams();

                for (const [key, value] of formData.entries()) {
                    if (value) {  // Ignorer les valeurs vides
                        params.append(key, value);
                    }
                }

                const url = mobileForm.action + '?' + params.toString();
                debugLog('📍 URL AJAX:', url);

                // Utiliser la fonction fetchResults existante (définie plus bas dans le script)
                if (typeof window.fetchResultsGlobal === 'function') {
                    window.fetchResultsGlobal(url, { pushState: true });

                    // Fermer le drawer après la soumission
                    setTimeout(closeDrawer, 300);  // Petit délai pour UX smooth
                } else {
                    debugError('❌ fetchResultsGlobal non disponible');
                }
            });
        }

        debugLog('✅ Mobile filter drawer initialized');
    });

    // ========================================
    // ALPINE.JS SEARCHABLE SELECT COMPONENT
    // ========================================
    // Alpine.js Searchable Select Component - Défini globalement
    window.searchableSelect = function(config) {
        debugLog('🔧 Initialisation searchableSelect avec config:', config);
        return {
            options: config.options || [],
            filteredOptions: [],
            search: '',
            open: false,
            selectedValue: config.selected || '',
            selectedLabel: '',
            placeholder: config.placeholder || 'Sélectionner...',

            init() {
                debugLog('✅ searchableSelect init() appelé');
                debugLog('📊 Nombre d\'options:', this.options.length);
                this.filteredOptions = this.options;
                this.updateSelectedLabel();
                debugLog('🏷️ Label sélectionné:', this.selectedLabel);

                // Watch for open changes to focus search input
                this.$watch('open', value => {
                    debugLog('👁️ Dropdown open:', value);
                    if (value) {
                        this.$nextTick(() => {
                            this.$refs.searchInput?.focus();
                        });
                    } else {
                        this.search = '';
                        this.filteredOptions = this.options;
                    }
                });

                // Écouter les events de reset
                const componentName = config.name;

                // Reset individuel (pour ce composant spécifique)
                window.addEventListener('reset-searchable-select', (e) => {
                    if (e.detail && e.detail.name === componentName) {
                        debugLog('🔄 Reset event received for:', componentName);
                        this.selectedValue = '';
                        this.selectedLabel = '';
                        this.search = '';
                        this.filteredOptions = this.options;
                        this.open = false;
                    }
                });

                // Reset tous les composants
                window.addEventListener('reset-all-searchable-selects', () => {
                    debugLog('🔄 Reset ALL event received for:', componentName);
                    this.selectedValue = '';
                    this.selectedLabel = '';
                    this.search = '';
                    this.filteredOptions = this.options;
                    this.open = false;
                });
            },

            filterOptions() {
                const searchLower = this.search.toLowerCase();
                this.filteredOptions = this.options.filter(option =>
                    option.label.toLowerCase().includes(searchLower)
                );
                debugLog('🔍 Filtrage:', this.search, '→', this.filteredOptions.length, 'résultats');
            },

            selectOption(option) {
                debugLog('✅ Option sélectionnée:', option);
                this.selectedValue = option.value;
                this.selectedLabel = option.label;
                this.open = false;
                this.search = '';
                this.filteredOptions = this.options;

                // Trigger AJAX refresh instead of form submission
                this.$nextTick(() => {
                    if (typeof window.triggerFilterChange === 'function') {
                        debugLog('📤 Déclenchement AJAX refresh...');
                        window.triggerFilterChange();
                    }
                });
            },

            updateSelectedLabel() {
                const selected = this.options.find(opt => opt.value === this.selectedValue);
                this.selectedLabel = selected ? selected.label : '';
                debugLog('🔄 updateSelectedLabel - value:', this.selectedValue, 'label:', this.selectedLabel);
            }
        }
    }

    debugLog('✅ Fonction searchableSelect définie globalement');

    // Update student count badge from partial data
    function updateStudentCountBadge() {
        var el = document.getElementById('student-count-inline');
        var badge = document.getElementById('student-count-badge');
        if (el && badge) {
            var total = el.dataset.total;
            // La liste se charge au defilement : « N sur cette page » ne voulait plus rien dire.
            badge.textContent = total + ' étudiant' + (parseInt(total) > 1 ? 's' : '');
        }
    }
    document.addEventListener('DOMContentLoaded', function () {
        updateStudentCountBadge();

        // Advanced filters toggle
        var toggleBtn = document.getElementById('toggle-advanced-filters-btn');
        if (toggleBtn) {
            toggleBtn.addEventListener('click', function() {
                var panel = document.getElementById('advanced-filters');
                var icon = this.querySelector('.fa-chevron-down, .fa-chevron-up');
                if (panel.style.display === 'none') {
                    panel.style.display = 'block';
                    if (icon) icon.classList.replace('fa-chevron-down', 'fa-chevron-up');
                } else {
                    panel.style.display = 'none';
                    if (icon) icon.classList.replace('fa-chevron-up', 'fa-chevron-down');
                }
            });
        }

        const form = document.getElementById('search-form');
        const resultsContainer = document.getElementById('etudiants-results');
        const submitButton = form.querySelector('button[type="submit"]');
        const filterInputs = form.querySelectorAll('select');
        const modalElement = document.getElementById('etudiantEditModal');
        const inscriptionsContainer = document.getElementById('inscriptions-accordion-container');
        const studentFrame = document.getElementById('student-edit-frame');
        const studentEditLoader = document.getElementById('student-edit-loader');
        let editModal = null;

        function setLoading(isLoading) {
            if (submitButton) {
                submitButton.disabled = isLoading;
            }
            if (isLoading) {
                resultsContainer.classList.add('opacity-50');
            } else {
                resultsContainer.classList.remove('opacity-50');
            }
        }

        // Fonction globale pour déclencher le refresh AJAX depuis le composant Alpine
        window.triggerFilterChange = function() {
            debugLog('🔄 triggerFilterChange appelée');
            const formData = new FormData(form);
            const params = new URLSearchParams();

            // Construire les paramètres depuis le formulaire
            for (const [key, value] of formData.entries()) {
                if (value) {  // Ignorer les valeurs vides
                    params.append(key, value);
                }
            }

            const url = form.action + '?' + params.toString();
            debugLog('📍 URL AJAX:', url);
            fetchResults(url, { pushState: true });
        };

        function bindPagination() {
            // Infinite scroll : remplace la pagination Laravel.
            // Observer la sentinelle, fetch page suivante, append rows au tbody.
            bindInfiniteScroll();
            // Click sur row → navigation vers fiche étudiant (sauf action buttons).
            bindRowClick();
        }

        let infiniteObserver = null;
        let infiniteLoading = false;
        function bindInfiniteScroll() {
            if (infiniteObserver) { infiniteObserver.disconnect(); infiniteObserver = null; }
            const sentinel = document.getElementById('etudiants-sentinel');
            // La grille mobile a sa propre sentinelle : celle du tableau est cachee
            // sous 992px et ne croise donc jamais l'ecran d'un telephone.
            const sentinelMobile = document.getElementById('etudiants-sentinel-mobile');
            const grilleMobile = document.getElementById('etudiants-grid-mobile');
            const tbody = document.getElementById('etudiants-tbody');
            if (!sentinel || !tbody) return;
            infiniteObserver = new IntersectionObserver(async (entries) => {
                for (const entry of entries) {
                    if (!entry.isIntersecting || infiniteLoading) continue;
                    if (tbody.dataset.hasMore !== '1') { infiniteObserver.disconnect(); continue; }
                    infiniteLoading = true;
                    const spinner = entry.target.querySelector('.eu-sentinel-spinner');
                    if (spinner) spinner.style.display = 'flex';
                    try {
                        const nextPage = parseInt(tbody.dataset.nextPage || '2', 10);
                        const url = new URL(window.location.href);
                        url.searchParams.set('page', String(nextPage));
                        const res = await fetch(url.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
                        if (!res.ok) throw new Error('HTTP ' + res.status);
                        const data = await res.json();
                        const tmp = document.createElement('div');
                        tmp.innerHTML = data.html || '';
                        const newTbody = tmp.querySelector('#etudiants-tbody');
                        if (newTbody) {
                            // Une fiche deja affichee (creee pendant qu'on defile) n'est pas repetee.
                            Array.from(newTbody.children)
                                .filter((row) => !row.dataset.liCle || !tbody.querySelector('[data-li-cle="' + row.dataset.liCle + '"]'))
                                .forEach((row) => tbody.appendChild(row));
                            tbody.dataset.hasMore = newTbody.dataset.hasMore || '0';
                            tbody.dataset.nextPage = newTbody.dataset.nextPage || String(nextPage + 1);
                            tbody.dataset.currentPage = newTbody.dataset.currentPage || String(nextPage);
                        }
                        const newGrille = tmp.querySelector('#etudiants-grid-mobile');
                        if (newGrille && grilleMobile) {
                            Array.from(newGrille.children)
                                .filter((carte) => carte.classList.contains('etm-card')
                                    && !grilleMobile.querySelector('[data-li-cle="' + carte.dataset.liCle + '"]'))
                                .forEach((carte) => grilleMobile.appendChild(carte));
                        }
                        const newSentinel = tmp.querySelector('#etudiants-sentinel');
                        if (newSentinel) sentinel.innerHTML = newSentinel.innerHTML;
                        const newSentinelMobile = tmp.querySelector('#etudiants-sentinel-mobile');
                        if (newSentinelMobile && sentinelMobile) sentinelMobile.innerHTML = newSentinelMobile.innerHTML;
                        // Re-bind row click on new rows
                        bindRowClick();
                    } catch (e) {
                        console.error('Erreur infinite scroll étudiants:', e);
                    } finally {
                        infiniteLoading = false;
                        if (spinner) spinner.style.display = 'none';
                    }
                }
            }, { rootMargin: '200px' });
            infiniteObserver.observe(sentinel);
            if (sentinelMobile) infiniteObserver.observe(sentinelMobile);
        }

        function bindRowClick() {
            document.querySelectorAll('.eu-row').forEach((tr) => {
                if (tr.dataset.clickBound === '1') return;
                tr.dataset.clickBound = '1';
                tr.addEventListener('click', function (ev) {
                    // Ignore si clic sur action button ou enfant marqué stop-propagation
                    if (ev.target.closest('[data-stop-propagation]')) return;
                    if (ev.target.closest('.eu-actions')) return;
                    if (ev.target.closest('a, button')) return;
                    const url = this.dataset.showUrl;
                    if (url) window.location.href = url;
                });
            });
        }

        function initTableSorting(scope = document) {
            const table = scope.querySelector('#etudiants-table');
            if (!table) {
                return;
            }

            scope.querySelectorAll('.table-sort').forEach((button) => {
                if (button.dataset.sortInit === '1') {
                    return;
                }
                button.dataset.sortInit = '1';
                button.addEventListener('click', function () {
                    const column = this.dataset.column;
                    if (!column) {
                        return;
                    }

                    // Récupérer la direction actuelle et alterner
                    const currentDirection = this.dataset.sortDirection || 'desc';
                    const newDirection = currentDirection === 'asc' ? 'desc' : 'asc';
                    this.dataset.sortDirection = newDirection;

                    // Retirer les indicateurs de tri sur les autres colonnes
                    scope.querySelectorAll('.table-sort').forEach((other) => {
                        if (other !== this) {
                            delete other.dataset.sortDirection;
                            other.classList.remove('sorted-asc', 'sorted-desc');
                        }
                    });

                    // Ajouter classe CSS pour indiquer le tri actif
                    this.classList.remove('sorted-asc', 'sorted-desc');
                    this.classList.add(`sorted-${newDirection}`);

                    // Construire l'URL avec les paramètres de tri
                    const urlParams = new URLSearchParams(window.location.search);
                    urlParams.set('sort', column);
                    urlParams.set('order', newDirection);

                    // Garder la page actuelle si elle existe
                    if (!urlParams.has('page')) {
                        urlParams.set('page', '1');
                    }

                    const newUrl = `${window.location.pathname}?${urlParams.toString()}`;

                    debugLog('🔀 Tri par colonne:', column, '→', newDirection);
                    debugLog('📍 URL:', newUrl);

                    // Faire l'appel AJAX pour récupérer les résultats triés
                    window.fetchResultsGlobal(newUrl, { pushState: true });
                }, { once: false });
            });
        }

        function formatStatusLabel(status) {
            if (!status) {
                return '';
            }
            const normalized = status.replace(/_/g, ' ');
            const classes = {
                'active': 'bg-success',
                'en attente': 'bg-warning text-dark',
                'en_attente': 'bg-warning text-dark',
                'annulée': 'bg-danger',
                'terminée': 'bg-secondary',
            };
            const key = status.toLowerCase();
            const badgeClass = classes[key] || 'bg-primary';
            return `<span class="badge ${badgeClass} text-uppercase">${normalized}</span>`;
        }

        function attachAccordionListeners(container) {
            if (!container) {
                return;
            }
            function loadIframeWithLoader(iframe) {
                if (!iframe || iframe.src) return;
                const loader = iframe.closest('.modal-iframe-wrapper')?.querySelector('.inscription-loader');
                if (loader) loader.classList.remove('hidden');
                const separator = iframe.dataset.src.includes('?') ? '&' : '?';
                iframe.src = `${iframe.dataset.src}${separator}_=${Date.now()}`;
                iframe.addEventListener('load', function h() {
                    if (loader) loader.classList.add('hidden');
                    iframe.removeEventListener('load', h);
                });
            }

            container.querySelectorAll('.accordion-collapse').forEach((collapseEl) => {
                collapseEl.addEventListener('show.bs.collapse', function () {
                    loadIframeWithLoader(this.querySelector('iframe[data-src]'));
                }, { once: true });
            });

            const firstVisible = container.querySelector('.accordion-collapse.show');
            if (firstVisible) {
                const iframe = firstVisible.querySelector('iframe[data-src]');
                if (iframe && !iframe.src) {
                    loadIframeWithLoader(iframe);
                }
            }
        }

        function formatWorkflowStepBadge(workflowStep) {
            if (!workflowStep) {
                return '';
            }

            const workflowSteps = {
                'prospect': { label: 'Prospect', class: 'bg-secondary', icon: 'fa-user-plus' },
                'documents_complets': { label: 'Documents complets', class: 'bg-info', icon: 'fa-file-check' },
                'en_validation': { label: 'En validation', class: 'bg-warning', icon: 'fa-hourglass-half' },
                'valide': { label: 'Validé', class: 'bg-success', icon: 'fa-check' },
                'etudiant_cree': { label: 'Étudiant créé', class: 'bg-primary', icon: 'fa-graduation-cap' }
            };

            const step = workflowSteps[workflowStep];
            if (step) {
                return `<span class="badge ${step.class} ms-2"><i class="fas ${step.icon} me-1"></i>${step.label}</span>`;
            }

            return `<span class="badge bg-light text-dark ms-2">${workflowStep}</span>`;
        }

        function renderInscriptionsAccordion(payload) {
            if (!inscriptionsContainer) {
                return;
            }

            const inscriptions = payload?.inscriptions ?? [];
            if (!inscriptions.length) {
                inscriptionsContainer.innerHTML = '<div class="alert alert-info mb-0">Aucune inscription disponible pour cet étudiant.</div>';
                return;
            }

            const accordionId = 'inscriptionsAccordion';
            const items = inscriptions.map((inscription, index) => {
                const collapseId = `inscription-collapse-${inscription.id}`;
                const headingId = `inscription-heading-${inscription.id}`;
                const affectation = inscription.affectation_status ? `<span class="badge bg-secondary ms-2 text-uppercase">${inscription.affectation_status}</span>` : '';
                const statusBadge = formatStatusLabel(inscription.status);
                const typeBadge = inscription.type ? `<span class="badge bg-info text-dark text-uppercase ms-2">${inscription.type}</span>` : '';
                const currentYearBadge = inscription.is_current_year ? `<span class="badge bg-primary text-white ms-2">Année courante</span>` : '';
                const dateChip = inscription.date_label ? `<span class="badge bg-light text-dark border ms-2"><i class="far fa-calendar-alt me-1"></i>${inscription.date_label}</span>` : '';
                const workflowBadge = formatWorkflowStepBadge(inscription.workflow_step);

                return `
<div class="accordion-item mb-2">
    <h2 class="accordion-header" id="${headingId}">
        <button class="accordion-button ${index === 0 ? '' : 'collapsed'}" type="button" data-bs-toggle="collapse" data-bs-target="#${collapseId}" aria-expanded="${index === 0}" aria-controls="${collapseId}">
            <div class="d-flex flex-column flex-md-row w-100 justify-content-between">
                <div>
                    <strong>${inscription.annee}</strong> ${currentYearBadge} — ${inscription.classe}
                    ${dateChip}
                </div>
                <div>
                    ${statusBadge || ''}
                    ${workflowBadge}
                    ${affectation}
                    ${typeBadge}
                </div>
            </div>
        </button>
    </h2>
    <div id="${collapseId}" class="accordion-collapse collapse ${index === 0 ? 'show' : ''}" data-bs-parent="#${accordionId}">
        <div class="accordion-body">
            <div class="mb-3 row g-3 text-muted small">
                ${inscription.filiere ? `<div class=\"col-md-4\"><i class=\"fas fa-book me-2 text-primary\"></i>${inscription.filiere}</div>` : ''}
                ${inscription.niveau ? `<div class=\"col-md-4\"><i class=\"fas fa-layer-group me-2 text-primary\"></i>${inscription.niveau}</div>` : ''}
                ${inscription.affectation_status ? `<div class=\"col-md-4\"><i class=\"fas fa-map-marker-alt me-2 text-primary\"></i>${inscription.affectation_status}</div>` : ''}
            </div>
            <div class="mb-3">
                <a href="/esbtp/inscriptions/${inscription.id}" target="_blank" class="btn btn-info btn-sm">
                    <i class="fas fa-eye me-1"></i>Voir l'inscription
                </a>
            </div>
            <div class="modal-iframe-wrapper" style="position:relative;">
                <div class="iframe-loader inscription-loader">
                    <div class="spinner-border text-primary" role="status" style="width:1.5rem;height:1.5rem;"></div>
                    <span style="font-size:0.8rem;color:#64748b;margin-top:0.4rem;">Chargement...</span>
                </div>
                <iframe class="border-0 inscription-frame" data-src="${inscription.edit_url}" title="Inscription #${inscription.id}" loading="eager"></iframe>
            </div>
        </div>
    </div>
</div>`;
            }).join('');

            inscriptionsContainer.innerHTML = `<div class="accordion accordion-modern" id="${accordionId}">${items}</div>`;
            attachAccordionListeners(inscriptionsContainer);
        }

        function openEditModal(datasetString) {
            if (!modalElement || !datasetString) {
                return;
            }
            if (!editModal) {
                editModal = new bootstrap.Modal(modalElement);
                modalElement.addEventListener('hidden.bs.modal', () => {
                    if (studentFrame) {
                        studentFrame.src = 'about:blank';
                        if (studentEditLoader) { studentEditLoader.classList.remove('hidden'); }
                    }
                    if (inscriptionsContainer) {
                        inscriptionsContainer.innerHTML = '<div class="text-muted">Sélectionnez un étudiant pour afficher ses inscriptions.</div>';
                    }
                });
            }

            let payload;
            try {
                payload = JSON.parse(datasetString);
            } catch (error) {
                debugError('Impossible de parser les données de l\'étudiant', error);
                return;
            }

            const modalTitle = document.getElementById('etudiantEditModalLabel');
            if (modalTitle) {
                const identifiant = payload.matricule ? ` (#${payload.matricule})` : '';
                modalTitle.textContent = `Modifier ${payload.name ?? 'l\'étudiant'}${identifiant}`;
            }

            if (studentFrame && payload.edit_url) {
                if (studentEditLoader) { studentEditLoader.classList.remove('hidden'); }
                const separator = payload.edit_url.includes('?') ? '&' : '?';
                studentFrame.src = `${payload.edit_url}${separator}_=${Date.now()}`;
                studentFrame.addEventListener('load', function handleLoad() {
                    if (studentEditLoader) { studentEditLoader.classList.add('hidden'); }
                    studentFrame.removeEventListener('load', handleLoad);
                });
            }

            // Charger TOUTES les inscriptions via AJAX (pas seulement l'année courante)
            fetch(`/esbtp/etudiants/${payload.id}/all-inscriptions`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.inscriptions) {
                    // Remplacer les inscriptions dans le payload avec toutes les inscriptions
                    payload.inscriptions = data.inscriptions;
                }
                renderInscriptionsAccordion(payload);
            })
            .catch(error => {
                debugError('Erreur chargement inscriptions:', error);
                // Afficher quand même avec les inscriptions par défaut (année courante)
                renderInscriptionsAccordion(payload);
            });

            const studentTab = document.getElementById('tab-etudiant-link');
            if (studentTab) {
                const tabInstance = bootstrap.Tab.getOrCreateInstance(studentTab);
                tabInstance.show();
            }
            editModal.show();
        }

        if (resultsContainer) {
            resultsContainer.addEventListener('click', function (event) {
                const trigger = event.target.closest('.btn-open-edit-modal');
                if (!trigger) {
                    return;
                }
                event.preventDefault();
                openEditModal(trigger.getAttribute('data-student'));
            });
        }

        function fetchResults(url, options = {}) {
            if (!url) {
                return Promise.resolve();
            }

            setLoading(true);

            return fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin'
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erreur lors du chargement des étudiants.');
                }
                return response.json();
            })
            .then(data => {
                resultsContainer.innerHTML = data.html;
                updateStudentCountBadge();
                if (options.pushState !== false) {
                    window.history.pushState({ url: data.url }, '', data.url);
                }
                bindPagination();
                initTableSorting(resultsContainer);

                // Mettre à jour l'indicateur APRÈS pushState
                updateActiveFiltersIndicator();
            })
            .catch(error => {
                debugError(error);
                alert('Impossible de charger les étudiants. Veuillez réessayer.');
            })
            .finally(() => setLoading(false));
        }

        // Exposer fetchResults globalement pour le drawer mobile
        window.fetchResultsGlobal = fetchResults;

        // ========================================
        // INDICATEUR FILTRES ACTIFS
        // ========================================

        // Mapping des classes (ID → Label) pour l'indicateur de filtres
        const classesMapping = window.etuIndexConfig.classesMapping;

        function updateActiveFiltersIndicator() {
            const container = document.getElementById('active-filters-container');
            if (!container) return;

            // Récupérer les paramètres de l'URL
            const urlParams = new URLSearchParams(window.location.search);
            const activeFilters = [];

            // Mapping des paramètres vers des labels lisibles
            const filterLabels = {
                'search': 'Recherche',
                'filiere': 'Filière',
                'niveau': 'Niveau',
                'classe': 'Classe',
                'annee': 'Année universitaire',
                'statut': 'Statut',
                'affectation_status': 'Statut affectation',
                'inscrit_annee_courante': 'Inscription validée',
                'est_transfert': 'Transfert',
                'accessibility': 'Accessibilité',
                'sexe': 'Genre'
            };

            // Récupérer les options de select pour avoir les labels
            const getSelectLabel = (name, value) => {
                // Pour le champ recherche, retourner la valeur directement
                if (name === 'search') {
                    return value;
                }

                // Pour la classe (searchable select Alpine.js)
                if (name === 'classe') {
                    // Utiliser le mapping créé depuis les data Laravel
                    return classesMapping[value] || value;
                }

                // Pour les autres selects standards
                const select = document.querySelector(`select[name="${name}"], #mobile-${name}`);
                if (select) {
                    const option = select.querySelector(`option[value="${value}"]`);
                    return option ? option.textContent.trim() : value;
                }

                return value;
            };

            // Parcourir les paramètres
            for (const [key, value] of urlParams) {
                if (value && filterLabels[key]) {
                    activeFilters.push({
                        key: key,
                        label: filterLabels[key],
                        value: value,
                        displayValue: getSelectLabel(key, value)
                    });
                }
            }

            // Afficher ou masquer le conteneur
            if (activeFilters.length === 0) {
                container.style.display = 'none';
                return;
            }

            container.style.display = 'flex';

            // Générer le HTML
            let html = `
                <div class="active-filters-label">
                    <i class="fas fa-filter"></i>
                    <span>Filtres actifs :</span>
                </div>
            `;

            // Ajouter chaque filtre
            activeFilters.forEach(filter => {
                html += `
                    <div class="filter-tag" data-filter-key="${filter.key}">
                        <span class="filter-tag-label">${filter.label}:</span>
                        <span class="filter-tag-value">${filter.displayValue}</span>
                        <button class="filter-tag-remove" data-filter-key="${filter.key}" title="Supprimer ce filtre">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                `;
            });

            // Bouton tout effacer
            html += `
                <button class="clear-all-filters" id="clear-all-filters-btn">
                    <i class="fas fa-times-circle"></i>
                    <span>Tout effacer</span>
                </button>
            `;

            container.innerHTML = html;

            // Attacher les event listeners
            container.querySelectorAll('.filter-tag-remove').forEach(btn => {
                btn.addEventListener('click', function() {
                    const key = this.getAttribute('data-filter-key');
                    removeFilter(key);
                });
            });

            const clearAllBtn = container.querySelector('#clear-all-filters-btn');
            if (clearAllBtn) {
                clearAllBtn.addEventListener('click', function() {
                    clearAllFilters();
                });
            }
        }

        // Fonction pour reset un select spécifique (desktop + mobile + Alpine.js)
        function resetSelectByName(name) {
            debugLog('🔄 Reset select:', name);

            // Reset select desktop standard
            const desktopSelect = document.querySelector(`select[name="${name}"]`);
            if (desktopSelect) {
                desktopSelect.value = '';
                debugLog('  ✅ Desktop select reset');
            }

            // Reset select mobile standard
            const mobileSelect = document.querySelector(`#mobile-${name}`);
            if (mobileSelect) {
                mobileSelect.value = '';
                debugLog('  ✅ Mobile select reset');
            }

            // Reset input recherche si c'est le champ search
            if (name === 'search') {
                const searchInput = document.querySelector('input[name="search"]');
                if (searchInput) {
                    searchInput.value = '';
                    debugLog('  ✅ Search input reset');
                }
                const mobileSearchInput = document.querySelector('#mobile-search');
                if (mobileSearchInput) {
                    mobileSearchInput.value = '';
                    debugLog('  ✅ Mobile search input reset');
                }
            }

            // Reset composant Alpine.js (classe searchable select)
            if (name === 'classe') {
                // Dispatcher un event custom pour reset le composant Alpine
                window.dispatchEvent(new CustomEvent('reset-searchable-select', {
                    detail: { name: 'classe' }
                }));
                debugLog('  ✅ Alpine.js classe component reset event dispatched');
            }
        }

        // Vider un formulaire, VRAIMENT.
        //
        // `form.reset()` ne vide pas : il restaure l'etat INITIAL du HTML rendu.
        // Or cette page est rendue AVEC les filtres appliques — les `selected` et
        // les `value` sont ceux du filtre. Reinitialiser ramenait donc les selects
        // a la valeur qu'on venait de retirer : la liste se rechargeait sans
        // filtre, mais l'ecran continuait d'afficher l'ancien choix. Deux etats
        // contradictoires, et aucun moyen de savoir lequel fait foi.
        function viderLesChamps(formulaire) {
            if (!formulaire) return;

            // Les CHAMPS D'ABORD, les menus ensuite, et AUCUN evenement emis ici.
            //
            // Une premiere version vidait les menus en emettant un `change` sur
            // chacun, pour prevenir les composants qui s'y synchronisent. Mais ce
            // `change` declenche la soumission automatique du formulaire : la
            // page se resoumettait des le PREMIER menu vide, alors que le champ
            // de recherche n'avait pas encore ete touche. La liste revenait donc
            // filtree sur la recherche, avec sa pastille, sous des champs vides —
            // pire que le defaut qu'on corrigeait.
            //
            // Les composants premium sont prevenus une seule fois, par
            // l'evenement global emis a la fin de resetAllSelects().
            formulaire.querySelectorAll('input').forEach(function (champ) {
                if (champ.type === 'checkbox' || champ.type === 'radio') {
                    champ.checked = false;
                } else if (champ.type !== 'hidden' && champ.type !== 'submit' && champ.type !== 'button') {
                    champ.value = '';
                }
            });

            formulaire.querySelectorAll('select').forEach(function (select) {
                select.selectedIndex = 0;
                select.value = '';
            });
        }

        // Fonction pour reset TOUS les selects
        function resetAllSelects() {
            debugLog('🔄 Reset ALL selects');

            // Reset formulaire desktop
            if (form) {
                viderLesChamps(form);
                debugLog('  ✅ Desktop form reset');
            }

            // Reset formulaire mobile
            const mobileForm = document.getElementById('mobile-search-form');
            if (mobileForm) {
                viderLesChamps(mobileForm);
                debugLog('  ✅ Mobile form reset');
            }

            // Reset tous les composants Alpine.js
            window.dispatchEvent(new CustomEvent('reset-all-searchable-selects'));
            debugLog('  ✅ Alpine.js reset event dispatched');
        }

        function removeFilter(key) {
            debugLog('🗑️ Suppression du filtre:', key);
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.delete(key);

            const newUrl = `${window.location.pathname}?${urlParams.toString()}`;

            // Faire l'appel AJAX puis reset le select correspondant
            window.fetchResultsGlobal(newUrl, { pushState: true }).then(() => {
                // Reset le select correspondant après l'AJAX
                resetSelectByName(key);
            });
        }

        function clearAllFilters() {
            debugLog('🗑️ Suppression de tous les filtres');

            // Faire l'appel AJAX puis reset tous les selects
            window.fetchResultsGlobal(window.location.pathname, { pushState: true }).then(() => {
                // Reset TOUS les selects après l'AJAX
                resetAllSelects();
            });
        }

        // Mettre à jour l'indicateur au chargement initial
        updateActiveFiltersIndicator();

        // Bouton "Réinitialiser" desktop
        const desktopResetBtn = document.getElementById('desktop-reset-btn');
        if (desktopResetBtn) {
            desktopResetBtn.addEventListener('click', function(e) {
                e.preventDefault();
                debugLog('🔄 Réinitialisation des filtres (desktop)');
                clearAllFilters();
            });
        }

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            event.stopPropagation();
            const formData = new FormData(form);
            const params = new URLSearchParams(formData);
            const targetUrl = `${form.action}?${params.toString()}`;
            // Utiliser window.fetchResultsGlobal pour déclencher l'update automatique
            window.fetchResultsGlobal(targetUrl, { pushState: true });
            return false;
        });

        filterInputs.forEach((input) => {
            input.addEventListener('change', () => {
                if (!form) {
                    return;
                }
                const formData = new FormData(form);
                const params = new URLSearchParams(formData);
                const targetUrl = `${form.action}?${params.toString()}`;
                // Utiliser window.fetchResultsGlobal pour déclencher l'update automatique
                window.fetchResultsGlobal(targetUrl, { pushState: true });
            });
        });

        // Relai AJAX pour les pickers LMD premium (au-mention-picker / au-parcours-picker).
        // Ces composants exposent des <input type="hidden" name="mention" / "parcours">
        // qui ne sont PAS captés par filterInputs (qui ne sélectionne que les <select>).
        // Custom event 'mention:changed' (cf composant) + input event natif sur hidden.
        const triggerLmdRefresh = () => {
            if (!form) return;
            const formData = new FormData(form);
            const params = new URLSearchParams(formData);
            const targetUrl = `${form.action}?${params.toString()}`;
            window.fetchResultsGlobal(targetUrl, { pushState: true });
        };
        window.addEventListener('mention:changed', triggerLmdRefresh);
        document.addEventListener('input', (ev) => {
            if (ev.target && (ev.target.name === 'mention' || ev.target.name === 'parcours')) {
                triggerLmdRefresh();
            }
        });

        if (window.history && window.history.replaceState) {
            window.history.replaceState({ url: window.location.href }, '', window.location.href);
        }

        window.addEventListener('popstate', function (event) {
            const targetUrl = event.state?.url || window.location.href;
            // Utiliser window.fetchResultsGlobal pour déclencher l'update automatique
            window.fetchResultsGlobal(targetUrl, { pushState: false });
        });

        bindPagination();
        initTableSorting(resultsContainer);

    });

    // ========================================
    // EXPORT MODAL - Alpine Component
    // ========================================
    function exportModal() {
        return {
            exportGroupBy: '',
            classSearch: '',

            // Data arrays
            allFilieres: (window.etuIndexConfig.exportFilieres || []).slice(),
            allNiveaux: (window.etuIndexConfig.exportNiveaux || []).slice(),
            allClasses: (window.etuIndexConfig.exportClasses || []).slice(),

            // Selected combinations: array of {filiere_id, niveau_id} pairs
            selectedCombinations: [],
            // Individual class selection (IDs)
            selectedClassIds: [],

            init() {
                // Start with all valid combinations selected
                var self = this;
                this.allClasses.forEach(function(c) {
                    if (c.filiere_id && c.niveau_etude_id) {
                        var exists = self.selectedCombinations.some(function(combo) {
                            return combo.filiere_id === c.filiere_id && combo.niveau_id === c.niveau_etude_id;
                        });
                        if (!exists) {
                            self.selectedCombinations.push({ filiere_id: c.filiere_id, niveau_id: c.niveau_etude_id });
                        }
                        // All classes selected by default
                        self.selectedClassIds.push(c.id);
                    }
                });
            },

            // Is a (filière, niveau) combination selected?
            isCombinationSelected(filiereId, niveauId) {
                return this.selectedCombinations.some(function(c) {
                    return c.filiere_id === filiereId && c.niveau_id === niveauId;
                });
            },

            // Toggle a specific (filière, niveau) combination + sync classes
            toggleCombination(filiereId, niveauId) {
                var self = this;
                var comboClassIds = this.allClasses
                    .filter(function(c) { return c.filiere_id === filiereId && c.niveau_etude_id === niveauId; })
                    .map(function(c) { return c.id; });

                var idx = -1;
                for (var i = 0; i < this.selectedCombinations.length; i++) {
                    if (this.selectedCombinations[i].filiere_id === filiereId && this.selectedCombinations[i].niveau_id === niveauId) {
                        idx = i;
                        break;
                    }
                }
                if (idx > -1) {
                    // Uncheck combo → remove its classes
                    this.selectedCombinations.splice(idx, 1);
                    this.selectedClassIds = this.selectedClassIds.filter(function(id) {
                        return comboClassIds.indexOf(id) === -1;
                    });
                } else {
                    // Check combo → add its classes
                    this.selectedCombinations.push({ filiere_id: filiereId, niveau_id: niveauId });
                    comboClassIds.forEach(function(id) {
                        if (self.selectedClassIds.indexOf(id) === -1) {
                            self.selectedClassIds.push(id);
                        }
                    });
                }
            },

            // Does a filière have at least one selected combination?
            hasFiliereSelection(filiereId) {
                return this.selectedCombinations.some(function(c) { return c.filiere_id === filiereId; });
            },

            // Are ALL niveaux of a filière selected?
            isFiliereFullySelected(filiereId) {
                var self = this;
                // Get niveaux that have classes in this filière
                var niveauIds = [];
                this.allClasses.forEach(function(c) {
                    if (c.filiere_id === filiereId && c.niveau_etude_id && niveauIds.indexOf(c.niveau_etude_id) === -1) {
                        niveauIds.push(c.niveau_etude_id);
                    }
                });
                if (niveauIds.length === 0) return false;
                return niveauIds.every(function(nId) { return self.isCombinationSelected(filiereId, nId); });
            },

            // Toggle all niveaux of a filière + sync classes
            toggleAllNiveauxOfFiliere(filiereId) {
                var self = this;
                var niveauIds = [];
                var filiereClassIds = [];
                this.allClasses.forEach(function(c) {
                    if (c.filiere_id === filiereId && c.niveau_etude_id) {
                        if (niveauIds.indexOf(c.niveau_etude_id) === -1) {
                            niveauIds.push(c.niveau_etude_id);
                        }
                        filiereClassIds.push(c.id);
                    }
                });

                if (this.isFiliereFullySelected(filiereId)) {
                    // Remove all combos + classes of this filière
                    this.selectedCombinations = this.selectedCombinations.filter(function(c) {
                        return c.filiere_id !== filiereId;
                    });
                    this.selectedClassIds = this.selectedClassIds.filter(function(id) {
                        return filiereClassIds.indexOf(id) === -1;
                    });
                } else {
                    // Add missing combos + classes
                    niveauIds.forEach(function(nId) {
                        if (!self.isCombinationSelected(filiereId, nId)) {
                            self.selectedCombinations.push({ filiere_id: filiereId, niveau_id: nId });
                        }
                    });
                    filiereClassIds.forEach(function(id) {
                        if (self.selectedClassIds.indexOf(id) === -1) {
                            self.selectedClassIds.push(id);
                        }
                    });
                }
            },

            // Classes matching selected combos (visible in the class list)
            get comboClasses() {
                var self = this;
                return this.allClasses.filter(function(c) {
                    return self.selectedCombinations.some(function(combo) {
                        return combo.filiere_id === c.filiere_id && combo.niveau_id === c.niveau_etude_id;
                    });
                });
            },

            // Resolved classes = only checked ones (for export)
            get resolvedClasses() {
                var self = this;
                return this.comboClasses.filter(function(c) {
                    return self.selectedClassIds.indexOf(c.id) !== -1;
                });
            },

            // Individual class toggle
            isClassSelected(classId) {
                return this.selectedClassIds.indexOf(classId) !== -1;
            },

            toggleClass(classId) {
                var idx = this.selectedClassIds.indexOf(classId);
                if (idx > -1) {
                    this.selectedClassIds.splice(idx, 1);
                } else {
                    this.selectedClassIds.push(classId);
                }
                // Sync combo: if no classes left for a combo, uncheck the combo
                this.syncCombosFromClasses();
            },

            toggleAllClasses() {
                var self = this;
                var comboIds = this.comboClasses.map(function(c) { return c.id; });
                var allChecked = comboIds.every(function(id) { return self.selectedClassIds.indexOf(id) !== -1; });

                if (allChecked) {
                    // Uncheck all visible classes
                    this.selectedClassIds = this.selectedClassIds.filter(function(id) {
                        return comboIds.indexOf(id) === -1;
                    });
                } else {
                    // Check all visible classes
                    comboIds.forEach(function(id) {
                        if (self.selectedClassIds.indexOf(id) === -1) {
                            self.selectedClassIds.push(id);
                        }
                    });
                }
            },

            // Sync: if all classes of a combo are unchecked, remove the combo
            syncCombosFromClasses() {
                var self = this;
                this.selectedCombinations = this.selectedCombinations.filter(function(combo) {
                    var comboClassIds = self.allClasses
                        .filter(function(c) { return c.filiere_id === combo.filiere_id && c.niveau_etude_id === combo.niveau_id; })
                        .map(function(c) { return c.id; });
                    // Keep combo if at least one class is still selected
                    return comboClassIds.some(function(id) { return self.selectedClassIds.indexOf(id) !== -1; });
                });
            },

            // Global toggle states
            get allSelected() {
                return this.allClasses.length > 0 && this.selectedClassIds.length === this.allClasses.length;
            },
            get someSelected() { return this.selectedClassIds.length > 0; },

            toggleAll() {
                var self = this;
                if (this.allSelected) {
                    this.selectedCombinations = [];
                    this.selectedClassIds = [];
                } else {
                    var combos = [];
                    var classIds = [];
                    this.allClasses.forEach(function(c) {
                        if (c.filiere_id && c.niveau_etude_id) {
                            var exists = combos.some(function(x) { return x.filiere_id === c.filiere_id && x.niveau_id === c.niveau_etude_id; });
                            if (!exists) combos.push({ filiere_id: c.filiere_id, niveau_id: c.niveau_etude_id });
                            classIds.push(c.id);
                        }
                    });
                    this.selectedCombinations = combos;
                    this.selectedClassIds = classIds;
                }
            },

            // Export action
            doExport(format) {
                var params = new URLSearchParams();

                var urlParams = new URLSearchParams(window.location.search);
                ['search', 'annee', 'status', 'sexe', 'affectation_status', 'inscrit_annee_courante', 'est_transfert'].forEach(function(key) {
                    if (urlParams.has(key) && urlParams.get(key)) {
                        params.set(key, urlParams.get(key));
                    }
                });

                var form = document.getElementById('search-form');
                if (form) {
                    var formData = new FormData(form);
                    ['search', 'annee', 'status', 'sexe', 'affectation_status', 'inscrit_annee_courante', 'est_transfert'].forEach(function(key) {
                        var val = formData.get(key);
                        if (val && !params.has(key)) {
                            params.set(key, val);
                        }
                    });
                }

                // Send selected class IDs
                if (this.selectedClassIds.length > 0 && this.selectedClassIds.length < this.allClasses.length) {
                    this.selectedClassIds.forEach(function(id) { params.append('classes[]', id); });
                }

                if (this.exportGroupBy) {
                    params.set('group_by', this.exportGroupBy);
                }

                var baseUrl = format === 'excel'
                    ? window.etuIndexConfig.exportExcelUrl
                    : window.etuIndexConfig.exportPdfUrl;

                var modal = bootstrap.Modal.getInstance(document.getElementById('exportModal'));
                if (modal) modal.hide();

                window.location.href = baseUrl + '?' + params.toString();
            }
        };
    }

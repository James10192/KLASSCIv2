/**
 * KLASSCI — Inscriptions Index
 * Gère : filtres AJAX, KPIs filtrables, sort colonnes, per_page, pagination,
 *        row cliquable (JS delegation), bulk actions, modals globaux,
 *        modals actions rapides (valider paiement / changer classe / créer paiement),
 *        refresh single row avec highlight animations, popstate.
 *
 * Dépendances globales :
 *   - window.KLASSCI_INSCRIPTIONS_ROUTES (injectée en inline script dans la vue)
 *   - window.KLASSCI_CSRF_TOKEN
 *   - bootstrap (via bundle Bootstrap 5)
 *   - debugLog/debugWarn/debugError (public/js/debug-helper.js)
 */
(function () {
    'use strict';

    const ROUTES = window.KLASSCI_INSCRIPTIONS_ROUTES || {};
    const CSRF_TOKEN = window.KLASSCI_CSRF_TOKEN || '';
    const HIGHLIGHT_DURATION = 3200;
    const HIGHLIGHT_STATUS_PASS_RATIO = 0.8;

    const resolveRoute = (template, id) => template.replace(':id', String(id));

    // ====================================================================
    // Helpers
    // ====================================================================

    function showToast(message, type = 'success') {
        if (!message) return;
        if (window.toastr && typeof window.toastr[type] === 'function') {
            window.toastr[type](message);
            return;
        }
        // Fallback : alert classique
        alert(message);
    }

    function setRowLoading(inscriptionId, isLoading) {
        const row = document.querySelector(`tr[data-inscription-id="${inscriptionId}"]`);
        if (!row) return;
        row.classList.toggle('is-loading', Boolean(isLoading));
        const wrapper = row.querySelector('.inscription-actions-wrapper');
        if (wrapper) wrapper.classList.toggle('is-loading', Boolean(isLoading));
    }

    function triggerRowHighlight(row, actionType = 'update', options = {}) {
        if (!row) return;
        const isReject = ['reject', 'cancel', 'danger', 'delete'].includes(actionType);
        const onStatusPassed = typeof options.onStatusPassed === 'function' ? options.onStatusPassed : null;

        row.classList.remove('inscription-row-flash', 'reject');
        void row.offsetWidth;

        const highlight = document.createElement('div');
        highlight.className = 'inscription-row-highlight';
        if (isReject) highlight.classList.add('reject');
        row.appendChild(highlight);

        requestAnimationFrame(() => highlight.classList.add('animate'));

        if (onStatusPassed) {
            setTimeout(() => onStatusPassed(highlight), HIGHLIGHT_DURATION * HIGHLIGHT_STATUS_PASS_RATIO);
        }

        highlight.addEventListener('animationend', () => highlight.remove(), { once: true });
        row.classList.add('inscription-row-flash');
        if (isReject) row.classList.add('reject');
        setTimeout(() => row.classList.remove('inscription-row-flash', 'reject'), 1200);
    }

    function getFormURL(form, extraParams = {}) {
        const formData = new FormData(form);
        for (const [key, value] of Object.entries(extraParams)) {
            formData.set(key, value);
        }
        return `${form.action}?${new URLSearchParams(formData).toString()}`;
    }

    // ====================================================================
    // AJAX : fetch results et re-render
    // ====================================================================

    const form = document.getElementById('inscriptions-filter-form');
    const resultsContainer = document.getElementById('inscriptions-results');
    const countSpan = document.getElementById('ii-result-count');

    function setLoading(isLoading) {
        if (resultsContainer) resultsContainer.style.opacity = isLoading ? '0.5' : '1';
    }

    function fetchResults(url, options = {}) {
        if (!url) return Promise.resolve();
        setLoading(true);
        return fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            credentials: 'same-origin',
        })
            .then((response) => {
                if (!response.ok) throw new Error('Erreur lors du chargement.');
                return response.json();
            })
            .then((data) => {
                resultsContainer.innerHTML = data.html;
                if (options.pushState !== false) {
                    window.history.pushState({ url: data.url }, '', data.url);
                }
                bindPaginationLinks();
                bindBulkSelection();
                bindPerPageSelect();
                bindSortLinks();
                updateActiveFilterChips();
                updateKpiActiveState();
                clearSelection();
                if (countSpan) {
                    const totalSpan = resultsContainer.querySelector('.ii-per-page-hint');
                    if (totalSpan) {
                        // "par page · N inscription(s) au total"
                        const match = totalSpan.textContent.match(/(\d+)\s+inscription/);
                        if (match) countSpan.textContent = match[1];
                    }
                }
            })
            .catch((err) => {
                debugError('[inscriptions] fetchResults error:', err);
                showToast('Impossible de charger les inscriptions. Veuillez réessayer.', 'error');
            })
            .finally(() => setLoading(false));
    }

    function submitFilterForm() {
        if (!form) return;
        fetchResults(getFormURL(form), { pushState: true });
    }

    // ====================================================================
    // Filter form handlers
    // ====================================================================

    if (form) {
        let searchDebounce = null;
        const searchInput = form.querySelector('#filter-search');
        const selects = form.querySelectorAll('select');

        form.addEventListener('submit', (e) => {
            e.preventDefault();
            e.stopPropagation();
            submitFilterForm();
            return false;
        });

        selects.forEach((select) => {
            select.addEventListener('change', submitFilterForm);
        });

        if (searchInput) {
            searchInput.addEventListener('input', () => {
                clearTimeout(searchDebounce);
                searchDebounce = setTimeout(submitFilterForm, 400);
            });
        }

        const resetBtn = document.getElementById('reset-filters-btn');
        if (resetBtn) {
            resetBtn.addEventListener('click', () => {
                form.querySelectorAll('input[type="text"], input[type="search"]').forEach((i) => (i.value = ''));
                form.querySelectorAll('select').forEach((s) => (s.value = s.querySelector('option').value));
                document.getElementById('sort-input').value = 'created_at';
                document.getElementById('dir-input').value = 'desc';
                submitFilterForm();
            });
        }
    }

    // ====================================================================
    // KPIs cliquables
    // ====================================================================

    function updateKpiActiveState() {
        const currentStatus = new URL(window.location.href).searchParams.get('status') || 'active';
        document.querySelectorAll('#ii-kpis .ii-kpi').forEach((kpi) => {
            const filter = kpi.dataset.kpiFilter;
            kpi.classList.toggle('ii-kpi--active', filter === currentStatus);
        });
    }

    document.querySelectorAll('#ii-kpis .ii-kpi').forEach((kpi) => {
        kpi.addEventListener('click', () => {
            const filter = kpi.dataset.kpiFilter;
            const statusSelect = form ? form.querySelector('#status') : null;
            if (!statusSelect) return;
            // Reclick KPI actif = reset vers "all"
            const currentStatus = new URL(window.location.href).searchParams.get('status') || 'active';
            statusSelect.value = filter === currentStatus ? 'all' : filter;
            submitFilterForm();
        });
    });

    // ====================================================================
    // Active filter chips
    // ====================================================================

    function updateActiveFilterChips() {
        const container = document.getElementById('ii-active-filters');
        if (!container || !form) return;
        container.innerHTML = '';
        const chips = [];
        const search = form.querySelector('#filter-search');
        if (search && search.value) {
            chips.push({ key: 'search', label: `Recherche : « ${search.value} »`, input: search });
        }
        const filterLabels = {
            filiere: 'Filière',
            niveau: 'Niveau',
            annee: 'Année',
            status: 'Statut',
        };
        Object.keys(filterLabels).forEach((key) => {
            const sel = form.querySelector(`#${key}`);
            if (!sel || !sel.value || (key === 'status' && sel.value === 'active')) return;
            if (key === 'annee' && !sel.value) return;
            const label = sel.options[sel.selectedIndex]?.text || sel.value;
            chips.push({ key, label: `${filterLabels[key]} : ${label}`, input: sel });
        });

        chips.forEach((chip) => {
            const el = document.createElement('span');
            el.className = 'ii-chip-active';
            el.innerHTML = `<span>${chip.label}</span><button type="button" aria-label="Retirer"><i class="fas fa-times"></i></button>`;
            el.querySelector('button').addEventListener('click', () => {
                if (chip.key === 'status') {
                    chip.input.value = 'active';
                } else {
                    chip.input.value = '';
                }
                submitFilterForm();
            });
            container.appendChild(el);
        });
    }

    // ====================================================================
    // Sort columns (intercept link clicks, update hidden inputs, AJAX)
    // ====================================================================

    function bindSortLinks() {
        resultsContainer.querySelectorAll('.ii-sort-link').forEach((link) => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const url = new URL(link.href);
                const newSort = url.searchParams.get('sort');
                const newDir = url.searchParams.get('dir');
                if (newSort) document.getElementById('sort-input').value = newSort;
                if (newDir) document.getElementById('dir-input').value = newDir;
                submitFilterForm();
            });
        });
    }

    // ====================================================================
    // Per-page selector
    // ====================================================================

    function bindPerPageSelect() {
        const select = resultsContainer.querySelector('#ii-per-page-select');
        if (!select) return;
        select.addEventListener('change', () => {
            document.getElementById('per-page-input').value = select.value;
            const url = new URL(form.action);
            const formData = new FormData(form);
            new URLSearchParams(formData).forEach((v, k) => url.searchParams.set(k, v));
            url.searchParams.delete('page');
            fetchResults(url.toString(), { pushState: true });
        });
    }

    // ====================================================================
    // Pagination AJAX
    // ====================================================================

    function bindPaginationLinks() {
        resultsContainer.querySelectorAll('.pagination a').forEach((link) => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                fetchResults(link.href, { pushState: true });
            });
        });
    }

    // ====================================================================
    // Row click (JS delegation, skip if target is interactive)
    // ====================================================================

    resultsContainer.addEventListener('click', (e) => {
        const row = e.target.closest('tr.ii-row');
        if (!row) return;
        const href = row.dataset.rowHref;
        if (!href) return;
        if (e.target.closest('button, a, input, label, .dropdown, [data-no-row-click]')) return;
        // Middle-click = ouvre dans nouvel onglet
        if (e.button === 1 || e.ctrlKey || e.metaKey) {
            window.open(href, '_blank');
            return;
        }
        window.location.href = href;
    });

    // ====================================================================
    // Bulk selection
    // ====================================================================

    function updateSelectionCount() {
        const count = document.querySelectorAll('.inscription-checkbox:checked').length;
        const bar = document.getElementById('ii-bulk-bar');
        const span = document.getElementById('ii-selected-count');
        if (span) span.textContent = count;
        if (bar) bar.classList.toggle('ii-bulk-bar--visible', count > 0);
    }

    function bindBulkSelection() {
        const selectAll = document.getElementById('select-all-inscriptions');
        if (selectAll) {
            selectAll.addEventListener('change', function () {
                document.querySelectorAll('.inscription-checkbox').forEach((cb) => {
                    cb.checked = this.checked;
                });
                updateSelectionCount();
            });
        }
        document.querySelectorAll('.inscription-checkbox').forEach((cb) => {
            cb.addEventListener('change', () => {
                updateSelectionCount();
                const all = document.querySelectorAll('.inscription-checkbox');
                const checked = document.querySelectorAll('.inscription-checkbox:checked');
                const sa = document.getElementById('select-all-inscriptions');
                if (sa) sa.checked = all.length === checked.length && all.length > 0;
            });
        });
    }

    function clearSelection() {
        document.querySelectorAll('.inscription-checkbox').forEach((cb) => (cb.checked = false));
        const sa = document.getElementById('select-all-inscriptions');
        if (sa) sa.checked = false;
        updateSelectionCount();
    }
    window.iiClearSelection = clearSelection;

    // ====================================================================
    // Bulk actions
    // ====================================================================

    window.iiBulkValider = function () {
        const ids = Array.from(document.querySelectorAll('.inscription-checkbox:checked')).map((cb) => cb.value);
        if (!ids.length) {
            showToast('Veuillez sélectionner au moins une inscription.', 'warning');
            return;
        }
        const confirmMsg = `Valider ${ids.length} inscription(s) ?\n\nLe système :\n• Valide les inscriptions avec paiements validés\n• Auto-valide les paiements en attente si nécessaire\n• Envoie les notifications aux étudiants`;
        if (!confirm(confirmMsg)) return;

        const formData = new FormData();
        formData.append('_token', CSRF_TOKEN);
        ids.forEach((id) => formData.append('inscription_ids[]', id));

        ids.forEach((id) => setRowLoading(id, true));

        fetch(ROUTES.bulkValider, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then((r) => {
                if (!r.ok) throw new Error('Erreur validation.');
                return r.json();
            })
            .then((data) => {
                if (!data.success) {
                    showToast(data.message || 'Validation échouée.', 'error');
                    ids.forEach((id) => setRowLoading(id, false));
                    return;
                }
                if (data.message) {
                    const hasProblems = data.inscriptions_problemes && Object.keys(data.inscriptions_problemes).length > 0;
                    showToast(data.message, hasProblems ? 'warning' : 'success');
                }
                const problems = data.inscriptions_problemes || {};
                Object.values(problems).forEach((p) => p?.message && showToast(p.message, 'warning'));
                ids.forEach((id) => refreshLigne(id, problems[id] ? 'reject' : 'validate'));
                clearSelection();
            })
            .catch((err) => {
                showToast(err.message || 'Erreur validation.', 'error');
                ids.forEach((id) => setRowLoading(id, false));
            });
    };

    window.iiBulkAnnuler = function () {
        const ids = Array.from(document.querySelectorAll('.inscription-checkbox:checked')).map((cb) => cb.value);
        if (!ids.length) return;
        showToast(`Action bulk Annuler en cours de développement (${ids.length} sélection(s))`, 'info');
        // TODO PR2 : endpoint bulk-annuler + modal motif
    };

    window.iiBulkExporter = function () {
        const ids = Array.from(document.querySelectorAll('.inscription-checkbox:checked')).map((cb) => cb.value);
        if (!ids.length) return;
        showToast(`Export de la sélection en cours de développement (${ids.length} sélection(s))`, 'info');
        // TODO PR2 : endpoint export sélection
    };

    // ====================================================================
    // Refresh single row
    // ====================================================================

    function refreshLigne(inscriptionId, actionType = 'update') {
        const row = document.querySelector(`tr[data-inscription-id="${inscriptionId}"]`);
        if (!row) {
            debugWarn('[inscriptions] row not found for refresh:', inscriptionId);
            return;
        }
        const checkbox = row.querySelector('.inscription-checkbox');
        const wasChecked = checkbox ? checkbox.checked : false;

        setRowLoading(inscriptionId, true);

        fetch(resolveRoute(ROUTES.refreshLigne, inscriptionId), {
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        })
            .then((r) => {
                if (!r.ok) throw new Error(`HTTP ${r.status}`);
                return r.json();
            })
            .then((data) => {
                if (!data.success || !data.html) throw new Error('Réponse invalide');
                const template = document.createElement('template');
                template.innerHTML = data.html.trim();
                const newRow = template.content.querySelector(`tr[data-inscription-id="${inscriptionId}"]`)
                    || template.content.querySelector('tr[data-inscription-id]');
                if (!newRow) throw new Error('HTML sans tr valide');

                let updated = false;
                const applyUpdate = (highlightEl) => {
                    if (updated) return;
                    updated = true;
                    const highlightNode = highlightEl || row.querySelector('.inscription-row-highlight');
                    const preserveClasses = ['inscription-row-flash', 'reject', 'is-loading'].filter((c) =>
                        row.classList.contains(c)
                    );
                    row.setAttribute('class', newRow.getAttribute('class') || '');
                    preserveClasses.forEach((c) => row.classList.add(c));

                    Array.from(newRow.attributes).forEach((attr) => {
                        if (attr.name !== 'class') row.setAttribute(attr.name, attr.value);
                    });

                    const currentCells = Array.from(row.children).filter((c) => c !== highlightNode);
                    const newCells = Array.from(newRow.children).map((c) => c.cloneNode(true));

                    currentCells.forEach((cell, idx) => {
                        if (newCells[idx]) cell.replaceWith(newCells[idx]);
                        else cell.remove();
                    });
                    newCells.slice(currentCells.length).forEach((node) => {
                        if (highlightNode && highlightNode.parentNode === row) {
                            row.insertBefore(node, highlightNode);
                        } else {
                            row.appendChild(node);
                        }
                    });
                    if (highlightNode && highlightNode.parentNode !== row) row.appendChild(highlightNode);

                    if (wasChecked) {
                        const cb = row.querySelector('.inscription-checkbox');
                        if (cb) {
                            cb.checked = true;
                            cb.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                    }
                    setRowLoading(inscriptionId, false);
                    updateSelectionCount();
                };

                triggerRowHighlight(row, actionType, {
                    onStatusPassed: applyUpdate,
                });
                setTimeout(() => {
                    if (!updated) applyUpdate();
                }, HIGHLIGHT_DURATION + 150);
            })
            .catch((err) => {
                debugError('[inscriptions] refresh ligne error:', err);
                setRowLoading(inscriptionId, false);
                showToast('Erreur lors de la mise à jour.', 'error');
            });
    }
    window.refreshInscriptionLigne = refreshLigne;

    // ====================================================================
    // Modals globaux (annuler + delete) — data-id dynamique
    // ====================================================================

    const annulerModalEl = document.getElementById('ii-modal-annuler');
    if (annulerModalEl) {
        annulerModalEl.addEventListener('show.bs.modal', (event) => {
            const trigger = event.relatedTarget;
            if (!trigger) return;
            const id = trigger.dataset.inscriptionId;
            const name = trigger.dataset.studentName || '—';
            document.getElementById('ii-annuler-student-name').textContent = name;
            document.getElementById('ii-form-annuler').action = resolveRoute(ROUTES.annuler, id);
        });
    }

    const deleteModalEl = document.getElementById('ii-modal-delete');
    if (deleteModalEl) {
        deleteModalEl.addEventListener('show.bs.modal', (event) => {
            const trigger = event.relatedTarget;
            if (!trigger) return;
            const id = trigger.dataset.inscriptionId;
            const name = trigger.dataset.studentName || '—';
            document.getElementById('ii-delete-student-name').textContent = name;
            document.getElementById('ii-form-delete').action = resolveRoute(ROUTES.destroy, id);
        });
    }

    // ====================================================================
    // Actions rapides — modals valider paiement / changer classe / créer paiement
    // ====================================================================

    window.ouvrirModalValiderPaiement = function (inscriptionId) {
        fetch(resolveRoute(ROUTES.paiementEnAttente, inscriptionId))
            .then((r) => r.json())
            .then((data) => {
                if (!data.success || !data.paiement) {
                    showToast('Impossible de récupérer les informations du paiement.', 'error');
                    return;
                }
                const p = data.paiement;
                document.getElementById('valider_inscription_id').value = inscriptionId;
                document.getElementById('valider_paiement_id').value = p.id;
                document.getElementById('valider_montant').value = new Intl.NumberFormat('fr-FR').format(p.montant) + ' FCFA';
                document.getElementById('valider_mode').value = p.mode_paiement || 'N/A';
                document.getElementById('valider_reference').value = p.reference_paiement || 'N/A';
                document.getElementById('validerPaiementInfo').textContent = `Paiement de ${p.etudiant.nom} ${p.etudiant.prenoms}`;
                document.getElementById('formValiderPaiement').action = resolveRoute(ROUTES.validerPaiementRapide, p.id);
                bootstrap.Modal.getOrCreateInstance(document.getElementById('modalValiderPaiement')).show();
            })
            .catch((err) => {
                debugError('[inscriptions] ouvrirModalValiderPaiement:', err);
                showToast('Erreur lors du chargement.', 'error');
            });
    };

    window.ouvrirModalChangerClasse = function (inscriptionId) {
        fetch(resolveRoute(ROUTES.classesAlternatives, inscriptionId))
            .then((r) => r.json())
            .then((data) => {
                if (!data.success) {
                    showToast(data.message || 'Erreur.', 'error');
                    return;
                }
                document.getElementById('changer_inscription_id').value = inscriptionId;
                document.getElementById('changer_ancienne_classe').value = data.classeActuelle.name;
                const select = document.getElementById('changer_nouvelle_classe');
                select.innerHTML = '<option value="">Sélectionnez une classe</option>';
                data.classesAlternatives.forEach((c) => {
                    const opt = document.createElement('option');
                    opt.value = c.id;
                    opt.textContent = c.is_available
                        ? `${c.name} (${c.places_disponibles}/${c.places_totales} places)`
                        : `${c.name} (COMPLET ${c.places_disponibles}/${c.places_totales})`;
                    opt.dataset.placesDisponibles = c.places_disponibles;
                    opt.dataset.isAvailable = c.is_available ? '1' : '0';
                    if (!c.is_available) opt.style.color = '#991b1b';
                    select.appendChild(opt);
                });

                select.onchange = function () {
                    const opt = this.options[this.selectedIndex];
                    const info = document.getElementById('classeDispoInfo');
                    const text = document.getElementById('classeDispoText');
                    if (!opt.value) {
                        info.style.display = 'none';
                        return;
                    }
                    const available = opt.dataset.isAvailable === '1';
                    info.style.display = 'flex';
                    text.textContent = available
                        ? `${opt.dataset.placesDisponibles} places disponibles`
                        : `Classe complète (${opt.dataset.placesDisponibles} places)`;
                };

                document.getElementById('formChangerClasse').action = resolveRoute(ROUTES.changerClasseRapide, inscriptionId);
                bootstrap.Modal.getOrCreateInstance(document.getElementById('modalChangerClasse')).show();
            })
            .catch((err) => {
                debugError('[inscriptions] ouvrirModalChangerClasse:', err);
                showToast('Erreur lors du chargement.', 'error');
            });
    };

    window.ouvrirModalCreerPaiement = function (inscriptionId) {
        fetch(resolveRoute(ROUTES.inscriptionData, inscriptionId))
            .then((r) => r.json())
            .then((data) => {
                if (!data.success || !data.inscription) {
                    showToast('Impossible de récupérer les informations.', 'error');
                    return;
                }
                const ins = data.inscription;
                document.getElementById('creer_inscription_id').value = inscriptionId;
                document.getElementById('creer_etudiant_id').value = ins.etudiant_id;
                document.getElementById('creer_annee_id').value = ins.annee_universitaire_id;
                document.getElementById('creerPaiementInfo').textContent =
                    `Créer un paiement pour ${ins.etudiant.nom} ${ins.etudiant.prenoms}`;
                document.getElementById('formCreerPaiement').action = resolveRoute(ROUTES.validerAvecPaiement, inscriptionId);
                bootstrap.Modal.getOrCreateInstance(document.getElementById('modalCreerPaiement')).show();
            })
            .catch((err) => {
                debugError('[inscriptions] ouvrirModalCreerPaiement:', err);
                showToast('Erreur lors du chargement.', 'error');
            });
    };

    // ====================================================================
    // Handlers génériques pour les 3 modals action rapide (submit AJAX)
    // ====================================================================

    function bindQuickActionForm(formId, modalId, actionType = 'update') {
        const formEl = document.getElementById(formId);
        const modalEl = document.getElementById(modalId);
        if (!formEl || !modalEl) return;
        let submitting = false;

        formEl.addEventListener('submit', function (e) {
            e.preventDefault();
            if (submitting) return false;
            submitting = true;

            const submitBtn = this.querySelector('button[type="submit"]');
            const originalHTML = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Traitement...';

            const inscriptionId =
                this.querySelector('[name="inscription_id"]')?.value
                || this.querySelector('input[type="hidden"]')?.value;

            fetch(this.action, {
                method: 'POST',
                body: new FormData(this),
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            })
                .then((r) => r.json())
                .then((data) => {
                    if (data.success) {
                        bootstrap.Modal.getInstance(modalEl).hide();
                        if (inscriptionId) refreshLigne(inscriptionId, actionType);
                        if (data.message) showToast(data.message, 'success');
                    } else {
                        showToast(data.message || 'Erreur.', 'error');
                    }
                })
                .catch((err) => {
                    debugError('[inscriptions] quick action:', err);
                    showToast('Erreur lors de la soumission.', 'error');
                })
                .finally(() => {
                    submitting = false;
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalHTML;
                });
        });
    }

    bindQuickActionForm('formValiderPaiement', 'modalValiderPaiement', 'validate');
    bindQuickActionForm('formChangerClasse', 'modalChangerClasse', 'update');
    bindQuickActionForm('formCreerPaiement', 'modalCreerPaiement', 'update');

    // ====================================================================
    // Valider button (PUT form submit inline)
    // ====================================================================

    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.valider-btn');
        if (!btn) return;
        const id = btn.dataset.id;
        if (!id) return;
        if (!confirm('Valider cette inscription ?')) return;
        const form = document.getElementById(`valider-form-${id}`);
        if (!form) return;
        setRowLoading(id, true);
        const formData = new FormData(form);
        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then((r) => r.json())
            .then((data) => {
                if (data.success) {
                    refreshLigne(id, 'validate');
                    if (data.message) showToast(data.message, 'success');
                } else {
                    setRowLoading(id, false);
                    showToast(data.message || 'Erreur validation.', 'error');
                }
            })
            .catch(() => {
                setRowLoading(id, false);
                showToast('Erreur validation.', 'error');
            });
    });

    // ====================================================================
    // popstate : restaurer la vue sur back/forward
    // ====================================================================

    if (window.history && window.history.replaceState) {
        window.history.replaceState({ url: window.location.href }, '', window.location.href);
    }
    window.addEventListener('popstate', (event) => {
        const targetUrl = (event.state && event.state.url) || window.location.href;
        fetchResults(targetUrl, { pushState: false });
    });

    // ====================================================================
    // Init
    // ====================================================================

    bindPaginationLinks();
    bindBulkSelection();
    bindPerPageSelect();
    bindSortLinks();
    updateActiveFilterChips();
    updateKpiActiveState();

    debugLog('[inscriptions] index.js initialized');
})();

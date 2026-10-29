/**
 * administration.js — gestion UI de la page inscriptions/administration.
 * Depend de : common.js (iiConfirm, showToast, updateKpisFromStats).
 *
 * Responsable :
 *  - AJAX filtering (search, selects, KPIs cliquables, sort, per_page)
 *  - pushState + popstate
 *  - Row click navigation
 *  - Bulk annuler + export CSV
 *
 * La validation (openBulkValidationModal, openPaymentModal, openCancelModal...)
 * reste dans le <script> inline de la page qui gere les modals complexes.
 */
(function () {
    'use strict';

    const ROUTES = window.KLASSCI_ADMIN_ROUTES || {};
    const resultsEl = document.getElementById('inscriptions-admin-results');
    const cardEl = document.getElementById('ia-results-card');
    const totalEl = document.getElementById('ia-total');
    const searchInput = document.getElementById('ia-search');
    const filiereSelect = document.getElementById('ia-filiere');
    const niveauSelect = document.getElementById('ia-niveau');
    const anneeSelect = document.getElementById('ia-annee');
    const perPageSelect = document.getElementById('ia-per-page');
    const resetBtn = document.getElementById('ia-reset');

    let searchTimer = null;
    let currentFilters = readFiltersFromUrl();

    // =========================================================================
    // URL & filter management
    // =========================================================================
    function readFiltersFromUrl() {
        const params = new URLSearchParams(window.location.search);
        return {
            search: params.get('search') || '',
            filiere: params.get('filiere') || '',
            niveau: params.get('niveau') || '',
            annee: params.get('annee') || '',
            workflow_step: params.get('workflow_step') || '',
            has_payment: params.get('has_payment') || '',
            sort: params.get('sort') || 'created_at',
            dir: params.get('dir') || 'desc',
            per_page: params.get('per_page') || '25',
            page: params.get('page') || '1',
        };
    }

    function buildUrl(filters) {
        const params = new URLSearchParams();
        Object.entries(filters).forEach(([k, v]) => {
            if (v !== '' && v !== null && v !== undefined) params.set(k, v);
        });
        return ROUTES.administration + (params.toString() ? '?' + params.toString() : '');
    }

    function updateUrl() {
        window.history.pushState({}, '', buildUrl(currentFilters));
    }

    function fetchResults(opts) {
        opts = opts || {};
        if (!opts.keepPage) currentFilters.page = '1';
        updateUrl();

        if (cardEl) cardEl.style.opacity = '0.6';

        fetch(buildUrl(currentFilters), {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        })
            .then((r) => r.json())
            .then((data) => {
                if (data.html && resultsEl) resultsEl.innerHTML = data.html;
                if (data.stats) {
                    window.updateKpisFromStats(data.stats);
                    if (totalEl && typeof data.stats.total_en_attente !== 'undefined') {
                        totalEl.textContent = data.stats.total_en_attente;
                    }
                }
                clearSelection();
                // Rebind legacy handlers after replacement
                if (typeof window.bindInscriptionActions === 'function') window.bindInscriptionActions();
                if (typeof window.bindBulkSelectionHandlers === 'function') window.bindBulkSelectionHandlers();
                if (typeof window.updateInscriptionSelectionCount === 'function') window.updateInscriptionSelectionCount();
            })
            .catch((err) => {
                console.error('ia fetchResults error', err);
                window.showToast('Erreur lors du rechargement', 'error');
            })
            .finally(() => {
                if (cardEl) cardEl.style.opacity = '1';
            });
    }

    // =========================================================================
    // Filter events
    // =========================================================================
    if (searchInput) {
        searchInput.addEventListener('input', () => {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => {
                currentFilters.search = searchInput.value.trim();
                fetchResults();
            }, 400);
        });
    }

    [filiereSelect, niveauSelect, anneeSelect].forEach((sel) => {
        if (!sel) return;
        sel.addEventListener('change', () => {
            if (sel === filiereSelect) currentFilters.filiere = sel.value;
            if (sel === niveauSelect) currentFilters.niveau = sel.value;
            if (sel === anneeSelect) currentFilters.annee = sel.value;
            fetchResults();
        });
    });

    if (perPageSelect) {
        perPageSelect.addEventListener('change', () => {
            currentFilters.per_page = perPageSelect.value;
            fetchResults();
        });
    }

    if (resetBtn) {
        resetBtn.addEventListener('click', () => {
            currentFilters = { sort: 'created_at', dir: 'desc', per_page: '25', page: '1' };
            if (searchInput) searchInput.value = '';
            if (filiereSelect) filiereSelect.value = '';
            if (niveauSelect) niveauSelect.value = '';
            if (anneeSelect) anneeSelect.value = '';
            document.querySelectorAll('.ii-kpi').forEach((k) => k.classList.remove('is-active'));
            const totalKpi = document.querySelector('.ii-kpi[data-kpi="total_en_attente"]');
            if (totalKpi) totalKpi.classList.add('is-active');
            fetchResults();
        });
    }

    // =========================================================================
    // KPIs cliquables
    // =========================================================================
    document.querySelectorAll('.ii-kpi[data-filter-type]').forEach((kpi) => {
        kpi.addEventListener('click', () => {
            const type = kpi.dataset.filterType;
            const value = kpi.dataset.filterValue || '';

            if (type === 'clear') {
                currentFilters.workflow_step = '';
                currentFilters.has_payment = '';
                document.querySelectorAll('.ii-kpi').forEach((k) => k.classList.remove('is-active'));
                kpi.classList.add('is-active');
            } else if (type === 'workflow_step') {
                if (currentFilters.workflow_step === value) {
                    currentFilters.workflow_step = '';
                    kpi.classList.remove('is-active');
                    const totalKpi = document.querySelector('.ii-kpi[data-filter-type="clear"]');
                    if (totalKpi) totalKpi.classList.add('is-active');
                } else {
                    currentFilters.workflow_step = value;
                    currentFilters.has_payment = '';
                    document.querySelectorAll('.ii-kpi').forEach((k) => k.classList.remove('is-active'));
                    kpi.classList.add('is-active');
                }
            } else if (type === 'has_payment') {
                if (currentFilters.has_payment === value) {
                    currentFilters.has_payment = '';
                    kpi.classList.remove('is-active');
                    const totalKpi = document.querySelector('.ii-kpi[data-filter-type="clear"]');
                    if (totalKpi) totalKpi.classList.add('is-active');
                } else {
                    currentFilters.has_payment = value;
                    currentFilters.workflow_step = '';
                    document.querySelectorAll('.ii-kpi').forEach((k) => k.classList.remove('is-active'));
                    kpi.classList.add('is-active');
                }
            }
            fetchResults();
        });
    });

    // =========================================================================
    // Sort columns (delegation, results-area only)
    // =========================================================================
    document.addEventListener('click', (e) => {
        const th = e.target.closest('#inscriptions-admin-results .is-sortable');
        if (!th) return;
        currentFilters.sort = th.dataset.sort;
        currentFilters.dir = th.dataset.nextDir || 'asc';
        fetchResults();
    });

    // =========================================================================
    // Row click navigation
    // =========================================================================
    document.addEventListener('click', (e) => {
        const row = e.target.closest('#inscriptions-admin-results tr[data-href]');
        if (!row) return;
        if (e.target.closest('[data-no-row-click], a, button, input, .dropdown')) return;
        window.location.href = row.dataset.href;
    });

    // =========================================================================
    // Pagination (AJAX)
    // =========================================================================
    document.addEventListener('click', (e) => {
        const link = e.target.closest('#inscriptions-admin-results .pagination a');
        if (!link) return;
        e.preventDefault();
        try {
            const url = new URL(link.href, window.location.origin);
            const page = url.searchParams.get('page') || '1';
            currentFilters.page = page;
            fetchResults({ keepPage: true });
        } catch (_) {
            window.location.href = link.href;
        }
    });

    // =========================================================================
    // Override legacy bulk-bar display to use flex when visible
    // =========================================================================
    const originalUpdate = window.updateInscriptionSelectionCount;
    window.updateInscriptionSelectionCount = function () {
        if (typeof originalUpdate === 'function') originalUpdate();
        const bulkBar = document.getElementById('bulk-actions-bar');
        if (!bulkBar) return;
        const count = document.querySelectorAll('.inscription-checkbox:checked').length;
        if (count > 0) {
            bulkBar.classList.add('is-visible');
            bulkBar.style.display = '';
        } else {
            bulkBar.classList.remove('is-visible');
            bulkBar.style.display = 'none';
        }
    };

    function clearSelection() {
        document.querySelectorAll('.inscription-checkbox').forEach((cb) => (cb.checked = false));
        const sa = document.getElementById('select-all-inscriptions');
        if (sa) sa.checked = false;
        if (typeof window.updateInscriptionSelectionCount === 'function') window.updateInscriptionSelectionCount();
    }

    function getSelectedIds() {
        return Array.from(document.querySelectorAll('.inscription-checkbox:checked')).map((cb) => cb.value);
    }

    // =========================================================================
    // Bulk annuler + export
    // =========================================================================
    window.iaBulkAnnuler = function () {
        const ids = getSelectedIds();
        if (ids.length === 0) {
            window.showToast('Aucune inscription sélectionnée', 'warning');
            return;
        }
        const modalEl = document.getElementById('ia-modal-bulk-annuler');
        if (!modalEl) return;
        document.getElementById('ia-bulk-annuler-count').textContent = ids.length;
        document.getElementById('ia-bulk-annuler-motif').value = '';
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();

        const submitBtn = document.getElementById('ia-bulk-annuler-submit');
        const handler = async () => {
            const motif = document.getElementById('ia-bulk-annuler-motif').value.trim();
            if (motif.length < 3) {
                window.showToast('Motif requis (minimum 3 caractères)', 'warning');
                return;
            }
            submitBtn.disabled = true;
            try {
                const res = await fetch(ROUTES.bulkAnnuler, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': ROUTES.csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ inscription_ids: ids, motif }),
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    modal.hide();
                    window.showToast(`${data.success_count} inscription(s) annulée(s)`, 'success');
                    fetchResults();
                } else {
                    window.showToast(data.message || 'Erreur lors du bulk', 'error');
                }
            } catch (err) {
                console.error(err);
                window.showToast('Erreur réseau', 'error');
            } finally {
                submitBtn.disabled = false;
            }
        };

        submitBtn.onclick = handler;
    };

    window.iaBulkExporter = function () {
        const ids = getSelectedIds();
        if (ids.length === 0) {
            window.showToast('Aucune inscription sélectionnée', 'warning');
            return;
        }
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = ROUTES.bulkExport;
        form.style.display = 'none';
        form.innerHTML = `<input type="hidden" name="_token" value="${ROUTES.csrf}">`;
        ids.forEach((id) => {
            const inp = document.createElement('input');
            inp.type = 'hidden';
            inp.name = 'inscription_ids[]';
            inp.value = id;
            form.appendChild(inp);
        });
        document.body.appendChild(form);
        form.submit();
        form.remove();
    };

    // =========================================================================
    // Browser back/forward
    // =========================================================================
    window.addEventListener('popstate', () => {
        currentFilters = readFiltersFromUrl();
        fetchResults({ keepPage: true });
    });
})();

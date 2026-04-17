/**
 * sous-reserve.js — gestion de la page inscriptions/sous-reserve.
 * Depend de : common.js (iiConfirm, showToast, updateKpisFromStats).
 */
(function () {
    'use strict';

    const ROUTES = window.KLASSCI_SOUSRESERVE_ROUTES || {};
    const resultsEl = document.getElementById('isr-results');
    const cardEl = document.getElementById('isr-results-card');
    const totalEl = document.getElementById('isr-total');
    const searchInput = document.getElementById('isr-search');
    const anneeSelect = document.getElementById('isr-annee');
    const conditionSelect = document.getElementById('isr-condition');
    const perPageSelect = document.getElementById('isr-per-page');
    const resetBtn = document.getElementById('isr-reset');
    const bulkBar = document.getElementById('isr-bulk-bar');
    const bulkCount = document.getElementById('isr-bulk-count');

    let searchTimer = null;
    let currentFilters = readFiltersFromUrl();

    // =========================================================================
    // URL & filter management
    // =========================================================================
    function readFiltersFromUrl() {
        const params = new URLSearchParams(window.location.search);
        return {
            search: params.get('search') || '',
            annee_id: params.get('annee_id') || '',
            condition: params.get('condition') || '',
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
        return ROUTES.index + (params.toString() ? '?' + params.toString() : '');
    }

    function updateUrl() {
        const url = buildUrl(currentFilters);
        window.history.pushState({}, '', url);
    }

    function fetchResults(opts) {
        opts = opts || {};
        if (!opts.keepPage) currentFilters.page = '1';
        updateUrl();

        if (cardEl) cardEl.style.opacity = '0.6';

        const url = buildUrl(currentFilters);
        fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        })
            .then((r) => r.json())
            .then((data) => {
                if (data.html && resultsEl) resultsEl.innerHTML = data.html;
                if (data.stats) {
                    window.updateKpisFromStats(data.stats);
                    if (totalEl && typeof data.stats.total !== 'undefined') totalEl.textContent = data.stats.total;
                }
                clearSelection();
            })
            .catch((err) => {
                console.error('isr fetchResults error', err);
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

    [anneeSelect, conditionSelect].forEach((sel) => {
        if (!sel) return;
        sel.addEventListener('change', () => {
            if (sel === anneeSelect) currentFilters.annee_id = sel.value;
            if (sel === conditionSelect) currentFilters.condition = sel.value;
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
            if (anneeSelect) anneeSelect.value = '';
            if (conditionSelect) conditionSelect.value = '';
            document.querySelectorAll('.ii-kpi').forEach((k) => k.classList.remove('is-active'));
            const totalKpi = document.querySelector('.ii-kpi[data-kpi="total"]');
            if (totalKpi) totalKpi.classList.add('is-active');
            fetchResults();
        });
    }

    // =========================================================================
    // KPIs cliquables (filter par statut paiement)
    // =========================================================================
    document.querySelectorAll('.ii-kpi[data-filter-payment]').forEach((kpi) => {
        kpi.addEventListener('click', () => {
            const target = kpi.dataset.filterPayment || '';
            if (currentFilters.has_payment === target) {
                currentFilters.has_payment = '';
                document.querySelectorAll('.ii-kpi').forEach((k) => k.classList.remove('is-active'));
                const totalKpi = document.querySelector('.ii-kpi[data-kpi="total"]');
                if (totalKpi) totalKpi.classList.add('is-active');
            } else {
                currentFilters.has_payment = target;
                document.querySelectorAll('.ii-kpi').forEach((k) => k.classList.remove('is-active'));
                kpi.classList.add('is-active');
            }
            fetchResults();
        });
    });

    // =========================================================================
    // Sort columns (delegation)
    // =========================================================================
    document.addEventListener('click', (e) => {
        const th = e.target.closest('#isr-results .is-sortable');
        if (!th) return;
        const sortCol = th.dataset.sort;
        const nextDir = th.dataset.nextDir || 'asc';
        currentFilters.sort = sortCol;
        currentFilters.dir = nextDir;
        fetchResults();
    });

    // =========================================================================
    // Row click navigation
    // =========================================================================
    document.addEventListener('click', (e) => {
        const row = e.target.closest('#isr-results tr[data-href]');
        if (!row) return;
        if (e.target.closest('[data-no-row-click], a, button, input')) return;
        window.location.href = row.dataset.href;
    });

    // =========================================================================
    // Pagination (AJAX)
    // =========================================================================
    document.addEventListener('click', (e) => {
        const link = e.target.closest('#isr-results .pagination a');
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
    // Checkboxes / bulk
    // =========================================================================
    document.addEventListener('change', (e) => {
        if (e.target.id === 'isr-select-all') {
            document.querySelectorAll('.isr-row-checkbox').forEach((cb) => (cb.checked = e.target.checked));
            updateBulkBar();
        } else if (e.target.classList.contains('isr-row-checkbox')) {
            const all = document.querySelectorAll('.isr-row-checkbox');
            const checked = document.querySelectorAll('.isr-row-checkbox:checked');
            const selectAll = document.getElementById('isr-select-all');
            if (selectAll) selectAll.checked = all.length > 0 && checked.length === all.length;
            updateBulkBar();
        }
    });

    function updateBulkBar() {
        const count = document.querySelectorAll('.isr-row-checkbox:checked').length;
        if (count > 0) {
            bulkBar.classList.add('is-visible');
            bulkCount.textContent = count;
        } else {
            bulkBar.classList.remove('is-visible');
        }
    }

    function getSelectedIds() {
        return Array.from(document.querySelectorAll('.isr-row-checkbox:checked')).map((cb) => cb.value);
    }

    function clearSelection() {
        document.querySelectorAll('.isr-row-checkbox').forEach((cb) => (cb.checked = false));
        const sa = document.getElementById('isr-select-all');
        if (sa) sa.checked = false;
        updateBulkBar();
    }
    window.isrClearSelection = clearSelection;

    // =========================================================================
    // Actions
    // =========================================================================
    window.isrLeverReserve = async function (id, label) {
        const ok = await window.iiConfirm({
            title: 'Lever la réserve',
            message: `Confirmer la levée de réserve pour ${label} ? L'inscription sera marquée comme confirmée.`,
            confirmLabel: 'Lever la réserve',
        });
        if (!ok) return;

        const res = await fetch(ROUTES.leverReservesBulk, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': ROUTES.csrf,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ inscription_ids: [id] }),
        });
        const data = await res.json().catch(() => ({}));
        if (res.ok && data.success) {
            window.showToast(data.message || 'Réserve levée avec succès', 'success');
            fetchResults({ keepPage: true });
        } else {
            window.showToast(data.message || 'Erreur lors de la levée de réserve', 'error');
        }
    };

    window.isrAnnuler = async function (id, label) {
        const ok = await window.iiConfirm({
            title: "Annuler l'inscription",
            message: `Confirmer l'annulation de l'inscription de ${label} ? Cette action est irréversible.`,
            confirmLabel: 'Annuler',
            cancelLabel: 'Retour',
            danger: true,
        });
        if (!ok) return;

        // Use legacy annuler endpoint via form POST with _method=PUT
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/esbtp/inscriptions/${id}/annuler`;
        form.style.display = 'none';
        form.innerHTML = `
            <input type="hidden" name="_token" value="${ROUTES.csrf}">
            <input type="hidden" name="_method" value="PUT">
            <input type="hidden" name="motif_annulation" value="Condition de réserve non remplie">
        `;
        document.body.appendChild(form);
        form.submit();
    };

    window.isrBulkLever = async function () {
        const ids = getSelectedIds();
        if (ids.length === 0) return;
        const ok = await window.iiConfirm({
            title: 'Lever les réserves',
            message: `Lever la réserve pour ${ids.length} inscription(s) ? Les documents afficheront "Est régulièrement inscrit(e)" sans mention sous réserve.`,
            confirmLabel: `Lever ${ids.length} réserve(s)`,
        });
        if (!ok) return;

        const res = await fetch(ROUTES.leverReservesBulk, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': ROUTES.csrf,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ inscription_ids: ids }),
        });
        const data = await res.json().catch(() => ({}));
        if (res.ok && data.success) {
            window.showToast(data.message || `${data.count} réserve(s) levée(s)`, 'success');
            fetchResults({ keepPage: true });
        } else {
            window.showToast(data.message || 'Erreur lors du bulk', 'error');
        }
    };

    window.isrBulkExporter = function () {
        const ids = getSelectedIds();
        if (ids.length === 0) return;
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

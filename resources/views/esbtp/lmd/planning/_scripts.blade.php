{{-- Alpine factory for lpPlanning + UE expand/collapse delegation.
     Tour and help modal scripts live in _tour_help_scripts.blade.php. --}}
@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('lpPlanning', () => ({
        loading: false,
        filters: {
            parcours_id: @json($filters['parcours_id']),
            niveau_id: @json($filters['niveau_id']),
            semestre: @json($filters['semestre']),
        },
        partialUrl: @json(route('esbtp.lmd.planning.partial')),
        pageUrl: @json(route('esbtp.lmd.planning.index')),

        init() {},

        async reload(value, key) {
            // Server-side cascade : just fetch with the new filter, the
            // backend computes the available semestres for the new niveau
            // and re-renders the semestre dropdown HTML accordingly.
            this.filters[key] = value || null;
            await this.fetchPartial();
            this.syncUrl();
        },

        async fetchPartial() {
            this.loading = true;
            try {
                const params = new URLSearchParams();
                Object.entries(this.filters).forEach(([k, v]) => {
                    if (v !== null && v !== '') params.append(k, v);
                });
                const resp = await fetch(this.partialUrl + '?' + params.toString(), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (!resp.ok) throw new Error('HTTP ' + resp.status);
                const json = await resp.json();
                document.getElementById('lpKpis').innerHTML = json.kpis || '';
                const lpContent = document.getElementById('lpContent');
                lpContent.innerHTML = json.listing || '';
                if (window.Alpine && typeof window.Alpine.initTree === 'function') {
                    window.Alpine.initTree(lpContent);
                }
                // Reset cached volume data so lpvRow re-fetches for new filters.
                window._lpvStore = null;
                window._lpvPromise = null;

                // Replace the semestre filter HTML and re-init Alpine on it
                // so the new au-select component options take effect.
                const semestreWrap = document.getElementById('lpFilterSemestre');
                if (semestreWrap && json.filters_semestre) {
                    // Keep the label, only replace the select component (which
                    // sits after it). Easiest : full innerHTML swap restores
                    // the label too because the partial doesn't include it.
                    const label = semestreWrap.querySelector('.lp-filter-label');
                    semestreWrap.innerHTML = (label ? label.outerHTML : '<label class="lp-filter-label">Semestre</label>') + json.filters_semestre;
                    if (window.Alpine && typeof window.Alpine.initTree === 'function') {
                        window.Alpine.initTree(semestreWrap);
                    }
                }

                if (json.filters && json.filters.semestre !== undefined) {
                    this.filters.semestre = json.filters.semestre;
                }

                // Sync data-lpe-context avec le nouveau filiere_id (parcours peut avoir changé).
                const root = document.querySelector('[data-lpe-context]');
                if (root) {
                    try {
                        const ctx = JSON.parse(root.dataset.lpeContext || '{}');
                        ctx.filiere_id = json.filiere_id ?? null;
                        ctx.niveau_id = this.filters.niveau_id;
                        ctx.semestre = this.filters.semestre;
                        root.dataset.lpeContext = JSON.stringify(ctx);
                    } catch (e) { /* noop */ }
                }
            } catch (e) {
                console.error('Planning fetch failed:', e);
            } finally {
                this.loading = false;
            }
        },

        syncUrl() {
            const params = new URLSearchParams();
            Object.entries(this.filters).forEach(([k, v]) => {
                if (v !== null && v !== '') params.append(k, v);
            });
            const newUrl = this.pageUrl + (params.toString() ? '?' + params : '');
            history.replaceState({}, '', newUrl);
            // Garder data-lpe-context synchro pour l'edition inline (filtres
            // niveau/semestre changent → la planif cible change aussi).
            const root = document.querySelector('[data-lpe-context]');
            if (root) {
                try {
                    const ctx = JSON.parse(root.dataset.lpeContext || '{}');
                    ctx.niveau_id = this.filters.niveau_id;
                    ctx.semestre = this.filters.semestre;
                    root.dataset.lpeContext = JSON.stringify(ctx);
                } catch (e) { /* noop */ }
            }
        },
    }));
});

// ---- lpvRow — Volume budget widget (réalisé vs planifié par ECUE) ----
// One bulk fetch per listing render, cached in window._lpvStore so each
// lpvRow component doesn't fire its own request (anti N+1).
window._lpvStore   = null;  // {ecueId: {cm, td, tp}} | null
window._lpvPromise = null;  // shared in-flight Promise | null

window.lpvRow = function () {
    return {
        loaded: false,
        bars: [],
        _handler: null,

        init() {
            const ecueId = parseInt(this.$el.dataset.lpvEcueId, 10);
            if (!ecueId) { this.loaded = true; return; }

            // Already fetched for this render cycle?
            if (window._lpvStore) {
                this._apply(ecueId, window._lpvStore);
                return;
            }

            // Listen for when the shared fetch completes.
            this._handler = (e) => this._apply(ecueId, e.detail || {});
            window.addEventListener('lpv:ready', this._handler);

            // First instance triggers the bulk fetch; others just wait.
            if (!window._lpvPromise) {
                window._lpvPromise = this._bulkFetch();
            }
        },

        _apply(ecueId, store) {
            const d = store[ecueId];
            if (!d) { this.loaded = true; return; }
            this.bars = [
                d.cm && d.cm.planifie + d.cm.realise > 0 ? { type: 'CM', ...d.cm } : null,
                d.td && d.td.planifie + d.td.realise > 0 ? { type: 'TD', ...d.td } : null,
                d.tp && d.tp.planifie + d.tp.realise > 0 ? { type: 'TP', ...d.tp } : null,
            ].filter(Boolean);
            this.loaded = true;
        },

        async _bulkFetch() {
            try {
                const root = document.querySelector('[data-lpe-context]');
                const ctx  = root ? JSON.parse(root.dataset.lpeContext || '{}') : {};
                if (!ctx.filiere_id || !ctx.niveau_id || !ctx.semestre) return;

                const params = new URLSearchParams({
                    filiere_id: ctx.filiere_id,
                    niveau_id:  ctx.niveau_id,
                    semestre:   ctx.semestre,
                    annee_id:   ctx.annee_universitaire_id || '',
                });
                const resp = await fetch(@json(route('esbtp.lmd.planning.volumes')) + '?' + params, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (!resp.ok) return;
                const json = await resp.json();
                window._lpvStore = json.budgets || {};
                window.dispatchEvent(new CustomEvent('lpv:ready', { detail: window._lpvStore }));
            } catch (e) {
                console.error('lpvRow bulk fetch failed:', e);
            }
        },

        destroy() {
            if (this._handler) window.removeEventListener('lpv:ready', this._handler);
        },
    };
};

// UE row expand/collapse — event delegation survives AJAX innerHTML replace.
document.addEventListener('click', function (e) {
    const row = e.target.closest('.js-ue-row');
    if (!row) return;
    const container = row.closest('#lpContent');
    if (!container) return;
    const idx = row.dataset.idx;
    const caret = row.querySelector('.js-ue-caret');
    const ecues = container.querySelectorAll('.js-ecue-row[data-parent-idx="' + idx + '"]');
    const isOpen = caret.classList.toggle('lp-ue-caret-open');
    ecues.forEach(function (tr) { tr.style.display = isOpen ? '' : 'none'; });
});
</script>
@endpush

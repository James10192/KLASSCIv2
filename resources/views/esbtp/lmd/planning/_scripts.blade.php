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
                document.getElementById('lpContent').innerHTML = json.listing || '';

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

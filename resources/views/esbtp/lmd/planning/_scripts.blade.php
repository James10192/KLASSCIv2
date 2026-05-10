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
        semestresMap: {},
        partialUrl: @json(route('esbtp.lmd.planning.partial')),
        pageUrl: @json(route('esbtp.lmd.planning.index')),

        init() {
            try {
                this.semestresMap = JSON.parse(this.$root.dataset.semestresMap || '{}') || {};
            } catch (e) { this.semestresMap = {}; }
        },

        availableSemestres() {
            const niveau = this.filters.niveau_id;
            if (niveau && Array.isArray(this.semestresMap[niveau])) {
                return this.semestresMap[niveau];
            }
            return Array.isArray(this.semestresMap.all) ? this.semestresMap.all : [];
        },

        // Hide options of the semestre native select outside the niveau scope.
        // The au-select component watches the native select and re-renders.
        syncSemestreOptions() {
            const wrap = this.$refs.semestreWrap;
            if (!wrap) return;
            const native = wrap.querySelector('select.au-select-native');
            if (!native) return;
            const allowed = this.availableSemestres();
            const allowedSet = new Set(allowed.map(String));
            Array.from(native.options).forEach(opt => {
                if (opt.value === '' || opt.dataset.placeholder === '1') return;
                opt.hidden = !allowedSet.has(String(opt.value));
                opt.disabled = !allowedSet.has(String(opt.value));
            });
            const cur = String(this.filters.semestre ?? '');
            if (cur !== '' && !allowedSet.has(cur)) {
                this.filters.semestre = null;
                native.value = '';
                native.dispatchEvent(new Event('change', { bubbles: true }));
            }
        },

        async reload(value, key) {
            const newVal = value || null;
            if (key === 'niveau_id') {
                this.filters.niveau_id = newVal;
                const allowed = this.availableSemestres().map(String);
                if (this.filters.semestre && !allowed.includes(String(this.filters.semestre))) {
                    this.filters.semestre = null;
                }
            } else {
                this.filters[key] = newVal;
            }
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
                if (json.semestresMap) {
                    this.semestresMap = json.semestresMap;
                }
                if (json.filters && json.filters.semestre !== undefined) {
                    this.filters.semestre = json.filters.semestre;
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

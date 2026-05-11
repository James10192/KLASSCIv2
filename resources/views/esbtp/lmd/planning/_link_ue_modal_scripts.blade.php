{{-- Alpine factory lpmModal — extracted for no-god-code compliance. --}}
@once
@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('lpmModal', () => ({
        open: false,
        loading: false,
        saving: false,
        error: null,
        parcoursId: null,
        parcoursName: '',
        search: '',
        rows: [],

        init() {
            window.addEventListener('lpm:open', (event) => {
                this.openWith(event.detail || {});
            });
        },

        get filteredRows() {
            if (!this.search.trim()) return this.rows;
            const q = this.search.trim().toLowerCase();
            return this.rows.filter(r =>
                (r.name || '').toLowerCase().includes(q) ||
                (r.code || '').toLowerCase().includes(q)
            );
        },

        get selectedCount() {
            return this.rows.filter(r => r.selected).length;
        },

        async openWith(detail) {
            if (!detail.parcoursId) {
                console.error('lpm:open requires parcoursId in detail');
                return;
            }
            this.parcoursId = detail.parcoursId;
            this.parcoursName = detail.parcoursName || '';
            this.search = '';
            this.error = null;
            this.open = true;
            await this.fetchUes();
        },

        close() {
            this.open = false;
            this.rows = [];
            this.error = null;
        },

        async fetchUes() {
            this.loading = true;
            this.error = null;
            try {
                const url = `/esbtp/lmd/parcours/${this.parcoursId}/ues-disponibles`;
                const resp = await fetch(url, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                if (!resp.ok) throw new Error('HTTP ' + resp.status);
                const json = await resp.json();
                this.rows = this.buildRows(json.liees || [], json.disponibles || []);
            } catch (e) {
                console.error('Failed to load UEs:', e);
                this.error = 'Impossible de charger les UE. Réessayez.';
            } finally {
                this.loading = false;
            }
        },

        buildRows(liees, disponibles) {
            const rows = [];
            for (const ue of liees) {
                rows.push({
                    id: ue.id, code: ue.code || '', name: ue.name,
                    selected: true,
                    semestre: (ue.semestres && ue.semestres[0]) || 1,
                    is_optional: false,
                });
            }
            for (const ue of disponibles) {
                rows.push({
                    id: ue.id, code: ue.code || '', name: ue.name,
                    selected: false, semestre: 1, is_optional: false,
                });
            }
            rows.sort((a, b) => {
                if (a.selected !== b.selected) return b.selected - a.selected;
                return (a.code || a.name).localeCompare(b.code || b.name);
            });
            return rows;
        },

        async submit() {
            this.saving = true;
            this.error = null;
            const payload = {
                ues: this.rows
                    .filter(r => r.selected)
                    .map(r => ({
                        id: r.id,
                        semestres: [parseInt(r.semestre, 10) || 1],
                        is_optional: !!r.is_optional,
                    })),
            };
            try {
                const url = `/esbtp/lmd/parcours/${this.parcoursId}/sync-ues`;
                const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
                const resp = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrf,
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify(payload),
                });
                if (!resp.ok) {
                    const errJson = await resp.json().catch(() => ({}));
                    throw new Error(errJson.message || 'HTTP ' + resp.status);
                }
                const json = await resp.json();
                window.dispatchEvent(new CustomEvent('lpm:saved', { detail: json }));
                this.close();
            } catch (e) {
                console.error('Save failed:', e);
                this.error = e.message || 'Echec de l\'enregistrement.';
            } finally {
                this.saving = false;
            }
        },
    }));
});
</script>
@endpush
@endonce

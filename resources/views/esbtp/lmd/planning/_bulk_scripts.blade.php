{{-- Scripts pour l'edition en masse des planifications LMD.
     - lpbBar    : factory pour la barre flottante (compteur + actions).
     - lpbModal  : factory pour le modal bulk-edit (toggle + values + commit).
     - Bootstrap : ecoute changements checkbox row + select-all dans le listing,
                   maintient un Set d'ECUE selectionnes, dispatch lpb:* events. --}}
@can('lmd.planning.edit')
@push('scripts')
<script>
(function () {
    if (window.__lpbBootstrapped) return;
    window.__lpbBootstrapped = true;

    const csrf       = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const bulkUrl    = @json(route('esbtp.lmd.planifications.bulk-update'));
    const selectedIds = new Set();
    const selectedLabels = new Map();

    // ---------- Helpers DOM listing -> selection ----------
    function rowCheckboxes() {
        return document.querySelectorAll('.lpb-check[data-lpb-ecue-id]');
    }

    function selectAllCheckbox() {
        return document.querySelector('.lpb-check-all');
    }

    function syncRowVisuals() {
        rowCheckboxes().forEach((cb) => {
            const id = parseInt(cb.dataset.lpbEcueId, 10);
            const tr = cb.closest('tr');
            if (!tr) return;
            tr.classList.toggle('lpb-row-selected', selectedIds.has(id));
            cb.checked = selectedIds.has(id);
        });
        const all = selectAllCheckbox();
        if (all) {
            const all_visible_ids = Array.from(rowCheckboxes()).map((cb) => parseInt(cb.dataset.lpbEcueId, 10));
            const all_selected   = all_visible_ids.length > 0 && all_visible_ids.every((id) => selectedIds.has(id));
            const some_selected  = all_visible_ids.some((id) => selectedIds.has(id));
            all.checked = all_selected;
            all.indeterminate = some_selected && !all_selected;
        }
    }

    function emitChanged() {
        window.dispatchEvent(new CustomEvent('lpb:selection-changed', {
            detail: { ids: Array.from(selectedIds), labels: Array.from(selectedIds).map((id) => selectedLabels.get(id) || ('ECUE ' + id)) },
        }));
    }

    function toggleEcue(id, label, checked) {
        if (checked) {
            selectedIds.add(id);
            if (label) selectedLabels.set(id, label);
        } else {
            selectedIds.delete(id);
        }
        syncRowVisuals();
        emitChanged();
    }

    function clearAll() {
        selectedIds.clear();
        syncRowVisuals();
        emitChanged();
    }

    function selectAllVisible(checked) {
        rowCheckboxes().forEach((cb) => {
            const id    = parseInt(cb.dataset.lpbEcueId, 10);
            const label = cb.dataset.lpbEcueLabel || '';
            if (checked) {
                selectedIds.add(id);
                if (label) selectedLabels.set(id, label);
            } else {
                selectedIds.delete(id);
            }
        });
        syncRowVisuals();
        emitChanged();
    }

    // Event delegation : checkboxes peuvent etre re-rendues par AJAX partial.
    document.addEventListener('change', function (e) {
        const t = e.target;
        if (t && t.classList && t.classList.contains('lpb-check')) {
            const id    = parseInt(t.dataset.lpbEcueId, 10);
            const label = t.dataset.lpbEcueLabel || '';
            toggleEcue(id, label, t.checked);
        } else if (t && t.classList && t.classList.contains('lpb-check-all')) {
            selectAllVisible(t.checked);
        }
    });

    // Apres reload AJAX du listing : resynchroniser les rows visibles vs selection persistee.
    window.addEventListener('lpe:planif-updated', syncRowVisuals);
    window.addEventListener('lpb:resync', syncRowVisuals);

    // ---------- Submit AJAX ----------
    async function submitBulk(payload) {
        const resp = await fetch(bulkUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(payload),
        });
        let json = {};
        try { json = await resp.json(); } catch (e) { json = {}; }
        if (!resp.ok && resp.status !== 422) {
            let detail = json.message || ('Erreur HTTP ' + resp.status);
            if (resp.status === 419) detail = 'Votre session a expire. Rechargez la page.';
            else if (resp.status === 403) detail = 'Vous n\'avez plus la permission d\'editer.';
            else if (resp.status === 429) detail = 'Trop de requetes. Patientez 1 minute.';
            const err = new Error(detail);
            err.status = resp.status;
            throw err;
        }
        return json;
    }

    document.addEventListener('alpine:init', () => {
        // ---------- Bar floating ----------
        Alpine.data('lpbBar', () => ({
            count: 0,
            ids: [],
            labels: [],

            init() {
                window.addEventListener('lpb:selection-changed', (e) => {
                    this.ids    = e.detail.ids || [];
                    this.labels = e.detail.labels || [];
                    this.count  = this.ids.length;
                });
            },

            clearSelection() { clearAll(); },

            openModal() {
                if (this.count === 0) return;
                window.dispatchEvent(new CustomEvent('lpb:open', {
                    detail: { ecueIds: this.ids.slice(), ecueLabels: this.labels.slice() },
                }));
            },
        }));

        // ---------- Modal bulk-edit ----------
        Alpine.data('lpbModal', () => ({
            open: false,
            saving: false,
            ecueIds: [],
            ecueLabels: [],
            enabled: {
                volume_horaire_cm: false,
                volume_horaire_td: false,
                volume_horaire_tp: false,
                volume_horaire_projet: false,
                volume_horaire_tpe: false,
                credits_ects: false,
                coefficient: false,
                enseignant_principal_id: false,
            },
            values: {
                volume_horaire_cm: '',
                volume_horaire_td: '',
                volume_horaire_tp: '',
                volume_horaire_projet: '',
                volume_horaire_tpe: '',
                credits_ects: '',
                coefficient: '',
            },
            errorList: [],

            numericFields: [
                { key: 'volume_horaire_cm',     label: 'Volume CM (h)',      min: 0, max: 500, step: 1,    placeholder: 'ex. 30' },
                { key: 'volume_horaire_td',     label: 'Volume TD (h)',      min: 0, max: 500, step: 1,    placeholder: 'ex. 20' },
                { key: 'volume_horaire_tp',     label: 'Volume TP (h)',      min: 0, max: 500, step: 1,    placeholder: 'ex. 10' },
                { key: 'volume_horaire_projet', label: 'Volume Projet (h)',  min: 0, max: 500, step: 1,    placeholder: 'ex. 0' },
                { key: 'volume_horaire_tpe',    label: 'Volume TPE (h)',     min: 0, max: 500, step: 1,    placeholder: 'ex. 60' },
                { key: 'credits_ects',          label: 'Credits ECTS',       min: 0, max: 30,  step: 1,    placeholder: 'ex. 6' },
                { key: 'coefficient',           label: 'Coefficient',        min: 0, max: 10,  step: 0.5,  placeholder: 'ex. 1' },
            ],

            get enabledCount() {
                return Object.values(this.enabled).filter(Boolean).length;
            },

            onOpen(detail) {
                this.ecueIds    = detail.ecueIds || [];
                this.ecueLabels = detail.ecueLabels || [];
                this.errorList  = [];
                // Reset toggles + values a chaque ouverture pour eviter sticky state.
                Object.keys(this.enabled).forEach((k) => { this.enabled[k] = false; });
                Object.keys(this.values).forEach((k) => { this.values[k] = ''; });
                this.open = true;
                this.$nextTick(() => {
                    const native = this.$el.querySelector('input[name="lpb_user_id"]');
                    if (native) native.value = '';
                    const picker = this.$el.querySelector('.au-up');
                    if (picker && picker._x_dataStack && picker._x_dataStack[0]) {
                        picker._x_dataStack[0].currentValue = '';
                    }
                });
            },

            buildPayloadFields() {
                const out = {};
                this.numericFields.forEach((f) => {
                    if (!this.enabled[f.key]) return;
                    const raw = String(this.values[f.key] ?? '').trim();
                    if (raw === '') {
                        out[f.key] = null;
                        return;
                    }
                    const num = f.step < 1 ? parseFloat(raw) : parseInt(raw, 10);
                    if (!Number.isNaN(num)) out[f.key] = num;
                });
                if (this.enabled.enseignant_principal_id) {
                    const native = this.$el.querySelector('input[name="lpb_user_id"]');
                    const v = native ? String(native.value || '').trim() : '';
                    out.enseignant_principal_id = v === '' ? null : parseInt(v, 10);
                }
                return out;
            },

            async commit() {
                if (this.saving || this.enabledCount === 0 || this.ecueIds.length === 0) return;
                const fields = this.buildPayloadFields();
                if (Object.keys(fields).length === 0) {
                    window.lpeShowToast && window.lpeShowToast('Aucun champ valide a appliquer', 'error');
                    return;
                }

                this.saving = true;
                this.errorList = [];
                try {
                    const json = await submitBulk({ ecue_ids: this.ecueIds, fields: fields });
                    if (json.success) {
                        window.lpeShowToast && window.lpeShowToast(
                            json.updated + ' ECUE mis a jour avec succes',
                            'success'
                        );
                        this.open = false;
                        clearAll();
                        // Trigger refetch du listing pour refleter les nouvelles valeurs.
                        window.dispatchEvent(new CustomEvent('lpm:saved'));
                    } else if (json.partial) {
                        window.lpeShowToast && window.lpeShowToast(
                            json.updated + ' / ' + json.total + ' ECUE mis a jour. ' + (json.errors?.length || 0) + ' erreur(s).',
                            'error'
                        );
                        this.errorList = json.errors || [];
                        // On rafraichit quand meme pour montrer les success.
                        window.dispatchEvent(new CustomEvent('lpm:saved'));
                    } else {
                        // Server-side validation (422) ou totale defaite.
                        const msg = json.message
                            || (json.errors ? Object.values(json.errors).flat().join(' · ') : 'Echec total de l\'operation');
                        window.lpeShowToast && window.lpeShowToast(msg, 'error');
                        this.errorList = json.errors || [];
                    }
                } catch (e) {
                    window.lpeShowToast && window.lpeShowToast(e.message || 'Erreur d\'enregistrement', 'error');
                } finally {
                    this.saving = false;
                }
            },
        }));
    });
})();
</script>
@endpush
@endcan

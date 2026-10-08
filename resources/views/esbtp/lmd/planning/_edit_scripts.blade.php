{{-- Scripts pour l'edition inline + modal enseignant.
     Le planning ECUE porte maintenant un POOL de professeurs ; le responsable UE
     reste un choix unique. --}}
@can('lmd.planning.edit')
@push('scripts')
<script>
(function () {
    if (window.__lpeBootstrapped) return;
    window.__lpeBootstrapped = true;

    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const updateUrlTpl = @json(route('esbtp.lmd.planifications.update', ['ecueId' => '__ID__']));
    const updateUeRespUrlTpl = @json(route('esbtp.lmd.ues.update-responsable', ['ueId' => '__ID__']));
    const poolReadUrl = @json(route('esbtp.lmd.planning.teacher-pool'));

    function buildContextParams() {
        const root = document.querySelector('[data-lpe-context]');
        if (!root) return {};
        try { return JSON.parse(root.dataset.lpeContext || '{}'); }
        catch (e) { return {}; }
    }

    window.lpeShowToast = function (message, type) {
        type = type || 'success';
        let toast = document.getElementById('lpeToast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'lpeToast';
            toast.className = 'lpt-toast';
            document.body.appendChild(toast);
        }
        toast.className = 'lpt-toast lpt-toast--' + type + ' lpt-toast--show';
        toast.innerHTML = (type === 'success' ? '<i class="fas fa-check-circle"></i>' : '<i class="fas fa-exclamation-triangle"></i>') + ' ' + message;
        clearTimeout(toast._t);
        toast._t = setTimeout(() => toast.classList.remove('lpt-toast--show'), 2400);
    };

    window.lpeSaveUeResponsable = async function (ueId, responsableId) {
        const url = updateUeRespUrlTpl.replace('__ID__', ueId);
        const resp = await fetch(url, {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ responsable_ue_id: responsableId }),
        });
        let json = {}; try { json = await resp.json(); } catch (e) {}
        if (!resp.ok || !json.success) throw responseError(resp, json);
        return { ue: json.ue };
    };

    window.lpeSavePlanification = async function (ecueId, payload) {
        const ctx = buildContextParams();
        const url = updateUrlTpl.replace('__ID__', ecueId);
        const resp = await fetch(url, {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify(Object.assign({}, ctx, payload)),
        });
        let json = {}; try { json = await resp.json(); } catch (e) {}
        if (!resp.ok || !json.success) throw responseError(resp, json);
        return { planification: json.planification, created: !!json.created };
    };

    function responseError(resp, json) {
        let detail = json.message || ('Erreur HTTP ' + resp.status);
        if (resp.status === 419) detail = 'Votre session a expire. Rechargez la page.';
        else if (resp.status === 403) detail = 'Vous n\'avez plus la permission d\'editer.';
        else if (resp.status === 422 && json.errors) detail = Object.values(json.errors).flat().join(' · ');
        else if (resp.status === 409) detail = json.message || 'Conflit de modification, rechargez la page.';
        const err = new Error(detail); err.status = resp.status; return err;
    }

    async function loadPool(ecueId) {
        const ctx = buildContextParams();
        if (!ecueId || !ctx.filiere_id || !ctx.niveau_id || !ctx.semestre || !ctx.annee_universitaire_id) return { ids: [] };
        const params = new URLSearchParams({
            matiere_id: ecueId,
            filiere_id: ctx.filiere_id,
            niveau_id: ctx.niveau_id,
            semestre: ctx.semestre,
            annee_universitaire_id: ctx.annee_universitaire_id,
        });
        const resp = await fetch(poolReadUrl + '?' + params.toString(), { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' });
        let json = {}; try { json = await resp.json(); } catch (e) {}
        if (!resp.ok) throw responseError(resp, json);
        return json;
    }

    document.addEventListener('alpine:init', () => {
        Alpine.data('lpeCell', () => ({
            ecueId: null, field: '', value: '', originalValue: '', editing: false, saving: false, isDecimal: false,
            init() {
                const ds = this.$el.dataset;
                this.ecueId = parseInt(ds.lpeEcueId, 10) || null;
                this.field = ds.lpeField || '';
                this.value = ds.lpeValue || '';
                this.originalValue = this.value;
                this.isDecimal = ds.lpeDecimal === '1';
            },
            startEdit() {
                if (this.editing || this.saving || !this.ecueId) return;
                this.editing = true;
                this.$nextTick(() => { const input = this.$refs.input; if (input) { input.focus(); input.select(); } });
            },
            cancel() { this.value = this.originalValue; this.editing = false; },
            async commit() {
                if (!this.editing) return;
                const raw = String(this.value).trim();
                const newVal = raw === '' ? null : (this.isDecimal ? parseFloat(raw) : parseInt(raw, 10));
                if (raw !== '' && (Number.isNaN(newVal) || newVal < 0)) { this.flashError(); this.cancel(); return; }
                if (String(newVal) === String(this.originalValue) || (newVal === null && this.originalValue === '')) { this.editing = false; return; }
                this.editing = false; this.saving = true;
                try {
                    const { planification: planif, created } = await window.lpeSavePlanification(this.ecueId, { [this.field]: newVal });
                    this.value = planif[this.field] ?? ''; this.originalValue = this.value;
                    this.$el.classList.add('lpe-cell--saved'); setTimeout(() => this.$el.classList.remove('lpe-cell--saved'), 700);
                    if (created) window.lpeShowToast('Planification creee', 'success');
                    window.dispatchEvent(new CustomEvent('lpe:planif-updated', { detail: { ecueId: this.ecueId, planif, created } }));
                } catch (e) { window.lpeShowToast(e.message || 'Erreur d\'enregistrement', 'error'); this.value = this.originalValue; this.flashError(); }
                finally { this.saving = false; }
            },
            flashError() { this.$el.classList.add('lpe-cell--error'); setTimeout(() => this.$el.classList.remove('lpe-cell--error'), 1200); },
            get displayValue() { if (this.value === '' || this.value == null) return '0'; return this.isDecimal ? parseFloat(this.value).toFixed(2).replace(/\.00$/, '') : this.value; },
        }));

        Alpine.data('lpeTeacherTrigger', () => ({
            ecueId: null, currentTeacherId: '', currentTeacherName: '', ecueLabel: '',
            init() {
                const ds = this.$el.dataset; this.ecueId = parseInt(ds.lpeEcueId, 10) || null; this.currentTeacherId = ds.lpeTeacherId || ''; this.currentTeacherName = ds.lpeTeacherName || ''; this.ecueLabel = ds.lpeEcueLabel || '';
            },
            openPicker() {
                if (!this.ecueId) return;
                window.dispatchEvent(new CustomEvent('lpt:open', { detail: { role: 'enseignant_ecue', ecueId: this.ecueId, currentTeacherId: this.currentTeacherId, targetLabel: this.ecueLabel ? ('ECUE : ' + this.ecueLabel) : '', triggerEl: this.$el } }));
            },
        }));

        Alpine.data('lpeResponsableTrigger', () => ({
            ueId: null, currentTeacherId: '', currentTeacherName: '', ueLabel: '',
            init() { const ds = this.$el.dataset; this.ueId = parseInt(ds.lpeUeId, 10) || null; this.currentTeacherId = ds.lpeTeacherId || ''; this.currentTeacherName = ds.lpeTeacherName || ''; this.ueLabel = ds.lpeUeLabel || ''; },
            openPicker() {
                if (!this.ueId) return;
                window.dispatchEvent(new CustomEvent('lpt:open', { detail: { role: 'responsable_ue', ueId: this.ueId, currentTeacherId: this.currentTeacherId, targetLabel: this.ueLabel ? ('UE : ' + this.ueLabel) : '', triggerEl: this.$el } }));
            },
        }));

        Alpine.data('lptModal', () => ({
            open: false, saving: false, loadingPool: false, role: 'enseignant_ecue', ecueId: null, ueId: null, targetLabel: '', currentTeacherId: '', selectedId: '', selectedIds: [], teacherSearch: '', triggerEl: null, _nativeChangeHandler: null,
            init() {
                this._nativeChangeHandler = ev => {
                    if (ev.target?.matches?.('input[name="lpt_user_id"]')) this.selectedId = String(ev.target.value || '');
                };
                this.$el.addEventListener('change', this._nativeChangeHandler, true);
            },
            destroy() { if (this._nativeChangeHandler) this.$el.removeEventListener('change', this._nativeChangeHandler, true); },
            async onOpen(detail) {
                this.role = detail.role || 'enseignant_ecue'; this.ecueId = detail.ecueId || null; this.ueId = detail.ueId || null; this.targetLabel = detail.targetLabel || detail.ecueLabel || ''; this.currentTeacherId = String(detail.currentTeacherId || ''); this.selectedId = this.currentTeacherId; this.selectedIds = this.currentTeacherId ? [parseInt(this.currentTeacherId, 10)] : []; this.teacherSearch = ''; this.triggerEl = detail.triggerEl || null; this.open = true;
                if (this.role === 'enseignant_ecue' && this.ecueId) {
                    this.loadingPool = true;
                    try { const json = await loadPool(this.ecueId); this.selectedIds = (json.ids || []).map(Number); }
                    catch (e) { window.lpeShowToast(e.message || 'Impossible de relire le pool', 'error'); }
                    finally { this.loadingPool = false; }
                    return;
                }
                this.$nextTick(() => {
                    const native = this.$el.querySelector('input[name="lpt_user_id"]'); if (native) native.value = this.currentTeacherId;
                    const picker = this.$el.querySelector('.au-up'); if (picker?._x_dataStack?.[0]) picker._x_dataStack[0].currentValue = this.currentTeacherId;
                });
            },
            getSelectedId() {
                if (this.selectedId !== '' || this.currentTeacherId === '') return this.selectedId;
                const native = this.$el.querySelector('input[name="lpt_user_id"]'); return native ? String(native.value || '') : '';
            },
            async commit() {
                if (this.saving || this.loadingPool) return;
                if (this.role === 'enseignant_ecue') { await this.savePool(); return; }
                const newId = this.getSelectedId(); if (newId === this.currentTeacherId) { this.open = false; return; }
                await this.saveSingle(newId === '' ? null : parseInt(newId, 10));
            },
            async unassign() { if (!this.saving) await this.saveSingle(null); },
            async savePool() {
                this.saving = true;
                const ids = Array.from(new Set(this.selectedIds.map(Number).filter(Boolean)));
                try {
                    const payload = { enseignant_principal_id: ids[0] || null, enseignants_secondaires: ids.slice(1) };
                    const { planification: planif, created } = await window.lpeSavePlanification(this.ecueId, payload);
                    const checked = Array.from(this.$el.querySelectorAll('.lpt-teacher-option input:checked'));
                    const names = checked.map(input => input.closest('.lpt-teacher-option')?.querySelector('strong')?.textContent?.trim()).filter(Boolean);
                    if (this.triggerEl) {
                        const label = names.length ? names[0] + (names.length > 1 ? ' +' + (names.length - 1) : '') : '+ Assigner';
                        this.triggerEl.dataset.lpeTeacherId = ids[0] || '';
                        this.triggerEl.dataset.lpeTeacherName = names.join(' / ');
                        const span = this.triggerEl.querySelector('.lpe-teacher-name'); if (span) span.textContent = label;
                        this.triggerEl.classList.toggle('lpe-teacher-btn--assigned', ids.length > 0);
                        if (this.triggerEl._x_dataStack?.[0]) { this.triggerEl._x_dataStack[0].currentTeacherId = String(ids[0] || ''); this.triggerEl._x_dataStack[0].currentTeacherName = names.join(' / '); }
                    }
                    window.lpeShowToast(ids.length + ' professeur(s) prevu(s) pour cet ECUE', 'success');
                    window.dispatchEvent(new CustomEvent('lpe:planif-updated', { detail: { ecueId: this.ecueId, planif, created } }));
                    this.open = false;
                } catch (e) { window.lpeShowToast(e.message || 'Erreur d\'enregistrement', 'error'); }
                finally { this.saving = false; }
            },
            async saveSingle(teacherId) {
                this.saving = true;
                try {
                    if (this.role === 'responsable_ue') await this._saveUeResponsable(teacherId);
                    this.open = false;
                } catch (e) { window.lpeShowToast(e.message || 'Erreur d\'enregistrement', 'error'); }
                finally { this.saving = false; }
            },
            async _saveUeResponsable(teacherId) {
                const { ue } = await window.lpeSaveUeResponsable(this.ueId, teacherId);
                if (this.triggerEl) {
                    const name = ue.responsable_name || ''; this.triggerEl.dataset.lpeTeacherId = ue.responsable_ue_id || ''; this.triggerEl.dataset.lpeTeacherName = name;
                    const span = this.triggerEl.querySelector('.lpe-resp-name'); if (span) span.textContent = name || '+ Assigner responsable UE';
                    this.triggerEl.classList.toggle('lpe-resp-btn--assigned', !!ue.responsable_ue_id);
                    if (this.triggerEl._x_dataStack?.[0]) { this.triggerEl._x_dataStack[0].currentTeacherId = String(ue.responsable_ue_id || ''); this.triggerEl._x_dataStack[0].currentTeacherName = name; }
                }
                window.lpeShowToast(teacherId ? 'Responsable UE assigne' : 'Responsable UE retire', 'success');
                window.dispatchEvent(new CustomEvent('lpe:ue-responsable-updated', { detail: { ueId: this.ueId, ue } }));
            },
        }));
    });
})();
</script>
@endpush
@endcan
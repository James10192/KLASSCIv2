{{-- Scripts pour l'edition inline + modal enseignant.
     Le planning ECUE porte maintenant un POOL de professeurs ; le responsable UE
     reste un choix unique. --}}
@can('lmd.planning.edit')
@php
    $lptTeachers = $enseignants->map(fn ($teacher) => [
        'id' => (int) $teacher->id,
        'name' => (string) $teacher->name,
        'email' => (string) ($teacher->email ?? ''),
    ])->values()->all();
@endphp
@push('scripts')
<script>
(function () {
    if (window.__lpeBootstrapped) return;
    window.__lpeBootstrapped = true;

    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const updateUrlTpl = @json(route('esbtp.lmd.planifications.update', ['ecueId' => '__ID__']));
    const updateUeRespUrlTpl = @json(route('esbtp.lmd.ues.update-responsable', ['ueId' => '__ID__']));
    const poolReadUrl = @json(route('esbtp.lmd.planning.teacher-pool'));
    const teacherOptions = @json($lptTeachers);

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
        toast.replaceChildren();
        const icon = document.createElement('i');
        icon.className = type === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-triangle';
        icon.setAttribute('aria-hidden', 'true');
        toast.append(icon, document.createTextNode(' ' + message));
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

    window.lpeSavePlanification = async function (ecueId, payload, contextOverride) {
        const ctx = contextOverride || buildContextParams();
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
        else if (resp.status === 403) detail = 'Vous n\'avez plus la permission d\'éditer.';
        else if (resp.status === 429) detail = 'Trop de demandes. Patientez avant de réessayer.';
        else if (resp.status === 422 && json.errors) detail = Object.values(json.errors).flat().join(' · ');
        else if (resp.status === 409) detail = json.message || 'Conflit de modification, rechargez la page.';
        const err = new Error(detail); err.status = resp.status; return err;
    }

    async function loadPool(ecueId) {
        const ctx = buildContextParams();
        if (!ecueId || !ctx.filiere_id || !ctx.niveau_id || !ctx.semestre || !ctx.annee_universitaire_id) {
            throw new Error('Sélectionnez un parcours, un niveau, un semestre et une année universitaire.');
        }
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
            ecueId: null, currentTeacherId: '', currentTeacherName: '', currentTeacherIds: [], ecueLabel: '',
            init() {
                const ds = this.$el.dataset;
                this.ecueId = parseInt(ds.lpeEcueId, 10) || null;
                this.currentTeacherId = ds.lpeTeacherId || '';
                this.currentTeacherName = ds.lpeTeacherName || '';
                try { this.currentTeacherIds = JSON.parse(ds.lpeTeacherIds || '[]').map(Number); }
                catch (e) { this.currentTeacherIds = []; }
                this.ecueLabel = ds.lpeEcueLabel || '';
            },
            openPicker() {
                if (!this.ecueId) return;
                window.dispatchEvent(new CustomEvent('lpt:open', { detail: {
                    role: 'enseignant_ecue', ecueId: this.ecueId, currentTeacherId: this.currentTeacherId,
                    currentTeacherIds: this.currentTeacherIds, targetLabel: this.ecueLabel ? ('ECUE : ' + this.ecueLabel) : '',
                    triggerEl: this.$el,
                } }));
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
            open: false, saving: false, loadingPool: false, loadError: '',
            role: 'enseignant_ecue', ecueId: null, ueId: null, targetLabel: '',
            currentTeacherId: '', selectedId: '', selectedIds: [], teacherSearch: '',
            teachers: teacherOptions, triggerEl: null, contextAtOpen: null,
            _poolSequence: 0, _nativeChangeHandler: null, _contextHandler: null,
            init() {
                this._nativeChangeHandler = ev => {
                    if (ev.target?.matches?.('input[name="lpt_user_id"]')) this.selectedId = String(ev.target.value || '');
                };
                this._contextHandler = () => { if (this.open && !this.saving) this.closeModal(); };
                this.$el.addEventListener('change', this._nativeChangeHandler, true);
                window.addEventListener('lpt:context-changed', this._contextHandler);
            },
            destroy() {
                this.$el.removeEventListener('change', this._nativeChangeHandler, true);
                window.removeEventListener('lpt:context-changed', this._contextHandler);
                this._poolSequence++;
            },
            get selectedTeachers() {
                return this.selectedIds.map(id => this.teachers.find(t => t.id === Number(id))).filter(Boolean);
            },
            get availableTeachers() {
                const query = this.teacherSearch.trim().toLocaleLowerCase('fr');
                return this.teachers.filter(t => !this.selectedIds.includes(t.id) &&
                    (!query || (t.name + ' ' + t.email).toLocaleLowerCase('fr').includes(query)));
            },
            initials(name) {
                return String(name || '').trim().split(/\s+/).slice(0, 2).map(w => w[0] || '').join('').toUpperCase();
            },
            addTeacher(id) {
                id = Number(id);
                if (this.saving || this.loadingPool || this.loadError || this.selectedIds.includes(id)) return;
                if (this.selectedIds.length >= 21) {
                    window.lpeShowToast('Maximum 21 enseignants pour un ECUE.', 'error');
                    return;
                }
                this.selectedIds = [...this.selectedIds, id];
            },
            removeTeacher(id) {
                if (this.saving || this.loadingPool || this.loadError) return;
                this.selectedIds = this.selectedIds.filter(value => value !== Number(id));
            },
            async onOpen(detail) {
                if (this.saving) return;
                const sequence = ++this._poolSequence;
                this.role = detail.role || 'enseignant_ecue';
                this.ecueId = detail.ecueId || null;
                this.ueId = detail.ueId || null;
                this.targetLabel = detail.targetLabel || detail.ecueLabel || '';
                this.currentTeacherId = String(detail.currentTeacherId || '');
                this.selectedId = this.currentTeacherId;
                this.selectedIds = Array.isArray(detail.currentTeacherIds)
                    ? detail.currentTeacherIds.map(Number).filter(Boolean)
                    : (this.currentTeacherId ? [Number(this.currentTeacherId)] : []);
                this.teacherSearch = '';
                this.loadError = '';
                this.loadingPool = false;
                this.contextAtOpen = { ...buildContextParams() };
                this.triggerEl = detail.triggerEl || null;
                this.open = true;
                this.$nextTick(() => this.$el.querySelector('.lpt-close')?.focus());

                if (this.role === 'enseignant_ecue' && this.ecueId) {
                    await this.readPool(sequence);
                    return;
                }
                this.$nextTick(() => {
                    const native = this.$el.querySelector('input[name="lpt_user_id"]');
                    if (native) native.value = this.currentTeacherId;
                    const picker = this.$el.querySelector('.au-up');
                    if (picker?._x_dataStack?.[0]) picker._x_dataStack[0].currentValue = this.currentTeacherId;
                    (picker?.querySelector('button') || this.$el.querySelector('.lpt-close'))?.focus();
                });
            },
            async readPool(sequence) {
                this.loadingPool = true;
                this.loadError = '';
                try {
                    const json = await loadPool(this.ecueId);
                    if (sequence !== this._poolSequence || !this.open) return;
                    this.selectedIds = [...new Set((json.ids || []).map(Number).filter(Boolean))];
                    for (const user of (json.teachers || [])) {
                        if (!this.teachers.some(t => t.id === Number(user.id))) {
                            this.teachers = [...this.teachers, { id: Number(user.id), name: user.name, email: user.email || '' }];
                        }
                    }
                    this.$nextTick(() => this.$refs.search?.focus());
                } catch (e) {
                    if (sequence !== this._poolSequence || !this.open) return;
                    this.loadError = e.message || 'Impossible de charger les enseignants. Réessayez.';
                } finally {
                    if (sequence === this._poolSequence) this.loadingPool = false;
                }
            },
            retryPool() {
                if (this.loadingPool || this.saving) return;
                this.readPool(++this._poolSequence);
            },
            handleEscape(event) {
                if (!this.open) return;
                event.preventDefault();
                const picker = this.$el.querySelector('.au-up');
                const state = picker?._x_dataStack?.[0];
                if (this.role === 'responsable_ue' && state?.open) { state.fermer(); return; }
                this.closeModal();
            },
            closeModal(force = false) {
                if (this.saving && !force) return;
                this.open = false;
                this._poolSequence++;
                const trigger = this.triggerEl;
                this.$nextTick(() => { if (trigger?.isConnected) trigger.focus(); });
            },
            trapFocus(event) {
                if (!this.open || event.key !== 'Tab') return;
                const nodes = [...this.$el.querySelectorAll('button:not([disabled]), input:not([disabled]), [tabindex="0"]')]
                    .filter(el => el.getClientRects().length > 0);
                if (!nodes.length) return;
                const first = nodes[0], last = nodes[nodes.length - 1];
                if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
                else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
            },
            getSelectedId() {
                if (this.selectedId !== '' || this.currentTeacherId === '') return this.selectedId;
                const native = this.$el.querySelector('input[name="lpt_user_id"]');
                return native ? String(native.value || '') : '';
            },
            async commit() {
                if (this.saving || this.loadingPool || this.loadError) return;
                if (this.role === 'enseignant_ecue') { await this.savePool(); return; }
                const newId = this.getSelectedId();
                if (newId === this.currentTeacherId) { this.closeModal(); return; }
                await this.saveSingle(newId === '' ? null : parseInt(newId, 10));
            },
            async unassign() { if (!this.saving) await this.saveSingle(null); },
            async savePool() {
                if (JSON.stringify(this.contextAtOpen) !== JSON.stringify(buildContextParams())) {
                    this.loadError = 'Les filtres ont changé. Fermez cette fenêtre et ouvrez l’ECUE du nouveau contexte.';
                    return;
                }
                this.saving = true;
                const ids = [...new Set(this.selectedIds.map(Number).filter(Boolean))];
                try {
                    const payload = { enseignant_principal_id: ids[0] || null, enseignants_secondaires: ids.slice(1) };
                    const { planification: planif, created } = await window.lpeSavePlanification(this.ecueId, payload, this.contextAtOpen);
                    const savedIds = (planif.enseignant_ids || ids).map(Number);
                    const savedTeachers = Array.isArray(planif.enseignants)
                        ? planif.enseignants : this.selectedTeachers;
                    const names = savedTeachers.map(teacher => teacher.name).filter(Boolean);
                    if (this.triggerEl?.isConnected) {
                        const label = names.length ? names[0] + (names.length > 1 ? ' +' + (names.length - 1) : '') : '+ Assigner';
                        this.triggerEl.dataset.lpeTeacherId = savedIds[0] || '';
                        this.triggerEl.dataset.lpeTeacherIds = JSON.stringify(savedIds);
                        this.triggerEl.dataset.lpeTeacherName = names.join(' / ');
                        this.triggerEl.title = names.join(' / ') || 'Assigner des enseignants';
                        this.triggerEl.setAttribute('aria-label', names.length ? 'Enseignants affectés : ' + names.join(' / ') + '. Modifier' : 'Assigner des enseignants');
                        const span = this.triggerEl.querySelector('.lpe-teacher-name');
                        if (span) span.textContent = label;
                        const icon = this.triggerEl.querySelector('i');
                        if (icon) { icon.classList.toggle('fa-user-check', !!savedIds.length); icon.classList.toggle('fa-user-plus', !savedIds.length); }
                        this.triggerEl.classList.toggle('lpe-teacher-btn--assigned', savedIds.length > 0);
                        if (this.triggerEl._x_dataStack?.[0]) {
                            Object.assign(this.triggerEl._x_dataStack[0], {
                                currentTeacherId: String(savedIds[0] || ''),
                                currentTeacherIds: savedIds,
                                currentTeacherName: names.join(' / '),
                            });
                        }
                    }
                    window.lpeShowToast(savedIds.length + ' professeur(s) prévu(s) pour cet ECUE', 'success');
                    window.dispatchEvent(new CustomEvent('lpe:planif-updated', { detail: { ecueId: this.ecueId, planif, created } }));
                    this.closeModal(true);
                } catch (e) {
                    window.lpeShowToast(e.message || 'Erreur d’enregistrement', 'error');
                } finally { this.saving = false; }
            },
            async saveSingle(teacherId) {
                if (this.saving) return;
                this.saving = true;
                try {
                    if (this.role === 'responsable_ue') await this._saveUeResponsable(teacherId);
                    this.closeModal(true);
                } catch (e) {
                    window.lpeShowToast(e.message || 'Erreur d’enregistrement', 'error');
                } finally { this.saving = false; }
            },
            async _saveUeResponsable(teacherId) {
                const { ue } = await window.lpeSaveUeResponsable(this.ueId, teacherId);
                if (this.triggerEl?.isConnected) {
                    const name = ue.responsable_name || '';
                    this.triggerEl.dataset.lpeTeacherId = ue.responsable_ue_id || '';
                    this.triggerEl.dataset.lpeTeacherName = name;
                    this.triggerEl.title = name || 'Assigner un responsable UE';
                    const span = this.triggerEl.querySelector('.lpe-resp-name');
                    if (span) span.textContent = name || '+ Assigner responsable UE';
                    const icon = this.triggerEl.querySelector('i');
                    if (icon) { icon.classList.toggle('fa-user-tie', !!ue.responsable_ue_id); icon.classList.toggle('fa-user-plus', !ue.responsable_ue_id); }
                    this.triggerEl.classList.toggle('lpe-resp-btn--assigned', !!ue.responsable_ue_id);
                    if (this.triggerEl._x_dataStack?.[0]) {
                        this.triggerEl._x_dataStack[0].currentTeacherId = String(ue.responsable_ue_id || '');
                        this.triggerEl._x_dataStack[0].currentTeacherName = name;
                    }
                }
                window.lpeShowToast(teacherId ? 'Responsable UE assigné' : 'Responsable UE retiré', 'success');
                window.dispatchEvent(new CustomEvent('lpe:ue-responsable-updated', { detail: { ueId: this.ueId, ue } }));
            },
        }));
    });
})();
</script>
@endpush
@endcan
{{-- Scripts pour l'édition inline + modal teacher.
     - lpeCell : Alpine factory pour cellules éditables (volume/coef/credits).
     - lptModal : Alpine factory pour le modal d'assignation enseignant.
     - lpeToast : helper global de toast feedback.
     - savePlanification : fetch PATCH commun. --}}
@can('lmd.planning.edit')
@push('scripts')
<script>
(function () {
    if (window.__lpeBootstrapped) return;
    window.__lpeBootstrapped = true;

    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const updateUrlTpl = @json(route('esbtp.lmd.planifications.update', ['ecueId' => '__ID__']));
    const updateUeRespUrlTpl = @json(route('esbtp.lmd.ues.update-responsable', ['ueId' => '__ID__']));

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
        toast._t = setTimeout(() => { toast.classList.remove('lpt-toast--show'); }, 2400);
    };

    // W1.2 — Sauvegarde du responsable d'UE (mode dual du lptModal).
    // Endpoint distinct de lpeSavePlanification car cible une UE entière
    // (esbtp_unites_enseignement.responsable_ue_id) et non une planification ECUE.
    window.lpeSaveUeResponsable = async function (ueId, responsableId) {
        const url = updateUeRespUrlTpl.replace('__ID__', ueId);
        const resp = await fetch(url, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ responsable_ue_id: responsableId }),
        });
        let json = {};
        try { json = await resp.json(); } catch (e) { json = {}; }
        if (!resp.ok || !json.success) {
            let detail = json.message || ('Erreur HTTP ' + resp.status);
            if (resp.status === 419) {
                detail = 'Votre session a expiré. Rechargez la page.';
            } else if (resp.status === 403) {
                detail = 'Vous n\'avez plus la permission d\'éditer.';
            } else if (resp.status === 422 && json.errors) {
                detail = Object.values(json.errors).flat().join(' · ');
            }
            const err = new Error(detail);
            err.status = resp.status;
            throw err;
        }
        return { ue: json.ue };
    };

    window.lpeSavePlanification = async function (ecueId, payload) {
        const ctx = buildContextParams();
        const url = updateUrlTpl.replace('__ID__', ecueId);
        const body = Object.assign({}, ctx, payload);
        const resp = await fetch(url, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(body),
        });
        let json = {};
        try { json = await resp.json(); } catch (e) { json = {}; }
        if (!resp.ok || !json.success) {
            // Silent #3 : handling explicite des codes HTTP critiques pour
            // afficher des messages utilisateur lisibles plutôt qu'un brut
            // "Erreur HTTP 419" qui ne dit rien à la directrice.
            let detail = json.message || ('Erreur HTTP ' + resp.status);
            if (resp.status === 419) {
                detail = 'Votre session a expiré. Rechargez la page.';
            } else if (resp.status === 403) {
                detail = 'Vous n\'avez plus la permission d\'éditer.';
            } else if (resp.status === 422 && json.errors) {
                detail = Object.values(json.errors).flat().join(' · ');
            } else if (resp.status === 409) {
                detail = json.message || 'Conflit de modification, rechargez la page.';
            }
            console.error('lpe save failed', resp.status, json);
            const err = new Error(detail);
            err.status = resp.status;
            throw err;
        }
        return { planification: json.planification, created: !!json.created };
    };

    document.addEventListener('alpine:init', () => {
        // ---------- Cellule éditable inline ----------
        Alpine.data('lpeCell', () => ({
            ecueId: null,
            field: '',
            value: '',
            originalValue: '',
            editing: false,
            saving: false,
            isDecimal: false,

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
                this.$nextTick(() => {
                    const input = this.$refs.input;
                    if (input) { input.focus(); input.select(); }
                });
            },

            cancel() {
                this.value = this.originalValue;
                this.editing = false;
            },

            async commit() {
                if (!this.editing) return;
                const raw = String(this.value).trim();
                const newVal = raw === '' ? null : (this.isDecimal ? parseFloat(raw) : parseInt(raw, 10));
                if (raw !== '' && (Number.isNaN(newVal) || newVal < 0)) {
                    this.$el.classList.add('lpe-cell--error');
                    setTimeout(() => this.$el.classList.remove('lpe-cell--error'), 1200);
                    this.cancel();
                    return;
                }
                if (String(newVal) === String(this.originalValue) || (newVal === null && this.originalValue === '')) {
                    this.editing = false;
                    return;
                }
                this.editing = false;
                this.saving = true;
                try {
                    const { planification: planif, created } = await window.lpeSavePlanification(this.ecueId, { [this.field]: newVal });
                    this.value = planif[this.field] ?? '';
                    this.originalValue = this.value;
                    this.$el.classList.add('lpe-cell--saved');
                    setTimeout(() => this.$el.classList.remove('lpe-cell--saved'), 700);
                    // Silent #1 : toast distinct création vs mise à jour
                    if (created) {
                        window.lpeShowToast('Planification créée', 'success');
                    }
                    window.dispatchEvent(new CustomEvent('lpe:planif-updated', {
                        detail: { ecueId: this.ecueId, planif, created },
                    }));
                } catch (e) {
                    window.lpeShowToast(e.message || 'Erreur d\'enregistrement', 'error');
                    this.value = this.originalValue;
                    this.$el.classList.add('lpe-cell--error');
                    setTimeout(() => this.$el.classList.remove('lpe-cell--error'), 1200);
                } finally {
                    this.saving = false;
                }
            },

            get displayValue() {
                if (this.value === '' || this.value === null || this.value === undefined) return '0';
                return this.isDecimal ? parseFloat(this.value).toFixed(2).replace(/\.00$/, '') : this.value;
            },
        }));

        // ---------- Bouton trigger modal teacher (mode enseignant_ecue) ----------
        Alpine.data('lpeTeacherTrigger', () => ({
            ecueId: null,
            currentTeacherId: '',
            currentTeacherName: '',
            ecueLabel: '',

            init() {
                const ds = this.$el.dataset;
                this.ecueId = parseInt(ds.lpeEcueId, 10) || null;
                this.currentTeacherId = ds.lpeTeacherId || '';
                this.currentTeacherName = ds.lpeTeacherName || '';
                this.ecueLabel = ds.lpeEcueLabel || '';
            },

            openPicker() {
                if (!this.ecueId) return;
                window.dispatchEvent(new CustomEvent('lpt:open', {
                    detail: {
                        role: 'enseignant_ecue',
                        ecueId: this.ecueId,
                        currentTeacherId: this.currentTeacherId,
                        targetLabel: this.ecueLabel ? ('ECUE : ' + this.ecueLabel) : '',
                        triggerEl: this.$el,
                    },
                }));
            },
        }));

        // ---------- Bouton trigger modal responsable UE (mode responsable_ue) ----------
        // W1.2 — directive UEMOA 03/2007/CM : 1 responsable par UE.
        Alpine.data('lpeResponsableTrigger', () => ({
            ueId: null,
            currentTeacherId: '',
            currentTeacherName: '',
            ueLabel: '',

            init() {
                const ds = this.$el.dataset;
                this.ueId = parseInt(ds.lpeUeId, 10) || null;
                this.currentTeacherId = ds.lpeTeacherId || '';
                this.currentTeacherName = ds.lpeTeacherName || '';
                this.ueLabel = ds.lpeUeLabel || '';
            },

            openPicker() {
                if (!this.ueId) return;
                window.dispatchEvent(new CustomEvent('lpt:open', {
                    detail: {
                        role: 'responsable_ue',
                        ueId: this.ueId,
                        currentTeacherId: this.currentTeacherId,
                        targetLabel: this.ueLabel ? ('UE : ' + this.ueLabel) : '',
                        triggerEl: this.$el,
                    },
                }));
            },
        }));

        // ---------- Modal teacher picker (polymorphe : enseignant_ecue | responsable_ue) ----------
        // W1.2 — mode dual :
        //   role='enseignant_ecue' → save sur PATCH /esbtp/lmd/planifications/{ecueId}
        //   role='responsable_ue'  → save sur PATCH /esbtp/lmd/ues/{ueId}/responsable
        // Le label d'en-tête (`targetLabel`) est passé par le trigger pour distinguer
        // "ECUE : ..." vs "UE : ..." dans l'UI.
        Alpine.data('lptModal', () => ({
            open: false,
            saving: false,
            role: 'enseignant_ecue',
            ecueId: null,
            ueId: null,
            targetLabel: '',
            currentTeacherId: '',
            // selectedId : id séléctionné CET INSTANT par le picker (peut différer
            // de currentTeacherId qui est l'id assigné avant l'ouverture du modal).
            // Tracké via listener `change` sur l'input hidden du picker (dispatché
            // par auUserPicker.select()) — plus fiable que querySelector au commit.
            selectedId: '',
            triggerEl: null,
            _nativeChangeHandler: null,

            init() {
                // Listener « change » sur l'input hidden du picker : auUserPicker
                // émet un Event('change',{bubbles:true}) à chaque select(). On
                // capture la nouvelle valeur ici sans dépendre de _x_dataStack.
                this._nativeChangeHandler = (ev) => {
                    if (ev.target && ev.target.matches && ev.target.matches('input[name="lpt_user_id"]')) {
                        this.selectedId = String(ev.target.value || '');
                    }
                };
                this.$el.addEventListener('change', this._nativeChangeHandler, true);
            },

            destroy() {
                if (this._nativeChangeHandler) {
                    this.$el.removeEventListener('change', this._nativeChangeHandler, true);
                    this._nativeChangeHandler = null;
                }
            },

            onOpen(detail) {
                this.role = detail.role || 'enseignant_ecue';
                this.ecueId = detail.ecueId || null;
                this.ueId = detail.ueId || null;
                // Rétro-compat : si l'ancien event utilise ecueLabel au lieu de targetLabel
                this.targetLabel = detail.targetLabel || detail.ecueLabel || '';
                this.currentTeacherId = String(detail.currentTeacherId || '');
                this.selectedId = this.currentTeacherId;
                this.triggerEl = detail.triggerEl || null;
                this.open = true;
                this.$nextTick(() => {
                    const native = this.$el.querySelector('input[name="lpt_user_id"]');
                    if (native) native.value = this.currentTeacherId;
                    const picker = this.$el.querySelector('.au-up');
                    if (picker && picker._x_dataStack && picker._x_dataStack[0]) {
                        picker._x_dataStack[0].currentValue = this.currentTeacherId;
                    }
                });
            },

            getSelectedId() {
                // Priorité 1 : selectedId tracké par le listener change (source réactive)
                if (this.selectedId !== '' || this.currentTeacherId === '') {
                    return this.selectedId;
                }
                // Fallback : lire l'input hidden (au cas où le listener n'aurait pas
                // fire — défensif pour les corner cases Alpine où l'event ne bubble pas)
                const native = this.$el.querySelector('input[name="lpt_user_id"]');
                return native ? String(native.value || '') : '';
            },

            async commit() {
                if (this.saving) return;
                const newId = this.getSelectedId();
                if (newId === this.currentTeacherId) {
                    this.open = false;
                    return;
                }
                await this.save(newId === '' ? null : parseInt(newId, 10));
            },

            async unassign() {
                if (this.saving) return;
                await this.save(null);
            },

            async save(teacherId) {
                this.saving = true;
                try {
                    if (this.role === 'responsable_ue') {
                        await this._saveUeResponsable(teacherId);
                    } else {
                        await this._saveEcueEnseignant(teacherId);
                    }
                    this.open = false;
                } catch (e) {
                    window.lpeShowToast(e.message || 'Erreur d\'enregistrement', 'error');
                } finally {
                    this.saving = false;
                }
            },

            async _saveEcueEnseignant(teacherId) {
                const { planification: planif, created } = await window.lpeSavePlanification(this.ecueId, { enseignant_principal_id: teacherId });
                if (this.triggerEl) {
                    const name = planif.enseignant_name || '';
                    this.triggerEl.dataset.lpeTeacherId = planif.enseignant_principal_id || '';
                    this.triggerEl.dataset.lpeTeacherName = name;
                    const span = this.triggerEl.querySelector('.lpe-teacher-name');
                    if (span) span.textContent = name || '+ Assigner';
                    if (planif.enseignant_principal_id) {
                        this.triggerEl.classList.add('lpe-teacher-btn--assigned');
                    } else {
                        this.triggerEl.classList.remove('lpe-teacher-btn--assigned');
                    }
                    if (this.triggerEl._x_dataStack && this.triggerEl._x_dataStack[0]) {
                        this.triggerEl._x_dataStack[0].currentTeacherId = String(planif.enseignant_principal_id || '');
                        this.triggerEl._x_dataStack[0].currentTeacherName = name;
                    }
                }
                let toastMsg;
                if (created) {
                    toastMsg = teacherId ? 'Planification créée et enseignant assigné' : 'Planification créée';
                } else {
                    toastMsg = teacherId ? 'Enseignant assigné' : 'Assignation supprimée';
                }
                window.lpeShowToast(toastMsg, 'success');
                window.dispatchEvent(new CustomEvent('lpe:planif-updated', {
                    detail: { ecueId: this.ecueId, planif, created },
                }));
            },

            async _saveUeResponsable(teacherId) {
                const { ue } = await window.lpeSaveUeResponsable(this.ueId, teacherId);
                if (this.triggerEl) {
                    const name = ue.responsable_name || '';
                    this.triggerEl.dataset.lpeTeacherId = ue.responsable_ue_id || '';
                    this.triggerEl.dataset.lpeTeacherName = name;
                    const span = this.triggerEl.querySelector('.lpe-resp-name');
                    if (span) span.textContent = name || '+ Assigner responsable UE';
                    if (ue.responsable_ue_id) {
                        this.triggerEl.classList.add('lpe-resp-btn--assigned');
                    } else {
                        this.triggerEl.classList.remove('lpe-resp-btn--assigned');
                    }
                    if (this.triggerEl._x_dataStack && this.triggerEl._x_dataStack[0]) {
                        this.triggerEl._x_dataStack[0].currentTeacherId = String(ue.responsable_ue_id || '');
                        this.triggerEl._x_dataStack[0].currentTeacherName = name;
                    }
                }
                window.lpeShowToast(teacherId ? 'Responsable UE assigné' : 'Responsable UE retiré', 'success');
                window.dispatchEvent(new CustomEvent('lpe:ue-responsable-updated', {
                    detail: { ueId: this.ueId, ue },
                }));
            },
        }));
    });
})();
</script>
@endpush
@endcan

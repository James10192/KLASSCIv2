@push('scripts')
<script>
function busSelect() {
    return {
        toasts: [],
        toastSeq: 0,
        configModal: {
            open: false,
            loading: false,
            saving: false,
            error: '',
            subtitle: '',
            context: {},
            matieres: [],
            configured_count: 0,
            configured_coefficients_count: 0,
            configured_professeurs_count: 0,
        },
        init() {
            window.addEventListener('toast', (ev) => this.pushToast(ev.detail));
            window.addEventListener('bus-open-config-modal', (ev) => this.openConfigModal(ev.detail || {}));
        },
        pushToast(detail) {
            const id = ++this.toastSeq;
            this.toasts.push({ id, type: detail.type || 'info', message: detail.message || '' });
            setTimeout(() => this.removeToast(id), 5000);
        },
        removeToast(id) {
            const idx = this.toasts.findIndex(t => t.id === id);
            if (idx !== -1) this.toasts.splice(idx, 1);
        },
        closeConfigModal() {
            if (this.configModal.saving) return;
            this.configModal.open = false;
        },
        async openConfigModal(detail) {
            const context = {
                classe_id: detail.classe_id || '',
                annee_universitaire_id: detail.annee_universitaire_id || '',
                periode: detail.periode || 'semestre1',
            };

            if (!context.classe_id || !context.annee_universitaire_id || !context.periode) {
                this.pushToast({ type: 'error', message: 'Selectionnez classe, annee universitaire et periode avant de configurer.' });
                return;
            }

            this.configModal.open = true;
            this.configModal.loading = true;
            this.configModal.saving = false;
            this.configModal.error = '';
            this.configModal.context = context;
            this.configModal.matieres = [];
            this.configModal.subtitle = 'Chargement...';

            try {
                const params = new URLSearchParams(context);
                const res = await fetch(`{{ route('esbtp.bulletins.config-matieres.inline-data') }}?${params.toString()}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                });
                const data = await this.parseJsonResponse(res);
                if (!res.ok || data.success === false) {
                    throw new Error(data.message || `Erreur HTTP ${res.status}`);
                }

                this.configModal.subtitle = [
                    data.classe?.name || 'Classe',
                    data.periode || context.periode,
                ].filter(Boolean).join(' - ');
                this.configModal.configured_count = data.configured_count || 0;
                this.configModal.configured_coefficients_count = data.configured_coefficients_count || 0;
                this.configModal.configured_professeurs_count = data.configured_professeurs_count || 0;
                this.configModal.matieres = (data.matieres || []).map((matiere) => ({
                    ...matiere,
                    selected_type: matiere.existing_type || matiere.suggested_type || 'technique',
                    coefficient: matiere.coefficient ?? '',
                    professeur: matiere.professeur || '',
                }));
            } catch (err) {
                this.configModal.error = err.message || 'Impossible de charger la configuration.';
            } finally {
                this.configModal.loading = false;
            }
        },
        async saveConfigModal() {
            if (this.configModal.loading || this.configModal.saving) return;

            this.configModal.saving = true;
            this.configModal.error = '';

            try {
                const fd = new FormData();
                fd.append('classe_id', this.configModal.context.classe_id);
                fd.append('annee_universitaire_id', this.configModal.context.annee_universitaire_id);
                fd.append('periode', this.configModal.context.periode);

                this.configModal.matieres.forEach((matiere) => {
                    fd.append(`matiere_type[${matiere.id}]`, matiere.selected_type || 'none');
                    fd.append(`coefficients[${matiere.id}]`, matiere.coefficient ?? '');
                    fd.append(`professeurs[${matiere.id}]`, matiere.professeur || '');
                });

                const res = await fetch(`{{ route('esbtp.bulletins.config-matieres.inline-save') }}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    body: fd,
                });
                const data = await this.parseJsonResponse(res);
                if (!res.ok || data.success === false) {
                    const msg = Object.values(data.errors || {}).flat().join(' - ') || data.message || `Erreur HTTP ${res.status}`;
                    throw new Error(msg);
                }

                const detail = { ...this.configModal.context };
                this.pushToast({ type: 'success', message: data.message || 'Configuration enregistree.' });
                this.configModal.open = false;
                window.dispatchEvent(new CustomEvent('bus-config-saved', { detail }));
            } catch (err) {
                this.configModal.error = err.message || 'Erreur lors de l enregistrement.';
            } finally {
                this.configModal.saving = false;
            }
        },
        async parseJsonResponse(res) {
            const text = await res.text();
            if (!text) return {};

            try {
                return JSON.parse(text);
            } catch (err) {
                return { message: res.redirected ? 'Le serveur a redirige la requete au lieu de retourner du JSON.' : text };
            }
        },
    };
}

if (typeof window.busCard !== 'function') {
window.busCard = function (cfg) {
    return {
        kind: cfg.kind,
        busy: false,
        loadingStudents: false,
        students: [],
        studentsAbort: null,
        studentsRequestSeq: 0,
        preflight: null,
        preflightBusy: false,
        preflightAbort: null,
        previewIssue: null,
        lastGeneration: null,
        // Année universitaire courante pré-sélectionnée (le user peut changer ensuite).
        form: {
            classe_id: '',
            annee_universitaire_id: @json($anneeActuelle?->id ? (string) $anneeActuelle->id : ''),
            etudiant_id: '',
            semestre: '',
            periode: '',
            recalculer: false,
            incomplete_reason: '',
        },

        init() {
            this.$watch('form.classe_id', () => {
                this.form.etudiant_id = '';
                this.previewIssue = null;
                this.lastGeneration = null;
                this.fetchStudents();
                this.queuePreflight();
            });
            this.$watch('form.annee_universitaire_id', () => {
                this.form.etudiant_id = '';
                this.previewIssue = null;
                this.lastGeneration = null;
                this.fetchStudents();
                this.queuePreflight();
            });
            this.$watch('form.periode', () => {
                this.previewIssue = null;
                this.lastGeneration = null;
                this.queuePreflight();
            });
            this.$watch('form.recalculer', () => {
                this.lastGeneration = null;
                this.queuePreflight();
            });
            this.$watch('form.etudiant_id', () => { this.previewIssue = null; });
            window.addEventListener('bus-config-saved', (ev) => {
                const detail = ev.detail || {};
                const sameContext = String(detail.classe_id || '') === String(this.form.classe_id || '')
                    && String(detail.annee_universitaire_id || '') === String(this.form.annee_universitaire_id || '')
                    && String(detail.periode || '') === String(this.form.periode || '');

                if (!sameContext) return;

                this.previewIssue = null;
                this.lastGeneration = null;
                if (this.kind === 'generate') this.queuePreflight();
            });
        },

        canSubmit() {
            if (!this.form.classe_id || !this.form.annee_universitaire_id) return false;
            if (this.kind === 'consult')  return !!this.form.semestre;
            if (this.kind === 'preview')  return !!this.form.etudiant_id && !!this.form.periode;
            if (this.kind === 'generate') return !!this.form.periode && !this.preflightBusy && !this.isGenerationBlocked();
            return false;
        },

        hasIncompleteReason() {
            return (this.form.incomplete_reason || '').trim().length >= 8;
        },

        isGenerationBlocked() {
            if (this.kind !== 'generate' || !this.preflight || this.preflight.ok) return false;

            const hardBlockCodes = ['missing_subject_configuration', 'bulletin_locked', 'coefficients_missing', 'professeurs_missing'];
            const blocks = this.preflight.blocking_errors || [];

            if ((this.preflight.missing_coefficients || []).length > 0) return true;
            if ((this.preflight.missing_professeurs || []).length > 0) return true;
            if (blocks.some(block => hardBlockCodes.includes(block.code))) return true;

            if (this.preflight.requires_incomplete_reason) {
                return !this.hasIncompleteReason();
            }

            return true;
        },

        generationSummary() {
            if (!this.lastGeneration) return '';

            const skipped = this.lastGeneration.skipped?.length || 0;
            const blocked = (this.lastGeneration.blocking_errors?.length || 0) + (this.lastGeneration.errors?.length || 0);
            return `${this.lastGeneration.created || 0} cree(s), ${this.lastGeneration.regenerated || 0} recalcule(s), ${skipped} ignore(s), ${blocked} blocage(s).`;
        },

        generationStudentsLabel() {
            if (!this.form.classe_id || !this.form.annee_universitaire_id) {
                return 'Selectionnez une classe et une annee';
            }

            if (this.preflight?.students_count !== undefined) {
                const count = this.preflight.students_count || 0;
                const plural = count > 1 ? 's' : '';
                const verb = count > 1 ? 'seront' : 'sera';
                return `${count} etudiant${plural} ${verb} concerne${plural}`;
            }

            if (this.preflightBusy) {
                return 'Verification des etudiants concernes...';
            }

            return 'Pre-controle requis';
        },

        canOpenPilotage() {
            return !!(this.form.classe_id && this.form.annee_universitaire_id && this.form.periode);
        },

        pilotageUrl() {
            if (!this.canOpenPilotage()) return '#';
            const params = new URLSearchParams({
                class_id: this.form.classe_id,
                year_id: this.form.annee_universitaire_id,
                period: this.form.periode,
            });
            return `{{ route('esbtp.pilotage-academique.index') }}?${params.toString()}#alerts`;
        },

        bulletinParams(action = null) {
            const params = new URLSearchParams();
            params.set('etudiant_id', this.form.etudiant_id);
            params.set('classe_id', this.form.classe_id);
            params.set('annee_universitaire_id', this.form.annee_universitaire_id);
            params.set('periode', this.form.periode);
            if (action) params.set('action', action);
            return params;
        },

        configMatieresUrl() {
            const params = new URLSearchParams({
                classe_id: this.form.classe_id,
                annee_universitaire_id: this.form.annee_universitaire_id,
                periode: this.form.periode || 'semestre1',
            });
            if (this.form.etudiant_id) params.set('bulletin', this.form.etudiant_id);
            return `{{ route('esbtp.bulletins.config-matieres') }}?${params.toString()}`;
        },

        openInlineConfig(issue = null) {
            if (!this.form.classe_id || !this.form.annee_universitaire_id || !this.form.periode) {
                this.notify('error', 'Selectionnez classe, annee universitaire et periode avant de configurer.');
                return;
            }

            window.dispatchEvent(new CustomEvent('bus-open-config-modal', {
                detail: {
                    classe_id: this.form.classe_id,
                    annee_universitaire_id: this.form.annee_universitaire_id,
                    periode: this.form.periode,
                    etudiant_id: this.form.etudiant_id || null,
                    issue,
                },
            }));
        },

        async fetchInlineConfigData() {
            const params = new URLSearchParams({
                classe_id: this.form.classe_id,
                annee_universitaire_id: this.form.annee_universitaire_id,
                periode: this.form.periode || 'semestre1',
            });
            const res = await fetch(`{{ route('esbtp.bulletins.config-matieres.inline-data') }}?${params.toString()}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            });
            const data = await this.parseJsonResponse(res);
            if (!res.ok || data.success === false) {
                throw new Error(data.message || `Erreur HTTP ${res.status}`);
            }
            return data;
        },

        async resolvePreviewUrl() {
            const params = this.bulletinParams('preview_pdf');
            const res = await fetch(`{{ route('esbtp.bulletins.check-consistency') }}?${params.toString()}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            });
            const data = await this.parseJsonResponse(res);
            if (!res.ok || !data.ok) {
                throw new Error(data.message || `Erreur HTTP ${res.status}`);
            }

            const consistency = data.consistency || {};
            const configuration = consistency.configuration || {};
            const usesCurrent = data.resolved_url === data.current_url || !consistency.official_bulletin_exists;
            let inlineData = null;

            if (usesCurrent) {
                try {
                    inlineData = await this.fetchInlineConfigData();
                } catch (err) {
                    inlineData = null;
                }
            }

            const missingTypes = inlineData?.missing_count || 0;
            const missingCoefficients = inlineData?.missing_coefficients_count || 0;
            const missingProfesseurs = inlineData?.missing_professeurs_count || 0;

            if (usesCurrent && (configuration.ready === false || missingTypes > 0 || missingCoefficients > 0 || missingProfesseurs > 0)) {
                const detail = inlineData
                    ? ` (${missingTypes} type(s), ${missingCoefficients} coefficient(s), ${missingProfesseurs} professeur(s) a completer)`
                    : '';

                return {
                    blocked: true,
                    inline_config: true,
                    message: 'La configuration du bulletin est incomplete. Completez les matieres, coefficients et professeurs requis avant d ouvrir le PDF live.' + detail,
                    configuration_url: this.configMatieresUrl(),
                };
            }

            return {
                blocked: false,
                url: data.resolved_url || `{{ route('esbtp.bulletins.pdf-params-preview') }}?${this.bulletinParams().toString()}`,
            };
        },

        queuePreflight() {
            if (this.kind !== 'generate') return;

            this.preflightAbort?.abort();
            this.preflight = null;

            if (!this.form.classe_id || !this.form.annee_universitaire_id || !this.form.periode) {
                this.preflightBusy = false;
                return;
            }

            this.fetchPreflight();
        },

        async fetchPreflight() {
            if (this.kind !== 'generate' || !this.form.classe_id || !this.form.annee_universitaire_id || !this.form.periode) {
                return null;
            }

            this.preflightAbort?.abort();
            const controller = new AbortController();
            this.preflightAbort = controller;
            this.preflightBusy = true;

            try {
                const params = new URLSearchParams({
                    classe_id: this.form.classe_id,
                    annee_universitaire_id: this.form.annee_universitaire_id,
                    periode: this.form.periode,
                    recalculer: this.form.recalculer ? '1' : '0',
                });
                const res = await fetch(`{{ route('esbtp.bulletins.generer-classe.preflight') }}?${params.toString()}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    signal: controller.signal,
                });
                const data = await this.parseJsonResponse(res);

                if (!res.ok && !data.preflight) {
                    throw new Error(data.message || `Erreur HTTP ${res.status}`);
                }

                this.preflight = data.preflight || null;
                return this.preflight;
            } catch (err) {
                if (err.name === 'AbortError') return null;
                this.preflight = null;
                this.notify('error', err.message || 'Erreur de pre-controle.');
                return null;
            } finally {
                if (this.preflightAbort === controller) {
                    this.preflightBusy = false;
                    this.preflightAbort = null;
                }
            }
        },

        async fetchStudents() {
            if (!this.form.classe_id || !this.form.annee_universitaire_id) {
                this.studentsAbort?.abort();
                this.students = [];
                this.injectStudentsIntoSelect();
                return;
            }
            // Seule la card apercu a besoin d'injecter une liste d'etudiants.
            if (this.kind !== 'preview') return;
            this.studentsAbort?.abort();
            const requestSeq = ++this.studentsRequestSeq;
            const controller = new AbortController();
            this.studentsAbort = controller;
            this.loadingStudents = true;
            try {
                const baseUrl = `{{ route('esbtp.classes.etudiants', ['classe' => '__ID__']) }}`.replace('__ID__', this.form.classe_id);
                const url = baseUrl + '?annee_universitaire_id=' + encodeURIComponent(this.form.annee_universitaire_id);
                const res = await fetch(url, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    signal: controller.signal,
                });
                const data = await this.parseJsonResponse(res);
                if (requestSeq !== this.studentsRequestSeq) return;
                if (!res.ok) throw new Error(data.message || `Erreur HTTP ${res.status}`);
                this.students = (data.etudiants || []).map(e => ({
                    value: e.id,
                    label: `${e.nom || ''} ${e.prenoms || e.prenom || ''}`.trim() + ` (${e.matricule || ''})`,
                }));
                if (this.kind === 'preview') this.injectStudentsIntoSelect();
            } catch (err) {
                if (err.name === 'AbortError') return;
                this.notify('error', 'Erreur lors du chargement des étudiants : ' + err.message);
                this.students = [];
                this.injectStudentsIntoSelect();
            } finally {
                if (requestSeq === this.studentsRequestSeq) {
                    this.loadingStudents = false;
                    this.studentsAbort = null;
                }
            }
        },

        injectStudentsIntoSelect() {
            if (this.kind !== 'preview') return;
            // Le composant au-select est rendu côté serveur avec un native <select> caché.
            // Pour preview card, on injecte les options étudiants dynamiquement.
            const card = this.$root || this.$el;
            // Trouve le native du 3e au-select (étudiant)
            const wrappers = card.querySelectorAll('.au-select');
            if (wrappers.length < 3) return;
            const studentWrapper = wrappers[2];
            const native = studentWrapper.querySelector('select.au-select-native');
            if (!native) return;
            const currentValue = this.form.etudiant_id;
            let html = '<option value="">' + (this.students.length ? 'Sélectionner…' : 'Aucun étudiant') + '</option>';
            this.students.forEach(s => {
                const sel = String(s.value) === String(currentValue) ? ' selected' : '';
                html += `<option value="${s.value}"${sel}>${s.label}</option>`;
            });
            native.innerHTML = html;
            // Force resync : le composant Alpine au-select écoute change sur le native
            native.value = currentValue || '';
            native.dispatchEvent(new Event('change', { bubbles: true }));
        },

        async submit() {
            if (!this.canSubmit()) {
                this.notify('error', 'Veuillez remplir tous les champs requis.');
                return;
            }
            this.busy = true;
            try {
                if (this.kind === 'consult') {
                    const params = new URLSearchParams();
                    params.set('classe_id', this.form.classe_id);
                    params.set('annee_universitaire_id', this.form.annee_universitaire_id);
                    params.set('semestre', this.form.semestre);
                    window.location.href = `{{ route('esbtp.resultats.index') }}?` + params.toString();
                    return;
                }
                if (this.kind === 'preview') {
                    // Utilise pdf-params-preview qui choisit auto entre snapshot officiel
                    // et live (via BulletinConsistencyService). Plus fiable que
                    // l'ancien previewBulletin qui pouvait erreur en l'absence de bulletin.
                    this.previewIssue = null;
                    const preview = await this.resolvePreviewUrl();
                    if (preview.blocked) {
                        this.previewIssue = preview;
                        this.notify('error', preview.message);
                        return;
                    }
                    window.open(preview.url, '_blank');
                    return;
                }
                if (this.kind === 'generate') {
                    const preflight = await this.fetchPreflight();
                    if (!preflight) {
                        this.notify('error', 'Pre-controle indisponible. La generation est annulee.');
                        return;
                    }
                    if (!preflight.ok && this.isGenerationBlocked()) {
                        this.notify('error', preflight.message || 'Des prerequis bloquent la generation.');
                        return;
                    }

                    const fd = new FormData();
                    fd.append('classe_id', this.form.classe_id);
                    fd.append('annee_universitaire_id', this.form.annee_universitaire_id);
                    fd.append('periode', this.form.periode);
                    if (this.form.recalculer) fd.append('recalculer', '1');
                    if (this.form.incomplete_reason) fd.append('incomplete_reason', this.form.incomplete_reason.trim());
                    const res = await fetch(`{{ route('esbtp.bulletins.generer-classe') }}`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                        body: fd,
                    });
                    const data = await this.parseJsonResponse(res);
                    this.lastGeneration = data;

                    if (res.redirected) {
                        this.notify('error', 'Le serveur a redirige la requete au lieu de retourner le resultat JSON.');
                        return;
                    }

                    if (!res.ok) {
                        const msg = Object.values(data.errors || {}).flat().join(' - ') || data.message || `Erreur HTTP ${res.status}`;
                        this.notify('error', msg);
                        return;
                    }

                    const writes = (data.created || 0) + (data.regenerated || 0);
                    const failures = (data.blocking_errors?.length || 0) + (data.errors?.length || 0);

                    if (writes > 0) {
                        this.notify(failures > 0 ? 'info' : 'success', data.message || 'Generation terminee.');
                        setTimeout(() => {
                            window.location.href = `{{ route('esbtp.bulletins.index') }}?classe_id=${this.form.classe_id}&annee_universitaire_id=${this.form.annee_universitaire_id}&periode_id=${this.form.periode}`;
                        }, 1200);
                        return;
                    }

                    this.notify(failures > 0 ? 'error' : 'info', data.message || 'Aucun bulletin genere.');
                    return;
                }
            } catch (err) {
                this.notify('error', err.message || 'Erreur inattendue.');
            } finally {
                if (this.kind === 'generate') this.busy = false;
                else setTimeout(() => { this.busy = false; }, 400);
            }
        },

        async parseJsonResponse(res) {
            const text = await res.text();
            if (!text) return {};

            try {
                return JSON.parse(text);
            } catch (err) {
                return { message: res.redirected ? 'Le serveur a redirige la requete au lieu de retourner du JSON.' : text };
            }
        },

        notify(type, message) {
            window.dispatchEvent(new CustomEvent('toast', { detail: { type, message } }));
        },
    };
};
}
</script>
@endpush

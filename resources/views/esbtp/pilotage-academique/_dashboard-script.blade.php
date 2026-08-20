@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('cpaDashboard', (config) => ({
        tab: 'direction',
        loading: false,
        syncing: false,
        error: null,
        syncResult: null,
        suppressFilterChange: false,
        filterKeys: ['year_id', 'period', 'system', 'class_id'],
        data: {
            summary: {}, classes: [], alerts: [], sheets: [], my_sheets: [], students: [],
            actor_activity: { actors: [], current_actor: null },
            scope: { global: false, class_count: 0, sources: [] },
            prerequisites: { year_configured: true, message: null }, freshness: {},
            note_coverage: { message: null, summary: {}, subjects: [], incomplete_students: [] },
        },
        drawer: { class: null, student: null },
        sheetWorkspace: { id: null, loading: false, error: null, success: null, data: null },
        transitionModal: { open: false, sheet: null, action: null, reason: '', error: null },
        alertTransitionModal: { open: false, alert: null, status: null, label: '', reason: '', error: null },
        assignmentState: { items: [], loading: false, saving: false, error: null, success: null },
        transitioning: false,
        alertTransitioning: false,
        filters: { ...config.initialFilters },
        init() {
            this.selectTabFromUrl();
            const params = new URLSearchParams(location.search);
            this.filters = { ...this.filters, ...this.pickFilters(Object.fromEntries(params.entries())) };
            this.syncFilterControls();
            if (this.tab === 'assignments') this.$nextTick(() => this.primeAssignmentClass());
            this.load();
            window.addEventListener('popstate', () => this.loadFromUrl());
        },
        selectTabFromUrl() {
            const tabs = ['direction', 'activity', 'sheets', 'alerts', 'classes', 'students', 'mine'];
            if (config.canManageAssignments) tabs.push('assignments');
            const requested = location.hash.replace('#', '');
            if (tabs.includes(requested)) this.tab = requested;
        },
        setTab(tab) {
            this.tab = tab;
            history.replaceState({}, '', `${location.pathname}${location.search}#${tab}`);
            // Un canvas rendu pendant qu'il est masque se dimensionne a zero :
            // si un filtre a recharge la page depuis un autre onglet, les
            // graphiques reviendraient ecrases. On les redessine a l'arrivee.
            if (tab === 'direction') this.$nextTick(() => this.dessinerGraphiques());
            if (tab === 'assignments') this.$nextTick(() => this.primeAssignmentClass());
        },
        formFilters() {
            const form = this.$refs.filtersForm || this.$root.querySelector('form.cpa-filters');
            if (!form) {
                return { ...this.filters };
            }
            const values = Object.fromEntries(new FormData(form).entries());
            return this.pickFilters({ ...this.filters, ...values });
        },
        handleFilterChange() {
            if (this.suppressFilterChange) return;
            this.applyFilters();
        },
        applyFilters() {
            if (this.loading) return;
            this.closeFilterMenus();
            this.syncResult = null;
            this.filters = this.formFilters();
            const params = new URLSearchParams(this.compactFilters(this.filters));
            const nextUrl = `${params.toString() ? `${location.pathname}?${params.toString()}` : location.pathname}#${this.tab}`;
            history.pushState({}, '', nextUrl);
            this.load();
        },
        async synchronize() {
            if (this.syncing || this.loading) return;
            this.closeFilterMenus();
            this.filters = this.formFilters();
            this.syncing = true;
            this.error = null;
            this.syncResult = null;

            try {
                const payload = await this.postJson(config.syncUrl, this.compactFilters(this.filters));
                this.syncResult = payload;
                if (payload.filters) {
                    this.filters = { ...this.filters, ...payload.filters };
                    this.syncFilterControls();
                }
                const params = new URLSearchParams(this.compactFilters(this.filters));
                const nextUrl = `${params.toString() ? `${location.pathname}?${params.toString()}` : location.pathname}#${this.tab}`;
                history.pushState({}, '', nextUrl);
                await this.load();
            } catch (e) {
                this.syncResult = { ok: false, message: e.message, sync: null };
            } finally {
                this.syncing = false;
            }
        },
        loadFromUrl() {
            this.selectTabFromUrl();
            const params = new URLSearchParams(location.search);
            this.filters = { ...config.initialFilters, ...this.pickFilters(Object.fromEntries(params.entries())) };
            this.syncFilterControls();
            this.load();
        },
        syncFilterControls() {
            this.suppressFilterChange = true;
            Object.entries(this.filters).forEach(([name, value]) => {
                const field = this.$root.querySelector(`[name="${name}"]`);
                if (field && field.value !== String(value ?? '')) {
                    field.value = value ?? '';
                    field.dispatchEvent(new Event('change', { bubbles: true }));
                    field.dispatchEvent(new Event('input', { bubbles: true }));
                }
            });
            this.$nextTick(() => { this.suppressFilterChange = false; });
        },
        async load() {
            this.filters = this.formFilters();
            this.loading = true;
            this.error = null;
            try {
                const params = new URLSearchParams(this.compactFilters(this.filters));
                const payload = await this.fetchJson(`${config.dataUrl}?${params.toString()}`);
                this.data = payload;
                this.$nextTick(() => this.dessinerGraphiques());
                if (config.canManageAssignments) await this.loadAssignments();
            } catch (e) {
                this.error = e.message;
            } finally {
                this.loading = false;
            }
        },
        async openClass(id) {
            try {
                const params = new URLSearchParams(this.compactFilters(this.filters));
                this.drawer.class = await this.fetchJson(`${config.classUrl}/${id}?${params.toString()}`);
            } catch (e) {
                this.error = e.message;
            }
        },
        async openStudent(id) {
            try {
                const params = new URLSearchParams(this.compactFilters(this.filters));
                this.drawer.student = await this.fetchJson(`${config.studentUrl}/${id}?${params.toString()}`);
            } catch (e) {
                this.error = e.message;
            }
        },
        async openSheet(id, preserveNotice = false) {
            if (!id) return;
            this.sheetWorkspace = {
                ...this.sheetWorkspace, id, loading: true, error: null,
                success: preserveNotice ? this.sheetWorkspace.success : null,
                data: this.sheetWorkspace.id === id ? this.sheetWorkspace.data : null,
            };
            try {
                this.sheetWorkspace.data = await this.fetchJson(`${config.sheetUrl}/${id}`, 'grade_sheet');
            } catch (e) {
                this.sheetWorkspace.error = e.message;
            } finally {
                this.sheetWorkspace.loading = false;
            }
        },
        sheetActions(sheet, explicitActions = null) {
            const source = explicitActions ?? sheet?.allowed_actions ?? [];
            const actions = Array.isArray(source)
                ? source
                : Object.entries(source).map(([key, value]) => typeof value === 'object' ? { key, ...value } : { key, action: value || key });
            return actions.map((action) => {
                const item = typeof action === 'string' ? { action } : action;
                const key = String(item.action || item.key || item.name || '');
                return {
                    ...item, key, status: key,
                    label: item.label || item.title || this.actionLabel(key),
                    reason_required: Boolean(item.reason_required || item.requires_reason || ['reject', 'request_correction', 'reopen', 'cancel'].includes(key)),
                };
            }).filter((action) => action.key);
        },
        actionLabel(action) {
            return {
                start_entry: 'Commencer la saisie', finish_entry: 'Terminer la saisie',
                submit: 'Transmettre', receive: 'Recevoir', control: 'Contrôler',
                validate: 'Valider', reject: 'Rejeter',
                request_correction: 'Demander une correction', reopen: 'Rouvrir', cancel: 'Annuler',
            }[action] || 'Mettre à jour';
        },
        actionIcon(action) {
            const key = action?.key;
            if (['reject', 'request_correction', 'reopen', 'cancel'].includes(key)) return 'fa-arrow-rotate-left';
            if (key === 'validate') return 'fa-check-double';
            if (key === 'control') return 'fa-shield-halved';
            if (key === 'submit') return 'fa-paper-plane';
            return 'fa-check';
        },
        actionButtonClass(action) {
            return ['reject', 'request_correction', 'reopen', 'cancel'].includes(action?.key) ? 'cpa-btn--danger' : 'cpa-btn--primary';
        },
        requestSheetTransition(action, sheet) {
            if (!sheet?.id || this.transitioning) return;
            this.transitionModal = { open: true, sheet, action, reason: '', error: null };
        },
        closeTransitionModal(force = false) {
            if (this.transitioning && !force) return;
            this.transitionModal = { open: false, sheet: null, action: null, reason: '', error: null };
        },
        async confirmSheetTransition() {
            const { sheet, action, reason } = this.transitionModal;
            if (!sheet?.id || !action?.key) return;
            if (action.reason_required && !reason.trim()) {
                this.transitionModal.error = 'Le motif est obligatoire pour cette transition.';
                return;
            }
            this.transitioning = true;
            this.transitionModal.error = null;
            try {
                const payload = await this.postJson(`${config.sheetUrl}/${sheet.id}/transition`, {
                    action: action.key, expected_lock_version: Number(sheet.lock_version), reason: reason.trim(),
                });
                this.sheetWorkspace.success = payload.message || 'La fiche a été mise à jour.';
                this.closeTransitionModal(true);
                await Promise.all([this.openSheet(sheet.id, true), this.load()]);
            } catch (e) {
                this.transitionModal.error = e.message;
                if (e.status === 409) await this.openSheet(sheet.id, true);
            } finally {
                this.transitioning = false;
            }
        },
        openAlertTransition(alert, status, label) {
            this.alertTransitionModal = { open: true, alert, status, label, reason: '', error: null };
        },
        closeAlertTransition() {
            if (this.alertTransitioning) return;
            this.alertTransitionModal = { open: false, alert: null, status: null, label: '', reason: '', error: null };
        },
        async confirmAlertTransition() {
            const { alert, status, reason } = this.alertTransitionModal;
            if (!alert || !status || !reason.trim() || this.alertTransitioning) return;
            this.alertTransitioning = true;
            this.alertTransitionModal.error = null;
            try {
                await this.postJson(`${config.alertTransitionUrl}/${alert.id}/transition`, { status, reason: reason.trim() });
                this.closeAlertTransitionAfterSuccess();
                await this.load();
            } catch (e) {
                this.alertTransitionModal.error = e.message;
            } finally {
                this.alertTransitioning = false;
            }
        },
        closeAlertTransitionAfterSuccess() {
            this.alertTransitionModal = { open: false, alert: null, status: null, label: '', reason: '', error: null };
        },
        async loadAssignments() {
            this.assignmentState.loading = true;
            this.assignmentState.error = null;
            try {
                const params = new URLSearchParams(this.compactFilters({
                    year_id: this.filters.year_id, class_id: this.filters.class_id,
                }));
                const payload = await this.fetchJson(`${config.assignmentUrl}?${params.toString()}`);
                this.assignmentState.items = payload.assignments || [];
            } catch (e) {
                this.assignmentState.error = e.message;
            } finally {
                this.assignmentState.loading = false;
            }
        },
        async saveAssignment() {
            if (this.assignmentState.saving) return;
            const values = Object.fromEntries(new FormData(this.$refs.assignmentForm).entries());
            if (!values.user_id || !values.classe_id || !values.responsibility || !this.filters.year_id) {
                this.assignmentState.error = 'Choisissez un utilisateur, une classe, une responsabilité et une année universitaire.';
                return;
            }
            this.assignmentState.saving = true;
            this.assignmentState.error = null;
            this.assignmentState.success = null;
            try {
                const payload = await this.sendJson(config.assignmentUrl, 'POST', {
                    ...values, annee_universitaire_id: this.filters.year_id,
                });
                this.assignmentState.success = payload.message;
                await this.loadAssignments();
            } catch (e) {
                this.assignmentState.error = e.message;
            } finally {
                this.assignmentState.saving = false;
            }
        },
        primeAssignmentClass() {
            const field = this.$refs.assignmentForm?.querySelector('[name="classe_id"]');
            if (!field || field.value || !this.filters.class_id) return;
            field.value = String(this.filters.class_id);
            field.dispatchEvent(new Event('change', { bubbles: true }));
            field.dispatchEvent(new Event('input', { bubbles: true }));
        },
        async deactivateAssignment(assignment) {
            if (this.assignmentState.saving) return;
            this.assignmentState.saving = true;
            this.assignmentState.error = null;
            this.assignmentState.success = null;
            try {
                const payload = await this.sendJson(`${config.assignmentUrl}/${assignment.id}`, 'DELETE');
                this.assignmentState.success = payload.message;
                await this.loadAssignments();
            } catch (e) {
                this.assignmentState.error = e.message;
            } finally {
                this.assignmentState.saving = false;
            }
        },
        async fetchJson(url, requiredKey = null) {
            const response = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok || payload.ok === false || (requiredKey && !(requiredKey in payload))) {
                const error = new Error(payload.message || 'Impossible de charger les données de pilotage.');
                error.status = response.status;
                error.payload = payload;
                throw error;
            }
            return payload;
        },
        async postJson(url, body) {
            return this.sendJson(url, 'POST', body);
        },
        async sendJson(url, method, body = null) {
            const response = await fetch(url, {
                method,
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: body === null ? null : JSON.stringify(body),
            });
            let payload;
            try {
                payload = await response.json();
            } catch (e) {
                const error = new Error('Le serveur a répondu dans un format inattendu.');
                error.status = response.status;
                throw error;
            }
            if (!response.ok || payload.ok === false) {
                const error = new Error(payload.message || 'Impossible de mettre à jour le pilotage académique.');
                error.status = response.status;
                error.payload = payload;
                throw error;
            }
            return payload;
        },
        sheetMeta(sheet) {
            if (!sheet) return '';
            return [sheet.classe, sheet.matiere, sheet.teacher || sheet.assigned_processor].filter(Boolean).join(' / ') || 'Informations de fiche indisponibles';
        },
        entryLabel(entry) {
            return entry.student?.name || entry.student_name || entry.student || entry.name || 'Entrée de note';
        },
        entryMeta(entry) {
            const score = entry.note_evidence?.note ?? entry.score ?? entry.note ?? entry.value;
            const status = entry.status_label || entry.status;
            return [score !== null && score !== undefined && score !== '' ? `Note : ${score}` : null, status].filter(Boolean).join(' / ') || 'Aucune information complémentaire';
        },
        eventLabel(event) {
            return event.label || event.status_label || event.type || event.action || event.status || 'Événement de fiche';
        },
        eventMeta(event) {
            return [event.actor?.name || event.actor, this.formatDate(event.occurred_at || event.created_at), event.reason]
                .filter(Boolean).join(' / ') || 'Détail non disponible';
        },
        documentMeta(document) {
            return [document.mime_type || document.type, document.uploaded_by?.name || document.uploaded_by, this.formatDate(document.uploaded_at), document.size_label]
                .filter(Boolean).join(' / ') || 'Document disponible';
        },
        progressPercent(progress) {
            const explicit = progress?.completion_pct ?? progress?.percent;
            if (explicit !== null && explicit !== undefined) return Math.max(0, Math.min(100, Number(explicit) || 0));
            const total = Number(progress?.total_entries ?? 0);
            const completed = Number(progress?.resolved_entries ?? 0);
            return total > 0 ? (completed / total) * 100 : 0;
        },
        progressLabel(progress) {
            const total = Number(progress?.total_entries ?? 0);
            const completed = Number(progress?.resolved_entries ?? 0);
            return total > 0 ? `${completed}/${total} note(s) traitée(s)` : 'Aucune note attendue';
        },
        formatDate(value) {
            if (!value) return '';
            const date = new Date(value);
            return Number.isNaN(date.getTime()) ? value : date.toLocaleString('fr-FR');
        },
        activityDate(value) {
            return value ? `Dernière activité : ${this.formatDate(value)}` : 'Aucune activité récente';
        },
        scopeLabel() {
            const scope = this.data.scope || {};
            if (scope.global) return 'Périmètre global autorisé.';
            const labels = {
                assignment: 'affectations explicites', grade_sheet_activity: 'activité sur les fiches',
                teaching_activity: 'cours réellement assurés', evaluation_activity: 'évaluations créées ou attribuées',
                grade_entry_activity: 'notes réellement saisies',
            };
            const sources = (scope.sources || []).map((source) => labels[source] || source);
            return `${scope.class_count || 0} classe(s) reconnue(s).${sources.length ? ` Preuves : ${sources.join(', ')}.` : ''}`;
        },
        compactFilters(filters) {
            return Object.fromEntries(Object.entries(filters).filter(([, value]) => value !== null && value !== undefined && value !== ''));
        },
        pickFilters(filters) {
            return Object.fromEntries(this.filterKeys.map((key) => [key, filters[key] ?? '']));
        },
        closeFilterMenus() {
            this.$root.querySelectorAll('.cpa-filters .au-select-trigger--open').forEach((trigger) => {
                trigger.click();
            });
        },
        syncSummary() {
            const sync = this.syncResult?.sync;
            if (!sync) return '';
            if (sync.global_refresh) {
                return `${sync.snapshots_refreshed || 0} indicateur(s) recalculé(s) sur ${sync.snapshots_scanned || 0} vérifié(s), ${sync.snapshots_remaining || 0} restant(s), ${sync.stale_retries || 0} reprise(s) différée(s).`;
            }
            return `${sync.classes_processed || 0} classe(s), ${sync.students_processed || 0} étudiant(s), ${sync.class_snapshots || 0} snapshot(s) classe, ${sync.student_snapshots || 0} snapshot(s) étudiant, ${sync.alerts_seen || 0} alerte(s) vérifiée(s).`;
        },
        actorLabel(primary, fallback, emptyLabel) {
            return primary || fallback || emptyLabel;
        },
        sheetTitle(sheet) {
            return sheet?.matiere ? `Fiche de notes · ${sheet.matiere}` : 'Fiche de notes';
        },
        entryModeLabel(sheet) {
            return sheet?.entry_mode === 'paper' ? 'Circuit : fiche papier' : 'Circuit : saisie directe';
        },
        receptionLabel(sheet) {
            if (sheet?.entry_mode !== 'paper') return 'Non requise · saisie directe';
            if (sheet.received_by) return `Reçue par ${sheet.received_by}`;
            if (sheet.submitted_by) return `Remise par ${sheet.submitted_by} · réception en attente`;
            return 'En attente de remise et de réception';
        },
        statusProgressLabel(sheet) {
            const direct = ['expected', 'in_entry', 'entered', 'controlled', 'validated'];
            const paper = ['expected', 'submitted', 'received', 'in_entry', 'entered', 'controlled', 'validated'];
            const exceptional = ['rejected', 'correction_requested', 'cancelled'];
            if (exceptional.includes(sheet?.status)) return 'Traitement interrompu ou à reprendre';
            const workflow = sheet?.entry_mode === 'paper' ? paper : direct;
            const index = workflow.indexOf(sheet?.status);
            return index >= 0 ? `Étape ${index + 1} sur ${workflow.length}` : 'Étape non déterminée';
        },
        entryProgress(sheet) {
            const resolved = Number(sheet.resolved_entries_count || 0);
            const total = Number(sheet.entries_count || 0);
            if (!total) return 'Aucun résultat attendu';
            return `${resolved}/${total}`;
        },
        entryProgressPercent(sheet) {
            const resolved = Number(sheet?.resolved_entries_count || 0);
            const total = Number(sheet?.entries_count || 0);
            return total > 0 ? Math.round(Math.min(100, (resolved / total) * 100)) : 0;
        },
        entryActorsLabel(sheet) {
            if (sheet.entry_actors?.length) {
                return sheet.entry_actors.join(', ');
            }
            if (sheet.entered_by) return sheet.entered_by;
            if (sheet.assigned_processor) return `À saisir par ${sheet.assigned_processor}`;
            return 'Personne non identifiée';
        },
        latestAuditLabel(sheet) {
            const event = sheet.latest_event;
            if (!event) return 'Aucune action enregistrée';
            const eventLabels = {
                created: 'Fiche créée', start_entry: 'Saisie démarrée', submit: 'Fiche remise',
                receive: 'Fiche reçue', finish_entry: 'Saisie terminée', control: 'Fiche contrôlée',
                validate: 'Fiche validée', reject: 'Fiche rejetée', request_correction: 'Correction demandée',
                reopen: 'Fiche rouverte', cancel: 'Fiche annulée', entries_synced: 'Liste des étudiants synchronisée',
                document_uploaded: 'Document ajouté',
            };
            const action = eventLabels[event.type] || 'Fiche mise à jour';
            const actor = event.actor || 'Système';
            const date = event.occurred_at ? new Date(event.occurred_at).toLocaleString('fr-FR') : '';
            return [action, actor, date].filter(Boolean).join(' · ');
        },
        noteCoverageClassLabel() {
            const coverage = this.data.note_coverage || {};
            const summary = coverage.summary || {};
            const classe = coverage.classe?.name || 'Classe sélectionnée';
            return `${classe} · ${summary.students_expected || 0} étudiant(s) attendu(s) · ${summary.evaluations_total || 0} évaluation(s) suivie(s)`;
        },
        noteCoverageCompletionPercent() {
            const summary = this.data.note_coverage?.summary || {};
            return `${this.noteCoverageRate(summary.treated_results || 0, summary.expected_results || 0)}%`;
        },
        noteCoverageRate(treated, expected) {
            const denominator = Number(expected || 0);
            if (denominator <= 0) return 0;
            const value = Math.round((Number(treated || 0) / denominator) * 100);
            return Math.min(100, Math.max(0, value));
        },
        noteCoverageSubjects() {
            return [...(this.data.note_coverage?.subjects || [])].sort((a, b) => {
                if ((b.missing_count || 0) !== (a.missing_count || 0)) {
                    return (b.missing_count || 0) - (a.missing_count || 0);
                }
                return String(a.name || '').localeCompare(String(b.name || ''), 'fr');
            });
        },
        noteCoveragePriorities() {
            return this.noteCoverageSubjects()
                .filter((subject) => (subject.missing_count || 0) > 0)
                .slice(0, 5);
        },
        noteCoverageBlockingEvaluations(subject) {
            return [...(subject?.evaluations || [])]
                .filter((evaluation) => (evaluation.missing_count || 0) > 0)
                .sort((a, b) => (b.missing_count || 0) - (a.missing_count || 0));
        },
        noteCoverageSubjectLabel(subject) {
            if (!subject || (subject.expected_count || 0) === 0) return 'Aucune note attendue';
            if ((subject.missing_count || 0) === 0) return 'Complet';
            return `${subject.treated_count || 0}/${subject.expected_count || 0} traité(s), ${subject.missing_count || 0} manquant(s)`;
        },
        noteCoverageKpis() {
            const summary = this.data.note_coverage?.summary || {};
            const completion = this.noteCoverageRate(summary.treated_results || 0, summary.expected_results || 0);
            return [
                {
                    label: 'Matières couvertes',
                    value: `${summary.subjects_evaluated || 0}/${summary.subjects_total || 0}`,
                    help: 'Avec évaluation et note',
                },
                {
                    label: 'Résultats traités',
                    value: `${summary.treated_results || 0}/${summary.expected_results || 0}`,
                    help: `${completion}% de couverture`,
                },
                {
                    label: 'À compléter',
                    value: summary.missing_results || 0,
                    help: 'Résultats à compléter',
                },
                {
                    label: 'Étudiants concernés',
                    value: summary.incomplete_students || 0,
                    help: `${summary.actors_count || 0} acteur(s) de saisie`,
                },
            ];
        },
        noteCoverageStatusClass() {
            const summary = this.data.note_coverage?.summary || {};
            if ((summary.missing_results || 0) > 0) return 'cpa-badge--danger';
            if ((summary.expected_results || 0) === 0) return 'cpa-badge--warn';
            return 'cpa-badge--ok';
        },
        noteCoverageStatusLabel() {
            const summary = this.data.note_coverage?.summary || {};
            if ((summary.expected_results || 0) === 0) return 'Aucune note attendue';
            if ((summary.missing_results || 0) > 0) return 'Notes incomplètes';
            return 'Couverture complète';
        },
        actorListLabel(actors) {
            if (!actors || !actors.length) return 'Aucun acteur';
            const label = actors.slice(0, 2).map((actor) => `${actor.name} (${actor.role})`).join(', ');
            return label + (actors.length > 2 ? ` +${actors.length - 2}` : '');
        },
        studentResultLabel(row) {
            if (!row) return 'Manquant';
            const labels = {
                numeric: row.note !== null && row.note !== undefined ? `Note : ${row.note}` : 'Note saisie',
                absent: 'Absent',
                exempt: 'Dispensé',
                not_applicable: 'Non applicable',
                missing: 'Manquant',
            };
            return labels[row.status] || 'Manquant';
        },
        studentActorLabel(row) {
            if (!row) return '';
            const parts = [];
            if (row.created_by) parts.push(`Saisie : ${row.created_by}`);
            if (row.updated_by) parts.push(`Correction : ${row.updated_by}`);
            return parts.join(' · ');
        },
        studentMissingLabel(student) {
            const subjects = (student.missing_subjects || []).slice(0, 2).map((subject) => subject.name).join(', ');
            const suffix = (student.missing_subjects || []).length > 2 ? ` +${student.missing_subjects.length - 2}` : '';
            return `${student.name} · ${student.missing_subjects_count} matière(s), ${student.missing_evaluations_count} évaluation(s) · ${subjects}${suffix}`;
        },
        // ===== Vue direction =====
        // Les classes arrivent triees par date de calcul : pour repondre a
        // « qui decroche », on les retrie par score croissant.
        classesTriees() {
            return (this.data.classes || [])
                .filter((c) => c.academic_score !== null && c.academic_score !== undefined)
                .slice()
                .sort((a, b) => Number(a.academic_score) - Number(b.academic_score));
        },
        totalAlertes() {
            return (this.data.summary?.alerts_by_severity || [])
                .reduce((somme, ligne) => somme + Number(ligne.value || 0), 0);
        },
        totalFiches() {
            return (this.data.summary?.sheets_by_status || [])
                .reduce((somme, ligne) => somme + Number(ligne.value || 0), 0);
        },
        // Sous 50 % la classe est en echec, sous 70 % elle decroche : les
        // deux seuils reprennent ceux des badges de niveau de la page.
        teinteScore(valeur) {
            const n = Number(valeur);
            if (n < 50) return 'critique';
            if (n < 70) return 'alerte';
            return '#0453cb';
        },
        // Rampe de gravite croissante. Bloquante et critique doivent se
        // distinguer a l'oeil : deux rouges identiques rendaient le camembert
        // illisible.
        teinteGravite(cle) {
            if (cle === 'blocking') return '#dc2626';
            if (cle === 'critical') return '#ea580c';
            if (cle === 'warning') return 'alerte';
            return '#3b7ddb';
        },
        // Une classe sans snapshot calcule n'a pas de score a tracer. La
        // masquer sans le dire ferait croire a un perimetre plus petit.
        classesSansScore() {
            return (this.data.classes || [])
                .filter((c) => c.academic_score === null || c.academic_score === undefined)
                .length;
        },
        dessinerGraphiques() {
            if (typeof window.klassciGraphique !== 'function') return;

            const classes = this.classesTriees();
            if (this.$refs.chartClasses && classes.length) {
                window.klassciGraphique(this.$refs.chartClasses, 'bar', {
                    labels: classes.map((c) => c.name),
                    datasets: [{
                        label: 'Score académique',
                        data: classes.map((c) => Number(c.academic_score)),
                        tones: classes.map((c) => this.teinteScore(c.academic_score)),
                    }],
                    tooltips: [classes.map((c) => {
                        const couverture = c.coverage_pct === null || c.coverage_pct === undefined
                            ? 'couverture inconnue'
                            : `couverture ${Number(c.coverage_pct).toFixed(0)} %`;
                        return `Score ${Number(c.academic_score).toFixed(0)} % · ${couverture}`;
                    })],
                }, { scales: { y: { max: 100 } } });

                // Cliquer une barre filtre toute la page sur cette classe :
                // c'est l'action que la question « qui decroche » appelle.
                const graphe = window.Chart && window.Chart.getChart(this.$refs.chartClasses);
                if (graphe) {
                    graphe.options.onClick = (evt, elements) => {
                        if (!elements.length) return;
                        const classe = classes[elements[0].index];
                        if (classe?.id) this.filtrerSurClasse(classe.id);
                    };
                    this.$refs.chartClasses.style.cursor = 'pointer';
                    graphe.update();
                }
            }

            const gravites = (this.data.summary?.alerts_by_severity || []).filter((l) => Number(l.value) > 0);
            if (this.$refs.chartAlertes && gravites.length) {
                window.klassciGraphique(this.$refs.chartAlertes, 'doughnut', {
                    labels: gravites.map((l) => l.label),
                    datasets: [{
                        data: gravites.map((l) => Number(l.value)),
                        tones: gravites.map((l) => this.teinteGravite(l.key)),
                    }],
                });
            }

            const etapes = (this.data.summary?.sheets_by_status || []).filter((l) => Number(l.value) > 0);
            if (this.$refs.chartFiches && etapes.length) {
                window.klassciGraphique(this.$refs.chartFiches, 'bar', {
                    labels: etapes.map((l) => l.label),
                    datasets: [{
                        label: 'Fiches',
                        data: etapes.map((l) => Number(l.value)),
                        tones: etapes.map((l) => (
                            l.key === 'rejected' || l.key === 'correction_requested' ? 'critique' : '#0453cb'
                        )),
                    }],
                }, { indexAxis: 'y', scales: { x: { grid: { color: '#eef2f7' } }, y: { grid: { display: false } } } });
            }
        },
        filtrerSurClasse(id) {
            const champ = document.querySelector('select[name="class_id"], input[name="class_id"]');
            if (champ) {
                champ.value = String(id);
                champ.dispatchEvent(new Event('change', { bubbles: true }));
                return;
            }
            this.filters = { ...this.filters, class_id: String(id) };
            this.load();
        },
        kpis() {
            const s = this.data.summary || {};
            return [
                { label: 'Score académique', value: this.percent(s.academic_score), help: 'Classes avec données suffisantes' },
                { label: 'Préparation opérationnelle', value: this.percent(s.operational_score), help: 'Fiches, saisie et validation' },
                { label: 'Alertes ouvertes', value: s.open_alerts ?? 0, help: `${s.blocking_alerts ?? 0} blocage(s)` },
                { label: 'Fiches à suivre', value: s.sheets_pending ?? 0, help: `${s.bulletin_blockers ?? 0} bulletin(s) bloqué(s)` },
            ];
        },
        percent(value) { return value === null || value === undefined || Number.isNaN(Number(value)) ? 'Indisponible' : `${Number(value).toFixed(0)}%`; },
        scoreLabel(value, label) { return value === null || value === undefined ? `${label} indisponible` : `${label} ${Number(value).toFixed(0)}%`; },
        freshnessLabel() {
            const f = this.data.freshness || {};
            return f.last_updated_at ? `Dernière mise à jour ${new Date(f.last_updated_at).toLocaleString('fr-FR')} · ${f.stale_count || 0} snapshot(s) obsolète(s)` : 'Aucune donnée calculée';
        },
        badgeClass(status) {
            if (['validated', 'controlled', 'entered'].includes(status)) return 'cpa-badge--ok';
            if (['rejected', 'correction_requested'].includes(status)) return 'cpa-badge--danger';
            return 'cpa-badge--warn';
        },
        severityClass(severity) {
            if (severity === 'blocking' || severity === 'critical') return 'cpa-badge--danger';
            if (severity === 'warning') return 'cpa-badge--warn';
            return 'cpa-badge--ok';
        },
    }));
});
</script>
@endpush

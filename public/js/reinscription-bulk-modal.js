/*
 * Modal « Réinscription groupée » (composant x-reinscription-bulk-modal) —
 * fabrique Alpine partagée par toutes ses instances.
 *
 * Sortie du composant en octobre 2026 : recopiée dans chaque page qui le porte
 * (liste des étudiants, réinscriptions), elle est désormais mise en cache un an
 * (?v=filemtime). La fabrique propre à chaque instance (identifiant, étudiants,
 * décision, adresse de chargement) reste dans le composant.
 */
window.__brmSharedFactory = function(modalId, students, decisionContext, studentsUrl) {
    return {
        modalId,
        decisionContext,
        students,
        studentsUrl: studentsUrl || null,
        studentsLoaded: !studentsUrl,
        studentsLoading: false,
        studentsError: '',
        selectedIds: [],
        searchQuery: '',
        step: 'select',
        loading: false,
        executing: false,
        results: [],
        errors: [],
        allClasses: [],
        stats: { eligible: 0, blockedSolde: 0, ficheIncomplete: 0,
                 byDecision: { passage: 0, rattrapage: 0, redoublement: 0, inconnu: 0 } },

        init() {
            const modalEl = document.getElementById(this.modalId);
            if (modalEl) {
                modalEl.addEventListener('hidden.bs.modal', () => this.backToSelect());
                // Les classes (dropdown de l'étape 2) et, si la page ne les a pas
                // sérialisés, les étudiants éligibles se chargent à la première
                // ouverture : la plupart des affichages n'ouvrent jamais le modal.
                modalEl.addEventListener('show.bs.modal', () => {
                    this.loadAllClasses();
                    this.loadStudents();
                });
                // Ouvert avant l'initialisation d'Alpine (?open_bulk=1) : l'évènement est passé.
                if (modalEl.classList.contains('show')) {
                    this.loadAllClasses();
                    this.loadStudents();
                }
            }
        },

        async loadStudents(force = false) {
            if (!this.studentsUrl || this.studentsLoading || (this.studentsLoaded && !force)) return;
            this.studentsLoading = true;
            this.studentsError = '';
            try {
                const res = await fetch(this.studentsUrl, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                const data = await res.json();
                this.students = Array.isArray(data?.students) ? data.students : [];
                this.studentsLoaded = true;
            } catch (e) {
                this.studentsError = 'Impossible de charger les étudiants éligibles.';
            } finally {
                this.studentsLoading = false;
            }
        },

        async loadAllClasses() {
            if (this.allClasses.length > 0) return;
            try {
                const res = await fetch('/esbtp/reinscription/api/classes-list', {
                    headers: { 'Accept': 'application/json' }
                });
                if (res.ok) {
                    const data = await res.json();
                    this.allClasses = data?.data?.classes || data?.classes || [];
                }
            } catch (e) { /* silent — suggestions auto restent disponibles */ }
        },

        get visibleStudents() {
            if (!this.searchQuery) return this.students;
            const q = this.searchQuery.toLowerCase();
            return this.students.filter(s =>
                (s.matricule || '').toLowerCase().includes(q) ||
                (s.nom_complet || '').toLowerCase().includes(q)
            );
        },
        get allSelected() {
            return this.visibleStudents.length > 0 &&
                   this.visibleStudents.every(s => this.selectedIds.includes(s.id));
        },
        toggleSelect(id) {
            if (this.selectedIds.includes(id)) this.selectedIds = this.selectedIds.filter(x => x !== id);
            else this.selectedIds.push(id);
        },
        selectAll() {
            if (this.allSelected) {
                this.selectedIds = this.selectedIds.filter(id => !this.visibleStudents.some(s => s.id === id));
            } else {
                this.selectedIds = [...new Set([...this.selectedIds, ...this.visibleStudents.map(s => s.id)])];
            }
        },
        backToSelect() {
            this.step = 'select';
            this.results = [];
            this.errors = [];
            this.loading = false;
            this.executing = false;
            this.showConfirmDialog = false;
            this.executionResults = null;
        },

        async analyze() {
            if (this.selectedIds.length === 0 || this.loading) return;
            if (this.selectedIds.length > 100) {
                window.klassciToast?.('warning', 'Limite : 100 étudiants par analyse');
                return;
            }
            this.loading = true;
            this.step = 'loading';
            try {
                const res = await fetch('/esbtp/reinscription/api/bulk-preview', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ etudiants_ids: this.selectedIds }),
                });
                const data = await res.json();
                if (!res.ok || !data.success) throw new Error(data?.message || 'Erreur réseau');
                const payload = data.data || data;
                this.results = (payload.rows || []).filter(r => !r.error).map(r => ({...r, decision_override: ''}));
                this.errors = (payload.rows || []).filter(r => r.error);
                this.step = 'results';
                this.recomputeStats();
            } catch (err) {
                window.klassciToast?.('error', err.message || 'Erreur analyse');
                this.step = 'select';
            } finally {
                this.loading = false;
            }
        },

        recomputeStats() {
            const s = { eligible: 0, blockedSolde: 0, ficheIncomplete: 0,
                        byDecision: { passage: 0, rattrapage: 0, redoublement: 0, inconnu: 0 } };
            this.results.forEach(r => {
                const dec = r.decision_override || r.decision || 'inconnu';
                if (s.byDecision[dec] !== undefined) s.byDecision[dec]++;
                else s.byDecision.inconnu++;
                if (r.peut_reinscrire && dec !== 'inconnu') s.eligible++;
                if (!r.peut_reinscrire) s.blockedSolde++;
                if (!r.fiche_complete) s.ficheIncomplete++;
            });
            this.stats = s;
        },

        // Confirmation premium (état Alpine — pas de window.confirm() natif disruptif)
        showConfirmDialog: false,
        // Résultats post-submit affichés dans le modal (rule ajax-no-reload-premium)
        executionResults: null,  // { success_list, error_list, success_count, error_count }

        requestExecuteBulk() {
            // Construit la liste d'items et déclenche la confirmation premium
            if (this.stats.eligible === 0 || this.executing) return;
            const items = this.buildItemsForExecute();
            if (items.length === 0) {
                window.dispatchEvent(new CustomEvent('toast', {
                    detail: { type: 'warning', message: 'Aucun étudiant prêt à être réinscrit (classe cible manquante ou bloqué).' }
                }));
                window.klassciToast?.('warning', 'Aucun étudiant prêt à être réinscrit (classe cible manquante ou bloqué).');
                return;
            }
            this.showConfirmDialog = true;
        },

        cancelConfirm() {
            this.showConfirmDialog = false;
        },

        buildItemsForExecute() {
            return this.results
                .filter(r => r.peut_reinscrire && (r.decision_override || r.decision) !== 'inconnu' && r.target_classe_id)
                .map(r => ({
                    etudiant_id: r.etudiant_id,
                    decision: r.decision_override || r.decision,
                    classe_id: r.target_classe_id,
                    affectation_status: r.affectation_status || 'affecté',
                    observations: r.observations || null,
                }));
        },

        async executeBulk() {
            this.showConfirmDialog = false;
            const items = this.buildItemsForExecute();
            if (items.length === 0 || this.executing) return;
            this.executing = true;
            try {
                const res = await fetch('/esbtp/reinscription/api/bulk-execute', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ items, decision_context: this.decisionContext }),
                });
                const data = await res.json();

                // Toast récap global (succès partiel ou complet)
                const successCount = data.success_count ?? 0;
                const errorCount = data.error_count ?? (data.errors?.length ?? 0);

                if (successCount > 0) {
                    const msg = errorCount > 0
                        ? `${successCount} réinscription(s) effectuée(s), ${errorCount} échec(s)`
                        : `${successCount} réinscription(s) effectuée(s) avec succès`;
                    window.dispatchEvent(new CustomEvent('toast', {
                        detail: { type: errorCount > 0 ? 'warning' : 'success', message: msg }
                    }));
                    window.klassciToast?.(errorCount > 0 ? 'warning' : 'success', msg);
                }

                // Toast détaillé PAR étudiant en échec (ne pas concaténer en une seule ligne)
                (data.error_list || data.errors || []).forEach(err => {
                    const label = (err.matricule || '#' + (err.etudiant_id ?? '?')) +
                                  (err.nom_complet ? ' — ' + err.nom_complet : '');
                    const detailMsg = label + ' : ' + (err.message || 'Erreur inconnue');
                    window.dispatchEvent(new CustomEvent('toast', {
                        detail: { type: 'error', message: detailMsg, duration: 8000 }
                    }));
                    window.klassciToast?.('error', detailMsg);
                });

                // Si zéro succès et zéro detail → toast global d'erreur
                if (successCount === 0 && errorCount === 0) {
                    const msg = data?.message || 'Échec de la batch';
                    window.dispatchEvent(new CustomEvent('toast', {
                        detail: { type: 'error', message: msg }
                    }));
                    window.klassciToast?.('error', msg);
                }

                // Stocke les résultats pour affichage in-modal (panneau récapitulatif)
                this.executionResults = {
                    success_count: successCount,
                    error_count: errorCount,
                    success_list: data.success_list || [],
                    error_list: data.error_list || data.errors || [],
                };

                // Refresh local sans reload (rule ajax-no-reload-premium)
                window.dispatchEvent(new CustomEvent('reinscription:refresh', {
                    detail: { batch_id: data.batch_id, success_count: successCount }
                }));

                // Si tout est OK, fermer le modal après un court délai
                if (errorCount === 0 && successCount > 0) {
                    setTimeout(() => {
                        const modalEl = document.getElementById(this.modalId);
                        if (modalEl && window.bootstrap?.Modal?.getInstance) {
                            const instance = window.bootstrap.Modal.getInstance(modalEl);
                            instance?.hide();
                        }
                        // Retirer du DOM les étudiants réinscrits (refresh local visuel)
                        const successIds = (data.success_list || []).map(s => s.etudiant_id);
                        this.students = this.students.filter(s => !successIds.includes(s.id));
                        this.selectedIds = [];
                    }, 1500);
                }
            } catch (err) {
                const msg = err.message || 'Erreur batch';
                window.dispatchEvent(new CustomEvent('toast', {
                    detail: { type: 'error', message: msg }
                }));
                window.klassciToast?.('error', msg);
            } finally {
                this.executing = false;
            }
        },
    };
};

// Factory dropdown premium réutilisable (remplace les <select> natifs)
// Idempotency guard : ne pas re-déclarer si déjà défini (rule premium-selects AJAX-safe)
if (typeof window.brmDropdown !== 'function') {
    window.brmDropdown = function () {
        return { open: false };
    };
}

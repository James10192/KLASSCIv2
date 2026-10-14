<script>
function cmConfigMatieres() {
    return {
        currentPeriode: '{{ $_periode }}',
        loadingSwitch: false,
        targetPeriode: null,
        periodRequestSequence: 0,
        counts: { total: 0, general: 0, technique: 0, excluded: 0, configured: 0 },
        completionStatus: 'unconfigured',
        completionStatusLabel: 'Non configuré',
        search: '',
        copyOpen: false,
        copyMode: 'override',
        copySaving: false,
        preview: null,
        previewLoading: false,
        previewRequestSequence: 0,

        get otherPeriode() {
            return this.currentPeriode === 'semestre1' ? 'semestre2'
                : this.currentPeriode === 'semestre2' ? 'semestre1' : null;
        },
        get otherPeriodeLabel() {
            return this.otherPeriode === 'semestre1' ? 'Semestre 1'
                : this.otherPeriode === 'semestre2' ? 'Semestre 2' : '';
        },
        get periodeLabel() {
            return this.currentPeriode === 'semestre1' ? 'Semestre 1'
                : this.currentPeriode === 'semestre2' ? 'Semestre 2' : 'Annuel (S1 + S2)';
        },

        init() {
            this.recountFromDOM();
            // Listen popstate pour synchroniser si l'utilisateur use back/forward
            window.addEventListener('popstate', (ev) => {
                const url = new URL(window.location.href);
                const p = url.searchParams.get('periode') || 'semestre1';
                if (p !== this.currentPeriode) {
                    // L'URL seule ne suffit pas : recharger les classifications de la période.
                    this.switchPeriode(p, true);
                }
            });
        },

        async switchPeriode(targetPeriode, fromPopstate = false) {
            if (!['semestre1', 'semestre2', 'annuel'].includes(targetPeriode)) return;
            if ((targetPeriode === this.currentPeriode && !this.loadingSwitch)
                || (this.loadingSwitch && !fromPopstate)) return;
            const sequence = ++this.periodRequestSequence;
            this.targetPeriode = targetPeriode;
            this.loadingSwitch = true;
            try {
                const url = new URL('{{ route('esbtp.bulletins.config-matieres.data') }}');
                url.searchParams.append('classe_id', '{{ $_classeId }}');
                url.searchParams.append('annee_universitaire_id', '{{ $_anneeId }}');
                url.searchParams.append('periode', targetPeriode);
                const res = await fetch(url.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                const data = await res.json();
                if (!data.success || !data.matiere_types) throw new Error(data.message || 'Classification indisponible');
                if (sequence !== this.periodRequestSequence) return;

                // Radios, période affichée et champ POST ne changent qu'ensemble.
                document.querySelectorAll('[data-matiere-row]').forEach(row => {
                    const matiereId = parseInt(row.dataset.matiereRow, 10);
                    const type = data.matiere_types[matiereId] ?? 'none';
                    const radio = row.querySelector(`.cm-type-picker input[value="${type}"]`);
                    if (radio) radio.checked = true;
                });
                this.applyPeriodeFromAjax(targetPeriode, fromPopstate);
                this.copyOpen = false;
                this.previewRequestSequence++;
                this.previewLoading = false;
                this.preview = null;
                this.onTypeChange();
            } catch (e) {
                if (sequence !== this.periodRequestSequence) return;
                if (fromPopstate) {
                    // Conserver la cohérence URL/formulaire si le retour arrière échoue.
                    const currentUrl = new URL(window.location.href);
                    currentUrl.searchParams.set('periode', this.currentPeriode);
                    window.history.replaceState({ periode: this.currentPeriode }, '', currentUrl);
                }
                if (typeof window.klassciToast === 'function') {
                    window.klassciToast('error', 'Impossible de changer de période : ' + e.message);
                } else {
                    alert('Impossible de changer de période : ' + e.message);
                }
            } finally {
                if (sequence === this.periodRequestSequence) {
                    this.loadingSwitch = false;
                    this.targetPeriode = null;
                }
            }
        },

        applyPeriodeFromAjax(periode, fromPopstate) {
            this.currentPeriode = periode;
            // Update hidden input + section title + URL (pushState)
            const hiddenPeriode = document.querySelector('input[name="periode"]');
            if (hiddenPeriode) hiddenPeriode.value = periode;
            const sectionTitle = document.getElementById('cmSectionTitle');
            if (sectionTitle) sectionTitle.textContent = 'Classification des matières — ' + this.periodeLabel;
            if (!fromPopstate) {
                const url = new URL(window.location.href);
                url.searchParams.set('periode', periode);
                window.history.pushState({ periode }, '', url.toString());
            }
        },

        recountFromDOM() {
            const allRows = document.querySelectorAll('[data-matiere-row]');
            this.counts.total = allRows.length;
            this.counts.general = document.querySelectorAll('.cm-type-picker input[value="general"]:checked').length;
            this.counts.technique = document.querySelectorAll('.cm-type-picker input[value="technique"]:checked').length;
            this.counts.excluded = document.querySelectorAll('.cm-type-picker input[value="none"]:checked').length;
            this.counts.configured = this.counts.general + this.counts.technique;
            // Status
            if (this.counts.total === 0) {
                this.completionStatus = 'unconfigured';
                this.completionStatusLabel = 'Aucune matière';
            } else if (this.counts.configured >= this.counts.total) {
                this.completionStatus = 'complete';
                this.completionStatusLabel = 'Complet';
            } else if (this.counts.configured === 0) {
                this.completionStatus = 'unconfigured';
                this.completionStatusLabel = 'Non configuré';
            } else {
                this.completionStatus = 'incomplete';
                this.completionStatusLabel = 'Incomplet';
            }
        },

        onTypeChange() {
            // Mise à jour pills statut
            document.querySelectorAll('[data-matiere-row]').forEach(row => {
                const matiereId = row.dataset.matiereRow;
                const checked = row.querySelector('.cm-type-picker input[type="radio"]:checked');
                const type = checked ? checked.value : null;
                row.querySelectorAll(`[data-status-pill="${matiereId}"]`).forEach(p => {
                    p.style.display = (p.dataset.pillType === type) ? 'inline-flex' : 'none';
                });
            });
            this.recountFromDOM();
        },

        setAll(type) {
            // ⚠️ Ne touche QUE les rows visibles (filter actif)
            const visibleRows = Array.from(document.querySelectorAll('[data-matiere-row]'))
                .filter(r => r.style.display !== 'none');
            visibleRows.forEach(row => {
                const radio = row.querySelector(`.cm-type-picker input[value="${type}"]`);
                if (radio) radio.checked = true;
            });
            this.onTypeChange();
        },

        filterMatieres() {
            const term = (this.search || '').toLowerCase().trim();
            document.querySelectorAll('[data-matiere-row]').forEach(row => {
                const matiereName = row.dataset.matiereName || '';
                row.style.display = (!term || matiereName.includes(term)) ? '' : 'none';
            });
        },

        onSubmit(ev) {
            if (this.counts.configured === 0 && this.counts.excluded === 0) {
                ev.preventDefault();
                alert('Veuillez choisir une classification (Général/Technique/Exclure) pour au moins une matière.');
            }
        },

        async openCopyModal() {
            this.copyOpen = true;
            await this.refreshPreview();
        },

        async refreshPreview() {
            const sequence = ++this.previewRequestSequence;
            if (!this.otherPeriode) {
                this.preview = null;
                this.previewLoading = false;
                return; // annuel n'a pas d'autre période
            }
            const source = this.currentPeriode;
            const target = this.otherPeriode;
            const mode = this.copyMode;
            this.previewLoading = true;
            this.preview = null;
            try {
                const fd = new FormData();
                fd.append('_token', document.querySelector('meta[name="csrf-token"]').content);
                fd.append('classe_id', '{{ $_classeId }}');
                fd.append('annee_universitaire_id', '{{ $_anneeId }}');
                fd.append('source_periode', source);
                fd.append('target_periode', target);
                fd.append('mode', mode);
                fd.append('dry_run', '1');
                const res = await fetch('{{ route('esbtp.bulletins.config-matieres.copy') }}', {
                    method: 'POST', body: fd,
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                const data = await res.json();
                if (sequence === this.previewRequestSequence && this.copyOpen
                    && source === this.currentPeriode && mode === this.copyMode) {
                    this.preview = data;
                }
            } catch (e) {
                if (sequence === this.previewRequestSequence && this.copyOpen) {
                    this.preview = null;
                    alert('Impossible de calculer l\'aperçu : ' + e.message);
                }
            } finally {
                if (sequence === this.previewRequestSequence) this.previewLoading = false;
            }
        },

        async executeCopy() {
            if (!this.otherPeriode || !this.preview || this.previewLoading || this.copySaving) return;
            this.copySaving = true;
            try {
                const fd = new FormData();
                fd.append('_token', document.querySelector('meta[name="csrf-token"]').content);
                fd.append('classe_id', '{{ $_classeId }}');
                fd.append('annee_universitaire_id', '{{ $_anneeId }}');
                fd.append('source_periode', this.currentPeriode);
                fd.append('target_periode', this.otherPeriode);
                fd.append('mode', this.copyMode);
                const res = await fetch('{{ route('esbtp.bulletins.config-matieres.copy') }}', {
                    method: 'POST', body: fd,
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.success) {
                    alert(`Copie effectuée : ${data.summary.created} créées, ${data.summary.updated} mises à jour, ${data.summary.skipped} skippées.`);
                    this.copyOpen = false;
                } else {
                    alert('Erreur : ' + (data.message || 'inconnue'));
                }
            } catch (e) {
                alert('Erreur réseau : ' + e.message);
            } finally {
                this.copySaving = false;
            }
        }
    };
}
</script>

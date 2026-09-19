{{-- Pilotage Alpine de la page de maquette : chargement d'un combo, place sur le bulletin, semestre, import depuis le planning général. Extrait de la vue pour la garder lisible. --}}
<script>
function matiereClassification() {
    return {
        filiereId: '',
        niveauId: '',
        loading: false,
        saving: false,
        loaded: false,
        isTroncCommun: false,
        filiereName: '',
        matieres: [],
        kpis: { total: 0, tronc_commun: 0, specialite: 0, non_classe: 0 },
        maquette: { renseignee: false, semestre_1: 0, semestre_2: 0 },
        planning: null,
        apercuOuvert: false,
        apercu: { lignes: [], hors_maquette: [], changements: 0, empreinte: null },
        ajoutOuvert: false,
        ajoutChargement: false,
        ajoutRecherche: '',
        ajoutDisponibles: [],
        ajoutSelection: [],
        get hasSuggestions() { return this.matieres.some(m => m.suggested); },

        init() {
            this.$watch('filiereId', () => this.tryLoad());
            this.$watch('niveauId', () => this.tryLoad());
        },

        // Le composant au-select évalue son x-model dans son propre scope : on capte
        // plutôt l'evenement change du select natif qui bulle jusqu'a cette racine.
        onNativeChange(e) {
            const t = e && e.target;
            if (!t || !t.name) return;
            if (t.name === 'filiere_id') this.filiereId = t.value;
            if (t.name === 'niveau_id') this.niveauId = t.value;
        },

        tryLoad() {
            if (this.filiereId && this.niveauId) this.loadCombo();
        },

        notify(message, type) {
            let host = document.getElementById('mtc-toast-host');
            if (!host) {
                host = document.createElement('div');
                host.id = 'mtc-toast-host';
                host.className = 'mtc-toast-host';
                document.body.appendChild(host);
            }
            const el = document.createElement('div');
            el.className = 'mtc-toast mtc-toast--' + (type === 'error' ? 'error' : 'success');
            el.innerHTML = '<i class="fas ' + (type === 'error' ? 'fa-circle-exclamation' : 'fa-circle-check') + '"></i><span></span>';
            el.querySelector('span').textContent = message;
            host.appendChild(el);
            requestAnimationFrame(() => el.classList.add('mtc-toast--in'));
            setTimeout(() => {
                el.classList.remove('mtc-toast--in');
                setTimeout(() => el.remove(), 250);
            }, 3200);
        },

        async loadCombo() {
            this.loading = true;
            this.loaded = false;
            try {
                const url = "{{ route('esbtp.matieres.classification.combo') }}"
                    + "?filiere_id=" + encodeURIComponent(this.filiereId)
                    + "&niveau_id=" + encodeURIComponent(this.niveauId);
                const res = await fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                if (!res.ok) throw new Error('Chargement impossible.');
                const data = await res.json();
                this.isTroncCommun = !!data.is_tronc_commun;
                this.filiereName = data.filiere || '';
                this.matieres = (data.matieres || []).map(m => ({
                    ...m,
                    wasSuggested: (m.classification === null && m.suggested != null),
                    classification: m.classification ?? (m.suggested ?? null),
                }));
                this.maquette = data.maquette || { renseignee: false, semestre_1: 0, semestre_2: 0 };
                this.planning = data.planning || null;
                this.recomputeKpis();
                this.loaded = true;
            } catch (e) {
                this.notify(e.message, 'error');
            } finally {
                this.loading = false;
            }
        },

        setClass(m, val) {
            m.classification = (m.classification === val) ? null : val;
            this.recomputeKpis();
        },

        bulk(val) {
            this.matieres.forEach(m => { m.classification = val; });
            this.recomputeKpis();
        },

        applySuggestions() {
            this.matieres.forEach(m => { if (m.suggested) m.classification = m.suggested; });
            this.recomputeKpis();
        },

        recomputeKpis() {
            this.kpis = {
                total: this.matieres.length,
                tronc_commun: this.matieres.filter(m => m.classification === 'tronc_commun').length,
                specialite: this.matieres.filter(m => m.classification === 'specialite').length,
                non_classe: this.matieres.filter(m => !m.classification).length,
            };
        },

        // --- Semestre ---------------------------------------------------

        setSemestre(m, valeur) {
            m.semestre = valeur;
        },

        libelleSemestre(valeur) {
            if (valeur === 1) return 'Semestre 1';
            if (valeur === 2) return 'Semestre 2';
            return 'Les deux';
        },

        // --- Place sur le bulletin --------------------------------------

        /**
         * Saisie directe : la matière prend une place propre à cette filière.
         *
         * Champ vidé = place propre effacée. On ne recale PAS le champ sur
         * l'ordre général au passage : effacer pour retaper ferait sauter une
         * valeur sous les doigts pendant la frappe. Le retour à l'héritage se
         * lit au rechargement, ou avec « Revenir à l'ordre général ».
         */
        poserRang(m, valeur) {
            const rang = parseInt(valeur, 10);
            m.ordre_effectif = Number.isFinite(rang) && rang >= 1 ? rang : null;
            m.ordre_bulletin = m.ordre_effectif;
            m.ordre_source = m.ordre_effectif === null ? 'aucun' : 'combo';
        },

        monter(idx) { this.deplacer(idx, idx - 1); },
        descendre(idx) { this.deplacer(idx, idx + 1); },

        /**
         * Réordonner fixe l'ordre DE CETTE FILIÈRE : toutes les lignes reçoivent
         * une place propre, 1..N. Sans quoi une ligne héritée et une ligne propre
         * se mélangeraient et l'ordre affiché ne serait pas celui enregistré.
         */
        deplacer(depuis, vers) {
            if (vers < 0 || vers >= this.matieres.length) return;
            const copie = this.matieres.slice();
            const [ligne] = copie.splice(depuis, 1);
            copie.splice(vers, 0, ligne);
            this.matieres = copie.map((m, i) => ({
                ...m,
                ordre_effectif: i + 1,
                ordre_bulletin: i + 1,
                ordre_source: 'combo',
            }));
        },

        // --- Import depuis le planning général --------------------------

        async ouvrirApercuPlanning() {
            if (!this.planning) return;
            this.saving = true;
            try {
                const data = await this.appelPlanning(false, null);
                this.apercu = {
                    lignes: data.diff.lignes || [],
                    hors_maquette: data.diff.hors_maquette || [],
                    changements: data.diff.changements || 0,
                    empreinte: data.diff.empreinte || null,
                };
                this.apercuOuvert = true;
            } catch (e) {
                this.notify(e.message, 'error');
            } finally {
                this.saving = false;
            }
        },

        fermerApercu() { this.apercuOuvert = false; },

        async appliquerPlanning() {
            this.saving = true;
            try {
                const data = await this.appelPlanning(true, this.apercu.empreinte);
                this.apercuOuvert = false;
                this.notify(data.message || 'Semestres importés.', 'success');
                await this.loadCombo();
            } catch (e) {
                // Conflit : la source a bougé depuis l'aperçu. On rouvre l'aperçu
                // à jour plutôt que d'écraser en silence.
                this.notify(e.message, 'error');
                await this.ouvrirApercuPlanning();
            } finally {
                this.saving = false;
            }
        },

        async appelPlanning(appliquer, empreinte) {
            const corps = {
                filiere_id: this.filiereId,
                niveau_id: this.niveauId,
                annee_universitaire_id: this.planning.annee_id,
                appliquer: appliquer,
            };
            if (empreinte) corps.empreinte = empreinte;

            const res = await fetch("{{ route('esbtp.matieres.classification.import-planning') }}", {
                method: 'POST',
                headers: this.entetes(),
                body: JSON.stringify(corps),
            });
            const data = await res.json();
            if (!res.ok || !data.success) throw new Error(data.message || "Import impossible.");
            return data;
        },

        // --- Ordre général ----------------------------------------------

        async promouvoirOrdreGeneral() {
            const ordres = this.matieres
                .filter(m => m.ordre_effectif !== null)
                .map(m => ({ matiere_id: m.matiere_id, ordre_bulletin: m.ordre_effectif }));

            if (ordres.length === 0) {
                this.notify("Aucune place à promouvoir : posez d'abord un ordre.", 'error');
                return;
            }

            await this.poster("{{ route('esbtp.matieres.classification.ordre-general') }}", { ordres });
        },

        async resetOrdre() {
            await this.poster("{{ route('esbtp.matieres.classification.reset-ordre') }}", {
                filiere_id: this.filiereId,
                niveau_id: this.niveauId,
            });
        },

        // --- Ajout à la maquette -----------------------------------------

        async ouvrirAjout() {
            this.ajoutOuvert = true;
            this.ajoutRecherche = '';
            this.ajoutSelection = [];
            this.ajoutChargement = true;
            try {
                const url = "{{ route('esbtp.matieres.available-for-combination') }}"
                    + "?filiere_id=" + encodeURIComponent(this.filiereId)
                    + "&niveau_id=" + encodeURIComponent(this.niveauId);
                const res = await fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                const data = await res.json();
                if (!res.ok || !data.success) throw new Error(data.message || 'Chargement impossible.');
                this.ajoutDisponibles = data.matieres || [];
            } catch (e) {
                this.notify(e.message, 'error');
                this.ajoutDisponibles = [];
            } finally {
                this.ajoutChargement = false;
            }
        },

        fermerAjout() {
            this.ajoutOuvert = false;
            this.ajoutSelection = [];
        },

        /** Les matières proposées, filtrées par la recherche. */
        ajoutFiltrees() {
            const q = (this.ajoutRecherche || '').trim().toLowerCase();
            if (!q) return this.ajoutDisponibles;
            return this.ajoutDisponibles.filter(m =>
                (m.name || '').toLowerCase().includes(q)
                || (m.code || '').toLowerCase().includes(q)
            );
        },

        basculerAjout(id) {
            const i = this.ajoutSelection.indexOf(id);
            if (i === -1) this.ajoutSelection.push(id);
            else this.ajoutSelection.splice(i, 1);
        },

        async appliquerAjout() {
            if (this.ajoutSelection.length === 0) return;
            this.saving = true;
            try {
                const res = await fetch("{{ route('esbtp.matieres.add-to-combination') }}", {
                    method: 'POST',
                    headers: this.entetes(),
                    body: JSON.stringify({
                        matiere_ids: this.ajoutSelection,
                        combinations: [{ filiere_id: this.filiereId, niveau_id: this.niveauId }],
                    }),
                });
                const data = await res.json();
                if (!res.ok || !data.success) throw new Error(data.message || 'Ajout impossible.');
                this.notify(data.message, 'success');
                this.fermerAjout();
                await this.loadCombo();
            } catch (e) {
                this.notify(e.message, 'error');
            } finally {
                this.saving = false;
            }
        },

        // --- Retrait de la maquette --------------------------------------

        /**
         * Retire une matière du combo courant.
         *
         * Le serveur refuse d'abord si la matière porte des évaluations ici, et
         * renvoie combien : on redemande alors, une seule fois, avec le nombre
         * sous les yeux. Une note retirée de la maquette reste en base mais
         * n'apparaît plus au bulletin, et c'est ce qu'il faut avoir compris
         * avant de valider.
         */
        async retirerDeLaMaquette(m, malgreLesNotes = false) {
            if (!malgreLesNotes && !confirm('Retirer « ' + m.name + ' » de cette maquette ?')) return;

            this.saving = true;
            try {
                const res = await fetch("{{ route('esbtp.matieres.classification.retirer') }}", {
                    method: 'POST',
                    headers: this.entetes(),
                    body: JSON.stringify({
                        filiere_id: this.filiereId,
                        niveau_id: this.niveauId,
                        matiere_id: m.matiere_id,
                        malgre_les_notes: malgreLesNotes,
                    }),
                });
                const data = await res.json();

                if (!res.ok && data.confirmation_requise) {
                    this.saving = false;
                    if (confirm(data.message + '\n\nRetirer quand même ?')) {
                        await this.retirerDeLaMaquette(m, true);
                    }
                    return;
                }

                if (!res.ok || !data.success) throw new Error(data.message || 'Retrait impossible.');

                this.notify(data.message, 'success');
                await this.loadCombo();
            } catch (e) {
                this.notify(e.message, 'error');
            } finally {
                this.saving = false;
            }
        },

        // --- Enregistrement ---------------------------------------------

        /** `validerSemestres` marque le combo comme renseigné : c'est un geste explicite. */
        async save(validerSemestres = false) {
            this.saving = true;
            try {
                const res = await fetch("{{ route('esbtp.matieres.classification.save') }}", {
                    method: 'POST',
                    headers: this.entetes(),
                    body: JSON.stringify({
                        filiere_id: this.filiereId,
                        niveau_id: this.niveauId,
                        valider_semestres: validerSemestres,
                        classifications: this.matieres.map(m => ({
                            matiere_id: m.matiere_id,
                            classification: m.classification,
                            // Une place héritée n'est pas renvoyée comme propre :
                            // sinon le simple fait d'enregistrer figerait l'héritage.
                            ordre_bulletin: m.ordre_source === 'combo' ? m.ordre_effectif : null,
                            semestre: m.semestre ?? null,
                        })),
                    }),
                });
                const data = await res.json();
                if (!res.ok || !data.success) throw new Error(data.message || 'Erreur lors de l\'enregistrement.');
                this.matieres.forEach(m => { m.wasSuggested = false; });
                this.maquette.renseignee = !!data.maquette_renseignee;
                this.notify(data.message, 'success');
                if (validerSemestres) await this.loadCombo();
            } catch (e) {
                this.notify(e.message, 'error');
            } finally {
                this.saving = false;
            }
        },

        async poster(url, corps) {
            this.saving = true;
            try {
                const res = await fetch(url, { method: 'POST', headers: this.entetes(), body: JSON.stringify(corps) });
                const data = await res.json();
                if (!res.ok || !data.success) throw new Error(data.message || 'Enregistrement impossible.');
                this.notify(data.message, 'success');
                await this.loadCombo();
            } catch (e) {
                this.notify(e.message, 'error');
            } finally {
                this.saving = false;
            }
        },

        entetes() {
            return {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            };
        },
    };
}
</script>

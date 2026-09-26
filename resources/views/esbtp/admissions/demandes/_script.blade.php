<script>
/*
 * Demandes d'inscription. Un seul composant Alpine pour la page : la liste et
 * le panneau sont rendus cote serveur et remplaces par fragments ; les
 * decisions passent par les routes des deux corbeilles, en JSON.
 */
if (typeof window.demandesInscription !== 'function') {
window.demandesInscription = function () {
    const jeton = () => document.querySelector('meta[name="csrf-token"]')?.content || '';
    const vierge = () => ({ prep: null, chargement: false, f: {}, tuteur: { nom: '', prenoms: '', telephone: '', relation: '', profession: '' }, sansTuteur: false, erreurs: {}, message: '', frais: { chargement: false, masques: false, lignes: [], total: 0, incomplet: false } });

    return {
        cfg: {}, filtres: {}, compteurs: {}, classes: [],
        chargement: false, ouvert: null, chargementDossier: false, dossier: null,
        fenetre: '', occupe: false, picker: { ouvert: false, q: '' },
        ins: vierge(),
        reins: { classe_id: null, decision: '', observations: '', erreurs: {}, message: '' },
        rejet: { motif: '', erreur: '' },
        rdv: { creneaux: [], chargement: false, choix: null },

        init() {
            // La page a ses propres toasts : le relais du shell mobile les doublerait.
            document.body.setAttribute('data-m-toast', 'off');
            this.cfg = JSON.parse(this.$root.dataset.dmiConfig || '{}');
            this.classes = JSON.parse(this.$root.dataset.dmiClasses || '[]');
            this.filtres = Object.assign({ type: '', etat: 'a_traiter', etape: '', q: '', sans_rdv: false, contact: false }, this.cfg.filtres || {});
            this.compteurs = this.cfg.compteurs || {};
            this.$root.addEventListener('click', (e) => this.surClic(e));
            this.$root.addEventListener('dmi-classe', () => { if (this.fenetre === 'inscrire') this.chargerFrais(); });
            this._clavier = (e) => this.surTouche(e);
            document.addEventListener('keydown', this._clavier);
            this._retour = () => { this.filtres = Object.assign(this.filtres, this.lireAdresse()); this.recharger(false, true); };
            window.addEventListener('popstate', this._retour);
            if (this.cfg.ouvrir) this.ouvrirDossier(this.cfg.ouvrir, this.cfg.agir ? 'suite' : null);
        },

        destroy() {
            document.removeEventListener('keydown', this._clavier);
            window.removeEventListener('popstate', this._retour);
        },

        /* ---------- Echanges avec le serveur ---------- */
        async appeler(url, options) {
            const o = options || {};
            const entetes = { 'X-CSRF-TOKEN': jeton(), 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' };
            let corps = o.corps;
            if (corps && !(corps instanceof FormData)) { entetes['Content-Type'] = 'application/json'; corps = JSON.stringify(corps); }
            const r = await fetch(url, { method: o.methode || 'GET', headers: entetes, body: corps, credentials: 'same-origin' });
            const d = await r.json().catch(() => ({}));
            if (!r.ok || d.ok === false) {
                const e = new Error(d.message || ({ 419: 'Votre session a expiré : rechargez la page.', 429: 'Trop de demandes, patientez une minute.', 403: "Vous n'avez pas le droit de faire cette action." })[r.status] || ('Action impossible (erreur ' + r.status + ').'));
                e.donnees = d; e.statut = r.status;
                throw e;
            }
            return d;
        },
        notifier(type, message) {
            const div = document.createElement('div');
            div.textContent = message || '';
            window.dispatchEvent(new CustomEvent('toast', { detail: { type: type, message: div.innerHTML } }));
        },

        /* ---------- Filtres et liste ---------- */
        lireAdresse() {
            const p = new URL(window.location.href).searchParams;
            return { type: p.get('type') || '', etat: p.get('etat') || 'a_traiter', etape: p.get('etape') || '', q: p.get('q') || '', sans_rdv: p.get('sans_rdv') === '1', contact: p.get('contact') === '1' };
        },
        parametres() {
            const p = new URLSearchParams();
            if (this.filtres.type) p.set('type', this.filtres.type);
            if (this.filtres.etat && this.filtres.etat !== 'a_traiter') p.set('etat', this.filtres.etat);
            if ((this.filtres.q || '').trim()) p.set('q', this.filtres.q.trim());
            if (this.filtres.sans_rdv) p.set('sans_rdv', '1');
            if (this.filtres.contact) p.set('contact', '1');
            if (this.filtres.etape) p.set('etape', this.filtres.etape);
            return p;
        },
        filtrer(changements) {
            Object.assign(this.filtres, changements);
            this.recharger(true);
        },
        /* Les etapes comptent les dossiers de l'onglet : changer d'onglet les relit. */
        changerType(type) {
            if (this.filtres.type === type) return;
            this.filtres.type = type;
            this.recharger(true, true);
        },
        /* Un second clic sur l'etape active revient a tous les dossiers ouverts. */
        choisirEtape(etape) {
            const nouvelle = etape && this.filtres.etape !== etape ? etape : '';
            this.filtrer(nouvelle ? { etape: nouvelle } : { etape: '', etat: 'a_traiter' });
        },
        /* Les compteurs ne dependent pas de la recherche : ils se relisent au changement d'onglet et apres une decision. */
        async recharger(historique, avecCompteurs = false) {
            this.chargement = true;
            try {
                const p = this.parametres();
                const adresse = this.cfg.index + (p.toString() ? '?' + p.toString() : '');
                p.set('fragment', '1');
                if (avecCompteurs) p.set('compteurs', '1');
                const d = await this.appeler(this.cfg.index + '?' + p.toString());
                document.getElementById('dmi-liste').innerHTML = d.liste;
                if (d.kpis) {
                    const kpis = document.getElementById('dmi-kpis');
                    kpis.innerHTML = d.kpis;
                    if (window.Alpine) window.Alpine.initTree(kpis);
                    this.compteurs = d.compteurs;
                    this.majBadgeDuMenu();
                }
                this.marquerSelection();
                if (historique) window.history.pushState({}, '', adresse);
            } catch (e) {
                this.notifier('error', e.message);
            } finally {
                this.chargement = false;
            }
        },
        /* Le badge du menu est rendu par la mise en page : on le suit sans recharger. */
        majBadgeDuMenu() {
            const lien = document.querySelector('.menu-sublink[href="' + this.cfg.index + '"]');
            if (!lien) return;
            let badge = lien.querySelector('.menu-badge');
            const n = this.compteurs.a_traiter || 0;
            if (!n) { if (badge) badge.remove(); return; }
            if (!badge) { badge = document.createElement('span'); badge.className = 'menu-badge'; lien.querySelector('.menu-text').appendChild(badge); }
            badge.textContent = n > 999 ? '999+' : String(n);
            badge.title = n + ' à traiter';
        },
        lignes() { return Array.from(document.querySelectorAll('#dmi-liste .dmi-ligne')); },
        marquerSelection() {
            this.lignes().forEach((l) => l.classList.toggle('is-selection', l.dataset.dmiCle === this.ouvert));
        },

        /* ---------- Panneau du dossier ---------- */
        urlDossier(cle) {
            const i = cle.lastIndexOf('-');
            return this.cfg.index + '/' + cle.slice(0, i) + '/' + cle.slice(i + 1);
        },
        async ouvrirDossier(cle, puis) {
            this.ouvert = cle;
            this.chargementDossier = true;
            this.marquerSelection();
            try {
                const d = await this.appeler(this.urlDossier(cle));
                const cible = document.getElementById('dmi-dossier');
                cible.innerHTML = d.html;
                this.dossier = JSON.parse(cible.querySelector('[data-dmi-dossier]')?.dataset.dmiDossier || 'null');
                if (puis) this.agir(puis === 'suite' ? this.etapeSuivante() : puis);
            } catch (e) {
                this.ouvert = null;
                this.notifier('error', e.statut === 404 ? "Ce dossier n'existe plus." : e.message);
            } finally {
                this.chargementDossier = false;
                this.marquerSelection();
            }
        },
        fermerDossier() {
            this.ouvert = null; this.dossier = null;
            this.marquerSelection();
        },
        etapeSuivante() {
            if (!this.dossier || !this.dossier.ouverte || !this.dossier.peut_traiter) return null;
            if (this.dossier.type === 'nouvelle') return this.dossier.preparer ? 'inscrire' : null;
            return this.dossier.obstacle ? null : 'reinscrire';
        },
        async apresDecision(message) {
            this.notifier('success', message);
            this.fermerFenetre();
            const cle = this.ouvert;
            await this.recharger(false, true);
            if (cle) this.ouvrirDossier(cle);
        },

        /* ---------- Clics et clavier ---------- */
        surClic(e) {
            const t = e.target;
            const etat = t.closest('[data-dmi-etat]');
            if (etat) { this.filtrer({ etat: etat.dataset.dmiEtat, etape: '' }); return; }
            const etape = t.closest('[data-dmi-etape]');
            if (etape) { this.choisirEtape(etape.dataset.dmiEtape); return; }
            if (t.closest('[data-dmi-effacer]')) {
                const typeChange = this.filtres.type !== '';
                Object.assign(this.filtres, { q: '', type: '', etape: '', sans_rdv: false, contact: false });
                this.recharger(true, typeChange);
                return;
            }
            if (t.closest('[data-dmi-fermer]')) { this.fermerDossier(); return; }
            const poster = t.closest('[data-dmi-poster]');
            if (poster) { this.poster(poster); return; }
            const agir = t.closest('[data-dmi-agir]');
            const ligne = t.closest('.dmi-ligne');
            if (ligne) {
                e.preventDefault();
                const cle = ligne.dataset.dmiCle;
                const action = agir ? agir.dataset.dmiAgir : null;
                if (cle === this.ouvert && this.dossier) { if (action) this.agir(action); return; }
                this.ouvrirDossier(cle, action);
                return;
            }
            if (agir) this.agir(agir.dataset.dmiAgir);
        },
        surTouche(e) {
            const saisie = e.target.closest('input, textarea, [contenteditable]');
            if (e.key === 'Escape') {
                if (this.picker.ouvert) return;
                if (this.fenetre) { this.fermerFenetre(); return; }
                if (this.ouvert && !saisie) { this.fermerDossier(); return; }
                return;
            }
            if (saisie || this.fenetre || e.ctrlKey || e.metaKey || e.altKey) return;
            if (e.key === '/') { e.preventDefault(); this.$refs.recherche && this.$refs.recherche.focus(); return; }
            if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                const lignes = this.lignes();
                if (!lignes.length) return;
                e.preventDefault();
                const actuelle = lignes.indexOf(document.activeElement.closest?.('.dmi-ligne'));
                const depart = actuelle >= 0 ? actuelle : lignes.findIndex((l) => l.dataset.dmiCle === this.ouvert);
                const suivante = lignes[Math.min(lignes.length - 1, Math.max(0, depart + (e.key === 'ArrowDown' ? 1 : -1)))] || lignes[0];
                // Le nom est le bouton de la ligne : Entree l'ouvre nativement.
                suivante.querySelector('.dmi-nom')?.focus();
                return;
            }
            if ((e.key === 'i' || e.key === 'I') && this.ouvert) {
                const suite = this.etapeSuivante();
                if (suite) { e.preventDefault(); this.agir(suite); }
            }
        },
        async poster(bouton) {
            if (bouton.classList.contains('is-occupe')) return;
            bouton.classList.add('is-occupe');
            try {
                const d = await this.appeler(bouton.dataset.dmiPoster, { methode: 'POST', corps: JSON.parse(bouton.dataset.dmiCorps || '{}') });
                await this.apresDecision(d.message || 'Fait.');
            } catch (e) {
                this.notifier('error', e.message);
            } finally {
                bouton.classList.remove('is-occupe');
            }
        },

        /* ---------- Actions ---------- */
        agir(action) {
            if (!action || !this.dossier) return;
            if (action === 'inscrire') return this.ouvrirInscription();
            if (action === 'accepter') return this.accepter();
            if (action === 'reinscrire') {
                this.reins = { classe_id: this.dossier.classe || null, decision: '', observations: '', erreurs: {}, message: '' };
                this.fenetre = 'reinscrire';
                return;
            }
            if (action === 'rejeter') { this.rejet = { motif: '', erreur: '' }; this.fenetre = 'rejeter'; return; }
            if (action === 'rendez-vous') return this.ouvrirCreneaux();
        },
        fermerFenetre() {
            if (this.occupe) return;
            this.fenetre = ''; this.picker.ouvert = false;
        },
        async accepter() {
            this.occupe = true;
            try {
                const d = await this.appeler(this.dossier.accepter, { methode: 'POST' });
                await this.apresDecision(d.message);
            } catch (e) { this.notifier('error', e.message); } finally { this.occupe = false; }
        },
        async reinscrire() {
            this.occupe = true; this.reins.erreurs = {}; this.reins.message = '';
            try {
                const d = await this.appeler(this.dossier.convertir, { methode: 'POST', corps: { classe_id: this.reins.classe_id, decision: this.reins.decision, observations: this.reins.observations } });
                this.occupe = false;
                await this.apresDecision(d.message);
            } catch (e) {
                this.reins.erreurs = this.premieresErreurs(e.donnees?.errors);
                this.reins.message = e.message;
            } finally { this.occupe = false; }
        },
        async rejeter() {
            this.occupe = true; this.rejet.erreur = '';
            try {
                const d = await this.appeler(this.dossier.rejeter, { methode: 'POST', corps: { motif_rejet: this.rejet.motif.trim() } });
                this.occupe = false;
                await this.apresDecision(d.message);
            } catch (e) {
                this.rejet.erreur = this.premieresErreurs(e.donnees?.errors).motif_rejet || e.message;
            } finally { this.occupe = false; }
        },
        async ouvrirCreneaux() {
            if (!this.dossier.rendez_vous || !this.cfg.creneaux) return;
            this.rdv = { creneaux: [], chargement: true, choix: null };
            this.fenetre = 'rendez-vous';
            try {
                this.rdv.creneaux = (await this.appeler(this.cfg.creneaux)).creneaux || [];
            } catch (e) { this.notifier('error', e.message); } finally { this.rdv.chargement = false; }
        },
        async fixerRendezVous() {
            this.occupe = true;
            try {
                const d = await this.appeler(this.dossier.rendez_vous, { methode: 'POST', corps: { creneau_id: this.rdv.choix } });
                this.occupe = false;
                await this.apresDecision(d.message);
            } catch (e) { this.notifier('error', e.message); } finally { this.occupe = false; }
        },

        /* ---------- Accepter et inscrire ---------- */
        async ouvrirInscription() {
            if (!this.dossier.preparer) return;
            this.ins = vierge();
            this.ins.chargement = true;
            this.fenetre = 'inscrire';
            try {
                const p = await this.appeler(this.dossier.preparer);
                const voeux = p.classes.filter((c) => c.voeu && !c.complete);
                this.ins.prep = p;
                this.ins.f = Object.assign({}, p.identite, {
                    classe_id: voeux.length === 1 ? voeux[0].id : null,
                    matricule: '', statut_etablissement: p.statut_etablissement_requis ? 'nouveau' : '',
                    duplicate_override: false, candidature_naissance_confirmee: false,
                });
                this.ins.tuteur = Object.assign(this.ins.tuteur, p.tuteur);
                this.ins.sansTuteur = !p.tuteur.nom && !p.tuteur.telephone;
                if (this.ins.f.classe_id) this.chargerFrais();
            } catch (e) {
                this.fenetre = '';
                this.notifier('error', e.message);
            } finally { this.ins.chargement = false; }
        },
        async chargerFrais() {
            const id = this.ins.f.classe_id;
            if (!id || !this.ins.prep) return;
            this.ins.frais = { chargement: true, masques: false, lignes: [], total: 0, incomplet: false };
            const p = new URLSearchParams({ affectation_status: this.ins.prep.candidature.affectation_status || '' });
            if (this.ins.f.statut_etablissement) p.set('statut_etablissement', this.ins.f.statut_etablissement);
            try {
                const d = await this.appeler(this.cfg.frais.replace('__ID__', id) + '?' + p.toString());
                if (id !== this.ins.f.classe_id) return;
                const lignes = (d.frais || []).filter((f) => f.is_mandatory).map((f) => ({ libelle: f.category?.name || 'Frais', montant: Number(f.configured_amount ?? f.default_amount ?? 0) }));
                this.ins.frais = { chargement: false, masques: !!d.hide_amounts, lignes: lignes, total: lignes.reduce((s, l) => s + l.montant, 0), incomplet: !!d.has_unconfigured_fees };
            } catch (e) {
                this.ins.frais = { chargement: false, masques: false, lignes: [], total: 0, incomplet: true };
            }
        },
        /*
         * Proposition, jamais appliquee d'office : couper au premier espace se
         * trompe sur les noms composes. L'agent lit la proposition et la valide.
         */
        coupeTuteur() {
            const t = this.ins.tuteur;
            if ((t.prenoms || '').trim() || !(t.nom || '').trim().includes(' ')) return null;
            const mots = t.nom.trim().split(/\s+/);
            return [mots[0], mots.slice(1).join(' ')];
        },
        appliquerCoupeTuteur() {
            const c = this.coupeTuteur();
            if (c) { this.ins.tuteur.nom = c[0]; this.ins.tuteur.prenoms = c[1]; }
        },
        naissanceModifiee() { return !!this.ins.prep && (this.ins.f.date_naissance || '') !== (this.ins.prep.identite.date_naissance || ''); },
        doublonsBloquants() { return (this.ins.prep?.doublons || []).filter((d) => d.bloquant); },
        tuteurPartiel() {
            if (this.ins.sansTuteur) return false;
            const t = this.ins.tuteur;
            const remplis = [t.nom, t.prenoms, t.telephone].filter((v) => (v || '').trim() !== '').length;
            return remplis > 0 && (remplis < 3 || !t.relation);
        },
        erreurTuteur() {
            const serveur = Object.keys(this.ins.erreurs).find((k) => k.startsWith('parents.'));
            if (serveur) return this.ins.erreurs[serveur];
            return this.tuteurPartiel() ? 'Complétez nom, prénoms, téléphone et lien, ou cochez « Ne pas enregistrer de tuteur maintenant ».' : '';
        },
        insIdentiteOk() {
            const f = this.ins.f;
            return !!(f.nom && f.prenoms && f.sexe && f.date_naissance && f.telephone)
                && (!this.naissanceModifiee() || f.candidature_naissance_confirmee)
                && (!this.doublonsBloquants().length || f.duplicate_override);
        },
        insAffectationOk() {
            const p = this.ins.prep, f = this.ins.f;
            return !!(p && f.classe_id && (p.matricule_automatique || (f.matricule || '').trim()) && (!p.statut_etablissement_requis || f.statut_etablissement));
        },
        insPret() { return this.insIdentiteOk() && this.insAffectationOk() && !this.tuteurPartiel(); },
        raisonNonPret() {
            const f = this.ins.f;
            if (!(f.nom && f.prenoms && f.sexe && f.date_naissance && f.telephone)) return "Complétez l'identité : nom, prénoms, sexe, date de naissance et téléphone.";
            if (this.naissanceModifiee() && !f.candidature_naissance_confirmee) return 'Confirmez la date de naissance vérifiée sur la pièce.';
            if (this.doublonsBloquants().length && !f.duplicate_override) return 'Tranchez les doublons : ouvrez la fiche proche, ou confirmez que c\'est une autre personne.';
            if (!f.classe_id) return 'Choisissez la classe.';
            if (!this.insAffectationOk()) return this.ins.prep?.statut_etablissement_requis && !f.statut_etablissement ? "Indiquez s'il est déjà inscrit dans l'établissement." : 'Saisissez le matricule.';
            return this.tuteurPartiel() ? 'Complétez le tuteur ou cochez « Ne pas enregistrer de tuteur ».' : '';
        },
        formulaire() {
            const fd = new FormData();
            const f = this.ins.f, c = this.ins.prep.candidature;
            ['nom', 'prenoms', 'sexe', 'date_naissance', 'lieu_naissance', 'telephone', 'email_personnel', 'ville', 'commune', 'classe_id', 'statut_etablissement'].forEach((k) => { if (f[k]) fd.append(k, f[k]); });
            if (!this.ins.prep.matricule_automatique && f.matricule) fd.append('matricule', f.matricule.trim());
            fd.append('candidature_id', c.id);
            if (c.annee_universitaire_id) fd.append('annee_universitaire_id', c.annee_universitaire_id);
            fd.append('affectation_status', c.affectation_status || '');
            fd.append('duplicate_override', f.duplicate_override ? '1' : '0');
            if (f.candidature_naissance_confirmee) fd.append('candidature_naissance_confirmee', '1');
            const t = this.ins.tuteur;
            if (!this.ins.sansTuteur && t.nom && t.prenoms && t.telephone) {
                fd.append('parents[0][type]', 'nouveau');
                ['nom', 'prenoms', 'telephone', 'relation', 'profession'].forEach((k) => { if (t[k]) fd.append('parents[0][' + k + ']', t[k]); });
            }
            return fd;
        },
        async inscrire() {
            if (!this.insPret() || this.occupe) return;
            this.occupe = true; this.ins.erreurs = {}; this.ins.message = '';
            // Une seule requete : le serveur accepte et inscrit dans la meme
            // transaction. Un refus laisse la candidature telle qu'elle etait.
            try {
                const d = await this.appeler(this.cfg.store, { methode: 'POST', corps: this.formulaire() });
                this.notifier('success', d.message || 'Inscription enregistrée.');
                // EXCEPTION ajax-no-reload-premium : l'inscription est creee, sa fiche
                // prend la suite (identifiants du compte, photo, premier paiement).
                window.location.href = d.redirect;
            } catch (e) {
                this.ins.erreurs = this.premieresErreurs(e.donnees?.errors);
                this.ins.message = e.message;
                if ((e.donnees?.doublons || []).length && !this.doublonsBloquants().length) {
                    this.ins.prep.doublons = e.donnees.doublons.map((d) => Object.assign({ bloquant: true, fiche: d.show_url || (this.cfg.etudiant ? this.cfg.etudiant.replace('__ID__', d.id) : '#') }, d));
                }
                this.occupe = false;
            }
        },
        formulaireComplet() {
            if (this.dossier.formulaire) window.location.href = this.dossier.formulaire;
        },

        /* ---------- Petits outils d'affichage ---------- */
        premieresErreurs(erreurs) {
            const r = {};
            Object.entries(erreurs || {}).forEach(([k, v]) => { r[k] = Array.isArray(v) ? v[0] : v; });
            return r;
        },
        initiales(nom) {
            const m = (nom || '').trim().split(/\s+/);
            return ((m[0] || '').charAt(0) + (m[1] || '').charAt(0)).toUpperCase();
        },
        dateFr(iso) { return iso ? iso.split('-').reverse().join('/') : '—'; },
        fcfa(n) { return new Intl.NumberFormat('fr-FR').format(Math.round(n || 0)).replace(/ /g, ' ') + ' FCFA'; },
        classeChoisie(liste, id) { return (liste || []).find((c) => c.id === id) || null; },
        filtrerClasses(liste) {
            const q = (this.picker.q || '').toLocaleLowerCase('fr').normalize('NFD').replace(/[̀-ͯ]/g, '').trim();
            if (!q) return liste || [];
            return (liste || []).filter((c) => (c.nom + ' ' + (c.detail || '')).toLocaleLowerCase('fr').normalize('NFD').replace(/[̀-ͯ]/g, '').includes(q));
        },
        jaugePct(c) { return c && c.places_totales > 0 ? Math.min(100, Math.round(c.places_prises / c.places_totales * 100)) : 0; },
        jaugeTon(c) {
            if (!c || c.complete === undefined) return '';
            if (c.complete) return 'dmi-jauge--pleine';
            return this.jaugePct(c) >= 85 ? 'dmi-jauge--presque' : '';
        },
        placesTexte(c) {
            if (!c) return '';
            if (c.places_totales === undefined) return '';
            // Sans capacite reglee, l'enregistrement refuse la classe comme pleine.
            if (!c.places_totales) return 'capacité non réglée';
            return c.complete ? 'complète' : c.places_prises + ' / ' + c.places_totales;
        },
    };
};
}
</script>

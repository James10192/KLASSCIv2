{{--
    La fabrique Alpine du bandeau de couverture.

    Deux façons de lui dire de quoi parler, parce que les pages hôtes sont de
    deux sortes :

    - la page CONNAÎT sa classe (résultats d'une classe, fiche étudiant) :
      le contexte est passé à la construction ;
    - la page LA FAIT CHOISIR (sélection de bulletin, liste d'évaluations
      filtrée) : elle émet `couverture:contexte` à chaque changement, et le
      bandeau suit.

    Fichier séparé du gabarit, et volontairement SANS `@once` ni `@push` :
    plusieurs pages hôtes sont chargées en AJAX, où un `@push('scripts')` serait
    avalé en silence. Le garde d'idempotence rend la double inclusion inoffensive.

    @see .claude/rules/premium-selects.md — pattern AJAX-safe
--}}
<script>
if (typeof window.couvertureNotes !== 'function') {
    window.couvertureNotes = function (config) {
        return {
            modele: config.modele,
            urlPilotage: config.urlPilotage || null,
            replie: config.replie !== false,

            classeId: config.classeId || null,
            anneeId: config.anneeId || null,
            periode: config.periode || 'semestre1',

            chargement: false,
            erreur: '',
            interdit: false,
            donnees: null,
            _requete: 0,

            init() {
                this._surContexte = (ev) => this.appliquerContexte(ev.detail || {});
                window.addEventListener('couverture:contexte', this._surContexte);

                // Une note enregistrée ailleurs sur la page rend ce chiffre
                // faux : le bandeau se remet à jour sans rechargement.
                //
                // EN FORÇANT LE RECALCUL. Le serveur garde la couverture dix
                // minutes ; redemander sans le dire rendait la même réponse
                // périmée, et le bandeau continuait d'annoncer les notes qu'on
                // venait justement de saisir. C'est le seul appel qui force :
                // un simple changement de classe se contente du cache.
                this._surInvalidation = () => this.charger(true);
                window.addEventListener('couverture:invalider', this._surInvalidation);

                if (this.pret()) { this.charger(); }
            },

            destroy() {
                window.removeEventListener('couverture:contexte', this._surContexte);
                window.removeEventListener('couverture:invalider', this._surInvalidation);
                this._surContexte = null;
                this._surInvalidation = null;
            },

            pret() {
                return !!(this.classeId && this.anneeId);
            },

            appliquerContexte(detail) {
                var classe = detail.classe_id ? Number(detail.classe_id) : null;
                var annee = detail.annee_universitaire_id ? Number(detail.annee_universitaire_id) : this.anneeId;
                var periode = detail.periode || this.periode;

                if (classe === this.classeId && annee === this.anneeId && periode === this.periode) {
                    return;
                }

                this.classeId = classe;
                this.anneeId = annee;
                this.periode = periode;
                this.donnees = null;
                this.erreur = '';

                if (this.pret()) { this.charger(); }
            },

            url(forcer) {
                return this.modele.replace('__CLASSE__', String(this.classeId))
                    + '?annee_universitaire_id=' + encodeURIComponent(this.anneeId)
                    + '&periode=' + encodeURIComponent(this.periode)
                    + (forcer ? '&recalculer=1' : '');
            },

            async charger(forcer) {
                if (!this.pret()) { return; }

                // Un choix rapide dans un sélecteur lance plusieurs requêtes :
                // seule la dernière demandée a le droit d'écrire le résultat,
                // sinon une réponse lente écraserait la bonne.
                var jeton = ++this._requete;

                this.chargement = true;
                this.erreur = '';
                this.interdit = false;
                try {
                    var res = await fetch(this.url(forcer), { headers: { 'Accept': 'application/json' } });
                    if (jeton !== this._requete) { return; }
                    if (res.status === 403) { this.interdit = true; return; }
                    if (!res.ok) { throw new Error('Suivi des notes indisponible (' + res.status + ').'); }
                    this.donnees = await res.json();
                } catch (err) {
                    if (jeton === this._requete) { this.erreur = err.message; }
                } finally {
                    if (jeton === this._requete) { this.chargement = false; }
                }
            },

            /* ---- Lecture du contenu ---------------------------------- */

            etat() {
                if (!this.donnees) { return null; }
                if (this.donnees.ok === false) { return 'indisponible'; }
                return (this.donnees.summary && this.donnees.summary.state) || 'indisponible';
            },

            resume() {
                return (this.donnees && this.donnees.summary) || {};
            },

            /* L'état en une phrase, celle que l'écran doit annoncer. */
            phrase() {
                var s = this.resume();
                switch (this.etat()) {
                    case 'referentiel_absent':
                        return "Aucune matière n'est rattachée à cette classe : le suivi ne peut rien annoncer.";
                    case 'aucune_matiere_ce_semestre':
                        return "La maquette ne prévoit aucune matière à cette période.";
                    case 'cohorte_vide':
                        return "Aucun étudiant sur cette période : rien à saisir.";
                    case 'aucune_evaluation':
                        return "Aucune évaluation n'a encore été créée : la saisie n'a pas commencé.";
                    case 'incomplete':
                        return s.missing_results + ' note(s) manquante(s) sur ' + s.expected_results + ' attendue(s).';
                    case 'complete':
                        return "Toutes les notes attendues sont reçues.";
                    default:
                        return (this.donnees && this.donnees.message) || "Suivi des notes indisponible.";
                }
            },

            ton() {
                switch (this.etat()) {
                    case 'complete': return 'cvn--ok';
                    case 'incomplete': return 'cvn--alerte';
                    case 'aucune_evaluation': return 'cvn--alerte';
                    default: return 'cvn--neutre';
                }
            },

            icone() {
                switch (this.etat()) {
                    case 'complete': return 'fa-circle-check';
                    case 'incomplete': return 'fa-triangle-exclamation';
                    case 'aucune_evaluation': return 'fa-hourglass-start';
                    default: return 'fa-circle-info';
                }
            },

            /*
             * Le pourcentage n'a de sens que si quelque chose est attendu.
             * Rendre 100 % sur zéro attendu ferait dire à l'écran « tout est
             * reçu » sur une classe vide — exactement ce que le lot précédent a
             * corrigé côté calcul.
             */
            pourcentage() {
                var s = this.resume();
                if (!s.expected_results) { return null; }
                return Math.round((s.treated_results / s.expected_results) * 100);
            },

            aUneBarre() { return this.pourcentage() !== null; },

            /* Les matières à relancer, les plus en retard d'abord. */
            prioritaires() {
                if (!this.donnees || !this.donnees.subjects) { return []; }
                return this.donnees.subjects
                    .filter(function (m) { return m.missing_count > 0 || m.statut === 'non_evaluee'; })
                    .sort(function (a, b) { return (b.missing_count || 0) - (a.missing_count || 0); });
            },

            libelleStatut(matiere) {
                switch (matiere.statut) {
                    case 'non_evaluee': return 'Aucune évaluation';
                    case 'partielle': return matiere.missing_count + ' manquante(s)';
                    case 'hors_maquette': return 'Hors référentiel';
                    default: return 'Complète';
                }
            },

            contact(matiere) {
                if (!matiere.enseignant || !matiere.enseignant.name) { return null; }
                return matiere.enseignant;
            },

            basculer() { this.replie = !this.replie; },
        };
    };
}
</script>

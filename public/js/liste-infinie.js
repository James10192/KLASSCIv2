/*
 * Defilement infini des listes KLASSCI.
 *
 * Chaque bas de liste [data-liste-infinie] (composant x-liste-infinie) charge
 * la tranche suivante quand il approche de l'ecran, ou au clic sur « Charger
 * la suite » — le bouton reste la pour le clavier, et apres une erreur.
 *
 * Cote serveur : App\Support\ListeInfinie. La reponse porte `rows_html`
 * (les lignes deja rendues) et `pagination` (next_page, total, affiches).
 *
 * Apres chaque ajout, l'evenement `liste-infinie:ajout` part du conteneur
 * cible (il remonte) : une page qui attache des gestionnaires ligne par
 * ligne s'y rebranche. Les pages qui deleguent leurs clics n'ont rien a faire.
 *
 * Une ligne qui porte data-li-cle (son identifiant) n'est jamais ajoutee deux
 * fois.
 *
 * Une liste remplacee par un filtrage AJAX amene un nouveau bas de liste :
 * l'observateur de mutations le branche seul. Une reponse arrivee pour un
 * bas de liste qui n'est plus dans la page est jetee.
 */
(function () {
    'use strict';

    if (window.ListeInfinie) return;

    var MARGE = '600px 0px';
    var etats = new WeakMap();

    function injecterStyles() {
        if (document.getElementById('li-styles')) return;
        var style = document.createElement('style');
        style.id = 'li-styles';
        style.textContent =
            '.li-bas{display:flex;align-items:center;justify-content:center;gap:.75rem;flex-wrap:wrap;padding:1rem .75rem;color:#64748b;font-size:.82rem}' +
            '.li-bas[data-etat="fin"] .li-compteur{color:#94a3b8}' +
            '.li-plus{border:1px solid #c9daf6;background:#fff;color:#0453cb;border-radius:9px;padding:.5rem 1rem;font-size:.82rem;font-weight:600;cursor:pointer;min-height:40px}' +
            '.li-plus:hover{background:#f1f6ff}' +
            '.li-plus:disabled{opacity:.6;cursor:wait}' +
            '.li-bas[data-etat="chargement"] .li-compteur::before{content:"";display:inline-block;width:.8rem;height:.8rem;margin-right:.45rem;vertical-align:-1px;border:2px solid #c9daf6;border-top-color:#0453cb;border-radius:50%;animation:li-tourne .7s linear infinite}' +
            '@keyframes li-tourne{to{transform:rotate(360deg)}}';
        document.head.appendChild(style);
    }

    function cssEchapper(v) {
        return window.CSS && CSS.escape ? CSS.escape(v) : String(v).replace(/["\\]/g, '\\$&');
    }

    function nombre(n) {
        return Number(n).toLocaleString('fr-FR');
    }

    // Le libelle est donne au pluriel ; « 1 inscription », pas « 1 inscriptions ».
    function accorder(libelle, n) {
        return Number(n) <= 1 && /s$/.test(libelle) ? libelle.slice(0, -1) : libelle;
    }

    // Le texte du bas de liste, le meme pour les listes rendues par le serveur
    // et pour celles qu'Alpine dessine (ListeInfinie.alpine).
    function texteCompteur(etat, affiches, total, libelle) {
        var n = total !== null ? total : affiches;
        if (etat === 'erreur') return 'La suite n’a pas pu être chargée.';
        if (etat === 'chargement') return 'Chargement…';
        if (etat === 'fin') return affiches === 0 ? '' : 'Fin de la liste · ' + nombre(n) + ' ' + accorder(libelle, n);
        return nombre(affiches) + (total !== null ? ' sur ' + nombre(total) : '') + ' ' + accorder(libelle, n);
    }

    function afficherEtat(bas, etat) {
        var d = bas.dataset;
        var bouton = bas.querySelector('[data-li-plus]');

        bas.dataset.etat = etat;
        bouton.disabled = etat === 'chargement';
        bas.querySelector('.li-compteur').textContent = texteCompteur(
            etat, Number(d.affiches || 0), d.total === '' ? null : Number(d.total), d.libelle || 'éléments'
        );
        bouton.textContent = etat === 'erreur' ? 'Réessayer' : 'Charger la suite';
        bouton.hidden = etat === 'fin';
    }

    /*
     * La page a demander pour continuer. Le serveur pagine par decalage : si
     * des lignes ont quitte la liste sans rechargement (restauration,
     * suppression, action groupee), toute la suite a recule d'autant. On part
     * donc de ce qui est reellement affiche, pas d'un numero de page retenu :
     * la tranche qui contient la premiere ligne manquante. Elle recouvre en
     * partie l'affichage ; le dedoublonnage par cle ecarte ce recouvrement.
     * Une seule regle, quel que soit le nombre de lignes retirees.
     */
    function pageAPrendre(affiches, parPage, repli) {
        return parPage > 0 ? Math.floor(affiches / parPage) + 1 : repli;
    }

    function adresse(bas) {
        var d = bas.dataset;
        var params = new URLSearchParams(d.query || '');
        params.set('page', String(pageAPrendre(Number(d.affiches || 0), Number(d.parPage || 0), d.pageSuivante)));
        params.set('mode', 'rows');
        return d.url + (d.url.indexOf('?') === -1 ? '?' : '&') + params.toString();
    }

    function charger(bas) {
        var etat = etats.get(bas);
        if (!etat || etat.enCours || !bas.dataset.pageSuivante) return;

        etat.enCours = true;
        var retraitsAuDepart = etat.retraits;
        afficherEtat(bas, 'chargement');

        fetch(adresse(bas), {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            credentials: 'same-origin'
        })
            .then(function (r) {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.json();
            })
            .then(function (data) {
                // La liste a ete remplacee (nouveau filtre) pendant le chargement.
                if (!bas.isConnected) return;
                // Des lignes ont quitte la liste pendant le trajet : la tranche a
                // ete choisie avant, le serveur l'a peut-etre servie apres, et des
                // lignes manqueraient avant elle. On la jette et on redemande.
                if (etat.retraits !== retraitsAuDepart) { etat.aRejouer = true; return; }

                var cible = document.querySelector(bas.dataset.cible);
                var p = data.pagination || {};

                if (cible && data.rows_html) {
                    var nouvelles = ajouterLignes(cible, data.rows_html);
                    // Pas d'Alpine.initTree ici : Alpine observe le DOM et initialise
                    // lui-meme les lignes ajoutees dans un composant. L'appeler en plus
                    // attacherait chaque @click deux fois.
                    cible.dispatchEvent(new CustomEvent('liste-infinie:ajout', {
                        bubbles: true,
                        detail: { lignes: nouvelles, pagination: p }
                    }));
                }

                bas.dataset.pageSuivante = p.has_more && p.next_page ? String(p.next_page) : '';
                if (p.total !== undefined && p.total !== null) bas.dataset.total = String(p.total);
                if (p.affiches !== undefined) bas.dataset.affiches = String(p.affiches);

                if (!bas.dataset.pageSuivante) {
                    afficherEtat(bas, 'fin');
                    if (etat.observateur) etat.observateur.disconnect();
                } else {
                    afficherEtat(bas, 'pret');
                }
            })
            .catch(function () {
                if (bas.isConnected) afficherEtat(bas, 'erreur');
            })
            .finally(function () {
                etat.enCours = false;
                if (etat.aRejouer) { etat.aRejouer = false; afficherEtat(bas, 'pret'); charger(bas); return; }
                // Une tranche courte peut laisser le bas de liste visible : l'observateur
                // ne se redeclenche pas tout seul, on relance tant qu'il est a l'ecran.
                if (bas.isConnected && bas.dataset.pageSuivante && bas.dataset.etat === 'pret' && estVisible(bas)) {
                    charger(bas);
                }
            });
    }

    // Ajoute des lignes en bas de `cible`, sans repeter une ligne deja affichee
    // (data-li-cle) : seconde ceinture du tri stable, pour une ligne creee en
    // tete pendant qu'on defile. Rend les lignes reellement ajoutees. Sert aussi
    // aux listes qui gardent leur propre defilement (evaluations, matieres).
    function ajouterLignes(cible, html) {
        var avant = cible.lastElementChild;
        cible.insertAdjacentHTML('beforeend', html);
        var nouvelles = [];
        var n = avant ? avant.nextElementSibling : cible.firstElementChild;
        while (n) {
            var suivante = n.nextElementSibling;
            var cle = n.getAttribute('data-li-cle');
            if (cle && cible.querySelectorAll('[data-li-cle="' + cssEchapper(cle) + '"]').length > 1) {
                n.remove();
            } else {
                nouvelles.push(n);
            }
            n = suivante;
        }
        return nouvelles;
    }

    function estVisible(el) {
        var r = el.getBoundingClientRect();
        // Un bas de liste masque (tableau sur telephone, grille sur ordinateur)
        // n'a pas de boite : il ne charge rien.
        if (r.width === 0 && r.height === 0) return false;
        return r.top < (window.innerHeight || document.documentElement.clientHeight) + 600 && r.bottom > -600;
    }

    function brancher(bas) {
        if (etats.has(bas)) return;
        var etat = { enCours: false, observateur: null, retraits: 0, aRejouer: false };
        etats.set(bas, etat);

        bas.querySelector('[data-li-plus]').addEventListener('click', function () { charger(bas); });

        if (!bas.dataset.pageSuivante) {
            afficherEtat(bas, 'fin');
            return;
        }
        afficherEtat(bas, 'pret');

        if ('IntersectionObserver' in window) {
            etat.observateur = new IntersectionObserver(function (entrees) {
                if (entrees.some(function (e) { return e.isIntersecting; })) charger(bas);
            }, { rootMargin: MARGE });
            etat.observateur.observe(bas);
        }
    }

    function init(racine) {
        injecterStyles();
        (racine || document).querySelectorAll('[data-liste-infinie]').forEach(brancher);
    }

    /*
     * Pour une liste qu'Alpine dessine a partir de JSON (journal d'audit,
     * corbeille) : le meme contrat, sans HTML de ligne cote serveur.
     *
     *   return Object.assign({ ...le composant, getters compris... }, ListeInfinie.alpine({
     *       champ: 'items',                       // le tableau que la vue parcourt
     *       libelle: 'entrées',                   // ou function () { return ... }
     *       tranche: (page) => fetch(...).then(r => ({ lignes, pagination })),
     *   }));
     *
     * Dans ce sens-la : Object.assign evalue les getters de ce qu'il copie ; le
     * melange n'en a pas, un composant peut en avoir.
     *
     * `pagination` suit App\Support\ListeInfinie::pagination() (has_more,
     * total, current_page). Le composant appelle liInit() dans son init() et
     * liDetruire() dans son destroy() ; recharger() repart de la premiere
     * tranche, retirer(cle) enleve une ligne sans rien recharger. Le bas de
     * liste est le composant Blade x-liste-infinie-alpine.
     */
    function alpine(options) {
        var champ = options.champ || 'items';
        var cle = options.cle || function (x) { return x.id; };
        var etat = {
            loading: true,
            liPage: 1,
            liSuite: false,
            liErreur: false,
            liAPlus: false,
            liTotal: null,
            liNumero: 0,
            liParPage: 0,
            // Position atteinte dans la liste du serveur (son `affiches`), moins
            // les lignes retirees sur place. Pas le nombre de lignes distinctes
            // a l'ecran : une ligne ecrite en tete pendant le defilement ferait
            // redemander la meme tranche indefiniment.
            liAffiches: 0,
            liRetraits: 0,

            liInit: function () {
                injecterStyles();
                var self = this;
                if ('IntersectionObserver' in window && this.$refs.basDeListe) {
                    this._liObservateur = new IntersectionObserver(function (entrees) {
                        if (entrees.some(function (e) { return e.isIntersecting; })) self.chargerSuite();
                    }, { rootMargin: MARGE });
                    this._liObservateur.observe(this.$refs.basDeListe);
                }
                return this.recharger();
            },
            liDetruire: function () {
                if (this._liObservateur) this._liObservateur.disconnect();
            },
            recharger: function () { return this._liCharger(false); },
            chargerSuite: function () {
                if (this.loading || this.liSuite || !this.liAPlus) return;
                return this._liCharger(true);
            },
            retirer: function (valeur) {
                var avant = this[champ].length;
                this[champ] = this[champ].filter(function (x) { return cle(x) !== valeur; });
                if (this[champ].length < avant) {
                    this.liRetraits++;
                    this.liAffiches = Math.max(0, this.liAffiches - 1);
                    if (this.liTotal !== null) this.liTotal--;
                }
            },
            liVide: function () { return this[champ].length === 0; },
            liEtat: function () {
                return this.liErreur ? 'erreur' : (this.liSuite ? 'chargement' : (this.liAPlus ? 'pret' : 'fin'));
            },
            compteurBas: function () {
                var lib = typeof options.libelle === 'function' ? options.libelle.call(this) : (options.libelle || 'éléments');
                return texteCompteur(this.liEtat(), this[champ].length, this.liTotal, lib);
            },
            _liCharger: function (ajouter) {
                var self = this;
                var numero = ++this.liNumero;
                if (ajouter) { this.liSuite = true; } else { this.loading = true; this.liSuite = false; }
                this.liErreur = false;
                // Depuis ce qui est affiche (pageAPrendre) : des lignes retirees
                // sur place ne font rien sauter.
                var page = ajouter ? pageAPrendre(this.liAffiches, this.liParPage, this.liPage + 1) : 1;
                var retraitsAuDepart = this.liRetraits;
                var rejouer = false;
                return Promise.resolve(options.tranche.call(this, page))
                    .then(function (res) {
                        // Un filtre ou un onglet change entre-temps : reponse perimee.
                        if (numero !== self.liNumero) return;
                        // Des lignes retirees pendant le trajet : la tranche, choisie
                        // avant, laisserait un trou devant elle. On redemande.
                        if (ajouter && self.liRetraits !== retraitsAuDepart) { rejouer = true; return; }
                        var lignes = res.lignes || [];
                        var p = res.pagination || {};
                        if (ajouter) {
                            // Une ligne ecrite pendant qu'on defile decale la suite :
                            // on n'affiche pas deux fois la meme.
                            var vus = new Set(self[champ].map(cle));
                            self[champ] = self[champ].concat(lignes.filter(function (x) { return !vus.has(cle(x)); }));
                        } else {
                            self[champ] = lignes;
                        }
                        self.liPage = p.current_page || page;
                        self.liParPage = Number(p.par_page || 0);
                        self.liAffiches = p.affiches !== undefined
                            ? Number(p.affiches)
                            : (self.liPage - 1) * self.liParPage + lignes.length;
                        self.liAPlus = !!p.has_more;
                        self.liTotal = p.total === undefined ? null : p.total;
                        self.$nextTick(function () {
                            // Une tranche courte laisse le bas a l'ecran : l'observateur
                            // ne se redeclenche pas, on relance.
                            var bas = self.$refs.basDeListe;
                            if (self.liAPlus && bas && bas.getBoundingClientRect().top < window.innerHeight + 600) self.chargerSuite();
                        });
                    })
                    .catch(function (e) {
                        if (numero !== self.liNumero) return;
                        if (ajouter) { self.liErreur = true; }
                        else if (typeof options.echec === 'function') { options.echec.call(self, e); }
                    })
                    .finally(function () {
                        if (numero !== self.liNumero) return;
                        self.loading = false;
                        self.liSuite = false;
                        if (rejouer) self.chargerSuite();
                    });
            }
        };
        etat[champ] = [];
        return etat;
    }

    // Des lignes ont quitte la liste sans rechargement (suppression, action
    // groupee) : le compteur du bas en tient compte.
    function ajuster(bas, retirees) {
        if (!bas || !retirees) return;
        var e = etats.get(bas);
        if (e) e.retraits++;
        var d = bas.dataset;
        d.affiches = String(Math.max(0, Number(d.affiches || 0) - retirees));
        if (d.total !== '') d.total = String(Math.max(0, Number(d.total) - retirees));
        // La page suivante se deduit de `affiches` (pageAPrendre) : rien a reculer ici.
        afficherEtat(bas, d.pageSuivante ? 'pret' : 'fin');
    }

    window.ListeInfinie = { init: init, charger: charger, alpine: alpine, ajuster: ajuster, pageAPrendre: pageAPrendre, ajouterLignes: ajouterLignes };

    function demarrer() {
        init(document);
        new MutationObserver(function (mutations) {
            mutations.forEach(function (m) {
                m.addedNodes.forEach(function (node) {
                    if (node.nodeType !== 1) return;
                    if (node.matches && node.matches('[data-liste-infinie]')) brancher(node);
                    else if (node.querySelector && node.querySelector('[data-liste-infinie]')) init(node);
                });
            });
        }).observe(document.body, { childList: true, subtree: true });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', demarrer);
    } else {
        demarrer();
    }
})();

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

    function nombre(n) {
        return Number(n).toLocaleString('fr-FR');
    }

    // Le libelle est donne au pluriel ; « 1 inscription », pas « 1 inscriptions ».
    function accorder(libelle, n) {
        return Number(n) <= 1 && /s$/.test(libelle) ? libelle.slice(0, -1) : libelle;
    }

    function afficherEtat(bas, etat) {
        var d = bas.dataset;
        var affiches = Number(d.affiches || 0);
        var total = d.total === '' ? null : Number(d.total);
        var libelle = d.libelle || 'éléments';
        var compteur = bas.querySelector('.li-compteur');
        var bouton = bas.querySelector('[data-li-plus]');

        bas.dataset.etat = etat;
        bouton.disabled = etat === 'chargement';

        if (etat === 'erreur') {
            compteur.textContent = 'La suite n’a pas pu être chargée.';
            bouton.textContent = 'Réessayer';
            bouton.hidden = false;
            return;
        }

        if (etat === 'fin') {
            compteur.textContent = affiches === 0
                ? ''
                : 'Fin de la liste · ' + nombre(total !== null ? total : affiches) + ' ' + accorder(libelle, total !== null ? total : affiches);
            bouton.hidden = true;
            return;
        }

        compteur.textContent = etat === 'chargement'
            ? 'Chargement…'
            : nombre(affiches) + (total !== null ? ' sur ' + nombre(total) : '') + ' ' + accorder(libelle, total !== null ? total : affiches);
        bouton.textContent = 'Charger la suite';
        bouton.hidden = false;
    }

    function adresse(bas) {
        var d = bas.dataset;
        var params = new URLSearchParams(d.query || '');
        params.set('page', d.pageSuivante);
        params.set('mode', 'rows');
        return d.url + (d.url.indexOf('?') === -1 ? '?' : '&') + params.toString();
    }

    function charger(bas) {
        var etat = etats.get(bas);
        if (!etat || etat.enCours || !bas.dataset.pageSuivante) return;

        etat.enCours = true;
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

                var cible = document.querySelector(bas.dataset.cible);
                var p = data.pagination || {};

                if (cible && data.rows_html) {
                    var avant = cible.lastElementChild;
                    cible.insertAdjacentHTML('beforeend', data.rows_html);
                    var nouvelles = [];
                    var n = avant ? avant.nextElementSibling : cible.firstElementChild;
                    while (n) { nouvelles.push(n); n = n.nextElementSibling; }
                    if (window.Alpine && typeof window.Alpine.initTree === 'function') {
                        nouvelles.forEach(function (el) { window.Alpine.initTree(el); });
                    }
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
                // Une tranche courte peut laisser le bas de liste visible : l'observateur
                // ne se redeclenche pas tout seul, on relance tant qu'il est a l'ecran.
                if (bas.isConnected && bas.dataset.pageSuivante && bas.dataset.etat === 'pret' && estVisible(bas)) {
                    charger(bas);
                }
            });
    }

    function estVisible(el) {
        var r = el.getBoundingClientRect();
        return r.top < (window.innerHeight || document.documentElement.clientHeight) + 600 && r.bottom > -600;
    }

    function brancher(bas) {
        if (etats.has(bas)) return;
        var etat = { enCours: false, observateur: null };
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

    window.ListeInfinie = { init: init, charger: charger };

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

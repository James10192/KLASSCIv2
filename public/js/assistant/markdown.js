/*
 * Assistant KLASSCI — Markdown diffusé : réparation du fragment, enrichissement, patch morphdom, diagrammes et bloc de texte.
 *
 * Fichier 2/5 de public/js/assistant/, chargés dans l'ordre par le
 * composant resources/views/components/chatbot/assistant.blade.php. Les
 * fichiers partagent l'espace window.KlassciAst.
 */
(function (A) {
    'use strict';

    if (A.markdown) {
        return;
    }

    var LIB_CODE = A.LIB_CODE,
        chargerScript = A.chargerScript,
        chargerMermaid = A.chargerMermaid,
        mouvementReduit = A.mouvementReduit,
        hachage = A.hachage,
        el = A.el,
        bouton = A.bouton,
        copierTexte = A.copierTexte,
        confirmerCopie = A.confirmerCopie,
        telecharger = A.telecharger,
        htmlSur = A.htmlSur,
        versNoeuds = A.versNoeuds,
        svgSur = A.svgSur,
        sourceMermaidSure = A.sourceMermaidSure,
        retirerRestesMermaid = A.retirerRestesMermaid;
    // ─── Markdown diffusé ───

    var RE_CLOTURE = /^\s{0,3}(`{3,}|~{3,})/;
    var RE_SEPARATEUR = /^\s*\|?\s*:?-{1,}:?\s*(\|\s*:?-{1,}:?\s*)*\|?\s*$/;

    function nombreClotures(src) {
        return src.split('\n').filter(function (l) { return RE_CLOTURE.test(l); }).length;
    }

    function occurrences(texte, motif) {
        var n = 0;
        var i = texte.indexOf(motif);
        while (i >= 0) { n += 1; i = texte.indexOf(motif, i + motif.length); }
        return n;
    }

    /**
     * Complète le markdown en cours de diffusion pour qu'il se rende sans
     * saccade : clôt un bloc de code ouvert, ferme gras / code / barré laissés
     * ouverts sur la dernière ligne, retire un lien inachevé, et ne montre un
     * tableau qu'une fois son en-tête et sa ligne de séparation reçus.
     * S'applique à la copie rendue, jamais au texte reçu.
     */
    function remend(src) {
        if (!src) { return ''; }
        if (nombreClotures(src) % 2 === 1) {
            return src.replace(/\s+$/, '') + '\n```';
        }
        var lignes = src.split('\n');
        var k = lignes.length;
        while (k > 0 && /^\s*\|/.test(lignes[k - 1])) { k -= 1; }
        var tableau = lignes.slice(k);
        if (tableau.length) {
            var colonnes = (tableau[0].match(/\|/g) || []).length;
            var separe = tableau.length >= 2 && RE_SEPARATEUR.test(tableau[1]) && (tableau[1].match(/\|/g) || []).length >= colonnes;
            if (!separe) {
                lignes = lignes.slice(0, k);
            } else if (tableau.length > 2 && !/\|\s*$/.test(tableau[tableau.length - 1])) {
                lignes = lignes.slice(0, lignes.length - 1);
            }
        }
        while (lignes.length && lignes[lignes.length - 1] === '' && lignes.length > 1 && lignes[lignes.length - 2] === '') {
            lignes.pop();
        }
        var n = lignes.length - 1;
        if (n < 0) { return ''; }
        var der = lignes[n];
        der = der.replace(/\[([^\]]*)\]\([^)]*$/, '$1').replace(/\[([^\]]*)$/, '$1');
        var sansCode = der.replace(/`[^`]*`/g, '');
        if (occurrences(der, '`') % 2 === 1) {
            der = der.replace(/\s+$/, '') + '`';
        } else {
            if (occurrences(sansCode, '**') % 2 === 1) {
                var net = der.replace(/\s+$/, '');
                // « **» ouvert à l'instant : on l'efface plutôt que de dessiner un gras vide.
                der = /\*\*$/.test(net) ? net.slice(0, -2) : net + '**';
            }
            if (occurrences(sansCode, '~~') % 2 === 1) { der = der.replace(/\s+$/, '') + '~~'; }
        }
        // Un « - » ou un « * » seul en fin de ligne dessinerait une puce vide.
        if (/^\s*([-*+]|\d+\.)\s*$/.test(der)) { der = ''; }
        lignes[n] = der;
        return lignes.join('\n');
    }

    /** Enrichit le rendu : tableaux défilants, blocs de code, diagrammes. */
    function enrichir(racine, ouvert) {
        Array.prototype.forEach.call(racine.querySelectorAll('table'), function (t) {
            var enveloppe = el('div', 'ast-md-table');
            t.parentNode.insertBefore(enveloppe, t);
            enveloppe.appendChild(t);
        });
        var blocs = racine.querySelectorAll('pre');
        Array.prototype.forEach.call(blocs, function (pre, index) {
            var code = pre.querySelector('code');
            var source = code ? code.textContent : pre.textContent;
            var classe = code ? (code.className.match(/language-([\w-]+)/) || [])[1] : null;
            var clos = !(ouvert && index === blocs.length - 1);
            var cle = 'b' + index + '-' + hachage(source) + (clos ? '' : '-o');
            var bloc;
            if (classe === 'mermaid') {
                bloc = el('div', 'ast-mmd');
                bloc.setAttribute('data-mmd', clos ? '1' : '0');
                var src = el('pre', 'ast-mmd-src', source);
                src.hidden = true;
                bloc.appendChild(src);
                var attente = el('div', 'ast-mmd-attente');
                attente.appendChild(el('span', 'ast-shimmer', clos ? 'Préparation du diagramme…' : 'Diagramme en cours de rédaction…'));
                bloc.appendChild(attente);
            } else {
                bloc = el('div', 'ast-code');
                var tete = el('div', 'ast-code-head');
                tete.appendChild(el('span', 'ast-code-lang', classe || 'texte'));
                var copie = bouton('ast-code-copy', 'far fa-copy', 'Copier', 'Copier le code');
                copie.setAttribute('data-ast-copy', '');
                tete.appendChild(copie);
                bloc.appendChild(tete);
                bloc.appendChild(pre.cloneNode(true));
                bloc.setAttribute('data-code', clos && classe ? classe : '');
            }
            bloc.setAttribute('data-fkey', cle);
            pre.parentNode.replaceChild(bloc, pre);
        });
    }

    /**
     * Entoure de <span class="ast-tok"> chaque lot de caractères apparu pendant
     * la diffusion. Les lots restent en place jusqu'à la fin : le fil ne fait
     * que grandir par la fin, donc chaque <span> garde sa position d'un rendu à
     * l'autre, morphdom le laisse intact et son fondu va jusqu'au bout. Pas
     * d'identifiant : un élément à clé que le markdown déplace d'un parent à
     * l'autre fait échouer morphdom.
     */
    function marquerNouveaux(racine, lots) {
        if (!lots.length) { return; }
        var parcours = document.createTreeWalker(racine, NodeFilter.SHOW_TEXT, {
            acceptNode: function (n) {
                return n.parentNode.closest('pre, .ast-mmd, .ast-code') ? NodeFilter.FILTER_REJECT : NodeFilter.FILTER_ACCEPT;
            }
        });
        var noeuds = [];
        while (parcours.nextNode()) { noeuds.push(parcours.currentNode); }
        var position = 0;
        noeuds.forEach(function (n) {
            var texte = n.nodeValue;
            var debut = position;
            var fin = position + texte.length;
            position = fin;
            if (fin <= lots[0].debut) { return; }
            var morceaux = [];
            var curseur = debut;
            if (curseur < lots[0].debut) {
                morceaux.push({ t: texte.slice(0, lots[0].debut - debut) });
                curseur = lots[0].debut;
            }
            for (var i = 0; i < lots.length && curseur < fin; i += 1) {
                var borne = i + 1 < lots.length ? lots[i + 1].debut : Infinity;
                if (borne <= curseur) { continue; }
                var jusqua = Math.min(borne, fin);
                morceaux.push({ t: texte.slice(curseur - debut, jusqua - debut), lot: lots[i].id });
                curseur = jusqua;
            }
            var frag = document.createDocumentFragment();
            morceaux.forEach(function (m) {
                if (!m.t) { return; }
                if (m.lot === undefined) { frag.appendChild(document.createTextNode(m.t)); return; }
                frag.appendChild(el('span', 'ast-tok', m.t));
            });
            n.parentNode.replaceChild(frag, n);
        });
    }

    function longueurVisible(racine) {
        var parcours = document.createTreeWalker(racine, NodeFilter.SHOW_TEXT, {
            acceptNode: function (n) {
                return n.parentNode.closest('pre, .ast-mmd, .ast-code') ? NodeFilter.FILTER_REJECT : NodeFilter.FILTER_ACCEPT;
            }
        });
        var total = 0;
        while (parcours.nextNode()) { total += parcours.currentNode.nodeValue.length; }
        return total;
    }

    function patcher(cible, source) {
        if (window.morphdom) {
            window.morphdom(cible, source, {
                childrenOnly: true,
                onBeforeElUpdated: function (de, vers) {
                    if (de.hasAttribute('data-frozen') && de.getAttribute('data-fkey') === vers.getAttribute('data-fkey')) {
                        return false;
                    }
                    return !de.isEqualNode(vers);
                }
            });
            return;
        }
        while (cible.firstChild) { cible.removeChild(cible.firstChild); }
        while (source.firstChild) { cible.appendChild(source.firstChild); }
    }

    // ─── Diagrammes ───

    var mmdCompteur = 0;

    /** Dessine un bloc .ast-mmd clos : diagramme, barre d'outils, ou code en repli. */
    function dessinerDiagramme(bloc) {
        if (bloc.hasAttribute('data-frozen')) { return; }
        bloc.setAttribute('data-frozen', '1');
        var source = (bloc.querySelector('.ast-mmd-src') || {}).textContent || '';
        var sure = sourceMermaidSure(source);
        mmdCompteur += 1;
        var id = 'ast-mmd-' + mmdCompteur + '-' + Date.now().toString(36);
        chargerMermaid().then(function (mermaid) {
            return Promise.resolve(mermaid.parse(sure, { suppressErrors: true })).then(function (valide) {
                if (!valide) { throw new Error('syntaxe'); }
                return mermaid.render(id, sure);
            });
        }).then(function (resultat) {
            retirerRestesMermaid(id);
            var frag = svgSur(resultat.svg);
            if (!frag) { throw new Error('svg'); }
            monterDiagramme(bloc, source, frag);
        }).catch(function () {
            retirerRestesMermaid(id);
            monterDiagramme(bloc, source, null);
        });
    }

    function monterDiagramme(bloc, source, fragSvg) {
        while (bloc.firstChild) { bloc.removeChild(bloc.firstChild); }
        var barre = el('div', 'ast-mmd-bar');
        barre.appendChild(el('span', 'ast-mmd-label', 'Diagramme'));
        var outils = el('div', 'ast-mmd-tools');
        var code = el('pre', 'ast-mmd-code', source);
        var vue = el('div', 'ast-mmd-view');
        if (fragSvg) {
            vue.appendChild(fragSvg);
            var bCode = bouton('ast-tool-btn', 'fas fa-code', null, 'Voir le code');
            bCode.addEventListener('click', function () {
                var montre = code.hidden;
                code.hidden = !montre;
                vue.hidden = montre;
                bCode.classList.toggle('is-on', montre);
            });
            var bCopie = bouton('ast-tool-btn', 'far fa-copy', null, 'Copier le code');
            bCopie.addEventListener('click', function () { copierTexte(source).then(function () { confirmerCopie(bCopie); }); });
            var bSvg = bouton('ast-tool-btn', 'fas fa-download', null, 'Télécharger en SVG');
            // Le fichier téléchargé est le SVG nettoyé, celui qui est affiché.
            bSvg.addEventListener('click', function () {
                var svg = vue.querySelector('svg');
                if (svg) { telecharger('diagramme.svg', new XMLSerializer().serializeToString(svg), 'image/svg+xml'); }
            });
            var bPlein = bouton('ast-tool-btn', 'fas fa-expand', null, 'Agrandir');
            bPlein.addEventListener('click', function () { ouvrirPleinEcran(vue.querySelector('svg'), bPlein); });
            outils.appendChild(bCode);
            outils.appendChild(bCopie);
            outils.appendChild(bSvg);
            outils.appendChild(bPlein);
            code.hidden = true;
        } else {
            vue.hidden = true;
            barre.appendChild(el('span', 'ast-mmd-note', 'Le diagramme n\'a pas pu être dessiné : voici sa description.'));
        }
        barre.appendChild(outils);
        bloc.appendChild(barre);
        bloc.appendChild(vue);
        bloc.appendChild(code);
    }

    function ouvrirPleinEcran(svg, retour) {
        if (!svg) { return; }
        var voile = el('div', 'ast-zoom');
        voile.setAttribute('role', 'dialog');
        voile.setAttribute('aria-modal', 'true');
        voile.setAttribute('aria-label', 'Diagramme agrandi');
        var scene = el('div', 'ast-zoom-scene');
        var copie = svg.cloneNode(true);
        copie.removeAttribute('style');
        copie.setAttribute('width', '100%');
        copie.setAttribute('height', '100%');
        scene.appendChild(copie);
        var barre = el('div', 'ast-zoom-bar');
        barre.appendChild(el('span', null, 'Molette pour zoomer · glisser pour déplacer'));
        var fermer = bouton('ast-zoom-close', 'fas fa-xmark', null, 'Fermer');
        barre.appendChild(fermer);
        voile.appendChild(barre);
        voile.appendChild(scene);
        document.body.appendChild(voile);
        var etat = { z: 1, x: 0, y: 0, glisse: null };
        function appliquer() { scene.style.transform = 'translate(' + etat.x + 'px,' + etat.y + 'px) scale(' + etat.z + ')'; }
        scene.addEventListener('wheel', function (ev) {
            ev.preventDefault();
            etat.z = Math.max(0.4, Math.min(6, etat.z * (ev.deltaY < 0 ? 1.12 : 0.89)));
            appliquer();
        }, { passive: false });
        scene.addEventListener('pointerdown', function (ev) {
            etat.glisse = { x: ev.clientX - etat.x, y: ev.clientY - etat.y };
            scene.setPointerCapture(ev.pointerId);
        });
        scene.addEventListener('pointermove', function (ev) {
            if (!etat.glisse) { return; }
            etat.x = ev.clientX - etat.glisse.x;
            etat.y = ev.clientY - etat.glisse.y;
            appliquer();
        });
        scene.addEventListener('pointerup', function () { etat.glisse = null; });
        function clore() {
            window.removeEventListener('keydown', clavier, true);
            voile.remove();
            if (retour && retour.isConnected) { retour.focus(); }
        }
        function clavier(ev) {
            if (ev.key === 'Escape') { ev.stopPropagation(); ev.preventDefault(); clore(); }
        }
        window.addEventListener('keydown', clavier, true);
        fermer.addEventListener('click', clore);
        voile.addEventListener('click', function (ev) { if (ev.target === voile) { clore(); } });
        fermer.focus();
    }

    // ─── Bloc de texte diffusé ───

    function BlocTexte(vue, id) {
        this.vue = vue;
        this.id = id;
        this.el = el('div', 'ast-md');
        this.src = '';
        this.tampon = '';
        this.tamponDepuis = 0;
        this.fini = false;
        this.raf = null;
        this.dernierRendu = 0;
        this.lots = [];
        this.lotId = 0;
        this.vus = 0;
        this.nettoyage = null;
    }

    BlocTexte.prototype.ajouter = function (delta) {
        if (!delta) { return; }
        if (!this.tampon) { this.tamponDepuis = performance.now(); }
        this.tampon += delta;
        this.planifier();
    };

    BlocTexte.prototype.planifier = function () {
        if (this.raf) { return; }
        var self = this;
        this.raf = window.requestAnimationFrame(function (t) { self.raf = null; self.cadence(t); });
    };

    /** Libère le tampon à chaque image : backlog/8 (2 au moins), jamais plus de 150 ms de retard. */
    BlocTexte.prototype.cadence = function (maintenant) {
        if (this.tampon) {
            var n = Math.max(2, Math.ceil(this.tampon.length / 8));
            if (maintenant - this.tamponDepuis > 150) { n = this.tampon.length; }
            var coupe = this.tampon.slice(0, n);
            // Ne pas couper une paire de substitution.
            if (/[\ud800-\udbff]$/.test(coupe)) { n += 1; coupe = this.tampon.slice(0, n); }
            this.src += coupe;
            this.tampon = this.tampon.slice(n);
            this.tamponDepuis = maintenant;
        }
        if (maintenant - this.dernierRendu >= 60 || !this.tampon) {
            this.rendre(maintenant);
        }
        if (this.tampon) {
            this.planifier();
        }
    };

    BlocTexte.prototype.terminer = function () {
        if (this.fini) { return; }
        this.src += this.tampon;
        this.tampon = '';
        this.fini = true;
        if (this.raf) { window.cancelAnimationFrame(this.raf); this.raf = null; }
        this.rendre(performance.now());
        // Laisse le dernier fondu s'achever, puis retire les marqueurs.
        var self = this;
        clearTimeout(this.nettoyage);
        this.nettoyage = setTimeout(function () { self.lots = []; self.rendre(performance.now()); }, mouvementReduit() ? 0 : 260);
    };

    BlocTexte.prototype.charger = function (texte) {
        this.src = String(texte || '');
        this.fini = true;
        this.rendre(performance.now());
    };

    BlocTexte.prototype.rendre = function (maintenant) {
        this.dernierRendu = maintenant;
        var diffusion = !this.fini;
        var src = diffusion ? remend(this.src) : this.src;
        var ouvert = diffusion && nombreClotures(this.src) % 2 === 1;
        var racine = versNoeuds(htmlSur(src));
        enrichir(racine, ouvert);
        var total = longueurVisible(racine);
        if (!mouvementReduit() && window.marked) {
            if (diffusion && total > this.vus) {
                this.lotId += 1;
                this.lots.push({ id: this.lotId, debut: this.vus, t: maintenant });
            }
            if (this.lots.length) { marquerNouveaux(racine, this.lots); }
        }
        this.vus = total;
        this.el.classList.toggle('is-streaming', diffusion);
        patcher(this.el, racine);
        this.apres();
        this.vue.signalerTexte();
    };

    /** Coloration des blocs de code clos, dessin des diagrammes clos. */
    BlocTexte.prototype.apres = function () {
        Array.prototype.forEach.call(this.el.querySelectorAll('.ast-code[data-code]:not([data-frozen])'), function (bloc) {
            var langue = bloc.getAttribute('data-code');
            if (!langue) { return; }
            bloc.setAttribute('data-frozen', '1');
            chargerScript(LIB_CODE).then(function (hljs) {
                var code = bloc.querySelector('code');
                if (code && hljs && hljs.getLanguage(langue)) { hljs.highlightElement(code); }
            }).catch(function () { /* code resté brut */ });
        });
        Array.prototype.forEach.call(this.el.querySelectorAll('.ast-mmd[data-mmd="1"]:not([data-frozen])'), dessinerDiagramme);
    };

    A.dessinerDiagramme = dessinerDiagramme;
    A.BlocTexte = BlocTexte;
    A.markdown = true;
})(window.KlassciAst = window.KlassciAst || {});

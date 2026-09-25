/*
 * Assistant KLASSCI — interface (namespace CSS ast-*).
 *
 * Fabrique Alpine `klassciAssistant()` utilisée par le composant
 * resources/views/components/chatbot/assistant.blade.php.
 *
 * Le serveur répond au protocole « UI message stream » v1 du Vercel AI SDK :
 * des événements SSE `data: {json}` (start, start-step, text-start/delta/end,
 * data-*, message-metadata, error, finish) terminés par `data: [DONE]`.
 * Parties propres à KLASSCI : data-etape (une étape de l'agent), data-widget
 * (un résultat mis en forme, à sa place dans le fil), data-suites, data-lien.
 * L'ancienne partie data-outil reste acceptée et devient une étape.
 *
 * Organisation : Alpine tient l'enveloppe (panneau, saisie, historique,
 * préférences). Le corps d'une réponse est tenu par une « vue » par message,
 * hors de la réactivité d'Alpine, qui dessine le fil (étapes, résultats,
 * texte diffusé) et le met à jour sans le reconstruire.
 *
 * Sécurité : tout texte du modèle passe par marked puis DOMPurify, puis est
 * analysé par DOMParser ; tant que ces bibliothèques ne sont pas chargées, le
 * texte est échappé. Les résultats riches sont construits avec textContent,
 * jamais en injectant une donnée comme balisage.
 */
(function () {
    'use strict';

    if (typeof window.klassciAssistant === 'function') {
        return;
    }

    var CLE_CONVERSATION = 'klassci.assistant.conversation';
    var SEUIL_BAS = 70;
    var PALETTE = ['#0453cb', '#5e91de', '#93b4ec', '#033a8e', '#3b7ddb', '#c7d8f5', '#1e5fd0', '#b3cbf1'];

    var BASE = [
        {
            global: 'marked',
            src: 'https://cdn.jsdelivr.net/npm/marked@15.0.12/marked.min.js',
            integrity: 'sha384-948ahk4ZmxYVYOc+rxN1H2gM1EJ2Duhp7uHtZ4WSLkV4Vtx5MUqnV+l7u9B+jFv+'
        },
        {
            global: 'DOMPurify',
            src: 'https://cdn.jsdelivr.net/npm/dompurify@3.2.6/dist/purify.min.js',
            integrity: 'sha384-JEyTNhjM6R1ElGoJns4U2Ln4ofPcqzSsynQkmEc/KGy6336qAZl70tDLufbkla+3'
        },
        {
            global: 'morphdom',
            src: 'https://cdn.jsdelivr.net/npm/morphdom@2.7.7/dist/morphdom-umd.min.js',
            integrity: 'sha384-PFKa183TQO/VHts2ympulsyJyBKe3IQluoIaAEBx0ZQyfsm6vPIYGRRV924IJhRe'
        }
    ];
    var LIB_GRAPHIQUE = {
        global: 'Chart',
        src: 'https://cdn.jsdelivr.net/npm/chart.js@4.5.0/dist/chart.umd.min.js',
        integrity: 'sha384-XcdcwHqIPULERb2yDEM4R0XaQKU3YnDsrTmjACBZyfdVVqjh6xQ4/DCMd7XLcA6Y'
    };
    var LIB_CODE = {
        global: 'hljs',
        src: 'https://cdn.jsdelivr.net/npm/@highlightjs/cdn-assets@11.11.1/highlight.min.js',
        integrity: 'sha384-RH2xi4eIQ/gjtbs9fUXM68sLSi99C7ZWBRX1vDrVv6GQXRibxXLbwO2NGZB74MbU'
    };
    // Module ESM (11 Ko d'entrée, le reste chargé à la demande) : jamais le paquet UMD de 1,6 Mo.
    // Un import() dynamique ne porte pas d'empreinte SRI : la version est épinglée.
    var MERMAID_ESM = 'https://cdn.jsdelivr.net/npm/mermaid@11.12.0/dist/mermaid.esm.min.mjs';

    var PURIFY = { USE_PROFILES: { html: true }, FORBID_TAGS: ['style', 'form', 'input', 'img', 'button'] };

    // ─── Chargement des bibliothèques ───

    var scripts = {};

    function chargerScript(lib) {
        if (window[lib.global]) { return Promise.resolve(window[lib.global]); }
        if (scripts[lib.src]) { return scripts[lib.src]; }
        scripts[lib.src] = new Promise(function (resolve, reject) {
            var s = document.createElement('script');
            s.src = lib.src;
            s.integrity = lib.integrity;
            s.crossOrigin = 'anonymous';
            s.referrerPolicy = 'no-referrer';
            s.onload = function () { resolve(window[lib.global]); };
            s.onerror = function () { scripts[lib.src] = null; reject(new Error('chargement')); };
            document.head.appendChild(s);
        });
        return scripts[lib.src];
    }

    var chargementBase = null;

    function chargerLibs() {
        if (chargementBase) { return chargementBase; }
        chargementBase = Promise.all(BASE.map(chargerScript)).then(function () {
            if (window.DOMPurify && !window.__astPurifyHook) {
                window.__astPurifyHook = true;
                window.DOMPurify.addHook('afterSanitizeAttributes', function (node) {
                    if (node.tagName !== 'A') { return; }
                    // Un lien hors de l'application perd son href : versNoeuds le
                    // remplace ensuite par son seul texte.
                    var sure = urlSure(node.getAttribute('href'));
                    node.removeAttribute('target');
                    if (sure) {
                        node.setAttribute('href', sure);
                    } else {
                        node.removeAttribute('href');
                    }
                });
            }
        });
        chargementBase.catch(function () { chargementBase = null; });
        return chargementBase;
    }

    var chargementMermaid = null;

    function chargerMermaid() {
        if (chargementMermaid) { return chargementMermaid; }
        chargementMermaid = import(MERMAID_ESM).then(function (mod) {
            var mermaid = mod.default || mod;
            mermaid.initialize({
                startOnLoad: false,
                securityLevel: 'strict',
                theme: 'base',
                fontFamily: 'inherit',
                htmlLabels: false,
                flowchart: { htmlLabels: false, curve: 'basis', padding: 12 },
                themeVariables: {
                    fontFamily: getComputedStyle(document.body).fontFamily,
                    fontSize: '14px',
                    primaryColor: '#e8f0fd',
                    primaryBorderColor: '#0453cb',
                    primaryTextColor: '#0f172a',
                    secondaryColor: '#f1f5fb',
                    tertiaryColor: '#f8fafc',
                    lineColor: '#5e91de',
                    textColor: '#1e293b',
                    mainBkg: '#e8f0fd',
                    nodeBorder: '#0453cb',
                    clusterBkg: '#f8fafc',
                    clusterBorder: '#c7d8f5',
                    edgeLabelBackground: '#ffffff',
                    noteBkgColor: '#f1f5fb',
                    noteBorderColor: '#93b4ec',
                    actorBkg: '#e8f0fd',
                    actorBorder: '#0453cb',
                    pie1: '#0453cb', pie2: '#5e91de', pie3: '#93b4ec', pie4: '#033a8e', pie5: '#3b7ddb', pie6: '#c7d8f5'
                }
            });
            return mermaid;
        });
        chargementMermaid.catch(function () { chargementMermaid = null; });
        return chargementMermaid;
    }

    // ─── Petits outils ───

    var compteur = 0;
    function uid(prefixe) {
        compteur += 1;
        return (prefixe || 'k') + '_' + Date.now().toString(36) + '_' + compteur;
    }

    function echapper(texte) {
        return String(texte == null ? '' : texte)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function mouvementReduit() {
        return !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
    }

    function hachage(texte) {
        var h = 5381;
        for (var i = 0; i < texte.length; i += 1) { h = ((h << 5) + h + texte.charCodeAt(i)) | 0; }
        return (h >>> 0).toString(36);
    }

    /**
     * Seuls les chemins internes de l'application deviennent des liens. Même
     * règle que AfficherTableau::estLienInterne côté serveur : le texte du modèle
     * n'est pas fiable, et une barre oblique inverse, une tabulation ou un retour
     * à la ligne dans l'URL mèneraient sinon hors du site. Le reste reste du texte.
     */
    var LIEN_INTERNE = /^\/(esbtp|dashboard|chatbot)([\/?#][A-Za-z0-9\/_\-?=&%.#]*)?$/;

    function urlSure(url) {
        if (typeof url !== 'string' || url === '') {
            return null;
        }
        var chemin = url;
        // Une adresse absolue du même site (route() côté serveur) est ramenée à son chemin.
        if (/^https?:\/\//i.test(url)) {
            if (/[\s\\]/.test(url)) { return null; }
            try {
                var u = new URL(url);
                if (u.origin !== window.location.origin) { return null; }
                chemin = u.pathname + u.search + u.hash;
            } catch (e) {
                return null;
            }
        }
        return LIEN_INTERNE.test(chemin) ? chemin : null;
    }

    /** Point d'envoi d'un formulaire ou d'une action : même site, http(s) seulement. */
    function envoiSur(url) {
        if (typeof url !== 'string' || url === '' || /[\s\\]/.test(url)) {
            return null;
        }
        try {
            var u = new URL(url, window.location.origin);
            return (u.origin === window.location.origin && /^https?:$/.test(u.protocol)) ? u.href : null;
        } catch (e) {
            return null;
        }
    }

    /** Classes d'icône Font Awesome venues du serveur : lettres, chiffres, tirets. */
    function classeIcone(valeur, defaut) {
        return (typeof valeur === 'string' && /^[a-z0-9 -]+$/i.test(valeur)) ? valeur : defaut;
    }

    function el(tag, classe, texte) {
        var n = document.createElement(tag);
        if (classe) {
            n.className = classe;
        }
        if (texte !== undefined && texte !== null) {
            n.textContent = String(texte);
        }
        return n;
    }

    function icone(classes) {
        var i = document.createElement('i');
        i.className = classes;
        i.setAttribute('aria-hidden', 'true');
        return i;
    }

    function bouton(classe, contenuIcone, libelle, titre) {
        var b = el('button', classe);
        b.type = 'button';
        if (contenuIcone) { b.appendChild(icone(contenuIcone)); }
        if (libelle) { b.appendChild(el('span', null, libelle)); }
        if (titre) { b.title = titre; b.setAttribute('aria-label', titre); }
        return b;
    }

    function ancre(url, classe) {
        var sure = urlSure(url);
        if (!sure) { return null; }
        var a = el('a', classe);
        a.href = sure;
        return a;
    }

    function lien(url, libelle, classeIco) {
        var a = ancre(url, 'ast-link');
        if (!a) {
            return null;
        }
        a.appendChild(el('span', null, libelle));
        a.appendChild(icone(classeIco || 'fas fa-arrow-right'));
        return a;
    }

    var FORMAT_NOMBRE = new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 2 });
    var FORMAT_COMPACT = new Intl.NumberFormat('fr-FR', { notation: 'compact', maximumFractionDigits: 1 });

    function nombre(v) {
        return (typeof v === 'number' && isFinite(v)) ? FORMAT_NOMBRE.format(v) : String(v == null ? '' : v);
    }

    function montant(v, unite) {
        if (typeof v === 'number' && isFinite(v)) { return FORMAT_NOMBRE.format(v) + ' ' + (unite || 'FCFA'); }
        return String(v == null ? '' : v);
    }

    function pluriel(n, un, plusieurs) {
        return n + ' ' + (n > 1 ? plusieurs : un);
    }

    function duree(ms) {
        if (!ms || ms < 0) { return ''; }
        if (ms < 1000) { return (ms / 1000).toFixed(1).replace('.', ',') + ' s'; }
        if (ms < 60000) { return (Math.round(ms / 100) / 10).toString().replace('.', ',') + ' s'; }
        return Math.floor(ms / 60000) + ' min ' + Math.round((ms % 60000) / 1000) + ' s';
    }

    function copierTexte(texte) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            return navigator.clipboard.writeText(texte);
        }
        return Promise.reject(new Error('presse-papiers'));
    }

    /** Bascule brièvement l'icône d'un bouton sur une coche après une copie. */
    function confirmerCopie(btn) {
        var i = btn.querySelector('i');
        if (!i) { return; }
        var avant = i.className;
        i.className = 'fas fa-check';
        btn.classList.add('is-done');
        setTimeout(function () { i.className = avant; btn.classList.remove('is-done'); }, 1500);
    }

    function telecharger(nom, contenu, type) {
        var blob = new Blob([contenu], { type: type });
        var url = URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = nom;
        document.body.appendChild(a);
        a.click();
        a.remove();
        setTimeout(function () { URL.revokeObjectURL(url); }, 1000);
    }

    function csv(lignes) {
        return lignes.map(function (l) {
            return l.map(function (v) {
                var s = String(v == null ? '' : v).replace(/ | /g, ' ');
                return /[";\n]/.test(s) ? '"' + s.replace(/"/g, '""') + '"' : s;
            }).join(';');
        }).join('\n');
    }

    /** Couleur sémantique autorisée seulement pour un statut. */
    function ton(valeur) {
        var v = String(valeur || '').toLowerCase();
        if (v === 'succes' || v === 'ok') { return 'ok'; }
        if (v === 'alerte') { return 'attention'; }
        if (v === 'neutre' || v === 'info') { return 'neutre'; }
        if (/(^|\b)(success|valid|activ|oui|pay|publi|presque)/.test(v)) { return 'ok'; }
        if (/(warning|attente|retard|partiel)/.test(v)) { return 'attention'; }
        if (/(danger|rejet|annul|^non$|impay|echec|échec)/.test(v)) { return 'alerte'; }
        return 'neutre';
    }

    function badge(texte, style) {
        return el('span', 'ast-badge ast-badge--' + ton(style || texte), texte);
    }

    function initiales(texte) {
        var mots = String(texte || '?').trim().split(/\s+/).filter(Boolean);
        var s = mots.length > 1 ? mots[0].charAt(0) + mots[mots.length - 1].charAt(0) : String(mots[0] || '?').slice(0, 2);
        return s.toUpperCase();
    }

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

    /** Markdown → balisage sûr : nettoyé par DOMPurify, ou échappé tant qu'il n'est pas chargé. */
    function htmlSur(texte) {
        if (window.marked && window.DOMPurify) {
            var html = window.marked.parse(String(texte || ''), { gfm: true, breaks: true, async: false });
            return window.DOMPurify.sanitize(html, PURIFY);
        }
        return echapper(texte).replace(/\n/g, '<br>');
    }

    var analyseur = null;

    /** Balisage déjà nettoyé → nœuds, par DOMParser (les scripts y restent inertes). */
    function versNoeuds(html) {
        analyseur = analyseur || new DOMParser();
        var doc = analyseur.parseFromString('<!doctype html><body><div>' + html + '</div></body>', 'text/html');
        var conteneur = document.importNode(doc.body.firstChild, true);
        // marked sépare ses blocs par des retours à la ligne : ces nœuds vides
        // deviendraient le dernier enfant et décrocheraient le point de diffusion.
        Array.prototype.slice.call(conteneur.childNodes).forEach(function (n) {
            if (n.nodeType === 3 && !/\S/.test(n.nodeValue)) { conteneur.removeChild(n); }
        });
        Array.prototype.forEach.call(conteneur.querySelectorAll('a:not([href])'), function (a) {
            a.replaceWith(document.createTextNode(a.textContent));
        });
        return conteneur;
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

    function svgSur(svg) {
        var frag = window.DOMPurify
            ? window.DOMPurify.sanitize(svg, { USE_PROFILES: { svg: true, svgFilters: true }, RETURN_DOM_FRAGMENT: true })
            : null;
        return frag && frag.firstChild ? frag : null;
    }

    /** Dessine un bloc .ast-mmd clos : diagramme, barre d'outils, ou code en repli. */
    /**
     * Un bloc mermaid du texte libre n'a traversé aucun contrôle serveur : les
     * directives %%{…}%% (qui réécrivent la configuration), l'en-tête de
     * configuration et les instructions click (liens, rappels) sont retirés.
     */
    function sourceMermaidSure(source) {
        var texte = String(source || '').replace(/^\s*---\r?\n[\s\S]*?\r?\n---\s*(\r?\n|$)/, '');
        return texte.split(/\r?\n/).filter(function (ligne) {
            return ligne.indexOf('%%{') === -1 && !/^\s*click\b/i.test(ligne);
        }).join('\n');
    }

    function dessinerDiagramme(bloc) {
        if (bloc.hasAttribute('data-frozen')) { return; }
        bloc.setAttribute('data-frozen', '1');
        var source = (bloc.querySelector('.ast-mmd-src') || {}).textContent || '';
        var sure = sourceMermaidSure(source);
        chargerMermaid().then(function (mermaid) {
            mmdCompteur += 1;
            return mermaid.render('ast-mmd-' + mmdCompteur + '-' + Date.now().toString(36), sure);
        }).then(function (resultat) {
            var frag = svgSur(resultat.svg);
            if (!frag) { throw new Error('svg'); }
            monterDiagramme(bloc, source, frag, resultat.svg);
        }).catch(function () {
            monterDiagramme(bloc, source, null, null);
        });
    }

    function monterDiagramme(bloc, source, fragSvg, svgTexte) {
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
            bSvg.addEventListener('click', function () { telecharger('diagramme.svg', svgTexte, 'image/svg+xml'); });
            var bPlein = bouton('ast-tool-btn', 'fas fa-expand', null, 'Agrandir');
            bPlein.addEventListener('click', function () { ouvrirPleinEcran(vue.querySelector('svg')); });
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

    function ouvrirPleinEcran(svg) {
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

    // ─── Rendus des résultats (widgets) ───

    /** Carte commune : titre, compteur, outils, corps, pied avec total et lien. */
    function carte(options) {
        var racine = el('section', 'ast-w' + (options.classe ? ' ' + options.classe : ''));
        var tete = null;
        if (options.titre || options.outils) {
            tete = el('header', 'ast-w-head');
            var titres = el('div', 'ast-w-titles');
            if (options.icone) {
                var ic = el('span', 'ast-w-icon');
                ic.appendChild(icone(options.icone));
                tete.appendChild(ic);
            }
            if (options.titre) { titres.appendChild(el('h4', 'ast-w-title', options.titre)); }
            if (options.sousTitre) { titres.appendChild(el('div', 'ast-w-sub', options.sousTitre)); }
            tete.appendChild(titres);
            if (options.outils) {
                var outils = el('div', 'ast-w-tools');
                options.outils.forEach(function (o) { outils.appendChild(o); });
                tete.appendChild(outils);
            }
            racine.appendChild(tete);
        }
        var corps = el('div', 'ast-w-body');
        racine.appendChild(corps);
        return { racine: racine, corps: corps, tete: tete };
    }

    function piedCarte(racine, total, affiches, url, libelle) {
        var a = url ? lien(url, libelle || 'Tout voir dans KLASSCI', 'fas fa-arrow-right') : null;
        if (!a && !total) { return false; }
        var f = el('footer', 'ast-w-foot');
        var info = '';
        if (total && affiches && total > affiches) {
            info = affiches + ' sur ' + nombre(total);
        } else if (total) {
            info = pluriel(nombre(total), 'résultat', 'résultats');
        }
        f.appendChild(el('span', 'ast-w-count', info));
        if (a) { f.appendChild(a); }
        racine.appendChild(f);
        return !!a;
    }

    function piedLegacy(racine, data, affiches) {
        return piedCarte(racine, data.total_available || data.total_count, data.total_count || affiches, data.deep_link, null);
    }

    function piedNouveau(racine, data, affiches) {
        var l = data.lien || {};
        return piedCarte(racine, data.total, affiches, l.url, l.libelle);
    }

    /** Montre `premiers` éléments puis un bouton qui déplie le reste. */
    function depliable(elements, premiers, conteneurBouton, libelle) {
        if (elements.length <= premiers + 1) { return; }
        elements.slice(premiers).forEach(function (e) { e.hidden = true; });
        var b = bouton('ast-more', 'fas fa-chevron-down', libelle(elements.length));
        b.setAttribute('aria-expanded', 'false');
        b.addEventListener('click', function () {
            var ouvert = b.getAttribute('aria-expanded') === 'true';
            elements.slice(premiers).forEach(function (e) { e.hidden = ouvert; });
            b.setAttribute('aria-expanded', ouvert ? 'false' : 'true');
            b.querySelector('span').textContent = ouvert ? libelle(elements.length) : 'Réduire';
        });
        conteneurBouton.appendChild(b);
    }

    /**
     * Tableau commun : en-tête collant, première colonne collante, chiffres
     * alignés, 8 lignes puis dépliage, copie au format CSV.
     * colonnes : [{ libelle, num }] ; lignes : [{ cellules:[Node|string], brut:[string] }]
     */
    function tableauCommun(options) {
        var boutonCsv = bouton('ast-tool-btn', 'far fa-copy', null, 'Copier au format CSV');
        var c = carte({ titre: options.titre, icone: 'fas fa-table', outils: [boutonCsv], classe: 'ast-w--table' });
        var defil = el('div', 'ast-tbl-scroll');
        defil.setAttribute('tabindex', '0');
        defil.setAttribute('role', 'region');
        defil.setAttribute('aria-label', options.titre || 'Tableau');
        var table = el('table', 'ast-tbl');
        var thead = el('thead');
        var tr = el('tr');
        options.colonnes.forEach(function (col) {
            var th = el('th', col.num ? 'is-num' : null, col.libelle);
            th.scope = 'col';
            tr.appendChild(th);
        });
        thead.appendChild(tr);
        table.appendChild(thead);
        var tbody = el('tbody');
        var rangs = options.lignes.map(function (ligne) {
            var r = el('tr');
            ligne.cellules.forEach(function (cellule, i) {
                var td = el(i === 0 ? 'th' : 'td', options.colonnes[i] && options.colonnes[i].num ? 'is-num' : null);
                if (i === 0) { td.scope = 'row'; }
                if (cellule && cellule.nodeType) { td.appendChild(cellule); } else { td.textContent = cellule == null || cellule === '' ? '—' : String(cellule); }
                r.appendChild(td);
            });
            tbody.appendChild(r);
            return r;
        });
        table.appendChild(tbody);
        defil.appendChild(table);
        defil.addEventListener('scroll', function () { defil.classList.toggle('is-scrolled', defil.scrollLeft > 2); }, { passive: true });
        c.corps.appendChild(defil);
        if (!rangs.length) { c.corps.appendChild(el('p', 'ast-w-empty', 'Aucune ligne à afficher.')); }
        var bas = el('div', 'ast-w-more');
        depliable(rangs, 8, bas, function (n) { return 'Voir les ' + n + ' lignes'; });
        if (bas.firstChild) { c.corps.appendChild(bas); }
        boutonCsv.addEventListener('click', function () {
            var donnees = [options.colonnes.map(function (col) { return col.libelle; })].concat(options.lignes.map(function (l) { return l.brut; }));
            copierTexte(csv(donnees)).then(function () { confirmerCopie(boutonCsv); });
        });
        return c;
    }

    function celluleTypee(type, valeur) {
        if (type === 'lien' && valeur && typeof valeur === 'object') {
            var a = ancre(valeur.url, 'ast-cell-link');
            if (!a) { return { n: valeur.texte || '—', b: valeur.texte || '' }; }
            a.textContent = valeur.texte || 'Ouvrir';
            return { n: a, b: valeur.texte || '' };
        }
        if (type === 'statut' && valeur && typeof valeur === 'object') {
            return { n: badge(valeur.texte, valeur.ton || valeur.texte), b: valeur.texte || '' };
        }
        if (type === 'montant') {
            var m = montant(valeur);
            return { n: el('span', 'ast-money', m), b: m };
        }
        if (type === 'nombre') {
            var nb = nombre(valeur);
            return { n: el('span', 'ast-nb', nb), b: nb };
        }
        if (type === 'date') {
            return { n: el('span', 'ast-date', valeur), b: valeur == null ? '' : String(valeur) };
        }
        var s = valeur == null ? '' : (typeof valeur === 'object' ? (valeur.texte || '') : String(valeur));
        return { n: s, b: s };
    }

    var ESTIME_MONTANT = /(\d[\d\s  .,]*\s?(F\s?CFA|FCFA|XOF|F)\b)/i;

    // Chaque rendu reçoit (data, ctx, vue) et rend un nœud ; ctx = composant Alpine.
    var RENDUS = {
        tableau: function (data) {
            var colonnes = (data.colonnes || []).map(function (c) {
                return { cle: c.cle, type: c.type, libelle: c.libelle || c.cle, num: c.type === 'montant' || c.type === 'nombre' };
            });
            var lignes = (data.lignes || []).map(function (ligne) {
                var cellules = [];
                var brut = [];
                colonnes.forEach(function (col) {
                    var r = celluleTypee(col.type, ligne[col.cle]);
                    cellules.push(r.n);
                    brut.push(r.b);
                });
                return { cellules: cellules, brut: brut };
            });
            var c = tableauCommun({ titre: data.titre, colonnes: colonnes, lignes: lignes });
            piedNouveau(c.racine, data, lignes.length);
            return c.racine;
        },

        table: function (data) {
            var aDesActions = (data.rows || []).some(function (r) { return r.actions && r.actions.length; });
            var colonnes = (data.columns || []).map(function (col) {
                return { libelle: col.label, num: /montant|reste|pay|total|effectif|affect|moyenne|nombre/i.test(col.label || '') };
            });
            if (aDesActions) { colonnes.push({ libelle: '' }); }
            var lignes = (data.rows || []).map(function (row) {
                var cellules = [];
                var brut = [];
                (row.cells || []).forEach(function (cell) {
                    if (cell.badge) {
                        cellules.push(badge(cell.value, cell.badge));
                    } else if (ESTIME_MONTANT.test(String(cell.value || ''))) {
                        cellules.push(el('span', 'ast-money', cell.value));
                    } else {
                        cellules.push(cell.value);
                    }
                    brut.push(cell.value);
                });
                if (aDesActions) {
                    var actions = el('span', 'ast-cell-actions');
                    (row.actions || []).forEach(function (a) {
                        var l = ancre(a.url, 'ast-cell-link');
                        if (!l) { return; }
                        l.appendChild(el('span', null, a.label));
                        actions.appendChild(l);
                    });
                    cellules.push(actions);
                    brut.push('');
                }
                return { cellules: cellules, brut: brut };
            });
            var c = tableauCommun({ titre: data.title || data.titre, colonnes: colonnes, lignes: lignes });
            piedLegacy(c.racine, data, lignes.length);
            return c.racine;
        },

        /** Liste d'entités (étudiants, débiteurs…) : rangées compactes, pas de grandes cartes. */
        cards: function (data) {
            var cartes = data.cards || [];
            var c = carte({ titre: data.title || data.titre || null, classe: 'ast-w--list' });
            var liste = el('ul', 'ast-rows');
            var items = cartes.map(function (card) {
                var meta = (card.meta || []).slice();
                var cleMontant = -1;
                meta.forEach(function (m, i) {
                    if (cleMontant < 0 && /(reste|solde|dû|du$|impay|montant)/i.test(m.label || '')) { cleMontant = i; }
                });
                if (cleMontant < 0) {
                    meta.forEach(function (m, i) { if (cleMontant < 0 && ESTIME_MONTANT.test(String(m.value || ''))) { cleMontant = i; } });
                }
                var principal = cleMontant >= 0 ? meta[cleMontant] : null;
                var cleTaux = -1;
                meta.forEach(function (m, i) { if (cleTaux < 0 && i !== cleMontant && /^\s*\d{1,3}([.,]\d+)?\s?%\s*$/.test(String(m.value || ''))) { cleTaux = i; } });
                var taux = cleTaux >= 0 ? parseFloat(String(meta[cleTaux].value).replace(',', '.')) : null;
                var reste = meta.filter(function (m, i) { return i !== cleMontant && i !== cleTaux; });

                var li = el('li', 'ast-row');
                var premiere = (card.actions || []).map(function (a) { return urlSure(a.url) ? a : null; }).filter(Boolean)[0];
                var cible = premiere ? ancre(premiere.url, 'ast-row-in') : el('div', 'ast-row-in');
                if (premiere) { cible.setAttribute('aria-label', (card.title || '') + ' — ' + (premiere.label || 'Ouvrir')); }
                cible.appendChild(el('span', 'ast-avatar', card.initials ? String(card.initials).toUpperCase().slice(0, 2) : initiales(card.title)));
                var centre = el('div', 'ast-row-main');
                // Le nom a toute la largeur ; les badges passent sur la ligne d'en dessous,
                // sinon un panneau de 440 px ne laisse que quelques lettres du nom.
                var l1 = el('div', 'ast-row-title');
                var nom = el('span', 'ast-row-name', card.title);
                nom.title = card.title || '';
                l1.appendChild(nom);
                centre.appendChild(l1);
                var badges = (card.badges || []).slice(0, 2);
                var sous = [card.subtitle].concat(reste.map(function (m) { return (m.label ? m.label + ' ' : '') + m.value; })).filter(Boolean);
                if (badges.length || sous.length) {
                    var l2 = el('div', 'ast-row-sub');
                    badges.forEach(function (b) { l2.appendChild(badge(b.label, b.style)); });
                    if (sous.length) { l2.appendChild(el('span', 'ast-row-subtxt', sous.join(' · '))); }
                    centre.appendChild(l2);
                }
                cible.appendChild(centre);
                var droite = el('div', 'ast-row-side');
                if (principal) {
                    var montant = el('span', 'ast-row-amount', principal.value);
                    if (principal.label) { montant.title = principal.label; montant.setAttribute('aria-label', principal.label + ' ' + principal.value); }
                    droite.appendChild(montant);
                }
                if (taux !== null && isFinite(taux)) {
                    var barre = el('span', 'ast-row-bar');
                    barre.setAttribute('role', 'img');
                    barre.setAttribute('aria-label', meta[cleTaux].label + ' ' + meta[cleTaux].value);
                    var remplissage = el('span');
                    remplissage.style.width = Math.max(2, Math.min(100, taux)) + '%';
                    barre.appendChild(remplissage);
                    var ligneTaux = el('span', 'ast-row-rate');
                    ligneTaux.appendChild(barre);
                    ligneTaux.appendChild(el('span', 'ast-row-pct', Math.round(taux) + ' %'));
                    droite.appendChild(ligneTaux);
                }
                cible.appendChild(droite);
                if (premiere) { cible.appendChild(icone('fas fa-chevron-right ast-row-go')); }
                li.appendChild(cible);
                liste.appendChild(li);
                return li;
            });
            c.corps.appendChild(liste);
            if (!items.length) { c.corps.appendChild(el('p', 'ast-w-empty', 'Aucun résultat.')); }
            var bas = el('div', 'ast-w-more');
            depliable(items, 6, bas, function (n) { return 'Voir les ' + n; });
            if (bas.firstChild) { c.corps.appendChild(bas); }
            piedLegacy(c.racine, data, items.length);
            return c.racine;
        },

        kpis: function (data) {
            var grille = el('div', 'ast-kpis');
            (data.elements || []).forEach(function (k) {
                var tuile = urlSure(k.url) ? ancre(k.url, 'ast-kpi is-link') : el('div', 'ast-kpi');
                tuile.appendChild(el('span', 'ast-kpi-label', k.libelle));
                var v = el('span', 'ast-kpi-value');
                v.appendChild(document.createTextNode(typeof k.valeur === 'number' ? nombre(k.valeur) : String(k.valeur == null ? '—' : k.valeur)));
                if (k.unite) { v.appendChild(el('small', null, k.unite)); }
                tuile.appendChild(v);
                if (k.repere) {
                    var t = k.ton === 'succes' ? 'ok' : (k.ton === 'danger' ? 'alerte' : 'neutre');
                    tuile.appendChild(el('span', 'ast-kpi-ref ast-kpi-ref--' + t, k.repere));
                }
                grille.appendChild(tuile);
            });
            if (!data.titre && !data.lien) { return grille; }
            var c = carte({ titre: data.titre, classe: 'ast-w--kpis' });
            c.corps.appendChild(grille);
            piedNouveau(c.racine, data, 0);
            return c.racine;
        },

        'stat-cards': function (data) {
            var grille = el('div', 'ast-kpis');
            (data.stats || []).forEach(function (s) {
                var tuile = el('div', 'ast-kpi');
                var tete = el('span', 'ast-kpi-label');
                tete.appendChild(icone(classeIcone(s.icon, 'fas fa-chart-simple') + ' ast-kpi-ic'));
                tete.appendChild(document.createTextNode(s.label || ''));
                tuile.appendChild(tete);
                tuile.appendChild(el('span', 'ast-kpi-value', s.value));
                if (s.detail) { tuile.appendChild(el('span', 'ast-kpi-ref ast-kpi-ref--neutre', s.detail)); }
                grille.appendChild(tuile);
            });
            var frag = el('div', 'ast-w-bare');
            frag.appendChild(grille);
            if (data.deep_link || data.total_count) {
                var c = carte({});
                c.corps.appendChild(grille);
                piedLegacy(c.racine, data, 0);
                return c.racine;
            }
            return frag;
        },

        graphique: function (data, ctx, vue) {
            var c = carte({ titre: data.titre || 'Graphique', icone: 'fas fa-chart-column', classe: 'ast-w--chart' });
            var zone = el('div', 'ast-chart');
            var canvas = el('canvas');
            canvas.setAttribute('role', 'img');
            canvas.setAttribute('aria-label', (data.titre || 'Graphique') + ' : ' + (data.series || []).map(function (s) { return s.nom; }).join(', '));
            zone.appendChild(canvas);
            var attente = el('div', 'ast-chart-wait');
            attente.appendChild(el('span', 'ast-shimmer', 'Préparation du graphique…'));
            zone.appendChild(attente);
            c.corps.appendChild(zone);
            piedNouveau(c.racine, data, 0);
            var detruit = false;
            var graphique = null;
            vue.aNettoyer(function () { detruit = true; if (graphique) { graphique.destroy(); graphique = null; } });
            chargerScript(LIB_GRAPHIQUE).then(function (Chart) {
                if (detruit) { return; }
                attente.remove();
                graphique = new Chart(canvas, configGraphique(data, Chart));
            }).catch(function () {
                attente.textContent = 'Graphique indisponible.';
            });
            return c.racine;
        },

        diagramme: function (data) {
            var c = carte({ titre: data.titre || null, classe: 'ast-w--diagram' });
            var bloc = el('div', 'ast-mmd');
            var src = el('pre', 'ast-mmd-src', data.mermaid || '');
            src.hidden = true;
            bloc.appendChild(src);
            var attente = el('div', 'ast-mmd-attente');
            attente.appendChild(el('span', 'ast-shimmer', 'Préparation du diagramme…'));
            bloc.appendChild(attente);
            c.corps.appendChild(bloc);
            setTimeout(function () { dessinerDiagramme(bloc); }, 0);
            piedNouveau(c.racine, data, 0);
            return c.racine;
        },

        'fee-groups': function (data) {
            var pile = el('div', 'ast-w-stack');
            (data.groups || []).forEach(function (g) {
                var obligatoire = g.type === 'mandatory';
                var cols = obligatoire
                    ? [{ libelle: 'Filière / niveau' }, { libelle: 'Affectés', num: true }, { libelle: 'Réaffectés', num: true }, { libelle: 'Non affectés', num: true }]
                    : [{ libelle: 'Formule' }, { libelle: 'Montant', num: true }];
                var lignes = obligatoire
                    ? (g.items || []).map(function (i) { return [i.label, i.affectes, i.reaffectes, i.non_affectes]; })
                    : (g.options || []).map(function (o) { return [o.name, o.montant]; });
                var c = carte({ titre: g.title, icone: 'fas fa-tags', sousTitre: obligatoire ? 'Frais obligatoire' : 'Frais optionnel', classe: 'ast-w--fees' });
                var table = el('table', 'ast-tbl ast-tbl--compact');
                var thead = el('thead');
                var tr = el('tr');
                cols.forEach(function (col) { tr.appendChild(el('th', col.num ? 'is-num' : null, col.libelle)); });
                thead.appendChild(tr);
                table.appendChild(thead);
                var tbody = el('tbody');
                lignes.forEach(function (cells) {
                    var r = el('tr');
                    cells.forEach(function (v, idx) { r.appendChild(el('td', idx ? 'is-num ast-money' : null, v || '—')); });
                    tbody.appendChild(r);
                });
                table.appendChild(tbody);
                var defil = el('div', 'ast-tbl-scroll');
                defil.appendChild(table);
                c.corps.appendChild(defil);
                pile.appendChild(c.racine);
            });
            var fin = el('div');
            piedLegacy(fin, data, 0);
            if (fin.firstChild) { pile.appendChild(fin.firstChild); }
            return pile;
        },

        'payment-groups': function (data) {
            var pile = el('div', 'ast-w-stack');
            (data.groups || []).forEach(function (g) {
                var insc = g.inscription || {};
                var sous = [insc.filiere, insc.annee, insc.type].filter(function (v) { return v && v !== 'N/A'; }).join(' · ');
                var total = el('div', 'ast-w-total');
                total.appendChild(el('span', 'ast-money', g.total_paye || '0 FCFA'));
                total.appendChild(el('span', 'ast-w-total-label', 'payé'));
                var c = carte({ titre: insc.classe || 'Inscription', sousTitre: sous || null, icone: 'fas fa-receipt', outils: [total], classe: 'ast-w--payments' });
                if (insc.statut) { c.tete.querySelector('.ast-w-titles').appendChild(badge(insc.statut)); }
                var liste = el('ul', 'ast-lines');
                (g.payments || []).forEach(function (p) {
                    var li = el('li', 'ast-line');
                    var gauche = el('div', 'ast-line-main');
                    gauche.appendChild(el('span', 'ast-line-title', p.categorie && p.categorie !== 'N/A' ? p.categorie : 'Paiement'));
                    var det = [p.date, p.mode, p.tranche].filter(function (v) { return v && v !== 'N/A'; }).join(' · ');
                    if (det) { gauche.appendChild(el('span', 'ast-line-sub', det)); }
                    li.appendChild(gauche);
                    var droite = el('div', 'ast-line-side');
                    droite.appendChild(el('span', 'ast-money', p.montant || '0 FCFA'));
                    if (p.statut) { droite.appendChild(badge(p.statut)); }
                    li.appendChild(droite);
                    liste.appendChild(li);
                });
                if (!(g.payments || []).length) { liste.appendChild(el('li', 'ast-w-empty', 'Aucun paiement enregistré')); }
                c.corps.appendChild(liste);
                piedCarte(c.racine, 0, 0, insc.lien, "Voir l'inscription");
                pile.appendChild(c.racine);
            });
            var fin = el('div');
            piedLegacy(fin, data, 0);
            if (fin.firstChild) { pile.appendChild(fin.firstChild); }
            return pile;
        },

        timetable: function (data) {
            var meta = [data.filiere, data.semestre, data.annee, data.periode].filter(function (v) { return v && v !== 'N/A'; }).join(' · ');
            var c = carte({ titre: data.classe || 'Emploi du temps', sousTitre: meta || null, icone: 'far fa-calendar', classe: 'ast-w--timetable' });
            (data.days || []).forEach(function (d) {
                var jour = el('div', 'ast-day');
                jour.appendChild(el('div', 'ast-day-name', d.jour || 'Jour'));
                var slots = d.slots || [];
                if (!slots.length) { jour.appendChild(el('div', 'ast-day-empty', 'Aucun cours')); }
                slots.forEach(function (s) {
                    var row = el('div', 'ast-slot');
                    row.appendChild(el('span', 'ast-slot-time', s.horaire));
                    var info = el('div', 'ast-slot-info');
                    info.appendChild(el('div', 'ast-slot-subject', s.matiere || '—'));
                    var det = [s.enseignant, s.salle].filter(function (v) { return v && v !== 'N/A'; }).join(' · ');
                    if (det) { info.appendChild(el('div', 'ast-slot-sub', det)); }
                    row.appendChild(info);
                    jour.appendChild(row);
                });
                c.corps.appendChild(jour);
            });
            piedLegacy(c.racine, data, 0);
            return c.racine;
        },

        checklist: function (data) {
            var c = carte({ titre: data.title || 'Configuration', icone: 'fas fa-list-check', classe: 'ast-w--checklist' });
            if (typeof data.progress_percent === 'number') {
                var pct = Math.max(0, Math.min(100, data.progress_percent));
                var ligne = el('div', 'ast-progress-line');
                var barre = el('div', 'ast-progress');
                barre.setAttribute('role', 'progressbar');
                barre.setAttribute('aria-valuenow', String(pct));
                barre.setAttribute('aria-valuemin', '0');
                barre.setAttribute('aria-valuemax', '100');
                var fill = el('span');
                fill.style.width = pct + '%';
                barre.appendChild(fill);
                ligne.appendChild(barre);
                ligne.appendChild(el('span', 'ast-progress-pct', pct + ' %'));
                c.corps.appendChild(ligne);
            }
            var icones = { done: 'fas fa-check', next: 'fas fa-arrow-right', blocked: 'fas fa-lock', todo: 'far fa-circle' };
            (data.sections || []).forEach(function (sec) {
                var s = el('div', 'ast-check-sec');
                if (sec.title) { s.appendChild(el('div', 'ast-check-title', sec.title)); }
                (sec.steps || []).forEach(function (step) {
                    var etat = icones[step.status] ? step.status : 'todo';
                    var row = el('div', 'ast-check ast-check--' + etat);
                    var ic = el('span', 'ast-check-icon');
                    ic.appendChild(icone(icones[etat]));
                    row.appendChild(ic);
                    var txt = el('div', 'ast-check-txt');
                    txt.appendChild(el('div', 'ast-check-name', step.title));
                    if (step.description) { txt.appendChild(el('div', 'ast-check-sub', step.description)); }
                    var l = lien(step.deep_link, step.action_label || 'Ouvrir');
                    if (l) { txt.appendChild(l); }
                    row.appendChild(txt);
                    s.appendChild(row);
                });
                c.corps.appendChild(s);
            });
            return c.racine;
        },

        form: function (data, ctx) {
            var form = el('form', 'ast-w ast-form');
            form.noValidate = false;
            if (data.title) { form.appendChild(el('div', 'ast-form-title', data.title)); }
            if (data.description) { form.appendChild(el('p', 'ast-form-desc', data.description)); }
            (data.fields || []).forEach(function (f) {
                var id = uid('champ');
                var groupe = el('div', 'ast-field' + (f.type === 'checkbox' ? ' ast-field--check' : ''));
                var input;
                if (f.type === 'select') {
                    // Select natif habillé (apparence retirée, chevron maison) : le
                    // formulaire est construit hors d'Alpine, le composant premium n'y est pas disponible.
                    input = el('select', 'ast-input ast-select');
                    (f.options || []).forEach(function (o) {
                        var opt = el('option', null, o.label);
                        opt.value = o.value;
                        input.appendChild(opt);
                    });
                } else if (f.type === 'textarea') {
                    input = el('textarea', 'ast-input');
                    input.rows = 3;
                } else {
                    input = el('input', f.type === 'checkbox' ? 'ast-check-input' : 'ast-input');
                    input.type = ['text', 'number', 'checkbox', 'date', 'email'].indexOf(f.type) >= 0 ? f.type : 'text';
                    ['min', 'max', 'step'].forEach(function (k) { if (f[k] !== undefined) { input[k] = f[k]; } });
                    if (f.type === 'checkbox') { input.value = '1'; }
                }
                input.id = id;
                input.name = f.name;
                if (f.required) { input.required = true; }
                if (f.placeholder) { input.placeholder = f.placeholder; }
                if (f.value !== undefined && f.type !== 'checkbox') { input.value = f.value; }
                var label = el('label', 'ast-label', f.label + (f.required ? ' *' : ''));
                label.htmlFor = id;
                if (f.type === 'checkbox') {
                    groupe.appendChild(input);
                    groupe.appendChild(label);
                } else {
                    groupe.appendChild(label);
                    groupe.appendChild(input);
                }
                if (f.help) { groupe.appendChild(el('small', 'ast-field-help', f.help)); }
                form.appendChild(groupe);
            });
            var erreur = el('div', 'ast-form-error');
            erreur.setAttribute('role', 'alert');
            form.appendChild(erreur);
            var envoi = el('button', 'ast-btn ast-btn--primary', data.submit_label || 'Envoyer');
            envoi.type = 'submit';
            form.appendChild(envoi);
            if (data.focus_field) {
                setTimeout(function () {
                    var cible = form.querySelector('[name="' + CSS.escape(data.focus_field) + '"]');
                    if (cible) { cible.focus(); }
                }, 120);
            }
            form.addEventListener('submit', function (ev) {
                ev.preventDefault();
                var corps = {};
                Array.prototype.forEach.call(form.elements, function (champ) {
                    if (!champ.name) { return; }
                    corps[champ.name] = champ.type === 'checkbox' ? (champ.checked ? 1 : 0) : champ.value;
                });
                Object.keys(data.hidden_fields || {}).forEach(function (k) { corps[k] = data.hidden_fields[k]; });
                envoi.disabled = true;
                erreur.textContent = '';
                ctx.posterFormulaire(data.action_url, corps).then(function (res) {
                    if (res.ok) {
                        form.classList.add('is-sent');
                        envoi.textContent = 'Envoyé';
                    } else {
                        erreur.textContent = res.message;
                        envoi.disabled = false;
                    }
                });
            });
            return form;
        },

        'approval-request': function (data, ctx) {
            var box = el('div', 'ast-w ast-approval');
            var head = el('div', 'ast-approval-head');
            head.appendChild(icone('fas fa-shield-halved'));
            head.appendChild(el('span', null, 'Validation requise'));
            box.appendChild(head);
            box.appendChild(el('p', null, data.summary || 'Une action attend votre validation.'));
            if (data.payload) {
                var dl = el('dl', 'ast-kv');
                [['Élément', data.payload.name], ['Code', data.payload.code], ['Montant', data.payload.default_amount]]
                    .filter(function (x) { return x[1] !== undefined && x[1] !== null && x[1] !== ''; })
                    .forEach(function (x) {
                        var row = el('div');
                        row.appendChild(el('dt', null, x[0]));
                        row.appendChild(el('dd', null, x[1]));
                        dl.appendChild(row);
                    });
                box.appendChild(dl);
            }
            var etat = el('div', 'ast-approval-state');
            etat.setAttribute('role', 'status');
            var actions = el('div', 'ast-approval-actions');
            var ok = el('button', 'ast-btn ast-btn--primary', 'Approuver et exécuter');
            ok.type = 'button';
            var non = el('button', 'ast-btn ast-btn--ghost', 'Rejeter');
            non.type = 'button';
            actions.appendChild(ok);
            actions.appendChild(non);
            box.appendChild(actions);
            box.appendChild(etat);
            var approval = data.approval || {};
            function decider(url, approuver) {
                ok.disabled = true;
                non.disabled = true;
                etat.textContent = approuver ? 'Exécution…' : 'Rejet…';
                ctx.deciderAction(url, approuver).then(function (res) {
                    etat.textContent = res.message;
                    etat.className = 'ast-approval-state ' + (res.ok ? 'is-ok' : 'is-ko');
                    if (res.ok) {
                        actions.remove();
                    } else {
                        ok.disabled = false;
                        non.disabled = false;
                    }
                });
            }
            ok.addEventListener('click', function () { decider(approval.approve_url, true); });
            non.addEventListener('click', function () { decider(approval.reject_url, false); });
            if (data.status && data.status !== 'proposed') {
                actions.remove();
                etat.textContent = data.status === 'executed' ? 'Action déjà exécutée.' : 'Action close (' + data.status + ').';
            }
            return box;
        }
    };
    RENDUS.proposal = RENDUS['approval-request'];

    /** Clés qui portent un lien de pied : le bouton « Ouvrir la page » du message devient alors redondant. */
    function widgetALien(kind, data) {
        if (!data) { return false; }
        if (data.lien && data.lien.url) { return true; }
        return ['table', 'cards', 'fee-groups', 'payment-groups', 'stat-cards', 'timetable'].indexOf(kind) >= 0 && !!data.deep_link;
    }

    function configGraphique(data, Chart) {
        var type = data.type === 'courbe' ? 'line' : (data.type === 'anneau' ? 'doughnut' : 'bar');
        var unite = data.unite || null;
        var reduit = mouvementReduit();
        var police = getComputedStyle(document.body).fontFamily;
        if (Chart.defaults && Chart.defaults.font) { Chart.defaults.font.family = police; }
        var series = (data.series || []).map(function (s, i) {
            var couleur = PALETTE[i % PALETTE.length];
            var jeu = { label: s.nom, data: (s.valeurs || []).map(Number) };
            if (type === 'doughnut') {
                jeu.backgroundColor = (s.valeurs || []).map(function (v, j) { return PALETTE[j % PALETTE.length]; });
                jeu.borderColor = '#fff';
                jeu.borderWidth = 2;
            } else if (type === 'line') {
                jeu.borderColor = couleur;
                jeu.backgroundColor = i === 0 ? 'rgba(4,83,203,.08)' : 'transparent';
                jeu.fill = i === 0;
                jeu.tension = 0.35;
                jeu.pointRadius = 3;
                jeu.pointBackgroundColor = couleur;
                jeu.borderWidth = 2;
            } else {
                jeu.backgroundColor = couleur;
                jeu.borderRadius = 6;
                jeu.maxBarThickness = 36;
            }
            return jeu;
        });
        function fmt(v) { return unite === 'FCFA' ? montant(v, 'FCFA') : nombre(v) + (unite ? ' ' + unite : ''); }
        var axes = type === 'doughnut' ? {} : {
            x: { grid: { display: false }, border: { display: false }, ticks: { color: '#64748b', font: { size: 11 } } },
            y: {
                beginAtZero: true,
                grid: { color: '#eef2f7' },
                border: { display: false },
                ticks: { color: '#64748b', font: { size: 11 }, maxTicksLimit: 5, callback: function (v) { return FORMAT_COMPACT.format(v); } }
            }
        };
        return {
            type: type,
            data: { labels: data.libelles || [], datasets: series },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: reduit ? false : { duration: 500 },
                cutout: type === 'doughnut' ? '62%' : undefined,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: series.length > 1 || type === 'doughnut', position: 'bottom', labels: { boxWidth: 10, boxHeight: 10, usePointStyle: true, color: '#475569', padding: 14 } },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        padding: 10,
                        cornerRadius: 8,
                        titleFont: { weight: '600' },
                        callbacks: {
                            label: function (c) {
                                var v = type === 'doughnut' ? c.parsed : c.parsed.y;
                                return ' ' + (c.dataset.label ? c.dataset.label + ' : ' : '') + fmt(v);
                            }
                        }
                    }
                },
                scales: axes
            }
        };
    }

    // ─── Vue d'une réponse ───

    /**
     * Tient le fil d'une réponse : groupes d'étapes, résultats, blocs de texte,
     * dans l'ordre d'arrivée. Hors de la réactivité d'Alpine ; ne touche au
     * message Alpine (msg) que pour les suites, les liens et les drapeaux.
     */
    function Vue(msg, ctx) {
        this.msg = msg;
        this.ctx = ctx;
        this.root = el('div', 'ast-flow');
        this.segments = [];
        this.etapes = {};
        this.widgets = {};
        this.nettoyages = [];
        this.pensee = el('div', 'ast-pensee');
        this.pensee.appendChild(el('span', 'ast-pensee-dot'));
        this.pensee.appendChild(el('span', 'ast-shimmer', 'Analyse de votre demande…'));
        this.debut = performance.now();
        var self = this;
        this.root.addEventListener('click', function (ev) { self.surClic(ev); });
    }

    Vue.prototype.monter = function (hote) {
        if (this.root.parentNode !== hote) { hote.appendChild(this.root); }
    };

    Vue.prototype.aNettoyer = function (fn) { this.nettoyages.push(fn); };

    Vue.prototype.detruire = function () {
        this.nettoyages.forEach(function (fn) { try { fn(); } catch (e) { /* déjà détruit */ } });
        this.nettoyages = [];
        this.segments.forEach(function (s) { if (s.bloc && s.bloc.raf) { window.cancelAnimationFrame(s.bloc.raf); } });
        this.root.remove();
    };

    Vue.prototype.attendre = function () {
        if (!this.segments.length && !this.pensee.parentNode) { this.root.appendChild(this.pensee); }
    };

    Vue.prototype.dernier = function () {
        return this.segments[this.segments.length - 1] || null;
    };

    Vue.prototype.ajouterSegment = function (seg) {
        if (this.pensee.parentNode) { this.pensee.remove(); }
        var precedent = this.dernier();
        this.segments.push(seg);
        seg.el.classList.add('ast-part');
        this.root.appendChild(seg.el);
        if (precedent && precedent.type === 'etapes') { this.replierSiFini(precedent); }
    };

    Vue.prototype.surClic = function (ev) {
        var copie = ev.target.closest('[data-ast-copy]');
        if (copie && this.root.contains(copie)) {
            var bloc = copie.closest('.ast-code');
            var code = bloc ? bloc.querySelector('pre') : null;
            if (code) { copierTexte(code.textContent).then(function () { confirmerCopie(copie); }); }
        }
    };

    // Étapes

    Vue.prototype.etape = function (id, data) {
        data = data || {};
        var cle = id || uid('e');
        var existant = this.etapes[cle];
        if (data.etat === 'retire') {
            if (existant) { this.retirerEtape(cle); }
            return;
        }
        if (!existant) {
            var groupe = this.dernier();
            if (!groupe || groupe.type !== 'etapes' || groupe.clos) {
                groupe = this.nouveauGroupe();
            }
            var item = { id: cle, groupe: groupe, vu: performance.now(), fin: null, data: {} };
            item.el = el('li', 'ast-step');
            item.ic = el('span', 'ast-step-ic');
            var txt = el('div', 'ast-step-txt');
            item.lbl = el('span', 'ast-step-lbl');
            item.det = el('span', 'ast-step-det');
            txt.appendChild(item.lbl);
            txt.appendChild(item.det);
            item.duree = el('span', 'ast-step-time');
            item.el.appendChild(item.ic);
            item.el.appendChild(txt);
            item.el.appendChild(item.duree);
            groupe.items.push(item);
            groupe.liste.appendChild(item.el);
            this.etapes[cle] = item;
            existant = item;
        }
        existant.data = Object.assign({}, existant.data, data);
        this.dessinerEtape(existant);
        this.majGroupe(existant.groupe);
        if (existant.groupe !== this.dernier()) { this.replierSiFini(existant.groupe); }
    };

    Vue.prototype.nouveauGroupe = function () {
        var g = { type: 'etapes', items: [], clos: false };
        g.el = el('div', 'ast-steps is-live');
        g.sommaire = el('button', 'ast-steps-sum');
        g.sommaire.type = 'button';
        g.sommaire.setAttribute('aria-expanded', 'true');
        g.sommaireIc = el('span', 'ast-steps-ic');
        g.sommaireTxt = el('span', 'ast-steps-txt');
        g.sommaire.appendChild(g.sommaireIc);
        g.sommaire.appendChild(g.sommaireTxt);
        g.sommaire.appendChild(icone('fas fa-chevron-down ast-steps-caret'));
        g.corps = el('div', 'ast-steps-body');
        var interieur = el('div', 'ast-steps-inner');
        g.liste = el('ol', 'ast-steps-list');
        interieur.appendChild(g.liste);
        g.corps.appendChild(interieur);
        g.el.appendChild(g.sommaire);
        g.el.appendChild(g.corps);
        g.sommaire.addEventListener('click', function () {
            var ouvert = g.el.classList.toggle('is-open');
            g.sommaire.setAttribute('aria-expanded', ouvert ? 'true' : 'false');
        });
        this.ajouterSegment(g);
        return g;
    };

    Vue.prototype.dessinerEtape = function (item) {
        var d = item.data;
        var etat = ['en_cours', 'termine', 'echec', 'interrompu'].indexOf(d.etat) >= 0 ? d.etat : 'en_cours';
        item.el.className = 'ast-step is-' + etat;
        while (item.ic.firstChild) { item.ic.removeChild(item.ic.firstChild); }
        if (etat === 'en_cours') {
            item.ic.appendChild(el('span', 'ast-spinner'));
        } else {
            item.ic.appendChild(icone(etat === 'termine' ? 'fas fa-check' : (etat === 'echec' ? 'fas fa-exclamation' : 'fas fa-minus')));
            if (!item.fin) { item.fin = performance.now(); }
        }
        var libelle = d.libelle || d.label || 'Consultation des données';
        if (etat === 'en_cours') {
            item.lbl.textContent = libelle + '…';
            item.lbl.className = 'ast-step-lbl ast-shimmer';
        } else if (etat === 'echec') {
            item.lbl.textContent = d.resume || (libelle + ' : donnée non accessible');
            item.lbl.className = 'ast-step-lbl';
        } else if (etat === 'interrompu') {
            item.lbl.textContent = libelle + ' (interrompu)';
            item.lbl.className = 'ast-step-lbl';
        } else {
            item.lbl.textContent = d.resume || libelle;
            item.lbl.className = 'ast-step-lbl';
        }
        item.det.textContent = d.detail || '';
        item.det.hidden = !d.detail;
        var ms = d.duree_ms || (item.fin ? item.fin - item.vu : 0);
        item.duree.textContent = etat === 'en_cours' ? '' : duree(ms);
    };

    Vue.prototype.majGroupe = function (g) {
        var n = g.items.length;
        var enCours = g.items.filter(function (i) { return (i.data.etat || 'en_cours') === 'en_cours'; }).length;
        var echecs = g.items.filter(function (i) { return i.data.etat === 'echec'; }).length;
        var total = 0;
        g.items.forEach(function (i) { total += i.data.duree_ms || (i.fin ? i.fin - i.vu : 0); });
        while (g.sommaireIc.firstChild) { g.sommaireIc.removeChild(g.sommaireIc.firstChild); }
        g.sommaireIc.appendChild(enCours ? el('span', 'ast-spinner') : icone(echecs ? 'fas fa-exclamation' : 'fas fa-check'));
        g.el.classList.toggle('has-fail', !!echecs && !enCours);
        var morceaux = [pluriel(n, 'étape', 'étapes')];
        if (echecs) { morceaux.push(pluriel(echecs, 'sans résultat', 'sans résultat')); }
        if (!enCours && total) { morceaux.push(duree(total)); }
        g.sommaireTxt.textContent = morceaux.join(' · ');
        g.el.hidden = n === 0;
    };

    Vue.prototype.replierSiFini = function (g) {
        if (g.clos || !g.items.length) { return; }
        var enCours = g.items.some(function (i) { return (i.data.etat || 'en_cours') === 'en_cours'; });
        if (enCours) { return; }
        g.clos = true;
        g.el.classList.remove('is-live', 'is-open');
        g.sommaire.setAttribute('aria-expanded', 'false');
        this.majGroupe(g);
    };

    Vue.prototype.retirerEtape = function (cle) {
        var item = this.etapes[cle];
        delete this.etapes[cle];
        var g = item.groupe;
        g.items = g.items.filter(function (i) { return i !== item; });
        item.el.remove();
        if (!g.items.length) {
            g.el.remove();
            this.segments = this.segments.filter(function (s) { return s !== g; });
            if (!this.segments.length && this.msg.status === 'streaming') { this.attendre(); }
        } else {
            this.majGroupe(g);
        }
    };

    // Résultats

    Vue.prototype.widget = function (id, kind, data) {
        var k = String(kind || '').replace(/_/g, '-');
        var rendu = RENDUS[k];
        if (!rendu || !data) {
            this.suites(data);
            return;
        }
        var noeud;
        try {
            noeud = rendu(data, this.ctx, this);
        } catch (e) {
            noeud = el('p', 'ast-w-error', "Ce résultat n'a pas pu être affiché.");
        }
        var hote = el('div', 'ast-widget ast-widget--' + k);
        hote.appendChild(noeud);
        var existant = id ? this.widgets[id] : null;
        if (existant) {
            existant.el.replaceWith(hote);
            existant.el = hote;
        } else {
            var seg = { type: 'widget', id: id, el: hote };
            if (id) { this.widgets[id] = seg; }
            this.ajouterSegment(seg);
        }
        if (widgetALien(k, data)) { this.msg.lienWidget = true; }
        this.suites(data);
    };

    // Texte

    Vue.prototype.bloc = function (id) {
        for (var i = this.segments.length - 1; i >= 0; i -= 1) {
            var s = this.segments[i];
            if (s.type === 'texte' && s.id === id) { return s.bloc; }
        }
        return null;
    };

    Vue.prototype.texteDebut = function (id) {
        var existant = this.bloc(id);
        if (existant && !existant.fini) { return existant; }
        var b = new BlocTexte(this, id);
        this.ajouterSegment({ type: 'texte', id: id, el: b.el, bloc: b });
        return b;
    };

    Vue.prototype.texteDelta = function (id, delta) {
        var b = this.bloc(id);
        if (!b || b.fini) { b = this.texteDebut(id); }
        b.ajouter(delta);
    };

    Vue.prototype.texteFin = function (id) {
        var b = this.bloc(id);
        if (b) { b.terminer(); }
    };

    Vue.prototype.signalerTexte = function () {
        if (!this.msg.aTexte) {
            var aTexte = this.segments.some(function (s) { return s.type === 'texte' && s.bloc.src.trim(); });
            if (aTexte) { this.msg.aTexte = true; }
        }
    };

    Vue.prototype.relire = function () {
        this.segments.forEach(function (s) { if (s.type === 'texte') { s.bloc.rendre(performance.now()); } });
    };

    Vue.prototype.markdown = function () {
        return this.segments.filter(function (s) { return s.type === 'texte'; })
            .map(function (s) { return s.bloc.src + s.bloc.tampon; })
            .filter(function (t) { return t.trim(); })
            .join('\n\n');
    };

    // Suites, liens, fin

    Vue.prototype.suites = function (data) {
        if (!data) { return; }
        var s = this.msg.suites;
        (data.follow_up || []).forEach(function (q) { if (typeof q === 'string' && s.questions.indexOf(q) < 0 && s.questions.length < 4) { s.questions.push(q); } });
        (data.follow_up_actions || []).forEach(function (x) { if (x && x.label) { s.actions.push(Object.assign({ fait: false }, x)); } });
    };

    Vue.prototype.lien = function (url) {
        if (url && this.msg.liens.indexOf(url) < 0) { this.msg.liens.push(url); }
    };

    /** Clôt la réponse : textes vidés, étapes en cours marquées interrompues, groupes repliés. */
    Vue.prototype.terminer = function (interrompu) {
        var self = this;
        if (this.pensee.parentNode) { this.pensee.remove(); }
        this.segments.forEach(function (s) {
            if (s.type === 'texte') { s.bloc.terminer(); }
            if (s.type === 'etapes') {
                s.items.forEach(function (i) {
                    if ((i.data.etat || 'en_cours') === 'en_cours') {
                        i.data = Object.assign({}, i.data, { etat: interrompu ? 'interrompu' : 'termine' });
                        self.dessinerEtape(i);
                    }
                });
                self.replierSiFini(s);
                self.majGroupe(s);
            }
        });
        this.signalerTexte();
    };

    /** Réponse enregistrée (historique) : parties ordonnées. */
    Vue.prototype.chargerParties = function (parties) {
        var self = this;
        (parties || []).forEach(function (p) {
            if (!p || !p.type) { return; }
            if (p.type === 'etape') {
                self.etape(p.id, { nom: p.nom, libelle: p.libelle, etat: p.etat || 'termine', resume: p.resume, detail: p.detail, duree_ms: p.duree_ms });
            } else if (p.type === 'widget') {
                var data = p.data || {};
                self.widget(p.id, p.kind || data.kind, data);
            } else if (p.type === 'texte') {
                var b = new BlocTexte(self, uid('t'));
                self.ajouterSegment({ type: 'texte', id: b.id, el: b.el, bloc: b });
                b.charger(p.texte);
            } else if (p.type === 'suites') {
                self.suites(p.data);
            } else if (p.type === 'lien') {
                self.lien(p.url || (p.data && p.data.url));
            }
        });
        this.terminer(false);
    };

    /** Ancien format (content + display_type / display_data) : texte, puis résultat, puis lien. */
    Vue.prototype.chargerLegacy = function (message) {
        var parties = versParties(message);
        var self = this;
        parties.forEach(function (p) {
            if (p.type === 'text') {
                var b = new BlocTexte(self, uid('t'));
                self.ajouterSegment({ type: 'texte', id: b.id, el: b.el, bloc: b });
                b.charger(p.text);
            } else if (p.type === 'rich') {
                self.widget(null, p.kind, p.data);
            } else if (p.type === 'suites') {
                self.suites(p.data);
            } else if (p.type === 'lien') {
                self.lien(p.url);
            }
        });
        this.terminer(false);
    };

    /** Transforme un message enregistré au format historique (route JSON) en parties. */
    function versParties(message) {
        var parties = [];
        if (message.content) {
            parties.push({ key: uid('p'), type: 'text', text: message.content, fini: true });
        }
        var type = message.display_type || 'text';
        var data = message.display_data || null;
        if (type !== 'text' && data) {
            parties.push({ key: uid('p'), type: 'rich', kind: type.replace(/_/g, '-'), data: data });
        } else if (data && (data.follow_up || data.follow_up_actions)) {
            parties.push({ key: uid('p'), type: 'suites', data: data });
        }
        if (message.deep_link) {
            parties.push({ key: uid('p'), type: 'lien', url: message.deep_link });
        }
        return parties;
    }

    // ─── Défilement collé en bas ───

    /**
     * Suit le bas du fil tant que l'utilisateur ne remonte pas. Toute remontée
     * (molette, glisser, touches) détache ; revenir près du bas rattache.
     * Les défilements lancés par le code sont reconnus et ignorés.
     */
    function Defileur(fil, contenu, surEtat) {
        this.fil = fil;
        this.contenu = contenu;
        this.surEtat = surEtat;
        this.echappe = false;
        this.dernierHaut = fil.scrollTop;
        this.attendu = null;
        this.raf = null;
        this.presBas = true;
        var self = this;
        this.surDefil = function () { self.defilement(); };
        this.surRoue = function (ev) { if (ev.deltaY < 0) { self.echapper(); } };
        this.surToucheDebut = function (ev) { self.toucheY = ev.touches[0] ? ev.touches[0].clientY : null; };
        this.surToucheMouv = function (ev) {
            var y = ev.touches[0] ? ev.touches[0].clientY : null;
            if (y !== null && self.toucheY !== null && y > self.toucheY + 4) { self.echapper(); }
            self.toucheY = y;
        };
        this.surClavier = function (ev) {
            if (['PageUp', 'Home', 'ArrowUp'].indexOf(ev.key) >= 0) { self.echapper(); }
        };
        fil.addEventListener('scroll', this.surDefil, { passive: true });
        fil.addEventListener('wheel', this.surRoue, { passive: true });
        fil.addEventListener('touchstart', this.surToucheDebut, { passive: true });
        fil.addEventListener('touchmove', this.surToucheMouv, { passive: true });
        fil.addEventListener('keydown', this.surClavier);
        if (window.ResizeObserver) {
            this.observateur = new ResizeObserver(function () { self.changement(); });
            this.observateur.observe(contenu);
        }
    }

    Defileur.prototype.detruire = function () {
        this.fil.removeEventListener('scroll', this.surDefil);
        this.fil.removeEventListener('wheel', this.surRoue);
        this.fil.removeEventListener('touchstart', this.surToucheDebut);
        this.fil.removeEventListener('touchmove', this.surToucheMouv);
        this.fil.removeEventListener('keydown', this.surClavier);
        if (this.observateur) { this.observateur.disconnect(); }
        if (this.raf) { window.cancelAnimationFrame(this.raf); }
    };

    Defileur.prototype.distance = function () {
        return this.fil.scrollHeight - this.fil.scrollTop - this.fil.clientHeight;
    };

    Defileur.prototype.echapper = function () {
        if (this.echappe) { return; }
        this.echappe = true;
        if (this.raf) { window.cancelAnimationFrame(this.raf); this.raf = null; }
        this.signaler();
    };

    Defileur.prototype.defilement = function () {
        var haut = this.fil.scrollTop;
        var programme = this.attendu !== null && Math.abs(haut - this.attendu) < 2;
        if (!programme) {
            // Une remontée collée au bas n'est pas un geste : c'est le navigateur qui
            // ramène scrollTop quand le contenu raccourcit (étapes repliées, texte recomposé).
            if (haut < this.dernierHaut - 1 && this.distance() > 2) {
                this.echapper();
            } else if (this.distance() <= SEUIL_BAS) {
                this.echappe = false;
            }
        }
        this.dernierHaut = haut;
        this.signaler();
    };

    Defileur.prototype.signaler = function () {
        var pres = this.distance() <= SEUIL_BAS;
        if (pres !== this.presBas || this.echappe !== this.dernierEchappe) {
            this.presBas = pres;
            this.dernierEchappe = this.echappe;
            this.surEtat(pres, this.echappe);
        }
    };

    Defileur.prototype.changement = function () {
        if (!this.echappe) { this.versBas(); }
        this.signaler();
    };

    Defileur.prototype.poser = function (haut) {
        this.fil.scrollTop = haut;
        this.attendu = this.fil.scrollTop;
        this.dernierHaut = this.attendu;
    };

    /** Anime vers le bas (ou vers `cible`) ; instantané si le mouvement est réduit. */
    Defileur.prototype.versBas = function (instantane, cible) {
        var self = this;
        var visee = function () {
            var max = self.fil.scrollHeight - self.fil.clientHeight;
            return cible === undefined ? max : Math.min(cible, max);
        };
        if (instantane || mouvementReduit()) {
            this.poser(visee());
            this.signaler();
            return;
        }
        if (this.raf) { return; }
        var pas = function () {
            self.raf = null;
            if (self.echappe) { return; }
            var but = visee();
            var ecart = but - self.fil.scrollTop;
            if (Math.abs(ecart) < 1) { self.poser(but); self.signaler(); return; }
            var avance = ecart * 0.24;
            if (Math.abs(avance) < 2) { avance = ecart > 0 ? Math.min(2, ecart) : Math.max(-2, ecart); }
            self.poser(self.fil.scrollTop + avance);
            self.raf = window.requestAnimationFrame(pas);
        };
        this.raf = window.requestAnimationFrame(pas);
    };

    Defileur.prototype.suivre = function (instantane) {
        this.echappe = false;
        this.versBas(instantane);
        this.signaler();
    };

    /**
     * À l'envoi : la question se place en haut (64 px du tour précédent restent
     * visibles), la réponse grandit dessous. Un espace réservé donne la hauteur
     * nécessaire ; le suivi du bas ne reprend que lorsque la réponse déborde.
     */
    Defileur.prototype.ancrer = function (question, reponse) {
        Array.prototype.forEach.call(this.contenu.querySelectorAll('[data-ast-spacer]'), function (n) {
            n.style.minHeight = '';
            n.removeAttribute('data-ast-spacer');
        });
        if (!question || !reponse) { this.suivre(); return; }
        var boiteFil = this.fil.getBoundingClientRect();
        var hautQuestion = question.getBoundingClientRect().top - boiteFil.top + this.fil.scrollTop;
        var cible = Math.max(0, hautQuestion - 64);
        var manque = cible + this.fil.clientHeight - this.fil.scrollHeight;
        if (manque > 0) {
            reponse.style.minHeight = (reponse.offsetHeight + manque) + 'px';
            reponse.setAttribute('data-ast-spacer', '');
        }
        this.echappe = false;
        this.versBas(false);
    };

    // ─── Composant Alpine ───

    window.klassciAssistant = function () {
        // Hors de l'état réactif : vues des messages et défileur (objets tenant des nœuds).
        var vues = new Map();
        var defileur = null;
        var relectureLibs = false;

        function vueDe(msg) { return vues.get(msg.key) || null; }

        return {
            ouvert: false,
            large: false,
            vue: 'chat',
            cfg: { routes: {}, suggestions: [], prenom: '', maxLength: 1000, modeles: [] },
            modeleChoisi: null,
            menuModele: false,
            messages: [],
            saisie: '',
            envoiEnCours: false,
            controleur: null,
            conversationId: null,
            conversations: [],
            chargementListe: false,
            chargementHistorique: false,
            suivreBas: true,
            nonLu: false,
            libsPretes: false,
            annonce: '',
            erreurSaisie: '',
            prefs: null,
            prefsEtat: '',
            _mq: null,
            _onMq: null,

            init: function () {
                var noeud = this.$root.querySelector('script[data-ast-config]');
                try {
                    this.cfg = Object.assign(this.cfg, JSON.parse(noeud ? noeud.textContent : '{}'));
                } catch (e) { /* configuration absente : l'assistant reste inerte */ }
                this.modeleChoisi = (this.cfg.modeles && this.cfg.modeles.defaut) || null;
                try {
                    this.conversationId = window.localStorage.getItem(CLE_CONVERSATION) || null;
                } catch (e) { this.conversationId = null; }

                var self = this;
                this._mq = window.matchMedia('(max-width: 767.98px)');
                this._onMq = function () { self.verrouillerDefilement(); };
                if (this._mq.addEventListener) { this._mq.addEventListener('change', this._onMq); }
                this.$nextTick(function () { self.brancherDefileur(); });
            },

            destroy: function () {
                if (this._mq && this._mq.removeEventListener) { this._mq.removeEventListener('change', this._onMq); }
                if (this.controleur) { this.controleur.abort(); }
                if (defileur) { defileur.detruire(); defileur = null; }
                vues.forEach(function (v) { v.detruire(); });
                vues.clear();
                document.documentElement.classList.remove('ast-lock');
            },

            brancherDefileur: function () {
                if (defileur || !this.$refs.fil || !this.$refs.filContenu) { return; }
                var self = this;
                defileur = new Defileur(this.$refs.fil, this.$refs.filContenu, function (presBas, echappe) {
                    self.suivreBas = presBas;
                    self.nonLu = !presBas && echappe && self.envoiEnCours;
                });
            },

            // ─── Ouverture / fermeture ───

            ouvrir: function () {
                var self = this;
                this.ouvert = true;
                this.verrouillerDefilement();
                chargerLibs().then(function () {
                    self.libsPretes = true;
                    if (!relectureLibs) {
                        relectureLibs = true;
                        vues.forEach(function (v) { v.relire(); });
                    }
                }).catch(function () { /* texte échappé en repli */ });
                if (this.conversationId && this.messages.length === 0) {
                    this.chargerConversation(this.conversationId, true);
                }
                this.$nextTick(function () {
                    self.brancherDefileur();
                    if (self.$refs.saisie) { self.$refs.saisie.focus(); }
                    self.defiler(true);
                });
            },

            fermer: function () {
                this.ouvert = false;
                this.menuModele = false;
                this.verrouillerDefilement();
                var self = this;
                this.$nextTick(function () { if (self.$refs.lanceur) { self.$refs.lanceur.focus(); } });
            },

            surEchap: function () {
                if (!this.ouvert) { return; }
                if (this.menuModele) { this.menuModele = false; return; }
                if (this.vue !== 'chat') { this.vue = 'chat'; return; }
                this.fermer();
            },

            basculerTaille: function () {
                this.large = !this.large;
                var self = this;
                this.$nextTick(function () { if (defileur && !defileur.echappe) { defileur.versBas(true); } self.ajusterHauteur(); });
            },

            verrouillerDefilement: function () {
                var telephone = this._mq && this._mq.matches;
                document.documentElement.classList.toggle('ast-lock', this.ouvert && telephone);
            },

            // ─── Rendu ───

            /** Monte la vue d'un message dans son emplacement (x-init du gabarit). */
            monter: function (hote, msg) {
                var v = vueDe(msg);
                if (v) { v.monter(hote); }
            },

            creerVue: function (msg) {
                var v = new Vue(msg, this);
                vues.set(msg.key, v);
                return v;
            },

            oublier: function (liste) {
                (liste || []).forEach(function (m) {
                    var v = vues.get(m.key);
                    if (v) { v.detruire(); vues.delete(m.key); }
                });
            },

            urlSure: urlSure,

            lienMessage: function (msg) {
                if (msg.lienWidget || enCoursStatut(msg)) { return null; }
                for (var i = 0; i < msg.liens.length; i += 1) {
                    var u = urlSure(msg.liens[i]);
                    if (u) { return u; }
                }
                return null;
            },

            suites: function (msg) {
                return msg.suites || { questions: [], actions: [] };
            },

            estDernierAssistant: function (msg) {
                for (var i = this.messages.length - 1; i >= 0; i -= 1) {
                    if (this.messages[i].role === 'assistant') { return this.messages[i] === msg; }
                }
                return false;
            },

            texteDe: function (msg) {
                var v = vueDe(msg);
                return v ? v.markdown() : '';
            },

            enCours: function (msg) {
                return enCoursStatut(msg);
            },

            nouveauMessageAssistant: function (question, etat) {
                this.messages.push({
                    key: uid('m'), role: 'assistant', status: etat || 'streaming', question: question || null,
                    copie: false, erreur: '', aTexte: false, lienWidget: false, liens: [],
                    suites: { questions: [], actions: [] }
                });
                var msg = this.messages[this.messages.length - 1];
                this.creerVue(msg);
                return msg;
            },

            // ─── Défilement ───

            defiler: function (forcer) {
                if (!defileur) { return; }
                if (forcer) { defileur.suivre(true); } else if (!defileur.echappe) { defileur.versBas(); }
            },

            allerEnBas: function () {
                if (defileur) { defileur.suivre(false); }
            },

            // ─── Saisie ───

            ajusterHauteur: function () {
                var t = this.$refs.saisie;
                if (!t) { return; }
                t.style.height = 'auto';
                t.style.height = Math.min(t.scrollHeight, 200) + 'px';
            },

            surTouche: function (ev) {
                if (ev.key === 'Enter' && !ev.shiftKey && !ev.isComposing) {
                    ev.preventDefault();
                    this.envoyer();
                }
            },

            proposer: function (question) {
                this.envoyer(question);
            },

            // ─── Envoi et diffusion ───

            envoyer: function (texteImpose, options) {
                var opts = options || {};
                var texte = String(texteImpose !== undefined ? texteImpose : this.saisie).trim();
                if (!texte || this.envoiEnCours) { return; }
                if (texte.length > this.cfg.maxLength) {
                    this.erreurSaisie = 'Message trop long : ' + this.cfg.maxLength + ' caractères au plus.';
                    return;
                }
                this.erreurSaisie = '';
                this.vue = 'chat';
                this.menuModele = false;

                var cleQuestion = null;
                if (!opts.relance) {
                    cleQuestion = uid('m');
                    this.messages.push({ key: cleQuestion, role: 'user', text: texte });
                } else {
                    for (var i = this.messages.length - 1; i >= 0; i -= 1) {
                        if (this.messages[i].role === 'user') { cleQuestion = this.messages[i].key; break; }
                    }
                }
                if (texteImpose === undefined) {
                    this.saisie = '';
                    this.$nextTick(this.ajusterHauteur.bind(this));
                }
                var msg = this.nouveauMessageAssistant(texte);
                vueDe(msg).attendre();

                this.envoiEnCours = true;
                var self = this;
                this.$nextTick(function () {
                    self.brancherDefileur();
                    if (!defileur) { return; }
                    var fil = self.$refs.filContenu;
                    var q = cleQuestion ? fil.querySelector('[data-key="' + cleQuestion + '"]') : null;
                    var r = fil.querySelector('[data-key="' + msg.key + '"]');
                    defileur.ancrer(q, r);
                });

                var diffusion = typeof window.ReadableStream === 'function' && typeof window.TextDecoder === 'function';
                var promesse = diffusion ? this.diffuser(texte, msg) : this.envoyerSansDiffusion(texte, msg);

                promesse.catch(function (e) {
                    if (e && e.name === 'AbortError') {
                        self.clore(msg, true);
                        msg.status = 'stopped';
                        return;
                    }
                    self.clore(msg, true);
                    msg.erreur = (e && e.messageUtilisateur) || 'La connexion a été interrompue.';
                    msg.status = 'error';
                }).finally(function () {
                    // Flux fermé sans « finish » ni « error » (délai du serveur, coupure du
                    // proxy) : ne jamais laisser une réponse muette passer pour aboutie.
                    if (msg.status === 'streaming') {
                        self.clore(msg, true);
                        msg.erreur = 'La réponse a été interrompue avant la fin. Réessayez.';
                        msg.status = 'error';
                    }
                    self.envoiEnCours = false;
                    self.nonLu = false;
                    self.controleur = null;
                    self.annonce = msg.status === 'error' ? 'Erreur : la réponse n\'a pas abouti.' : self.texteDe(msg).slice(0, 600);
                    self.$nextTick(function () { if (self.$refs.saisie && self.ouvert) { self.$refs.saisie.focus(); } });
                });
            },

            libelleModele: function () {
                var liste = (this.cfg.modeles && this.cfg.modeles.liste) || [];
                var choisi = this.modeleChoisi;
                var m = liste.find(function (x) { return x.cle === choisi; });
                return m ? m.libelle : 'Modèle';
            },

            choisirModele: function (cle) {
                this.modeleChoisi = cle;
                this.menuModele = false;
                if (this.$refs.saisie) { this.$refs.saisie.focus(); }
            },

            corpsRequete: function (texte) {
                // Le modèle n'est envoyé que si le sélecteur est proposé (permission assistant.model.choose).
                var avecChoix = this.cfg.modeles && this.cfg.modeles.liste && this.modeleChoisi;
                return JSON.stringify({
                    modele: avecChoix ? this.modeleChoisi : undefined,
                    message: texte,
                    conversation_id: this.conversationId,
                    current_url: window.location.href.slice(0, 2048),
                    current_path: window.location.pathname.slice(0, 1024),
                    page_title: document.title.slice(0, 255)
                });
            },

            enTetes: function (accept) {
                return {
                    'Content-Type': 'application/json',
                    'Accept': accept,
                    'X-CSRF-TOKEN': this.cfg.csrfToken || '',
                    'X-Requested-With': 'XMLHttpRequest'
                };
            },

            erreurHttp: function (statut) {
                var texte = 'La réponse n\'a pas pu aboutir. Réessayez.';
                if (statut === 419) { texte = 'Votre session a expiré. Rechargez la page.'; }
                if (statut === 429) { texte = 'Trop de messages en peu de temps. Patientez une minute.'; }
                if (statut === 401) { texte = 'Vous avez été déconnecté. Rechargez la page.'; }
                var e = new Error(texte);
                e.messageUtilisateur = texte;
                return e;
            },

            diffuser: function (texte, msg) {
                var self = this;
                var controleur = new AbortController();
                this.controleur = controleur;

                return fetch(this.cfg.routes.messageStream, {
                    method: 'POST',
                    headers: this.enTetes('text/event-stream'),
                    credentials: 'same-origin',
                    body: this.corpsRequete(texte),
                    signal: controleur.signal
                }).then(function (res) {
                    var type = res.headers.get('Content-Type') || '';
                    if (!res.ok || !res.body || type.indexOf('text/event-stream') !== 0) {
                        throw self.erreurHttp(res.status);
                    }
                    return self.lireFlux(res.body.getReader(), msg);
                });
            },

            lireFlux: function (lecteur, msg) {
                var self = this;
                var decodeur = new TextDecoder();
                var tampon = '';

                function traiterBloc(bloc) {
                    bloc.split('\n').forEach(function (ligne) {
                        if (ligne.indexOf('data:') !== 0) { return; }
                        var donnee = ligne.slice(5).trim();
                        if (donnee === '' || donnee === '[DONE]') { return; }
                        var partie;
                        try { partie = JSON.parse(donnee); } catch (e) { return; }
                        self.appliquerPartie(msg, partie);
                    });
                }

                function lire() {
                    return lecteur.read().then(function (r) {
                        if (r.done) {
                            tampon += decodeur.decode();
                            if (tampon.trim()) { traiterBloc(tampon); }
                            return;
                        }
                        tampon += decodeur.decode(r.value, { stream: true }).replace(/\r\n/g, '\n');
                        var idx = tampon.indexOf('\n\n');
                        while (idx >= 0) {
                            traiterBloc(tampon.slice(0, idx));
                            tampon = tampon.slice(idx + 2);
                            idx = tampon.indexOf('\n\n');
                        }
                        return lire();
                    });
                }

                return lire();
            },

            /** Applique une partie du protocole UI message stream v1 au message. */
            appliquerPartie: function (msg, partie) {
                var type = partie.type || '';
                var meta = partie.messageMetadata || null;
                var v = vueDe(msg);
                if (!v) { return; }

                if (meta && meta.conversationId) {
                    this.memoriserConversation(meta.conversationId, meta.title);
                }

                if (type === 'text-start') {
                    v.texteDebut(partie.id);
                } else if (type === 'text-delta') {
                    v.texteDelta(partie.id, partie.delta || '');
                } else if (type === 'text-end') {
                    v.texteFin(partie.id);
                } else if (type === 'data-etape') {
                    v.etape(partie.id, partie.data || {});
                } else if (type === 'data-outil') {
                    // Ancienne puce d'outil : même forme qu'une étape (libelle, etat).
                    v.etape(partie.id, partie.data || {});
                } else if (type === 'data-widget') {
                    var d = partie.data || {};
                    v.widget(partie.id, d.kind, d);
                } else if (type === 'data-lien') {
                    if (partie.data && partie.data.url) { v.lien(partie.data.url); }
                } else if (type === 'data-suites') {
                    v.suites(partie.data || {});
                } else if (type.indexOf('data-') === 0) {
                    // Ancien format : data-<kind> porte directement le display_data.
                    v.widget(partie.id || null, type.slice(5), partie.data || {});
                } else if (type === 'error') {
                    this.clore(msg, true);
                    msg.erreur = partie.errorText || 'La réponse n\'a pas pu aboutir.';
                    msg.status = 'error';
                } else if (type === 'abort') {
                    this.clore(msg, true);
                    msg.status = 'stopped';
                } else if (type === 'finish') {
                    this.clore(msg, false);
                    if (msg.status === 'streaming') { msg.status = 'done'; }
                }
            },

            clore: function (msg, interrompu) {
                var v = vueDe(msg);
                if (v) { v.terminer(interrompu); }
            },

            envoyerSansDiffusion: function (texte, msg) {
                var self = this;
                return fetch(this.cfg.routes.message, {
                    method: 'POST',
                    headers: this.enTetes('application/json'),
                    credentials: 'same-origin',
                    body: this.corpsRequete(texte)
                }).then(function (res) {
                    if (!res.ok) { throw self.erreurHttp(res.status); }
                    return res.json();
                }).then(function (json) {
                    if (!json.success) {
                        var e = new Error(json.message);
                        e.messageUtilisateur = json.message;
                        throw e;
                    }
                    if (json.conversation_id) { self.memoriserConversation(json.conversation_id); }
                    vueDe(msg).chargerLegacy({ content: json.message, display_type: json.display_type, display_data: json.display_data, deep_link: json.deep_link });
                    msg.status = 'done';
                });
            },

            arreter: function () {
                if (this.controleur) { this.controleur.abort(); }
            },

            relancer: function (msg) {
                if (this.envoiEnCours) { return; }
                var question = msg.question;
                if (!question) {
                    var idx = this.messages.indexOf(msg);
                    for (var i = idx - 1; i >= 0; i -= 1) {
                        if (this.messages[i].role === 'user') { question = this.messages[i].text; break; }
                    }
                }
                if (!question) { return; }
                this.oublier([msg]);
                this.messages.splice(this.messages.indexOf(msg), 1);
                this.envoyer(question, { relance: true });
            },

            copier: function (msg) {
                var texte = this.texteDe(msg);
                if (!texte) { return; }
                copierTexte(texte).then(function () {
                    msg.copie = true;
                    setTimeout(function () { msg.copie = false; }, 1600);
                }).catch(function () { /* presse-papiers refusé */ });
            },

            // ─── Conversations ───

            memoriserConversation: function (id, titre) {
                this.conversationId = id;
                try { window.localStorage.setItem(CLE_CONVERSATION, id); } catch (e) { /* stockage indisponible */ }
                if (titre) {
                    var c = this.conversations.find(function (x) { return x.id === id; });
                    if (c) { c.title = titre; }
                }
            },

            nouvelleConversation: function () {
                if (this.controleur) { this.controleur.abort(); }
                this.oublier(this.messages);
                this.messages = [];
                this.conversationId = null;
                try { window.localStorage.removeItem(CLE_CONVERSATION); } catch (e) { /* ignore */ }
                this.vue = 'chat';
                var self = this;
                this.$nextTick(function () { if (self.$refs.saisie) { self.$refs.saisie.focus(); } });
            },

            ouvrirHistorique: function () {
                var self = this;
                this.vue = 'historique';
                this.chargementListe = true;
                this.getJson(this.cfg.routes.conversations).then(function (json) {
                    self.conversations = (json && json.conversations) || [];
                }).catch(function () {
                    self.conversations = [];
                }).finally(function () { self.chargementListe = false; });
            },

            chargerConversation: function (id, silencieux) {
                var self = this;
                this.chargementHistorique = true;
                if (this.controleur) { this.controleur.abort(); }
                return this.getJson(this.cfg.routes.history.replace('__ID__', encodeURIComponent(id))).then(function (json) {
                    self.oublier(self.messages);
                    self.messages = [];
                    ((json && json.messages) || []).forEach(function (m) {
                        if (m.role === 'user') {
                            self.messages.push({ key: uid('m'), role: 'user', text: m.content || '' });
                            return;
                        }
                        var msg = self.nouveauMessageAssistant(null, 'done');
                        var v = vueDe(msg);
                        if (Array.isArray(m.parties) && m.parties.length) {
                            v.chargerParties(m.parties);
                        } else {
                            v.chargerLegacy(m);
                        }
                    });
                    self.memoriserConversation(id);
                    self.vue = 'chat';
                    self.$nextTick(function () { self.defiler(true); });
                }).catch(function () {
                    if (silencieux) {
                        // Conversation disparue (supprimée, autre compte) : on repart d'une page blanche.
                        self.conversationId = null;
                        try { window.localStorage.removeItem(CLE_CONVERSATION); } catch (e) { /* ignore */ }
                    }
                }).finally(function () { self.chargementHistorique = false; });
            },

            supprimerConversation: function (conv) {
                var self = this;
                fetch(this.cfg.routes.delete.replace('__ID__', encodeURIComponent(conv.id)), {
                    method: 'DELETE',
                    headers: this.enTetes('application/json'),
                    credentials: 'same-origin'
                }).then(function (res) {
                    if (!res.ok) { return; }
                    self.conversations = self.conversations.filter(function (c) { return c.id !== conv.id; });
                    if (self.conversationId === conv.id) { self.nouvelleConversation(); self.vue = 'historique'; }
                });
            },

            getJson: function (url) {
                return fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
                    .then(function (res) {
                        if (!res.ok) { throw new Error(String(res.status)); }
                        return res.json();
                    });
            },

            postJson: function (url, corps, methode) {
                var self = this;
                return fetch(url, {
                    method: methode || 'POST',
                    headers: this.enTetes('application/json'),
                    credentials: 'same-origin',
                    body: JSON.stringify(corps || {})
                }).then(function (res) {
                    return res.json().catch(function () { return {}; }).then(function (json) {
                        return { statut: res.status, ok: res.ok && json.success !== false, json: json, message: json.message || (res.ok ? '' : self.erreurHttp(res.status).messageUtilisateur) };
                    });
                });
            },

            ajouterReponse: function (json) {
                if (!json || (!json.message && !json.display_data)) { return; }
                if (json.conversation_id) { this.memoriserConversation(json.conversation_id); }
                var msg = this.nouveauMessageAssistant(null, 'done');
                vueDe(msg).chargerLegacy({ content: json.message, display_type: json.display_type, display_data: json.display_data, deep_link: json.deep_link });
                this.$nextTick(this.allerEnBas.bind(this));
            },

            // ─── Actions des résultats (formulaires, validations, raccourcis) ───

            posterFormulaire: function (url, corps) {
                var self = this;
                if (!envoiSur(url)) { return Promise.resolve({ ok: false, message: 'Formulaire indisponible.' }); }
                return this.postJson(url, corps).then(function (r) {
                    if (r.ok) { self.ajouterReponse(r.json); return { ok: true }; }
                    var detail = r.json && r.json.errors ? Object.values(r.json.errors)[0] : null;
                    return { ok: false, message: (Array.isArray(detail) ? detail[0] : detail) || r.message || 'Envoi impossible.' };
                }).catch(function () { return { ok: false, message: 'Connexion interrompue.' }; });
            },

            deciderAction: function (url, approuver) {
                var self = this;
                if (!envoiSur(url)) { return Promise.resolve({ ok: false, message: 'Action indisponible.' }); }
                return this.postJson(url, {}).then(function (r) {
                    if (r.ok && approuver && r.json.display_data) {
                        self.ajouterReponse(r.json);
                    }
                    return { ok: r.ok, message: r.message || (approuver ? 'Action exécutée.' : 'Action rejetée.') };
                }).catch(function () { return { ok: false, message: 'Connexion interrompue.' }; });
            },

            lancerAction: function (action, msg) {
                var self = this;
                var routes = this.cfg.routes;
                if (action.action === 'save_preferred_name') {
                    this.postJson(routes.preferencesMemory, { type: 'preferred_name', value: action.value }).then(function (r) {
                        action.fait = r.ok;
                        self.annonce = r.ok ? 'Nom enregistré.' : 'Enregistrement impossible.';
                    });
                    return;
                }
                if (action.action === 'open_form') {
                    var cible = String(action.value || '');
                    var params = new URLSearchParams();
                    var url = null;
                    if (cible.indexOf('frais_config') === 0) {
                        url = routes.formFraisConfig;
                        if (cible.indexOf(':') > 0) { params.set('category_id', cible.split(':')[1]); }
                    } else if (cible === 'frais_category') {
                        url = routes.formFraisCategory;
                    } else if (cible.indexOf('inscriptions_filter') === 0) {
                        url = routes.formInscriptionsFilter;
                        if (cible.indexOf(':') > 0) { params.set('focus_field', cible.split(':')[1]); }
                    }
                    if (!url) { return; }
                    if (this.conversationId) { params.set('conversation_id', this.conversationId); }
                    this.getJson(url + (params.toString() ? '?' + params.toString() : '')).then(function (json) {
                        self.ajouterReponse(json);
                    }).catch(function () {
                        msg.erreur = "Ce formulaire n'est pas disponible.";
                    });
                }
            },

            // ─── Préférences ───

            ouvrirPreferences: function () {
                var self = this;
                this.vue = 'preferences';
                this.prefsEtat = '';
                this.getJson(this.cfg.routes.preferences).then(function (json) {
                    var p = (json && json.preferences) || {};
                    self.prefs = {
                        preferred_name: p.preferred_name || '',
                        response_style: p.response_style || 'standard',
                        response_tone: p.response_tone || 'pedagogique',
                        clarification_mode: p.clarification_mode || 'auto',
                        notes: p.notes || ''
                    };
                }).catch(function () { self.prefsEtat = 'Préférences indisponibles.'; });
            },

            enregistrerPreferences: function () {
                var self = this;
                if (!this.prefs) { return; }
                this.prefsEtat = 'Enregistrement…';
                this.postJson(this.cfg.routes.preferencesUpdate, this.prefs, 'PUT').then(function (r) {
                    self.prefsEtat = r.ok ? 'Préférences enregistrées.' : (r.message || 'Enregistrement impossible.');
                }).catch(function () { self.prefsEtat = 'Connexion interrompue.'; });
            }
        };
    };

    function enCoursStatut(msg) {
        return msg.status === 'streaming';
    }

    // Exposé pour les tests : fonctions pures du rendu diffusé.
    window.klassciAssistant.interne = { remend: remend, csv: csv, ton: ton, duree: duree, versParties: versParties };
})();

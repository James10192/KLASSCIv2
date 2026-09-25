/*
 * Assistant KLASSCI — Chargement des bibliothèques, petits outils et sécurité du rendu (liens, DOMPurify, SVG, mermaid).
 *
 * Fichier 1/5 de public/js/assistant/, chargés dans l'ordre par le
 * composant resources/views/components/chatbot/assistant.blade.php. Les
 * fichiers partagent l'espace window.KlassciAst.
 *
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
(function (A) {
    'use strict';

    if (A.noyau) {
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

    /*
     * Le texte du modèle n'est pas fiable : une injection peut arriver par une
     * donnée qu'un outil a lue. Seules les balises que marked produit passent,
     * sans style (url() déclencherait une requête vers un tiers à chaque rendu,
     * position:fixed permettrait de maquiller l'écran), sans id ni data-*, et
     * une classe n'est gardée que si c'est la langue d'un bloc de code.
     */
    var PURIFY = {
        ALLOWED_TAGS: ['p', 'br', 'strong', 'em', 'del', 's', 'code', 'pre', 'blockquote', 'ul', 'ol', 'li',
            'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'hr', 'table', 'thead', 'tbody', 'tr', 'th', 'td', 'a'],
        ALLOWED_ATTR: ['href', 'title', 'class', 'align', 'start'],
        FORBID_ATTR: ['style', 'id'],
        ALLOW_DATA_ATTR: false,
        ALLOW_ARIA_ATTR: false
    };
    var CLASSE_PERMISE = /^language-[\w-]+$/;

    /*
     * Diagrammes : le SVG de mermaid garde ses classes et sa feuille de style,
     * mais perd tout lien et tout objet qui charge une ressource.
     */
    var PURIFY_SVG = {
        USE_PROFILES: { svg: true, svgFilters: true },
        FORBID_TAGS: ['a', 'foreignObject', 'image', 'use', 'script', 'animate', 'set'],
        FORBID_ATTR: ['href', 'xlink:href'],
        ALLOW_DATA_ATTR: false,
        RETURN_DOM_FRAGMENT: true
    };

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
    // Profil du nettoyage en cours : le crochet DOMPurify est global à la bibliothèque.
    var profilEnCours = null;

    function chargerLibs() {
        if (chargementBase) { return chargementBase; }
        chargementBase = Promise.all(BASE.map(chargerScript)).then(function () {
            if (window.DOMPurify && !window.__astPurifyHook) {
                window.__astPurifyHook = true;
                window.DOMPurify.addHook('afterSanitizeAttributes', function (node) {
                    // SVG et HTML : nodeName est « a » en SVG, « A » en HTML.
                    var nom = String(node.nodeName || '').toUpperCase();
                    if (nom === 'A') {
                        // Un lien hors de l'application perd son href : versNoeuds le
                        // remplace ensuite par son seul texte.
                        var sure = urlSure(node.getAttribute('href'));
                        node.removeAttribute('target');
                        node.removeAttribute('xlink:href');
                        if (sure) {
                            node.setAttribute('href', sure);
                        } else {
                            node.removeAttribute('href');
                        }
                    }
                    if (profilEnCours === 'html' && node.hasAttribute && node.hasAttribute('class')) {
                        var garde = String(node.getAttribute('class')).split(/\s+/).filter(function (c) { return CLASSE_PERMISE.test(c); });
                        if (garde.length) { node.setAttribute('class', garde.join(' ')); } else { node.removeAttribute('class'); }
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
                // Un tableur exécuterait une cellule qui commence par = + - @.
                if (/^[=+\-@\t\r]/.test(s)) { s = "'" + s; }
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

    // ─── Sécurité du rendu ───

    /** Markdown → balisage sûr : nettoyé par DOMPurify, ou échappé tant qu'il n'est pas chargé. */
    function htmlSur(texte) {
        if (window.marked && window.DOMPurify) {
            var html = window.marked.parse(String(texte || ''), { gfm: true, breaks: true, async: false });
            profilEnCours = 'html';
            try {
                return window.DOMPurify.sanitize(html, PURIFY);
            } finally {
                profilEnCours = null;
            }
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

    /** Une feuille de style ne charge rien : ni url(), ni @import. */
    function cssSansRessource(css) {
        return String(css || '').replace(/@import[^;]*;?/gi, '').replace(/url\s*\(/gi, 'none(').replace(/expression\s*\(/gi, 'none(');
    }

    function svgSur(svg) {
        if (!window.DOMPurify) { return null; }
        profilEnCours = 'svg';
        var frag;
        try {
            frag = window.DOMPurify.sanitize(svg, PURIFY_SVG);
        } finally {
            profilEnCours = null;
        }
        if (!frag || !frag.firstChild) { return null; }
        Array.prototype.forEach.call(frag.querySelectorAll('style'), function (st) { st.textContent = cssSansRessource(st.textContent); });
        Array.prototype.forEach.call(frag.querySelectorAll('[style]'), function (n) { n.setAttribute('style', cssSansRessource(n.getAttribute('style'))); });
        return frag;
    }

    /**
     * Un bloc mermaid du texte libre n'a traversé aucun contrôle serveur. Sont
     * retirés : l'en-tête de configuration, les directives %%{…}%% (qui
     * réécrivent la configuration), et les instructions click / link / links /
     * callback (liens et rappels), y compris quand elles suivent un « ; ».
     */
    var INSTRUCTION_INTERDITE = /^\s*(click|links?|callback)\b/i;

    function sourceMermaidSure(source) {
        var texte = String(source || '').replace(/^\s*---\r?\n[\s\S]*?\r?\n---\s*(\r?\n|$)/, '');
        return texte.split(/\r?\n/).filter(function (ligne) {
            return ligne.indexOf('%%{') === -1;
        }).map(function (ligne) {
            return ligne.split(';').filter(function (instr) { return !INSTRUCTION_INTERDITE.test(instr); }).join(';');
        }).join('\n');
    }

    /** mermaid laisse ses nœuds d'erreur sous <body> quand un rendu échoue. */
    function retirerRestesMermaid(id) {
        ['d' + id, id].forEach(function (cle) {
            var n = document.getElementById(cle);
            if (n && !n.closest('.ast-panel')) { n.remove(); }
        });
    }

    A.CLE_CONVERSATION = CLE_CONVERSATION;
    A.SEUIL_BAS = SEUIL_BAS;
    A.PALETTE = PALETTE;
    A.LIB_GRAPHIQUE = LIB_GRAPHIQUE;
    A.LIB_CODE = LIB_CODE;
    A.chargerScript = chargerScript;
    A.chargerLibs = chargerLibs;
    A.chargerMermaid = chargerMermaid;
    A.uid = uid;
    A.mouvementReduit = mouvementReduit;
    A.hachage = hachage;
    A.urlSure = urlSure;
    A.envoiSur = envoiSur;
    A.classeIcone = classeIcone;
    A.el = el;
    A.icone = icone;
    A.bouton = bouton;
    A.ancre = ancre;
    A.lien = lien;
    A.FORMAT_COMPACT = FORMAT_COMPACT;
    A.nombre = nombre;
    A.montant = montant;
    A.pluriel = pluriel;
    A.duree = duree;
    A.copierTexte = copierTexte;
    A.confirmerCopie = confirmerCopie;
    A.telecharger = telecharger;
    A.csv = csv;
    A.badge = badge;
    A.initiales = initiales;
    A.htmlSur = htmlSur;
    A.versNoeuds = versNoeuds;
    A.svgSur = svgSur;
    A.sourceMermaidSure = sourceMermaidSure;
    A.retirerRestesMermaid = retirerRestesMermaid;
    A.noyau = true;
})(window.KlassciAst = window.KlassciAst || {});

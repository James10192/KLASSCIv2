{{--
    Palette de recherche « Spotlight » : Ctrl K (Windows, Linux), ⌘ K (Mac), ou « / »
    hors d'un champ de saisie. Elle s'ouvre aussi depuis tout élément portant
    data-spl-ouvrir (bouton de la barre du haut, champ de la feuille m-navbar-plus)
    ou par l'évènement window « klassci-spotlight:ouvrir » (detail.saisie facultatif).

    Ce qu'elle montre est filtré CÔTÉ SERVEUR par les permissions et par la porte
    de chaque route (SearchController, App\Support\Recherche) : ce fichier n'en
    décide rien. Tout texte est posé par x-text, jamais par innerHTML — un nom
    d'étudiant ou un numéro de reçu ne peut donc pas injecter de balise.

    Espace de noms CSS : spl-*.
--}}
<style>
    .spl-root { position: fixed; inset: 0; z-index: 1400; }
    .spl-root[x-cloak] { display: none !important; }
    .spl-voile {
        position: absolute; inset: 0;
        background: rgba(15, 23, 42, .45);
        -webkit-backdrop-filter: blur(3px);
        backdrop-filter: blur(3px);
    }
    .spl-panneau {
        position: relative;
        margin: 12vh auto 0;
        width: calc(100% - 32px);
        max-width: 640px;
        max-height: 72vh;
        display: flex;
        flex-direction: column;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 18px;
        box-shadow: 0 24px 64px rgba(15, 23, 42, .22), 0 8px 24px rgba(4, 83, 203, .10), 0 1px 3px rgba(15, 23, 42, .08);
        overflow: hidden;
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
        color: #0f172a;
    }
    .spl-entre { transition: opacity .16s ease, transform .16s ease; }
    .spl-entre-debut { opacity: 0; transform: translateY(-6px) scale(.985); }
    .spl-entre-fin { opacity: 1; transform: none; }
    .spl-voile-entre { transition: opacity .16s ease; }
    .spl-voile-debut { opacity: 0; }
    .spl-voile-fin { opacity: 1; }

    /* ---- Champ ---- */
    .spl-champ { position: relative; flex-shrink: 0; border-bottom: 1px solid #eef2f7; }
    .spl-champ-ico {
        position: absolute; left: 20px; top: 50%; transform: translateY(-50%);
        color: #0453cb; font-size: 17px; pointer-events: none;
    }
    .spl-champ input {
        display: block;
        width: 100%;
        height: 60px;
        /* 20 + 17 + 15 à gauche ; à droite la croix (28) et « Esc » (40) + marges :
           le texte ne passe jamais dessous. */
        padding: 0 104px 0 52px;
        border: 0;
        background: transparent;
        font-size: 17px;
        color: #0f172a;
        outline: none;
    }
    .spl-champ input::placeholder { color: #94a3b8; }
    .spl-champ input::-webkit-search-cancel-button { display: none; }
    .spl-actions {
        position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
        display: flex; align-items: center; gap: 6px;
    }
    .spl-effacer {
        width: 28px; height: 28px;
        display: inline-flex; align-items: center; justify-content: center;
        border: 0; border-radius: 8px;
        background: #f1f5f9; color: #64748b; font-size: 13px;
        cursor: pointer;
        transition: background-color .15s ease, color .15s ease;
    }
    .spl-effacer:hover { background: #e2e8f0; color: #0f172a; }
    .spl-esc {
        height: 24px; min-width: 40px; padding: 0 7px;
        border: 1px solid #e2e8f0; border-radius: 6px;
        background: #fff; color: #64748b;
        font: 600 11px/1 'Inter', system-ui, sans-serif;
        box-shadow: 0 1px 0 #e2e8f0;
        cursor: pointer;
    }
    .spl-esc:hover { color: #0453cb; border-color: #c7d4e5; }
    .spl-effacer:focus-visible, .spl-esc:focus-visible, .spl-pied a:focus-visible {
        outline: none; box-shadow: 0 0 0 3px rgba(4, 83, 203, .2);
    }

    /* ---- Corps ---- */
    .spl-corps { flex: 1; min-height: 0; overflow-y: auto; overscroll-behavior: contain; padding: 6px 8px 8px; }
    .spl-groupe {
        padding: 12px 12px 6px;
        font-size: 11px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase;
        color: #64748b;
    }
    .spl-option {
        display: flex; align-items: center; gap: 12px;
        padding: 8px 12px;
        border-radius: 10px;
        color: inherit; text-decoration: none;
        cursor: pointer;
        scroll-margin: 8px;
    }
    .spl-option--actif { background: #eef4fd; box-shadow: inset 0 0 0 1px rgba(4, 83, 203, .14); }
    .spl-ico {
        width: 32px; height: 32px; border-radius: 9px; flex-shrink: 0;
        display: inline-flex; align-items: center; justify-content: center;
        background: rgba(4, 83, 203, .08); color: #0453cb; font-size: 14px;
    }
    .spl-option--actif .spl-ico { background: #0453cb; color: #fff; }
    .spl-texte { flex: 1; min-width: 0; }
    .spl-titre, .spl-sous-titre { display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .spl-titre { font-size: 14px; font-weight: 600; color: #0f172a; }
    .spl-sous-titre { font-size: 12.5px; color: #64748b; margin-top: 1px; }
    .spl-marque { background: rgba(4, 83, 203, .14); color: #033a8e; border-radius: 3px; padding: 0 1px; }
    .spl-entrer { flex-shrink: 0; color: #0453cb; font-size: 12px; opacity: 0; }
    .spl-option--actif .spl-entrer { opacity: 1; }

    .spl-squelette { display: flex; align-items: center; gap: 12px; padding: 8px 12px; }
    .spl-squelette span { display: block; border-radius: 8px; background: linear-gradient(90deg, #f1f5f9 0%, #e8eef6 50%, #f1f5f9 100%); background-size: 200% 100%; animation: spl-lueur 1.1s ease-in-out infinite; }
    .spl-squelette .spl-sq-ico { width: 32px; height: 32px; flex-shrink: 0; }
    .spl-squelette .spl-sq-lignes { flex: 1; display: grid; gap: 6px; }
    .spl-squelette .spl-sq-l1 { height: 11px; width: 55%; }
    .spl-squelette .spl-sq-l2 { height: 9px; width: 35%; }
    @keyframes spl-lueur { from { background-position: 100% 0; } to { background-position: -100% 0; } }

    .spl-message { padding: 28px 20px; text-align: center; color: #64748b; font-size: 14px; }
    .spl-message i { display: block; font-size: 22px; color: #94a3b8; margin-bottom: 10px; }
    .spl-message a { color: #0453cb; font-weight: 600; text-decoration: none; }
    .spl-message a:hover { text-decoration: underline; }

    /* ---- Pied ---- */
    .spl-pied {
        flex-shrink: 0;
        display: flex; align-items: center; gap: 14px; flex-wrap: wrap;
        padding: 9px 16px;
        border-top: 1px solid #eef2f7;
        background: #f8fafc;
        font-size: 12px; color: #64748b;
    }
    .spl-pied kbd {
        display: inline-flex; align-items: center; justify-content: center;
        min-width: 20px; height: 20px; padding: 0 5px; margin-right: 4px;
        border: 1px solid #e2e8f0; border-radius: 5px; background: #fff;
        font: 600 11px/1 'Inter', system-ui, sans-serif; color: #475569;
        box-shadow: 0 1px 0 #e2e8f0;
    }
    .spl-pied a { margin-left: auto; color: #0453cb; font-weight: 600; text-decoration: none; border-radius: 6px; }

    /* ---- Téléphone : plein écran ---- */
    @media (max-width: 767.98px) {
        .spl-panneau {
            margin: 0; width: 100%; max-width: none;
            height: 100vh; height: 100dvh; max-height: none;
            border: 0; border-radius: 0;
            padding-top: env(safe-area-inset-top);
        }
        /* « Fermer » en toutes lettres (64) + croix (28) + écarts : 124 réservés. */
        .spl-champ input { height: 56px; font-size: 16px; padding-right: 124px; }
        .spl-option { padding: 10px 12px; }
        .spl-pied { display: none; }
        .spl-esc { min-width: 64px; }
    }

    @media (prefers-reduced-motion: reduce) {
        .spl-entre, .spl-voile-entre { transition: none; }
        .spl-squelette span { animation: none; }
    }
</style>

<div class="spl-root"
     x-data="klassciSpotlight()"
     x-show="ouvert"
     x-cloak
     data-url-recherche="{{ route('search.global') }}"
     data-url-resultats="{{ route('search.results') }}"
     data-utilisateur="{{ auth()->id() }}">
    <div class="spl-voile" x-show="ouvert"
         x-transition:enter="spl-voile-entre" x-transition:enter-start="spl-voile-debut" x-transition:enter-end="spl-voile-fin"
         x-transition:leave="spl-voile-entre" x-transition:leave-start="spl-voile-fin" x-transition:leave-end="spl-voile-debut"
         x-on:click="fermer()" aria-hidden="true"></div>

    <div class="spl-panneau" role="dialog" aria-modal="true" aria-labelledby="spl-titre"
         x-show="ouvert" x-trap.noscroll="ouvert"
         x-on:keydown.escape.prevent.stop="saisie.length ? effacer() : fermer()"
         x-transition:enter="spl-entre" x-transition:enter-start="spl-entre-debut" x-transition:enter-end="spl-entre-fin"
         x-transition:leave="spl-entre" x-transition:leave-start="spl-entre-fin" x-transition:leave-end="spl-entre-debut">
        <h2 id="spl-titre" class="visually-hidden">Rechercher dans l'application</h2>

        <div class="spl-champ">
            <i class="fas fa-magnifying-glass spl-champ-ico" aria-hidden="true"></i>
            <input type="text" x-ref="champ"
                   role="combobox" aria-expanded="true" aria-controls="spl-liste" aria-autocomplete="list"
                   x-bind:aria-activedescendant="actif >= 0 ? 'spl-o-' + actif : null"
                   placeholder="Rechercher une page, un étudiant, un reçu…"
                   autocomplete="off" autocapitalize="off" spellcheck="false" enterkeyhint="go"
                   x-model="saisie" x-on:input="surSaisie()" x-on:keydown="clavier($event)">
            <div class="spl-actions">
                <button type="button" class="spl-effacer" x-show="saisie.length > 0" x-on:click="effacer()"
                        aria-label="Effacer la recherche" title="Effacer">
                    <i class="fas fa-xmark" aria-hidden="true"></i>
                </button>
                <button type="button" class="spl-esc" x-on:click="fermer()" aria-label="Fermer la recherche">
                    <span class="d-none d-md-inline">Esc</span><span class="d-md-none">Fermer</span>
                </button>
            </div>
        </div>

        <div class="spl-corps" x-ref="corps">
            <div id="spl-liste" role="listbox" aria-label="Résultats de la recherche" x-show="elements.length > 0">
                <template x-for="(groupe, gi) in groupes" x-bind:key="groupe.nom">
                    <div role="group" x-bind:aria-labelledby="'spl-g-' + gi">
                        <div class="spl-groupe" role="presentation" x-bind:id="'spl-g-' + gi" x-text="groupe.nom"></div>
                        <template x-for="el in groupe.elements" x-bind:key="el.index">
                            <a class="spl-option" role="option"
                               x-bind:id="'spl-o-' + el.index"
                               x-bind:href="el.url"
                               x-bind:aria-selected="actif === el.index ? 'true' : 'false'"
                               x-bind:class="actif === el.index ? 'spl-option--actif' : ''"
                               tabindex="-1"
                               x-on:mousemove="actif = el.index"
                               x-on:click="ouvrirElement(el, $event)">
                                <span class="spl-ico" aria-hidden="true"><i x-bind:class="'fas ' + el.icon"></i></span>
                                <span class="spl-texte">
                                    <span class="spl-titre">
                                        <template x-for="(morceau, mi) in surligner(el.title)" x-bind:key="mi">
                                            <span x-bind:class="morceau.m ? 'spl-marque' : ''" x-text="morceau.t"></span>
                                        </template>
                                    </span>
                                    <span class="spl-sous-titre" x-show="el.subtitle" x-text="el.subtitle"></span>
                                </span>
                                <i class="fas fa-arrow-turn-down fa-rotate-90 spl-entrer" aria-hidden="true"></i>
                            </a>
                        </template>
                    </div>
                </template>
            </div>

            <div x-show="chargement && elements.length === 0" aria-hidden="true">
                <template x-for="n in 4" x-bind:key="n">
                    <div class="spl-squelette"><span class="spl-sq-ico"></span><span class="spl-sq-lignes"><span class="spl-sq-l1"></span><span class="spl-sq-l2"></span></span></div>
                </template>
            </div>

            <div class="spl-message" x-show="!chargement && messageVide" role="status">
                <i class="fas" x-bind:class="erreur ? 'fa-triangle-exclamation' : 'fa-magnifying-glass'" aria-hidden="true"></i>
                <span x-text="messageVide"></span>
                <template x-if="saisie.trim().length >= 2 && !erreur">
                    <div class="mt-2"><a x-bind:href="urlResultatsComplets()">Ouvrir la page de recherche complète</a></div>
                </template>
            </div>
        </div>

        <div class="spl-pied">
            <span aria-hidden="true"><kbd>↑</kbd><kbd>↓</kbd>naviguer</span>
            <span aria-hidden="true"><kbd>↵</kbd>ouvrir</span>
            <span aria-hidden="true"><kbd x-text="estMac ? '⌘' : 'Ctrl'"></kbd><kbd>↵</kbd>nouvel onglet</span>
            <span aria-hidden="true"><kbd>Esc</kbd>fermer</span>
            <a x-show="saisie.trim().length >= 2" x-bind:href="urlResultatsComplets()">Tous les résultats</a>
        </div>
    </div>
</div>

<script>
(function () {
    if (typeof window.klassciSpotlight === 'function') { return; }

    var MAX_RECENTS = 6;
    var DELAI_FRAPPE = 140;

    function estMacPlateforme() {
        var p = (navigator.userAgentData && navigator.userAgentData.platform) || navigator.platform || '';
        return /mac|iphone|ipad|ipod/i.test(p);
    }

    /* Minuscules sans accents, caractère pour caractère : la position d'un
       caractère normalisé est celle du caractère d'origine, ce qui permet de
       surligner le texte affiché à partir d'une recherche sans accents. */
    function normaliserCaractere(c) {
        var n = c.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
        return n.length === 1 ? n : c.toLowerCase();
    }
    function normaliser(texte) {
        var s = '';
        for (var i = 0; i < texte.length; i++) { s += normaliserCaractere(texte[i]); }
        return s;
    }

    window.klassciSpotlight = function () {
        return {
            ouvert: false,
            saisie: '',
            elements: [],
            groupes: [],
            actif: -1,
            chargement: false,
            erreur: false,
            messageVide: '',
            estMac: estMacPlateforme(),
            _suggestions: null,
            _minuteur: null,
            _controleur: null,
            _ecouteurs: [],

            init: function () {
                var self = this;
                var racine = this.$root;
                this._urlRecherche = racine.dataset.urlRecherche;
                this._urlResultats = racine.dataset.urlResultats;
                this._cleRecents = 'klassci.spotlight.recents.' + (racine.dataset.utilisateur || '0');

                if (this.estMac) {
                    document.querySelectorAll('[data-spl-raccourci]').forEach(function (k) { k.textContent = '⌘ K'; });
                    document.querySelectorAll('[data-spl-ouvrir][aria-keyshortcuts]').forEach(function (b) { b.setAttribute('aria-keyshortcuts', 'Meta+K'); });
                }

                this._ecoute(document, 'keydown', function (ev) { self.raccourciGlobal(ev); });
                this._ecoute(document, 'click', function (ev) {
                    var cible = ev.target.closest && ev.target.closest('[data-spl-ouvrir]');
                    if (!cible) { return; }
                    var champ = cible.tagName === 'FORM' ? cible.querySelector('input[name="q"]') : null;
                    if (cible.tagName === 'FORM' && ev.target !== champ) { return; }
                    ev.preventDefault();
                    self.ouvrir(champ ? champ.value : '');
                });
                // Champ de la feuille mobile : taper ou valider ouvre la palette,
                // le formulaire GET ne sert que sans JavaScript.
                this._ecoute(document, 'input', function (ev) {
                    var form = ev.target.closest && ev.target.closest('form[data-spl-ouvrir]');
                    if (!form) { return; }
                    var valeur = ev.target.value;
                    ev.target.value = '';
                    self.ouvrir(valeur);
                });
                this._ecoute(document, 'submit', function (ev) {
                    if (!ev.target.matches || !ev.target.matches('form[data-spl-ouvrir]')) { return; }
                    ev.preventDefault();
                    var champ = ev.target.querySelector('input[name="q"]');
                    self.ouvrir(champ ? champ.value : '');
                });
                this._ecoute(window, 'klassci-spotlight:ouvrir', function (ev) {
                    self.ouvrir(ev.detail && typeof ev.detail.saisie === 'string' ? ev.detail.saisie : '');
                });
            },

            destroy: function () {
                this._ecouteurs.forEach(function (e) { e[0].removeEventListener(e[1], e[2]); });
                this._ecouteurs = [];
                if (this._controleur) { this._controleur.abort(); }
                clearTimeout(this._minuteur);
            },

            _ecoute: function (cible, type, fn) {
                cible.addEventListener(type, fn);
                this._ecouteurs.push([cible, type, fn]);
            },

            raccourciGlobal: function (ev) {
                if (ev.defaultPrevented) { return; }
                var cible = ev.target;
                // Un éditeur riche garde son Ctrl K (insérer un lien).
                if (cible.closest && cible.closest('[contenteditable="true"], .note-editor')) { return; }

                var touche = (ev.key || '').toLowerCase();
                if ((ev.ctrlKey || ev.metaKey) && !ev.altKey && !ev.shiftKey && touche === 'k') {
                    ev.preventDefault();
                    if (this.ouvert) {
                        this.$refs.champ.focus();
                        this.$refs.champ.select();
                    } else {
                        this.ouvrir('');
                    }
                    return;
                }

                if (touche === '/' && !this.ouvert && !ev.ctrlKey && !ev.metaKey && !ev.altKey) {
                    var tag = (cible.tagName || '').toLowerCase();
                    if (tag === 'input' || tag === 'textarea' || tag === 'select' || cible.isContentEditable) { return; }
                    ev.preventDefault();
                    this.ouvrir('');
                }
            },

            ouvrir: function (saisieInitiale) {
                // Une fenêtre Bootstrap ouverte garde le focus pour elle : la palette
                // s'y superposerait sans pouvoir recevoir la frappe.
                if (document.querySelector('.modal.show')) { return; }
                // Les feuilles mobiles (m-sheet) se referment d'abord : elles
                // rendent le focus à leur ouvreur, la palette le reprend ensuite.
                window.dispatchEvent(new CustomEvent('m-sheet:close'));

                var self = this;
                this.saisie = saisieInitiale || '';
                this.ouvert = true;
                this.$nextTick(function () {
                    var champ = self.$refs.champ;
                    champ.focus({ preventScroll: true });
                    var fin = champ.value.length;
                    try { champ.setSelectionRange(fin, fin); } catch (e) { /* type sans sélection */ }
                    self.surSaisie(true);
                });
            },

            fermer: function () {
                this.ouvert = false;
                if (this._controleur) { this._controleur.abort(); this._controleur = null; }
                clearTimeout(this._minuteur);
                this.chargement = false;
            },

            effacer: function () {
                this.saisie = '';
                this.surSaisie(true);
                this.$refs.champ.focus();
            },

            clavier: function (ev) {
                var n = this.elements.length;
                switch (ev.key) {
                    case 'ArrowDown':
                        ev.preventDefault();
                        if (n) { this.activer(this.actif < n - 1 ? this.actif + 1 : 0); }
                        break;
                    case 'ArrowUp':
                        ev.preventDefault();
                        if (n) { this.activer(this.actif > 0 ? this.actif - 1 : n - 1); }
                        break;
                    case 'Home':
                        if (n && (ev.ctrlKey || ev.metaKey)) { ev.preventDefault(); this.activer(0); }
                        break;
                    case 'End':
                        if (n && (ev.ctrlKey || ev.metaKey)) { ev.preventDefault(); this.activer(n - 1); }
                        break;
                    case 'Enter':
                        ev.preventDefault();
                        if (this.actif >= 0 && this.elements[this.actif]) {
                            this.ouvrirElement(this.elements[this.actif], ev);
                        } else if (this.saisie.trim().length >= 2) {
                            window.location.href = this.urlResultatsComplets();
                        }
                        break;
                    case 'Escape':
                        // Premier Échap : vider ; second : fermer. Jamais les deux d'un coup.
                        ev.preventDefault();
                        ev.stopPropagation();
                        if (this.saisie.length) { this.effacer(); } else { this.fermer(); }
                        break;
                }
            },

            activer: function (index) {
                this.actif = index;
                var self = this;
                this.$nextTick(function () {
                    var option = document.getElementById('spl-o-' + index);
                    if (option && self.$refs.corps.contains(option)) { option.scrollIntoView({ block: 'nearest' }); }
                });
            },

            ouvrirElement: function (el, ev) {
                if (ev) { ev.preventDefault(); }
                this.memoriser(el);
                var nouvelOnglet = ev && (ev.ctrlKey || ev.metaKey || ev.button === 1);
                if (nouvelOnglet) {
                    window.open(el.url, '_blank', 'noopener');
                    return;
                }
                this.fermer();
                window.location.href = el.url;
            },

            urlResultatsComplets: function () {
                return this._urlResultats + '?q=' + encodeURIComponent(this.saisie.trim());
            },

            surSaisie: function (immediat) {
                var self = this;
                var q = this.saisie.trim();
                clearTimeout(this._minuteur);
                if (this._controleur) { this._controleur.abort(); this._controleur = null; }
                this.erreur = false;

                if (q.length === 0) {
                    this.chargement = false;
                    this.afficherAccueil();
                    return;
                }
                if (q.length < 2) {
                    this.chargement = false;
                    this.poser([]);
                    this.messageVide = 'Encore une lettre…';
                    return;
                }

                this.chargement = true;
                this.messageVide = '';
                this._minuteur = setTimeout(function () { self.chercher(q); }, immediat ? 0 : DELAI_FRAPPE);
            },

            chercher: function (q) {
                var self = this;
                var controleur = new AbortController();
                this._controleur = controleur;

                fetch(this._urlRecherche + '?q=' + encodeURIComponent(q), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                    signal: controleur.signal
                }).then(function (reponse) {
                    if (reponse.status === 429) { throw new Error('trop'); }
                    if (!reponse.ok) { throw new Error('http'); }
                    return reponse.json();
                }).then(function (donnees) {
                    if (controleur.signal.aborted || self.saisie.trim() !== q) { return; }
                    self.chargement = false;
                    var resultats = Array.isArray(donnees.results) ? donnees.results : [];
                    self.poser(resultats);
                    if (!resultats.length) {
                        self.messageVide = 'Aucun résultat pour « ' + q + ' »';
                    } else if (donnees.partial) {
                        self.messageVide = '';
                    }
                }).catch(function (e) {
                    if (e && e.name === 'AbortError') { return; }
                    self.chargement = false;
                    self.erreur = true;
                    self.poser([]);
                    self.messageVide = e && e.message === 'trop'
                        ? 'Trop de recherches à la suite. Patientez quelques secondes.'
                        : 'La recherche est momentanément indisponible.';
                });
            },

            afficherAccueil: function () {
                var self = this;
                var recents = this.lireRecents().map(function (r) { return Object.assign({}, r, { group: 'Récents' }); });
                var poserAccueil = function () {
                    var dejaVu = {};
                    recents.forEach(function (r) { dejaVu[r.url] = true; });
                    var suggestions = (self._suggestions || []).filter(function (s) { return !dejaVu[s.url]; })
                        .map(function (s) { return Object.assign({}, s, { group: 'Suggestions' }); });
                    self.poser(recents.concat(suggestions));
                    self.messageVide = self.elements.length ? '' : 'Tapez un nom, un matricule, un numéro de reçu ou une page.';
                };
                poserAccueil();

                if (this._suggestions === null) {
                    this._suggestions = [];
                    fetch(this._urlRecherche, {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin'
                    }).then(function (r) { return r.ok ? r.json() : { suggestions: [] }; })
                      .then(function (d) {
                          self._suggestions = Array.isArray(d.suggestions) ? d.suggestions : [];
                          if (self.ouvert && self.saisie.trim() === '') { poserAccueil(); }
                      })
                      .catch(function () { self._suggestions = null; });
                }
            },

            /* Range les résultats par groupe, en gardant l'ordre d'arrivée, et
               numérote chaque élément pour la navigation au clavier. */
            poser: function (liste) {
                var groupes = [];
                var parNom = {};
                var elements = [];
                liste.forEach(function (r) {
                    if (!r || typeof r.url !== 'string') { return; }
                    var nom = r.group || 'Résultats';
                    if (!parNom[nom]) { parNom[nom] = { nom: nom, elements: [] }; groupes.push(parNom[nom]); }
                    parNom[nom].elements.push(r);
                });
                groupes.forEach(function (g) {
                    g.elements = g.elements.map(function (r) {
                        var el = {
                            index: elements.length,
                            url: r.url,
                            title: String(r.title || ''),
                            subtitle: String(r.subtitle || ''),
                            icon: /^fa-[a-z0-9-]+$/.test(r.icon || '') ? r.icon : 'fa-circle',
                            type: r.type || '',
                            group: g.nom
                        };
                        elements.push(el);
                        return el;
                    });
                });
                this.groupes = groupes;
                this.elements = elements;
                this.actif = elements.length ? 0 : -1;
                if (this.$refs.corps) { this.$refs.corps.scrollTop = 0; }
            },

            /* Découpe un texte en morceaux marqués / non marqués selon les mots
               saisis. Aucun HTML : chaque morceau est posé par x-text. */
            surligner: function (texte) {
                var mots = normaliser(this.saisie.trim()).split(/\s+/).filter(function (m) { return m.length >= 1; });
                if (!mots.length || !texte) { return [{ t: texte, m: false }]; }
                var bas = normaliser(texte);
                var marque = new Array(texte.length).fill(false);
                mots.forEach(function (mot) {
                    var depart = 0, pos;
                    while ((pos = bas.indexOf(mot, depart)) !== -1) {
                        for (var i = pos; i < pos + mot.length; i++) { marque[i] = true; }
                        depart = pos + mot.length;
                    }
                });
                var morceaux = [];
                for (var i = 0; i < texte.length; i++) {
                    var dernier = morceaux[morceaux.length - 1];
                    if (dernier && dernier.m === marque[i]) { dernier.t += texte[i]; }
                    else { morceaux.push({ t: texte[i], m: marque[i] }); }
                }
                return morceaux;
            },

            lireRecents: function () {
                try {
                    var brut = JSON.parse(window.localStorage.getItem(this._cleRecents) || '[]');
                    return Array.isArray(brut) ? brut.filter(function (r) { return r && typeof r.url === 'string'; }).slice(0, MAX_RECENTS) : [];
                } catch (e) {
                    return [];
                }
            },

            memoriser: function (el) {
                try {
                    var recents = this.lireRecents().filter(function (r) { return r.url !== el.url; });
                    recents.unshift({ url: el.url, title: el.title, subtitle: el.subtitle, icon: el.icon, type: el.type });
                    window.localStorage.setItem(this._cleRecents, JSON.stringify(recents.slice(0, MAX_RECENTS)));
                } catch (e) { /* stockage indisponible : les récents sont un confort */ }
            }
        };
    };
})();
</script>

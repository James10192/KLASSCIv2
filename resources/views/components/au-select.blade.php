@props([
    'name' => null,
    'value' => '',
    'options' => [],
    'placeholder' => 'Sélectionner…',
    'label' => null,
    'icon' => null,
    'searchable' => false,
    'placeholderIsFirstOption' => true,
    'nativeClass' => null,
    'disabled' => false,
])

@php
    $wrapperClass = trim('au-select ' . ($attributes->get('class') ?? ''));
    $nativeAttributes = $attributes->except(['class']);
    $normalized = collect($options)->map(function ($v, $k) {
        if (is_array($v) && array_key_exists('value', $v) && array_key_exists('label', $v)) {
            return ['value' => $v['value'], 'label' => $v['label']];
        }
        if (is_object($v) && property_exists($v, 'value') && property_exists($v, 'label')) {
            return ['value' => $v->value, 'label' => $v->label];
        }
        return ['value' => $k, 'label' => (string) $v];
    })->values();
    $componentId = 'au-select-' . substr(md5(uniqid('', true)), 0, 8);
@endphp

{{-- Le modificateur « outside » d'Alpine compare la cible du clic au SEUL
     element racine. Or le menu peut etre deplace en fin de document (cf.
     verifierAncrage dans le script) : il cesse alors d'etre un descendant de
     cette racine, et un clic dans son champ de recherche ou sur sa croix
     d'effacement passerait pour un clic dehors — le menu se refermerait sous
     les doigts. On delegue donc la decision au composant, seul a connaitre la
     position reelle de son menu. --}}
<div class="{{ $wrapperClass }}" x-data="auSelect()" x-id="['{{ $componentId }}']" @click.outside="fermerSiExterieur($event)" @keydown.escape="fermer()">
    <button type="button"
            class="au-select-trigger"
            :class="{ 'au-select-trigger--open': open, 'au-select-trigger--has-value': currentValue !== '', 'au-select-trigger--disabled': isDisabled }"
            @click="toggle()"
            @keydown.arrow-down.prevent="openAndFocusNext()"
            @keydown.arrow-up.prevent="openAndFocusPrevious()"
            @keydown.enter.prevent="open ? selectFocused() : openAndFocusNext()"
            @keydown.space.prevent="open ? selectFocused() : toggle()"
            @if($disabled) disabled @endif
            {{ $attributes->whereStartsWith('x-bind:disabled') }}
            aria-label="{{ $label ?: $placeholder }}"
            :aria-expanded="open.toString()"
            :aria-controls="$id('{{ $componentId }}')"
            aria-haspopup="listbox"
            role="combobox">
        @if($icon)<i class="fas {{ $icon }} au-select-icon"></i>@endif
        <span class="au-select-value" x-text="selectedLabel || {{ \Illuminate\Support\Js::from($placeholder) }}"
              :class="{ 'au-select-value--placeholder': !selectedLabel }"></span>
        <i class="fas fa-chevron-down au-select-caret" :class="{ 'au-select-caret--open': open }"></i>
    </button>

    <div class="au-select-menu"
         x-ref="menu"
         :id="$id('{{ $componentId }}')"
         x-show="open"
         x-cloak
         x-transition:enter="au-select-menu--entering"
         x-transition:enter-start="au-select-menu--enter-start"
         x-transition:enter-end="au-select-menu--enter-end"
         role="listbox"
         :aria-label="{{ \Illuminate\Support\Js::from($label ?: $placeholder) }}">
        @if($searchable)
        <div class="au-select-search">
            <i class="fas fa-search"></i>
            <input type="text"
                   x-model="search"
                   x-ref="searchInput"
                   @click.stop
                   @keydown.escape.stop="fermer()"
                   @keydown.arrow-down.prevent="focusNextOption()"
                   @keydown.arrow-up.prevent="focusPreviousOption()"
                   @keydown.enter.prevent="selectFocused()"
                   placeholder="Rechercher…">
            <button type="button"
                    class="au-select-search-clear"
                    x-show="search.length > 0"
                    @click="search = ''; $refs.searchInput.focus()"
                    aria-label="Effacer la recherche">
                <i class="fas fa-times"></i>
            </button>
        </div>
        @endif

        <ul class="au-select-options">
            <template x-for="(opt, idx) in filteredOptions" :key="opt.placeholder ? '__placeholder__' : (opt.value + '|' + opt.label)">
                <li class="au-select-option"
                    :class="{ 'au-select-option--active': opt.value === currentValue, 'au-select-option--focused': idx === focusedIndex, 'au-select-option--placeholder': opt.placeholder }"
                    @mouseenter="focusedIndex = idx"
                    @click="select(opt)"
                    role="option"
                    :aria-selected="(opt.value === currentValue).toString()">
                    <span class="au-select-option-label" x-text="opt.label"></span>
                    <i class="fas fa-check au-select-option-check" x-show="opt.value === currentValue"></i>
                </li>
            </template>
            <li class="au-select-empty" x-show="filteredOptions.length === 0" x-cloak>
                <i class="fas fa-search"></i>
                <span>Aucun résultat pour <strong x-text="search"></strong></span>
            </li>
        </ul>
    </div>

    <select {{ $nativeAttributes->class(['au-select-native', $nativeClass]) }}
            x-ref="native"
            @if($name) name="{{ $name }}" @endif
            @if($disabled) disabled @endif
            aria-hidden="true"
            tabindex="-1">
        @if($placeholderIsFirstOption)
            <option value="" data-placeholder="1">{{ $placeholder }}</option>
        @endif
        @foreach($normalized as $opt)
            <option value="{{ $opt['value'] }}" @selected((string) $opt['value'] === (string) $value)>{{ $opt['label'] }}</option>
        @endforeach
    </select>
</div>

@once
@push('styles')
<style>
.au-select { position: relative; display: inline-flex; flex: 1 1 0%; min-width: 0; }
.au-filter-grow { flex-grow: 4; }
.au-select-native { position: absolute; width: 1px; height: 1px; opacity: 0; pointer-events: none; clip: rect(0 0 0 0); }
.au-select-trigger {
    width: 100%; display: inline-flex; align-items: center; gap: .5rem;
    min-height: 44px;
    padding: .55rem .85rem;
    background: #fff; border: 1px solid #e2e8f0; border-radius: 10px;
    font-size: .85rem; color: #1e293b; cursor: pointer;
    transition: border-color .15s, box-shadow .15s, transform .15s;
    text-align: left; line-height: 1.2;
}
.au-select-trigger:active { transform: scale(.96); }
.au-select-trigger:hover { border-color: #cbd5e1; }
.au-select-trigger:focus-visible { outline: none; border-color: #0453cb; box-shadow: 0 0 0 3px rgba(4,83,203,.12); }
.au-select-trigger--open { border-color: #0453cb; box-shadow: 0 0 0 3px rgba(4,83,203,.10); }
.au-select-trigger--disabled, .au-select-trigger:disabled { cursor: not-allowed; opacity: .55; background: #f8fafc; }
.au-select-icon { color: #64748b; font-size: .85rem; flex-shrink: 0; }
.au-select-value { flex: 1; min-width: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-weight: 500; }
.au-select-value--placeholder { color: #94a3b8; font-weight: 400; }
.au-select-caret { color: #94a3b8; font-size: .72rem; flex-shrink: 0; transition: transform .2s ease; }
.au-select-caret--open { transform: rotate(180deg); color: #0453cb; }
.au-select-menu {
    position: absolute; top: calc(100% + 6px);
    left: 8px; right: 8px;
    z-index: 1100;
    background: #fff; border: 1px solid #e2e8f0; border-radius: 12px;
    box-shadow: 0 12px 40px rgba(15,23,42,.12), 0 4px 12px rgba(15,23,42,.06);
    overflow: hidden; max-height: 360px;
    display: flex; flex-direction: column;
    transform-origin: top center;
}
.au-select-options, .au-select-option { position: relative; z-index: 1; }
.au-select-menu--enter-start { opacity: 0; transform: translateY(-6px) scale(.98); transition: opacity .14s ease, transform .14s ease; }
.au-select-menu--enter-end { opacity: 1; transform: translateY(0) scale(1); }
.au-select-search {
    position: relative; padding: .55rem .75rem;
    border-bottom: 1px solid #f1f5f9;
    display: flex; align-items: center; gap: .5rem;
}
.au-select-search > i:first-child { color: #94a3b8; font-size: .8rem; }
/* min-width:0 : sans lui, le champ garde sa largeur native (~170px) et deborde
   d'un menu etroit ; le focus fait alors defiler le menu, qui s'affiche
   coupe a gauche. */
.au-select-search input { flex: 1; min-width: 0; width: 100%; border: none; background: transparent; outline: none; font-size: .85rem; color: #1e293b; }
.au-select-search-clear {
    background: #f1f5f9; border: none; width: 22px; height: 22px;
    border-radius: 50%; cursor: pointer;
    display: inline-flex; align-items: center; justify-content: center;
    color: #64748b; font-size: .65rem;
}
.au-select-search-clear:hover { background: #e2e8f0; color: #0f172a; }
.au-select-options {
    list-style: none; margin: 0; padding: .35rem 0;
    overflow-y: auto; flex: 1;
    scrollbar-width: thin; scrollbar-color: #cbd5e1 transparent;
}
.au-select-options::-webkit-scrollbar { width: 6px; }
.au-select-options::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
.au-select-option {
    display: flex; align-items: center; justify-content: space-between;
    padding: .55rem .85rem; cursor: pointer;
    font-size: .85rem; color: #1e293b;
    transition: background .12s;
    border-left: 3px solid transparent;
}
.au-select-option:hover { background: #f8fafc; border-left-color: #cbd5e1; }
.au-select-option--focused { background: #f8fafc; border-left-color: #94a3b8; }
.au-select-option--active { background: #eff6ff; color: #0453cb; font-weight: 600; border-left-color: #0453cb; }
.au-select-option--active:hover { background: #dbeafe; }
.au-select-option--placeholder { color: #94a3b8; font-style: italic; }
.au-select-option-label { flex: 1; min-width: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.au-select-option-check { color: #0453cb; font-size: .78rem; flex-shrink: 0; margin-left: .5rem; }
.au-select-empty {
    padding: 1.25rem 1rem; text-align: center;
    color: #64748b; font-size: .82rem;
    display: flex; flex-direction: column; align-items: center; gap: .35rem;
}
.au-select-empty i { color: #cbd5e1; font-size: 1.4rem; }
@media (max-width: 576px) {
    .au-select-menu { max-height: 60vh; }
    .au-select-trigger { padding: .65rem .85rem; font-size: .9rem; }
    .au-select-option { padding: .7rem .85rem; font-size: .9rem; }
}
</style>
@endpush

@push('scripts')
<script>
/**
 * Un libelle correspond a une saisie quand CHACUN de ses mots s'y retrouve,
 * dans n'importe quel ordre.
 *
 * L'ancien test etait `label.includes(saisie)` : une sous-chaine CONTIGUE. Or
 * un etudiant s'affiche « 14196637U - YAO FRANCK PARFAIT KONE » et la
 * caissiere tape « KONE YAO PARFAIT » — ce dont elle se souvient, pas l'etat
 * civil dans l'ordre. Aucun de ces trois mots n'est contigu aux autres : la
 * liste se vidait alors meme que le serveur venait de renvoyer le bon
 * etudiant. C'est le meme defaut que celui corrige cote serveur
 * (ESBTPEtudiantController::searchForApi), simplement rejoue cote client.
 *
 * La regle est volontairement PLUS PERMISSIVE que l'ancienne, jamais moins :
 * une saisie d'un seul mot se comporte exactement comme avant, et toute
 * correspondance qui passait continue de passer. Les listes statiques
 * (filtres, formulaires) ne perdent donc aucun resultat.
 */
if (typeof window.auSelectMatchesQuery !== 'function') {
    window.auSelectMatchesQuery = function (label, query) {
        var termes = String(query == null ? '' : query)
            .toLowerCase()
            .split(/\s+/)
            .filter(function (terme) { return terme.length > 0; });

        if (termes.length === 0) {
            return true;
        }

        var cible = String(label == null ? '' : label).toLowerCase();

        return termes.every(function (terme) { return cible.indexOf(terme) !== -1; });
    };
}

/**
 * Un element devient BLOC CONTENEUR de ses descendants `position: fixed` des
 * qu'il porte l'une de ces proprietes : transform, filter, backdrop-filter,
 * perspective, contain (layout|paint|strict|content), ou will-change citant
 * l'une d'elles. Les transformations individuelles rotate/scale/translate
 * comptent au meme titre.
 *
 * Ce composant pose son menu en `fixed` avec des coordonnees lues sur le
 * VIEWPORT. Sous un tel ancetre, le navigateur les interprete par rapport a
 * CET ancetre : le menu part de plusieurs centaines de pixels, souvent hors
 * ecran. C'est ce qu'a produit `.card-moderne:hover { transform: ... }` sur
 * /esbtp/paiements/create — le selecteur d'etudiant disparaissait des qu'on
 * survolait la carte qui le contient.
 *
 * Corriger regle par regle est sans fin : la feuille globale en compte des
 * dizaines et il s'en ajoute a chaque page. Cette fonction sert donc a rendre
 * le composant INSENSIBLE au probleme, en detectant la situation pour aller
 * poser le menu ailleurs.
 *
 * Elle recoit un objet de style deja lu (pas un element) pour rester
 * verifiable hors navigateur.
 */
if (typeof window.auSelectStyleCreeBlocConteneur !== 'function') {
    window.auSelectStyleCreeBlocConteneur = function (style) {
        if (! style) {
            return false;
        }

        // Une valeur CSS calculee est TOUJOURS une chaine. Tout le reste est
        // du bruit — a commencer par `style.filter`, qui vaut la methode
        // `filter` heritee si on tend un tableau au lieu d'un style.
        var lire = function (nom, variante) {
            var valeur = style[nom];
            if (typeof valeur !== 'string' && variante) {
                valeur = style[variante];
            }
            return typeof valeur === 'string' ? valeur.trim().toLowerCase() : '';
        };

        // `none` et `auto` sont les valeurs calculees d'une propriete absente.
        var posee = function (valeur) {
            return valeur !== '' && valeur !== 'none' && valeur !== 'auto';
        };

        if (posee(lire('transform'))) return true;
        if (posee(lire('filter'))) return true;
        if (posee(lire('backdropFilter', 'backdrop-filter'))) return true;
        if (posee(lire('perspective'))) return true;

        // rotate / scale / translate ecrivent leur valeur neutre differemment
        // selon le navigateur : `none`, mais aussi `0deg`, `1` ou `0px`. Seul
        // un deplacement reel cree un bloc conteneur.
        var rotation = lire('rotate');
        if (posee(rotation) && ! /^0(deg|rad|grad|turn)?$/.test(rotation)) return true;

        var echelle = lire('scale');
        if (posee(echelle) && ! /^1(\s+1){0,2}$/.test(echelle)) return true;

        var deplacement = lire('translate');
        if (posee(deplacement) && ! /^0(px)?(\s+0(px)?){0,2}$/.test(deplacement)) return true;

        if (/\b(layout|paint|strict|content)\b/.test(lire('contain'))) return true;

        if (/\b(transform|filter|backdrop-filter|perspective|rotate|scale|translate|contain)\b/
            .test(lire('willChange', 'will-change'))) return true;

        return false;
    };
}

/**
 * Remonte la chaine des ancetres — l'element lui-meme compris, car il englobe
 * deja le menu — et rend le premier qui rendrait le menu mal place, ou null.
 *
 * `lireStyle` est injectable pour que la remontee soit verifiable sans
 * navigateur ; en production elle lit le style calcule.
 */
if (typeof window.auSelectAncetreBloquant !== 'function') {
    window.auSelectAncetreBloquant = function (element, lireStyle) {
        if (! element) {
            return null;
        }

        var lire = typeof lireStyle === 'function'
            ? lireStyle
            : function (noeud) { return window.getComputedStyle(noeud); };

        var noeud = element;
        // Garde-fou : une chaine circulaire ne doit pas figer la page.
        var restant = 200;

        while (noeud && restant-- > 0) {
            if (window.auSelectStyleCreeBlocConteneur(lire(noeud))) {
                return noeud;
            }
            noeud = noeud.parentElement || null;
        }

        return null;
    };
}

if (typeof window.auSelect !== 'function') {
    window.auSelect = function () {
        return {
            open: false,
            search: '',
            _value: '',
            focusedIndex: -1,
            optionsVersion: 0,
            _optionsObserver: null,
            _repositionMenu: null,
            _remeasureMenu: null,
            _menuWidth: null,
            _menuMaxHeight: null,
            _menuOpenUp: null,
            _repositionFrame: null,
            _menu: null,
            _racine: null,
            _declencheur: null,
            _menuDeplace: false,
            _marqueur: null,
            _veilleAncrage: null,
            init() {
                this._value = this.$refs.native.value;
                // Avec `x-model`, Alpine pose la valeur du parent dans le
                // <select> APRES cet init, et sans evenement : sans relecture,
                // une valeur pre-remplie (depuis l'URL, par exemple) etait bien
                // en place mais l'ecran affichait encore l'invite.
                this.$nextTick(() => this.relireLaValeurNative());
                // Reference gardee une fois pour toutes : le menu peut quitter
                // cette racine (cf. verifierAncrage), un querySelector sur
                // $el ne le retrouverait alors plus.
                this._menu = this.$refs.menu || this.$el.querySelector('.au-select-menu');
                // Meme precaution pour la racine et le declencheur. Dans une
                // methode appelee depuis un gestionnaire (`@click="toggle()"`
                // sur le bouton), `$el` designe l'element du gestionnaire — le
                // BOUTON —, pas la racine. `$el.querySelector('.au-select-trigger')`
                // y rendait null : positionMenu sortait sans rien poser, et le
                // menu s'ouvrait sur sa seule position CSS. Passe sous <body>,
                // il s'etirait alors sur toute la largeur de la page, jusqu'au
                // premier scroll — dont l'ecouteur, lui, voyait la bonne racine.
                this._racine = this.$el;
                this._declencheur = this.$el.querySelector('.au-select-trigger');

                this.observeNativeOptions();
                // Le scroll DEPLACE le menu, il ne le redimensionne pas. Un
                // redimensionnement de fenetre, lui, doit tout remesurer.
                this._repositionMenu = () => this.scheduleReposition();
                this._remeasureMenu = () => this.open && this.positionMenu(true);
                window.addEventListener('resize', this._remeasureMenu, { passive: true });
                window.addEventListener('scroll', this._repositionMenu, { passive: true, capture: true });
                window.visualViewport?.addEventListener('resize', this._remeasureMenu, { passive: true });
                window.visualViewport?.addEventListener('scroll', this._repositionMenu, { passive: true });

                this.$refs.native.addEventListener('change', () => {
                    if (this._value !== this.$refs.native.value) {
                        this._value = this.$refs.native.value;
                    }
                });
                this.$watch('_value', (v) => {
                    if (this.$refs.native.value !== v) {
                        this.$refs.native.value = v;
                        this.$refs.native.dispatchEvent(new Event('change', { bubbles: true }));
                        this.$refs.native.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                });
                this.$watch('search', () => {
                    this.focusFirstSelectable();
                });

            },
            relireLaValeurNative() {
                const native = this.$refs.native;
                if (native && this._value !== native.value) {
                    this._value = native.value;
                }
            },
            destroy() {
                this._optionsObserver?.disconnect();
                this.cancelReposition();
                this.cesserSurveillanceAncrage();
                // Le composant disparait (modal remplacee en AJAX, ligne
                // retiree) : son menu ne doit pas rester seul sous <body>.
                this.rapatrierLeMenu();
                window.removeEventListener('resize', this._remeasureMenu);
                window.removeEventListener('scroll', this._repositionMenu, { capture: true });
                window.visualViewport?.removeEventListener('resize', this._remeasureMenu);
                window.visualViewport?.removeEventListener('scroll', this._repositionMenu);
            },
            observeNativeOptions() {
                if (!this.$refs.native || typeof MutationObserver === 'undefined') {
                    return;
                }

                const bump = () => {
                    this.optionsVersion++;

                    const hasCurrentValue = Array.from(this.$refs.native.options || []).some(
                        option => option.value === this._value
                    );

                    if (!hasCurrentValue) {
                        this._value = this.$refs.native.value || '';
                    }
                };

                bump();

                this._optionsObserver = new MutationObserver(() => {
                    bump();
                });

                this._optionsObserver.observe(this.$refs.native, {
                    childList: true,
                    subtree: true,
                    attributes: true,
                    attributeFilter: ['value', 'label', 'selected', 'data-placeholder'],
                });
            },
            toggle() {
                if (this.isDisabled) {
                    return;
                }
                if (this.open) {
                    this.fermer();
                } else {
                    this.ouvrir();
                }
            },
            ouvrir() {
                if (this.isDisabled) {
                    return;
                }
                this.open = true;
                this.focusInitialOption();
                // L'ancrage se decide AVANT la mesure : deplacer le menu change
                // la reference de ses coordonnees.
                this.verifierAncrage();
                // Position before Alpine reveals the menu, then refine with its rendered size.
                this.positionMenu(true);
                this.$nextTick(() => {
                    window.requestAnimationFrame(() => {
                        this.verifierAncrage();
                        this.positionMenu(true);
                        this.$refs.searchInput?.focus();
                    });
                });
                this.surveillerAncrage();
            },
            /**
             * Unique chemin de fermeture — clic dehors, echappement, choix
             * d'une option, second clic sur le declencheur. Tout ce qui a ete
             * pose a l'ouverture est defait ici, sinon un menu reste deplace
             * sous <body> ou garde une position perimee.
             */
            fermer() {
                if (! this.open) {
                    // Appel defensif — echappement sur un menu deja ferme.
                    this.cesserSurveillanceAncrage();
                    this.rapatrierLeMenu();
                    return;
                }
                this.open = false;
                this.effacerPositionMenu();
                this.focusedIndex = -1;
                // La prochaine ouverture remesure : le contenu a pu changer.
                this._menuWidth = null;
                this._menuMaxHeight = null;
                this._menuOpenUp = null;
                this.cancelReposition();
                this.cesserSurveillanceAncrage();
                this.rapatrierLeMenu();
            },
            /**
             * `@click.outside` ne connait que la racine du composant. Quand le
             * menu a ete deplace sous <body>, un clic dans son champ de
             * recherche est « dehors » au sens d'Alpine alors qu'il est
             * evidemment dedans pour l'utilisateur.
             */
            fermerSiExterieur(evenement) {
                if (! this.open) {
                    return;
                }
                if (this._menu && evenement && this._menu.contains(evenement.target)) {
                    return;
                }
                this.fermer();
            },
            /**
             * Deplace le menu sous <body> si — et seulement si — un ancetre en
             * fausserait le placement (cf. auSelectAncetreBloquant).
             *
             * Le deplacement est conditionnel a dessein : sous <body>, les
             * regles CSS ecrites en descendance d'un parent (`.cpa-filters
             * .au-select-menu { ... }`) cessent de s'appliquer. Tant que rien
             * ne casse, on laisse le menu ou il est.
             *
             * Rend true si le menu vient d'etre deplace.
             */
            verifierAncrage() {
                if (this._menuDeplace || ! this._menu || ! this._menu.parentNode) {
                    return false;
                }
                if (typeof window.auSelectAncetreBloquant !== 'function') {
                    return false;
                }
                if (! window.auSelectAncetreBloquant(this._racine)) {
                    return false;
                }

                // Le marqueur retient la place exacte du menu dans la racine.
                this._marqueur = document.createComment('au-select-menu');
                this._menu.parentNode.insertBefore(this._marqueur, this._menu);
                document.body.appendChild(this._menu);
                this._menuDeplace = true;

                return true;
            },
            rapatrierLeMenu() {
                if (! this._menuDeplace) {
                    return;
                }
                if (this._marqueur && this._marqueur.parentNode) {
                    this._marqueur.parentNode.insertBefore(this._menu, this._marqueur);
                    this._marqueur.remove();
                } else if (this._menu && this._menu.parentNode) {
                    // La place d'origine a disparu du document : plutot que de
                    // laisser un menu orphelin sous <body>, on le retire.
                    this._menu.remove();
                }
                this._marqueur = null;
                this._menuDeplace = false;
            },
            /**
             * Un `transform` de survol ne s'applique qu'une fois la souris sur
             * la carte. Ouvert au clavier, le menu peut donc etre correctement
             * ancre a l'ouverture puis se decrocher au premier mouvement de
             * souris. On surveille jusqu'au deplacement, puis on arrete : il
             * n'y a plus rien a decider.
             */
            surveillerAncrage() {
                if (this._veilleAncrage) {
                    return;
                }
                this._veilleAncrage = () => {
                    if (! this.open || this._menuDeplace) {
                        this.cesserSurveillanceAncrage();
                        return;
                    }
                    if (this.verifierAncrage()) {
                        this.positionMenu(true);
                        this.cesserSurveillanceAncrage();
                    }
                };
                document.addEventListener('pointerover', this._veilleAncrage, { passive: true, capture: true });
            },
            cesserSurveillanceAncrage() {
                if (! this._veilleAncrage) {
                    return;
                }
                document.removeEventListener('pointerover', this._veilleAncrage, { capture: true });
                this._veilleAncrage = null;
            },
            openAndFocusNext() {
                if (!this.open) {
                    this.toggle();
                    return;
                }
                this.focusNextOption();
            },
            openAndFocusPrevious() {
                if (!this.open) {
                    this.toggle();
                    return;
                }
                this.focusPreviousOption();
            },
            /**
             * Le scroll est bruyant : l'ecouteur est pose sur `window` en phase
             * de capture, donc il se declenche pour TOUT conteneur defilant de
             * la page — y compris la liste d'options du menu lui-meme. Sans
             * regroupement, plusieurs repositionnements tombaient dans la meme
             * image, chacun forcant une relecture de mise en page, et l'ecriture
             * finale pouvait arriver apres que le navigateur ait deja peint la
             * position du declencheur : le menu s'ecartait d'une image puis
             * revenait. On ne garde donc qu'un repositionnement par image,
             * cale sur le rendu.
             */
            scheduleReposition() {
                if (!this.open || this._repositionFrame !== null) {
                    return;
                }
                this._repositionFrame = window.requestAnimationFrame(() => {
                    this._repositionFrame = null;
                    if (this.open) {
                        this.positionMenu(false);
                    }
                });
            },
            cancelReposition() {
                if (this._repositionFrame !== null) {
                    window.cancelAnimationFrame(this._repositionFrame);
                    this._repositionFrame = null;
                }
            },
            /**
             * Place le menu. `remeasure` distingue deux gestes tres differents.
             *
             * A l'ouverture et au redimensionnement, on MESURE : largeur naturelle
             * du menu, hauteur disponible. Au scroll, on ne fait que DEPLACER, en
             * reutilisant ces mesures.
             *
             * Les melanger produisait deux bugs visibles :
             *
             * - la largeur se relisait elle-meme. `menu.offsetWidth` rend la
             *   largeur posee au tour precedent ; chaque scroll repartait donc de
             *   sa propre sortie, et le menu se decalait a mesure qu'on scrollait.
             *
             * - la hauteur suivait la place restante sous le champ, qui change a
             *   chaque pixel scrolle. La liste interne changeait donc de taille en
             *   continu, faisant apparaitre et disparaitre ses barres de defilement.
             *
             * Une taille se decide une fois, quand le menu s'ouvre.
             */
            positionMenu(remeasure = false) {
                const trigger = this._declencheur;
                const menu = this._menu;
                if (!trigger || !menu) return;

                const margin = 12;
                const gap = 6;
                const triggerRect = trigger.getBoundingClientRect();
                // position:fixed et getBoundingClientRect parlent le viewport
                // de MISE EN PAGE. visualViewport (le zoom) n'y entre pas :
                // le mixer plaquait le menu a gauche des 200 %.
                const layoutWidth = document.documentElement.clientWidth || window.innerWidth;
                const layoutHeight = document.documentElement.clientHeight || window.innerHeight;
                const viewportWidth = layoutWidth - (margin * 2);

                const spaceBelowNow = layoutHeight - triggerRect.bottom - margin - gap;
                const spaceAboveNow = triggerRect.top - margin - gap;

                if (remeasure || this._menuWidth === null) {
                    // La largeur du menu EST celle du champ. Mesurer le contenu
                    // etirait le dropdown a toute la page des qu'un libelle
                    // d'etudiant etait long.
                    // Sauf au telephone : un champ en demi-largeur donnait un
                    // menu de 150px ou chaque libelle etait coupe. Sous 768px,
                    // le menu prend la largeur de l'ecran.
                    this._menuWidth = layoutWidth < 768 ? viewportWidth : Math.min(triggerRect.width, viewportWidth);
                    this._menuMaxHeight = Math.max(0, Math.min(
                        380,
                        Math.max(spaceBelowNow, spaceAboveNow)
                    ));
                    // Le SENS d'ouverture se decide en meme temps que la taille,
                    // et pour la meme raison. `spaceBelow`/`spaceAbove` changent
                    // a chaque pixel defile : recalculer ce test au scroll faisait
                    // basculer le menu au-dessus puis a nouveau au-dessous des que
                    // le declencheur passait pres du seuil — un saut de toute la
                    // hauteur du menu, aller et retour. Une orientation se choisit
                    // une fois, a l'ouverture ; ensuite le scroll ne fait que
                    // deplacer.
                    this._menuOpenUp = spaceBelowNow < this._menuMaxHeight
                        && spaceAboveNow > spaceBelowNow;
                }

                const menuWidth = Math.min(this._menuWidth, viewportWidth);
                const availableHeight = this._menuMaxHeight;
                const minimumWidth = Math.min(Math.max(triggerRect.width, menuWidth), viewportWidth);
                const left = Math.max(margin, Math.min(triggerRect.left, layoutWidth - menuWidth - margin));

                // Sous <body>, le menu perd les regles CSS ecrites en
                // descendance de son parent d'origine — dont, sur certaines
                // pages, un z-index releve. On le repose ici (z-index 99999),
                // au meme niveau que les menus Bootstrap deplaces
                // (cf. universal-dropdowns).
                this.appliquerPositionMenu({
                    'z-index': this._menuDeplace ? '99999' : '',
                    position: 'fixed',
                    left: `${left}px`,
                    right: 'auto',
                    top: this._menuOpenUp ? 'auto' : `${triggerRect.bottom + gap}px`,
                    bottom: this._menuOpenUp ? `${layoutHeight - triggerRect.top + gap}px` : 'auto',
                    width: `${menuWidth}px`,
                    'min-width': `${minimumWidth}px`,
                    'max-width': `${menuWidth}px`,
                    'max-height': `${availableHeight}px`,
                    'transform-origin': this._menuOpenUp ? 'bottom center' : 'top center',
                });
            },
            /**
             * La position s'ecrit propriete par propriete, jamais par un liant
             * `:style` en chaine. Ce liant reecrit l'attribut style ENTIER, sur
             * lequel x-show pose et retire `display` au rythme de sa
             * transition : les deux se disputaient le meme attribut, et il
             * fallait deja une garde dans fermer() pour que l'un n'efface pas
             * l'autre. `display` n'appartient qu'a x-show ; on n'y touche
             * jamais ici.
             */
            appliquerPositionMenu(proprietes) {
                const menu = this._menu;
                if (! menu) return;
                Object.entries(proprietes).forEach(([nom, valeur]) => {
                    if (valeur === '') {
                        menu.style.removeProperty(nom);
                    } else {
                        menu.style.setProperty(nom, valeur);
                    }
                });
            },
            effacerPositionMenu() {
                const menu = this._menu;
                if (! menu) return;
                ['z-index', 'position', 'left', 'right', 'top', 'bottom', 'width',
                    'min-width', 'max-width', 'max-height', 'transform-origin']
                    .forEach((nom) => menu.style.removeProperty(nom));
            },
            get currentValue() { return this._value; },
            get isDisabled() { return !!this.$refs.native?.disabled; },
            get rawOptions() {
                this.optionsVersion;

                if (!this.$refs.native) return [];
                return Array.from(this.$refs.native.options).map(o => ({
                    value: o.value,
                    label: o.textContent.trim(),
                    placeholder: o.dataset.placeholder === '1',
                }));
            },
            get filteredOptions() {
                const s = this.search.trim();
                if (!s) return this.rawOptions;
                // Repli sur la liste entiere si le comparateur manque : mieux
                // vaut trop montrer que masquer une reponse juste.
                const correspond = window.auSelectMatchesQuery;
                if (typeof correspond !== 'function') return this.rawOptions;
                return this.rawOptions.filter(o => o.placeholder || correspond(o.label, s));
            },
            get selectedLabel() {
                const opt = this.rawOptions.find(o => o.value === this._value);
                return opt && !opt.placeholder ? opt.label : '';
            },
            focusInitialOption() {
                const currentIndex = this.filteredOptions.findIndex(o => o.value === this._value);
                this.focusedIndex = currentIndex >= 0 ? currentIndex : this.firstSelectableIndex();
            },
            focusFirstSelectable() {
                this.focusedIndex = this.firstSelectableIndex();
            },
            firstSelectableIndex() {
                const idx = this.filteredOptions.findIndex(o => !o.placeholder);
                return idx >= 0 ? idx : -1;
            },
            focusNextOption() {
                const options = this.filteredOptions;
                if (!options.length) return;
                let idx = this.focusedIndex;
                for (let i = 0; i < options.length; i++) {
                    idx = (idx + 1 + options.length) % options.length;
                    if (!options[idx].placeholder) {
                        this.focusedIndex = idx;
                        return;
                    }
                }
            },
            focusPreviousOption() {
                const options = this.filteredOptions;
                if (!options.length) return;
                let idx = this.focusedIndex < 0 ? options.length : this.focusedIndex;
                for (let i = 0; i < options.length; i++) {
                    idx = (idx - 1 + options.length) % options.length;
                    if (!options[idx].placeholder) {
                        this.focusedIndex = idx;
                        return;
                    }
                }
            },
            /**
             * L'invite (« Toutes les classes ») est une option comme une autre
             * au clic : elle ramene la valeur vide. La refuser laissait le menu
             * ouvert et la valeur figee — impossible de revenir a « toutes »
             * apres avoir choisi une classe, et le clic suivant sur le champ
             * refermait le menu au lieu de l'ouvrir. Le clavier, lui, continue
             * de la sauter (focusNextOption), comme avant.
             */
            select(opt) {
                this._value = opt.placeholder ? '' : opt.value;
                this.fermer();
                this.search = '';
            },
            selectFocused() {
                const opt = this.filteredOptions[this.focusedIndex];
                if (opt) {
                    this.select(opt);
                }
            },
            selectFirstFiltered() {
                const first = this.filteredOptions.find(o => !o.placeholder);
                if (first) this.select(first);
            },
            /**
             * Remplace la liste des options.
             *
             * `conserverRecherche` existe pour une raison precise : une
             * recherche DISTANTE appelle cette methode avec ce que le serveur a
             * renvoye, pendant que l'utilisateur a encore les doigts sur le
             * clavier. Effacer `search` a ce moment vide le champ SOUS SA
             * FRAPPE — et comme la recherche n'part qu'a partir de trois
             * caracteres, plus rien ne se declenche ensuite. La liste se
             * remplissait correctement, mais le champ se vidait : de
             * l'exterieur, cela s'appelle « la recherche ne marche pas ».
             *
             * Le defaut reste l'effacement, qui est juste pour l'autre usage —
             * une liste en cascade (filiere -> classe) ou l'ancienne saisie ne
             * s'applique plus a rien.
             */
            setOptions(items, selectedValue = '', conserverRecherche = false) {
                const native = this.$refs.native;
                if (!native) {
                    return;
                }

                const keepPlaceholder = native.querySelector('option[data-placeholder="1"]');
                native.innerHTML = '';
                if (keepPlaceholder) {
                    native.appendChild(keepPlaceholder);
                }

                (items || []).forEach((item) => {
                    const option = document.createElement('option');
                    option.value = item.value == null ? '' : String(item.value);
                    option.textContent = item.label == null ? '' : String(item.label);
                    native.appendChild(option);
                });

                const nextValue = selectedValue == null ? '' : String(selectedValue);
                const exists = Array.from(native.options).some(option => option.value === nextValue);
                this._value = exists ? nextValue : (native.value || '');
                native.value = this._value;
                this.optionsVersion++;

                if (! conserverRecherche) {
                    this.search = '';
                }

                this.focusedIndex = -1;
            },
        };
    };
}
</script>
@endpush
@endonce

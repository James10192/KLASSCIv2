# Rule: Premium Selects — JAMAIS de `<select>` natif visible

## Quand s'active

Cette rule s'active automatiquement quand :
- Tu écris ou modifies une vue Blade qui contient `<select>`, `<option>`, ou un dropdown
- Tu crées une page de filtres, un formulaire, ou un modal avec un sélecteur
- Tu **réutilises un partial Blade contenant un picker** (`<x-au-mention-picker>`, `<x-au-user-picker>`, `<x-au-select>`) dans un **modal AJAX** chargé via `fetch + innerHTML`
- Tu es dans une page namespace premium (`au-*`, `pi-*`, `mi-*`, `ee-*`, `ec-*`, `is-*`, `nm-*`, `rl-*`, `dc-*`, `fc-*`, `ps-*`, `ph-*`, `bav-*`, `ie-*`, `ci-*`, `cs-*`, `mc-*`, `sc-*`, `fi-*`, `sf-*`, `cr-*`, `gp-*`, `et-*`, ...)
- Tu vois un `<select class="form-select">`, `<select class="form-control">`, ou `<select>` raw dans le diff

## Règle absolue

**Aucun `<select>` natif ne doit être visible dans une page KLASSCI au design premium.** Toute liste déroulante passe par les composants Blade `<x-au-select>` ou `<x-au-user-picker>`, ou un clone documenté de ces composants si un cas particulier le justifie (cascade filière → niveau → classe, picker de matières, etc.).

Le `<select>` natif est tolérable UNIQUEMENT dans :
- Pages legacy pas encore redesignées premium (à migrer au fur et à mesure)
- Templates PDF (DomPDF — pas d'interaction)
- Pages backoffice technique uniquement accessibles serviceTechnique
- Cas où DomPDF / l'export Excel a besoin du select source de vérité (c'est exactement ce que `<x-au-select>` préserve déjà via son `<select>` caché interne)

## Pourquoi

**Marcel a explicitement dit le 3 mai 2026 :** « les selects sont hyper moches alors que l'input est très design premium ». Le bug : sur une page premium (hero gradient KLASSCI, cards à border-radius 14px, palette monochrome bleu, ombres multicouches), un `<select>` natif apparaît avec :
- Le styling default du browser (Chrome/Firefox/Safari ont chacun le leur — incohérent)
- La flèche `▼` native qui ne ressemble à rien d'autre dans la page
- Aucune recherche, aucun groupement, aucun avatar, aucune indication visuelle de l'état actif
- Sur Windows : un look gris-bleu-pétouille des années 2000

→ Disruptif, casse l'illusion de produit fini, perte de confiance utilisateur.

## Le pattern : `<x-au-select>` (générique)

```blade
<x-au-select
    name="event"
    :value="request('event')"
    placeholder="Tous événements"
    icon="fa-bolt"
    :searchable="false"
    :options="[
        'created' => 'Création',
        'updated' => 'Modification',
        'deleted' => 'Suppression',
    ]" />
```

Props :
| Prop | Type | Défaut | Description |
|---|---|---|---|
| `name` | string | null | Form field name. Indispensable pour un form submit GET/POST. Inutile si on utilise `x-model` côté Alpine. |
| `value` | mixed | `''` | Valeur sélectionnée par défaut (pour rendu server-side). |
| `options` | array \| Collection | `[]` | Tableau associatif `['key' => 'Label']` OU tableau de `['value' => x, 'label' => y]`. Le composant normalise les deux. |
| `placeholder` | string | `'Sélectionner…'` | Texte affiché tant que rien n'est sélectionné. Devient le 1er `<option value="">` du native. |
| `icon` | string\|null | null | Icône Font Awesome affichée à gauche du value (ex: `fa-filter`, `fa-bolt`, `fa-cubes`). |
| `searchable` | bool | false | Active une input de recherche en haut du dropdown (debounce instant). À mettre à true dès que `count($options) > 8`. |
| `placeholderIsFirstOption` | bool | true | Si false, pas d'option vide en tête (utile quand la valeur est obligatoire). |

**Bindings supportés** (passés via `$attributes`, propagés au `<select>` natif caché) :
- `x-model="filters.event"` — bind Alpine bidirectionnel
- `@change="reload()"` — handler natif de change event
- `onchange="this.form.submit()"` — handler inline (legacy)
- `class="..."` — appliquée au **wrapper** `.au-select` (pas au native), permet d'ajouter des modifiers comme `au-filter-grow`

**Ce que le composant fait sous le capot** :
1. Rend un `<select class="au-select-native">` caché (`opacity:0; clip:rect(0,0,0,0)`) qui reste la **source de vérité** pour le DOM. Tout `x-model`, `@change`, et form submit fonctionnent normalement dessus.
2. Rend par-dessus un `<button class="au-select-trigger">` stylé (bg blanc, border, padding, caret animé), un menu Alpine `x-show="open"` avec recherche optionnelle et options stylées, et un input search au top.
3. La sync se fait via Alpine `$watch('_value', v => { native.value = v; native.dispatchEvent(new Event('change', {bubbles:true})) })`. Ainsi un x-model parent capture les changes.

**Ce que le composant ne fait PAS** :
- Multi-select (à créer si besoin — pattern à étendre, pas à hacker)
- Async loading (`<select>` natif est synchrone, pas d'API)
- Tag input (chips éditables — autre composant)
- Cascade dépendant (un select dont les options dépendent d'un autre — voir « Cas custom »)

## Le pattern : `<x-au-user-picker>` (utilisateurs)

Spécialisé pour sélectionner un User. Recherche live + groupement par rôle + avatars.

```blade
<x-au-user-picker
    name="user_id"
    :value="$selectedUser?->id"
    :users="$users"
    placeholder="— Tous les utilisateurs —"
    :submit-on-change="true" />
```

**Le controller doit eager-load les rôles** pour éviter N+1 :

```php
$users = User::select('id', 'name', 'email', 'username')
    ->with('roles:id,name')
    ->orderBy('name')
    ->get();
```

Groupement automatique par rôle (Super Admin, Service Technique, Secrétaire, Coordinateur, Comptable, Caissier, Enseignant, Étudiant) avec couleur sémantique + icône + avatar circle (initiale du name).

Recherche traverse name + email + username + label de rôle. Empty state si aucun match.

`submit-on-change="true"` → submit le `<form>` parent dès qu'un user est sélectionné (utile sur les pages de surveillance comme `/audit/user-activity`).

## Le pattern : créer un composant custom (cas particuliers)

Quand le picker doit afficher des données autres que User ou un dropdown KV simple (ex: picker de classes par filière, picker de matières par UE, picker de séances par jour…), **NE PAS** partir de zéro avec une lib externe. Cloner `<x-au-select>` ou `<x-au-user-picker>` :

```blade
{{-- resources/views/components/au-classe-picker.blade.php --}}
@props([
    'name' => 'classe_id',
    'value' => null,
    'classes' => collect(),  // collection avec ->load('filiere', 'niveauEtude')
    'placeholder' => '— Toutes les classes —',
])

@php
    // Grouper par filière puis trier par niveau
    $grouped = $classes->groupBy('filiere.name')->map(fn ($bucket) => [
        'label' => $bucket->first()->filiere->name,
        'classes' => $bucket->sortBy('niveauEtude.ordre')->values()->all(),
    ])->values();
@endphp

<div class="au-cp" x-data="auClassePicker()" data-groups='@json($grouped)' data-current="{{ $value ?? '' }}">
    {{-- même structure que au-user-picker : trigger + menu + groupes + options + input hidden native --}}
</div>

@once
@push('styles')
<style>
.au-cp { ... }  /* clone visuel de au-up avec namespace cp- */
</style>
@endpush
@push('scripts')
<script>
window.auClassePicker = function() { ... };  /* clone fonctionnel */
</script>
@endpush
@endonce
```

Règles à respecter pour un nouveau composant picker :
1. **`<input type="hidden" name="..." x-ref="native">` reste la source de vérité** — pour que form submit + x-model parent + tous les handlers natifs fonctionnent
2. **`@once @push`** sur les styles et scripts — pour ne pas dupliquer si plusieurs occurrences sur la page
3. **Recherche au minimum si > 8 options** — c'est le seuil au-delà duquel scanner devient fastidieux
4. **Avatars / chips colorés** quand l'entité a une couleur sémantique (User → role color, Classe → filière color, Évaluation → type color)
5. **Empty state premium** : icône + message dynamique avec la query
6. **Mobile-friendly** : menu fullwidth sous 768px (`@media (max-width: 768px) { .au-cp-menu { width: calc(100vw - 2rem); } }`)
7. **Namespace dédié** au composant, pas de fuite (`au-cp-*` pour Classe Picker, `au-mp-*` pour Matière Picker, etc.)

## Comment utiliser dans une page premium

```blade
{{-- DANS une .au-filters-row (flex container), placer DIRECTEMENT le composant --}}
<form method="GET" class="au-filters">
    <div class="au-filters-row">
        {{-- Premier select grow (placeholder long) --}}
        <x-au-select
            class="au-filter-grow"
            name="model_type"
            icon="fa-filter"
            :options="$models"
            placeholder="Tous les types" />

        {{-- Deuxième select compact --}}
        <x-au-select name="event" :options="$events" placeholder="Tous événements" />

        {{-- Inputs natifs gardent leur .au-filter-field wrapper (icône prefix) --}}
        <div class="au-filter-field">
            <label><i class="fas fa-calendar"></i></label>
            <input type="date" name="date_from">
        </div>

        <button type="submit" class="au-btn au-btn--primary">
            <i class="fas fa-search"></i> Filtrer
        </button>
    </div>
</form>
```

## Anti-patterns à BLOQUER en review

1. ❌ `<select class="form-select">` ou `<select class="form-control">` direct dans une page premium — utiliser `<x-au-select>`.
2. ❌ Wrapper `<x-au-select>` dans `<div class="au-filter-field">` — le composant a son propre styling premium, le wrapper le neutralise (problème vu le 3 mai 2026 : trigger devenait transparent + sans border).
3. ❌ `<select onchange="window.location.href = '?...' + this.value">` — utiliser `:submit-on-change="true"` sur le composant ou `@change="reload()"` Alpine.
4. ❌ Charger Select2 ou Choices.js pour un nouveau picker — on a déjà nos composants Alpine custom qui matchent le design system. Utiliser ceux-là ou les cloner.
5. ❌ `<select>` natif avec `style="..."` inline pour essayer de le rendre joli — le browser ignore une grande partie des styles natifs (notamment la flèche). Utiliser le composant.
6. ❌ Créer un composant dropdown qui utilise `<div role="combobox">` sans `<select>` natif caché — perd la sémantique form, casse les a11y, casse les handlers natifs.
7. ❌ Composant qui hardcode une liste d'options statique au lieu de prendre `$options` en prop — pas réutilisable.
8. ❌ Utiliser `<x-au-select>` mais oublier `name=` quand il y a un `<form>` GET/POST autour — le filtre ne sera jamais soumis.
9. ❌ Eager-loading manquant sur `<x-au-user-picker>` (pas de `->with('roles:id,name')` côté controller) — N+1 garanti à chaque rendu.
10. ❌ Garder un `<select>` natif visible « parce que ça marche » sur une page premium — c'est exactement ce que cette rule interdit.

## Checklist AVANT commit d'une page avec sélecteurs

- [ ] `grep -n "<select" path/to/view.blade.php` retourne 0 occurrence visible (les `<select>` cachés des composants `<x-au-*>` sont OK car ils sont en `clip:rect(0,0,0,0)`)
- [ ] Tous les sélecteurs sont des composants `<x-au-select>`, `<x-au-user-picker>`, ou un clone documenté
- [ ] Aucun `<x-au-select>` enveloppé dans `<div class="au-filter-field">` (sauf à l'avoir documenté pour un cas exceptionnel)
- [ ] Si présence d'un `<x-au-user-picker>` : controller eager-load `->with('roles:id,name')`
- [ ] Form submit testé manuellement (sélection → submit → query string ou body POST contient bien la valeur)
- [ ] Mobile testé (< 768px) — menu fullwidth, pas de débordement horizontal
- [ ] Si `searchable="true"` : test avec une query qui retourne 0 résultats (empty state)

## ⚠ Pattern AJAX-safe pour pickers premium dans un modal

**Incident fondateur** : 14 mai 2026 — le modal "Nouvelle classe" / "Modifier classe" sur `/esbtp/classes` ne basculait pas en mode LMD quand l'utilisateur choisissait niveau Licence/Master/Doctorat. La page standalone `/esbtp/classes/create` marchait parfaitement. Bug invisible aux 4 agents (Critic + Codebase + Docs + DevAdvocate) lors du premier `/plan-and-confirm`.

### Le piège du `@push('scripts')` + `innerHTML` (3 couches indépendantes)

Quand un partial Blade contient un picker premium (`<x-au-mention-picker>`, `<x-au-user-picker>`, `<x-au-parcours-picker>`, etc.) ET est chargé via AJAX dans un modal :

1. **`@push('scripts')` sans `@stack('scripts')` parent → contenu DROPPÉ silencieusement**. Le partial AJAX est rendu seul (pas dans un layout), donc le `@stack('scripts')` du layout n'est jamais exécuté. Toute factory définie dans `@push('scripts')` est silently lost.
2. **`<script>` injecté via `element.innerHTML = html` N'EST PAS EXÉCUTÉ**. Comportement standard du navigateur depuis 1999. Le `<script>` reste dans le DOM mais inerte.
3. **Alpine 3 NE SCANNE PAS** automatiquement le DOM injecté via `innerHTML`. `x-data="myFactory()"` n'est pas évalué tant que `Alpine.initTree(target)` n'est pas appelé.

### Solution canonique (3 mai 2026 onwards)

**Côté composants picker** (au-mention-picker, au-user-picker, futur au-parcours-picker) :
- **NE PAS utiliser** `@once @push('styles')` ni `@once @push('scripts')` pour les factories Alpine
- **À LA PLACE** : inline `<style>` + `<script>` avec **idempotency guards** :

```blade
<style>
.au-mp { ... }
</style>

<script>
if (typeof window.auMentionPicker !== 'function') {
    window.auMentionPicker = function () { return { /* ... */ }; };
}
</script>
```

L'idempotency guard `if (typeof window.X !== 'function')` empêche le double-register quand le composant est rendu plusieurs fois sur la même page (ex: modal create + modal edit coexistent dans `classes.index`).

**Côté handler modal AJAX** (caller, ex: `index.blade.php`) :
- **NE PAS faire** `modalBody.innerHTML = html` directement
- **À LA PLACE** : utiliser un helper qui re-crée les `<script>` tags pour qu'ils s'exécutent ET appelle `Alpine.initTree()` :

```js
function injectHtmlWithScripts(target, html) {
    target.innerHTML = html;
    target.querySelectorAll('script').forEach(function(oldScript) {
        const newScript = document.createElement('script');
        Array.from(oldScript.attributes).forEach(function(attr) {
            newScript.setAttribute(attr.name, attr.value);
        });
        newScript.textContent = oldScript.textContent;
        oldScript.parentNode.replaceChild(newScript, oldScript);
    });
    if (window.Alpine && typeof window.Alpine.initTree === 'function') {
        window.Alpine.initTree(target);
    }
}
```

Appel : `injectHtmlWithScripts(modalCreateBody, html)` au lieu de `modalCreateBody.innerHTML = html`.

**Côté Alpine factory** (anti memory leak) :
Si la factory ajoute des `window.addEventListener` dans `init()`, **OBLIGATOIRE** d'implémenter `destroy()` pour cleanup. Alpine appelle `destroy()` automatiquement quand le composant est retiré du DOM (modal close + innerHTML replace).

```js
window.classeLmdForm = function () {
    return {
        _mentionChangedHandler: null,
        init() {
            this._mentionChangedHandler = (ev) => { /* ... */ };
            window.addEventListener('mention:changed', this._mentionChangedHandler);
        },
        destroy() {
            if (this._mentionChangedHandler) {
                window.removeEventListener('mention:changed', this._mentionChangedHandler);
                this._mentionChangedHandler = null;
            }
        }
    };
};
```

Sans `destroy()`, chaque re-open de modal ajoute un handler supplémentaire → memory leak + double-fire.

### Checklist obligatoire AVANT push pour TOUT partial Blade utilisé en modal AJAX

- [ ] `grep "@push\|@once" mon_partial.blade.php` → 0 occurrence (sauf à l'intérieur de `{{-- ... --}}` comments)
- [ ] Le `<script>` factory du partial a une **idempotency guard** (`if (typeof window.X !== 'function')`)
- [ ] Le caller modal utilise `injectHtmlWithScripts(target, html)` PAS `target.innerHTML = html`
- [ ] Si Alpine listener `window.addEventListener` dans `init()` → méthode `destroy()` correspondante
- [ ] **Audit blade-pitfalls** : `grep "<x-" dans commentaires JS` → 0 occurrence (rule `blade-pitfalls.md` Pitfall #3 — bug live prod 14 mai 2026, 500 sur `/esbtp/classes`)
- [ ] Test Feature minimal : `assertSee('window.X', false)` dans la réponse AJAX

### Anti-patterns à BLOQUER en review (suite)

11. ❌ `@push('scripts')` dans un partial utilisé en modal AJAX (drop silent)
12. ❌ `@once` autour d'une factory globale (inutile en AJAX, fausse sécurité)
13. ❌ `innerHTML = html` sans `injectHtmlWithScripts` quand le HTML contient des `<script>` qui doivent exécuter
14. ❌ `window.addEventListener` dans Alpine `init()` sans `destroy()` cleanup → memory leak
15. ❌ Refactor d'un picker sans audit blade-pitfalls AVANT push (`<x-` dans commentaire = 500 prod)

## Voir aussi

- `<x-au-select>` : `resources/views/components/au-select.blade.php`
- `<x-au-user-picker>` : `resources/views/components/au-user-picker.blade.php`
- `<x-au-mention-picker>` : `resources/views/components/au-mention-picker.blade.php` (AJAX-safe depuis 14 mai 2026)
- Rule sœur : `.claude/rules/premium-redesign.md` (palette KLASSCI, namespace, hero pattern)
- Rule sœur : `.claude/rules/no-mvp-only-premium.md` (interdit le MVP, exige le production-grade)
- Rule sœur : `.claude/rules/blade-pitfalls.md` (Pitfall #3 : `<x-` dans commentaire JS = 500)
- Rule sœur : `.claude/rules/pre-merge-checklist.md` (discipline tests + visual-check pre-merge)
- Mémoire incident fondateur : session 3 mai 2026, PR #323 + commit `1cc85d0f` (fix trigger transparent dans `.au-filter-field`)
- Mémoire incident PR #393 (14 mai 2026) : modal LMD switch + listener leak Critic discovery
- Rule globale : `~/.claude/rules/marcel-global-preferences.md` (no AI slop, monochrome bleu)

# Rule: Premium Selects — JAMAIS de `<select>` natif visible

## Quand s'active

Cette rule s'active automatiquement quand :
- Tu écris ou modifies une vue Blade qui contient `<select>`, `<option>`, ou un dropdown
- Tu crées une page de filtres, un formulaire, ou un modal avec un sélecteur
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

## Voir aussi

- `<x-au-select>` : `resources/views/components/au-select.blade.php`
- `<x-au-user-picker>` : `resources/views/components/au-user-picker.blade.php`
- Rule sœur : `.claude/rules/premium-redesign.md` (palette KLASSCI, namespace, hero pattern)
- Rule sœur : `.claude/rules/no-mvp-only-premium.md` (interdit le MVP, exige le production-grade)
- Mémoire incident fondateur : session 3 mai 2026, PR #323 + commit `1cc85d0f` (fix trigger transparent dans `.au-filter-field`)
- Rule globale : `~/.claude/rules/marcel-global-preferences.md` (no AI slop, monochrome bleu)

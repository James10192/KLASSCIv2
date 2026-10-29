# Rule: Blade + Alpine — Pièges UI courants

## Quand s'active

Quand tu modifies un fichier `.blade.php` avec composants Alpine (`x-data`, `:class`, `:style`, `x-show`, `@click`), surtout des toggle buttons / filter chips / state-dependent buttons.

## Pourquoi cette rule existe

Marcel 16 mai 2026 (incident emploi-temps Suivi heures) : après refactor du toggle Semestre/Année en AJAX, les boutons ont perdu leur design (border-radius, padding, font-size) car `:style` Alpine override TOUS les styles inline statiques.

## Piège #1 — Alpine `:style` override le `style` inline statique

**Règle** : Sur un même élément, Alpine `:style="..."` **remplace** tout le `style="..."` inline, il ne **merge pas**. Les border-radius, padding, font-size statiques disparaissent au render.

### ❌ INTERDIT

```blade
<button
    style="padding:.45rem .85rem;border-radius:7px;font-size:.78rem;font-weight:600;border:none;cursor:pointer"
    :style="active ? 'background:#0453cb;color:#fff' : 'background:transparent;color:#475569'">
```

→ Au render, seul `background+color` apparaît. Le bouton perd radius/padding/typo.

### ✅ CORRECT — CSS class statique + `:class` toggle

```blade
<style>
    .xx-btn { padding:.45rem .85rem; border-radius:7px; font-size:.78rem; font-weight:600; border:none; cursor:pointer; background:transparent; color:#475569; transition:background .15s, color .15s; }
    .xx-btn:hover:not(.xx-btn--active):not(:disabled) { background:rgba(4,83,203,.06); color:#0453cb; }
    .xx-btn--active { background:#0453cb; color:#fff; }
    .xx-btn:disabled { opacity:.6; cursor:wait; }
</style>
<button class="xx-btn" :class="active ? 'xx-btn--active' : ''">Action</button>
```

**Pourquoi pas `:style="{ background:..., color:... }"` objet syntax** : marche aussi mais moins maintenable (pas de hover/disabled states, pas de media queries). Garder le CSS dans `<style>` block ou dans `@push('styles')` pour cohérence avec namespace rules.

## Piège #2 — `@push('scripts')` dans partial AJAX silently dropped

Quand un partial Blade contient `@push('scripts')` ET est rendu via `view()->render()` ou injecté via `element.innerHTML = html`, le `@push` ne s'exécute jamais (pas de `@stack` parent disponible).

**Symptômes** : factory Alpine définie dans le partial = `undefined` au runtime. Logs JS error `etsSuiviToggle is not defined`.

**Fix** : voir rule sœur [`premium-selects.md`](premium-selects.md) section "Pattern AJAX-safe pour pickers premium dans un modal" — `<script>` inline avec idempotency guard `if (typeof window.X !== 'function')`.

## Piège #3 — Mix `style="..."` HTML + `:style="..."` Alpine

L'antipattern : avoir les 2 attributs sur le même élément. **Alpine remplace tout au render.**

Si tu veux conditional styling, **TOUJOURS** :
- Style statique → CSS class dans `<style>` block
- Conditional → `:class="active ? 'modifier' : ''"`

## Anti-patterns à BLOQUER en review

1. ❌ Inline `style="..."` + Alpine `:style="..."` sur même élément (toggle visuel cassé)
2. ❌ `@push('scripts')` dans partial rendu en AJAX (innerHTML / fetch + replace)
3. ❌ Re-déclarer `window.factoryName` sans idempotency guard `if (typeof window.X !== 'function')`
4. ❌ Hardcoder colors dans `:style` au lieu d'utiliser CSS class active

## Voir aussi

- [`.claude/rules/blade-pitfalls.md`](blade-pitfalls.md) — directives `@xxx` dans commentaires JS, `@php(...)` shortform
- [`.claude/rules/premium-selects.md`](premium-selects.md) — pattern AJAX-safe pour pickers premium dans modal
- [`.claude/rules/premium-redesign.md`](premium-redesign.md) — design system KLASSCI, namespace CSS
- Mémoire `feedback_alpine_style_override_pitfall.md` (incident fondateur 16/05/2026)

# Premium Redesign — Conventions KLASSCI

## Quand s'active

Cette rule s'active quand on redesign une page Blade (vue) avec du CSS premium.

## Design System KLASSCI

**Palette monochrome bleu** — une seule couleur, jamais multicolore :
```css
--primary:    #0453cb     /* Bleu KLASSCI */
--primary-d:  #033a8e     /* Hover/dark */
--secondary:  #5e91de     /* Gradient end */
--accent:     #3b7ddb     /* Intermédiaire */
--dark:       #0f172a     /* Texte principal */
--text:       #1e293b     /* Texte body */
--muted:      #64748b     /* Labels, hints */
--success:    #10b981     /* Statuts positifs uniquement */
--surface:    #f8fafc     /* Fond léger */
```

**INTERDIT** : purple `#7c3aed`, amber `#f59e0b` (sauf warnings fonctionnels), rouge `#ef4444`, multicolore, AI slop (dark mode, gradient orbs, bento grids, glassmorphism, animated counters).

## Namespace CSS (OBLIGATOIRE)

Chaque page a son propre namespace pour éviter les conflits avec les classes globales :

| Page | Namespace | Exemple |
|------|-----------|---------|
| inscriptions.show | `is-*` | `is-hero`, `is-card`, `is-info-grid` |
| inscriptions.edit | `ie-*` | `ie-hero`, `ie-card` |
| matieres.create | `mc-*` | `mc-hero`, `mc-card` |
| relances.index | `rl-*` | `rl-hero`, `rl-kpi`, `rl-filters` |
| notes.index | `nm-*` | `nm-hero`, `nm-card` |
| situation financière | `sf-*` | `sf-hero` |

Pour une nouvelle page : choisir un préfixe 2-3 lettres unique, documenter ici.

## Composants premium récurrents

### Hero
```css
.XX-hero {
    background: linear-gradient(135deg, #071631 0%, #0a2d6e 35%, #0453cb 70%, #3674d1 100%);
    border-radius: 18px;
    padding: 2.25rem;
    box-shadow: 0 8px 32px rgba(4,83,203,.18), 0 2px 8px rgba(15,23,42,.1),
                inset 0 1px 0 rgba(255,255,255,.08);
}
/* ::before = radial highlights, ::after = decorative circle */
```

### Card premium
```css
.XX-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06); /* sm */
    /* hover → 0 8px 30px rgba(4,83,203,.08), 0 2px 8px rgba(15,23,42,.04) */ /* lg */
}
```

### Icônes dans carrés teintés
```css
.XX-icon {
    width: 36px; height: 36px;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    background: rgba(4,83,203,.08);
    color: var(--XX-primary);
}
```

### Section header
```css
.XX-section-header {
    /* Icône dans cercle gradient + titre + sous-titre */
}
```

### Ombres multicouches (3 niveaux)
```css
--shadow-sm: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06);
--shadow-md: 0 4px 16px rgba(4,83,203,.06), 0 1px 3px rgba(15,23,42,.04);
--shadow-lg: 0 8px 30px rgba(4,83,203,.08), 0 2px 8px rgba(15,23,42,.04);
```

## Règles absolues

1. **Garder TOUS les éléments fonctionnels** — ne jamais supprimer un élément HTML qui a du JS attaché
2. **Ne jamais casser le JS** — conserver les IDs, data-attributes, class names référencés par JS
3. **CSS inline dans `<style>` block** avec `@push('styles')` — pas de fichier CSS séparé
4. **Mobile-first** — `@media` breakpoints pour 992px, 768px, 576px
5. **Spacing 8px grid** — `0.5rem`, `1rem`, `1.5rem`, `2rem`
6. **Font weights** : 400 (body), 500 (medium), 600 (semi), 700 (bold), 800 (extra)
7. **Transitions** : `all .2s ease` ou `all .15s ease` — jamais > .3s
8. **Border-radius** : 8-10px (petits), 12-14px (cards), 16-18px (hero)

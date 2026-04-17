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
--surface:    #f8fafc     /* Fond léger */
```

### Couleurs sémantiques autorisées (exception à la règle monochrome)

Les couleurs sémantiques **success/warning/danger** sont autorisées **quand elles portent du sens fonctionnel** — ne JAMAIS les utiliser comme simple décoration ou pour différencier des catégories neutres.

```css
--success: #10b981    /* Succès : paiement validé, action réussie, statut OK */
--warning: #f59e0b    /* Alerte : paiement en attente, délai approchant, à surveiller */
--danger:  #dc2626    /* Danger : suppression, erreur critique, paiement rejeté, delete confirm */
```

**Exemples d'usage correct (sens fonctionnel) :**
- Badge "Payé" → vert success, "En attente" → orange warning, "Annulé" → rouge danger
- Bouton "Supprimer" / "Archiver" → `btn-danger` (a11y + convention universelle)
- Alert "Paiement échoué" → alert-danger, "Paiement imminent" → alert-warning
- Modal header destructif ("Annuler inscription") → gradient rouge

**Exemples d'usage INCORRECT (décoration, différenciation neutre) :**
- Badges Types de séances CM/TD/TP → multicolore (bleu/vert/orange) → **FAUX** — utiliser monochrome tonal (opacity + icône + border-style)
- KPIs dashboard "Total étudiants" → couleur différente par KPI → **FAUX** — tous en bleu
- Boutons d'action secondaires colorés → **FAUX** — ghost/outline bleu

**Principe** : si la couleur porte une information sémantique que l'œil doit capter en <1 seconde (statut, niveau d'alerte, action destructive), elle est autorisée. Sinon, monochrome bleu.

**INTERDIT** : purple `#7c3aed`, multicolore décoratif, AI slop (dark mode, gradient orbs, bento grids, glassmorphism, animated counters), `--esbtp-green` (variable morte), couleurs arbitraires pour différencier des catégories neutres.

## Modèle de référence : Planning Général (`planning-header`)

**TOUJOURS copier le pattern du composant `planning-header.blade.php` (`ph-*`) pour les headers de page.** Ne pas inventer un nouveau design de header à chaque page.

### Structure du hero (2 rangées)
```
Rangée 1 (sm-hero-top / ph-hero-top) :
  GAUCHE : icône carré 52px + titre h1 + sous-titre
  DROITE : boutons d'action / filtres

Rangée 2 (stats-grid / ph-kpis) :
  KPIs en cards semi-transparentes, pleine largeur
```

### CSS du hero (copier tel quel, adapter le préfixe)
```css
.XX-hero {
    background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
    border-radius: 18px;
    padding: 2rem 2.5rem 1.5rem;
    color: #fff;
    margin-bottom: 1.25rem;
}

.XX-hero-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 1rem;
}

.XX-hero-left {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.XX-hero-icon {
    width: 52px; height: 52px;
    border-radius: 14px;
    background: rgba(255,255,255,.12);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255,255,255,.15);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.35rem; flex-shrink: 0; color: #fff;
}

/* Titre : BLANC sur fond bleu, jamais bleu sur bleu */
.XX-hero h1 { font-size: 1.45rem; font-weight: 700; color: #fff; margin: 0; }
.XX-hero p { color: rgba(255,255,255,.7); font-size: .88rem; margin: 0; }
```

### KPIs dans le hero (rangée 2)
```css
.XX-kpis {
    display: flex;
    gap: .75rem;
    margin-top: 1.5rem;
    flex-wrap: wrap;
}

.XX-kpi {
    flex: 1; min-width: 140px;
    background: rgba(255,255,255,.1);
    border: 1px solid rgba(255,255,255,.15);
    border-radius: 12px;
    padding: .9rem 1rem;
    display: flex; align-items: center; gap: .75rem;
}

.XX-kpi-value { font-size: 1.35rem; font-weight: 700; color: #fff; }
.XX-kpi-label { font-size: .72rem; color: rgba(255,255,255,.65); margin-top: .15rem; }
```

### Boutons dans le hero
```css
/* Glass (fond bleu) */
.XX-btn--glass {
    background: rgba(255,255,255,.15);
    color: #fff;
    border: 1px solid rgba(255,255,255,.2);
    border-radius: 10px;
    padding: .5rem 1rem;
    font-size: .82rem;
    font-weight: 600;
}

/* Action principale (blanc) */
.XX-btn--white {
    background: #fff;
    color: #0453cb;
    border-color: transparent;
}
```

## Pages SANS hero (formulaires)

Les pages de formulaire (create, edit) utilisent le **`dashboard-header` standard** de `dashboard-moderne.css` — pas de hero gradient. Le hero est réservé aux pages de listing/dashboard.

```blade
<div class="dashboard-header">
    <div class="header-left">
        <h1><i class="fas fa-plus-circle me-2"></i>Titre</h1>
        <p class="header-subtitle">Sous-titre</p>
    </div>
    <div class="header-actions">
        <a href="..." class="btn-acasi secondary"><i class="fas fa-arrow-left"></i>Retour</a>
    </div>
</div>
```

## Namespace CSS (OBLIGATOIRE)

Chaque page a son propre namespace pour éviter les conflits avec les classes globales :

| Page | Namespace | Exemple |
|------|-----------|---------|
| inscriptions.show | `is-*` | `is-hero`, `is-card`, `is-info-grid` |
| inscriptions.edit | `ie-*` | `ie-hero`, `ie-card` |
| matieres.create | `mc-*` | `mc-hero`, `mc-card` |
| relances.index | `rl-*` | `rl-hero`, `rl-kpi`, `rl-filters` |
| frais.configure | `fc-*` | `fc-hero`, `fc-card`, `fc-grid` |
| notes.index | `nm-*` | `nm-hero`, `nm-card` |
| situation financière | `sf-*` | `sf-hero` |
| student-messages | `sm-*` | `sm-hero-top`, `sm-hero-icon` |
| planning-header | `ph-*` | `ph-hero`, `ph-kpi`, `ph-tab` (composant réutilisable) |

Pour une nouvelle page : choisir un préfixe 2-3 lettres unique, documenter ici.

## Card premium (sous le hero)
```css
.XX-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06);
}
.XX-card:hover {
    box-shadow: 0 8px 30px rgba(4,83,203,.08), 0 2px 8px rgba(15,23,42,.04);
    transform: translateY(-2px);
}
```

## Section header (icône + titre)
```css
.XX-section-header {
    display: flex; align-items: center; gap: .75rem;
}
.XX-section-icon {
    width: 40px; height: 40px; border-radius: 10px;
    background: linear-gradient(135deg, #0453cb, #3b7ddb);
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: .95rem;
}
```

## Ombres multicouches (3 niveaux)
```css
--shadow-sm: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06);
--shadow-md: 0 4px 16px rgba(4,83,203,.06), 0 1px 3px rgba(15,23,42,.04);
--shadow-lg: 0 8px 30px rgba(4,83,203,.08), 0 2px 8px rgba(15,23,42,.04);
```

## Règles absolues

1. **Copier le pattern planning-header** pour les headers de page — ne pas inventer
2. **Hero = pages listing/dashboard SEULEMENT** — pas sur les formulaires (create/edit)
3. **Titre BLANC sur hero bleu** — jamais bleu sur bleu
4. **Garder TOUS les éléments fonctionnels** — ne jamais supprimer un élément HTML qui a du JS attaché
5. **Ne jamais casser le JS** — conserver les IDs, data-attributes, class names référencés par JS
6. **CSS inline dans `<style>` block** avec `@push('styles')` — pas de fichier CSS séparé
7. **Mobile-first** — `@media` breakpoints pour 992px, 768px, 576px
8. **Spacing 8px grid** — `0.5rem`, `1rem`, `1.5rem`, `2rem`
9. **Transitions** : `all .2s ease` — jamais > .3s
10. **Border-radius** : 8-10px (petits), 12-14px (cards), 18px (hero)

# Référence de Design Moderne - ESBTP-yAKRO

## CSS Framework Principal
**Fichier :** `public/css/dashboard-moderne.css`

## Structure de Page Standard

### Header Section
```html
<div class="dashboard-header">
    <div class="header-left">
        <h1><i class="fas fa-[icon] me-2"></i>[Titre Principal]</h1>
        <p class="header-subtitle">[Description/Sous-titre]</p>
    </div>
    <div class="header-actions">
        <input type="search" class="search-bar" placeholder="Rechercher...">
        <a href="[route]" class="btn-acasi primary">
            <i class="fas fa-plus-circle"></i>[Action Principale]
        </a>
    </div>
</div>
```

### KPI Grid (Statistiques)
```html
<div class="kpi-grid">
    <div class="kpi-card card-moderne bg-primary">
        <div class="kpi-title">[Titre KPI]</div>
        <div class="kpi-value color-primary">[Valeur]</div>
        <div class="kpi-trend">
            <i class="fas fa-[icon]"></i>
            [Description]
        </div>
    </div>
    <!-- Répéter avec bg-success, bg-accent, bg-warning -->
</div>
```

### Main Card Container
```html
<div class="main-card">
    <div class="main-card-header">
        <div class="main-card-title">
            <i class="fas fa-[icon]"></i>
            [Titre de la Section]
        </div>
        <div class="main-card-subtitle">[Description de la section]</div>
    </div>
    <div class="main-card-body">
        <!-- Contenu -->
    </div>
</div>
```

## Couleurs du Système

### Classes de Couleur
- `bg-primary` : Bleu principal (#1e3a8a)
- `bg-success` : Vert (#10b981) 
- `bg-accent` : Cyan (#06b6d4)
- `bg-warning` : Orange (#f59e0b)
- `color-primary`, `color-success`, etc. : Versions texte

### Variables CSS
```css
--primary: #1e3a8a;
--secondary: #1e40af;
--success: #10b981;
--warning: #f59e0b;
--danger: #ef4444;
--accent-blue: #06b6d4;
```

## Composants de Formulaire

### Input Groups
```html
<div class="form-group-modern">
    <label class="form-label-modern">
        <i class="fas fa-[icon]"></i>
        [Label]
    </label>
    <input type="text" class="form-input-modern">
</div>
```

### Boutons Modernes
```html
<button class="btn-acasi primary">
    <i class="fas fa-[icon]"></i>[Texte]
</button>

<!-- Variantes : primary, secondary, success, warning, danger -->
```

## Layout Container
```html
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header Section -->
        <!-- KPI Grid (optionnel) -->
        <!-- Main Card -->
    </div>
</div>
```

## Bonnes Pratiques

1. **Icônes :** Toujours utiliser FontAwesome avec `me-2` pour l'espacement
2. **Couleurs :** Respecter la palette bleu/gris/blanc/noir
3. **Espacement :** Utiliser les variables CSS `--space-*`
4. **Responsive :** Le framework est mobile-first
5. **Accessibilité :** Toujours inclure les attributs ARIA appropriés

## Pages de Référence Réussies

1. **Emploi du temps étudiant** : `/esbtp/mon-emploi-temps`
2. **Évaluations étudiants** : `/esbtp/mes-evaluations` 
3. **Planning général** : `/esbtp/planning-general`
4. **Liste des évaluations** : `/esbtp/evaluations`

## Exemples de Transformation

### Avant (Bootstrap basique)
```html
<div class="card">
    <div class="card-header">
        <h5>Titre</h5>
    </div>
</div>
```

### Après (Moderne)
```html
<div class="main-card">
    <div class="main-card-header">
        <div class="main-card-title">
            <i class="fas fa-icon"></i>
            Titre
        </div>
    </div>
</div>
```
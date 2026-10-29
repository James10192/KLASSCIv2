# Guide pour les développeurs - Charte graphique ESBTP

Ce guide est destiné aux développeurs qui travailleront sur le système de gestion scolaire ESBTP. Il explique comment maintenir et étendre la charte graphique de l'École Supérieure du Bâtiment et des Travaux Publics.

## Palette de couleurs ESBTP

La charte graphique ESBTP repose sur trois couleurs principales :

- **Vert ESBTP**: `#01632f` - Couleur principale, utilisée pour les éléments importants et la barre latérale
- **Orange ESBTP**: `#f29400` - Couleur d'accentuation, utilisée pour les boutons d'action et les éléments interactifs
- **Blanc**: `#ffffff` - Couleur de fond et de texte sur les fonds colorés

## Fichier CSS ESBTP

Un fichier CSS contenant toutes les classes et variables de la charte graphique ESBTP est disponible dans `scripts_esbtp/esbtp-colors.css`. Ce fichier peut être inclus dans de nouvelles pages pour assurer la cohérence visuelle.

### Comment utiliser le fichier CSS

1. Inclure le fichier dans votre page :
   ```html
   <link rel="stylesheet" href="{{ asset('css/esbtp-colors.css') }}">
   ```

2. Utiliser les classes CSS prédéfinies :
   ```html
   <button class="btn-esbtp-green">Bouton vert</button>
   <div class="bg-esbtp-orange">Fond orange</div>
   ```

3. Ou utiliser les variables CSS dans votre propre CSS :
   ```css
   .mon-element {
       color: var(--esbtp-green);
       border-color: var(--esbtp-orange);
   }
   ```

## Principes de design à respecter

### 1. Hiérarchie des couleurs

- **Vert ESBTP** : Utilisé pour les éléments principaux (barre latérale, en-têtes, titres)
- **Orange ESBTP** : Utilisé pour les éléments d'action et d'accentuation (boutons, liens, badges)
- **Blanc** : Utilisé pour les fonds et le texte sur les fonds colorés

### 2. Accessibilité

- Assurez-vous que le contraste entre le texte et le fond est suffisant pour une bonne lisibilité
- Utilisez les classes prédéfinies qui respectent les normes d'accessibilité
- Évitez de créer des combinaisons de couleurs à faible contraste

### 3. Cohérence

- Utilisez les mêmes styles pour les mêmes types d'éléments dans toute l'application
- Respectez la hiérarchie visuelle établie
- Ne mélangez pas d'autres palettes de couleurs avec la charte ESBTP

## Composants UI

### Boutons

```html
<!-- Bouton vert (action principale) -->
<button class="btn btn-esbtp-green">Action principale</button>

<!-- Bouton orange (action secondaire) -->
<button class="btn btn-esbtp-orange">Action secondaire</button>

<!-- Bouton contour vert -->
<button class="btn btn-outline-esbtp-green">Action tertiaire</button>
```

### Cartes

```html
<div class="card card-esbtp">
    <div class="card-header card-header-esbtp-green">
        Titre de la carte
    </div>
    <div class="card-body">
        Contenu de la carte
    </div>
</div>
```

### Alertes

```html
<div class="alert alert-esbtp-green">
    Message de succès
</div>

<div class="alert alert-esbtp-orange">
    Message d'avertissement
</div>
```

### Tableaux

```html
<table class="table table-esbtp table-esbtp-striped table-esbtp-hover">
    <thead>
        <tr>
            <th>En-tête 1</th>
            <th>En-tête 2</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Donnée 1</td>
            <td>Donnée 2</td>
        </tr>
    </tbody>
</table>
```

## Création de nouvelles pages

Lors de la création de nouvelles pages, suivez ces étapes pour assurer la cohérence avec la charte graphique ESBTP :

1. Étendez le layout principal qui contient déjà les styles ESBTP :
   ```php
   @extends('layouts.app')
   ```

2. Utilisez les classes CSS ESBTP pour les nouveaux éléments

3. Pour les éléments qui ne sont pas couverts par les classes existantes, utilisez les variables CSS :
   ```css
   .mon-nouvel-element {
       background-color: var(--esbtp-green-light);
       border: 1px solid var(--esbtp-green);
   }
   ```

4. Testez votre page sur différentes tailles d'écran pour assurer la responsivité

## Bonnes pratiques

1. **Ne modifiez pas directement** les couleurs principales dans le fichier CSS principal
2. Si vous avez besoin de nouvelles variantes de couleurs, créez-les à partir des couleurs principales
3. Documentez tout nouveau composant ou classe que vous créez
4. Utilisez les icônes Font Awesome pour maintenir la cohérence visuelle
5. Testez régulièrement l'accessibilité de vos pages (contraste, lisibilité)

## Ressources

- [Palette de couleurs ESBTP](scripts_esbtp/esbtp-colors.css)
- [Bootstrap Documentation](https://getbootstrap.com/docs/)
- [Font Awesome Icons](https://fontawesome.com/icons)
- [WCAG Accessibility Guidelines](https://www.w3.org/WAI/standards-guidelines/wcag/)

## Contact

Pour toute question concernant la charte graphique ESBTP, contactez le responsable du design à [design@esbtp.edu].

---

Document créé le {{ date('d/m/Y') }} 
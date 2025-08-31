# JavaScript Patterns - ESBTP Project

## Patterns de Validation JavaScript

### 1. Gestion des Templates Dynamiques
```javascript
// Pattern pour désactiver les champs de template
function disableTemplateFields() {
    const template = document.getElementById('parent-template');
    if (template) {
        template.querySelectorAll('input, select, textarea').forEach(input => {
            input.disabled = true;
            input.removeAttribute('required');
            input.removeAttribute('data-required');
        });
    }
}
```

### 2. Validation Conditionnelle
```javascript
// Pattern pour validation basée sur l'état des checkboxes
if (checkbox && checkbox.checked) {
    // Parent existant sélectionné
    existantSection.querySelectorAll('[data-required="true"]').forEach(input => {
        input.setAttribute('required', 'required');
    });
} else {
    // Nouveau parent par défaut
    nouveauSection.querySelectorAll('[data-required="true"]').forEach(input => {
        input.setAttribute('required', 'required');
    });
}
```

### 3. Protection Continue
```javascript
// Pattern pour protection continue contre les régressions
setInterval(ensureTemplateDisabled, 500);
```

### 4. Interception de Soumission
```javascript
// Pattern pour préparation avant soumission
const form = document.querySelector('form');
if (form) {
    form.addEventListener('submit', function(e) {
        disableTemplateFields();
        // Autres préparations...
    });
}
```

## Bonnes Pratiques Identifiées

1. **Désactivation Immédiate** - Toujours désactiver les templates dès le chargement
2. **Vérification Périodique** - Utiliser des intervals pour maintenir l'état
3. **Appels Après Modifications** - Appeler les fonctions de protection après chaque changement
4. **Préparation Avant Soumission** - Nettoyer le DOM avant soumission

## Fichiers Concernés
- `resources/views/esbtp/inscriptions/create.blade.php`
- `resources/views/esbtp/inscriptions/show.blade.php`

## Outils JavaScript Utilisés
- `querySelector` / `querySelectorAll`
- `addEventListener`
- `setInterval`
- `setAttribute` / `removeAttribute`
- `forEach`

## Debugging Tips
- Utiliser la console pour vérifier les attributs `required`
- Tester avec différents états de formulaire
- Vérifier les templates cachés dans l'inspecteur
# Fix Erreur Validation Parents Template - Formulaire Inscription

## Contexte
- **Date**: 2025-01-14
- **Fichier**: `resources/views/esbtp/inscriptions/create.blade.php`
- **Problème**: Les champs `parents[template]` du template HTML empêchaient la soumission du formulaire
- **Erreur Console**: `An invalid form control with name='parents[template][prenoms]' is not focusable`

## Description du Problème

Le template HTML `#parent-template` contient des champs avec `data-required="true"` qui étaient automatiquement marqués comme `required` par le JavaScript, même quand le template était caché. Cela empêchait la soumission du formulaire car le navigateur validait tous les champs `required` du DOM, y compris les champs cachés.

### Erreurs Observées
```javascript
create:3158 An invalid form control with name='parents[template][prenoms]' is not focusable
create:3158 An invalid form control with name='parents[template][telephone]' is not focusable
```

## Solution Implémentée

### 1. Fonction `ensureTemplateDisabled()`
```javascript
function ensureTemplateDisabled() {
    const template = document.getElementById('parent-template');
    if (template) {
        template.querySelectorAll('[data-required="true"]').forEach(input => {
            input.removeAttribute('required');
        });
    }
}
```

### 2. Initialisation au Chargement
```javascript
function initializeRequiredAttributes() {
    // Traiter le template caché - retirer tous les attributs required
    const template = document.getElementById('parent-template');
    if (template) {
        template.querySelectorAll('[data-required="true"]').forEach(input => {
            input.removeAttribute('required');
        });
    }
    // ... reste du code
}
```

### 3. Vérification Périodique
```javascript
// S'assurer que le template reste désactivé régulièrement
setInterval(ensureTemplateDisabled, 500);
```

### 4. Désactivation Avant Soumission
```javascript
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

// Intercepter la soumission du formulaire
const form = document.querySelector('form');
if (form) {
    form.addEventListener('submit', function(e) {
        disableTemplateFields();
    });
}
```

### 5. Appels Après Modifications
- Après clonage du template
- Après changement de checkbox parent existant/nouveau
- Après synchronisation des attributs

## Mécanismes de Protection

1. **Désactivation au chargement** - Retire immédiatement tous les attributs `required` du template
2. **Vérification périodique** - Un `setInterval` vérifie toutes les 500ms que le template reste désactivé
3. **Appels après modifications** - La fonction est appelée après chaque changement dans le formulaire
4. **Désactivation avant soumission** - Désactive complètement tous les champs du template avant soumission

## Résultat
- Le formulaire peut maintenant être soumis sans être bloqué par les champs du template
- Les champs `parents[template]` restent désactivés en permanence
- La validation fonctionne correctement pour les parents réels ajoutés dynamiquement

## Fichiers Modifiés
- `resources/views/esbtp/inscriptions/create.blade.php` - Ajout des fonctions de protection JavaScript

## Tests Recommandés
1. Tester soumission avec parent existant sélectionné
2. Tester soumission avec nouveau parent
3. Tester ajout/suppression de parents multiples
4. Vérifier que les champs template restent désactivés dans la console

## Notes Techniques
- Le template `#parent-template` sert de modèle pour créer dynamiquement de nouveaux parents
- Il doit rester caché et ses champs ne doivent jamais être validés
- La solution est robuste et utilise plusieurs mécanismes de protection contre les régressions
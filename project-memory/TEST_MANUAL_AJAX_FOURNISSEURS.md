# Test Manuel de l'Endpoint AJAX Fournisseurs

## Instructions pour tester l'endpoint corrigé

### 1. Se connecter au système
- URL: http://localhost:8000/login
- Username: superadmin  
- Password: password123

### 2. Aller sur la page de création de dépenses
- URL: http://localhost:8000/esbtp/comptabilite/depenses/create

### 3. Test via console navigateur
Copier-coller le script dans la console du navigateur (F12):

```javascript
// Test simple de l'endpoint
fetch('/esbtp/comptabilite/fournisseurs/ajax', {
    method: 'POST',
    body: new FormData(Object.assign(document.createElement('form'), {
        innerHTML: `
            <input name="nom" value="Test ${Date.now()}">
            <input name="email" value="test@example.com">
            <input name="_token" value="${document.querySelector('meta[name=csrf-token]').content}">
        `
    }))
}).then(r => r.json()).then(console.log).catch(console.error);
```

### 4. Test via modal
- Cliquer sur le bouton "Nouveau fournisseur"
- Remplir le formulaire:
  - Nom: Test Fournisseur
  - Email: test@test.com
  - Téléphone: 0123456789
- Cliquer sur "Créer le fournisseur"
- Vérifier qu'aucune erreur JavaScript n'apparaît
- Vérifier que le fournisseur est ajouté au select principal

### 5. Vérifications attendues

#### ✅ Succès attendu:
- Réponse JSON: `{"success": true, "fournisseur": {...}, "message": "Fournisseur créé avec succès"}`
- Modal se ferme automatiquement
- Nouveau fournisseur apparaît dans le select
- Aucune erreur JavaScript dans la console

#### ❌ Échecs possibles:
- Erreur 419: Token CSRF encore invalide
- Erreur 500: Problème dans le contrôleur ou base de données
- SyntaxError: Réponse HTML au lieu de JSON

## Script automatique

Utilisez le fichier `test-ajax-fournisseurs.js` dans la console pour un test automatique complet.

## Corrections appliquées

1. **URL corrigée** dans `public/js/klassci-modal.js`
   - Avant: `/esbtp/comptabilite/fournisseurs/ajax/store`
   - Après: `/esbtp/comptabilite/fournisseurs/ajax`

2. **Token CSRF ajouté** au FormData
   - `formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'))`

## En cas d'échec

Si l'erreur persiste, vérifier:
1. Route définie dans `routes/web.php` ligne 952
2. Méthode `storeFournisseurAjax()` dans `ESBTPComptabiliteController`
3. Modèle `ESBTPFournisseur` et table `esbtp_fournisseurs`
4. Permissions utilisateur pour `access_comptabilite_module`

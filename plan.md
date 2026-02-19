# Plan - Fix notes.index : absent toggle + backdrop

## Diagnostic des 3 bugs

### Bug 1 & 2 : Erreur 422 + input bloqué au décocher "absent"

**Séquence problématique :**
1. Cocher "absent" → `toggleAbsence()` → input disabled, `saveNote()` envoie `note:0, is_absent:'on'` → `store()` crée la note (OK)
2. Décocher "absent" → `toggleAbsence()` → input enabled, `val('')`, puis `saveNote()` envoie `note:'', is_absent:''`
3. Validation Laravel : `required_unless:is_absent,on` → note est vide ET is_absent n'est pas 'on' → **422**
4. Alerte générique "Erreur lors de la sauvegarde des notes"

**Causes profondes :**
- `store()` ne fait qu'un INSERT (pas UPSERT) et retourne des redirects (pas JSON)
- `toggleAbsence()` auto-sauvegarde avec note vide lors du décocher
- `saveNote()` ne gère pas correctement les réponses d'erreur 422

### Bug 3 : Backdrop reste après fermeture du modal

**Cause :** `#classSelectionModal` n'a pas de handler `hidden.bs.modal` pour nettoyer les backdrops. Bootstrap les retire normalement mais le code existant pour `evaluationCreateModal` peut interférer.

---

## Fichiers à modifier (3 fichiers)

### 1. `app/Http/Controllers/ESBTPNoteController.php`
Ajouter méthode `saveNoteAjax()` :
- Retourne toujours du JSON (`response()->json()`)
- Logique UPSERT : si note existe → UPDATE, sinon → CREATE
- Validation adaptée : `note` nullable (peut être 0 ou valeur réelle)
- Respecte les permissions coordinateur

### 2. `routes/web.php`
Ajouter avant le `Route::resource('notes', ...)` :
```php
Route::post('notes/save-ajax', [ESBTPNoteController::class, 'saveNoteAjax'])
    ->name('esbtp.notes.save-ajax');
```
(avant le resource pour éviter le conflit de routes)

### 3. `resources/views/esbtp/notes/index.blade.php`
Trois corrections JS :

**a) `saveNote()` :**
- Changer l'URL vers `esbtp.notes.save-ajax`
- Garder la guard : si non-absent ET note vide → ne pas sauvegarder
- Améliorer le handler d'erreur pour afficher le message JSON

**b) `toggleAbsence()` :**
- Quand cochage (isAbsent=true) : comportement actuel conservé + auto-save
- Quand décochage (isAbsent=false) : réactiver l'input, vider la valeur, **NE PAS auto-sauvegarder** (attendre la saisie d'une vraie note). L'input reçoit un placeholder visuel.

**c) Handler `hidden.bs.modal` pour `#classSelectionModal` :**
```javascript
$('#classSelectionModal').on('hidden.bs.modal', function() {
    setTimeout(() => {
        document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
    }, 150);
});
```

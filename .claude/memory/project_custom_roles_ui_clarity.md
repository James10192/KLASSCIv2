# Custom Roles — UI Clarity (Lot 16)

> Lot précédents pertinents : Lot 4 (UI roles-permissions Service Technique), Lot 5 (matrice "qui peut gérer qui"), Lot 8 (CRUD rôles custom).

## Séparation système vs custom (architecture)

KLASSCI a deux pages distinctes pour deux audiences distinctes :

| Page | Route | Audience | Rôles concernés | Source de vérité |
|------|-------|----------|-----------------|------------------|
| **Service Technique** | `/esbtp/roles-permissions` | `role:serviceTechnique` (Lot 4) | Rôles **système** : `superAdmin`, `secretaire`, `comptable`, `caissier`, `coordinateur`, `enseignant`, `etudiant` | `config/permissions.php` |
| **Personnel unifié** | `/esbtp/personnel/unified` | `users.manage` (Lot 8) | Rôles **custom** uniquement (`is_custom = true`) | DB (`roles` table + métadonnées Lot 8) |

Cette séparation est **stricte** :
- `ESBTPCustomRoleController` refuse explicitement toute action sur un rôle non-custom (vérif `is_custom = true` dans `edit/update/destroy/assignUsers/detachUser`).
- Les rôles définis dans `config('permissions.roles')` ne peuvent pas être créés via `store()` (garde-fou `reservedNames`).

## Encadré informatif (Lot 16)

Ajouté dans `unified-index.blade.php`, à l'intérieur de `cr-section-content`, juste avant la liste des rôles custom :

```blade
<div class="cr-info-note" role="note">
    <div class="cr-info-note-icon"><i class="fas fa-info-circle"></i></div>
    <div class="cr-info-note-body">
        <p class="cr-info-note-title">Rôles personnalisés gérés ici</p>
        <p class="cr-info-note-text">
            Vous pouvez créer, modifier et supprimer ces rôles, et leur assigner des utilisateurs.
            Pour modifier les permissions des <strong>rôles système</strong> (Secrétaire, Comptable, ...),
            contactez le <strong>Service Technique</strong>.
        </p>
    </div>
</div>
```

CSS namespace `cr-info-note-*` (monochrome bleu, gradient discret, border-left 3px primary). Visible uniquement quand la section "Rôles personnalisés" est dépliée.

## Pattern modal edit (miroir de create)

Les deux modals partagent :
- Même structure DOM (`cr-modal`, `cr-modal-header`, `cr-section`, etc.)
- Même `_permissions-picker.blade.php` (réutilisable, accepte `assignedPermissions` array)
- Même chargement AJAX (`fetch(url) → modalEl.innerHTML = html → bsModal.show()`)
- Même `wireModalForm()` qui détecte automatiquement `#cr-create-form` ou `#cr-edit-form`

Différences :
- `_edit-modal.blade.php` rend le `name` (slug) en **readonly** — le slug est immuable après création (référencé en DB par `users.role`)
- Le formulaire utilise `@method('PUT')` et l'URL de `update`
- `assignedPermissions` est pré-coché dans le picker

## Validation grantable permissions (sécurité)

`store()` et `update()` reproduisent **strictement le même pattern** :

```php
$requestedPerms = collect($validated['permissions'] ?? [])->unique()->values()->all();
$allowedPerms = $this->grantablePermissionsForActor($registry)
    ->flatten(1)->pluck('name')->all();
$forbidden = array_diff($requestedPerms, $allowedPerms);
if (! empty($forbidden)) {
    return response()->json(['success' => false, 'message' => '...', 'forbidden' => ...], 403);
}
```

`grantablePermissionsForActor()` filtre via `$actor->can($p->name)` — donc :
- `superAdmin` (via `Gate::before`) peut tout accorder
- Tout autre acteur ne peut accorder QUE ses propres permissions

Cela empêche l'escalade : un secrétaire ne peut pas créer un rôle « SuperSecrétaire » qui hériterait de `users.manage` puisqu'il ne possède pas cette permission.

## Whitelist d'icônes (Lot 16)

`ESBTPCustomRoleController::ALLOWED_ICONS` contient ~40 icônes Font Awesome (regroupées en Personnel & rôles, Métiers, Sécurité). Validation via `Rule::in(self::ALLOWED_ICONS)` + sanitization défensive `normalizeIcon()` qui rabat tout hors-whitelist sur `fa-user-tag`.

But : empêcher un acteur malveillant de saisir une classe arbitraire (XSS via classes CSS, `fa-skull` non-pro, etc.).

Méthode statique `allowedIcons(): array` exposée si une vue/script externe veut afficher la liste complète.

## Fichiers Lot 16

| Fichier | Changement |
|---------|------------|
| `app/Http/Controllers/ESBTPCustomRoleController.php` | + constante `ALLOWED_ICONS` + `normalizeIcon()` + `allowedIcons()` ; `Rule::in()` sur `icon` dans `store()` et `update()` |
| `resources/views/esbtp/personnel/unified-index.blade.php` | + encadré `cr-info-note` + CSS namespace |

## Tests verts

- `php artisan view:cache` ✓
- `php -d memory_limit=512M vendor/bin/phpunit tests/Unit/ --filter="PermissionRegistryTest|PermissionRegistryRoleMetaTest|ExampleTest"` → **23 tests, 79 assertions, OK**
- `php artisan permissions:audit` → 4 faux positifs connus (assignRole, manage-user, assign-role, permission), 0 alias legacy

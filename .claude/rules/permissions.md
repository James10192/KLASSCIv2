# Permissions — Convention KLASSCI

## Quand s'active

Quand tu ajoutes/modifies une permission, un rôle, une vérif `@can(...)`, `->can(...)`, `middleware('permission:...')`, ou que tu touches `config/permissions.php`.

## Source unique de vérité

- **Registry** : `config/permissions.php`
- **Lecture** : `App\Services\PermissionRegistry` (jamais `config('permissions.xxx')` direct dans le code)
- **Déploiement** : `bin/deploy/fix_permissions.php` lit le registry et synchronise rôles + permissions sur la DB tenant

Les seeders `database/seeders/*Seeder.php` sont **gitignored** (`.gitignore` contient `*Seeder.php`) et constituent du code mort en prod. Ne pas s'en servir comme référence.

## Convention canonique

Format : `domaine.action[.qualifier]` en **snake_case ASCII**.

Exemples : `students.view`, `notes.view_own`, `comptabilite.dashboard.view`, `module.caisse.access`.

**INTERDIT** :
- Espaces : `'view cycles'`, `'edit inscriptions'` → utiliser `cycles.view`, `inscriptions.edit`
- Kebab-case : `'manage-users'`, `'view-attendance-reports'` → utiliser `users.manage`, `attendances.view_reports`

Les noms non-canoniques restent supportés via le mécanisme d'**aliases** (rétrocompat Lot 6).

## Ajouter une permission

1. Éditer `config/permissions.php`, clé `permissions` :
   ```php
   'students.archive' => [
       'label' => 'Archiver un étudiant',
       'group' => 'Étudiants',
       'icon' => 'fa-archive',
       'aliases' => [],  // optionnel
   ],
   ```
2. Attribuer aux rôles via `role_defaults` (ou via UI `/esbtp/roles-permissions`).
3. Lancer `php bin/deploy/fix_permissions.php` pour propager en DB.
4. Vérifier `php artisan permissions:audit`.

## Ajouter un alias (rétrocompat)

Si un nom legacy doit continuer à fonctionner pendant la migration progressive :

```php
'inscriptions.validate' => [
    'label' => 'Valider une inscription',
    'aliases' => ['valider inscriptions', 'approve_inscriptions'],
],
```

`PermissionRegistry::canonicalize('valider inscriptions')` retournera `inscriptions.validate`. `fix_permissions.php` créera les deux noms en DB. Une fois le code migré, retirer l'alias.

## Auditer

```bash
php artisan permissions:audit            # rapport console
php artisan permissions:audit --json     # storage/app/permissions-audit.json
```

Catégories du rapport :
- **Cassées** : utilisées en code mais ni dans le registry ni en DB → bug/typo
- **Hors-registry** : en code + DB mais pas dans le registry → à canoniser
- **Aliases legacy utilisés** : à migrer Lot 6
- **Orphelines en DB** : jamais référencées → à supprimer
- **Deprecated en DB** : marquées deprecated encore assignées

## Matrice "qui peut gérer qui"

`config/permissions.php` clé `role_management` :

```php
'role_management' => [
    'superAdmin'   => ['secretaire', 'comptable', 'caissier', 'coordinateur', 'enseignant', 'etudiant'],
    'secretaire'   => ['enseignant', 'etudiant', 'caissier'],
    'caissier'     => ['etudiant'],          // pré-inscription
    'comptable'    => [],                     // ne gère personne
    'coordinateur' => ['enseignant', 'etudiant'],
],
```

Lue par `App\Services\UserManagementService`. Configurable via UI admin.

## Rôles canoniques

`config/permissions.php` clé `roles` : `superAdmin`, `secretaire`, `comptable`, `caissier`, `coordinateur`, `enseignant`, `etudiant` (visibles UI) + `serviceTechnique` (masqué). Le rôle `parent` a été supprimé (Lot 1).

## Règles absolues

1. **Pas d'espaces, pas de kebab-case** dans les nouveaux noms de permission
2. **Toujours via le registry** — jamais hardcodé dans un seeder Laravel
3. **Toujours via `PermissionRegistry`** — jamais `config('permissions.xxx')` direct
4. **`Gate::before` couvre superAdmin** — ne pas dupliquer `if (hasRole('superAdmin'))` dans le code
5. **Avant de supprimer un alias** : `php artisan permissions:audit` pour vérifier qu'aucun code ne le référence

# Permissions — Convention KLASSCI

## Quand s'active

Quand tu ajoutes/modifies une permission, un rôle, une vérif `@can(...)`, `->can(...)`, `middleware('permission:...')`, ou que tu touches `config/permissions.php`.

## Interface unique de gestion du personnel

La seule page fonctionnelle de gestion du personnel et des rôles est
`/esbtp/personnel/unified`. Les anciennes routes GET `/esbtp/personnel`,
`/esbtp/roles`, `/esbtp/roles-permissions` et `/esbtp/custom-roles`
redirigent vers cette page. Les anciennes routes POST/PUT/PATCH/DELETE
`/esbtp/personnel` sont fermées : seules les écritures du contrôleur unifié
restent publiées. Les GET techniques de lecture/export sont maintenus pour
compatibilité. Les endpoints AJAX dédiés à la création,
aux modifications et aux affectations restent autorisés par leurs gardes
serveur : les redirections ne doivent pas affaiblir leur sécurité.

`superAdmin` et `serviceTechnique` demeurent des rôles de secours
non éditables dans l'interface, l'ancienne API technique et la CLI.
Le serviceTechnique peut restaurer les droits standards depuis la modale
d'édition sur la page unifiée. Le tenant garde ses propres décisions
d'ajout et de retrait de permissions.

## Source unique de vérité

- **Registry** : `config/permissions.php`
- **Lecture** : `App\Services\PermissionRegistry` (jamais `config('permissions.xxx')` direct dans le code)
- **Déploiement** : `bin/deploy/fix_permissions.php` lit le registry et synchronise rôles + permissions sur la DB de l'instance

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
2. Attribuer aux rôles via `role_defaults` (ou via les modales de l'écran unique `/esbtp/personnel/unified`, section Rôles).
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

## Gestion du personnel : permissions effectives exclusivement

`UserManagementService`, `UserManagementPolicy` et les services d'affectation
ne doivent **jamais** autoriser une personne d'après le nom de son rôle.

- Consulter : `personnel.view` ou permission de gestion adaptée.
- Modifier / désactiver : `personnel.manage` ou `users.manage`, hors auto-gestion,
  avec périmètre de permissions de la cible. La capacité explicite
  `personnel.roles.manage_all` peut élargir ce périmètre, mais ne permet pas
  de modifier le cycle de vie d'un compte sensible.
- Attribuer un rôle ordinaire : `personnel.roles.assign`, plus les permissions
  contenues dans le rôle cible. Les autres mutations exigent leur droit
  spécifique. Ne jamais autoriser par `hasRole()` de l'opérateur.
- Comptes `superAdmin` et `serviceTechnique` : noms utilisés seulement pour
  **identifier un état protégé**, jamais pour autoriser l'acteur. Leur
  transformation et déclassement passent par un workflow de validation
  distincte ; en attendant, refuser sans side-effect.
- `superadmin.accounts.create`, `paywall.manage` et
  `system.technical_access` sont accordés par défaut au service technique,
  PAS au joker `superAdmin`. Un tenant peut attribuer explicitement ces
  permissions, avec audit. La synchronisation doit respecter les extensions et
  révocations déclarées par l'établissement.
- Multi-rôles : le profil de session sélectionné n'est que **l'espace de
  travail affiché**. Il ne modifie pas l'identité et ne peut donner de droits.

`config/permissions.php.role_management` subsiste pour compatibilité
historique mais **n'est pas une source d'autorisation** ; ne pas y réintroduire
une matrice « quel rôle peut gérer quel rôle ». L'unique interface est
`/esbtp/personnel/unified`.

## Rôles canoniques

`config/permissions.php` clé `roles` : `superAdmin`, `secretaire`, `comptable`, `caissier`, `coordinateur`, `directeurEtudes`, `enseignant`, `etudiant` (visibles UI) + `serviceTechnique` (masqué). Le rôle `parent` a été supprimé (Lot 1).

## Règles absolues

1. **Pas d'espaces, pas de kebab-case** dans les nouveaux noms de permission
2. **Toujours via le registry** — jamais hardcodé dans un seeder Laravel
3. **Toujours via `PermissionRegistry`** — jamais `config('permissions.xxx')` direct
4. **Aucun passe-droit global par nom de rôle** : même `superAdmin`/`serviceTechnique` sont soumis aux permissions effectives. `Gate::before` ne doit jamais retourner `true` à partir de `hasRole` ; le contrôle d'un état protégé n'est pas une permission.
5. **Avant de supprimer un alias** : `php artisan permissions:audit` pour vérifier qu'aucun code ne le référence

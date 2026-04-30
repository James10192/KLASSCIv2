# Changelog

Toutes les évolutions notables de KLASSCI sont consignées dans ce fichier.

Le format suit [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/) et le projet adopte un versioning par mois (`YYYY.MM`) plutôt que SemVer strict, du fait du modèle SaaS multi-tenant déployé en continu.

> Discipline de mise à jour : voir [.claude/rules/changelog.md](.claude/rules/changelog.md). Toute modification visible utilisateur, breaking change, migration data, ou refactor structurel doit ajouter une entrée sous `[Unreleased]` au moment du commit.

---

## [Unreleased]

### Added

- _Lot 8 — Custom roles CRUD + assign users (en cours)._
- _Lot 9 — Dashboard widgets configurables (en cours)._
- _Lot 10 — UI permission-aware sans 403 sur la page entière (en cours)._
- _Lot 11 — Rules `.claude/rules/memory-updates.md` + `.claude/rules/changelog.md` + ce `CHANGELOG.md` initial._

### Changed

### Deprecated

### Removed

### Fixed

### Security

---

## [2026.04] - 2026-04-30

Refonte complète du système de rôles & permissions (Lots 0-7). Source unique de vérité dans `config/permissions.php`, lue via `App\Services\PermissionRegistry`. Suppression du rôle `parent` (jamais utilisé en prod) et nettoyage de ~1 500 lignes de code mort. Refonte UI `/esbtp/roles-permissions` registry-driven, commande d'audit, healing automatique des tenants existants.

### Added

- Registry centralisé des rôles & permissions (`config/permissions.php`) avec 154 permissions canoniques (dot.notation), labels FR, aliases legacy et matrice "qui peut gérer qui".
- Service `App\Services\PermissionRegistry` (lecture registry, canonicalize, role meta) — point d'entrée unique pour lire le catalogue.
- Service `App\Services\UserManagementService` + Policy `UserManagementPolicy` pour la gestion granulaire des utilisateurs (Lot 5).
- Commande `php artisan permissions:audit` (mode console + `--json`) qui détecte les permissions cassées, orphelines en DB, aliases legacy utilisés et permissions deprecated encore assignées.
- Refonte UI `/esbtp/roles-permissions` registry-driven (Lot 4) : rôle caissier visible, badges Legacy/Deprecated, restauration des défauts par rôle.
- 24 tests unitaires (`PermissionRegistry`, `UserManagementService`) pour 68 assertions, garantissant la non-régression du registry.

### Changed

- `Gate::before` ajouté dans `AuthServiceProvider` : tous les `@can()` sont court-circuités pour `superAdmin`, qui détient implicitement toutes les permissions (Lot 0).
- `bin/deploy/fix_permissions.php` refactoré (637 → 117 lignes) pour lire depuis le registry au lieu d'une liste hardcodée (Lot 3).
- Healing automatique : les tenants existants reçoivent les noms canoniques en complément des aliases lors du prochain `fix_permissions`, sans casser le code legacy.
- 82 aliases legacy (`view_students`, `manage-users`, `view cycles`...) migrés vers leurs noms canoniques (`students.view`, `users.manage`, `cycles.view`...) dans 96 fichiers : controllers, routes, vues Blade, policies, middleware (Lot 6).
- Page admin permissions lit le catalogue depuis le registry, suppression des 185 lignes hardcodées (Lot 4).

### Removed

- Rôle `parent` supprimé (jamais utilisé en prod : les parents utilisent le compte étudiant de leur enfant) — Lot 1.
- 10 contrôleurs `Parent*` (1 500+ lignes), 21 vues `parent/*`, routes `role:parent`, branches dead-code dans les contrôleurs partagés.
- Service mort `app/Services/ParentNotificationMethods.php` (scaffold doc, pas exécutable en l'état).
- Vues orphelines `dashboard/parent.blade.php`, `dashboard/parent_setup.blade.php`.
- Permission `can_view_parent_features` et `view children bulletins`.
- Commande artisan obsolète `esbtp:add-permission-superadmin` (rendue inutile par `Gate::before`) — Lot 7a.
- Auto-attribution dangereuse de rôle par email dans `fix_permissions.php` (`str_contains(email, 'admin')` → `superAdmin` sans validation).

### Fixed

- Route `esbtp.parents.search` cassait le boot de l'app après suppression du rôle parent : redirigée vers `ESBTPEtudiantController::searchParents` (qui reste utilisé par les flows d'inscription d'étudiants).
- 4 permissions cassées créées dans le registry : `inscriptions.cancel`, `create_classe`, `create-paiements`, `edit-paiements` (référencées en code mais absentes en DB → bug silencieux).
- Bug dans la lecture de config : `config('permissions.permissions.X.Y')` interprétait les dots comme nesting Laravel ; refactor vers la lecture directe d'array dans `PermissionRegistry`.

### Security

- Suppression de l'auto-assignment dangereux par email : un utilisateur créé avec un email contenant `admin` devenait `superAdmin` sans validation. Désormais, l'attribution de rôle passe obligatoirement par `UserManagementService` qui vérifie la matrice `role_management`.
- Matrice `role_management` granulaire remplace la permission monolithique `manage-users` : un secrétaire ne peut plus créer un superAdmin, un caissier ne peut créer qu'un étudiant en pré-inscription. Lot 5.

### Deprecated

Permissions à supprimer après audit complet (cible Lot 7+) :

- `view_frais_scolarite`, `create_frais_scolarite`, `edit_frais_scolarite`, `delete_frais_scolarite`
- `view_bourses`, `create_bourses`, `edit_bourses`, `delete_bourses`
- `view_depenses`, `create_depenses`, `edit_depenses`, `delete_depenses`
- `view_salaires`, `create_salaires`, `edit_salaires`, `delete_salaires`
- `view_reporting_financier`, `export_reporting_financier`
- `manage_attendance_codes`, `validate_attendance`, `view_all_attendance`
- `view_comptabilite`, `manage_comptabilite`
- Rôles `admin` et `teacher` (doublons de `superAdmin` et `enseignant`)

---

*Format inspiré de [keepachangelog.com](https://keepachangelog.com/fr/1.1.0/). Versioning calendaire `YYYY.MM` aligné sur le rythme de release SaaS.*

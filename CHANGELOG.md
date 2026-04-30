# Changelog

Toutes les évolutions notables de KLASSCI sont consignées dans ce fichier.

Le format suit [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/) et le projet adopte un versioning par mois (`YYYY.MM`) plutôt que SemVer strict, du fait du modèle SaaS multi-tenant déployé en continu.

> Discipline de mise à jour : voir [.claude/rules/changelog.md](.claude/rules/changelog.md). Toute modification visible utilisateur, breaking change, migration data, ou refactor structurel doit ajouter une entrée sous `[Unreleased]` au moment du commit.

---

## [Unreleased]

### Added

- **Lot 17c — Édition des rôles standards dans `/esbtp/personnel/unified`** : whitelist `EDITABLE_STANDARD_ROLES` (secretaire, comptable, caissier, coordinateur, enseignant, etudiant). Les users avec `users.manage` peuvent override label/icône/description et synchroniser les permissions de ces rôles depuis la même UI que les rôles custom. Le `name` interne reste immuable. superAdmin et serviceTechnique restent EXCLUSIVEMENT gérés par le Service Technique. Modal `_edit-standard-modal.blade.php` (miroir de `_edit-modal` avec bandeau warning + tag « immuable »). Routes `GET/PUT /esbtp/custom-roles/standard/{role}/edit`. Garde-fou grantable reproduit (un acteur ne peut donner que les permissions qu'il possède).

### Changed

- **Lot 17a — Fallback PDF "ESBTP-yAKRO" → "KLASSCI"** : tous les fallbacks runtime de `school_name` qui hard-codaient "ESBTP-yAKRO" passent à "KLASSCI" (générique). Concerne `NotificationService`, `Mail/Parents/ReinscriptionConfirmationMail`, `ESBTPClasseController` (6 occ), `ESBTPInscriptionPaiementController` (2 occ), `Exports/ClassesExport` (2 occ). Les tenants conservent leur configuration via `school_name` dans settings ; "KLASSCI" n'apparaît que si rien n'est configuré.
- **Lot 17b — Settings établissement nullable** : seul `school_name` reste required (`is_required = true`) dans `SettingsSeeder`. Tous les autres champs (téléphone, fax, RC, NCC, capital, banque, RIB, etc.) sont `nullable`. Double défense dans `ESBTPSettingsController::update()` : si valeur vide ET `! is_required` → écrase et skip validation ; sinon, retire `required` des rules et prepend `nullable` (ordre crucial : Laravel évalue gauche→droite, donc `nullable|email|...` permet `''` de passer le `email` rule).
- **Lot 17d — Header PDF export-detaille aligné sur `liste-complete-pdf`** : table 2 colonnes (logo 18% | infos école 82%), theme injection via `pdf.partials.theme`, marges 0.5cm, méta cells (lignes/date/total), footer signature. A4 paysage si colonne « Encaissé par » visible (donc plus de colonnes), sinon portrait.

- **Lot 13 — Paiements ownership + creator visibility** : nouvelle permission canonique `paiements.view_own` (voir uniquement les paiements créés par l'utilisateur) en plus de `paiements.view` (voir tous). Permission `paiements.export` pour le gating des exports. Le **caissier** voit désormais uniquement ses propres encaissements via `paiements.view_own`. Relation `creator()` sur `ESBTPPaiement` + scope `ownedBy(User)`. Colonne "Encaissé par" sur `paiements/index` (visible aux users avec `paiements.view`), bandeau prominent sur le reçu PDF et la card show. Tests `ESBTPPaiementOwnershipTest` (5 tests).
- **Lot 14 — KPI breakdown par mode de paiement (Côte d'Ivoire 2026)** : nouveau widget `paiements.by_mode` (taille `lg`) affichant le nombre + total par mode pour le mois en cours. Catalogue `config/payment_modes.php` (11 modes canoniques + 27 aliases) couvrant Espèces / Chèque / Virement / Carte / Orange Money / MTN Money / Moov Money / Wave / Djamo / Autres. Le widget agrège en 1 query (`groupBy mode_paiement`) puis normalise les variantes via `Str::slug` + table d'aliases. Disponible pour superAdmin, comptable, caissier.
- **Lot 15 — Export états financiers détaillés (PDF + Excel)** : page `/esbtp/paiements/export-detaille` avec form de filtres (étudiant autocomplete, classes multi-select, filière + niveau, période, modes de paiement) et radio Format PDF | Excel. Service `PaiementExportService` avec `buildQuery() / count() / exportPdf() / exportExcel()`. Garde-fou : pre-flight count, si format PDF demandé et `count > 500` → réponse 422 + toast UI explicite incitant à affiner les filtres ou choisir Excel. Permission `paiements.export` (assignée à comptable + secrétaire). Respect ownership : un user `paiements.view_own` n'exporte que ses propres encaissements. Migration `add_paiements_export_permission`. Tests `PaiementExportServiceTest` (16 tests).
- **Lot 16 — Édition rôles custom (vérification + clarification)** : whitelist de 41 icônes Font Awesome dans `ESBTPCustomRoleController::ALLOWED_ICONS` (sécurité contre injection arbitraire), validation `Rule::in` sur `store()` et `update()`. Encadré informatif `cr-info-note` dans `unified-personnel` rappelant la séparation : rôles custom modifiables ici, rôles système réservés au Service Technique. (Le CRUD edit lui-même était déjà dans le Lot 8.)

- **Lot 8 — Custom roles CRUD + assign users** : `ESBTPCustomRoleController` permet au superAdmin (et users avec `users.manage`) de créer des rôles custom de A à Z depuis `/esbtp/personnel/unified` (nom, label FR, icône, description), de sélectionner les permissions parmi les 154 canoniques (UI premium namespace `cr-*`), et d'assigner/détacher des utilisateurs. Migration `roles.label_fr/icon/description/is_custom/created_by_user_id`. `PermissionRegistry::roleMeta()` lit la DB en priorité (override + custom roles) avec fallback config. Tests unitaires `PermissionRegistryRoleMetaTest` (14 tests).
- **Lot 9 — Dashboard widgets configurables** : système de widgets ajoutables/retirables par chaque user, gated par permissions canoniques. Catalogue de 12 widgets concrets (`students.total`, `inscriptions.pending_validation`, `paiements.pending`, `bulletins.generated_this_period`, `attendances.today_rate`, etc.). Service `DashboardWidgetRegistry`, controller `DashboardWidgetController`, migration `users.dashboard_widgets` JSON, vue universelle `dashboard/widget-based.blade.php` namespace `dw-*`, modal "Configurer mon dashboard". Les rôles custom sont automatiquement routés vers ce dashboard widget-based. Tests unitaires `DashboardWidgetRegistryTest` (11 tests).
- **Lot 10 — UI permission-aware sans 403 sur la page entière** : 23 vues mises à jour pour wrapper les boutons d'actions secondaires avec `@can()` canonique. Les pages restent accessibles dès que l'user a la permission de base (ex: `inscriptions.view`), seuls les boutons "Valider", "Modifier", "Supprimer", "Ajouter paiement", "Générer bulletin", "Configurer", "Envoyer relances" sont conditionnels. Plus jamais de 403 sur une page entière à cause d'un seul bouton.
- **Lot 11 — Discipline memory + changelog** : nouvelles rules `.claude/rules/memory-updates.md` (quand sauvegarder en mémoire, format frontmatter, mise à jour pendant le travail) et `.claude/rules/changelog.md` (quand updater CHANGELOG, format keepachangelog, workflow Unreleased → version datée). Ce `CHANGELOG.md` initial avec historique complet Lots 0-11.

### Changed

- `PermissionRegistry::roles()` et `rolesVisibleInUi()` incluent désormais les rôles custom (`is_custom = true`) en plus des rôles config.
- `DashboardController` détecte les rôles custom et les route vers la nouvelle vue widget-based plutôt que l'un des dashboards historiques (superAdmin/secretaire/comptable/etc.).
- `.claude/rules/premium-redesign.md` : ajout du namespace `cr-*` (custom-roles Lot 8) dans la table des préfixes CSS par page.

### Removed

- 2 fichiers backup legacy `ESBTPEtudiantController(anicen commit).php` et `ESBTPParent(anicen commit).php` rangés par erreur dans `resources/views/esbtp/inscriptions/` (-1238 lignes). Polluaient l'audit `permissions:audit`.

### Performance

- **Lot 12 — Refactor backlog dashboard widgets** :
  - Composants Blade `<x-dw-widget>` et `<x-dw-widget-list>` extraits ; les 12 widget partials passent de ~20 lignes HTML/CSS répétées à 5-15 lignes de logique uniquement (mutualisation ~150 lignes).
  - `ESBTPAnneeUniversitaire::scopeCurrent()` + `getCurrent()` (cache 10 min, clé `esbtp:annee_universitaire:current`) — supprime les ~8 lookups répétés `where('is_current', true)->first()` dans les widgets et `DashboardController`.
  - `ESBTPInscription::scopePendingValidation()` — extrait la logique workflow_step (status en_attente/pending OR status=active+workflow incomplet) dispersée entre `DashboardController` et le widget `inscriptions-pending-validation`.
  - Cache HTML 60 s par widget × user dans `widget-based.blade.php` (`dw:widget:{key}:user:{id}`) — divise les requêtes DB du dashboard par ~10 sur les hits répétés.
  - Migration `add_date_status_index_to_esbtp_attendances` (composite index `(date, status)`) — élimine le full-table-scan du widget `attendances.today_rate` (passe de ~50-200 ms à <5 ms en fin d'année).
  - Widget `attendances-today-rate` : 2 queries → 1 query agrégée avec `SUM(CASE WHEN ...)`.

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

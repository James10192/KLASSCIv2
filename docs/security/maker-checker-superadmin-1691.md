# Issue #1691 — Contrat de sécurité du workflow Maker–Checker Superadmin

> **Statut : WIP / non déployable.** Ce document constitue le cadrage auditable de la PR Draft. **Aucune migration, route, service d'exécution ou interface Maker–Checker n'est encore implémenté.** Ne jamais marquer l'issue terminée ou fusionner cette PR sur la base de ce document.

## Périmètre et sources de vérité

- Application : KLASSCIv2, Laravel 12, Spatie Permission, modèle `User`, `App\Services\Personnel\RoleAssignmentService`, `App\Services\UserLifecycle\SuperAdminLifecycleGuard`, `App\Services\UserManagementService`, `App\Listeners\AuditPermissionChange`.
- Base : brancher ce chantier sur `audit/integration-rbac-laravel12-20261025`, contenant les correctifs de #1687 et Laravel 12 #1689. Ne pas déployer ni fusionner sur `presentation`.
- **Chaque établissement possède une base isolée : ne pas inventer de colonne `tenant_id`**. Le contexte de connexion et l'authentification doivent imposer le même établissement à toutes les lectures/écritures de la requête.
- **Créer toute migration avec `php artisan make:migration`** conformément à `.claude/rules/migrations.md` ; ne pas créer une migration horodatée manuellement.
- Ne pas exploiter `sod.bypass` comme raccourci de promotion ; les opérations protégées passent obligatoirement par l'approbation indépendante, sauf éventuel mécanisme d'urgence ultérieurement spécifié et testé séparément.

## Machine d'états et autorisations

Permissions *distinctes* à définir et synchroniser dans `PermissionRegistry` et `config/permissions.php` :

1. `superadmin.accounts.create` : création initiale d'une identité Superadmin **nouvelle** uniquement, chemin existant à conserver.
2. `superadmin.changes.request` : demander une transformation d'un compte protégé.
3. `superadmin.changes.approve` : approuver/rejeter indépendamment.
4. `superadmin.changes.execute` : exécuter exactement une transformation approuvée.

Aucune attribution automatique par nom `superAdmin` ou `serviceTechnique`. Les éventuels droits par défaut sont définis explicitement et audités selon les règles existantes, sans changer une révocation manuelle. Les permissions de révision/d'exécution ne peuvent pas être dérivées de `users.manage` seul.

Transitions autorisées :

| De | Vers | Condition |
| --- | --- | --- |
| absent | `pending` | Demandeur actif, habilité, motif non vide, état initial signé/normalisé, expiration définie |
| `pending` | `approved` | Autre utilisateur actif et habilité ; ni auto-approbation ni modification concurrente |
| `pending` | `rejected` | Autre acteur habilité, motif du refus |
| `pending` | `revoked` | Demandeur/auditeur autorisé, motif conservé |
| `pending`/`approved` | `expired` | Date d'expiration atteinte, jamais d'exécution après expiration |
| `approved` | `consumed` | Exécution atomique, contrôle d'état optimiste, audit durable, idempotence |

Une décision `rejected`, `revoked`, `expired` ou `consumed` est terminale. On ne recycle jamais son identifiant comme nouveau droit.

## Stockage proposé (à implémenter via Artisan)

Table dédiée, créée dans la **base de l'établissement** : `superadmin_change_requests`.

- PK `id` (bigint).
- `requester_user_id`, `target_user_id`, `approver_user_id` nullable, `executor_user_id` nullable ; FKs users selon conventions de migration du projet.
- `operation` : promotion, retrait, remplacement, désactivation, réactivation sensible ou transformation technique ; liste autorisée côté serveur.
- `status` : pending/approved/rejected/consumed/expired/revoked ; valeurs conservées sans enum SQL destructive.
- `expected_before_state` JSON canonique (rôles, statut, permissions effectives pertinentes, version) ; `requested_after_state` JSON de changement strictement limité ; jamais de mot de passe/token dans ces colonnes.
- `request_reason`, `decision_reason`, `revocation_reason` selon état.
- `expires_at`, `approved_at`, `consumed_at`, `rejected_at`, `revoked_at`, timestamps.
- `idempotency_key_hash` binaire/char hex unique par établissement ; `request_digest` normalisé ; `version` optimistic concurrency.
- Index sur `(status, expires_at)`, `(target_user_id,status)`, `(requester_user_id,status)`, `(approver_user_id,status)`.
- Ne pas stocker de secrets ou IP non nécessaires ; traces détaillées dans l'audit existant.

## Service d'exécution — invariants obligatoires

- Contrôle HTTP de permission, puis **revalidation dans la transaction** des habilitations demandeur, approbateur et exécuteur suivant la politique retenue.
- Pour l'exécution, verrouillage déterministe des lignes sur la même connexion SQL : ligne du rôle `superAdmin` utilisée comme mutex global du tenant, requête d'approbation, compte cible et acteurs concernés. Respecter un ordre stable sur toutes les routes pour réduire les deadlocks.
- Recharger la cible, calculer son état canonique, comparer exactement au snapshot approuvé (stale => `409` sans mutation).
- Confirmer l'expiration, le statut `approved`, deux personnes distinctes et l'organisation. Un droit de création initial ne dispense jamais de ce contrôle.
- Sous le verrou du rôle privilégié, compter les autres Superadmin **actifs** ; refuser toute transition qui ferait passer ce nombre sous 1. Deux retraits concurrents ne doivent jamais pouvoir consommer les deux dernières identités.
- Appliquer **une seule** mutation permise, conserver les autres rôles du compte, journaliser `old_values/new_values` via `AuditPermissionChange::enregistrerDecisionDeCompte`, puis marquer `consumed`. L'échec d'audit lève une exception et **annule la transaction entière**.
- Après validation, requêtes répétées : `409` ou retour idempotent documenté **sans ré-exécution**. Les mutations sensibles des anciens contrôleurs doivent rester fermées sans approbation consommable.
- Utiliser `DB::transaction(..., 3)`, MySQL 8 / MariaDB 10.11 ; ne pas considérer les tests SQLite comme preuve de sérialisation.

## Routes et interface

- Interface unique `/esbtp/personnel/unified` ; pas de second centre de gestion.
- Actions POST/PATCH indépendantes (création, approbation/rejet, consommation/révocation) soumises à CSRF/session Laravel, validation stricte des identifiants, limitation de débit et protections AJAX doubles clics.
- Afficher demandeur, cible, ancien/nouvel état métier, expiration, décision, statut, historique audité. Profil d'usage sélectionné n'accorde aucun droit.
- Échec JSON : `403`, `404`, `409`, `422`, `419` selon le contrat ; aucun SQL, stack, hash ou token transmis.
- cPanel : expiration synchrone lors de toute interaction, plus commande Artisan idempotente planifiable via cron cPanel ; pas de Redis, daemon, Supervisor ou root obligatoires.

## Preuves de clôture obligatoires avant revue indépendante

- Générer la migration avec Artisan, compléter modèle, services, contrôleurs/routes, interface dans `personnel/unified`, registre/synchronisation de droits, documentation et tests.
- Tests d'intégration HTTP positifs/négatifs pour chaque action et chaque rôle, révocation en session, auto-approbation, changement de cible, refus, expiration, double soumission, anti-rejeu, audit indisponible.
- Tests de concurrence **réels** sous MySQL 8 et MariaDB 10.11 : simultanément deux suppressions des deux derniers Superadmin, et deux consommations du même ticket ; un seul résultat valide.
- Test de migration sur un schéma historique anonymisé, état des rôles et audit existants, `php artisan migrate:rollback` sur copie, recette PHP 8.3 sur cPanel et sauvegarde validée.
- `composer audit`, Laravel CI, tests RBAC/CLI/MCP/BTS/LMD/finances/inscriptions, scan sécurité au **dernier SHA** ; revue externe distincte.

## Blocage déclaré lors de la création de cette Draft

L'agent dispose des actions GitHub de lecture/écriture, mais pas d'une copie locale du dépôt accompagnée de ses dépendances et d'une commande Artisan exécutable. **La migration obligatoire ne peut pas être produite selon la règle du projet à partir de cet environnement**. Le workflow Maker–Checker n'est donc **pas implémenté**, le verdict reste **BLOCK — CORRECTIONS REMAINING** jusqu'à génération et validation effectives. La PR demeure en Draft.

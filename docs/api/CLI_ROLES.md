# API CLI — rôles et comptes (PR #1685, NON FUSIONNÉE)

La base de données utilisée est toujours celle de l'établissement courant. Une
ability Sanctum cli:admin ne suffit pas : la permission Spatie effective de
l'auteur est nécessaire. Les autorisations serveur ne reposent pas sur les noms
des rôles détenus par l'auteur.

## POST /api/cli/roles

Créer un rôle personnalisé. Conditions : cli:admin ET personnel.roles.create.
Un rôle custom ne peut recevoir que des permissions réellement détenues par
son créateur. Les rôles canoniques sont réservés et ne sont pas créés implicitement.

Exemple de requête :

    {"name":"informaticien","label_fr":"Informaticien","permissions":["inscriptions.validate"]}

## POST /api/cli/user/{id}/role

Mutations d'un compte existant via RoleAssignmentService : transaction, audit
avant/après et motif obligatoire. Rôles inconnus = 422 ; permission absente = 403.

Ajout non destructif (par défaut : mode add) :

    {"role":"coordinateur","mode":"add","motif":"Enseignant et coordinateur"}

Retrait ciblé :

    {"role":"coordinateur","mode":"remove","motif":"Fin de mission"}

Remplacement ciblé : remplace seulement old_role, conserve les autres rôles.
expected_roles est la liste complète des rôles de la cible AVANT modification,
et protège contre l'écrasement d'une autre affectation récente :

    {
      "role":"secretaire",
      "mode":"replace",
      "old_role":"coordinateur",
      "expected_roles":["enseignant","coordinateur"],
      "motif":"Changement de poste validé"
    }

La réponse contient roles_before et roles_after. Etat attendu périmé = 422.
Le remplacement sans old_role ou expected_roles = 422.

Gouvernance : la création INITIALE d'un NOUVEAU compte superAdmin via
POST /api/cli/user/create exige cli:admin + users.manage + personnel.roles.assign
+ superadmin.accounts.create. Le service insère le compte, affecte le rôle
et consigne la décision dans la même transaction. Une promotion, un retrait,
un remplacement ou une modification d'un compte sensible déjà existant reste
refusé (403) tant que le processus Maker-Checker n'est pas livré. L'attribution
de serviceTechnique est interdite par ce parcours.

## GET /api/cli/users

Lister les comptes nécessite **les deux couches** : `cli:read` et
`personnel.view` **ou** `users.manage` attribué à l'auteur. L'ability
Sanctum seule ne suffit plus (403). `role` est conservé pour les anciens
clients, mais il ne représente qu'un des rôles. Le nouveau champ `roles`
est un tableau de tous les rôles du compte. `limit` est borné à 1–100.
La lecture charge les rôles sans requête N+1 et ne retourne pas les empreintes.

## POST /api/cli/user/create

Création + premier rôle + audit dans une même transaction. Conditions :
cli:admin, users.manage et personnel.roles.assign. Champ motif obligatoire.
Rôle existant obligatoire. Création superAdmin : permission supplémentaire
superadmin.accounts.create. Création serviceTechnique interdite par cette API.

Le champ historique password_hash reste admis : les flux d'export et de
réinitialisation d'empreintes demandent une revue de sécurité indépendante.

## POST /api/cli/user/{id}/delete

Désactive et supprime logiquement un compte ordinaire. Nécessite cli:admin,
personnel.users.deactivate et un champ motif obligatoire (3–1000 caractères).
La modification, la révocation des tokens et la trace avant/après sont
transactionnelles. Un compte Superadmin ou Service Technique ne peut pas être
désactivé par ce parcours sans le circuit de double validation, même si
plusieurs Superadmin actifs existent : 403 sans modification.

    {"motif":"Fin de contrat confirmée"}

## Identifiants et réinitialisation de mots de passe CLI

- POST /api/cli/user/{id}/reset-password et /reset-password-expiry :
  cli:admin + personnel.users.password.reset **ET** périmètre de gestion
  effectif de la cible (personnel.manage ou users.manage, sans dépasser ses
  propres privilèges sauf délégation manage_all explicite). Les comptes
  Superadmin ou Service Technique et l'auto-gestion sont toujours refusés 403.
  Le changement réel utilise un verrou SQL et un audit durable transactionnel
  ne contenant ni mot de passe ni empreinte.
- GET /api/cli/user/{id}/credentials : cli:admin +
  personnel.users.credentials.export **ET** périmètre de gestion effectif.
  Cette réponse contient une empreinte de mot de passe : endpoint sensible,
  jamais destiné à des journaux ni à un compte moins habilité que sa cible.
  Comptes sensibles : 403.

Les capacités sont évaluées directement sur les permissions effectives,
pas sur le nom du rôle de l'opérateur.

## POST /api/cli/permissions/sync et /api/cli/permissions/fix

Synchronise les rôles/permissions du registry sur la **base de l'établissement
courant**. Les deux routes exigent désormais `cli:admin` **ET** la permission
effective `system.technical_access` ou `personnel.roles.manage_all`.
Un simple token d'administration CLI ne suffit pas à reconfigurer les rôles
de l'école (403). Aucun pouvoir n'est accordé par le nom superAdmin.

## Historique et compatibilité

- 26/10/2026 (PR #1687, non fusionnée) : BREAKING CHANGE sécurité : export
  d'identifiants et changements de mot de passe CLI vérifient la délégation sur
  la cible ; reset sous verrou avec audit sans secret. Les endpoints de synchro
  de permissions exigent une permission technique ou manage_all en plus
  de cli:admin. Adapter les tokens avant tout déploiement pilote.


- 26/10/2026 (PR #1687, non fusionnée) : BREAKING CHANGE en lecture :
  GET /api/cli/users exige désormais la permission effective de consultation
  en plus de cli:read ; nouveau tableau roles, champ role conservé.


- 26/10/2026 (PR #1687, non fusionnée) : création Superadmin initiale corrigée,
  sans promotion d'un compte existant et avec transaction/audit atomiques.
  Comportement API réparé sans modifier son format.


- 25/10/2026 (PR #1685, EN ATTENTE) : BREAKING CHANGE. Pour toute mutation de rôle
  et création de compte via la CLI, motif devient obligatoire. Le mode implicite
  est maintenant add et non replace ; replace exige old_role et expected_roles.
  Un ancien appel {"role":"x","mode":"replace"} reçoit 422 au lieu de supprimer
  tous les autres rôles. Adapter les clients avant le déploiement.
- 31/08/2026 : création et affectation de rôles custom introduites.

Aucune migration de base de données propre à ces changements. Les permissions
sont enregistrées dans le registry et synchronisées via le service existant.
Ne pas propager la PR tant que le Composer Security Audit échoue sur
GHSA-f9x3-7vq9-cph6.

# 04 — Organisation et habilitations

Les titres UCAO sont des **responsabilités candidates**. Ils ne deviennent pas des `hasRole('daf')`. L’école compose des rôles custom + permissions.

## 1. Quatre administrations distinctes

| Plan | Qui | Peut | Ne peut pas |
|---|---|---|---|
| ADC centrale | `serviceTechnique` (masqué) + Filament adminKlassci | Provision, abonnement, style bulletin, support avec identité réelle | Se faire passer pour un utilisateur ; écrire dans le métier en silence |
| Instance | **Administrateur d’instance** (à créer) | Réglages métier, comptes, rôles custom, diagnostics, audit borné | `paywall.manage`, style bulletin, `*` finance, s’auto-attribuer un droit de paiement |
| Composante | rôle custom + scope `composante_id` | Dossiers de sa composante | Autre composante, paie, abonnement |
| Audit interne | permission `audit.view` sans mutation | Lire le journal filtré | Pièces d’identité / santé / salaires en clair sans motif |

`superAdmin` actuel = `Gate::before` tout vrai (`AuthServiceProvider.php:62-65`). **Incompatible** avec A4. Trajet :

1. Créer `administrateurInstance` sans `*`, sans Gate::before.
2. Retirer progressivement `*` à `superAdmin` **ou** le réserver ADC (renommer en UI).
3. Les écoles CI conservent un profil « direction » assemblé (scolarité + un peu de finance) via custom — pas un troisième `*`.

## 2. Séparation : attribuer vs exercer

Règle produit : `roles.assign` n’inclut pas les permissions cibles. Interdits :

- S’auto-attribuer `paiements.validate`, `lmd.jury.publish`, `achats.engager`, `stock.ajuster`.
- Escalade via groupe/rôle custom que l’admin vient de créer **et** de s’affecter dans la même transaction — exiger un second admin ou ST.
- Réinitialiser le mot de passe d’un valideur **et** exercer son droit.

SoD finance aujourd’hui : reconcil (setting), salaires (`validate` hors défaut comptable), paiements `validate.self_override`. SoD `config/sod.php` = jury. **Étendre le même service** aux achats / paiements fournisseurs, pas un second moteur.

## 3. Cartographie UCAO → permissions (hypothèses marquées)

| Responsabilité rapportée | Rôle UI de départ | Permissions à ouvrir | Notes |
|---|---|---|---|
| DSI UCAO | `administrateurInstance` | `system.settings.manage`, `users.*`, `roles.assign` borné, `audit.view`, diagnostics | Pas ST |
| Scolarité (fait tout y compris jury) | `serviceScolarite` / custom + **cocher jury** | `lmd.*` y compris `lmd.jury.*` selon B8 | Décision **avant** 1er jury |
| Directeur adjoint ESMEA | `directeurEtudes` + scope ESMEA | maquettes, planning | Pas un rôle `esmea` |
| SP | **à confirmer D-01** | `enseignants.prestation.validate` (nom indicatif) | Pas `hasRole('sp')` |
| Caissier | `caissier` | inchangé | Recettes étudiants |
| Préparateur achats + stock | custom | `achats.preparer`, `stock.*` | Même personne possible en petite école |
| Chargé des dépenses | custom | `achats.controler`, `achats.engager` | Pas le décaissement |
| Trésorerie | custom | `tresorerie.decaisser`, `tresorerie.rapprocher` | Pas créer la commande |
| Chef comptable / DAF / président | custom + seuils | visas selon montant | Circuit configurable |
| Enseignant | `enseignant` | notes, émargement, TPE selon stratégie | |
| RH employés | hors KLASSCI ou `rh.view` lecture | ADC Paie | |

Petite école CI : un `comptable` reçoit `achats.*` + `stock.*` + `tresorerie.*`. UCAO : trois customs. Yakro : ne pas cocher.

## 4. Cycle de vie des comptes

Invitation → activation (mot de passe + 2FA si politique instance) → affectation (composante, campus) → délégation temporaire (dates, permissions listées, journal) → suspension → départ (révocation sessions, conservation audit) → revue périodique (setting `access_review_days`, défaut 90, **hypothèse**).

Comptes de service : flag `is_service_account`, pas de login interactif, secret rotatif, audit séparé.

## 5. Périmètres intra-instance

Dimensions **indépendantes** (pas une cascade obligatoire) :

- `composante_id` (EGEI / ESMEA / FSAE / FDE)
- `campus_id` (si plusieurs sites — **non confirmé** UCAO)
- `annee_universitaire_id`
- `caisse_id` / magasin

Un enseignant partagé : visible dans les deux composantes pour **l’affectation de cours**, pas pour les salaires ni les dossiers étudiants de l’autre.

Contrôler : listes, recherche, agrégats, dashboards, pièces, exports, jobs, CLI, chatbot.

## 6. Délégation et absence

Délégation = objet daté (`from`, `to`, `permissions[]`, `delegator_id`, `delegate_id`). Expiration ⇒ refus. Remplacement d’un valideur : le dossier reste à l’étape ; le nouveau vise en son nom. Double-clic : idempotence sur `approval_id`.

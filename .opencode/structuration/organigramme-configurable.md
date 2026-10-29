---
date: 2026-09-15
type: project
rules:
  - .claude/rules/rien-en-dur.md
  - .claude/rules/customizable-roles.md
  - .claude/rules/permissions.md
---

# Organigramme d’instance — configurable, rien en dur

Brief Marcel (15/09/2026). Ça structure UCAO UUC **et** les instances déjà là. On n’implémente pas les modules dans ce fichier : on fixe le contrat.

## Test

Deux écoles peuvent-elles vouloir une valeur différente ? Oui → setting, permission, rôle **custom** UI, ou colonne sur l’objet. Non → invariant logiciel.

## Interdit

- `hasRole('serviceInformatique')`, `hasRole('chargeDepenses')`, `hasRole('gestionnaireStock')`, `hasRole('directeurAdjointEsmea')`
- `if ($tenant === 'ucao')`
- Renouvellement d’agrément `+3 years` en dur
- TPE toujours / jamais planifiable en dur hors enum + setting

Canon figé aujourd’hui : `superAdmin`, `secretaire`, `comptable`, `caissier`, `coordinateur`, `directeurEtudes`, `enseignant`, `etudiant`, `serviceTechnique` (masqué, **ADC**, `visible_in_ui => false`, `*`).

**Correction 15/09 (dossier artifact, pas Wikipédia) :** `serviceTechnique` n’est **pas** le DSI du client. C’est ADC. Le dossier exige un second rôle canonique **Administrateur d’instance** : réglages, comptes, droits, dépannage. Jamais l’abonnement, jamais le style de bulletin. Scinder `system.manage`. Il peut **distribuer** des droits de jury sans les **exercer** (pas d’auto-attribution, finance à deux mains). Pas d’impersonation (« voir comme ») : ça corrompt l’audit.

## Cartographie demandée → comment ça vit

### Service informatique (Administrateur d’instance — à créer ; pas `serviceTechnique`)

| Demande | Mécanisme |
|---|---|
| Paramétrage de l’instance | `system.settings.manage` (scindé de l’abonnement) |
| Comptes, rôles, permissions | admin d’instance + explicateur de droits en français |
| Audit complet | filtre du journal dérivé des lignes réelles (déjà un lot) |
| Dépannage | diagnostics métier à l’écran ; **pas** impersonation, pas console infra, pas journal brut |

### Service scolarité

| Demande | Mécanisme |
|---|---|
| Directeur adjoint / ESMEA / programmation / maquettes | `directeurEtudes` + permissions planning/maquette, **ou** rôle custom |
| Relation enseignants, agréments, fiches (exécution, prestation, facturation), validation SP | **permissions** nouvelles le jour du module ; assignation custom. SP = permission `enseignants.prestation.validate` (nom indicatif), pas un rôle `sp` |
| TPE faits à l’école | setting d’instance (défaut : non planifiable) |
| RH enseignants ≠ RH employés | domaines séparés ; la RH ADC Paie ne gère pas les agréments |

Agréments : échéance **sur l’objet** ; alertes aux moments critiques (setting : jours avant échéance, pièce manquante, changement de statut). Pas un cron « tous les 3 ans ».

### Service comptabilité

Déjà : `caissier`, `comptable` = client / caisse KLASSCI, à pousser.

À ouvrir **par permissions** (l’école compose les postes) :

1. Caisse — existant
2. Besoin interne → dossier de pièces (proforma, commande, BL, autorisations) — workflow + types de pièces **réglables**
3. Stock — gestionnaire = rôle custom + `stock.*`
4. Chargé de dépenses / fournisseur — `achats.*` + pièces
5. Trésorerie — décaissement + **log** + rapprochement `tresorerie.*`
6. Écritures OHADA — journal, lettrage

Petite école : un `comptable` reçoit tout. UCAO : trois custom (stock, dépenses, trésorerie). Yakro peut rester caisse + client.

### Service RH (employés, ADC Paie)

Attestation, assurance, promotion, mission, fiche de poste CRUD, pointage IP : module RH **employés**. Permissions `rh.*` / `paie.*`. Pointage : allowlist IP en setting + postes. Artifact Claude non lu (login) : on reprend le brief IP, on ne invente pas l’écran.

## Ordre de construction (quand on codera)

1. Permissions + settings (TPE sur site, alertes agrément, IP) — zéro rôle nouveau
2. Agréments enseignants (objet + moments critiques)
3. Circuit achats / stock / trésorerie (OHADA), après le client
4. Pointage IP
5. Fiches enseignant (exécution, prestation, facturation) + validation

Chaque lot : `/plan-and-confirm`, rien en dur, custom roles, CHANGELOG.

---
date: 2026-09-15
type: research
sources:
  - https://fr.wikipedia.org/wiki/Université_catholique_de_l'Afrique_de_l'Ouest
  - https://en.wikipedia.org/wiki/Catholic_University_of_West_Africa
  - https://www.ohada.com/actualite.html (SYSCOHADA / SMT cités)
  - .claude/rules/rien-en-dur.md
  - .claude/rules/customizable-roles.md
  - app/Enums/NatureComposante.php (UCAO-UUC déjà nommé comme exemple de vocabulaire)
---

# Recherche — UCAO / UUC et services d’établissement

Notes pour OpenCode **et** Claude. Ce n’est pas une spec à coder tel quel : c’est le terrain. Chaque écart entre écoles va dans un **réglage** ou un **rôle custom**, jamais dans un `hasRole('ucao')`.

## UCAO

L’Université catholique de l’Afrique de l’Ouest (UCAO) est un **réseau d’unités universitaires** (CERAO, février 2000). Rectorat central à Ouagadougou. Huit unités dans sept pays, dont :

| Sigle | Ville | Ce que dit la source publique |
|---|---|---|
| UUA | Abidjan | sciences éco, philo, droit, communication, théologie (ex-ICAO) |
| UUC | Cotonou | agronomie et électronique |
| UUY | Yamoussoukro | sciences de la santé |
| UUB | Bobo-Dioulasso | agroalimentaire |
| UUCo | Conakry | sciences politiques / fondamentales |
| UUBa | Bamako | sciences de l’éducation |
| UUZ | Ziguinchor | économie et gestion |
| UUT | Togo | sciences technologiques |

**Pour KLASSCI :** « UCAO UUC » n’est pas un mode du logiciel. C’est **une instance** (comme `esbtp-abidjan`). Son organigramme, ses libellés de composantes (Faculté / École / ESMEA) et ses politiques (TPE à l’école, agréments) se règlent sur **cette** instance. `NatureComposante` existe déjà pour nommer UFR / Faculté / École / Institut sans nouvelle table.

ESMEA (école de management, directeur adjoint, programmation des cours) est un **poste et une composante de cette école**, pas un rôle PHP.

## TPE (UEMOA / LMD)

Dans KLASSCI aujourd’hui, le TPE n’est **pas** une séance d’emploi du temps : volume théorique d’ECUE (`TypeSeance::TPE`, `mapToType() === null`).

À UCAO, le TPE se ferait **sur place**. Ça ne casse pas l’enum : ça ouvre une **conduite configurable** :

- défaut (ESBTP, etc.) : TPE non plannable, non pointé
- option d’instance : TPE validable (présence sur site, créneau, attestation)

Deux écoles peuvent légitimement diverger → setting, pas `if ($tenant === 'ucao')`.

## Agréments enseignants

Pas de texte unique « 3 ans partout » trouvé qui doive gouverner le logiciel. L’utilisateur a tranché la politique produit : **ne pas renouveler sur un calendrier fixe** ; n’agir qu’aux **moments critiques** (échéance proche, changement de statut, pièce manquante, contrôle).

Donc : date d’échéance **sur l’agrément** (colonne de l’objet), seuils d’alerte **en réglage d’instance**, jamais `+ 3 years` en dur.

RH salariale ≠ RH enseignants : les agréments, fiches d’exécution, demandes de prestation, facturation enseignant, validation SP sont un **domaine enseignant**, pas le module ADC Paie des employés.

## Comptabilité OHADA (AUDCIF / héritage SYSCOHADA)

Le droit comptable OHADA (AUDCIF 2017, successeur du SYSCOHADA) sépare notamment :

- **Clients** — ce que KLASSCI fait déjà (caisse, inscriptions, reçus)
- **Fournisseurs** — charges, factures d’achat, 401
- **Stocks** — classe 3, mouvements, inventaire
- **Trésorerie** — classe 5, décaissements, rapprochement bancaire
- **Écritures** — journal, lettrage, pièces

KLASSCI a le **comptable client + caissier**. UCAO (et d’autres) ont en plus : détection de besoin → dossier de pièces (proforma, BC, BL, autorisations) → chargé de dépenses → trésorerie → log + rapprochement.

Ce n’est pas trois nouveaux rôles hardcodés. C’est des **permissions** (`stock.*`, `achats.*`, `tresorerie.*`, `ecritures.*`) et des **rôles custom** par école (petite école : un comptable les a toutes ; grande : stock / dépenses / trésorerie séparés).

Circuit de pièces et signatures (chef comptable, DAF, président) : **workflow configurable**, pas une liste de titres écrite dans le contrôleur.

## Pointage IP (artifact Claude inaccessible)

L’artifact `https://claude.ai/artifact/7Qg7FD5C2itssDyzjedHFy` exige un login Anthropic : contenu non récupéré le 15/09/2026.

Intention utilisateur : pointage intelligent, code sur l’ordinateur professionnel, **verrouillage par adresse IP**.

Conduite à livrer (réglable) :

- allowlist d’IP / plages par poste ou par site
- refus hors allowlist (pas de repli silencieux)
- le « 3 ans » n’a rien à voir ici ; la politique d’IP est un setting + des objets poste

ADC Paie (employés) : fiche de poste, attestation, assurance, promotion, mission — distinct du pointage enseignants.

## Sources à ne pas durcir

Wikipédia UCAO est incomplet (bandeau sources). Les spécialités d’une unité changent. On ne code pas « UUC = agronomie ».

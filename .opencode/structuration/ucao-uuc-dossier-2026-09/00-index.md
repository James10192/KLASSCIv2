# Dossier de conception — KLASSCI × UCAO-UUC

**Mandat** : audit et conception. Aucun comportement applicatif modifié dans cette passe. Aucun déploiement.

**Date d’observation** : 15 septembre 2026.  
**Produit** : KLASSCI (African Digit Consulting).  
**Client visé** : UCAO-UUC, Unité Universitaire de Cotonou.  
**Règle produit** : aucune constante `ucao`, aucun `hasRole` métier nouveau. Variantes = settings, permissions, rôles custom, colonnes datées sur l’objet.

## Comment lire

1. [01 — Lecture de décision](01-lecture-de-decision.md) — conclusions, risques, architecture. Lire en premier.
2. [02 — Carte des preuves](02-carte-des-preuves.md) — SHA, fichiers, écarts avec le dossier 14–15 sept.
3. [03 — Matrice de couverture](03-matrice-de-couverture.md) — chaque besoin explicite a une ligne.
4. [04 — Organisation et habilitations](04-organisation-habilitations.md)
5. [05 — Catalogue des configurations](05-catalogue-configurations.md)
6. [06 — Workflows](06-workflows.md)
7. [07 — Modèle de données et intégrations](07-modele-donnees-integrations.md)
8. [08 — Écrans](08-ecrans.md)
9. [09 — Registre réglementaire et sécurité](09-registre-reglementaire.md)
10. [10 — Angles morts](10-angles-morts.md)
11. [11 — Backlog par lots](11-backlog.md)
12. [12 — Mise en service](12-mise-en-service.md)
13. [13 — Décisions restantes](13-decisions-restantes.md)
14. [14 — Recette Given/When/Then](14-recette.md)

Notes antérieures non opposables à ce dossier : `.opencode/structuration/ce-quil-faut-faire.md`, `organigramme-configurable.md`, `docs/runbooks/ucao-benin-mise-en-service.md`.

## Décision en une phrase

Garder KLASSCI multi-instance isolé, réparer et étendre la chaîne LMD déjà là, créer un administrateur d’instance distinct d’ADC, construire achats–stock–trésorerie opérationnels **sans** grand livre SYSCOHADA concurrent, laisser ADC Paie maître de la paie salariés, et ne rien figer en `if (ucao)`.

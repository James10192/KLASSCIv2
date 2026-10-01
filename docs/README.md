# Documentation technique de KLASSCI

Présentation du produit, modules et démarrage en local : voir le [README à la racine](../README.md).
Documentation utilisateur publique : [klassci.com/docs](https://klassci.com/docs).

Ce dossier rassemble la documentation destinée à l'équipe qui développe et exploite KLASSCI.

## API

- [`api/`](api/) — une fiche par API, REST ou CLI d'exploitation, avec son historique ; point d'entrée : [`api/README.md`](api/README.md)
- Utilisation du CLI : [`api/CLI_USAGE.md`](api/CLI_USAGE.md)
- Intégration LMS : [`LMS_API_README.md`](LMS_API_README.md), [`LMS_INTEGRATION_GUIDE.md`](LMS_INTEGRATION_GUIDE.md), [`LMS_ARCHITECTURE_GUIDE.md`](LMS_ARCHITECTURE_GUIDE.md)

## Architecture et exploitation

- [`SAAS_ARCHITECTURE.md`](SAAS_ARCHITECTURE.md) — architecture multi-instance, base centrale
- [`SAAS_DEPLOYMENT_PLAN.md`](SAAS_DEPLOYMENT_PLAN.md) — déploiement des instances
- [`VERSIONING.md`](VERSIONING.md) — conventions de version et production des notes de version
- [`runbooks/`](runbooks/) — procédures d'exploitation (mise en service d'une instance hors Côte d'Ivoire, sécurité, pilotage académique, rattrapages de bulletins)
- [`architecture/`](architecture/) — notes de conception ciblées
- [`support/KLASSCI_CARE.md`](support/KLASSCI_CARE.md) — support intégré aux instances

## Produit

- [`PRD_KLASSCI.md`](PRD_KLASSCI.md) — document de cadrage produit (décembre 2024)
- [`product/`](product/) — études produit (LMD)
- [`MASTER-PLAN-emploi-temps-lmd-unification.md`](MASTER-PLAN-emploi-temps-lmd-unification.md) — chantier emploi du temps et LMD
- [`tutoriels/`](tutoriels/) — tutoriels utilisateur illustrés

## Audits

- [`audits/`](audits/) — audits datés (comptabilité, parcours LMD, rendez-vous)
- [`SECURITY_AUDIT_2026-05-21.md`](SECURITY_AUDIT_2026-05-21.md) — audit de sécurité

## Ailleurs dans le dépôt

- [`../CHANGELOG.md`](../CHANGELOG.md) — historique des changements
- [`../.claude/rules/`](../.claude/rules/) — règles de développement : permissions, design, pièges connus
- [`../CLAUDE.md`](../CLAUDE.md) — consignes générales du dépôt

## Documents anciens

Les autres fichiers à la racine de ce dossier (correctifs de frais, reliquats, affectation, inscriptions…) sont des notes de chantiers passés. Ils décrivent l'état du code au moment où ils ont été écrits : vérifiez dans le code avant de vous y fier.

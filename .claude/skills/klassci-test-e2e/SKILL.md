---
name: klassci-test-e2e
description: Rejouer un test end-to-end KLASSCI supérieur sur un tenant réel, avec priorité aux flux BTS tronc commun, diagnostics klassci-cli, mutations API CLI, seed académique contrôlée et vérification navigateur. À utiliser pour "continue le test e2e", "valide de bout en bout", "teste sur presentation", "BTS TC end-to-end".
---

# KLASSCI Test E2E

Méthode courte et reproductible pour valider un flux réel sur tenant, sans s'arrêter au code ou aux tests unitaires.

## Quand utiliser ce skill

- L'utilisateur demande une validation de bout en bout sur un tenant
- Il faut vérifier UI + données + endpoints + mutations réelles
- Le chantier touche BTS tronc commun, résultats, bulletins, inscriptions, orientation

## Principes

1. Toujours partir d'un cas réel, tenant + année + étudiant + inscription + classe.
2. Prouver chaque étape par au moins deux sources : diagnostic CLI/API et écran navigateur.
3. Ne pas conclure "c'est bon" sans données métiers réelles.
4. En cas d'outil `klassci-cli` cassé sur un POST, basculer vers l'endpoint `api/cli` signé par token tenant.
5. Quand le moteur et l'UI divergent, corriger d'abord la source de vérité puis la projection.

## Boucle E2E standard

### 1. Cadre du cas réel

Identifier :

- tenant, souvent `presentation`
- `annee_universitaire_id`
- `etudiant_id`
- `inscription_id`
- `classe_source_id`
- `classe_cible_id` si orientation

Commandes utiles :

```powershell
powershell -ExecutionPolicy Bypass -File .\klassci-cli.ps1 bts-tc:student-journey presentation 831 1
powershell -ExecutionPolicy Bypass -File .\klassci-cli.ps1 bts-tc:diagnose presentation 831
powershell -ExecutionPolicy Bypass -File .\klassci-cli.ps1 resultats:diagnose presentation 831 99 1 annuel 1
```

### 2. Vérifier le parcours BTS TC

Valider :

- phase active
- timeline `tronc_commun -> specialisation`
- mapping de classes S1/S2
- cohérence année / niveau / filière

Si la sortie n'existe pas encore :

- créer la cible autorisée
- orienter l'inscription

Privilégier `klassci-cli` si le wrapper fonctionne. Sinon appeler `api/cli` directement avec le token tenant.

### 3. Vérifier la donnée académique réelle

Sans notes ni bulletins, le test académique n'est pas clos.

Checklist minimale :

- au moins une matière et une évaluation en S1 sur la classe TC
- au moins une matière et une évaluation en S2 sur la classe de spécialisation
- au moins une note par semestre
- bulletins S1 et S2 présents ou recalculables

Si besoin, utiliser un seed contrôlé côté API CLI sur une inscription de test, jamais un `migrate:fresh` ni un wipe.

### 4. Vérifier les résultats et l'annualisation

À prouver :

- `semestre1` lit la classe TC
- `semestre2` lit la classe de spécialisation
- `annuel` agrège S1 et S2 avec les poids configurés

Commandes utiles :

```powershell
powershell -ExecutionPolicy Bypass -File .\klassci-cli.ps1 resultats:diagnose presentation 831 98 1 semestre1 1
powershell -ExecutionPolicy Bypass -File .\klassci-cli.ps1 resultats:diagnose presentation 831 99 1 semestre2 1
powershell -ExecutionPolicy Bypass -File .\klassci-cli.ps1 resultats:diagnose presentation 831 99 1 annuel 1
powershell -ExecutionPolicy Bypass -File .\klassci-cli.ps1 resultats:bulletin-consistency-diagnose presentation 831 98 1 semestre1
powershell -ExecutionPolicy Bypass -File .\klassci-cli.ps1 resultats:bulletin-consistency-diagnose presentation 831 99 1 semestre2
powershell -ExecutionPolicy Bypass -File .\klassci-cli.ps1 resultats:bulletin-consistency-diagnose presentation 831 99 1 annuel
```

### 5. Vérifier l'UI réelle

Utiliser `npx dev-browser`.

À contrôler au minimum :

- `etudiants.show`
- `inscriptions.show`
- `etudiants.index`
- `inscriptions.index`
- `resultats.etudiant` si concerné

Points BTS TC :

- badge de phase visible et lisible
- bloc `Parcours BTS` présent
- historique S1/S2 correct
- moyenne annuelle affichée cohérente avec le diagnostic
- aucun fallback visuel trompeur

### 6. Déclarer le statut final

Ne dire "validé de bout en bout" que si :

- mutation métier réelle exécutée
- diagnostics BTS et résultats alignés
- UI alignée avec la donnée
- annualisation prouvée sur vraies données académiques

Sinon, conclure explicitement ce qui manque.

## Pattern de sortie

Toujours rendre :

```markdown
## Statut

Validé partiellement | Validé de bout en bout | Bloqué

## Preuves

- Cas réel : étudiant / inscription / classes / année
- Diagnostics : commandes + points saillants
- UI : pages vérifiées + écarts restants

## Gaps

- ce qui manque encore pour conclure
```

## Notes BTS TC

- Une inscription annuelle unique peut changer de phase sans changer d'identité.
- Le diagnostic n'est pas une preuve suffisante si aucune note réelle n'existe.
- Le bon ordre de preuve est : orientation réelle, seed ou données réelles, diagnostic semestre 1, diagnostic semestre 2, diagnostic annuel, vérification UI.

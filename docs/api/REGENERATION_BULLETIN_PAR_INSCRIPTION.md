# Génération et régénération contextualisées des bulletins

## Interface

Depuis l'onglet « Académique » de la fiche étudiant, les boutons « Générer / régénérer S1/S2 » (BTS) ou « S1…S10 » (LMD, selon le niveau) ouvrent une confirmation avec motif facultatif pour les personnes habilitées à générer un dossier incomplet.

Le client POSTe sur `esbtp.bulletins.regenerer-inscription` avec `periode` et `incomplete_reason`. Il affiche la réponse avec une action explicite « Actualiser le bilan académique » (les KPI de la fiche ne sont pas actualisés à chaud), ainsi qu'un lien LMD si l'utilisateur peut consulter les bulletins. Les erreurs métier et de prérequis sont affichées dans le dialogue ; la confirmation n'affirme jamais le succès avant la réponse serveur.

## Périmètre et protections

- L'inscription est choisie par son identifiant serveur, pas par des paramètres `classe_id` ou `etudiant_id` forgés.
- BTS : `BtsStudentAcademicRecords::classMap` choisit la classe S1/S2 (tronc commun → spécialisation). Une génération initiale passe par `BtsBulkBulletinGenerationService` sur le seul étudiant, avec ses contrôles et son classement. La régénération utilise `BulletinConsistencyService`.
- LMD : validation du semestre dans `classe.getSemestresLMD`, préparation par `BulletinGenerationReadinessService`, calcul par `LMDBulletinService`.
- Autorisations : `students.view` et `paywall`, puis `bulletins.generate` pour une **première génération BTS**, `bulletins.edit` pour un **recalcul BTS**, ou `module.lmd.access` + `lmd.bulletins.generate` en LMD, en plus du groupe d'identité protégé.
- Les bulletins BTS publiés ou signés sont verrouillés sous transaction avec verrou de ligne dans le service central, y compris pour les anciens points d'entrée « bulk » et « regenerate ». Les bulletins LMD publiés sont également refusés sous verrou de ligne.
- Les anciennes périodes BTS `1`/`2` sont conservées au recalcul pour ne pas créer un deuxième bulletin sous `semestre1`/`semestre2`.
- Si deux anciens bulletins `1` et `semestre1` coexistent pour le même semestre, aucun des deux n'est recalculé automatiquement : le doublon demande une consolidation manuelle.
- Une première génération LMD exige une inscription active et validée ; pour un ancien bulletin existant, le recalcul reste possible s'il n'est pas publié et si les contrôles académiques passent.

## Vérifications

1. BTS : générer S1 puis S2, et régénérer une période existante ; comparer classe du tronc commun et spécialité.
2. BTS : refuser un bulletin publié, signé, et un dossier académique incomplet sans habilitation/motif.
3. LMD : générer puis régénérer S3/S4 ; refuser semestre hors niveau et bulletin publié.
4. Historiques : toute action conserve l'identité et l'année de l'inscription sélectionnée, et ne modifie pas une autre année.

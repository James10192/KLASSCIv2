# Gestion des notes dans les fiches

- `etudiants.show` : onglet Académique, toutes les inscriptions appartenant à l'étudiant, chaque évaluation filtrée par **classe ET année**.
- `inscriptions.show` : uniquement les évaluations de cette inscription, aucun mélange des années.
- Lecture : permission `notes.view` + droit sur la fiche. Création de note : `notes.create`. Modification : `notes.edit`. Suppression : `notes.delete` et serveur revérifiant étudiant, inscription, classe et année. Évaluations : `evaluations.create` / `evaluations.edit`. Contrôles **backend**, pas seulement boutons cachés.
- La création et la correction des notes réutilisent `ESBTPNoteController::saveNoteAjax` : validations, cohorte, barème, recalcul, brouillon, notes verrouillées.
- La création d'évaluation réutilise `ESBTPEvaluationController::store` en mode JSON ; le titre/barème/coefficient et la publication réutilisent les endpoints métier existants.
- Les matières BTS sont filtrées via la combinaison strictement filière+niveau et `btsOnly`; LMD utilise les matières des planifications de l'année. Aucun fallback naïf par matière homonyme.
- Le bulletin BTS ouvre un PDF d'un bulletin **existant** ; les relevés LMD utilisent la route sécurisée d'aperçu. Pas de publication implicite de notes ni de génération de document silencieuse.
- Les dialogues POST/PUT/DELETE utilisent `fetch`, CSRF et la surcharge Laravel `_method`, sans rechargement complet. Les erreurs de permissions (403), validations (422) et conflits remontent dans le dialogue.
- Aucun changement de DB, aucune modification rétroactive de notes, aucun déploiement sur tenants.

## Recette préalable
Vérifier un étudiant réinscrit avec S1 dans la classe tronc commun puis S2 en spécialité ; un étudiant LMD avec ECUE ; un professeur ne disposant que de `notes.manage_own` (ne doit pas voir le gestionnaire administratif) ; un comptable sans `notes.view` ; un rôle avec lecture seule ; une évaluation non publiée ; une note déjà soumise/verrouillée ; une suppression interdite ; des notes nulles/absence ; un PDF historique.

## Optimisation de l'intégration dans les fiches

Le panneau de notes utilise un en-tête de section discret, sans second grand hero bleu ni carte décorative. Sur `etudiants.show`, les requêtes académiques attendent que l’onglet devienne visible ; sur `inscriptions.show`, le bloc visible est chargé immédiatement. `IntersectionObserver` déclenche un seul chargement, avec repli pour les navigateurs anciens. Les modifications conservent les fetch AJAX, transitions existantes, contrôles de permissions et dialogues.

La suppression d'une note depuis la fiche est également soumise au verrou canonique `GradeSheetNoteMutationGuard`, sous transaction et verrou de fiche (`lockForUpdate`). Une fiche de notes validée ou un examen planifié avec `notes_locked` répond par conflit métier JSON (409), sans suppression ni recalcul indu.

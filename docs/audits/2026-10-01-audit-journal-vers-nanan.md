# Du journal d'audit aux actions de Nanan — 1er octobre 2026

Question posée : « en vérifiant les audits, voir les actions faites et comment
améliorer Nanan pour que lui aussi puisse faire cela ».

## Ce que ce rapport n'est pas

**Aucun chiffre de production n'a été lu.** L'environnement de travail n'a pas de
jeton CLI vers les écoles : la table `audits` des instances n'a pas été
consultée. Les fréquences ci-dessous sont donc **qualitatives**, déduites du code
(quelles écritures sont journalisées, par quel écran, pour quels rôles par
défaut) et du rythme métier d'une école (notes et présences chaque semaine,
inscriptions par campagne). Aucun volume n'est inventé.

La mesure qui trancherait, à rejouer sur chaque instance (lecture seule) :

```sql
-- Actions de personnes sur 90 jours, par sorte d'objet et par écran
SELECT a.auditable_type, a.event,
       SUBSTRING_INDEX(SUBSTRING_INDEX(a.url, '?', 1), '/', 6) AS ecran,
       r.name AS role, COUNT(*) AS actions, COUNT(DISTINCT a.user_id) AS personnes
FROM audits a
LEFT JOIN model_has_roles mr ON mr.model_id = a.user_id AND mr.model_type = 'App\\Models\\User'
LEFT JOIN roles r ON r.id = mr.role_id
WHERE a.user_id IS NOT NULL AND a.event <> 'retrieved'
  AND a.created_at >= NOW() - INTERVAL 90 DAY
GROUP BY 1, 2, 3, 4
ORDER BY actions DESC
LIMIT 60;
```

Aucun point d'accès `/api/cli/*` ne l'expose aujourd'hui (seul
`/api/cli/audit-comptable` existe). Un point d'accès en lecture, agrégé et sans
donnée personnelle, permettrait de reclasser ce tableau sur des chiffres.

## 1. Ce que le journal enregistre

Le journal (`/esbtp/audit`, gabarits `jda-*`, onglets dans
`App\Domain\Audit\ThemesDuJournal`) lit la table OwenIt `audits`. Chaque ligne
porte l'auteur, l'événement (`created`, `updated`, `deleted`, `restored`), les
valeurs avant/après limitées à la liste blanche `$auditInclude` du modèle, et
l'URL d'origine (donc l'écran). Trente-deux modèles sont audités ; ceux qui
portent les gestes quotidiens :

| Onglet | Modèle | Ce qu'une ligne raconte | Écran d'origine |
|---|---|---|---|
| Notes | `ESBTPEvaluation` | création, barème/coefficient, statut, publication de l'épreuve et des notes | `/esbtp/evaluations` (create, quick-update, cancel/restore, toggle-published, toggle-notes-published) |
| Notes | `ESBTPNote` | saisie et correction de note, absence | saisie rapide, modal notes, Nanan (`proposer_saisie_notes`) |
| Notes | `ESBTPResultat`, `ESBTPBulletin` | moyennes manuelles, génération et publication de bulletins | `/esbtp/resultats`, `/esbtp/bulletins` (bulk-publish, regenerate) |
| Notes | `ESBTPLMDJury*`, `ESBTPTpeDeclaration` | délibération LMD, validation TPE | `/esbtp/lmd/jurys`, `/esbtp/tpe-validation` |
| — | `ESBTPAttendance` | présence / absence d'un étudiant à une séance | `/esbtp/attendances`, `/teacher/attendance/mark` |
| Finances | `ESBTPPaiement`, `ESBTPPaiementAllocation` | encaissement, validation, annulation, ventilation | caisse, `/esbtp/paiements/{id}/valider`, `bulk-valider` |
| Finances | `ESBTPFraisSubscription`, `ESBTPFraisCategory`, `ESBTPFacture`, `ESBTPSalaire` | frais souscrits, configuration, factures, paie | comptabilité |
| Inscriptions | `ESBTPInscription`, `ESBTPEtudiant`, `ESBTPCandidature`, `ESBTPReinscriptionDemande` | création, validation, affectation de classe, traitement des demandes | `/esbtp/inscriptions` (valider, bulk-valider), candidatures (accepter/rejeter) |
| Comptes | `User`, rôles, permissions, réglages | comptes et droits | administration |

## 2. Les gestes, rôle par rôle

Tiré de `config/permissions.php` (`role_defaults`) croisé avec les écrans
ci-dessus. Les rôles décrits sont les rôles par défaut ; une école peut en
créer d'autres, et Nanan raisonne toujours en permissions, jamais en rôle.

| Rôle | Gestes audités récurrents |
|---|---|
| **Enseignant** | créer une évaluation, saisir ses notes, publier les notes, faire l'appel, valider des TPE |
| **Secrétaire / responsable de scolarité** | créer et valider des inscriptions, traiter candidatures et réinscriptions, créer des évaluations, corriger des notes, générer et publier les bulletins, saisir des présences |
| **Agent d'inscription** | créer, modifier et valider des inscriptions |
| **Caissier** | encaisser, valider des paiements, envoyer des relances |
| **Comptable** | valider des paiements, configurer les frais, relances, paie |
| **Coordinateur** | évaluations, notes, présences, bulletins (génération et publication en masse), jurys LMD, inscriptions |
| **Directeur des études** | planning, emplois du temps, maquettes LMD, bulletins LMD |

## 3. Ce que Nanan sait faire aujourd'hui

- **Lire** : 21 outils `search_*` / indicateurs (étudiants, notes, paiements,
  inscriptions, évaluations, présences, résultats, emplois du temps, impayés…).
  Trois sont désactivés par défaut (`CHATBOT_SENSITIVE_TOOLS_ENABLED`).
- **Proposer une écriture** (patron proposition → validation → exécution
  revérifiée, `App\Domain\Assistant\Actions\ExecutionDesPropositions`) :
  - saisie de notes sur une évaluation existante ;
  - suppression des moyennes sans note ;
  - configuration de la maquette BTS.

Le trou le plus visible : **la saisie de notes exige une évaluation qui
existe**, et ni sa création ni la publication des notes n'étaient à la portée
de Nanan. Le parcours le plus fréquent d'un enseignant — créer le devoir,
saisir les notes, les publier — n'était couvert qu'en son milieu.

## 4. Propositions classées

Valeur = fréquence probable × temps gagné × moindre risque. F/M/É = faible,
moyen, élevé. Toutes suivent le même garde-fou : proposition affichée,
validation explicite, préparation refaite et comparée au clic, écriture par le
chemin canonique (modèle audité), trace dans `chatbot_actions_log`.

| # | Action proposée | Fréquence | Temps gagné | Risque | Permission | Données nécessaires | Statut |
|---|---|---|---|---|---|---|---|
| 1 | **Créer une évaluation** | É | M | F | `evaluations.create` | classe, matière, type, date et horaires, barème, coefficient, semestre | **livré** |
| 2 | **Publier les notes d'évaluations terminées** (en lot) | É | M | M | `evaluations.edit` | identifiants d'évaluations | **livré** |
| 3 | Faire l'appel d'une séance (absents nommés) | É | É | M — chaque absence peut prévenir les parents | `attendances.create` | séance, liste des absents (matricule ou nom, résolus comme pour les notes) | suite recommandée |
| 4 | Annuler / réactiver une évaluation | M | F | M — recalcul des moyennes | `evaluations.edit` | évaluation ; passer par `ChangementDeStatut` | à faire |
| 5 | Traiter une demande de réinscription ou une candidature (accepter / rejeter avec motif) | M (en campagne) | M | M | `reinscriptions.demandes.*`, `inscriptions.candidatures.*` | demande, décision, motif | à faire |
| 6 | Publier les bulletins d'une classe | M | M | M–É — visibles des familles | `bulletins.publish.bulk` | classe, période ; contrôle de cohérence avant | à faire |
| 7 | Envoyer des relances ciblées | M | M | M — message parti ne se reprend pas | `comptabilite.relances.send` | liste d'impayés (déjà lisible par `search_debtors`) | à cadrer |
| 8 | Valider des inscriptions | M (en campagne) | M | É — règle : jamais une inscription dont le paiement est en attente | `inscriptions.validate` | inscriptions, état du paiement | après 3 à 7 |
| 9 | Valider des paiements en attente | É | M | É — argent, séparation créateur/validateur, période verrouillée, seuil gros montant | `paiements.validate` (+ `paiements.validate.high_amount`) | paiements ; outil de lecture des paiements activé | après mesure réelle |
| 10 | Modifier barème ou coefficient d'une évaluation notée | F | F | É — recalcul, moyennes officielles | `evaluations.edit` | évaluation ; `RecalculApresDeplacement` | non recommandé |
| 11 | Paie, jurys LMD, droits et rôles | F | — | É | — | — | **hors de Nanan** : décisions signées ou légales |

Pourquoi 1 et 2 d'abord : ils complètent l'action existante de saisie (le
parcours devient entier), ils écrivent un seul modèle déjà audité, une
erreur se corrige à l'écran (une évaluation en brouillon se supprime, une
publication se retire), et aucun message ne part vers une famille au moment de
l'écriture. L'appel (3) a plus de valeur encore, mais il déclenche des avis
d'absence : il demande d'abord de décider si Nanan les envoie ou les diffère.

## 5. Ce qui a été livré

- `App\Domain\Assistant\Actions\Evaluations\CreerEvaluation`
  (`proposer_creation_evaluation`). Mêmes règles que l'écran de création ;
  barème et coefficient **demandés**, jamais supposés ; cohérence BTS / LMD
  vérifiée avant de proposer ; doublon (même classe, matière, jour, titre)
  refusé ; un enseignant qui ne coordonne pas ne crée que pour une matière que
  la maquette de la classe lui confie cette année.
- `App\Domain\Assistant\Actions\Evaluations\PublierNotes`
  (`proposer_publication_notes`). Même règle que le bouton de l'écran
  (`canPublishNotes()` : évaluation terminée et notée) ; une évaluation non
  publiable bloque avec sa raison au lieu d'être sautée en silence ; les notes
  encore en brouillon sont signalées ; un enseignant ne publie que ses
  évaluations ; écriture modèle par modèle pour que chaque publication soit
  auditée.
- La liste des types d'évaluation vit désormais à un seul endroit
  (`ESBTPEvaluation::TYPES_SAISISSABLES`), lue par l'écran et par Nanan.

## 6. Hors périmètre de cette livraison

Le prompt système (`Harnais/ConstructeurDePrompt.php`, section `<actions>`)
décrit déjà de façon générique les outils `proposer_*` : les deux actions sont
utilisables sans y toucher. Une ligne aiderait Nanan à enchaîner le parcours,
à ajouter par l'équipe qui tient le Harnais :

```
- Pour des notes sur une évaluation qui n'existe pas encore, propose d'abord proposer_creation_evaluation ; une fois validée, prépare proposer_saisie_notes avec son identifiant, puis proposer_publication_notes seulement si la personne demande de publier.
```

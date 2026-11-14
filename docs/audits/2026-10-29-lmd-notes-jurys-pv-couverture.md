# Audit documentaire LMD — cycle Notes, Jurys, PV, relevés et clôture
**Date :** 29 octobre 2026  
**Dépôt lu :** `James10192/KLASSCIv2`, branche `presentation`.  
**Type :** audit statique de fonctionnalités et comparaison de référentiels, **pas recette fonctionnelle ou validation de production**.

## 1. Contexte et résultat
Une chaîne LMD complète ne s'arrête ni à la création de classe ni à l'emploi du temps. Elle doit assurer la preuve d'une **décision universitaire cohérente** : de la maquette et des inscriptions vers les notes, le rattrapage, les décisions et les actes officiels.

Une forte partie de ce socle existe dans KLASSCI : saisie LMD, examens, rattrapage, bulletins, jury, PV, registre documentaire, relevé, réclamations et portefeuilles de crédits. **La continuité en conditions réelles ne peut pas être déduite du seul code.**

## 2. Références examinées
1. **UEMOA**, Directive 03/2007/CM/UEMOA — adoption du LMD : [texte](https://www.e-docucenter.uemoa.int/fr/directive-ndeg032007cmuemoa-portant-adoption-du-systeme-licence-master-doctorat-lmd-dans-les).
2. **CAMES**, Référentiel de développement des systèmes d'information (notamment B.6–B.8) : [PDF](https://www.lecames.org/wp-content/uploads/2019/06/Referentiel-developpement-SI-CAMES.pdf).
3. **MESRS Côte d'Ivoire**, arrêté Licence du 13 décembre 2011 : [PDF](https://uvci.online/fr/__pdf/textes/7%20Arrete%20LICENCE%20du%2013%20dec%202011%20MESRS.pdf).
4. **MESRS Côte d'Ivoire**, arrêté Master du 13 décembre 2011 : [PDF](https://www.uvci.edu.ci/fr/__pdf/textes/6%20Arrete%20MASTER%20du%2013%20dec%202011%20MESRS.pdf).
5. **Sorbonne Nouvelle**, module APOGÉE gestion des notes et résultats : [documentation](https://www.sorbonne-nouvelle.fr/module-gestion-des-notes-et-des-resultats-888168.kjsp).

**Réserve réglementaire :** règles de note éliminatoire, compensation, seconde session, mention, absence et délivrance de documents sont à **valider sur le règlement des études de chaque établissement** et sur la juridiction applicable (Côte d'Ivoire, Bénin, etc.). Les defaults logiciels ne sont pas une norme universelle. Les obligations documentaires nationales doivent être revérifiées avant adoption par un établissement.

## 3. Matrice de couverture

| Étape / objectif CAMES / pratique APOGÉE | Preuve KLASSCI sur `presentation` | Niveau | Vérification de recette |
|---|---|---|---|
| Structure domaine→mention→parcours→niveau→semestre | Vues `esbtp/lmd/parcours`, `esbtp/lmd/ue`, `planning` et services `App/Services/LMD` | Présent dans le code | Épreuve S1-S6, réutilisation d'UE et contrôle des crédits par parcours |
| Évaluations par ECUE, contrôle continu et examen final | `ESBTPLMDNoteController`, `lmd/notes/index`, `docs/api/EXAMENS_LMD.md` | Présent | Vérifier coefficients, barèmes, complétude, attribution à l'enseignant |
| Saisie notes, absences, brouillons, validation | `lmd/notes/index.blade.php`, feuille de saisie rapide, grade guard | Présent | Vérifier statut persistant, correction tracée, différence 0 / absent / vide |
| Organisation des épreuves et anonymat | `ESBTPExamenPlanifieController`, `ESBTPExamenAnonymat` | Présent | Saisie par numéro de copie, levée d'anonymat autorisée |
| Rattrapages | `docs/api/RATTRAPAGE_LMD.md`, `lmd/rattrapage/show` | Présent | Lier session à sa source, contrôler `max|replace` et le résultat officiel |
| Calcul UE/ECUE, crédits et moyenne | `LMDBulletinService`, `LmdAcademicRuleProfile`, `JuryDeliberationService` | Présent, cohérence à tester | Scénarios limites, compensation intra/inter UE, crédit à 0, redoublement |
| Pré-jury, quorum, membres, signatures | `ESBTPLMDJuryController`, `jurys/partials/readiness`, `tab-composition` | Présent | Bloquer PV si note non validée, quorum ou signature insuffisants |
| Délibération, override motivé | `JuryDeliberationService`, `tab-deliberation` | Présent | Comparer auto et décision motivée, piste de vote, verrou après PV |
| PV semestriel et annuel, PDF, registre | `docs/api/JURY_DELIBERATION.md`, `actions-bar`, `official-document-panel` | Présent | Vérifier année S3/S4 et S5/S6, signatures, version, téléchargement, audit |
| Publication / bulletins / relevé | `lmd/resultats`, `lmd/bulletins`, `LmdTranscriptController` | Présent | Prouver PV = décision = bulletin = relevé pour 100 % de la cohorte |
| Réclamations et PV rectificatifs | `ReclamationsDeNotes`, `rectifierPv` | Présent | Modifier note approuvée et vérifier version supérieure, invalidation des actes précédents |
| Capitalisation historique et équivalences | `LmdCreditWalletService`, ledger; backlog LMD360 | Partiel / ambigu | Auditer équivalences, origine des crédits, réinscription, mobilité, doublons |
| Attestation, diplôme, supplément au diplôme | Recherche ciblée de l'existant | **Non démontré** | Vérifier modèles, signatures, publication et délivrance avant d'affirmer couverture |
| Parcours E2E + 3 profils + tests mobile | Code des vues et documentation pilote | **Non testé en réel** | Jouer des parcours du début à la fin sur un tenant de démonstration |

**Niveaux :** « Présent » signifie routes/classes/vues/contrats identifiés dans GitHub ; pas d'affirmation que l'option est déployée, utilisable sans erreur ou conforme au règlement de l'école. Les notes de backlog peuvent être plus anciennes que certaines implémentations, notamment sur le ledger des crédits.

## 4. Comparaison méthodologique avec APOGÉE
Le modèle université/APOGÉE privilégie le **contrat de contrôle des connaissances paramétré**, la **saisie**, les **calculs et contrôles**, les **résultats**, la **délibération/jury**, la **publication** et les **actes de fin de cursus**. KLASSCI dispose d'interfaces pour les étapes centrales. Pour être comparable en fiabilité, KLASSCI doit démontrer qu'il n'existe aucune double source de vérité entre notes validées, calculs de crédits, décisions du jury et documents publiés.

## 5. Risques et priorités
- **P0 — Cohérence académique :** vérifier que le rattrapage alimente effectivement la décision finale, que 0/absent/non-saisi restent distincts et que les crédits sont calculés depuis la maquette de **ce parcours et ce semestre**, pas celle d'un autre.
- **P0 — Chaîne documentaire :** bloquer PV sur feuilles non validées; après rectification, garantir que PV, bulletin, relevé, portefeuille affichent la même version officielle; interdire les actes contradictoires.
- **P0 — Permission et périmètre :** enseignant limité à ses ECUE, direction pédagogique aux matières concernées, scolarité aux tâches allouées; aucune mutation cross-tenant; correction de notes validées uniquement après autorisation ponctuelle auditable.
- **P1 — Expérience utilisateur :** guider « quelle est la prochaine action ? », expliquer états `brouillon/validé/publié`, rendre l'absence d'UE/ECUE/évaluation explicite, disposer d'une vue par classe et par étudiant.
- **P1 — Mobilité et transfert :** tester reprise d'équivalences, supplément au diplôme, documentation complète de la trajectoire de crédits sans doubles comptages.
- **P1 — Documentation :** captures **authentiques** avec données fictives/masquées par rôle; aujourd'hui seules **reconstitutions** basées sur le code sont disponibles.

## 6. Jeu de recette indispensable
**Instance de démonstration, données fictives.** Deux parcours avec une UE commune mais crédits différents; L1, L2 et L3; un étudiant admis, un ajourné, un absent, un deuxième-session admissible, un transfert ou redoublant.

1. Contrôler référentiel UE/ECUE, coefficients et total 30 CECT par semestre.
2. Créer CC+examen, saisir depuis profil enseignant, laisser une feuille incomplète : le pré-jury ne doit pas accepter la publication.
3. Valider les feuilles, calculer bulletins provisoires sans les publier, observer les crédits et le profil réglementaire utilisé.
4. Créer session normale puis rattrapage, saisir note 2, recalculer et vérifier le statut définitif sur tous les documents.
5. Organiser le jury, appliquer décisions automatiques, forcer une décision motivée et signer avec chaque compte membre.
6. Générer les PV semestriels et annuels, vérifier l'année (S3/S4 et S5/S6), l'identité, les signatures et la version.
7. Publier, récupérer bulletins et relevés officiels, comparer les documents à la décision et au wallet de crédits.
8. Corriger une note sur autorisation unique, émettre PV rectificatif, vérifier révocation/supersession et les droits d'accès aux anciens fichiers.
9. Confirmer 403 sur permissions interdites, séparation BTS/LMD, isolation des établissements, mobile 390px et zoom 200 %, tests d'impression.
10. Vérifier attestations/diplômes/suppléments et transférabilité quand ces fonctionnalités sont réellement disponibles.

## 7. Livrable de formation
Guide métier dans `docs/tutoriels/editorial/KLS-LMD-TUT-2026-03_cycle-notes-jury-pv-v1.md`. Trois schémas SVG de repérage sont stockés dans `docs/tutoriels/editorial/captures/KLS-LMD-TUT-2026-03/`, expressément étiquetés **RECONSTITUTION**. Ils ne peuvent pas être présentés comme des captures d'instance KLASSCI connectée.

**Critère de clôture** : pas de déclaration « LMD complet / production-ready » tant que les jeux de recette ne sont pas exécutés, résultats/relevés/PV comparés et écarts réglementaires explicitement approuvés par la direction des études.

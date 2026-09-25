# Audit E2E — circuit LMD, émargement et paie enseignants (25 septembre 2026)

Tenant : `presentation` (démo). Connexion navigateur en super administrateur, 58 écrans capturés
(57 en HTTP 200), 8 PDF officiels rendus. Aucune écriture sur une école en production.
Limite : aucun compte enseignant de démo documenté, l'émargement côté professeur est vérifié dans le code.

Version illustrée complète : page Claude « Audit E2E LMD KLASSCI » (lien donné dans la session).

## Notes par module (confort d'un agent qui va au bout sans appeler le support)

| Module | Note | Porte | Coûte |
|---|---|---|---|
| Jury de délibération | 7 | Quorum, pré-requis, PV numéroté + code de vérification + SHA-256 | Codes bruts (`ADMIS_SOUS_CONDITION`), jury LMD sur classe BTS |
| Examens planifiés | 6 | Cohorte UEMOA, convocations, PV surveillance | Anonymat décoratif, verrou sans effet, pas de lien vers la saisie |
| Bulletins LMD | 6 | Lecture UE/ECUE, PDF officiel, relevé annuel conditionné | Bulletin vide publiable, publié à 0,00 |
| Devoirs / TP | 6 | Types Devoir, TP, Projet, Oral | Formulaire BTS (« Période »), dates au format US |
| Résultats LMD | 6 | Vue classe + live | Sélecteurs natifs, cohorte live ≠ bulletins |
| Émargement | 5 | Fenêtres début/fin, retard, appel | Délais en dur, aucune prolongation, 3 écrans admin contradictoires |
| Saisie notes LMD | 5 | Entrée par classe | 0 évaluation partout malgré des examens |
| Ajournés | 5 | Écran propre | Aucun chemin vers le rattrapage |
| Paie enseignants | 4 | ITS/CNPS paramétrables, circuit brouillon→payé | Paie l'horaire prévu, net négatif, fiche sans net |
| Rattrapage | 3 | Workflow annoncé | Session sans session d'origine → cul-de-sac |
| Soutenance | 1 | Type d'épreuve | Aucun circuit mémoire |
| Documents enseignants | 1 | Fiche complète | Aucun agrément, contrat, attestation |

## Défauts prioritaires (prouvés)

1. **Anonymat factice** — `ESBTPExamenPlanifie.is_anonymous` est le seul champ ; l'écran dit lui-même « Sans numéro d'anonymat : la saisie des notes affiche les noms ». ![](captures/34-examen-1.jpg)
2. **Verrou des notes sans effet** — `ExamenSchedulingService::lockNotesAfterExam()` pose `notes_locked`, que rien ne lit ; l'examen n'a aucun lien vers une évaluation. Notes LMD : 0 évaluation sur 9 classes. ![](captures/05-lmd-notes.jpg)
3. **Bulletin vide publiable** — bulletin 4 (S3) publié à 0,00, 0/30 crédits, PDF officiel signé. ![](captures/pdf-bulletin4-s3-0.jpg)
4. **Rattrapage sans session d'origine** — session 1 créée sans parent ; « Saisir les notes » → « impossible de retrouver les résultats » ; « Publier » actif avec 0 examen. ![](captures/53-rattrapage-notes.jpg)
5. **Émargement rigide** — `ESBTP\TeacherAttendanceController::sign()` : +20 min présent, +45 min retard, absent auto au-delà, fin −20/+30 min, tout en littéraux ; aucune prolongation ni recours. ![](captures/42-seance-edit.jpg)
6. **Paie à l'horaire prévu** — `TeacherHoursService::dureeHeures()` paie `heure_fin − heure_debut` planifiés dès qu'un émargement existe ; 40 min de retard payées pleines.
7. **Net négatif validable, fiche sans net** — `PayrollComputationService::computePreview()` : `net = brut − retenues` sans plancher ; PDF sans ligne « Net à payer », marges nulles. ![](captures/pdf-payslip2.jpg)

## Incohérences et frictions

- « Année — » dans les bandeaux Jurys et Rattrapage. ![](captures/11-lmd-rattrapage.jpg)
- Délibération : décisions et mentions en codes bruts ; jury publié affiché en rouge « rectification atomique requise ». ![](captures/52-jury2-deliberation.jpg)
- Jury LMD rattaché à « 1BTS IG A » : aucune garde de système.
- Rapport d'émargement : en-tête violet (hors charte), ligne « VALIDÉ » mais 0 % validé, « N/A » en heure d'arrivée. ![](captures/44-emargement-generer.jpg)
- Planning → Émargement : 170 séances / 330 h mais « 0 matière », « 0 enseignant actif ». ![](captures/21-emargement.jpg)
- Deux modales empilées à la connexion (« Nouveautés » + « Expiration imminente »). ![](captures/F01-modales-empilees.jpg)
- Sélecteurs natifs : Résultats classe, Générer un bulletin, Nouvel examen (UE/ECUE).
- Examen « Droit Privé L1 S1 » rattaché à une Licence 3, semestre vide ; bouton « Ajouter au jury » dans le bloc Surveillants.

## Navigation cassée

| Depuis | Vers | Aujourd'hui |
|---|---|---|
| Examen | sa feuille de notes | aucun lien |
| Ajournés | rattrapage | seul lien : Jurys |
| Jury publié | bulletins | aucun lien |
| Rapport d'émargement | paie du mois | aucun lien |
| Fiche enseignant | fiches de paie, documents | aucun onglet |

## Priorités

1. Garde de publication des bulletins (pas de bulletin sans note, sauf dérogation motivée).
2. Paie : refuser le net négatif, imprimer le net, puis payer l'heure réelle (début→fin émargés, plafonnée ; réglage d'école planifiée/réelle).
3. Rattrapage : session d'origine obligatoire, « Inscrire au rattrapage » depuis les ajournés, « Publier » bloqué sans examen.
4. Examen → évaluation ECUE créée automatiquement ; verrou appliqué à la saisie ; lien « Saisir les notes ».
5. Anonymat réel : numéros par copie sur les convocations, saisie par numéro, levée tracée par une personne habilitée.
6. Prolongation de séance : demande par l'enseignant, décision par la personne habilitée avec contrôle de la séance suivante (salle, classe, enseignant) ; délais 20/45/30 en réglages ; justification de retard.
7. Documents enseignants : agrément/diplômes avec expiration, attestations d'heures et de service fait, contrat de vacation, vérifiables comme le PV.
8. Soutenances : sujet, directeur, autorisation, dépôt, planification sans conflit, jury, PV, report de note.
9. Finition : libellés français, « Année — », un seul écran d'émargement, sélecteurs premium, une modale à la fois.

## Références

- [Vademecum jury et examens, Université de Lille](https://droit.univ-lille.fr/filedroit/user_upload/Vademecum_jury_et_examens_23-24.pdf)
- [Charte des examens, Aix-Marseille Université](https://www.univ-amu.fr/system/files/2018-09/DEVE-HANDI-charte_des_examens.pdf)
- [OSE, paiement des vacataires — Campus Matin](https://www.campusmatin.com/numerique/equipements-systemes-informations/une-application-open-source-pour-reduire-les-delais-de-paiement-des-vacataires.html)
- [Procédure de soutenance de master, Université de Sétif](https://www.univ-setif.dz/externe/Soutenance-Master.pdf)

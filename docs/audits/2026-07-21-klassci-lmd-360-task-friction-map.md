# Carte des frictions par tâche

Date : 21 juillet 2026

Cette carte est un cognitive walkthrough. Aucun utilisateur représentatif n'a été observé pendant la session.

| Persona et tâche | Départ | Friction principale | Risque | Reprise | Décision |
|---|---|---|---|---|---|
| Scolarité, trouver ce qui bloque le jury | Classe ou liste jurys | Les prérequis sont dispersés entre notes, quorum et décisions | Élevé | Faible | Créer une readiness canonique et une liste d'actions contextuelle |
| Enseignant mobile, saisir puis soumettre | Dashboard enseignant | Compte et fixture absents; verrou contournable par bulk write | Critique | Non prouvée | Mutation unique, brouillon hors ligne chiffré dans un lot ultérieur |
| Responsable UE, contrôler plusieurs enseignants | AcademicPilotage | Affectations et populations de matières ont plusieurs sources | Élevé | Partielle | Résolveur d'autorisation et snapshot pédagogique commun |
| Agent examens, résoudre un conflit de salle | Examen | Historique visible, mais responsabilités et conflit ne sont pas réunis | Moyen | Bonne | Étendre l'écran existant, ne pas recréer ExamOps |
| Président, examiner un cas exceptionnel | JuryRoom | Aucune cohorte sur le jury observé; règles et décision non canoniques | Critique | Faible | Pré-jury, décision officielle et review screen |
| Secrétaire, reprendre après interruption | JuryRoom | Statuts présents sans machine d'états explicite | Critique | Faible | Transitions transactionnelles et journal append-only |
| Étudiant, comprendre une non-validation | Résultat étudiant | `NAQ`, tirets et crédits zéro ne donnent pas la cause | Élevé | Bonne lecture, faible compréhension | Explication par règle et données manquantes |
| Auditeur, retrouver une modification | Historique examen | Audit partiel et rétention limitée | Élevé | Partielle | Journal légal immuable lié aux snapshots |
| Multi-fonctions, enchaîner contrôle et jury | Classe vers workspaces | Navigation possible mais responsabilités non résolues uniformément | Élevé | Partielle | Conserver le contexte et unifier le scope |

## Points de peur ou perte de confiance

1. Un PDF portant la même référence peut être régénéré avec un contenu différent.
2. Une décision de jury peut diverger du bulletin affiché.
3. Une note validée peut être modifiée par une écriture bulk.
4. Un membre peut être signé par un autre utilisateur autorisé.
5. `0`, absence de calcul et donnée manquante sont visuellement confondus.
6. Une erreur dashboard expose une stack trace.
7. Le jury peut afficher une action avant que les prérequis soient complets.
8. Les règles varient selon le service appelant.
9. Les données chatbot ne sont pas limitées au périmètre de l'utilisateur.
10. L'absence de fixtures empêche de prouver les permissions des personas.

## Mesures à instrumenter

- Nombre d'interactions jusqu'au blocage expliqué.
- Temps jusqu'au premier contenu utile.
- Nombre de pages et changements de contexte.
- Nombre de termes non expliqués.
- Capacité de reprise après interruption.
- Erreurs réseau et mutations rejouées.
- Temps et confiance perçue à la fin de la tâche.


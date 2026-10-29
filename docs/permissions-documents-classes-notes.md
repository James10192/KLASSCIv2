# Permissions ciblées — classes, notes et documents

## Principe

Un droit de consultation ne vaut jamais autorisation implicite d'export ou de
modification. Les permissions ci-dessous sont attribuables à un rôle ou à une
personne via le mécanisme existant « Accès individuels » (temporaire ou
permanent). La présence d'un bouton dans une vue ne fait pas autorité : la
route et, pour les documents sensibles, le contrôleur vérifient le droit.

| Surface | Consultation / aperçu | Export / action |
| --- | --- | --- |
| Catalogue, cartes et fiche classes | `classes.view` | `classes.create`, `classes.edit`, `classes.delete` |
| Tableau nominatif d'une classe | `classes.students.view` | `students.edit` pour changer l'affectation |
| Liste d'appel | `classes.liste_appel.view` | `classes.liste_appel.export` pour PDF |
| Liste complète des étudiants | `classes.liste_complete.view` | `classes.liste_complete.export` pour PDF ou Excel |
| Catalogue de classes, tous formats | `classes.view` | `classes.export` pour PDF, CSV ou Excel |
| Grille BTS et indicateurs de notes | `notes.view` | `notes.create`, `notes.edit`, `notes.manage_own` selon l'action |
| Notes Excel | `notes.view` pour la grille | `notes.export_excel` |
| Fiche vierge de saisie (classe/évaluation) | `notes.feuille_vierge.export` | Même droit pour prévisualiser ou télécharger |
| Évaluation avec notes renseignées | `evaluations.notes.export` | Même droit pour prévisualiser ou télécharger ; ancien alias couvert |
| Relevé de notes BTS interne | `bts.releve.view` | `bts.releve.export` |
| Grille de notes LMD | `lmd.notes.view` | `lmd.notes.manage` + droits de saisie adéquats |
| Relevé officiel LMD | `lmd.releve.view` | `lmd.releve.issue` pour l'émission versionnée |
| Bulletins BTS/LMD et exports groupés | `bulletins.view`, `lmd.bulletins.view` | Réutiliser les droits existants `bulletins.generate`, `bulletins.export.bulk`, `lmd.bulletins.generate`, `lmd.bulletins.export.bulk` |
| Suivi des notes manquantes, sans évaluation, hors maquette | `academic_health.view` ou `academic_health.view_own` | `academic_health.recalculate` uniquement pour recalculer |

## Règles de périmètre et UX

- Les contrôleurs limitent les classes et notes d'un enseignant à son périmètre
  d'enseignement, sans se fier à un simple identifiant fourni par le navigateur.
- Les classes sont universelles : l'inscription et les séances fixent l'année,
  pas une colonne d'année portée par la classe elle-même.
- Les exports PDF/Excel/CSV et leurs prévisualisations sont contrôlés directement
  sur les routes, avec limitation de fréquence. `classes.view` n'autorise pas
  l'export des listes nominatives.
- Les cartes, boutons, liens desktop/mobile et modals de consultation reflètent
  les mêmes droits sans rechargement complet (`AJAX no reload`).
- Le suivi des notes réutilise le composant de couverture existant : il distingue
  les notes manquantes, l'absence d'évaluation et les matières hors maquette.
  Son accès suppose également le droit d'activation du module Pilotage
  académique (`module.academic_pilotage.access`).
- Les écrans de notes nécessitent aussi le toggle métier correspondant :
  `module.notes_evaluations.access` pour BTS et `module.lmd.access` pour LMD.

## Activation sur une instance existante

1. Déployer sur une instance de présentation d'abord. Conserver une sauvegarde
   de base et relever le SHA exact de la version déployée.
2. Synchroniser le registre des nouvelles permissions avec la procédure
   habituelle du projet (`php bin/deploy/fix_permissions.php`) depuis le dossier
   Laravel. Aucune nouvelle table n'est requise par cette modification.
3. Dans Rôles & Permissions ou Accès individuels, attribuer **explicitement**
   les droits de documents nécessaires au personnel concerné. Ne pas attribuer
   tous les exports à chaque rôle par défaut : c'est un choix de l'établissement.
4. Vérifier au moins un profil vue seule, un profil export seul, un profil
   enseignant borné à ses classes et un profil sans droit ; tester les URL PDF,
   Excel et AJAX directement, sans passer par les boutons.
5. Contrôler les logs applicatifs, la fonctionnalité de génération de bulletins
   (le sélecteur d'étudiants est partagé), les filtres AJAX et les requêtes SQL
   sur une classe très remplie avant toute propagation.

**Attention :** ne pas substituer `migrate:rollback` à la synchronisation de
permissions. Une marche arrière de code et un retour de données sont deux
opérations différentes et demandent une vérification de compatibilité.

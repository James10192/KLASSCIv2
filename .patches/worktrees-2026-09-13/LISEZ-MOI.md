# Sauvegarde avant nettoyage des worktrees — 13 septembre 2026

42 worktrees tranaient sous `.claude/worktrees/`, restes de sessions d'agents
jamais nettoyees. Ils ralentissaient toute recherche recursive lancee depuis la
racine du depot.

`git worktree remove` ne detruit que le repertoire de travail : les branches et
leurs commits restent dans `.git`. Le seul risque etait donc le non-commite.
C'est ce que ce dossier conserve.

## Les sept correctifs

Un `.patch` par worktree portant des modifications non commitees, produit par
`git diff HEAD` (et non `git diff`, qui aurait manque les 26 suppressions
stagees de `wf_7eb564e8-12f-4`). Reapplication : `git apply <fichier>.patch`.

## Les trois fichiers non suivis

Un patch ne capture pas un fichier que git ne suit pas. Ceux-la sont copies tels
quels :

- `RepartitionEncaissement.php` (508 l.) et son test (292 l.) — **superseded**.
  Ces 800 lignes n'ont jamais ete commitees nulle part, mais le probleme qu'elles
  traitent — un versement couvrant plusieurs frais, dont `max(0, du - paye)`
  ecretait l'excedent — a ete resolu et livre depuis sous un autre nom :
  `app/Services/Frais/RepartitionDuVersement.php` (441 l., 18 tests). Le
  brouillon est conserve pour une seule raison : quatre de ses cas de test
  n'ont pas d'equivalent evident dans la version livree — deux lignes pour le
  meme frais, lignes a zero, le frais majoritaire porte le paiement, et
  l'empreinte d'une repartition. A verifier avant de jeter.
- `pdf-detaille.blade.php` — export paiements, version d'avril, distincte de
  celle de `presentation`.

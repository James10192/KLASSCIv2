# Deux branches qui n'existaient que localement — 14 septembre 2026

`fix/journal-sans-etat-civil` (1 commit) et `worktree-agent-ae4c218f136fe72bb`
(4 commits) n'avaient aucune copie sur `origin`. Leur suppression aurait ete
definitive : d'ou ce bundle, qui les conserve entieres.

Les patchs individuels ont ete retires. Le bundle suffit : il porte les cinq
commits, avec leurs messages et leur filiation.

Il est **mince** a dessein : il ne contient que les objets absents de
`presentation`, soit 69 Ko. Un bundle construit sur les noms de branches
embarquait tout leur historique — 196 Mo, qui auraient alourdi chaque clone
du depot pour toujours. En contrepartie il s'appuie sur deux commits de base
(`801ad6379` et `b4189d757`), tous deux dans l'historique de `presentation` :
il se deplie donc depuis n'importe quel clone, mais pas hors du depot.

## Recuperer

    B=.patches/branches-locales-2026-09-14/cinq-commits.bundle
    git bundle verify $B
    git fetch $B refs/archive/caisse-et-au-select:refs/heads/recup-caisse
    git fetch $B refs/archive/journal-sans-etat-civil:refs/heads/recup-vie-privee

## Ce que contiennent ces commits

**Deja livre sur `presentation`, verifie par comparaison du contenu** — rien a
faire, conserve pour trace uniquement :

- `908d9ea7a` vie privee : l'etat civil des eleves hors des journaux.
  `ContactController` ecrit bien `champs_recus => array_keys(...)`.
- `a5b5432df` au-select : la geometrie du menu. `auSelectAncetreBloquant` et
  `positionMenu` sont dans le composant, qui a depuis beaucoup evolue.
- `f3624f10b` son entree de changelog.

**Jamais livre, et les deux vont ENSEMBLE** :

- `ea5aef59c` `fix(caisse): le montant d'abord…` — restructuration de l'ecran
  d'encaissement. Elle introduit une seconde cause de blocage du bouton
  d'envoi sans lui donner de memoire.
- `d7f98ce8e` `fix(caisse): le blocage « frais illisibles » ne s'annule plus
  tout seul` — c'est lui qui repare cela, en remplacant le booleen unique par
  un jeu de raisons nommees.

**Ne jamais reprendre le premier sans le second.**

Aucun des deux ne s'applique tel quel a la lignee actuelle de
`create.blade.php` : ni `let soldes = null` ni `function oublierEtudiant`,
sur lesquels ils se greffent, n'y existent.

## Une erreur a ne pas refaire

On a d'abord cru le defaut de `d7f98ce8e` vivant en production, au motif que
ni `raisonsDeBloquer` ni la raison `'frais-illisibles'` ne figurent dans le
fichier actuel. C'est un raisonnement faux : **l'absence d'un correctif ne
prouve un defaut que si les CONDITIONS du defaut sont reunies.**

Elles ne le sont pas. `bloquerEnvoi(bloque)` n'a aujourd'hui qu'un seul
proprietaire — l'apercu de repartition — et le gestionnaire d'erreur de
`loadCategories` ne bloque rien, il journalise. Sans seconde cause, le booleen
unique n'entre en collision avec personne.

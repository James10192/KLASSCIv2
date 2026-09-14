---
name: thermo-nuclear-code-quality-review
description: Revue de qualité adverse avec verdict bloquant sur un diff KLASSCI. Cherche la complexité qu'on aurait pu ne pas écrire, le code écrit et jamais lu, les valeurs en dur, les replis silencieux et les secondes sources de vérité. Obligatoire avant tout commit, toute fusion et tout déploiement (rule pre-merge-checklist, commandement 0). Use before committing, merging or deploying any code change.
---

# Revue thermo-nucléaire

## Ce que cette revue cherche — et ce qu'elle ne cherche pas

Trois revues coexistent dans ce dépôt. Elles ne font pas le même travail, et les confondre revient à n'en faire aucune.

| Revue | Question posée | Sortie |
|---|---|---|
| `/code-review` | **Est-ce que ça marche ?** Bugs, sécurité, tests, performance | Liste de constats |
| Audit 4 axes (`quality-gate`) | **Est-ce que ça casse autre chose ?** Régressions, multi-instance, SOLID | Liste de risques |
| **Thermo-nucléaire** | **Fallait-il l'écrire ?** Complexité évitable, code mort-né, doublons d'autorité | **Verdict `PASS` / `BLOCK`** |

La thermo **ne remplace pas** l'audit 4 axes, elle s'y ajoute. L'audit cherche les régressions ; la thermo cherche ce qu'on aurait pu supprimer.

> Le défaut le plus coûteux de ce dépôt n'a jamais été un plantage. C'est du code qui s'exécute, qui ne lève rien, et qui sert une valeur fausse ou ne sert à personne. C'est cela qu'on traque ici.

## Quand elle est obligatoire

Avant **tout commit, toute fusion et tout déploiement** qui touche du code. C'est le commandement 0 de `.claude/rules/pre-merge-checklist.md`.

```bash
git diff origin/presentation...HEAD --stat     # la plage à donner au sous-agent
git diff origin/presentation...HEAD            # le diff lui-même
```

Sur une branche tenant, remplacer `presentation` par la base réelle.

## Exemptions

Quatre cas, et seulement ceux-là :

- documentation seule ;
- configuration seule ;
- suppressions pures (aucune ligne ajoutée) ;
- diff de **moins de cinq lignes dans un seul fichier**.

Une exemption se constate, elle ne se décide pas. En cas de doute, la revue a lieu.

## Running it yourself, as an agent

La revue se lance **en sous-agent**, pour que le contexte du diff ne pollue pas la session qui vient d'écrire le code — et surtout pour qu'elle ne soit pas relue par celui qui l'a écrite.

```
Agent(
  subagent_type: "general-purpose",
  description:   "Revue thermo-nucléaire",
  run_in_background: true,
  prompt: <le gabarit ci-dessous, avec la plage de diff réelle>
)
```

### Gabarit de brief

> Tu es un relecteur adverse sur le dépôt KLASSCIv2 (`/home/user/KLASSCIv2`), Laravel, scolarité multi-instance, huit instances en production en Afrique de l'Ouest.
>
> **Plage à relire :** `git diff origin/presentation...HEAD` (lis le diff ET les fichiers entiers qu'il touche — un diff ne montre pas ce qui manque).
>
> Ta question n'est pas « est-ce que ça marche ». C'est **« fallait-il l'écrire ? »**. Applique les sept axes et les détecteurs KLASSCI du skill `thermo-nuclear-code-quality-review` (`.claude/skills/thermo-nuclear-code-quality-review/SKILL.md`) — lis-le en entier avant de commencer.
>
> Les rules de `.claude/rules/` font autorité. Toute violation est un `BLOCK`.
>
> **Contraintes :**
> - `fichier:ligne` obligatoire pour chaque constat. Si tu n'as pas lu, ne l'affirme pas.
> - Ne fabrique pas d'objection : un faux positif coûte plus cher qu'un silence, parce qu'il apprend à ignorer les verdicts.
> - Tu as le droit de conclure « cette partie est juste, n'y touchez pas ».
> - Lecture seule. N'écris ni ne modifie aucun fichier.
>
> **Rends un verdict `PASS` ou `BLOCK`**, puis les constats classés, chacun avec sa correction proposée.

### Si le sous-agent est indisponible

Contexte saturé, outil refusé, quota. Alors : **dis-le clairement** et fais la revue toi-même contre les mêmes standards. La sauter en silence n'est jamais acceptable — et c'est exactement ce que cette règle existe pour empêcher.

## Les sept axes

### 1. Ce qui est écrit et jamais lu

Le défaut signature de ce dépôt. Pour chaque champ, colonne, table, réglage ou valeur de retour **ajouté** par le diff, prouve qu'il a un lecteur.

```bash
grep -rn "nom_du_champ" app/ resources/ routes/ config/
```

Un seul résultat — celui qui l'écrit — est un `BLOCK`. Cas réels : une motivation de décision de jury calculée puis jetée ; une colonne de crédit de pivot alimentée par l'import et lue par aucun calcul ; un registre de crédits rempli à chaque publication de bulletin dont le seul lecteur est un contrôleur non routé.

### 2. La seconde source de vérité

Le diff introduit-il un second endroit qui répond à une question déjà répondue ailleurs ? Deux calculs de la même moyenne, deux façons de savoir qui est diplômé, deux moteurs de décision.

Deux sources ne se contredisent pas tout de suite. Elles divergent, et personne ne les compare.

### 3. La valeur en dur

`.claude/rules/rien-en-dur.md` fait autorité. Le test : **deux écoles peuvent-elles légitimement vouloir une valeur différente ici ?** Si oui, ça se configure.

```bash
grep -rnE '[><=]=?\s*[0-9]{4,}' --include="*.php" app/ | grep -vE 'max:|min:|migrations|Test\.php'
grep -rn "hasRole(" --include="*.php" app/ | grep -vE "superAdmin|serviceTechnique"
```

### 4. Le zéro confondu avec l'absence

Un cas particulier de l'axe 3, assez fréquent pour mériter son propre passage.

```bash
grep -nE '\?:\s|\?\?\s*[0-9]|> 0\)|!= 0|empty\(' <fichiers du diff>
```

Chaque `?:`, chaque `> 0` en filtre, chaque `empty()` sur un nombre : **le code veut-il dire « absent » ou « nul » ?** Un montant nul est une valeur ; une moyenne de 0,00 est une note ; un total de crédits nul n'est pas un défaut de 30.

### 5. Le repli silencieux

Un `catch` qui vide une collection, un `?? valeur_par_défaut` sur une configuration introuvable, un `continue` qui saute une ligne.

**Un rattrapage qui dégrade l'affichage doit toujours journaliser ce qu'il a rattrapé.** Sinon on ne cherche même pas. Un repli muet est un `BLOCK`.

### 6. Le code non atteint

- un contrôleur, une méthode, une route, une permission **déclarée sans consommateur** ;
- un enum ou une constante sans référence ;
- une permission au registre sans `can()`, ou une route sans permission.

```bash
php artisan route:list --path=<chemin>      # le contrôleur édité est-il celui qui sert ?
grep -rn "nom.permission" app/ resources/ routes/ config/
```

Le piège n°1 de `.claude/rules/klassci-debugging-discipline.md` — `ESBTPEtudiantController` contre `ESBTPStudentController` — a coûté trois heures. Il se détecte en trente secondes.

### 7. La complexité qu'on pouvait ne pas écrire

- Un agrégat neuf là où un type de plus suffisait (`OfficialDocument.document_type` est une chaîne libre : ajouter un type n'appelle aucune migration).
- Un service qui enveloppe un service qui enveloppe une requête.
- Un paramètre booléen qui fait de la fonction deux fonctions (`.claude/rules/lmd-bts-matieres-single-source.md`).
- Une abstraction pour deux cas dont le second est hypothétique.

Question à poser sur chaque classe ajoutée : **qu'est-ce qui, dans le dépôt, faisait déjà ça ?**

## Les détecteurs KLASSCI

Défauts récurrents, chacun déjà survenu en production. Chaque ligne est un `BLOCK`.

| Détecteur | Commande ou repère |
|---|---|
| Colonne sélectionnée mais jamais migrée | `grep -rln "colonne" database/migrations/` → doit toucher **la bonne table** |
| `nom` contre `name`, `phone` contre `telephone` | vérifier le `$fillable` de **chaque** modèle visé par un eager-load |
| Migration qui ensemence avec `created_by => 1` | `DB::table('users')->min('id')` — sinon la suite de tests casse sur base vide |
| Migration créée à la main | `php artisan make:migration` obligatoire (`.claude/rules/migrations.md`) |
| `down()` absent ou non testé | additif et nullable uniquement |
| Import croisé BTS ↔ LMD | `.claude/rules/lmd-bts-bulletin-separation.md` |
| ECUE LMD dans un sélecteur BTS | `->whereNull('unite_enseignement_id')` sur tout listing **global** de matières |
| `$classe->matieres` en direct | passer par `MatiereTreeBuilder` |
| Classe filtrée par `annee_universitaire_id` | une classe est universelle ; l'année vit sur l'inscription |
| Réglage déclaré qui ne pilote aucun calcul | ne pas l'affirmer dans un document officiel |
| Seuil lu au rendu plutôt que figé | réécrit rétroactivement des décisions archivées (#798) |
| `@php(...)` court + `@php...@endphp` dans le même fichier | `.claude/rules/blade-pitfalls.md` |
| Directive `@can` / `<x-...>` dans un commentaire JS ou CSS | idem — 500 au runtime, invisible à la compilation |
| `@json([...])` multiligne | extraire en `@php $var = [...]; @endphp` |
| `:style` Alpine sur un élément portant déjà `style=` | l'un écrase l'autre |
| `transform` au survol d'un parent de menu déroulant | rompt le bloc conteneur (`universal-dropdowns.md`) |
| Parcours à plusieurs étapes enfermé dans une modale | lui donner une route |
| Rôle inventé plutôt qu'une permission | `.claude/rules/customizable-roles.md` |
| Nom d'établissement, indicatif, fuseau en dur | réglage d'instance, défaut inchangé |

## Le verdict

```
VERDICT : PASS | BLOCK
Plage    : origin/presentation...HEAD  (N fichiers, +X / -Y)

BLOQUANTS  (n)
  [axe] fichier:ligne — constat en une phrase
        → correction proposée

À CORRIGER AVANT FUSION  (n)
  ...

REMARQUES  (n)
  ...

JUSTE, NE PAS TOUCHER  (n)
  ...
```

La dernière rubrique n'est pas une politesse : elle évite qu'on « améliore » au prochain passage ce qui avait été pesé.

## Après un `BLOCK`

On ne commit pas, on ne fusionne pas, on ne déploie pas. On corrige, **puis on relance la revue sur le nouveau diff** — pas sur l'ancien.

Un bloquant ne se discute pas avec le relecteur : il se corrige, ou il se retire du diff. Si le constat est faux, la réponse est de le prouver par `fichier:ligne`, pas de l'argumenter.

## Voir aussi

- `.claude/rules/pre-merge-checklist.md` — commandement 0, qui rend cette revue obligatoire
- `.claude/rules/rien-en-dur.md` — axes 3 et 4
- `.claude/rules/klassci-debugging-discipline.md` — axe 6, les treize pièges
- `.claude/rules/blade-pitfalls.md` · `.claude/rules/blade-alpine-pitfalls.md`
- `.claude/rules/customizable-roles.md` · `.claude/rules/permissions.md`
- `.claude/rules/feature-delivery-methodology.md` — phase 9
- `/code-review` et `/simplify` — les deux autres revues, qui posent d'autres questions

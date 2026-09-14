---
name: thermo-review
description: Revue adverse avec verdict bloquant sur un diff KLASSCI, sur onze axes. Côté code — complexité évitable, code écrit et jamais lu, valeurs en dur, replis silencieux, secondes sources de vérité. Côté produit — pertinence vérifiée à sa source, design premium prouvé par capture réelle, répartition du travail à la source plutôt qu'accumulation et relance, liens profonds, modales, absence de rechargement. Obligatoire avant tout commit, toute fusion et tout déploiement (rule pre-merge-checklist, commandement 0). Use before committing, merging or deploying any change.
---

# Revue thermo-nucléaire

## Ce que cette revue cherche — et ce qu'elle ne cherche pas

Trois revues coexistent dans ce dépôt. Elles ne font pas le même travail, et les confondre revient à n'en faire aucune.

| Revue | Question posée | Sortie |
|---|---|---|
| `/code-review` | **Est-ce que ça marche ?** Bugs, sécurité, tests, performance | Liste de constats |
| Audit 4 axes (`pre-commit-quality-gate`) | **Est-ce que ça casse autre chose ?** Régressions, multi-instance, SOLID | Liste de risques |
| **Thermo-nucléaire** | **Fallait-il l'écrire — et fallait-il le faire ainsi ?** Complexité évitable, code mort-né, doublons d'autorité ; puis pertinence, ergonomie, répartition du travail | **Verdict `PASS` / `BLOCK`** |

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
> Ta question n'est pas « est-ce que ça marche ». C'est **« fallait-il l'écrire, et fallait-il le faire ainsi ? »**. Applique les onze axes et les détecteurs KLASSCI du skill `thermo-review` (`.claude/skills/thermo-review/SKILL.md`) — lis-le en entier avant de commencer, **partie B comprise**.
>
> Les rules de `.claude/rules/` font autorité. Toute violation est un `BLOCK`.
>
> **Pour la partie B, tu as le droit et le devoir d'utiliser :**
> - **la recherche internet** — axe 8 : toute affirmation sur le monde extérieur que le diff encode doit porter sa source, ou être marquée non vérifiée. Vérifie-la toi-même plutôt que de la croire.
> - **la mémoire projet et les rules** — une question déjà tranchée ne se retranche pas ; une décision déjà écrite se cite.
> - **l'agent `critique-transversale`** si le diff touche un parcours utilisateur entier et que tu veux un second angle.
>
> Tu ne peux pas exécuter l'application. Pour les axes 9 et 11, **exige la preuve plutôt que de la produire** : dis précisément quelle capture d'écran ou quelle exécution `/klassci-test-e2e` manque, et sur quel écran.
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

## Partie A — Axes code : fallait-il l'écrire ?

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

## Partie B — Axes produit : fallait-il le faire ainsi ?

Un diff peut être irréprochable en code et livrer la mauvaise chose, ou la bonne chose sous une forme que personne ne voudra utiliser. Ces quatre axes gouvernent le **même verdict** que les sept précédents.

### 8. Pertinence — est-ce seulement vrai ?

KLASSCI est adossé à des réalités extérieures : textes UEMOA et CAMES, droit national, procédures d'un ministère, pratique réelle d'une école. **Une décision de conception qui repose sur une affirmation sur le monde extérieur doit porter sa source.**

Ce n'est pas théorique. En une seule session d'audit, trois affirmations qui semblaient solides se sont révélées fausses ou périmées :

- « la migration téléphonique béninoise de 2020 » — c'était **novembre 2024**, et la règle réelle rendait le défaut bien plus large ;
- un décret cité comme fondement en vigueur avait été **déclaré contraire à la Constitution** sept ans plus tôt ;
- « homologation » et « accréditation » étaient employées comme synonymes — deux autorités, deux conséquences.

**Le contrôle :** pour chaque affirmation extérieure que le diff encode — un seuil réglementaire, un format de document, une obligation légale, une pratique métier — l'auteur peut-il produire la source ? Sinon, deux issues seulement : aller la chercher, ou **marquer l'hypothèse comme non vérifiée dans le code et dans l'issue**. Une supposition assumée est acceptable ; une supposition déguisée en fait ne l'est pas.

Corollaire : une recherche qui n'aboutit pas se dit. « Je n'ai pas pu vérifier le texte officiel » vaut infiniment mieux qu'un silence qui laissera croire que c'était vérifié.

### 9. Design premium et ergonomie — preuve, pas déclaration

Une page n'est pas premium parce qu'on l'affirme. Elle l'est quand on la voit.

**La preuve exigée est une capture d'écran réelle du parcours livré**, prise sur un tenant, jamais une maquette redessinée — c'est la règle d'or de `/klassci-user-tutorial`, et elle vaut ici : *une capture réelle mal cadrée vaut mieux qu'une belle maquette fausse*. Pour un parcours qui traverse données et écrans, la preuve est une exécution `/klassci-test-e2e` sur le tenant, avec le chemin réel qui plantait.

Grille, tirée de `premium-redesign.md` et `premium-selects.md` :

- hero copié du patron `planning-header`, pas réinventé — et réservé aux pages de liste ou de tableau de bord ;
- palette monochrome bleu ; couleurs sémantiques **seulement** quand elles portent un statut à capter en moins d'une seconde ;
- namespace CSS dédié, pas de fuite dans les classes globales ;
- aucun `<select>` natif visible ; aucun menu déroulant tronqué par un parent ;
- utilisable à 400 px de large, sans défilement horizontal ;
- états vides qui **proposent l'action** au lieu de constater l'absence ;
- boutons Guide et Aide dès que l'écran cumule filtres, indicateurs et actions (`interactive-guides.md`).

**Le test qui tranche :** une secrétaire qui n'a jamais vu cet écran sait-elle quoi faire en dix secondes ? Si la réponse exige une formation, le défaut est dans l'écran, pas dans la secrétaire.

### 10. La répartition du travail — le plus important, et le plus oublié

Une fonctionnalité peut être juste, belle, et **faire porter à une personne un travail qu'une autre aurait fait sans effort à la source**.

Deux formes, toutes deux coûteuses :

- **L'accumulation.** Une entité collecte, empile, puis ressaisit d'un coup ce que dix personnes savaient chacune au moment où l'information existait. Une secrétaire qui recopie cent fiches de présence papier fait un travail que cent enseignants avaient déjà fait.
- **La relance.** Une entité doit courir après une autre pour obtenir ce dont elle a besoin. Chaque relance est un travail pur, qui ne produit rien, et qui recommence.

**La question à poser sur toute fonctionnalité ajoutée :**

> Qui détient l'information **au moment exact où elle existe** ? C'est là qu'elle doit être saisie, une fois, par cette personne.

Si le diff crée un écran dont l'unique fonction est de permettre à quelqu'un de **ressaisir ce qu'un autre savait déjà**, ce n'est pas une fonctionnalité : c'est une dette organisationnelle déguisée en produit.

Contrôles concrets :

- La donnée est-elle saisie par celui qui la détient, ou recopiée par un tiers ?
- Le parcours crée-t-il une attente d'un acteur envers un autre ? Si oui, l'attendu peut-il agir directement — et sinon, le système relance-t-il **à la place** de l'humain ?
- Le travail est-il étalé au fil de l'eau, ou concentré en une pointe que quelqu'un devra absorber ?
- Existe-t-il une saisie en masse pour les cas où l'étalement est impossible ?

Le module de travail personnel étudiant en est l'illustration réussie : l'étudiant déclare, l'enseignant valide si l'école le veut, et **personne ne ressaisit**.

### 11. Fluidité — liens profonds, modales, absence de rechargement

**Liens profonds.** Le test tient en une phrase : **puis-je envoyer à un collègue l'écran exact que je regarde ?** Tout état filtré, trié, paginé doit vivre dans l'URL, et l'écran cible doit pré-remplir ses filtres depuis la chaîne de requête (`premium-redesign.md`, section « Pré-remplir un filtre UI depuis query string »). Un bouton qui mène vers une page cible **sans emporter son contexte** est un défaut.

**Modales.** Elles sont légitimes pour une décision unitaire — un champ, une confirmation. Elles ne le sont pas pour un parcours à plusieurs étapes, ni quand l'utilisateur doit consulter autre chose en parallèle : cela réclame une route. Une modale empilée sur une modale signale presque toujours un problème de flux en amont. Toute modale conservée ferme sur `Échap`, rend le focus à son déclencheur et porte un titre lié en ARIA.

**Aucun rechargement.** `ajax-no-reload-premium.md` fait autorité : `window.location.reload()`, un formulaire sans `@submit.prevent`, un `redirect()->back()` après une mutation simple, un bouton « Actualiser » visible — chacun est un `BLOCK`. Les exceptions tolérées existent et se **commentent dans le code**.

**Brouillon récupérable** sur tout parcours à étapes : un utilisateur interrompu ne doit rien perdre.

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
- `.claude/rules/ajax-no-reload-premium.md` · `.claude/rules/premium-redesign.md` · `.claude/rules/interactive-guides.md` — axes 9 et 11
- `/klassci-test-e2e` — la preuve d'exécution exigée par l'axe 9
- `/klassci-user-tutorial` — la règle d'or de la capture réelle, et le test « une secrétaire sait-elle quoi faire »
- `critique-transversale` (agent) — le second angle sur un parcours entier
- `/code-review` et `/simplify` — les deux autres revues, qui posent d'autres questions

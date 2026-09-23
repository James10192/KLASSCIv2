---
name: thermo-review
description: Revue adverse avec verdict bloquant sur un diff KLASSCI. Cherche d'abord le coup de judo — la reformulation qui supprime des branches entières au lieu de les ranger. Puis quinze axes en quatre familles : ce que le code fait de faux (code jamais lu, seconde source de vérité, valeur en dur, repli silencieux, état à moitié écrit), ce qu'il coûte à lire (fichier qui enfle, branche greffée, emballage vide), ce que le produit vaut (pertinence sourcée, design premium prouvé par capture, répartition du travail à la source, fluidité — liens profonds, modales, aucun rechargement), et ce qui passe sans être relu (commentaire, message de commit, mémoire). Obligatoire avant toute fusion et tout déploiement ; sur une branche de travail, le commit n'attend pas le verdict, mais la fusion ne part pas sans lui ; la capture d'écran, elle, se prend sur presentation après la fusion et conditionne la propagation vers les écoles (rule pre-merge-checklist, commandement 0). Use before committing, merging or deploying any change.
---

# Revue thermo-nucléaire

Adaptée pour KLASSCI du skill `thermo-nuclear-code-quality-review` de
[`cursor/plugins`](https://github.com/cursor/plugins/blob/main/cursor-team-kit/skills/thermo-nuclear-code-quality-review/SKILL.md).
L'original vise la qualité d'implémentation en TypeScript ; cette version garde son
ambition et sa barre d'approbation, transpose ses seuils à un Laravel multi-instance,
et y ajoute ce que l'original ne couvre pas : la pertinence de ce qu'on construit,
l'ergonomie de ce qu'on livre, et ce qui échappe à toute relecture.

## Ce que cette revue cherche — et ce qu'elle ne cherche pas

Trois revues coexistent dans ce dépôt. Elles ne font pas le même travail, et les confondre revient à n'en faire aucune.

| Revue | Question posée | Sortie |
|---|---|---|
| `/code-review` | **Est-ce que ça marche ?** Bugs, sécurité, tests, performance | Liste de constats |
| Audit 4 axes (`pre-commit-quality-gate`) | **Est-ce que ça casse autre chose ?** Régressions, multi-instance, SOLID | Liste de risques |
| **Thermo-nucléaire** | **Fallait-il l'écrire — et fallait-il le faire ainsi ?** | **Verdict `PASS` / `PASS — capture en attente` / `BLOCK`** |

La thermo **ne remplace pas** l'audit 4 axes, elle s'y ajoute. L'audit cherche les régressions ; la thermo cherche ce qu'on aurait pu supprimer.

> **`pre-commit-quality-gate.md` n'est pas dans ce dépôt.** C'est une rule globale du
> poste (`~/.claude/rules/`), citée par cinq fichiers d'ici et lisible par aucun agent
> qui travaille en conteneur. Les quatre axes tiennent en une ligne chacun —
> architecture, dette assumée, tenue en production multi-instance, SOLID — et c'est
> à cela qu'il faut se référer, pas au fichier. Le signaler plutôt que de laisser
> croire qu'on l'a lu : c'est l'axe 15 appliqué à ce skill.

> Le défaut le plus coûteux de ce dépôt n'a jamais été un plantage. C'est du code qui s'exécute, qui ne lève rien, et qui sert une valeur fausse ou ne sert à personne. C'est cela qu'on traque ici.

## L'ambition, avant tout le reste : le coup de judo

C'est l'axe **0**, et il prime sur les quinze autres. Ne t'arrête jamais à « ça
pourrait être un peu plus propre ».

> Cherche la reformulation qui fait **disparaître** des branches, des modes, des
> auxiliaires, des couches entières — plutôt que celle qui les range mieux.

Un refactor qui déplace la complexité sans en réduire la quantité de concepts que
le lecteur doit tenir en tête n'a rien apporté. Le repère : **la bonne solution
paraît évidente après coup.** Si une réorganisation utilise mieux l'architecture
existante et rend le changement radicalement plus simple, exige-la — même si elle
demande de toucher du code au-delà du diff.

Sur ce dépôt, le coup de judo prend presque toujours l'une de ces formes :

- **Ça existe déjà.** La proposition redécrit quelque chose de construit, parfois
  déployé et simplement invisible. `grep` avant d'écrire une ligne.
- **Un type suffisait.** `OfficialDocument.document_type` est une chaîne libre :
  ajouter un type de document n'appelle ni migration, ni agrégat, ni service.
- **Le besoin réel était plus petit.** Reformule ce que la proposition cherche à
  obtenir **sans reprendre le vocabulaire de la solution proposée**. Exemple vécu :
  « il faut une IA qui délibère » → le besoin était *voir la motivation de chaque
  décision*, et le code la calculait déjà avant de la jeter.
- **Le helper canonique existe.** `MatiereTreeBuilder`, `ExportRenderer`,
  `PermissionRegistry`, `SettingsHelper`, `EntityLabelHelper`, `PhoneFormatter`.
  Un auxiliaire fait sur mesure à côté de l'un d'eux est un `BLOCK`.

**Une complexité qu'on garde alors qu'un coup de judo visible la supprimerait est
un bloquant présumé**, pas une remarque.

## Quand elle est obligatoire

Avant **toute fusion et tout déploiement** qui touche du code — c'est le commandement 0
de `.claude/rules/pre-merge-checklist.md`.

**Sur une branche de travail, le commit n'attend pas le verdict.** La revue est longue,
et les garde-fous de session exigent un arbre propre à chaque fin de tour : faire porter
le blocage sur le commit mettait ces deux exigences en contradiction. On commit, on dit
que la revue tourne, et **le verdict s'applique dans un commit de suite, avant la
fusion**. Ce qui reste interdit : fusionner ou déployer sans `PASS`, et laisser un
`BLOCK` sans correction.

**La capture d'écran ne bloque pas la fusion dans `presentation`.** Elle ne se prend
que sur un tenant déployé, et `presentation` est ce tenant-là : l'exiger avant d'y
fusionner rendait la fusion impossible. Quand **le seul** manque est une preuve
visuelle (axes 10 et 12), le verdict est `PASS — capture en attente` : fusion et
déploiement sur presentation autorisés, **propagation vers les écoles interdite**
tant que la capture n'est pas jointe. Voir le commandement 0 de
`.claude/rules/pre-merge-checklist.md`.

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
> Ta question n'est pas « est-ce que ça marche ». C'est **« fallait-il l'écrire, et fallait-il le faire ainsi ? »**. Lis en entier `.claude/skills/thermo-review/SKILL.md` avant de commencer — **les quatre parties**, et l'axe 0.
>
> **Commence par l'axe 0 — le coup de judo.** Cherche la reformulation qui supprime des branches entières, pas celle qui les range. Puis seulement les quinze axes.
>
> Les rules de `.claude/rules/` font autorité. Toute violation est un `BLOCK`.
>
> **Tu as le droit et le devoir d'utiliser :**
> - **la recherche internet** — axe 9 : toute affirmation sur le monde extérieur que le diff encode doit porter sa source. Vérifie-la toi-même plutôt que de la croire.
> - **la mémoire projet et les rules** — une question déjà tranchée ne se retranche pas ; une décision déjà écrite se cite.
> - **l'agent `critique-transversale`** si le diff touche un parcours utilisateur entier et que tu veux un second angle.
>
> Tu ne peux pas exécuter l'application. Pour les axes 10 et 12, **exige la preuve plutôt que de la produire** : dis précisément quelle capture d'écran ou quelle exécution `/klassci-test-e2e` manque, et sur quel écran. Si c'est **le seul** manque, rends `PASS — capture en attente` plutôt que `BLOCK` : la capture se prend sur presentation après la fusion, et conditionne la propagation vers les écoles, pas la fusion.
>
> **Contraintes :**
> - `fichier:ligne` obligatoire pour chaque constat. Si tu n'as pas lu, ne l'affirme pas.
> - Ne fabrique pas d'objection : un faux positif coûte plus cher qu'un silence, parce qu'il apprend à ignorer les verdicts.
> - **Ne noie pas la revue sous les remarques mineures.** Peu de constats à forte conviction valent mieux qu'une longue liste cosmétique. S'il y a un problème structurel, les nits attendront.
> - Tu as le droit de conclure « cette partie est juste, n'y touchez pas ».
> - Lecture seule. N'écris ni ne modifie aucun fichier.
>
> **Rends un verdict `PASS`, `PASS — capture en attente` ou `BLOCK`**, puis les constats classés, chacun avec sa correction proposée.

### Si le sous-agent est indisponible

Contexte saturé, outil refusé, quota. Alors : **dis-le clairement** et fais la revue toi-même contre les mêmes standards. La sauter en silence n'est jamais acceptable — et c'est exactement ce que cette règle existe pour empêcher.

---

## Partie A — Ce que le code fait de faux

### 1. Ce qui est écrit et jamais lu

Le défaut signature de ce dépôt. Pour chaque champ, colonne, table, réglage ou valeur de retour **ajouté** par le diff, prouve qu'il a un lecteur.

```bash
grep -rn "nom_du_champ" app/ resources/ routes/ config/
php artisan route:list --path=<chemin>      # le contrôleur édité est-il celui qui sert ?
```

Un seul résultat — celui qui l'écrit — est un `BLOCK`. Cas réels : une motivation de décision de jury calculée puis jetée ; une colonne de crédit de pivot alimentée par l'import et lue par aucun calcul ; un registre de crédits rempli à chaque publication de bulletin dont le seul lecteur est un contrôleur non routé.

Même axe, autre face : **le code non atteint**. Une route sans permission, une permission au registre sans `can()`, un enum sans référence, un contrôleur qui n'est pas celui que la route sert. Le piège n°1 de `klassci-debugging-discipline.md` — `ESBTPEtudiantController` contre `ESBTPStudentController` — a coûté trois heures et se détecte en trente secondes.

### 2. La seconde source de vérité

Le diff introduit-il un second endroit qui répond à une question déjà répondue ailleurs ? Deux calculs de la même moyenne, deux façons de savoir qui est diplômé, deux moteurs de décision.

Deux sources ne se contredisent pas tout de suite. Elles divergent, et personne ne les compare.

Cas voisin, tout aussi bloquant : **un auxiliaire fait sur mesure quand le canonique existe.** Avant d'accepter une nouvelle classe, demande : *qu'est-ce qui, dans le dépôt, faisait déjà ça ?* Et la logique est-elle dans **la bonne couche** — le domaine plutôt que le contrôleur, le service canonique plutôt qu'une méthode privée dupliquée ?

### 3. La valeur en dur

`.claude/rules/rien-en-dur.md` fait autorité. Le test : **deux écoles peuvent-elles légitimement vouloir une valeur différente ici ?** Si oui, ça se configure.

```bash
grep -rnE '[><=]=?\s*[0-9]{4,}' --include="*.php" app/ | grep -vE 'max:|min:|migrations|Test\.php'
grep -rn "hasRole(" --include="*.php" app/ | grep -vE "superAdmin|serviceTechnique"
```

**Le zéro confondu avec l'absence** en est le cas le plus fréquent, et mérite son propre passage :

```bash
grep -nE '\?:\s|\?\?\s*[0-9]|> 0\)|!= 0|empty\(' <fichiers du diff>
```

Chaque `?:`, chaque `> 0` en filtre, chaque `empty()` sur un nombre : **le code veut-il dire « absent » ou « nul » ?** Un montant nul est une valeur ; une moyenne de 0,00 est une note ; un total de crédits nul n'est pas un défaut de 30.

### 4. Le repli silencieux

Un `catch` qui vide une collection, un `?? valeur_par_défaut` sur une configuration introuvable, un `continue` qui saute une ligne.

**Un rattrapage qui dégrade l'affichage doit toujours journaliser ce qu'il a rattrapé.** Sinon on ne cherche même pas. Un repli muet est un `BLOCK`.

Sa version typée est tout aussi coûteuse : un `mixed`, un tableau associatif informe là où un DTO ou un enum dirait la vraie forme, une optionnalité ajoutée pour éviter de trancher. **Quand une branche repose sur un repli pour masquer un invariant flou, la question n'est pas comment mieux replier : c'est de rendre la frontière explicite.**

### 5. L'état à moitié écrit

Une mutation qui peut laisser deux tables en désaccord. Ce dépôt en fait une règle
partout — « sync atomique requise » pour la clôture, le PV de jury, la
réconciliation — parce qu'un état partiel ne lève rien et se découvre des semaines
plus tard.

Demande : si le processus s'interrompt **entre** ces deux écritures, que reste-t-il
en base, et quelqu'un s'en apercevra-t-il ? S'il existe une structure plus atomique
évidente (transaction, événement après commit, une seule écriture), exige-la.

Symétriquement : un enchaînement séquentiel de travaux **indépendants** qui pourrait
être plus simple en parallèle est un signal de conception — sans sur-optimiser, mais
sans normaliser une orchestration inutilement fragile.

---

## Partie B — Ce que le code coûte à lire

### 6. Le fichier qui enfle

L'original pose la règle en **différentiel**, et c'est ce qui la rend applicable :
un diff ne doit pas faire passer un fichier de sous 1000 lignes à au-dessus sans
raison forte. Ce dépôt compte **90 fichiers PHP au-delà de 1000 lignes** — 55 vues
Blade et 35 fichiers de code, dont 30 sous `app/` :

```bash
git ls-files -z '*.php' | while IFS= read -r -d '' f; do
  [ -f "$f" ] && [ "$(wc -l < "$f")" -gt 1000 ] && echo "$(wc -l < "$f") $f"
done | sort -rn | head
```

En tête : `app/Services/BulletinService.php` (3969),
`ESBTPResultatController.php` (3343), `app/Services/NotificationService.php` (3056),
`ESBTPInscriptionController.php` (2869), `ESBTPBulletinController.php` (2526).
Un seuil absolu serait donc mort à l'arrivée ; le seuil différentiel, lui, tient :

| Situation | Verdict |
|---|---|
| Le diff fait passer un fichier **sous** 1000 lignes à **au-dessus** | `BLOCK` — décomposer d'abord |
| Le fichier était **déjà** au-dessus et le diff l'agrandit | `BLOCK` — le nouveau code va dans un service ou une action |
| Le fichier était au-dessus et le diff le **réduit** | c'est le bon sens de la marche |

**Le seuil qui mord vraiment est celui de la méthode, pas du fichier.** Découper un
contrôleur de 2500 lignes change la liaison de routes sur huit instances en
production : c'est un chantier, pas une remarque de revue. Extraire une méthode
privée ne coûte rien et se vérifie à l'œil. Donc :

> **Une méthode que ce diff crée ou allonge au-delà de 80 lignes est un bloquant,
> quelle que soit la taille du fichier.** Le remède est gratuit : une méthode privée
> nommée, ou une action dédiée.

Ordres de grandeur relevés sur la branche, pour situer : `BulletinService::buildDonneesBulletin()`
552 lignes, `ESBTPBulletinController::buildBulletinPdf()` 465,
`ESBTPPaiementController::store()` 244. Ces méthodes-là sont de la dette connue ;
la règle vise ce que **ce diff** ajoute.

**En comptabilité, le seuil est plus strict et c'est lui qui s'applique** :
`.claude/rules/no-god-code-compta.md` pose méthode de contrôleur > 40 lignes,
contrôleur > 200 lignes, service ou action > 250 lignes. Sur
`app/Domain/Comptabilite/**`, `ESBTPPaiementController` et
`ESBTPComptabilite*Controller`, c'est 40, pas 80. Partout ailleurs, 80.
Un seul seuil s'applique à un fichier donné — ne les cite jamais tous les deux
dans le même constat.

Le remède n'est jamais « ranger » : c'est extraire vers
`app/Domain/<Domaine>/<SousDomaine>/{Actions,Services,DTOs,Events}`.

```bash
# Fichiers touchés, par taille
git diff origin/presentation...HEAD --numstat | awk '$1>0 {print $3}' | while read f; do
  [ -f "$f" ] && echo "$(wc -l < "$f") $f"
done | sort -rn | head

# Méthodes PHP de plus de 80 lignes dans les fichiers touchés
git diff origin/presentation...HEAD --name-only --diff-filter=ACM | grep '\.php$' | while read f; do
  [ -f "$f" ] && awk -v f="$f" '
    /^    (public|private|protected).*function /  { if (nom && FNR-debut > 80) print f ":" debut "  " nom " (" FNR-debut " lignes)"; nom=$0; debut=FNR }
    END { if (nom && FNR-debut > 80) print f ":" debut "  " nom " (" FNR-debut " lignes)" }
  ' "$f"
done
```

### 7. La branche greffée

Une condition ponctuelle insérée au milieu d'un flux qui ne la concernait pas. Un
booléen en paramètre qui fait de la fonction deux fonctions. Un mode nullable. Un
cas particulier traité en plein cœur d'une méthode déjà chargée.

**Ce n'est pas une remarque de style, c'est un défaut de conception.** Le test :
après ce diff, le flux existant est-il plus difficile à parcourir des yeux qu'avant ?
Si oui, la logique doit vivre derrière sa propre abstraction — une action, une
politique, un type — plutôt que tordre un chemin partagé.

Cas particulier nommé par les rules : le paramètre booléen. `MatiereTreeBuilder`
expose `buildForPlanning()` et `buildWithVolumeBudget()`, **deux méthodes
publiques distinctes**, précisément parce qu'un `bool $includeVolumeBudget` aurait
été oublié par le premier appelant venu (`lmd-bts-matieres-single-source.md`).

Et son inverse exact, tout aussi bloquant : **unifier ce qui doit rester séparé.**
BTS et LMD sont deux systèmes distincts ; les fondre au nom du DRY est interdit
(`lmd-bts-bulletin-separation.md`). Deux codes simples valent mieux qu'un code
complexe.

### 8. L'emballage qui n'emballe rien

Un service qui enveloppe un service qui enveloppe une requête. Une classe dont
toutes les méthodes délèguent. Une abstraction écrite pour deux cas dont le second
est hypothétique. Une indirection qui n'achète aucune clarté.

Demande sur chaque couche ajoutée : **qu'est-ce qu'un lecteur comprend mieux grâce
à elle ?** Si la réponse est « rien, mais c'est plus propre », le remède est de la
supprimer, pas de la polir.

---

## Partie C — Ce que le produit vaut

Un diff peut être irréprochable en code et livrer la mauvaise chose, ou la bonne chose sous une forme que personne ne voudra utiliser. Ces quatre axes gouvernent le **même verdict** que les précédents.

### 9. Pertinence — est-ce seulement vrai ?

KLASSCI est adossé à des réalités extérieures : textes UEMOA et CAMES, droit national, procédures d'un ministère, pratique réelle d'une école. **Une décision de conception qui repose sur une affirmation sur le monde extérieur doit porter sa source.**

Ce n'est pas théorique. En une seule session d'audit, trois affirmations qui semblaient solides se sont révélées fausses ou périmées :

- « la migration téléphonique béninoise de 2020 » — c'était **novembre 2024**, et la règle réelle rendait le défaut bien plus large ;
- un décret cité comme fondement en vigueur avait été **déclaré contraire à la Constitution** sept ans plus tôt ;
- « homologation » et « accréditation » étaient employées comme synonymes — deux autorités, deux conséquences.

**Le contrôle :** pour chaque affirmation extérieure que le diff encode — un seuil réglementaire, un format de document, une obligation légale, une pratique métier — l'auteur peut-il produire la source ? Sinon, deux issues seulement : aller la chercher, ou **marquer l'hypothèse comme non vérifiée dans le code et dans l'issue**. Une supposition assumée est acceptable ; une supposition déguisée en fait ne l'est pas.

Corollaire : une recherche qui n'aboutit pas se dit. « Je n'ai pas pu vérifier le texte officiel » vaut infiniment mieux qu'un silence qui laissera croire que c'était vérifié.

### 10. Design premium et ergonomie — preuve, pas déclaration

Une page n'est pas premium parce qu'on l'affirme. Elle l'est quand on la voit.

**La preuve exigée est une capture d'écran réelle du parcours livré**, prise sur un tenant — en pratique presentation, après la fusion et avant toute propagation vers une école — jamais une maquette redessinée — c'est la règle d'or de `/klassci-user-tutorial`, et elle vaut ici : *une capture réelle mal cadrée vaut mieux qu'une belle maquette fausse*. Pour un parcours qui traverse données et écrans, la preuve est une exécution `/klassci-test-e2e` sur le tenant, avec le chemin réel qui plantait.

Grille, tirée de `premium-redesign.md` et `premium-selects.md` :

- hero copié du patron `planning-header`, pas réinventé — et réservé aux pages de liste ou de tableau de bord ;
- palette monochrome bleu ; couleurs sémantiques **seulement** quand elles portent un statut à capter en moins d'une seconde ;
- namespace CSS dédié, pas de fuite dans les classes globales ;
- aucun `<select>` natif visible ; aucun menu déroulant tronqué par un parent ;
- utilisable à 400 px de large, sans défilement horizontal ;
- états vides qui **proposent l'action** au lieu de constater l'absence ;
- boutons Guide et Aide dès que l'écran cumule filtres, indicateurs et actions (`interactive-guides.md`).

**Le test qui tranche :** une secrétaire qui n'a jamais vu cet écran sait-elle quoi faire en dix secondes ? Si la réponse exige une formation, le défaut est dans l'écran, pas dans la secrétaire.

### 11. La répartition du travail — le plus important, et le plus oublié

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

### 12. Fluidité — liens profonds, modales, absence de rechargement

**Liens profonds.** Le test tient en une phrase : **puis-je envoyer à un collègue l'écran exact que je regarde ?** Tout état filtré, trié, paginé doit vivre dans l'URL, et l'écran cible doit pré-remplir ses filtres depuis la chaîne de requête (`premium-redesign.md`, section « Pré-remplir un filtre UI depuis query string »). Un bouton qui mène vers une page cible **sans emporter son contexte** est un défaut.

**Modales.** Elles sont légitimes pour une décision unitaire — un champ, une confirmation. Elles ne le sont pas pour un parcours à plusieurs étapes, ni quand l'utilisateur doit consulter autre chose en parallèle : cela réclame une route. Une modale empilée sur une modale signale presque toujours un problème de flux en amont. Toute modale conservée ferme sur `Échap`, rend le focus à son déclencheur et porte un titre lié en ARIA.

**Aucun rechargement.** `ajax-no-reload-premium.md` fait autorité : `window.location.reload()`, un formulaire sans `@submit.prevent`, un `redirect()->back()` après une mutation simple, un bouton « Actualiser » visible — chacun est un `BLOCK`. Les exceptions tolérées existent et se **commentent dans le code**.

**Brouillon récupérable** sur tout parcours à étapes : un utilisateur interrompu ne doit rien perdre.

---

## Partie D — Ce qui passe sans être relu

Trois surfaces échappent à toute relecture : personne ne teste un commentaire,
personne ne relit un message de commit, personne ne conteste une mémoire. Ce sont
exactement les endroits où une fausseté s'installe et vieillit.

### 13. Le commentaire

**Un commentaire qui ment est pire que pas de commentaire** — il est cru, et le
lecteur ne va pas vérifier. La recherche sur les incohérences code‑commentaire le
mesure : les commentaires sont rarement mis à jour quand le code change, et une
incohérence code‑commentaire est associée à un risque accru d'introduire un bug
([Wen et al., ICPC 2019](https://dl.acm.org/doi/abs/10.1109/ICPC.2019.00019) ·
[Radmanesh, Imani, Ahmed & Moshirpour, 2024](https://arxiv.org/abs/2409.10781)).

Trois contrôles sur les commentaires **ajoutés ou voisins d'une ligne modifiée** :

1. **Est-il encore vrai après ce diff ?** Un commentaire qui décrit le comportement
   d'avant est un `BLOCK`. Le diff a changé le code ; il devait changer le
   commentaire.
2. **Dit-il *pourquoi*, ou répète-t-il *quoi* ?** Un commentaire qui paraphrase la
   ligne suivante est du bruit ; celui qui donne la raison, la contrainte, l'incident
   d'origine est ce qui manque toujours.
3. **Affirme-t-il quelque chose d'invérifiable ?** « conforme à la norme »,
   « obligatoire », « le ministère exige » — c'est l'axe 9 dans un commentaire, et
   il porte sa source ou il est marqué non vérifié.

**Et le piège propre à ce dépôt** : dans un `.blade.php`, un commentaire n'est pas
inerte. Blade scanne le fichier entier, commentaires JS et CSS compris. Un `@can`
dans un `//`, un `<x-composant>` dans un `/* */` : **500 en production, invisible à
la compilation**. Deux incidents datés, mai 2026 (`blade-pitfalls.md`, pièges 2 et 3).

**Ne le cherche pas à la main : `.githooks/pre-commit` le refuse au commit**, et sait
auditer tout le dépôt (`sh .githooks/pre-commit --arbre`). Un contrôle qu'un hook
applique ne doit pas consommer en plus l'attention d'un relecteur. Ce qui reste à
toi ici, c'est le **fond** du commentaire — les trois questions ci-dessus.

La seule chose à vérifier côté Blade : que le diff **n'a pas contourné** le hook
(`--no-verify`). Aucun contrôle serveur ne rejoue ces règles.

### 14. Le message de commit

Les garde-fous du dépôt (`.githooks/commit-msg`, `hygiene-commits.yml`) attrapent la
**forme** : signature d'outil, message non conventionnel, `feat`/`fix` sans entrée
au `CHANGELOG`. Ils ne peuvent rien contre le **fond**. Or un bon message porte deux
choses — **ce qui change** et **pourquoi** — et la mesure empirique est sévère :
environ 44 % des messages sont de faible qualité sur cinq projets ouverts étudiés
([Tian et al., 2022](https://arxiv.org/pdf/2202.02974)), et les messages de faible
qualité précèdent plus souvent les changements qui introduisent des bugs
([Li & Ahmed, *Commit Message Matters*, ICSE 2023](https://ieeexplore.ieee.org/document/10172825/)).

**La liste des défauts de rédaction vit dans `/commit`** — c'est là qu'on écrit un
message, pas ici. Ne la recopie pas : ce serait exactement l'axe 2.

En **revue**, une seule question se pose, et elle ne peut se poser que là, parce
qu'elle confronte deux objets que seul le relecteur a sous les yeux :

> **Le message affirme-t-il quelque chose que le diff ne fait pas ?**

« corrige X » alors que X reste, « ajoute un garde-fou » alors que le garde-fou ne
se déclenche jamais. C'est la forme la plus coûteuse : elle ferme l'enquête future.
Quelqu'un cherchera la cause de X ailleurs, en s'appuyant sur ce message. `BLOCK`.

Corollaire, même geste : **ce qui est mis en index a-t-il été relu ?** `git add -A`
embarque ce que personne n'a regardé — résidus d'agent, fichier de travail, secret
(`multi-agent-git-safety.md`).

```bash
git log origin/presentation..HEAD --format='%h %s%n%b'      # relis tes propres messages
git diff --cached --stat                                     # et ce que tu t'apprêtes à figer
```

### 15. La mémoire

Une mémoire de projet est **crue sans être vérifiée** — c'est tout son intérêt, et
tout son danger. Une mémoire fausse est pire qu'une mémoire absente : elle oriente
une décision future sans que personne ne remonte à la source. La littérature
sécurité nomme désormais le cas extrême (l'OWASP a ajouté *Memory and Context
Poisoning* à son top 10 des risques agentiques en 2026), mais dans ce dépôt le
défaut ordinaire est plus banal et plus fréquent : **la mémoire périmée**, qui
devient un distracteur au lieu d'un repère.

`.claude/rules/memory-updates.md` pose déjà la discipline — vérifier avant d'agir
sur une mémoire qui nomme un fichier, supprimer plutôt que laisser pourrir. **Rien
ne le vérifie.** C'est ce que cet axe ajoute.

Quand le diff s'accompagne d'une écriture en mémoire, ou quand il **invalide** une
mémoire existante :

1. **Une mémoire qui nomme un fichier, une méthode, une permission a-t-elle été
   revérifiée ?** Si le diff renomme ou supprime ce qu'une mémoire cite, la mémoire
   se corrige **dans le même geste**. Sinon elle ment dès le lendemain.
2. **Est-ce une préférence, ou un instantané ?** Une préférence exprimée par
   l'utilisateur est durable et se garde. Un instantané d'architecture périme au
   premier refactor — préfère relire le code ou `git log`.
3. **La raison est-elle écrite ?** Une décision sans son *pourquoi* ne se rediscute
   pas, elle se subit. `**Why:**` et `**How to apply:**` ne sont pas décoratifs.
4. **Aucun secret.** Jamais un jeton, un mot de passe, une clé.
5. **Est-elle indexée ?** Une mémoire absente de `MEMORY.md` est invisible aux
   sessions futures : le travail d'écriture est perdu.

Note d'environnement : `memory-updates.md` donne un chemin Windows
(`C:\Users\PAVILION\.claude\...`). Depuis un conteneur Linux, il est **inatteignable**.
Si tu ne peux pas lire la mémoire, dis-le — c'est un résultat, pas un échec — et ne
prétends pas l'avoir vérifiée.

---

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
| Les **quatre pièges Blade silencieux** | **déjà refusés par `.githooks/pre-commit`** — ne les cherche pas ici. Vérifie seulement qu'ils n'ont pas été contournés par `--no-verify` : aucun contrôle serveur ne les rejoue |
| `:style` Alpine sur un élément portant déjà `style=` | l'un écrase l'autre |
| `transform` au survol d'un parent de menu déroulant | rompt le bloc conteneur (`universal-dropdowns.md`) |
| Méthode de contrôleur nommée `validate` / `authorize` / `failed` | signature réservée Laravel — 500 au boot (`controller-naming.md`) |
| Parcours à plusieurs étapes enfermé dans une modale | lui donner une route |
| Rôle inventé plutôt qu'une permission | `.claude/rules/customizable-roles.md` |
| Nom d'établissement, indicatif, fuseau en dur | réglage d'instance, défaut inchangé |

## Le ton

Direct, sérieux, exigeant. Jamais brutal — mais **ne transforme pas un problème de
maintenabilité majeur en suggestion polie.** Si le diff rend le dépôt plus
désordonné, dis-le en ces termes. S'il a manqué une simplification décisive,
dis-le aussi.

Quelques formulations qui portent :

- « ce diff fait passer ce fichier au-delà de 1000 lignes — on décompose d'abord ? »
- « ça ajoute un cas particulier dans un flux déjà chargé — on le met derrière sa propre abstraction ? »
- « ça marche, mais ça rend le code autour plus emmêlé. Gardons le comportement, restructurons l'implémentation. »
- « il y a un coup de judo ici : reformulé ainsi, ces trois branches disparaissent. »
- « ce refactor déplace la complexité sans la supprimer. Le modèle lui-même peut-il être plus simple ? »
- « cet auxiliaire existe déjà — `MatiereTreeBuilder`. On réutilise le canonique ? »
- « pourquoi ce repli ici ? Rendons plutôt la frontière explicite. »

## Le verdict

```
VERDICT : PASS | PASS — capture en attente | BLOCK
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

**Ordre de priorité** — et il est contraignant :

1. régression structurelle ;
2. coup de judo manqué — la simplification décisive qui était visible ;
3. croissance en branches, flux emmêlé ;
4. frontières, types, contrats qui rendent le code plus dur à suivre ;
5. taille de fichier et décomposition ;
6. pertinence, ergonomie, répartition du travail, fluidité ;
7. ce qui passe sans être relu — commentaire, commit, mémoire ;
8. lisibilité et maintenabilité de détail.

**Ne noie pas la revue sous les remarques mineures quand il existe un problème
structurel.** Peu de constats à forte conviction valent mieux qu'une longue liste
cosmétique — une revue qu'on n'a pas le courage de lire ne change rien.

> **Au-delà de cinq bloquants, la revue est probablement mal cadrée.** Soit le diff
> est trop large pour être relu d'un bloc — dis-le et demande qu'il soit scindé —
> soit un seul défaut de conception produit les autres : nomme-le, et range le reste
> dessous comme conséquences. Cinq bloquants qu'on corrige valent mieux que quinze
> qu'on ignore.

La dernière rubrique n'est pas une politesse : elle évite qu'on « améliore » au prochain passage ce qui avait été pesé.

## La barre d'approbation

Ne rends pas `PASS` au motif que le comportement semble correct. La barre est :

- aucune régression structurelle ;
- aucune simplification décisive manquée alors qu'elle était visible ;
- aucune enflure de fichier injustifiée ;
- aucune branche greffée dans un flux qui ne la concernait pas ;
- aucun emballage, `mixed` ou optionnalité qui rende la conception plus indirecte ;
- aucune seconde source de vérité, aucun auxiliaire doublant un helper canonique ;
- aucune logique posée dans la mauvaise couche ;
- aucun état à moitié écrit là où une structure atomique était évidente ;
- aucune affirmation extérieure sans source ni marquage ;
- aucun écran affirmé premium sans capture réelle — au plus tard avant la propagation vers les écoles ;
- aucun commentaire, message de commit ou mémoire qui dise quelque chose de faux.

**Bloquants présumés** — à corriger, sauf justification explicite de l'auteur :

- le diff garde une complexité qu'un coup de judo visible aurait supprimée ;
- il fait franchir à un fichier la barre des 1000 lignes, ou agrandit un fichier déjà au-delà ;
- il résout un problème local en dispersant des tests de cas particulier dans du code partagé ;
- il duplique un helper canonique, ou pose la logique dans la mauvaise couche ;
- il laisse un commentaire qui décrit le comportement d'avant.

## Après un `BLOCK`

On ne fusionne pas, on ne déploie pas. On corrige, **puis on relance la revue sur le nouveau diff** — pas sur l'ancien. (Sur une branche de travail, commiter la correction fait partie du geste.)

Un bloquant ne se discute pas avec le relecteur : il se corrige, ou il se retire du diff. Si le constat est faux, la réponse est de le prouver par `fichier:ligne`, pas de l'argumenter.

## Voir aussi

- Source d'origine : [`cursor/plugins` — thermo-nuclear-code-quality-review](https://github.com/cursor/plugins/tree/main/cursor-team-kit/skills/thermo-nuclear-code-quality-review)
- `.claude/rules/pre-merge-checklist.md` — commandement 0, qui rend cette revue obligatoire
- `.claude/rules/rien-en-dur.md` — axe 3
- `.claude/rules/no-god-code-compta.md` — axe 6, les seuils de décomposition
- `.claude/rules/klassci-debugging-discipline.md` — axe 1, les treize pièges
- `.claude/rules/blade-pitfalls.md` — axe 13, le commentaire qui casse la production
- `.claude/rules/changelog.md` · `.claude/rules/multi-agent-git-safety.md` — axe 14
- `.claude/rules/memory-updates.md` — axe 15
- `.claude/rules/customizable-roles.md` · `.claude/rules/permissions.md` · `.claude/rules/controller-naming.md`
- `.claude/rules/ajax-no-reload-premium.md` · `.claude/rules/premium-redesign.md` · `.claude/rules/interactive-guides.md` — axes 10 et 12
- `/klassci-test-e2e` — la preuve d'exécution exigée par l'axe 10
- `/klassci-user-tutorial` — la règle d'or de la capture réelle, et le test « une secrétaire sait-elle quoi faire »
- `critique-transversale` (agent) — le second angle sur un parcours entier
- `/code-review` et `/simplify` — les deux autres revues, qui posent d'autres questions

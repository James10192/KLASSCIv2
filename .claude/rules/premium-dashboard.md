# Rule: Dashboard premium — ce n'est pas une page premium

## Quand s'active

Dès que tu crées ou refais un **tableau de bord** : l'accueil d'un rôle
(`resources/views/dashboard/*.blade.php`), un « dashboard » de module
(`/esbtp/comptabilite/dashboard`, analytics, pilotage), ou toute page dont le
rôle est de dire « où en est-on, et que faut-il faire maintenant ».

`premium-redesign.md` reste la base visuelle (palette, hero, namespace, cards).
Cette rule ajoute ce qu'un **tableau de bord** exige en plus. Une page premium
se juge à sa finition ; un tableau de bord se juge à ce qu'on sait et fait
**dans les dix secondes** après l'avoir ouvert.

## Pourquoi cette rule existe

Septembre 2026, ISLG et USAT : les agents rappelaient sans cesse pour la même
chose — « où je vois ce que j'ai encaissé ? », « comment j'annule ? ». L'accueil
de la caisse avait le hero et les cards d'une page premium, mais n'était qu'une
**page d'actions** : quatre compteurs du jour, une liste vide, aucune tendance,
aucune file de travail. Joli, et muet. Marcel : « je veux rendre l'école
indépendante ».

## Les dix exigences

### 1. Chaque chiffre répond à « par rapport à quoi ? »

Un nombre seul ne dit rien. Tout KPI principal porte un **repère** : hier, la
semaine dernière, le mois dernier, l'objectif, ou le total dont il est la part.

```
2 450 000 FCFA   ▲ +18 % vs hier      ✅
2 450 000 FCFA                         ❌
```

Le delta est **sémantique** (vert en hausse d'un encaissement, rouge en hausse
d'un impayé) — c'est l'exception autorisée par `premium-redesign.md`.

### 2. Chaque chiffre est cliquable vers sa liste filtrée

Un compteur « 12 à valider » ouvre la liste des paiements **déjà filtrée** sur
ces 12. Un nombre qui ne mène nulle part oblige l'agent à refaire le filtre à
la main, et c'est là qu'il appelle le support.

### 3. Une file de travail, pas seulement des indicateurs

Le tableau de bord montre **ce qui attend l'utilisateur**, trié par urgence,
chaque ligne avec son action : paiements à valider, saisies encore annulables,
caisse non ouverte ou non clôturée, relances dues. Vide, la file le dit
positivement (« Rien en attente »), avec l'heure du dernier contrôle.

### 4. Une tendance visible

Au moins une série dans le temps : sept derniers jours pour un guichet, six
derniers mois pour un comptable. En SVG inline ou Chart.js, jamais une image,
jamais sans valeur au survol, jamais de couleur décorative (monochrome bleu).

### 5. Le rôle et ses permissions décident du contenu, pas le fichier

Même vue, contenu différent : chaque bloc est derrière son `@can` ou son
réglage d'instance (`caisse.pre_inscription.enabled`…). **Aucun bloc ne
s'affiche pour dire « vous n'avez pas le droit »** — il disparaît. Aucun
bouton ne mène à un 403. Un bloc sans données pour ce rôle n'est pas rendu.

### 6. Les actions sur la donnée se font depuis le tableau de bord

Les dernières opérations listées portent leurs actions (voir, reçu, annuler ma
saisie, annuler par avoir) selon les droits. Renvoyer vers une autre page pour
agir sur une ligne déjà affichée est une étape de trop.

### 7. Pas de chiffre faux par repli silencieux

Un `catch` qui met un KPI à 0 affiche un zéro **qui ment** : l'agent croit
n'avoir rien encaissé. Un bloc en erreur s'affiche « indisponible » et le
rattrapage est journalisé (`klassci-debugging-discipline.md`, piège #12).

### 8. Les montants tiennent sur une ligne

`216 625 000 FCFA` ne se coupe jamais en deux lignes : taille adaptée
(`clamp()`), `white-space: nowrap`, unité en petit à côté. Pas de carte seule
sur sa rangée : la grille se remplit (`repeat(auto-fit, minmax(…))`).

### 9. Rapide, ou différé

Le premier affichage ne dépend que de requêtes agrégées (`SUM`, `COUNT`,
`GROUP BY`) — jamais d'un `->get()` sur l'année. Ce qui coûte cher se charge
après (AJAX, `x-init`) ou se met en cache court (`Cache::remember` 60 s,
driver `file` : pas de `tags`).

### 10. Mobile d'abord, variante conservée

Si la vue a une variante mobile (`m-only-mobile` / `m-only-desktop`), on refait
le bureau **sans supprimer** le mobile. Sur téléphone, la file de travail passe
avant les graphiques.

## Structure de référence

```
HERO (premium-redesign) — titre + salutation + date + période
  └─ KPIs du hero : 3 à 4, chacun avec son repère (exigence 1) et son lien (2)
FILE DE TRAVAIL (3)            | TENDANCE (4)
DERNIÈRES OPÉRATIONS + actions (6)
RÉPARTITION (par mode, par frais…) | ACCÈS RAPIDES (seulement ce que le rôle peut ouvrir)
```

## Anti-patterns à BLOQUER en review

1. ❌ KPI sans repère de comparaison
2. ❌ Compteur non cliquable, ou cliquable vers une liste non filtrée
3. ❌ Tableau de bord sans file de travail (« que dois-je faire ? » sans réponse)
4. ❌ Bouton ou carte qui mène à un 403 pour le rôle qui la voit
5. ❌ Bloc affiché alors que le réglage d'instance le désactive (pré-inscription en caisse chez ISLG)
6. ❌ `catch (\Exception) { $kpi = 0; }` sans journal ni mention « indisponible »
7. ❌ Montant coupé sur deux lignes, carte orpheline sur sa rangée
8. ❌ Couleur ambre/violette décorative, bouton orange « Voir les relances »
9. ❌ `->get()` sur tous les paiements de l'année pour calculer un total
10. ❌ Refonte bureau qui supprime la variante mobile

## Voir aussi

- `premium-redesign.md` — base visuelle (hero, palette, namespace)
- `ajax-no-reload-premium.md` — actions sans rechargement
- `customizable-roles.md` — le contenu suit les permissions, pas le nom du rôle
- `analytics-pitfalls.md` — tendances et fenêtres temporelles configurables

## Avant de commencer

Ce guide s'adresse au **super-administrateur** d'un établissement qui vient d'être provisionné sur KLASSCI. Il décrit l'ordre dans lequel paramétrer la plateforme pour pouvoir démarrer les inscriptions de votre première année universitaire.

Comptez environ **2 à 3 heures** pour un paramétrage complet, à condition d'avoir sous la main :

- la liste de vos filières avec leurs niveaux d'étude,
- la grille des frais de scolarité (ou au moins les frais d'inscription),
- la liste de votre personnel administratif et enseignant avec leurs adresses email,
- éventuellement les comptes parents si vous activez l'accès parental.

:::callout
**Pas de panique en cas d'oubli.** Tout est modifiable a posteriori. Vous pouvez très bien démarrer avec une seule filière et un seul frais, puis enrichir au fil des semaines.
:::

## Étape 1 : créer l'année universitaire courante

L'année universitaire est la fondation de toute la plateforme : inscriptions, paiements, bulletins et résultats sont rattachés à une année donnée.

Rendez-vous dans `Paramètres → Configuration → Année universitaire` et créez l'année courante en remplissant :

- **Libellé** : `2025-2026` (ou la convention que vous utilisez en interne)
- **Date de début** : 1er septembre par défaut
- **Date de fin** : 31 juillet par défaut
- **Année courante** : cochez `is_current = true`

Une seule année peut être marquée comme courante à la fois. C'est elle qui apparaît par défaut dans tous les écrans de saisie.

### À savoir sur les inscriptions et l'année

Dans KLASSCI, **une classe n'est PAS attachée à une année universitaire** — c'est l'inscription qui l'est. Cela signifie qu'une même classe (ex. : `BTS Bâtiment 1ère année`) peut accueillir des étudiants de plusieurs années universitaires successives sans devoir être recréée.

Concrètement, lorsque vous filtrez les étudiants par classe, ce qui les sépare entre 2024-2025 et 2025-2026, c'est la `annee_universitaire_id` de leur inscription, pas la classe elle-même.

## Étape 2 : configurer les filières

Allez dans `Académique → Filières → Nouvelle filière`. Pour chaque filière, renseignez :

- **Nom** : `Génie Civil`, `Bâtiment`, `Informatique de Gestion`, etc.
- **Code** : un code court unique (`GC`, `BAT`, `IG`)
- **Système académique** : `BTS` ou `LMD`
- **Tronc commun** : cochez si la filière commence par un semestre commun avant spécialisation

Si votre établissement propose à la fois des BTS et des Licences/Masters, créez deux filières distinctes (`BTS Génie Civil` et `Licence Génie Civil`) — les bulletins et calculs de moyenne diffèrent profondément entre les deux systèmes.

### Cas particulier : tronc commun

Si vous activez le tronc commun sur une filière, vous devrez préciser :

- combien de semestres en tronc commun (généralement 1)
- les filières de spécialisation cibles (les filières « enfants » de ce tronc commun)

Le module tronc commun déclenche un workflow d'inscription en deux temps : l'étudiant s'inscrit d'abord en tronc commun, puis en spécialisation à l'issue du semestre commun. Le bulletin de la classe d'origine est conservé et la moyenne annuelle agrège les notes du tronc commun et de la spécialisation.

## Étape 3 : créer les niveaux d'étude

Dans `Académique → Niveaux`, créez les niveaux que votre établissement propose. Quelques exemples :

| Système | Niveau | Cycle |
| --- | --- | --- |
| BTS | BTS1 | Cycle court |
| BTS | BTS2 | Cycle court |
| LMD | L1 | Licence |
| LMD | L2 | Licence |
| LMD | L3 | Licence |
| LMD | M1 | Master |
| LMD | M2 | Master |

Chaque niveau possède un **cycle** parent (BTS, Licence, Master, Doctorat) qui sert de filtre dans les rapports et les bulletins.

## Étape 4 : créer les classes

Une classe est l'intersection **filière × niveau d'étude** dans laquelle vos étudiants seront inscrits. Allez dans `Académique → Classes → Nouvelle classe` :

- **Nom** : `BTS Bâtiment 1ère année`, `L2 Génie Civil`, etc.
- **Filière** : sélectionner dans la liste créée à l'étape 2
- **Niveau d'étude** : sélectionner dans la liste créée à l'étape 3
- **Système académique** : déduit automatiquement de la filière (BTS ou LMD)
- **Places totales** : la capacité maximale d'étudiants — sert au blocage automatique sur les inscriptions

:::callout
**Astuce.** Le seuil de blocage par défaut est 30 % de places restantes (vert), 10–30 % (orange), <10 % (orange foncé), 0 (rouge complet). Ces seuils sont configurables dans `Paramètres → Inscriptions → Capacité des classes`.
:::

## Étape 5 : configurer les matières

### Pour les filières BTS

Allez dans `Académique → Matières → Nouvelle matière`. Pour chaque matière, indiquez :

- **Nom** : `Mathématiques appliquées`, `Topographie`, etc.
- **Coefficient par défaut** : utilisé par les bulletins si la planification académique ne précise pas autrement

Les matières BTS sont **rattachées aux classes** via les **planifications académiques**. Cette mécanique vous permet de définir des coefficients différents pour la même matière selon la filière et le niveau (par exemple, les Mathématiques peuvent avoir un coefficient 3 en Génie Civil et 4 en Topographie).

Concrètement, allez dans `Planning Général → Configuration` et définissez les triplets `(filière, niveau, matière)` avec leur coefficient et volume horaire (CM / TD / TP).

### Pour les filières LMD

Le LMD utilise une hiérarchie en deux niveaux :

1. **UE (Unité d'Enseignement)** — un regroupement thématique avec un nombre total de crédits ECTS (généralement entre 6 et 12).
2. **ECUE** — les matières concrètes qui composent l'UE. Une ECUE peut appartenir à plusieurs UE avec des coefficients contextuels différents (relation many-to-many).

Créez d'abord les UE (`Académique → LMD → UE → Nouvelle UE`) puis les ECUE (`Académique → LMD → ECUE → Nouvelle ECUE`). La modale ECUE inclut une jauge de validation des crédits qui empêche de dépasser le total alloué à l'UE.

## Étape 6 : configurer les frais

Le module comptabilité est central pour la trésorerie. Allez dans `Comptabilité → Frais → Configurer`.

Définissez d'abord les **catégories de frais** (dans `Comptabilité → Frais → Catégories`) :

- Frais d'inscription
- Frais de scolarité
- Frais d'examen
- Frais de bibliothèque
- Frais de polycopiés
- Frais d'uniforme
- etc.

Chaque catégorie peut être obligatoire ou optionnelle. Les catégories obligatoires sont automatiquement attendues lors de l'inscription d'un étudiant.

Ensuite, dans `Comptabilité → Frais → Configurer`, créez des **variants** : pour chaque combinaison `(filière, niveau, catégorie)`, définissez le montant attendu. Vous pouvez gérer cela en mode interactif via la commande artisan :

```bash
php artisan klassci:fees:configure
```

La commande vous guide pas à pas pour générer toutes les souscriptions sans doublon, archiver les inactifs et corriger les violations de contrainte unique.

## Étape 7 : créer les comptes du personnel

Allez dans `Personnel → Vue unifiée` et utilisez le bouton « + Nouveau personnel ». Choisissez le rôle (`coordinateur`, `enseignant`, `secrétaire`, `comptable`, `caissier`, `service technique`, `super-administrateur`).

Pour chaque membre, KLASSCI génère automatiquement :

- un **username** au format `prenom.nom` (déduplication automatique en cas de collision)
- un **mot de passe initial** sécurisé (envoyé par email ou affiché dans une modale immédiate)
- un **email** d'activation contenant le lien de première connexion

À la première connexion, le membre est forcé à changer son mot de passe. Les mots de passe expirent au bout de 6 mois avec une alerte 1 mois avant.

:::callout warning
**Attention aux permissions enseignant.** Si vous activez les modules `notes_evaluations`, `presences` et `emploi_temps`, pensez à les attribuer également au rôle `enseignant`. Sinon, les enseignants verront une sidebar vide. Le toggle module ne suffit pas, il faut aussi la permission métier.
:::

## Étape 8 : valider la configuration et lancer

Avant de communiquer l'URL aux étudiants, faites une dernière vérification :

1. Connectez-vous avec le compte super-administrateur sur l'URL de votre établissement (`https://votre-ecole.klassci.com`).
2. Vérifiez que la sidebar affiche bien tous les modules attendus.
3. Créez un étudiant test (`Étudiants → Nouvel étudiant`) et inscrivez-le dans une classe.
4. Vérifiez que la situation financière de cet étudiant test affiche les frais attendus.
5. Connectez-vous avec un compte enseignant test et vérifiez que la saisie des notes fonctionne.

Vous êtes prêt. Communiquez l'URL et les identifiants à votre équipe administrative et démarrez les inscriptions.

## Que faire ensuite

- Pour la gestion quotidienne des inscriptions, lisez le guide [Secrétaire — gérer les inscriptions](/docs/secretaire/inscriptions).
- Pour comprendre les statuts d'inscription et le workflow de validation, le même guide les détaille.
- Pour toute question, l'équipe support répond sous 24h ouvrées à [contact@klassci.com](mailto:contact@klassci.com).

Bon démarrage avec KLASSCI.

# Rule: Rien n'est en dur dans KLASSCI

## Quand s'active

Dès que tu écris une valeur littérale qui décrit **une réalité d'établissement** plutôt qu'une
règle du logiciel : un montant, un seuil, un rôle, un système académique, un libellé de frais,
une période, une liste de valeurs métier.

Et dès que tu lis une valeur en dur dans du code existant : c'est une dette, on la remonte.

## Le principe

KLASSCI est **multi-instance**. Six écoles tournent aujourd'hui sur la même base de code, et
chacune a sa propre organisation, ses propres tarifs, son propre système académique, ses propres
rôles. Une valeur écrite en dur impose la réalité d'une école à toutes les autres — souvent
celle qui existait le jour où la ligne a été écrite.

Le symptôme est toujours le même et toujours silencieux : **ça marche chez celui pour qui c'était
écrit, et ça sert une valeur fausse chez les autres**. Personne ne voit d'erreur. C'est ce qui
rend ce défaut plus coûteux qu'un plantage.

## L'incident fondateur (31 août 2026)

`ESBTPFraisConfiguration::getApplicableConfiguration()` — la méthode qu'utilise l'écran
d'encaissement pour trouver le tarif d'un étudiant :

```php
return static::getApplicableForScope($categoryId, [
    'systeme' => FraisScopeResolver::SYSTEME_BTS,   // ← en dur
    'filiere_id' => $filiereId,
    'niveau_id' => $niveauId,
    'annee_universitaire_id' => $anneeId,
]);
```

Le modèle sait pourtant distinguer les deux systèmes — à l'enregistrement il écrit
`systeme_academique = parcours_id ? LMD : BTS`. Mais la recherche, elle, ne demande que du BTS
et ne reçoit jamais de `parcours_id`.

Conséquence : **une inscription LMD ne trouve jamais sa configuration de frais**. Elle retombe
sur `default_amount`, et le caissier encaisse un montant que personne n'a configuré pour cet
étudiant. Sur un tenant qui a migré ses filières en LMD, c'est toute la caisse qui est fausse,
sans une seule ligne d'erreur.

## Ce qui ne doit JAMAIS être en dur

| Nature | Où ça vit |
|---|---|
| Montant, seuil, plafond, pourcentage | Setting d'instance (`SettingsHelper`), ou colonne de configuration |
| Rôle métier | Permission dans `config/permissions.php` + rôle custom créé par l'école (cf. `customizable-roles.md`) |
| Système académique (BTS / LMD) | Dérivé de la donnée — `systeme_academique`, `parcours_id`, `niveau->type` |
| Nom ou code de filière, de classe, de frais | Base de données |
| Fenêtre temporelle (3, 6, 12 mois) | Setting d'instance |
| Barème, pondération, coefficient | Setting d'instance (cf. `analytics-pitfalls.md`, barème d'assiduité) |
| Liste de valeurs métier | Enum PHP, et `Enum::cases()` partout — jamais une seconde liste recopiée |
| Nom d'établissement, adresse, logo | Settings d'instance (cf. purge des `ESBTP` codés en dur) |

## Ce qui PEUT rester littéral

Une constante qui décrit le **logiciel**, pas l'école :

- Un nom de table, de colonne, de route (mais voir `RouteNamesExistTest` : vérifie qu'il existe)
- Un code HTTP, un format de date, une borne technique (`max:255` alignée sur la colonne)
- Une valeur d'enum **dans sa propre définition** — c'est là qu'elle est déclarée
- Une valeur historique **dans une migration** : une migration décrit un état figé du passé,
  elle ne doit pas suivre l'évolution des constantes

## Le test qui tranche

Pose-toi la question : **« deux écoles peuvent-elles légitimement vouloir une valeur
différente ici ? »**

- Oui → ça se configure. Setting, registry, colonne, ou dérivé de la donnée.
- Non, c'est une règle du logiciel → littéral acceptable.

En cas de doute, c'est configurable : une valeur configurable qu'on ne change jamais ne coûte
rien, une valeur en dur qu'il faut changer coûte un déploiement et une régression.

## Une question ouverte se répond par un réglage, pas par une réunion

Le test ci-dessus vaut pour une valeur. Il vaut tout autant pour une **conduite** :
que fait le logiciel quand telle situation se présente ?

Ces questions-là surgissent en pleine conception et donnent l'impression qu'il faut
trancher avant d'écrire une ligne. C'est presque toujours faux. « Que se passe-t-il
quand le stock s'épuise ? », « une annulation rend-elle ce qu'elle avait consommé ? »,
« au bout de combien de temps une pièce est-elle périmée ? » : aucune n'a de réponse
unique valable pour six écoles. Ce sont des **politiques d'établissement**, et
l'écrire dans le code revient à imposer celle de la première école qui a posé la
question.

La réponse, à chaque fois : **on livre les conduites possibles, et l'école choisit.**
Un défaut sensé, jamais bloquant, et l'école durcit si elle le veut.

**Où poser le réglage — la distinction qui compte.** Une décision qui décrit une
POLITIQUE va dans les réglages d'instance. Une décision qui décrit UN OBJET va sur
l'objet, en colonne. La confondre produit des réglages globaux qui ne peuvent pas
répondre juste :

- « Au bout de combien de temps une pièce du dossier est-elle périmée ? » n'est pas
  un réglage d'école. Un extrait de naissance ne périme jamais, un certificat médical
  si. La durée appartient donc **à la pièce**, pas à l'établissement.
- « Bloque-t-on une inscription dont le dossier est incomplet ? » est bien une
  politique : elle vaut pour toutes les pièces, elle va dans les réglages.

Demande-toi : est-ce que deux lignes de la même table peuvent légitimement vouloir
des réponses différentes ? Si oui, c'est une colonne, pas un réglage.

**Ce que cela n'autorise pas.** Rendre configurable ce qui relève d'un invariant
comptable, légal ou d'intégrité. On ne règle pas si un paiement validé peut être
réécrit, ni si un motif de refus est obligatoire : ce sont des règles du logiciel,
et les livrer en option revient à livrer une porte.

## Le cas particulier du « zéro »

Un `0` en dur mérite une attention propre, parce qu'il se confond avec l'absence. Trois fois
dans la même journée, du code a traité « montant nul » comme « pas de montant » :

- Le reçu cochait un frais comme réglé dès que le reste tombait à zéro — or un frais non
  configuré a un dû nul, donc tout frais impayé partait coché
- La réinscription divisait par un total attendu nul et levait une `DivisionByZeroError` fatale
- L'API des frais substituait le montant par défaut dès qu'une configuration valait zéro — or
  un tarif configuré à zéro est une décision (étudiant exempté), pas une absence

**Un montant nul est une valeur. Ne le confonds jamais avec une valeur manquante.**

## Audit avant commit

```bash
# Montants et seuils en dur
grep -rnE '[><=]=?\s*[0-9]{4,}' --include="*.php" app/ | grep -vE 'max:|min:|migrations|Test\.php'

# Systeme academique en dur hors definition
grep -rn "SYSTEME_BTS\|SYSTEME_LMD\|'BTS'\|'LMD'" --include="*.php" app/ | grep -v Enums/

# Roles en dur (seuls superAdmin et serviceTechnique sont tolerables)
grep -rn "hasRole(" --include="*.php" app/ | grep -vE "superAdmin|serviceTechnique"

# Noms d'etablissement
grep -rniE "esbtp|islg|hetec|ephrata|rostan" --include="*.php" app/ | grep -vE "ESBTP[A-Z]|namespace|use "
```

Chaque résultat doit être justifié, ou remonté dans un setting.

## Anti-patterns à BLOQUER en review

1. Un montant, un seuil ou un pourcentage littéral dans un service ou un contrôleur
2. `SYSTEME_BTS` / `'BTS'` posé en dur dans une recherche, au lieu d'être dérivé de l'inscription
3. Un rôle métier inventé dans le code plutôt qu'une permission (cf. `customizable-roles.md`)
4. Une liste de valeurs recopiée à côté de son enum (cf. `type-seance-enum-extension.md`)
5. Un nom d'établissement, une adresse, un logo écrits dans le code
6. Un `0` traité comme « absent » sans que le code dise lequel des deux il veut dire
7. Une fenêtre temporelle figée dans un calcul d'analytics
8. Un repli silencieux vers une valeur par défaut quand la configuration ne répond pas :
   si la configuration est introuvable, dis-le, ne devine pas

## Voir aussi

- `.claude/rules/customizable-roles.md` — jamais de rôle en dur, uniquement des permissions
- `.claude/rules/analytics-pitfalls.md` — seuils et fenêtres configurables
- `.claude/rules/type-seance-enum-extension.md` — une liste de valeurs vit dans son enum
- `.claude/rules/adminklassci-tenant-management.md` — secrets et config par tenant

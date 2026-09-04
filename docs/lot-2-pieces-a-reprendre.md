# Pièces à fournir — ce que le lot 2 reprend

Ce document existe pour une raison précise : le lot 1 avait livré une table et un
modèle que **rien n'appelait**, et dont la forme s'est révélée fausse. Les sortir
sans rien écrire aurait fait perdre les décisions déjà prises ; les garder aurait
figé un modèle que personne ne pouvait éprouver. Ils sont donc retirés du code et
consignés ici, avec ce qui a été appris.

Ce qui a été **retiré du lot 1** (commit de correction, jamais déployé) :

- `database/migrations/2026_09_04_171338_create_esbtp_inscription_pieces_table.php`
- `app/Models/ESBTPInscriptionPiece.php`
- `app/Enums/EtatPieceDossier.php`
- les tests qui les couvraient, dans `tests/Feature/PiecesDossier/CataloguePiecesDossierTest.php`

Ce qui **reste** au lot 1, et sur quoi le lot 2 s'appuie : le catalogue
(`esbtp_pieces_dossier` et ses deux pivots de portée), son écran, son service
`App\Services\CataloguePiecesDossier`, et les cinq réglages d'école.

---

## 1. Le modèle, corrigé

**Une pièce appartient d'abord à l'étudiant, pas à l'année.** Un extrait de
naissance, des photos d'identité, un diplôme sont déposés une fois et durent. Ce
qui est propre à l'année, c'est ce qu'une inscription en **consomme** : six photos
déposées, deux consommées la première année, deux la deuxième, il en reste deux.

La première version du lot enseignait l'inverse, en toutes lettres et de façon
argumentée : « l'école reprend un exemplaire de chaque pièce chaque année, un
étudiant en licence 3 a donc trois lignes extrait de naissance, et c'est voulu ».
C'était faux. Le commentaire est corrigé dans la migration du catalogue, et la
phrase est rappelée ici pour que personne ne la rétablisse en croyant réparer.

Le catalogue porte désormais la décision, pièce par pièce :

| Colonne | Ce qu'elle décide |
|---|---|
| `appartenance` | `etudiant` (le dépôt dure et se consomme) ou `inscription` (redonnée chaque rentrée) |
| `exemplaires_par_inscription` | ce qu'**une** inscription consomme — ex-`nombre_exemplaires`, renommée parce que « nombre d'exemplaires » ne disait pas si c'était par an ou en tout |
| `duree_validite_mois` | nullable, **NULL = ne périme jamais** |

## 2. Les deux tables à créer

### `esbtp_pieces_deposees` — le dépôt, ancré sur l'étudiant

| Colonne | Contrainte |
|---|---|
| `etudiant_id` | `cascadeOnDelete` |
| `piece_dossier_id` | `restrictOnDelete` |
| `inscription_id` | **nullable**, `nullOnDelete` |
| `quantite_deposee` | entier |
| `etat` | **quatre** valeurs : `attendue`, `deposee`, `validee`, `refusee` |
| `motif`, `decidee_par`, `decidee_at` | la décision et son auteur |
| `document_id` | nullable, vers `esbtp_etudiant_documents` |
| `date_delivrance` | nullable |

Deux points qui ne vont pas de soi :

- **La validité court depuis la délivrance, pas depuis le dépôt.** Un extrait
  délivré en 2019 et déposé en 2026 est déjà périmé sous une validité de trois
  mois. Quand `date_delivrance` est nulle, on part du dépôt — et la colonne doit
  le dire dans son commentaire, sinon le prochain lecteur croira que le dépôt fait
  toujours foi.
- **Le fichier vit ici, une seule fois**, via `document_id`. La colonne
  `fichier_chemin` de l'ancienne table disparaît. Le téléversement reste
  **facultatif** : « déposée » n'exige aucun fichier, et une école qui ne
  numérise rien doit suivre ses dossiers de bout en bout. Il devient seulement
  plus *utile* qu'avant, parce qu'une pièce qui dure a plus de valeur numérisée
  qu'une pièce redemandée chaque année.
- **Une pièce annuelle produit aussi une ligne de dépôt**, simplement liée à son
  inscription. La priver de ligne obligerait son état, son motif et son décideur
  à vivre sur la ligne de consommation : l'état d'une pièce habiterait deux
  tables selon son appartenance, et chaque écran devrait brancher.

### `esbtp_inscription_pieces` — la consommation

| Colonne | Contrainte |
|---|---|
| `etudiant_id` | `cascadeOnDelete` |
| `piece_dossier_id` | `restrictOnDelete` |
| `inscription_id` | **nullable**, `nullOnDelete` |
| `quantite_consommee` | entier |
| `non_applicable` + `motif` | le seul des cinq états d'origine qui soit vraiment annuel |

`inscription_id` est **nullable et `nullOnDelete`**, et ce n'est pas un détail de
schéma. KLASSCI supprime définitivement des étudiants avec leurs dépendances. En
`cascadeOnDelete` — ce que faisait la table livrée — une suppression définitive
évapore les lignes de consommation et rend le stock **quoi que l'école ait
choisi** : le réglage « non, on garde » ne peut pas être honoré. En `nullOnDelete`,
une inscription supprimée laisse une consommation orpheline rattachée à
l'étudiant, que le réglage retient ou ignore. La suppression de l'étudiant, elle,
emporte tout, correctement.

## 3. Le piège d'unicité, que ce lot connaît déjà

`unique(etudiant_id, piece_dossier_id, inscription_id)` **ne protège rien** : sur
MySQL, un index unique portant une colonne nulle laisse passer les doublons sans
rien dire. C'est exactement ce que la migration du catalogue explique à propos du
`code`, et c'est la raison pour laquelle il est unique globalement.

Le remède est la sentinelle zéro, déjà employée dans ce dépôt par
`esbtp_ue_matiere` pour cette raison précise : une colonne générée sur
`COALESCE(inscription_id, 0)`, et l'unicité posée dessus.

## 4. Le disponible se CALCULE, il ne se décrémente pas

C'est la décision qui compte le plus.

> disponible = somme déposée non périmée − somme consommée par les inscriptions retenues

Les « retenues » sont toutes, ou seulement celles qui ne sont ni annulées ni
orphelines, selon le réglage. L'annulation, la suppression et la double
décrémentation deviennent alors **un `where`**, et non trois écritures
compensatoires à écrire et à tenir. Elle rend aussi automatique le fait qu'une
inscription annulée ne consomme plus rien, sans qu'aucun code n'ait à le rendre.

## 5. Les réglages, déjà posés

Ils existent en base depuis le lot 1
(`2026_09_04_171339_add_pieces_dossier_settings`) et l'écran de scolarité les
montre déjà, en disant qu'ils attendent ce lot :

- `pieces_dossier.epuisement` — `bloquer` | `signaler` | `silence`, défaut `signaler` ;
- `pieces_dossier.restitution_annulation` — booléen, défaut `1`.

Leurs clés sont des constantes de `App\Services\CataloguePiecesDossier`
(`REGLAGE_EPUISEMENT`, `REGLAGE_RESTITUTION_ANNULATION`) : ne les réécrivez pas en
chaînes.

---

## 6. Trois défauts trouvés sur le code retiré — ne les réintroduisez pas

Ils portaient sur `ESBTPInscriptionPiece`, qui sort du lot. Le code part, les
défauts restent vrais : ils visent la garde du motif, qui vivra désormais sur
`esbtp_pieces_deposees`. Les corriger dans le vide n'aurait servi à rien ; les
taire aurait garanti leur retour.

### 6.1 La garde du motif se contourne par écriture de masse

Le modèle tenait « un refus doit dire pourquoi » dans un hook `saving`, et son
commentaire affirmait que la règle valait « là où toutes les écritures passent ».
C'est faux :

```php
ESBTPInscriptionPiece::where('inscription_id', 42)->update(['etat' => 'refusee']);
```

`update()` sur le constructeur de requête n'instancie aucun modèle, ne déclenche
aucun événement, et écrit un refus sans motif. Une commande, un import ou une
reprise de données contournaient donc exactement la règle qu'ils étaient censés
respecter.

**Ce qu'il faut faire au lot 2** : poser la garde **en base**, dans la migration
de `esbtp_pieces_deposees`, par `DB::statement` après le `Schema::create` (la
table est vide, la contrainte ne peut échouer sur aucune ligne existante) :

```php
// Uniquement là où le moteur applique réellement les CHECK : MySQL 8.0.16+ et
// MariaDB 10.2+. Sur SQLite, ALTER TABLE ... ADD CONSTRAINT n'existe pas.
if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
    DB::statement("
        ALTER TABLE esbtp_pieces_deposees
        ADD CONSTRAINT esbtp_pieces_deposees_etat_connu
        CHECK (etat IN ('attendue','deposee','validee','refusee'))
    ");

    DB::statement("
        ALTER TABLE esbtp_pieces_deposees
        ADD CONSTRAINT esbtp_pieces_deposees_refus_motive
        CHECK (etat <> 'refusee' OR (motif IS NOT NULL AND TRIM(motif) <> ''))
    ");
}
```

Les **deux** contraintes, et pas seulement la seconde. La colonne `etat` est un
`string` sans domaine : une écriture de masse posant `'REFUSEE'` ou `'refuse'`
passerait à côté de la garde du motif sans rien déclencher. La garde du motif ne
vaut que si le vocabulaire des états est clos.

Contrepartie assumée : ajouter un état exigera une migration. C'est le prix d'une
règle que le moteur applique, et c'est le bon prix pour une règle qui décide si un
dossier d'étudiant peut être débloqué.

Le `down()` doit les retirer avant de rendre la table — sachant que le `drop` de
la table les emporte de toute façon ; l'utilité de la ligne explicite est le jour
où le `down()` conservera la table.

### 6.2 La valeur par défaut « attendue » était morte

L'appel le plus naturel de tous —

```php
ESBTPInscriptionPiece::create(['inscription_id' => 1, 'piece_dossier_id' => 1]);
```

— avait un `etat` nul au moment du `saving`, `tryFrom(null)` rendait `null`, et le
modèle levait « Etat de piece inconnu : « » ». Le `->default('attendue')` de la
migration était donc **inatteignable**, et le message accusait une valeur inconnue
là où il n'y avait pas de valeur du tout.

**Ce qu'il faut faire** : un état absent vaut `ATTENDUE`, avant tout contrôle.

```php
$etat = $ligne->etat instanceof EtatPieceDossier
    ? $ligne->etat
    : EtatPieceDossier::tryFrom((string) ($ligne->etat ?? EtatPieceDossier::ATTENDUE->value));
```

**Et une note liée, qui vaut avertissement** : une exception levée depuis `saving`
remonte en **500**. Un refus sans motif donnerait une page blanche là où il faut un
422 et un message. La garde du modèle — et plus encore la contrainte en base — est
le **filet de dernier recours**, celui qui rattrape les commandes et les imports.
La validation que voit un utilisateur doit vivre dans un `FormRequest`, comme
`UpsertPieceDossierRequest` le fait déjà pour le catalogue.

### 6.3 Un test vert pour la mauvaise raison

```php
$this->expectException(RuntimeException::class);
$this->ligneDEtat(EtatPieceDossier::REFUSEE);   // crée l'inscription ET la pièce
```

`QueryException extends PDOException extends RuntimeException` : **toute** erreur
SQL survenant pendant la préparation — une colonne manquante, une clé étrangère
non satisfaite — rendait ce test vert sans que la garde du motif fût jamais
atteinte. C'était le test le plus important du lot, sur la seule règle métier
qu'il défendait, et il ne prouvait rien.

**Ce qu'il faut faire** : construire le décor **avant** d'armer l'attente, et
nommer le message.

```php
[$inscription, $piece] = $this->contexteDEtat();   // rien d'attendu encore

$this->expectException(RuntimeException::class);
$this->expectExceptionMessage('Un refus doit dire pourquoi');

ESBTPPieceDeposee::create([...]);                  // la seule ligne sous surveillance
```

La règle générale, au-delà de ce cas : `expectException` ne doit jamais couvrir
plus d'une instruction, et jamais une classe d'exception assez large pour attraper
la plomberie du test.

---

## 7. Le code retiré, et où le relire

Le code intégral des trois fichiers retirés se relit tel qu'il était livré :

```sh
git show 3544d05b:database/migrations/2026_09_04_171338_create_esbtp_inscription_pieces_table.php
git show 3544d05b:app/Models/ESBTPInscriptionPiece.php
git show 3544d05b:app/Enums/EtatPieceDossier.php
```

Il est référencé plutôt que recopié ici **à dessein** : la migration et le modèle
décrivent un schéma que la section 2 remplace. Les recopier dans ce document en
ferait le point de départ le plus commode, donc celui qu'on reprendrait — et l'on
rejouerait la forme fausse. Ce qui méritait d'être sauvé de leur prose est déjà
au-dessus.

Une seule pièce se reprend presque telle quelle : l'énumération des états.

```php
enum EtatPieceDossier: string
{
    case ATTENDUE = 'attendue';
    case DEPOSEE = 'deposee';
    case VALIDEE = 'validee';
    case REFUSEE = 'refusee';

    // `non_applicable` NE REVIENT PAS ICI. C'était le cinquième cas, et c'est le
    // seul des cinq qui soit vraiment ANNUEL : une pièce du catalogue peut ne pas
    // concerner un étudiant cette année-là (un transfert sans certificat de
    // scolarité du même établissement) sans que cela dise quoi que ce soit de son
    // dépôt. Il devient donc un drapeau `non_applicable` + `motif` sur la ligne de
    // CONSOMMATION, et l'état ci-dessus ne décrit plus que le dépôt.

    public function exigeUnMotif(): bool
    {
        return $this === self::REFUSEE;
    }

    public function estSoldee(): bool
    {
        // DEPOSEE n'est PAS soldée : l'étudiant a remis quelque chose, personne
        // n'a encore dit que c'était la bonne pièce. Confondre les deux, c'est
        // laisser partir au ministère un dossier que nul n'a relu.
        return $this === self::VALIDEE;
    }
}
```

Et la raison d'être des cinq — devenus quatre — reste entière : **un booléen
« fournie » ne suffit pas**. Il ne distingue pas une pièce jamais apportée d'une
pièce apportée puis refusée : dans les deux cas il vaut faux, et le guichet
rappelle un étudiant qui s'est déjà déplacé. C'était le manque central des essais
précédents.

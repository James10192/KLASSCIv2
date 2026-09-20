# Rule: Fuite ECUE LMD dans un sélecteur de matières BTS — playbook rapide

## Quand s'active

Dès que :
- Un utilisateur signale des **matières en double** sur une page BTS (modal notes, formulaire d'évaluation, présences, bulletins, exports, dashboard enseignant, picker de matière…) après un import de maquettes LMD.
- Tu ajoutes/modifies un **sélecteur ou listing de matières** dans un contexte **BTS classique**.
- Tu vois `ESBTPMatiere::...->get()` / `->pluck()` sans filtre LMD dans un controller ou une vue BTS.

## Le principe (1 phrase)

Une matière qui a `unite_enseignement_id` non nul est une **ECUE LMD** : elle ne doit JAMAIS apparaître dans un contexte **BTS**, et inversement.

| Contexte | Filtre à appliquer sur `esbtp_matieres` |
|---|---|
| **BTS** (évaluations, notes, présences BTS, bulletins BTS…) | `->whereNull('unite_enseignement_id')` |
| **LMD strict** (notes LMD, TPE, ECUE管理…) | `->whereNotNull('unite_enseignement_id')` |

## Fix express (copier-coller)

```php
// AVANT (fuite : retourne aussi les ECUE LMD)
$matieres = ESBTPMatiere::orderBy('name')->get();

// APRÈS (BTS only)
$matieres = ESBTPMatiere::whereNull('unite_enseignement_id') // BTS only : exclure les ECUE LMD
    ->orderBy('name')->get();
```

S'il y a déjà un `where('is_active', true)` ou autre, ajoute simplement `->whereNull('unite_enseignement_id')` dans la chaîne.

## Trouver tous les sites suspects (1 commande)

```bash
# Listings globaux de matières sans filtre ECUE LMD (fuite potentielle)
grep -rnE "ESBTPMatiere::(orderBy|all|query|where\('is_active')" app/Http/Controllers/ resources/views/ \
  | grep -viE "unite_enseignement_id"
```

Chaque résultat = à auditer :
- **Listing GLOBAL non scopé** (`->get()` direct, pas de `whereHas('filieres'/'niveaux'/'liaisonsFilieresNiveaux')`) → **FUITE** → applique le filtre.
- **Listing scopé filière+niveau via `liaisonsFilieresNiveaux`** (pivot 3-way `esbtp_matiere_filiere_niveau`) → **FUITE AUSSI. Applique le filtre.**

  Cette rule a dit le contraire pendant trois mois — « pas de fuite, l'import LMD
  ne peuple pas ce pivot, laisser » — et c'est exact sur l'import : `LMDImportService`
  n'écrit jamais cette table. Mais **d'autres écrivains le font**, eux sans garde :
  `ESBTPMatiereController::addToCombination()`, son `update()`, et le picker
  « matières disponibles » de `/esbtp/classes/{id}/matieres`. Il suffit d'un clic.

  Mesuré sur esbtp-abidjan (septembre 2026) : `TPOH243` « Alimentation en eau et
  QTE », une ECUE, portait une ligne `(TRAVAUX_PUBLICS, 2A)` et sortait donc sur
  les bulletins de Travaux Publics 2ᵉ année — invisible des deux écrans qui
  auraient permis de l'en retirer. **Cette dernière partie est corrigée** :
  `/esbtp/matieres/classification` affiche ces lignes dans un bloc distinct, avec
  leur croix de retrait. Le défaut, lui, se mesure toujours — voir plus bas.

  **Le coût de cette phrase n'est pas le défaut, c'est l'audit qu'elle a clos.**
  Six lecteurs scopés ont été examinés, déclarés sains sur cette prémisse, et
  laissés tels quels. Une rule qui absout est plus dangereuse qu'une rule absente :
  ne réécris pas celle-ci en « sain » sans avoir compté les lignes de ce pivot qui
  pointent vers une matière à `unite_enseignement_id` non nul.
- ⚠️ `whereHas('filieres')` seul (pivot 2-way `esbtp_matiere_filiere`) → **PEUT fuiter** (l'import LMD peuple `esbtp_matiere_filiere`). Vérifier : si pas aussi `whereHas('niveaux')`, ajouter `whereNull('unite_enseignement_id')`.

## Depuis septembre 2026 : le garde est à l'ÉCRITURE, pas en lecture

**Ne sème plus de `btsOnly()` chez les lecteurs. Ça ne marche pas.** Le chantier
qui a corrigé `TPOH243` l'a essayé : douze filtres, neuf fichiers, **quatre passes
de revue** — et chaque passe trouvait une porte que la précédente avait manquée
(le panneau des coefficients, `updateLiaisons()`, le « Total matières » du planning
général). Filtrer en lecture demande à chaque futur écran de s'en souvenir.

`LiaisonsDeMatiere::poser()` **lève** désormais sur une ECUE, avec un
`Log::warning`. Les cinq appelants refusent déjà en amont avec un message pour
l'utilisateur ; ce garde-ci sert au sixième, celui qui n'est pas encore écrit.

**Ce n'est PAS le goulot unique, et cette rule l'a affirmé à tort.** Des
écrivains touchent le pivot canonique sans passer par lui — la phrase a été
écrite sans être mesurée, exactement comme celle qu'elle remplaçait. Ils
étaient trois ; il en reste **deux** :

| écrivain | garde |
|---|---|
| `app/Console/Commands/SyncMatiereFilireNiveau.php` (`insert()` brut) | **le sien**, ajouté en même temps que cette ligne |
| `database/seeders/Demo/PromotionPrecedenteNotesDemoData.php` | aucun — données de démonstration |

`ChargementDeMaquette` en était le troisième, et son garde ne tenait que par
l'**ordre** de deux appels — le commentaire l'avouait sur place. Il n'écrit
plus : `poser()` **rend** la ligne qu'il crée, donc le chargement la complète
au lieu de la réécrire. Un écrivain hors goulot en moins, un piège
d'ordonnancement en moins. C'est le geste à imiter pour les deux qui restent.

La commande qui rejoue cette liste, au lieu de la croire :

```bash
grep -rn "esbtp_matiere_filiere_niveau\|ESBTPMatiereFilierNiveau" app/ database/ --include="*.php" \
  | grep -E "::(insert|create|upsert|updateOrCreate|firstOrCreate|firstOrNew)\(|->insert\(|new ESBTPMatiereFilierNiveau" \
  | grep -vE "LiaisonsDeMatiere\.php|database/migrations/|^\S+:[0-9]+:\s*(\*|//)"
```

Au 20 septembre 2026, il rend **exactement les deux lignes du tableau**.

Le motif cherche les **créateurs** : `update()` et `delete()` sur une ligne
existante ne posent pas de nouvelle ligne et ne sont pas concernés. Il exclut
aussi les lignes de commentaire — une première version les comptait, et rendait
quatre lignes pour trois écrivains, ce qui rendait son propre seuil faux.

Toute ligne rendue est un écrivain à garder. Si elle en rend plus de deux,
l'inventaire ci-dessus est périmé — corrigez-le plutôt que de le contourner.
Et si elle en rend moins, dites-le aussi : ce compte a déjà été faux dans les
deux sens.

Corollaire, et c'est le piège symétrique : **le RETRAIT doit rester ouvert.**
`ResolutionDeMatiere::matierePourRetrait()` accepte une ECUE, et c'est délibéré.
**Deux méthodes publiques, pas un drapeau** : le paramètre booléen d'origine
faisait basculer la garde centrale de ce chantier, et un appelant qui l'oublie
doit se voir plutôt que se deviner — c'est la consigne de
`lmd-bts-matieres-single-source.md`, et `MatiereTreeBuilder` l'applique déjà
(`buildForPlanning()` / `buildWithVolumeBudget()`). Ne le re-fusionnez pas en un
seul appel « plus simple ». Refuser des deux côtés est exactement ce qui avait rendu la ligne
inextirpable — listée par le CLI, masquée par l'écran, refusée au retrait.
Le chargement contamine, le retrait corrige : ils ne peuvent pas porter le même
garde.

Les filtres en lecture qui restent sont une ceinture, pas la bretelle. Un nouvel
écran BTS n'a plus à en poser.

## Il y a DEUX familles, et la première n'était pas la pire

Tout ce qui précède décrit la **famille 1** : une ECUE rattachée à une maquette BTS
par le pivot `esbtp_matiere_filiere_niveau`. Le garde à l'écriture la ferme.

**La famille 2 était ouverte, et elle coûte plus cher : une ECUE ÉVALUÉE dans une
classe BTS.** Elle n'a rien à voir avec les pivots — elle passe par
`esbtp_evaluations` (ou par une moyenne manuelle dans `esbtp_resultats`), et elle
atteint le bulletin **par les notes**.

**Pourquoi aucun des douze filtres `btsOnly()` ne l'arrêtait.** Ils portent tous
sur des lecteurs de la **maquette**. Or `BulletinSubjectRowsCompleter` le dit
lui-même, en toutes lettres dans son en-tête :

> **Rien ne disparait.** Une matiere reellement notee mais absente de la maquette
> reste au bulletin. La maquette ajoute des lignes, elle n'en retire jamais.

Le bulletin BTS est **piloté par les notes** : la maquette complète, elle ne
retranche pas. Un filtre posé sur ce qu'elle ajoute ne peut, par construction,
rien enlever à une ligne qui porte une note.

**Ce que ça faisait, mesuré et rejoué en test** (septembre 2026, esbtp-abidjan) :
l'ECUE `TPGC641` « OGC », évaluée dans la classe BTS **2BTS GBAT E**, portait
**34 notes**. Un 14/20 en matière BTS (coef 2) accompagné d'un 4/20 sur l'ECUE
(coef 1) rendait une moyenne générale de **10,67 au lieu de 14,00** — et la valeur
fausse était **persistée** dans `esbtp_resultats`, `esbtp_resultats_matieres` et
`esbtp_bulletins.moyenne_generale`.

### Ce qui est en place depuis septembre 2026

Un seul prédicat, `App\Domain\Academique\CoherenceSystemeAcademique`, et trois
usages qui n'ont volontairement pas la même conduite :

| site | conduite | pourquoi |
|---|---|---|
| `ESBTPEvaluation::booted()` (existait depuis août 2026) | **refuse** | c'est le geste qui contamine |
| `ESBTPResultat::booted()` (nouveau) | **refuse** | la moyenne manuelle atteint la même ligne sans passer par une évaluation |
| `BulletinService` + `BtsCurrentResultSnapshotService` | **écarte, et le journalise** | ces notes ont été saisies par quelqu'un : refuser de générer le bulletin punirait l'élève |

Les deux gardes d'écriture ne se déclenchent **qu'à la création, ou si la classe
ou la matière change**. Une ligne héritée reste modifiable sur sa moyenne ou son
titre — sinon elle deviendrait incorrigeable, exactement le défaut que le retrait
de maquette corrige par ailleurs.

### Un garde à l'écriture rend insauvegardable tout écran qui offre ce qu'il refuse

C'est la leçon de la passe 15, et elle vaut pour tout garde futur : poser un
`throw` sur un modèle ne suffit pas. **Il faut retirer la chose refusée de tous
les écrans qui la proposent, et rendre atomique toute boucle qui l'écrit.**

Mesuré sur `ESBTPResultatController` :

- `previewMoyennes()` construisait sa liste en croisant les deux pivots **plats**,
  que `LiaisonsDeMatiere::retirer()` ne nettoie volontairement pas. Une ECUE
  retirée de la maquette y ressortait donc, et l'écran la proposait à la saisie.
- `updateMoyennes()` écrit **une ligne par matière**, sans transaction. Le garde
  levait au milieu : les matières déjà traitées restaient enregistrées, les
  suivantes jamais, et chaque nouvelle tentative laissait un état partiel
  différent. **L'écran devenait insauvegardable**, et la seule façon d'en sortir
  était de comprendre le refus — que le redirect n'expliquait pas.

Le jumeau AJAX `bulkUpdateMoyennes()` avait bien sa transaction et son
`catch (ValidationException)`. C'est cette **asymétrie entre deux méthodes du même
fichier** qui a survécu à quatre passes : on relit celle qu'on vient d'écrire, pas
sa voisine.

**Et un écran peut avoir plusieurs chemins vers la MÊME liste. Comptez-les, ne les
estimez pas.** `previewMoyennes()` en a **quatre**, et ce chantier les a comptés à
voix haute « trois », puis « deux », avant de les compter vraiment :

| ordre | source | pourquoi il masque les suivants |
|---|---|---|
| 1 | les lignes **déjà enregistrées** de `esbtp_resultats` | il a la préséance : `array_diff_key` retire d'emblée ce qu'il a posé |
| 2 | les **notes** | n'ajoute que `if (! isset(...))` |
| 3 | le **catalogue** | idem |
| 4 | le **snapshot** | filtré en amont |

Deux correctifs successifs ont visé le 2 puis le 3, et aucun des deux ne pouvait
voir une ECUE portant une ligne enregistrée — c'est-à-dire le cas le plus courant
en production, celui que `diagnostics:evaluation-system-mismatch` recense sous
`moyennes_manuelles`. **Un filtre placé tard ne retire rien de ce qu'un chemin
plus tôt a déjà mis.** C'est la même leçon que
`calculateStudentStatsFixed()` avait déjà coûtée, reprise une passe plus tard sur
un autre fichier : **compter les chemins d'ingestion, pas les méthodes.**

Un troisième écran, `editResultatsClasse()`, portait le même croisement non filtré.
Le précédent à imiter était dans le dépôt : `BulletinInlineConfigurationService::matieresPourConfiguration()`
a **trois** lectures — la canonique, le repli plat, et les matières qui portent une
évaluation — et les trois portent `btsOnly()`.

**L'extraction est faite pour une des trois, et pas pour les deux autres.**
`previewMoyennes()` délègue désormais à `App\Domain\Bulletins\MoyennesDeLApercu`,
qui porte la préséance des quatre chemins **une seule fois** et y pose le prédicat
une seule fois — 510 lignes de contrôleur ramenées à une centaine. C'est là qu'il
faut poser tout nouveau filtre de cet écran, et nulle part ailleurs.

`updateMoyennes()` et `editResultatsClasse()`, eux, gardent leur `btsOnly()` par
liste, et ce n'est pas un oubli : `updateMoyennes()` fait 219 lignes sur le chemin
d'impression de huit instances, et la sortir dans la même branche ajouterait du
risque au lieu d'en retirer. Ce qui protège réellement est **le garde à
l'écriture**, qui refuse ; les listes ne font que ne plus proposer. Le déclencheur
de leur extraction reste la **quatrième** liste, pas la prochaine revue.

**Le contrôle à faire en posant un garde d'écriture** : chercher tous les
écrivains du modèle gardé, et pour chacun se demander (a) d'où vient ce qu'il
écrit, (b) combien de chemins mènent à ce qu'il propose, et (c) que reste-t-il en
base si le garde lève au troisième tour de boucle.

```bash
grep -rn "ESBTPResultat::\(create\|updateOrCreate\)\|new ESBTPResultat" app/ --include="*.php"
```

**Le snapshot est filtré AUSSI, et ce n'est pas une redondance.**
`BtsCurrentResultSnapshotService` alimente l'écart « Officiel / Courant ». Filtrer
seulement la génération laisserait l'erreur des deux côtés de la comparaison :
l'écart resterait nul et **n'alerterait personne**.

**L'écart écarté est journalisé** (`Log::warning`, dédoublonné par couple
classe × matière). Une moyenne qui bouge sans explication est pire qu'une moyenne
fausse : on ne sait même pas qu'il faut chercher. C'est le piège #12 de
`klassci-debugging-discipline.md`.

### La leçon qui a coûté trois passes de revue : un lecteur peut avoir DEUX portes

Le correctif de lecture a été posé trois fois, et deux fois il ne servait à rien —
non pas parce qu'un lecteur avait été oublié, mais parce qu'**un lecteur déjà
corrigé avait un second chemin d'ingestion qui annulait le premier**.

`BulletinService::calculateStudentStatsFixed()` lit les notes, **puis** lit
`esbtp_resultats` — et cette seconde lecture n'ajoute pas, elle **écrase et
recrée** l'entrée que le filtre du premier chemin venait d'écarter :

```php
// Manual moyenne overrides the note-computed value
$notesByStudentMatiere[$etudiantId][$matiereId]['total_points'] = $resultat->moyenne;
```

Filtrer un seul des deux chemins revenait donc à **n'en filtrer aucun** dès qu'une
ligne héritée existe — c'est-à-dire dans le cas même que le correctif visait.
Mesuré : `/esbtp/resultats` rendait **9,00** pendant que le PDF affichait **14,00**,
pour le même élève.

**Le contrôle à faire avant de déclarer un lecteur corrigé** : compter ses chemins
d'ingestion, pas ses méthodes. Un `foreach` qui écrit dans le même tableau qu'un
autre `foreach` est un second chemin, même s'il est 30 lignes plus bas.

### Les calculs de moyenne TROUVÉS À CE JOUR — ce nombre n'est pas une garantie

> La version précédente de cette section écrivait « **les cinq calculs de moyenne
> du dépôt** ». C'était un absolu jamais mesuré, et il était faux. La version
> d'après annonçait **neuf**, mesurés — et la passe 12 en a trouvé **deux de
> plus**. Le nombre ci-dessous est donc, lui aussi, à lire comme un relevé, pas
> comme un inventaire. La passe 15 en a trouvé un **douzième**,
> `DashboardController::moyenneCourante()`, et c'était le plus vicieux des
> douze : son repli n'est emprunté que **quand aucun bulletin n'est
> configuré**, c'est-à-dire précisément au moment où une ECUE mal rangée se
> voit le plus. C'est exactement le défaut que le piège #14 de
> `klassci-debugging-discipline.md` raconte pour lui-même — un inventaire
> démenti quatre fois, chaque version publiée comme définitive. Lisez donc ce
> tableau pour ce qu'il est : ce qui a été trouvé, pas ce qui existe.
>
> **Et la passe 16 en a trouvé un treizième**, `ESBTPNoteController::computeGeneralAverage()`,
> qui alimente le panneau d'impact affiché SOUS LA MAIN de l'enseignant pendant
> qu'il saisit une note. Le tamis ci-dessous ne pouvait pas le rendre : il ne
> moyenne ni par `avg(` ni par une somme de coefficients sur la ligne, il
> délègue à `NoteCalculationService` matière par matière. C'est la lecture qui
> l'a trouvé, comme cette section le prescrit — et il illustre le coût réel du
> défaut : tant que **tous** les écrans se trompaient d'accord, personne ne
> voyait rien. C'est en corrigeant les douze autres qu'on fabriquait la
> contradiction.
>
> **Ce treizième a été nommé ici et oublié du tableau pendant une passe entière**
> — le défaut même que cette section raconte, commis dans le paragraphe qui le
> raconte. Il y figure désormais. S'y ajoute `MoyennesDeLApercu::assembler()`,
> qui n'est pas une quatorzième trouvaille mais l'**extraction** du calcul qui
> vivait dans `previewMoyennes()` : le tableau nomme maintenant l'endroit où il
> vit, pas le contrôleur qui l'appelle.

Ce qui vaut pour les calculs vaut pour les **lectures** : le balayage
`withTrashed()` — une lecture nue rend `null` sur une ligne effacée en douceur,
ce qui désarme tout filtre écrit en `! $x ||` — a été annoncé à **sept sites**,
et il en manquait **trois**, écrits dans la même branche :
`ReeinscriptionService::getNotesEtudiant()`,
`EtudiantAcademicJourneyPresenter::resultats()` et
`EtudiantDossierService::getNotesParSemestre()`. Le premier ne montre rien : il
DÉCIDE du passage en année supérieure. Même leçon, même forme, même passe.

| calcul | ce qu'il alimente | chemins | état |
|---|---|---|---|
| `BulletinService::buildDonneesBulletin()` | bulletin, `esbtp_resultats`, `esbtp_resultats_matieres` | notes + moyennes enregistrées | filtré |
| `BulletinService::calculerMoyenneGlobaleEtudiant()` | `moyenne_classe`, `meilleure_moyenne`, `plus_faible_moyenne` | moyennes enregistrées, repli sur notes | filtré |
| `BulletinService::calculateStudentStatsFixed()` | moyennes et rangs du **tableau** de `/esbtp/resultats` | notes **+** moyennes enregistrées qui écrasent | filtré, y compris sans classe sélectionnée (passe 12) |
| `BulletinService::getPreCalculatedResults()` | **la bande KPI** de `/esbtp/resultats` (Moyenne générale, Taux de réussite) | moyennes enregistrées | filtré (passe 12) |
| `BulletinService::calculateStudentAverageForPeriode()`, branche `annuel` | **écrit** `esbtp_bulletins.moyenne_generale` via le backfill | notes **+** moyennes enregistrées qui écrasent | filtré (passe 12) — **mais sa requête reste non cadrée**, voir plus bas |
| `BtsCurrentResultSnapshotService` | écart « Officiel / Courant », Bilan de la fiche étudiant | notes + moyennes enregistrées | filtré |
| `ESBTPResultatController::resultatEtudiant()` | tableau « Résultats par matière », KPI Matières / Coefficients | notes + moyennes enregistrées | filtré |
| `ESBTPNoteController::computeGeneralAverage()` | le panneau d'impact affiché pendant la saisie d'une note | notes de la classe cible | filtré (passe 16) |
| `App\Domain\Bulletins\MoyennesDeLApercu::assembler()` | l'écran « Modifier les moyennes » (`previewMoyennes()`) | lignes enregistrées, notes, maquette, snapshot | filtré — **le seul endroit** où la préséance des quatre chemins est écrite |
| `ESBTPBulletinController::buildBulletinPdf()` | rien — **écrasé** par la projection du service (voir plus bas) | notes | filtré par précaution |
| `ReeinscriptionService::getNotesEtudiant()` | décision passage / rattrapage / redoublement, matières échouées | notes | filtré (passe 11) |
| `EtudiantAcademicJourneyPresenter::resultats()` | moyenne du parcours, fiche étudiant | moyennes enregistrées | filtré (passe 11) |
| `EtudiantDossierService::getNotesParSemestre()` | rien — `$dossier` n'est cité dans aucune vue | notes | filtré par précaution |
| `DashboardController::moyenneCourante()` | la moyenne de **l'accueil mobile** de l'élève | snapshot, **repli sur notes brutes** si aucun bulletin n'est configuré | filtré (passe 15) |

> **Ne désignez jamais ces calculs par leur rang.** La passe 12 a inséré deux
> lignes au milieu de ce tableau, et trois renvois de la prose (« le sixième »,
> « le septième », « le cinquième ») se sont mis à pointer ailleurs. Deux se
> rattrapaient parce que le paragraphe nommait sa méthode ; le troisième, non —
> il envoyait le lecteur vers une méthode qui existe, qui est dans le tableau,
> et dont la description ne correspond pas. Un faux repère qui se lit comme un
> vrai. Les renvois nomment donc la méthode, et le tableau peut grandir.

**`buildBulletinPdf()` a été signalé comme « le seul calcul qui écrive », et il est INERTE.**
C'est le cas le plus instructif du chantier, parce qu'il se lit exactement comme
une fuite : `buildBulletinPdf()` n'appelle pas `genererDonneesBulletin()`, il
relit `esbtp_notes` lui-même, recalcule, et **persiste** (`$bulletin->save()`).

**Mesuré, et l'inverse de ce qui était annoncé.** Cent trente lignes plus bas,
`array_replace($data, getOfficialBulletinTemplateDefaults(...))` remplace
`resultatsGeneraux`, `resultatsTechniques`, `moyenneGenerale` et `moyenneGlobale`
par la projection du service — déjà filtrée — et cette projection **ré-enregistre**
la bonne moyenne après celle du contrôleur. Le gabarit `pdf-configurable` ne lit
d'ailleurs que `$moyenneGlobale`, jamais `$bulletin->moyenne_generale`. Le filtre
a donc été posé, puis **retiré pour mesurer** : le test restait vert. Rien
n'atteignait ni le document, ni l'état final de la base.

Le filtre est gardé quand même — dix lignes, et le jour où l'ordre de ce
`array_replace` change, ce calcul-là reprend la main en silence. Mais il ne ferme
aucune fuite, et le prétendre serait la quatrième assertion creuse de ce
chantier. **La correction qui vaudrait vraiment** est que `buildBulletinPdf()`
cesse d'avoir un calcul propre et lise le service, comme l'aperçu : tant que deux
calculs cohabitent, une revue les re-signalera. Ce refactor n'est pas fait — un
contrôleur de 2500 lignes sur le chemin d'impression de huit instances demande sa
propre revue.

**La leçon de méthode, elle, n'est pas annulée** : c'est en retirant le correctif
et en relançant le test qu'on apprend s'il prouve quelque chose. Un test écrit
après le correctif et jamais rejoué sans lui ne dit rien — quatre fois sur ce
chantier, il ne disait rien.

**La ligne « bande KPI : filtré » était fausse, et c'est le défaut de cette rule
qu'elle décrit ailleurs.** `computeResultatsKpis()` appelle
`getPreCalculatedResults()` **d'abord**, et ne retombe sur le calcul filtré que
`if (empty($moyennes))`. Or `esbtp_resultats` est peuplée à chaque génération de
bulletin **et** à chaque sauvegarde de note : sur toute instance en service, le
chemin filtré n'était jamais emprunté. Une ligne de tableau qui absout ferme
l'enquête suivante — c'est exactement ce que cette rule reproche à ses versions
antérieures.

**Et le défaut qu'elle cachait était plus gros que la fuite.** `esbtp_resultats`
porte **une ligne par matière** ; la boucle faisait
`$moyennes[$etudiantId] = $resultat->moyenne` sur chacune. La **dernière matière
lue** devenait donc la « moyenne générale » de l'élève, sans pondération ni ordre.
Mesuré en test : avec une matière BTS à 14 (coef 2) et une ECUE à 4 (coef 1)
créée après, la bande KPI affichait **4,00**. Elle agrège désormais par élève,
pondérée par le coefficient de la ligne. Le `rang` n'est plus relu du tout : c'est
un rang **par matière**, et le prendre pour un rang de classe était le même défaut.

**Le mode « Toutes les classes » rendait le filtre inerte.** Le commentaire de
`calculateStudentStatsFixed()` affirmait « les deux appelants passent toujours la
classe ». Faux : `resultats/index.blade.php` porte un `<option value="">Toutes les
classes</option>`, et le contrôleur transmet alors `classe_id = null`. La classe
se résout maintenant **par note**, via `$note->evaluation->classe` — que les deux
appelants eager-loadent déjà, donc sans requête de plus, et c'est plus juste que
le paramètre dans un mode où les élèves viennent de plusieurs classes.

**Pourquoi PAS un scope SQL, alors que ce serait plus court.** La passe 12 a
proposé de remplacer les quatorze filtres PHP par
`ESBTPNote::scopeCoherentesAvecLaClasse()` appuyé sur `contraindreLIncoherence()`.

> **Le premier motif écrit ici était faux, et il a été remplacé.** Il disait
> qu'« un `WHERE` écarte en silence, donc un scope perdrait la trace du couple
> (classe, matière) ». C'est réfutable en une ligne : `contraindreLIncoherence()`
> existe précisément pour **énumérer** les lignes incohérentes, donc un scope qui
> écarte peut être doublé d'une requête qui recense et journalise. Une raison
> fausse invite au mauvais geste — qui la réfute croit avoir levé l'objection et
> refait la passe. C'est l'avertissement que `ESBTPResultat` porte déjà pour
> lui-même.

Les raisons qui tiennent, elles, sont des raisons de **portée** :

- `calculateStudentStatsFixed()` reçoit `$notes` **en paramètre**, déjà
  matérialisée par l'appelant. Un scope sur `ESBTPNote` ne l'atteint pas sans
  changer les deux appelants.
- Plusieurs sites filtrent des **collections en mémoire** (`ReeinscriptionService`,
  les tableaux `$resultatsParMatiere`) : il n'y a pas de requête à scoper.
- Le mémo de dédoublonnage donne **une ligne de journal par couple**, pas par
  requête. Le doubler côté SQL ferait deux mécaniques de trace à tenir d'accord.

Un scope resterait donc une couverture **partielle**, à côté des filtres PHP, pas
à leur place. Ce qui a été retenu de la critique est son vrai fond : **plus aucun
site ne dépend d'un `$classeId` passé en paramètre avec un repli `null`.**

**Et la question qu'il fallait poser avant : contre QUELLE classe juge-t-on une
note ?** Le dépôt avait trois réponses implicites. Elle tient en une ligne, et
c'est celle appliquée partout depuis la passe 13 : **la classe cible quand il y en
a une** (le bulletin qu'on calcule, la classe d'où part la décision), **celle de
la ligne lue sinon**. La différence n'est pas théorique : un élève inscrit la même
année en LMD *et* en BTS a des ECUE parfaitement cohérentes avec leur propre
classe — juger note par note les déclarait valides et les laissait entrer dans sa
moyenne BTS.

**`ReeinscriptionService` n'affiche rien, il DÉCIDE.** Il compte la
matière étrangère dans la moyenne **et** dans les matières échouées : un 4/20 sur
une ECUE peut faire basculer un passage en redoublement, pour un élève comme pour
une promotion entière via la réinscription groupée.

`ESBTPResultatController::resultatEtudiant()` reste le plus piégeux à lire : ses onglets **semestriels** remplacent
entièrement son calcul par le snapshot (donc sains), mais la branche annuelle
`annual_incomplete` ne remplace rien — elle se contente de **renommer** les
libellés. Une ECUE y ressortait dans le tableau et pesait dans la moyenne du pied,
pendant que le KPI d'en-tête affichait la valeur filtrée. Deux chiffres
contradictoires sur un seul écran.

**La branche `annuel` est filtrée mais pas cadrée, et c'est écrit exprès.** Son
jumeau `BtsCurrentResultSnapshotService::buildSemesterSnapshot()` porte trois
portées qu'elle n'a pas : `annee_universitaire_id`, `classe_id`, et
`status != 'cancelled'`. Pour `periode = 'annuel'`, son `$semestre` vaut `'1'` :
elle ramasse **toutes** les notes de semestre 1 de l'élève, toutes années et
toutes classes confondues — l'année précédente d'un redoublant y entre, et une
évaluation annulée aussi. Et son résultat est **écrit** dans
`esbtp_bulletins.moyenne_generale`.

Ce n'est **pas** une régression du chantier : ces notes passaient déjà avant lui.
Le cadrage est un changement de comportement sur une valeur écrite — il demande
sa propre mesure et sa propre entrée de journal des versions, pas d'être glissé
dans un correctif de fuite. Il est reporté, commenté sur place, et
`docs/api/CLI_COHERENCE_SYSTEME.md` corrige le conseil qui disait qu'annuler une
évaluation suffisait (vrai du chemin de génération, faux de celui-ci).

**Ce qui n'est PAS concerné, et pourquoi** — utile pour ne pas les « corriger »
par réflexe, comme le piège #14 l'a déjà fait payer : `BulletinService::calculerMoyenneGenerale()`
et `ESBTPBulletin::calculerMoyenneGenerale()` agrègent `esbtp_resultats_matieres`,
c'est-à-dire une table **écrite** par la génération déjà filtrée ; `ESBTPPDFService::genererEvaluationPDF()`
moyenne les notes d'**une seule** évaluation, donc d'une seule matière ;
`ESBTPEtudiantController` moyenne des bulletins ; `JuryPvSnapshotBuilder` est LMD.
(Une revue a par ailleurs cité `ESBTPBulletin::calculerMoyenneGeneraleSansSauvegarde()` :
cette méthode n'existe pas dans le dépôt.)

**Le tamis, à rejouer plutôt qu'à croire** — il liste les fichiers qui lisent des
lignes par matière *et* en tirent une moyenne. Il rend **34 fichiers**, dont la
plupart sont hors sujet : c'est un point de départ pour la lecture, pas un
verdict. Un fichier qu'il ne rend pas n'est pas pour autant innocent.

```bash
grep -rlE "ESBTPNote::|ESBTPResultat::|->notes\b|->resultats\b" app/ --include="*.php" \
  | xargs grep -lE "avg\(|sommeCoef|sommeCoefficients|total_coefficients|totalCoefficients|moyenne" 2>/dev/null \
  | grep -viE "lmd" | sort
```

**Le contrôle qui décide, lui, se fait à la lecture** : pour chaque fichier rendu,
compter ses **chemins d'ingestion**, pas ses méthodes. Un `foreach` qui écrit dans
le même tableau qu'un autre `foreach` est un second chemin, même trente lignes
plus bas — et s'il **écrase** au lieu d'ajouter, filtrer le premier seul revient
à n'en filtrer aucun.

**Corollaire de méthode.** Un filtre large de tests qui ne bouge pas prouve
l'absence de régression, **jamais** la présence d'une couverture. Le quatrième
lecteur a survécu à une passe entière parce que `grep -rn "calculateStudentStatsFixed" tests/`
rendait zéro, et qu'un « 327 tests, chiffres identiques » avait tenu lieu de preuve.

### Recenser la famille 2

```bash
klassci diagnostics:evaluation-system-mismatch <tenant>
# ou : GET /api/cli/diagnostics/evaluation-system-mismatch
```

Depuis septembre 2026 la réponse porte **deux** blocs : `details` (évaluations) et
`moyennes_manuelles` (`esbtp_resultats`). La version antérieure ne voyait que le
premier **et a été prise pour l'inventaire complet** — c'est ce qui a laissé la
famille des moyennes manuelles hors de tout recensement.

**Le sort des notes trouvées est une décision d'école, pas de code** (`rien-en-dur.md`) :
les rebasculer vers la bonne matière (`POST /api/cli/evaluations/{id}/matiere`,
qui existe et gère la colonne dénormalisée `esbtp_notes.matiere_id`), déplacer
l'évaluation vers sa classe LMD, ou l'annuler (`status = cancelled`, déjà exclu du
bulletin). Ne les efface jamais d'office.

### Ce qui n'est PAS vérifié

`esbtp_planifications_academiques` est bien peuplée avec des ECUE par
`LMDImportService::upsertPlanification()`, et ses lecteurs BTS sont scopés sur
`(filiere_id, niveau_etude_id)`. L'argument « les niveaux LMD et BTS sont
disjoints, donc c'est sain » **a exactement la forme de celui qui s'est révélé
faux pour la famille 1** (« l'import LMD ne peuple pas ce pivot » — vrai de
l'import, faux des autres écrivains). Traite-le comme *non vérifié*, pas comme
*sain* : le contrôle à faire est de chercher un couple (ECUE, niveau de type BTS)
dans cette table, pas de relire le code de l'import.

## Compter ce qui est déjà en base

Le garde protège l'avenir ; il n'efface rien. À faire sur chaque instance ayant
importé des maquettes LMD :

```sql
SELECT mfn.filiere_id, mfn.niveau_etude_id, m.id, m.code, m.name
  FROM esbtp_matiere_filiere_niveau mfn
  JOIN esbtp_matieres m ON m.id = mfn.matiere_id
 WHERE m.unite_enseignement_id IS NOT NULL;
```

Chaque ligne rendue sort sur un bulletin BTS. Retrait par l'écran de
classification (bloc « éléments LMD »), ou par `POST /api/cli/bts/maquette/retirer`.

**Et le retrait ne suffit pas à rendre le nettoyage durable.**
`LiaisonsDeMatiere::retirer()` ne touche volontairement pas les pivots plats :
leur charge utile (`coefficient`, `heures_cours`) ne se retrouve nulle part
ailleurs. Une ECUE retirée de la maquette garde donc ses lignes dans
`esbtp_matiere_filiere` et `esbtp_matiere_niveau`, et `sync:matiere-filiere-niveau`
la recréerait — c'est pour ça que cette commande a désormais son propre garde.
Pour vérifier ce qui reste :

```sql
SELECT m.id, m.code, m.name
  FROM esbtp_matieres m
 WHERE m.unite_enseignement_id IS NOT NULL
   AND (EXISTS (SELECT 1 FROM esbtp_matiere_filiere f WHERE f.matiere_id = m.id)
     OR EXISTS (SELECT 1 FROM esbtp_matiere_niveau n WHERE n.matiere_id = m.id));
```

## Garde-fou avant d'appliquer

Avant d'ajouter `whereNull(...)` à une page, vérifie qu'elle est **BTS-strict** :
- Les notes/évaluations classiques (`ESBTPEvaluationController`, `ESBTPNoteController`) = BTS (le LMD a `ESBTPLMDNoteController` séparé) → filtre OK.
- **Présences / attendance** : peuvent légitimement concerner des ECUE LMD → NE PAS filtrer sans confirmer que le contexte est BTS-only (sinon tu casses l'attendance LMD).
- En cas de doute, demander avant de filtrer.

## Sites déjà corrigés (référence)

- `ESBTPMatiereController` (liste matières BTS) — pattern d'origine
- `ESBTPEvaluationController` (index/create/edit) — juin 2026
- `ESBTPNoteController` (notes.index ×2) — juin 2026
- Inverses LMD : `ESBTPLMDNoteController`, `ESBTPTpeDeclarationController` (`whereNotNull`)

## Voir aussi

- `.claude/rules/lmd-bts-matieres-single-source.md` — source canonique des matières d'une classe (MatiereTreeBuilder)
- `.claude/rules/lmd-bts-bulletin-separation.md` — séparation stricte BTS / LMD
- `.claude/rules/lmd-cli-maquette-import.md` — import des maquettes (origine des ECUE)

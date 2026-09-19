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

**Ce n'est PAS le goulot unique, et cette rule l'a affirmé à tort.** Trois
écrivains touchent le pivot canonique sans passer par lui — la phrase a été
écrite sans être mesurée, exactement comme celle qu'elle remplaçait :

| écrivain | garde |
|---|---|
| `app/Console/Commands/SyncMatiereFilireNiveau.php` (`insert()` brut) | **le sien**, ajouté en même temps que cette ligne |
| `app/Domain/BtsTroncCommun/ChargementDeMaquette.php` (`updateOrCreate`) | par l'**ordre** : `poser()` lève d'abord. Fragile, commenté sur place |
| `database/seeders/Demo/PromotionPrecedenteNotesDemoData.php` | aucun — données de démonstration |

La commande qui rejoue cette liste, au lieu de la croire :

```bash
grep -rn "esbtp_matiere_filiere_niveau\|ESBTPMatiereFilierNiveau" app/ database/ --include="*.php" \
  | grep -E "::(insert|create|upsert|updateOrCreate|firstOrCreate|firstOrNew)\(|->insert\(|new ESBTPMatiereFilierNiveau" \
  | grep -vE "LiaisonsDeMatiere\.php|database/migrations/|^\S+:[0-9]+:\s*(\*|//)"
```

Au 19 septembre 2026, il rend **exactement les trois lignes du tableau**.

Le motif cherche les **créateurs** : `update()` et `delete()` sur une ligne
existante ne posent pas de nouvelle ligne et ne sont pas concernés. Il exclut
aussi les lignes de commentaire — une première version les comptait, et rendait
quatre lignes pour trois écrivains, ce qui rendait son propre seuil faux.

Toute ligne rendue est un écrivain à garder. Si elle en rend plus de trois,
l'inventaire ci-dessus est périmé — corrigez-le plutôt que de le contourner.

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

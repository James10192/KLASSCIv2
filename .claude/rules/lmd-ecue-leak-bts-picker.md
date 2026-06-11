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
- **Listing scopé filière+niveau via `liaisonsFilieresNiveaux`** (pivot 3-way `esbtp_matiere_filiere_niveau`) → **PAS de fuite** (l'import LMD ne peuple pas ce pivot) → laisser.
- ⚠️ `whereHas('filieres')` seul (pivot 2-way `esbtp_matiere_filiere`) → **PEUT fuiter** (l'import LMD peuple `esbtp_matiere_filiere`). Vérifier : si pas aussi `whereHas('niveaux')`, ajouter `whereNull('unite_enseignement_id')`.

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

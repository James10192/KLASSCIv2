# Rule: LMD + BTS — Single Source of Truth pour matières d'une classe

## Quand s'active

Cette rule s'active automatiquement quand tu :
- Crées ou modifies un controller / service / vue qui doit **lister les matières d'une classe**
- Travailles sur un fichier dans `app/Http/Controllers/ESBTP*Controller.php` qui touche aux séances, planifications, notes, bulletins
- Travailles sur `app/Http/Controllers/ESBTPEmploiTempsController.php` ou `ESBTPSeanceCoursController.php`
- Vois `whereHas('filieres')`, `whereHas('niveaux')`, `$classe->matieres()` dans le code

## Règle fondamentale

**Tout consommateur de "matières d'une classe" DOIT passer par `App\Services\LMD\MatiereTreeBuilder`.**

C'est la **Single Source of Truth** validée par toutes les investigations depth=5+ du chantier emploi-temps LMD unification (mai 2026).

## API canonique

Le service expose **2 méthodes publiques distinctes** (jamais flag boolean) :

```php
use App\Services\LMD\MatiereTreeBuilder;

// SANS volumeBudget (heures réalisées CM/TD/TP)
// Pour : bulk-edit, addSession, seances-cours/edit, formulaires planification
$planificationData = app(MatiereTreeBuilder::class)
    ->buildForPlanning($planificationData, $classe);

// AVEC volumeBudget
// Pour : emploi-temps/show, dashboards KPI réalisation
$planificationData = app(MatiereTreeBuilder::class)
    ->buildWithVolumeBudget($planificationData, $classe, $annee);

// Helper pour classes.show tab Suivi heures
$lmdMatieres = app(MatiereTreeBuilder::class)
    ->loadLmdMatieresForClasse($classe);
```

## Pourquoi cette rule existe

**Incident fondateur (mai 2026)** : 4 bugs racines découverts simultanément :
- `bulk-edit` affiche "Planification non configurée — 0 matières" pour LICENCE 3 DROIT PRIVE A (presentation) malgré LMD planning configuré
- `addSession()` utilise `whereHas('filieres')` BTS-only → 0 matières pour toute classe LMD
- `seances-cours/edit` même problème
- Une duplication code `MatiereTreeBuilder::overridePlanificationForLmd()` (service) vs méthode privée `ESBTPEmploiTempsController::overridePlanificationForLmd()` (line 1068)

Rule globale `klassci-classe-matieres.md` documentait déjà le pattern canonique (source `ESBTPPlanificationAcademique`) mais sans enforcement, 3 controllers le contournaient encore.

**Cette rule applique l'enforcement strict.**

## Comment appliquer concrètement

### ✅ BON

```php
public function show(Request $request, ESBTPEmploiTemps $emploi_temp)
{
    $planificationData = $this->getPlanificationDataForClasse(
        $emploi_temp->classe, $emploi_temp->annee, $emploi_temp->semestre
    );

    if (($emploi_temp->classe->systeme_academique ?? '') === 'LMD') {
        $planificationData = app(MatiereTreeBuilder::class)
            ->buildWithVolumeBudget($planificationData, $emploi_temp->classe, $emploi_temp->annee);
    }

    return view('esbtp.emploi-temps.show', compact('planificationData', 'emploi_temp'));
}
```

### ❌ ANTI-PATTERNS À BLOQUER EN REVIEW

1. **`$classe->matieres()`** direct dans un nouveau code :
```php
// ❌ INTERDIT
$matieres = $classe->matieres;
```

2. **`whereHas('filieres')` + `whereHas('niveaux')`** sur `ESBTPMatiere` :
```php
// ❌ INTERDIT — BTS-only, 0 résultats pour LMD
ESBTPMatiere::where('is_active', true)
    ->whereHas('filieres', fn ($q) => $q->where('id', $filiereId))
    ->whereHas('niveaux', fn ($q) => $q->where('id', $niveauId))
    ->get();
```

3. **Duplication privée de `overridePlanificationForLmd()`** dans un Controller (la duplication a été supprimée en PR2 du chantier 2026-05).

4. **Flag boolean `bool $includeVolumeBudget`** sur un appel au service :
```php
// ❌ INTERDIT — anti-pattern : un caller oubliera le param
app(MatiereTreeBuilder::class)->build($data, $classe, includeVolumeBudget: true);

// ✅ CORRECT — méthodes publiques distinctes
app(MatiereTreeBuilder::class)->buildWithVolumeBudget($data, $classe, $annee);
```

5. **Cascade `if-else` sur `systeme_academique`** dans 3+ fichiers différents — encapsuler dans le service.

## Fallbacks acceptables

Si le service retourne une collection vide (rare cas tronc commun mention sans planifs), tu PEUX fallback sur le pivot BTS legacy POUR COMPATIBILITÉ :

```php
$matieres = app(MatiereTreeBuilder::class)
    ->buildForPlanning($planificationData, $classe)['matieres_planifiees'] ?? collect();

if ($matieres->isEmpty()) {
    // Fallback pivot BTS legacy
    $matieres = $classe->matieres()
        ->orderBy('name')
        ->get(['esbtp_matieres.id', 'esbtp_matieres.name']);
}
```

## Audit avant tout commit

```bash
# Aucun nouveau call site interdit
grep -rn '$classe->matieres' app/Http/Controllers/
grep -rn "whereHas('filieres')" app/Http/Controllers/
grep -rn "whereHas('niveaux')" app/Http/Controllers/
```

Si grep trouve nouvelle occurrence non documentée → BLOQUE le commit.

## Voir aussi

- Memory projet : `feedback_matiere_tree_builder_canonical.md`
- Memory projet : `feedback_strangler_fig_refactor.md`
- Rule globale : `~/.claude/rules/klassci-classe-matieres.md`
- Master plan : `docs/MASTER-PLAN-emploi-temps-lmd-unification.md`
- Service : `app/Services/LMD/MatiereTreeBuilder.php`
- Skill : `klassci-lmd-bts-audit` (grep automatisé)

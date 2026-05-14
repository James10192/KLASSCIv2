# Rule: Pre-merge checklist — éviter les gap-fills post-merge

## Quand s'active

Cette rule s'active dès qu'une PR sur KLASSCI touche **au moins un** de ces patterns :
- Un fichier dans `app/Models/`
- Un fichier dans `app/Http/Requests/` (FormRequest)
- Un fichier dans `app/Services/` ou `app/Domain/**/Services/`
- Une nouvelle migration dans `database/migrations/` qui modifie un schéma déjà utilisé en prod
- Un nouvel Enum dans `app/Enums/`
- Une vue Blade qui rend des données de ces modèles (badges, filtres, listings)

## Pourquoi cette rule existe

**Incident fondateur** : feat/385-lmd-volume-tracking — 7 PRs mergées en chaîne (`#386 → #392`) entre le 11 et le 13 mai 2026 sans tests Feature, sans visual-check post-merge, sans audit 4-axes systématique. Résultat :

- 5 gaps livrés (G1 audit, G2 conditional, G3 cache, G4 cascade UPPERCASE, G5 tests) découverts par audit post-mortem
- 1 bug **live en prod** non détecté pendant 2 jours sur 4 tenants : `seances-cours/index.blade.php` filtre et badges cassés (literals lowercase `'cours'/'td'/...` vs valeurs DB UPPERCASE après backfill)
- Discipline reactive (gap-fill) au lieu de preventive (test pre-merge)

Coût : ~4h de plan + agents + ultrathink pour réparer ce qui aurait été 1h de tests avant merge.

## Les 6 commandements pre-merge

### 1. Test Feature obligatoire si on touche un FormRequest

Une PR qui ajoute/modifie un `app/Http/Requests/*.php` DOIT inclure `tests/Feature/<Domain>/*Test.php` qui vérifie au minimum :
- Le happy path (validation passe avec données valides)
- 1 cas d'échec (validation rejette le bon scenario)
- Si le FormRequest a une logique conditionnelle (`Rule::when`, closure) — un test par branche

### 2. Test Unit obligatoire pour tout nouvel Enum

`app/Enums/*.php` ajouté ou modifié → `tests/Unit/Enums/*Test.php`. Au minimum :
- `values()` retourne le bon nombre de cases
- Toutes les méthodes statiques (`fromLegacy`, `selectOptions`, `badgeStyles`...) testées
- Edge cases (null, empty, valeur inconnue, ambiguïté de casse)

### 3. Test Feature obligatoire si on ajoute Auditable à un Model

`implements Auditable` ajouté → test qui vérifie :
- Update du Model crée une entrée `audits`
- Les colonnes de la whitelist (`$auditInclude`) sont bien dans `new_values`
- Les colonnes hors whitelist N'apparaissent PAS
- Bulk operations (boucle `->save()`) ne créent pas d'audit si `$auditEvents` les exclut

### 4. Test du Service avec cache

Un Service qui implémente `Cache::remember()` → test avec `Cache::shouldReceive('remember')->once()` pour confirmer le cache est invoqué, ET `Cache::shouldReceive('forget')` pour l'invalidation.

### 5. Visual-check obligatoire pour les vues touchées

Toute PR qui modifie une vue Blade DOIT inclure une capture d'écran OU une exécution `/visual-check <route>` qui prouve :
- La page rend sans erreur 500
- Les badges/filtres/listings affichent les valeurs attendues
- Le design respecte rules `premium-redesign` + `premium-selects` (monochrome bleu, pas de purple/multicolore)

### 6. Audit 4-axes mental (rule sœur `pre-commit-quality-gate`)

Avant de mettre une PR en review, l'auteur DOIT auditer mentalement :
- **Architecture** — pattern bien choisi ? Pas de god-class aggravée ?
- **Quality vs Speed** — tests planifiés ? Pas de dette technique invisible ?
- **Production-grade** — multi-tenant non-régressif ? Pas de hardcode ? Cache driver compatible (file ≠ tags) ?
- **SOLID** — interfaces préservées ? OCP respecté ?

Et le critic catch les phantom problems (G2 du feat/385 = conditional validation sur un set vide).

## Anti-patterns à BLOQUER en review

1. ❌ PR qui ajoute un Model field utilisé en agrégat sans test du chemin Service → Vue
2. ❌ PR qui modifie les valeurs canoniques d'un Enum (lowercase → UPPERCASE) sans audit grep des consommateurs (Blade, controllers, seeders)
3. ❌ PR qui ajoute Cache::tags() sans vérifier `CACHE_DRIVER` du tenant cible (file/database = tags interdits)
4. ❌ PR qui ajoute Auditable sans whitelist `$auditInclude` (table audits explose)
5. ❌ PR qui ajoute conditional FormRequest sans test des 2 branches (BTS pass + LMD fail)
6. ❌ PR qui ajoute un filtre Blade hardcoded au lieu de boucler sur `Enum::cases()`/`Enum::selectOptions()`

## Cas particulier : migration de valeurs DB existantes

Quand une migration modifie les valeurs déjà persistées (UPPERCASE backfill, normalisation, etc.) :

1. Grep dans `app/Http/Controllers/`, `resources/views/`, `app/Services/`, `tests/` toutes les comparaisons avec les anciennes valeurs
2. Documenter les sites à mettre à jour dans la description de PR
3. Mettre à jour ces sites dans la MÊME PR que la migration (pas une PR séparée — sinon bug latent)
4. Test de régression : 1 test qui vérifie que les vues continuent à rendre correctement après la migration

## Process recommandé (humain, pas hook CI)

Marcel a explicitement préféré un process humain documenté plutôt qu'un hook CI lourd. La discipline est portée par :
- Cette rule projet (`.claude/rules/pre-merge-checklist.md`) chargée automatiquement à chaque session Claude Code
- Le skill `/plan-and-confirm` qui exige le 4-axis audit avant code
- Le skill `/pr-review-toolkit:review-pr` qui appelle 4 agents post-PR (code, tests, silent-failures, types)
- La discipline `/simplify` + `/visual-check` après chaque PR mergée

## Voir aussi

- Rule globale `pre-commit-quality-gate.md` — audit 4-axes avant commit
- Rule projet `feature-delivery-methodology.md` — méthodologie 13 phases
- Rule projet `multi-agent-git-safety.md` — discipline parallel agents
- Mémoire `feedback_defer_gaps_lmd_volume.md` — pourquoi G1/G3 ont été deferé (conditions de réveil mesurables)

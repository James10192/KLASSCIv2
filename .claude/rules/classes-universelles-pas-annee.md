# Rule: Classes universelles — pas liées à une année universitaire

## Quand s'active

Cette rule s'active quand tu touches à :
- `app/Models/ESBTPClasse.php`
- Service / Controller qui filtre les `esbtp_classes` par `annee_universitaire_id`
- Vue qui affiche / liste / suggère des classes en filtrant par année
- Toute query `whereHas('classe', $q => $q->where('annee_universitaire_id', ...))` sur paiement, inscription, note, etc.
- Toute logique « classes candidates » / orientation BTS TC / spécialisation / mapping de classes
- Toute migration qui (re)introduit `annee_universitaire_id` sur `esbtp_classes`

## Règle fondamentale

**Une classe KLASSCI est universelle.** Elle représente une cohorte/spécialité métier (ex: « BATIMENT A », « TRAVAUX PUBLICS D », « TRONC COMMUN G ») qui existe indépendamment d'une année universitaire spécifique. Une même classe peut accueillir des étudiants pendant 10 ans consécutifs sans qu'on en duplique l'identité.

C'est l'**inscription** (`esbtp_inscriptions`) qui porte le `annee_universitaire_id`. Filtrer par année se fait via la relation `inscriptions.annee_universitaire_id`, JAMAIS sur la classe elle-même.

## Pourquoi cette rule existe

Marcel a explicitement confirmé (juin 2026, audit page `/esbtp/filieres/{id}`) :

> « LES CLASSES EN KLASSCI NE SONT PAS LIÉES À UNE ANNÉE UNIVERSITAIRE. […] Si ça
> fonctionne actuellement c'est probablement parce que le schéma legacy stocke par
> erreur une `annee_universitaire_id` sur `esbtp_classes` — à vérifier. »

Symptôme historique : sur `/esbtp/filieres/{id}` section « Sorties BTS Tronc Commun », chaque classe TC affichait le warning :

> « Aucune classe candidate (même niveau + même année universitaire, filière non-TC). Créez d'abord les classes de spécialité. »

→ La filière TC était de l'année N-1, les classes de spécialité étaient de l'année N. Le filtre `annee_universitaire_id` excluait alors toutes les classes valides.

## État DB legacy à connaître

La table `esbtp_classes` contient une colonne `annee_universitaire_id` héritée d'une migration historique (`2024_03_21_000003_add_annee_universitaire_id_to_esbtp_classes_table.php`). Cette colonne est `noise` métier :
- Elle peut être lue pour de l'affichage informatif (« dernière année connue »), mais N'EST PAS un critère de filtre métier.
- À terme : migration de nettoyage `drop_annee_universitaire_id_from_esbtp_classes_table`.
- Tant qu'elle existe, ne JAMAIS la lire pour gating un comportement (candidates, suggestions, mapping, autorisation).

## Patterns corrects

```php
// ✅ Classes candidates pour orientation TC (même niveau, filière non-TC)
ESBTPClasse::query()
    ->where('niveau_etude_id', $classeTroncCommun->niveau_etude_id)
    ->whereHas('filiere', fn ($q) => $q->where('is_tronc_commun', false))
    ->where('is_active', true)
    ->get();

// ✅ Classes ayant des inscriptions actives pour une année donnée
ESBTPClasse::query()
    ->whereHas('inscriptions', fn ($q) => $q->where('annee_universitaire_id', $anneeId)
                                            ->where('status', 'active'))
    ->get();

// ✅ Compter étudiants d'une classe pour une année
$count = $classe->inscriptions()
    ->where('annee_universitaire_id', $anneeId)
    ->where('status', 'active')
    ->count();
```

## Anti-patterns à BLOQUER en review

1. ❌ `ESBTPClasse::where('annee_universitaire_id', $anneeId)->get()` — la colonne `annee_universitaire_id` sur `esbtp_classes` est legacy/incorrect, à ne PAS utiliser pour filtrer
2. ❌ `$classe->annee_universitaire_id` utilisé comme attribut métier de filtrage / validation / orientation
3. ❌ Filtre candidates / suggestions / disponibilité de classes par année universitaire (« classes de l'année N seulement »)
4. ❌ Guard `if ((int) $a->annee_universitaire_id !== (int) $b->annee_universitaire_id) { 422 }` entre deux classes (source TC vs target spé, par exemple)
5. ❌ Création d'une "nouvelle" classe identique pour chaque année (BATIMENT A 2024-2025 + BATIMENT A 2025-2026 séparées) → c'est la même classe
6. ❌ Migration ajoutant `annee_universitaire_id` sur `esbtp_classes` (sauf si justification explicite et tracée)

## Audit avant tout commit qui touche aux classes

```bash
# Sites suspects à inspecter
grep -rnE "ESBTPClasse::.*annee_universitaire_id|->where\(['\"]annee_universitaire_id['\"]" app/ resources/views/
```

Chaque match doit être justifié :
- Soit le filtre est sur `esbtp_inscriptions` (correct)
- Soit le filtre est sur `esbtp_classes` → STOP, refactor pour passer par inscription ou retirer le filtre

## Sites historiques fixés (juin 2026)

- `app/Http/Controllers/ESBTPFiliereController.php::show()` — candidats sortie TC
- `app/Http/Controllers/Admin/BtsOrientationTargetController.php::index()` — candidats UI admin
- `app/Domain/BtsTroncCommun/BtsOrientationPolicySupport.php::validateTarget()` — guard orientation
- `app/Console/Commands/BtsTroncCommun/SeedOrientationTargets.php::handle()` — seed auto-detect
- `app/Http/Controllers/API/CLI/CLIBtsTroncCommunController.php` — `classOrientationCheck()` + `addOrientationTarget()`
- `resources/views/esbtp/filieres/show.blade.php` — message warning UX

## Voir aussi

- `.claude/rules/klassci-classe-matieres.md` — matières via planification, pas via classe directe
- `.claude/rules/lmd-bts-matieres-single-source.md` — MatiereTreeBuilder canonical
- `.claude/rules/classe-lmd-filiere-as-mention.md` — convention LMD
- Migration legacy : `database/migrations/2024_03_21_000003_add_annee_universitaire_id_to_esbtp_classes_table.php` (colonne tolérée pour rétro-compat, à nettoyer à terme)

# Rule: Bulletins BTS et LMD — séparation stricte

## Quand s'active

Cette rule s'active quand tu travailles sur :
- `app/Http/Controllers/ESBTPBulletinController.php` (BTS legacy)
- `app/Http/Controllers/ESBTPLMDBulletinController.php` (LMD)
- `app/Services/ESBTPBulletinService.php` ou `app/Services/LMDBulletinService.php`
- `resources/views/esbtp/bulletins/*` ou `resources/views/esbtp/lmd/bulletins/*`
- Migrations qui touchent `esbtp_resultats`, `esbtp_resultats_matiere`, `esbtp_bulletins`, `esbtp_lmd_resultat_ue`, `esbtp_lmd_resultat_ecue`, `esbtp_lmd_bulletins`

## Règle fondamentale

KLASSCI a **2 systèmes de bulletins complètement séparés**. NE PAS unifier ces 2 systèmes même par "DRY".

| | BTS Legacy | LMD UEMOA |
|---|---|---|
| Controller | `ESBTPBulletinController` | `ESBTPLMDBulletinController` |
| Service | (existant intra-controller ou `ESBTPBulletinService`) | `LMDBulletinService` |
| Tables résultats | `esbtp_resultats`, `esbtp_resultats_matiere`, `esbtp_bulletins` | `esbtp_lmd_resultat_ue`, `esbtp_lmd_resultat_ecue`, `esbtp_lmd_bulletins` |
| Vues | `resources/views/esbtp/bulletins/` | `resources/views/esbtp/lmd/bulletins/` |
| Routes | `/esbtp/bulletins/*` | `/esbtp/lmd/bulletins/*` |
| Permission gate | `bulletins.view` | `module.lmd.access` |
| Composants métier | Matières + coefficients + MGA | UE/ECUE/compensation/crédits ECTS/mentions UEMOA |

## Pourquoi cette rule existe

Marcel a explicitement confirmé en Iteration 4 (depth=7+) du chantier emploi-temps LMD : "Pour bulletin LMD c'est aussi à part de BTS". Les logiques métier diffèrent (LMD a compensation intra-UE, note éliminatoire, mentions, crédits ECTS — BTS est plus simple).

`ESBTPLMDBulletinController` filtre déjà `where('systeme_academique', 'LMD')` aux lignes 55 et 69. Mais `ESBTPBulletinController` ne filtre PAS — une classe LMD passée par erreur retourne 0 matières (via `$classe->matieres` direct line 184, 874, 1189) → bulletin vide silencieux.

## Comment appliquer concrètement

### Guard explicite dans ESBTPBulletinController

```php
// app/Http/Controllers/ESBTPBulletinController.php
public function generate(Request $request)
{
    $classe = ESBTPClasse::findOrFail($request->classe_id);

    // GUARD : refuser les classes LMD
    abort_if(
        $classe->systeme_academique === 'LMD',
        422,
        'Cette classe est LMD. Utilisez /esbtp/lmd/bulletins pour générer des bulletins LMD.'
    );

    // ... reste BTS legacy ...
}
```

À appliquer sur :
- `generate()` line 180+
- Autre méthode line 874+
- Autre méthode line 1189+

### Guard dans LMDBulletinService

Déjà filtré côté controller, mais ajouter une assertion défensive en service :

```php
// app/Services/LMDBulletinService.php
public function genererBulletinLMD(int $etudiantId, int $classeId, ...): ESBTPLMDBulletin
{
    $classe = ESBTPClasse::findOrFail($classeId);

    abort_if(
        $classe->systeme_academique !== 'LMD',
        422,
        'Cette classe est BTS. Utilisez ESBTPBulletinService.'
    );

    // ... reste LMD ...
}
```

## Anti-patterns à bloquer en review

1. ❌ **Unifier** les 2 controllers en 1 seul "ESBTPBulletinFactoryController" → architecture métier différente
2. ❌ **Modifier** `ESBTPBulletinService` BTS pour qu'il "fonctionne aussi" pour LMD → 2 codes simples > 1 code complexe
3. ❌ **Passer une classe LMD** dans `/esbtp/bulletins/generate` sans guard 422
4. ❌ **Passer une classe BTS** dans `/esbtp/lmd/bulletins/*` (le controller filtre déjà)
5. ❌ **Migration combinée** qui touche les 6 tables (BTS+LMD) en même temps → 2 migrations séparées préférables
6. ❌ **Vues partagées** entre `bulletins/` et `lmd/bulletins/` → namespace CSS strict (`bul-*` BTS, `lmb-*` LMD)
7. ❌ **`$classe->matieres`** direct dans ESBTPBulletinController (BTS-only même si comportement legacy)
8. ❌ Cross-import `App\Services\LMD\*` dans `ESBTPBulletinController` ou `ESBTPBulletinService`

## Voir aussi

- Memory projet : `feedback_bulletin_bts_lmd_separation.md`
- Master plan : `docs/MASTER-PLAN-emploi-temps-lmd-unification.md` (PR7)
- Rule projet : `lmd-bts-matieres-single-source.md` (rule sœur)
- `app/Models/ESBTPLMDBulletin.php` vs `app/Models/ESBTPBulletin.php`

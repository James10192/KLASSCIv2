# Rule: Bulletins BTS et LMD — séparation stricte

## Quand s'active

Cette rule s'active quand tu travailles sur :
- `app/Http/Controllers/ESBTPBulletinController.php` (BTS legacy)
- `app/Http/Controllers/ESBTPLMDBulletinController.php` (LMD)
- `app/Services/BulletinService.php` (BTS) ou `app/Services/LMDBulletinService.php` (LMD)
- `resources/views/esbtp/bulletins/*` ou `resources/views/esbtp/lmd/bulletins/*`
- Migrations qui touchent `esbtp_resultats`, `esbtp_resultats_matiere`, `esbtp_bulletins`, `esbtp_lmd_resultat_ue`, `esbtp_lmd_resultat_ecue`, `esbtp_lmd_bulletins`

## Règle fondamentale

KLASSCI a **2 systèmes de bulletins complètement séparés**. NE PAS unifier ces 2 systèmes même par "DRY".

| | BTS Legacy | LMD UEMOA |
|---|---|---|
| Controller | `ESBTPBulletinController` | `ESBTPLMDBulletinController` |
| Service | `app/Services/BulletinService.php` | `app/Services/LMDBulletinService.php` |
| Tables résultats | `esbtp_resultats`, `esbtp_resultats_matiere`, `esbtp_bulletins` | `esbtp_lmd_resultat_ue`, `esbtp_lmd_resultat_ecue`, `esbtp_lmd_bulletins` |
| Vues | `resources/views/esbtp/bulletins/` | `resources/views/esbtp/lmd/bulletins/` |
| Routes | `/esbtp/bulletins/*` | `/esbtp/lmd/bulletins/*` |
| Permission gate | `bulletins.view` | `module.lmd.access` |
| Composants métier | Matières + coefficients + MGA | UE/ECUE/compensation/crédits ECTS/mentions UEMOA |

## Pourquoi cette rule existe

Marcel a explicitement confirmé en Iteration 4 (depth=7+) du chantier emploi-temps LMD : "Pour bulletin LMD c'est aussi à part de BTS". Les logiques métier diffèrent (LMD a compensation intra-UE, note éliminatoire, mentions, crédits ECTS — BTS est plus simple).

`ESBTPLMDBulletinController` ne liste que des classes LMD (`where('systeme_academique', 'LMD')`
dans `index()` et sa méthode voisine). Dans l'autre sens, rien ne filtrait : une classe LMD
passée par erreur à `ESBTPBulletinController` produisait un **bulletin vide sans erreur**,
parce que les matières d'une classe LMD ne se lisent pas par le pivot BTS.

## État actuel — ce qui est déjà en place

Les gardes côté BTS **existent** (chantier PR7, `abort_if(… === 'LMD', 422)`), sur les
quatre points d'entrée qui prennent une classe :

| Méthode de `ESBTPBulletinController` | Ce qu'elle fait |
|---|---|
| `store()` | création/configuration d'un bulletin |
| `genererClasseBulletins()` | génération en masse |
| `preflightClasseBulletins()` | contrôle avant génération en masse |
| `previewBulletin()` | aperçu |

Ne les cherchez pas par numéro de ligne : `grep -n "systeme_academique" app/Http/Controllers/ESBTPBulletinController.php`.

**Ce qui reste à faire** : la garde symétrique côté service LMD. `LMDBulletinService`
n'assert rien — si un jour un appelant lui passe une classe BTS, il calculera sur des
UE inexistantes plutôt que de refuser.

```php
// app/Services/LMDBulletinService.php::genererBulletinLMD()
abort_if(
    ($classe->systeme_academique ?? '') !== 'LMD',
    422,
    'Cette classe est BTS. Utilisez BulletinService.'
);
```

## Comment appliquer sur un nouveau point d'entrée

Toute nouvelle méthode de `ESBTPBulletinController` qui reçoit une `classe_id` porte
la même garde, dès sa première version :

```php
$classe = ESBTPClasse::findOrFail($request->classe_id);

abort_if(
    ($classe->systeme_academique ?? '') === 'LMD',
    422,
    'Cette classe est LMD. Utilisez /esbtp/lmd/bulletins pour générer des bulletins LMD.'
);
```

Le `?? ''` n'est pas décoratif : la colonne est nullable, et une classe BTS historique
peut l'avoir nulle. Comparer `$classe->systeme_academique === 'LMD'` sans repli est
juste ; l'écrire avec dit au lecteur suivant que le nul a été envisagé.

## Anti-patterns à bloquer en review

1. ❌ **Unifier** les 2 controllers en 1 seul "ESBTPBulletinFactoryController" → architecture métier différente
2. ❌ **Modifier** `BulletinService` (BTS) pour qu'il "fonctionne aussi" pour LMD → 2 codes simples > 1 code complexe
3. ❌ **Passer une classe LMD** dans `/esbtp/bulletins/generate` sans guard 422
4. ❌ **Passer une classe BTS** dans `/esbtp/lmd/bulletins/*` (le controller filtre déjà)
5. ❌ **Migration combinée** qui touche les 6 tables (BTS+LMD) en même temps → 2 migrations séparées préférables
6. ❌ **Vues partagées** entre `bulletins/` et `lmd/bulletins/` → namespace CSS strict (`bul-*` BTS, `lmb-*` LMD)
7. ❌ **`$classe->matieres`** direct dans `ESBTPBulletinController` — le pivot BTS ne rend rien pour une classe LMD. Passer par `MatiereTreeBuilder` (rule `lmd-bts-matieres-single-source.md`)
8. ❌ Cross-import `App\Services\LMD\*` dans `ESBTPBulletinController` ou `BulletinService`

## Voir aussi

- Memory projet : `feedback_bulletin_bts_lmd_separation.md`
- Master plan : `docs/MASTER-PLAN-emploi-temps-lmd-unification.md` (PR7)
- Rule projet : `lmd-bts-matieres-single-source.md` (rule sœur)
- `app/Models/ESBTPLMDBulletin.php` vs `app/Models/ESBTPBulletin.php`

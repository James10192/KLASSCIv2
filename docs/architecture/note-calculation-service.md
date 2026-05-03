# NoteCalculationService — Service unifié BTS / LMD

> Source : `app/Services/NoteCalculationService.php`
> Tests : `tests/Unit/Services/NoteCalculationServiceTest.php`
> Tests JS↔PHP : `tests/Feature/Notes/JsPhpCalculationConsistencyTest.php`

## Pourquoi ce service

KLASSCI gère deux systèmes académiques en parallèle :

- **BTS classique** : semestres + matières simples (`ESBTPNoteController` + `BulletinService`)
- **LMD** : UE / ECUE / crédits ECTS (`ESBTPLMDNoteController` + `LMDBulletinService`)

Avant ce service, la formule de calcul de moyenne était dupliquée à
**au moins 5 endroits** (BulletinService BTS, LMDBulletinService, controller
preview impact, JS `calculateStudentAverage()` côté UI, exports Excel).

Conséquences observées en avril/mai 2026 :

- Un bug critique (notes 0 exclues côté JS, ignorées côté bulletin) a obligé
  à corriger 2 fois la même formule, dans 2 fichiers.
- Un autre bug (barème 30 non normalisé) a faussé silencieusement les
  bulletins officiels (15/30 + 10/20 = 12.5 au lieu de 10).
- Aucune garantie que BTS et LMD donnent les mêmes moyennes pour les mêmes
  inputs : drift garanti à long terme.

Ce service centralise toute la logique mathématique pour que les deux
modules consomment **strictement la même formule**, testée unitairement
sur 28 cas.

## API publique

Toutes les méthodes sont **stateless** (pas de DB, pas de side-effect).
Le service est résolvable par DI Laravel comme un singleton implicite.

| Méthode | Domaine | Formule |
|---|---|---|
| `studentMatiereAverage(array $notes): float` | BTS + LMD | Σ((note/bareme)*20*coef) / Σ coef |
| `studentGeneralAverage(array $matieres): float` | BTS | Σ(moy_matiere * coef_matiere) / Σ coef_matiere |
| `classEvaluationAverage(array $notes, float $bareme): float` | BTS + LMD | Σ((note/bareme)*20) / count |
| `classMatiereAverage(array $studentAverages): float` | BTS + LMD | Σ moy_etudiant / count_non_null |
| `lmdUEAverage(array $ecues): float` | LMD | Σ(moy_ecue * credits_ecue) / Σ credits_ecue |
| `lmdSemesterAverage(array $ues): float` | LMD | Alias de `lmdUEAverage` (même formule) |
| `lmdCreditsValidated(array $ues, float $threshold = 10): int` | LMD | Σ credits où moy >= seuil |
| `getMention(float $moyenne): string` | BTS + LMD | Mention CAMES (TB/B/AB/P/Insuffisant) |
| `getAppreciation(float $moyenne): string` | BC | Alias de `getMention()` |

### Garanties algorithmiques (toutes méthodes)

1. **Notes 0 légitimes incluses** (anti-bug "filter > 0").
2. **Normalisation systématique sur 20** (`(note / bareme) * 20`).
3. **Absences exclues** (n'affectent ni numérateur ni dénominateur).
4. **Garde-fou** : barème ≤ 0 ou coefficient ≤ 0 = entrée ignorée silencieusement.
5. **Arrondi 2 décimales** systématique sur le résultat final.
6. **Aucune entrée valide → 0.0** (jamais d'exception, jamais de division par zéro).

## Consommateurs migrés (PR initiale, mai 2026)

- `app/Http/Controllers/ESBTPNoteController.php::previewImpact()` — la méthode
  d'aperçu temps réel d'impact d'une note hypothétique consomme désormais
  le service via DI (`NoteCalculationService $calc` injecté en paramètre de
  méthode). Les helpers privés `computeMatiereAverage` et `computeGeneralAverage`
  construisent désormais des arrays normalisés et délèguent au service.
  La méthode `getMention()` privée a été retirée au profit de `$calc->getMention()`.

## Consommateurs à migrer (dette technique tracée)

Ces points sont volontairement **hors scope** de la PR initiale pour ne pas
collisionner avec les PRs en review (#316, #317, #318, #319). Ils doivent
être migrés une fois ces PRs mergées.

| Fichier | Méthode | Pourquoi pas migré tout de suite |
|---|---|---|
| `app/Services/BulletinService.php` | `computeMoyenneFromNotesData()` | Ajoutée par PR #316 (calc fix BTS). Migrer après merge. |
| `app/Services/BulletinService.php` | `calculerMoyennePonderee()` | Logique historique. Vérifier compat avant migration. |
| `app/Services/BulletinService.php` | `calculerMoyenneDepuisNotes()` | Idem. |
| `app/Services/BulletinService.php` | `calculateStudentAverageForPeriode()` | Idem — peut adopter `studentMatiereAverage`. |
| `app/Services/BulletinService.php` | `getAppreciation()` | Doit déléguer à `NoteCalculationService::getAppreciation()`. |
| `app/Services/LMDBulletinService.php` | `calculerMoyenneECUE()` | Doit consommer `studentMatiereAverage()` (même formule). |
| `app/Services/LMDBulletinService.php` | `calculerMoyenneGenerale()` | Doit consommer `lmdSemesterAverage()` ou `lmdUEAverage()`. |
| `app/Services/LMDBulletinService.php` | `determinerMentionUE()` | Logique différente (TB/B/AB/P/INS/F + seuils settings) — **garder spécifique LMD** ou ajouter une variante au service. |
| `resources/views/esbtp/notes/index.blade.php` | `calculateStudentAverage()` JS | Voir section "Cas tests JS↔PHP" ci-dessous. |
| `resources/views/esbtp/notes/index.blade.php` | `calculateClassAverages()` JS | Idem. |
| Exports Excel (`app/Exports/`) | divers | Si calculs inline trouvés, migrer. |

### Recommandation LMD

La formule LMD est légèrement différente des moyennes BTS car :

- ECUE → UE : pondéré par `coefficient_ecue` (pas par crédits)
- UE → semestre : pondéré par `credits` ECTS

Le service expose les deux variantes :

- Pour ECUE → UE (pondération coefficient), utiliser **`studentMatiereAverage`**
  en passant `coefficient` comme coef ECUE (déjà ce qui est fait dans
  `LMDBulletinService::calculerResultatUE()` avec une boucle inline — peut
  être remplacé directement par un appel au service).
- Pour UE → semestre (pondération crédits ECTS), utiliser **`lmdSemesterAverage`**
  ou **`lmdUEAverage`** (alias) avec `credits` au lieu de `coefficient`.

## Cas tests JS ↔ PHP (contrat de cohérence)

10 cas sont définis dans `tests/Feature/Notes/JsPhpCalculationConsistencyTest.php`
comme contrat de référence. Toute évolution future de la formule de moyenne
DOIT mettre à jour ces 10 cas ET vérifier que la fonction JS
`calculateStudentAverage()` produit les mêmes résultats.

| # | Label | Inputs (notes) | Attendu |
|---|---|---|---|
| 1 | zero-grade-included | 10/20 + 0/20 (coef 1) | 5.0 |
| 2 | bareme-normalization | 15/30 + 10/20 (coef 1) | 10.0 |
| 3 | absent-excluded | 12/20 + (0/20, absent) | 12.0 |
| 4 | coefficient-applied | 8/20 coef 3 + 16/20 coef 1 | 10.0 |
| 5 | mixed-baremes-coefs | 12/20 coef 2 + 30/40 coef 3 | 13.8 |
| 6 | invalid-bareme-skipped | 10/0 + 14/20 | 14.0 |
| 7 | decimal-grades | 12.5/20 + 13.75/20 | 13.13 |
| 8 | empty-returns-zero | (aucune note) | 0.0 |
| 9 | single-note | 17/20 | 17.0 |
| 10 | string-inputs | "14"/20 coef 2 + "8"/20 coef 1 | 12.0 |

### Esquisse Jest / Vitest pour la suite frontend (PR future)

```js
import { calculateStudentAverage } from '@/lib/notes-calc';

describe('JS ↔ PHP consistency', () => {
  test.each([
    ['cas-01', [{note:10,bareme:20,coefficient:1,is_absent:false},{note:0,bareme:20,coefficient:1,is_absent:false}], 5.0],
    ['cas-02', [{note:15,bareme:30,coefficient:1,is_absent:false},{note:10,bareme:20,coefficient:1,is_absent:false}], 10.0],
    // ... 8 autres cas ...
  ])('%s', (label, notes, expected) => {
    expect(calculateStudentAverage(notes)).toBe(expected);
  });
});
```

## Évolutions futures à considérer

- Ajout d'une méthode `studentMatiereAverageWithDetails(array $notes): array`
  qui retourne `{ moyenne, count_used, count_excluded, mention }` pour les
  besoins UI ou debug.
- Variante `lmdMentionThreshold(float $moy, array $thresholds): string` pour
  remplacer `LMDBulletinService::determinerMentionUE()` qui lit ses seuils
  depuis les settings tenant — à garder configurable.
- Extraction d'un `BulletinDataAssembler` séparé pour la composition
  matières → bulletin (orchestration), distincte du service de calcul pur.
